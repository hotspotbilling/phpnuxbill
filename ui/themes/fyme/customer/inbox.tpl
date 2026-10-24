{include file="customer/header.tpl"}

{if $tipe == 'view'}
    <div class="box box-primary">
        <div class="box-body no-padding">
            <div class="mailbox-read-info">
                <h3>{$mail.subject|escape:'html':'UTF-8'}</h3>
                <h5>From: {$mail.from|escape:'html':'UTF-8'}
                    <span class="mailbox-read-time pull-right" data-toggle="tooltip" data-placement="top"
                        title="Read at {Lang::dateTimeFormat($mail.date_read)}">{Lang::dateTimeFormat($mail.date_created)}</span>
                </h5>
            </div>
            <div class="mailbox-read-message">
                {if Text::is_html($mail.body)}
                    {$mail.body}
                {else}
                    {nl2br($mail.body|htmlspecialchars_decode)}
                {/if}
            </div>
        </div>
        <div class="box-footer">
            <div class="pull-right">
                {if $prev}
                    <a href="{Text::url('mail/view/', $prev)}" class="btn btn-default"><i class="fa fa-chevron-left"></i>
                        {Lang::T("Previous")}</a>
                {/if}
                {if $next}
                    <a href="{Text::url('mail/view/', $next)}" class="btn btn-default"><i class="fa fa-chevron-right"></i>
                        {Lang::T("Next")}</a>
                {/if}
            </div>
            <a href="{Text::url('mail')}" class="btn btn-primary"><i class="fa fa-arrow-left"></i> {Lang::T("Back")}</a>
            <a href="{Text::url('mail/delete/')}{$mail.id}" class="btn btn-danger"
                onclick="return ask(this, '{Lang::T("Delete")}?')"><i class="fa fa-trash-o"></i>
                {Lang::T("Delete")}</a>
            <a href="https://api.whatsapp.com/send?text={if Text::is_html($mail.body)}{urlencode(strip_tags($mail.body))}{else}{urlencode($mail.body)}{/if}"
                class="btn btn-success"><i class="fa fa-share"></i> {Lang::T("Share")}</a>
        </div>
        <!-- /.box-footer -->
    </div>
{else}
    <div class="box box-primary">
        <div class="box-header with-border">
            <form method="post">
                <div class="box-tools pull-right">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="{Lang::T('Search')}..." value="{$q|escape:'html':'UTF-8'}">
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
                <a href="{Text::url('mail')}" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i></a>
                <div class="pull-right">
                    <div class="btn-group">
                        {if $p>0}
                            <a href="{Text::url('mail&p=')}{$p-1}&q={urlencode($q)}" class="btn btn-default btn-sm"><i
                                    class="fa fa-chevron-left"></i></a>
                        {/if}
                        <a href="{Text::url('mail&p=')}{$p+1}&q={urlencode($q)}" class="btn btn-default btn-sm"><i
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
                                    <a href="{Text::url('mail/view/')}{$mail.id}">
                                        <div>
                                            {if $mail.date_read == null}
                                                <i class="fa fa-envelope text-yellow" title="unread"></i>
                                            {else}
                                                <i class="fa fa-envelope-o text-yellow" title="read"></i>
                                            {/if}
                                            <b>{$mail.subject|escape:'html':'UTF-8'}</b>
                                        </div>
                                    </a>
                                </td>
                                <td class="mailbox-name">{$mail.from|escape:'html':'UTF-8'}</td>
                                <td class="mailbox-attachment"></td>
                                <td class="mailbox-date">{Lang::dateTimeFormat($mail.date_created)}</td>
                            </tr>
                        {/foreach}
                        {if empty($mails)}
                            <tr>
                                <td colspan="4">{Lang::T("No email found.")}</td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        </div>
        <div class="box-footer no-padding">
            <div class="mailbox-controls">
                <a href="{Text::url('mail')}" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i></a>
                <div class="pull-right">
                    <div class="btn-group">
                        {if $p>0}
                            <a href="{Text::url('mail&p=')}{$p-1}&q={urlencode($q)}" class="btn btn-default btn-sm"><i
                                    class="fa fa-chevron-left"></i></a>
                        {/if}
                        <a href="{Text::url('mail&p=')}{$p+1}&q={urlencode($q)}" class="btn btn-default btn-sm"><i
                                class="fa fa-chevron-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
{/if}

{include file="customer/footer.tpl"}
