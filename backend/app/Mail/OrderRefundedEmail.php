<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderRefundedEmail extends Mailable
{
    use SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing(['event', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Order Has Been Refunded - Order #' . $this->order->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.order-refunded',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
