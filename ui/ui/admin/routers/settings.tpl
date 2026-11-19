{include file="sections/header.tpl"}
{*
    Network > Routers > Settings: every router option in one place. Saved by routers.php "settings", which keeps only these
    settings and checks each value. A switch posts 0 from its hidden field, or 1 when it is on.
*}
{function name=rt_switch key='' def='0' icon='' title='' hint=''}
    <label class="mc-row mc-row--switch">
        <input type="hidden" name="{$key}" value="0">
        <input type="checkbox" name="{$key}" value="1" {if ($_c[$key]|default:$def) == '1'}checked{/if}>
        <span class="mc-row__icon"><i class="fa {$icon}" aria-hidden="true"></i></span>
        <span class="mc-row__text"><b>{$title}</b><small>{$hint}</small></span>
        <span class="mc-switch__track" aria-hidden="true"></span>
    </label>
{/function}

<form class="ls st mc nf" method="post" role="form" autocomplete="off" action="{Text::url('routers/settings')}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">

    <div class="nf__grid">
        <div class="nf__main">
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-heartbeat" aria-hidden="true"></i> {Lang::T('Monitoring')}</h3>
                        <small>{Lang::T('How PHPNuxBill keeps an eye on your routers.')}</small>
                    </div>
                </header>
                <div class="mc-rows nf__switches">
                    {rt_switch key='router_check' def='0' icon='fa-plug' title=Lang::T('Router Check')
                        hint=Lang::T('The cron checks that each router answers and marks it Online or Offline on the list, and emails you when one stops answering. With many routers it can take a while.')}
                    {rt_switch key='router_health' def='1' icon='fa-tachometer' title=Lang::T('Live health')
                        hint=Lang::T('CPU, memory and users online on the Routers page, read from each router when the page opens, and the Live graphs. Turn off with many routers or slow links.')}
                    {rt_switch key='router_monitor' def='1' icon='fa-history' title=Lang::T('History')
                        hint=Lang::T('The cron reads each router about every 5 minutes and keeps 30 days of CPU, memory, users online and uplink traffic for the History tab.')}
                </div>
                {if !$history_ready}
                <p class="nf__hint nf__hint--warn"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> {Lang::T('History and alerts need the database update: run update.php (or php system/cron/migrate.php).')}</p>
                {/if}
            </section>

            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-bell" aria-hidden="true"></i> {Lang::T('Alerts')}</h3>
                        <small>{Lang::T('A message when a router goes offline (after two missed readings), comes back, restarts, or runs at 90% CPU or memory. Needs History.')}</small>
                    </div>
                </header>
                <div class="mc-rows nf__switches">
                    {rt_switch key='router_alerts' def='0' icon='fa-bullhorn' title=Lang::T('Send router alerts')
                        hint=Lang::T('Routers behind Routely VPN are already watched by Routely: with both on, you may get the same news twice.')}
                    {rt_switch key='router_alert_telegram' def='1' icon='fa-paper-plane' title=Lang::T('By Telegram')
                        hint=Lang::T('To the Telegram chat set in Settings > Notifications.')}
                    {rt_switch key='router_alert_email' def='0' icon='fa-envelope' title=Lang::T('By email')
                        hint=Lang::T('To the sender address set in Settings > Email.')}
                </div>
                <div class="st-card-body">
                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="router_alert_phone">{Lang::T('Also to this phone')} <small>{Lang::T('Optional')}</small></label>
                            <input type="tel" class="form-control" id="router_alert_phone" name="router_alert_phone" maxlength="20" inputmode="tel"
                                pattern="\+?[0-9 ]{literal}{7,18}{/literal}" value="{$_c['router_alert_phone']|default:''|escape}" placeholder="+234 800 000 0000">
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="router_alert_via">{Lang::T('Send by')}</label>
                            <select class="form-control" id="router_alert_via" name="router_alert_via">
                                <option value="wa" {if ($_c['router_alert_via']|default:'wa') == 'wa'}selected{/if}>WhatsApp</option>
                                <option value="sms" {if ($_c['router_alert_via']|default:'wa') == 'sms'}selected{/if}>SMS</option>
                            </select>
                        </div>
                    </div>
                    <p class="nf__hint">{Lang::T('Through the WhatsApp or SMS gateway set in Settings > Notifications. Alerts name the router and what happened, never its address or login.')}</p>
                </div>
            </section>

            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Backups')}</h3>
                        <small>{Lang::T('A backup of each router saved on the router itself, once a day.')}</small>
                    </div>
                </header>
                <div class="mc-rows nf__switches">
                    {rt_switch key='router_backup' def='0' icon='fa-clock' title=Lang::T('Nightly backups')
                        hint=Lang::T('The cron saves phpnuxbill-auto-YYYYMMDD.backup on each enabled router. Files named that way count as nightly backups and are removed when older than the number below, so do not use the name for your own; other backups are never removed.')}
                </div>
                <div class="st-card-body">
                    <div class="nf__field">
                        <label class="nf__label" for="router_backup_keep">{Lang::T('Nightly backups to keep on each router')}</label>
                        <input type="number" class="form-control" id="router_backup_keep" name="router_backup_keep" min="1" max="30" step="1"
                            value="{$_c['router_backup_keep']|default:'7'|escape}">
                        <p class="nf__hint">{Lang::T('Older nightly backups are removed from the router. To keep a copy elsewhere, download it from Files in Winbox or WebFig.')}</p>
                    </div>
                </div>
            </section>
        </div>

        <aside class="nf__side">
            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-server" aria-hidden="true"></i> {Lang::T('Router Settings')}</h3></div>
                </header>
                <div class="st-card-body">
                    <p class="nf__hint">{Lang::T('These apply to every router. The cron must run (every 5 minutes is best) for Router Check, History, alerts and backups.')}</p>
                </div>
                <footer class="st-card-foot">
                    <a class="ls-btn" href="{Text::url('routers/list')}"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                    <button type="submit" class="ls-btn ls-btn--primary"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                </footer>
            </section>
        </aside>
    </div>
</form>

{include file="sections/footer.tpl"}
