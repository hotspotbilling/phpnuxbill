<option value=''>{$_L['Select_Routers']}</option>
{foreach $d as $ds}
    {if $_c['radius_enable']}
        <option value="radius">Radius</option>
    {/if}
	<option value="{$ds['name']}">{$ds['name']}</option>
{/foreach}