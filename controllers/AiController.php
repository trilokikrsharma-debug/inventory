<?php
/**
 * AI Controller — Enterprise AI Sales Assistant
 *
 * Features:
 *   1. Text Parsing  — Natural language sales order parsing (Hindi + English)
 *   2. Voice Input    — Voice-to-text conversion (handled client-side, parsed here)
 *   3. Chit/PO Scan  — Image/PDF OCR extraction of orders
 *   4. Invoice Scan   — Purchase invoice OCR extraction
 *   5. Smart Matching — Auto-matches AI output to existing products & customers in DB
 *
 * Uses Google Vertex AI (Gemini 2.5 Flash) via Service Account JWT auth.
 */
class AiController extends Controller {

    protected $allowedActions = [
        'scan_invoice',
        'scan_sales_order',
        'parse_sales_text',
        'smart_suggest',
    ];

    /** Cached access token for this request cycle */
    private static ?string $cachedToken = null;

    // ─── Gemini Config ───────────────────────────────────────────
    private const PROJECT_ID  = 'ramswaroop-ai';
    private const LOCATION    = 'us-central1';
    private const MODEL       = 'gemini-2.5-flash';
    private const TOKEN_TTL   = 15;   // curl timeout for OAuth token
    private const GEMINI_TTL  = 45;   // curl timeout for Gemini generation
    private const CONNECT_TTL = 5;    // curl connect timeout

    // ─── OAuth2 Token ────────────────────────────────────────────

    /**
     * Get a Google OAuth2 access token via Service Account JWT.
     * Caches the token for the entire PHP request lifecycle.
     */
    private function getAccessToken(): string {
        if (self::$cachedToken !== null) {
            return self::$cachedToken;
        }

        $keyFile = BASE_PATH . '/storage/service-account.json';
        if (!file_exists($keyFile)) {
            error_log('[AI] Service account key file not found: ' . $keyFile);
            return '';
        }

        $key = json_decode(file_get_contents($keyFile), true);
        if (!$key || empty($key['client_email']) || empty($key['private_key'])) {
            error_log('[AI] Invalid service account key file');
            return '';
        }

        $now = time();
        $header  = self::base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims  = self::base64url(json_encode([
            'iss'   => $key['client_email'],
            'sub'   => $key['client_email'],
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
        ]));

        $sig = '';
        if (!openssl_sign("{$header}.{$claims}", $sig, $key['private_key'], 'SHA256')) {
            error_log('[AI] JWT signing failed');
            return '';
        }
        $jwt = "{$header}.{$claims}." . self::base64url($sig);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => self::TOKEN_TTL,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TTL,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
        ]);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log('[AI] OAuth curl error: ' . $err);
            return '';
        }

        $res = json_decode($raw, true);
        self::$cachedToken = $res['access_token'] ?? '';

        if (!self::$cachedToken) {
            error_log('[AI] No access_token in OAuth response: ' . substr($raw, 0, 200));
        }

        return self::$cachedToken;
    }

    // ─── Gemini API Call ─────────────────────────────────────────

    /**
     * Call Vertex AI Gemini model with text prompt + optional file.
     *
     * @return array|null  Parsed JSON from Gemini response, or null on failure
     */
    private function callGemini(string $token, string $prompt, ?string $mimeType = null, ?string $base64Data = null): ?array {
        $url = sprintf(
            'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/%s:generateContent',
            self::LOCATION, self::PROJECT_ID, self::LOCATION, self::MODEL
        );

        $parts = [['text' => $prompt]];
        if ($mimeType && $base64Data) {
            $parts[] = ['inlineData' => ['mimeType' => $mimeType, 'data' => $base64Data]];
        }

        $payload = [
            'contents' => [['role' => 'user', 'parts' => $parts]],
            'generationConfig' => [
                'temperature'     => 0.1,
                'topK'            => 32,
                'topP'            => 0.95,
                'responseMimeType' => 'application/json',
            ],
        ];

        // Release session lock before long API call to prevent hanging other requests
        @session_write_close();

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => self::GEMINI_TTL,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TTL,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$token}",
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        @session_write_close(); // Unlock session while waiting for AI
        $raw = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErr) {
            error_log('[AI] Gemini curl error: ' . $curlErr);
            return null;
        }

        if ($httpCode !== 200) {
            error_log('[AI] Gemini HTTP ' . $httpCode . ': ' . substr($raw, 0, 300));
            return null;
        }

        $res = json_decode($raw, true);
        $text = $res['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if ($text === '') {
            error_log('[AI] Empty Gemini output. Full response: ' . substr($raw, 0, 500));
            return null;
        }

        // Strip any leftover markdown fences
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/i', '', $text);

        $parsed = json_decode(trim($text), true);
        if ($parsed === null) {
            error_log('[AI] Failed to JSON-decode Gemini output: ' . substr($text, 0, 300));
        }

        return $parsed;
    }

    // ─── Smart Product Matching ──────────────────────────────────

    /**
     * Try to match AI-extracted item names against the product database.
     * Returns enriched items with product_id, selling_price, tax_rate if matched.
     */
    private function matchProducts(array $items): array {
        if (empty($items)) return [];

        $companyId = Tenant::id();
        if (!$companyId) return $items;

        $db = Database::getInstance();

        foreach ($items as &$item) {
            $name = trim($item['name'] ?? '');
            if ($name === '') continue;

            try {
                // Fuzzy search: exact match first, then LIKE
                $product = $db->query(
                    "SELECT id, name, selling_price, tax_rate, current_stock
                     FROM products
                     WHERE company_id = ? AND LOWER(name) = LOWER(?)
                     LIMIT 1",
                    [$companyId, $name]
                )->fetch();

                if (!$product) {
                    $product = $db->query(
                        "SELECT id, name, selling_price, tax_rate, current_stock
                         FROM products
                         WHERE company_id = ? AND LOWER(name) LIKE LOWER(?)
                         LIMIT 1",
                        [$companyId, '%' . $name . '%']
                    )->fetch();
                }

                if ($product) {
                    $item['product_id']    = (int)$product['id'];
                    $item['matched_name']  = $product['name'];
                    $item['rate']          = (float)($item['rate'] ?? 0) ?: (float)$product['selling_price'];
                    $item['tax_rate']      = (float)$product['tax_rate'];
                    $item['current_stock'] = (float)$product['current_stock'];
                    $item['matched']       = true;
                } else {
                    $item['matched'] = false;
                }
            } catch (\Exception $e) {
                error_log('[AI] Product match error: ' . $e->getMessage());
                $item['matched'] = false;
            }
        }
        unset($item);

        return $items;
    }

    /**
     * Try to match AI-extracted customer name against the customer database.
     */
    private function matchCustomer(?string $name): ?array {
        if (!$name || trim($name) === '') return null;

        $companyId = Tenant::id();
        if (!$companyId) return null;

        try {
            $db = Database::getInstance();
            $customer = $db->query(
                "SELECT id, name FROM customers
                 WHERE company_id = ? AND LOWER(name) LIKE LOWER(?)
                 ORDER BY CASE WHEN LOWER(name) = LOWER(?) THEN 0 ELSE 1 END
                 LIMIT 1",
                [$companyId, '%' . trim($name) . '%', trim($name)]
            )->fetch();

            return $customer ?: null;
        } catch (\Exception $e) {
            error_log('[AI] Customer match error: ' . $e->getMessage());
            return null;
        }
    }

    // ─── Action: Parse Natural Language Text ─────────────────────

    public function parse_sales_text() {
        $this->requireAuth();
        @set_time_limit(120); // Gemini API needs time

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid method.'], 405);
        }

        $text = trim((string)$this->post('text'));
        if ($text === '') {
            $this->json(['success' => false, 'message' => 'Please enter some text to parse.'], 400);
        }

        $token = $this->getAccessToken();
        if (!$token) {
            $this->json(['success' => false, 'message' => 'AI service unavailable. Please try again later.'], 503);
        }

        $prompt = <<<PROMPT
