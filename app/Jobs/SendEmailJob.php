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

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min
    public $timeout = 120;

    // SMTP rate limiting constants
    const MAX_EMAILS_PER_BATCH = 10;
    const DELAY_BETWEEN_EMAILS = 5; // seconds between each email in batch

    public function __construct(public array $data) {}

    public function handle(): void
    {
        $successCount = 0;
        $failCount = 0;

        foreach ($this->data['recipients'] as $index => $recipient) {
            try {
                // Add delay between individual emails in the same batch
                if ($index > 0) {
                    sleep(self::DELAY_BETWEEN_EMAILS);
                }

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
                    'sent_at'   => now(),
                ]);

                $successCount++;
                Log::info("Email sent successfully to {$recipient}");

            } catch (\Throwable $e) {
                $failCount++;
                
                EmailCampaignLog::create([
                    'recipient'     => $recipient,
                    'success'       => false,
                    'error_message' => $e->getMessage(),
                    'sent_at'       => now(),
                ]);

                Log::error("Failed to send email to {$recipient}: " . $e->getMessage());

                // If it's a rate limit error, release the job with longer delay
                if ($this->isRateLimitError($e)) {
                    $this->handleRateLimitError($e);
                }
            }
        }

        Log::info("SendEmailJob completed: {$successCount} successful, {$failCount} failed");
    }

    /**
     * Check if the error is a rate limit error
     */
    private function isRateLimitError(\Throwable $e): bool
    {
        $rateLimitIndicators = [
            '451 4.7.1',
            'Too many mails',
            'Rate limit exceeded',
            'Quota exceeded',
            'Too many requests',
            '421 4.7.0',
        ];

        foreach ($rateLimitIndicators as $indicator) {
            if (str_contains($e->getMessage(), $indicator)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle rate limit errors with exponential backoff
     */
    private function handleRateLimitError(\Throwable $e): void
    {
        $attempts = $this->attempts();
        
        // Exponential backoff: 5min, 15min, 30min
        $delay = min(300 * pow(2, $attempts - 1), 1800); // Max 30 minutes
        
        Log::warning("SMTP rate limit hit. Attempt {$attempts}. Retrying in {$delay} seconds.");
        
        $this->release($delay);
    }

    /**
     * Determine if job should be retried
     */
    public function shouldRetry(\Throwable $e): bool
    {
        // Retry on rate limits and temporary failures
        return $this->isRateLimitError($e) || 
               str_contains($e->getMessage(), 'Swift_TransportException') ||
               str_contains($e->getMessage(), 'Connection');
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("SendEmailJob failed after {$this->tries} attempts. Error: " . $exception->getMessage());
        
        // Log final failure for all recipients in this batch
        foreach ($this->data['recipients'] as $recipient) {
            EmailCampaignLog::firstOrCreate(
                ['recipient' => $recipient],
                [
                    'success' => false,
                    'error_message' => 'Final attempt failed: ' . $exception->getMessage(),
                    'sent_at' => now(),
                ]
            );
        }
    }
}