{if $_c['router_check'] && count($routeroffs)> 0}
    <div class="panel panel-danger">
        <div class="panel-heading text-bold">{Lang::T('Routers Offline')}</div>
        <div class="table-responsive">
            <table class="table table-condensed">
                <tbody>
                    {foreach $routeroffs as $ros}
                        <tr>
                            <td><a href="{Text::url('routers/edit/',$ros['id'])}" class="text-bold text-red">{$ros['name']}</a></td>
                            <td data-toggle="tooltip" data-placement="top" class="text-red"
                                    title="{Lang::dateTimeFormat($ros['last_seen'])}">{Lang::timeElapsed($ros['last_seen'])}
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
{/if}