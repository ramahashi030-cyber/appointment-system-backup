/**
 * Real-time notification bell + detail modal.
 *
 * Works for all three dashboards (patient, doctor, admin/triager) because it
 * reads its wiring from `window.QMMC_NOTIFICATIONS`, rendered by
 * resources/views/partials/notification-modal.blade.php.
 *
 * Design notes:
 *  - The database is the source of truth. Every live event just triggers a
 *    refresh of GET /notifications, so the modal and the badge can never drift
 *    from what is stored.
 *  - Everything is guarded: if Reverb is down, if the endpoint fails, or if
 *    nothing on this page needs notifications, this file exits silently and
 *    leaves the rest of the dashboard untouched.
 *  - The WebSocket itself lives in realtime.js, shared with the other live
 *    features on the page, so this file only registers listeners.
 */

import { onRealtimeNotification, onRealtimeReconnect } from './realtime';

const POLL_INTERVAL = 15000;

/** @type {{channel: string|null, modalId: string, broadcast: object|null, endpoints: object, csrf: string}|null} */
let cfg = null;
let started = false;
let fetching = false;
let unread = 0;

const config = () => {
    if (!cfg) {
        cfg = window.QMMC_NOTIFICATIONS || null;
    }

    return cfg;
};

const qs = (selector, root = document) => root.querySelector(selector);
const qsa = (selector, root = document) => Array.prototype.slice.call(root.querySelectorAll(selector));

/* ------------------------------------------------------------------ fetch -- */

async function request(url, options = {}) {
    const headers = Object.assign({ Accept: 'application/json' }, options.headers || {});

    if ((options.method || 'GET').toUpperCase() !== 'GET') {
        headers['X-CSRF-TOKEN'] = config()?.csrf || '';
        headers['Content-Type'] = 'application/json';
        headers['X-Requested-With'] = 'XMLHttpRequest';
    }

    return fetch(url, Object.assign({}, options, { headers, credentials: 'same-origin' }));
}

async function refresh() {
    const settings = config();

    if (!settings || fetching) {
        return;
    }

    fetching = true;

    try {
        const response = await request(settings.endpoints.index);

        if (!response.ok) {
            return;
        }

        apply(await response.json());
    } catch (error) {
        // A dropped connection must never surface as a page error.
    } finally {
        fetching = false;
    }
}

function apply(payload) {
    unread = Number(payload && payload.unread) || 0;

    updateBadges(unread);
    render(payload && Array.isArray(payload.notifications) ? payload.notifications : []);
    updateUnreadLabel();
}

/* ------------------------------------------------------------------ badge -- */

function setBadge(badge, count) {
    if (!badge) {
        return;
    }

    if (count > 0) {
        badge.textContent = count > 9 ? '9+' : String(count);
        badge.hidden = false;
    } else {
        badge.hidden = true;
    }
}

function patientBell() {
    return qs('[data-rt-bell][data-open-notifications], [data-open-notifications]');
}

/**
 * The patient header only renders its badge when the server-side count was
 * non-zero, so create it on demand instead of changing the Blade markup.
 */
function ensurePatientBadge() {
    const bell = patientBell();

    if (!bell) {
        return null;
    }

    let badge = qs('.patient-header-badge', bell);

    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'patient-header-badge';
        badge.setAttribute('aria-live', 'polite');
        badge.hidden = true;
        bell.appendChild(badge);
    }

    return badge;
}

function updateBadges(count) {
    qsa('[data-rt-badge]').forEach((badge) => setBadge(badge, count));

    const bell = patientBell();

    if (bell) {
        setBadge(ensurePatientBadge(), count);

        qsa('.patient-sidebar-badge').forEach((badge) => setBadge(badge, count));
    }

    qsa('[data-rt-bell], [data-open-notifications]').forEach((button) => {
        button.setAttribute('aria-label', count > 0 ? `Notifications, ${count} unread` : 'Notifications');
    });
}

function updateUnreadLabel() {
    qsa('[data-rt-unread-label]').forEach((label) => {
        label.textContent = `${unread} unread update${unread === 1 ? '' : 's'}`;
    });
}

/* ------------------------------------------------------------------ list --- */

function escapeHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function chip(label, value, extraClass = '') {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    return `<p class="rtm-item-service">${escapeHtml(label)}: <span class="rtm-chip ${extraClass}">${escapeHtml(value)}</span></p>`;
}

