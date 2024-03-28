{include file="sections/header.tpl"}
<!-- routers-add -->

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">Radius - Edit NAS</div>
            <div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{$_url}radius/nas-edit-post/{$d['id']}">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Router Name')}</label>
                        <div class="col-md-6">
                            <input type="text" required class="form-control" id="shortname" name="shortname" value="{$d['shortname']}" maxlength="32">
                            <p class="help-block">{Lang::T('Name of Area that router operated')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('IP Address')}</label>
                        <div class="col-md-6">
                            <input type="text" placeholder="192.168.88.1" value="{$d['nasname']}" required class="form-control" id="nasname"
                                name="nasname" maxlength="128">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Secret</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="secret" name="secret" required value="{$d['secret']}"
                                onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'" maxlength="60">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Ports</label>
                        <div class="col-md-2">
                            <input type="text" class="form-control" id="ports" name="ports" placeholder="null" value="{$d['ports']}">
                        </div>
                        <label class="col-md-2 control-label">Type</label>
                        <div class="col-md-2">
                            <input type="text" class="form-control" id="type" name="type" value="other" value="{$d['type']}" required
                                placeholder="other">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Server</label>
                        <div class="col-md-2">
                            <input type="text" class="form-control" id="server" name="server" value="{$d['server']}" placeholder="null">
                        </div>
                        <label class="col-md-2 control-label">Community</label>
                        <div class="col-md-2">
                            <input type="text" class="form-control" id="community" name="community" value="{$d['community']}" placeholder="null">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Description')}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="description" name="description"> {htmlentities($d['description'])}</textarea>
                            <p class="help-block">{Lang::T('Explain Coverage of router')}</p>
                        </div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label"><a href="{$_url}routers/add">{Lang::T('Routers')}</a></label>
						<div class="col-md-6">
							<select id="routers" name="routers" class="form-control select2">
                                <option value="">No Router</option>
                                {foreach $routers as $rs}
                                <option {if $rs['name'] == $d['routers']}selected{/if} value="{$rs['name']}">{$rs['name']}</option>
                                {/foreach}
                            </select>
						</div>
                        <p class="help-block col-md-4">Assign NAS to Router</p>
                    </div>
                    <div class="form-gro
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary"
                                type="submit">{Lang::T('Save Changes')}</button>
                            Or <a href="{$_url}radius/nas-list">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}