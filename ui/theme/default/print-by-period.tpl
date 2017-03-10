<!DOCTYPE html>
<html>
<head>
    <title>{$_title}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="{$_theme}/styles/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" type="image/x-icon" href="{$_theme}/images/favicon.ico">

    <style type="text/css">
        @media print
        {
            .no-print, .no-print *
            {
                display: none !important;
            }
        }
    </style>
</head>

<body>
<div class="row">
    <div class="col-md-12">
        <div id="printable">
            <h4>{$_L['All_Transactions_at_Date']}: {date( $_c['date_format'], strtotime($fdate))} - {date( $_c['date_format'], strtotime($tdate))}</h4>
            <table class="table table-condensed table-bordered" style="background: #ffffff">
                <th class="text-center">{$_L['Username']}</th>
                <th class="text-center">{$_L['Plan_Name']}</th>
                <th class="text-center">{$_L['Type']}</th>
                <th class="text-center">{$_L['Plan_Price']}</th>
                <th class="text-center">{$_L['Created_On']}</th>
				<th class="text-center">{$_L['Expires_On']}</th>
				<th class="text-center">{$_L['Method']}</th>
				<th class="text-center">{$_L['Routers']}</th>
                {foreach $d as $ds}
                    <tr>
						<td>{$ds['username']}</td>
						<td class="text-center">{$ds['plan_name']}</td>
						<td class="text-center">{$ds['type']}</td>
						<td class="text-right">{$_c['currency_code']} {number_format($ds['price'],2,$_c['dec_point'],$_c['thousands_sep'])}</td>
						<td>{date($_c['date_format'], strtotime($ds['recharged_on']))} {$ds['time']}</td>
						<td>{date($_c['date_format'], strtotime($ds['expiration']))} {$ds['time']}</td>
						<td class="text-center">{$ds['method']}</td>
						<td class="text-center">{$ds['routers']}</td>
                    </tr>
                {/foreach}
            </table>
			<div class="clearfix text-right total-sum mb10">
				<h4 class="text-uppercase text-bold">{$_L['Total_Income']}:</h4>
				<h3 class="sum">{$_c['currency_code']} {number_format($dr,2,$_c['dec_point'],$_c['thousands_sep'])}</h3>
			</div>
        </div>
        <button type="button" id="actprint" class="btn btn-default btn-sm no-print">{$_L['Click_Here_to_Print']}</button>
    </div>
</div>
<script src="{$_theme}/scripts/jquery-1.10.2.js"></script>
<script src="{$_theme}/scripts/bootstrap.min.js"></script>
{if isset($xfooter)}
    {$xfooter}
{/if}
<script>
    jQuery(document).ready(function() {
        // initiate layout and plugins
        $("#actprint").click(function() {
            window.print();
            return false;
        });
    });
</script>

</body>
</html>