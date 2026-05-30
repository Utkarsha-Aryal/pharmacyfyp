<?php

namespace App\Console\Commands;

use App\Mail\SystemStatusMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    protected $signature = 'mail:test {recipient? : Optional email address for the test mail}';

    protected $description = 'Send one SMTP test mail using database settings first, then env fallback.';

    // This command helps us verify SMTP from terminal without opening the settings page.
    public function handle(): int
    {
        $recipient = $this->argument('recipient') ?: notification_email_address();

        if (empty($recipient)) {
            $this->error('No recipient found. Add notification email or pass one as argument.');

            return self::FAILURE;
        }

        $mailSettings = apply_runtime_mail_settings();

        $missingFields = missing_smtp_mail_settings($mailSettings);

        if (!empty($missingFields)) {
            $this->error(implode(', ', $missingFields) . ' required before sending test mail.');

            return self::FAILURE;
        }

        try {
            Mail::to($recipient)->send(new SystemStatusMail(
                mailSubject: 'SMTP Test Mail',
                title: 'SMTP connection is working',
                intro: 'This is a test mail from the pharmacy management system command.',
                lines: [
                    'Mail host: ' . ($mailSettings['host'] ?: 'Not set'),
                    'Mail port: ' . ($mailSettings['port'] ?: 'Not set'),
                    'Generated from: artisan mail:test',
                ]
            ));
        } catch (\Throwable $throwable) {
            $this->error('Test mail failed: ' . $throwable->getMessage());

            return self::FAILURE;
        }

        $this->info('Test mail sent to ' . $recipient . '.');

        return self::SUCCESS;
    }
}
