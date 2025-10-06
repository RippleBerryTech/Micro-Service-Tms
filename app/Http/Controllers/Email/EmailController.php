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

        // Repeat the same recipient 500 times
        $recipient = $data['recipients'][0];
        $recipients = array_fill(0, 500, $recipient);

        // Split into batches of 15
        $chunks = array_chunk($recipients, 15);

        // Dispatch each batch 1 minute apart
        foreach ($chunks as $index => $chunk) {
            dispatch(new SendEmailJob([
                'recipients'   => $chunk,
                'subject'      => $data['subject'],
                'body'         => $data['body'],
                'attachments'  => $data['attachments'] ?? []
            ]))->delay(now()->addMinutes($index));
        }

        return response()->json([
            'success' => true,
            'message' => '500 emails queued successfully (15 per minute).'
        ]);
    }
}