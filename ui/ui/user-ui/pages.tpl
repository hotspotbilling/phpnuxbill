{include file="user-ui/header.tpl"}
<!-- user-pages -->

					<div class="row">
						<div class="col-sm-12">
							<div class="panel mb20 panel-primary panel-hovered">
								<div class="panel-heading">{$_L[$pageHeader]}</div>
								<div class="panel-body">
									{include file="$PAGES_PATH/$PageFile.html"}
								</div>
							</div>
						</div>
					</div>

{include file="user-ui/footer.tpl"}
