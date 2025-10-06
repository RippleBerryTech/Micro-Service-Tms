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

        // Split all recipients into batches of 15
        $chunks = array_chunk($data['recipients'], 15);

        // Dispatch each batch with a 1-minute delay between them
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
            'message' => count($data['recipients']) . ' emails queued successfully (15 per minute).'
        ]);
    }

}