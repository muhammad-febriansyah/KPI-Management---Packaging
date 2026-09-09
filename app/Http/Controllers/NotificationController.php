<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markRead(Request $request, string $notification): JsonResponse
    {
        $request->user()->notifications()->whereKey($notification)->first()?->markAsRead();

        return response()->json(['message' => 'ok']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'ok']);
    }
}
