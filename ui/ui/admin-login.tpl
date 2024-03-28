<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{Lang::T('Login')} - {$_c['CompanyName']}</title>
    <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

    <link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="ui/ui/styles/modern-AdminLTE.min.css">


</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            {$_c['CompanyName']}
        </div>
        <div class="login-box-body">
            <p class="login-box-msg">{Lang::T('Enter Admin Area')}</p>
            {if isset($notify)}
                {$notify}
            {/if}
            <form action="{$_url}admin/post" method="post">
                <div class="form-group has-feedback">
                    <input type="text" required class="form-control" name="username" placeholder="{Lang::T('Username')}">
                    <span class="glyphicon glyphicon-user form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <input type="password" required class="form-control" name="password" placeholder="{Lang::T('Password')}">
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-flat">{Lang::T('Login')}</button>
            </form>
        </div>
    </div>
</body>

</html>