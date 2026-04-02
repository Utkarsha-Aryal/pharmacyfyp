<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Invoice</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        .header, .info-wrap { width: 100%; }
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
        .totals { width: 40%; margin-left: auto; margin-top: 14px; border-collapse: collapse; }
        .totals td { padding: 6px 8px; }
        .totals .strong { font-weight: bold; border-top: 1px solid #1f2937; }
        .payment-box { margin-top: 12px; border: 1px solid #dee2e6; padding: 10px; }
        .footer { margin-top: 22px; }
    </style>
</head>
<body>
    <table class="header">
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
                <div class="title">Sales Invoice</div>
                <div class="text-end">Document No: {{ $invoice->reference }}</div>
                <div class="muted text-end">Date: {{ $invoice->invoice_date_show }}</div>
            </td>
        </tr>
    </table>

    <hr class="line">

    <table class="info-wrap">
        <tr>
            <td class="info-box">
                <strong>Bill To</strong><br>
                {{ $invoice->customer?->name ?? 'Walk-in Customer' }}<br>
                {{ $invoice->customer?->address ?? '-' }}<br>
                {{ $invoice->customer?->phone ?? '-' }}
            </td>
            <td width="4%"></td>
            <td class="info-box">
                <strong>Document Info</strong><br>
                Document No: {{ $invoice->reference }}<br>
                Date: {{ $invoice->invoice_date_show }}<br>
                Payment Status: {{ ucfirst((string) $invoice->payment_status) }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Product Name</th>
                <th>Batch No</th>
                <th>Expiry</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Free Qty</th>
                <th class="text-end">MRP</th>
                <th class="text-end">Rate</th>
                <th class="text-end">Disc%</th>
                <th class="text-end">Disc Amt</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $index => $item)
                @php
                    $grossAmount = (float) $item->quantity * (float) $item->unit_price;
                    $discountAmount = (float) ($item->discount_amount ?? (($grossAmount * (float) ($item->discount_percent ?? 0)) / 100));
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product?->display_name ?? '-' }}</td>
                    <td>{{ $item->batch?->batch_number ?? '-' }}</td>
                    <td>{{ $item->batch?->expiry_show ?? '-' }}</td>
                    <td class="text-end">{{ $item->quantity }}</td>
                    <td class="text-end">{{ $item->free_qty ?? 0 }}</td>
                    <td class="text-end">{{ money_value($item->mrp) }}</td>
                    <td class="text-end">{{ money_value($item->unit_price) }}</td>
                    <td class="text-end">{{ number_format((float) ($item->discount_percent ?? 0), 2) }}%</td>
                    <td class="text-end">{{ money_value($discountAmount) }}</td>
                    <td class="text-end">{{ money_value($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="text-end">{{ money_value($invoice->subtotal) }}</td>
        </tr>
        <tr>
            <td>Total Discount</td>
            <td class="text-end">{{ money_value($invoice->total_discount ?? $invoice->discount_amount) }}</td>
        </tr>
        @if ((float) $invoice->items->sum('free_goods_value') > 0)
            <tr>
                <td>Free Goods Value</td>
                <td class="text-end">{{ money_value($invoice->items->sum('free_goods_value')) }}</td>
            </tr>
        @endif
        <tr>
            <td class="strong">Net Payable</td>
            <td class="text-end strong">{{ money_value($invoice->total_amount) }}</td>
        </tr>
    </table>

    <div class="payment-box">
        Payment Status: {{ ucfirst((string) $invoice->payment_status) }}<br>
        @if ($invoice->payment_status === 'partial')
            Amount Paid: {{ money_value($invoice->paid_amount) }}<br>
            Balance Due: {{ money_value($invoice->due_amount) }}
        @elseif ($invoice->payment_status === 'paid')
            Amount Paid: {{ money_value($invoice->paid_amount) }}
        @endif
    </div>

    <div class="footer">
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
