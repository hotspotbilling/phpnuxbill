{include file="sections/header.tpl"}

<div class="rb">
    <div class="rb-top">
        <a href="{Text::url('community')}" class="rb-btn"><i class="fa fa-arrow-left" aria-hidden="true"></i> {Lang::T('Community')}</a>
    </div>

    <div class="rb-card rb-pad">
        <div class="rb-intro">
            <i class="fa fa-history" aria-hidden="true"></i>
            <div>
                <b>{Lang::T('Go back to an earlier version')}</b>
                <p>{Lang::T('You are running')} <strong>{$current_version|escape}</strong>. {Lang::T('Choose a version below. The updater opens with its address filled in and nothing is downloaded until you press the button there.')}</p>
                <p><strong>{Lang::T('A rollback restores the program files only.')}</strong> {Lang::T('Database changes are not undone. Back up your database first.')}</p>
            </div>
        </div>
    </div>

    <div class="rb-card">
        <div class="rb-bar">
            <div class="rb-tabs" role="tablist">
                <button type="button" class="rb-tab" role="tab" id="rbTabMaster" aria-selected="true" data-target="rbMaster">Master<small>{count($masters)}</small></button>
                <button type="button" class="rb-tab" role="tab" id="rbTabDev" aria-selected="false" data-target="rbDev">Development<small>{count($devs)}</small></button>
            </div>
            <input type="search" autocomplete="off" class="rb-search" id="rbSearch" placeholder="{Lang::T('Search')}..." aria-label="{Lang::T('Search')}">
        </div>

        {foreach from=['rbMaster' => $masters, 'rbDev' => $devs] key=listId item=commits}
            <ul class="rb-list" id="{$listId}" role="tabpanel" {if $listId eq 'rbDev'}hidden{/if}>
                {foreach $commits as $c}
                    <li class="rb-row" data-text="{($c.subject|cat:' '|cat:$c.body|cat:' '|cat:$c.short|cat:' '|cat:$c.author)|lower|escape}">
                        <div>
                            <div class="rb-subject">{$c.subject|escape}</div>
                            {if $c.body neq ''}<p class="rb-body">{$c.body|escape}</p>{/if}
                            <div class="rb-meta">
                                <span>{Lang::dateTimeFormat($c.date)}</span>
                                {if $c.author neq ''}<span>{$c.author|escape}</span>{/if}
                                <a class="rb-sha" href="https://github.com/hotspotbilling/phpnuxbill/commit/{$c.sha}" target="_blank" rel="noopener" title="{Lang::T('View on GitHub')}">{$c.short}</a>
                            </div>
                        </div>
                        <a class="rb-btn" href="{$app_url}/update.php?older=1&amp;update_url=https://github.com/hotspotbilling/phpnuxbill/archive/{$c.sha}.zip"><i class="fa fa-history" aria-hidden="true"></i> {Lang::T('Roll back to this')}</a>
                    </li>
                {foreachelse}
                    <li class="rb-empty"><b>{Lang::T('No versions to show')}</b>{Lang::T('GitHub could not be reached or has limited requests for now. Try again in a few minutes.')}</li>
                {/foreach}
            </ul>
        {/foreach}
        <div class="rb-empty" id="rbNone" hidden><b>{Lang::T('No version matches your search')}</b></div>
    </div>
</div>

{literal}
<script>
window.addEventListener('DOMContentLoaded', function () {
    var tabs = document.querySelectorAll('.rb-tab');
    var search = document.getElementById('rbSearch');
    var none = document.getElementById('rbNone');
    function current() {
        for (var i = 0; i < tabs.length; i++) { if (tabs[i].getAttribute('aria-selected') === 'true') { return document.getElementById(tabs[i].dataset.target); } }
    }
    function filter() {
        var q = search.value.trim().toLowerCase(), list = current(), shown = 0;
        list.querySelectorAll('.rb-row').forEach(function (row) {
            var ok = !q || (row.dataset.text || '').indexOf(q) !== -1;
            row.hidden = !ok;
            if (ok) { shown++; }
        });
        none.hidden = shown > 0 || !list.querySelector('.rb-row');
    }
    tabs.forEach(function (t) {
        t.addEventListener('click', function () {
            tabs.forEach(function (o) {
                var on = o === t;
                o.setAttribute('aria-selected', on ? 'true' : 'false');
                document.getElementById(o.dataset.target).hidden = !on;
            });
            filter();
        });
    });
    search.addEventListener('input', filter);
});
</script>
{/literal}
{include file="sections/footer.tpl"}
