@extends('finance.pdf.layout')

@section('title', 'Trial Balance')
@section('report-subtitle', 'Trial balance report')

@section('report-meta')
    <span><strong>Generated:</strong> {{ now()->format('M j, Y h:i A') }}</span>
    <span><strong>Total Debit:</strong> {{ money_value($summary['debit']) }}</span>
    <span><strong>Total Credit:</strong> {{ money_value($summary['credit']) }}</span>
    <span><strong>Difference:</strong> {{ money_value($summary['difference']) }}</span>
@endsection

@section('content')
    <table class="report-table">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Code</th>
                <th>Account</th>
                <th>Group</th>
                <th>Normal Side</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Closing Balance</th>
            </tr>
        </thead>
        <tbody>
            @php $serial = 1; @endphp
            @forelse ($rowGroups as $groupName => $groupRows)
                @foreach ($groupRows as $row)
                    <tr>
                        <td>{{ $serial++ }}</td>
                        <td>{{ $row['code'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $groupName }}</td>
                        <td>{{ $row['nature'] }}</td>
                        <td class="text-right">{{ money_value($row['debit']) }}</td>
                        <td class="text-right">{{ money_value($row['credit']) }}</td>
                        <td class="text-right">{{ money_value($row['closing_amount']) }} {{ $row['closing_side'] }}</td>
                    </tr>
                @endforeach
                <tr class="group-row">
                    <td colspan="5" class="text-right">{{ $groupName }} Total</td>
                    <td class="text-right">{{ money_value($groupRows->sum('debit')) }}</td>
                    <td class="text-right">{{ money_value($groupRows->sum('credit')) }}</td>
                    <td></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center muted">No account summary available.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="footer-total">
                <td colspan="5" class="text-right">Grand Total</td>
                <td class="text-right">{{ money_value($summary['debit']) }}</td>
                <td class="text-right">{{ money_value($summary['credit']) }}</td>
                <td class="text-right">{{ money_value($summary['difference']) }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
