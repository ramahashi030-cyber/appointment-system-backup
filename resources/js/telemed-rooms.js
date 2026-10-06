/**
 * Live consultation-room state for the Patient and Doctor dashboards.
 *
 * The doctor creates the room, and the patient's "Room not ready" cell turns
 * into "Join the Room" without a refresh. Both dashboards keep following the
 * room afterwards: the patient's join flips the appointment to Completed, and
 * the scheduled end time closes it for good.
 *
 * Three sources keep the rows honest, fastest first:
 *   1. the broadcast notification on the shared realtime channel,
 *   2. a re-sync after a WebSocket reconnect, plus tab focus/visibility,
 *   3. a slow poll as a safety net when no socket is available at all.
 *
 * Everything is guarded: pages without rooms, or without a session, exit
 * silently and leave the rest of the dashboard untouched.
 */

import { onRealtimeNotification, onRealtimeReconnect } from './realtime';

const POLL_INTERVAL = 20000;
const REFRESH_GAP = 4000;

/** @type {number} */
let lastRefreshAt = 0;
let refreshing = false;
let started = false;

const qsa = (selector, root = document) => Array.prototype.slice.call(root.querySelectorAll(selector));

/**
 * Every row that carries a room-status endpoint (all telemedicine rows do).
 *
 * @param {ParentNode} root
 * @return {HTMLElement[]}
 */
export function roomRows(root = document) {
    return qsa('[data-room-status-url]', root).filter((row) => {
        const url = row.getAttribute('data-room-status-url');

        return typeof url === 'string' && url.trim() !== '';
    });
}

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');

    return meta ? meta.getAttribute('content') : '';
}

function toElement(markup) {
    const template = document.createElement('template');
    template.innerHTML = String(markup || '').trim();

    return template.content.firstElementChild;
}

function escapeHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function isDoctorRow(row) {
    return row.hasAttribute('data-doctor-appointment-row');
}

function isCard(row) {
    return row.classList.contains('visit-card');
}

function roomControls(row) {
    return row.querySelector(
        '[data-doctor-create-room-form], [data-doctor-room-action], ' +
        '[data-patient-create-room-form], [data-patient-room-action]'
    );
}

/* ------------------------------------------------------------------ markup -- */

function patientControlMarkup(room, card) {
    const joinUrl = room.join_path || room.join_url || '';
    const openUrl = room.open_room_path || '';

    if (room.can_join && joinUrl) {
        return card
            ? `<a href="${escapeHtml(joinUrl)}" target="_blank" rel="noopener"
                   class="btn btn-success btn-pill" data-patient-room-action="join">
                   <i class="bi bi-camera-video me-1" aria-hidden="true"></i>Join the Room
               </a>`
            : `<a class="dashboard-join-button" href="${escapeHtml(joinUrl)}" target="_blank" rel="noopener" data-patient-room-action="join">
                   <i class="bi bi-camera-video-fill" aria-hidden="true"></i>
                   <span>Join the Room</span>
               </a>`;
    }

    if (room.can_create_room && openUrl) {
        const button = card
            ? `<button type="submit" class="btn btn-primary btn-pill" data-patient-room-action="create">
                   <i class="bi bi-camera-video me-1" aria-hidden="true"></i>Create a Room
               </button>`
            : `<button type="submit" class="dashboard-join-button" data-patient-room-action="create">
                   <i class="bi bi-camera-video-fill" aria-hidden="true"></i>
                   <span>Create a Room</span>
               </button>`;

        return `<form method="POST" action="${escapeHtml(openUrl)}" class="d-inline" data-patient-create-room-form>
            <input type="hidden" name="_token" value="${escapeHtml(csrfToken())}">
            ${button}
        </form>`;
    }

    const label = room.is_expired ? 'Consultation ended' : 'Room not ready';

    return card
        ? `<span class="btn btn-outline-secondary btn-pill disabled" data-patient-room-action="pending">
               <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>${escapeHtml(label)}
           </span>`
        : `<span class="dashboard-link-pending" data-patient-room-action="pending">${escapeHtml(label)}</span>`;
}

function doctorControlMarkup(room) {
    const joinUrl = room.join_path || room.meeting_link || '';
    const openUrl = room.open_room_path || '';

    if (room.can_join && joinUrl) {
        return `<a href="${escapeHtml(joinUrl)}" target="_blank" rel="noopener" class="doctor-action-button primary" data-doctor-room-action="join">
            <i class="bi bi-camera-video-fill" aria-hidden="true"></i>Join the Room
        </a>`;
    }

    if (room.can_create_room && openUrl) {
        return `<form method="POST" action="${escapeHtml(openUrl)}" class="d-inline" data-doctor-create-room-form>
            <input type="hidden" name="_token" value="${escapeHtml(csrfToken())}">
            <button type="submit" class="doctor-action-button primary" data-doctor-room-action="create">
                <i class="bi bi-camera-video-fill" aria-hidden="true"></i>Create a Room
            </button>
        </form>`;
    }

    return `<span class="doctor-action-button disabled" aria-disabled="true" data-doctor-room-action="unavailable">Unavailable</span>`;
}

/* ------------------------------------------------------------------ apply --- */

