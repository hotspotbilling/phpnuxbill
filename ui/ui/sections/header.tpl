<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<title>{$_title}</title>
	<link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

	<!-- Icons -->
	<link rel="stylesheet" href="ui/ui/fonts/ionicons/css/ionicons.min.css">
	<link rel="stylesheet" href="ui/ui/fonts/font-awesome/css/font-awesome.min.css">

	<!-- Plugins -->
	<link rel="stylesheet" href="ui/ui/styles/plugins/waves.css">
	<link rel="stylesheet" href="ui/ui/styles/plugins/perfect-scrollbar.css">
	<link rel="stylesheet" href="ui/ui/styles/plugins/select2.css">
	<link rel="stylesheet" href="ui/ui/styles/plugins/bootstrap-colorpicker.css">
	<link rel="stylesheet" href="ui/ui/styles/plugins/bootstrap-slider.css">
	<link rel="stylesheet" href="ui/ui/styles/plugins/bootstrap-datepicker.css">
	<link rel="stylesheet" href="ui/ui/styles/plugins/summernote.css">

	<!-- Css/Less Stylesheets -->
	<link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">
	<link rel="stylesheet" href="ui/ui/styles/main.min.css">


	<!-- Match Media polyfill for IE9 -->
	<!--[if IE 9]> <script src="ui/ui/scripts/ie/matchMedia.js"></script>  <![endif]-->
{if isset($xheader)}
	{$xheader}
{/if}

</head>

