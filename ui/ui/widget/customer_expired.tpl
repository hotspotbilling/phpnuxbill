<div class="panel panel-warning mb20 panel-hovered project-stats table-responsive">
    <div class="panel-heading">{Lang::T('Customers Expired, Today')}</div>
    <div class="table-responsive">
        <table class="table table-condensed">
            <thead>
                <tr>
                    <th>
                        <select style="border: 0px; width: 100%; background-color: #f9f9f9;"
                            onchange="changeExpiredDefault(this)">
                            <option value="username" {if $cookie['expdef'] == 'username'}selected{/if}>
                                {Lang::T('Username')}
                            </option>
                            <option value="fullname" {if $cookie['expdef'] == 'fullname'}selected{/if}>
                                {Lang::T('Full Name')}</option>
                            <option value="phone" {if $cookie['expdef'] == 'phone'}selected{/if}>{Lang::T('Phone')}
                            </option>
                            <option value="email" {if $cookie['expdef'] == 'email'}selected{/if}>{Lang::T('Email')}
                            </option>
                        </select>
                    </th>
                    <th>{Lang::T('Created / Expired')}</th>
                    <th>{Lang::T('Internet Package')}</th>
                    <th>{Lang::T('Location')}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $expire as $expired}
                    {assign var="rem_exp" value="{$expired['expiration']} {$expired['time']}"}
                    {assign var="rem_started" value="{$expired['recharged_on']} {$expired['recharged_time']}"}
                    <tr>
                        <td><a href="{Text::url('customers/view/',$expired['id'])}">
                                {if $cookie['expdef'] == 'fullname'}
                                    {$expired['fullname']}
                                {elseif $cookie['expdef'] == 'phone'}
                                    {$expired['phonenumber']}
                                {elseif $cookie['expdef'] == 'email'}
                                    {$expired['email']}
                                {else}
                                    {$expired['username']}
                                {/if}
                            </a></td>
                        <td><small data-toggle="tooltip" data-placement="top"
                                title="{Lang::dateAndTimeFormat($expired['recharged_on'],$expired['recharged_time'])}">{Lang::timeElapsed($rem_started)}</small>
                            /
                            <span data-toggle="tooltip" data-placement="top"
                                title="{Lang::dateAndTimeFormat($expired['expiration'],$expired['time'])}">{Lang::timeElapsed($rem_exp)}</span>
                        </td>
                        <td>{$expired['namebp']}</td>
                        <td>{$expired['routers']}</td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
    &nbsp; {include file="pagination.tpl"}
</div>
<script>
    function changeExpiredDefault(fl) {
        setCookie('expdef', fl.value, 365);
        setTimeout(() => {
            location.reload();
        }, 1000);
    }
</script>