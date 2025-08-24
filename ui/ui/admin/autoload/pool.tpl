<option value=''>{Lang::T('Select Pool')}</option>
{foreach $d as $ds}
<option value="{$ds['pool_name']}">{$ds['pool_name']}{if $routers==''} - {$ds['routers']}{/if}</option>
{/foreach}