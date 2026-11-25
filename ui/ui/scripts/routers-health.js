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
    var live = {};           // id => { cpu: [[at, v]], mem: [[at, v]], prev: {name: {rx, tx, at}}, ifs: {name: {type, total}}, rate: {name: [[at, down, up]]}, iface, boot, up, timer, paused, started }
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
    var livePainted = {};    // id => a Live reading has put its answer in the row's Health cell
    var LIMIT = 20000;       // ms a request (and reading its answer) may take before it counts as failed

    var esc = window.RtChart.esc; // the chart pieces shared with the History graphs (routers-chart.js)
    function size(b) {
        if (b == null) return '';
        var u = ['B', 'KB', 'MB', 'GB', 'TB'], i = 0;
        while (b >= 1024 && i < u.length - 1) { b /= 1024; i++; }
        return (b >= 10 || i === 0 ? Math.round(b) : b.toFixed(1)) + ' ' + u[i];
    }
    var bps = window.RtChart.bps;
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
    // the total only when both counts are known: an unknown count is not a zero (zero is a count like any other)
    function users(d) {
        var h = num(d.hotspot), p = num(d.pppoe);
        if (h === null || p === null) return null;
        return h + p;
    }
    // what is known when the total is not: "Hotspot 3" or "PPPoE 2", or '' when neither is
    function usersPart(d) {
        var h = num(d.hotspot), p = num(d.pppoe);
        if (h !== null) return t.hotspot + ' ' + h;
        if (p !== null) return t.pppoe + ' ' + p;
        return '';
    }
    // an uptime as the server says it ("9d 3h 4m", to the minute) as seconds, or null when it is not in that form
    function upSecs(u) {
        if (typeof u !== 'string' || !/^\s*(\d+\s*[wdhms]\s*)+$/.test(u)) return null;
        var mult = { w: 604800, d: 86400, h: 3600, m: 60, s: 1 }, n = 0;
        u.replace(/(\d+)\s*([wdhms])/g, function (all, v, k) { n += v * mult[k]; });
        return n;
    }
    var BOOT_SLACK = 120; // seconds a start time worked out from a reading can move without a restart (the uptime is to the minute)
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
        var n = users(d), part = n == null ? usersPart(d) : '';
        td.innerHTML = '<span class="rt-cell">' + meter(t.cpu, num(d.cpu)) + meter(t.ram, pct(num(d.mem_used), num(d.mem_total)))
            + (n != null ? '<span class="rt-users" title="' + esc(t.users) + '"><i class="fa fa-users" aria-hidden="true"></i> ' + esc(n) + '</span>'
                : part ? '<span class="rt-users" title="' + esc(t.users) + '"><i class="fa fa-users" aria-hidden="true"></i> ' + esc(part) + '</span>' : '') + '</span>';
    }

    /* ---------- history kept while a panel is open ---------- */
    function remember(id, d) {
        var L = live[id];
        if (L && !d.ok) {
            // a failed reading: the counters before it are no baseline for the next (the speed would average over the gap);
            // the graphs so far stay, and a speed shows again after two good readings in a row. The CPU and memory lines are
            // not joined across it either (see L.gap below).
            L.prev = Object.create(null);
            L.gap = true;
            return;
        }
        if (!L || !d.at) return;
        if (d.at <= (L.lastAt || 0)) return; // the same (cached) reading again, whether or not it had a CPU figure
        L.lastAt = d.at;
        // the router's address was changed since the last reading: its readings are another router's, so its graphs start
        // again (CPU, memory, traffic, the reading picked on them and the interface chosen) and no speed across the change
        if (d.rev && L.rev && d.rev !== L.rev) {
            L.prev = Object.create(null);
            L.ifs = Object.create(null);
            L.cpu = [];
            L.mem = [];
            L.rate = Object.create(null);
            L.iface = null;
            L.boot = L.up = null;
            L.gap = false;
            ['load', 'traffic'].forEach(function (k) { L.hover[k].f = null; L.hover[k].at = null; });
        }
        if (d.rev) L.rev = d.rev;
        // the router restarted since the last reading (its start time, when read less its uptime, moved later, or its uptime went
        // back): its counters started again, so the reading before is no baseline; this one is the new one (a gap, then speeds
        // from the next reading on). A counter that happens to be higher after the restart would otherwise give a wrong speed.
        // the exact uptime in seconds when the server sends it (the text is to the minute), with a tighter allowance for it
        var exact = d.uptime_s != null && isFinite(Number(d.uptime_s));
        var up = exact ? Number(d.uptime_s) : upSecs(d.uptime);
        if (up !== null) {
            var boot = d.at - up;
            if (L.boot != null && (boot - L.boot > (exact ? 15 : BOOT_SLACK) || up < L.up)) L.prev = Object.create(null);
            L.boot = boot;
            L.up = up;
        }
        var cut = d.at - WINDOW;
        function push(arr, row) {
            arr.push(row);
            while (arr.length && arr[0][0] < cut) arr.shift();
        }
        if (L.gap) {
            // the first reading after a failed one, a pause or a hidden page: a gap in the CPU and memory graphs, so the line is
            // not drawn across the time nothing was measured (this reading is kept, as a new start)
            if (L.cpu.length) push(L.cpu, [d.at, null]);
            if (L.mem.length) push(L.mem, [d.at, null]);
            L.gap = false;
        }
        // a metric that is not available is a gap at this time (a null point), each one on its own
        push(L.cpu, [d.at, d.cpu != null ? d.cpu : null]);
        var m = pct(d.mem_used, d.mem_total);
        push(L.mem, [d.at, m != null ? m : null]);
        // the interfaces running now: one that has gone (unplugged, disabled) leaves the picker; its graph history stays in
        // L.rate, so it is there again if the interface comes back
        // L.ifs is the list shown in the picker: kept apart from L.prev, the counter baseline, which a pause, a failed reading or
        // a hidden page clears, so the traffic graph stays shown (with its history) while it waits for two new readings
        // PHP gives a keyed object when filtering left gaps in the keys (two interfaces at keys 1 and 3): one list either way
        var ifaces = d.ifaces ? (Array.isArray(d.ifaces) ? d.ifaces : Object.keys(d.ifaces).map(function (k) { return d.ifaces[k]; })).filter(function (i) { return i && i.name != null; }) : null; // (an entry that is not an interface is left out: it must not stop the polling)
        if (ifaces) {
            var now = Object.create(null); // (an interface may be called "constructor": no inherited names here)
            ifaces.forEach(function (i) { now[i.name] = { type: i.type, total: i.rx + i.tx }; });
            Object.keys(L.prev).forEach(function (n) { if (!now[n]) delete L.prev[n]; });
            L.ifs = now;
        } else {
            L.prev = Object.create(null); // no reading this time: never diff against a stale baseline
        }
        (ifaces || []).forEach(function (i) {
            var p = L.prev[i.name];
            // an interface with the same name but another internal id is another interface (removed and made again): a new baseline
            if (p && (p.id || '') !== (i.id || '')) { p = null; delete L.rate[i.name]; } // (the old interface's readings are not the new one's)
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
            L.prev[i.name] = { rx: i.rx, tx: i.tx, at: d.at, type: i.type, total: i.rx + i.tx, id: i.id || '' };
        });
        // every interface's speeds older than the window go, those of one that is not listed now included; a series left with
        // nothing is dropped, and what is still inside the window stays for an interface that comes back
        Object.keys(L.rate).forEach(function (n) {
            while (L.rate[n].length && L.rate[n][0][0] < cut) L.rate[n].shift();
            if (!L.rate[n].length) delete L.rate[n];
        });
        if (!L.iface || !L.ifs[L.iface]) {
            // the busiest interface since the router started is usually the uplink
            var best = null;
            Object.keys(L.ifs).forEach(function (n) {
                if (!best || L.ifs[n].total > L.ifs[best].total) best = n;
            });
            L.iface = saved(id) && L.ifs[saved(id)] ? saved(id) : best;
        }
    }
    // the interface picked is kept for the router at its present address (its revision): after the address changes, the new
    // router's busiest interface is shown instead of a name the old one had
    function saved(id, v) {
        var key = 'rt-iface-' + id + (live[id] && live[id].rev ? '-' + live[id].rev : '');
        try {
            if (v === undefined) return localStorage.getItem(key);
            localStorage.setItem(key, v);
        } catch (e) {
            return null;
        }
    }

    /* ---------- line chart: one y axis, 2px lines, crosshair and tooltip ---------- */
    var niceMax = window.RtChart.niceMax;
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
        var svg = window.RtChart.svg(series, { W: W, H: H, padL: padL, padR: padR, padT: padT, padB: padB, top: top, X: X, Y: Y });
        // axis text sits in HTML over the drawing, so it keeps its size when the drawing stretches
        var axis = '<span class="rt-chart__y" style="top:' + (Y(top) / H * 100) + '%">' + esc(fmt(top)) + '</span>'
            + '<span class="rt-chart__y" style="top:' + (Y(top / 2) / H * 100) + '%">' + esc(fmt(top / 2)) + '</span>'
            + '<span class="rt-chart__y" style="top:' + (Y(0) / H * 100) + '%">' + esc(fmt(0)) + '</span>'
            + '<span class="rt-chart__x rt-chart__x--start" style="left:' + (padL / W * 100) + '%">' + esc(t.minutes5) + '</span>'
            + '<span class="rt-chart__x rt-chart__x--end">' + esc(t.now) + '</span>';
        // empty only when no series has a value yet (a single reading is drawn as a dot and can be looked at); a traffic chart
        // with only its first baseline has no speed in it, so it stays "measuring"
        var empty = series.every(function (s) { return !s.points.some(function (p) { return p[1] != null; }); });
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
        // the times to snap to: every reading of every series, in order (one series may have readings the other has not), and
        // each series' readings by their time, so a reading is found without searching the series
        var times = [], seen = Object.create(null);
        series.forEach(function (s) {
            s.byAt = Object.create(null);
            s.points.forEach(function (p) {
                s.byAt[p[0]] = p;
                if (!seen[p[0]]) { seen[p[0]] = true; times.push(p[0]); }
            });
        });
        times.sort(function (a, b) { return a - b; });
        // the readings that have a value in some series: the keys step through these only
        var withValue = times.filter(function (tm) {
            return series.some(function (s) { var p = s.byAt[tm]; return p && p[1] != null; });
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
                var p = s.byAt[tm];
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
        var failEl = body.querySelector('[data-rt-fail]');
        if (!d.ok) {
            // the graphs so far stay where they are (and keep the keyboard focus); the failure is said above them
            if (!body.querySelector('.rt-charts')) {
                body.innerHTML = '<p class="rt-err rt-err--block">' + failText(d) + '</p>';
                return;
            }
            if (!failEl) {
                failEl = document.createElement('p');
                failEl.className = 'rt-err rt-err--block';
                failEl.setAttribute('data-rt-fail', '');
                body.insertBefore(failEl, body.firstChild);
            }
            failEl.innerHTML = failText(d);
            // the current download / upload speed goes (it is not the router's now); the chart and its focus stay
            var tl = body.querySelector('[data-rt-chart="traffic"] .rt-legend');
            if (tl) tl.innerHTML = legend([{ label: t.down, cls: 'rt-s1' }, { label: t.up, cls: 'rt-s2' }], [null, null]);
            return;
        }
        if (failEl) failEl.parentNode.removeChild(failEl);
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
        // no total unless both counts are known; one that is known is still shown, labelled, never as the whole
        body.querySelector('.rt-online').innerHTML = (nh === null && np === null) ? '' : '<i class="fa fa-users" aria-hidden="true"></i> ' + esc(t.online) + ': '
            + (n == null ? '' : '<b>' + esc(n) + '</b> ')
            + '<span class="ls-dim">' + (n == null ? '' : '(') + esc([nh !== null ? t.hotspot + ' ' + nh : '', np !== null ? t.pppoe + ' ' + np : ''].filter(Boolean).join(', ')) + (n == null ? '' : ')') + '</span>';
        draw(id);
    }
    function draw(id) {
        var L = live[id], d = data[id];
        var box = document.querySelector('[data-rt-panel="' + id + '"]');
        if (!L || !d || !box || !box.querySelector('.rt-charts')) return;
        var now = d.at || Date.now() / 1000;
        var load = box.querySelector('[data-rt-chart="load"]');
        // after a failed reading only the traffic chart is drawn again (a different interface picked), from the readings kept;
        // the failure message stays and no current speed is shown
        if (d.ok) {
        var sLoad = [
            { label: t.cpu, cls: 'rt-s1', points: L.cpu.filter(function (p) { return p[0] >= now - WINDOW; }) },
            { label: t.memory, cls: 'rt-s2', points: L.mem.filter(function (p) { return p[0] >= now - WINDOW; }) }
        ];
        chart(load.querySelector('.rt-chart__box'), sLoad, function (v) { return Math.round(v) + '%'; }, 100, now, L.hover.load, t.cpuMem);
        load.querySelector('.rt-legend').innerHTML = legend(sLoad, [d.cpu != null ? d.cpu + '%' : null, pct(d.mem_used, d.mem_total) != null ? pct(d.mem_used, d.mem_total) + '%' : null]);
        }

        var traffic = box.querySelector('[data-rt-chart="traffic"]');
        var pick = traffic.querySelector('.rt-chart__pick');
        var names = Object.keys(L.ifs).sort(function (a, b) { return L.ifs[b].total - L.ifs[a].total; });
        if (pick.getAttribute('data-names') !== names.join('\n')) {
            pick.innerHTML = names.map(function (nm) {
                return '<option value="' + esc(nm) + '">' + esc(nm) + (L.ifs[nm].type ? ' (' + esc(L.ifs[nm].type) + ')' : '') + '</option>';
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
        var current = d.ok && !L.paused && lastRow && lastRow[1] != null && lastRow[0] === L.lastAt && L.prev[L.iface];
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
        // (a Live reading asked before the page was hidden, or shown again, is not used: see visibilitychange below)
        var vis = opts.live && live[id] ? live[id].vis || 0 : 0;
        var seq = asked[kind][id] = (asked[kind][id] || 0) + 1;
        var order = sentAll[id] = (sentAll[id] || 0) + 1;
        // a deadline for the whole request, reading the answer included: a stuck one fails, so its queue place is freed and the
        // live readings carry on. It is raced against the request, so it holds where requests cannot be cancelled as well.
        var ctl = window.AbortController ? new AbortController() : null;
        // a Live reading can be cancelled when its chain is started again (start())
        if (opts.gen !== undefined && live[id]) live[id].ctl = ctl;
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
                if (opts.live && live[id] && (live[id].vis || 0) !== vis) {
                    // its counters span the time the page was hidden: no speed from them (the next reading starts again). Not
                    // used, and not a failure either: the next reading is asked at the usual pace, not after the backoff
                    return { ok: false, discarded: true };
                }
                var cur = data[id];
                // answers of one kind can come back out of order: one from a request older than the answer already shown is
                // dropped, failures too, and so is an older reading served from the store
                if (seq < (applied[kind][id] || 0) || (d.ok && d.at && cur && cur.ok && cur.at && d.at < cur.at)) {
                    return cur || d;
                }
                applied[kind][id] = seq;
                var newest = order >= (shownAll[id] || 0);
                // a queued first check does not replace what Live has shown in the cell (a cached success over a Live failure);
                // Check now and the Live readings themselves always do
                // (only once Live has put an answer in the cell: until then the first check is what replaces "loading")
                if (newest && !opts.live && !opts.fresh && live[id] && livePainted[id]) newest = false;
                if (newest) {
                    shownAll[id] = order;
                    cell(id, d);
                    if (opts.live) livePainted[id] = true;
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
            L.gap = true;
            L.timer = setTimeout(function () { tick(id, gen); }, EVERY);
            return;
        }
        // one reading at a time for each router: start() waits for this one before a new chain asks again
        var req = L.inflight = ask(id, { live: true, gen: gen });
        var settled = function () { if (L.inflight === req) L.inflight = null; };
        req.then(settled, settled);
        req.then(function (d) {
            // a router that does not answer is asked every 30 seconds only: each try can wait for the connection time limit
            var cur = live[id];
            // (an answer dropped because the page was hidden or shown meanwhile is no failure: asked again at the usual pace)
            if (cur && !cur.paused && cur.gen === gen) cur.timer = setTimeout(function () { tick(id, gen); }, d && (d.ok || d.discarded) ? EVERY : EVERY_DOWN);
        });
    }
    function start(id) {
        // prev and rate are keyed by interface name: maps without inherited names, so "constructor" is an interface like any other
        var L = live[id] || (live[id] = { cpu: [], mem: [], prev: Object.create(null), ifs: Object.create(null), rate: Object.create(null), iface: null, gen: 0, hover: { load: { f: null, at: null }, traffic: { f: null, at: null } } });
        clearTimeout(L.timer);
        L.gen++;
        L.suspended = false;
        // the counters read before the pause are no baseline for the next reading (the speed would average over the pause);
        // the graphs so far stay, with a gap after them
        L.prev = Object.create(null);
        L.gap = true;
        L.paused = false;
        L.started = Date.now();
        if (data[id]) panel(id, data[id]);
        var gen = L.gen;
        if (L.inflight) {
            // a reading of the chain before (paused, or the panel closed, while it was on its way) is still out: cancelled where the
            // browser can, and the new chain asks only once it has settled (at the latest at its deadline), so a router never has
            // two readings on their way at once. Its answer is still dropped (its generation is over).
            if (L.ctl) L.ctl.abort();
            var go = function () { tick(id, gen); };
            L.inflight.then(go, go);
        } else {
            tick(id, gen);
        }
    }
    // suspended: paused by leaving the Live tab or closing the row, so showing it again carries on; any other pause (the Pause
    // button, the 10-minute limit) stays until Resume is pressed
    function pause(id, suspended) {
        var L = live[id];
        if (!L) return;
        clearTimeout(L.timer);
        L.paused = true; // (the baseline is cleared when it starts again: start())
        L.suspended = !!suspended;
        if (data[id]) panel(id, data[id]);
    }
    function stop(id) {
        if (live[id]) {
            clearTimeout(live[id].timer);
            // (a panel paused by hand stays so: see rt:tab)
            if (!live[id].paused) live[id].suspended = true;
            live[id].paused = true;
        }
    }

    // the page hidden (or shown again): the counters read before are no baseline for the next reading, whenever between two
    // readings it happens, so the first reading after it starts the speeds again and only the second gives one
    document.addEventListener('visibilitychange', function () {
        Object.keys(live).forEach(function (id) {
            live[id].vis = (live[id].vis || 0) + 1;
            live[id].prev = Object.create(null);
            live[id].gap = true;
        });
    });

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
            // back on the Live tab: reading again only if leaving it paused the readings, not after a Pause
            if (!live[id] || (live[id].paused && live[id].suspended)) start(id);
        } else if (live[id] && !live[id].paused) {
            pause(id, true);
        }
    });

    // the list's fallback opener and routers-tools.js check this: set only once this script's handlers are in place. The Live
    // tabs and Check now start disabled in the page and are switched on here
    window.rtHealthReady = true;
    document.querySelectorAll('[data-rt-tab="live"]').forEach(function (b) { b.disabled = false; });
    (function () {
        var check = document.getElementById('rtCheckNow');
        // as the list's own check (admin/routers/list.tpl): on only when a ticked router can be checked, i.e. has a row this
        // script reads (tr[data-rt-id]: enabled, with an address); a selection of disabled routers alone keeps it off
        if (!check) return;
        var can = false;
        document.querySelectorAll('input[name="ids[]"][form="rtBulkForm"]:checked').forEach(function (b) {
            if (document.querySelector('tr[data-rt-id="' + b.value + '"]')) can = true;
        });
        check.disabled = !can;
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
                // (a panel paused by hand before the row was closed stays paused until Resume)
                if (box && (sel ? sel.getAttribute('data-rt-tab') === 'live' : !window.rtToolsReady) && (!live[id] || live[id].suspended)) start(id);
                if (!sel && !window.rtToolsReady) {
                    // routers-tools.js is not there to pick a tab: the Live tab is marked as the selected one, and is the tab stop
                    var liveTab = row.querySelector('[data-rt-tab="live"]');
                    if (liveTab) {
                        liveTab.classList.add('is-on');
                        liveTab.setAttribute('aria-selected', 'true');
                        liveTab.tabIndex = 0;
                    }
                }
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
