<?php

namespace App\Mail;

use App\Models\Broadcast;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Broadcast $broadcast) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->broadcast->subject ?: $this->broadcast->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.broadcast',
            with: ['broadcast' => $this->broadcast],
        );
    }
}
