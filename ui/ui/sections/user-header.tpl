<!DOCTYPE html>
<html lang="en" class="has-aside-left has-aside-mobile-transition has-navbar-fixed-top has-aside-expanded">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$_title} - {$_c['CompanyName']}</title>
    <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

    <!-- Icons -->
    <link rel="stylesheet" href="ui/ui/fonts/ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="ui/ui/fonts/MaterialDesign/css/materialdesignicons.min.css">

    <!-- Css/Less Stylesheets -->
    <link rel="stylesheet" href="ui/ui/styles/main.min.css">


    <!-- Match Media polyfill for IE9 -->
    <!--[if IE 9]> <script src="ui/ui/scripts/ie/matchMedia.js"></script>  <![endif]-->
    {if isset($xheader)}
        {$xheader}
    {/if}

</head>

<body>
    <div id="app">
        <nav id="navbar-main" class="navbar is-fixed-top">
            <div class="navbar-brand">
                <a class="navbar-item is-hidden-desktop jb-aside-mobile-toggle">
                    <span class="icon"><i class="mdi mdi-forwardburger mdi-24px"></i></span>
                </a>
                <div class="navbar-item">
                    <span>{$_title}</span>
                </div>
            </div>
            <div class="navbar-brand is-right">
                <a class="navbar-item is-hidden-desktop jb-navbar-menu-toggle" data-target="navbar-menu">
                    <span class="icon"><i class="ion ion-person"></i></span>
                </a>
            </div>
            <div class="navbar-menu fadeIn animated faster" id="navbar-menu">
                <div class="navbar-end">
                    <div
                        class="navbar-item has-dropdown has-dropdown-with-icons has-divider has-user-avatar is-hoverable">
                        <a class="navbar-link is-arrowless">
                            <div class="is-user-avatar">
                                <img src="https://robohash.org/{$_user['id']}?set=set3&size=100x100&bgset=bg1"
                                    alt="avatar">
                            </div>
                            <div class="is-user-name"><span>{$_user['fullname']}</span></div>
                            <span class="icon"><i class="ion ion-chevron-down"></i></span>
                        </a>
                        <div class="navbar-dropdown">
                            <a href="{$_url}accounts/profile" class="navbar-item">
                                <span class="icon"><i class="ion ion-person"></i></span>
                                <span>{$_L['My_Account']}</span>
                            </a>
                            <a class="navbar-item" href="{$_url}accounts/change-password">
                                <span class="icon"><i class="ion ion-settings"></i></span>
                                <span>{$_L['Change_Password']}</span>
                            </a>
                            <hr class="navbar-divider">
                            <a class="navbar-item" href="{$_url}logout">
                                <span class="icon"><i class="ion ion-power"></i></span>
                                <span> {$_L['Logout']}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <aside class="aside is-placed-left is-expanded">
            <div class="aside-tools">
                <div class="aside-tools-label">
                    <span>{$_L['Logo']}</span>
                </div>
            </div>
            <div class="menu is-menu-main">
                <ul class="menu-list">
                    <li>
                        <a href="{$_url}home" {if $_system_menu eq 'home'}class="is-active router-link-active" {/if}>
                            <span class="icon"><i class="ion ion-monitor"></i></span>
                            <span class="text">{$_L['Dashboard']}</span>
                        </a>
                    </li>
                    {$_MENU_AFTER_DASHBOARD}
                    <li>
                        <a href="{$_url}order/voucher" {if $_system_menu eq 'voucher'}class="is-active router-link-active" {/if}>
                            <span class="icon"><i class="ion ion-android-cart"></i></span>
                            <span class="text">{Lang::T('Voucher')}</span>
                        </a>
                    </li>
                    {if $_c['payment_gateway'] != 'none' or $_c['payment_gateway'] == '' }
                    <li>
                        <a href="{$_url}order/package" {if $_system_menu eq 'package'}class="is-active router-link-active" {/if}>
                            <span class="icon"><i class="ion ion-ios-cart"></i></span>
                            <span class="text">{Lang::T('Package')}</span>
                        </a>
                    </li>
                    <li>
                        <a href="{$_url}order/history" {if $_system_menu eq 'history'}class="is-active router-link-active" {/if}>
                            <span class="icon"><i class="ion ion-card"></i></span>
                            <span class="text">{Lang::T('Package History')}</span>
                        </a>
                    </li>
                    {/if}
                    {$_MENU_AFTER_ORDER}
                    <li>
                        <a href="{$_url}voucher/list-activated"
                            {if $_system_menu eq 'list-activated'}class="is-active router-link-active"{/if}>
                            <span class="icon"><i class="ion ion-card"></i></span>
                            <span class="text">{Lang::T('Buy History')}</span>
                        </a>
                    </li>
                    {$_MENU_AFTER_HISTORY}
                </ul>
                <p class="menu-label">&copy; PHP NUX BILL</p>
            </div>
        </aside>
        <section class="section is-main-section">
            {if isset($notify)}
                {$notify}
{/if}