{include file="sections/header.tpl"}
<div class="row">
    <div class="col-md-6 col-md-offset-3">
        <div class="panel panel-info panel-hovered">
            <div class="panel-heading">{Lang::T('Payment Gateway')}</div>
            <div class="table-responsive">
                <table class="table table-striped table-condensed">
                    <tbody>
                        {foreach $pgs as $pg}
                            <tr>
                                <td><a href="{$_url}paymentgateway/{$pg}"
                                        class="btn btn-block btn-default text-left">{ucwords($pg)}</a></td>
                                <td width="10"><a href="{$_url}paymentgateway/delete/{$pg}" onclick="return confirm('{Lang::T('Delete')} {$pg}?')" class="btn btn-danger"><i
                                            class="glyphicon glyphicon-trash"></i></a></td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
{include file="sections/footer.tpl"}