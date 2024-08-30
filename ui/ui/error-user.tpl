<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{ucwords(Lang::T("Error"))} - {$_c['CompanyName']}</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="ui/ui/styles/modern-AdminLTE.min.css">
</head>

<body class="hold-transition lockscreen">
    <div class="lockscreen-wrapper">
        <div class="panel panel-danger">
            <div class="panel-heading">{ucwords(Lang::T("Internal Error"))}</div>
            <div class="panel-body">
                {Lang::T("Sorry, the software failed to process the request, if it still happening, please tell")}
                {$_c['CompanyName']}
            </div>
            <div class="panel-footer">
                <a href="{$url}" id="button" class="btn btn-danger btn-block">{Lang::T('Try Again')}</a>
            </div>
        </div>
        <div class="lockscreen-footer text-center">
            {$_c['CompanyName']}
        </div>
    </div>

    {if $_c['tawkto'] != ''}
        <!--Start of Tawk.to Script-->
        <script type="text/javascript">
            var Tawk_API = Tawk_API || {},
                Tawk_LoadStart = new Date();
            (function() {
                var s1 = document.createElement("script"),
                    s0 = document.getElementsByTagName("script")[0];
                s1.async = true;
                s1.src='https://embed.tawk.to/{$_c['tawkto']}';
                s1.charset = 'UTF-8';
                s1.setAttribute('crossorigin', '*');
                s0.parentNode.insertBefore(s1, s0);
            })();
        </script>
        <!--End of Tawk.to Script-->
    {/if}

</body>

</html>