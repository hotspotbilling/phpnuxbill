<script>
    (function () {
        var root = document.querySelector('.mp-wrap');
        if (!root) return;
        var cards = root.querySelectorAll('.mp-card[data-search]');
        var search = root.querySelector('[data-mp-search]');
        var filters = root.querySelectorAll('.mp-filters [data-filter]');
        var empty = root.querySelector('#mp-noresults');
        var state = { filter: 'all', query: '' };

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
            // hide section headers whose grid has no visible cards
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
        var toggle = root.querySelector('#toggle-upload');
        if (toggle) {
            toggle.addEventListener('click', function () {
                var panel = document.getElementById('upload-panel');
                if (panel) panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
            });
        }
    })();
</script>
