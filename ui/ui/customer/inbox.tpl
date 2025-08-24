{include file="customer/header.tpl"}

<div class="container">
    {if $tipe == 'view'}
    <div class="panel panel-default">
        <div class="panel-body">
            <h3>{$mail.subject|escape:'html':'UTF-8'}</h3>
            <h5>
                <strong>From:</strong> {$mail.from|escape:'html':'UTF-8'}
                <span class="pull-right text-muted" data-toggle="tooltip"
                    title="Read at {Lang::dateTimeFormat($mail.date_read)}">
                    {Lang::dateTimeFormat($mail.date_created)}
                </span>
            </h5>
            <hr>
            <div class="mailbox-read-message">
                {if Text::is_html($mail.body)}
                {$mail.body}
                {else}
                {nl2br($mail.body|htmlspecialchars_decode)}
                {/if}
            </div>
        </div>
        <div class="panel-footer clearfix">
            <div class="pull-left">
                <a href="{Text::url('mail')}" class="btn btn-primary btn-sm"><i class="fa fa-arrow-left"></i>
                    {Lang::T("Back")}</a>
                <a href="{Text::url('mail/delete/')}{$mail.id}" class="btn btn-danger btn-sm"
                    onclick="return ask(this, '{Lang::T(" Delete")}?')"><i class="fa fa-trash-o"></i>
                    {Lang::T("Delete")}</a>
                <a href="https://api.whatsapp.com/send?text={if Text::is_html($mail.body)}{urlencode(strip_tags($mail.body))}{else}{urlencode($mail.body)}{/if}"
                    class="btn btn-success btn-sm"><i class="fa fa-share"></i> {Lang::T("Share")}</a>
            </div>
            <div class="pull-right">
                {if $prev}
                <a href="{Text::url('mail/view/', $prev)}" class="btn btn-default btn-sm"><i
                        class="fa fa-chevron-left"></i> {Lang::T("Previous")}</a>
                {/if}
                {if $next}
                <a href="{Text::url('mail/view/', $next)}" class="btn btn-default btn-sm">{Lang::T("Next")} <i
                        class="fa fa-chevron-right"></i></a>
                {/if}
            </div>
        </div>
    </div>
    {else}
    <div class="panel panel-default">
        <div class="panel-heading clearfix">
            <form method="post" class="form-inline pull-right">
                <div class="input-group">
                    <input type="text" name="q" class="form-control input-sm" placeholder="{Lang::T('Search')}..."
                        value="{$q|escape:'html':'UTF-8'}">
                    <span class="input-group-btn">
                        <button type="submit" class="btn btn-success btn-sm"><i
                                class="glyphicon glyphicon-search"></i></button>
                    </span>
                </div>
            </form>
        </div>

        <div class="panel-body no-padding">
            <div class="table-responsive">
                <div style="margin-left: 10px; margin-right: 10px;">&nbsp;
                    <table class="table table-hover table-striped table-bordered">
                        <thead class="hidden-xs">
                            <tr>
                                <th>{Lang::T("Subject")}</th>
                                <th>{Lang::T("From")}</th>
                                <th class="hidden-xs">{Lang::T("Date")}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $mails as $mail}
                            <tr>
                                <td>
                                    <a href="{Text::url('mail/view/')}{$mail.id}">
                                        {if $mail.date_read == null}
                                        <i class="fa fa-envelope text-warning" title="Unread"></i>
                                        {else}
                                        <i class="fa fa-envelope-o text-muted" title="Read"></i>
                                        {/if}
                                        <strong>{$mail.subject|escape:'html':'UTF-8'}</strong>
                                    </a>
                                </td>
                                <td>{$mail.from|escape:'html':'UTF-8'}</td>
                                <td class="hidden-xs">{Lang::dateTimeFormat($mail.date_created)}</td>
                            </tr>
                            {/foreach}
                            {if empty($mails)}
                            <tr>
                                <td colspan="3" class="text-center">{Lang::T("No email found.")}</td>
                            </tr>
                            {/if}
                        </tbody>
                    </table>
                    <div class="mailbox-controls" style="margin-bottom: 20px; text-align: center;">
                        <!-- Check all button -->
                        <a href="{Text::url('mail')}" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i></a>
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
    </div>
    {/if}
</div>
{include file="customer/footer.tpl"}