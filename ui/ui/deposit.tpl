{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Refill Balance')}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{$_url}plan/deposit-post">
                    <input type="hidden" name="stoken" value="{App::getToken()}">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Select Account')}</label>
                        <div class="col-md-6">
                            <select id="personSelect" class="form-control select2" name="id_customer" style="width: 100%"
                                data-placeholder="{Lang::T('Select a customer')}...">
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><a href="{$_url}services/balance">{Lang::T('Balance Plans')}</a></label>
                        <div class="col-md-6">
                            <select id="planSelect" class="form-control select2" name="id_plan" style="width: 100%"
                                data-placeholder="{Lang::T('Select Plans')}...">
                                <option></option>
                                {foreach $p as $pl}
                                    <option value="{$pl['id']}">{if $pl['enabled'] neq 1}DISABLED PLAN &bull; {/if}{$pl['name_plan']} - {Lang::moneyFormat($pl['price'])}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-success"
                                type="submit">{Lang::T('Recharge')}</button>
                            Or <a href="{$_url}customers/list">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}