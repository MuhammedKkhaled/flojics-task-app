<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Notifications\ChannelManager;
use Illuminate\Http\JsonResponse;

class NotificationChannelController extends Controller
{
    public function index(ChannelManager $channels): JsonResponse
    {
        return response()->json(['data' => $channels->catalog()]);
    }
}
