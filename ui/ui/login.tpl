<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<title>{$_title} - {$_L['Login']}</title>
	<link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

	<!-- Css/Less Stylesheets -->
	<link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">
	<link rel="stylesheet" href="ui/ui/styles/main.min.css">

	<!-- Match Media polyfill for IE9 -->
	<!--[if IE 9]> <script src="ui/ui/scripts/ie/matchMedia.js"></script>  <![endif]-->

</head>
<body>
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
			<div class="col-md-4 col-md-offset-2">
				<div class="panel panel-default">
				<div class="panel-heading">{$_L['Announcement']}</div>
				<div class="panel-body" style="height:296px;max-height:296px;overflow:scroll;">
					{include file="$_path/../pages/Announcement.html"}
				</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="panel panel-default">
					<div class="panel-heading">{$_L['Sign_In_Member']}</div>
					<div class="panel-body" style="height:296px;max-height:296px;">
						<div class="form-container">
							<form class="form-horizontal" action="{$_url}login/post" method="post">
								<div class="md-input-container md-float-label">
									<input type="text" name="username" placeholder="{$_L['Phone_Number']}" class="md-input">
									<label>{$_L['Username']}</label>
								</div>

								<div class="md-input-container md-float-label">
									<input type="password" name="password" placeholder="{$_L['Password']}" class="md-input">
									<label>{$_L['Password']}</label>
								</div>

								<div class="clearfix hidden">
									<div class="ui-checkbox ui-checkbox-primary right">
										<label>
											<input type="checkbox">
											<span>Remember me</span>
										</label>
									</div>
								</div>
								<div class="btn-group btn-group-justified mb15">
									<div class="btn-group">
										<button type="submit" class="btn btn-primary">{$_L['Login']}</button>
									</div>
									<div class="btn-group">
										<a href="{$_url}register" class="btn btn-success">{$_L['Register']}</a>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script src="scripts/vendors.js"></script>
</body>
</html>