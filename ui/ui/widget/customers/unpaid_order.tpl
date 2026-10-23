{if $unpaids && count($unpaids) > 0}
    <div class="box box-danger box-solid">
        <div class="box-header">
            <h3 class="box-title">{Lang::T('Unpaid Order')}{if count($unpaids) > 1} ({count($unpaids)}){/if}</h3>
        </div>
        <div style="margin-left: 5px; margin-right: 5px;">
            {assign var="total_unpaid" value=0}
            {foreach $unpaids as $unpaid}
                <table class="table table-condensed table-bordered table-striped table-hover"
                    style="margin-bottom: 5px;">
                    <tbody>
                        <tr>
                            <td>{Lang::T('expired')}</td>
                            <td>{Lang::dateTimeFormat($unpaid['expired_date'])} </td>
                        </tr>
                        <tr>
                            <td>{Lang::T('Package Name')}</td>
                            <td>{$unpaid['plan_name']|escape:'html'}</td>
                        </tr>
                        <tr>
                            <td>{Lang::T('Package Price')}</td>
                            <td>{Lang::moneyFormat($unpaid['price'])}</td>
                        </tr>
                        <tr>
                            <td>{Lang::T('Routers')}</td>
                            <td>{$unpaid['routers']|escape:'html'}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="btn-group btn-group-justified mb15">
                    <div class="btn-group">
                        <a href="{Text::url('order/view/', $unpaid['id'], '/cancel')}" class="btn btn-danger btn-sm"
                            onclick="return ask(this, '{Lang::T('Cancel it?')}')">
                            <span class="glyphicon glyphicon-trash"></span>
                            {Lang::T('Cancel')}
                        </a>
                    </div>
                    <div class="btn-group">
                        <a class="btn btn-success btn-block btn-sm" href="{Text::url('order/view/',$unpaid['id'])}">
                            <span class="icon"><i class="ion ion-card"></i></span>
                            <span>{Lang::T('PAY NOW')}</span>
                        </a>
                    </div>
                </div>
                {assign var="total_unpaid" value=$total_unpaid+$unpaid['price']}
            {/foreach}
            {if count($unpaids) > 1}
                <table class="table table-condensed" style="margin-bottom: 5px;">
                    <tr>
                        <td class="small text-danger text-uppercase text-normal"><b>{Lang::T('Total')}</b></td>
                        <td class="small mb15 text-danger"><b>{Lang::moneyFormat($total_unpaid)}</b></td>
                    </tr>
                </table>
            {/if}
        </div>&nbsp;&nbsp;
    </div>
{/if}