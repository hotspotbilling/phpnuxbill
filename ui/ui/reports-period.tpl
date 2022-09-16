{include file="sections/header.tpl"}
					
					<div class="row">
						<div class="col-sm-12 col-md-6">
							<div class="panel panel-default panel-hovered panel-stacked mb30">
								<div class="panel-heading">{$_L['Period_Reports']}</div>
								<div class="panel-body">
								<form class="form-horizontal" method="post" role="form" action="{$_url}reports/period-view"> 
									<div class="form-group">
										<label class="col-md-3 control-label">{$_L['From_Date']}</label>
										<div class="col-md-9">
											<div class="input-group date" id="datepicker1">
												<input type="text" class="form-control" value="{$tdate}" name="fdate" id="fdate">
												<span class="input-group-addon ion ion-calendar"></span>
											</div>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">{$_L['To_Date']}</label>
										<div class="col-md-9">
											<div class="input-group date" id="datepicker2">
												<input type="text" class="form-control" value="{$mdate}" name="tdate" id="tdate">
												<span class="input-group-addon ion ion-calendar"></span>
											</div>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">{$_L['Type']}</label>
										<div class="col-md-9">
											<select class="form-control" id="stype" name="stype">
												<option value="" selected="">{$_L['All_Transactions']}</option>
												<option value="Hotspot">Hotspot</option>
												<option value="PPPOE">PPPOE</option>
											</select>
										</div>
									</div>

									<div class="form-group">
										<div class="col-sm-offset-3 col-sm-9">
											<button type="submit" id="submit" class="btn btn-primary">{$_L['Period_Reports']}</button>
										</div>
									</div>
								</form>
								</div>
							</div>
						</div>
					</div>

{include file="sections/footer.tpl"}
