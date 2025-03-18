<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $template;
    public $data;

    /**
     * Create a new message instance.
     */
    public function __construct($subject, $template, $data = [])
    {
        $this->subject = $subject;
        $this->template = $template;
        $this->data = $data;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $mail = $this->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
             ->subject($this->subject)
             ->view($this->template)
             ->with(['data' => $this->data]);

        return $mail;
    }
}