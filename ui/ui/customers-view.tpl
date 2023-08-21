{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-4 col-md-4">
        <div class="box box-primary">
            <div class="box-body box-profile">
                <img class="profile-user-img img-responsive img-circle"
                    src="https://robohash.org/{$d['id']}?set=set3&size=100x100&bgset=bg1"
                    onerror="this.src='system/uploads/user.default.jpg'" alt="avatar">

                <h3 class="profile-username text-center">{$d['fullname']}</h3>

                <ul class="list-group list-group-unbordered">
                    <li class="list-group-item">
                        <b>{$_L['Username']}</b> <a class="pull-right">{$d['username']}</a>
                    </li>
                    <li class="list-group-item">
                        <b>{$_L['Phone_Number']}</b> <a class="pull-right">{$d['phonenumber']}</a>
                    </li>
                    <li class="list-group-item">
                        <b>{$_L['Email']}</b> <a class="pull-right">{$d['email']}</a>
                    </li>
                </ul>
                <p class="text-muted">{Lang::nl2br($d['address'])}</p>
                <ul class="list-group list-group-unbordered">
                    <li class="list-group-item">
                        <b>{$_L['Password']}</b> <a class="pull-right" style="background-color: black; color:black;"
                            onclick="this.select()">{$d['password']}</a>
                    </li>
                    {if $d['pppoe_password'] != ''}
                        <li class="list-group-item">
                            <b>PPPOE {$_L['Password']}</b> <a class="pull-right"
                                style="background-color: black; color:black;"
                                onclick="this.select()">{$d['pppoe_password']}</a>
                        </li>
                    {/if}
                    <li class="list-group-item">
                        <b>{Lang::T('Balance')}</b> <a class="pull-right">{Lang::moneyFormat($d['balance'])}</a>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Auto Renewal')}</b> <a
                            class="pull-right">{if $d['auto_renewal']}yes{else}no{/if}</a>
                    </li>
                    <li class="list-group-item">
                        <b>{$_L['Created_On']}</b> <a class="pull-right">{Lang::dateTimeFormat($d['created_at'])}</a>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Last Login')}</b> <a
                            class="pull-right">{Lang::dateTimeFormat($d['last_login'])}</a>
                    </li>
                </ul>
                <div class="row">
                    <div class="col-xs-4">
                        <a href="{$_url}customers/delete/{$d['id']}" id="{$d['id']}"
                            class="btn btn-danger btn-block btn-sm" onclick="return confirm('{$_L['Delete']}?')"><span
                                class="fa fa-trash"></span></a>
                    </div>
                    <div class="col-xs-8">
                        <a href="{$_url}customers/edit/{$d['id']}"
                            class="btn btn-warning btn-sm btn-block">{$_L['Edit']}</a>
                    </div>
                </div>
            </div>
            <!-- /.box-body -->
        </div>
    </div>
    <div class="col-sm-8 col-md-8">
        <ul class="nav nav-tabs">
            <li role="presentation" {if $v=='order'}class="active" {/if}><a
                    href="{$_url}customers/view/{$d['id']}/order">30 {Lang::T('Order History')}</a></li>
            <li role="presentation" {if $v=='activation'}class="active" {/if}><a
                    href="{$_url}customers/view/{$d['id']}/activation">30 {Lang::T('Activation History')}</a></li>
        </ul>
        <div class="table-responsive" style="background-color: white;">
            <table id="datatable" class="table table-bordered table-striped">
                {if Lang::arrayCount($activation)}
                    <thead>
                        <tr>
                            <th>{$_L['Username']}</th>
                            <th>{$_L['Plan_Name']}</th>
                            <th>{$_L['Plan_Price']}</th>
                            <th>{$_L['Type']}</th>
                            <th>{$_L['Created_On']}</th>
                            <th>{$_L['Expires_On']}</th>
                            <th>{$_L['Method']}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $activation as $ds}
                            <tr>
                                <td>{$ds['username']}</td>
                                <td>{$ds['plan_name']}</td>
                                <td>{Lang::moneyFormat($ds['price'])}</td>
                                <td>{$ds['type']}</td>
                                <td class="text-success">{date($_c['date_format'], strtotime($ds['recharged_on']))}</td>
                                <td class="text-danger">{date($_c['date_format'], strtotime($ds['expiration']))}
                                    {$ds['time']}</td>
                                <td>{$ds['method']}</td>
                            </tr>
                        {/foreach}
                    </tbody>
                {/if}
                {if Lang::arrayCount($order)}
                    <thead>
                        <tr>
                            <th>{$_L['Plan_Name']}</th>
                            <th>{Lang::T('Gateway')}</th>
                            <th>{Lang::T('Routers')}</th>
                            <th>{$_L['Type']}</th>
                            <th>{$_L['Plan_Price']}</th>
                            <th>{$_L['Created_On']}</th>
                            <th>{$_L['Expires_On']}</th>
                            <th>{Lang::T('Date Done')}</th>
                            <th>{$_L['Method']}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $order as $ds}
                            <tr>
                                <td>{$ds['plan_name']}</td>
                                <td>{$ds['gateway']}</td>
                                <td>{$ds['routers']}</td>
                                <td>{$ds['payment_channel']}</td>
                                <td>{Lang::moneyFormat($ds['price'])}</td>
                                <td class="text-primary">{date("{$_c['date_format']} H:i",
                                    strtotime($ds['created_date']))}</td>
                                <td class="text-danger">{date("{$_c['date_format']} H:i",
                                    strtotime($ds['expired_date']))}</td>
                                <td class="text-success">{if $ds['status']!=1}{date("{$_c['date_format']} H:i",
                                    strtotime($ds['paid_date']))}{/if}</td>
                                <td>{if $ds['status']==1}{$_L['UNPAID']}
                                    {elseif $ds['status']==2}{$_L['PAID']}
                                    {elseif $ds['status']==3}{$_L['FAILED']}
                                    {elseif $ds['status']==4}{$_L['CANCELED']}
                                    {elseif $ds['status']==5}{$_L['UNKNOWN']}
                                    {/if}</td>
                            </tr>
                        {/foreach}
                    </tbody>
                {/if}
            </table>
        </div>
        {$paginator['contents']}
    </div>
</div>

{include file="sections/footer.tpl"}