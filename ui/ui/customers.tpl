{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">{$_L['Manage_Accounts']}</div>
            <div class="panel-body">
                <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                    <div class="col-md-8">
                        <form id="site-search" method="post" action="{$_url}customers/list/">
                            <div class="input-group">
                                <input type="text" name="search" value="{$search}" class="form-control"
                                    placeholder="{Lang::T('Search')}...">
                                <div class="input-group-btn">
                                    <button class="btn btn-success" type="submit"><span
                                            class="fa fa-search"></span></button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <a href="{$_url}customers/add" class="btn btn-primary btn-block waves-effect"><i
                                class="ion ion-android-add"> </i> {$_L['Add_Contact']}</a>
                    </div>&nbsp;
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>{$_L['Manage']}</th>
                                <th>{$_L['Username']}</th>
                                <th>{$_L['Full_Name']}</th>
                                <th>{Lang::T('Balance')}</th>
                                <th>{$_L['Phone_Number']}</th>
                                <th>{$_L['Email']}</th>
                                <th>{$_L['Created_On']}</th>
                                <th>{$_L['Recharge']}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $d as $ds}
                                <tr>
                                    <td align="center">
                                        <a href="{$_url}customers/view/{$ds['id']}" id="{$ds['id']}"
                                            class="btn btn-success btn-sm">{Lang::T('View')}</a>
                                    </td>
                                    <td>{$ds['username']}</td>
                                    <td>{$ds['fullname']}</td>
                                    <td>{Lang::moneyFormat($ds['balance'])}</td>
                                    <td>{$ds['phonenumber']}</td>
                                    <td>{$ds['email']}</td>
                                    <td>{Lang::dateTimeFormat($ds['created_at'])}</td>
                                    <td align="center"><a href="{$_url}prepaid/recharge-user/{$ds['id']}" id="{$ds['id']}"
                                            class="btn btn-primary btn-sm">{$_L['Recharge']}</a></td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                {$paginator['contents']}
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}