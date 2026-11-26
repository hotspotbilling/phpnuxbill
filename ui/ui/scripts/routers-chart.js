/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *
 *  Routers list: the chart pieces shared by the Live graphs (routers-health.js) and the History graphs (routers-tools.js): text
 *  escaping, speed and axis helpers, and the drawing itself (grid, lines broken at gaps, a dot for a reading with no neighbour,
 *  and the hidden crosshair and dots the scripts move). What each one keeps (Live redraw state, History range) stays in its own file.
 **/
(function () {
    'use strict';
    window.RtChart = {
        esc: function (s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        },
        bps: function (v) {
            if (v == null) return '';
            var u = ['bps', 'kbps', 'Mbps', 'Gbps'], i = 0;
            while (v >= 1000 && i < u.length - 1) { v /= 1000; i++; }
            return (v >= 100 || i === 0 ? Math.round(v) : v.toFixed(1)) + ' ' + u[i];
        },
        niceMax: function (v) {
            if (!v || v <= 0) return 1;
            var p = Math.pow(10, Math.floor(Math.log10(v)));
            var n = v / p;
            return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * p;
        },
        // the drawing as an <svg> string. series: [{ cls, points: [[at, v]] }] (a null value breaks the line); g: { W, H, padL, padR,
        // padT, padB, top, X(at), Y(v) }
        svg: function (series, g) {
            var svg = '<svg viewBox="0 0 ' + g.W + ' ' + g.H + '" preserveAspectRatio="none" class="rt-chart__svg" aria-hidden="true">';
            [0, 0.5, 1].forEach(function (f) {
                var y = g.Y(g.top * f);
                svg += '<line class="rt-chart__grid" x1="' + g.padL + '" x2="' + (g.W - g.padR) + '" y1="' + y + '" y2="' + y + '"></line>';
            });
            series.forEach(function (s) {
                var d = '', pen = false;
                s.points.forEach(function (p) {
                    if (p[1] == null) { pen = false; return; }
                    d += (pen ? 'L' : 'M') + g.X(p[0]).toFixed(1) + ' ' + g.Y(p[1]).toFixed(1) + ' ';
                    pen = true;
                });
                if (d) svg += '<path class="rt-chart__line ' + s.cls + '" d="' + d + '"></path>';
                // a reading with no reading next to it on either side has no line to be part of: a dot shows it
                s.points.forEach(function (p, i) {
                    var before = s.points[i - 1], after = s.points[i + 1];
                    if (p[1] != null && !(before && before[1] != null) && !(after && after[1] != null)) {
                        svg += '<circle class="rt-chart__pt ' + s.cls + '" r="3.5" cx="' + g.X(p[0]).toFixed(1) + '" cy="' + g.Y(p[1]).toFixed(1) + '"></circle>';
                    }
                });
            });
            svg += '<line class="rt-chart__cross" x1="0" x2="0" y1="' + g.padT + '" y2="' + (g.H - g.padB) + '" hidden></line>';
            series.forEach(function (s) { svg += '<circle class="rt-chart__dot ' + s.cls + '" r="4" cx="0" cy="0" hidden></circle>'; });
            return svg + '</svg>';
        }
    };
})();
