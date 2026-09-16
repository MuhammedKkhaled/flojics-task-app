<?php

namespace App\Http\Controllers\Api;

use App\Notifications\ChannelManager;
use Illuminate\Http\JsonResponse;

class NotificationChannelController extends BaseApiController
{
    public function index(ChannelManager $channels): JsonResponse
    {
        return $this->addToResponse(['data' => $channels->catalog()])->toResponse();
    }
}
