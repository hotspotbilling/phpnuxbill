{include file="sections/header.tpl"}

<div class="row">
	<div class="col-sm-12 col-md-12">
		<div class="panel panel-primary panel-hovered panel-stacked mb30">
			<div class="panel-heading">{Lang::T('Translation')}</div>
			<div class="panel-body">
				<form class="form-horizontal" method="post" role="form" action="{$_url}settings/lang-post">
					{foreach $langs as $lang}
						<div class="form-group">
							<div class="col-md-12">
								<small>{str_replace('_',' ', $lang@key)}</small>
								<input type="text" class="form-control" rows="1" name="{$lang@key}"
									placeholder="{$lang@key}" value="{$lang}">
							</div>
						</div>
					{/foreach}
					<div class="form-group">
						<div class="col-lg-offset-2 col-lg-10">
							<button class="btn btn-primary"
								type="submit">{Lang::T('Save Changes')}</button>
							Or <a href="{$_url}settings/localisation">{Lang::T('Cancel')}</a>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

{include file="sections/footer.tpl"}