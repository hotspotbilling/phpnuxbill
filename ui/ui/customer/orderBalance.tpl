{include file="customer/header.tpl"}
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
                                                    <td>{Lang::moneyFormat($plan['price'])}
                                                        {if !empty($plan['price_old'])}
                                                            <sup
                                                                style="text-decoration: line-through; color: red">{Lang::moneyFormat($plan['price_old'])}</sup>
                                                        {/if}
                                                    </td>
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
                    {if $_c['allow_balance_custom'] eq 'yes'}
                        <div class="col col-md-4">
                            <form action="{$_url}order/gateway/0/0" method="post">
                                <input type="hidden" name="custom" value="1">
                                <div class="box box-solid box-default">
                                    <div class="box-header text-bold">{Lang::T('Custom Balance')}</div>
                                    <div class="table-responsive">
                                        <div style="margin-left: 5px; margin-right: 5px;">
                                            <table class="table table-bordered table-striped">
                                                <tbody>
                                                    <tr>
                                                        <input type="number" name="amount" id="amount" class="form-control"
                                                            placeholder="{Lang::T('Input Desired Amount')}">
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="box-body">
                                        <button onclick="return confirm('{Lang::T('Buy Balance')}?')"
                                            class="btn btn-sm btn-block btn-primary">{Lang::T('Buy')}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    {/if}
                </div>
            </div>
        {/if}
    </div>
</div>
{include file="customer/footer.tpl"}