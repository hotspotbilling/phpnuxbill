<option value="">Select Plans</option>
{foreach $d as $ds}
	<option value="{$ds['id']}">{$ds['name_plan']} &bull; {Lang::moneyFormat($ds['price'])}</option>
{/foreach}