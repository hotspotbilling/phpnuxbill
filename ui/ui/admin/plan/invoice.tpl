{include file="sections/header.tpl"}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js"></script>
<div class="row">
    <div class="col-md-6 col-sm-12 col-md-offset-3">
        <div class="panel panel-hovered panel-primary panel-stacked mb30">
            <div class="panel-heading">{$in['invoice']}</div>
            <div class="panel-body">
                {if !empty($logo)}
                    <center><img src="{$app_url}/{$logo}?"></center>
                {/if}
                <form class="form-horizontal" method="post" action="{Text::url('')}plan/print" target="_blank">
                    <pre id="content"
                    style="border: 0px; ;text-align: center; background-color: transparent; background-image: url('{$app_url}/system/uploads/paid.png');background-repeat:no-repeat;background-position: center"></pre>
                    <textarea class="hidden" id="formcontent" name="content">{$invoice}</textarea>
                    <input type="hidden" name="id" value="{$in['id']}">
                    <a href="{Text::url('plan/list')}" class="btn btn-default btn-sm"><i
                            class="ion-reply-all"></i>{Lang::T('Finish')}</a>
                    <a href="javascript:download()" class="btn btn-success btn-sm text-black">
                        <i class="glyphicon glyphicon-share"></i> Download</a>
                    <a href="https://api.whatsapp.com/send/?text={$whatsapp}" target="_blank"
                        class="btn btn-primary btn-sm">
                        <i class="glyphicon glyphicon-share"></i> WhatsApp</a>
                    <a href="{Text::url('')}plan/view/{$in['id']}/send" class="btn btn-info text-black btn-sm"><i
                            class="glyphicon glyphicon-envelope"></i> {Lang::T("Resend")}</a>
                    <hr>
                    <a href="{Text::url('')}plan/print/{$in['id']}" target="_print"
                        class="btn btn-info text-black btn-sm"><i class="glyphicon glyphicon-print"></i>
                        {Lang::T('Print')} HTML</a>
                    <button type="submit" class="btn btn-info text-black btn-sm"><i
                            class="glyphicon glyphicon-print"></i>
                        {Lang::T('Print')} Text</button>
                    <a href="nux://print?text={urlencode($invoice)}"
                        class="btn btn-success text-black btn-sm hidden-md hidden-lg">
                        <i class="glyphicon glyphicon-phone"></i>
                        NuxPrint
                    </a>
                    <a href="https://github.com/hotspotbilling/android-printer"
                        class="btn btn-success text-black btn-sm hidden-xs hidden-sm" target="_blank">
                        <i class="glyphicon glyphicon-phone"></i>
                        NuxPrint
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    const canvas = document.createElement("canvas");
    const ctx = canvas.getContext('2d');
    ctx.font = '16px Courier';
    var text = document.getElementById("formcontent").innerHTML;
    var lines = text.split(/\r\n|\r|\n/).length;
    var meas = ctx.measureText("A");
    let width = Math.round({$_c['printer_cols']} * 9.6);
    var height = Math.round((14 * lines));
    console.log(width, height, lines);
    var paid = new Image();
    paid.src = '{$app_url}/system/uploads/paid.png';
    {if !empty($logo)}
        var img = new Image();
        img.src = '{$app_url}/{$logo}';
        var new_width = (width / 4) * 2;
        var new_height = Math.ceil({$hlogo} * (new_width/{$wlogo}));
        height = height + new_height;
    {/if}

    function download() {
        var doc = new jsPDF('p', 'px', [width, height]);
        {if !empty($logo)}
            try {
                doc.addImage(img, 'PNG', (width - new_width) / 2, 10, new_width, new_height);
            } catch (err) {}
        {/if}
        try {
            doc.addImage(paid, 'PNG', (width - 200) / 2, (height - 145) / 2, 200, 145);
        } catch (err) {}
        doc.setFont("Courier");
        doc.setFontSize(16);
        doc.text($('#formcontent').html(), width / 2, new_height + 30, 'center');
        doc.save('{$in['invoice']}.pdf');
    }

    var s5_taf_parent = window.location;
    document.getElementById('content').innerHTML = document.getElementById('formcontent').innerHTML;
</script>
{include file="sections/footer.tpl"}