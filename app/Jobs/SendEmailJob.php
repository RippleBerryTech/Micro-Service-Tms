<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailCampaign;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $data) {}

    public function handle(): void
    {
        foreach ($this->data['recipients'] as $recipient) {
            Mail::to($recipient)->send(
                new EmailCampaign(
                    $this->data['subject'],
                    $this->data['body'],
                    $this->data['attachments'] ?? []
                )
            );
        }
    }
}
