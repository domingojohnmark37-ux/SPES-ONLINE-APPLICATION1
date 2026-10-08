<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();

            if ($request->input('redirect_to') === 'previous' && $user->role === 'user') {
                return redirect()->route('applicant.notifications.previous');
            }

            if ($request->input('redirect_to') === 'back' && ! $request->expectsJson()) {
                return redirect()->back();
            }

            return response()->json(['ok' => true]);
        }
        return response()->json(['ok' => false], 404);
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();
        return response()->json(['ok' => true]);
    }
}