<body id="app" class="app off-canvas nav-expand">

	<header class="site-head" id="site-head">
		<ul class="list-unstyled left-elems">
			<li>
				<a href="#" class="nav-trigger ion ion-drag"></a>
			</li>
			{if $_admin['user_type'] eq 'Admin' || $_admin['user_type'] eq 'Sales'}
			<li>
				<div class="form-search hidden-xs">
					<form id="site-search" method="post" action="{$_url}customers/list/">
						<input type="search" class="form-control" name="username" placeholder="{$_L['Search_Contact']}">
						<button type="submit" class="ion ion-ios-search-strong"></button>
					</form>
				</div>
			</li>
			{/if}
			<li>
				<div class="site-logo visible-xs">
					<a href="{$_url}dashboard" class="text-uppercase h3">
						<span class="text">{Lang::T('Logo')}</span>
					</a>
				</div>
			</li>
			<li class="fullscreen hidden-xs">
				<a href="#"><i class="ion ion-qr-scanner"></i></a>
			</li>
			<!-- Notification on progress, hide it  -->
			<li class="notify-drop hidden-xs dropdown hidden">
				<a href="#" data-toggle="dropdown">
					<i class="ion ion-chatboxes"></i>
					<span class="badge badge-danger badge-xs circle">3</span>
				</a>
				<div class="panel panel-default dropdown-menu">
					<div class="panel-heading">
						You have 3 new message
						<a href="#" class="right btn btn-xs btn-pink mt-3">Show All</a>
					</div>
					<div class="panel-body">
						Coming Soon!!! Next Version...
					</div>
				</div>
			</li>
		</ul>
		<ul class="list-unstyled right-elems">
			<li class="logout hidden-xs">
				<a href="{$_url}logout"><i class="ion ion-power"></i> {$_L['Logout']}</a></a>
			</li>
		</ul>
	</header>

	<div class="main-container clearfix">
		<aside class="nav-wrap nav-expand" id="site-nav" data-perfect-scrollbar>
			<div class="nav-head">
				<a href="{$_url}dashboard" class="site-logo text-uppercase">
					<i class="ion ion-wifi"></i>
					<span class="text">{$_L['Logo']}</span>
				</a>
			</div>

			<nav class="site-nav clearfix" role="navigation">
			{if $_admin['user_type'] eq 'Admin' || $_admin['user_type'] eq 'Sales'}
				<div class="profile clearfix mb15">
					<img src="https://robohash.org/{$_admin['id']}?set=set3&size=100x100&bgset=bg1" alt="admin">
					<div class="group">
						<div class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">{$_admin['fullname']}<span class="caret"></a></span>
							<ul class="dropdown-menu">
								<li><a href="{$_url}settings/users-edit/{$_admin['id']}"><i class="ion ion-person"></i> {$_L['My_Account']}<div class="ripple-wrapper"></div></a></li>
								<li><a href="{$_url}settings/change-password"><i class="ion ion-settings"></i> {$_L['Change_Password']}</a></li>
								<li><a href="{$_url}logout"><i class="ion ion-power"></i> {$_L['Logout']}</a></li>
							</ul>
						</div>
						<small class="desig">{if $_admin['user_type'] eq 'Admin'} {$_L['Administrator']} {else} {$_L['Sales']} {/if}</small>
					</div>
				</div>
			{else}
				<div class="profile clearfix mb15">
					<img src="https://robohash.org/{$_user['id']}?set=set3&size=100x100&bgset=bg1" alt="admin">
					<div class="group">
						<div class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">{$_user['fullname']}<span class="caret"></span></a>
							<ul class="dropdown-menu">
								<li><a href="{$_url}accounts/profile"><i class="ion ion-person"></i> {$_L['My_Account']}<div class="ripple-wrapper"></div></a></li>
								<li><a href="{$_url}accounts/change-password"><i class="ion ion-settings"></i> {$_L['Change_Password']}</a></li>
								<li><a href="{$_url}logout"><i class="ion ion-power"></i> {$_L['Logout']}</a></li>
							</ul>
						</div>
						<small class="desig">{$_L['Member']}</small>
					</div>
				</div>
			{/if}

				<ul id="leftMenu" class="list-unstyled clearfix nav-list mb15">
					<li {if $_system_menu eq 'dashboard'}class="active"{/if}>
						<a href="{$_url}dashboard">
							<i class="ion ion-monitor"></i>
							<span class="text">{$_L['Dashboard']}</span>
						</a>
					</li>
                    {$_MENU_AFTER_DASHBOARD}
				{if $_admin['user_type'] eq 'Admin' || $_admin['user_type'] eq 'Sales'}
					<li {if $_system_menu eq 'customers'}class="open"{/if}>
						<a href="#">
							<i class="ion ion-android-contacts"></i>
							<span class="text">{$_L['Customers']}</span>
							<i class="arrow ion-chevron-left"></i>
						</a>
						<ul class="inner-drop list-unstyled">
							<li {if $_routes[1] eq 'add'}class="active"{/if}><a href="{$_url}customers/add">{$_L['Add_Contact']}</a></li>
							<li {if $_routes[1] eq 'list'}class="active"{/if}><a href="{$_url}customers/list">{$_L['List_Contact']}</a></li>
                            {$_MENU_CUSTOMERS}
						</ul>
					</li>
                    {$_MENU_AFTER_CUSTOMERS}
					<li {if $_system_menu eq 'prepaid'}class="open"{/if}>
						<a href="#">
							<i class="ion ion-card"></i>
							<span class="text">{$_L['Prepaid']}</span>
							<i class="arrow ion-chevron-left"></i>
						</a>
						<ul class="inner-drop list-unstyled">
							<li {if $_routes[1] eq 'list'}class="active"{/if}><a href="{$_url}prepaid/list">{$_L['Prepaid_User']}</a></li>
							<li {if $_routes[1] eq 'voucher'}class="active"{/if}><a href="{$_url}prepaid/voucher">{$_L['Prepaid_Vouchers']}</a></li>
							<li {if $_routes[1] eq 'refill'}class="active"{/if}><a href="{$_url}prepaid/refill">{$_L['Refill_Account']}</a></li>
							<li {if $_routes[1] eq 'recharge'}class="active"{/if}><a href="{$_url}prepaid/recharge">{$_L['Recharge_Account']}</a></li>
                            {$_MENU_PREPAID}
						</ul>
					</li>
                    {$_MENU_AFTER_PREPAID}
					<li {if $_system_menu eq 'services'}class="open"{/if}>
						<a href="#">
							<i class="ion ion-cube"></i>
							<span class="text">{$_L['Services']}</span>
							<i class="arrow ion-chevron-left"></i>
						</a>
						<ul class="inner-drop list-unstyled">
							<li {if $_routes[1] eq 'hotspot'}class="active"{/if}><a href="{$_url}services/hotspot">{$_L['Hotspot_Plans']}</a></li>
							<li {if $_routes[1] eq 'pppoe'}class="active"{/if}><a href="{$_url}services/pppoe">{$_L['PPPOE_Plans']}</a></li>
							<li {if $_routes[1] eq 'list'}class="active"{/if}><a href="{$_url}bandwidth/list">{$_L['Bandwidth_Plans']}</a></li>
                            {$_MENU_SERVICES}
						</ul>
					</li>
                    {$_MENU_AFTER_SERVICES}
					<li {if $_system_menu eq 'reports'}class="open"{/if}>
						<a href="#">
							<i class="ion ion-clipboard"></i>
							<span class="text">{$_L['Reports']}</span>
							<i class="arrow ion-chevron-left"></i>
						</a>
						<ul class="inner-drop list-unstyled">
							<li {if $_routes[1] eq 'daily-report'}class="active"{/if}><a href="{$_url}reports/daily-report">{$_L['Daily_Report']}</a></li>
							<li {if $_routes[1] eq 'by-period'}class="active"{/if}><a href="{$_url}reports/by-period">{$_L['Period_Reports']}</a></li>
                            {$_MENU_REPORTS}
						</ul>
					</li>
                    {$_MENU_AFTER_REPORTS}
				{/if}
				{if $_admin['user_type'] eq 'Admin'}
					<li {if $_system_menu eq 'network'}class="open"{/if}>
						<a href="#">
							<i class="ion ion-network"></i>
							<span class="text">{$_L['Network']}</span>
							<i class="arrow ion-chevron-left"></i>
						</a>
						<ul class="inner-drop list-unstyled">
							<li {if $_routes[0] eq 'routers' and $_routes[1] eq 'list'}class="active"{/if}><a href="{$_url}routers/list">{$_L['Routers']}</a></li>
							<li {if $_routes[0] eq 'pool' and $_routes[1] eq 'list'}class="active"{/if}><a href="{$_url}pool/list">{$_L['Pool']}</a></li>
                            {$_MENU_NETWORK}
						</ul>
					</li>
                    {$_MENU_AFTER_NETWORKS}
					<li {if $_system_menu eq 'pages'}class="open"{/if}>
						<a href="#">
							<i class="ion ion-document"></i>
							<span class="text">{$_L['Static_Pages']}</span>
							<i class="arrow ion-chevron-left"></i>
						</a>
						<ul class="inner-drop list-unstyled">
							<li {if $_routes[1] eq 'Order_Voucher'}class="active"{/if}><a href="{$_url}pages/Order_Voucher">{$_L['Order_Voucher']}</a></li>
							<li {if $_routes[1] eq 'Voucher'}class="active"{/if}><a href="{$_url}pages/Voucher">{$_L['Voucher']} Template</a></li>
							<li {if $_routes[1] eq 'Announcement'}class="active"{/if}><a href="{$_url}pages/Announcement">{$_L['Announcement']} Editor</a></li>
							<li {if $_routes[1] eq 'Registration_Info'}class="active"{/if}><a href="{$_url}pages/Registration_Info">{$_L['Registration_Info']} Editor</a></li>
                            {$_MENU_PAGES}
						</ul>
					</li>
                    {$_MENU_AFTER_PAGES}
					<li {if $_system_menu eq 'settings'}class="open"{/if}>
						<a href="#">
							<i class="ion ion-gear-a"></i>
							<span class="text">{$_L['Settings']}</span>
							<i class="arrow ion-chevron-left"></i>
						</a>
						<ul class="inner-drop list-unstyled">
							<li {if $_routes[1] eq 'app'}class="active"{/if}><a href="{$_url}settings/app">{$_L['General_Settings']}</a></li>
							<li {if $_routes[1] eq 'localisation'}class="active"{/if}><a href="{$_url}settings/localisation">{$_L['Localisation']}</a></li>
							<li {if $_routes[1] eq 'users'}class="active"{/if}><a href="{$_url}settings/users">{$_L['Administrator_Users']}</a></li>
							<li {if $_routes[1] eq 'dbstatus'}class="active"{/if}><a href="{$_url}settings/dbstatus">{$_L['Backup_Restore']}</a></li>
							{$_MENU_SETTINGS}
						</ul>
					</li>
                    {$_MENU_AFTER_SETTINGS}
                    <li {if $_system_menu eq 'paymentgateway'}class="active"{/if}>
						<a href="{$_url}paymentgateway">
							<i class="ion ion-cash"></i>
							<span class="text">{Lang::T('Payment Gateway')}</span>
						</a>
					</li>
                    {$_MENU_AFTER_PAYMENTGATEWAY}
					<li {if $_system_menu eq 'community'}class="active"{/if}>
						<a href="{$_url}community">
							<i class="ion ion-chatboxes"></i>
							<span class="text">{Lang::T('Community')}</span>
						</a>
					</li>
				{/if}
				</ul>

			</nav>

			<footer class="nav-foot">
				<p>{date('Y')} &copy; <span>{$_c['CompanyName']}</span></p>
			</footer>
		</aside>
		<div class="content-container" id="content">
			<div class="page {if $_system_menu eq 'dashboard'}page-dashboard{/if}{if $_system_menu eq 'reports'}page-invoice{/if}">

			{if isset($notify)}
				{$notify}
			{/if}