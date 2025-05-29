<?php

namespace App\Http\Controllers;

use App\Models\SensorNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // SOLUTION 1: Render page immediately without data
        return Inertia::render('Notifications', [
            'initialNotifications' => [], // Empty - load via AJAX
            'hasMore' => true,
            'currentPage' => 1
        ]);
    }

    // SOLUTION 2: Load data via separate API call
    public function loadMore(Request $request)
    {
        $limit = $request->query('limit', 20); // Reduce default limit
        $page = $request->query('page', 1);

        // Add database indexing for better performance
        $notifications = SensorNotification::latest()
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get(['id', 'device_id', 'type', 'message', 'translation_key', 'translation_params', 'created_at']);

        return response()->json([
            'notifications' => $notifications->map(fn($n) => [
                'id' => $n->id,
                'device_id' => $n->device_id,
                'type' => $n->type,
                'message' => $n->message,
                'translation_key' => $n->translation_key,
                'translation_params' => $n->translation_params,
                'timestamp' => $n->created_at->toIso8601String(),
                'created_at' => $n->created_at->timestamp
            ]),
            'hasMore' => SensorNotification::count() > ($page * $limit)
        ]);
    }

    // Mark notifications as read (optional feature)
    public function markAsRead(Request $request)
    {
        $ids = $request->input('ids', []);

        SensorNotification::whereIn('id', $ids)
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
