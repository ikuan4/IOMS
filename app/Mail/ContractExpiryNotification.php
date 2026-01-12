<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractExpiryNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Contract $contract;
    public int $daysRemaining;

    /**
     * Create a new message instance.
     */
    public function __construct(Contract $contract, int $daysRemaining)
    {
        $this->contract = $contract;
        $this->daysRemaining = $daysRemaining;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = 'Contract Expiry Notification';

        if ($this->daysRemaining > 0) {
            $subject .= " - {$this->contract->contract_number} (Expiring in {$this->daysRemaining} days)";
        } elseif ($this->daysRemaining === 0) {
            $subject .= " - {$this->contract->contract_number} (Test Notification)";
        } else {
            $subject .= " - {$this->contract->contract_number} (Expired)";
        }

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contract-expiry',
            with: [
                'contract' => $this->contract,
                'daysRemaining' => $this->daysRemaining,
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
