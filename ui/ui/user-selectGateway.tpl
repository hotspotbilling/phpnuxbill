{include file="sections/user-header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-info panel-hovered">
            <div class="panel-heading">{Lang::T('Available Payment Gateway')}</div>
            <div class="panel-footer">
                <form method="post" action="{$_url}order/buy/{$route2}/{$route3}">
                    <div class="form-group row">
                        <label class="col-md-2 control-label">Payment Gateway</label>
                        <div class="col-md-8">
                            <select name="gateway" id="gateway" class="form-control">
                                {foreach $pgs as $pg}
                                    <option value="{$pg}">
                                        {ucwords($pg)}</option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-block btn-primary">{Lang::T('Pay Now')}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{include file="sections/user-footer.tpl"}