You are a sales order parser for an Indian business. Parse the following natural language text (which may be in Hindi, Hinglish, or English) into a structured sales order.

Rules:
- Extract customer name if mentioned (after "to", "ko", "ke liye", "ka order", etc.)
- Extract all items with name and quantity
- If price/rate is mentioned, include it as 'rate', otherwise set rate to 0
- Quantity should default to 1 if not specified
- Understand Hindi numerals (ek=1, do=2, teen=3, char=4, paanch=5, chhah=6, saat=7, aath=8, nau=9, das=10)
- Understand Hindi product descriptions (e.g., "chai patti" = tea, "cheeni" = sugar)
- Handle multi-word product names naturally

Input text: "{$text}"

Return a JSON object with exactly these keys:
{
  "customer_name": "string or null",
  "items": [{"name": "string", "qty": number, "rate": number}]
}
PROMPT;

        $data = $this->callGemini($token, $prompt);
        if (!$data) {
            $this->json(['success' => false, 'message' => 'AI could not parse the text. Please try rephrasing.'], 422);
        }

        // Smart-match items and customer against database
        $data['items'] = $this->matchProducts($data['items'] ?? []);
        $customerMatch = $this->matchCustomer($data['customer_name'] ?? null);
        if ($customerMatch) {
            $data['customer_id']   = (int)$customerMatch['id'];
            $data['customer_name'] = $customerMatch['name'];
        }

        $this->logActivity('AI Parse Text', 'ai', null, 'Parsed: ' . substr($text, 0, 100));
        $this->json(['success' => true, 'data' => $data]);
    }

    // ─── Action: Scan Sales Order Image/PDF ──────────────────────

    public function scan_sales_order() {
        $this->requireAuth();
        @set_time_limit(120);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid method.'], 405);
        }

        if (!isset($_FILES['order_file']) || $_FILES['order_file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Please upload a valid image or PDF file.'], 400);
        }

        $fileTmpPath = $_FILES['order_file']['tmp_name'];
        $mimeType    = mime_content_type($fileTmpPath);
        $allowed     = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

        if (!in_array($mimeType, $allowed, true)) {
            $this->json(['success' => false, 'message' => 'Only JPEG, PNG, WEBP and PDF files are supported.'], 400);
        }

        // Check file size (max 10MB)
        if (filesize($fileTmpPath) > 10 * 1024 * 1024) {
            $this->json(['success' => false, 'message' => 'File too large. Maximum 10MB allowed.'], 400);
        }

        $base64Data = base64_encode(file_get_contents($fileTmpPath));

        $token = $this->getAccessToken();
        if (!$token) {
            $this->json(['success' => false, 'message' => 'AI service unavailable. Please try again later.'], 503);
        }

        $prompt = <<<PROMPT
