{include file="sections/header.tpl"}
<form method="post">
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="panel panel-info panel-hovered">
                <div class="panel-heading">{Lang::T('Payment Gateway')}</div>
                <div class="table-responsive">
                    <table class="table table-striped table-condensed">
                        <tbody>
                            {foreach $pgs as $pg}
                                <tr>
                                    <td width="10" align="center" valign="center"><input type="checkbox" name="pgs[]"
                                            {if in_array($pg, $actives)}checked{/if} value="{$pg}"></td>
                                    <td><a href="{$_url}paymentgateway/{$pg}"
                                            class="btn btn-block btn-{if in_array($pg, $actives)}info{else}default{/if} text-left">{ucwords($pg)}</a>
                                    </td>
                                    <td width="10"><a href="{$_url}paymentgateway/delete/{$pg}"
                                            onclick="return confirm('{Lang::T('Delete')} {$pg}?')" class="btn btn-danger"><i
                                                class="glyphicon glyphicon-trash"></i></a></td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                <div class="panel-footer"><button type="submit" class="btn btn-primary btn-block" name="save"
                        value="actives">{Lang::T('Save Changes')}</button></div>
            </div>
        </div>
    </div>
</form>
{include file="sections/footer.tpl"}