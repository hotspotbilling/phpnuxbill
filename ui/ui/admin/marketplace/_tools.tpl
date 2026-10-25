{* Secondary row for an installed card. Params: c, route, can_convert (bool), disabled (bool) *}
<div class="mp-card__tools">
    <button type="button" class="mp-mini" data-details><i class="fa fa-info-circle"></i> {Lang::T('Details')}</button>
    {if $c.license.required}
    <button type="button" class="mp-mini" data-license="{$c.slug}"><i class="fa fa-key"></i> {Lang::T('License')}</button>
    {/if}
    {if $c.update.available}
    <form method="post" action="{Text::url('marketplace/', $route, '/update/', $c.slug)}" class="mp-inline">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
        <button type="button" class="mp-mini mp-mini--accent" {if $disabled}disabled{/if}
            onclick="return ask(this, '{Lang::T('Update this extension? The current version is backed up first.')|escape:'javascript'}')">
            <i class="fa fa-arrow-circle-up"></i> {Lang::T('Update')}</button>
    </form>
    {/if}
    {if $can_convert}
    <form method="post" action="{Text::url('marketplace/', $route, '/convert/', $c.slug)}" class="mp-inline">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
        <button type="button" class="mp-mini" {if $disabled}disabled{/if}
            onclick="return ask(this, '{Lang::T('Convert to folder style? A backup is saved first.')|escape:'javascript'}')">
            <i class="fa fa-exchange"></i> {Lang::T('Convert to folder')}</button>
    </form>
    {/if}
</div>
