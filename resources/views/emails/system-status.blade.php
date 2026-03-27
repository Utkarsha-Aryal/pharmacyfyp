<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $mailSubject ?? $title }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background: #f8fbff; margin: 0; padding: 24px;">
    <div style="max-width: 620px; margin: 0 auto; background: #ffffff; border: 1px solid #dbe5f0; border-radius: 16px; overflow: hidden;">
        <div style="padding: 22px 24px; background: #0f172a; color: #ffffff;">
            <h1 style="margin: 0; font-size: 22px;">{{ setting('app_name', 'Pharmacy Management System') }}</h1>
            <p style="margin: 8px 0 0; color: rgba(255,255,255,0.82);">{{ $title }}</p>
        </div>

        <div style="padding: 24px;">
            <p style="margin-top: 0; color: #334155;">{{ $intro }}</p>

            @if (!empty($lines))
                <ul style="padding-left: 18px; color: #334155;">
                    @foreach ($lines as $line)
                        <li style="margin-bottom: 8px;">{{ $line }}</li>
                    @endforeach
                </ul>
            @endif

            <p style="margin-bottom: 0; color: #64748b; font-size: 13px;">
                Generated on {{ now()->format('M j, Y h:i A') }}
            </p>
        </div>
    </div>
</body>
</html>
