<?php
/**
 * BlogController – Public SEO support articles.
 */
class BlogController extends Controller {
    protected $allowedActions = ['index', 'show'];

    public function index(): void {
        $baseUrl = rtrim(APP_URL, '/');
        $articles = $this->articles();
        $items = [];
        foreach ($articles as $slug => $article) {
            $items[] = [
                'slug' => $slug,
                'title' => $article['title'],
                'heading' => $article['heading'] ?? $article['title'],
                'description' => $article['description'],
                'url' => $baseUrl . '/blog/' . $slug,
            ];
        }

        $this->renderPartial('public.blog_index', [
            'items' => $items,
            'canonicalUrl' => $baseUrl . '/blog',
        ]);
    }

    public function show(): void {
        $slug = trim((string)($_GET['slug'] ?? ''));
        $baseUrl = rtrim(APP_URL, '/');
        $articles = $this->articles();

        if (!isset($articles[$slug])) {
            http_response_code(404);
            $this->renderPartial('errors.404', []);
            return;
        }

        $article = $articles[$slug];
        $article['slug'] = $slug;
        $article['canonicalUrl'] = $baseUrl . '/blog/' . $slug;
        $article['publishedDate'] = (string)($article['publishedDate'] ?? date('Y-m-d'));
        $article['modifiedDate'] = (string)($article['modifiedDate'] ?? $article['publishedDate']);

        $this->renderPartial('public.blog_article', $article);
    }

