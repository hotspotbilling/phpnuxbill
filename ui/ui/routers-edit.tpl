{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-default panel-hovered panel-stacked mb30">
            <div class="panel-heading">{$_L['Edit_Router']}</div>
                <div class="panel-body">
                    <form class="form-horizontal" method="post" role="form" action="{$_url}routers/edit-post" >
                        <input type="hidden" name="id" value="{$d['id']}">
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Status')}</label>
                            <div class="col-md-10">
                                <label class="radio-inline warning">
                                    <input type="radio" {if $d['enabled'] == 1}checked{/if} name="enabled" value="1"> Enable
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" {if $d['enabled'] == 0}checked{/if} name="enabled" value="0"> Disable
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{$_L['Router_Name']}</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="name" name="name" maxlength="32" value="{$d['name']}">
                                <p class="help-block">{Lang::T('Name of Area that router operated')}</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{$_L['IP_Address']}</label>
                            <div class="col-md-6">
                                <input type="text" placeholder="192.168.88.1:8728" class="form-control" id="ip_address" name="ip_address" value="{$d['ip_address']}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{$_L['Username']}</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="username" name="username" value="{$d['username']}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{$_L['Router_Secret']}</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="password" name="password" value="{$d['password']}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{$_L['Description']}</label>
                            <div class="col-md-6">
                                <textarea class="form-control" id="description" name="description">{$d['description']}</textarea>
                                <p class="help-block">{Lang::T('Explain Coverage of router')}</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-lg-offset-2 col-lg-10">
                                <button class="btn btn-primary waves-effect waves-light" type="submit">{$_L['Save']}</button>
                                Or <a href="{$_url}routers/list">{$_L['Cancel']}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
