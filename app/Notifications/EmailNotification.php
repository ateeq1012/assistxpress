<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailNotification extends Notification
{
    use Queueable;

    protected $subject;
    protected $message;
    protected $cc;

    /**
     * Create a new notification instance.
     */
    public function __construct($subject, $message, $cc)
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->cc = $cc;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
                    ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
                    ->subject($this->subject)
                    ->line($this->message)
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');

        // Add CC if available and ensure it's an array
        if (!empty($this->cc)) {
            $mail->cc(is_array($this->cc) ? $this->cc : [$this->cc]);
        }

        return $mail;
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}