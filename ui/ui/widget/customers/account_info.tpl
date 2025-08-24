<div class="box box-primary box-solid">
    <div class="box-header">
        <h3 class="box-title">{Lang::T('Your Account Information')}</h3>
    </div>
    <div style="margin-left: 5px; margin-right: 5px;">
        <table class="table table-bordered table-striped table-bordered table-hover mb-0" style="margin-bottom: 0px;">
            <tr>
                <td class="small text-success text-uppercase text-normal">{Lang::T('Usernames')}</td>
                <td class="small mb15">{$_user['username']}</td>
            </tr>
            <tr>
                <td class="small text-success text-uppercase text-normal">{Lang::T('Password')}</td>
                <td class="small mb15"><input type="password" value="{$_user['password']}"
                        style="width:100%; border: 0px;" onmouseleave="this.type = 'password'"
                        onmouseenter="this.type = 'text'" onclick="this.select()"></td>
            </tr>
            <tr>
                <td class="small text-success text-uppercase text-normal">{Lang::T('Service Type')}</td>
                <td class="small mb15">
                    {if $_user.service_type == 'Hotspot'}
                        Hotspot
                    {elseif $_user.service_type == 'PPPoE'}
                        PPPoE
                    {elseif $_user.service_type == 'VPN'}
                        VPN
                    {elseif $_user.service_type == 'Others' || $_user.service_type == null}
                        Others
                    {/if}
                </td>
            </tr>

            {if $_c['enable_balance'] == 'yes'}
                <tr>
                    <td class="small text-warning text-uppercase text-normal">{Lang::T('Yours Balances')}</td>
                    <td class="small mb15 text-bold">
                        {Lang::moneyFormat($_user['balance'])}
                        {if $_user['auto_renewal'] == 1}
                            <a class="label label-success pull-right" href="{Text::url('home&renewal=0')}"
                                onclick="return ask(this, '{Lang::T('Disable auto renewal?')}')">{Lang::T('Auto Renewal
                                On')}</a>
                        {else}
                            <a class="label label-danger pull-right" href="{Text::url('home&renewal=1')}"
                                onclick="return ask(this, '{Lang::T('Enable auto renewal?')}')">{Lang::T('Auto Renewal
                                Off')}</a>
                        {/if}
                    </td>
                </tr>
            {/if}
        </table>&nbsp;&nbsp;
    </div>
    {if $abills && count($abills)>0}
        <div class="box-header">
            <h3 class="box-title">{Lang::T('Additional Billing')}</h3>
        </div>

        <div style="margin-left: 5px; margin-right: 5px;">
            <table class="table table-bordered table-striped table-bordered table-hover mb-0" style="margin-bottom: 0px;">
                {assign var="total" value=0}
                {foreach $abills as $k => $v}
                    <tr>
                        <td class="small text-success text-uppercase text-normal">{str_replace(' Bill', '', $k)}</td>
                        <td class="small mb15">
                            {if strpos($v, ':') === false}
                                {Lang::moneyFormat($v)}
                                <sup title="recurring">∞</sup>
                                {assign var="total" value=$v+$total}
                            {else}
                                {assign var="exp" value=explode(':',$v)}
                                {Lang::moneyFormat($exp[0])}
                                <sup title="{$exp[1]} more times">{if $exp[1]==0}{Lang::T('paid
                                off')}{else}{$exp[1]}x{/if}</sup>
                                {if $exp[1]>0}
                                    {assign var="total" value=$exp[0]+$total}
                                {/if}
                            {/if}
                        </td>
                    </tr>
                {/foreach}
                <tr>
                    <td class="small text-success text-uppercase text-normal"><b>{Lang::T('Total')}</b></td>
                    <td class="small mb15"><b>
                            {if $total==0}
                                {ucwords(Lang::T('paid off'))}
                            {else}
                                {Lang::moneyFormat($total)}
                            {/if}
                        </b></td>
                </tr>
            </table>
        </div> &nbsp;&nbsp;
    {/if}
</div>
