<!DOCTYPE html>
<html>

<head>
    <title>{$_title}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/x-icon" href="ui/ui/images/favicon.ico">
    <style>
        .ukuran {
            size: A4;
        }

        body,
        td,
        th {
            font-size: 12px;
            font-family: Segoe, "Segoe UI", "DejaVu Sans", "Trebuchet MS", Verdana, sans-serif;
        }

        page[size="A4"] {
            background: white;
            width: 21cm;
            height: 29.7cm;
            display: block;
            margin: 0 auto;
            margin-bottom: 0.5cm;

            html,
            body {
                width: 210mm;
                height: 297mm;
            }
        }

        @media print {
            body {
                size: auto;
                margin: 0;
                box-shadow: 0;
            }

            page[size="A4"] {
                margin: 0;
                size: auto;
                box-shadow: 0;
            }

            .page-break {
                display: block;
                page-break-before: always;
            }

            .no-print,
            .no-print * {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <page size="A4">
        <form method="post" action="{$_url}plan/print-voucher/" class="no-print">
            <table width="100%" border="0" cellspacing="0" cellpadding="1" class="btn btn-default btn-sm">
                <tr>
                    <td>From ID &gt; <input type="text" name="from_id" style="width:40px" value="{$from_id}"> limit
                        <input type="text" name="limit" style="width:40px" value="{$limit}"></td>
                    <td>Voucher PerLine <input type="text" style="width:40px" name="vpl" value="{$vpl}">
                        vouchers</td>
                    <td>PageBreak after <input type="text" style="width:40px" name="pagebreak" value="{$pagebreak}">
                        vouchers</td>
                    <td>Plans <select id="plan_id" name="planid" style="width:50px">
                            <option value="0">--all--</option>
                            {foreach $plans as $plan}
                                <option value="{$plan['id']}" {if $plan['id']==$planid}selected{/if}>{$plan['name_plan']}
                                </option>
                            {/foreach}
                        </select></td>
                    <td><button type="submit">submit</button></td>
                </tr>
            </table>
            <hr>
            <center><button type="button" onclick="window.print()"
                    class="btn btn-default btn-sm no-print">{Lang::T('Click Here to Print')}</button><br>
                {Lang::T('Print side by side, it will easy to cut')}<br>
                show {$v|@count} vouchers from {$vc} vouchers<br>
                from ID {$v[0]['id']} limit {$limit} vouchers
            </center>
        </form>
        <div id="printable" align="center">
            <hr>
            {$n = 1}
            {foreach $voucher as $vs}
                {$jml = $jml + 1}
                {if $n == 1}
                    <table>
                        <tr>
                        {/if}
                        <td>{$vs}</td>
                        {if $n == $vpl}
                    </table>
                    {$n = 1}
                {else}
                    {$n = $n + 1}
                {/if}


                {if $jml == $pagebreak}
                    {$jml = 0}
                    <!-- pageBreak -->
                    <div class="page-break">
                        <div class="no-print" style="background-color: #E91E63; color:#FFF;" align="center">-- pageBreak --
                            <hr>
                        </div>
                    </div>
                {/if}
            {/foreach}
        </div>
    </page>
    <script src="ui/ui/scripts/jquery-1.10.2.js"></script>
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