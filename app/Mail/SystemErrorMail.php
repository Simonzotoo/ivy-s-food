<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SystemErrorMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(private readonly string $exceptionClass, private readonly string $details) {}

    public static function forException(Throwable $e): self
    {
        return new self(
            $e::class,
            sprintf(
                "An error occurred in the Ivy's Food system.\n\nType: %s\nMessage: %s\nFile: %s:%d\nTime: %s\n\nFull details are in the server's storage/logs.",
                $e::class,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                now()->toDateTimeString(),
            )
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ivy's Food system error: ".$this->exceptionClass,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.system-error',
            with: ['details' => $this->details],
        );
    }
}
