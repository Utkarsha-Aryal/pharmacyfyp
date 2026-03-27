<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendNotificationDigestCommand extends Command
{
    protected $signature = 'notifications:send-email {recipient? : Optional email address for the alert digest} {--force-empty : Send even when the tray is empty}';

    protected $description = 'Send the current admin notification tray as an email digest.';

    // This command sends the same alert list that admin sees in the notification tray.
    public function handle(): int
    {
        $recipients = $this->argument('recipient')
            ? [trim((string) $this->argument('recipient'))]
            : notification_email_recipients();
        $notifications = admin_notifications();

        if (empty($recipients)) {
            $this->error('No recipient found. Add notification email or pass one as argument.');

            return self::FAILURE;
        }

        if (empty($notifications) && !$this->option('force-empty')) {
            $this->warn('No notifications found right now. Use --force-empty if you still want a mail test.');

            return self::SUCCESS;
        }

        if (empty($notifications) && $this->option('force-empty')) {
            $primaryRecipient = array_shift($recipients);
            $allRecipients = $primaryRecipient ? array_merge([$primaryRecipient], $recipients) : [];

            if (empty($allRecipients)) {
                $this->error('No recipient found. Add notification email or pass one as argument.');

                return self::FAILURE;
            }

            $sent = send_system_notification_mail(
                subject: 'Admin notification digest',
                title: 'No active notifications right now',
                intro: 'This is a forced digest email from the command line.',
                lines: ['The notification tray is empty at the moment.'],
                recipient: $allRecipients,
            );

            if (!$sent) {
                $this->error('Notification digest could not be sent. Please check SMTP settings and notification email.');

                return self::FAILURE;
            }

            $this->info('Notification digest sent to ' . count($allRecipients) . ' recipient(s).');

            return self::SUCCESS;
        }

        if (!send_admin_notification_digest($recipients)) {
            $this->error('Notification digest could not be sent. Please check SMTP settings and notification email.');

            return self::FAILURE;
        }

        $this->info('Notification digest sent to ' . count($recipients) . ' recipient(s).');

        return self::SUCCESS;
    }
}
