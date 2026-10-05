<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Dashboard') — QMMC Admin Panel</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    @vite([
        'resources/css/app.css',
        'resources/css/admin/admin.css',
        'resources/css/admin-layout-append.css',
        'resources/css/notifications.css',
        'resources/js/app.js',
        'resources/js/notifications.js',
    ])

    @stack('head')
    @stack('styles')
</head>
<body class="admin-dashboard-body">
    <div class="admin-shell">
        @yield('sidebar')

        <div class="admin-page">
            @yield('header')

            <main class="admin-main">
                @include('partials.flash')
                @yield('content')
            </main>

            <footer class="admin-footer">
                <div class="admin-footer-brand">
                    <strong>QMMC Admin Portal</strong>
                    <span aria-hidden="true">*</span>
                    <span>Doctors and Patient Management</span>
                </div>
                <em>Dekalidad na Serbisyo, Alagang QMMC.
</em>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script id="admin-layout-navigation">
        $(function () {
            const $main = $('.admin-main');
            const $shell = $('.admin-shell');
            const $sidebarLinks = $('.admin-sidebar-link, .admin-sidebar-sublink');
            const pageCache = {};
            const renderedCache = {};
            const PREFETCH_TTL_MS = 5000;
            const RENDERED_TTL_MS = 60000;
            let navToken = 0;

            function pathOf(url) {
                return new URL(url, window.location.href).pathname;
            }

            function locationKey(url) {
                const parsed = new URL(url, window.location.href);

                return parsed.pathname + parsed.search;
            }

            let currentLocationKey = locationKey(window.location.href);

            function isAdminNavAnchor(anchor) {
                if (!anchor.href) {
                    return false;
                }

                if (anchor.hasAttribute('download') || anchor.dataset.bsToggle) {
                    return false;
                }

                if (anchor.target && anchor.target !== '_self') {
                    return false;
                }

                let parsed;

                try {
                    parsed = new URL(anchor.href, window.location.href);
                } catch (error) {
                    return false;
                }

                if (parsed.origin !== window.location.origin) {
                    return false;
                }

                return parsed.pathname.startsWith('/admin');
            }

            function setActiveLink(url) {
                const path = pathOf(url);
                $sidebarLinks.removeClass('active').removeAttr('aria-current');
                $sidebarLinks.filter(function () {
                    return this.href && pathOf(this.href) === path;
                }).addClass('active').attr('aria-current', 'page');

                syncSidebarGroups();
            }

            /*
             * A dropdown parent stays highlighted while one of its sub-pages is
             * the current page, and that group is revealed.
             *
             * Groups that do NOT own the current page are left untouched, so they
             * only open and close when the person clicks them.
             *
             * IMPORTANT: getOrCreateInstance(panel) with no options makes
             * Bootstrap's Collapse toggle (open) the panel as soon as the
             * instance is created, which opened every group on each navigation.
             * Passing { toggle: false } prevents that.
             *
             * aria-expanded is not set manually here; Bootstrap maintains it on
             * show/hide, and the chevron rotation in sidebar.css depends on it.
             */
            function syncSidebarGroups() {
                $('.admin-sidebar-group').each(function () {
                    const group = $(this);
                    const toggle = group.find('.admin-sidebar-group-toggle');
                    const panel = group.find('.collapse')[0];
                    const groupActive = group.find('.admin-sidebar-sublink.active').length > 0;

                    group.toggleClass('active', groupActive);

                    if (groupActive) {
                        toggle.attr('aria-current', 'page');
                    } else {
                        toggle.removeAttr('aria-current');
                    }

                    if (groupActive && panel) {
                        bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false }).show();
                    }
                });
            }

            function resetModalState() {
                document.querySelectorAll('.modal.show').forEach((modal) => {
                    bootstrap.Modal.getInstance(modal)?.hide();
                });
                document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }

            function syncPageStyles(doc) {
                document.querySelectorAll('[data-admin-page-style]').forEach((node) => node.remove());
                doc.querySelectorAll('head style').forEach((style) => {
                    const tag = document.createElement('style');
                    tag.setAttribute('data-admin-page-style', '');
                    tag.textContent = style.textContent;
                    document.head.appendChild(tag);
                });
            }

            function evalPageScript(source) {
                const trimmed = source.trim();

                if (document.readyState !== 'loading') {
                    const domReadyMatch = trimmed.match(
                        /^document\.addEventListener\s*\(\s*['"]DOMContentLoaded['"]\s*,\s*\(\)\s*=>\s*\{([\s\S]*)\}\s*\)\s*;?\s*$/
                    );

                    if (domReadyMatch) {
                        $.globalEval(`(function () {${domReadyMatch[1]}})();`);

                        return;
                    }
                }

                $.globalEval(source);
            }

            function runPageScripts(doc) {
                doc.querySelectorAll('script:not([src])').forEach((script) => {
                    if (script.id === 'admin-layout-navigation') {
                        return;
                    }

                    evalPageScript(script.textContent);
                });
            }

            function parsePage(html) {
                return new DOMParser().parseFromString(html, 'text/html');
            }

            function applyPage(html, url, push) {
                const doc = parsePage(html);
                const newMain = doc.querySelector('.admin-main');

                if (!newMain) {
                    window.location.href = url;

                    return;
                }

                syncPageStyles(doc);
                resetModalState();
                setActiveLink(url);
                currentLocationKey = locationKey(url);
                document.title = doc.querySelector('title')?.textContent || document.title;

                if (push !== false) {
                    window.history.pushState({ url: url }, '', url);
                }

                $main.removeClass('is-navigating').html(newMain.innerHTML);

                requestAnimationFrame(function () {
                    runPageScripts(doc);
                });
            }

            function fetchPage(url) {
                const hit = pageCache[url];

                if (hit && Date.now() - hit.time < PREFETCH_TTL_MS) {
                    return hit.request;
                }

                const request = $.ajax({
                    url: url,
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                pageCache[url] = { time: Date.now(), request: request };
                request.fail(function () {
                    delete pageCache[url];
                });

                return request;
            }

            function navigateTo(url, push) {
                const token = ++navToken;
                const key = locationKey(url);
                const cached = renderedCache[key];

                setActiveLink(url);

                if (cached && Date.now() - cached.time < RENDERED_TTL_MS) {
                    if (token !== navToken) {
                        return;
                    }

                    applyPage(cached.html, url, push);

                    return;
                }

                $main.addClass('is-navigating');

                const request = fetchPage(url);
                delete pageCache[url];

                request.done(function (html) {
                    if (token !== navToken) {
                        return;
                    }

                    renderedCache[key] = { time: Date.now(), html: html };
                    applyPage(html, url, push);
                }).fail(function () {
                    if (token === navToken) {
                        window.location.href = url;
                    }
                });
            }

            $sidebarLinks.on('mouseenter focus', function () {
                const url = this.href;

                // Dropdown toggles are buttons and have no href to prefetch.
                if (!url || locationKey(url) === currentLocationKey) {
                    return;
                }

                fetchPage(url);
            });

            $shell.on('mousedown touchstart', 'a[href]', function () {
                if (!isAdminNavAnchor(this)) {
                    return;
                }

                if (locationKey(this.href) !== currentLocationKey) {
                    fetchPage(this.href);
                }
            });

            $shell.on('click', 'a[href]', function (event) {
                if (!isAdminNavAnchor(this)) {
                    return;
                }

                if (event.ctrlKey || event.metaKey || event.shiftKey || event.which === 2) {
                    return;
                }

                event.preventDefault();

                if (locationKey(this.href) === currentLocationKey) {
                    return;
                }

                navigateTo(this.href, true);
            });

            window.addEventListener('popstate', function () {
                if (locationKey(window.location.href) !== currentLocationKey) {
                    navigateTo(window.location.href, false);
                }
            });
        });
    </script>
    <script id="admin-responsive-tables">
        /*
         * Phones: turns data tables into stacked cards. Adds `.admin-table-stack` and a
         * `data-label` (from the <thead>) to each cell; the CSS lives in admin-css/mobile.css.
         * Runs once. The page-swap code re-evaluates inline scripts, hence the guard.
         * A MutationObserver covers AJAX-replaced tables and rows rendered by page scripts.
         */
        (function () {
            if (window.__adminResponsiveTables) {
                return;
            }

            window.__adminResponsiveTables = true;

            function labelOf(th) {
                const copy = th.cloneNode(true);
                copy.querySelectorAll('.visually-hidden').forEach(function (node) { node.remove(); });

                return copy.textContent.replace(/\s+/g, ' ').trim();
            }

            function prepare(table) {
                if (table.classList.contains('compact')
                    || table.classList.contains('admin-patient-table')
                    || table.classList.contains('no-stack')
                    || table.closest('.modal')) {
                    return;
                }

                const heads = table.querySelectorAll('thead tr:last-child th');

                if (!heads.length) {
                    return;
                }

                const labels = Array.prototype.map.call(heads, labelOf);

                table.classList.add('admin-table-stack');

                table.querySelectorAll('tbody tr').forEach(function (row) {
                    Array.prototype.forEach.call(row.children, function (cell, index) {
                        if (cell.tagName !== 'TD' || cell.hasAttribute('colspan') || cell.hasAttribute('data-label')) {
                            return;
                        }

                        if (labels[index]) {
                            cell.setAttribute('data-label', labels[index]);
                        }
                    });
                });
            }

            function run() {
                document.querySelectorAll('.admin-main table.admin-doctor-table').forEach(prepare);
            }

            run();

            const main = document.querySelector('.admin-main');

            if (main && 'MutationObserver' in window) {
                new MutationObserver(run).observe(main, { childList: true, subtree: true });
            }
        })();
    </script>
    @stack('scripts')
</body>
</html>