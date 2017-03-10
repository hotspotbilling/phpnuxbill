<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<title>{$_title}</title>
	<link rel="shortcut icon" href="{$_theme}/images/logo.png" type="image/x-icon" />
	
	<!-- Icons -->
	<link rel="stylesheet" href="{$_theme}/fonts/ionicons/css/ionicons.min.css">
	<link rel="stylesheet" href="{$_theme}/fonts/font-awesome/css/font-awesome.min.css">

	<!-- Plugins -->
	<link rel="stylesheet" href="{$_theme}/styles/plugins/waves.css">
	<link rel="stylesheet" href="{$_theme}/styles/plugins/perfect-scrollbar.css">
	<link rel="stylesheet" href="{$_theme}/styles/plugins/select2.css">
	<link rel="stylesheet" href="{$_theme}/styles/plugins/bootstrap-colorpicker.css">
	<link rel="stylesheet" href="{$_theme}/styles/plugins/bootstrap-slider.css">
	<link rel="stylesheet" href="{$_theme}/styles/plugins/bootstrap-datepicker.css">
	<link rel="stylesheet" href="{$_theme}/styles/plugins/summernote.css">

	<!-- Css/Less Stylesheets -->
	<link rel="stylesheet" href="{$_theme}/styles/bootstrap.min.css">
	<link rel="stylesheet" href="{$_theme}/styles/main.min.css">
	
	<link href='http://fonts.googleapis.com/css?family=Roboto:400,500,700,300' rel='stylesheet' type='text/css'>
	
	<!-- Match Media polyfill for IE9 -->
	<!--[if IE 9]> <script src="{$_theme}/scripts/ie/matchMedia.js"></script>  <![endif]--> 
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

			<li class="notify-drop hidden-xs dropdown">
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
					<img src="system/uploads/user.jpg" alt="admin">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
					<div class="group">
						<div class="dropdown">{$_user['fullname']}<span class="caret"></span>
							<ul class="dropdown-menu">
								<li><a href="{$_url}accounts/profile"><i class="ion ion-person"></i> {$_L['My_Account']}<div class="ripple-wrapper"></div></a></li>
								<li><a href="{$_url}accounts/change-password"><i class="ion ion-settings"></i> {$_L['Change_Password']}</a></li>
								<li><a href="{$_url}logout"><i class="ion ion-power"></i> {$_L['Logout']}</a></li>
							</ul>
						</div>
						<small class="desig">{$_L['Member']}</small>
					</div>
					</a>
				</div>
				
				<ul class="list-unstyled clearfix nav-list mb15">
					<li {if $_system_menu eq 'home'}class="active"{/if}>
						<a href="{$_url}home">
							<i class="ion ion-monitor"></i>
							<span class="text">{$_L['Dashboard']}</span>
						</a>
					</li>
					<li {if $_system_menu eq 'pm'}class="open"{/if}>
						<a href="#">
							<i class="ion ion-email"></i>
							<span class="text">{$_L['Private_Message']}</span>
							<i class="arrow ion-chevron-left"></i>
						</a>
						<ul class="inner-drop list-unstyled">
							<li {if $_system_menu eq 'pm'}class="active"{/if}><a href="{$_url}pm/inbox">{$_L['Inbox']}</a></li>
							<li {if $_system_menu eq 'pm'}class="active"{/if}><a href="{$_url}pm/outbox">{$_L['Outbox']}</a></li>
							<li {if $_system_menu eq 'pm'}class="active"{/if}><a href="{$_url}pm/compose">{$_L['Compose']}</a></li>
						</ul>
					</li>
				
					<li {if $_system_menu eq 'voucher'}class="open"{/if}>
						<a href="#">
							<i class="ion ion-card"></i>
							<span class="text">{$_L['Voucher']}</span>
							<i class="arrow ion-chevron-left"></i>
						</a>
						<ul class="inner-drop list-unstyled">
							<li {if $_system_menu eq 'voucher'}class="active"{/if}><a href="{$_url}voucher/activation">{$_L['Voucher_Activation']}</a></li>
							<li {if $_system_menu eq 'voucher'}class="active"{/if}><a href="{$_url}voucher/list-activated">{$_L['List_Activated_Voucher']}</a></li>
						</ul>
					</li>
					<li {if $_system_menu eq 'order'}class="active"{/if}>
						<a href="{$_url}order">
							<i class="ion ion-ios-cart"></i>
							<span class="text">{$_L['Order_Voucher']}</span>
						</a>
					</li>
					<li {if $_system_menu eq 'accounts'}class="open"{/if}>
						<a href="#">
							<i class="ion ion-gear-a"></i>
							<span class="text">{$_L['My_Account']}</span>
							<i class="arrow ion-chevron-left"></i>
						</a>
						<ul class="inner-drop list-unstyled">
							<li {if $_system_menu eq 'accounts'}class="active"{/if}><a href="{$_url}accounts/profile">{$_L['My_Profile']}</a></li>
							<li {if $_system_menu eq 'accounts'}class="active"{/if}><a href="{$_url}accounts/change-password">{$_L['Change_Password']}</a></li>
							<li>&nbsp;</li>
						</ul>
					</li>
				
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