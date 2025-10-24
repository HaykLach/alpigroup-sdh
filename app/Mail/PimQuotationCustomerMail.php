<?php

namespace App\Mail;

use App\Models\Pim\Customer\PimAgent;
use App\Models\Pim\Customer\PimCustomer;
use App\Models\Pim\PimQuotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PimQuotationCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected PimQuotation $quotation,
        protected string $pdfPath,
        protected Collection $mediaAttachments,
        protected string $emailContent = '',
    ) {}

    public function envelope(): Envelope
    {
        /** @var PimAgent $agent */
        $agent = $this->quotation->agents->first();
        /** @var PimCustomer $customer */
        $customer = $this->quotation->customers->first();

        return new Envelope(
            from: new Address($agent->email, $agent->full_name),
            to: [$customer->email],
            subject: __('Ihr Angebot :number vom :date', [
                'number' => $this->quotation->formatted_quotation_number,
                'date' => $this->quotation->date->format('d.m.Y'),
            ])
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quotation',
            with: [
                'content' => $this->emailContent,
            ],
        );
    }

    public function attachments(): array
    {
        $mediaAttachments = [
            Attachment::fromPath($this->pdfPath)
                ->as(basename($this->pdfPath))
                ->withMime('application/pdf'),
        ];

        $this->mediaAttachments->each(function ($attachment) use (&$mediaAttachments) {
            $mediaAttachments[] = Attachment::fromPath($attachment->getPath())
                ->as($attachment->file_name)
                ->withMime($attachment->mime_type);
        });

        return $mediaAttachments;
    }
}
