import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css',
                'resources/js/app.js',
                'resources/css/app.css',
                'resources/css/auth/register.css',
                'resources/css/auth/login.css',
                'resources/css/auth/verify-otp.css',
                'resources/css/landing.css',
                'resources/css/user/dashboard.css',
                'resources/css/user/navbar.css',
                'resources/css/user/reviews.css',
                'resources/css/eo/dashboard.css',
                'resources/css/eo/create-event.css',
                'resources/css/tickets/tickets.css',
                'resources/css/eo/status.css',
                'resources/css/eo/scan.css',
                'resources/css/admin/admin.css',
                'resources/css/admin/dashboard.css',
                'resources/css/checkout/checkout.css',
                'resources/css/event/show.css',
                'resources/js/app.js',],
            refresh: true,
        }),
        tailwindcss(),
    ],

    // ── WAJIB untuk ngrok ──────────────────────────────
    server: {
        host: '0.0.0.0',          // dengarkan semua interface, bukan cuma localhost
        port: 5173,
        strictPort: true,
        cors: true,                // izinkan request dari origin manapun (termasuk ngrok)
        hmr: {
            host: 'localhost',     // tetap localhost untuk koneksi HMR websocket lokal
        },
    },
});
