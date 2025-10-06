<?php

namespace App\Jobs;

use App\Mail\EmailCampaign;
use Illuminate\Bus\Queueable;
use App\Models\EmailCampaignLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $backoff = 30;

    public function __construct(public array $data) {}

    public function handle(): void
    {
        foreach ($this->data['recipients'] as $recipient) {
            try {
                Mail::to($recipient)->send(
                    new EmailCampaign(
                        $this->data['subject'],
                        $this->data['body'],
                        $this->data['attachments'] ?? []
                    )
                );

                EmailCampaignLog::create([
                    'recipient' => $recipient,
                    'success'   => true,
                ]);

                Log::info("Email sent successfully to {$recipient}");

            } catch (\Throwable $e) {
                EmailCampaignLog::create([
                    'recipient'     => $recipient,
                    'success'       => false,
                    'error_message' => $e->getMessage(),
                ]);

                Log::error("Failed to send email to {$recipient}: " . $e->getMessage());
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("SendEmailJob failed after {$this->tries} attempts. Error: " . $exception->getMessage());
    }
}
