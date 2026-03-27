@extends('finance.pdf.layout')

@section('title', 'Ledger Report')
@section('report-subtitle', 'General ledger report')

@section('report-meta')
    <span><strong>Generated:</strong> {{ now()->format('M j, Y h:i A') }}</span>
    <span><strong>Party Type:</strong> {{ $filters['party_type'] ?? 'All' }}</span>
    <span><strong>Account:</strong> {{ $filters['account_type'] ?? 'All' }}</span>
    <span><strong>Entry:</strong> {{ $filters['entry_type'] ?? 'All' }}</span>
@endsection

@section('content')
    <table class="summary-table">
        <tr>
            <td><strong>Debit</strong><br>{{ money_value($summary['debit']) }}</td>
            <td><strong>Credit</strong><br>{{ money_value($summary['credit']) }}</td>
            <td><strong>Receivable</strong><br>{{ money_value($summary['receivable']) }}</td>
            <td><strong>Payable</strong><br>{{ money_value($summary['payable']) }}</td>
        </tr>
    </table>

    <div class="section-title">Ledger Entries</div>
    <table class="report-table">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Date</th>
                <th>Voucher / Ref</th>
                <th>Party</th>
                <th>Account</th>
                <th>Group</th>
                <th>Narration</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Created By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $index => $transaction)
                @php $account = $accountCatalog->get($transaction->account_type); @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $transaction->transaction_date_show }}</td>
                    <td>{{ $transaction->reference_type ? $transaction->reference_type . ' #' . $transaction->reference_id : '-' }}</td>
                    <td>{{ $transaction->party_name }}</td>
                    <td>{{ $account['name'] ?? $transaction->account_label }}</td>
                    <td>{{ $account['group'] ?? '-' }}</td>
                    <td>{{ $transaction->notes ?: '-' }}</td>
                    <td class="text-right">{{ $transaction->entry_type === 'debit' ? money_value($transaction->amount) : '-' }}</td>
                    <td class="text-right">{{ $transaction->entry_type === 'credit' ? money_value($transaction->amount) : '-' }}</td>
                    <td>{{ $transaction->creator?->name ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center muted">No ledger entry found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
