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

	<script src="ui/ui/scripts/vendors.js"></script>
	<script src="ui/ui/scripts/plugins/screenfull.js"></script>
	<script src="ui/ui/scripts/plugins/perfect-scrollbar.min.js"></script>
	<script src="ui/ui/scripts/plugins/waves.min.js"></script>
	<script src="ui/ui/scripts/plugins/select2.min.js"></script>
	<script src="ui/ui/scripts/plugins/bootstrap-colorpicker.min.js"></script>
	<script src="ui/ui/scripts/plugins/bootstrap-slider.min.js"></script>
	<script src="ui/ui/scripts/plugins/summernote.min.js"></script>
	<script src="ui/ui/scripts/plugins/bootstrap-datepicker.min.js"></script>
	<script src="ui/ui/scripts/app.js"></script>
	<script src="ui/ui/scripts/custom.js"></script>
	<script src="ui/ui/scripts/form-elements.init.js"></script>

	<script src="ui/lib/js/bootbox.min.js"></script>

{if isset($xfooter)}
	{$xfooter}
{/if}

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

</body>
</html>