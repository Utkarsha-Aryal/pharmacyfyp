@extends('finance.pdf.layout')

@section('title', 'GST Report')
@section('report-subtitle', 'GST / tax report')

@section('report-meta')
    <span><strong>Generated:</strong> {{ now()->format('M j, Y h:i A') }}</span>
    <span><strong>From:</strong> {{ $filters['date_from'] ?? 'Start' }}</span>
    <span><strong>To:</strong> {{ $filters['date_to'] ?? 'Today' }}</span>
    <span><strong>Default Tax Rate:</strong> {{ default_tax_rate() }}%</span>
@endsection

@section('content')
    <table class="summary-table">
        <tr>
            <td><strong>Taxable Sales</strong><br>{{ money_value($summary['taxable_sales']) }}</td>
            <td><strong>Tax Collected</strong><br>{{ money_value($summary['tax_amount']) }}</td>
            <td><strong>Total Sales</strong><br>{{ money_value($summary['total_sales']) }}</td>
        </tr>
    </table>

    <div class="section-title">Invoice Tax Rows</div>
    <table class="report-table">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Invoice</th>
                <th>Party</th>
                <th>Date</th>
                <th>Taxable Sales</th>
                <th>Tax</th>
                <th>Total</th>
                <th>Payment</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $index => $invoice)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $invoice->reference }}</td>
                    <td>{{ $invoice->customer?->name ?? '-' }}</td>
                    <td>{{ $invoice->invoice_date_show }}</td>
                    <td class="text-right">{{ money_value($invoice->subtotal) }}</td>
                    <td class="text-right">{{ money_value($invoice->tax_amount) }}</td>
                    <td class="text-right">{{ money_value($invoice->total_amount) }}</td>
                    <td>{{ $invoice->payment_label }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center muted">No invoice found for GST report.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
