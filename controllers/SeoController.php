<?php
/**
 * SeoController – Public SEO landing pages for commercial search terms.
 */
class SeoController extends Controller {
    protected $allowedActions = ['show'];

    public function show(): void {
        $slug = trim((string)($_GET['slug'] ?? ''));

        $pages = [
            'gst-billing-software' => [
                'title' => 'GST Billing Software for Small Business in India | TSA Legacy',
                'description' => 'GST billing software for Indian small businesses. Create GST invoices, manage customers, track payments, print PDFs, and run billing operations from one cloud platform.',
                'headline' => 'GST Billing Software for Indian Small Businesses',
                'eyebrow' => 'GST Billing Software',
                'intro' => 'Create GST invoices, manage dues, track customers, and run faster billing operations from one cloud-based system built for Indian SMEs.',
                'benefits' => [
                    'Create CGST, SGST and IGST invoices with cleaner billing workflows.',
                    'Generate PDF invoices, quotations, receipts and sale returns from one place.',
                    'Track due payments, customer balances and invoice history without spreadsheets.',
                ],
                'useCases' => ['Retail billing counters', 'Service businesses', 'Wholesale invoice teams'],
                'faq' => [
                    ['q' => 'Is TSA Legacy suitable for GST billing in India?', 'a' => 'Yes. The platform supports GST invoice creation, PDF output, payment tracking, customer records, and daily billing workflows for Indian businesses.'],
                    ['q' => 'Can I use it for multi-user billing operations?', 'a' => 'Yes. Plans support multiple users, role-based access, and shared billing operations for growing teams.'],
                    ['q' => 'Does it also handle inventory with billing?', 'a' => 'Yes. TSA Legacy combines inventory management, GST invoicing, reports, customer ledgers, and supplier workflows in one system.'],
                ],
            ],
            'inventory-management-software' => [
                'title' => 'Inventory Management Software for Small Business in India | TSA Legacy',
                'description' => 'Inventory management software for Indian SMEs. Track stock, low-stock alerts, products, purchases, sales, suppliers and billing in one cloud system.',
                'headline' => 'Inventory Management Software for Growing Indian Businesses',
                'eyebrow' => 'Inventory Management Software',
                'intro' => 'Track stock movement, manage products, control purchases, and connect inventory with billing from one cloud-native platform for Indian SMEs.',
                'benefits' => [
                    'Monitor products, categories, brands and stock levels in real time.',
                    'Connect purchases, sales, returns and billing with inventory records.',
                    'Reduce stock errors with shared visibility for owners and staff.',
                ],
                'useCases' => ['Retail stores', 'Wholesale stock rooms', 'Trading and distribution teams'],
                'faq' => [
                    ['q' => 'Who should use this inventory management software?', 'a' => 'It is designed for Indian retailers, wholesalers, trading firms, and service-led businesses that need stock visibility with billing and reporting.'],
                    ['q' => 'Can I manage products and suppliers together?', 'a' => 'Yes. Products, suppliers, purchases, payments, and stock updates live in one shared workflow.'],
                    ['q' => 'Does it support billing and inventory together?', 'a' => 'Yes. TSA Legacy is built as an all-in-one billing and inventory management platform rather than a stock-only tool.'],
                ],
            ],
            'billing-software-for-small-business' => [
                'title' => 'Billing Software for Small Business in India | TSA Legacy',
                'description' => 'Billing software for small business owners in India. Manage invoices, inventory, customers, suppliers, reports and multi-user operations from one SaaS platform.',
                'headline' => 'Billing Software for Small Business Owners in India',
                'eyebrow' => 'Small Business Billing Software',
                'intro' => 'Run billing, inventory, customer tracking, dues, reports, and team workflows from one software platform designed for Indian small businesses.',
                'benefits' => [
                    'Replace manual billing, paper registers and scattered spreadsheets.',
                    'Run sales, purchases, customer dues and product tracking in one dashboard.',
                    'Start with low monthly pricing and upgrade only when the business grows.',
                ],
                'useCases' => ['Kirana and retail shops', 'Owner-led SMEs', 'Fast-growing local businesses'],
                'faq' => [
                    ['q' => 'Why is TSA Legacy a good fit for small businesses?', 'a' => 'It combines billing, inventory, reports, customer tracking, and team access in one affordable monthly platform for Indian SMEs.'],
                    ['q' => 'Can a small business start without a big setup?', 'a' => 'Yes. The system is self-serve, cloud-based, and designed for quick onboarding without a separate IT team.'],
                    ['q' => 'Is it only for billing?', 'a' => 'No. TSA Legacy includes inventory management, purchase tracking, customer and supplier records, and business reporting as well.'],
                ],
            ],
        ];

        if (!isset($pages[$slug])) {
            http_response_code(404);
            $this->renderPartial('errors.404', []);
            return;
        }

        $page = $pages[$slug];
        $page['slug'] = $slug;
        $page['canonicalUrl'] = rtrim(APP_URL, '/') . '/' . $slug;

        $this->renderPartial('public.seo_page', $page);
    }
}
