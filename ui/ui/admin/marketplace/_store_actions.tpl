{* Footer of a store card. Params: x (store item), route, disabled (bool) *}
<button type="button" class="mp-act mp-act--icon" data-details title="{Lang::T('Details')}"><i class="fa fa-info-circle"></i></button>
{if $x.url}
<a href="{$x.url}" target="_blank" rel="noopener noreferrer" class="mp-act" title="{Lang::T('Website')}"><i class="fa fa-globe"></i> {Lang::T('Web')}</a>
{/if}
{if $x.github}
<a href="{$x.github}" target="_blank" rel="noopener noreferrer" class="mp-act" title="{Lang::T('Source')}"><i class="fa fa-github"></i> {Lang::T('Source')}</a>
{/if}
<form method="post" action="{Text::url('marketplace/', $route, '/install/', $x.id)}" class="mp-act-form">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <button type="button" class="mp-act mp-act--primary" {if $disabled}disabled{/if}
        onclick="return ask(this, '{Lang::T('Install this extension? It is checked, then installed; any existing copy is backed up first.')|escape:'javascript'}')"
        title="{Lang::T('Install')}"><i class="fa fa-download"></i> {Lang::T('Install')}</button>
</form>
