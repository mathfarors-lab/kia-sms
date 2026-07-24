<x-app-layout>
    <x-slot name="title">{{ __('role_guide.title') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('role_guide.title') }}</h1>
            <p class="kia-page-sub">{{ __('role_guide.subtitle') }}</p>
        </div>
    </div>

    <div id="roleSwitcher" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;"></div>

    <div class="kia-card" id="roleSummaryCard" style="margin-bottom:20px;">
        <div class="kia-card-body" id="roleSummary" style="display:flex;align-items:baseline;justify-content:space-between;gap:16px;flex-wrap:wrap;"></div>
    </div>

    <div style="display:grid;grid-template-columns:260px 1fr;gap:20px;align-items:start;" id="rgPanes">
        <div id="sidebarMock" style="background:linear-gradient(180deg, var(--navy) 0%, #1c2466 100%);border-radius:var(--radius-lg);padding:16px 0;box-shadow:var(--shadow);position:sticky;top:20px;"></div>
        <div id="detailPane"></div>
    </div>

    <p style="margin-top:24px;font-size:.8rem;color:var(--muted);">{{ __('role_guide.footer_note') }}</p>

    <style>
        .rg-role-btn {
            font-weight: 600; font-size: .8rem; padding: 8px 16px; border-radius: 999px;
            border: 1px solid var(--line); background: var(--surface); color: var(--ink);
            cursor: pointer; transition: background .12s, color .12s, border-color .12s;
        }
        .rg-role-btn:hover { border-color: var(--royal); }
        .rg-role-btn.active { background: var(--navy); color: #fff; border-color: var(--navy); }
        .rg-role-btn .n { opacity: .65; font-weight: 400; margin-left: 5px; }

        .sm-brand { display: flex; align-items: center; gap: 10px; padding: 0 16px 14px; margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,.12); }
        .sm-logo { width: 28px; height: 28px; border-radius: 7px; background: var(--gold); color: var(--navy); font-weight: 800; font-size: 11px; display: flex; align-items: center; justify-content: center; }
        .sm-name { color: #fff; font-weight: 700; font-size: 12.5px; }
        .sm-section { color: rgba(255,255,255,.4); font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; padding: 12px 16px 5px; }
        .sm-section:first-of-type { padding-top: 4px; }
        .sm-item { display: flex; align-items: center; gap: 9px; padding: 6px 16px; color: rgba(255,255,255,.85); font-size: 12.5px; }
        .sm-dot { width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,.4); flex-shrink: 0; }

        .rg-ds-title { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--royal); margin: 0 0 8px; }
        .rg-ds-section { margin-bottom: 18px; }
        .rg-ds-section:last-child { margin-bottom: 0; }
        .rg-ds-item {
            background: var(--surface); border: 1px solid var(--line); border-top: none;
            padding: 9px 14px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
        }
        .rg-ds-item:first-child { border-top: 1px solid var(--line); border-radius: var(--radius-sm) var(--radius-sm) 0 0; }
        .rg-ds-item:last-child { border-radius: 0 0 var(--radius-sm) var(--radius-sm); }
        .rg-ds-item:only-child { border-radius: var(--radius-sm); }
        .rg-ds-name { font-weight: 600; font-size: .875rem; }
        .rg-ds-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
        .rg-perm-chip { font-family: ui-monospace, Consolas, monospace; font-size: .7rem; }
    </style>

    <script>
        const RG_ROLES = @json($roles);
        const RG_ROLE_META = @json($roleMeta);
        const RG_SECTIONS = @json($sections);
        const RG_LABELS = {
            featuresVisible: @json(__('role_guide.features_visible')),
            alwaysVisible: @json(__('role_guide.always_visible')),
            scoped: @json(__('role_guide.scoped_label')),
            full: @json(__('role_guide.full_label')),
        };

        function rgItemVisible(item, roleId) {
            if (roleId === 'owner' || roleId === 'admin') {
                if (RG_SECTIONS.find(s => s.id === 'owner-only').items.includes(item)) return roleId === 'owner';
                if (RG_SECTIONS.find(s => s.id === 'student-portal').items.includes(item)) return false;
                if (RG_SECTIONS.find(s => s.id === 'parent-portal').items.includes(item)) return false;
                return true;
            }
            return item.roles.includes(roleId);
        }

        function rgRenderAll(roleId) {
            const meta = RG_ROLE_META[roleId];

            const counts = {};
            RG_ROLES.forEach(r => {
                counts[r] = RG_SECTIONS.flatMap(s => s.items).filter(i => rgItemVisible(i, r)).length;
            });

            document.getElementById('roleSwitcher').innerHTML = RG_ROLES.map(r =>
                `<button class="rg-role-btn ${r === roleId ? 'active' : ''}" data-role="${r}">${RG_ROLE_META[r].label}<span class="n">${counts[r]}</span></button>`
            ).join('');
            document.querySelectorAll('.rg-role-btn').forEach(btn => {
                btn.addEventListener('click', () => rgRenderAll(btn.dataset.role));
            });

            document.getElementById('roleSummary').innerHTML = `
                <div><h2 style="font-size:1.05rem;font-weight:700;margin:0 0 3px;">${meta.label}</h2><p style="font-size:.8125rem;color:var(--muted);margin:0;max-width:56ch;">${meta.blurb}</p></div>
                <div style="text-align:right;white-space:nowrap;"><b style="font-size:1.3rem;font-variant-numeric:tabular-nums;">${counts[roleId]}</b><span style="display:block;font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">${RG_LABELS.featuresVisible}</span></div>
            `;

            let sm = `<div class="sm-brand"><div class="sm-logo">KIA</div><div class="sm-name">KIA School System</div></div>`;
            RG_SECTIONS.forEach(sec => {
                const visible = sec.items.filter(i => rgItemVisible(i, roleId));
                if (!visible.length) return;
                if (sec.title) sm += `<div class="sm-section">${sec.title}</div>`;
                visible.forEach(i => { sm += `<div class="sm-item"><span class="sm-dot"></span>${i.label}</div>`; });
            });
            document.getElementById('sidebarMock').innerHTML = sm;

            let dp = '';
            RG_SECTIONS.forEach(sec => {
                const visible = sec.items.filter(i => rgItemVisible(i, roleId));
                if (!visible.length) return;
                dp += `<div class="rg-ds-section"><div class="rg-ds-title">${sec.title || RG_LABELS.alwaysVisible}</div>`;
                visible.forEach(i => {
                    const scopeNote = i.scope && i.scope[roleId];
                    const scopeHtml = scopeNote
                        ? `<span class="pill ${scopeNote.startsWith('scoped') ? 'pill-ok' : 'pill-warn'}">${scopeNote.startsWith('scoped') ? RG_LABELS.scoped : RG_LABELS.full}</span>`
                        : '';
                    dp += `<div class="rg-ds-item">
                        <div class="rg-ds-name">${i.label}</div>
                        <div class="rg-ds-meta">${scopeHtml}<span class="pill pill-muted rg-perm-chip">${i.perm}</span></div>
                    </div>`;
                });
                dp += `</div>`;
            });
            document.getElementById('detailPane').innerHTML = dp;
        }

        rgRenderAll('teacher');
    </script>

    <style>
        @media (max-width: 860px) {
            #rgPanes { grid-template-columns: 1fr !important; }
        }
    </style>
</x-app-layout>
