<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{$_title}</title>
    <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

    <link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">

    <link rel="stylesheet" href="ui/ui/fonts/ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="ui/ui/fonts/MaterialDesign/css/materialdesignicons.min.css">

    <link rel="stylesheet" href="ui/ui/styles/adminlte.min.css">
    <link rel="stylesheet" href="ui/ui/styles/skin-blue.min.css">


    {if isset($xheader)}
        {$xheader}
    {/if}

</head>

<body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">

        <header class="main-header">
            <a href="{$_url}dashboard" class="logo">
                <span class="logo-mini"><b>N</b>uX</span>
                <span class="logo-lg">{Lang::T('Logo')}</span>
            </a>
            <nav class="navbar navbar-static-top">
                <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                    <span class="sr-only">Toggle navigation</span>
                </a>
                <div class="navbar-custom-menu">

                    <ul class="nav navbar-nav">
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <img src="https://robohash.org/{$_admin['id']}?set=set3&size=100x100&bgset=bg1"
                                    class="user-image" alt="Avatar">
                                <span class="hidden-xs">{$_admin['fullname']}</span>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="user-header">
                                    <img src="https://robohash.org/{$_admin['id']}?set=set3&size=100x100&bgset=bg1"
                                        class="img-circle" alt="Avatar">

                                    <p>
                                        {$_admin['fullname']}
                                        <small>{if $_admin['user_type'] eq 'Admin'} {$_L['Administrator']}
                                            {else}
                                            {$_L['Sales']} {/if}</small>
                                    </p>
                                </li>
                                <li class="user-body">
                                    <div class="row">
                                        <div class="col-xs-6 text-center">
                                            <a href="{$_url}settings/change-password"> {$_L['Change_Password']}</a>
                                        </div>
                                        <div class="col-xs-6 text-center">
                                            <a href="{$_url}settings/users-edit/{$_admin['id']}">
                                                {$_L['My_Account']}</a>
                                        </div>
                                    </div>
                                </li>
                                <li class="user-footer">
                                    <div class="pull-right">
                                        <a href="{$_url}logout" class="btn btn-default btn-flat"><i
                                                class="ion ion-power"></i> {$_L['Logout']}</a>
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
                            <span>{$_L['Dashboard']}</span>
                        </a>
                    </li>
                    {$_MENU_AFTER_DASHBOARD}
                    {if $_admin['user_type'] eq 'Admin' || $_admin['user_type'] eq 'Sales'}
                        <li class="{if $_system_menu eq 'customers'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-android-contacts"></i> <span>{$_L['Customers']}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'add'}class="active" {/if}><a href="{$_url}customers/add"><i
                                            class="fa fa-user-plus"></i> {$_L['Add_Contact']}</a></li>
                                <li {if $_routes[1] eq 'list'}class="active" {/if}><a href="{$_url}customers/list"><i
                                            class="fa fa-users"></i> {$_L['List_Contact']}</a></li>
                                {$_MENU_CUSTOMERS}
                            </ul>
                        </li>
                        {$_MENU_AFTER_CUSTOMERS}
                        <li class="{if $_system_menu eq 'prepaid'}active{/if} treeview">
                            <a href="#">
                                <i class="fa fa-ticket"></i> <span>{$_L['Prepaid']}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'list'}class="active" {/if}><a
                                        href="{$_url}prepaid/list">{$_L['Prepaid_User']}</a></li>
                                <li {if $_routes[1] eq 'voucher'}class="active" {/if}><a
                                        href="{$_url}prepaid/voucher">{$_L['Prepaid_Vouchers']}</a></li>
                                <li {if $_routes[1] eq 'refill'}class="active" {/if}><a
                                        href="{$_url}prepaid/refill">{$_L['Refill_Account']}</a></li>
                                <li {if $_routes[1] eq 'recharge'}class="active" {/if}><a
                                        href="{$_url}prepaid/recharge">{$_L['Recharge_Account']}</a></li>
                                {$_MENU_PREPAID}
                            </ul>
                        </li>
                        {$_MENU_AFTER_PREPAID}
                        <li class="{if $_system_menu eq 'services'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-cube"></i> <span>{$_L['Services']}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'hotspot'}class="active" {/if}><a
                                        href="{$_url}services/hotspot">{$_L['Hotspot_Plans']}</a></li>
                                <li {if $_routes[1] eq 'pppoe'}class="active" {/if}><a
                                        href="{$_url}services/pppoe">{$_L['PPPOE_Plans']}</a></li>
                                <li {if $_routes[1] eq 'list'}class="active" {/if}><a
                                        href="{$_url}bandwidth/list">{$_L['Bandwidth_Plans']}</a></li>
                                {$_MENU_SERVICES}
                            </ul>
                        </li>
                        {$_MENU_AFTER_SERVICES}
                        <li class="{if $_system_menu eq 'reports'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-clipboard"></i> <span>{$_L['Reports']}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'daily-report'}class="active" {/if}><a
                                        href="{$_url}reports/daily-report">{$_L['Daily_Report']}</a></li>
                                <li {if $_routes[1] eq 'by-period'}class="active" {/if}><a
                                        href="{$_url}reports/by-period">{$_L['Period_Reports']}</a></li>
                                {$_MENU_REPORTS}
                            </ul>
                        </li>
                        {$_MENU_AFTER_REPORTS}
                    {/if}
                    {if $_admin['user_type'] eq 'Admin'}
                        <li  class="{if $_system_menu eq 'network'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-network"></i> <span>{$_L['Network']}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[0] eq 'routers' and $_routes[1] eq 'list'}class="active" {/if}><a
                                        href="{$_url}routers/list">{$_L['Routers']}</a></li>
                                <li {if $_routes[0] eq 'pool' and $_routes[1] eq 'list'}class="active" {/if}><a
                                        href="{$_url}pool/list">{$_L['Pool']}</a></li>
                                {$_MENU_NETWORK}
                            </ul>
                        </li>
                        {$_MENU_AFTER_NETWORKS}
                        <li class="{if $_system_menu eq 'pages'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-document"></i> <span>{$_L['Static_Pages']}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'Order_Voucher'}class="active" {/if}><a
                                        href="{$_url}pages/Order_Voucher">{$_L['Order_Voucher']}</a></li>
                                <li {if $_routes[1] eq 'Voucher'}class="active" {/if}><a
                                        href="{$_url}pages/Voucher">{$_L['Voucher']} Template</a></li>
                                <li {if $_routes[1] eq 'Announcement'}class="active" {/if}><a
                                        href="{$_url}pages/Announcement">{$_L['Announcement']} Editor</a></li>
                                <li {if $_routes[1] eq 'Registration_Info'}class="active" {/if}><a
                                        href="{$_url}pages/Registration_Info">{$_L['Registration_Info']} Editor</a></li>
                                {$_MENU_PAGES}
                            </ul>
                        </li>
                        {$_MENU_AFTER_PAGES}
                        <li class="{if $_system_menu eq 'settings'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-gear-a"></i> <span>{$_L['Settings']}</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[1] eq 'app'}class="active" {/if}><a
                                        href="{$_url}settings/app">{$_L['General_Settings']}</a></li>
                                <li {if $_routes[1] eq 'localisation'}class="active" {/if}><a
                                        href="{$_url}settings/localisation">{$_L['Localisation']}</a></li>
                                <li {if $_routes[1] eq 'users'}class="active" {/if}><a
                                        href="{$_url}settings/users">{$_L['Administrator_Users']}</a></li>
                                <li {if $_routes[1] eq 'dbstatus'}class="active" {/if}><a
                                        href="{$_url}settings/dbstatus">{$_L['Backup_Restore']}</a></li>
                                {$_MENU_SETTINGS}
                            </ul>
                        </li>
                        {$_MENU_AFTER_SETTINGS}
                        <li {if $_system_menu eq 'paymentgateway'}class="active" {/if}>
                            <a href="{$_url}paymentgateway">
                                <i class="ion ion-cash"></i>
                                <span class="text">{Lang::T('Payment Gateway')}</span>
                            </a>
                        </li>
                        {$_MENU_AFTER_PAYMENTGATEWAY}
                        <li {if $_system_menu eq 'community'}class="active" {/if}>
                            <a href="{$_url}community">
                                <i class="ion ion-chatboxes"></i>
                                <span class="text">{Lang::T('Community')}</span>
                            </a>
                        </li>
                    {/if}
                </ul>
            </section>
        </aside>

        <div class="content-wrapper">
            <section class="content-header">
                <h1>
                    Dashboard
                </h1>
            </section>

            <section class="content">
                {if isset($notify)}{$notify}{/if}