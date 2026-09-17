<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\NotificationChannelController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketEscalationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthTokenController::class, 'store'])->middleware('throttle:10,1');
Route::prefix('tickets')->group(function () {
    Route::get('/', [TicketController::class, 'index']);
    Route::get('/{ticket}', [TicketController::class, 'show']);

    Route::post('/{ticket}/escalate', [TicketEscalationController::class, 'store'])
        ->middleware('auth:sanctum');
});

Route::get('/notification-channels', [NotificationChannelController::class, 'index']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::delete('/logout', [AuthTokenController::class, 'destroy'])->middleware('auth:sanctum');
