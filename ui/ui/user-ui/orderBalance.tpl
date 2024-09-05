{include file="user-ui/header.tpl"}
<!-- user-orderPlan -->
<div class="row">
    <div class="col-sm-12">
        {if $_c['enable_balance'] == 'yes'}
        <div class="box box-solid box-success bg-gray-light">
            <div class="box-header">{Lang::T('Buy Balance Plans')}</div>
            <div class="box-body row">
                {foreach $plans_balance as $plan}
                <div class="col col-md-4">
                    <div class="box box-solid box-default">
                        <div class="box-header text-bold">{$plan['name_plan']}</div>
                        <div class="table-responsive">
                            <div style="margin-left: 5px; margin-right: 5px;">
                                <table class="table table-bordered table-striped">
                                    <tbody>
                                        <tr>
                                            <td>{Lang::T('Price')}</td>
                                            <td>{Lang::moneyFormat($plan['price'])}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="box-body">
                            <a href="{$_url}order/gateway/0/{$plan['id']}"
                                onclick="return confirm('{Lang::T('Buy Balance')}?')"
                                class="btn btn-sm btn-block btn-primary">{Lang::T('Buy')}</a>
                        </div>
                    </div>
                </div>
                {/foreach}
            </div>
        </div>
        {/if}
    </div>
</div>
{include file="user-ui/footer.tpl"}