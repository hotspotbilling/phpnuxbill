{*
    New version notice, shown on the admin dashboards (default and dynamic, core and themes).
    Compares the installed version.json with the public one, and only offers a release that is newer than the one installed.
    The check is cached for a few hours in the browser; "Later" hides it for a day and "Skip this version" until a newer release.
*}
{if $_c['new_version_notify'] != 'disable'}
<section class="un" id="updateNotice" role="region" aria-labelledby="unTitle" hidden>
    <div class="un__head">
        <span class="un__icon" aria-hidden="true"><i class="fa fa-arrow-circle-up"></i></span>
        <div class="un__titles">
            <h2 class="un__title" id="unTitle">{Lang::T('Update available')}</h2>
            <p class="un__sub">{Lang::T('A new version of PHPNuxBill is ready to install.')}</p>
        </div>
        <button type="button" class="un__close" data-un="later" aria-label="{Lang::T('Close')}"><i class="fa fa-times" aria-hidden="true"></i></button>
    </div>

    <div class="un__vers">
        <div class="un__ver"><small>{Lang::T('Installed')}</small><b data-un-current>&hellip;</b></div>
        <i class="fa fa-long-arrow-right un__arrow" aria-hidden="true"></i>
        <div class="un__ver un__ver--new"><small>{Lang::T('Latest')}</small><b data-un-latest>&hellip;</b></div>
    </div>

    <div class="un__notes" data-un-notes hidden>
        <h3>{Lang::T('What\'s new')}</h3>
        <ul data-un-list></ul>
        <a class="un__more" data-un-more href="https://github.com/hotspotbilling/phpnuxbill/blob/master/CHANGELOG.md" target="_blank" rel="noopener" hidden></a>
    </div>

    <div class="un__actions">
        {if $_admin['user_type'] eq 'SuperAdmin'}
        <a class="un__btn un__btn--primary" href="{Text::url('community')}#update"><i class="fa fa-cloud-download" aria-hidden="true"></i> {Lang::T('Update now')}</a>
        {else}
        <a class="un__btn un__btn--primary" href="{Text::url('community')}#update"><i class="fa fa-info-circle" aria-hidden="true"></i> {Lang::T('View details')}</a>
        {/if}
        <button type="button" class="un__btn" data-un="later">{Lang::T('Later')}</button>
    </div>
    {if $_admin['user_type'] neq 'SuperAdmin'}
    <p class="un__note">{Lang::T('Only a SuperAdmin can install an update.')}</p>
    {/if}
    <button type="button" class="un__skip" data-un="skip">{Lang::T('Skip this version')}</button>
</section>