    private function articles(): array {
        return [
            'how-to-choose-gst-billing-software' => [
                'title' => 'How to Choose GST Billing Software for Your Business in India | TSA Legacy',
                'description' => 'Learn how to choose GST billing software for your business in India. Compare billing workflows, inventory support, reports, pricing and team access.',
                'heading' => 'How to choose GST billing software for your business in India',
                'excerpt' => 'Most businesses do not fail software selection because of features. They fail because they buy billing software that does not match their actual daily workflow.',
                'publishedDate' => '2026-03-08',
                'modifiedDate' => '2026-04-06',
                'sections' => [
                    ['h' => 'Start with your daily billing workflow', 'p' => 'Check how invoices are created, how often returns happen, whether quotations are needed, and how customer dues are tracked. The right billing software should reduce clicks during the busiest hours of the day.'],
                    ['h' => 'Do not separate billing from inventory', 'p' => 'If stock, purchases, and billing live in different tools, operational mistakes increase. A strong GST billing product should also support product management, stock visibility, and payment tracking.'],
                    ['h' => 'Look for multi-user and role control', 'p' => 'Owner-led businesses often start with one user, but growing teams need cashier access, report visibility, and cleaner control over who can edit products, prices, and records.'],
                    ['h' => 'Compare reporting, not just invoice screens', 'p' => 'Good billing software should also show revenue trends, stock movement, purchase summaries, due balances, and business performance without manual spreadsheet cleanup.'],
                ],
            ],
            'inventory-management-tips-small-business-india' => [
                'title' => 'Inventory Management Tips for Small Businesses in India | TSA Legacy',
                'description' => 'Practical inventory management tips for Indian small businesses. Improve stock control, purchasing, billing coordination and reporting with fewer errors.',
                'heading' => 'Inventory management tips for small businesses in India',
                'excerpt' => 'Inventory problems usually start small: missed stock updates, manual purchase notes, delayed product entry, or no visibility into slow-moving items.',
                'publishedDate' => '2026-03-10',
                'modifiedDate' => '2026-04-06',
                'sections' => [
                    ['h' => 'Keep one product source of truth', 'p' => 'Use one shared product list instead of separate notebooks, WhatsApp messages, and spreadsheets. A single catalog reduces confusion across billing, purchase entry, and stock checks.'],
                    ['h' => 'Track stock movement, not just current stock', 'p' => 'A useful inventory system shows why stock changed: sale, purchase, return, damage, or manual adjustment. This makes reconciliation much easier.'],
                    ['h' => 'Review low-stock and dead-stock regularly', 'p' => 'Low-stock alerts help avoid lost sales, while dead-stock visibility helps prevent money from getting stuck in products that move too slowly.'],
                    ['h' => 'Connect inventory to billing and supplier payments', 'p' => 'The strongest operational improvement comes when billing, purchases, suppliers, and stock records are connected. That reduces duplicate data entry and improves accuracy.'],
                ],
            ],
            'billing-software-vs-accounting-software' => [
                'title' => 'Billing Software vs Accounting Software: What Small Businesses Need First | TSA Legacy',
                'description' => 'Understand the difference between billing software and accounting software for Indian small businesses. Learn what to buy first and when to upgrade.',
                'heading' => 'Billing software vs accounting software: what small businesses need first',
                'excerpt' => 'Many small businesses buy software with the wrong expectation. Billing software and accounting software solve related but different problems.',
                'publishedDate' => '2026-03-12',
                'modifiedDate' => '2026-04-06',
                'sections' => [
                    ['h' => 'Billing software is for daily operations', 'p' => 'Billing software helps create invoices, manage products, handle customers, track dues, and support day-to-day sales or purchase workflows.'],
                    ['h' => 'Accounting software is for books and compliance depth', 'p' => 'Accounting software is stronger when the business needs full bookkeeping depth, journal entries, advanced tax workflows, or formal accounting processes.'],
                    ['h' => 'Most small businesses need billing + inventory first', 'p' => 'If the immediate pain is invoice speed, stock confusion, payment follow-up, and operational visibility, billing software with inventory support is usually the better first investment.'],
                    ['h' => 'Upgrade systems only when business complexity demands it', 'p' => 'As teams grow and processes become more formal, the business may later add accounting depth. But most early-stage SMEs benefit first from operational software that staff can use daily.'],
                ],
            ],
            'best-billing-software-for-kirana-shop' => [
                'title' => 'Best Billing Software for Kirana Shop Owners in India | TSA Legacy',
                'description' => 'What kirana shop owners should look for in billing software: fast invoices, product lookup, customer dues, stock tracking and daily ease of use.',
                'heading' => 'Best billing software for kirana shop owners in India',
                'excerpt' => 'Kirana shops need billing software that moves fast at the counter and stays simple enough for daily use.',
                'publishedDate' => '2026-03-15',
                'modifiedDate' => '2026-04-06',
                'sections' => [
                    ['h' => 'Counter speed matters more than feature count', 'p' => 'For kirana workflows, quick product selection, fast invoice creation, and easy edits are more valuable than long feature lists that slow down staff.'],
                    ['h' => 'Customer dues and repeat billing should be easy', 'p' => 'Many local stores handle repeat customers and informal credit. Software should make due tracking and past invoice lookup straightforward.'],
                    ['h' => 'Inventory visibility prevents silent stock loss', 'p' => 'Even a simple kirana store benefits when billing updates stock and low-stock items become visible without manual counting every day.'],
                    ['h' => 'Choose software that staff can learn quickly', 'p' => 'If a tool requires too much training, adoption drops. Clean screens and daily practicality usually matter more than advanced enterprise features.'],
                ],
            ],
            'wholesale-billing-software-features' => [
                'title' => 'Wholesale Billing Software Features That Actually Matter | TSA Legacy',
                'description' => 'Important wholesale billing software features for Indian businesses: multi-user workflows, bulk products, supplier balances, returns and stock visibility.',
                'heading' => 'Wholesale billing software features that actually matter',
                'excerpt' => 'Wholesale businesses deal with larger catalogs, more stock movement, and more operational coordination than a basic billing tool usually supports.',
                'publishedDate' => '2026-03-18',
                'modifiedDate' => '2026-04-06',
                'sections' => [
                    ['h' => 'Supplier and purchase visibility is critical', 'p' => 'Wholesale operations need clean records for supplier balances, purchase history, and stock coming in, not just sales invoices going out.'],
                    ['h' => 'Bulk products and team workflows need better control', 'p' => 'As product volume grows, software should support multiple users, better product organization, and clear role-based access.'],
                    ['h' => 'Returns and stock adjustments should not be manual', 'p' => 'Returns, corrections, and stock changes create errors when handled outside the main system. Good wholesale software keeps these actions traceable.'],
                    ['h' => 'Reports should help with decisions, not just records', 'p' => 'Owners need visibility into product movement, due balances, sales performance, and purchasing trends to make smarter stock decisions.'],
                ],
            ],
            'retail-billing-software-checklist' => [
                'title' => 'Retail Billing Software Checklist for Small Businesses | TSA Legacy',
                'description' => 'A practical checklist for choosing retail billing software in India. Compare billing speed, product handling, inventory links, reports and team access.',
                'heading' => 'Retail billing software checklist for small businesses',
                'excerpt' => 'Retail businesses need software that supports daily transactions without creating extra operational overhead.',
                'publishedDate' => '2026-03-21',
                'modifiedDate' => '2026-04-06',
                'sections' => [
                    ['h' => 'Check invoice speed during busy hours', 'p' => 'Retail billing software should stay fast when staff are handling multiple customers back to back. Slow checkout hurts both revenue and customer experience.'],
                    ['h' => 'Make sure products, stock and billing are connected', 'p' => 'Retail teams work better when the same platform manages catalog data, stock counts, billing records, and customer history.'],
                    ['h' => 'Choose software with usable reports', 'p' => 'A good retail tool should show sales patterns, product demand, and due balances in a way the owner can review quickly.'],
                    ['h' => 'Keep onboarding simple', 'p' => 'The right tool should be easy for owners and staff to learn, especially if the business is moving from notebooks or spreadsheets.'],
                ],
            ],
            'invoice-software-for-small-business-india' => [
                'title' => 'Invoice Software for Small Business in India: What to Look For | TSA Legacy',
                'description' => 'What Indian small businesses should look for in invoice software: GST billing, PDFs, customer history, payment tracking and connected inventory.',
                'heading' => 'Invoice software for small business in India: what to look for',
                'excerpt' => 'Invoice software should do more than generate a printable bill. For most SMEs, it should connect invoicing with the rest of the business workflow.',
                'publishedDate' => '2026-03-24',
                'modifiedDate' => '2026-04-06',
                'sections' => [
                    ['h' => 'GST-ready invoicing is the baseline', 'p' => 'For Indian businesses, invoice software should support GST billing cleanly and reliably, including invoice records and printable PDF output.'],
                    ['h' => 'Customer history and payment tracking save time', 'p' => 'The software should make it easy to see previous invoices, outstanding balances, and payment history without searching through manual files.'],
                    ['h' => 'Connected inventory makes invoicing more useful', 'p' => 'When invoice software also updates product and stock data, day-to-day operations become much more accurate.'],
                    ['h' => 'Monthly pricing should still allow growth', 'p' => 'Pick a product that is affordable early on but can support more users, more products, and better reporting as the business grows.'],
                ],
            ],
            'stock-management-software-benefits' => [
                'title' => 'Stock Management Software Benefits for Growing Businesses | TSA Legacy',
                'description' => 'Learn the real benefits of stock management software for small and growing businesses in India, including fewer errors, better purchasing and faster operations.',
                'heading' => 'Stock management software benefits for growing businesses',
                'excerpt' => 'Stock management software is most useful when it improves decisions, not just when it counts products.',
                'publishedDate' => '2026-03-28',
                'modifiedDate' => '2026-04-06',
                'sections' => [
                    ['h' => 'Better visibility reduces stock mistakes', 'p' => 'Businesses make fewer purchasing and billing mistakes when owners and staff can see current stock and recent stock movement in one place.'],
                    ['h' => 'Purchasing gets smarter over time', 'p' => 'With better stock records and product movement data, businesses can avoid both over-buying and stockouts.'],
                    ['h' => 'Billing accuracy improves when stock is connected', 'p' => 'When stock records and billing workflows are tied together, product-level errors become easier to catch before they affect customers.'],
                    ['h' => 'Operational discipline becomes easier to maintain', 'p' => 'A shared system reduces dependence on memory, manual notes, and scattered spreadsheets, making business operations more consistent.'],
                ],
            ],
        ];
    }
}
