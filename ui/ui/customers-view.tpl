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
                        <b>{$_L['Username']}</b> <span class="pull-right">{$d['username']}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{$_L['Phone_Number']}</b> <span class="pull-right">{$d['phonenumber']}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{$_L['Email']}</b> <span class="pull-right">{$d['email']}</span>
                    </li>
                </ul>
                <p class="text-muted">{Lang::nl2br($d['address'])}</p>
                <ul class="list-group list-group-unbordered">
                    <li class="list-group-item">
                        <b>{$_L['Password']}</b> <input type="password" value="{$d['password']}"
                            style=" border: 0px; text-align: right;" class="pull-right"
                            onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'"
                            onclick="this.select()">
                    </li>
                    {if $d['pppoe_password'] != ''}
                        <li class="list-group-item">
                            <b>PPPOE {$_L['Password']}</b> <input type="password" value="{$d['pppoe_password']}"
                            style=" border: 0px; text-align: right;" class="pull-right"
                            onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'"
                            onclick="this.select()">
                        </li>
                    {/if}
					<li class="list-group-item">
                        <b>{Lang::T('Service Type')}</b> <span class="pull-right">{Lang::T($d['service_type'])}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Balance')}</b> <span class="pull-right">{Lang::moneyFormat($d['balance'])}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Auto Renewal')}</b> <span
                            class="pull-right">{if $d['auto_renewal']}yes{else}no{/if}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{$_L['Created_On']}</b> <span
                            class="pull-right">{Lang::dateTimeFormat($d['created_at'])}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{Lang::T('Last Login')}</b> <span
                            class="pull-right">{Lang::dateTimeFormat($d['last_login'])}</span>
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
        </div>
        {if $package}
            <div class="box box-{if $package['status']=='on'}success{else}danger{/if}">
                <div class="box-body box-profile">
                    <h4 class="text-center">{$package['type']} - {$package['namebp']}</h4>
                    <ul class="list-group list-group-unbordered">
                        <li class="list-group-item">
                            {Lang::T('Active')} <span
                                class="pull-right">{if $package['status']=='on'}yes{else}no{/if}</span>
                        </li>
                        <li class="list-group-item">
                            {$_L['Created_On']} <span
                                class="pull-right">{Lang::dateAndTimeFormat($package['recharged_on'],$package['recharged_time'])}</span>
                        </li>
                        <li class="list-group-item">
                            {$_L['Expires_On']} <span
                                class="pull-right">{Lang::dateAndTimeFormat($package['expiration'], $package['time'])}</span>
                        </li>
                        <li class="list-group-item">
                            {$package['routers']} <span class="pull-right">{$package['method']}</span>
                        </li>
                    </ul>
                    <div class="row">
                        <div class="col-xs-4">
                            <a href="{$_url}customers/deactivate/{$d['id']}" id="{$d['id']}"
                                class="btn btn-danger btn-block btn-sm"
                                onclick="return confirm('This will deactivate Customer Plan, and make it expired')">{Lang::T('Deactivate')}</a>
                        </div>
                        <div class="col-xs-4">
                            <a href="{$_url}customers/recharge/{$d['id']}"
                                onclick="return confirm('This will extend Customer plan, same as recharge')"
                                class="btn btn-success btn-sm btn-block">{Lang::T('Recharge')}</a>
                        </div>
                        <div class="col-xs-4">
                            <a href="{$_url}customers/sync/{$d['id']}"
                                onclick="return confirm('This will sync Customer to Mikrotik?')"
                                class="btn btn-primary btn-sm btn-block">{Lang::T('Sync')}</a>
                        </div>
                    </div>
                </div>
            </div>
        {else}
            <a href="{$_url}prepaid/recharge/{$d['id']}"
                class="btn btn-success btn-sm btn-block mt-1">{Lang::T('Recharge')}</a><br>
        {/if}
        <a href="{$_url}customers/list" class="btn btn-primary btn-sm btn-block mt-1">{Lang::T('Back')}</a><br>
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
                            <th>{$_L['Invoice']}</th>
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
                            <tr onclick="window.location.href = '{$_url}prepaid/view/{$ds['id']}'" style="cursor:pointer;">
                                <td>{$ds['invoice']}</td>
                                <td>{$ds['username']}</td>
                                <td>{$ds['plan_name']}</td>
                                <td>{Lang::moneyFormat($ds['price'])}</td>
                                <td>{$ds['type']}</td>
                                <td class="text-success">{Lang::dateAndTimeFormat($ds['recharged_on'],$ds['recharged_time'])}
                                </td>
                                <td class="text-danger">{Lang::dateAndTimeFormat($ds['expiration'],$ds['time'])}</td>
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
                                <td class="text-primary">{Lang::dateTimeFormat($ds['created_date'])}</td>
                                <td class="text-danger">{Lang::dateTimeFormat($ds['expired_date'])}</td>
                                <td class="text-success">{if $ds['status']!=1}{Lang::dateTimeFormat($ds['paid_date'])}{/if}</td>
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