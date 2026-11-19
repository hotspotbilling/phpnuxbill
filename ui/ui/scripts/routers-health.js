/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *
 *  Routers list: live health. Settings come from window.rtHealth (url, t = texts), set by admin/routers/list.tpl.
 *
 *  The list asks each router once after the page has loaded, three at a time (the server keeps the answer for 60 seconds).
 *  An open details panel asks its router again every second (the server shares a reading for under a second, so several
 *  admins on one router share it) and draws the last 5 minutes: CPU and memory, and the traffic of one interface, worked out from the change
 *  in its byte counters between two readings. It pauses while the tab is hidden and after 10 minutes, so a panel left open
 *  does not keep a shared host busy.
 **/
(function () {
    'use strict';
    var cfg = window.rtHealth;
    if (!cfg) return;
    var t = cfg.t;
    var WINDOW = 300;        // seconds of history drawn
    var EVERY = 1000;        // ms between readings while a panel is open (the next is asked once the last has answered)
    var EVERY_DOWN = 30000;  // ms between tries while the router does not answer
    var MAX_LIVE = 600000;   // ms a panel keeps reading before it pauses itself
    var data = {};           // id => last answer
    var live = {};           // id => { cpu: [[at, v]], mem: [[at, v]], prev: {name: {rx, tx, at}}, rate: {name: [[at, down, up]]}, iface, timer, paused, started }
    var queue = [];
    var running = 0;
    var asked = {};          // id => number of the newest request sent
    var applied = {};        // id => number of the newest request whose answer is shown
    var LIMIT = 20000;       // ms a request (and reading its answer) may take before it counts as failed

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
    function bps(v) {
        if (v == null) return '';
        var u = ['bps', 'kbps', 'Mbps', 'Gbps'], i = 0;
        while (v >= 1000 && i < u.length - 1) { v /= 1000; i++; }
        return (v >= 100 || i === 0 ? Math.round(v) : v.toFixed(1)) + ' ' + u[i];
    }
    function pct(used, total) {
        return used != null && total ? Math.round(used / total * 100) : null;
    }
    function level(p) {
        return p == null ? '' : p >= 90 ? 'bad' : p >= 75 ? 'warn' : 'ok';
    }
    function clock(at) {
        var d = new Date(at * 1000);
        return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2) + ':' + ('0' + d.getSeconds()).slice(-2);
    }
    function meter(label, p, wide, extra) {
        if (p == null) return '';
        return '<span class="rt-meter' + (wide ? ' rt-meter--wide' : '') + '" data-level="' + level(p) + '">'
            + '<span class="rt-meter__label">' + esc(label) + '</span>'
            + '<span class="rt-meter__bar"><i style="width:' + Math.max(2, Math.min(100, p)) + '%"></i></span>'
            + '<b>' + p + '%</b>' + (extra ? '<small>' + esc(extra) + '</small>' : '') + '</span>';
    }
    function users(d) {
        if (d.hotspot == null && d.pppoe == null) return null;
        return (d.hotspot || 0) + (d.pppoe || 0);
    }
    function failText(d) {
        return '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' + esc(d.error || t.fail)
            + (d.hint ? '<small class="rt-hint">' + esc(d.hint) + '</small>' : '');
    }

    /* ---------- the row's Health cell ---------- */
    function cell(id, d) {
        var td = document.querySelector('tr[data-rt-id="' + id + '"] .rt-health');
        if (!td) return;
        if (!d.ok) {
            td.innerHTML = '<span class="rt-err" title="' + esc((d.error || t.fail) + (d.hint ? ' ' + d.hint : '')) + '"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' + esc(d.error || t.fail) + '</span>';
            return;
        }
        var n = users(d);
        td.innerHTML = '<span class="rt-cell">' + meter(t.cpu, d.cpu) + meter(t.ram, pct(d.mem_used, d.mem_total))
            + (n != null ? '<span class="rt-users" title="' + esc(t.users) + '"><i class="fa fa-users" aria-hidden="true"></i> ' + n + '</span>' : '') + '</span>';
    }

    /* ---------- history kept while a panel is open ---------- */
    function remember(id, d) {
        var L = live[id];
        if (!L || !d.ok || !d.at) return;
        var last = L.cpu.length ? L.cpu[L.cpu.length - 1][0] : 0;
        if (d.at <= last) return; // the same (cached) reading again
        var cut = d.at - WINDOW;
        function push(arr, row) {
            arr.push(row);
            while (arr.length && arr[0][0] < cut) arr.shift();
        }
        if (d.cpu != null) push(L.cpu, [d.at, d.cpu]);
        var m = pct(d.mem_used, d.mem_total);
        if (m != null) push(L.mem, [d.at, m]);
        // the interfaces running now: one that has gone (unplugged, disabled) leaves the picker; its graph history stays in
        // L.rate, so it is there again if the interface comes back
        if (d.ifaces) {
            var now = {};
            d.ifaces.forEach(function (i) { now[i.name] = true; });
            Object.keys(L.prev).forEach(function (n) { if (!now[n]) delete L.prev[n]; });
        }
        (d.ifaces || []).forEach(function (i) {
            var p = L.prev[i.name];
            if (p && d.at > p.at) {
                var s = d.at - p.at;
                var down = (i.rx - p.rx) * 8 / s, up = (i.tx - p.tx) * 8 / s;
                if (down >= 0 && up >= 0) { // a counter that went back (reset, reboot) gives no speed this time
                    if (!L.rate[i.name]) L.rate[i.name] = [];
                    push(L.rate[i.name], [d.at, down, up]);
                }
            }
            L.prev[i.name] = { rx: i.rx, tx: i.tx, at: d.at, type: i.type, total: i.rx + i.tx };
        });
        if (!L.iface || !L.prev[L.iface]) {
            // the busiest interface since the router started is usually the uplink
            var best = null;
            Object.keys(L.prev).forEach(function (n) {
                if (!best || L.prev[n].total > L.prev[best].total) best = n;
            });
            L.iface = saved(id) && L.prev[saved(id)] ? saved(id) : best;
        }
    }
    function saved(id, v) {
        try {
            if (v === undefined) return localStorage.getItem('rt-iface-' + id);
            localStorage.setItem('rt-iface-' + id, v);
        } catch (e) {
            return null;
        }
    }

    /* ---------- line chart: one y axis, 2px lines, crosshair and tooltip ---------- */
    function niceMax(v) {
        if (!v || v <= 0) return 1;
        var p = Math.pow(10, Math.floor(Math.log10(v)));
        var n = v / p;
        return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * p;
    }
    // series: [{ key, label, cls, points: [[at, v]] }], fmt: value -> text, max: fixed top (or null),
    // hover: { f } where the pointer is (0..1 across the chart), kept so the crosshair stays put when a new reading redraws it
    function chart(box, series, fmt, max, now, hover) {
        var W = 600, H = 150, padL = 56, padR = 12, padT = 10, padB = 22;
        var all = [];
        series.forEach(function (s) { s.points.forEach(function (p) { all.push(p[1]); }); });
        var top = max != null ? max : niceMax(Math.max.apply(null, all.concat([0])) * 1.1);
        var x0 = now - WINDOW;
        function X(at) { return padL + (at - x0) / WINDOW * (W - padL - padR); }
        function Y(v) { return padT + (1 - Math.min(v, top) / top) * (H - padT - padB); }
        var svg = '<svg viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="none" class="rt-chart__svg" aria-hidden="true">';
        [0, 0.5, 1].forEach(function (f) {
            var y = Y(top * f);
            svg += '<line class="rt-chart__grid" x1="' + padL + '" x2="' + (W - padR) + '" y1="' + y + '" y2="' + y + '"></line>';
        });
        series.forEach(function (s) {
            if (s.points.length < 2) return;
            var d = s.points.map(function (p, i) { return (i ? 'L' : 'M') + X(p[0]).toFixed(1) + ' ' + Y(p[1]).toFixed(1); }).join(' ');
            svg += '<path class="rt-chart__line ' + s.cls + '" d="' + d + '"></path>';
        });
        svg += '<line class="rt-chart__cross" x1="0" x2="0" y1="' + padT + '" y2="' + (H - padB) + '" hidden></line>';
        series.forEach(function (s) {
            svg += '<circle class="rt-chart__dot ' + s.cls + '" r="4" cx="0" cy="0" hidden></circle>';
        });
        svg += '</svg>';
        // axis text sits in HTML over the drawing, so it keeps its size when the drawing stretches
        var axis = '<span class="rt-chart__y" style="top:' + (Y(top) / H * 100) + '%">' + esc(fmt(top)) + '</span>'
            + '<span class="rt-chart__y" style="top:' + (Y(top / 2) / H * 100) + '%">' + esc(fmt(top / 2)) + '</span>'
            + '<span class="rt-chart__y" style="top:' + (Y(0) / H * 100) + '%">' + esc(fmt(0)) + '</span>'
            + '<span class="rt-chart__x rt-chart__x--start" style="left:' + (padL / W * 100) + '%">' + esc(t.minutes5) + '</span>'
            + '<span class="rt-chart__x rt-chart__x--end">' + esc(t.now) + '</span>';
        var empty = series.every(function (s) { return s.points.length < 2; });
        box.innerHTML = '<div class="rt-chart__plot">' + svg + axis
            + (empty ? '<span class="rt-chart__wait">' + esc(t.measuring) + '</span>' : '')
            + '<div class="rt-chart__tip" hidden></div></div>';
        if (empty) return;
        var plot = box.firstChild;
        var el = plot.querySelector('svg');
        var cross = el.querySelector('.rt-chart__cross');
        var dots = el.querySelectorAll('.rt-chart__dot');
        var tip = plot.querySelector('.rt-chart__tip');
        // the times to snap to: from the series with the most readings (one may be empty, CPU or memory alone)
        var times = series.reduce(function (a, s) { return s.points.length > a.points.length ? s : a; }).points.map(function (p) { return p[0]; });
        function move(ev) {
            var r = el.getBoundingClientRect();
            if (ev) hover.f = (ev.clientX - r.left) / r.width;
            var at = x0 + (hover.f * W - padL) / (W - padL - padR) * WINDOW;
            var best = 0;
            times.forEach(function (tm, i) { if (Math.abs(tm - at) < Math.abs(times[best] - at)) best = i; });
            var tm = times[best];
            if (tm == null) return;
            var x = X(tm);
            cross.setAttribute('x1', x); cross.setAttribute('x2', x); cross.removeAttribute('hidden');
            var rows = '';
            series.forEach(function (s, i) {
                var p = s.points.filter(function (q) { return q[0] === tm; })[0];
                if (!p) { dots[i].setAttribute('hidden', ''); return; }
                dots[i].setAttribute('cx', x); dots[i].setAttribute('cy', Y(p[1])); dots[i].removeAttribute('hidden');
                rows += '<span><i class="rt-key ' + s.cls + '"></i>' + esc(s.label) + ' <b>' + esc(fmt(p[1])) + '</b></span>';
            });
            tip.innerHTML = '<small>' + clock(tm) + '</small>' + rows;
            tip.hidden = false;
            // beside the crosshair, on the side with more room, so it never covers the lines at that moment
            var left = x / W * r.width;
            var flip = left > r.width / 2;
            tip.style.left = (flip ? left - 10 : left + 10) + 'px';
            tip.style.transform = flip ? 'translateX(-100%)' : 'none';
        }
        function leave() {
            hover.f = null;
            cross.setAttribute('hidden', '');
            tip.hidden = true;
            dots.forEach(function (d) { d.setAttribute('hidden', ''); });
        }
        plot.addEventListener('pointermove', move);
        plot.addEventListener('pointerleave', leave);
        if (hover.f != null) move(null);
    }
    function legend(series, values) {
        return series.map(function (s, i) {
            return '<span class="rt-legend__item"><i class="rt-key ' + s.cls + '"></i>' + esc(s.label)
                + (values[i] != null ? ' <b>' + esc(values[i]) + '</b>' : '') + '</span>';
        }).join('');
    }

    /* ---------- the details panel ---------- */
    function panel(id, d) {
        var box = document.querySelector('[data-rt-panel="' + id + '"]');
        if (!box) return;
        var L = live[id];
        if (!box.querySelector('.rt-panel__head')) {
            box.innerHTML = '<div class="rt-panel__head"><b class="rt-panel__name"></b>'
                + '<span class="rt-live" data-rt-state></span>'
                + '<span class="rt-panel__when"></span>'
                + '<button type="button" class="ls-btn ls-btn--sm" data-rt-toggle="' + id + '"></button></div>'
                + '<div class="rt-panel__body"></div>';
        }
        box.querySelector('.rt-panel__name').textContent = d.identity || '';
        box.querySelector('.rt-panel__when').textContent = d.ok && d.at ? t.updated + ' ' + clock(d.at) : '';
        var state = box.querySelector('[data-rt-state]');
        var btn = box.querySelector('[data-rt-toggle]');
        var on = L && !L.paused;
        state.className = 'rt-live' + (on ? ' is-on' : '');
        state.innerHTML = on ? '<i aria-hidden="true"></i> ' + esc(t.live) : esc(t.paused);
        btn.innerHTML = on ? '<i class="fa fa-pause" aria-hidden="true"></i> ' + esc(t.pause) : '<i class="fa fa-play" aria-hidden="true"></i> ' + esc(t.resume);
        var body = box.querySelector('.rt-panel__body');
        if (!d.ok) {
            body.innerHTML = '<p class="rt-err rt-err--block">' + failText(d) + '</p>';
            return;
        }
        if (!body.querySelector('.rt-facts')) {
            body.innerHTML = '<dl class="rt-facts"></dl><div class="rt-meters"></div>'
                + '<div class="rt-charts">'
                + '<figure class="rt-chart" data-rt-chart="load"><figcaption class="rt-chart__head"><b>' + esc(t.cpuMem) + '</b><span class="rt-legend"></span></figcaption><div class="rt-chart__box"></div></figure>'
                + '<figure class="rt-chart" data-rt-chart="traffic"><figcaption class="rt-chart__head"><b>' + esc(t.traffic) + '</b>'
                + '<select class="rt-chart__pick" aria-label="' + esc(t.iface) + '"></select><span class="rt-legend"></span></figcaption><div class="rt-chart__box"></div></figure>'
                + '</div><p class="rt-online"></p>';
            body.querySelector('.rt-chart__pick').addEventListener('change', function () {
                live[id].iface = this.value;
                saved(id, this.value);
                draw(id);
            });
        }
        var facts = [[t.board, d.board], [t.version, d.version], [t.uptime, d.uptime], [t.temp, d.temp != null ? d.temp + ' °C' : '']];
        body.querySelector('.rt-facts').innerHTML = facts.filter(function (f) { return f[1]; }).map(function (f) {
            return '<div><dt>' + esc(f[0]) + '</dt><dd>' + esc(f[1]) + '</dd></div>';
        }).join('');
        body.querySelector('.rt-meters').innerHTML =
            meter(t.cpu, d.cpu, true, d.cores ? d.cores + ' ' + (d.cores === 1 ? t.core : t.cores) : '')
            + meter(t.memory, pct(d.mem_used, d.mem_total), true, d.mem_total ? t.of + ' ' + size(d.mem_total) : '')
            + meter(t.disk, pct(d.disk_used, d.disk_total), true, d.disk_total ? t.of + ' ' + size(d.disk_total) : '');
        var n = users(d);
        body.querySelector('.rt-online').innerHTML = n == null ? '' : '<i class="fa fa-users" aria-hidden="true"></i> ' + esc(t.online) + ': <b>' + n + '</b>'
            + ' <span class="ls-dim">(' + [d.hotspot != null ? t.hotspot + ' ' + d.hotspot : '', d.pppoe != null ? t.pppoe + ' ' + d.pppoe : ''].filter(Boolean).join(', ') + ')</span>';
        draw(id);
    }
    function draw(id) {
        var L = live[id], d = data[id];
        var box = document.querySelector('[data-rt-panel="' + id + '"]');
        if (!L || !d || !d.ok || !box || !box.querySelector('.rt-charts')) return;
        var now = d.at || Date.now() / 1000;
        var load = box.querySelector('[data-rt-chart="load"]');
        var sLoad = [
            { label: t.cpu, cls: 'rt-s1', points: L.cpu },
            { label: t.memory, cls: 'rt-s2', points: L.mem }
        ];
        chart(load.querySelector('.rt-chart__box'), sLoad, function (v) { return Math.round(v) + '%'; }, 100, now, L.hover.load);
        load.querySelector('.rt-legend').innerHTML = legend(sLoad, [d.cpu != null ? d.cpu + '%' : null, pct(d.mem_used, d.mem_total) != null ? pct(d.mem_used, d.mem_total) + '%' : null]);

        var traffic = box.querySelector('[data-rt-chart="traffic"]');
        var pick = traffic.querySelector('.rt-chart__pick');
        var names = Object.keys(L.prev).sort(function (a, b) { return L.prev[b].total - L.prev[a].total; });
        if (pick.getAttribute('data-names') !== names.join('\n')) {
            pick.innerHTML = names.map(function (nm) {
                return '<option value="' + esc(nm) + '">' + esc(nm) + (L.prev[nm].type ? ' (' + esc(L.prev[nm].type) + ')' : '') + '</option>';
            }).join('');
            pick.setAttribute('data-names', names.join('\n'));
        }
        if (L.iface) pick.value = L.iface;
        traffic.hidden = !names.length;
        var rows = (L.iface && L.rate[L.iface]) || [];
        var sNet = [
            { label: t.down, cls: 'rt-s1', points: rows.map(function (r) { return [r[0], r[1]]; }) },
            { label: t.up, cls: 'rt-s2', points: rows.map(function (r) { return [r[0], r[2]]; }) }
        ];
        chart(traffic.querySelector('.rt-chart__box'), sNet, bps, null, now, L.hover.traffic);
        var lastRow = rows[rows.length - 1];
        traffic.querySelector('.rt-legend').innerHTML = legend(sNet, lastRow ? [bps(lastRow[1]), bps(lastRow[2])] : [null, null]);
    }

    /* ---------- asking the server ---------- */
    function ask(id, opts) {
        var q = [];
        if (opts.fresh) q.push('fresh=1');
        if (opts.live) q.push('live=1');
        var u = cfg.url + id + (q.length ? (cfg.url.indexOf('?') < 0 ? '?' : '&') + q.join('&') : '');
        var seq = asked[id] = (asked[id] || 0) + 1;
        // a deadline for the whole request, reading the answer included: a stuck one fails, so its queue place is freed and the
        // live readings carry on
        var ctl = window.AbortController ? new AbortController() : null;
        var timer = ctl ? setTimeout(function () { ctl.abort(); }, LIMIT) : null;
        return fetch(u, { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' }, signal: ctl ? ctl.signal : undefined })
            .then(function (r) { return r.json(); })
            .catch(function () { return { ok: false, error: t.fail }; })
            .then(function (d) {
                clearTimeout(timer);
                var cur = data[id];
                // answers can come back out of order (the list's first check, Check now, the live readings): one from a request
                // older than the answer already shown is dropped, failures too, and so is an older reading served from the store
                if (seq < (applied[id] || 0) || (d && d.ok && d.at && cur && cur.ok && cur.at && d.at < cur.at)) {
                    return cur || d;
                }
                applied[id] = seq;
                data[id] = d;
                remember(id, d);
                cell(id, d);
                if (live[id]) panel(id, d);
                return d;
            });
    }
    function fetchQueued(item) {
        running++;
        ask(item.id, { fresh: item.fresh }).finally(function () {
            running--;
            next();
        });
    }
    function next() {
        while (running < 3 && queue.length) fetchQueued(queue.shift());
    }

    // one reading chain per router: start() begins a new generation, and an answer from an older one (a request still on its
    // way when the panel was closed and opened again, or paused and resumed) schedules nothing
    function tick(id, gen) {
        var L = live[id];
        if (!L || L.paused || L.gen !== gen) return;
        if (Date.now() - L.started > MAX_LIVE) {
            pause(id);
            return;
        }
        if (document.hidden) { // nothing is read for a hidden tab; it carries on when the tab is shown
            L.timer = setTimeout(function () { tick(id, gen); }, EVERY);
            return;
        }
        ask(id, { live: true }).then(function (d) {
            // a router that does not answer is asked every 30 seconds only: each try can wait for the connection time limit
            var cur = live[id];
            if (cur && !cur.paused && cur.gen === gen) cur.timer = setTimeout(function () { tick(id, gen); }, d && d.ok ? EVERY : EVERY_DOWN);
        });
    }
    function start(id) {
        var L = live[id] || (live[id] = { cpu: [], mem: [], prev: {}, rate: {}, iface: null, gen: 0, hover: { load: { f: null }, traffic: { f: null } } });
        clearTimeout(L.timer);
        L.gen++;
        L.paused = false;
        L.started = Date.now();
        if (data[id]) panel(id, data[id]);
        tick(id, L.gen);
    }
    function pause(id) {
        var L = live[id];
        if (!L) return;
        clearTimeout(L.timer);
        L.paused = true;
        if (data[id]) panel(id, data[id]);
    }
    function stop(id) {
        if (live[id]) {
            clearTimeout(live[id].timer);
            live[id].paused = true;
        }
    }

    document.querySelectorAll('tr[data-rt-id]').forEach(function (tr) {
        queue.push({ id: tr.getAttribute('data-rt-id'), fresh: false });
    });
    next();

    // "Check now" for the ticked routers: asked again at once, not from the 60-second store
    window.rtHealthRefresh = function (ids) {
        ids.forEach(function (id) {
            var td = document.querySelector('tr[data-rt-id="' + id + '"] .rt-health');
            if (!td) return; // a disabled router is not asked
            td.innerHTML = '<span class="rt-skel"></span>';
            queue.push({ id: String(id), fresh: true });
        });
        next();
    };

    // the details row has tabs: the live readings run only while the Live tab is shown (routers-tools.js says which is)
    document.addEventListener('rt:tab', function (e) {
        var id = e.detail.id;
        var row = document.getElementById('rt-detail-' + id);
        if (!row || row.hidden || !document.querySelector('[data-rt-panel="' + id + '"]')) return;
        if (e.detail.tab === 'live') {
            if (!live[id] || live[id].paused) start(id);
        } else if (live[id] && !live[id].paused) {
            pause(id);
        }
    });

    document.addEventListener('click', function (e) {
        var more = e.target.closest('[data-rt-more]');
        if (more) {
            var id = more.getAttribute('data-rt-more');
            var row = document.getElementById('rt-detail-' + id);
            var open = row.hidden;
            row.hidden = !open;
            more.setAttribute('aria-expanded', open ? 'true' : 'false');
            more.classList.toggle('is-open', open);
            if (open) {
                var box = document.querySelector('[data-rt-panel="' + id + '"]');
                if (!box.innerHTML) box.innerHTML = '<span class="rt-skel rt-skel--wide"></span>';
                start(id);
            } else {
                stop(id);
            }
            return;
        }
        var toggle = e.target.closest('[data-rt-toggle]');
        if (toggle) {
            var tid = toggle.getAttribute('data-rt-toggle');
            if (live[tid] && !live[tid].paused) pause(tid); else start(tid);
        }
    });
})();
