<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<title>{$_title} - {$_L['Login']}</title>
	<link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

	<!-- Icons -->
	<link rel="stylesheet" href="ui/ui/fonts/ionicons/css/ionicons.min.css">
	<link rel="stylesheet" href="ui/ui/fonts/font-awesome/css/font-awesome.min.css">

	<!-- Plugins -->
	<link rel="stylesheet" href="ui/ui/styles/plugins/waves.css">
	<link rel="stylesheet" href="ui/ui/styles/plugins/perfect-scrollbar.css">

	<!-- Css/Less Stylesheets -->
	<link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">
	<link rel="stylesheet" href="ui/ui/styles/main.min.css">

	<!-- Match Media polyfill for IE9 -->
	<!--[if IE 9]> <script src="ui/ui/scripts/ie/matchMedia.js"></script>  <![endif]-->

</head>
<body id="app" class="app off-canvas body-full">
	<div class="main-container clearfix">
		<div class="content-container" id="content">
			<div class="page page-auth">
				<div class="auth-container">
					<div class="form-head mb20">
						<h1 class="site-logo h2 mb5 mt5 text-center text-uppercase text-bold">{$_L['Logo']}</h1>
						<h5 class="text-normal h5 text-center">{$_L['Sign_In_Admin']}</h5>
					</div>
				{if isset($notify)}
					{$notify}
				{/if}
					<div class="form-container">
						<form class="form-horizontal" action="{$_url}admin/post" method="post">
							<div class="md-input-container md-float-label">
								<input type="text" name="username" class="md-input">
								<label>{$_L['Username']}</label>
							</div>

							<div class="md-input-container md-float-label">
								<input type="password" name="password" class="md-input">
								<label>{$_L['Password']}</label>
							</div>

							<div class="clearfix">
								<div class="ui-checkbox ui-checkbox-primary right">
									<label>
										<input type="checkbox">
										<span>Remember me</span>
									</label>
								</div>
							</div>
							<div class="btn-group btn-group-justified mb15">
								<div class="btn-group">
									<button type="submit" class="btn btn-success">{$_L['Login']}</button>
								</div>
							</div>
						</form>
					</div>

				</div>
			</div>
		</div>
	</div>
	<script src="scripts/vendors.js"></script>
</body>
</html>