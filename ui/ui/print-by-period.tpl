<!DOCTYPE html>
<html>
<head>
    <title>{$_title}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="ui/ui/styles/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" type="image/x-icon" href="ui/ui/images/favicon.ico">

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
            <h4>{Lang::T('All Transactions at Date')}: {date( $_c['date_format'], strtotime($fdate))} - {date( $_c['date_format'], strtotime($tdate))}</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-condensed table-striped " style="background: #ffffff">
                    <th class="text-center">{Lang::T('Username')}</th>
                    <th class="text-center">{Lang::T('Plan Name')}</th>
                    <th class="text-center">{Lang::T('Type')}</th>
                    <th class="text-center">{Lang::T('Plan Price')}</th>
                    <th class="text-center">{Lang::T('Created On')}</th>
                    <th class="text-center">{Lang::T('Expires On')}</th>
                    <th class="text-center">{Lang::T('Method')}</th>
                    <th class="text-center">{Lang::T('Routers')}</th>
                    {foreach $d as $ds}
                        <tr>
                            <td>{$ds['username']}</td>
                            <td class="text-center">{$ds['plan_name']}</td>
                            <td class="text-center">{$ds['type']}</td>
                            <td class="text-right">{Lang::moneyFormat($ds['price'])}</td>
                            <td>{Lang::dateAndTimeFormat($ds['recharged_on'],$ds['recharged_time'])}</td>
                            <td>{Lang::dateAndTimeFormat($ds['expiration'],$ds['time'])}</td>
                            <td class="text-center">{$ds['method']}</td>
                            <td class="text-center">{$ds['routers']}</td>
                        </tr>
                    {/foreach}
                </table>
            </div>
			<div class="clearfix text-right total-sum mb10">
				<h4 class="text-uppercase text-bold">{Lang::T('Total Income')}:</h4>
				<h3 class="sum">{Lang::moneyFormat($dr)}</h3>
			</div>
        </div>
        <button type="button" id="actprint" class="btn btn-default btn-sm no-print">{Lang::T('Click Here to Print')}</button>
    </div>
</div>
<script src="ui/ui/scripts/jquery-1.10.2.js"></script>
<script src="ui/ui/scripts/bootstrap.min.js"></script>
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