			</div>
		</div>
	</div>

	<div class="site-settings clearfix hidden-xs">
		<div class="settings clearfix">
			<div class="trigger ion ion-settings left"></div>
			<div class="wrapper left">
				<ul class="list-unstyled other-settings">
					<li class="clearfix mb10">
						<div class="left small">Fixed Header</div>
						<div class="md-switch right">
							<label>
								<input type="checkbox" id="fixedHeader"> 
								<span>&nbsp;</span> 
							</label>
						</div>
					</li>
					<li class="clearfix mb10">
						<div class="left small">Nav Full</div>
						<div class="md-switch right">
							<label>
								<input type="checkbox" id="navFull"> 
								<span>&nbsp;</span> 
							</label>
						</div>
					</li>
				</ul>
				<hr/>
				<ul class="themes list-unstyled" id="themeColor">
					<li data-theme="theme-zero" class="active"></li>
					<li data-theme="theme-one"></li>
					<li data-theme="theme-two"></li>
					<li data-theme="theme-three"></li>
					<li data-theme="theme-four"></li>
					<li data-theme="theme-five"></li>
					<li data-theme="theme-six"></li>
					<li data-theme="theme-seven"></li>
				</ul>
			</div>
		</div>
	</div>

	<script src="{$_theme}/scripts/vendors.js"></script>
	<script src="{$_theme}/scripts/plugins/screenfull.js"></script>
	<script src="{$_theme}/scripts/plugins/perfect-scrollbar.min.js"></script>
	<script src="{$_theme}/scripts/plugins/waves.min.js"></script>
	<script src="{$_theme}/scripts/plugins/select2.min.js"></script>
	<script src="{$_theme}/scripts/plugins/bootstrap-colorpicker.min.js"></script>
	<script src="{$_theme}/scripts/plugins/bootstrap-slider.min.js"></script>
	<script src="{$_theme}/scripts/plugins/summernote.min.js"></script>
	<script src="{$_theme}/scripts/plugins/bootstrap-datepicker.min.js"></script>
	<script src="{$_theme}/scripts/app.js"></script>
	<script src="{$_theme}/scripts/custom.js"></script>
	<script src="{$_theme}/scripts/form-elements.init.js"></script>

	<script src="ui/lib/js/bootbox.min.js"></script>
	
{if isset($xfooter)}
	{$xfooter}
{/if}
		
</body>
</html>