function applyStatus(row, payload, room) {
    const doctor = isDoctorRow(row);
    const status = String(payload.status || room.status || '');
    const display = String(payload.display_status || payload.status_label || status || 'Booked');
    const cancelled = display.toLowerCase().indexOf('cancel') !== -1;

    if (doctor) {
        const chip = row.querySelector('[data-doctor-status]');

        if (chip) {
            const statusClass = String(payload.status_class || status.replace(/[^A-Za-z]+/g, '').toLowerCase() || 'booked');
            chip.className = `doctor-status ${statusClass}`;
            chip.textContent = String(payload.status_label || status);
        }

        const view = row.querySelector('[data-doctor-appointment-view]');

        if (view && display) {
            view.setAttribute('data-status', String(payload.status_label || status));
        }

        return;
    }

    if (isCard(row)) {
        const badge = row.querySelector('[data-patient-status-badge]');

        if (badge) {
            if (room.is_expired || payload.is_expired) {
                badge.className = 'badge bg-warning text-dark';
                badge.setAttribute('data-patient-status-badge', '');
                badge.textContent = 'Expired';
            } else {
                const active = ['booked', 'pending', 'confirmed', 'approved'].indexOf(display.toLowerCase()) !== -1;
                badge.className = `badge rounded-pill ${active ? 'bg-primary' : 'bg-secondary'}`;
                badge.setAttribute('data-patient-status-badge', '');
                badge.textContent = display;
            }
        }

        return;
    }

    const statusCell = row.querySelector('td[data-label="Status"] .dashboard-status');

    if (statusCell) {
        statusCell.className = `dashboard-status ${cancelled ? 'cancelled' : 'booked'}`;
        statusCell.innerHTML = `${escapeHtml(display)} <i class="bi bi-circle-fill" aria-hidden="true"></i>`;
    }
}

function applyNotice(row, room) {
    const notice = row.querySelector('[data-patient-room-notice], [data-doctor-room-notice]');

    if (!notice) {
        return;
    }

    const text = String(room.peer_notice || '');
    notice.textContent = text;
    notice.hidden = text === '';
}

/**
 * Render one row from a room-status payload
 * (`{room, status, display_status?, status_label?, status_class?, is_expired}`).
 *
 * @param {HTMLElement} row
 * @param {object} payload
 */
export function applyRoomState(row, payload) {
    if (!row || !payload) {
        return;
    }

    const room = payload.room || {};
    const controls = roomControls(row);

    if (controls) {
        const markup = isDoctorRow(row)
            ? doctorControlMarkup(room)
            : patientControlMarkup(room, isCard(row));
        const next = toElement(markup);

        if (next) {
            controls.replaceWith(next);
        }
    }

    applyStatus(row, payload, room);
    applyNotice(row, room);
}

/**
 * Fetch the current room state for one row and render it.
 *
 * @param {HTMLElement} row
 * @return {Promise<boolean>}
 */
export async function refreshRoomRow(row) {
    const url = row && typeof row.getAttribute === 'function'
        ? row.getAttribute('data-room-status-url')
        : '';

    if (!url) {
        return false;
    }

    try {
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return false;
        }

        applyRoomState(row, await response.json());

        return true;
    } catch (error) {
        // A dropped connection must never surface as a page error: the next
        // poll, reconnect or tab focus retries.
        return false;
    }
}

/**
 * Refresh every room row, throttled so focus + notifications + polling cannot
 * stack requests on top of each other.
 *
 * @param {boolean} [force]
 */
export function refreshAll(force = false) {
    const now = Date.now();

    if (refreshing || (!force && now - lastRefreshAt < REFRESH_GAP)) {
        return;
    }

    const rows = roomRows();

    if (!rows.length) {
        return;
    }

    refreshing = true;
    lastRefreshAt = now;

    Promise.all(rows.map((row) => refreshRoomRow(row))).finally(() => {
        refreshing = false;
    });
}

/* ------------------------------------------------------------------ events - */

function handleNotification(payload) {
    const type = String((payload && payload.type) || '');
    const appointmentId = payload && payload.appointment_id;

    if (type && !/appointment|telemed/i.test(type)) {
        return;
    }

    if (appointmentId) {
        const match = roomRows().filter((row) => (
            String(row.getAttribute('data-appointment-id')) === String(appointmentId)
        ));

        if (match.length) {
            match.forEach((row) => refreshRoomRow(row));

            return;
        }
    }

    refreshAll(true);
}

function bind() {
    // 1. Live push: the doctor created the room, or the patient joined it.
    onRealtimeNotification(handleNotification);

    // 2. Catch up on whatever was broadcast while the socket was down.
    onRealtimeReconnect(() => refreshAll(true));

    // 3. Coming back to the tab (the join happens in another tab).
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshAll(true);
        }
    });

    window.addEventListener('focus', () => refreshAll(true));

    // 4. Safety net while offline or when broadcasting is unavailable.
    window.setInterval(() => refreshAll(), POLL_INTERVAL);
}

function boot() {
    if (started || !roomRows().length) {
        return;
    }

    started = true;
    bind();
    refreshAll(true);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => boot());
} else {
    boot();
}
