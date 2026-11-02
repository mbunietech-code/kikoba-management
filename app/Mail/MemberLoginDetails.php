<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A member's sign-in details: username (phone number) and a temporary password.
 */
class MemberLoginDetails extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $memberName,
        public string $groupName,
        public string $username,
        public string $temporaryPassword,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('k.memberLogin.emailSubject', ['org' => $this->groupName], 'sw'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.member-login',
        );
    }
}
