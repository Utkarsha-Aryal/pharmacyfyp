<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password reset OTP</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background: #f8fbff; margin: 0; padding: 24px;">
    <div style="max-width: 620px; margin: 0 auto; background: #ffffff; border: 1px solid #dbe5f0; border-radius: 16px; overflow: hidden;">
        <div style="padding: 22px 24px; background: #0f172a; color: #ffffff;">
            <h1 style="margin: 0; font-size: 22px;">{{ setting('app_name', 'Pharmacy Management System') }}</h1>
            <p style="margin: 8px 0 0; color: rgba(255,255,255,0.82);">Password reset verification</p>
        </div>

        <div style="padding: 24px;">
            <p style="margin-top: 0; color: #334155;">Hello {{ $user->name }},</p>
            <p style="color: #334155;">Use this OTP to reset your admin password. It expires in 15 minutes.</p>

            <div style="font-size: 28px; font-weight: 700; letter-spacing: 8px; color: #0f172a; background: #eef6ff; border: 1px solid #d6e9ff; border-radius: 14px; padding: 16px 18px; text-align: center;">
                {{ $otp }}
            </div>

            <p style="color: #64748b; font-size: 13px; margin-bottom: 0; margin-top: 18px;">
                If you did not request this, you can ignore this email.
            </p>
        </div>
    </div>
</body>
</html>
