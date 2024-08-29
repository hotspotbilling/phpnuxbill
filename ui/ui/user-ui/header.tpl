<!DOCTYPE html>
<html lang="en" class="has-aside-left has-aside-mobile-transition has-navbar-fixed-top has-aside-expanded">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$_title} - {$_c['CompanyName']}</title>
    <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

    <link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">

    <link rel="stylesheet" href="ui/ui/fonts/ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="ui/ui/styles/modern-AdminLTE.min.css">
    <link rel="stylesheet" href="ui/ui/styles/sweetalert2.min.css" />
    <script src="ui/ui/scripts/sweetalert2.all.min.js"></script>


    <style>
        /* New Customize Interface Start Here */
        body {
            position: relative;
            z-index: 1;
            background-color: rgb(241 245 249);
            font-family: Satoshi, sans-serif;
            font-size: 1rem;
            line-height: 1.5rem;
            font-weight: 400;
            color: rgb(100 116 139);
        }

        .modern-skin-dark .main-header .logo {
            background-color: rgb(28 36 52);
            color: #fff;
        }

        .modern-skin-dark .main-header .navbar {
            background: rgb(28 36 52);
        }

        .modern-skin-dark .main-sidebar .sidebar {
            background-color: rgb(28 36 52);
            bottom: 0;
        }

        .modern-skin-dark .main-sidebar {
            background-color: rgb(28 36 52);
            box-shadow: 0 0 5px rgba(0, 0, 0, .3);
        }

        .modern-skin-dark .main-header .navbar>a:focus,
        .modern-skin-dark .main-header .navbar>a:active,
        .modern-skin-dark .main-header .navbar>a:visited,
        .modern-skin-dark .main-header .navbar>a:hover {
            background-color: rgb(28 36 52);
        }

        .sidebar-menu li>a {
            position: relative;
            background-color: rgb(28 36 52);
        }

        .sidebar-menu li:focus,
        .sidebar-menu li :hover {
            color: #10d435;

        }

        .modern-skin-dark .main-sidebar .sidebar .sidebar-menu li.active a {
            background-color: #2e298e;
            border-radius: 5px;
            margin: 10px;

        }

        .modern-skin-dark .main-sidebar .sidebar .sidebar-menu {
            background-color: rgb(28 36 52);
        }

        .modern-skin-dark .main-sidebar .sidebar .sidebar-menu li .treeview-menu li.active a {
            background-color: transparent !important;
            color: rgb(84, 131, 227);
        }

        .modern-skin-dark .main-sidebar .sidebar .sidebar-menu li .treeview-menu li>a {
            background-color: transparent !important;
            padding: 10px 5px 5px 15px;
        }

        .modern-skin-dark .main-sidebar .sidebar .sidebar-menu li .treeview-menu {
            padding-left: 0;
            border-left: 3px solid #10d435;
        }

        .content-header {
            list-style-type: none;
            padding: 15px;
            background-color: #f6f9fc;

        }

        @media (max-width: 767px) {
            .content {
                padding: 0 15px !important;
                background-color: #f6f9fc;
            }
        }

        .content {
            padding: 25px !important;
            background-color: #f6f9fc;

        }

        .content-wrapper,
        .right-side {
            min-height: 100%;
            background-color: #f6f9fc;
            z-index: 800;
        }

        .main-footer {
            background: rgb(28 36 52);
            padding: 15px;
            color: rgb(100 116 139);
            border-top: 1px solid #d2d6de;
        }

        .panel-primary {
            border-color: #333;
        }

        .panel {
            margin-bottom: 20px;
            background-color: #fff;
            border: 0px solid transparent;
            border-radius: 21px;
            -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
            box-shadow: 0px 4px 30px rgba(221, 224, 255, .54);
        }

        .panel-primary>.panel-heading {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .panel-primary>.panel-heading {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .panel-heading {
            padding: 10px 15px;
            border-bottom: 1px solid transparent;
            border-top-right-radius: 3px;
            border-top-left-radius: 3px;
        }

        .box.box-solid.box-primary>.box-header {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .box-body {
            border-radius: 21px;
            padding: 10px;
        }

        .box.box-solid.box-primary {
            background-color: #fff;
            border: 0px solid transparent;
            border-radius: 21px;
            -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
            box-shadow: 0px 4px 30px rgba(221, 224, 255, .54);
        }

        .content .row [class*=col-] .box {
           -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
            box-shadow: 4px 4px 30px rgba(221, 224, 255, .54);
            -moz-box-shadow: 0 1px 1px rgba(0, 0, 0, .1);
            -ms-box-shadow: 0 1px 1px rgba(0, 0, 0, .1);
            -webkit-border-radius: 1px !important;
            -moz-border-radius: 1px !important;
            -ms-border-radius: 1px !important;
            border-radius: 15px !important;
            border-color: rgba(221, 224, 255, .54);
        }

        .box.box-solid.box-info>.box-header {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .box-header {
            color: inherit;
            display: block;
            padding: 10px;
            position: relative;
            border-color: transparent;
            border-radius: 25px;
        }

        .box.box-solid.box-default>.box-header {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .box.box-solid.box-success>.box-header {
            color: inherit;
            background: transparent;
            background-color: transparent;
        }
        .box.box-solid.box-primary>.box-header {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }
        .box.box-solid.box-info>.box-header {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }
        .box.box-solid.box-danger>.box-header {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .box.box-solid.box-warning>.box-header {
            color: inherit;
            background-color: transparent;
            border-color: transparent;
        }

        .box {
            position: relative;
            border-radius: 15px;
            margin-bottom: 20px;
            width: 100%;
            -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
            box-shadow: 0px 4px 30px rgba(221, 224, 255, .54);
        }

        /* New Customize Interface End Here */

        ::-moz-selection {
            /* Code for Firefox */
            color: red;
            background: yellow;
        }

        ::selection {
            color: red;
            background: yellow;
        }

        .content-wrapper {
            margin-top: 50px;
        }

        @media (max-width: 767px) {
            .content-wrapper {
                margin-top: 100px;
            }
        }


        .loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .loading::after {
            content: "";
            display: inline-block;
            width: 16px;
            height: 16px;
            vertical-align: middle;
            margin-left: 10px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s infinite linear;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .dropdown-menu .dropdown-item {
            margin-bottom: 5px;
        }

        .dropdown-menu .dropdown-item button {
            margin: 0;
            padding: 10px;
        }
    </style>

    {if isset($xheader)}
        {$xheader}
    {/if}

</head>

<body class="hold-transition modern-skin-dark sidebar-mini">
    <div class="wrapper">
        <header class="main-header" style="position:fixed; width: 100%">
            <a href="{$_url}home" class="logo">
                <span class="logo-mini"><b>N</b>uX</span>
                <span class="logo-lg">{$_c['CompanyName']}</span>
            </a>
            <nav class="navbar navbar-static-top">
                <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                    <span class="sr-only">Toggle navigation</span>
                </a>
                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        <li class="dropdown tasks-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                                <i class="fa fa-flag-o"></i> <span class="d-none d-sm-inline">{ucwords($user_language)}</span>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <!-- inner menu: contains the actual data -->
                                    <ul class="menu" api-get-text="{$_url}autoload_user/language&select={$user_language}"></ul>
                                </li>
                            </ul>
                        </li>
                        <li class="dropdown notifications-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <i class="fa fa-envelope-o"></i>
                                <span class="label label-warning"
                                    api-get-text="{$_url}autoload_user/inbox_unread"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <!-- inner menu: contains the actual data -->
                                    <ul class="menu" api-get-text="{$_url}autoload_user/inbox"></ul>
                                </li>
                                <li class="footer"><a href="{$_url}mail">{Lang::T('Inbox')}</a></li>
                            </ul>
                        </li>
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                {if $_c['enable_balance'] == 'yes'}
                                    <span
                                        style="color: whitesmoke;">&nbsp;{Lang::moneyFormat($_user['balance'])}&nbsp;</span>
                                {else}
                                    <span>{$_user['fullname']}</span>
                                {/if}
                                <img src="https://robohash.org/{$_user['id']}?set=set3&size=100x100&bgset=bg1"
                                    onerror="this.src='{$UPLOAD_PATH}/user.default.jpg'" class="user-image"
                                    alt="User Image">
                            </a>
                            <ul class="dropdown-menu">
                                <li class="user-header">
                                    <img src="https://robohash.org/{$_user['id']}?set=set3&size=100x100&bgset=bg1"
                                        onerror="this.src='{$UPLOAD_PATH}/user.default.jpg'" class="img-circle"
                                        alt="User Image">

                                    <p>
                                        {$_user['fullname']}
                                        <small>{$_user['phonenumber']}<br>
                                            {$_user['email']}</small>
                                    </p>
                                </li>
                                <li class="user-body">
                                    <div class="row">
                                        <div class="col-xs-7 text-center text-sm">
                                            <a href="{$_url}accounts/change-password"><i class="ion ion-settings"></i>
                                                {Lang::T('Change Password')}</a>
                                        </div>
                                        <div class="col-xs-5 text-center text-sm">
                                            <a href="{$_url}accounts/profile"><i class="ion ion-person"></i>
                                                {Lang::T('My Account')}</a>
                                        </div>
                                    </div>
                                </li>
                                <li class="user-footer">
                                    <div class="pull-right">
                                        <a href="{$_url}logout" class="btn btn-default btn-flat"><i
                                                class="ion ion-power"></i> {Lang::T('Logout')}</a>
                                    </div>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>

        <aside class="main-sidebar" style="position:fixed;">
            <section class="sidebar">
                <ul class="sidebar-menu" data-widget="tree">
                    <li {if $_system_menu eq 'home'}class="active" {/if}>
                        <a href="{$_url}home">
                            <i class="ion ion-monitor"></i>
                            <span>{Lang::T('Dashboard')}</span>
                        </a>
                    </li>
                    {$_MENU_AFTER_DASHBOARD}
                    <li {if $_system_menu eq 'inbox'}class="active" {/if}>
                        <a href="{$_url}mail">
                            <i class="fa fa-envelope"></i>
                            <span>{Lang::T('Inbox')}</span>
                        </a>
                    </li>
                    {$_MENU_AFTER_INBOX}
                    {if $_c['disable_voucher'] != 'yes'}
                        <li {if $_system_menu eq 'voucher'}class="active" {/if}>
                            <a href="{$_url}voucher/activation">
                                <i class="fa fa-ticket"></i>
                                <span>Voucher</span>
                            </a>
                        </li>
                    {/if}
                    {if $_c['payment_gateway'] != 'none' or $_c['payment_gateway'] == '' }
                        {if $_c['enable_balance'] == 'yes'}
                            <li {if $_system_menu eq 'balance'}class="active" {/if}>
                                <a href="{$_url}order/balance">
                                    <i class="ion ion-ios-cart"></i>
                                    <span>{Lang::T('Buy Balance')}</span>
                                </a>
                            </li>
                        {/if}
                        <li {if $_system_menu eq 'package'}class="active" {/if}>
                            <a href="{$_url}order/package">
                                <i class="ion ion-ios-cart"></i>
                                <span>{Lang::T('Buy Package')}</span>
                            </a>
                        </li>
                        <li {if $_system_menu eq 'history'}class="active" {/if}>
                            <a href="{$_url}order/history">
                                <i class="fa fa-file-text"></i>
                                <span>{Lang::T('Order History')}</span>
                            </a>
                        </li>
                    {/if}
                    {$_MENU_AFTER_ORDER}
                    <li {if $_system_menu eq 'list-activated'}class="active" {/if}>
                        <a href="{$_url}voucher/list-activated">
                            <i class="fa fa-list-alt"></i>
                            <span>{Lang::T('Activation History')}</span>
                        </a>
                    </li>
                    {$_MENU_AFTER_HISTORY}
                </ul>
            </section>
        </aside>

        <div class="content-wrapper">
            <section class="content-header">
                <h1>
                    {$_title}
                </h1>
            </section>
            <section class="content">


                {if isset($notify)}
                    <script>
                        // Display SweetAlert toast notification
                        Swal.fire({
                            icon: '{if $notify_t == "s"}success{else}warning{/if}',
                            title: '{$notify}',
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        });
                    </script>
{/if}
