<div class="invoice-sheet-card">
    <div class="invoice-sheet-body">
        <div class="invoice-sheet-top">
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

            <div class="invoice-sheet-ref">
                <span>Invoice No</span>
                <strong>{{ $invoice->reference }}</strong>
                <small>Date: {{ $invoice->invoice_date_show }}</small>
            </div>
        </div>

        <div class="invoice-sheet-grid">
            <div class="invoice-sheet-box">
                <h5>Party Details</h5>
                <p><strong>Name:</strong> {{ $invoice->customer?->name ?? 'Walk-in Customer' }}</p>
                <p><strong>Contact:</strong> {{ $invoice->customer?->phone ?? ($invoice->customer?->email ?? '-') }}</p>
                <p><strong>Address:</strong> {{ $invoice->customer?->address ?: '-' }}</p>
            </div>

            <div class="invoice-sheet-box">
                <h5>Invoice Summary</h5>
                <p><strong>Sale Type:</strong> {{ $invoice->sale_type_label }}</p>
                <p><strong>Payment:</strong> {{ $invoice->payment_label }} via {{ $invoice->payment_method_label }}</p>
                <p><strong>Created By:</strong> {{ $invoice->creator?->name ?? ($invoice->soldBy?->name ?? '-') }}</p>
            </div>
        </div>

        <table class="invoice-sheet-table">
            <thead>
                <tr>
                    <th style="width: 60px;">S.No</th>
                    <th>Product</th>
                    <th>Batch</th>
                    <th style="width: 80px;">Qty</th>
                    <th style="width: 110px;">Rate</th>
                    <th style="width: 90px;">Disc %</th>
                    <th style="width: 90px;">Tax %</th>
                    <th style="width: 130px;">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->product?->display_name ?? '-' }}</td>
                        <td>{{ $item->batch?->batch_number ?? '-' }}</td>
                        <td>{{ number_format((float) $item->quantity, 2) }}</td>
                        <td>{{ money_value($item->unit_price) }}</td>
                        <td>{{ number_format((float) $item->discount_percent, 2) }}</td>
                        <td>{{ number_format((float) $item->tax_percent, 2) }}</td>
                        <td>{{ money_value($item->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="invoice-sheet-total-wrap">
            <div class="invoice-sheet-total">
                <div class="invoice-sheet-total-row">
                    <span>Subtotal</span>
                    <strong>{{ money_value($invoice->subtotal) }}</strong>
                </div>
                <div class="invoice-sheet-total-row">
                    <span>Discount</span>
                    <strong>{{ money_value($invoice->discount_amount) }}</strong>
                </div>
                <div class="invoice-sheet-total-row">
                    <span>Tax</span>
                    <strong>{{ money_value($invoice->tax_amount) }}</strong>
                </div>
                <div class="invoice-sheet-total-row">
                    <span>Paid</span>
                    <strong>{{ money_value($invoice->paid_amount) }}</strong>
                </div>
                <div class="invoice-sheet-total-row">
                    <span>Due</span>
                    <strong>{{ money_value($invoice->due_amount) }}</strong>
                </div>
                <div class="invoice-sheet-total-row grand-total">
                    <span>Total</span>
                    <strong>{{ money_value($invoice->total_amount) }}</strong>
                </div>
            </div>
        </div>

        @if ($invoice->notes)
            <div class="invoice-sheet-note">
                <strong>Notes</strong>
                <span>{{ $invoice->notes }}</span>
            </div>
        @endif

        <div class="invoice-sheet-footer">
            <span>Printed on {{ now()->format('M j, Y h:i A') }}</span>
            <span>Thank you for doing business with us.</span>
        </div>
    </div>
</div>
