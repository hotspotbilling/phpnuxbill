<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{$_title} - {$_c['CompanyName']}</title>
    <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

    <link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">

    <link rel="stylesheet" href="ui/ui/fonts/ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="ui/ui/fonts/MaterialDesign/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="ui/ui/styles/modern-AdminLTE.min.css">
    <link rel="stylesheet" href="ui/ui/styles/select2.min.css" />
    <link rel="stylesheet" href="ui/ui/styles/select2-bootstrap.min.css" />
    <link rel="stylesheet" href="ui/ui/styles/sweetalert2.min.css" />
    <link rel="stylesheet" href="ui/ui/styles/plugins/pace.css" />
    <script src="ui/ui/scripts/sweetalert2.all.min.js"></script>
    <style>
        ::-moz-selection {
            /* Code for Firefox */
            color: red;
            background: yellow;
        }

        ::selection {
            color: red;
            background: yellow;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            margin-top: 0px !important;
        }

        @media (min-width: 768px) {
            .outer {
                height: 200px
                    /* Or whatever */
            }
        }

        .text1line {
            display: block;
            /* or inline-block */
            text-overflow: ellipsis;
            word-wrap: break-word;
            overflow: hidden;
            max-height: 1em;
            line-height: 1em;
        }
    </style>

    {if isset($xheader)}
        {$xheader}
    {/if}

</head>

