{include file="sections/user-header.tpl"}

{if $tipe == 'view'}
    <div class="box box-primary">
        <div class="box-body no-padding">
            <div class="mailbox-read-info">
                <h3>{$mail['subject']}</h3>
                <h5>From: {$mail['from']}
                    <span class="mailbox-read-time pull-right" data-toggle="tooltip" data-placement="top"
                        title="Read at {Lang::dateTimeFormat($mail['date_read'])}">{Lang::dateTimeFormat($mail['date_created'])}</span>
                </h5>
            </div>
            <div class="mailbox-read-message">
                {if Text::is_html($mail['body'])}
                    {$mail['body']}
                {else}
                    {nl2br($mail['body'])}
                {/if}
        </div>
    </div>
    <div class="box-footer">
        <div class="pull-right">
            {if $prev}
            <a href="{$_url}mail/view/{$prev}" class="btn btn-default"><i class="fa fa-chevron-left"></i>
                {Lang::T("Previous")}</a>
            {/if}
            {if $next}
            <a href="{$_url}mail/view/{$next}" class="btn btn-default"><i class="fa fa-chevron-right"></i>
                {Lang::T("Next")}</a>
            {/if}
        </div>
        <a href="{$_url}mail/delete/{$mail['id']}" class="btn btn-danger"
            onclick="return confirm('{Lang::T("Delete")}?')"><i class="fa fa-trash-o"></i>
            {Lang::T("Delete")}</a>
        <a href="https://api.whatsapp.com/send?text={if Text::is_html($mail['body'])}{urlencode(strip_tags($mail['body']))}{else}{urlencode($mail['body'])}{/if}" class="btn btn-primary"><i class="fa fa-share"></i> {Lang::T("Share")}</a>
    </div>
    <!-- /.box-footer -->
</div>
{else}
<div class="box box-primary">
    <div class="box-header with-border">
        <form method="post">
            <div class="box-tools pull-right">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="{Lang::T('Search')}..." value="{$q}">
                    <div class="input-group-btn">
                        <button type="submit" class="btn btn-success"><span
                                class="glyphicon glyphicon-search"></span></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="box-body no-padding">
        <div class="mailbox-controls">
            <a href="{$_url}mail" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i></a>
            <div class="pull-right">
                <div class="btn-group">
                    {if $p>0}
                    <a href="{$_url}mail&p={$p-1}&q={urlencode($q)}" class="btn btn-default btn-sm"><i
                            class="fa fa-chevron-left"></i></a>
                    {/if}
                    <a href="{$_url}mail&p={$p+1}&q={urlencode($q)}" class="btn btn-default btn-sm"><i
                            class="fa fa-chevron-right"></i></a>
                </div>
            </div>
        </div>
        <div class="table-responsive mailbox-messages">
            <table class="table table-hover table-striped table-bordered">
                <tbody>
                    {foreach $mails as $mail}
                    <tr>
                        <td class="mailbox-subject">
                            <a href="{$_url}mail/view/{$mail['id']}">
                                <div>
                                    {if $mail['date_read'] == null}
                                    <i class="fa fa-envelope text-yellow" title="unread"></i>
                                    {else}
                                    <i class="fa fa-envelope-o text-yellow" title="read"></i>
                                    {/if}
                                    <b>{$mail['subject']}</b>
                                </div>
                            </a>
                        </td>
                        <td class="mailbox-name">{$mail['from']}</td>
                        <td class="mailbox-attachment"></td>
                        <td class="mailbox-date">{Lang::dateTimeFormat($mail['date_created'])}</td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
    <div class="box-footer no-padding">
        <div class="mailbox-controls">
            <a href="{$_url}mail" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i></a>
            <div class="pull-right">
                <div class="btn-group">
                    {if $p>0}
                        <a href="{$_url}mail&p={$p-1}&q={urlencode($q)}" class="btn btn-default btn-sm"><i
                                class="fa fa-chevron-left"></i></a>
                    {/if}
                    <a href="{$_url}mail&p={$p+1}&q={urlencode($q)}" class="btn btn-default btn-sm"><i
                            class="fa fa-chevron-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

{/if}

{include file="sections/user-footer.tpl"}