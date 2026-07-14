<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('gate.station_title') }} — {{ __('KIA School System') }}</title>
    @vite(['resources/css/kia.css'])
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body { height: 100%; margin: 0; overflow: hidden; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: #0d1226;
            color: #fff;
            display: flex; flex-direction: column;
            min-height: 100vh;
        }
        .gate-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 24px;
            background: rgba(255,255,255,.04);
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .gate-brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.1rem; }
        .gate-brand .badge {
            width: 34px; height: 34px; border-radius: 8px; background: var(--gold, #ECC531);
            color: #1a1a1a; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 800;
        }
        .gate-branch { font-size: .85rem; color: rgba(255,255,255,.6); }
        .gate-stage {
            flex: 1; display: flex; align-items: center; justify-content: center;
            position: relative; padding: 24px;
        }
        #reader { width: min(92vw, 480px); border-radius: 20px; overflow: hidden; box-shadow: 0 0 0 4px rgba(255,255,255,.06); }
        .gate-idle-hint {
            position: absolute; bottom: 40px; left: 0; right: 0; text-align: center;
            font-size: 1.1rem; color: rgba(255,255,255,.75);
        }
        .gate-feedback {
            position: absolute; inset: 0; display: none; flex-direction: column; align-items: center; justify-content: center;
            gap: 18px; padding: 24px; text-align: center; z-index: 10;
        }
        .gate-feedback.show { display: flex; }
        .gate-feedback.ok     { background: rgba(31,157,107,.97); }
        .gate-feedback.late   { background: rgba(224,146,47,.97); }
        .gate-feedback.info   { background: rgba(43,58,143,.97); }
        .gate-feedback.reject { background: rgba(216,87,63,.97); }
        .gate-icon { width: 110px; height: 110px; border-radius: 50%; background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center; }
        .gate-icon svg { width: 60px; height: 60px; }
        .gate-photo { width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,.5); }
        .gate-name-en { font-size: 2rem; font-weight: 800; }
        .gate-name-km { font-size: 1.6rem; font-weight: 600; opacity: .92; }
        .gate-status-line { font-size: 1.3rem; font-weight: 700; }
        .gate-hint-line { font-size: 1rem; opacity: .85; max-width: 480px; }
        .gate-time { font-size: 1.1rem; opacity: .8; }
    </style>
</head>
<body>
    <div class="gate-header">
        <div class="gate-brand"><span class="badge">KIA</span> {{ __('gate.station_title') }}</div>
        <div class="gate-branch">{{ auth()->user()->branch?->name_en ?? __('KIA School System') }} · {{ auth()->user()->name }}</div>
    </div>

    <div class="gate-stage">
        <div id="reader"></div>
        <div class="gate-idle-hint" id="idleHint">{{ __('gate.point_camera_hint') }}</div>

        <div class="gate-feedback" id="feedback">
            <div class="gate-icon" id="feedbackIcon"></div>
            <img class="gate-photo" id="feedbackPhoto" style="display:none;" alt="">
            <div class="gate-name-en" id="feedbackNameEn"></div>
            <div class="gate-name-km" id="feedbackNameKm"></div>
            <div class="gate-status-line" id="feedbackStatus"></div>
            <div class="gate-hint-line" id="feedbackHint"></div>
            <div class="gate-time" id="feedbackTime"></div>
        </div>
    </div>

    <script>
    (function () {
        const scanUrl = @json(route('gate.scan'));
        const csrfToken = document.querySelector('meta[name=csrf-token]').content;
        const feedback = document.getElementById('feedback');
        const idleHint = document.getElementById('idleHint');
        let processing = false;
        let clearTimer = null;

        const ICONS = {
            check: '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>',
            cross: '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
            clock: '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            info: '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
        };

        function showFeedback({ tone, icon, photo, nameEn, nameKm, status, hint }) {
            feedback.className = 'gate-feedback show ' + tone;
            document.getElementById('feedbackIcon').innerHTML = ICONS[icon] || ICONS.info;

            const photoEl = document.getElementById('feedbackPhoto');
            if (photo) { photoEl.src = photo; photoEl.style.display = 'block'; } else { photoEl.style.display = 'none'; }

            document.getElementById('feedbackNameEn').textContent = nameEn || '';
            document.getElementById('feedbackNameKm').textContent = nameKm || '';
            document.getElementById('feedbackStatus').textContent = status || '';
            document.getElementById('feedbackHint').textContent = hint || '';
            document.getElementById('feedbackTime').textContent = new Date().toLocaleTimeString();

            idleHint.style.display = 'none';
            clearTimeout(clearTimer);
            clearTimer = setTimeout(clearFeedback, 2200);
        }

        function clearFeedback() {
            feedback.classList.remove('show');
            idleHint.style.display = 'block';
            processing = false;
        }

        function labels(data) {
            const type = data.type === 'staff' ? {{ Js::from(__('Staff')) }} : {{ Js::from(__('Student')) }};

            if (data.result === 'unmatched') {
                return { tone: 'reject', icon: 'cross', status: @json(__('gate.not_recognized')), hint: @json(__('gate.not_recognized_hint')) };
            }
            if (data.result === 'wrong_branch') {
                return { tone: 'reject', icon: 'cross', nameEn: data.name_en, nameKm: data.name_km, photo: data.photo_url, status: @json(__('gate.wrong_campus')), hint: @json(__('gate.wrong_campus_hint')) };
            }
            if (data.result === 'duplicate') {
                return { tone: 'info', icon: 'info', nameEn: data.name_en, nameKm: data.name_km, photo: data.photo_url, status: @json(__('gate.duplicate_scan')), hint: @json(__('gate.already_recorded')) };
            }
            if (data.event === 'departure') {
                return { tone: 'ok', icon: 'check', nameEn: data.name_en, nameKm: data.name_km, photo: data.photo_url, status: @json(__('gate.departed')) };
            }
            return { tone: 'ok', icon: 'check', nameEn: data.name_en, nameKm: data.name_km, photo: data.photo_url, status: @json(__('gate.arrived')) };
        }

        async function processCode(code) {
            if (processing) return;
            processing = true;

            try {
                const res = await fetch(scanUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ code }),
                });
                const data = await res.json();
                showFeedback(labels(data));
            } catch (e) {
                showFeedback({ tone: 'reject', icon: 'cross', status: @json(__('gate.camera_error')) });
            }
        }

        const scanner = new Html5Qrcode('reader');
        Html5Qrcode.getCameras().then(function (cameras) {
            if (!cameras || !cameras.length) throw new Error('no camera');
            const cameraId = cameras[0].id;
            scanner.start(
                cameraId,
                { fps: 10, qrbox: { width: 260, height: 260 } },
                function (decodedText) { processCode(decodedText); },
                function () { /* per-frame no-QR-found noise — ignore */ }
            );
        }).catch(function () {
            idleHint.textContent = @json(__('gate.camera_error'));
        });
    })();
    </script>
</body>
</html>
