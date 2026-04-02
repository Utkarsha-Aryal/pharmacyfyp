<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $payment->type === 'in' ? 'Payment Receipt' : 'Payment Voucher' }}</title>
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
        .amount-box { margin-top: 14px; border: 1px solid #dee2e6; padding: 12px; background: #f8f9fa; font-size: 16px; font-weight: bold; }
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
                <div class="title">{{ $payment->type === 'in' ? 'Payment Receipt' : 'Payment Voucher' }}</div>
                <div class="text-end">Document No: PAY-{{ str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div class="muted text-end">Date: {{ $payment->payment_date_show }}</div>
            </td>
        </tr>
    </table>
    <hr class="line">

    <table width="100%">
        <tr>
            <td class="info-box">
                <strong>{{ $payment->party_type === 'customer' ? 'Bill To' : 'Supplier' }}</strong><br>
                {{ $payment->party_name }}<br>
                {{ $payment->customer?->address ?? $payment->supplier?->address ?? '-' }}<br>
                {{ $payment->customer?->phone ?? $payment->supplier?->phone_number ?? '-' }}
            </td>
            <td width="4%"></td>
            <td class="info-box">
                <strong>Document Info</strong><br>
                Document No: PAY-{{ str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT) }}<br>
                Date: {{ $payment->payment_date_show }}<br>
                Payment Mode: {{ $payment->paymentMode?->name ?? '-' }}
            </td>
        </tr>
    </table>

    <div class="amount-box">
        Amount: {{ money_value($payment->amount) }}
    </div>

    <div style="margin-top: 12px;">
        <strong>Reference Number:</strong> {{ $payment->reference_number ?: '-' }}<br>
        <strong>Notes:</strong> {{ $payment->notes ?: '-' }}
    </div>

    @if ($allocationRows->isNotEmpty())
        <table class="items-table">
            <thead>
                <tr>
                    <th>Bill Type</th>
                    <th>Bill Number</th>
                    <th>Bill Date</th>
                    <th class="text-end">Bill Amount</th>
                    <th class="text-end">Allocated Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($allocationRows as $row)
                    <tr>
                        <td>{{ $row['bill_type'] }}</td>
                        <td>{{ $row['bill_number'] }}</td>
                        <td>{{ $row['bill_date'] }}</td>
                        <td class="text-end">{{ money_value($row['bill_amount']) }}</td>
                        <td class="text-end">{{ money_value($row['allocated_amount']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div style="margin-top: 30px; text-align: right;">
        ___________________________<br>
        Signature
    </div>

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
