{include file="sections/header.tpl"}

<div class="row">
    <div class="col-lg-6 col-lg-offset-3">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Refill Balance')}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{$_url}plan/deposit-post">
                    <input type="hidden" name="stoken" value="{App::getToken()}">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Select Account')}</label>
                        <div class="col-md-9">
                            <select id="personSelect" class="form-control select2" onchange="getBalance(this)" name="id_customer" style="width: 100%"
                                data-placeholder="{Lang::T('Select a customer')}...">
                            </select>
                            <span class="help-block" id="customerBalance">-</span>
                        </div>
                    </div>
                    <span class="help-block">{Lang::T('Select Balance Package or Custom Amount')}</span>
                    <div class="form-group">
                        <label class="col-md-3 control-label"><a href="{$_url}services/balance">{Lang::T('Balance Package')}</a></label>
                        <div class="col-md-9">
                            <select id="planSelect" class="form-control select2" name="id_plan" style="width: 100%"
                                data-placeholder="{Lang::T('Select Plans')}...">
                                <option></option>
                                {foreach $p as $pl}
                                    <option value="{$pl['id']}">{if $pl['enabled'] neq 1}DISABLED PLAN &bull; {/if}{$pl['name_plan']} - {Lang::moneyFormat($pl['price'])}</option>
                                {/foreach}
                            </select>
                            <span class="help-block">{Lang::T('Or custom balance amount below')}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Balance Amount')}</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="amount" style="width: 100%" placeholder="0">
                            <span class="help-block">{Lang::T('Input custom balance, will ignore plan above')}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Note')}</label>
                        <div class="col-md-9">
                            <textarea class="form-control" name="note" style="width: 100%"></textarea>
                            <span class="help-block"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-9 col-md-offset-3">
                            <button class="btn btn-success" onclick="return confirm('Continue the Customer Balance top-up process?')"
                                type="submit">{Lang::T('Recharge')}</button>
                            Or <a href="{$_url}customers/list">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function getBalance(f){
    $.get('{$_url}autoload/balance/'+f.value+'/1', function(data) {
        document.getElementById('customerBalance').innerHTML = data;
    });
}
</script>

{include file="sections/footer.tpl"}
