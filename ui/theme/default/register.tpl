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
	<div class="main-container clearfix">
		<div class="content-container" id="content">
			<div class="page page-auth" style="margin-top:180px">
				<div class="auth-container">
					<div class="form-head mb20">
						<h1 class="site-logo h2 mb5 mt5 text-center text-uppercase text-bold"><a href="./">{$_c['CompanyName']}</a></h1>
						<h5 class="text-normal h5 text-center">{$_L['Register_Member']}</h5>
					</div> 
					{if isset($notify)}
						{$notify}
					{/if}
					<div class="form-container">
						<form class="form-horizontal" action="{$_url}register/post" method="post">
							<div class="md-input-container md-float-label">
								<input type="text" required class="md-input" id="username" value="{$username}" placeholder="{$_L['Phone_Number']}" name="username">
								<label>{$_L['Username']}</label>
							</div>
							<div class="md-input-container md-float-label">
								<input type="text" required class="md-input" id="fullname" value="{$fullname}" name="fullname">
								<label>{$_L['Full_Name']}</label>
							</div>
							<div class="md-input-container md-float-label">
								<input type="password" required class="md-input" id="password" name="password">
								<label>{$_L['Password']}</label>
							</div>
							<div class="md-input-container md-float-label">
								<input type="password" required class="md-input" id="cpassword" name="cpassword">
								<label>{$_L['Confirm_Password']}</label>
							</div>
							<div class="md-input-container md-float-label">
								<input type="text" name="address" id="address" value="{$address}" class="md-input">
								<label>{$_L['Address']}</label>
							</div>
							<div class="md-input-container md-float-label">
								<input type="text" required class="md-input" value="{$phonenumber}" id="phonenumber" name="phonenumber">
								<label>{$_L['Phone_Number']}</label>
							</div>
							<div class="md-input-container md-float-label">
								<input type="text" required class="md-input" id="kodevoucher" name="kodevoucher">
								<label>{$_L['Code_Voucher']}</label>
							</div>
							<div class="btn-group btn-group-justified mb15">
								<div class="btn-group">
									<button class="btn btn-primary waves-effect waves-light" type="submit">{$_L['Register']}</button>
								</div>
								<div class="btn-group">
									<a href="{$_url}login" class="btn btn-success">{$_L['Cancel']}</a>
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