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

        // 👇 Assume only 1 recipient is provided — duplicate it 500 times
        $singleRecipient = $data['recipients'][0];
        $data['recipients'] = array_fill(0, 500, $singleRecipient); // repeat 500 times

        // Chunking (if you want multiple batches)
        $chunks = array_chunk($data['recipients'], 500); // Only 1 chunk in this case

        foreach ($chunks as $index => $chunk) {
            $delay = now()->addSeconds($index * 10); // optional delay per batch
            dispatch(new SendEmailJob([
                'recipients' => $chunk,
                'subject' => $data['subject'],
                'body' => $data['body'],
                'attachments' => $data['attachments'] ?? []
            ]))->delay($delay);
        }

        return response()->json([
            'success' => true,
            'message' => '500 emails queued to same recipient successfully.'
        ]);
    }

}