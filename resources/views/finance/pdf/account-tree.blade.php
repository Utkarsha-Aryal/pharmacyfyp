@extends('finance.pdf.layout')

@section('title', 'Account Tree')
@section('report-subtitle', 'Account tree / chart of accounts')

@section('report-meta')
    <span><strong>Generated:</strong> {{ now()->format('M j, Y h:i A') }}</span>
    <span><strong>Total Accounts:</strong> {{ $summary['accounts'] }}</span>
    <span><strong>Total Debit:</strong> {{ money_value($summary['debit']) }}</span>
    <span><strong>Total Credit:</strong> {{ money_value($summary['credit']) }}</span>
@endsection

@section('content')
    @foreach ($groups as $group)
        <div class="section-title">{{ $group['name'] }}</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Code</th>
                    <th>Account Name</th>
                    <th>Normal Side</th>
                    <th>Debit</th>
                    <th>Credit</th>
                    <th>Closing Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['rows'] as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['code'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['nature'] }}</td>
                        <td class="text-right">{{ money_value($row['debit']) }}</td>
                        <td class="text-right">{{ money_value($row['credit']) }}</td>
                        <td class="text-right">{{ money_value($row['closing_amount']) }} {{ $row['closing_side'] }}</td>
                    </tr>
                @endforeach
                <tr class="group-row">
                    <td colspan="4" class="text-right">{{ $group['name'] }} Total</td>
                    <td class="text-right">{{ money_value($group['debit']) }}</td>
                    <td class="text-right">{{ money_value($group['credit']) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endforeach
@endsection
