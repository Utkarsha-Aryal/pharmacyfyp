<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #0f172a;
            margin: 24px;
        }

        .report-header {
            margin-bottom: 18px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 10px;
        }

        .report-header h1 {
            margin: 0 0 4px;
            font-size: 20px;
        }

        .report-header p {
            margin: 0;
            color: #475569;
        }

        .meta {
            margin-top: 10px;
            font-size: 10px;
            color: #475569;
        }

        .meta span {
            margin-right: 14px;
        }

        .summary-table,
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .summary-table td,
        .report-table th,
        .report-table td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            vertical-align: top;
        }

        .report-table th {
            background: #eff6ff;
            font-weight: 700;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .muted {
            color: #64748b;
        }

        .section-title {
            margin-top: 20px;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        .group-row td,
        .total-row td,
        .footer-total td {
            background: #f8fafc;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>{{ setting('app_name', 'Pharmacy Management System') }}</h1>
        <p>@yield('report-subtitle')</p>
        <div class="meta">
            @yield('report-meta')
        </div>
    </div>

    @yield('content')
</body>
</html>
