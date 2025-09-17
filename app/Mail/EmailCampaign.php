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
                $email->attachFromUrl(
                    $file['url'], // Public URL
                    $file['name'] ?? null,
                    ['mime' => $file['mime'] ?? null]
                );
            }
        }

        return $email;
    }
}
