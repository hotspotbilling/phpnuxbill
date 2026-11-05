{*
    Lets an admin arrange the dashboard: an "Arrange" button for the toolbar, and the drag, keyboard and touch behaviour.
    Included by every dashboard template. Parameters:
        kind  'blocks'  the dynamic dashboard: one column of .dash-block inside .dash-blocks
              'widgets' the default dashboard: .dash-block inside several .dash-slot columns
        btn   the CSS class(es) of the toolbar's own buttons, so the button matches the theme
    The page must have the dash_token variable. The layout is saved per admin by ?_route=dashboard/layout.
*}
<button type="button" id="dash-arrange-toggle" class="{$btn|default:'btn btn-default'}"
    data-kind="{$kind|escape}" data-url="{Text::url('dashboard/layout')|escape}" data-token="{$dash_token|escape}"
    data-t-arrange="{Lang::T('Arrange')|escape}" data-t-done="{Lang::T('Done')|escape}"
    data-t-saved="{Lang::T('Layout saved')|escape}" data-t-saving="{Lang::T('Saving...')|escape}"
    data-t-failed="{Lang::T('Could not save. Reload the page and try again.')|escape}"
    data-t-reset-ask="{Lang::T('Go back to the default layout?')|escape}"
    data-t-sure="{Lang::T('Are you sure?')|escape}" data-t-yes="{Lang::T('Yes, proceed')|escape}" data-t-cancel="{Lang::T('Cancel')|escape}"
    data-t-drag="{Lang::T('Drag to move')|escape}" data-t-up="{Lang::T('Move up')|escape}" data-t-down="{Lang::T('Move down')|escape}"
    data-t-left="{Lang::T('Move to the previous column')|escape}" data-t-right="{Lang::T('Move to the next column')|escape}"
    title="{Lang::T('Drag the cards of the dashboard where you want them')|escape}">
    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true" style="vertical-align:-2px"><path stroke-linecap="round" stroke-linejoin="round" d="M5 9l-3 3 3 3M9 5l3-3 3 3M15 19l-3 3-3-3M19 9l3 3-3 3M2 12h20M12 2v20"/></svg>
    <span class="dash-arrange-label">{Lang::T('Arrange')}</span>
</button>
<button type="button" id="dash-arrange-reset" class="{$btn|default:'btn btn-default'}" hidden>{Lang::T('Reset layout')}</button>
<span id="dash-arrange-status" class="dash-arrange-status" role="status" aria-live="polite"></span>

