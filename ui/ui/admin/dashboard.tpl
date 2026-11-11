{include file="sections/header.tpl"}

<div class="dash-toolbar">
    <a href="{Text::url('dashboard')}&refresh=1" class="dash-refresh" title="{Lang::T('Refresh Data')}"
        onclick="this.classList.add('is-loading')">
        <i class="fa fa-refresh"></i><span>{Lang::T('Refresh')}</span>
    </a>
    {include file="admin/dash-arrange.tpl" kind="widgets" btn="dash-refresh"}
</div>

{function showWidget pos=0}
<div class="dash-slot" data-slot="{$pos}">
{foreach $widgets as $w}
{if $w['position'] == $pos}
<div class="dash-block" data-block="{$w['block']|escape}" data-title="{$w['title']|escape}">
{$w['content']}
</div>
{/if}
{/foreach}
</div>
{/function}

{assign dtipe value="dashboard_`$tipeUser`"}

{assign rows explode(".", $_c[$dtipe])}
{assign pos 1}
{foreach $rows as $cols}
{if $cols == 12}
<div class="row">
    <div class="col-md-12">
        {showWidget widgets=$widgets pos=$pos}
    </div>
</div>
{assign pos value=$pos+1}
{else}
{assign colss explode(",", $cols)}
<div class="row">
    {foreach $colss as $c}
    <div class="col-md-{$c}">
        {showWidget widgets=$widgets pos=$pos}
    </div>
    {assign pos value=$pos+1}
    {/foreach}
</div>
{/if}
{/foreach}

{include file="admin/update-notice.tpl"}

{include file="sections/footer.tpl"}