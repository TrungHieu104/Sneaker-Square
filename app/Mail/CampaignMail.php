<?php

namespace App\Mail;

use App\Models\CampaignRecipientModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CampaignMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public CampaignRecipientModel $recipient) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->recipient->campaign->subject);
    }

    /**
     * Mail clients show their own unsubscribe button when this is present.
     */
    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.route('campaign.unsubscribe', $this->recipient->token).'>',
        ]);
    }

    public function content(): Content
    {
        $coupon = $this->recipient->coupon;

        return new Content(
            view: 'mail.campaign',
            with: [
                'name' => $this->recipient->user?->name,
                'lead' => $this->recipient->campaign->message,
                'percent' => $coupon?->coupon_value,
                'minOrder' => $coupon?->coupon_min_order,
                'maxDiscount' => $coupon?->coupon_max_discount,
                'code' => $coupon?->coupon_code,
                'expires' => $coupon ? Carbon::parse($coupon->coupon_end) : null,
                'visitUrl' => route('campaign.visit', $this->recipient->token),
                'unsubscribeUrl' => route('campaign.unsubscribe', $this->recipient->token),
                'pixelUrl' => route('campaign.opened', $this->recipient->token),
            ],
        );
    }
}
