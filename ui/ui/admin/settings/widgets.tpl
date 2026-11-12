{include file="sections/header.tpl"}
{* Dashboard widgets per user type. Forms, field names and routes are what widgets.php reads: orders[] + id[] per area
   (widgets/pos), and dashboard + save=struct for the structure. *}

<div class="ls st">
    <div class="ls-tabs" role="tablist" aria-label="{Lang::T('User type')}">
        {foreach ['Admin', 'Agent', 'Sales', 'Customer'] as $t}
        <a class="ls-tab{if $tipeUser == $t} is-on{/if}" role="tab" aria-selected="{if $tipeUser == $t}true{else}false{/if}"
            href="{Text::url('widgets&user=', $t)}">{Lang::T($t)}</a>
        {/foreach}
    </div>

    {function showWidget pos=0}
    <form method="post" action="{Text::url('widgets/pos/', '&user=', $tipeUser)}" class="ls-card st-area">
        <header class="st-card-head">
            <div><h3><span class="st-area__no">{$pos}</span> {Lang::T("Area")} {$pos}</h3></div>
            <a href="{Text::url('widgets/add/', $pos, '&user=', $tipeUser)}" class="ls-btn ls-btn--sm"><i class="fa fa-plus" aria-hidden="true"></i> {Lang::T("Add widget")}</a>
        </header>
        {$n = 0}
        <ul class="st-wlist">
            {foreach $widgets as $w}
            {if $w['position'] == $pos}
            {$n = $n + 1}
            <li class="st-w{if !$w['enabled']} is-off{/if}">
                <input type="hidden" name="id[]" value="{$w['id']}">
                <label class="st-w__seq" title="{Lang::T('Sequence')}">
                    <span class="sr-only">{Lang::T('Sequence')}</span>
                    <input type="number" name="orders[]" value="{$w['orders']}" class="form-control" aria-label="{Lang::T('Sequence')}: {$w['title']|escape}">
                </label>
                <div class="st-w__text">
                    <b>{$w['title']|escape}</b>
                    <small>{ucwords(str_replace('.php', '', str_replace('_', ' ', $w['widget'])))|escape}</small>
                </div>
                {if !$w['enabled']}<span class="ls-pill ls-pill--inactive">{Lang::T('Disabled')}</span>{/if}
                <span class="st-w__act">
                    <a href="{Text::url('widgets/edit/', $w['id'], '&user=', $tipeUser)}" class="ls-btn ls-btn--sm">{Lang::T("Edit")}</a>
                    <a href="{Text::url('widgets/delete/', $w['id'], '&user=', $tipeUser)}" onclick="var a = this; return ask(a, '{Lang::T("Delete this widget?")}', function () { window.location.href = a.href; })"
                        class="ls-btn ls-btn--sm ls-btn--icon ls-btn--danger" title="{Lang::T('Delete')}" aria-label="{Lang::T('Delete')}: {$w['title']|escape}"><i class="fa fa-trash" aria-hidden="true"></i></a>
                </span>
            </li>
            {/if}
            {/foreach}
            {if $n == 0}<li class="st-w st-w--none">{Lang::T('No widget in this area yet')}</li>{/if}
        </ul>
        {if $n > 0}
        <footer class="st-card-foot">
            <small>{Lang::T('Lower numbers show first')}</small>
            <button type="submit" class="ls-btn"><i class="fa fa-sort-numeric-asc" aria-hidden="true"></i> {Lang::T("Save sequence")}</button>
        </footer>
        {/if}
    </form>
    {/function}

    {assign dtipe value="dashboard_`$tipeUser`"}
    <div class="st-widgets">
        <aside class="ls-card st-struct">
            <header class="st-card-head">
                <div><h3><i class="fa fa-th-large" aria-hidden="true"></i> {Lang::T("Dashboard Structure")}</h3>
                    <small>{Lang::T('Click an area to add a widget to it')}</small></div>
            </header>
            <div class="st-card-body">
                <div class="st-layout">
                    {assign rows explode(".", $_c[$dtipe])}
                    {assign pos 1}
                    {foreach $rows as $cols}
                    <div class="st-layout__row">
                        {foreach explode(",", $cols) as $c}
                        <a href="{Text::url('widgets/add/', $pos, '&user=', $tipeUser)}" class="st-layout__cell" style="flex: {$c|intval} 1 0" title="{Lang::T('Add widget')}: {Lang::T('Area')} {$pos}">{$pos}</a>
                        {assign pos value=$pos+1}
                        {/foreach}
                    </div>
                    {/foreach}
                </div>
            </div>
            <form method="post" class="st-card-foot st-struct__form">
                <label for="stStruct" class="mc-label">{Lang::T("Structure")}
                    <a href="{$app_url}/docs/#Dashboard%20Structure" target="_blank" rel="noopener">{Lang::T('How it works')}</a></label>
                <div class="st-struct__row">
                    <input type="text" id="stStruct" name="dashboard" value="{$_c[$dtipe]|escape}" class="form-control" placeholder="12.7,5.12">
                    <button type="submit" class="ls-btn ls-btn--primary" name="save" value="struct">{Lang::T("Save")}</button>
                </div>
            </form>
        </aside>

        <div class="st-areas">
            <div class="st-areas__col">
                {for $pos=1 to $max}
                {if $pos%2 != 0}{showWidget widgets=$widgets pos=$pos}{/if}
                {/for}
            </div>
            <div class="st-areas__col">
                {for $pos=1 to $max}
                {if $pos%2 == 0}{showWidget widgets=$widgets pos=$pos}{/if}
                {/for}
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
