<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendNewPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $newPassword;
    /**
     * Create a new message instance.
     */
    public function __construct(string $name, string $password)
    {
        //
        $this->name = $name;
        $this->newPassword = $password;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Password Has Been Reset',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new_password',
            with: [
                'name'        => $this->user->name,
                'email'       => $this->user->email,
                'newPassword' => $this->newPassword,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
