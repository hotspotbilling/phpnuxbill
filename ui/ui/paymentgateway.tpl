{include file="sections/header.tpl"}
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-info panel-hovered">
            <div class="panel-heading">{Lang::T('Payment Gateway')}</div>
            <div class="panel-body row">
                {foreach $pgs as $pg}
                <div class="col-sm-4 mb20">
                    <a href="{$_url}paymentgateway/{$pg}" class="btn btn-block btn-default">{ucwords($pg)}</a>
                </div>
                {/foreach}
            </div>
            <!-- <div class="panel-footer">
                <form method="post">
                <div class="form-group row">
                    <label class="col-md-2 control-label">Payment Gateway</label>
                    <div class="col-md-8">
                        <select name="payment_gateway" id="payment_gateway" class="form-control">
                            <option value="none">None</option>
                            {foreach $pgs as $pg}
                                <option value="{$pg}" {if $_c['payment_gateway'] eq {$pg}}selected="selected" {/if}>{ucwords($pg)}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-block btn-primary" type="submit">{Lang::T('Save Changes')}</button>
                    </div>
                </div>
                </div>
            </div> -->
        </div>
    </div>
    {include file="sections/footer.tpl"}