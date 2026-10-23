{include file="sections/header.tpl"}

<style>
    /* =========================================
       LIGHT MODE (DEFAULT)
       ========================================= */
    .redis-stats-card {
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.07), 0 1px 2px rgba(0, 0, 0, 0.04);
        padding: 20px;
        margin-bottom: 25px;
        position: relative;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #00c0ef;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .redis-stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
    }
    .redis-stats-card.card-success { border-left-color: #00a65a; }
    .redis-stats-card.card-danger { border-left-color: #dd4b39; }
    .redis-stats-card.card-warning { border-left-color: #f39c12; }
    .redis-stats-card.card-info { border-left-color: #00c0ef; }

    .redis-card-title {
        font-size: 12px;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.5px;
        font-weight: 600;
        margin-bottom: 8px;
    }
    .redis-card-val {
        font-size: 26px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .redis-card-sub {
        font-size: 13px;
        color: #64748b;
        margin-top: 6px;
    }
    .redis-card-sub code {
        background-color: #f1f5f9;
        color: #0284c7;
        border: 1px solid #cbd5e1;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
    }
    .redis-card-icon {
        position: absolute;
        right: 18px;
        top: 20px;
        font-size: 38px;
        color: rgba(0, 0, 0, 0.08);
    }
    
    /* Toggle switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 52px;
        height: 26px;
        vertical-align: middle;
        margin: 0;
    }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1;
        transition: .3s;
        border-radius: 26px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background-color: #ffffff;
        transition: .3s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    input:checked + .slider {
        background-color: #00a65a;
    }
    input:checked + .slider:before {
        transform: translateX(26px);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-online {
        background-color: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .status-offline {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
    .pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        animation: pulse 1.8s infinite;
    }
    .pulse-online { background-color: #16a34a; }
    .pulse-offline { background-color: #dc2626; }
    @keyframes pulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(22, 163, 74, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
    }

    /* =========================================
       DARK MODE (When body or html has .dark-mode or .dark)
       ========================================= */
    body.dark-mode .redis-stats-card,
    html.dark-mode .redis-stats-card,
    .dark-mode .redis-stats-card,
    .dark .redis-stats-card {
        background: #1e1e2d;
        border: 1px solid #2b2b40;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
    }
    body.dark-mode .redis-stats-card:hover,
    html.dark-mode .redis-stats-card:hover,
    .dark-mode .redis-stats-card:hover,
    .dark .redis-stats-card:hover {
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
    }
    body.dark-mode .redis-stats-card.card-success,
    .dark-mode .redis-stats-card.card-success { border-left-color: #00a65a !important; }
    body.dark-mode .redis-stats-card.card-danger,
    .dark-mode .redis-stats-card.card-danger { border-left-color: #dd4b39 !important; }
    body.dark-mode .redis-stats-card.card-warning,
    .dark-mode .redis-stats-card.card-warning { border-left-color: #f39c12 !important; }
    body.dark-mode .redis-stats-card.card-info,
    .dark-mode .redis-stats-card.card-info { border-left-color: #00c0ef !important; }

    body.dark-mode .redis-card-title,
    html.dark-mode .redis-card-title,
    .dark-mode .redis-card-title,
    .dark .redis-card-title {
        color: #94a3b8;
    }

    body.dark-mode .redis-card-val,
    html.dark-mode .redis-card-val,
    .dark-mode .redis-card-val,
    .dark .redis-card-val {
        color: #ffffff;
    }

    body.dark-mode .redis-card-sub,
    html.dark-mode .redis-card-sub,
    .dark-mode .redis-card-sub,
    .dark .redis-card-sub {
        color: #cbd5e1;
    }

    body.dark-mode .redis-card-sub code,
    html.dark-mode .redis-card-sub code,
    .dark-mode .redis-card-sub code,
    .dark .redis-card-sub code {
        background-color: #151521;
        color: #38bdf8;
        border: 1px solid #334155;
    }

    body.dark-mode .redis-card-icon,
    html.dark-mode .redis-card-icon,
    .dark-mode .redis-card-icon,
    .dark .redis-card-icon {
        color: rgba(255, 255, 255, 0.08);
    }

    body.dark-mode .status-badge.status-online,
    html.dark-mode .status-badge.status-online,
    .dark-mode .status-badge.status-online,
    .dark .status-badge.status-online {
        background-color: rgba(16, 185, 129, 0.2);
        color: #4ade80;
        border: 1px solid rgba(16, 185, 129, 0.35);
    }

    body.dark-mode .status-badge.status-offline,
    html.dark-mode .status-badge.status-offline,
    .dark-mode .status-badge.status-offline,
    .dark .status-badge.status-offline {
        background-color: rgba(221, 75, 57, 0.2);
        color: #f87171;
        border: 1px solid rgba(221, 75, 57, 0.35);
    }

    body.dark-mode .slider,
    html.dark-mode .slider,
    .dark-mode .slider,
    .dark .slider {
        background-color: #3b3f5c;
    }

    .redis-config-divider {
        border-bottom: 1px solid #e2e8f0;
    }
    body.dark-mode .redis-config-divider,
    html.dark-mode .redis-config-divider,
    .dark-mode .redis-config-divider,
    .dark .redis-config-divider {
        border-bottom-color: #2b2b40 !important;
    }
</style>

<div class="row">
    <div class="col-md-12">
        {if !$stats['extension_loaded']}
        <div class="alert alert-danger" style="border-radius: 6px;">
            <h4><i class="fa fa-exclamation-triangle"></i> {Lang::T('PHP Redis Extension Not Installed')}</h4>
            <p>{Lang::T('The PHP Redis extension is required to enable in-memory caching. On your Ubuntu server, run:')}</p>
            <pre style="background: #2b2b2b; color: #00ff7f; padding: 10px; border-radius: 4px; margin-top: 8px;">sudo apt update && sudo apt install -y redis-server php-redis && sudo systemctl restart redis-server</pre>
        </div>
        {elseif !empty($_c['redis_enabled']) && !$stats['connected']}
        <div class="alert alert-warning" style="border-radius: 6px;">
            <h4><i class="fa fa-warning"></i> {Lang::T('Redis Service Unreachable')}</h4>
            <p>{Lang::T('Redis is enabled in settings, but PHPNuxBill cannot connect to')} <code>{$_c['redis_host']|default:'127.0.0.1'}:{$_c['redis_port']|default:'6379'}</code>. {Lang::T('Please verify the host, port, and authentication password.')}</p>
        </div>
        {/if}
    </div>
</div>

<!-- 4 Graphical Metric Cards -->
<div class="row">
    <div class="col-sm-6 col-md-3">
        <div class="redis-stats-card {if $stats['connected']}card-success{else}card-danger{/if}">
            <div class="redis-card-title">{Lang::T('Connection Status')}</div>
            <div class="redis-card-val">
                {if $stats['connected']}
                    <span class="status-badge status-online"><span class="pulse-dot pulse-online"></span> {Lang::T('Connected')}</span>
                {else}
                    <span class="status-badge status-offline"><span class="pulse-dot pulse-offline"></span> {Lang::T('Disconnected')}</span>
                {/if}
            </div>
            <div class="redis-card-sub">
                {if $stats['connected']}
                    Redis v{$stats['version']} | {Lang::T('Uptime')}: {$stats['uptime']}
                {else}
                    {Lang::T('Fallback to MySQL active')}
                {/if}
            </div>
            <i class="fa fa-server redis-card-icon"></i>
        </div>
    </div>

    <div class="col-sm-6 col-md-3">
        <div class="redis-stats-card card-info">
            <div class="redis-card-title">{Lang::T('Memory Usage')}</div>
            <div class="redis-card-val">{$stats['used_memory']}</div>
            <div class="redis-card-sub">{Lang::T('Peak')}: {$stats['peak_memory']} | {Lang::T('Clients')}: {$stats['clients']}</div>
            <i class="fa fa-microchip redis-card-icon"></i>
        </div>
    </div>

    <div class="col-sm-6 col-md-3">
        <div class="redis-stats-card card-warning">
            <div class="redis-card-title">{Lang::T('Cached Keys')}</div>
            <div class="redis-card-val">{$stats['total_keys']}</div>
            <div class="redis-card-sub">{Lang::T('Prefix')}: <code>nuxbill:*</code></div>
            <i class="fa fa-cubes redis-card-icon"></i>
        </div>
    </div>

    <div class="col-sm-6 col-md-3">
        <div class="redis-stats-card card-success">
            <div class="redis-card-title">{Lang::T('Hit Ratio')}</div>
            <div class="redis-card-val">{$stats['hit_ratio']}%</div>
            <div class="redis-card-sub">{Lang::T('Hits')}: {$stats['hits']|number_format} | {Lang::T('Misses')}: {$stats['misses']|number_format}</div>
            <i class="fa fa-tachometer redis-card-icon"></i>
        </div>
    </div>
</div>

<!-- Main Configuration Form -->
<form class="form-horizontal" method="post" autocomplete="off" role="form" action="{Text::url('settings/redis')}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    
    <div class="row">
        <!-- Configuration Column -->
        <div class="col-md-8">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">
                    <i class="fa fa-sliders"></i> {Lang::T('Redis Engine & Modules Configuration')}
                </div>
                <div class="panel-body">
                    
                    <!-- Master Toggle -->
                    <!-- Master Toggle -->
                    <div class="form-group redis-config-divider" style="padding-bottom: 15px;">
                        <label class="col-md-4 control-label" style="font-size: 15px;">
                            {Lang::T('Enable Redis Caching')}
                        </label>
                        <div class="col-md-8">
                            <label class="switch">
                                <input type="checkbox" id="redis_enabled" value="1" name="redis_enabled" {if $_c['redis_enabled']==1}checked{/if}>
                                <span class="slider"></span>
                            </label>
                            <p class="help-block" style="margin-top: 6px;">{Lang::T('Master switch. When disabled, PHPNuxBill safely queries the database directly.')}</p>
                        </div>
                    </div>

                    <!-- Connection details -->
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Redis Host')}</label>
                        <div class="col-md-5">
                            <input class="form-control" type="text" name="redis_host" value="{$_c['redis_host']|default:'127.0.0.1'}" placeholder="127.0.0.1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Redis Port')}</label>
                        <div class="col-md-3">
                            <input class="form-control" type="number" name="redis_port" value="{$_c['redis_port']|default:'6379'}" placeholder="6379">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Redis Password')}</label>
                        <div class="col-md-5">
                            <input class="form-control" type="password" name="redis_pass" value="" autocomplete="new-password" placeholder="{if isset($_c['redis_pass']) && $_c['redis_pass']|strlen > 0}{Lang::T('Password is set. Leave blank to keep it')}{else}{Lang::T('Leave blank if no password')}{/if}">
                            {if isset($_c['redis_pass']) && $_c['redis_pass']|strlen > 0}
                            <div class="checkbox">
                                <label><input type="checkbox" name="redis_pass_clear" value="1"> {Lang::T('Clear stored password')}</label>
                            </div>
                            {/if}
                        </div>
                    </div>

                    <hr>
                    <h4 class="redis-section-title" style="margin-bottom: 15px; font-weight: 600;"><i class="fa fa-cubes"></i> {Lang::T('Target Modules to Cache')}</h4>

                    <!-- Dashboard Cache Toggle -->
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Admin Dashboard Cache')}</label>
                        <div class="col-md-8">
                            <label class="switch">
                                <input type="checkbox" id="redis_cache_dashboard" value="1" name="redis_cache_dashboard" {if $_c['redis_cache_dashboard']==1}checked{/if}>
                                <span class="slider"></span>
                            </label>
                            <span class="text-muted" style="margin-left: 10px;">{Lang::T('Caches revenue aggregations, charts, customer stats')}</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Dashboard Cache TTL (Seconds)')}</label>
                        <div class="col-md-3">
                            <input class="form-control" type="number" min="5" name="redis_dashboard_ttl" value="{$_c['redis_dashboard_ttl']|default:'60'}">
                        </div>
                        <div class="col-md-5">
                            <span class="help-block">{Lang::T('Default: 60 seconds (Admin can also click "Data Refreshed" to force reload)')}</span>
                        </div>
                    </div>

                    <!-- RADIUS Cache Toggle -->
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('RADIUS Authentication & Plan Cache')}</label>
                        <div class="col-md-8">
                            <label class="switch">
                                <input type="checkbox" id="redis_cache_radius" value="1" name="redis_cache_radius" {if $_c['redis_cache_radius']==1}checked{/if}>
                                <span class="slider"></span>
                            </label>
                            <span class="text-muted" style="margin-left: 10px;">{Lang::T('Drastically reduces DB query load from FreeRADIUS auth requests')}</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('RADIUS Cache TTL (Seconds)')}</label>
                        <div class="col-md-3">
                            <input class="form-control" type="number" min="5" name="redis_radius_ttl" value="{$_c['redis_radius_ttl']|default:'60'}">
                        </div>
                        <div class="col-md-5">
                            <span class="help-block">{Lang::T('Default: 60 seconds (Auto-invalidated on plan/password change)')}</span>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 25px;">
                        <div class="col-md-offset-4 col-md-8">
                            <button class="btn btn-primary waves-effect waves-light" name="save" value="save" type="submit">
                                <i class="fa fa-save"></i> {Lang::T('Save Settings')}
                            </button>
                            <button class="btn btn-info waves-effect waves-light" name="action" value="test" type="submit" style="margin-left: 10px;">
                                <i class="fa fa-plug"></i> {Lang::T('Test Connection')}
                            </button>
                        </div>
                    </div>

                </div>
            </div>
            </form>
        </div>

        <!-- Management & Quick Tools Column -->
        <div class="col-md-4">
            <!-- Purge Cache Card -->
            <div class="panel panel-default panel-hovered panel-stacked mb30">
                <div class="panel-heading"><i class="fa fa-trash"></i> {Lang::T('Cache Management')}</div>
                <div class="panel-body">
                    <p>{Lang::T('Flush all cached dashboard charts, customer lookups, and session keys from Redis memory.')}</p>
                    <form method="post" action="{Text::url('settings/redis')}">
                        <input type="hidden" name="csrf_token" value="{$csrf_token}">
                        <input type="hidden" name="action" value="flush">
                        <button type="submit" class="btn btn-danger btn-block" onclick="return ask(this, '{Lang::T('Are you sure you want to flush all Redis cache?')}');">
                            <i class="fa fa-refresh"></i> {Lang::T('Purge / Flush Redis Cache')}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Help / Info Card -->
            <div class="panel panel-info panel-hovered panel-stacked mb30">
                <div class="panel-heading"><i class="fa fa-info-circle"></i> {Lang::T('Why use Redis?')}</div>
                <div class="panel-body" style="font-size: 13px; line-height: 1.6;">
                    <p><strong>{Lang::T('Dashboard Optimization')}</strong>: {Lang::T('The admin dashboard executes up to 30 SQL aggregation queries. Caching for 60 seconds makes dashboard loads instant.')}</p>
                    <p><strong>{Lang::T('FreeRADIUS Scalability')}</strong>: {Lang::T('When thousands of users or routers authenticate simultaneously, MySQL can become a bottleneck. Redis serves authentication responses in sub-milliseconds.')}</p>
                </div>
            </div>
        </div>
    </div>

{include file="sections/footer.tpl"}
