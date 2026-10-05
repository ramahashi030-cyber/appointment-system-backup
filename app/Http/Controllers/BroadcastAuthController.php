<?php

namespace App\Http\Controllers;

use App\Support\Notifications\NotificationActor;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * POST /broadcasting/auth
 *
 * Signs the browser's subscription to its private notification channel.
 *
 * The framework's own broadcast controller cannot be used here because this app
 * has no single authenticated user: patients and doctors only have session
 * keys, and triagers authenticate on the separate `admin` guard. This endpoint
 * does the equivalent check against those same session keys, so a signed-in
 * patient can only ever authorise `private-user.patient.{their id}`.
 */
class BroadcastAuthController extends Controller
{
    private const CHANNEL_PATTERN = '/^private-user\.(patient|staff|admin)\.(\d+)$/';

    public function __invoke(Request $request): JsonResponse
    {
        $channel = (string) $request->input('channel_name', '');
        $socketId = (string) $request->input('socket_id', '');

        if ($socketId === '' || preg_match(self::CHANNEL_PATTERN, $channel, $matches) !== 1) {
            throw new AccessDeniedHttpException('Unsupported channel.');
        }

        $actor = NotificationActor::current();

        if ($actor === null
            || $actor['type'] !== $matches[1]
            || (string) $actor['id'] !== $matches[2]) {
            throw new AccessDeniedHttpException('This channel does not belong to you.');
        }

        $broadcaster = Broadcast::connection();

        if (! $broadcaster instanceof PusherBroadcaster) {
            throw new AccessDeniedHttpException('Broadcasting is not configured.');
        }

        $pusher = $broadcaster->getPusher();

        $auth = method_exists($pusher, 'authorizeChannel')
            ? $pusher->authorizeChannel($channel, $socketId)
            : $pusher->socket_auth($channel, $socketId);

        return response()->json(json_decode($auth, true) ?? []);
    }
}
