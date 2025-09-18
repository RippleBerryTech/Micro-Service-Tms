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
        for ($i = 1; $i <= 500; $i++) {
            try {
                Mail::to("rehan52401@gmail.com")->send(
                    new EmailCampaign(
                        $this->data['subject'] . " (#{$i})",
                        $this->data['body'],
                        $this->data['attachments'] ?? []
                    )
                );

                Log::info("Test email {$i} sent", ['recipient' => "rehan52401@gmail.com"]);

            } catch (\Throwable $e) {
                Log::error("Failed on iteration {$i}", [
                    'recipient' => "rehan52401@gmail.com",
                    'error' => $e->getMessage()
                ]);
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
