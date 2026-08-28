<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SaleConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Sale $sale) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sale confirmation - '.$this->sale->sale_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sale-confirmation',
            with: ['sale' => $this->sale],
        );
    }
}