<style>
    .dash-toolbar { gap: 8px; flex-wrap: wrap; align-items: center; }
    .dash-blocks { display: flex; flex-direction: column; }
    .dash-block { min-width: 0; }
    .dash-arrange-status { font-size: 12.5px; opacity: .75; align-self: center; margin-left: 6px; }
    #dash-arrange-toggle.is-on { background: #2563eb; border-color: #2563eb; color: #fff; }
    #dash-arrange-reset[hidden] { display: none; }

    /* while arranging: every card shrinks to a short preview with a bar on top, so long charts are easy to move */
    .dash-arranging .dash-block { position: relative; outline: 2px dashed rgba(100, 116, 139, .55); outline-offset: -2px; border-radius: 10px; max-height: 168px; overflow: hidden; margin-bottom: 12px; background: rgba(127, 127, 127, .04); }
    .dash-arranging .dash-block > *:not(.dash-bar) { opacity: .45; pointer-events: none; user-select: none; }
    .dash-arranging .dash-slot { min-height: 72px; outline: 1px dashed rgba(100, 116, 139, .35); outline-offset: 2px; border-radius: 10px; }
    .dash-bar { display: flex; align-items: center; gap: 4px; padding: 6px 8px; position: relative; z-index: 3; background: rgba(37, 99, 235, .16); color: inherit; font: 600 13px/1.2 system-ui, -apple-system, 'Segoe UI', sans-serif; }
    .dash-bar__title { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dash-bar button { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border: 1px solid rgba(100, 116, 139, .45); border-radius: 8px; background: rgba(255, 255, 255, .85); color: #1e293b; font-size: 15px; line-height: 1; cursor: pointer; }
    .dash-bar button:hover, .dash-bar button:focus-visible { background: #2563eb; color: #fff; border-color: #2563eb; outline: none; }
    .dash-bar .dash-handle { cursor: grab; touch-action: none; }
    .dash-lifted { position: fixed !important; z-index: 9999; pointer-events: none; opacity: .92; box-shadow: 0 14px 36px rgba(0, 0, 0, .35); background: #fff; }
    .dash-placeholder { border: 2px dashed #2563eb; border-radius: 10px; background: rgba(37, 99, 235, .08); margin-bottom: 12px; }
</style>

{literal}
<script>
(function () {
    var btn = document.getElementById('dash-arrange-toggle');
    if (!btn) return;
    var reset = document.getElementById('dash-arrange-reset');
    var status = document.getElementById('dash-arrange-status');
    var kind = btn.dataset.kind;
    var T = function (k) { return btn.dataset['t' + k.charAt(0).toUpperCase() + k.slice(1)] || ''; };
    var arranging = false, timer = null, drag = null;

    function lists() {
        return [].slice.call(document.querySelectorAll(kind === 'widgets' ? '.dash-slot' : '.dash-blocks'));
    }
    function blocks(list) {
        return [].filter.call(list.children, function (c) { return c.classList && c.classList.contains('dash-block'); });
    }
    /* the dynamic dashboard shows blocks by CSS `order`; before moving anything make the document order match what is on screen */
    function normalize(list) {
        if (kind !== 'blocks') return;
        blocks(list).sort(function (a, b) { return (parseInt(a.style.order, 10) || 0) - (parseInt(b.style.order, 10) || 0); })
            .forEach(function (b) { list.appendChild(b); });
    }
    function stamp(list) {
        if (kind === 'blocks') blocks(list).forEach(function (b, i) { b.style.order = i; });
    }
    function layout() {
        if (kind === 'blocks') {
            return { order: blocks(lists()[0]).map(function (b) { return b.dataset.block; }) };
        }
        var slots = {};
        lists().forEach(function (l) { slots[l.dataset.slot] = blocks(l).map(function (b) { return b.dataset.block; }); });
        return { slots: slots };
    }
    function say(text) { status.textContent = text; }

    function post(data, done) {
        var body = new URLSearchParams(data);
        body.set('token', btn.dataset.token);
        body.set('kind', kind);
        fetch(btn.dataset.url, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (j) { done(!!(j && j.success)); })
            .catch(function () { done(false); });
    }
    function save() {
        clearTimeout(timer);
        say(T('saving'));
        timer = setTimeout(function () {
            post({ layout: JSON.stringify(layout()) }, function (ok) { say(ok ? T('saved') : T('failed')); });
        }, 350);
    }
    function changed() {
        lists().forEach(stamp);
        window.dispatchEvent(new Event('resize'));
        save();
    }

    /* ---- the bar on each card ---- */
    function button(label, text, cls) {
        var b = document.createElement('button');
        b.type = 'button'; b.title = label; b.setAttribute('aria-label', label); b.textContent = text;
        if (cls) b.className = cls;
        return b;
    }
    function addBar(block) {
        if (block.querySelector(':scope > .dash-bar')) return;
        var bar = document.createElement('div');
        bar.className = 'dash-bar';
        var handle = button(T('drag'), '☰', 'dash-handle');
        var title = document.createElement('span');
        title.className = 'dash-bar__title';
        title.textContent = block.dataset.title || block.dataset.block;
        bar.appendChild(handle); bar.appendChild(title);
        bar.appendChild(button(T('up'), '↑', 'dash-up'));
        bar.appendChild(button(T('down'), '↓', 'dash-down'));
        if (kind === 'widgets') {
            bar.appendChild(button(T('left'), '←', 'dash-left'));
            bar.appendChild(button(T('right'), '→', 'dash-right'));
        }
        block.insertBefore(bar, block.firstChild);
    }
    function removeBars() {
        [].forEach.call(document.querySelectorAll('.dash-bar'), function (b) { b.parentNode.removeChild(b); });
    }

    /* ---- buttons: works with a keyboard and on a phone ---- */
    function step(block, dir) {
        var list = block.parentNode;
        lists().forEach(normalize);
        if (dir === 'up' && block.previousElementSibling && block.previousElementSibling.classList.contains('dash-block')) {
            list.insertBefore(block, block.previousElementSibling);
        } else if (dir === 'down' && block.nextElementSibling && block.nextElementSibling.classList.contains('dash-block')) {
            list.insertBefore(block.nextElementSibling, block);
        } else if ((dir === 'left' || dir === 'right') && kind === 'widgets') {
            var all = lists(), i = all.indexOf(list), j = dir === 'left' ? i - 1 : i + 1;
            if (j < 0 || j >= all.length) return;
            var at = Math.min(blocks(list).indexOf(block), blocks(all[j]).length);
            var ref = blocks(all[j])[at] || null;
            all[j].insertBefore(block, ref);
        } else {
            return;
        }
        changed();
        var again = block.querySelector('.dash-' + dir);
        if (again) again.focus();
    }
    document.addEventListener('click', function (e) {
        var b = e.target.closest && e.target.closest('.dash-bar button');
        if (!b || b.classList.contains('dash-handle')) return;
        var dir = ['up', 'down', 'left', 'right'].filter(function (d) { return b.classList.contains('dash-' + d); })[0];
        if (dir) step(b.closest('.dash-block'), dir);
    });

    /* ---- dragging by the handle (mouse, pen and finger) ---- */
    function listAt(x, y) {
        var all = lists();
        if (all.length === 1) return all[0];
        var best = null, bestD = Infinity;
        all.forEach(function (l) {
            var r = l.getBoundingClientRect();
            var dx = x < r.left ? r.left - x : (x > r.right ? x - r.right : 0);
            var dy = y < r.top ? r.top - y : (y > r.bottom ? y - r.bottom : 0);
            var d = dx * 1000 + dy; /* the column under the pointer wins; then the nearest */
            if (d < bestD) { bestD = d; best = l; }
        });
        return best;
    }
    function place(x, y) {
        var list = listAt(x, y);
        var kids = blocks(list).filter(function (b) { return b !== drag.block; });
        var before = null;
        for (var i = 0; i < kids.length; i++) {
            var r = kids[i].getBoundingClientRect();
            if (y < r.top + r.height / 2) { before = kids[i]; break; }
        }
        if (drag.ph.parentNode !== list || drag.ph.nextSibling !== before) list.insertBefore(drag.ph, before);
    }
    document.addEventListener('pointerdown', function (e) {
        var h = e.target.closest && e.target.closest('.dash-handle');
        if (!h || !arranging || (e.pointerType === 'mouse' && e.button !== 0)) return;
        e.preventDefault();
        var block = h.closest('.dash-block'), r = block.getBoundingClientRect();
        lists().forEach(normalize);
        var ph = document.createElement('div');
        ph.className = 'dash-placeholder';
        ph.style.height = Math.min(r.height, 168) + 'px';
        block.parentNode.insertBefore(ph, block);
        drag = { block: block, ph: ph, dx: e.clientX - r.left, dy: e.clientY - r.top, id: e.pointerId, handle: h };
        block.classList.add('dash-lifted');
        block.style.width = r.width + 'px'; block.style.left = r.left + 'px'; block.style.top = r.top + 'px';
        try { h.setPointerCapture(e.pointerId); } catch (x) { }
    });
    document.addEventListener('pointermove', function (e) {
        if (!drag || e.pointerId !== drag.id) return;
        drag.block.style.left = (e.clientX - drag.dx) + 'px';
        drag.block.style.top = (e.clientY - drag.dy) + 'px';
        place(e.clientX, e.clientY);
        if (e.clientY < 70) window.scrollBy(0, -18); else if (window.innerHeight - e.clientY < 70) window.scrollBy(0, 18);
    });
    function drop(e) {
        if (!drag || e.pointerId !== drag.id) return;
        var d = drag; drag = null;
        d.block.classList.remove('dash-lifted');
        d.block.style.width = d.block.style.left = d.block.style.top = '';
        d.ph.parentNode.insertBefore(d.block, d.ph);
        d.ph.parentNode.removeChild(d.ph);
        try { d.handle.releasePointerCapture(d.id); } catch (x) { }
        changed();
    }
    document.addEventListener('pointerup', drop);
    document.addEventListener('pointercancel', drop);

    /* ---- the toolbar buttons ---- */
    btn.addEventListener('click', function () {
        arranging = !arranging;
        document.documentElement.classList.toggle('dash-arranging', arranging);
        btn.classList.toggle('is-on', arranging);
        btn.querySelector('.dash-arrange-label').textContent = arranging ? T('done') : T('arrange');
        reset.hidden = !arranging;
        say('');
        if (arranging) {
            lists().forEach(normalize);
            [].forEach.call(document.querySelectorAll('.dash-block'), addBar);
        } else {
            removeBars();
            window.dispatchEvent(new Event('resize'));
        }
    });
    /* the same box the rest of the app's ask() shows; ask() itself only submits a form or follows a link, and this saves in the background */
    function confirmReset(go) {
        if (window.Swal && Swal.fire) {
            Swal.fire({
                title: T('sure'), text: T('resetAsk'), icon: 'warning', showCancelButton: true,
                confirmButtonText: T('yes'), cancelButtonText: T('cancel')
            }).then(function (r) { if (r.isConfirmed) go(); });
        } else if (window.confirm(T('resetAsk'))) {
            go();
        }
    }
    reset.addEventListener('click', function () {
        confirmReset(function () {
            post({ 'do': 'reset' }, function (ok) { if (ok) window.location.reload(); else say(T('failed')); });
        });
    });
})();
</script>
{/literal}
