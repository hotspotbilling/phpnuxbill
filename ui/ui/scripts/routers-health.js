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
    // per kind of request (the list's checks, the Live readings), id => number of the newest request sent / shown: kept apart, so
    // a Check now cannot make a Live reading on its way look out of date (and the other way round)
    var asked = { list: {}, live: {} };
    var applied = { list: {}, live: {} };
    // and across both kinds, id => number of the newest request sent / shown in the row's Health cell: the cell shows the answer
    // to the newest request of either kind, so an older list success cannot replace a newer Live failure (or the other way)
    var sentAll = {};
    var shownAll = {};
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
            + '<b>' + esc(p) + '%</b>' + (extra ? '<small>' + esc(extra) + '</small>' : '') + '</span>';
    }
    // a figure from the server as a number, or null when it is missing or not a number (never a string to add up)
    function num(v) {
        if (v === null || v === undefined || v === '') return null;
        v = Number(v);
        return isFinite(v) ? v : null;
    }
    function users(d) {
        var h = num(d.hotspot), p = num(d.pppoe);
        if (h === null && p === null) return null;
        return (h || 0) + (p || 0);
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
        td.innerHTML = '<span class="rt-cell">' + meter(t.cpu, num(d.cpu)) + meter(t.ram, pct(num(d.mem_used), num(d.mem_total)))
            + (n != null ? '<span class="rt-users" title="' + esc(t.users) + '"><i class="fa fa-users" aria-hidden="true"></i> ' + esc(n) + '</span>' : '') + '</span>';
    }

    /* ---------- history kept while a panel is open ---------- */
    function remember(id, d) {
        var L = live[id];
        if (L && !d.ok) {
            // a failed reading: the counters before it are no baseline for the next (the speed would average over the gap);
            // the graphs so far stay, and a speed shows again after two good readings in a row
            L.prev = Object.create(null);
            return;
        }
        if (!L || !d.at) return;
        if (d.at <= (L.lastAt || 0)) return; // the same (cached) reading again, whether or not it had a CPU figure
        L.lastAt = d.at;
        // the router's address was changed since the last reading: its counters are another router's, so no speed across them
        if (d.rev && L.rev && d.rev !== L.rev) L.prev = Object.create(null);
        if (d.rev) L.rev = d.rev;
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
            var now = Object.create(null); // (an interface may be called "constructor": no inherited names here)
            d.ifaces.forEach(function (i) { now[i.name] = true; });
            Object.keys(L.prev).forEach(function (n) { if (!now[n]) delete L.prev[n]; });
        }
        (d.ifaces || []).forEach(function (i) {
            var p = L.prev[i.name];
            if (!p && L.rate[i.name] && L.rate[i.name].length) {
                // a new baseline after a failed reading or a pause: a gap in the graph, so the next speed is not joined to the
                // last one from before
                push(L.rate[i.name], [d.at, null, null]);
            }
            if (p && d.at > p.at) {
                var s = d.at - p.at;
                var down = (i.rx - p.rx) * 8 / s, up = (i.tx - p.tx) * 8 / s;
                if (!L.rate[i.name]) L.rate[i.name] = [];
                // a counter that went back (reset, reboot) gives no speed: a gap, so the line is not drawn across it, and no
                // current speed until the next pair of readings
                push(L.rate[i.name], down >= 0 && up >= 0 ? [d.at, down, up] : [d.at, null, null]);
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
    // series: [{ key, label, cls, points: [[at, v]] }], fmt: value -> text, max: fixed top (or null), title: the chart's name,
    // hover: { f } where the pointer is (0..1 across the chart), { at } the reading picked with the keyboard: kept so the
    // crosshair (and the keyboard focus) stay put when a new reading redraws the chart every second
    function chart(box, series, fmt, max, now, hover, title) {
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
            // a missing value (null) breaks the line there
            var d = '', pen = false;
            s.points.forEach(function (p) {
                if (p[1] == null) { pen = false; return; }
                d += (pen ? 'L' : 'M') + X(p[0]).toFixed(1) + ' ' + Y(p[1]).toFixed(1) + ' ';
                pen = true;
            });
            if (d) svg += '<path class="rt-chart__line ' + s.cls + '" d="' + d + '"></path>';
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
        var empty = series.every(function (s) { return s.points.filter(function (p) { return p[1] != null; }).length < 2; });
        // the chart had the keyboard focus: the new one gets it back (the old one being replaced is no blur)
        var hadFocus = box.contains(document.activeElement);
        hover.redraw = true;
        // with readings, the plot takes the keyboard focus: the arrow keys move from reading to reading, each one said in the
        // figure's live region (outside this box, so a redraw does not cut it off)
        box.innerHTML = '<div class="rt-chart__plot"' + (empty ? '' : ' tabindex="0" role="group" aria-label="' + esc((title ? title + '. ' : '') + t.chartKeys) + '"') + '>'
            + svg + axis
            + (empty ? '<span class="rt-chart__wait">' + esc(t.measuring) + '</span>' : '')
            + '<div class="rt-chart__tip" hidden></div></div>';
        hover.redraw = false;
        if (empty) return;
        var plot = box.firstChild;
        var el = plot.querySelector('svg');
        var cross = el.querySelector('.rt-chart__cross');
        var dots = el.querySelectorAll('.rt-chart__dot');
        var tip = plot.querySelector('.rt-chart__tip');
        var said = box.parentNode && box.parentNode.querySelector('[data-rt-said]');
        // the times to snap to: from the series with the most readings (one may be empty, CPU or memory alone)
        var times = series.reduce(function (a, s) { return s.points.length > a.points.length ? s : a; }).points.map(function (p) { return p[0]; });
        // the readings that have a value in some series: the keys step through these only
        var withValue = times.filter(function (tm) {
            return series.some(function (s) { return s.points.some(function (p) { return p[0] === tm && p[1] != null; }); });
        });
        function nearest(list, at) {
            var best = 0;
            list.forEach(function (tm, i) { if (Math.abs(tm - at) < Math.abs(list[best] - at)) best = i; });
            return best;
        }
        function showAt(tm, speak) {
            if (tm == null) return;
            var r = el.getBoundingClientRect();
            var x = X(tm);
            cross.setAttribute('x1', x); cross.setAttribute('x2', x); cross.removeAttribute('hidden');
            var rows = '', words = [];
            series.forEach(function (s, i) {
                var p = s.points.filter(function (q) { return q[0] === tm; })[0];
                if (!p || p[1] == null) { dots[i].setAttribute('hidden', ''); return; }
                dots[i].setAttribute('cx', x); dots[i].setAttribute('cy', Y(p[1])); dots[i].removeAttribute('hidden');
                rows += '<span><i class="rt-key ' + s.cls + '"></i>' + esc(s.label) + ' <b>' + esc(fmt(p[1])) + '</b></span>';
                words.push(s.label + ' ' + fmt(p[1]));
            });
            tip.innerHTML = '<small>' + clock(tm) + '</small>' + rows;
            tip.hidden = false;
            // beside the crosshair, on the side with more room, so it never covers the lines at that moment
            var left = x / W * r.width;
            var flip = left > r.width / 2;
            tip.style.left = (flip ? left - 10 : left + 10) + 'px';
            tip.style.transform = flip ? 'translateX(-100%)' : 'none';
            if (speak && said) said.textContent = clock(tm) + ': ' + (words.length ? words.join(', ') : '—');
        }
        function move(ev) {
            var r = el.getBoundingClientRect();
            if (ev) { hover.f = (ev.clientX - r.left) / r.width; hover.at = null; }
            var at = x0 + (hover.f * W - padL) / (W - padL - padR) * WINDOW;
            showAt(times[nearest(times, at)], false);
        }
        function leave() {
            hover.f = null;
            cross.setAttribute('hidden', '');
            tip.hidden = true;
            dots.forEach(function (d) { d.setAttribute('hidden', ''); });
        }
        plot.addEventListener('pointermove', move);
        plot.addEventListener('pointerleave', function () {
            // the keyboard's reading stays while the chart has the focus
            if (document.activeElement === plot && hover.at != null) { hover.f = null; showAt(hover.at, false); } else leave();
        });
        plot.addEventListener('keydown', function (e) {
            if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(e.key) === -1 || !withValue.length) return;
            e.preventDefault();
            var k;
            if (hover.at == null) { // the first key: the newest reading (the oldest for Home)
                k = e.key === 'Home' ? 0 : withValue.length - 1;
            } else {
                k = nearest(withValue, hover.at);
                // (left and right follow the drawing, whose time runs left to right in every language)
                k = e.key === 'Home' ? 0 : e.key === 'End' ? withValue.length - 1
                    : Math.max(0, Math.min(withValue.length - 1, k + (e.key === 'ArrowRight' ? 1 : -1)));
            }
            hover.at = withValue[k];
            hover.f = null;
            showAt(hover.at, true);
        });
        plot.addEventListener('blur', function () {
            if (hover.redraw) return;
            hover.at = null;
            leave();
        });
        if (hadFocus) plot.focus({ preventScroll: true });
        if (hover.at != null && withValue.length) {
            // the reading picked stays picked; once it has moved out of the five minutes, the oldest one left
            hover.at = withValue[nearest(withValue, hover.at)];
            showAt(hover.at, false);
        } else if (hover.f != null) {
            move(null);
        }
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
                + '<figure class="rt-chart" data-rt-chart="load"><figcaption class="rt-chart__head"><b>' + esc(t.cpuMem) + '</b><span class="rt-legend"></span></figcaption><div class="rt-chart__box"></div>'
                + '<span class="sr-only" aria-live="polite" data-rt-said></span></figure>'
                + '<figure class="rt-chart" data-rt-chart="traffic"><figcaption class="rt-chart__head"><b>' + esc(t.traffic) + '</b>'
                + '<select class="rt-chart__pick" aria-label="' + esc(t.iface) + '"></select><span class="rt-legend"></span></figcaption><div class="rt-chart__box"></div>'
                + '<span class="sr-only" aria-live="polite" data-rt-said></span></figure>'
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
        var nh = num(d.hotspot), np = num(d.pppoe);
        body.querySelector('.rt-online').innerHTML = n == null ? '' : '<i class="fa fa-users" aria-hidden="true"></i> ' + esc(t.online) + ': <b>' + esc(n) + '</b>'
            + ' <span class="ls-dim">(' + esc([nh !== null ? t.hotspot + ' ' + nh : '', np !== null ? t.pppoe + ' ' + np : ''].filter(Boolean).join(', ')) + ')</span>';
        draw(id);
    }
    function draw(id) {
        var L = live[id], d = data[id];
        var box = document.querySelector('[data-rt-panel="' + id + '"]');
        if (!L || !d || !d.ok || !box || !box.querySelector('.rt-charts')) return;
        var now = d.at || Date.now() / 1000;
        var load = box.querySelector('[data-rt-chart="load"]');
        var sLoad = [
            { label: t.cpu, cls: 'rt-s1', points: L.cpu.filter(function (p) { return p[0] >= now - WINDOW; }) },
            { label: t.memory, cls: 'rt-s2', points: L.mem.filter(function (p) { return p[0] >= now - WINDOW; }) }
        ];
        chart(load.querySelector('.rt-chart__box'), sLoad, function (v) { return Math.round(v) + '%'; }, 100, now, L.hover.load, t.cpuMem);
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
        // only the last five minutes: an interface that came back keeps older speeds in L.rate, which are not drawn or shown
        var rows = ((L.iface && L.rate[L.iface]) || []).filter(function (r) { return r[0] >= now - WINDOW; });
        var sNet = [
            { label: t.down, cls: 'rt-s1', points: rows.map(function (r) { return [r[0], r[1]]; }) },
            { label: t.up, cls: 'rt-s2', points: rows.map(function (r) { return [r[0], r[2]]; }) }
        ];
        chart(traffic.querySelector('.rt-chart__box'), sNet, bps, null, now, L.hover.traffic, t.traffic);
        var lastRow = rows[rows.length - 1];
        // the current speed only from the newest reading: after a pause or a failed reading there is none until a new pair
        var current = lastRow && lastRow[1] != null && lastRow[0] === L.lastAt && L.prev[L.iface];
        traffic.querySelector('.rt-legend').innerHTML = legend(sNet, current ? [bps(lastRow[1]), bps(lastRow[2])] : [null, null]);
    }

    /* ---------- asking the server ---------- */
    // opts.gen: the reading chain a live request belongs to; its answer is dropped once that chain is over (the panel closed,
    // paused or opened again)
    function ask(id, opts) {
        var q = [];
        if (opts.fresh) q.push('fresh=1');
        if (opts.live) q.push('live=1');
        var u = cfg.url + id + (q.length ? (cfg.url.indexOf('?') < 0 ? '?' : '&') + q.join('&') : '');
        var kind = opts.live ? 'live' : 'list';
        var seq = asked[kind][id] = (asked[kind][id] || 0) + 1;
        var order = sentAll[id] = (sentAll[id] || 0) + 1;
        // a deadline for the whole request, reading the answer included: a stuck one fails, so its queue place is freed and the
        // live readings carry on. It is raced against the request, so it holds where requests cannot be cancelled as well.
        var ctl = window.AbortController ? new AbortController() : null;
        var timer;
        var deadline = new Promise(function (resolve) {
            timer = setTimeout(function () { if (ctl) ctl.abort(); resolve({ ok: false, error: t.fail }); }, LIMIT);
        });
        return Promise.race([
            fetch(u, { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' }, signal: ctl ? ctl.signal : undefined })
                .then(function (r) { return r.json(); }),
            deadline
        ])
            .catch(function () { return { ok: false, error: t.fail }; })
            .then(function (d) {
                clearTimeout(timer);
                d = d || { ok: false, error: t.fail };
                if (opts.gen !== undefined && (!live[id] || live[id].gen !== opts.gen || live[id].paused)) {
                    return data[id] || d;
                }
                var cur = data[id];
                // answers of one kind can come back out of order: one from a request older than the answer already shown is
                // dropped, failures too, and so is an older reading served from the store
                if (seq < (applied[kind][id] || 0) || (d.ok && d.at && cur && cur.ok && cur.at && d.at < cur.at)) {
                    return cur || d;
                }
                applied[kind][id] = seq;
                var newest = order >= (shownAll[id] || 0);
                if (newest) {
                    shownAll[id] = order;
                    cell(id, d);
                }
                if (!opts.live) {
                    // a list check: the row's cell only. Once the details panel has been opened (reading, or paused), the panel,
                    // its graphs and their traffic baseline are the Live readings' own; before that it starts from this answer
                    if (!live[id] && newest) data[id] = d;
                    return d;
                }
                data[id] = d;
                remember(id, d);
                if (live[id]) panel(id, d);
                return d;
            });
    }
    var forcing = {}; // id => a Check now for it is queued or on its way
    function fetchQueued(item) {
        running++;
        ask(item.id, { fresh: item.fresh }).finally(function () {
            if (item.fresh) delete forcing[item.id];
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
            // as after a pause (start()): the counters from before are no baseline, so the first reading back starts the speeds
            // again and only the one after it gives a speed
            L.prev = Object.create(null);
            L.timer = setTimeout(function () { tick(id, gen); }, EVERY);
            return;
        }
        ask(id, { live: true, gen: gen }).then(function (d) {
            // a router that does not answer is asked every 30 seconds only: each try can wait for the connection time limit
            var cur = live[id];
            if (cur && !cur.paused && cur.gen === gen) cur.timer = setTimeout(function () { tick(id, gen); }, d && d.ok ? EVERY : EVERY_DOWN);
        });
    }
    function start(id) {
        // prev and rate are keyed by interface name: maps without inherited names, so "constructor" is an interface like any other
        var L = live[id] || (live[id] = { cpu: [], mem: [], prev: Object.create(null), rate: Object.create(null), iface: null, gen: 0, hover: { load: { f: null, at: null }, traffic: { f: null, at: null } } });
        clearTimeout(L.timer);
        L.gen++;
        // the counters read before the pause are no baseline for the next reading (the speed would average over the pause);
        // the graphs so far stay
        L.prev = Object.create(null);
        L.paused = false;
        L.started = Date.now();
        if (data[id]) panel(id, data[id]);
        tick(id, L.gen);
    }
    function pause(id) {
        var L = live[id];
        if (!L) return;
        clearTimeout(L.timer);
        L.paused = true; // (the baseline is cleared when it starts again: start())
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
            if (!td || forcing[String(id)]) return; // a disabled router is not asked, and one already being checked is not asked twice
            forcing[String(id)] = true;
            td.innerHTML = '<span class="rt-skel"></span>';
            // its first check, if still waiting, becomes this one (not a second request), and goes before the first checks still
            // waiting for other routers; the three-at-a-time limit is next()'s, unchanged
            queue = queue.filter(function (q) { return q.id !== String(id); });
            var at = 0;
            while (at < queue.length && queue[at].fresh) at++;
            queue.splice(at, 0, { id: String(id), fresh: true });
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

    // the list's fallback opener and routers-tools.js check this: set only once this script's handlers are in place. The Live
    // tabs and Check now start disabled in the page and are switched on here
    window.rtHealthReady = true;
    document.querySelectorAll('[data-rt-tab="live"]').forEach(function (b) { b.disabled = false; });
    (function () {
        var check = document.getElementById('rtCheckNow');
        if (check) check.disabled = !document.querySelector('input[name="ids[]"][form="rtBulkForm"]:checked');
    })();

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
                if (box && !box.innerHTML) box.innerHTML = '<span class="rt-skel rt-skel--wide"></span>';
                // reopened on the Live tab: read again. The first opening (no tab chosen yet) and other tabs wait for the tab
                // to be shown, which says so through rt:tab
                var sel = row.querySelector('.rt-tab.is-on');
                // without routers-tools.js (it failed to load) no tab is ever chosen: the Live panel, the only one shown, starts now
                if (box && (sel ? sel.getAttribute('data-rt-tab') === 'live' : !window.rtToolsReady)) start(id);
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
