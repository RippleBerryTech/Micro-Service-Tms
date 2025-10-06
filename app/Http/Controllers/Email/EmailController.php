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

        // Assuming only one recipient provided
        $recipient = $data['recipients'][0];

        // Create an array with the same recipient repeated 500 times
        $recipients = array_fill(0, 500, $recipient);

        // Dispatch job (it will handle the 15/min rate)
        dispatch(new SendEmailJob([
            'recipients' => $recipients,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'attachments' => $data['attachments'] ?? []
        ]));

        return response()->json([
            'success' => true,
            'message' => '500 emails queued successfully for the same recipient.'
        ]);
    }

}