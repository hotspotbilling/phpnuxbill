{include file="sections/header.tpl"}
					<div class="row">
						<div class="col-sm-12">
							<div class="panel mb20 panel-primary panel-hovered">
								<div class="panel-heading">{$_L[$pageHeader]}</div>
								<div id="myNicPanel" style="width: 100%;"></div>
								<div id="panel-edit" class="panel-body">{$htmls}</div>
								{if $writeable}
								<div class="panel-footer">
								<a href="javascript:saveIt()" class="btn btn-primary btn-block">SAVE</a>
								<br>
								<p class="help-block">{$_L['Info_Page']}</p>
								</div>
								{else}
								<div class="panel-footer">
									{$_L['Failed_Save_Page']}
								</div>
								{/if}
							</div>
						</div>
					</div>
<form id="formpages" class="hidden" method="post" role="form" action="{$_url}pages/{$pageHeader}-post" > 
<textarea name="html" id="html"></textarea>
</form>
<script src="{$_theme}/scripts/nicEdit.js"></script>
<script type="text/javascript">
var myNicEditor
bkLib.onDomLoaded(function() {
	myNicEditor = new nicEditor();
	myNicEditor.setPanel('myNicPanel');
	myNicEditor.addInstance('panel-edit');
});
function saveIt(){
	//alert(document.getElementById('panel-edit').innerHTML);
	document.getElementById('html').value = nicEditors.findEditor('panel-edit').getContent()
	document.getElementById('formpages').submit();
}
</script>
						
{include file="sections/footer.tpl"}