<script>
(function () {
    var REMOTE = 'https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/';
    var CHECK_TTL = 6 * 3600 * 1000;   // how long a GitHub answer is reused
    var LATER_TTL = 24 * 3600 * 1000;  // how long "Later" hides the notice
    var T_VERSION = '{Lang::T('Version')|escape:'javascript'}';
    var T_AVAILABLE = '{Lang::T('available')|escape:'javascript'}';
    var T_MORE = '{Lang::T('more changes in the changelog')|escape:'javascript'}';
    var T_ALL = '{Lang::T('Read the full changelog')|escape:'javascript'}';

    function store(key, value) {
        try {
            if (value === undefined) { return JSON.parse(localStorage.getItem('un.' + key)); }
            localStorage.setItem('un.' + key, JSON.stringify(value));
        } catch (e) { return null; }
    }
    function cmp(a, b) {
        var x = String(a).split('.'), y = String(b).split('.');
        for (var i = 0; i < Math.max(x.length, y.length); i++) {
            var d = (parseInt(x[i], 10) || 0) - (parseInt(y[i], 10) || 0);
            if (d) { return d < 0 ? -1 : 1; }
        }
        return 0;
    }
    function json(url) {
        return fetch(url + '?' + Date.now(), { cache: 'no-store' }).then(function (r) {
            if (!r.ok) { throw new Error(r.status); }
            return r.json();
        }).then(function (d) {
            if (!d || typeof d.version !== 'string' || !d.version.trim()) { throw new Error('no version'); }
            return d.version.trim();
        });
    }
    // The bullet points under "## <version>" in the public changelog, without markdown marks.
    function notesFor(md, version) {
        var lines = String(md).split(/\r?\n/), out = [], on = false;
        for (var i = 0; i < lines.length; i++) {
            var l = lines[i];
            if (/^##\s/.test(l)) { if (on) { break; } on = l.replace(/^##\s+/, '').trim() === version; continue; }
            if (on && /^\s*[-*]\s+/.test(l)) {
                out.push(l.replace(/^\s*[-*]\s+/, '').replace(/[`*_]/g, '').trim());
            }
        }
        return out;
    }
    // A cached answer is reused for a few hours, so the dashboard does not ask GitHub on every visit.
    function latest() {
        var c = store('check');
        if (c && c.at && Date.now() - c.at < CHECK_TTL && c.version) { return Promise.resolve(c); }
        return json(REMOTE + 'version.json').then(function (v) {
            return fetch(REMOTE + 'CHANGELOG.md?' + Date.now(), { cache: 'no-store' })
                .then(function (r) { return r.ok ? r.text() : ''; })
                .catch(function () { return ''; })
                .then(function (md) {
                    var c = { at: Date.now(), version: v, notes: notesFor(md, v) };
                    store('check', c);
                    return c;
                });
        });
    }

    function footer(text, isNew) {
        var el = document.getElementById('version');
        if (!el) { return; }
        el.textContent = text;
        el.style.cursor = 'pointer';
        el.classList.toggle('un-footer-new', !!isNew);
    }

    function show(box, current, info) {
        box.querySelector('[data-un-current]').textContent = current;
        box.querySelector('[data-un-latest]').textContent = info.version;

        var notes = info.notes || [], list = box.querySelector('[data-un-list]'), more = box.querySelector('[data-un-more]');
        if (notes.length) {
            notes.slice(0, 4).forEach(function (n) {
                var li = document.createElement('li');
                li.textContent = n.length > 140 ? n.slice(0, 137) + '…' : n;
                li.title = n;
                list.appendChild(li);
            });
            more.textContent = notes.length > 4 ? '+' + (notes.length - 4) + ' ' + T_MORE : T_ALL;
            more.hidden = false;
            box.querySelector('[data-un-notes]').hidden = false;
        }

        function close(kind) {
            if (kind === 'skip') { store('skip', info.version); } else { store('later', { version: info.version, at: Date.now() }); }
            box.classList.remove('is-open');
            document.removeEventListener('keydown', onKey);
            setTimeout(function () { box.hidden = true; }, 250);
        }
        function onKey(e) { if (e.key === 'Escape') { close('later'); } }
        box.addEventListener('click', function (e) {
            var b = e.target.closest('[data-un]');
            if (b) { close(b.getAttribute('data-un')); }
        });
        document.addEventListener('keydown', onKey);

        box.hidden = false;
        // a short wait so the notice slides in after the dashboard has drawn, instead of jumping in with it
        setTimeout(function () { box.classList.add('is-open'); }, 900);
    }

    function run() {
        var box = document.getElementById('updateNotice');
        if (!box || !window.fetch) { return; }
        json('./version.json').then(function (current) {
            footer(T_VERSION + ' ' + current);
            return latest().then(function (info) {
                if (cmp(current, info.version) >= 0) { return; }   // up to date, or ahead of the public release
                footer(T_VERSION + ' ' + current + ' · ' + info.version + ' ' + T_AVAILABLE, true);
                if (store('skip') === info.version) { return; }
                var later = store('later');
                if (later && later.version === info.version && Date.now() - later.at < LATER_TTL) { return; }
                show(box, current, info);
            });
        }).catch(function () { /* offline, or GitHub unreachable: say nothing */ });
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', run); } else { run(); }
})();
</script>
{/if}
