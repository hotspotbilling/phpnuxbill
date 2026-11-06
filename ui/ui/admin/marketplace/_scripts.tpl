<script>
    window.MP = window.MP || {};
    MP.csrf = '{$csrf_token}';
    MP.base = '{Text::url('marketplace/')}';
    MP.route = '{$route|default:''}';
    MP.t = { };
    MP.t.neu = '{Lang::T('New')|escape:'javascript'}';
    MP.t.install = '{Lang::T('Install')|escape:'javascript'}';
    MP.t.update = '{Lang::T('Update')|escape:'javascript'}';
    MP.t.license = '{Lang::T('License')|escape:'javascript'}';
    MP.t.type = '{Lang::T('Type')|escape:'javascript'}';
    MP.t.version = '{Lang::T('Version')|escape:'javascript'}';
    MP.t.author = '{Lang::T('Author')|escape:'javascript'}';
    MP.t.updated = '{Lang::T('Last update')|escape:'javascript'}';
    MP.t.source = '{Lang::T('Source')|escape:'javascript'}';
    MP.t.package = '{Lang::T('Package')|escape:'javascript'}';
    MP.t.website = '{Lang::T('Website')|escape:'javascript'}';
    MP.t.layout = '{Lang::T('Layout')|escape:'javascript'}';
    MP.t.folder = '{Lang::T('Folder style')|escape:'javascript'}';
    MP.t.flat = '{Lang::T('Single file')|escape:'javascript'}';
    MP.t.licenseState = '{Lang::T('License')|escape:'javascript'}';
    MP.t.premium = '{Lang::T('Premium')|escape:'javascript'}';
    MP.t.free = '{Lang::T('Free')|escape:'javascript'}';
    MP.t.installed = '{Lang::T('Installed')|escape:'javascript'}';
    MP.t.repo = '{Lang::T('Store')|escape:'javascript'}';
    MP.t.confirmInstall = '{Lang::T('Install this extension? It is checked, then installed; any existing copy is backed up first.')|escape:'javascript'}';
    MP.t.confirmUpdate = '{Lang::T('Update this extension? The current version is backed up first.')|escape:'javascript'}';
    MP.t.noReadme = '{Lang::T('No README available.')|escape:'javascript'}';
    MP.t.loading = '{Lang::T('Loading...')|escape:'javascript'}';
    MP.t.watchAdded = '{Lang::T('Added to watchlist')|escape:'javascript'}';
    MP.t.watchRemoved = '{Lang::T('Removed from watchlist')|escape:'javascript'}';
    MP.t.licNeeded = '{Lang::T('License needed')|escape:'javascript'}';
    MP.t.licOk = '{Lang::T('Licensed')|escape:'javascript'}';
    MP.t.writable = {if isset($writable) && !$writable}false{else}true{/if};
