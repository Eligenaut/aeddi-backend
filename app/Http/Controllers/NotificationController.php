<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\NotificationService;
use App\Models\User;

class NotificationController extends Controller
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function saveFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token FCM sauvegardé',
        ]);
    }

    public function sendToAll(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'body'  => 'required|string',
        ]);

        $this->notificationService->sendToAll(
            $request->title,
            $request->body,
            $request->data ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Notifications envoyées',
        ]);
    }

    public function sendToUser(Request $request, $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user || !$user->fcm_token) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé ou pas de token FCM',
            ], 404);
        }

        $this->notificationService->sendToDevice(
            $user->fcm_token,
            $request->title,
            $request->body,
            $request->data ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification envoyée',
        ]);
    }
}
