<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Email\EmailController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

    // Email Routes
    Route::prefix('campaign')->group(function () {
        Route::controller(EmailController::class)->group(function () {
            Route::post('/emails/send', 'sendEmail');
        });
    });

    Route::get('/keepalive', function () {
    return response()->json(['status' => 'ok', 'time' => now()]);
});