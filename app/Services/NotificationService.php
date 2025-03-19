<?php

namespace App\Services;

use App\Jobs\SendEmailNotification;
use App\Mail\CustomMail;
use App\Models\Notification;
use App\Models\ServiceRequest;
use App\Helpers\SlaHelper;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function sendNotification($recipients, $cc, $subject, $template, $data, $serviceRequestId = null, $queue = false)
    {
        $notification = Notification::create([
            'service_request_id' => $serviceRequestId,
            'recipients' => json_encode(['email' => $recipients, 'email_cc' => $cc]),
            'subject' => $subject,
            'template' => $template,
            'large_content' => json_encode($data),
            'status' => 'pending',
        ]);

        if ($queue) {
            dispatch(new SendEmailNotification($notification));
        } else {
            Mail::to($recipients)
                ->cc($cc)
                ->send(new CustomMail($subject, $template, $data));

            $notification->update(['status' => 'sent', 'sent_at' => now()]);
        }

        return $notification;
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

        return $this->sendNotification($recipients, $cc, $subject, $template, $data, $sr_id, $queue);
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

        return $this->sendNotification($recipients, $cc, $subject, $template, $data, $sr_id, $queue);
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