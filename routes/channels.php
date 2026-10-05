<?php

use App\Support\Notifications\NotificationActor;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| Channel authorisation for the real-time notification bell.
|
| Every user of the app gets one private channel — `user.{role}.{id}` — and
| nothing else may subscribe to it. The decision is made from the same
| session keys the rest of the app signs in with, so a browser can never
| authorise somebody else's channel even if it guesses their id.
|
| Note: the bell uses its own `POST /notifications/auth` endpoint because it
| has to resolve session-only patients and doctors, which the framework's
| generic `/broadcasting/auth` endpoint cannot. This file keeps that generic
| endpoint safe and correct too, should anything else ever call it.
*/

Broadcast::channel('user.{type}.{id}', function (mixed $user, string $type, int $id): bool {
    $actor = NotificationActor::current();

    return $actor !== null
        && $actor['type'] === $type
        && $actor['id'] === $id;
});
