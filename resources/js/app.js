import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Service workers require a secure context (https:// or localhost) — this
// silently no-ops over a plain http:// LAN address, which is expected, not
// a bug (see the mobile-polish report for real-device deployment notes).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Installability is a progressive enhancement — a failed
            // registration shouldn't be user-visible or block the app.
        });
    });
}
