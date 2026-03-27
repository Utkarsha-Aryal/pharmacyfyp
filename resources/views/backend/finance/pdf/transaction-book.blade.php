@extends('backend.finance.pdf.layout')

@section('title', $title)
@section('report-subtitle', $subtitle)

@section('report-meta')
    <span><strong>Generated:</strong> {{ now()->format('M j, Y h:i A') }}</span>
    <span><strong>Debit:</strong> {{ money_value($summary['debit']) }}</span>
    <span><strong>Credit:</strong> {{ money_value($summary['credit']) }}</span>
@endsection

@section('content')
    <table class="summary-table">
        <tr>
            <td><strong>Debit</strong><br>{{ money_value($summary['debit']) }}</td>
            <td><strong>Credit</strong><br>{{ money_value($summary['credit']) }}</td>
            <td><strong>Net</strong><br>{{ money_value($summary['debit'] - $summary['credit']) }}</td>
        </tr>
    </table>

    <div class="section-title">{{ $title }} Entries</div>
    <table class="report-table">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Date</th>
                <th>Voucher / Ref</th>
                <th>Party</th>
                <th>Narration</th>
                <th>Debit</th>
                <th>Credit</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $index => $transaction)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $transaction->transaction_date_show }}</td>
                    <td>{{ $transaction->reference_type ? $transaction->reference_type . ' #' . $transaction->reference_id : '-' }}</td>
                    <td>{{ $transaction->party_name }}</td>
                    <td>{{ $transaction->notes ?: '-' }}</td>
                    <td class="text-right">{{ $transaction->entry_type === 'debit' ? money_value($transaction->amount) : '-' }}</td>
                    <td class="text-right">{{ $transaction->entry_type === 'credit' ? money_value($transaction->amount) : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center muted">No transaction rows found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
