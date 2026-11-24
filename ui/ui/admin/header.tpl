<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{$_title} - {$_c['CompanyName']}</title>
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/logo.png" type="image/x-icon" />

    <script>
        var appUrl = '{$app_url}';
        window.paceOptions = {
            ajax: {
                trackMethods: ['GET', 'POST'],
                ignoreURLs: [/autoload/, /autoload_user/, /search_user/, /global_search/]
            },
            restartOnRequestAfter: false,
            restartOnPushState: false
        };
    </script>

    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/phpnuxbill.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/fontawesome.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/select2.min.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/select2-bootstrap.min.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/sweetalert2.min.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/loading.css?2026.11.11.1" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/plugins/pace.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/summernote/summernote.min.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/styles.css?2026.11.24.3" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/admin-chrome.css?2026.11.7.10" />
    <script src="{$app_url}/ui/ui/scripts/sweetalert2.all.min.js"></script>
    <script src="{$app_url}/ui/ui/scripts/chart.min.js"></script>
     {if isset($xheader)}
    {$xheader}
    {/if}
    <script>
        (function() {
            var mode = localStorage.getItem('mode') || 'system';
            var isDark = mode === 'dark' || (mode === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.classList.add('dark-mode');
            }
        })();
    </script>
</head>

<body class="hold-transition modern-skin-dark as-chrome sidebar-mini {if $_kolaps}sidebar-collapse{/if}">
    <script>
        if (document.documentElement.classList.contains('dark-mode')) {
            document.body.classList.add('dark-mode');
        }
        document.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                const searchWrap = document.getElementById('searchOverlay');
                if (searchWrap && window.innerWidth <= 767) {
                    searchWrap.classList.add('is-active-mobile');
                }
                const searchInput = document.getElementById('searchTerm');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });
    </script>
    <div class="wrapper">
        <header class="main-header">
            <a href="{Text::url('dashboard')}" class="logo">
                <span class="logo-mini"><b>{$_c['CompanyName']|truncate:1:''|upper|escape}</b></span>
                <span class="logo-lg"><span class="logo-name">{$_c['CompanyName']}</span><small class="logo-edition">Ultimate</small></span>
            </a>
            <a href="#" class="as-edge-toggle" data-toggle="push-menu" role="button" aria-label="Toggle menu"
                onclick="return setKolaps()">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 6l-6 6l6 6" />
                </svg>
            </a>
            <nav class="navbar navbar-static-top">
                <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button" onclick="return setKolaps()">
                    <span class="sr-only">Toggle navigation</span>
                </a>

                <div id="searchOverlay" class="topbar-search-wrap">
                    <button type="button" class="search-mobile-back" id="searchMobileBack" title="{Lang::T('Close')}" aria-label="{Lang::T('Close')}">
                        <i class="fa fa-arrow-left"></i>
                    </button>
                    <span class="search-icon"><i class="fa fa-search"></i></span>
                    <input type="text" id="searchTerm" class="searchTerm"
                        placeholder="{Lang::T('Search customers, routers, plans...')} (⌘K)" autocomplete="off">
                    <span class="search-spinner" id="searchSpinner" style="display: none;"><i class="fa fa-spinner fa-spin"></i></span>
                    <button type="button" class="search-clear-btn" id="searchClearBtn" style="display: none;" title="{Lang::T('Clear')}">&times;</button>
                    <kbd class="search-kbd">⌘K</kbd>
                    <div id="searchResults" class="search-results">
                        <!-- Search results will be displayed here -->
                    </div>
                </div>

                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        <li class="mobile-search-item">
                            <a id="searchMobileBtn" class="toggle-container" href="#" title="{Lang::T('Search')}" aria-label="{Lang::T('Search')}">
                                <i class="toggle-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="icon icon-tabler icons-tabler-outline icon-tabler-search">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                        <path d="M21 21l-6 -6" />
                                    </svg>
                                </i>
                            </a>
                        </li>
                        <li>
                            <a id="toggleIcon" class="toggle-container" href="#" title="{Lang::T('Toggle Theme')}">
                                <i class="toggle-icon"><svg xmlns="http://www.w3.org/2000/svg"
                                        width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="icon icon-tabler icons-tabler-outline icon-tabler-moon">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path
                                            d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" />
                                    </svg></i>
                            </a>
                        </li>
                        {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li>
                            <a class="toggle-container" href="{Text::url('settings/notifications')}" title="{Lang::T('Notifications')}">
                                <i class="toggle-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="icon icon-tabler icons-tabler-outline icon-tabler-bell-ringing">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path
                                            d="M10 5a2 2 0 0 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
                                        <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                                        <path d="M21 6.727a11.05 11.05 0 0 0 -2.794 -3.727" />
                                        <path d="M3 6.727a11.05 11.05 0 0 1 2.792 -3.727" />
                                    </svg>
                                </i>
                            </a>
                        </li>
                        {/if}
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <img src="{$app_url}/{$UPLOAD_PATH}{$_admin['photo']}.thumb.jpg"
                                    onerror="this.src='https://www.gravatar.com/avatar/{$_admin['email']|default:'user@example.com'|md5}?d=mp'" alt="" class="user-image">
                                <span class="hidden-xs">{$_admin['fullname']}</span>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="user-header">
                                    <img src="{$app_url}/{$UPLOAD_PATH}{$_admin['photo']}.thumb.jpg"
                                        onerror="this.src='https://www.gravatar.com/avatar/{$_admin['email']|default:'user@example.com'|md5}?d=mp'"
                                        class="img-circle" alt="Avatar">
                                    <p>
                                        {$_admin['fullname']}
                                        <small>{Lang::T($_admin['user_type'])}</small>
                                    </p>
                                </li>
                                <li class="user-body">
                                    <div class="row">
                                        <div class="col-xs-7 text-center text-sm">
                                            <a href="{Text::url('settings/change-password')}"><i
                                                    class="ion ion-settings"></i>
                                                {Lang::T('Change Password')}</a>
                                        </div>
                                        <div class="col-xs-5 text-center text-sm">
                                            <a href="{Text::url('settings/users-view/', $_admin['id'])}">
                                                <i class="ion ion-person"></i> {Lang::T('My Account')}</a>
                                        </div>
                                    </div>
                                </li>
                                <li class="user-footer">
                                    <div class="pull-right">
                                        <a href="{Text::url('logout')}" class="btn btn-default btn-flat"><i
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
                    <li {if $_system_menu eq 'dashboard' }class="active" {/if}>
                        <a href="{Text::url('dashboard')}">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 4h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-6a1 1 0 0 1 1 -1" />
                                <path d="M5 16h4a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-2a1 1 0 0 1 1 -1" />
                                <path
                                    d="M15 12h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-6a1 1 0 0 1 1 -1" />
                                <path d="M15 4h4a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-2a1 1 0 0 1 1 -1" />
                            </svg>
                            <span>{Lang::T('Dashboard')}</span>
                        </a>
                    </li>
                    {$_MENU_AFTER_DASHBOARD}
                    <li {if $_system_menu eq 'customers' }class="active" {/if}>
                        <a href="{Text::url('customers')}">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-users-group">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M10 13a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                <path d="M8 21v-1a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v1" />
                                <path d="M15 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                <path d="M17 10h2a2 2 0 0 1 2 2v1" />
                                <path d="M5 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                <path d="M3 13v-1a2 2 0 0 1 2 -2h2" />
                            </svg>
                            <span>{Lang::T('Customer')}</span>
                        </a>
                    </li>
                    {$_MENU_AFTER_CUSTOMERS}
                    {if !in_array($_admin['user_type'],['Report'])}
                    <li class="{if in_array($_routes[0], ['plan', 'coupons']) || $_system_menu eq 'services_refill' || $_system_menu eq 'voucher'}active menu-open{/if} treeview">
                        <a href="#">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-device-gamepad-3">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M9 12l-3 -3h-2a1 1 0 0 0 -1 1v4a1 1 0 0 0 1 1h2z" />
                                <path d="M15 12l3 -3h2a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-2z" />
                                <path d="M12 15l-3 3v2a1 1 0 0 0 1 1h4a1 1 0 0 0 1 -1v-2z" />
                                <path d="M12 9l-3 -3v-2a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v2z" />
                            </svg> <span>{Lang::T('Services')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-down pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li {if $_routes[0] eq 'plan' && ($_routes[1] eq 'list' || empty($_routes[1]))}class="active" {/if}><a
                                    href="{Text::url('plan/list')}">{Lang::T('Active Customers')}</a></li>
                            {if $_c['disable_voucher'] != 'yes'}
                            <li {if $_routes[0] eq 'plan' && $_routes[1] eq 'refill'}class="active" {/if}><a
                                    href="{Text::url('plan/refill')}">{Lang::T('Refill Customer')}</a></li>
                            {/if}
                            {if $_c['disable_voucher'] != 'yes'}
                            <li {if $_routes[0] eq 'plan' && $_routes[1] eq 'voucher'}class="active" {/if}><a
                                    href="{Text::url('plan/voucher')}">{Lang::T('Vouchers')}</a></li>
                            {/if}
                            {if $_c['enable_coupons'] == 'yes'}
                            <li {if $_routes[0] eq 'coupons'}class="active" {/if}><a
                                    href="{Text::url('coupons')}">{Lang::T('Coupons')}</a></li>
                            {/if}
                            <li {if $_routes[0] eq 'plan' && $_routes[1] eq 'recharge'}class="active" {/if}><a
                                    href="{Text::url('plan/recharge')}">{Lang::T('Recharge Customer')}</a></li>
                            {if $_c['enable_balance'] == 'yes'}
                            <li {if $_routes[0] eq 'plan' && $_routes[1] eq 'deposit'}class="active" {/if}><a
                                    href="{Text::url('plan/deposit')}">{Lang::T('Refill Balance')}</a></li>
                            {/if}
                            {$_MENU_SERVICES}
                        </ul>
                    </li>
                    {/if}
                    {$_MENU_AFTER_SERVICES}
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                    {if $_c['enable_balance'] == 'yes'}
                    <li {if $_system_menu eq 'referrals' || $_routes[0] eq 'referrals'}class="active" {/if}>
                        <a href="{Text::url('referrals/list')}">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M7 12l5 5l-1.5 1.5a3.5 3.5 0 1 1 -7 0v-10a3.5 3.5 0 1 1 7 0l-1.5 1.5l-5 5"/>
                                <path d="M17 12l-5 -5l1.5 -1.5a3.5 3.5 0 1 1 7 0v10a3.5 3.5 0 1 1 -7 0l1.5 -1.5l5 -5"/>
                            </svg>
                            <span>{Lang::T('Referrals')}</span>
                        </a>
                    </li>
                    {/if}
                    {/if}
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                    <li class="{if $_system_menu eq 'services' || in_array($_routes[0], ['services', 'bandwidth'])}active menu-open{/if} treeview">
                        <a href="#">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-replace">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path
                                    d="M3 3m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                                <path
                                    d="M15 15m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                                <path d="M21 11v-3a2 2 0 0 0 -2 -2h-6l3 3m0 -6l-3 3" />
                                <path d="M3 13v3a2 2 0 0 0 2 2h6l-3 -3m0 6l3 -3" />
                            </svg> <span>{Lang::T('Internet Plan')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-down pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li {if $_routes[0] eq 'services' && $_routes[1] eq 'hotspot'}class="active" {/if}><a
                                    href="{Text::url('services/hotspot')}">Hotspot</a></li>
                            <li {if $_routes[0] eq 'services' && $_routes[1] eq 'pppoe'}class="active" {/if}><a
                                    href="{Text::url('services/pppoe')}">PPPOE</a></li>
                            <li {if $_routes[0] eq 'services' && $_routes[1] eq 'vpn'}class="active" {/if}><a
                                    href="{Text::url('services/vpn')}">VPN</a>
                            </li>
                            <li {if $_routes[0] eq 'bandwidth'}class="active" {/if}><a
                                    href="{Text::url('bandwidth/list')}">Bandwidth</a></li>
                            {if $_c['enable_balance'] == 'yes'}
                            <li {if $_routes[0] eq 'services' && $_routes[1] eq 'balance'}class="active" {/if}><a
                                    href="{Text::url('services/balance')}">{Lang::T('Customer Balance')}</a></li>
                            {/if}
                            {$_MENU_PLANS}
                        </ul>
                    </li>
                    {/if}
                    {$_MENU_AFTER_PLANS}
                    <li class="{if in_array($_routes[0], ['maps']) || $_system_menu eq 'maps'}active menu-open{/if} treeview">
                        <a href="#">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-map-2">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 18.5l-3 -1.5l-6 3v-13l6 -3l6 3l6 -3v7.5" />
                                <path d="M9 4v13" />
                                <path d="M15 7v5.5" />
                                <path
                                    d="M21.121 20.121a3 3 0 1 0 -4.242 0c.418 .419 1.125 1.045 2.121 1.879c1.051 -.89 1.759 -1.516 2.121 -1.879z" />
                                <path d="M19 18v.01" />
                            </svg> <span>{Lang::T('Maps')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-down pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li {if $_routes[0] eq 'maps' && ($_routes[1] eq 'customer' || empty($_routes[1]))}class="active" {/if}><a
                                    href="{Text::url('maps/customer')}">{Lang::T('Customer')}</a></li>
                            <li {if $_routes[0] eq 'maps' && $_routes[1] eq 'routers'}class="active" {/if}><a
                                    href="{Text::url('maps/routers')}">{Lang::T('Routers')}</a></li>
                            {$_MENU_MAPS}
                        </ul>
                    </li>
                    <li class="{if in_array($_routes[0], ['reports']) || $_system_menu eq 'reports'}active menu-open{/if} treeview">
                        {if in_array($_admin['user_type'],['SuperAdmin','Admin', 'Report'])}
                        <a href="#">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-clipboard-text">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path
                                    d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />
                                <path
                                    d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                                <path d="M9 12h6" />
                                <path d="M9 16h6" />
                            </svg> <span>{Lang::T('Reports')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-down pull-right"></i>
                            </span>
                        </a>
                        {/if}
                        <ul class="treeview-menu">
                            <li {if $_routes[0] eq 'reports' && (empty($_routes[1]) || $_routes[1] eq 'reports' || $_routes[1] eq 'daily')}class="active" {/if}><a
                                    href="{Text::url('reports')}">{Lang::T('Daily Reports')}</a></li>
                            <li {if $_routes[0] eq 'reports' && $_routes[1] eq 'activation'}class="active" {/if}><a
                                    href="{Text::url('reports/activation')}">{Lang::T('Activation History')}</a></li>
                            {* <li {if $_routes[0] eq 'invoices' }class="active" {/if}><a
                                    href="{Text::url('invoices')}">{Lang::T('Invoices')}</a></li> *}
                            {$_MENU_REPORTS}
                        </ul>
                    </li>
                    {$_MENU_AFTER_REPORTS}
                    <li class="{if in_array($_routes[0], ['message']) || $_system_menu eq 'message'}active menu-open{/if} treeview">
                        <a href="#">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-mail-check">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M11 19h-6a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v6" />
                                <path d="M3 7l9 6l9 -6" />
                                <path d="M15 19l2 2l4 -4" />
                            </svg> <span>{Lang::T('Send Message')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-down pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li {if $_routes[0] eq 'message' && ($_routes[1] eq 'send' || empty($_routes[1]))}class="active" {/if}><a
                                    href="{Text::url('message/send')}">{Lang::T('Single Customer')}</a></li>
                            <li {if $_routes[0] eq 'message' && $_routes[1] eq 'send_bulk'}class="active" {/if}><a
                                    href="{Text::url('message/send_bulk')}">{Lang::T('Bulk Customers')}</a></li>
                            {$_MENU_MESSAGE}
                        </ul>
                    </li>
                    {$_MENU_AFTER_MESSAGE}
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                    <li class="{if in_array($_routes[0], ['routers', 'pool']) || $_system_menu eq 'network'}active menu-open{/if} treeview">
                        <a href="#">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-sitemap">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path
                                    d="M3 15m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                                <path
                                    d="M15 15m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                                <path
                                    d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                                <path d="M6 15v-1a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v1" />
                                <path d="M12 9l0 3" />
                            </svg> <span>{Lang::T('Network')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-down pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li {if $_routes[0] eq 'routers'}class="active" {/if}><a
                                    href="{Text::url('routers')}">Routers</a></li>
                            <li {if $_routes[0] eq 'pool' and $_routes[1] neq 'port'}class="active" {/if}><a
                                    href="{Text::url('pool/list')}">IP Pool</a></li>
                            <li {if $_routes[0] eq 'pool' and $_routes[1] eq 'port'}class="active" {/if}><a
                                    href="{Text::url('pool/port')}">Port Pool</a></li>
                            {$_MENU_NETWORK}
                        </ul>
                    </li>
                    {$_MENU_AFTER_NETWORKS}
                    {if $_c['radius_enable']}
                    <li class="{if in_array($_routes[0], ['radius']) || $_system_menu eq 'radius'}active menu-open{/if} treeview">
                        <a href="#">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-database-cog">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M4 6c0 1.657 3.582 3 8 3s8 -1.343 8 -3s-3.582 -3 -8 -3s-8 1.343 -8 3" />
                                <path d="M4 6v6c0 1.657 3.582 3 8 3c.21 0 .42 -.003 .626 -.01" />
                                <path d="M20 11.5v-5.5" />
                                <path d="M4 12v6c0 1.657 3.582 3 8 3" />
                                <path d="M19.001 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                <path d="M19.001 15.5v1.5" />
                                <path d="M19.001 21v1.5" />
                                <path d="M22.032 17.25l-1.299 .75" />
                                <path d="M17.27 20l-1.3 .75" />
                                <path d="M15.97 17.25l1.3 .75" />
                                <path d="M20.733 20l1.3 .75" />
                            </svg> <span>{Lang::T('Radius')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-down pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li {if $_routes[0] eq 'radius'}class="active" {/if}><a
                                    href="{Text::url('radius/nas-list')}">{Lang::T('Radius NAS')}</a></li>
                            {$_MENU_RADIUS}
                        </ul>
                    </li>
                    {/if}
                    {$_MENU_AFTER_RADIUS}
                    <li class="{if in_array($_routes[0], ['pages']) || $_system_menu eq 'pages'}active menu-open{/if} treeview">
                        <a href="#">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-html">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M13 16v-8l2 5l2 -5v8" />
                                <path d="M1 16v-8" />
                                <path d="M5 8v8" />
                                <path d="M1 12h4" />
                                <path d="M7 8h4" />
                                <path d="M9 8v8" />
                                <path d="M20 8v8h3" />
                            </svg> <span>{Lang::T("Static Pages")}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-down pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li {if $_routes[0] eq 'pages' && $_routes[1] eq 'Order_Voucher'}class="active" {/if}><a
                                    href="{Text::url('pages/Order_Voucher')}">{Lang::T('Order Voucher')}</a></li>
                            <li {if $_routes[0] eq 'pages' && $_routes[1] eq 'Voucher'}class="active" {/if}><a
                                    href="{Text::url('pages/Voucher')}">{Lang::T('Theme Voucher')}</a></li>
                            <li {if $_routes[0] eq 'pages' && $_routes[1] eq 'Announcement'}class="active" {/if}><a
                                    href="{Text::url('pages/Announcement')}">{Lang::T('Announcement')}</a></li>
                            <li {if $_routes[0] eq 'pages' && $_routes[1] eq 'Announcement_Customer'}class="active" {/if}><a
                                    href="{Text::url('pages/Announcement_Customer')}">{Lang::T('Customer
                                    Announcement')}</a>
                            </li>
                            <li {if $_routes[0] eq 'pages' && $_routes[1] eq 'Registration_Info'}class="active" {/if}><a
                                    href="{Text::url('pages/Registration_Info')}">{Lang::T('Registration Info')}</a>
                            </li>
                            <li {if $_routes[0] eq 'pages' && $_routes[1] eq 'Payment_Info'}class="active" {/if}><a
                                    href="{Text::url('pages/Payment_Info')}">{Lang::T('Payment Info')}</a></li>
                            <li {if $_routes[0] eq 'pages' && $_routes[1] eq 'Privacy_Policy'}class="active" {/if}><a
                                    href="{Text::url('pages/Privacy_Policy')}">{Lang::T('Privacy Policy')}</a></li>
                            <li {if $_routes[0] eq 'pages' && $_routes[1] eq 'Terms_and_Conditions'}class="active" {/if}><a
                                    href="{Text::url('pages/Terms_and_Conditions')}">{Lang::T('Terms and
                                    Conditions')}</a></li>
                            {$_MENU_PAGES}
                        </ul>
                    </li>
                    {/if}
                    {$_MENU_AFTER_PAGES}
                    <li
                        class="{if in_array($_system_menu, ['settings', 'paymentgateway']) || in_array($_routes[0], ['settings', 'paymentgateway', 'customfield', 'widgets', 'pluginmanager'])}active menu-open{/if} treeview">
                        <a href="#">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-adjustments-cog">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M4 10a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                <path d="M6 4v4" />
                                <path d="M6 12v8" />
                                <path d="M13.199 14.399a2 2 0 1 0 -1.199 3.601" />
                                <path d="M12 4v10" />
                                <path d="M12 18v2" />
                                <path d="M16 7a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                <path d="M18 4v1" />
                                <path d="M18 9v2.5" />
                                <path d="M19.001 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                <path d="M19.001 15.5v1.5" />
                                <path d="M19.001 21v1.5" />
                                <path d="M22.032 17.25l-1.299 .75" />
                                <path d="M17.27 20l-1.3 .75" />
                                <path d="M15.97 17.25l1.3 .75" />
                                <path d="M20.733 20l1.3 .75" />
                            </svg> <span>{Lang::T('Settings')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-down pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                            <li {if $_routes[0] eq 'settings' and ($_routes[1] eq 'app' || empty($_routes[1]))}class="active" {/if}><a
                                    href="{Text::url('settings/app')}">{Lang::T('General Settings')}</a></li>
                            <li {if $_routes[0] eq 'settings' and $_routes[1] eq 'localisation'}class="active" {/if}><a
                                    href="{Text::url('settings/localisation')}">{Lang::T('Localisation')}</a></li>
                            <li {if $_routes[0] eq 'customfield'}class="active" {/if}><a
                                    href="{Text::url('customfield')}">{Lang::T('Custom Fields')}</a></li>
                            <li {if $_routes[0] eq 'settings' and $_routes[1] eq 'miscellaneous'}class="active" {/if}><a
                                    href="{Text::url('settings/miscellaneous')}">{Lang::T('Miscellaneous')}</a></li>
                            <li {if $_routes[0] eq 'settings' and $_routes[1] eq 'maintenance'}class="active" {/if}><a
                                    href="{Text::url('settings/maintenance')}">{Lang::T('Maintenance Mode')}</a></li>
                            <li {if $_routes[0] eq 'settings' and $_routes[1] eq 'redis'}class="active" {/if}><a
                                    href="{Text::url('settings/redis')}">{Lang::T('Redis Cache')}</a></li>
                            <li {if $_routes[0] eq 'widgets'}class="active" {/if}><a
                                    href="{Text::url('widgets')}">{Lang::T('Widgets')}</a></li>
                            <li {if $_routes[0] eq 'settings' and $_routes[1] eq 'notifications'}class="active" {/if}><a
                                    href="{Text::url('settings/notifications')}">{Lang::T('Notifications')}</a></li>
                            <li {if $_routes[0] eq 'settings' and $_routes[1] eq 'devices'}class="active" {/if}><a
                                    href="{Text::url('settings/devices')}">{Lang::T('Devices')}</a></li>
                            {/if}
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin','Agent'])}
                            <li {if $_routes[0] eq 'settings' and $_routes[1] eq 'users'}class="active" {/if}><a
                                    href="{Text::url('settings/users')}">{Lang::T('Administrator Users')}</a></li>
                            {/if}
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                            <li {if $_routes[0] eq 'settings' and $_routes[1] eq 'dbstatus'}class="active" {/if}><a
                                    href="{Text::url('settings/dbstatus')}">{Lang::T('Backup/Restore')}</a></li>
                            <li {if $_routes[0] eq 'paymentgateway' || $_system_menu eq 'paymentgateway'}class="active" {/if}>
                                <a href="{Text::url('paymentgateway')}">
                                    <span class="text">{Lang::T('Payment Gateway')}</span>
                                </a>
                            </li>
                            {$_MENU_SETTINGS}
                            <li {if $_routes[0] eq 'pluginmanager'}class="active" {/if}>
                                <a href="{Text::url('pluginmanager')}">
                                    {Lang::T('Plugin Manager (Legacy)')}</a>
                            </li>
                            {/if}
                        </ul>
                    </li>
                    {$_MENU_AFTER_SETTINGS}
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                    <li class="{if in_array($_routes[0], ['logs']) || $_system_menu eq 'logs'}active menu-open{/if} treeview">
                        <a href="#">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-brand-tabler">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M8 9l3 3l-3 3" />
                                <path d="M13 15h3" />
                                <path d="M3 7a4 4 0 0 1 4 -4h10a4 4 0 0 1 4 4v10a4 4 0 0 1 -4 4h-10a4 4 0 0 1 -4 -4z" />
                            </svg> <span>{Lang::T('Logs')}</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-down pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li {if $_routes[0] eq 'logs' and ($_routes[1] eq 'phpnuxbill' || empty($_routes[1]) || $_routes[1] eq 'list')}class="active" {/if}><a
                                    href="{Text::url('logs/phpnuxbill')}">System</a></li>
                            {if $_c['radius_enable']}
                            <li {if $_routes[0] eq 'logs' and $_routes[1] eq 'radius'}class="active" {/if}><a
                                    href="{Text::url('logs/radius')}">Radius</a>
                            </li>
                            {/if}
                            <li {if $_routes[0] eq 'logs' and $_routes[1] eq 'message'}class="active" {/if}><a
                                    href="{Text::url('logs/message')}">Message</a></li>
                            {$_MENU_LOGS}
                        </ul>
                    </li>
                    {/if}
                    {$_MENU_AFTER_LOGS}
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                    <li {if $_routes[0] eq 'settings' && $_routes[1] eq 'docs'}class="active" {/if}>
                        <a
                            href="{if $_c['docs_clicked'] != 'yes'}{Text::url('settings/docs')}{else}{$app_url}/docs{/if}">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-file-type-doc">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
                                <path d="M5 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1z" />
                                <path d="M20 16.5a1.5 1.5 0 0 0 -3 0v3a1.5 1.5 0 0 0 3 0" />
                                <path
                                    d="M12.5 15a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1 -3 0v-3a1.5 1.5 0 0 1 1.5 -1.5z" />
                            </svg>
                            <span class="text">{Lang::T('Documentation')}</span>
                            {if $_c['docs_clicked'] != 'yes'}
                            <span class="pull-right-container"><small
                                     class="label pull-right bg-green">New</small></span>
                            {/if}
                        </a>
                    </li>
                    <li {if $_system_menu eq 'community' || $_routes[0] eq 'community'}class="active" {/if}>
                        <a href="{Text::url('community')}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-messages">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M21 14l-3 -3h-7a1 1 0 0 1 -1 -1v-6a1 1 0 0 1 1 -1h9a1 1 0 0 1 1 1v10" />
                            <path d="M14 15v2a1 1 0 0 1 -1 1h-7l-3 3v-10a1 1 0 0 1 1 -1h2" />
                        </svg>
                            <span class="text">Community</span>
                        </a>
                    </li>
                    {/if}
                    {$_MENU_AFTER_COMMUNITY}
                </ul>
            </section>
        </aside>

        {if $_c['maintenance_mode'] == 1}
        <div class="notification-top-bar">
            <p>{Lang::T('The website is currently in maintenance mode, this means that some or all functionality may be
                unavailable to regular users during this time.')}<small> &nbsp;&nbsp;<a
                        href="{Text::url('settings/maintenance')}">{Lang::T('Turn Off')}</a></small></p>
        </div>
        {/if}

        <div class="content-wrapper">
            <section class="content-header">
                <h1>
                    {$_title}
                </h1>
            </section>
           
            <section class="content fade-in" {if $_system_menu neq 'dashboard' } id="dashboard-content" 
                {/if}>
                {if isset($notify)}
                <script>
                    // Display SweetAlert toast notification
                    Swal.fire({
                        icon: '{if $notify_t == "s"}success{else}error{/if}',
                        title: {$notify|escape|replace:'&lt;br&gt;':'<br>'|replace:'&lt;b&gt;':'<b>'|replace:'&lt;/b&gt;':'</b>'|json_encode},
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