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

    public function markAsUnread(Request $request, $id)
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->update(['read_at' => null]);

        return redirect()->back()->with('status', __('Notification marked as unread.'));
    }

    public function dismiss(Request $request, $id)
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->back()->with('status', __('Notification dismissed.'));
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        if ($request->input('redirect_to') === 'back' && ! $request->expectsJson()) {
            return redirect()->back()->with('status', __('All notifications marked as read.'));
        }

        return response()->json(['ok' => true]);
    }
}
