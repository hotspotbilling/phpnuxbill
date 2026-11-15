{include file="sections/header.tpl"}
<!-- pppoe-add -->

{* the controller assigns the bandwidths as $d here *}
{$nf_bw = $d}{$d = []}
{include file="admin/services/plan-form.tpl" nf_kind="pppoe" nf_edit=false}

{include file="sections/footer.tpl"}
