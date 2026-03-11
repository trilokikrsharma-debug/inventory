<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #333; background: #fff; }
    .invoice { max-width: 800px; margin: 0 auto; padding: 30px; }

    /* Header */
    .header { display: table; width: 100%; margin-bottom: 25px; padding-bottom: 18px; border-bottom: 3px solid #4e73df; }
    .header .company-block, .header .invoice-block { display: table-cell; vertical-align: top; }
    .header .company-logo { display: block; max-height: 64px; max-width: 190px; width: auto; height: auto; object-fit: contain; margin-bottom: 8px; }
    .header .company-block { width: 68%; padding-right: 18px; }
    .header .invoice-block { width: 32%; text-align: right; }
    .header .company-block h1 { font-size: 22px; color: #4e73df; margin-bottom: 3px; }
    .header .company-block .company-details { font-size: 11.5px; color: #666; line-height: 1.6; }
    .header .invoice-title { font-size: 14px; font-weight: 700; color: #4e73df; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
    .header .invoice-number { font-size: 18px; font-weight: bold; color: #333; }
    .header .invoice-date { color: #666; font-size: 12px; margin-top: 2px; }

    /* Party Info */
    .party-info { display: flex; justify-content: space-between; margin-bottom: 22px; padding: 14px 16px; background: #f8f9fc; border-radius: 8px; }
    .party-info .label { font-weight: bold; margin-bottom: 4px; color: #4e73df; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
    .party-info .party-name { font-weight: 600; font-size: 14px; margin-bottom: 2px; }
    .party-info .party-detail { font-size: 11.5px; color: #666; line-height: 1.5; }

    /* Table */
    table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
    table th { background: #4e73df; color: #fff; padding: 9px 10px; text-align: left; font-size: 11.5px; font-weight: 600; }
    table th:first-child { border-radius: 4px 0 0 0; }
    table th:last-child { border-radius: 0 4px 0 0; }
    table td { padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 12.5px; }
    table tbody tr:nth-child(even) { background: #fcfcfd; }

    /* Summary */
    .summary-section { display: flex; justify-content: space-between; gap: 30px; margin-bottom: 25px; }
    .summary-left { flex: 1; }
    .summary { width: 280px; flex-shrink: 0; }
    .summary-row { display: table; width: 100%; table-layout: fixed; padding: 5px 0; font-size: 12.5px; }
    .summary-row > span { display: table-cell; vertical-align: middle; }
    .summary-row > span:last-child { text-align: right; white-space: nowrap; padding-left: 14px; }
    .summary-row.total { font-size: 15px; font-weight: bold; border-top: 2px solid #333; padding-top: 10px; margin-top: 5px; }

    /* Bank Details */
    .bank-section { background: #f8f9fc; border-radius: 8px; padding: 12px 14px; font-size: 11.5px; }
    .bank-section .bank-title { font-weight: 700; color: #4e73df; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .bank-section .bank-info { color: #555; white-space: pre-line; line-height: 1.6; }

    /* Terms */
    .terms-section { margin-top: 20px; padding: 12px 14px; border: 1px solid #eee; border-radius: 8px; font-size: 11px; }
    .terms-section .terms-title { font-weight: 700; color: #333; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .terms-section .terms-text { color: #666; white-space: pre-line; line-height: 1.5; }

    /* Signature */
    .signature-section { text-align: right; margin-top: 40px; }
    .signature-section .sig-line { width: 200px; border-top: 1px solid #333; margin-left: auto; margin-bottom: 5px; }
    .signature-section .sig-label { font-size: 11px; color: #555; font-weight: 600; }
    .signature-section .sig-company { font-size: 10px; color: #888; }

    /* Footer */
    .footer { margin-top: 30px; padding-top: 12px; border-top: 1px solid #eee; text-align: center; font-size: 11px; color: #999; }

    /* Badge */
    .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
    .badge-paid { background: #d4edda; color: #155724; }
    .badge-unpaid { background: #f8d7da; color: #721c24; }
    .badge-partial { background: #fff3cd; color: #856404; }

    /* Detail Row (for receipts) */
    .detail-table td { padding: 10px 14px; }
    .detail-table .detail-label { color: #888; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.3px; width: 140px; }
    .detail-table .detail-value { font-weight: 600; font-size: 13px; }
    .detail-table .amount-value { font-size: 22px; font-weight: 700; color: #4e73df; }

    /* Status badge (for quotations) */
    .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .status-draft { background: #e3e6f0; color: #5a5c69; }
    .status-sent { background: #cce5ff; color: #004085; }
    .status-converted { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }

    @media print {
        body { margin: 0; }
        .no-print { display: none !important; }
        .invoice { padding: 15px; }
    }
</style>
