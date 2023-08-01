<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{$_title} - {$_L['Login']}</title>
    <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

    <link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="ui/ui/styles/adminlte.min.css">

</head>

<body>
    <div class="container">
        <div class="hidden-xs" style="height:150px"></div>
        <div class="form-head mb20">
            <h1 class="site-logo h2 mb5 mt5 text-center text-uppercase text-bold"
                style="text-shadow: 2px 2px 4px #757575;">{$_c['CompanyName']}</h1>
            <hr>
        </div>
        {if isset($notify)}
            <div class="row">
                <div class="col-sm-6 col-sm-offset-3">
                    {$notify}
                </div>
            </div>
        {/if}
        <div class="row">
            <div class="col-sm-6 col-sm-offset-1">
                <div class="panel panel-info">
                    <div class="panel-heading">{$_L['Announcement']}</div>
                    <div class="panel-body">
                        {include file="$_path/../pages/Announcement.html"}
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">{$_L['Sign_In_Member']}</div>
                    <div class="panel-body">
                            <form action="{$_url}login/post" method="post">
                                <div class="form-group">
                                    <label>{$_L['Phone_Number']}</label>
                                    <input type="text" class="form-control" name="username"
                                        placeholder="{$_L['Phone_Number']}">
                                </div>
                                <div class="form-group">
                                    <label>{$_L['Password']}</label>
                                    <input type="password" class="form-control" name="password"
                                        placeholder="{$_L['Password']}">
                                </div>

                                <div class="clearfix hidden">
                                    <div class="ui-checkbox ui-checkbox-primary right">
                                        <label>
                                            <input type="checkbox">
                                            <span>Remember me</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="btn-group btn-group-justified mb15">
                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">{$_L['Login']}</button>
                                    </div>
                                    <div class="btn-group">
                                        <a href="{$_url}register" class="btn btn-success">{$_L['Register']}</a>
                                    </div>
                                </div>
                            </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="ui/ui/scripts/vendors.js"></script>
</body>

</html>