        </section>
    </div>

	<script src="ui/ui/scripts/main.min.js"></script>
	<script src="ui/ui/scripts/plugins/select2.min.js"></script>
	<script src="ui/ui/scripts/custom.js"></script>
	<script src="ui/ui/scripts/form-elements.init.js"></script>

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