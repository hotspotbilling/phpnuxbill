{include file="sections/user-header.tpl"}

					<div class="row">
						<div class="col-md-12">
							<div class="dash-head clearfix mt15 mb20">
								<div class="left">
									<h4 class="mb5 text-light">{$_L['Welcome']}, {$_user['fullname']}</h4>
									<p>{$_L['Welcome_Text_User']}</p>
									<ul>
										<li> {$_L['Account_Information']}</li>
										<li> <a href="{$_url}voucher/activation">{$_L['Voucher_Activation']}</a></li>
										<li> <a href="{$_url}voucher/list-activated">{$_L['List_Activated_Voucher']}</a></li>
										<li> <a href="{$_url}accounts/change-password">{$_L['Change_Password']}</a></li>
										<li> {$_L['Order_Voucher']}</li>
										<li> {$_L['Private_Message']}</li>
									</ul>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
							<div class="panel mb20 panel-primary panel-hovered">
								<div class="panel-heading">{$_L['Account_Information']}</div>
								<div class="panel-body">
									<div class="row">
			            				<div class="col-sm-3">
					               			<p class="small text-success text-uppercase text-normal">{$_L['Username']}</p>
					               			<p class="small mb15">{$_bill['username']}</p>
					                	</div>
			            				<div class="col-sm-3">
					               			<p class="small text-primary text-uppercase text-normal">{$_L['Plan_Name']}</p>
					               			<p class="small mb15">{$_bill['namebp']}</p>
					                	</div>
					                	<div class="col-sm-3">
					                		<p class="small text-info text-uppercase text-normal">{$_L['Created_On']}</p>
					               			<p class="small mb15">{date($_c['date_format'], strtotime($_bill['recharged_on']))} {$_bill['time']}</p>
					                	</div>
					                	<div class="col-sm-3">
					                		<p class="small text-danger text-uppercase text-normal">{$_L['Expires_On']}</p>
					               			<p class="small mb15">{date($_c['date_format'], strtotime($_bill['expiration']))} {$_bill['time']}</p>
					                	</div>
									</div>
								</div>
							</div>
						</div>
					</div>

{include file="sections/user-footer.tpl"}
