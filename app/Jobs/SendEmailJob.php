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

    protected $data;
    protected $batchNo;

    public function __construct($data, $batchNo)
    {
        $this->data = $data;
        $this->batchNo = $batchNo;
    }

    public function handle(): void
    {
        $recipient = $this->data['recipients'][0];
        $subject   = $this->data['subject'];
        $body      = $this->data['body'];
        $attachments = $this->data['attachments'] ?? [];

        try {
            Mail::to($recipient)->send(
                new EmailCampaign($subject, $body, $attachments)
            );

            EmailCampaignLog::create([
                'recipient' => $recipient,
                'success'   => true,
                'batch_no'  => $this->batchNo
            ]);

            Log::info("Email {$this->batchNo}/500 sent successfully to {$recipient}");
        } catch (\Throwable $e) {
            EmailCampaignLog::create([
                'recipient'     => $recipient,
                'success'       => false,
                'error_message' => $e->getMessage(),
                'batch_no'      => $this->batchNo
            ]);

            Log::error("Failed to send email {$this->batchNo}/500 to {$recipient}: " . $e->getMessage());
        }
    }
}

