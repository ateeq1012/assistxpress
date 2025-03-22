<?php

namespace App\Services;

use App\Jobs\SendEmailNotification;
use App\Jobs\SendWhatsAppNotification;
use App\Mail\CustomMail;
use App\Models\Notification;
use App\Models\ServiceRequest;
use App\Helpers\SlaHelper;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function sendNotification($recipients, $cc, $subject, $template, $data, $serviceRequestId = null, $queue = false, $sr = null)
    {
        $recipientData = [
            'email' => $recipients,
            'email_cc' => $cc,
            'whatsapp' => $this->extractWhatsAppRecipients($sr), // Extract WhatsApp numbers
        ];

        $notification = Notification::create([
            'service_request_id' => $serviceRequestId,
            'recipients' => json_encode($recipientData),
            'subject' => $subject,
            'template' => $template,
            'large_content' => json_encode($data),
            'status' => 'pending',
        ]);

        if ($queue) {
            dispatch(new SendEmailNotification($notification));
            dispatch(new SendWhatsAppNotification($notification));
        } else {
            try {
                // Email Notification
                Mail::to($recipients)
                    ->cc($cc)
                    ->send(new CustomMail($subject, $template, $data));

                // WhatsApp Notification (synchronous)
                $this->sendWhatsAppNotificationSync($notification);

                $notification->update(['status' => 'sent', 'sent_at' => now()]);
            } catch (\Exception $e) {
                $notification->update([
                    'status' => 'failed',
                    'logs' => $e->getMessage(),
                ]);
            }
        }

        return $notification;
    }

    private function sendWhatsAppNotificationSync(Notification $notification)
    {
        $recipients = json_decode($notification->recipients, true);
        $whatsappRecipients = $recipients['whatsapp'] ?? [];

        if (empty($whatsappRecipients)) {
            $notification->update(['logs' => $notification->logs . "\nNo WhatsApp recipients provided"]);
            return;
        }

        $data = json_decode($notification->large_content, true);
        $message = $this->buildWhatsAppMessage($data);

        foreach ($whatsappRecipients as $chatId) {
            try {
                if(env('WHATSAPP_API_URL') != '' && env('WHATSAPP_API_URL') != null) {
                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])->post(env('WHATSAPP_API_URL'), [
                        'chatId' => $chatId,
                        'reply_to' => null,
                        'text' => $message,
                        'session' => 'default',
                    ]);
                }

                if ($response->failed()) {
                    throw new \Exception("WhatsApp API failed for $chatId: " . $response->body());
                }

                $notification->update(['logs' => $notification->logs . "\nWhatsApp sent to $chatId"]);
            } catch (\Exception $e) {
                $notification->update(['logs' => $notification->logs . "\nWhatsApp failed for $chatId: " . $e->getMessage()]);
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

    private function extractWhatsAppRecipients($sr = null)
    {
        if (!$sr) return []; // Return empty if no SR provided

        $phones = [];
        if ($sr->creator && $sr->creator->phone) {
            $phones[] = $sr->creator->phone; // Assuming phone column exists
        }
        if ($sr->executor && $sr->executor->phone) {
            $phones[] = $sr->executor->phone;
        }
        return ["923160514938@c.us"]; // testing
        return $phones ?: ["923160514938@c.us"];
    }

    public function serviceRequestCreated($sr_id, $queue = false)
    {
        $sr = ServiceRequest::with('status', 'priority', 'serviceDomain', 'service', 'creator', 'executor', 'creatorGroup', 'executorGroup', 'serviceRequestCustomField', 'updater', 'sla')
            ->findOrFail($sr_id);

        $slaInfo = null;
        if (isset($sr->sla)) {
            $slaInfo = SlaHelper::getSlaInfo($sr->status, $sr, $sr->sla);
        }

        $srArray = $sr->toArray();
        $srArray['sla_calculations'] = $slaInfo;

        $subject = "New Service Request" . ($srArray['subject'] ? ": " . $srArray['subject'] : "");
        $template = 'emails.sr_notification';
        $data = [
            'emailTitle' => 'New Service Request',
            'salutation' => "Dear Concerned, \n\n A new Service Request is assigned to your user group.",
            'ending' => "Regards,\n INX Helpdesk",
            'sr' => $srArray,
            'actionText' => 'Go to Service Request',
            'actionUrl' => route('service_requests.edit', ['service_request' => $srArray['id']]),
        ];

        $recipients = $this->getNewServiceRequestRecipients($sr);
        $cc = $this->getNewServiceRequestCC($sr);

        return $this->sendNotification($recipients, $cc, $subject, $template, $data, $sr_id, $queue, $sr);
    }

    public function serviceRequestUpdated($sr_id, $queue = false)
    {
        $sr = ServiceRequest::with('status', 'priority', 'serviceDomain', 'service', 'creator', 'executor', 'creatorGroup', 'executorGroup', 'serviceRequestCustomField', 'updater', 'sla')
            ->findOrFail($sr_id);

        $slaInfo = null;
        if (isset($sr->sla)) {
            $slaInfo = SlaHelper::getSlaInfo($sr->status, $sr, $sr->sla);
        }

        $srArray = $sr->toArray();
        $srArray['sla_calculations'] = $slaInfo;

        $subject = "Service Request Updated" . ($srArray['subject'] ? ": " . $srArray['subject'] : "");
        $template = 'emails.sr_notification';
        $data = [
            'emailTitle' => 'Service Request Updated',
            'salutation' => "Dear Concerned, \n\n Below Service Request was updated.",
            'ending' => "Regards,\n INX Helpdesk",
            'sr' => $srArray,
            'actionText' => 'Go to Service Request',
            'actionUrl' => route('service_requests.edit', ['service_request' => $srArray['id']]),
        ];

        $recipients = $this->getUpdatedServiceRequestRecipients($sr);
        $cc = $this->getUpdatedServiceRequestCC($sr);

        return $this->sendNotification($recipients, $cc, $subject, $template, $data, $sr_id, $queue, $sr);
    }

    private function getNewServiceRequestRecipients($sr)
    {
        $emails = [];
        if ($sr->creator && $sr->creator->email) {
            $emails[] = $sr->creator->email;
        }
        if ($sr->executor && $sr->executor->email) {
            $emails[] = $sr->executor->email;
        }
        return $emails ?: ['default@innexiv.com'];
    }

    private function getNewServiceRequestCC($sr)
    {
        return $sr->creatorGroup && $sr->creatorGroup->email ? [$sr->creatorGroup->email] : [];
    }

    private function getUpdatedServiceRequestRecipients($sr)
    {
        $emails = [];
        if ($sr->updater && $sr->updater->email) {
            $emails[] = $sr->updater->email;
        }
        if ($sr->executor && $sr->executor->email) {
            $emails[] = $sr->executor->email;
        }
        return $emails ?: ['default@innexiv.com'];
    }

    private function getUpdatedServiceRequestCC($sr)
    {
        return $sr->executorGroup && $sr->executorGroup->email ? [$sr->executorGroup->email] : [];
    }
}
