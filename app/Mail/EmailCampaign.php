<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailCampaign extends Mailable
{
    use Queueable, SerializesModels;

    public string $subjectTitle;
    public string $body;
    public ?array $attachmentFiles;

    public function __construct(string $subjectTitle, string $body, ?array $attachmentFiles = null)
    {
        $this->subjectTitle = $subjectTitle;
        $this->body = $body;
        $this->attachmentFiles = $attachmentFiles;
    }

    public function build()
    {
        $email = $this->subject($this->subjectTitle)
            ->view('emails.campaign.email.email_campaign')
            ->with(['body' => $this->body]);

        if (!empty($this->attachmentFiles)) {
            foreach ($this->attachmentFiles as $file) {
                try {
        $fileContents = file_get_contents($file['url']);

        if ($fileContents === false) {
            \Log::error("file_get_contents failed", ['url' => $file['url']]);
            continue; // skip this file
        }

        $fileName = $file['name'] ?? basename($file['url']);
        $mimeType = $file['mime'] ?? 'application/octet-stream';

        $email->attachData($fileContents, $fileName, [
            'mime' => $mimeType,
        ]);

        \Log::info("Attachment added", ['file' => $fileName]);

                } catch (\Throwable $e) {
                    \Log::error("Failed to attach file from URL", [
                        'url' => $file['url'],
                        'error' => $e->getMessage()
                    ]);
                }

            }
        }

        return $email;
    }

}
