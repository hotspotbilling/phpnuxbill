<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<title>{$_title} - {$_L['Register']}</title>
	<link rel="shortcut icon" href="{$_theme}/images/logo.png" type="image/x-icon" />

	<!-- Icons -->
	<link rel="stylesheet" href="{$_theme}/fonts/ionicons/css/ionicons.min.css">
	<link rel="stylesheet" href="{$_theme}/fonts/font-awesome/css/font-awesome.min.css">

	<!-- Plugins -->
	<link rel="stylesheet" href="{$_theme}/styles/plugins/waves.css">
	<link rel="stylesheet" href="{$_theme}/styles/plugins/perfect-scrollbar.css">

	<!-- Css/Less Stylesheets -->
	<link rel="stylesheet" href="{$_theme}/styles/bootstrap.min.css">
	<link rel="stylesheet" href="{$_theme}/styles/main.min.css">

 	<!-- <link href='http://fonts.googleapis.com/css?family=Roboto:400,500,700,300' rel='stylesheet' type='text/css'> -->
	<!-- Match Media polyfill for IE9 -->
	<!--[if IE 9]> <script src="{$_theme}/scripts/ie/matchMedia.js"></script>  <![endif]-->

</head>
<body id="app" class="app off-canvas body-full">

	<div class="container">
		<div class="hidden-xs" style="height:150px"></div>
		<div class="form-head mb20">
			<h1 class="site-logo h2 mb5 mt5 text-center text-uppercase text-bold" style="text-shadow: 2px 2px 4px #757575;">{$_c['CompanyName']}</h1>
			<hr>
		</div>
		{if isset($notify)}
			<div class="row">
				<div class="col-md-6 col-md-offset-3">
					{$notify}
				</div>
			</div>
		{/if}
		<div class="row">
            <div class="col-md-2">
            </div>
			<div class="col-md-4">
				<div class="panel panel-default">
				<div class="panel-heading">{$_L['Registration_Info']}</div>
				<div class="panel-body" style="height:375px;max-height:375px;overflow:scroll;">
					{include file="$_path/../pages/Registration_Info.html"}
				</div>
				</div>
			</div>
			<form class="form-horizontal" action="{$_url}register" method="post">
			<div class="col-md-4">
				<div class="panel panel-default">
					<div class="panel-heading">1. {$_L['Register_Member']}</div>
					<div class="panel-body">
						<div class="form-container">
							<div class="md-input-container md-float-label">
								<input type="text" required class="md-input" id="username" placeholder="{$_L['Phone_Number']}" name="username">
								<label>{$_L['Phone_Number']}</label>
							</div>
							<div class="btn-group btn-group-justified mb15">
								<div class="btn-group">
									<button class="btn btn-primary waves-effect waves-light" type="submit">{Lang::T('Request OTP')}</button>
								</div>
								<div class="btn-group">
									<a href="{$_url}login" class="btn btn-success">{$_L['Cancel']}</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			</form>
		</div>
	</div>
	<script src="scripts/vendors.js"></script>
</body>
</html>