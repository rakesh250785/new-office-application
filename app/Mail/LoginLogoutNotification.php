<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginLogoutNotification extends Mailable
{
    use Queueable, SerializesModels;

    public array $details;

    /**
     * Create a new message instance.
     */
    public function __construct(array $details)
    {
        $this->details = $details;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject("{$this->details['event']} Notification")
            ->view('emails.login_logout')
            ->with('details', $this->details);
    }
}
