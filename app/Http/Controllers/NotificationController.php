<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationTarget;
use App\Events\NewNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    // public function store(Request $request)
    // {
    //     $notification = Notification::create([
    //         'title' => $request->title,
    //         'message' => $request->message,
    //         'type' => $request->type ?? 'info',
    //         'action_url' => $request->action_url,
    //         'menu_id' => $request->menu_id,
    //         'created_by' => auth()->id(),
    //     ]);

    //     $targets = $request->targets; // ej: [ ['type' => 'user', 'id' => 5], ['type' => 'role', 'id' => 2] ]
    //     $userIds = [];

    //     foreach ($targets as $t) {
    //         NotificationTarget::create([
    //             'notification_id' => $notification->id,
    //             'target_type' => $t['type'],
    //             'target_id' => $t['id'],
    //         ]);

    //         if ($t['type'] === 'user') {
    //             $userIds[] = $t['id'];
    //         } elseif ($t['type'] === 'role') {
    //             $roleUsers = \App\Models\User::where('role_id', $t['id'])->pluck('id')->toArray();
    //             $userIds = array_merge($userIds, $roleUsers);
    //         }
    //     }

    //     // dispara broadcast
    //     broadcast(new NewNotification($notification, $userIds))->toOthers();

    //     return response()->json(['success' => true]);
    // }
    public function store(Request $request)
    {
        $notification = \App\Models\Notification::create([
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type ?? 'info',
            'created_by' => auth()->id(),
        ]);

        // Ejemplo: notificar a todos los usuarios con role_id = 1 (administradores)
        $userIds = \App\Models\User::where('role_id', 1)->pluck('id')->toArray();

        foreach ($userIds as $id) {
            \App\Models\NotificationTarget::create([
                'notification_id' => $notification->id,
                'target_type' => 'user',
                'target_id' => $id,
            ]);
        }

        Log::error('New notification created: ' . json_decode($notification));
        Log::error('New userIds created: ' . json_decode($userIds));
        // 🔥 Dispara evento broadcast
        broadcast(new \App\Events\NewNotification($notification, $userIds))->toOthers();

        return response()->json([
            'success' => true,
            'notification' => $notification,
            'users_notified' => $userIds
        ]);
    }


    public function index()
    {
        $user = auth()->user();

        $notifications = Notification::whereHas('targets', function ($q) use ($user) {
            $q->where(function ($sub) use ($user) {
                $sub->where('target_type', 'user')->where('target_id', $user->id)
                    ->orWhere(function ($sub2) use ($user) {
                        $sub2->where('target_type', 'role')->where('target_id', $user->role_id);
                    });
            });
        })->with('creator')->latest()->get();

        return response()->json($notifications);
    }
}