{if $unpaid }
    <div class="box box-danger box-solid">
        <div class="box-header">
            <h3 class="box-title">{Lang::T('Unpaid Order')}</h3>
        </div>
        <div style="margin-left: 5px; margin-right: 5px;">
            <table class="table table-condensed table-bordered table-striped table-hover"
                style="margin-bottom: 0px;">
                <tbody>
                    <tr>
                        <td>{Lang::T('expired')}</td>
                        <td>{Lang::dateTimeFormat($unpaid['expired_date'])} </td>
                    </tr>
                    <tr>
                        <td>{Lang::T('Package Name')}</td>
                        <td>{$unpaid['plan_name']}</td>
                    </tr>
                    <tr>
                        <td>{Lang::T('Package Price')}</td>
                        <td>{$unpaid['price']}</td>
                    </tr>
                    <tr>
                        <td>{Lang::T('Routers')}</td>
                        <td>{$unpaid['routers']}</td>
                    </tr>
                </tbody>
            </table> &nbsp;
        </div>
        <div class="box-footer p-2">
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

        </div>&nbsp;&nbsp;
    </div>
{/if}