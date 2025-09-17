<?php

namespace App\Jobs;


use App\Mail\EmailCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Max retries before failing permanently
    public $tries = 1;

    // Time before job is retried (in seconds)
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

                Log::info("Email sent to {$recipient}");

            } catch (\Throwable $e) {
                Log::error("Failed to send email to {$recipient}: " . $e->getMessage());

                // Optionally rethrow so Laravel retry system can handle it
                throw $e;
            }
        }
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical("SendEmailJob failed after {$this->tries} attempts. Error: " . $exception->getMessage());
    }
}
