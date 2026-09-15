<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthTokenController::class, 'store'])->middleware('throttle:10,1');
Route::get('/tickets', [TicketController::class, 'index']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::delete('/logout', [AuthTokenController::class, 'destroy'])->middleware('auth:sanctum');
