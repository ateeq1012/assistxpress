<?php

namespace App\Jobs;

use App\Mail\CustomMail;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function handle(): void
    {
        try {
            $this->notification->update(['status' => 'processing']);

            $recipients = json_decode($this->notification->recipients, true);
            $emailRecipients = $recipients['email'] ?? [];
            $cc = $recipients['email_cc'] ?? [];

            if (empty($emailRecipients)) {
                $this->notification->update(['status' => 'skipped', 'logs' => 'No email recipients provided']);
                return;
            }

            $data = json_decode($this->notification->large_content, true);

            // Extract template from data or use a default
            $template = $data['template'] ?? 'emails.simple';

            Mail::to($emailRecipients)
                ->cc($cc)
                ->queue(new CustomMail(
                    $this->notification->subject,
                    $this->notification->template,
                    $data
                ));

            $this->notification->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Exception $e) {
            $this->notification->update([
                'status' => 'failed',
                'logs' => $e->getMessage(),
            ]);
        }
    }
}