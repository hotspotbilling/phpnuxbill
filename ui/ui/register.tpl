<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<title>{$_title} - {$_L['Register']}</title>
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
			<div class="col-md-4">
				<div class="panel panel-default">
				<div class="panel-heading">{$_L['Registration_Info']}</div>
				<div class="panel-body" style="height:375px;max-height:375px;overflow:scroll;">
					{include file="$_path/../pages/Registration_Info.html"}
				</div>
				</div>
			</div>
			<form class="form-horizontal" action="{$_url}register/post" method="post">
			<div class="col-md-4">
				<div class="panel panel-default">
					<div class="panel-heading">1. {$_L['Register_Member']}</div>
					<div class="panel-body">
						<div class="form-container">
							<div class="md-input-container md-float-label">
								<input type="text" required class="md-input" id="username" value="{$username}" placeholder="{$_L['Phone_Number']}" name="username">
								<label>{$_L['Phone_Number']}</label>
							</div>
							<div class="md-input-container md-float-label">
								<input type="text" required class="md-input" id="fullname" value="{$fullname}" name="fullname">
								<label>{$_L['Full_Name']}</label>
							</div>
							<div class="md-input-container md-float-label">
								<input type="text" name="address" id="address" value="{$address}" class="md-input">
								<label>{$_L['Address']}</label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="panel panel-default">
					<div class="panel-heading">2. {$_L['Password']}</div>
					<div class="panel-body">
						<div class="form-container">
							<div class="md-input-container md-float-label">
								<input type="password" required class="md-input" id="password" name="password">
								<label>{$_L['Password']}</label>
							</div>
							<div class="md-input-container md-float-label">
								<input type="password" required class="md-input" id="cpassword" name="cpassword">
								<label>{$_L['Confirm_Password']}</label>
							</div>
							<div class="btn-group btn-group-justified mb15">
								<div class="btn-group">
									<button class="btn btn-primary waves-effect waves-light" type="submit">{$_L['Register']}</button>
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
{if $_c['tawkto'] != ''}
<!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/{$_c['tawkto']}';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->
{/if}
	<script src="scripts/vendors.js"></script>
</body>
</html>