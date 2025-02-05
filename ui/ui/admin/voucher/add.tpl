{include file="sections/header.tpl"}
<!-- voucher-add -->

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Add Vouchers')}</div>
            <div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{Text::url('')}plan/voucher-post">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Type')}</label>
                        <div class="col-md-6">
                            <input type="radio" id="Hot" name="type" value="Hotspot"> {Lang::T('Hotspot Plans')}
                            <input type="radio" id="POE" name="type" value="PPPOE"> {Lang::T('PPPOE Plans')}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Routers')}</label>
                        <div class="col-md-6">
                            <select id="server" name="server" class="form-control select2">
                                <option value=''>{Lang::T('Select Routers')}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Service Plan')}</label>
                        <div class="col-md-6">
                            <select id="plan" name="plan" class="form-control select2">
                                <option value=''>{Lang::T('Select Plans')}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Number of Vouchers')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="numbervoucher" value="1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Voucher Format')}</label>
                        <div class="col-md-6">
                            <select name="voucher_format" id="voucher_format" class="form-control">
                                <option value="numbers" {if $_c['voucher_format']=='numbers' }selected="selected" {/if}>
                                    Numbers
                                </option>
                                <option value="up" {if $_c['voucher_format']=='up' }selected="selected" {/if}>UPPERCASE
                                </option>
                                <option value="low" {if $_c['voucher_format']=='low' }selected="selected" {/if}>
                                    lowercase
                                </option>
                                <option value="rand" {if $_c['voucher_format']=='rand' }selected="selected" {/if}>
                                    RaNdoM
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">UPPERCASE lowercase RaNdoM</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Voucher Prefix')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="prefix" placeholder="NUX-"
                                value="{$_c['voucher_prefix']}">
                        </div>
                        <p class="help-block col-md-4">NUX-VoUCHeRCOdE</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Length Code')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="lengthcode" value="12">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputSkills" class="col-sm-2 control-label">{Lang::T('Print Now')}</label>

                        <div class="col-sm-10">
                            <input type="checkbox" id="print_now" name="print_now" class="iCheck" value="yes"
                                onclick="showVouchersPerPage()">
                        </div>
                    </div>

                    <div class="form-group" id="printers" style="display:none;">
                        <label class="col-md-2 control-label">{Lang::T('Vouchers Per Page')}</label>
                        <div class="col-md-6">
                            <input type="text" id="voucher-print" class="form-control" name="voucher_per_page"
                                value="36" placeholder="{Lang::T("Vouchers Per Page")} (default 36)">
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('Vouchers Per Page')} (default 36)
                        </p>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-success"
                                onclick="return ask(this, '{Lang::T("Continue the Voucher creation process?")}')"
                                type="submit">{Lang::T('Generate')}</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<!-- /voucher-add -->

<script>
    function showVouchersPerPage() {
        var printNow = document.getElementById('print_now');
        var printers = document.getElementById('printers');
        var voucherPrint = document.getElementById('voucher-print');

        voucherPrint.required = false;
        if (printNow.checked) {
            printers.style.display = 'block';
            voucherPrint.required = true;
        } else {
            printers.style.display = 'none';
        }
    }
</script>

{include file="sections/footer.tpl"}