function itemHtml(item) {
    const read = !!item.read;
    const service = item.service ? chip('Service', item.service) : '';
    const schedule = item.date
        ? chip('Schedule', `${item.date}${item.time ? ` • ${item.time}` : ''}`)
        : (item.time ? chip('Time', item.time) : '');
    const reason = chip('Reason', item.reason, 'rtm-chip--reason');
    const action = item.action_label && item.action_url
        ? `<div class="rtm-item-actions"><a class="rtm-action" href="${escapeHtml(item.action_url)}"${
            item.action_external ? ' target="_blank" rel="noopener"' : ''
        } data-rt-action>${escapeHtml(item.action_label)}</a></div>`
        : '';

    return `<article class="rtm-item ${read ? 'is-read' : 'is-unread'}" data-rt-item="${escapeHtml(item.id)}" tabindex="0" role="button">
        <span class="rtm-item-icon" aria-hidden="true"><i class="bi ${escapeHtml(item.icon || 'bi-bell-fill')}"></i></span>
        <div class="rtm-item-main">
            <div class="rtm-item-title-row">
                <strong class="rtm-item-title">${escapeHtml(item.title || 'Notification')}</strong>
                ${read ? '' : '<span class="rtm-new">New</span>'}
            </div>
            <p class="rtm-item-message">${escapeHtml(item.message || '')}</p>
            ${service}${schedule}${reason}
            <div class="rtm-item-meta"><span><i class="bi bi-clock" aria-hidden="true"></i>${escapeHtml(item.created_label || '')}</span></div>
            ${action}
        </div>
    </article>`;
}

function render(items) {
    const list = qs('[data-rt-list]');
    const empty = qs('[data-rt-empty]');
    const loading = qs('[data-rt-loading]');

    if (loading) {
        loading.hidden = true;
    }

    if (list) {
        list.innerHTML = items.map(itemHtml).join('');
    }

    if (empty) {
        empty.hidden = items.length > 0;
    }

    if (list) {
        list.hidden = items.length === 0;
    }
}

/* ------------------------------------------------------------- interaction -- */

async function post(url) {
    try {
        const response = await request(url, { method: 'POST' });

        if (response.ok) {
            apply(await response.json());
        }
    } catch (error) {
        // Ignore: the next refresh or page load re-syncs from the database.
    }
}

function bind() {
    // Refresh as soon as the bell is clicked (Bootstrap or the patient's own
    // handler is what actually opens the modal).
    document.addEventListener('click', (event) => {
        const bell = event.target.closest('[data-rt-bell], [data-open-notifications]');

        if (bell) {
            refresh();
        }
    });

    // Refresh whenever any of our modals is about to be shown. Bootstrap's
    // events bubble, so this is bound once on the document and stays correct
    // even though the admin dashboard swaps page content in place.
    document.addEventListener('show.bs.modal', (event) => {
        if (event.target.hasAttribute && event.target.hasAttribute('data-rt-modal')) {
            refresh();
        }
    });

    document.addEventListener('click', (event) => {
        const markAll = event.target.closest('[data-rt-mark-all]');
        const action = event.target.closest('[data-rt-action]');
        const item = event.target.closest('[data-rt-item]');

        if (markAll) {
            const settings = config();
            markAll.disabled = true;
            post(settings.endpoints.readAll).finally(() => {
                markAll.disabled = false;
            });
            return;
        }

        if (action) {
            // Opening a notification counts as viewing it.
            const owner = action.closest('[data-rt-item]');
            if (owner) {
                post(readUrl(owner.getAttribute('data-rt-item')));
            }
            return;
        }

        if (item) {
            if (item.classList.contains('is-unread')) {
                post(readUrl(item.getAttribute('data-rt-item')));
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        const item = event.target.closest && event.target.closest('[data-rt-item]');

        if (item && item.classList.contains('is-unread')) {
            event.preventDefault();
            post(readUrl(item.getAttribute('data-rt-item')));
        }
    });
}

function readUrl(id) {
    const base = config().endpoints.index.replace(/\/$/, '');

    return `${base}/${encodeURIComponent(id)}/read`;
}

/* ----------------------------------------------------------------- echo ---- */

function connectEcho() {
    const settings = config();

    if (!settings || !settings.channel || !settings.broadcast) {
        return;
    }

    onRealtimeNotification(() => {
        refresh();
    });

    onRealtimeReconnect(() => {
        refresh();
    });
}

/* ------------------------------------------------------------------ boot --- */

function boot() {
    const settings = config();

    if (!settings) {
        return;
    }

    bind();
    refresh();
    connectEcho();

    // Polling is only a safety net: the WebSocket push is what makes the bell
    // live, this keeps it correct if that socket ever drops.
    window.setInterval(refresh, POLL_INTERVAL);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (!started) {
            started = true;
            boot();
        }
    });
} else if (!started) {
    started = true;
    boot();
}
