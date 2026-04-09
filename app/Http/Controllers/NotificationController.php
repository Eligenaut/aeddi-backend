<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $limit = (int) $request->query('limit', 20);
        $limit = max(1, min($limit, 50));

        $items = UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->paginate($limit);

        $unreadCount = UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'unread_count' => $unreadCount,
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function poll(Request $request)
    {
        $user = $request->user();
        $afterId = (int) $request->query('after_id', 0);
        $limit = (int) $request->query('limit', 20);
        $limit = max(1, min($limit, 50));

        $newItems = UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id', 'asc')
            ->take($limit)
            ->get();

        $unreadCount = UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data' => $newItems,
            'meta' => [
                'unread_count' => $unreadCount,
                'max_id' => (int) ($newItems->last()?->id ?? $afterId),
            ],
        ]);
    }

    public function markRead(Request $request, int $id)
    {
        $user = $request->user();

        $notif = UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->findOrFail($id);

        if ($notif->read_at === null) {
            $notif->read_at = Carbon::now();
            $notif->save();
        }

        return response()->json(['success' => true]);
    }

    public function markUnread(Request $request, int $id)
    {
        $user = $request->user();

        $notif = UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->findOrFail($id);

        if ($notif->read_at !== null) {
            $notif->read_at = null;
            $notif->save();
        }

        return response()->json(['success' => true]);
    }

    public function readAll(Request $request)
    {
        $user = $request->user();

        UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();

        $notif = UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->findOrFail($id);

        $notif->delete();

        return response()->json(['success' => true]);
    }
}

