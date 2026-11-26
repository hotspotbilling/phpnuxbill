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

    var esc = window.RtChart.esc; // the chart pieces shared with the Live graphs (routers-chart.js)
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
        // a deadline for the whole request, reading the answer included, raced against it: it holds where requests cannot be
        // cancelled too, and the one that is late is cancelled where it can be. The tab says to try again (Refresh).
        var ctl = window.AbortController ? new AbortController() : null;
        var timer;
        var deadline = new Promise(function (resolve) {
            timer = setTimeout(function () { if (ctl) ctl.abort(); resolve({ ok: false, error: t.fail }); }, LIMIT);
        });
        var opts = { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' }, signal: ctl ? ctl.signal : undefined };
        if (post) {
            var body = new FormData();
            body.append('csrf_token', cfg.csrf);
            Object.keys(post).forEach(function (k) { body.append(k, post[k]); });
            opts.method = 'POST';
            opts.body = body;
        }
        return Promise.race([fetch(url(action, id), opts).then(function (r) { return r.json(); }), deadline])
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
    // a session as listed: its ID with the user, address and MAC / caller ID, as the router can give an ended session's ID to
    // another one (the server checks the same before it disconnects), so an answer about one never marks its replacement
    function dropKey(cfg, kind, sid, user, address, mac) {
        var v = function (x) { return x == null ? '' : String(x); };
        return (cfg || '') + ' ' + JSON.stringify([v(kind), v(sid), v(user), v(address), v(mac)]);
    }
    // and the same session, not a new one the router gave the same ID, user, address and MAC: it began when the listed one did
    // (to a few seconds: each list works the start out from an uptime read at its own moment). Unknown on either side matches
    function sameStart(a, b) {
        if (a == null || b == null || a === '' || b === '') return true;
        return Math.abs(Number(a) - Number(b)) <= 5;
    }
    // both starts known and equal: only then is a disconnect result carried over to a session listed later
    function knownSame(a, b) {
        return a != null && b != null && a !== '' && b !== '' && sameStart(a, b);
    }
    function loadUsers(id, pane) {
        var readStart = Date.now(); // a read begun before a disconnect was answered cannot show that session gone
        return request('sessions', id).then(function (d) {
            if (!d.ok) { pane.innerHTML = head(t.user) + fail(d); return; }
            // a list that was not read can come back missing (or null): no rows from it, and d.read says it was not read
            // (PHP writes an array whose keys have gaps, after filtering, as an object: its values are the sessions)
            var toList = function (v) { return Array.isArray(v) ? v : (v && typeof v === 'object' ? Object.keys(v).map(function (k) { return v[k]; }) : []); };
            var hs = toList(d.hotspot), pp = toList(d.pppoe);
            var rows = hs.map(function (r) { r.kind = 'hotspot'; return r; }).concat(pp.map(function (r) { r.kind = 'pppoe'; return r; }));
            // a disconnect sent from here (still on its way, or done) stays shown on its row, even in a list read from the router
            // before it went through; kept per router settings, as a session ID only means something on the router it came from
            // (a failed one keeps its message until it is tried again; one whose session is no longer listed is said above the list)
            var orphans = [];
            // a read of both lists that listed every session: a session missing from it is confirmed gone
            var complete = !!(d.read ? d.read.hotspot && d.read.pppoe : true) && hs.length + pp.length >= (d.total_hotspot || hs.length) + (d.total_pppoe || pp.length);
            Object.keys(pane._drops || {}).forEach(function (k) {
                var s = pane._drops[k];
                var same = rows.filter(function (x) {
                    return dropKey(d.cfg, x.kind, x.sid, x.user, x.address, x.mac) === k;
                });
                var r = same.filter(function (x) { return knownSame(x.started, (pane._dropStart || {})[k]); })[0];
                if (!r) {
                    // the same session by key but with no start on one side: the identity cannot be told, and the disconnect is not
                    // sent again from here (busy() refuses it), so the row says so instead of offering an action that does nothing.
                    // A row whose start is known and different is another session, and stays actionable
                    var unsure = same.filter(function (x) { return sameStart(x.started, (pane._dropStart || {})[k]); })[0];
                    if (unsure && (s === 'pending' || s === 'gone')) {
                        unsure.err = t.dropRefresh;
                        r = null;
                    }
                }
                if (r) {
                    if (s === 'gone') r.gone = true; else if (s === 'pending') r.pending = true; else if (s && s.err) r.err = s.err;
                } else if (s && s.err && k.indexOf((d.cfg || '') + ' ') === 0) {
                    orphans.push(s.user + ': ' + s.err);
                } else if (s === 'gone' && complete && !same.length && readStart >= ((pane._dropAt || {})[k] || 0) && k.indexOf((d.cfg || '') + ' ') === 0) {
                    // the disconnected session has left the list: forgotten, so a later session that is given the same ID (the
                    // router numbers them again after a restart) is not shown as disconnected
                    delete pane._drops[k];
                    if (pane._dropStart) delete pane._dropStart[k];
                }
            });
            var curl = d.customer_url;
            // which lists were read: neither is no list at all (not "nobody online"), one of them is a count of that one only
            var rd = d.read || { hotspot: true, pppoe: true };
            if (!rd.hotspot && !rd.pppoe) {
                pane.innerHTML = head(t.user) + fail({ error: t.readErr + ': ' + (d.read_errors || []).join('; ') });
                return;
            }
            var partial = !rd.hotspot || !rd.pppoe;
            var total = (d.total_hotspot || hs.length) + (d.total_pppoe || pp.length);
            listView(pane, total + ' ' + t.online + (partial ? ' (' + t.partial + ')' : ''), {
                rows: rows,
                cfg: d.cfg, // which router settings the list was read with: a disconnect is refused once they change
                capped: rows.length < total ? t.capped.replace('%s', rows.length) : '',
                // a menu the router refused (no permission) is said, so an empty list is not taken for nobody online
                notice: [(d.read_errors || []).length ? t.readErr + ': ' + d.read_errors.join('; ') : ''].concat(orphans).filter(Boolean).join(' · '),
                empty: (rd.hotspot && rd.pppoe) ? t.none : t.noneRead,
                cols: [t.user, t.type, t.address, t.mac, t.uptime, t.data, ''],
                filters: [{ key: 'all', label: t.all, test: function () { return true; } },
                    { key: 'hotspot', label: 'Hotspot ' + hs.length, test: function (r) { return r.kind === 'hotspot'; } },
                    { key: 'pppoe', label: 'PPPoE ' + pp.length, test: function (r) { return r.kind === 'pppoe'; } }],
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
                            + '" data-address="' + esc(r.address) + '" data-mac="' + esc(r.mac) + '" data-started="' + esc(r.started == null ? '' : r.started) + '"' + (r.pending || r.err === t.dropRefresh ? ' disabled' : '') + '><i class="fa ' + (r.pending ? 'fa-spinner fa-spin' : 'fa-sign-out') + '" aria-hidden="true"></i> ' + esc(t.drop) + '</button>'
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
            // the button that was pressed is gone with the old page: the focus goes to the new one unless it was moved meanwhile
            var af = document.activeElement;
            if (check && (!af || af === document.body || af === pane)) {
                var nb = pane.querySelector('[data-rt-checkupd]') || pane.querySelector('[data-rt-reload]:not([disabled])');
                if (nb) nb.focus();
            }
        });
    }

    /* ---------- history (readings kept by the cron) ---------- */
    var bps = window.RtChart.bps;
    function stamp(at, long) {
        var d = new Date(at * 1000);
        var hm = ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
        return long ? d.toLocaleDateString(undefined, { day: 'numeric', month: 'short' }) + ' ' + hm : hm;
    }
    var niceMax = window.RtChart.niceMax;
    // one y axis, 2px lines, a crosshair and tooltip; series: [{ label, cls, points: [[at, v]] }]; gaps (null) break the line
    // empty: the text shown when no series has a value; title: the chart's name, for the keyboard and screen readers
    function lineChart(series, fmt, max, from, to, long, empty, title) {
        var W = 600, H = 150, padL = 56, padR = 12, padT = 10, padB = 22;
        var vals = [];
        series.forEach(function (s) { s.points.forEach(function (p) { if (p[1] != null) vals.push(p[1]); }); });
        var top = max != null ? max : niceMax(Math.max.apply(null, vals.concat([0])) * 1.1);
        function X(at) { return padL + (at - from) / (to - from) * (W - padL - padR); }
        function Y(v) { return padT + (1 - Math.min(v, top) / top) * (H - padT - padB); }
        var svg = window.RtChart.svg(series, { W: W, H: H, padL: padL, padR: padR, padT: padT, padB: padB, top: top, X: X, Y: Y });
        // with readings, the plot takes the keyboard focus: the arrow keys move the crosshair from reading to reading, and each
        // one is also said in a hidden live region for screen readers (the drawing itself is hidden from them)
        var html = '<div class="rt-chart__plot" data-from="' + from + '" data-to="' + to + '"'
            + (vals.length ? ' tabindex="0" role="group" aria-label="' + esc((title ? title + '. ' : '') + t.chartKeys) + '"' : '') + '>' + svg
            + '<span class="rt-chart__y" style="top:' + (Y(top) / H * 100) + '%">' + esc(fmt(top)) + '</span>'
            + '<span class="rt-chart__y" style="top:' + (Y(top / 2) / H * 100) + '%">' + esc(fmt(top / 2)) + '</span>'
            + '<span class="rt-chart__y" style="top:' + (Y(0) / H * 100) + '%">' + esc(fmt(0)) + '</span>'
            + '<span class="rt-chart__x rt-chart__x--start" style="left:' + (padL / W * 100) + '%">' + esc(stamp(from, long)) + '</span>'
            + '<span class="rt-chart__x rt-chart__x--end">' + esc(stamp(to, long)) + '</span>'
            + (vals.length ? '' : '<span class="rt-chart__wait">' + esc(empty || t.noHistory) + '</span>')
            + '<div class="rt-chart__tip" hidden></div><span class="sr-only" aria-live="polite" data-rt-said></span></div>';
        return { html: html, wire: function (plot) {
            if (!vals.length) return;
            var el = plot.querySelector('svg'), cross = el.querySelector('.rt-chart__cross'), dots = el.querySelectorAll('.rt-chart__dot'), tip = plot.querySelector('.rt-chart__tip');
            var times = series[0].points.map(function (p) { return p[0]; });
            var said = plot.querySelector('[data-rt-said]');
            var at0 = null; // the reading shown, by its place in times
            // the readings that have a value in some series: the keys step through these only
            var withValue = times.map(function (tm, i) { return i; }).filter(function (i) {
                return series.some(function (s) { return s.points[i] && s.points[i][1] != null; });
            });
            function showAt(best, speak) {
                var r = el.getBoundingClientRect();
                at0 = best;
                var tm = times[best], x = X(tm), rows = '', words = [];
                cross.setAttribute('x1', x); cross.setAttribute('x2', x); cross.removeAttribute('hidden');
                series.forEach(function (s, i) {
                    var p = s.points[best];
                    if (!p || p[1] == null) { dots[i].setAttribute('hidden', ''); return; }
                    dots[i].setAttribute('cx', x); dots[i].setAttribute('cy', Y(p[1])); dots[i].removeAttribute('hidden');
                    rows += '<span><i class="rt-key ' + s.cls + '"></i>' + esc(s.label) + ' <b>' + esc(fmt(p[1])) + '</b></span>';
                    words.push(s.label + ' ' + fmt(p[1]));
                });
                tip.innerHTML = '<small>' + esc(stamp(tm, true)) + '</small>' + rows;
                tip.hidden = false;
                var left = x / W * r.width, flip = left > r.width / 2;
                tip.style.left = (flip ? left - 10 : left + 10) + 'px';
                tip.style.transform = flip ? 'translateX(-100%)' : 'none';
                if (speak) said.textContent = stamp(tm, true) + ': ' + (words.length ? words.join(', ') : '—');
            }
            function hide() {
                cross.setAttribute('hidden', ''); tip.hidden = true;
                dots.forEach(function (d) { d.setAttribute('hidden', ''); });
            }
            plot.addEventListener('pointermove', function (ev) {
                var r = el.getBoundingClientRect();
                var at = from + (((ev.clientX - r.left) / r.width) * W - padL) / (W - padL - padR) * (to - from);
                var best = -1; // (a break in the lines is no reading: the pointer does not stop on it)
                times.forEach(function (tm, i) {
                    if (!series[0].points[i][2] && (best < 0 || Math.abs(tm - at) < Math.abs(times[best] - at))) best = i;
                });
                if (best >= 0) showAt(best, false);
            });
            plot.addEventListener('pointerleave', function () {
                if (document.activeElement !== plot) hide(); // the keyboard's reading stays while the plot has the focus
            });
            plot.addEventListener('keydown', function (e) {
                if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(e.key) === -1 || !withValue.length) return;
                e.preventDefault();
                if (at0 == null) { // the first key: the newest reading (the oldest for Home)
                    showAt(withValue[e.key === 'Home' ? 0 : withValue.length - 1], true);
                    return;
                }
                var k = withValue.indexOf(at0);
                if (k === -1) { // from a pointer position: the nearest reading that has a value
                    k = 0;
                    withValue.forEach(function (i, j) { if (Math.abs(i - at0) < Math.abs(withValue[k] - at0)) k = j; });
                }
                // (left and right follow the drawing, whose time runs left to right in every language)
                k = e.key === 'Home' ? 0 : e.key === 'End' ? withValue.length - 1 : Math.max(0, Math.min(withValue.length - 1, k + (e.key === 'ArrowRight' ? 1 : -1)));
                showAt(withValue[k], true);
            });
            plot.addEventListener('blur', function () { hide(); at0 = null; });
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
            // the range buttons are drawn again below: one that has the keyboard focus when the answer comes keeps it
            var fe = document.activeElement;
            var focused = fe && pane.contains(fe) && fe.getAttribute('data-rt-range');
            var refocus = function () {
                var b = focused && pane.querySelector('[data-rt-range="' + focused + '"]');
                if (b) b.focus();
            };
            var picker = '<span class="rt-ranges" role="group">' + ['24h', '7d', '30d'].map(function (r) {
                return '<button type="button" class="ls-btn ls-btn--sm' + (r === range ? ' is-on' : '') + '" data-rt-range="' + r + '" aria-pressed="' + (r === range) + '">' + r + '</button>';
            }).join('') + '</span>';
            if (!d.ok) { pane.innerHTML = head(t.history, picker) + fail(d); refocus(); return; }
            var long = true; // the ends of even a 24-hour axis are on different days
            var span = d.to - d.from;
            // how long a stretch each point stands for, as the server averaged them (an older server does not say: worked out the
            // same way)
            var step = +d.step > 0 ? +d.step : Math.max(300, Math.ceil(span / 300));
            // two points further apart than this have a stretch with no readings between them (the collection stopped): the lines
            // are broken there by a point with no value (marked, so the pointer does not stop on it). Two readings that follow on
            // (up to the server's GAP apart) can land in buckets up to GAP + one step apart, so those stay joined.
            var brk = (+d.gap > 0 ? +d.gap : 1200) + step;
            var pts = [];
            d.points.forEach(function (p, i) {
                var before = d.points[i - 1];
                if (before && p.at - before.at > brk) pts.push({ at: (before.at + p.at) / 2, hole: true });
                pts.push(p);
            });
            function line(k) {
                return pts.map(function (p) { return p.hole ? [p.at, null, true] : [p.at, p[k]]; });
            }
            var charts = [
                { title: t.cpuMem, series: [{ label: t.cpu, cls: 'rt-s1', points: line('cpu') },
                    { label: t.memory, cls: 'rt-s2', points: line('mem') }], fmt: function (v) { return Math.round(v) + '%'; }, max: 100 },
                { title: t.usersOnline, series: [{ label: t.usersOnline, cls: 'rt-s1', points: line('users') }], fmt: function (v) { return String(Math.round(v)); }, max: null },
                { title: t.uplink, series: [{ label: t.dl, cls: 'rt-s1', points: line('down') },
                    { label: t.ul, cls: 'rt-s2', points: line('up') }], fmt: bps, max: null }
            ];
            // online and offline along the period: green or red only where there are readings, neutral where there are none.
            // Each point stands for one stretch of time, as wide as the server's averaging step (cut to the period shown). An
            // offline stretch is also lower, and one with failed readings as well as answered ones is striped and shorter (not
            // told apart by colour alone); each one says its time and state, on hover and to screen readers.
            var strip = '';
            var onText = t.stripOn || t.up, offText = t.stripOff || t.down;
            d.points.forEach(function (p) {
                var a = Math.max(d.from, p.at - step / 2), b = Math.min(d.to, p.at + step / 2);
                if (b <= a) return;
                var n = +p.n || 0, on = +p.on || 0, mixed = p.online && n > 0 && on < n;
                var said = stamp(a, true) + ' – ' + stamp(b) + ': ' + (p.online ? onText : offText)
                    + (mixed ? ' (' + on + ' ' + t.of + ' ' + n + ' ' + t.samples + ')' : '');
                strip += '<i role="listitem" class="' + (mixed ? 'is-on is-mixed' : p.online ? 'is-on' : 'is-off') + '" title="' + esc(said) + '" style="left:' + ((a - d.from) / span * 100).toFixed(3) + '%;width:' + Math.max(0.15, (b - a) / span * 100).toFixed(3) + '%">'
                    + '<span class="sr-only">' + esc(said) + '</span></i>';
            });
            // far fewer readings than a cron every 5 minutes gives (under a quarter): said, as the charts are then mostly gaps
            var expected = Math.round(span / 300), sparse = '';
            if (d.collecting && d.samples > 0 && expected > 0 && d.samples < expected / 4 && t.sparse) {
                sparse = '<p class="rt-err rt-err--block"><i class="fa fa-info-circle" aria-hidden="true"></i> '
                    + esc(t.sparse.replace('%1', d.samples).replace('%2', expected.toLocaleString())) + '</p>';
            }
            var html = head(t.history, picker)
                + (d.collecting ? '' : '<p class="rt-err rt-err--block">' + esc(t.notCollecting) + '</p>')
                + sparse
                + '<dl class="rt-facts"><div><dt>' + esc(t.uptimePct) + '</dt><dd>' + (d.uptime == null ? '&mdash;' : esc(d.uptime) + '%') + '</dd></div>'
                + '<div><dt>' + esc(t.samples) + '</dt><dd>' + esc(d.samples) + '</dd></div></dl>'
                + '<div class="rt-strip" role="list" title="' + esc(t.uptimePct) + '" aria-label="' + esc(t.uptimePct) + '">' + strip + '</div><div class="rt-charts">';
            var wires = [];
            charts.forEach(function (c, i) {
                // with readings but none for this graph (no uplink found, users not countable) it says so, rather than asking
                // for history to be turned on
                var ch = lineChart(c.series, c.fmt, c.max, d.from, d.to, long, pts.length ? t.noMetric : (d.collecting ? t.waitReadings : t.noHistory), c.title);
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
            refocus();
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
        // Refresh had the keyboard focus (Enter or Space): the new Refresh gets it back once the tab is drawn, unless the focus
        // has been put somewhere else meanwhile (replacing the old button leaves it on the page itself)
        var fe = document.activeElement, refocus = !!(fe && pane.contains(fe) && fe.hasAttribute('data-rt-reload'));
        var seq = pane._lseq = (pane._lseq || 0) + 1;
        pane.innerHTML = '<span class="rt-skel rt-skel--wide" aria-label="' + esc(t.loading) + '"></span>';
        var done = loaders[tab](id, pane, extra);
        if (refocus && done && done.then) done.then(function () {
            var now = document.activeElement;
            if (seq !== pane._lseq || (now && now !== document.body && now !== pane)) return;
            var b = pane.querySelector('[data-rt-reload]:not([disabled])');
            if (b) b.focus();
        });
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
        var list = Array.prototype.slice.call(tabBtn.closest('[data-rt-tabs]').querySelectorAll('[data-rt-tab]:not([disabled])'));
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
            // the session as listed: the server disconnects it only while its address and MAC / caller ID are still these
            var dAddr = drop.getAttribute('data-address') || '', dMac = drop.getAttribute('data-mac') || '';
            var dStarted = drop.getAttribute('data-started') || '';
            var dCfg = dModel ? dModel.opts.cfg || '' : '', dKey = dropKey(dCfg, dKind, dSid, dUser, dAddr, dMac);
            // a retry after a failed disconnect goes with the start of the session as it was first listed, not a later list's
            var dPrev = dPane._drops && dPane._drops[dKey];
            if (dPrev && dPrev.err) {
                var dOrig = (dPane._dropStart || {})[dKey] || '';
                if (dOrig !== '' && dStarted !== '' && !sameStart(dOrig, dStarted)) {
                    // the listed session began at another time: a replacement that got the same ID, not the one that failed.
                    // Its own start is used and the old failure does not stand in its way
                    delete dPane._drops[dKey];
                    delete dPane._dropStart[dKey];
                    dPrev = null;
                } else if (dOrig === '' && dStarted !== '') {
                    // the refreshed row has a known start: the old unknown-start failure is not inherited
                    delete dPane._drops[dKey];
                    if (dPane._dropStart) delete dPane._dropStart[dKey];
                    dPrev = null;
                    dOrig = dStarted;
                }
                if (dPrev && dOrig === '') {
                    // no start to tell this session from a replacement that got the same ID: not tried again from here
                    dPane._drops[dKey] = { err: t.dropRefresh, user: dUser };
                    var dRow0 = dModel && dModel.opts.rows.filter(function (r) {
                        return dropKey(dCfg, r.kind, r.sid, r.user, r.address, r.mac) === dKey;
                    })[0];
                    if (dRow0) { dRow0.err = t.dropRefresh; listRender(dPane); }
                    return;
                }
                if (dPrev) dStarted = dOrig;
            }
            var find = function (model) { // the row for this session (as listed: see dropKey()) in a list read with the same router settings
                return model && (model.opts.cfg || '') === dCfg ? model.opts.rows.filter(function (r) {
                    return dropKey(dCfg, r.kind, r.sid, r.user, r.address, r.mac) === dKey && sameStart(r.started, dStarted);
                })[0] : null;
            };
            // the row an answer is carried to afterwards: the dispatched one, or a refreshed one only when both starts are known
            // and equal (a refreshed row with no start is not shown as the session that was disconnected)
            var findStrict = function (model) {
                return model && (model.opts.cfg || '') === dCfg ? model.opts.rows.filter(function (r) {
                    return dropKey(dCfg, r.kind, r.sid, r.user, r.address, r.mac) === dKey && (r === dRow || knownSame(r.started, dStarted));
                })[0] : null;
            };
            var dRow = find(dModel);
            var busy = function () { // waiting for an answer, or done; a failed one can be tried again
                var s = dPane._drops && dPane._drops[dKey];
                // only for this session: a new one with the same ID, user, address and MAC (it began later) can be disconnected
                return (s === 'pending' || s === 'gone') && sameStart((dPane._dropStart || {})[dKey], dStarted);
            };
            if (!dRow || dRow.pending || dRow.gone || busy()) return;
            ask(drop, dUser + ': ' + t.dropAsk, function () {
                dPane._drops = dPane._drops || {};
                if (busy()) return;
                dPane._drops[dKey] = 'pending'; // (replaces an earlier failure: trying again clears it)
                dPane._dropStart = dPane._dropStart || {};
                dPane._dropStart[dKey] = dStarted;
                // this request's mark: a disconnect sent since for a new session with the same key replaces it, and this one's
                // answer, if it comes later, no longer sets the state kept for that key
                var dReq = {};
                dPane._dropReq = dPane._dropReq || {};
                dPane._dropReq[dKey] = dReq;
                dRow.pending = true;
                dRow.err = null;
                // the Disconnect button is drawn again (waiting) and the one that had the keyboard is gone: the focus is parked
                // on the sessions pane (a stable target), and goes to the retry button if this fails and it has not moved since
                var fe = document.activeElement;
                var parked = false;
                if (dPane._lv === dModel) {
                    listRender(dPane);
                    if (!fe || fe === drop || fe === document.body || !document.contains(fe)) {
                        dPane.focus({ preventScroll: true });
                        parked = document.activeElement === dPane;
                    }
                }
                request('drop', rid, { kind: dKind, sid: dSid, user: dUser, address: dAddr, mac: dMac, started: dStarted, cfg: dCfg }).then(function (d) {
                    // kept on the pane, not only on the row: a Refresh meanwhile replaces the rows, and the result must survive it
                    // (a disconnect of a newer session with the same key has been sent since: the state, the rows and any notice
                    // are that one's to set, so this answer changes nothing)
                    if (dPane._dropReq[dKey] !== dReq) return;
                    dPane._drops[dKey] = d.ok ? 'gone' : { err: d.error || t.fail, user: dUser };
                    (dPane._dropAt = dPane._dropAt || {})[dKey] = Date.now(); // when the answer came: only a read begun after it shows absence
                    // the answer goes to the row it was sent from and to the same session in the list shown now (a Refresh may
                    // have replaced it); a list from other router settings is left alone
                    var cur = findStrict(dPane._lv);
                    [dRow, cur].forEach(function (r) {
                        if (!r) return;
                        r.pending = false;
                        if (d.ok) r.gone = true; else r.err = d.error || t.fail;
                    });
                    if (cur) {
                        // a control that had the keyboard (this session's retry, or another session's Disconnect) keeps it
                        var fe2 = document.activeElement, keep = fe2 && fe2 !== dPane && dPane.contains(fe2) && fe2.hasAttribute('data-rt-drop') ? fe2 : null;
                        var kk = keep ? ['data-rt-drop', 'data-sid', 'data-user', 'data-address', 'data-mac', 'data-started'].map(function (a) { return keep.getAttribute(a) || ''; }) : null;
                        listRender(dPane);
                        if (kk) {
                            var again = Array.prototype.filter.call(dPane.querySelectorAll('[data-rt-drop]'), function (b) {
                                return !b.disabled && ['data-rt-drop', 'data-sid', 'data-user', 'data-address', 'data-mac', 'data-started'].every(function (a, i) { return (b.getAttribute(a) || '') === kk[i]; });
                            })[0];
                            if (again) again.focus();
                        }
                        if (!d.ok && parked && document.activeElement === dPane) {
                            var retry = Array.prototype.filter.call(dPane.querySelectorAll('[data-rt-drop]'), function (b) {
                                return b.getAttribute('data-rt-drop') === dKind && b.getAttribute('data-sid') === dSid && b.getAttribute('data-user') === dUser
                                    && (b.getAttribute('data-address') || '') === dAddr && (b.getAttribute('data-mac') || '') === dMac
                                    && sameStart(b.getAttribute('data-started') || '', dStarted);
                            })[0];
                            if (retry) retry.focus();
                        }
                    } else if (!d.ok && dPane._lv && (dPane._lv.opts.cfg || '') === dCfg && dPane.querySelector('[data-rt-list]')) {
                        // the session is not in the list shown now (read with the same router settings): the failure is said above
                        // it. A list from other settings is about another router, and is left alone
                        dPane.querySelector('[data-rt-list]').insertAdjacentHTML('beforebegin', '<p class="rt-err rt-err--block">' + esc(dUser + ': ' + (d.error || t.fail)) + '</p>');
                    }
                });
            });
            return;
        }
    });
    // routers-health.js and the list check this: without it (it failed to load), the details row opens on its Live tab only
    window.rtToolsReady = true;
})();
