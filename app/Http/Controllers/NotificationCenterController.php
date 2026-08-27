<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Centre de notifications, partagé par le site et l'application.
 *
 * Les notifications en base sont le journal du client : elles arrivent quel
 * que soit son avis, et il ne peut pas les couper. Seul le push — intrusif —
 * dépend de son consentement et d'un appareil enregistré.
 */
class NotificationCenterController extends Controller
{
    /** Journal paginé, non lues d'abord côté compteur. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => $user->notifications()
                ->latest()
                ->take(50)
                ->get()
                ->map(fn ($notification): array => [
                    'id' => $notification->id,
                    'type' => $notification->data['type'] ?? 'info',
                    'title' => $notification->data['title'] ?? '',
                    'body' => $notification->data['body'] ?? '',
                    'url' => $notification->data['url'] ?? null,
                    'read' => $notification->read_at !== null,
                    'createdAt' => $notification->created_at?->toIso8601String(),
                ])
                ->all(),
            'unread' => $user->unreadNotifications()->count(),
        ]);
    }

    /** Marque une notification — ou toutes — comme lue. */
    public function markRead(Request $request, ?string $notification = null): JsonResponse
    {
        $user = $request->user();

        if ($notification === null) {
            $user->unreadNotifications()->update(['read_at' => now()]);
        } else {
            $user->notifications()->where('id', $notification)->update(['read_at' => now()]);
        }

        return response()->json(['unread' => $user->unreadNotifications()->count()]);
    }

    /**
     * Enregistre le jeton d'un appareil.
     *
     * Appelé à chaque lancement : Firebase renouvelle les jetons sans
     * prévenir, et un jeton périmé ferait échouer tous les envois suivants.
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', Rule::in(['android', 'ios', 'web'])],
            'device_name' => ['nullable', 'string', 'max:120'],
        ], attributes: ['token' => 'jeton', 'platform' => 'plateforme']);

        DeviceToken::register(
            $request->user(),
            $data['token'],
            $data['platform'],
            $data['device_name'] ?? null,
        );

        return response()->json(['registered' => true]);
    }

    /** Retire un appareil : plus aucune notification ne lui sera poussée. */
    public function forgetDevice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        $request->user()->deviceTokens()->where('token', $data['token'])->delete();

        return response()->json(['registered' => false]);
    }
}
