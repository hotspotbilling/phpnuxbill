<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$_title}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{$app_url}/ui/ui/images/favicon.ico">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 10px;
        }

        .invoice {
            width: 100%;
            max-width: 70mm;
            /* Maximum width for thermal printing */
            background: white;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }

        .details {
            margin-bottom: 10px;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 5px;
            text-align: left;
        }

        .details div {
            margin: 5px 0;
            font-weight: bold;
            /* Bold text for details */
            color: black;
            /* Ensure text is black */
        }

        .invoice-info {
            margin: 10px 0;
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-info th,
        .invoice-info td {
            padding: 5px;
            text-align: left;
            color: black;
            /* Ensure text is black */
            font-weight: bold;
            /* Bold text for table content */
        }

        .invoice-info th {
            background-color: #0056b3;
            color: white;
        }

        .footer {
            margin-top: 10px;
            text-align: left;
            /* Align footer text to left */
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 12px;
            }

            .invoice {
                width: 70mm;
                /* Fixed width for thermal printer */
                padding: 5px;
                /* Reduced padding for print */
                box-shadow: none;
                /* Remove shadow for clearer print */
                border: none;
                /* Remove border for print */
            }

            .details {
                margin-bottom: 5px;
                text-align: left;
            }

            .invoice-info {
                border-collapse: collapse;
            }

            .invoice-info th,
            .invoice-info td {
                padding: 5px;
                /* Reduced padding */
                text-align: left;
                font-weight: bold;
                /* Bold text for print */
                color: black;
                /* Ensure text is black */
                border: none;
                /* Remove borders for print */
            }

            hr {
                border: 1px solid #000;
                /* Darker line for print */
            }

            .btn {
                display: none;
                /* Hide buttons when printing */
            }
        }
    </style>
    <script type="text/javascript">
        function printpage() {
            window.print();
        }
    </script>
</head>

<body {if !$nuxprint} onload="printpage()" {/if}>
    <div class="container">
        <div class="invoice">
            {if $content}
                <pre style="border-style: none; background-color: white;">{$content}</pre>
            {else}
                <div class="header">
                    <h1>{Lang::pad($_c['CompanyName'], ' ', 2)}</h1>
                    <p>{Lang::pad($_c['address'], ' ', 2)} | {Lang::pad($_c['phone'], ' ', 2)}</p>
                </div>
                <div class="details">
                    <div><strong>{Lang::pad(Lang::T('Invoice'), ' ', 2)}:</strong> {$in['invoice']}</div>
                    <div><strong>{Lang::pad(Lang::T('Date'), ' ', 2)}:</strong> {$date}</div>
                    <div><strong>{Lang::pad(Lang::T('Sales'), ' ', 2)}:</strong> {Lang::pad($_admin['fullname'], ' ', 2)}
                    </div>
                </div>

                <table class="invoice-info">
                    <tr>
                        <th>{Lang::pad(Lang::T('Type'), ' ', 2)}</th>
                        <td>{$in['type']}</td>
                    </tr>
                    <tr>
                        <th>{Lang::pad(Lang::T('Package Name'), ' ', 2)}</th>
                        <td>{$in['plan_name']}</td>
                    </tr>
                    <tr>
                        <th>{Lang::pad(Lang::T('Package Price'), ' ', 2)}</th>
                        <td>{Lang::moneyFormat($in['price'])}</td>
                    </tr>
                    <tr>
                        <th>{Lang::pad(Lang::T('Username'), ' ', 2)}</th>
                        <td>{$in['username']}</td>
                    </tr>
                    <tr>
                        <th>{Lang::pad(Lang::T('Password'), ' ', 2)}</th>
                        <td>**********</td>
                    </tr>
                    <tr>
                        <th>{Lang::pad(Lang::T('Payment Method'), ' ', 2)}</th>
                        <td>{$in['method']}</td>
                    </tr>
                    {if $in['type'] != 'Balance'}
                        <tr>
                            <th>{Lang::pad(Lang::T('Created On'), ' ', 2)}</th>
                            <td>{Lang::dateAndTimeFormat($in['recharged_on'], $in['recharged_time'])}</td>
                        </tr>
                        <tr>
                            <th>{Lang::pad(Lang::T('Expires On'), ' ', 2)}</th>
                            <td>{Lang::dateAndTimeFormat($in['expiration'], $in['time'])}</td>
                        </tr>
                    {/if}
                </table>

                <hr style="border: 2px solid #0056b3; margin-top: 10px;">

                <div class="footer">
                    <p>{Lang::pad($_c['note'], ' ', 2)}</p>
                    {if $nuxprint}
                        <a href="{$nuxprint}" class="btn btn-success" name="nux" value="print">
                            <i class="glyphicon glyphicon-print"></i> Nux Print
                        </a>
                    {/if}
                </div>
            {/if}
        </div>
    </div>

    <script src="{$app_url}/ui/ui/scripts/jquery.min.js"></script>
    <script src="{$app_url}/ui/ui/scripts/bootstrap.min.js"></script>
    {if isset($xfooter)} {$xfooter} {/if}
</body>

</html>