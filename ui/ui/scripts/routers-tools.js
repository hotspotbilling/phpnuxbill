/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *
 *  Routers list: the tabs under a router's details (users online, interfaces, setup check, updates and backups). Settings come
 *  from window.rtTools (url, csrf, superadmin, t = texts), set by admin/routers/list.tpl. A tab is loaded the first time it is
 *  opened and again with its Refresh button. Everything shown comes from the router, so all of it is escaped; changes
 *  (disconnect, check for updates, download) are POSTed with the CSRF token, and the server checks the role again.
 **/
(function () {
    'use strict';
    var cfg = window.rtTools;
    if (!cfg) return;
    var t = cfg.t;
    var LIMIT = 30000; // ms a tab may take to load

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function size(b) {
        if (b == null) return '';
        var u = ['B', 'KB', 'MB', 'GB', 'TB'], i = 0;
        while (b >= 1024 && i < u.length - 1) { b /= 1024; i++; }
        return (b >= 10 || i === 0 ? Math.round(b) : b.toFixed(1)) + ' ' + u[i];
    }
    function num(n) {
        return n == null ? '' : Number(n).toLocaleString();
    }
    function url(action, id) {
        // id is a router number, optionally followed by a query ("3?range=7d"); with plain links the route is already in "?"
        var parts = String(id).split('?');
        var q = parts[1] ? (cfg.url.indexOf('?') < 0 ? '?' : '&') + parts[1] : '';
        return cfg.url + action + '/' + encodeURIComponent(parts[0]) + q;
    }
    function request(action, id, post) {
        var ctl = window.AbortController ? new AbortController() : null;
        var timer = ctl ? setTimeout(function () { ctl.abort(); }, LIMIT) : null;
        var opts = { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' }, signal: ctl ? ctl.signal : undefined };
        if (post) {
            var body = new FormData();
            body.append('csrf_token', cfg.csrf);
            Object.keys(post).forEach(function (k) { body.append(k, post[k]); });
            opts.method = 'POST';
            opts.body = body;
        }
        return fetch(url(action, id), opts)
            .then(function (r) { return r.json(); })
            .catch(function () { return { ok: false, error: t.fail }; })
            .then(function (d) { clearTimeout(timer); return d || { ok: false, error: t.fail }; });
    }
    function fail(d) {
        return '<p class="rt-err rt-err--block"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' + esc(d.error || t.fail)
            + (d.hint ? '<small class="rt-hint">' + esc(d.hint) + '</small>' : '') + '</p>';
    }
    function head(title, extra) {
        return '<div class="rt-pane__head"><b>' + esc(title) + '</b>' + (extra || '')
            + '<button type="button" class="ls-btn ls-btn--sm" data-rt-reload><i class="fa fa-refresh" aria-hidden="true"></i> ' + esc(t.refresh) + '</button></div>';
    }

    /* ---------- a list that can be searched, filtered and paged in the page (the rows are fetched once) ---------- */
    var PAGE = 25;
    // opts: rows, cols (header cells), row (row -> <tr> html), match (row, lower-case query -> bool), filters [{ key, label, test }],
    // toggle { label, test, on } (rows hidden unless ticked), empty (text), capped (text or '')
    function listView(pane, title, opts) {
        pane._lv = { opts: opts, q: '', f: opts.filters ? opts.filters[0].key : '', all: opts.toggle ? !!opts.toggle.on : true, page: 0 };
        // the same search field as the Routers list itself (.ls-search: icon, field colours, focus ring, light and dark)
        var tools = '<div class="rt-lv__tools"><div class="ls-search rt-lv__q"><i class="fa fa-search" aria-hidden="true"></i>'
            + '<input type="search" autocomplete="off" data-rt-q placeholder="' + esc(t.search) + '" aria-label="' + esc(t.search) + '"></div>';
        if (opts.filters) {
            tools += '<span class="rt-ranges" role="group">' + opts.filters.map(function (f, i) {
                return '<button type="button" class="ls-btn ls-btn--sm' + (i ? '' : ' is-on') + '" data-rt-f="' + esc(f.key) + '" aria-pressed="' + (i === 0) + '">' + esc(f.label) + '</button>';
            }).join('') + '</span>';
        }
        if (opts.toggle) {
            tools += '<label class="rt-lv__toggle"><input type="checkbox" data-rt-all' + (opts.toggle.on ? ' checked' : '') + '> ' + esc(opts.toggle.label) + '</label>';
        }
        pane.innerHTML = head(title) + tools + '</div>' + (opts.capped ? '<p class="rt-err rt-err--block">' + esc(opts.capped) + '</p>' : '')
            + (opts.notice ? '<p class="rt-err rt-err--block">' + esc(opts.notice) + '</p>' : '')
            + '<div data-rt-list></div><div class="rt-lv__pager" data-rt-pager></div>';
        listRender(pane);
    }
    function listRender(pane) {
        var st = pane._lv, o = st.opts, q = st.q.trim().toLowerCase();
        // a page button that had the keyboard keeps it (the buttons are drawn again below)
        var fe = document.activeElement, focusPage = fe && pane.contains(fe) && fe.getAttribute('data-rt-page');
        var filter = o.filters ? o.filters.filter(function (f) { return f.key === st.f; })[0] : null;
        var rows = o.rows.filter(function (r) {
            return (!filter || filter.test(r)) && (!o.toggle || st.all || !o.toggle.test(r)) && (!q || o.match(r, q));
        });
        var pages = Math.max(1, Math.ceil(rows.length / PAGE));
        st.page = Math.min(st.page, pages - 1);
        var from = st.page * PAGE, part = rows.slice(from, from + PAGE);
        pane.querySelector('[data-rt-list]').innerHTML = part.length
            ? '<div class="ls-scroll"><table class="rt-mini"><thead><tr>' + o.cols.map(function (c) { return '<th>' + esc(c) + '</th>'; }).join('')
                + '</tr></thead><tbody>' + part.map(o.row).join('') + '</tbody></table></div>'
            : '<p class="ls-dim">' + esc(o.rows.length ? t.noMatch : o.empty) + '</p>';
        pane.querySelector('[data-rt-pager]').innerHTML = rows.length > PAGE
            ? '<span class="ls-dim">' + (from + 1) + '&ndash;' + (from + part.length) + ' ' + esc(t.of) + ' ' + rows.length + '</span>'
                + '<button type="button" class="ls-btn ls-btn--sm" data-rt-page="-1"' + (st.page ? '' : ' disabled') + '><i class="fa fa-chevron-left" aria-hidden="true"></i> ' + esc(t.prev) + '</button>'
                + '<button type="button" class="ls-btn ls-btn--sm" data-rt-page="1"' + (st.page < pages - 1 ? '' : ' disabled') + '>' + esc(t.next) + ' <i class="fa fa-chevron-right" aria-hidden="true"></i></button>'
            : (rows.length ? '<span class="ls-dim">' + rows.length + ' ' + esc(t.shown) + '</span>' : '');
        if (focusPage) {
            var same = pane.querySelector('[data-rt-page="' + focusPage + '"]:not([disabled])'), other = pane.querySelector('[data-rt-page]:not([disabled])');
            if (same || other) (same || other).focus();
        }
    }
    function has(v, q) {
        return v != null && String(v).toLowerCase().indexOf(q) !== -1;
    }

    /* ---------- users online ---------- */
    function dropKey(cfg, kind, sid) {
        return (cfg || '') + ' ' + kind + ' ' + sid;
    }
    function loadUsers(id, pane) {
        return request('sessions', id).then(function (d) {
            if (!d.ok) { pane.innerHTML = head(t.user) + fail(d); return; }
            var rows = d.hotspot.map(function (r) { r.kind = 'hotspot'; return r; }).concat(d.pppoe.map(function (r) { r.kind = 'pppoe'; return r; }));
            // a disconnect sent from here (still on its way, or done) stays shown on its row, even in a list read from the router
            // before it went through; kept per router settings, as a session ID only means something on the router it came from
            rows.forEach(function (r) {
                var s = pane._drops && pane._drops[dropKey(d.cfg, r.kind, r.sid)];
                if (s === 'gone') r.gone = true; else if (s === 'pending') r.pending = true;
            });
            var curl = d.customer_url;
            var total = (d.total_hotspot || d.hotspot.length) + (d.total_pppoe || d.pppoe.length);
            listView(pane, total + ' ' + t.online, {
                rows: rows,
                cfg: d.cfg, // which router settings the list was read with: a disconnect is refused once they change
                capped: rows.length < total ? t.capped.replace('%s', rows.length) : '',
                // a menu the router refused (no permission) is said, so an empty list is not taken for nobody online
                notice: (d.read_errors || []).length ? t.readErr + ': ' + d.read_errors.join('; ') : '',
                empty: t.none,
                cols: [t.user, t.type, t.address, t.mac, t.uptime, t.data, ''],
                filters: [{ key: 'all', label: t.all, test: function () { return true; } },
                    { key: 'hotspot', label: 'Hotspot ' + d.hotspot.length, test: function (r) { return r.kind === 'hotspot'; } },
                    { key: 'pppoe', label: 'PPPoE ' + d.pppoe.length, test: function (r) { return r.kind === 'pppoe'; } }],
                match: function (r, q) {
                    return has(r.user, q) || has(r.address, q) || has(r.mac, q) || (r.customer && has(r.customer.name, q));
                },
                row: function (r) {
                    var who = r.customer
                        ? '<a href="' + esc(curl + r.customer.id) + '"><b>' + esc(r.user) + '</b></a>' + (r.customer.name ? ' <small class="ls-dim">' + esc(r.customer.name) + '</small>' : '')
                        : '<b>' + esc(r.user) + '</b> <small class="ls-dim">' + esc(t.notCustomer) + '</small>';
                    return '<tr><td>' + who + '</td><td class="ls-dim">' + (r.kind === 'hotspot' ? 'Hotspot' : 'PPPoE') + '</td><td>' + esc(r.address) + '</td><td class="ls-dim">' + esc(r.mac) + '</td><td>' + esc(r.uptime) + '</td>'
                        + '<td class="ls-dim">' + (r.kind === 'hotspot' ? '&darr; ' + esc(size(r.out)) + ' &middot; &uarr; ' + esc(size(r['in'])) : '') + '</td>'
                        + '<td class="rt-mini__end">' + (r.gone ? '<span class="ls-dim">' + esc(t.dropped) + '</span>'
                            : '<button type="button" class="ls-btn ls-btn--sm ls-btn--danger" data-rt-drop="' + esc(r.kind) + '" data-sid="' + esc(r.sid) + '" data-user="' + esc(r.user)
                            + '"' + (r.pending ? ' disabled' : '') + '><i class="fa ' + (r.pending ? 'fa-spinner fa-spin' : 'fa-sign-out') + '" aria-hidden="true"></i> ' + esc(t.drop) + '</button>'
                            + (r.err ? '<small class="rt-err">' + esc(r.err) + '</small>' : '')) + '</td></tr>';
                }
            });
        });
    }

    /* ---------- interfaces ---------- */
    function loadIfaces(id, pane) {
        return request('interfaces', id).then(function (d) {
            if (!d.ok) { pane.innerHTML = head(t.name) + fail(d); return; }
            var dyn = d.items.filter(function (i) { return i.dynamic; }).length;
            listView(pane, (d.total || d.items.length) + ' ' + t.interfaces, {
                rows: d.items,
                capped: d.total > d.items.length ? t.capped.replace('%s', d.items.length) : '',
                empty: t.noIfaces,
                cols: [t.name, t.type, t.state, t.traffic, t.errors, t.linkDowns, t.lastUp],
                // each PPPoE customer gets a dynamic interface of its own: hidden unless asked for, so the real ports are not lost
                toggle: dyn ? { label: t.showDynamic + ' (' + dyn + ')', test: function (i) { return i.dynamic; }, on: false } : null,
                match: function (i, q) { return has(i.name, q) || has(i.type, q) || has(i.comment, q); },
                row: function (i) {
                    var bad = (i.rx_error || 0) + (i.tx_error || 0) + (i.rx_drop || 0) + (i.tx_drop || 0);
                    var state = i.disabled ? '<span class="ls-pill">' + esc(t.disabled) + '</span>'
                        : i.running ? '<span class="ls-pill ls-pill--active">' + esc(t.up) + '</span>' : '<span class="ls-pill ls-pill--banned">' + esc(t.down) + '</span>';
                    return '<tr><td><b>' + esc(i.name) + '</b>' + (i.comment ? ' <small class="ls-dim">' + esc(i.comment) + '</small>' : '') + '</td>'
                        + '<td class="ls-dim">' + esc(i.type) + '</td><td>' + state + '</td>'
                        + '<td class="ls-dim">&darr; ' + esc(size(i.rx)) + ' &middot; &uarr; ' + esc(size(i.tx)) + '</td>'
                        + '<td' + (bad ? ' class="rt-warn"' : ' class="ls-dim"') + '>' + esc(num(i.rx_error)) + ' / ' + esc(num(i.tx_error)) + ' &middot; ' + esc(num(i.rx_drop)) + ' / ' + esc(num(i.tx_drop)) + '</td>'
                        + '<td' + (i.link_downs > 0 ? ' class="rt-warn"' : ' class="ls-dim"') + '>' + esc(num(i.link_downs)) + '</td>'
                        + '<td class="ls-dim">' + esc(i.last_up || '') + '</td></tr>';
                }
            });
        });
    }

    // the list controls: typing searches, a filter or page button moves, the switch shows the hidden rows
    document.addEventListener('input', function (e) {
        var q = e.target.closest && e.target.closest('[data-rt-q]');
        if (!q) return;
        var pane = q.closest('[data-rt-pane]');
        pane._lv.q = q.value;
        pane._lv.page = 0;
        listRender(pane);
    });
    document.addEventListener('change', function (e) {
        var box = e.target.closest && e.target.closest('[data-rt-all]');
        if (!box) return;
        var pane = box.closest('[data-rt-pane]');
        pane._lv.all = box.checked;
        pane._lv.page = 0;
        listRender(pane);
    });

    /* ---------- setup check ---------- */
    function loadCheck(id, pane) {
        return request('check', id).then(function (d) {
            if (!d.ok) { pane.innerHTML = head(t.runAgain) + fail(d); return; }
            var problems = d.items.filter(function (i) { return i.state !== 'ok'; }).length;
            var icon = { ok: 'fa-check-circle', warn: 'fa-exclamation-circle', bad: 'fa-times-circle' };
            var html = head(problems ? problems + ' ' + (problems === 1 ? t.oneProblem : t.problems) : t.allGood) + '<ul class="rt-checks">';
            d.items.forEach(function (i) {
                html += '<li class="rt-check rt-check--' + esc(i.state) + '"><i class="fa ' + icon[i.state] + '" aria-hidden="true"></i><div><b>' + esc(i.title) + '</b>'
                    + (i.detail ? '<small>' + esc(i.detail) + '</small>' : '') + '</div></li>';
            });
            pane.innerHTML = html + '</ul>';
        });
    }

    /* ---------- updates and backups ---------- */
    function loadUpdates(id, pane, check) {
        // while "Check for updates" runs, Refresh waits: a plain read finishing after it would put back the old versions
        if (check) {
            pane._busy = true;
            pane.querySelectorAll('[data-rt-reload]').forEach(function (b) { b.disabled = true; });
        }
        return Promise.all([request('updates', id, check ? {} : null), request('backups', id)]).then(function (res) {
            if (pane._busy && !check) return;
            pane._busy = false;
            var u = res[0], b = res[1];
            var html = head(t.routeros);
            if (!u.ok) {
                html += fail(u);
            } else {
                var verdict = !u.latest ? '<span class="ls-dim">' + esc(t.notChecked) + '</span>'
                    : u.newer ? '<span class="ls-pill ls-pill--inactive">' + esc(t.newer) + ': ' + esc(u.latest) + '</span>'
                        : '<span class="ls-pill ls-pill--active">' + esc(t.current) + '</span>';
                html += '<dl class="rt-facts">'
                    + '<div><dt>' + esc(t.installed) + '</dt><dd>' + esc(u.installed) + '</dd></div>'
                    + '<div><dt>' + esc(t.latest) + '</dt><dd>' + verdict + '</dd></div>'
                    + (u.channel ? '<div><dt>' + esc(t.channel) + '</dt><dd>' + esc(u.channel) + '</dd></div>' : '')
                    + (u.fw_current ? '<div><dt>' + esc(t.firmware) + '</dt><dd>' + esc(u.fw_current)
                        + (u.fw_newer ? ' <span class="ls-pill ls-pill--inactive">' + esc(t.newer) + ': ' + esc(u.fw_upgrade) + '</span>' : '') + '</dd></div>' : '')
                    + '</dl>'
                    + (u.check_error ? '<p class="rt-err rt-err--block">' + esc(u.check_error) + '</p>' : '')
                    + '<p class="rt-pane__row"><button type="button" class="ls-btn ls-btn--sm" data-rt-checkupd><i class="fa fa-search" aria-hidden="true"></i> '
                    + esc(t.checkUpdates) + '</button> <small class="ls-dim">' + esc(t.updateHow) + '</small></p>';
            }
            html += '<h4 class="rt-mini__title">' + esc(t.backups) + '</h4>';
            if (!b.ok) {
                html += fail(b);
            } else {
                html += '<p class="ls-dim rt-pane__row">' + esc(t.nightly) + ': <b>' + esc(b.nightly ? t.on : t.off) + '</b> (' + esc(t.nightlyWhere) + ') &middot; '
                    + esc(t.downloadHow) + '</p>';
                if (!b.items.length) {
                    html += '<p class="ls-dim">' + esc(t.noBackups) + '</p>';
                } else {
                    html += '<div class="ls-scroll"><table class="rt-mini"><tbody>';
                    b.items.forEach(function (f) {
                        html += '<tr><td><b>' + esc(f.name) + '</b>' + (f.auto ? ' <span class="ls-pill">' + esc(t.auto) + '</span>' : '') + '</td><td class="ls-dim">' + esc(f.created || '')
                            + '</td><td class="ls-dim rt-mini__end">' + esc(size(f.size)) + '</td></tr>';
                    });
                    html += '</tbody></table></div>';
                }
            }
            pane.innerHTML = html;
        });
    }

    /* ---------- history (readings kept by the cron) ---------- */
    function bps(v) {
        if (v == null) return '';
        var u = ['bps', 'kbps', 'Mbps', 'Gbps'], i = 0;
        while (v >= 1000 && i < u.length - 1) { v /= 1000; i++; }
        return (v >= 100 || i === 0 ? Math.round(v) : v.toFixed(1)) + ' ' + u[i];
    }
    function stamp(at, long) {
        var d = new Date(at * 1000);
        var hm = ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
        return long ? d.toLocaleDateString(undefined, { day: 'numeric', month: 'short' }) + ' ' + hm : hm;
    }
    function niceMax(v) {
        if (!v || v <= 0) return 1;
        var p = Math.pow(10, Math.floor(Math.log10(v)));
        var n = v / p;
        return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * p;
    }
    // one y axis, 2px lines, a crosshair and tooltip; series: [{ label, cls, points: [[at, v]] }]; gaps (null) break the line
    // empty: the text shown when no series has a value
    function lineChart(series, fmt, max, from, to, long, empty) {
        var W = 600, H = 150, padL = 56, padR = 12, padT = 10, padB = 22;
        var vals = [];
        series.forEach(function (s) { s.points.forEach(function (p) { if (p[1] != null) vals.push(p[1]); }); });
        var top = max != null ? max : niceMax(Math.max.apply(null, vals.concat([0])) * 1.1);
        function X(at) { return padL + (at - from) / (to - from) * (W - padL - padR); }
        function Y(v) { return padT + (1 - Math.min(v, top) / top) * (H - padT - padB); }
        var svg = '<svg viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="none" class="rt-chart__svg" aria-hidden="true">';
        [0, 0.5, 1].forEach(function (f) {
            svg += '<line class="rt-chart__grid" x1="' + padL + '" x2="' + (W - padR) + '" y1="' + Y(top * f) + '" y2="' + Y(top * f) + '"></line>';
        });
        series.forEach(function (s) {
            var d = '', pen = false;
            s.points.forEach(function (p) {
                if (p[1] == null) { pen = false; return; }
                d += (pen ? 'L' : 'M') + X(p[0]).toFixed(1) + ' ' + Y(p[1]).toFixed(1) + ' ';
                pen = true;
            });
            if (d) svg += '<path class="rt-chart__line ' + s.cls + '" d="' + d + '"></path>';
        });
        svg += '<line class="rt-chart__cross" x1="0" x2="0" y1="' + padT + '" y2="' + (H - padB) + '" hidden></line>';
        series.forEach(function (s) { svg += '<circle class="rt-chart__dot ' + s.cls + '" r="4" cx="0" cy="0" hidden></circle>'; });
        svg += '</svg>';
        var html = '<div class="rt-chart__plot" data-from="' + from + '" data-to="' + to + '">' + svg
            + '<span class="rt-chart__y" style="top:' + (Y(top) / H * 100) + '%">' + esc(fmt(top)) + '</span>'
            + '<span class="rt-chart__y" style="top:' + (Y(top / 2) / H * 100) + '%">' + esc(fmt(top / 2)) + '</span>'
            + '<span class="rt-chart__y" style="top:' + (Y(0) / H * 100) + '%">' + esc(fmt(0)) + '</span>'
            + '<span class="rt-chart__x rt-chart__x--start" style="left:' + (padL / W * 100) + '%">' + esc(stamp(from, long)) + '</span>'
            + '<span class="rt-chart__x rt-chart__x--end">' + esc(stamp(to, long)) + '</span>'
            + (vals.length ? '' : '<span class="rt-chart__wait">' + esc(empty || t.noHistory) + '</span>')
            + '<div class="rt-chart__tip" hidden></div></div>';
        return { html: html, wire: function (plot) {
            if (!vals.length) return;
            var el = plot.querySelector('svg'), cross = el.querySelector('.rt-chart__cross'), dots = el.querySelectorAll('.rt-chart__dot'), tip = plot.querySelector('.rt-chart__tip');
            var times = series[0].points.map(function (p) { return p[0]; });
            plot.addEventListener('pointermove', function (ev) {
                var r = el.getBoundingClientRect();
                var at = from + (((ev.clientX - r.left) / r.width) * W - padL) / (W - padL - padR) * (to - from);
                var best = 0;
                times.forEach(function (tm, i) { if (Math.abs(tm - at) < Math.abs(times[best] - at)) best = i; });
                var tm = times[best], x = X(tm), rows = '';
                cross.setAttribute('x1', x); cross.setAttribute('x2', x); cross.removeAttribute('hidden');
                series.forEach(function (s, i) {
                    var p = s.points[best];
                    if (!p || p[1] == null) { dots[i].setAttribute('hidden', ''); return; }
                    dots[i].setAttribute('cx', x); dots[i].setAttribute('cy', Y(p[1])); dots[i].removeAttribute('hidden');
                    rows += '<span><i class="rt-key ' + s.cls + '"></i>' + esc(s.label) + ' <b>' + esc(fmt(p[1])) + '</b></span>';
                });
                tip.innerHTML = '<small>' + esc(stamp(tm, true)) + '</small>' + rows;
                tip.hidden = false;
                var left = x / W * r.width, flip = left > r.width / 2;
                tip.style.left = (flip ? left - 10 : left + 10) + 'px';
                tip.style.transform = flip ? 'translateX(-100%)' : 'none';
            });
            plot.addEventListener('pointerleave', function () {
                cross.setAttribute('hidden', ''); tip.hidden = true;
                dots.forEach(function (d) { d.setAttribute('hidden', ''); });
            });
        } };
    }
    function legend(series) {
        return series.map(function (s) { return '<span class="rt-legend__item"><i class="rt-key ' + s.cls + '"></i>' + esc(s.label) + '</span>'; }).join('');
    }
    function loadHistory(id, pane, range) {
        range = range || pane.getAttribute('data-range') || '24h';
        pane.setAttribute('data-range', range);
        var seq = pane._hseq = (pane._hseq || 0) + 1; // only the latest range asked for is drawn
        return request('history', id + '?range=' + encodeURIComponent(range)).then(function (d) {
            if (seq !== pane._hseq) return;
            var picker = '<span class="rt-ranges" role="group">' + ['24h', '7d', '30d'].map(function (r) {
                return '<button type="button" class="ls-btn ls-btn--sm' + (r === range ? ' is-on' : '') + '" data-rt-range="' + r + '" aria-pressed="' + (r === range) + '">' + r + '</button>';
            }).join('') + '</span>';
            if (!d.ok) { pane.innerHTML = head(t.history, picker) + fail(d); return; }
            var long = true; // the ends of even a 24-hour axis are on different days
            var pts = d.points;
            var charts = [
                { title: t.cpuMem, series: [{ label: t.cpu, cls: 'rt-s1', points: pts.map(function (p) { return [p.at, p.cpu]; }) },
                    { label: t.memory, cls: 'rt-s2', points: pts.map(function (p) { return [p.at, p.mem]; }) }], fmt: function (v) { return Math.round(v) + '%'; }, max: 100 },
                { title: t.usersOnline, series: [{ label: t.usersOnline, cls: 'rt-s1', points: pts.map(function (p) { return [p.at, p.users]; }) }], fmt: function (v) { return String(Math.round(v)); }, max: null },
                { title: t.uplink, series: [{ label: t.dl, cls: 'rt-s1', points: pts.map(function (p) { return [p.at, p.down]; }) },
                    { label: t.ul, cls: 'rt-s2', points: pts.map(function (p) { return [p.at, p.up]; }) }], fmt: bps, max: null }
            ];
            // online and offline along the period: green or red only where there are readings, neutral where there are none.
            // Each point stands for one stretch of time, as wide as the server's averaging step.
            var span = d.to - d.from, strip = '';
            var step = Math.max(300, Math.ceil(span / 300)), w = Math.max(0.15, step / span * 100);
            pts.forEach(function (p) {
                strip += '<i class="' + (p.online ? 'is-on' : 'is-off') + '" style="left:' + ((p.at - step / 2 - d.from) / span * 100).toFixed(3) + '%;width:' + w.toFixed(3) + '%"></i>';
            });
            var html = head(t.history, picker)
                + (d.collecting ? '' : '<p class="rt-err rt-err--block">' + esc(t.notCollecting) + '</p>')
                + '<dl class="rt-facts"><div><dt>' + esc(t.uptimePct) + '</dt><dd>' + (d.uptime == null ? '&mdash;' : esc(d.uptime) + '%') + '</dd></div>'
                + '<div><dt>' + esc(t.samples) + '</dt><dd>' + esc(d.samples) + '</dd></div></dl>'
                + '<div class="rt-strip" title="' + esc(t.uptimePct) + '">' + strip + '</div><div class="rt-charts">';
            var wires = [];
            charts.forEach(function (c, i) {
                // with readings but none for this graph (no uplink found, users not countable) it says so, rather than asking
                // for history to be turned on
                var ch = lineChart(c.series, c.fmt, c.max, d.from, d.to, long, pts.length ? t.noMetric : '');
                wires.push(ch.wire);
                html += '<figure class="rt-chart" data-rt-hchart="' + i + '"><figcaption class="rt-chart__head"><b>' + esc(c.title) + '</b><span class="rt-legend">'
                    + (c.series.length > 1 ? legend(c.series) : '') + '</span></figcaption><div class="rt-chart__box">' + ch.html + '</div></figure>';
            });
            html += '</div><h4 class="rt-mini__title">' + esc(t.events) + '</h4>';
            if (!d.events.length) {
                html += '<p class="ls-dim">' + esc(t.noEvents) + '</p>';
            } else {
                html += '<ul class="rt-checks">';
                d.events.forEach(function (e) {
                    var bad = /offline|high/.test(e.type), good = /online|_ok/.test(e.type);
                    html += '<li class="rt-check rt-check--' + (bad ? 'bad' : good ? 'ok' : 'warn') + '"><i class="fa ' + (bad ? 'fa-times-circle' : good ? 'fa-check-circle' : 'fa-refresh') + '" aria-hidden="true"></i><div><b>'
                        + esc(t['ev_' + e.type] || e.type) + (e.detail ? ' <span class="ls-dim">' + esc(e.detail) + '</span>' : '') + '</b><small>' + esc(stamp(e.at, true)) + '</small></div></li>';
                });
                html += '</ul>';
            }
            pane.innerHTML = html;
            pane.querySelectorAll('[data-rt-hchart]').forEach(function (fig) {
                wires[+fig.getAttribute('data-rt-hchart')](fig.querySelector('.rt-chart__plot'));
            });
        });
    }

    var loaders = { users: loadUsers, ifaces: loadIfaces, check: loadCheck, updates: loadUpdates, history: loadHistory };

    function load(id, tab, extra) {
        var pane = document.querySelector('[data-rt-tabs="' + id + '"]') && document.querySelector('#rt-detail-' + id + ' [data-rt-pane="' + tab + '"]');
        if (!pane || !loaders[tab]) return;
        pane.setAttribute('data-loaded', '1');
        pane._lv = null; // the list being replaced: an answer for it (a disconnect) no longer draws into the pane
        pane.innerHTML = '<span class="rt-skel rt-skel--wide" aria-label="' + esc(t.loading) + '"></span>';
        loaders[tab](id, pane, extra);
    }
    function show(id, tab) {
        var row = document.getElementById('rt-detail-' + id);
        if (!row) return;
        row.querySelectorAll('[data-rt-tab]').forEach(function (b) {
            var on = b.getAttribute('data-rt-tab') === tab;
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
            b.tabIndex = on ? 0 : -1; // one tab stop for the tab list: the selected tab
        });
        row.querySelectorAll('[data-rt-pane]').forEach(function (p) { p.hidden = p.getAttribute('data-rt-pane') !== tab; });
        var pane = row.querySelector('[data-rt-pane="' + tab + '"]');
        if (pane && !pane.getAttribute('data-loaded')) load(id, tab);
        // the live graphs read only while their tab is shown
        document.dispatchEvent(new CustomEvent('rt:tab', { detail: { id: String(id), tab: tab } }));
    }

    // the tab list from the keyboard: left and right arrows (and Home, End) move between tabs and open the one reached; Enter and
    // Space work as on any button
    document.addEventListener('keydown', function (e) {
        var tabBtn = e.target.closest && e.target.closest('[data-rt-tab]');
        if (!tabBtn || ['ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(e.key) === -1) return;
        var list = Array.prototype.slice.call(tabBtn.closest('[data-rt-tabs]').querySelectorAll('[data-rt-tab]'));
        var i = list.indexOf(tabBtn);
        var to = e.key === 'Home' ? 0 : e.key === 'End' ? list.length - 1 : (i + (e.key === 'ArrowRight' ? 1 : -1) + list.length) % list.length;
        e.preventDefault();
        list[to].focus();
        show(tabBtn.closest('[data-rt-tabs]').getAttribute('data-rt-tabs'), list[to].getAttribute('data-rt-tab'));
    });

    document.addEventListener('click', function (e) {
        var more = e.target.closest('[data-rt-more]');
        if (more) {
            var id = more.getAttribute('data-rt-more');
            // the row is opened by the live-health script (or the list's own script); once open, show its first tab
            setTimeout(function () {
                var row = document.getElementById('rt-detail-' + id);
                if (row && !row.hidden && !row.querySelector('.rt-tab.is-on')) {
                    // the Live tab needs the live-health script: without it, the first of the others
                    var first = row.querySelector(window.rtHealthReady ? '[data-rt-tab]' : '[data-rt-tab]:not([data-rt-tab="live"])');
                    if (first) show(id, first.getAttribute('data-rt-tab'));
                }
            }, 0);
            return;
        }
        var tab = e.target.closest('[data-rt-tab]');
        if (tab) {
            show(tab.closest('[data-rt-tabs]').getAttribute('data-rt-tabs'), tab.getAttribute('data-rt-tab'));
            return;
        }
        var lf = e.target.closest('[data-rt-f]');
        if (lf) {
            var lp = lf.closest('[data-rt-pane]');
            lp._lv.f = lf.getAttribute('data-rt-f');
            lp._lv.page = 0;
            lp.querySelectorAll('[data-rt-f]').forEach(function (b) {
                var on = b === lf;
                b.classList.toggle('is-on', on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            listRender(lp);
            return;
        }
        var pg = e.target.closest('[data-rt-page]');
        if (pg) {
            var pp = pg.closest('[data-rt-pane]');
            pp._lv.page += +pg.getAttribute('data-rt-page');
            listRender(pp);
            return;
        }
        var reload = e.target.closest('[data-rt-reload]');
        if (reload) {
            var pane = reload.closest('[data-rt-pane]');
            if (pane._busy) return;
            load(pane.closest('.rt-detail').id.replace('rt-detail-', ''), pane.getAttribute('data-rt-pane'));
            return;
        }
        var rng = e.target.closest('[data-rt-range]');
        if (rng) {
            var hp = rng.closest('[data-rt-pane]');
            loadHistory(hp.closest('.rt-detail').id.replace('rt-detail-', ''), hp, rng.getAttribute('data-rt-range'));
            return;
        }
        var upd = e.target.closest('[data-rt-checkupd]');
        if (upd) {
            upd.disabled = true;
            upd.querySelector('i').className = 'fa fa-spinner fa-spin';
            var pane2 = upd.closest('[data-rt-pane]');
            loadUpdates(pane2.closest('.rt-detail').id.replace('rt-detail-', ''), pane2, true);
            return;
        }
        var drop = e.target.closest('[data-rt-drop]');
        if (drop) {
            var rid = drop.closest('.rt-detail').id.replace('rt-detail-', '');
            // which session, in which list: taken now, as paging, searching or Refresh can redraw the row while the request is
            // out. Its state (waiting, failed) is kept on the row itself, so every redraw shows it and it cannot be sent twice.
            var dPane = drop.closest('[data-rt-pane]'), dModel = dPane._lv;
            var dKind = drop.getAttribute('data-rt-drop'), dSid = drop.getAttribute('data-sid'), dUser = drop.getAttribute('data-user');
            var dCfg = dModel ? dModel.opts.cfg || '' : '', dKey = dropKey(dCfg, dKind, dSid);
            var find = function (model) { // the row for this session in a list read with the same router settings
                return model && (model.opts.cfg || '') === dCfg ? model.opts.rows.filter(function (r) { return r.sid === dSid && r.kind === dKind; })[0] : null;
            };
            var dRow = find(dModel);
            if (!dRow || dRow.pending || dRow.gone || (dPane._drops && dPane._drops[dKey])) return;
            ask(drop, dUser + ': ' + t.dropAsk, function () {
                dPane._drops = dPane._drops || {};
                if (dPane._drops[dKey]) return;
                dPane._drops[dKey] = 'pending';
                dRow.pending = true;
                dRow.err = null;
                if (dPane._lv === dModel) listRender(dPane);
                request('drop', rid, { kind: dKind, sid: dSid, user: dUser, cfg: dCfg }).then(function (d) {
                    if (d.ok) dPane._drops[dKey] = 'gone'; else delete dPane._drops[dKey];
                    // the answer goes to the row it was sent from and to the same session in the list shown now (a Refresh may
                    // have replaced it); a list from other router settings is left alone
                    var cur = find(dPane._lv);
                    [dRow, cur].forEach(function (r) {
                        if (!r) return;
                        r.pending = false;
                        if (d.ok) r.gone = true; else r.err = d.error || t.fail;
                    });
                    if (cur) listRender(dPane);
                });
            });
            return;
        }
    });
})();
