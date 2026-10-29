{include file="sections/header.tpl"}
<!-- voucher-add -->

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Add Vouchers')}</div>
            <div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{Text::url('')}plan/voucher-post" id="voucherForm">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
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
                            <select style="width: 100%" id="server" name="server" class="form-control select2">
                                <option value=''>{Lang::T('Select Routers')}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Service Plan')}</label>
                        <div class="col-md-6">
                            <select style="width: 100%" id="plan" name="plan" class="form-control select2">
                                <option value=''>{Lang::T('Select Plans')}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Batch Name')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="batch_name" value="" placeholder="{Lang::T('Batch 1')}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Number of Vouchers')}</label>
                        <div class="col-md-6">
                            <input type="number" class="form-control" name="numbervoucher" id="numbervoucher" value="1"
                                min="1" max="{$voucher_max}" required>
                            <div style="margin-top:6px">
                                {foreach [10, 50, 100, 500, 1000] as $n}
                                <button type="button" class="btn btn-default btn-xs vg-count" data-n="{$n}">{$n}</button>
                                {/foreach}
                            </div>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Up to')} {$voucher_max} {Lang::T('at a time')}</p>
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
                            <input type="text" class="form-control" name="prefix" id="prefix" placeholder="NUX-"
                                maxlength="20" pattern="[A-Za-z0-9_\-]*" value="{$_c['voucher_prefix']}">
                        </div>
                        <p class="help-block col-md-4">NUX-VoUCHeRCOdE</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Length Code')}</label>
                        <div class="col-md-6">
                            <input type="number" class="form-control" name="lengthcode" id="lengthcode" value="12" min="4"
                                max="32" required>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('4 to 32, at least 6 for Numbers')}</p>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Preview')}</label>
                        <div class="col-md-6">
                            <div class="well well-sm" style="margin:0">
                                <div style="font-family:monospace;font-size:20px;letter-spacing:1px;word-break:break-all"
                                    id="vgExample">-</div>
                                <div class="text-muted" style="margin-top:4px" id="vgSummary"></div>
                            </div>
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

    // example code and a one-line summary that follow the form.
    // jQuery is loaded by the footer, after this block, so everything waits for the page to finish loading.
    window.addEventListener('load', function () {
        var $ = window.jQuery, max = {$voucher_max};
        if (!$) return;
        function sample(format, len) {
            var pool = format === 'numbers' ? '0123456789' : format === 'low' ? '0123456789abcdef' : '0123456789ABCDEF', out = '';
            for (var i = 0; i < len; i++) {
                var c = pool.charAt(Math.floor(Math.random() * pool.length));
                out += (format === 'rand' && Math.random() < 0.5) ? c.toLowerCase() : c;
            }
            return out;
        }
        function refresh() {
            var len = Math.max(0, Math.min(32, parseInt($('#lengthcode').val(), 10) || 0));
            var n = parseInt($('#numbervoucher').val(), 10) || 0;
            var fmt = $('#voucher_format').val();
            $('#vgExample').text(($('#prefix').val() || '') + sample(fmt, len));
            var plan = $('#plan option:selected');
            var txt = n + ' {Lang::T('vouchers')}';
            if (plan.val()) txt += ' \u2022 ' + $.trim(plan.text()).replace(/\s+/g, ' ');
            var bad = n < 1 || n > max || len < 4 || (fmt === 'numbers' && len < 6);
            $('#vgSummary').text(txt).css('color', bad ? '#c0392b' : '');
            $('#voucherForm button[type=submit]').prop('disabled', bad);
        }
        $('#voucherForm').on('input change keyup', 'input, select', refresh);
        // iCheck / select2 fire jQuery events, not native ones, so listen on the elements themselves too
        $('#voucher_format, #plan, #server').on('change', refresh);
        $('.vg-count').on('click', function () { $('#numbervoucher').val($(this).data('n')); refresh(); });
        refresh();
    });
</script>

{include file="sections/footer.tpl"}