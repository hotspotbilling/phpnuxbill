{include file="sections/user-header.tpl"}
<!-- user-pages -->

					<div class="row">
						<div class="col-sm-12">
							<div class="panel mb20 panel-primary panel-hovered">
								<div class="panel-heading">{$_L[$pageHeader]}</div>
								<div class="panel-body">
									{include file="$_path/../pages/$PageFile.html"}
								</div>
							</div>
						</div>
					</div>

{include file="sections/user-footer.tpl"}
