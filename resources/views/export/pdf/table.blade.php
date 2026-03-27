@extends('finance.pdf.layout')

@section('title', $title)

@section('report-subtitle')
    {{ $subtitle }}
@endsection

@section('report-meta')
    <span>Generated: {{ $generatedAt }}</span>
    <span>Total Rows: {{ $rows->count() }}</span>
@endsection

@section('content')
    <table class="report-table">
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($columns as $column)
                        <td>{{ $row[$column] ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="text-center muted">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
