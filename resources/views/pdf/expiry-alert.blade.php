<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Expiry Alert Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        .header td { vertical-align: top; }
        .title { font-size: 20px; font-weight: bold; text-transform: uppercase; text-align: right; }
        .company-name { font-size: 18px; font-weight: bold; }
        .muted { font-size: 11px; color: #666; }
        .line { border: 0; border-top: 1px solid #d1d5db; margin: 10px 0 14px; }
        .items-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 14px; }
        .items-table th { background: #1a3c5e; color: white; padding: 6px 8px; text-align: left; }
        .items-table td { padding: 6px 8px; border-bottom: 1px solid #dee2e6; }
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
                <div class="title">Expiry Alert Report</div>
                <div class="text-end">Period: {{ $dateFrom->format('M j, Y') }} to {{ $dateTo->format('M j, Y') }}</div>
                <div class="muted text-end">Generated on {{ now()->format('M j, Y h:i A') }}</div>
            </td>
        </tr>
    </table>
    <hr class="line">

    <table class="items-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Product Name</th>
                <th>Batch No</th>
                <th class="text-end">Remaining Qty</th>
                <th>Expiry Date</th>
                <th class="text-end">Days Left</th>
                <th>Supplier</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($expiryItems as $index => $item)
                @php
                    $daysLeft = (int) $item->days_left;
                    $rowStyle = $daysLeft <= 90 ? 'background: #ffe0e0;' : ($daysLeft <= 180 ? 'background: #fff3cd;' : '');
                @endphp
                <tr style="{{ $rowStyle }}">
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product?->display_name ?? '-' }}</td>
                    <td>{{ $item->batch_number }}</td>
                    <td class="text-end">{{ $item->quantity_available }}</td>
                    <td>{{ $item->expiry_show }}</td>
                    <td class="text-end">{{ $daysLeft }}</td>
                    <td>{{ $item->supplier?->supplier_name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

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
