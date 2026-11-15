{include file="sections/header.tpl"}
<!-- hotspot-add -->

{* the controller assigns the bandwidths as $d here *}
{$nf_bw = $d}{$d = []}
{include file="admin/services/plan-form.tpl" nf_kind="hotspot" nf_edit=false}

{include file="sections/footer.tpl"}
