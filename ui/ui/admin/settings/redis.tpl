{include file="sections/header.tpl"}
{* Redis cache. Field names are what settings.php (case 'redis') reads: redis_enabled, redis_host, redis_port, redis_pass,
   redis_pass_clear, redis_cache_dashboard, redis_dashboard_ttl, redis_cache_radius, redis_radius_ttl; save=save, action=test,
   and action=flush on the Cache Management form. *}

<div class="ls st mc st-redis">
    {if !$stats['extension_loaded']}
    <div class="rf-note rf-note--warn" role="alert">
        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
        <div><b>{Lang::T('PHP Redis Extension Not Installed')}</b>
            {Lang::T('The PHP Redis extension is required to enable in-memory caching. On your Ubuntu server, run:')}
            <pre class="st-cmd">sudo apt update && sudo apt install -y redis-server php-redis && sudo systemctl restart redis-server</pre></div>
    </div>
    {elseif !empty($_c['redis_enabled']) && !$stats['connected']}
    <div class="rf-note rf-note--warn" role="alert">
        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
        <div><b>{Lang::T('Redis Service Unreachable')}</b>
            {Lang::T('Redis is enabled in settings, but PHPNuxBill cannot connect to')} <code>{$_c['redis_host']|default:'127.0.0.1'|escape}:{$_c['redis_port']|default:'6379'|escape}</code>.
            {Lang::T('Please verify the host, port, and authentication password.')}</div>
    </div>
    {/if}

    {* overview *}
    <div class="rf-kpis">
        <div class="ls-card rf-kpi">
            <span class="rf-kpi__icon {if $stats['connected']}st-ok{else}rf-kpi__icon--warn{/if}"><i class="fa fa-server" aria-hidden="true"></i></span>
            <small>{Lang::T('Connection')}</small>
            {if $stats['connected']}
            <b class="st-state st-state--ok"><span class="st-dot"></span> {Lang::T('Connected')}</b>
            <span class="rf-kpi__sub">Redis {$stats['version']|escape} &middot; {Lang::T('up')} {$stats['uptime']|escape}</span>
            {else}
            <b class="st-state st-state--off"><span class="st-dot"></span> {Lang::T('Not connected')}</b>
            <span class="rf-kpi__sub">{if empty($_c['redis_enabled'])}{Lang::T('Caching is off: pages read the database directly')}{else}{Lang::T('Falling back to the database')}{/if}</span>
            {/if}
        </div>
        <div class="ls-card rf-kpi">
            <span class="rf-kpi__icon"><i class="fa fa-microchip" aria-hidden="true"></i></span>
            <small>{Lang::T('Memory')}</small>
            <b>{$stats['used_memory']|escape}</b>
            {$memPct = 0}{if $stats['peak_bytes'] > 0}{$memPct = min(100, round($stats['used_bytes'] / $stats['peak_bytes'] * 100))}{/if}
            <div class="st-meter" role="img" aria-label="{Lang::T('Memory')}: {$memPct}% {Lang::T('of peak')}"><span style="width: {$memPct}%"></span></div>
            <span class="rf-kpi__sub">{Lang::T('Peak')} {$stats['peak_memory']|escape} &middot; {$stats['clients']|number_format} {Lang::T('clients')}</span>
        </div>
        <div class="ls-card rf-kpi">
            <span class="rf-kpi__icon"><i class="fa fa-cubes" aria-hidden="true"></i></span>
            <small>{Lang::T('Cached keys')}</small>
            <b>{$stats['total_keys']|number_format}</b>
            <span class="rf-kpi__sub">{Lang::T('Prefix')} <code class="st-chip">nuxbill:*</code></span>
        </div>
        <div class="ls-card rf-kpi">
            <span class="rf-kpi__icon"><i class="fa fa-tachometer" aria-hidden="true"></i></span>
            <small>{Lang::T('Hit ratio')}</small>
            <b>{$stats['hit_ratio']}%</b>
            <div class="st-meter st-meter--{if $stats['hit_ratio'] >= 80}ok{elseif $stats['hit_ratio'] >= 50}mid{else}low{/if}" role="img" aria-label="{Lang::T('Hit ratio')}: {$stats['hit_ratio']}%"><span style="width: {$stats['hit_ratio']}%"></span></div>
            <span class="rf-kpi__sub">{$stats['hits']|number_format} {Lang::T('hits')} &middot; {$stats['misses']|number_format} {Lang::T('misses')}</span>
        </div>
    </div>

    <div class="st-redis__grid">
        <form method="post" autocomplete="off" role="form" action="{Text::url('settings/redis')}" class="ls-card st-redis__form">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <header class="st-card-head">
                <div><h3><i class="fa fa-sliders" aria-hidden="true"></i> {Lang::T('Redis Engine & Modules Configuration')}</h3></div>
            </header>
            <div class="st-card-body">
                <div class="mc-rows">
                    <label class="mc-row mc-row--switch">
                        <input type="checkbox" id="redis_enabled" value="1" name="redis_enabled" {if $_c['redis_enabled']==1}checked{/if}>
                        <span class="mc-row__icon"><i class="fa fa-bolt" aria-hidden="true"></i></span>
                        <span class="mc-row__text">
                            <b>{Lang::T('Enable Redis Caching')}</b>
                            <small>{Lang::T('Master switch. When disabled, PHPNuxBill safely queries the database directly.')}</small>
                        </span>
                        <span class="mc-switch__track" aria-hidden="true"></span>
                    </label>
                </div>

                <p class="mc-label st-redis__sec">{Lang::T('Connection')}</p>
                <div class="st-redis__conn">
                    <div>
                        <label class="mc-sublabel" for="rdHost">{Lang::T('Redis Host')}</label>
                        <input class="form-control" type="text" id="rdHost" name="redis_host" value="{$_c['redis_host']|default:'127.0.0.1'|escape}" placeholder="127.0.0.1">
                    </div>
                    <div>
                        <label class="mc-sublabel" for="rdPort">{Lang::T('Redis Port')}</label>
                        <input class="form-control" type="number" id="rdPort" name="redis_port" value="{$_c['redis_port']|default:'6379'|escape}" placeholder="6379">
                    </div>
                    <div class="st-redis__pass">
                        <label class="mc-sublabel" for="rdPass">{Lang::T('Redis Password')}</label>
                        <input class="form-control" type="password" id="rdPass" name="redis_pass" value="" autocomplete="new-password"
                            placeholder="{if isset($_c['redis_pass']) && $_c['redis_pass']|strlen > 0}{Lang::T('Password is set. Leave blank to keep it')}{else}{Lang::T('Leave blank if no password')}{/if}">
                        {if isset($_c['redis_pass']) && $_c['redis_pass']|strlen > 0}
                        <label class="st-all st-redis__clear"><input type="checkbox" name="redis_pass_clear" value="1"> {Lang::T('Clear stored password')}</label>
                        {/if}
                    </div>
                </div>

                <p class="mc-label st-redis__sec">{Lang::T('Target Modules to Cache')}</p>
                <div class="mc-rows">
                    <label class="mc-row mc-row--switch">
                        <input type="checkbox" id="redis_cache_dashboard" value="1" name="redis_cache_dashboard" {if $_c['redis_cache_dashboard']==1}checked{/if}>
                        <span class="mc-row__icon"><i class="fa fa-dashboard" aria-hidden="true"></i></span>
                        <span class="mc-row__text">
                            <b>{Lang::T('Admin Dashboard Cache')}</b>
                            <small>{Lang::T('Caches revenue aggregations, charts, customer stats')}</small>
                        </span>
                        <span class="mc-switch__track" aria-hidden="true"></span>
                    </label>
                    <div class="mc-row">
                        <span class="mc-row__icon"><i class="fa fa-clock" aria-hidden="true"></i></span>
                        <span class="mc-row__text">
                            <label for="rdDashTtl"><b>{Lang::T('Dashboard Cache TTL (Seconds)')}</b></label>
                            <small>{Lang::T('Default: 60 seconds (Admin can also click "Data Refreshed" to force reload)')}</small>
                        </span>
                        <input class="form-control mc-row__control" type="number" min="5" id="rdDashTtl" name="redis_dashboard_ttl" value="{$_c['redis_dashboard_ttl']|default:'60'|escape}">
                    </div>
                    <label class="mc-row mc-row--switch">
                        <input type="checkbox" id="redis_cache_radius" value="1" name="redis_cache_radius" {if $_c['redis_cache_radius']==1}checked{/if}>
                        <span class="mc-row__icon"><i class="fa fa-shield" aria-hidden="true"></i></span>
                        <span class="mc-row__text">
                            <b>{Lang::T('RADIUS Authentication & Plan Cache')}</b>
                            <small>{Lang::T('Drastically reduces DB query load from FreeRADIUS auth requests')}</small>
                        </span>
                        <span class="mc-switch__track" aria-hidden="true"></span>
                    </label>
                    <div class="mc-row">
                        <span class="mc-row__icon"><i class="fa fa-clock" aria-hidden="true"></i></span>
                        <span class="mc-row__text">
                            <label for="rdRadTtl"><b>{Lang::T('RADIUS Cache TTL (Seconds)')}</b></label>
                            <small>{Lang::T('Default: 60 seconds (Auto-invalidated on plan/password change)')}</small>
                        </span>
                        <input class="form-control mc-row__control" type="number" min="5" id="rdRadTtl" name="redis_radius_ttl" value="{$_c['redis_radius_ttl']|default:'60'|escape}">
                    </div>
                </div>
            </div>
            <footer class="st-card-foot">
                <button class="ls-btn" name="action" value="test" type="submit"><i class="fa fa-plug" aria-hidden="true"></i> {Lang::T('Test Connection')}</button>
                <button class="ls-btn ls-btn--primary" name="save" value="save" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Settings')}</button>
            </footer>
        </form>

        <aside class="st-redis__side">
            <form method="post" action="{Text::url('settings/redis')}" class="ls-card">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <input type="hidden" name="action" value="flush">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-trash" aria-hidden="true"></i> {Lang::T('Cache Management')}</h3></div>
                </header>
                <div class="st-card-body">
                    <p class="st-text">{Lang::T('Flush all cached dashboard charts, customer lookups, and session keys from Redis memory.')}</p>
                    <p class="st-text st-text--dim">{$stats['total_keys']|number_format} {Lang::T('keys are cached now. Pages rebuild them as they are opened.')}</p>
                </div>
                <footer class="st-card-foot">
                    <span></span>
                    {* type=button: the footer script puts a 5-second spinner on every submit button, which fights the confirm dialog;
                       ask() submits the form itself once confirmed *}
                    <button type="button" class="ls-btn ls-btn--danger" {if !$stats['connected']}disabled title="{Lang::T('Redis is not connected')}"{/if}
                        onclick="return ask(this, '{Lang::T('Are you sure you want to flush all Redis cache?')|escape:'javascript'}');">
                        <i class="fa fa-refresh" aria-hidden="true"></i> {Lang::T('Purge / Flush Redis Cache')}</button>
                </footer>
            </form>

            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-info-circle" aria-hidden="true"></i> {Lang::T('Why use Redis?')}</h3></div>
                </header>
                <ul class="st-why">
                    <li><i class="fa fa-dashboard" aria-hidden="true"></i><div><b>{Lang::T('Dashboard Optimization')}</b>{Lang::T('The admin dashboard executes up to 30 SQL aggregation queries. Caching for 60 seconds makes dashboard loads instant.')}</div></li>
                    <li><i class="fa fa-shield" aria-hidden="true"></i><div><b>{Lang::T('FreeRADIUS Scalability')}</b>{Lang::T('When thousands of users or routers authenticate simultaneously, MySQL can become a bottleneck. Redis serves authentication responses in sub-milliseconds.')}</div></li>
                </ul>
            </section>
        </aside>
    </div>
</div>

{include file="sections/footer.tpl"}
