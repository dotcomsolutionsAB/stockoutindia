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
use App\Models\RazorpayOrdersModel;

class ProductListedConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */

    public ProductModel $product;
    public User $user;
    public string $validity;
    public string $razorpay_payment_id;
    public string $mode_of_payment;

    public function __construct(ProductModel $product, User $user, string $validity, string $razorpay_payment_id, string $mode_of_payment)
    {
        $this->product = $product;
        $this->user = $user;
        $this->validity = $validity;
        $this->razorpay_payment_id = $razorpay_payment_id;
        $this->mode_of_payment = $mode_of_payment;
    }

    public function build()
    {
        return $this->subject('Product Listed Successfully: ' . ($this->product->product_name ?? 'Product'))
            ->view('emails.products.product_listed_confirmation', [
                'product' => $this->product,
                'user' => $this->user,
                'validity' => $this->validity,
                'razorpay_payment_id' => $this->razorpay_payment_id,
                'mode_of_payment' => $this->mode_of_payment,
            ]);
    }
    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Product Listed Confirmation Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.product_listed_confirmation',
            with: [
                'name' => $this->name,
                'listingLink' => $this->listingLink,
            ],
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
