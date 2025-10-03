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

    // Max retries before failing permanently
    public $tries = 1;

    // Time before job is retried (in seconds)
    public $backoff = 30;

    public function __construct(public array $data) {}

    public function handle(): void
    {
        $recipient = $this->data['recipients'][0];
        $subject   = $this->data['subject'];
        $body      = $this->data['body'];
        $attachments = $this->data['attachments'] ?? [];

        for ($i = 1; $i <= 500; $i++) {
            try {
                Mail::to($recipient)->send(
                    new EmailCampaign(
                        $subject,
                        $body,
                        $attachments
                    )
                );

                // Log success in DB
                EmailCampaignLog::create([
                    'recipient' => $recipient,
                    'success'   => true,
                    'batch_no'  => $i // optional: kaun sa mail hai 1..500
                ]);

                Log::info("Email {$i}/500 sent successfully to {$recipient}");
            } catch (\Throwable $e) {
                // Log failure in DB
                EmailCampaignLog::create([
                    'recipient'     => $recipient,
                    'success'       => false,
                    'error_message' => $e->getMessage(),
                    'batch_no'      => $i
                ]);

                Log::error("Failed to send email {$i}/500 to {$recipient}: " . $e->getMessage());
            }
        }
    }



    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void { 
        Log::critical("SendEmailJob failed after {$this->tries} attempts. Error: " . $exception->getMessage());
    }
}
