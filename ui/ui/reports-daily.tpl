{include file="sections/header.tpl"}
<!-- reports-daily -->
<div class="row">
    <div class="col-lg-3">
        <form method="get" class="form">
            <div class="box box-primary box-solid">
                <div class="box-header" onclick="showFilter()" style=" cursor: pointer;">
                    <h3 class="box-title">{Lang::T('Filter')}</h3>
                </div>
                <div id="filter_box" class="box-body hidden-xs hidden-sm hidden-md">
                    <input type="hidden" name="_route" value="reports">
                    <label>{Lang::T('Start Date')}</label>
                    <input type="date" class="form-control" name="sd" value="{$sd}">
                    <label>{Lang::T('Start time')}</label>
                    <input type="time" class="form-control" name="ts" value="{$ts}">
                    <label>{Lang::T('End Date')}</label>
                    <input type="date" class="form-control" name="ed" value="{$ed}">
                    <label>{Lang::T('End Time')}</label>
                    <input type="time" class="form-control" name="te" value="{$te}">
                    <label>{Lang::T('Type')}</label>
                    <select class="form-control" name="tps[]" multiple>
                        {foreach $types as $type}
                            <option value="{$type}" {if in_array($type, $tps)}selected{/if}>{$type}</option>
                        {/foreach}
                    </select>
                    <label>{Lang::T('Internet Plans')}</label>
                    <select class="form-control" name="plns[]" multiple>
                        {foreach $plans as $plan}
                            <option value="{$plan}" {if in_array($plan, $plns)}selected{/if}>{$plan}</option>
                        {/foreach}
                    </select>
                    <label>{Lang::T('Methods')}</label>
                    <select class="form-control" name="mts[]" multiple>
                        {foreach $methods as $method}
                            <option value="{$method}" {if in_array($method, $mts)}selected{/if}>{$method}</option>
                        {/foreach}
                    </select>
                    <label>{Lang::T('Routers')}</label>
                    <select class="form-control" name="rts[]" multiple>
                        {foreach $routers as $router}
                            <option value="{$router}" {if in_array($router, $rts)}selected{/if}>{Lang::T($router)}</option>
                        {/foreach}
                    </select>
                    <input type="submit" class="btn btn-success btn-block">
                </div>
            </div>
        </form>
    </div>
    <div class="col-lg-9">
        <div class="box box-primary box-solid">
            <div class="table-responsive">
                <table class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>
                                <a href="{$_url}export/print-by-date&{$filter}" class="btn btn-default" target="_blank"><i
                                        class="ion ion-printer"></i></a>
                                <a href="{$_url}export/pdf-by-date&{$filter}" class="btn btn-default"><i
                                        class="fa fa-file-pdf-o"></i></a>
                            </th>
                            <th colspan="7"></th>
                        </tr>
                        <tr>
                            <th>{Lang::T('Username')}</th>
                            <th>{Lang::T('Type')}</th>
                            <th>{Lang::T('Plan Name')}</th>
                            <th>{Lang::T('Plan Price')}</th>
                            <th>{Lang::T('Created On')}</th>
                            <th>{Lang::T('Expires On')}</th>
                            <th>{Lang::T('Method')}</th>
                            <th>{Lang::T('Routers')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $d as $ds}
                            <tr>
                                <td>{$ds['username']}</td>
                                <td>{$ds['type']}</td>
                                <td>{$ds['plan_name']}</td>
                                <td class="text-right">{Lang::moneyFormat($ds['price'])}</td>
                                <td>{Lang::dateAndTimeFormat($ds['recharged_on'],$ds['recharged_time'])}</td>
                                <td>{Lang::dateAndTimeFormat($ds['expiration'],$ds['time'])}</td>
                                <td>{$ds['method']}</td>
                                <td>{$ds['routers']}</td>
                            </tr>
                        {/foreach}
                        <tr>
                            <th>{Lang::T('Total')}</th>
                            <td colspan="2"></td>
                            <th class="text-right">{Lang::moneyFormat($dr)}</th>
                            <td colspan="4"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="box-footer">
                <p class="text-center small text-info">{Lang::T('All Transactions at Date')}:
                {Lang::dateAndTimeFormat($sd, $ts)} - {Lang::dateAndTimeFormat($ed, $te)}</p>
            </div>
        </div>
    </div>
</div>

{include file="pagination.tpl"}

<div class="bs-callout bs-callout-warning bg-gray">
    <h4>Information</h4>
    <p>Export and Print will show all data without pagination.</p>
</div>

<script>
    var isShow = false;

    function showFilter() {
        if (isShow) {
            $("#filter_box").addClass("hidden-xs");
            $("#filter_box").addClass("hidden-sm");
            $("#filter_box").addClass("hidden-md");
            isShow = false;
        } else {
            // remove class
            $("#filter_box").removeClass("hidden-xs");
            $("#filter_box").removeClass("hidden-sm");
            $("#filter_box").removeClass("hidden-md");
            isShow = true;
        }
    }
</script>

{include file="sections/footer.tpl"}