<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Membresia;
use App\Models\MembershipPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipPaymentReceived extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $membresia;
    public $payment;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Membresia $membresia, $payment)
    {
        $this->user = $user;
        $this->membresia = $membresia;
        $this->payment = $payment;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pago de Membresía Recibido',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.membership_payment_received',
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