<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\ProductModel;
use App\Models\User;

class ProductCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public ProductModel $product;
    public User $user;
    public string $paymentUrl;
    /**
     * Create a new message instance.
     */
    public function __construct(ProductModel $product, User $user)
    {
        $this->product = $product;
        $this->user = $user;

        // Payment link (as you requested)
        $this->paymentUrl = "https://stockoutindia.com/pages/make-payment?product_id=" . $product->id;
    }

    public function build()
    {
        return $this->subject('New Product Created: ' . ($this->product->product_name ?? 'Product'))
            ->markdown('emails.products.created', [
                'product' => $this->product,
                'user' => $this->user,
                'paymentUrl' => $this->paymentUrl,
            ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Product Created Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.products.created',
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
