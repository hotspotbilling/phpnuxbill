{include file="sections/header.tpl"}
<!-- hotspot-edit -->

{$nf_bw = $b}
{include file="admin/services/plan-form.tpl" nf_kind="hotspot" nf_edit=true}

<script src="{$app_url}/ui/ui/scripts/codemirror/codemirror.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/codemirror/perl.min.js"></script>
<link rel="stylesheet" href="{$app_url}/ui/ui/scripts/codemirror/codemirror.min.css">
<link rel="stylesheet" href="{$app_url}/ui/ui/scripts/codemirror/abbott.min.css">
<script>
    // RouterOS scripts with line numbers (only on a package that is not RADIUS)
    ['code', 'code2'].forEach(function (id) {
        var t = document.getElementById(id);
        if (t && window.CodeMirror) CodeMirror.fromTextArea(t, { lineNumbers: true, mode: 'text/x-perl', theme: document.body.classList.contains('dark-mode') ? 'abbott' : 'default' });
    });
</script>

{include file="sections/footer.tpl"}
