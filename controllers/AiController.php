<?php
/**
 * AI Controller for Enterprise features
 */
class AiController extends Controller {

    protected $allowedActions = ['scan_invoice'];

    public function scan_invoice() {
        // Authenticate request
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid method.'], 405);
        }

        if (!isset($_FILES['invoice_file']) || $_FILES['invoice_file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Please upload a valid image or PDF.'], 400);
        }

        $fileTmpPath = $_FILES['invoice_file']['tmp_name'];
        $mimeType = mime_content_type($fileTmpPath);
        $fileContent = file_get_contents($fileTmpPath);
        $base64Data = base64_encode($fileContent);

        // Map MIME type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        if (!in_array($mimeType, $allowedMimes)) {
            $this->json(['success' => false, 'message' => 'Only JPEG, PNG, WEBP, and PDF are allowed.'], 400);
        }

        $project_id = 'ramswaroop-ai';
        $location   = 'us-central1';
        $model      = 'gemini-2.5-flash';
        $key_file   = BASE_PATH . '/storage/service-account.json';

        if (!file_exists($key_file)) {
            $this->json(['success' => false, 'message' => 'AI Service Account key missing.'], 500);
        }

        // Generate JWT token for Google Auth
        $key = json_decode(file_get_contents($key_file), true);
        $now = time();
        $h = rtrim(strtr(base64_encode(json_encode(['alg'=>'RS256','typ'=>'JWT'])), '+/', '-_'), '=');
        $c = rtrim(strtr(base64_encode(json_encode([
            'iss' => $key['client_email'], 'sub' => $key['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/cloud-platform'
        ])), '+/', '-_'), '=');
        $sig = '';
        openssl_sign("$h.$c", $sig, $key['private_key'], 'SHA256');
        $jwt = "$h.$c." . rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');

        $chToken = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($chToken, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion'=>$jwt]),
        ]);
        $resToken = json_decode(curl_exec($chToken), true);
        curl_close($chToken);
        
        $accessToken = $resToken['access_token'] ?? '';
        if (!$accessToken) {
            $this->json(['success' => false, 'message' => 'AI Authentication Failed.'], 500);
        }

        // Call Gemini
        $url = "https://{$location}-aiplatform.googleapis.com/v1/projects/{$project_id}/locations/{$location}/publishers/google/models/{$model}:generateContent";
        
        $prompt = "Extract Invoice No, Date (YYYY-MM-DD), Party Name, and ALL Table Items. Each item should have 'name', 'qty', and 'rate'. Return ONLY a valid JSON object with the keys: 'invoice_no', 'date', 'party_name', 'items'. Do not include markdown code block formatting.";

        $data = [
            "contents" => [
                [
                    "role" => "user",
                    "parts" => [
                        ["text" => $prompt],
                        [
                            "inlineData" => [
                                "mimeType" => $mimeType,
                                "data" => $base64Data
                            ]
                        ]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.2,
                "topK" => 32,
                "topP" => 0.95
            ]
        ];

        $chGen = curl_init($url);
        curl_setopt_array($chGen, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$accessToken}",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $response = curl_exec($chGen);
        curl_close($chGen);

        $resJson = json_decode($response, true);
        $textOutput = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Clean text output
        $textOutput = str_replace(['```json', '```'], '', $textOutput);
        $extractedData = json_decode(trim($textOutput), true);

        if (!$extractedData) {
             $this->json(['success' => false, 'message' => 'Failed to parse AI response.', 'raw' => $textOutput], 500);
        }

        $this->json([
            'success' => true,
            'data' => $extractedData
        ]);
    }
}
