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

        foreach ($data['recipients'] as $index => $recipient) {
            $delay = now()->addMilliseconds($index * 500); // 0.5 sec between each
            dispatch(new SendEmailJob([
                'recipients' => [$recipient],
                'subject' => $data['subject'],
                'body' => $data['body'],
                'attachments' => $data['attachments'] ?? []
            ]))->delay($delay);
        }

        return response()->json([
            'success' => true,
            'message' => 'All emails queued with delays.'
        ]);
    }
}