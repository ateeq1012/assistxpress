<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function handle(): void
    {
        $recipients = json_decode($this->notification->recipients, true);
        $whatsappRecipients = $recipients['whatsapp'] ?? [];

        if (empty($whatsappRecipients)) {
            $this->notification->update(['logs' => $this->notification->logs . "\nNo WhatsApp recipients provided"]);
            return;
        }

        $data = json_decode($this->notification->large_content, true);
        $message = $this->buildWhatsAppMessage($data);

        foreach ($whatsappRecipients as $chatId) {
            try {
                $response = Http::withHeaders([
                    'accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post(env('WHATSAPP_API_URL', 'https://some_url/api/sendText'), [
                    'chatId' => $chatId,
                    'reply_to' => null,
                    'text' => $message,
                    'session' => 'default',
                ]);

                if ($response->failed()) {
                    throw new \Exception("WhatsApp API failed for $chatId: " . $response->body());
                }

                $this->notification->update(['logs' => $this->notification->logs . "\nWhatsApp sent to $chatId"]);
            } catch (\Exception $e) {
                $this->notification->update([
                    'logs' => $this->notification->logs . "\nWhatsApp failed for $chatId: " . $e->getMessage(),
                ]);
            }
        }
    }

    private function buildWhatsAppMessage($data)
    {
        $sr = $data['sr'] ?? [];
        return "Service Request: {$data['emailTitle']}\n" .
               "Subject: " . (isset($sr['subject']) ? $sr['subject'] : 'N/A') . "\n" .
               "Details: {$data['salutation']}\n" .
               "Link: {$data['actionUrl']}\n" .
               "From: INX Helpdesk";
    }
}