{* Status chips for an installed extension card. Param: c *}
<span class="mp-chip" title="{if $c.style eq 'folder'}{Lang::T('Folder style')}{else}{Lang::T('Single file')}{/if}"><i class="fa fa-{if $c.style eq 'folder'}folder{else}file{/if}"></i>&nbsp;{if $c.style eq 'folder'}{Lang::T('Folder')}{else}{Lang::T('Flat')}{/if}</span>
{if $c.health.status eq 'error'}
<span class="mp-status mp-status--update" title="{$c.health.text|escape}"><i class="fa fa-exclamation-triangle"></i>&nbsp;{Lang::T('Error')}</span>
{elseif $c.health.status eq 'warn'}
<span class="mp-chip mp-chip--warn" title="{$c.health.text|escape}"><i class="fa fa-exclamation-circle"></i>&nbsp;{Lang::T('Check')}</span>
{/if}
{if $c.update.available}
<span class="mp-status mp-status--update" title="{$c.update.current} &rarr; {$c.update.latest}"><i class="fa fa-arrow-circle-up"></i>&nbsp;{Lang::T('Update')} {$c.update.latest}</span>
{/if}
{if $c.license.required}
{if $c.license.blocked}
<span class="mp-chip mp-chip--warn" title="{Lang::T('A license key is required')}"><i class="fa fa-key"></i>&nbsp;{Lang::T('License needed')}</span>
{else}
<span class="mp-chip mp-chip--ok"><i class="fa fa-key"></i>&nbsp;{Lang::T('Licensed')}</span>
{/if}
{/if}
