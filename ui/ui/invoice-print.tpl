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

<body topmargin="0" leftmargin="0" {if !$nuxprint} onload="printpage()" {/if}>
    <div class="row">
        <div class="col-md-6">
            <table width="200">
                <tr>
                    <td>
                        {if $content}
                        <pre style="border-style: none; background-color: white;">{$content}</pre>{else}
                            <pre style="border-style: none; background-color: white;"><b>{Lang::pad($_c['CompanyName'],' ', 2)}</b>
                    {Lang::pad($_c['address'],' ', 2)}
                    {Lang::pad($_c['phone'],' ', 2)}
                    {Lang::pad("", '=')}
                    {Lang::pads("Invoice", $in['invoice'], ' ')}
                    {Lang::pads(Lang::T('Date'), $date, ' ')}
                    {Lang::pads(Lang::T('Sales'), $_admin['fullname'], ' ')}
                    {Lang::pad("", '=')}
                    {Lang::pads(Lang::T('Type'), $in['type'], ' ')}
                    {Lang::pads(Lang::T('Plan Name'), $in['plan_name'], ' ')}
                    {Lang::pads(Lang::T('Plan Price'), Lang::moneyFormat($in['price']), ' ')}
                    {Lang::pad($in['method'], ' ', 2)}

                    {Lang::pads(Lang::T('Username'), $in['username'], ' ')}
                    {Lang::pads(Lang::T('Password'), '**********', ' ')}
                    {if $in['type'] != 'Balance'}
                                    {Lang::pads(Lang::T('Created On'), Lang::dateAndTimeFormat($in['recharged_on'],$in['recharged_time']), ' ')}
                                    {Lang::pads(Lang::T('Expires On'), Lang::dateAndTimeFormat($in['expiration'],$in['time']), ' ')}
                    {/if}
                    {Lang::pad("", '=')}
                    {Lang::pad($_c['note'],' ', 2)}</pre>
                        {/if}
                    </td>
                </tr>
            </table>
            {if $nuxprint}
                <a href="{$nuxprint}" class="btn btn-success text-black btn-sm" name="nux" value="print">
                    <i class="glyphicon glyphicon-print"></i>
                    Nux Print
                    <i class="glyphicon glyphicon-phone"></i>
                </a>
                <br>
                <iframe src="{$nuxprint}" style="height: 2px;"><iframe>
            {/if}
        </div>
    </div>

    <script src="ui/ui/scripts/jquery.min.js"></script>
    <script src="ui/ui/scripts/bootstrap.min.js"></script>
    {if isset($xfooter)}
        {$xfooter}
    {/if}


</body>

</html>