{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{$_L['Add_Plan']}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{$_url}services/pppoe-add-post">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Status')}</label>
                        <div class="col-md-10">
                            <label class="radio-inline warning">
                                <input type="radio" checked name="enabled" value="1"> Enable
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="enabled" value="0"> Disable
                            </label>
                        </div>
                    </div>
                    {if $_c['radius_enable']}
                        <div class="form-group">
                            <label class="col-md-2 control-label">Radius</label>
                            <div class="col-md-6">
                                <label class="radio-inline">
                                    <input type="checkbox" name="radius" onclick="isRadius(this)" value="1"> Radius Plan
                                </label>
                            </div>
                            <p class="help-block col-md-4">{Lang::T('Cannot be change after saved')}</p>
                        </div>
                    {/if}
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Plan_Name']}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="name_plan" maxlength="40" name="name_plan">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><a
                                href="{$_url}bandwidth/add">{$_L['BW_Name']}</a></label>
                        <div class="col-md-6">
                            <select id="id_bw" name="id_bw" class="form-control select2">
                                <option value="">{$_L['Select_BW']}...</option>
                                {foreach $d as $ds}
                                    <option value="{$ds['id']}">{$ds['name_bw']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Plan_Price']}</label>
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-addon">{$_c['currency_code']}</span>
                                <input type="number" class="form-control" name="price" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Plan_Validity']}</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="validity" name="validity">
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" id="validity_unit" name="validity_unit">
                                <option value="Mins">{$_L['Mins']}</option>
                                <option value="Hrs">{$_L['Hrs']}</option>
                                <option value="Days">{$_L['Days']}</option>
                                <option value="Months">{$_L['Months']}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><a
                                href="{$_url}routers/add">{$_L['Router_Name']}</a></label>
                        <div class="col-md-6">
                            <select id="routers" name="routers" required class="form-control select2">
                                <option value=''>{$_L['Select_Routers']}</option>
                                {foreach $r as $rs}
                                    <option value="{$rs['name']}">{$rs['name']}</option>
                                {/foreach}
                            </select>
                            <p class="help-block">{Lang::T('Cannot be change after saved')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><a href="{$_url}pool/add">{$_L['Pool']}</a></label>
                        <div class="col-md-6">
                            <select id="pool_name" name="pool_name" required class="form-control select2">
                                <option value=''>{$_L['Select_Pool']}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><a
                                href="{$_url}pool/add">{Lang::T('Expired IP Pool')}</a></label>
                        <div class="col-md-6">
                            <select id="pool_expired" name="pool_expired" class="form-control select2">
                                <option value=''>{$_L['Select_Pool']}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-offset-2 col-md-10">
                            <button class="btn btn-primary waves-effect waves-light"
                                type="submit">{$_L['Save']}</button>
                            Or <a href="{$_url}services/pppoe">{$_L['Cancel']}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
{if $_c['radius_enable']}
    {literal}
        <script>
            function isRadius(cek) {
                if (cek.checked) {
                    document.getElementById("routers").required = false;
                    document.getElementById("routers").disabled = true;
                    $.ajax({
                        url: "index.php?_route=autoload/pool",
                        data: "routers=radius",
                        cache: false,
                        success: function(msg) {
                            $("#pool_name").html(msg);
                        }
                    });
                    $.ajax({
                        url: "index.php?_route=autoload/pool",
                        data: "routers=radius",
                        cache: false,
                        success: function(msg) {
                            $("#pool_expired").html(msg);
                        }
                    });
                } else {
                    document.getElementById("routers").required = true;
                    document.getElementById("routers").disabled = false;
                }
            }
        </script>
    {/literal}
{/if}
{include file="sections/footer.tpl"}