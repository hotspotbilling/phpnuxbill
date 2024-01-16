<!DOCTYPE html>
<html>

<head>
    <title>{$_title}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="ui/ui/styles/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" type="image/x-icon" href="ui/ui/images/favicon.ico">

    <script type="text/javascript">
        function printpage() {
            window.print();
        }
    </script>
</head>

<body topmargin="0" leftmargin="0" onload="printpage()">
    <div class="row">
        <div class="col-md-12">
            <table width="200">
                <tr>
                    <td>
<pre style="border-style: none; background-color: white;"><b>{Lang::pad($_c['CompanyName'],' ', 2)}</b>
{Lang::pad($_c['address'],' ', 2)}
{Lang::pad($_c['phone'],' ', 2)}
{Lang::pad("", '=')}
{Lang::pads("Invoice", $in['invoice'], ' ')}
{Lang::pads($_L['Date'], $date, ' ')}
{Lang::pads($_L['Sales'], $_admin['fullname'], ' ')}
{Lang::pad("", '=')}
{Lang::pads($_L['Type'], $in['type'], ' ')}
{Lang::pads($_L['Plan_Name'], $in['plan_name'], ' ')}
{Lang::pads($_L['Plan_Price'], Lang::moneyFormat($in['price']), ' ')}
{Lang::pad($in['method'], ' ', 2)}

{Lang::pads($_L['Username'], $in['username'], ' ')}
{Lang::pads($_L['Password'], '**********', ' ')}
{if $in['type'] != 'Balance'}
{Lang::pads($_L['Created_On'], Lang::dateAndTimeFormat($in['recharged_on'],$in['recharged_time']), ' ')}
{Lang::pads($_L['Expires_On'], Lang::dateAndTimeFormat($in['expiration'],$in['time']), ' ')}
{/if}
{Lang::pad("", '=')}
{Lang::pad($_c['note'],' ', 2)}</pre>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <script src="ui/ui/scripts/jquery-1.10.2.js"></script>
    <script src="ui/ui/scripts/bootstrap.min.js"></script>
    {if isset($xfooter)}
        {$xfooter}
    {/if}

</body>

</html>