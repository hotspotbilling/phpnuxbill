{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">{$_L['Recharge_Account']}</h3>
            </div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{$_url}prepaid/edit-post">
                    <input type="hidden" name="id" value="{$d['id']}">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Select_Account']}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="username" name="username"
                                value="{$d['username']}" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Service_Plan']}</label>
                        <div class="col-md-6">
                            <select id="id_plan" name="id_plan" class="form-control select2">
                                {foreach $p as $ps}
                                    <option value="{$ps['id']}" {if $d['plan_id'] eq $ps['id']} selected {/if}>
                                    {if $ps['is_radius']=='1'}Radius{else}{$ps['routers']}{/if} - {$ps['name_plan']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Created_On']}</label>
                        <div class="col-md-4">
                            <input type="date" class="form-control" name="expiration" readonly
                                value="{$d['recharged_on']}">
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control" placeholder="00:00:00" readonly
                                value="{$d['recharged_time']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Expires_On']}</label>
                        <div class="col-md-4">
                            <input type="date" class="form-control" id="expiration" name="expiration"
                                value="{$d['expiration']}">
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control" id="time" name="time" placeholder="00:00:00"
                                value="{$d['time']}">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-success waves-effect waves-light"
                                type="submit">{$_L['Edit']}</button>
                            Or <a href="{$_url}prepaid/list">{$_L['Cancel']}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}