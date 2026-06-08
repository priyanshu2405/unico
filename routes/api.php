<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\Api\KuickpayController;
use App\Http\Controllers\Api\WiseController;
use App\Http\Controllers\Api\WiseWebhookController;

Route::match(['get', 'post'], '/webhook/wise', function (Request $request) {
    \Log::info('Wise Webhook Data:', $request->all());
    return response('OK', 200);
});

// Webhook for Wise payments
Route::post('/wise/webhook', [WiseWebhookController::class, 'handle']);

Route::group(['prefix' => 'v1'], function () {
    Route::post('/BillInquiry', [KuickpayController::class, 'billInquiry']);
    Route::post('/BillPayment', [KuickpayController::class, 'billPayment']);
    Route::post('/wise/send-money', [WiseController::class, 'sendMoney']);
});
