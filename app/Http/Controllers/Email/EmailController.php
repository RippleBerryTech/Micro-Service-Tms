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

    $recipient = $data['recipients'][0];

    // 500 jobs dispatch karo, har ek job 10 sec delay ke saath
    for ($i = 1; $i <= 500; $i++) {
        SendEmailJob::dispatch($data, $i)
            ->delay(now()->addSeconds(($i - 1) * 10));
    }

    return response()->json([
        'success' => true,
        'message' => '500 emails queued with 10s delay successfully.'
    ]);
}

}