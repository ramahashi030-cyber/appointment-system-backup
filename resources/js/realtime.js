/**
 * One shared realtime connection for the whole application.
 *
 * The notification bell (notifications.js) and the live consultation-room
 * state (telemed-rooms.js) both listen to the same private
 * `user.{role}.{id}` channel, so a dashboard opens one WebSocket instead of
 * one per feature.
 *
 * Settings come from `window.QMMC_NOTIFICATIONS` (any page that renders the
 * notification modal) or, failing that, `window.QMMC_TELEMED_ROOMS` (pages
 * that only need room updates). Without either, connectRealtime() returns
 * null and callers simply fall back to polling.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/** @type {{channel: string|null, broadcast: object|null, csrf: string}|null|undefined} */
let settings;
let echo = null;
let attempted = false;
let everConnected = false;

/** @type {Array<(payload: object) => void>} */
const notificationHandlers = [];
/** @type {Array<() => void>} */
const reconnectHandlers = [];

export function realtimeSettings() {
    if (settings === undefined) {
        settings = window.QMMC_NOTIFICATIONS || window.QMMC_TELEMED_ROOMS || null;
    }

    return settings;
}

export function connectRealtime() {
    if (echo) {
        return echo;
    }

    const config = realtimeSettings();

    if (!config || !config.channel || !config.broadcast) {
        return null;
    }

    if (attempted) {
        return null;
    }

    attempted = true;

    try {
        window.Pusher = window.Pusher || Pusher;

        echo = new Echo({
            broadcaster: 'reverb',
            key: config.broadcast.key,
            wsHost: config.broadcast.host,
            wsPort: config.broadcast.port,
            wssPort: config.broadcast.port,
            forceTLS: !!config.broadcast.forceTLS,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: config.broadcast.authEndpoint,
            csrfToken: config.csrf,
        });

        echo.private(config.channel).notification((payload) => {
            notificationHandlers.forEach((handler) => {
                try {
                    handler(payload || {});
                } catch (error) {
                    // One broken listener must not silence the others.
                }
            });
        });

        const connection = echo.connector.pusher && echo.connector.pusher.connection;

        if (connection && typeof connection.bind === 'function') {
            connection.bind('state_change', (states) => {
                if (!states || states.current !== 'connected') {
                    return;
                }

                if (everConnected) {
                    // A reconnect: whatever the socket dropped has to be
                    // re-synced from the database.
                    reconnectHandlers.forEach((handler) => {
                        try {
                            handler();
                        } catch (error) {
                            // Keep the remaining handlers running.
                        }
                    });
                }

                everConnected = true;
            });
        }
    } catch (error) {
        // Reverb unavailable / blocked: leave polling to the callers.
        echo = null;
        attempted = false;
    }

    return echo;
}

/**
 * Listen to every broadcast notification on this user's channel.
 *
 * @param {(payload: object) => void} handler
 */
export function onRealtimeNotification(handler) {
    if (typeof handler === 'function') {
        notificationHandlers.push(handler);
    }

    return connectRealtime();
}

/**
 * Run after the WebSocket reconnected, so listeners can catch up on whatever
 * was broadcast while the socket was down.
 *
 * @param {() => void} handler
 */
export function onRealtimeReconnect(handler) {
    if (typeof handler === 'function') {
        reconnectHandlers.push(handler);
    }

    return connectRealtime();
}
