{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Refill Balance')}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{$_url}prepaid/deposit-post">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Select_Account']}</label>
                        <div class="col-md-6">
                            <select id="personSelect" class="form-control" name="id_customer" style="width: 100%"
                                data-placeholder="{$_L['Select_Customer']}...">
                                <option></option>
                                {foreach $c as $cs}
                                    <option value="{$cs['id']}">{$cs['username']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Service_Plan']}</label>
                        <div class="col-md-6">
                            <select id="planSelect" class="form-control" name="id_plan" style="width: 100%"
                                data-placeholder="{$_L['Select_Plans']}...">
                                <option></option>
                                {foreach $p as $pl}
                                    <option value="{$pl['id']}">{$pl['name_plan']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-success waves-effect waves-light"
                                type="submit">{$_L['Recharge']}</button>
                            Or <a href="{$_url}customers/list">{$_L['Cancel']}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}