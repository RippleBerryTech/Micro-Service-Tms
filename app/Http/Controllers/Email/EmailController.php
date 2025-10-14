<?php

namespace App\Http\Controllers\Email;

use App\Jobs\SendEmailJob;
use Illuminate\Http\Request;
use F9Web\ApiResponseHelpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Log;

class EmailController extends Controller
{
    use ApiResponseHelpers;

    public function sendEmail(Request $request)
    {
        $data = $request->only(['recipients', 'subject', 'body', 'attachments']);

        if (empty($data['recipients']) || empty($data['subject'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request payload.'
            ], 400);
        }

        // Remove duplicates and validate emails
        $validRecipients = [];
        foreach ($data['recipients'] as $recipient) {
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $validRecipients[] = $recipient;
            }
        }

        $validRecipients = array_unique($validRecipients);

        // Gmail limits: ~100-150 emails per hour for free accounts
        // Split into smaller batches with proper delays
        $chunks = array_chunk($validRecipients, 10); // Reduced from 15 to 10
        
        // Calculate delays to stay within Gmail limits
        $emailsPerHour = 100; // Conservative limit
        $delayInSeconds = ceil(3600 / $emailsPerHour); // ~36 seconds between batches
        
        foreach ($chunks as $index => $chunk) {
            dispatch(new SendEmailJob([
                'recipients'   => $chunk,
                'subject'      => $data['subject'],
                'body'         => $data['body'],
                'attachments'  => $data['attachments'] ?? []
            ]))->delay(now()->addSeconds($index * $delayInSeconds));
        }

        return response()->json([
            'success' => true,
            'message' => count($validRecipients) . ' emails queued successfully (10 per ' . $delayInSeconds . ' seconds).',
            'total_recipients' => count($validRecipients),
            'estimated_completion_time' => now()->addSeconds(count($chunks) * $delayInSeconds)->diffForHumans()
        ]);
    }

}