</script>
{literal}
<script>
    (function () {
        var root = document.querySelector('.mp-wrap');
        if (!root) return;
        var cards = root.querySelectorAll('.mp-card[data-search]');
        var search = root.querySelector('[data-mp-search]');
        var filters = root.querySelectorAll('.mp-filters [data-filter]');
        var empty = root.querySelector('#mp-noresults');
        var state = { filter: 'all', query: '' };
        var NEW_DAYS = 90;

        function el(tag, cls, text) {
            var n = document.createElement(tag);
            if (cls) n.className = cls;
            if (text !== undefined && text !== null) n.textContent = text;
            return n;
        }

        // flag store items updated recently so they can be filtered / badged
        var now = Date.now();
        cards.forEach(function (card) {
            var d = Date.parse(card.getAttribute('data-date') || '');
            if (!isNaN(d) && (now - d) / 86400000 <= NEW_DAYS) {
                card.setAttribute('data-tags', (card.getAttribute('data-tags') || '') + ' new');
                var tags = card.querySelector('.mp-card__tags');
                if (tags) tags.appendChild(el('span', 'mp-badge mp-badge--new', MP.t.neu));
            }
        });

        // click a clamped description to expand / collapse it
        root.querySelectorAll('.mp-card__body p').forEach(function (p) {
            if (p.scrollHeight > p.clientHeight + 1) {
                p.classList.add('is-clamped');
                p.addEventListener('click', function () { p.classList.toggle('is-open'); });
            }
        });

        function apply() {
            var shown = 0;
            cards.forEach(function (card) {
                var tags = ' ' + (card.getAttribute('data-tags') || '') + ' ';
                var okFilter = state.filter === 'all' || tags.indexOf(' ' + state.filter + ' ') !== -1;
                var okQuery = !state.query || card.getAttribute('data-search').indexOf(state.query) !== -1;
                var visible = okFilter && okQuery;
                card.classList.toggle('mp-card--hidden', !visible);
                if (visible) shown++;
            });
            root.querySelectorAll('.mp-grid').forEach(function (grid) {
                var any = grid.querySelector('.mp-card:not(.mp-card--hidden)');
                grid.style.display = any ? '' : 'none';
                var head = grid.previousElementSibling;
                while (head && !head.classList.contains('mp-section')) head = head.previousElementSibling;
                if (head && head.hasAttribute('data-mp-section')) head.classList.toggle('mp-section--hidden', !any);
            });
            if (empty) empty.classList.toggle('is-visible', cards.length > 0 && shown === 0);
        }
        if (search) {
            search.addEventListener('input', function () {
                state.query = this.value.toLowerCase().trim();
                apply();
            });
        }
        filters.forEach(function (btn) {
            btn.addEventListener('click', function () {
                filters.forEach(function (b) { b.classList.remove('is-active'); });
                this.classList.add('is-active');
                state.filter = this.getAttribute('data-filter') || 'all';
                apply();
            });
        });

        // sorting of the store grids
        function sortGrids(mode) {
            root.querySelectorAll('.mp-grid[data-sortable]').forEach(function (grid) {
                var items = Array.prototype.slice.call(grid.children);
                if (!grid.hasAttribute('data-original')) {
                    items.forEach(function (c, i) { c.setAttribute('data-pos', i); });
                    grid.setAttribute('data-original', '1');
                }
                items.sort(function (a, b) {
                    if (mode === 'name') return (a.querySelector('h3').textContent || '').localeCompare(b.querySelector('h3').textContent || '');
                    if (mode === 'newest') return (Date.parse(b.getAttribute('data-date') || '') || 0) - (Date.parse(a.getAttribute('data-date') || '') || 0);
                    return (+a.getAttribute('data-pos')) - (+b.getAttribute('data-pos'));
                });
                items.forEach(function (c) { grid.appendChild(c); });
            });
        }
        var sorter = root.querySelector('[data-mp-sort]');
        if (sorter) sorter.addEventListener('change', function () { sortGrids(this.value); });

        var toggle = root.querySelector('#toggle-upload');
        if (toggle) {
            toggle.addEventListener('click', function () {
                var panel = document.getElementById('upload-panel');
                if (panel) panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
            });
        }

        // ---- watchlist star ---------------------------------------------------
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-watch]');
            if (!btn) return;
            e.preventDefault();
            var body = new URLSearchParams();
            body.set('csrf_token', MP.csrf);
            body.set('key', btn.getAttribute('data-watch'));
            fetch(MP.base + 'watch', { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (!j.ok) return;
                    document.querySelectorAll('[data-watch="' + btn.getAttribute('data-watch') + '"]').forEach(function (b) {
                        b.classList.toggle('is-on', j.watched);
                        b.setAttribute('aria-pressed', j.watched ? 'true' : 'false');
                        var card = b.closest('.mp-card');
                        if (card) {
                            var t = ' ' + (card.getAttribute('data-tags') || '') + ' ';
                            t = t.replace(' watch ', ' ').trim();
                            if (j.watched) t += ' watch';
                            card.setAttribute('data-tags', t);
                        }
                    });
                    var wc = document.querySelector('[data-filter="watch"] b');
                    if (wc) wc.textContent = j.count;
                });
        });

        // ---- details drawer -----------------------------------------------------
        var drawer = document.getElementById('mp-drawer');
        var license = document.getElementById('mp-license');
        // Overlays must be children of <body>: inside the admin layout an ancestor (.content-wrapper uses
        // transform) makes "position: fixed" relative to the whole page, so the drawer would be page-tall and
        // its header or its Install footer would sit off-screen depending on how far the page is scrolled.
        [drawer, license].forEach(function (n) { if (n && n.parentNode !== document.body) document.body.appendChild(n); });
        function closeAll() {
            if (drawer) drawer.hidden = true;
            if (license) license.hidden = true;
            document.body.classList.remove('mp-noscroll');
        }
        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-close]')) closeAll();
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(); });

        function fact(dl, label, value) {
            if (value === undefined || value === null || value === '') return;
            dl.appendChild(el('dt', '', label));
            var dd = el('dd');
            if (value instanceof Node) dd.appendChild(value); else dd.textContent = value;
            dl.appendChild(dd);
        }
        function link(href, text) {
            // only http(s) URLs become clickable; anything else is shown as plain text
            if (!/^https?:\/\//i.test(String(href || ''))) return document.createTextNode(text || '');
            var a = el('a', 'mp-link', text);
            a.href = href; a.target = '_blank'; a.rel = 'noopener noreferrer';
            return a;
        }
        function postForm(action, label, cls, confirmText) {
            var f = el('form', 'mp-inline');
            f.method = 'post'; f.action = action;
            var h = el('input'); h.type = 'hidden'; h.name = 'csrf_token'; h.value = MP.csrf; f.appendChild(h);
            var b = el('button', 'mp-act ' + cls, label); b.type = 'button';
            if (!MP.t.writable) b.disabled = true;
            b.addEventListener('click', function () { return ask(b, confirmText); });
            f.appendChild(b);
            return f;
        }
        function openDetails(info) {
            document.getElementById('mp-d-title').textContent = info.name || info.slug || info.id;
            document.getElementById('mp-d-sub').textContent = (info.author ? '@' + info.author : '') + (info.version ? '  v' + info.version : '');
            document.getElementById('mp-d-desc').textContent = info.description || '';
            var tags = document.getElementById('mp-d-tags'); tags.textContent = '';
            tags.appendChild(el('span', 'mp-badge mp-badge--folder', info.type));
            tags.appendChild(el('span', 'mp-badge ' + (info.kind === 'installed' ? 'mp-badge--active' : (info.paid ? 'mp-badge--paid' : 'mp-badge--free')),
                info.kind === 'installed' ? MP.t.installed : (info.paid ? MP.t.premium : MP.t.free)));
            var issues = document.getElementById('mp-d-issues'); issues.textContent = '';
            if (info.health && info.health.messages && info.health.messages.length) {
                var box = el('div', 'mp-banner ' + (info.health.status === 'error' ? 'mp-banner--error' : 'mp-banner--warn'));
                info.health.messages.forEach(function (m) { box.appendChild(el('div', '', m)); });
                issues.appendChild(box);
            }
            var dl = document.getElementById('mp-d-facts'); dl.textContent = '';
            fact(dl, MP.t.type, info.type);
            fact(dl, MP.t.version, info.version);
            fact(dl, MP.t.author, info.author);
            fact(dl, MP.t.updated, info.updated);
            if (info.style) fact(dl, MP.t.layout, info.style === 'folder' ? MP.t.folder : MP.t.flat);
            if (info.repo) fact(dl, MP.t.repo, info.repo);
            if (info.update && info.update.available) fact(dl, MP.t.update, (info.update.current || '?') + ' → ' + info.update.latest);
            if (info.license && info.license.required) fact(dl, MP.t.licenseState, info.license.blocked ? MP.t.licNeeded : MP.t.licOk);
            if (info.url) fact(dl, MP.t.website, link(info.url, info.url));
            if (info.github) fact(dl, MP.t.source, link(info.github, info.github));
            if (info.zip) fact(dl, MP.t.package, link(info.zip, info.zip));

            var acts = document.getElementById('mp-d-actions'); acts.textContent = '';
            var route = info.type === 'plugin' ? 'plugins' : info.type === 'gateway' ? 'gateways' : info.type === 'device' ? 'devices' : 'themes';
            if (info.kind === 'store') {
                acts.appendChild(postForm(MP.base + route + '/install/' + encodeURIComponent(info.id), MP.t.install, 'mp-act--primary', MP.t.confirmInstall));
            } else {
                if (info.update && info.update.available) acts.appendChild(postForm(MP.base + route + '/update/' + encodeURIComponent(info.slug), MP.t.update, 'mp-act--success', MP.t.confirmUpdate));
                if (info.license && info.license.required) {
                    var lb = el('button', 'mp-act', MP.t.license); lb.type = 'button';
                    lb.addEventListener('click', function () { openLicense(route, info.slug, info.name); });
                    acts.appendChild(lb);
                }
            }

            var rt = document.getElementById('mp-d-readme-title'), rb = document.getElementById('mp-d-readme');
            rt.hidden = true; rb.hidden = true; rb.textContent = '';
            if (info.github) {
                rt.hidden = false; rb.hidden = false; rb.textContent = MP.t.loading;
                var ownerName = String(info.github).replace(/^https:\/\/github\.com\//i, '').replace(/\/+$/, '');
                fetch(MP.base + 'readme&repo=' + encodeURIComponent(ownerName), { credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        if (j.html) rb.innerHTML = j.html; // server-side escaped markdown subset
                        else rb.textContent = MP.t.noReadme;
                    }).catch(function () { rb.textContent = MP.t.noReadme; });
            }
            drawer.hidden = false;
            document.body.classList.add('mp-noscroll');
        }
        document.addEventListener('click', function (e) {
            var b = e.target.closest('[data-details]');
            if (!b) return;
            var card = b.closest('[data-info]');
            if (!card) return;
            try { openDetails(JSON.parse(card.getAttribute('data-info'))); } catch (err) { }
        });

        // ---- license modal ------------------------------------------------------
        function openLicense(route, slug, name) {
            if (drawer) drawer.hidden = true;
            document.getElementById('mp-license-form').action = MP.base + route + '/license/' + encodeURIComponent(slug);
            document.getElementById('mp-license-name').textContent = name || slug;
            document.getElementById('mp-license-key').value = '';
            license.hidden = false;
            document.getElementById('mp-license-key').focus();
        }
        document.addEventListener('click', function (e) {
            var b = e.target.closest('[data-license]');
            if (!b || !license) return;
            var card = b.closest('[data-info]');
            var info = {};
            try { info = JSON.parse(card.getAttribute('data-info')); } catch (err) { }
            var route = info.type === 'plugin' ? 'plugins' : info.type === 'gateway' ? 'gateways' : info.type === 'device' ? 'devices' : 'themes';
            openLicense(route, b.getAttribute('data-license'), info.name);
        });
    })();
</script>
{/literal}
