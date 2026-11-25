<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{Lang::T('Site is down for maintenance')} - {$companyName|escape}</title>
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/logo.png" type="image/x-icon">
    {*
        Shown (HTTP 503) to everyone while Maintenance Mode is on, by displayMaintenanceMessage() in init.php. Uses the colour
        tokens and theme switch of the public pages (styles.public.css, the "theme" setting in localStorage), so it follows the
        login page: light, dark, or the device's. $date (optional): when the site is back, as the countdown target.
    *}
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/styles.public.css?2026.11.06.6">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/fontawesome.css">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (t === 'light' || t === 'dark') document.documentElement.classList.add('theme-' + t);
            } catch (e) { }
        })();
    </script>
    <style>
        .mt-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .mt-card {
            position: relative;
            width: 100%;
            max-width: 520px;
            padding: 40px 32px 32px;
            text-align: center;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
        }

        .mt-toggle {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--card-bg);
            border: 1px solid var(--input-border);
            border-radius: 50%;
            color: var(--text-secondary);
            cursor: pointer;
            transition: border-color .2s, color .2s;
        }

        .mt-toggle:hover,
        .mt-toggle:focus-visible {
            border-color: #2563eb;
            color: #2563eb;
            outline: none;
        }

        .mt-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.9rem;
            color: #fff;
            background: #2563eb;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, .3);
        }

        .mt-icon i {
            animation: mt-spin 6s linear infinite;
        }

        @keyframes mt-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .mt-brand {
            margin: 0 0 6px;
            font-size: .85rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--text-tertiary);
        }

        .mt-title {
            margin: 0 0 12px;
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.25;
            color: var(--text-primary);
        }

        .mt-text {
            margin: 0 0 8px;
            font-size: .95rem;
            line-height: 1.55;
            color: var(--text-secondary);
        }

        .mt-sign {
            margin: 16px 0 0;
            font-size: .85rem;
            color: var(--text-tertiary);
        }

        .mt-count {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin: 24px 0 4px;
        }

        .mt-count[hidden] {
            display: none;
        }

        .mt-unit {
            padding: 12px 4px;
            background: var(--stat-item-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
        }

        .mt-unit b {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            color: var(--text-primary);
        }

        .mt-unit span {
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--text-tertiary);
        }

        .mt-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 16px;
            padding: 5px 12px;
            font-size: .78rem;
            font-weight: 600;
            color: #b45309;
            background: rgba(245, 158, 11, .12);
            border: 1px solid rgba(245, 158, 11, .35);
            border-radius: 999px;
        }

        .mt-dot {
            width: 8px;
            height: 8px;
            background: #f59e0b;
            border-radius: 50%;
            animation: mt-pulse 1.6s ease-in-out infinite;
        }

        @keyframes mt-pulse {
            50% {
                opacity: .35;
                transform: scale(.8);
            }
        }

        .mt-back {
            margin: 16px 0 0;
            font-size: .9rem;
            color: var(--text-secondary);
        }

        .mt-back b {
            color: var(--text-primary);
        }

        .mt-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--divider-bg);
        }

        .mt-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            font: inherit;
            font-size: .9rem;
            font-weight: 600;
            color: #fff;
            background: #2563eb;
            border: 0;
            border-radius: 10px;
            cursor: pointer;
            transition: opacity .2s;
        }

        .mt-btn:hover,
        .mt-btn:focus-visible {
            opacity: .9;
            outline: none;
        }

        .mt-btn[disabled] {
            opacity: .6;
            cursor: default;
        }

        .mt-btn.is-busy i {
            animation: mt-spin 1s linear infinite;
        }

        .mt-note {
            margin: 0;
            font-size: .78rem;
            color: var(--text-tertiary);
        }

        .mt-contact {
            margin: 14px 0 0;
            font-size: .85rem;
            color: var(--text-secondary);
        }

        .mt-contact a {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
        }

        html.theme-dark .mt-status {
            color: #fbbf24;
        }

        @media (prefers-color-scheme: dark) {
            html:not(.theme-light) .mt-status {
                color: #fbbf24;
            }
        }

        @media (max-width: 480px) {
            .mt-card {
                padding: 36px 20px 24px;
            }

            .mt-title {
                font-size: 1.3rem;
            }

            .mt-unit b {
                font-size: 1.2rem;
            }

            .mt-unit span {
                font-size: .62rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .mt-icon i,
            .mt-dot,
            .mt-btn.is-busy i {
                animation: none;
            }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <main class="mt-wrap">
        <section class="mt-card">
            <button type="button" class="mt-toggle" id="mtToggle" aria-label="{Lang::T('Toggle theme')}">
                <i class="fas fa-adjust" aria-hidden="true"></i>
            </button>
            <div class="mt-icon" aria-hidden="true"><i class="fas fa-cog"></i></div>
            <p class="mt-status"><span class="mt-dot" aria-hidden="true"></span>{Lang::T('Maintenance in progress')}</p>
            <p class="mt-brand">{$companyName|escape}</p>
            <h1 class="mt-title">{Lang::T('Site is temporarily unavailable.')}</h1>
            <p class="mt-text">{Lang::T('Scheduled maintenance is currently in progress. Please check back soon.')}</p>
            {if $date}
            <div class="mt-count" id="mtCount" hidden>
                <div class="mt-unit"><b id="mtD">0</b><span>{Lang::T('Days')}</span></div>
                <div class="mt-unit"><b id="mtH">0</b><span>{Lang::T('Hours')}</span></div>
                <div class="mt-unit"><b id="mtM">0</b><span>{Lang::T('Minutes')}</span></div>
                <div class="mt-unit"><b id="mtS">0</b><span>{Lang::T('Seconds')}</span></div>
            </div>
            {/if}
            {if $date}<p class="mt-back" id="mtBack" hidden>{Lang::T('Expected back')}: <b id="mtBackAt"></b></p>{/if}
            <div class="mt-actions">
                <button type="button" class="mt-btn" id="mtCheck"><i class="fas fa-sync-alt" aria-hidden="true"></i> <span>{Lang::T('Check again')}</span></button>
                <p class="mt-note" id="mtNote" role="status" aria-live="polite">{Lang::T('This page checks by itself and opens the site as soon as it is back.')}</p>
            </div>
            {if $_c['phone'] neq '' or $_c['address'] neq ''}
            <p class="mt-contact">
                {if $_c['phone'] neq ''}<i class="fas fa-phone-alt" aria-hidden="true"></i> <a href="tel:{$_c['phone']|escape}">{$_c['phone']|escape}</a>{/if}
                {if $_c['phone'] neq '' and $_c['address'] neq ''} &middot; {/if}
                {if $_c['address'] neq ''}{$_c['address']|escape}{/if}
            </p>
            {/if}
            <p class="mt-sign">{Lang::T('We apologize for any inconvenience.')}</p>
        </section>
    </main>
    <script>
        (function () {
            // the site is asked again every 30 seconds (and on the button): the page opens it once it no longer answers 503
            var btn = document.getElementById('mtCheck'), note = document.getElementById('mtNote');
            var busy = false, wait = 30, idle = note.textContent;
            function check() {
                if (busy) return;
                busy = true;
                btn.disabled = true;
                btn.classList.add('is-busy');
                // a stalled answer is given up on after 10 seconds, and then goes through the same cleanup as any other
                var ctl = window.AbortController ? new AbortController() : null;
                var stop = setTimeout(function () { if (ctl) ctl.abort(); }, 10000);
                fetch(location.pathname + location.search, { cache: 'no-store', credentials: 'same-origin', headers: { 'X-Requested-With': 'maintenance-check' }, signal: ctl ? ctl.signal : undefined })
                    // reloads only when the application itself answered: a 502, 503 or 504 (a proxy or the maintenance page) keeps waiting
                    .then(function (r) { if (r.ok) location.reload(); })
                    .catch(function () { })
                    .then(function () { clearTimeout(stop); })
                    .then(function () {
                        busy = false;
                        wait = 30;
                        btn.disabled = false;
                        btn.classList.remove('is-busy');
                        note.textContent = idle;
                    });
            }
            btn.addEventListener('click', check);
            setInterval(function () { if (!document.hidden && --wait <= 0) check(); }, 1000);
        })();
    </script>
    <script>
        (function () {
            // light / dark / device, kept in the same "theme" setting as the login page
            var root = document.documentElement;
            document.getElementById('mtToggle').addEventListener('click', function () {
                var now = root.classList.contains('theme-dark') ? 'dark' : root.classList.contains('theme-light') ? 'light' : 'system';
                var next = now === 'system' ? 'light' : now === 'light' ? 'dark' : 'system';
                root.classList.remove('theme-light', 'theme-dark');
                root.style.colorScheme = next === 'system' ? '' : next;
                if (next !== 'system') root.classList.add('theme-' + next);
                try { localStorage.setItem('theme', next); } catch (e) { }
            });
        })();
    </script>
    {if $date}
    <script>
        (function () {
            var end = new Date('{if $date|strlen == 10}{$date|escape:"javascript"}T00:00{else}{$date|replace:" ":"T"|escape:"javascript"}{/if}').getTime();
            var box = document.getElementById('mtCount');
            if (isNaN(end)) return;
            var at = document.getElementById('mtBackAt');
            if (at && end > Date.now()) {
                try { at.textContent = new Date(end).toLocaleString(undefined, { dateStyle: 'long', timeStyle: 'short' }); }
                catch (e) { at.textContent = new Date(end).toLocaleString(); }
                document.getElementById('mtBack').hidden = false;
            }
            function tick() {
                var left = end - Date.now();
                if (left <= 0) { box.hidden = true; var bk = document.getElementById('mtBack'); if (bk) bk.hidden = true; return false; }
                box.hidden = false;
                var s = Math.floor(left / 1000);
                document.getElementById('mtD').textContent = Math.floor(s / 86400);
                document.getElementById('mtH').textContent = Math.floor(s % 86400 / 3600);
                document.getElementById('mtM').textContent = Math.floor(s % 3600 / 60);
                document.getElementById('mtS').textContent = s % 60;
                return true;
            }
            if (tick()) {
                var timer = setInterval(function () { if (!tick()) clearInterval(timer); }, 1000);
            }
        })();
    </script>
    {/if}
</body>

</html>
