<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $mailSubject }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background: #f8fbff; margin: 0; padding: 24px;">
    <div style="max-width: 680px; margin: 0 auto; background: #ffffff; border: 1px solid #dbe5f0; border-radius: 16px; overflow: hidden;">
        <div style="padding: 22px 24px; background: #0f172a; color: #ffffff;">
            <h1 style="margin: 0; font-size: 22px;">{{ setting('app_name', 'Pharmacy Management System') }}</h1>
            <p style="margin: 8px 0 0; color: rgba(255,255,255,0.82);">Current notification summary from the admin panel.</p>
        </div>

        <div style="padding: 24px;">
            <p style="margin-top: 0; color: #334155;">These are the same alerts currently shown in the notification tray.</p>

            @foreach ($notifications as $notification)
                <div style="border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; margin-bottom: 14px;">
                    <div style="display: flex; justify-content: space-between; gap: 12px; align-items: start;">
                        <div>
                            <h2 style="margin: 0 0 6px; font-size: 16px; color: #0f172a;">{{ $notification['title'] }}</h2>
                            <p style="margin: 0; color: #475569; line-height: 1.6;">{{ $notification['message'] }}</p>
                        </div>
                        <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #ffffff; background: {{ $notification['color'] === 'danger' ? '#dc2626' : ($notification['color'] === 'warning' ? '#f59e0b' : ($notification['color'] === 'info' ? '#2563eb' : '#16a34a')) }}; border-radius: 999px; padding: 6px 10px;">
                            {{ ucfirst($notification['color']) }}
                        </span>
                    </div>

                    @if (!empty($notification['url']))
                        <div style="margin-top: 12px;">
                            <a href="{{ $notification['url'] }}" style="display: inline-block; text-decoration: none; background: #2563eb; color: #ffffff; border-radius: 10px; padding: 10px 14px; font-size: 13px; font-weight: 600;">
                                Open in admin panel
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach

            <p style="margin-bottom: 0; color: #64748b; font-size: 13px;">
                Generated on {{ now()->format('M j, Y h:i A') }}
            </p>
        </div>
    </div>
</body>
</html>