You are an Indian business document scanner. Extract the sales order / purchase order / chit details from this image.

Rules:
- Extract customer/party name if visible
- Extract ALL line items with their name, quantity, and rate/price
- If rate is not visible, set it to 0
- Handle handwritten Hindi/English text
- Handle both printed and handwritten documents
- Ignore headers, footers, stamps — focus on item lines

Return a JSON object with exactly these keys:
{
  "customer_name": "string or null",
  "items": [{"name": "string", "qty": number, "rate": number}]
}
PROMPT;

        $data = $this->callGemini($token, $prompt, $mimeType, $base64Data);
        if (!$data) {
            $this->json(['success' => false, 'message' => 'Could not read the document. Please try a clearer image.'], 422);
        }

        // Smart-match items and customer
        $data['items'] = $this->matchProducts($data['items'] ?? []);
        $customerMatch = $this->matchCustomer($data['customer_name'] ?? null);
        if ($customerMatch) {
            $data['customer_id']   = (int)$customerMatch['id'];
            $data['customer_name'] = $customerMatch['name'];
        }

        $this->logActivity('AI Scan Order', 'ai', null, 'Scanned order document');
        $this->json(['success' => true, 'data' => $data]);
    }

    // ─── Action: Scan Purchase Invoice ───────────────────────────

    public function scan_invoice() {
        $this->requireAuth();
        @set_time_limit(120);

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid method.'], 405);
        }

        if (!isset($_FILES['invoice_file']) || $_FILES['invoice_file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Please upload a valid image or PDF.'], 400);
        }

        $fileTmpPath = $_FILES['invoice_file']['tmp_name'];
        $mimeType    = mime_content_type($fileTmpPath);
        $allowed     = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

        if (!in_array($mimeType, $allowed, true)) {
            $this->json(['success' => false, 'message' => 'Only JPEG, PNG, WEBP and PDF files are supported.'], 400);
        }

        if (filesize($fileTmpPath) > 10 * 1024 * 1024) {
            $this->json(['success' => false, 'message' => 'File too large. Maximum 10MB allowed.'], 400);
        }

        $base64Data = base64_encode(file_get_contents($fileTmpPath));

        $token = $this->getAccessToken();
        if (!$token) {
            $this->json(['success' => false, 'message' => 'AI service unavailable. Please try again later.'], 503);
        }

        $prompt = <<<PROMPT
Extract all details from this purchase invoice / bill.

Return a JSON object with exactly these keys:
{
  "invoice_no": "string or null",
  "date": "YYYY-MM-DD or null",
  "party_name": "string or null",
  "items": [{"name": "string", "qty": number, "rate": number}]
}
PROMPT;

        $data = $this->callGemini($token, $prompt, $mimeType, $base64Data);
        if (!$data) {
            $this->json(['success' => false, 'message' => 'Could not read the invoice. Please try a clearer image.'], 422);
        }

        $this->logActivity('AI Scan Invoice', 'ai', null, 'Scanned purchase invoice');
        $this->json(['success' => true, 'data' => $data]);
    }

    // ─── Action: Smart Product Suggestions ───────────────────────

    /**
     * Quick endpoint for inline product suggestions while typing.
     * Returns top 5 matching products from DB.
     */
    public function smart_suggest() {
        $this->requireAuth();

        $term = trim((string)$this->get('term'));
        if (strlen($term) < 2) {
            $this->json(['success' => true, 'data' => []]);
        }

        $companyId = Tenant::id();
        if (!$companyId) {
            $this->json(['success' => true, 'data' => []]);
        }

        try {
            $db = Database::getInstance();
            $products = $db->query(
                "SELECT id, name, selling_price, tax_rate, current_stock
                 FROM products
                 WHERE company_id = ? AND LOWER(name) LIKE LOWER(?)
                 ORDER BY name ASC
                 LIMIT 5",
                [$companyId, '%' . $term . '%']
            )->fetchAll();

            $this->json(['success' => true, 'data' => $products]);
        } catch (\Exception $e) {
            $this->json(['success' => true, 'data' => []]);
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private static function base64url(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
