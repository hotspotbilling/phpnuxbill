{include file="sections/header.tpl"}

<div class="ls st st-db">
    <form method="post" action="{Text::url('')}settings/dbbackup" class="ls-card st-db__backup">
        <header class="st-card-head">
            <div><h3><i class="fa fa-download" aria-hidden="true"></i> {Lang::T('Backup Database')}</h3>
                <small>{count($tables)} {Lang::T('tables')}</small></div>
            <label class="st-all"><input type="checkbox" id="stAll" checked> {Lang::T('Select all')}</label>
        </header>
        <div class="ls-scroll">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th class="ls-check" aria-label="{Lang::T('Choose')}"></th>
                        <th>{Lang::T('Table Name')}</th>
                        <th class="ls-num">{Lang::T('Rows')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $tables as $tbl}
                    <tr>
                        <td class="ls-check"><input type="checkbox" checked name="tables[]" value="{$tbl['name']|escape}" id="tb_{$tbl['name']|escape}"></td>
                        <td data-label="{Lang::T('Table Name')}"><label for="tb_{$tbl['name']|escape}" class="st-tbl">{$tbl['name']|escape}</label></td>
                        <td data-label="{Lang::T('Rows')}" class="ls-num">{$tbl['rows']|number_format}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        <footer class="st-card-foot">
            <small><i class="fa fa-info-circle" aria-hidden="true"></i> {Lang::T('Don\'t select logs if it failed')}</small>
            <button type="submit" class="ls-btn ls-btn--primary"><i class="fa fa-download" aria-hidden="true"></i> {Lang::T('Download Backup Database')}</button>
        </footer>
    </form>

    <form method="post" action="{Text::url('')}settings/dbrestore" enctype="multipart/form-data" class="ls-card st-db__restore">
        <header class="st-card-head">
            <div><h3><i class="fa fa-upload" aria-hidden="true"></i> {Lang::T('Restore Database')}</h3>
                <small>{Lang::T('A backup file downloaded from this page (.json)')}</small></div>
        </header>
        <div class="st-card-body">
            <div class="rf-note rf-note--warn" role="note">
                <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                <div>{Lang::T('Restoring the database will clean up data and then restore all the data.')}</div>
            </div>
            <input type="file" name="json" accept="application/json" class="form-control" required>
        </div>
        <footer class="st-card-foot">
            <span></span>
            <button type="submit" class="ls-btn ls-btn--primary" onclick="return this.form.reportValidity() && ask(this, '{Lang::T('Restoring the database will clean up data and then restore all the data.')|escape:'javascript'}')">
                <i class="fa fa-upload" aria-hidden="true"></i> {Lang::T('Restore Database')}</button>
        </footer>
    </form>
</div>

<script>
    (function () {
        var all = document.getElementById('stAll');
        var boxes = document.querySelectorAll('input[name="tables[]"]');
        function sync() {
            var n = 0; boxes.forEach(function (b) { if (b.checked) { n++; } });
            all.checked = n === boxes.length; all.indeterminate = n > 0 && n < boxes.length;
        }
        all.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = all.checked; }); });
        // nothing ticked: no empty download
        all.form.addEventListener('submit', function (e) {
            var any = [].some.call(boxes, function (b) { return b.checked; });
            if (!any) {
                e.preventDefault();
                all.setCustomValidity('{Lang::T('Choose at least one table to back up')|escape:'javascript'}');
                all.reportValidity();
            }
        });
        boxes.forEach(function (b) { b.addEventListener('change', function () { all.setCustomValidity(''); }); });
        all.addEventListener('change', function () { all.setCustomValidity(''); });
        boxes.forEach(function (b) { b.addEventListener('change', sync); });
    })();
</script>

{include file="sections/footer.tpl"}
