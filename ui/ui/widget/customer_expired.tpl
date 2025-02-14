<div class="panel panel-warning mb20 panel-hovered project-stats table-responsive">
    <div class="panel-heading">{Lang::T('Customers Expired, Today')}</div>
    <div class="table-responsive">
        <table class="table table-condensed">
            <thead>
                <tr>
                    <th>{Lang::T('Username')}</th>
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
                        <td><a href="{Text::url('customers/viewu/',$expired['username'])}">{$expired['username']}</a></td>
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