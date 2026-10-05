import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // Bootstrap remains CDN-loaded; these entries contain the
            // application and authentication layout styles imported by Blade.
            input: [
                // Shared + auth
                'resources/css/app.css',
                'resources/css/auth.css',

                // Patient portal
                'resources/css/patient-dashboard.css',
                'resources/css/patient/pages.css',

                // Optional dark variant of the patient dashboard. It re-declares the
                // --dashboard-* custom properties on .patient-dashboard-body, so it
                // must load *after* patient-dashboard.css to take effect. Add it
                // below that entry to switch the dashboard to the dark palette.
                // 'resources/css/patient-dashboard-theme.css',

                // Doctor portal
                'resources/css/doctor-dashboard.css',
                'resources/css/doctor-patients-append.css',

                // Admin panel
                'resources/css/admin/admin.css',
                'resources/css/admin/triager-processed.css',
                'resources/css/admin-layout-append.css',

                // Real-time notification bell + modal (Patient, Doctor, Admin)
                'resources/css/notifications.css',

                // Scripts
                'resources/js/app.js',
                'resources/js/doctor-dashboard.js',
                'resources/js/patient-dashboard.js',
                'resources/js/notifications.js',
            ],
            refresh: true,
        }),
    ],
});