<body class="hold-transition modern-skin-dark sidebar-mini {if $_kolaps}sidebar-collapse{/if}">
    <div class="wrapper">
        <header class="main-header">
            <a href="{$_url}dashboard" class="logo">
                <span class="logo-mini"><b>N</b>uX</span>
                <span class="logo-lg">{$_c['CompanyName']}</span>
            </a>
            <nav class="navbar navbar-static-top">
                <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button" onclick="return setKolaps()">
                    <span class="sr-only">Toggle navigation</span>
                </a>
                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <img src="https://robohash.org/{$_admin['id']}?set=set3&size=100x100&bgset=bg1"
                                    onerror="this.src='system/uploads/admin.default.png'" class="user-image"
                                    alt="Avatar">
                                <span class="hidden-xs">{$_admin['fullname']}</span>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="user-header">
                                    <img src="https://robohash.org/{$_admin['id']}?set=set3&size=100x100&bgset=bg1"
                                        onerror="this.src='system/uploads/admin.default.png'" class="img-circle"
                                        alt="Avatar">
                                    <p>
                                        {$_admin['fullname']}
                                        <small>{Lang::T($_admin['user_type'])}</small>
                                    </p>
                                </li>
                                <li class="user-body">
                                    <div class="row">
                                        <div class="col-xs-7 text-center text-sm">
                                            <a href="{$_url}settings/change-password"><i class="ion ion-settings"></i>
                                                {Lang::T('Change Password')}</a>
                                        </div>
                                        <div class="col-xs-5 text-center text-sm">
                                            <a href="{$_url}settings/users-view/{$_admin['id']}">
                                                <i class="ion ion-person"></i> {Lang::T('My Account')}</a>
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
        <aside class="main-sidebar">
            <section class="sidebar">
                <ul class="sidebar-menu" data-widget="tree">
                    <li {if $_system_menu eq 'dashboard'}class="active" {/if}>
                        <a href="{$_url}dashboard">
                            <i class="ion ion-monitor"></i>
                            <span>{Lang::T('Dashboard')}</span>
                        </a>
                    </li>
                    {$_MENU_AFTER_DASHBOARD}
                    {if !in_array($_admin['user_type'],['Report'])}
                        <li class="{if $_system_menu eq 'customers'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-android-contacts"></i> <span>{Lang::T('Customer')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'add'}class="active" {/if}><a href="{$_url}customers/add"><i
                                            class="fa fa-user-plus"></i> {Lang::T('Add New Contact')}</a></li>
                                <li {if $_routes[1] eq 'list'}class="active" {/if}><a href="{$_url}customers/list"><i
                                            class="fa fa-users"></i> {Lang::T('List Contact')}</a></li>
                                {$_MENU_CUSTOMERS}
                            </ul>
                        </li>
                        {$_MENU_AFTER_CUSTOMERS}
                        <li class="{if $_system_menu eq 'prepaid'}active{/if} treeview">
                            <a href="#">
                                <i class="fa fa-ticket"></i> <span>{Lang::T('Prepaid')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'list'}class="active" {/if}><a
                                        href="{$_url}prepaid/list">{Lang::T('Prepaid Users')}</a></li>
                                {if $_c['disable_voucher'] != 'yes'}
                                    <li {if $_routes[1] eq 'voucher'}class="active" {/if}><a
                                            href="{$_url}prepaid/voucher">{Lang::T('Prepaid Vouchers')}</a></li>
                                    <li {if $_routes[1] eq 'refill'}class="active" {/if}><a
                                            href="{$_url}prepaid/refill">{Lang::T('Refill Account')}</a></li>
                                {/if}
                                <li {if $_routes[1] eq 'recharge'}class="active" {/if}><a
                                        href="{$_url}prepaid/recharge">{Lang::T('Recharge Account')}</a></li>
                                <li {if $_routes[1] eq 'deposit'}class="active" {/if}><a
                                        href="{$_url}prepaid/deposit">{Lang::T('Refill Balance')}</a></li>
                                {$_MENU_PREPAID}
                            </ul>
                        </li>
                    {/if}
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        {$_MENU_AFTER_PREPAID}
                        <li class="{if $_system_menu eq 'services'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-cube"></i> <span>{Lang::T('Services')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'hotspot'}class="active" {/if}><a
                                        href="{$_url}services/hotspot">{Lang::T('Hotspot Plans')}</a></li>
                                <li {if $_routes[1] eq 'pppoe'}class="active" {/if}><a
                                        href="{$_url}services/pppoe">{Lang::T('PPPOE Plans')}</a></li>
                                <li {if $_routes[1] eq 'list'}class="active" {/if}><a
                                        href="{$_url}bandwidth/list">{Lang::T('Bandwidth Plans')}</a></li>
                                <li {if $_routes[1] eq 'balance'}class="active" {/if}><a
                                        href="{$_url}services/balance">{Lang::T('Balance Plans')}</a></li>
                                {$_MENU_SERVICES}
                            </ul>
                        </li>
                        {$_MENU_AFTER_SERVICES}
                    {/if}
                    <li class="{if $_system_menu eq 'reports'}active{/if} treeview">
                        <a href="#">
                            <i class="ion ion-clipboard"></i> <span>{Lang::T('Reports')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li {if $_routes[1] eq 'daily-report'}class="active" {/if}><a
                                    href="{$_url}reports/daily-report">{Lang::T('Daily Reports')}</a></li>
                            <li {if $_routes[1] eq 'by-period'}class="active" {/if}><a
                                    href="{$_url}reports/by-period">{Lang::T('Period Reports')}</a></li>
                            <li {if $_routes[1] eq 'activation'}class="active" {/if}><a
                                    href="{$_url}reports/activation">{Lang::T('Activation History')}</a></li>
                            {$_MENU_REPORTS}
                        </ul>
                    </li>
                    {$_MENU_AFTER_REPORTS}
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li class="{if $_system_menu eq 'network'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-network"></i> <span>{Lang::T('Network')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[0] eq 'routers' and $_routes[1] eq 'list'}class="active" {/if}><a
                                        href="{$_url}routers/list">{Lang::T('Routers')}</a></li>
                                <li {if $_routes[0] eq 'pool' and $_routes[1] eq 'list'}class="active" {/if}><a
                                        href="{$_url}pool/list">{Lang::T('IP Pool')}</a></li>
                                {$_MENU_NETWORK}
                            </ul>
                        </li>
                        {$_MENU_AFTER_NETWORKS}
                        {if $_c['radius_enable']}
                            <li class="{if $_system_menu eq 'radius'}active{/if} treeview">
                                <a href="#">
                                    <i class="fa fa-database"></i> <span>{Lang::T('Radius')}</span>
                                    <span class="pull-right-container">
                                        <i class="fa fa-angle-left pull-right"></i>
                                    </span>
                                </a>
                                <ul class="treeview-menu">
                                    <li {if $_routes[0] eq 'radius' and $_routes[1] eq 'nas-list'}class="active" {/if}><a
                                            href="{$_url}radius/nas-list">{Lang::T('Radius NAS')}</a></li>
                                    {$_MENU_RADIUS}
                                </ul>
                            </li>
                        {/if}
                        {$_MENU_AFTER_RADIUS}
                        <li class="{if $_system_menu eq 'pages'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-document"></i> <span>{Lang::T("Static Pages")}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'Order_Voucher'}class="active" {/if}><a
                                        href="{$_url}pages/Order_Voucher">{Lang::T('Order Voucher')}</a></li>
                                <li {if $_routes[1] eq 'Voucher'}class="active" {/if}><a
                                        href="{$_url}pages/Voucher">{Lang::T('Voucher')} Template</a></li>
                                <li {if $_routes[1] eq 'Announcement'}class="active" {/if}><a
                                        href="{$_url}pages/Announcement">{Lang::T('Announcement')}</a></li>
                                <li {if $_routes[1] eq 'Registration_Info'}class="active" {/if}><a
                                        href="{$_url}pages/Registration_Info">{Lang::T('Registration Info')}</a></li>
                                <li {if $_routes[1] eq 'Privacy_Policy'}class="active" {/if}><a
                                        href="{$_url}pages/Privacy_Policy">Privacy Policy</a></li>
                                <li {if $_routes[1] eq 'Terms_and_Conditions'}class="active" {/if}><a
                                        href="{$_url}pages/Terms_and_Conditions">Terms and Conditions</a></li>
                                {$_MENU_PAGES}
                            </ul>
                        </li>
                        {$_MENU_AFTER_PAGES}
                    {/if}
                    <li
                        class="{if $_system_menu eq 'settings' || $_system_menu eq 'paymentgateway' }active{/if} treeview">
                        <a href="#">
                            <i class="ion ion-gear-a"></i> <span>{Lang::T('Settings')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                <li {if $_routes[1] eq 'app'}class="active" {/if}><a
                                        href="{$_url}settings/app">{Lang::T('General Settings')}</a></li>
                                <li {if $_routes[1] eq 'localisation'}class="active" {/if}><a
                                        href="{$_url}settings/localisation">{Lang::T('Localisation')}</a></li>
                                <li {if $_routes[1] eq 'notifications'}class="active" {/if}><a
                                        href="{$_url}settings/notifications">{Lang::T('User Notification')}</a></li>
                            {/if}
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin','Agent'])}
                                <li {if $_routes[1] eq 'users'}class="active" {/if}><a
                                        href="{$_url}settings/users">{Lang::T('Administrator Users')}</a></li>
                            {/if}
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                <li {if $_routes[1] eq 'dbstatus'}class="active" {/if}><a
                                        href="{$_url}settings/dbstatus">{Lang::T('Backup/Restore')}</a></li>
                                <li {if $_system_menu eq 'paymentgateway'}class="active" {/if}>
                                    <a href="{$_url}paymentgateway">
                                        <span class="text">{Lang::T('Payment Gateway')}</span>
                                    </a>
                                </li>
                                {$_MENU_SETTINGS}
                                <li {if $_routes[0] eq 'pluginmanager'}class="active" {/if}>
                                    <a href="{$_url}pluginmanager"><i class="glyphicon glyphicon-tasks"></i>
                                        {Lang::T('Plugin Manager')} <small class="label pull-right">Free</small></a>
                                </li>
                                {* <li {if $_routes[0] eq 'codecanyon'}class="active" {/if}>
                                <a href="{$_url}codecanyon"><i class="glyphicon glyphicon-shopping-cart"></i>
                                    Codecanyon.net <small class="label pull-right">Paid</small></a>
                            </li> *}
                            {/if}
                        </ul>
                    </li>
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        {$_MENU_AFTER_SETTINGS}
                        <li class="{if $_system_menu eq 'logs' }active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-clock"></i> <span>{Lang::T('Logs')}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'list'}class="active" {/if}><a
                                        href="{$_url}logs/phpnuxbill">PhpNuxBill</a></li>
                                {if $_c['radius_enable']}
                                    <li {if $_routes[1] eq 'radius'}class="active" {/if}><a href="{$_url}logs/radius">Radius</a>
                                    </li>
                                {/if}
                            </ul>
                            {$_MENU_LOGS}
                        </li>
                    {/if}
                    {$_MENU_AFTER_LOGS}
                    <li {if $_system_menu eq 'community'}class="active" {/if}>
                        <a href="{$_url}community">
                            <i class="ion ion-chatboxes"></i>
                            <span class="text">{Lang::T('Community')}</span>
                        </a>
                    </li>
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
                        icon: '{if $notify_t == "s"}success{else}error{/if}',
                        title: '{$notify}',
                        toast: true,
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