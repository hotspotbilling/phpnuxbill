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

<body id="app" class="app off-canvas">

	<header class="site-head" id="site-head">
		<ul class="list-unstyled left-elems">
			<li>
				<a href="#" class="nav-trigger ion ion-drag"></a>
			</li>

			<li>
				<div class="site-logo visible-xs">
					<a href="{$_url}home" class="text-uppercase h3">
						<span class="text">{$_L['Logo']}</span>
					</a>
				</div>
			</li>
			<li class="fullscreen hidden-xs">
				<a href="#"><i class="ion ion-qr-scanner"></i></a>
			</li>

			<li class="notify-drop hidden hidden-xs dropdown">
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
		<aside class="nav-wrap" id="site-nav" data-perfect-scrollbar>
			<div class="nav-head">
				<a href="{$_url}home" class="site-logo text-uppercase">
					<i class="ion ion-wifi"></i>
					<span class="text">{$_L['Logo']}</span>
				</a>
			</div>

			<nav class="site-nav clearfix" role="navigation">
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

				<ul class="list-unstyled clearfix nav-list mb15">
					<li {if $_system_menu eq 'home'}class="active"{/if}>
						<a href="{$_url}home">
							<i class="ion ion-monitor"></i>
							<span class="text">{$_L['Dashboard']}</span>
						</a>
					</li>
                    {$_MENU_AFTER_DASHBOARD}
					<li {if $_system_menu eq 'order'}class="open"{/if}>
						<a href="#" >
							<i class="ion ion-ios-cart"></i>
							<span class="text">{Lang::T('ORDER')}</span>
							<i class="arrow ion-chevron-left"></i>
						</a>
						<ul class="inner-drop list-unstyled">
							<li {if $_system_menu eq 'order'}class="active"{/if}><a href="{$_url}order/voucher">Voucher</a></li>
                            {if $_c['payment_gateway'] != 'none' or $_c['payment_gateway'] == '' }
                                <li {if $_system_menu eq 'order'}class="active"{/if}><a href="{$_url}order/package">{Lang::T('Package')}</a></li>
                                <li {if $_system_menu eq 'order'}class="active"{/if}><a href="{$_url}order/history">{Lang::T('History')}</a></li>
                            {/if}
                            {$_MENU_ORDER}
						</ul>
					</li>
                    {$_MENU_AFTER_ORDER}
					<li {if $_system_menu eq 'voucher'}class="active"{/if}>
						<a href="{$_url}voucher/list-activated">
							<i class="ion ion-card"></i>
							<span class="text">{Lang::T('History')}</span>
						</a>
					</li>
                    {$_MENU_AFTER_HISTORY}
					<li {if $_system_menu eq 'accounts'}class="open"{/if}>
						<a href="#" >
							<i class="ion ion-gear-a"></i>
							<span class="text">{$_L['My_Account']}</span>
							<i class="arrow ion-chevron-left"></i>
						</a>
						<ul class="inner-drop list-unstyled">
							<li {if $_system_menu eq 'accounts'}class="active"{/if}><a href="{$_url}accounts/profile">{$_L['My_Profile']}</a></li>
							<li {if $_system_menu eq 'accounts'}class="active"{/if}><a href="{$_url}accounts/change-password">{$_L['Change_Password']}</a></li>
							{$_MENU_ACCOUNTS}
						</ul>
					</li>
                    {$_MENU_AFTER_ACCOUNTS}
				</ul>

			</nav>

			<footer class="nav-foot">
				<p>{date('Y')} &copy; <span>{$_c['CompanyName']}</span></p>
			</footer>
		</aside>
		<div class="content-container" id="content">
			<div class="page {if $_system_menu eq 'dashboard'}page-dashboard{/if}">

			{if isset($notify)}
				{$notify}
			{/if}