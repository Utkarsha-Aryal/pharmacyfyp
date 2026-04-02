<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->reference }} PDF</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #0f172a;
            margin: 24px;
        }

        .invoice-sheet-card {
            border: 1px solid #dbe5f0;
            border-radius: 16px;
            overflow: hidden;
        }

        .invoice-sheet-body {
            padding: 22px;
        }

        .invoice-sheet-top {
            width: 100%;
            margin-bottom: 18px;
        }

        .invoice-sheet-top table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-sheet-brand h1 {
            margin: 0 0 4px;
            font-size: 22px;
        }

        .invoice-sheet-brand p {
            margin: 0 0 8px;
            color: #475569;
        }

        .invoice-sheet-company span {
            display: block;
            margin-bottom: 3px;
            color: #475569;
        }

        .invoice-sheet-ref {
            border: 1px solid #dbeafe;
            background: #eff6ff;
            border-radius: 12px;
            padding: 14px;
            text-align: right;
        }

        .invoice-sheet-ref span {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
        }

        .invoice-sheet-ref strong {
            display: block;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .invoice-sheet-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
            margin-bottom: 12px;
        }

        .invoice-sheet-box {
            border: 1px solid #e5edf7;
            border-radius: 12px;
            padding: 14px;
            vertical-align: top;
        }

        .invoice-sheet-box h5 {
            margin: 0 0 8px;
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.08em;
        }

        .invoice-sheet-box p {
            margin: 0 0 6px;
        }

        .invoice-sheet-table,
        .invoice-sheet-total {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-sheet-table th,
        .invoice-sheet-table td,
        .invoice-sheet-total td {
            border: 1px solid #dbe5f0;
            padding: 8px 10px;
            vertical-align: top;
        }

        .invoice-sheet-table th {
            background: #eff6ff;
            text-transform: uppercase;
            font-size: 10px;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .invoice-sheet-total-wrap {
            margin-top: 16px;
            margin-left: auto;
            width: 320px;
        }

        .grand-total td {
            background: #eff6ff;
            font-weight: 700;
        }

        .invoice-sheet-note {
            margin-top: 16px;
            padding: 12px;
            border: 1px dashed #d4e1f0;
            border-radius: 12px;
            background: #f8fbff;
        }

        .invoice-sheet-note strong {
            display: block;
            margin-bottom: 4px;
        }

        .invoice-sheet-footer {
            margin-top: 18px;
            color: #64748b;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="invoice-sheet-card">
        <div class="invoice-sheet-body">
            <div class="invoice-sheet-top">
                <table>
                    <tr>
                        <td style="width: 65%; vertical-align: top;">
                            <div class="invoice-sheet-brand">
                                <h1>{{ $company['name'] }}</h1>
                                <p>Sales invoice copy</p>
                                <div class="invoice-sheet-company">
                                    @if (!empty($company['phone']))
                                        <span><strong>Phone:</strong> {{ $company['phone'] }}</span>
                                    @endif
                                    @if (!empty($company['email']))
                                        <span><strong>Email:</strong> {{ $company['email'] }}</span>
                                    @endif
                                    @if (!empty($company['address']))
                                        <span><strong>Address:</strong> {{ $company['address'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="width: 35%; vertical-align: top;">
                            <div class="invoice-sheet-ref">
                                <span>Invoice No</span>
                                <strong>{{ $invoice->reference }}</strong>
                                <small>Date: {{ $invoice->invoice_date_show }}</small>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <table class="invoice-sheet-grid">
                <tr>
                    <td class="invoice-sheet-box" style="width: 50%;">
                        <h5>Party Details</h5>
                        <p><strong>Name:</strong> {{ $invoice->customer?->name ?? 'Walk-in Customer' }}</p>
                        <p><strong>Contact:</strong> {{ $invoice->customer?->phone ?? ($invoice->customer?->email ?? '-') }}</p>
                        <p><strong>Address:</strong> {{ $invoice->customer?->address ?: '-' }}</p>
                    </td>
                    <td style="width: 16px;"></td>
                    <td class="invoice-sheet-box" style="width: 50%;">
                        <h5>Invoice Summary</h5>
                        <p><strong>Sale Type:</strong> {{ $invoice->sale_type_label }}</p>
                        <p><strong>Payment:</strong> {{ $invoice->payment_label }} via {{ $invoice->payment_method_label }}</p>
                        <p><strong>Created By:</strong> {{ $invoice->creator?->name ?? ($invoice->soldBy?->name ?? '-') }}</p>
                    </td>
                </tr>
            </table>

            <table class="invoice-sheet-table">
                <thead>
                    <tr>
                        <th style="width: 45px;">S.No</th>
                        <th>Product</th>
                        <th>Batch</th>
                        <th style="width: 62px;">Qty</th>
                        <th style="width: 62px;">Free</th>
                        <th style="width: 80px;">MRP</th>
                        <th style="width: 80px;">Rate</th>
                        <th style="width: 70px;">Disc %</th>
                        <th style="width: 70px;">CC %</th>
                        <th style="width: 90px;">Free Value</th>
                        <th style="width: 90px;">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->product?->display_name ?? '-' }}</td>
                            <td>{{ $item->batch?->batch_number ?? '-' }}</td>
                            <td>{{ number_format((float) $item->quantity, 2) }}</td>
                            <td>{{ number_format((float) ($item->free_qty ?? 0), 2) }}</td>
                            <td>{{ money_value($item->mrp ?? 0) }}</td>
                            <td>{{ money_value($item->unit_price) }}</td>
                            <td>{{ number_format((float) $item->discount_percent, 2) }}</td>
                            <td>{{ number_format((float) ($item->cc_rate ?? 0), 2) }}</td>
                            <td>{{ money_value($item->free_goods_value ?? 0) }}</td>
                            <td>{{ money_value($item->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="invoice-sheet-total-wrap">
                <table class="invoice-sheet-total">
                    <tr>
                        <td>Subtotal</td>
                        <td class="text-right">{{ money_value($invoice->subtotal) }}</td>
                    </tr>
                    <tr>
                        <td>Discount</td>
                        <td class="text-right">{{ money_value($invoice->discount_amount) }}</td>
                    </tr>
                    <tr>
                        <td>Paid</td>
                        <td class="text-right">{{ money_value($invoice->paid_amount) }}</td>
                    </tr>
                    <tr>
                        <td>Due</td>
                        <td class="text-right">{{ money_value($invoice->due_amount) }}</td>
                    </tr>
                    <tr class="grand-total">
                        <td>Total</td>
                        <td class="text-right">{{ money_value($invoice->total_amount) }}</td>
                    </tr>
                </table>
            </div>

            @if ($invoice->notes)
                <div class="invoice-sheet-note">
                    <strong>Notes</strong>
                    <span>{{ $invoice->notes }}</span>
                </div>
            @endif

            <div class="invoice-sheet-footer">
                Printed on {{ now()->format('M j, Y h:i A') }} | Thank you for doing business with us.
            </div>
        </div>
    </div>
</body>
</html>
