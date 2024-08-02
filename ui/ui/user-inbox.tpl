{include file="sections/user-header.tpl"}

{if $tipe == 'view'}

{else}
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Inbox</h3>
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
                                    {if $mail['date_read'] == null}
                                        <i class="fa fa-envelope text-yellow" title="unread"></i>
                                    {else}
                                        <i class="fa fa-envelope-o text-yellow" title="read"></i>
                                    {/if}<a href="{$_url}mail/view/{$mail['id']}"><b>{$mail['subject']}</b></a>
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