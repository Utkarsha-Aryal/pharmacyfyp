<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Party Ledger</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        .header td { vertical-align: top; }
        .title { font-size: 20px; font-weight: bold; text-transform: uppercase; text-align: right; }
        .company-name { font-size: 18px; font-weight: bold; }
        .muted { font-size: 11px; color: #666; }
        .line { border: 0; border-top: 1px solid #d1d5db; margin: 10px 0 14px; }
        .info-box { width: 48%; border: 1px solid #dee2e6; padding: 10px; vertical-align: top; }
        .items-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 14px; }
        .items-table th { background: #1a3c5e; color: white; padding: 6px 8px; text-align: left; }
        .items-table td { padding: 6px 8px; border-bottom: 1px solid #dee2e6; }
        .items-table tbody tr:nth-child(even) { background: #f8f9fa; }
        .text-end { text-align: right; }
    </style>
</head>
<body>
    <table width="100%" class="header">
        <tr>
            <td width="62%">
                @if ($logoSrc)
                    <img src="{{ $logoSrc }}" style="max-height: 60px; margin-bottom: 6px;">
                @endif
                <div class="company-name">{{ $company['company_name'] }}</div>
                <div class="muted">{{ $company['company_address'] }}</div>
                <div class="muted">{{ $company['company_phone'] }}</div>
                <div class="muted">{{ $company['company_email'] }}</div>
            </td>
            <td width="38%">
                <div class="title">Party Ledger</div>
                <div class="text-end">Party: {{ $customer->name }}</div>
                <div class="muted text-end">Generated on {{ now()->format('M j, Y h:i A') }}</div>
            </td>
        </tr>
    </table>
    <hr class="line">

    <table width="100%">
        <tr>
            <td class="info-box">
                <strong>Party Details</strong><br>
                {{ $customer->name }}<br>
                {{ $customer->address ?: '-' }}<br>
                {{ $customer->phone ?: '-' }}
            </td>
            <td width="4%"></td>
            <td class="info-box">
                <strong>Ledger Summary</strong><br>
                Outstanding: {{ money_value($outstanding) }}<br>
                Sales Count: {{ $salesCount }}<br>
                Invoice Total: {{ money_value($invoiceTotal) }}<br>
                Paid Total: {{ money_value($paidTotal) }}<br>
                Aging: {{ $agingDays }} days
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Reference</th>
                <th>Date</th>
                <th>Sale Type</th>
                <th>Payment</th>
                <th class="text-end">Total</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Due</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $index => $invoice)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $invoice->reference }}</td>
                    <td>{{ $invoice->invoice_date_show }}</td>
                    <td>{{ $invoice->sale_type_label }}</td>
                    <td>{{ $invoice->payment_label }}</td>
                    <td class="text-end">{{ money_value($invoice->total_amount) }}</td>
                    <td class="text-end">{{ money_value($invoice->paid_amount) }}</td>
                    <td class="text-end">{{ money_value($invoice->due_amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">No invoice history available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($returns->isNotEmpty())
        <table class="items-table" style="margin-top: 18px;">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Return Date</th>
                    <th>Product</th>
                    <th class="text-end">Quantity</th>
                    <th class="text-end">Refund</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($returns as $index => $returnItem)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $returnItem->return_date_show }}</td>
                        <td>{{ $returnItem->product?->display_name ?? '-' }}</td>
                        <td class="text-end">{{ $returnItem->quantity }}</td>
                        <td class="text-end">{{ money_value($returnItem->refund_amount) }}</td>
                        <td>{{ $returnItem->reason ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div style="margin-top: 22px;">
        <hr class="line">
        <table width="100%">
            <tr>
                <td>{{ $company['footer_text'] ?? 'Thank you for your business' }}</td>
                <td class="text-end">Printed on {{ now()->format('M j, Y h:i A') }}</td>
            </tr>
        </table>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script('
                $font = $fontMetrics->getFont("DejaVu Sans", "normal");
                $pdf->text(270, 820, "Page " . $PAGE_NUM . " of " . $PAGE_COUNT, $font, 9, array(0,0,0));
            ');
        }
    </script>
</body>
</html>
