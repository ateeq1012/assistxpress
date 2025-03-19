<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailNotification;
use App\Models\Notification;
use Illuminate\Console\Command;

class ProcessPendingNotifications extends Command
{
    protected $signature = 'notifications:process';
    protected $description = 'Process pending email notifications';

    public function handle()
    {
        $notifications = Notification::where('status', 'pending')->get();

        if ($notifications->isEmpty()) {
            $this->info('No pending notifications to process.');
            return;
        }

        foreach ($notifications as $notification) {
            dispatch(new SendEmailNotification($notification));
            $this->info("Dispatched email job for notification ID: {$notification->id}");
        }

        $this->info('Finished processing pending notifications.');
    }
}