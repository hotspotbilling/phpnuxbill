{include file="sections/header.tpl"}

<div class="dash-toolbar">
    <a href="{Text::url('dashboard')}&refresh=1" class="dash-refresh" title="{Lang::T('Refresh Data')}"
        onclick="this.classList.add('is-loading')">
        <i class="fa fa-refresh"></i><span>{Lang::T('Refresh')}</span>
    </a>
    {include file="admin/dash-arrange.tpl" kind="blocks" btn="dash-refresh"}
</div>

<div class="dash-blocks">
<!-- Cron Monitor Status Alert Banner -->
{if $can_view_system && !empty($system_status.cron)}
<div class="dash-block" data-block="cron" data-title="{Lang::T('Cron status')|escape}" style="order:{$dash_order['cron']|default:0}">
{if $system_status.cron.status == 'danger'}
<div class="isp-card isp-rounded-lg isp-padding-4 isp-margin-bottom-6"
    style="border-left: 4px solid #f56565; background: rgba(245, 101, 101, 0.08);">
    <div class="isp-flex isp-align-center isp-justify-between" style="flex-wrap: wrap; gap: 10px;">
        <div class="isp-flex isp-align-center" style="gap: 12px;">
            <div
                style="flex: none; width: 40px; height: 40px; border-radius: 50%; background: rgba(245, 101, 101, 0.2); display: flex; align-items: center; justify-content: center; color: #f56565; font-size: 18px;">
                <i class="fa fa-exclamation-triangle"></i>
            </div>
            <div>
                <strong style="color: #e53e3e; font-size: 14px;">{Lang::T('Cron appears not to have been set up or is
                    not running')}</strong>
                <p style="margin: 2px 0 0; font-size: 12px; opacity: 0.85;">{$system_status.cron.message}</p>
            </div>
        </div>
        <div class="isp-flex isp-align-center" style="gap: 8px;">
            <span class="badge badge-danger" style="font-size: 11px; padding: 4px 8px;">{Lang::T('Offline')}</span>
            <span style="font-size: 11px; opacity: 0.7;">{Lang::T('Run via CLI')}:
                <code>php system/cron.php</code></span>
        </div>
    </div>
</div>
{elseif $system_status.cron.status == 'warning'}
<div class="isp-card isp-rounded-lg isp-padding-4 isp-margin-bottom-6"
    style="border-left: 4px solid #ed8936; background: rgba(237, 137, 54, 0.08);">
    <div class="isp-flex isp-align-center isp-justify-between" style="flex-wrap: wrap; gap: 10px;">
        <div class="isp-flex isp-align-center" style="gap: 12px;">
            <div
                style="flex: none; width: 40px; height: 40px; border-radius: 50%; background: rgba(237, 137, 54, 0.2); display: flex; align-items: center; justify-content: center; color: #ed8936; font-size: 18px;">
                <i class="fa fa-clock"></i>
            </div>
            <div>
                <strong style="color: #dd6b20; font-size: 14px;">{Lang::T('Cron has not run for over 1 hour. Please
                    check your setup.')}</strong>
                <p style="margin: 2px 0 0; font-size: 12px; opacity: 0.85;">{$system_status.cron.message}</p>
            </div>
        </div>
        <div class="isp-flex isp-align-center" style="gap: 8px;">
            <span class="badge badge-warning" style="font-size: 11px; padding: 4px 8px;">{Lang::T('Delayed')}</span>
            <span style="font-size: 11px; opacity: 0.7;">{Lang::T('Last ran')}: {$system_status.cron.run_date}</span>
        </div>
    </div>
</div>
{else}
<div class="dash-cron isp-margin-bottom-6">
    <div class="dash-cron-main">
        <span class="dash-cron-icon"><i class="fa fa-check"></i></span>
        <span class="dash-cron-text">
            <strong>{Lang::T('Cron Job')}:</strong> {Lang::T('Cron Job last ran on')}:
            <strong>{$system_status.cron.run_date}</strong> <span
                class="dash-cron-ago">({$system_status.cron.time_ago})</span>
        </span>
    </div>
    <div class="dash-cron-meta">
        <span class="badge badge-success">{Lang::T('Healthy')}</span>
        <span class="dash-cron-uptime">{Lang::T('Uptime')}: {$system_status.uptime.formatted}</span>
    </div>
</div>
{/if}
</div>
{/if}

<!-- Stats Cards (100% Real Database Values) -->
<div class="dash-block" data-block="stats" data-title="{Lang::T('Statistics')|escape}" style="order:{$dash_order['stats']|default:0}">
<div class="top-stats-grid">
    <!-- Card 1: Total Customers -->
    <div class="isp-stat-card isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between">
            <div id="total-customers-content">
                <p class="isp-text-sm isp-font-medium isp-text-gray-dark">{Lang::T('Total Customers')}</p>
                <p class="isp-text-2xl isp-font-bold isp-margin-top-1">{$c_all}</p>
                <p
                    class="isp-text-sm {if $c_percentage_change >= 0}isp-text-green{else}isp-text-red{/if} isp-margin-top-1 isp-flex isp-align-center">
                    <i
                        class="fas {if $c_percentage_change >= 0}fa-arrow-up{else}fa-arrow-down{/if} isp-margin-right-1"></i>
                    {$c_percentage_change}% {Lang::T('from last month')}
                </p>
            </div>
            <div
                class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-blue-light isp-flex isp-align-center isp-justify-center">
                <i class="fa fa-users" style="color: #4299e1; font-size: 18px;"></i>
            </div>
        </div>
    </div>

    <!-- Card 2: Active Connections -->
    <div class="isp-stat-card isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between">
            <div id="active-connections-content">
                <p class="isp-text-sm isp-font-medium isp-text-gray-dark">{Lang::T('Active Connections')}</p>
                <p class="isp-text-2xl isp-font-bold isp-margin-top-1">{$u_act}</p>
                <p class="isp-text-sm isp-text-green isp-margin-top-1 isp-flex isp-align-center">
                    <i
                        class="fas {if $c_percentage_change >= 0}fa-arrow-up{else}fa-arrow-down{/if} isp-margin-right-1"></i>
                    {$u_act_pct}% {Lang::T('Active')} {if $u_expired > 0}• {$u_expired} {Lang::T('Expired')}{/if}
                </p>
            </div>
            <div
                class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-green-light isp-flex isp-align-center isp-justify-center">
                <i class="fas fa-wifi isp-text-green"></i>
            </div>
        </div>
    </div>

    <!-- Card 3: Online Users (Live - Mikrotik API, Radius REST, Radius MySQL) -->
    <div class="isp-stat-card isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between">
            <div id="online-users-content">
                <div class="isp-flex isp-align-center" style="gap: 6px;">
                    <p class="isp-text-sm isp-font-medium isp-text-gray-dark">{Lang::T('Online Users')}</p>
                    <span class="live-dot-pulse" title="{Lang::T('Live Online Real-time')}"></span>
                </div>
                <p class="isp-text-2xl isp-font-bold isp-margin-top-1" id="online-users-total">
                    {$online_users.total|default:0}</p>
                <div class="isp-margin-top-1 isp-flex isp-align-center" style="flex-wrap: wrap; gap: 4px;">
                    <span class="badge"
                        style="font-size: 10px; padding: 2px 5px; background: rgba(66, 153, 225, 0.12); color: #3182ce; font-weight: 600;"
                        title="{Lang::T('Mikrotik API (Hotspot & PPPoE)')}">
                        MT: <span id="online-users-mt">{$online_users.mikrotik|default:0}</span>
                    </span>
                    {if $online_users.radius_enabled}
                    <span class="badge"
                        style="font-size: 10px; padding: 2px 5px; background: rgba(72, 187, 120, 0.12); color: #38a169; font-weight: 600;"
                        title="{Lang::T('Radius REST (rad_acct via radius.php)')}">
                        REST: <span id="online-users-rest">{$online_users.rest|default:0}</span>
                    </span>
                    <span class="badge"
                        style="font-size: 10px; padding: 2px 5px; background: rgba(159, 122, 234, 0.12); color: #805ad5; font-weight: 600;"
                        title="{Lang::T('Radius FreeRADIUS (radacct)')}">
                        SQL: <span id="online-users-mysql">{$online_users.mysql|default:0}</span>
                    </span>
                    {else}
                    <span style="font-size: 10px; opacity: 0.65;">{Lang::T('API Live')}</span>
                    {/if}
                </div>
            </div>
            <div class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-teal-light isp-flex isp-align-center isp-justify-center">
                <i class="fa fa-signal isp-text-teal"></i>
            </div>
        </div>
    </div>

    <!-- Card 4: Monthly Revenue -->
    <div class="isp-stat-card isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between">
            <div id="monthly-revenue-content">
                <p class="isp-text-sm isp-font-medium isp-text-gray-dark">{Lang::T('Monthly Revenue')}</p>
                <p class="isp-text-2xl isp-font-bold isp-margin-top-1">{Lang::moneyFormat($imonth)}</p>
                <p
                    class="isp-text-sm {if $rev_percentage_change >= 0}isp-text-green{else}isp-text-red{/if} isp-margin-top-1 isp-flex isp-align-center">
                    <i
                        class="fas {if $rev_percentage_change >= 0}fa-arrow-up{else}fa-arrow-down{/if} isp-margin-right-1"></i>
                    {$rev_percentage_change}% {Lang::T('from last month')}
                </p>
            </div>
            <div
                class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-purple-light isp-flex isp-align-center isp-justify-center">
                <i class="fas fa-dollar-sign isp-text-purple"></i>
            </div>
        </div>
    </div>

    <!-- Card 5: Today Sales -->
    <div class="isp-stat-card isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between">
            <div id="today-sales-content">
                <p class="isp-text-sm isp-font-medium isp-text-gray-dark">{Lang::T('Today Sales')}</p>
                <p class="isp-text-2xl isp-font-bold isp-margin-top-1">{Lang::moneyFormat($iday)}</p>
                <p class="isp-text-sm isp-text-blue isp-margin-top-1 isp-flex isp-align-center">
                    <i class="fas fa-receipt isp-margin-right-1"></i>
                    {$iday_count} {Lang::T('Transactions')}
                </p>
            </div>
            <div
                class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-yellow-light isp-flex isp-align-center isp-justify-center">
                <i class="fas fa-coins isp-text-yellow"></i>
            </div>
        </div>
    </div>
</div>
</div>
{foreach $dash_cards as $c}
<div class="dash-block" data-block="{$c.id|escape}" data-title="{$c.title|escape}" style="order:{$dash_order[$c.id]|default:0}">{$c.html}</div>
{/foreach}

{if $can_view_system}
<!-- System Status & Resource Monitoring -->
<div class="dash-block" data-block="system" data-title="{Lang::T('System status')|escape}" style="order:{$dash_order['system']|default:0}">
<div class="isp-card isp-padding-6 isp-rounded-lg isp-margin-bottom-6">
    <div class="sys-header-bar">
        <div class="isp-flex isp-align-center" style="gap: 12px;">
            <div
                style="width: 38px; height: 38px; border-radius: 8px; background: rgba(66, 153, 225, 0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fa fa-server" style="color: #3182ce; font-size: 18px;"></i>
            </div>
            <div>
                <h2 class="isp-text-lg isp-font-semibold" style="margin: 0; line-height: 1.2;">{Lang::T('System Status &
                    Resources')}</h2>
                <span class="isp-text-xs" style="opacity: 0.7; display: block; margin-top: 4px;">
                    {$system_status.os.hostname} • {$system_status.os.name} ({$system_status.os.arch}) • PHP
                    {$system_status.os.php} • {Lang::T('Uptime')}: <span id="sys-uptime-subtitle"
                        style="font-weight: 600;">{$system_status.uptime.formatted}</span>
                </span>
            </div>
        </div>
        <div class="isp-flex isp-align-center" style="gap: 8px;">
            <span class="isp-flex isp-align-center isp-text-xs"
                style="gap: 6px; padding: 4px 10px; border-radius: 20px; background: rgba(72, 187, 120, 0.1); color: #48bb78; font-weight: 500;">
                <span class="isp-status-indicator isp-status-online" style="margin: 0;"></span>
                <span>{Lang::T('Live System')}</span>
            </span>
            <button type="button" id="btn-refresh-sysinfo" class="isp-button isp-button-secondary"
                style="display: inline-flex; align-items: center; gap: 5px; font-size: 11px;">
                <i class="fa fa-refresh" id="sysinfo-refresh-icon"></i>
                <span>{Lang::T('Refresh')}</span>
            </button>
        </div>
    </div>

    {if !empty($system_status.shared_host)}
    <div id="sys-shared-note" class="isp-text-xs" role="note" style="display: flex; gap: 8px; align-items: flex-start; margin: 0 0 14px; padding: 10px 12px; border-radius: 8px; border: 1px solid rgba(66, 153, 225, 0.35); background: rgba(66, 153, 225, 0.1); line-height: 1.5;">
        <i class="fa fa-info-circle" style="color: #3182ce; margin-top: 2px;" aria-hidden="true"></i>
        <span>{if $system_status.ram.source == 'php'}{Lang::T("This looks like shared hosting. Disk and CPU below are for the whole server, which other websites share, so a high value here may not be caused by your system. Memory shows this site's PHP limit, because the server's memory cannot be read here, so a high value there can come from this application.")}{else}{Lang::T('This looks like shared hosting. Disk, memory and CPU below are for the whole server, which other websites share, so a high value here may not be caused by your system.')}{/if}</span>
    </div>
    {/if}

    <!-- 4 System Resource Graphs (Disk, RAM, CPU, Database & Uptime) -->
    <div class="sys-resource-grid">
        <!-- 1. Disk Storage -->
        <div class="sys-stat-card">
            <div>
                <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-2">
                    <span class="isp-text-xs isp-font-semibold"
                        style="text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8;">
                        <i class="fa fa-hard-drive isp-margin-right-1" style="color: #4299e1;"></i>{Lang::T('Disk Storage')}
                    </span>
                    <span id="disk-pct-badge"
                        class="badge {if $system_status.disk.status == 'danger'}badge-danger{elseif $system_status.disk.status == 'warning'}badge-warning{else}badge-success{/if}"
                        style="font-size: 10px;">
                        <span id="disk-pct-val">{$system_status.disk.percent}</span>% {Lang::T('Used')}
                    </span>
                </div>
                <div style="height: 140px; position: relative;">
                    <canvas id="diskChart"></canvas>
                </div>
            </div>
            <div style="margin-top: 10px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 8px; font-size: 11px;">
                <div class="isp-flex isp-justify-between" style="margin-bottom: 2px;">
                    <span style="opacity: 0.7;">{Lang::T('Used')}:</span>
                    <strong id="disk-used-val">{$system_status.disk.used_formatted}</strong>
                </div>
                <div class="isp-flex isp-justify-between" style="margin-bottom: 2px;">
                    <span style="opacity: 0.7;">{Lang::T('Free')}:</span>
                    <span id="disk-free-val"
                        style="color: #48bb78; font-weight: 500;">{$system_status.disk.free_formatted}</span>
                </div>
                <div class="isp-flex isp-justify-between">
                    <span style="opacity: 0.7;">{Lang::T('Total')}:</span>
                    <span id="disk-total-val" style="opacity: 0.85;">{$system_status.disk.total_formatted}</span>
                </div>
            </div>
        </div>

        <!-- 2. RAM (Memory) -->
        <div class="sys-stat-card">
            <div>
                <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-2">
                    <span class="isp-text-xs isp-font-semibold"
                        style="text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8;">
                        <i class="fa fa-microchip isp-margin-right-1" style="color: #9f7aea;"></i>{Lang::T('Memory
                        (RAM)')}
                    </span>
                    <span id="ram-pct-badge"
                        class="badge {if $system_status.ram.status == 'danger'}badge-danger{elseif $system_status.ram.status == 'warning'}badge-warning{else}badge-success{/if}"
                        style="font-size: 10px;">
                        <span id="ram-pct-val">{$system_status.ram.percent}</span>% {Lang::T('Used')}
                    </span>
                </div>
                <div style="height: 140px; position: relative;">
                    <canvas id="ramChart"></canvas>
                </div>
            </div>
            <div style="margin-top: 10px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 8px; font-size: 11px;">
                <div class="isp-flex isp-justify-between" style="margin-bottom: 2px;">
                    <span style="opacity: 0.7;">{Lang::T('Used')}:</span>
                    <strong id="ram-used-val">{$system_status.ram.used_formatted}</strong>
                </div>
                <div class="isp-flex isp-justify-between" style="margin-bottom: 2px;">
                    <span style="opacity: 0.7;">{Lang::T('Free')}:</span>
                    <span id="ram-free-val"
                        style="color: #48bb78; font-weight: 500;">{$system_status.ram.free_formatted}</span>
                </div>
                <div class="isp-flex isp-justify-between">
                    <span style="opacity: 0.7;">PHP {Lang::T('Limit')}:</span>
                    <span style="opacity: 0.85;">{$system_status.ram.php_memory_used} /
                        {$system_status.ram.php_memory_limit}</span>
                </div>
            </div>
        </div>

        <!-- 3. CPU Load & Utilization -->
        <div class="sys-stat-card">
            <div>
                <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-2">
                    <span class="isp-text-xs isp-font-semibold"
                        style="text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8;">
                        <i class="fa fa-tachometer isp-margin-right-1" style="color: #ed8936;"></i>{Lang::T('CPU
                        Utilization')}
                    </span>
                    <span id="cpu-cores-badge"
                        class="badge {if $system_status.cpu.status == 'danger'}badge-danger{elseif $system_status.cpu.status == 'warning'}badge-warning{else}badge-success{/if}"
                        style="font-size: 10px;">
                        <span id="cpu-cores-val">{$system_status.cpu.cores}</span> {Lang::T('Cores')}
                    </span>
                </div>
                <div style="height: 140px; position: relative;">
                    <canvas id="cpuChart"></canvas>
                </div>
            </div>
            <div style="margin-top: 10px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 8px; font-size: 11px;">
                <div class="isp-flex isp-justify-between" style="margin-bottom: 2px;">
                    <span style="opacity: 0.7;">{Lang::T('Load (1/5/15m)')}:</span>
                    <strong id="cpu-load-val">{$system_status.cpu.load_string}</strong>
                </div>
                <div class="isp-flex isp-justify-between" style="margin-bottom: 2px;">
                    <span style="opacity: 0.7;">{Lang::T('Architecture')}:</span>
                    <span>{$system_status.os.arch}</span>
                </div>
                <div class="isp-flex isp-justify-between"
                    style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <span style="opacity: 0.7;">{Lang::T('Model')}:</span>
                    <span style="opacity: 0.85;" title="{$system_status.cpu.model}">{$system_status.cpu.model}</span>
                </div>
            </div>
        </div>

        <!-- 4. Database & System Uptime -->
        <div class="sys-stat-card">
            <div>
                <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-2">
                    <span class="isp-text-xs isp-font-semibold"
                        style="text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8;">
                        <i class="fa fa-database isp-margin-right-1" style="color: #38b2ac;"></i>{Lang::T('Database &
                        Uptime')}
                    </span>
                    <span id="db-status-badge"
                        class="badge {if $system_status.database.status == 'online'}badge-success{else}badge-danger{/if}"
                        style="font-size: 10px;">
                        <i class="fa fa-circle" style="font-size: 8px; margin-right: 3px;"></i><span
                            id="db-status-val">{$system_status.database.status|escape}</span>
                    </span>
                </div>

                <div style="padding: 10px 0;">
                    <div style="margin-bottom: 10px;">
                        <span class="isp-text-xs" style="opacity: 0.65; display: block;">{Lang::T('System
                            Uptime')}</span>
                        <strong class="isp-text-xl" style="color: #3182ce; font-family: monospace;"
                            id="sys-uptime-val">{$system_status.uptime.formatted}</strong>
                        <span class="isp-text-xs"
                            style="opacity: 0.6; display: block; font-size: 10px; margin-top: 1px;">Boot:
                            {$system_status.uptime.boot_time}</span>
                    </div>

                    <div style="background: rgba(0,0,0,0.03); border-radius: 6px; padding: 8px 10px; font-size: 11px;">
                        <div class="isp-flex isp-justify-between" style="margin-bottom: 3px;">
                            <span style="opacity: 0.7;">{Lang::T('Engine')}:</span>
                            <strong>{$system_status.database.version}</strong>
                        </div>
                        <div class="isp-flex isp-justify-between" style="margin-bottom: 3px;">
                            <span style="opacity: 0.7;">{Lang::T('Latency')}:</span>
                            <span style="color: #48bb78; font-weight: 600;"
                                id="db-latency-val">{$system_status.database.latency_ms} ms</span>
                        </div>
                        <div class="isp-flex isp-justify-between">
                            <span style="opacity: 0.7;">{Lang::T('Size & Tables')}:</span>
                            <span>{$system_status.database.size_formatted} ({$system_status.database.tables_count}
                                {Lang::T('tables')})</span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                style="border-top: 1px solid rgba(0,0,0,0.05); padding-top: 6px; font-size: 10px; opacity: 0.65; text-align: right;">
                <span>DB: <code>{$system_status.database.name}</code></span>
            </div>
        </div>
    </div>

    <!-- Services Status Matrix -->
    <div>
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-3">
            <h3 class="isp-text-sm isp-font-semibold"
                style="margin: 0; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.75;">
                <i class="fa fa-cogs isp-margin-right-1"></i>{Lang::T('Services Status Matrix')}
            </h3>
            <span class="isp-text-xs" style="opacity: 0.6;">{count($system_status.services)} {Lang::T('Core
                Services')}</span>
        </div>

        <div class="sys-services-grid" id="services-status-matrix">
            {foreach $system_status.services as $svc}
            <div class="sys-service-card">
                <div style="display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1;">
                    <div
                        style="width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; {if $svc.badge_class == 'success'}background: rgba(72, 187, 120, 0.12); color: #48bb78;{elseif $svc.badge_class == 'warning'}background: rgba(237, 137, 54, 0.12); color: #ed8936;{elseif $svc.badge_class == 'danger'}background: rgba(245, 101, 101, 0.12); color: #f56565;{else}background: rgba(160, 174, 192, 0.12); color: #718096;{/if}">
                        <i class="{$svc.icon}" style="font-size: 14px;"></i>
                    </div>
                    <div style="min-width: 0; flex: 1;">
                        <div
                            style="font-size: 12px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {$svc.name}</div>
                        <div style="font-size: 10px; opacity: 0.65; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                            title="{$svc.info}">{$svc.info}</div>
                    </div>
                </div>
                <span
                    class="badge {if $svc.badge_class == 'success'}badge-success{elseif $svc.badge_class == 'warning'}badge-warning{elseif $svc.badge_class == 'danger'}badge-danger{else}badge-default badge-secondary{/if}">
                    {$svc.status_label}
                </span>
            </div>
            {/foreach}
            <div class="sys-service-card" id="sys-time-card">
                <div style="display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1;">
                    <div
                        style="width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: rgba(66, 153, 225, 0.12); color: #4299e1;">
                        <i class="fa fa-clock" style="font-size: 14px;"></i>
                    </div>
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 12px; font-weight: 600;">{Lang::T('Server Clocks')}</div>
                        <div
                            style="font-size: 10.5px; line-height: 1.55; opacity: 0.8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {Lang::T('Server Time')}: <strong id="srv-time-val"
                                data-ts="{$system_status.time.server}">{$system_status.time.server|default:'-'}</strong>
                            <span id="srv-time-tz" style="opacity: 0.65;">{if
                                $system_status.time.server_tz}({$system_status.time.server_tz}){/if}</span><br>
                            {Lang::T('PHP Time')}: <strong id="php-time-val"
                                data-ts="{$system_status.time.php}">{$system_status.time.php}</strong> <span
                                id="php-time-tz" style="opacity: 0.65;">({$system_status.time.php_tz})</span><br>
                            {Lang::T('Database Time')}: <strong id="db-time-val"
                                data-ts="{$system_status.time.database}">{$system_status.time.database|default:'-'}</strong>
                            <span id="db-time-tz" style="opacity: 0.65;">{if
                                $system_status.time.database_tz}({$system_status.time.database_tz}){/if}</span>
                        </div>
                    </div>
                </div>
                {assign var=timeBad value=($system_status.time.skew >= 60 || $system_status.time.tz_mismatch)}
                <span id="time-drift-badge" class="badge {if $timeBad}badge-warning{else}badge-success{/if}"
                    data-base="badge" data-ok="badge-success" data-warn="badge-warning" data-t-ok="{Lang::T('In sync')}"
                    data-t-tz="{Lang::T('Timezone differs')}" data-t-skew="{Lang::T('Clock skew')}">{if
                    $system_status.time.skew >= 60}{Lang::T('Clock skew')} {if $system_status.time.skew >=
                    3600}{($system_status.time.skew / 3600)|string_format:"%.1f"} h{else}{($system_status.time.skew /
                    60)|round} min{/if}{elseif $system_status.time.tz_mismatch}{Lang::T('Timezone
                    differs')}{else}{Lang::T('In sync')}{/if}</span>
            </div>
        </div>
    </div>
</div>
</div>

{/if}

<!-- Dedicated Live Online Users Multi-Gateway Card (Bigger Card with Separate Columns) -->
<div class="dash-block" data-block="online" data-title="{Lang::T('Online users')|escape}" style="order:{$dash_order['online']|default:0}">
<div class="isp-card isp-padding-6 isp-rounded-lg isp-margin-bottom-6" id="card-online-users-multitech">
    <!-- Header -->
    <div
        style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid rgba(0,0,0,0.06);">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 42px; height: 42px; border-radius: 10px; background: rgba(56, 178, 172, 0.12); display: flex; align-items: center; justify-content: center; color: #319795; flex-shrink: 0;">
                <i class="fa fa-wifi" style="font-size: 20px;"></i>
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h2 class="isp-text-lg isp-font-semibold" style="margin: 0;">{Lang::T('Live Online Users &
                        Accounting')}</h2>
                    <span class="badge badge-success"
                        style="font-size: 11px; padding: 3px 8px; display: inline-flex; align-items: center; gap: 5px;">
                        <span class="live-dot-pulse"></span>
                        <span>{Lang::T('Real-Time Live')}</span>
                    </span>
                </div>
                <p class="isp-text-xs" style="margin: 2px 0 0; opacity: 0.7;">
                    {Lang::T('Monitored across 3 technologies: Mikrotik RouterOS API, Radius REST API (rad_acct), and
                    FreeRADIUS MySQL (radacct)')}
                </p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="text-align: right;">
                <span class="isp-text-xs" style="opacity: 0.65; display: block;">{Lang::T('Total Active Online')}</span>
                <span class="isp-text-2xl isp-font-bold" style="color: #319795;"
                    id="big-card-total-online">{$online_users.total|default:0}</span>
            </div>
            <a href="{Text::url('customers/list')}" class="isp-button isp-button-secondary"
                style="font-size: 11px; padding: 6px 12px; border-radius: 6px; text-decoration: none;">
                <i class="fa fa-users" style="margin-right: 4px;"></i>{Lang::T('View Customers')}
            </a>
        </div>
    </div>

    <!-- 3 Distinct Columns -->
    <div class="online-tech-grid">
        <!-- Column 1: Mikrotik API -->
        <div class="online-tech-col" style="border-top: 3px solid #4299e1;">
            <div>
                <div
                    style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div>
                        <span class="badge"
                            style="font-size: 10px; padding: 2px 6px; background: rgba(66, 153, 225, 0.15); color: #3182ce; font-weight: 600;">
                            <i class="fa fa-microchip" style="margin-right: 4px;"></i>RouterOS API
                        </span>
                        <h3 style="font-size: 15px; font-weight: 700; margin: 6px 0 2px;">{Lang::T('Mikrotik API')}</h3>
                        <p style="font-size: 11px; opacity: 0.7; margin: 0;">Hotspot & PPPoE Active Sessions</p>
                    </div>
                    <div style="text-align: right;">
                        <span class="isp-text-2xl isp-font-bold" style="color: #3182ce;"
                            id="big-col-mt-total">{$online_users.mikrotik|default:0}</span>
                        <span style="font-size: 10px; display: block; opacity: 0.6;">{Lang::T('Online')}</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">
                    <div class="online-tech-stat-box">
                        <span style="font-size: 10px; opacity: 0.65; display: block;">{Lang::T('Hotspot Active')}</span>
                        <strong style="font-size: 14px; color: #3182ce;"
                            id="big-col-mt-hotspot">{$online_users.mikrotik_hotspot|default:0}</strong>
                    </div>
                    <div class="online-tech-stat-box">
                        <span style="font-size: 10px; opacity: 0.65; display: block;">{Lang::T('PPPoE Active')}</span>
                        <strong style="font-size: 14px; color: #3182ce;"
                            id="big-col-mt-pppoe">{$online_users.mikrotik_pppoe|default:0}</strong>
                    </div>
                </div>

                <div
                    style="font-size: 11px; opacity: 0.75; padding: 8px 10px; background: rgba(66, 153, 225, 0.04); border-radius: 6px; margin-bottom: 10px;">
                    <div class="isp-flex isp-justify-between" style="margin-bottom: 3px;">
                        <span>{Lang::T('Routers Checked')}:</span>
                        <strong>{$online_users.routers_checked} {Lang::T('Active')}</strong>
                    </div>
                    <div class="isp-flex isp-justify-between">
                        <span>{Lang::T('Protocol')}:</span>
                        <span>RouterOS API / Port 8728</span>
                    </div>
                </div>
            </div>

            <div
                style="border-top: 1px solid rgba(0,0,0,0.06); padding-top: 10px; margin-top: auto; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 10px; opacity: 0.6;">/ip/hotspot/active & /ppp/active</span>
                <a href="{Text::url('routers')}"
                    style="font-size: 11px; color: #4299e1; text-decoration: none; font-weight: 500;">
                    {Lang::T('Manage Routers')}
                </a>
            </div>
        </div>

        <!-- Column 2: Radius REST API (rad_acct via radius.php) -->
        <div class="online-tech-col" style="border-top: 3px solid #38a169;">
            <div>
                <div
                    style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div>
                        <span class="badge"
                            style="font-size: 10px; padding: 2px 6px; background: rgba(72, 187, 120, 0.15); color: #38a169; font-weight: 600;">
                            <i class="fa fa-exchange" style="margin-right: 4px;"></i>REST / HTTP
                        </span>
                        <h3 style="font-size: 15px; font-weight: 700; margin: 6px 0 2px;">{Lang::T('Radius REST')}</h3>
                        <p style="font-size: 11px; opacity: 0.7; margin: 0;">Auth & Acct via <code>radius.php</code></p>
                    </div>
                    <div style="text-align: right;">
                        <span class="isp-text-2xl isp-font-bold" style="color: #38a169;"
                            id="big-col-rest-total">{$online_users.rest|default:0}</span>
                        <span style="font-size: 10px; display: block; opacity: 0.6;">{Lang::T('Sessions')}</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">
                    <div class="online-tech-stat-box">
                        <span style="font-size: 10px; opacity: 0.65; display: block;">{Lang::T('DB Table')}</span>
                        <strong style="font-size: 13px; font-family: monospace; color: #38a169;">rad_acct</strong>
                    </div>
                    <div class="online-tech-stat-box">
                        <span style="font-size: 10px; opacity: 0.65; display: block;">{Lang::T('Unique Users')}</span>
                        <strong style="font-size: 14px; color: #38a169;"
                            id="big-col-rest-users">{$online_users.rest_users|default:0}</strong>
                    </div>
                </div>

                <!-- Sessions List / Placeholder -->
                <div
                    style="max-height: 120px; overflow-y: auto; background: rgba(72, 187, 120, 0.04); border-radius: 6px; padding: 6px; font-size: 11px; margin-bottom: 10px;">
                    {if !empty($online_users.rest_sessions)}
                    {foreach $online_users.rest_sessions as $rs}
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding: 4px 6px; background: rgba(255,255,255,0.7); border-radius: 4px; margin-bottom: 3px;">
                        <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 140px;">
                            <strong>{$rs.username|escape}</strong> {if !empty($rs.framedipaddress)}<span
                                style="opacity: 0.6; font-size: 10px;">({$rs.framedipaddress|escape})</span>{/if}
                        </span>
                        <span style="font-size: 10px; opacity: 0.6;">{$rs.acctstarttime|escape}</span>
                    </div>
                    {/foreach}
                    {else}
                    <div style="text-align: center; padding: 12px 6px; opacity: 0.6; font-size: 11px;">
                        {if $online_users.rest > 0}
                        {$online_users.rest} {Lang::T('active sessions recorded in rad_acct')}
                        {else}
                        {Lang::T('No active accounting sessions in rad_acct')}
                        {/if}
                    </div>
                    {/if}
                </div>
            </div>

            <div
                style="border-top: 1px solid rgba(0,0,0,0.06); padding-top: 10px; margin-top: auto; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 10px; opacity: 0.6;">radius.php?action=accounting</span>
                <span class="badge badge-success" style="font-size: 10px; padding: 2px 6px;">Active</span>
            </div>
        </div>

        <!-- Column 3: Radius MySQL FreeRADIUS (radacct) -->
        <div class="online-tech-col" style="border-top: 3px solid #805ad5;">
            <div>
                <div
                    style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div>
                        <span class="badge"
                            style="font-size: 10px; padding: 2px 6px; background: rgba(159, 122, 234, 0.15); color: #805ad5; font-weight: 600;">
                            <i class="fa fa-database" style="margin-right: 4px;"></i>FreeRADIUS AAA
                        </span>
                        <h3 style="font-size: 15px; font-weight: 700; margin: 6px 0 2px;">{Lang::T('Radius MySQL')}</h3>
                        <p style="font-size: 11px; opacity: 0.7; margin: 0;">Accounting in <code>radacct</code> Table
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <span class="isp-text-2xl isp-font-bold" style="color: #805ad5;"
                            id="big-col-mysql-total">{$online_users.mysql|default:0}</span>
                        <span style="font-size: 10px; display: block; opacity: 0.6;">{Lang::T('Sessions')}</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">
                    <div class="online-tech-stat-box">
                        <span style="font-size: 10px; opacity: 0.65; display: block;">{Lang::T('DB Table')}</span>
                        <strong style="font-size: 13px; font-family: monospace; color: #805ad5;">radacct</strong>
                    </div>
                    <div class="online-tech-stat-box">
                        <span style="font-size: 10px; opacity: 0.65; display: block;">{Lang::T('Unique Users')}</span>
                        <strong style="font-size: 14px; color: #805ad5;"
                            id="big-col-mysql-users">{$online_users.mysql_users|default:0}</strong>
                    </div>
                </div>

                <!-- Sessions List / Placeholder -->
                <div
                    style="max-height: 120px; overflow-y: auto; background: rgba(159, 122, 234, 0.04); border-radius: 6px; padding: 6px; font-size: 11px; margin-bottom: 10px;">
                    {if !empty($online_users.mysql_sessions)}
                    {foreach $online_users.mysql_sessions as $ms}
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding: 4px 6px; background: rgba(255,255,255,0.7); border-radius: 4px; margin-bottom: 3px;">
                        <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 140px;">
                            <strong>{$ms.username|escape}</strong> {if !empty($ms.framedipaddress)}<span
                                style="opacity: 0.6; font-size: 10px;">({$ms.framedipaddress|escape})</span>{/if}
                        </span>
                        <span style="font-size: 10px; opacity: 0.6;">{$ms.acctstarttime|escape}</span>
                    </div>
                    {/foreach}
                    {else}
                    <div style="text-align: center; padding: 12px 6px; opacity: 0.6; font-size: 11px;">
                        {if $online_users.mysql > 0}
                        {$online_users.mysql} {Lang::T('active sessions recorded in radacct')}
                        {else}
                        {Lang::T('No active accounting sessions in radacct (acctstoptime IS NULL)')}
                        {/if}
                    </div>
                    {/if}
                </div>
            </div>

            <div
                style="border-top: 1px solid rgba(0,0,0,0.06); padding-top: 10px; margin-top: auto; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 10px; opacity: 0.6;">acctstoptime IS NULL</span>
                {if $online_users.radius_enabled}
                <a href="{Text::url('radius/nas-list')}"
                    style="font-size: 11px; color: #805ad5; text-decoration: none; font-weight: 500;">
                    {Lang::T('Radius NAS')}
                </a>
                {else}
                <span style="font-size: 10px; opacity: 0.5;">{Lang::T('Radius Disabled')}</span>
                {/if}
            </div>
        </div>
    </div>
</div>
</div>

<!-- Main Charts Row -->
<div class="dash-block" data-block="charts1" data-title="{Lang::T('Sales and customer growth')|escape}" style="order:{$dash_order['charts1']|default:0}">
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6 isp-margin-bottom-6">
    <!-- Sales Report Chart -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="sales-report-title" class="isp-text-lg isp-font-semibold">{Lang::T('Sales Report')}</h2>
            <div class="isp-flex isp-space-x-2" id="sales-timeframe-btns">
                <button type="button" class="isp-button isp-button-day active"
                    data-timeframe="day">{Lang::T('Day')}</button>
                <button type="button" class="isp-button isp-button-secondary"
                    data-timeframe="week">{Lang::T('Week')}</button>
                <button type="button" class="isp-button isp-button-secondary"
                    data-timeframe="month">{Lang::T('Month')}</button>
                <button type="button" class="isp-button isp-button-secondary"
                    data-timeframe="year">{Lang::T('Year')}</button>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Customer Growth Chart -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="customer-growth-title" class="isp-text-lg isp-font-semibold">{Lang::T('Customer Growth')}</h2>
            <div class="isp-flex isp-space-x-2" id="customer-filter-btns">
                <button type="button" class="isp-button isp-button-day active"
                    data-filter="all">{Lang::T('All')}</button>
                <button type="button" class="isp-button isp-button-secondary"
                    data-filter="new">{Lang::T('New')}</button>
                <button type="button" class="isp-button isp-button-secondary"
                    data-filter="churned">{Lang::T('Churned')}</button>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="customersChart"></canvas>
        </div>
    </div>
</div>
</div>

<!-- Second Charts Row -->
<div class="dash-block" data-block="charts2" data-title="{Lang::T('Network and transactions')|escape}" style="order:{$dash_order['charts2']|default:0}">
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6 isp-margin-bottom-6">
    <!-- Network Status Chart (Routers + Radius Server & NAS) -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <div class="isp-flex isp-align-center" style="gap: 8px;">
                <h2 id="network-status-title" class="isp-text-lg isp-font-semibold">{Lang::T('Network Status')}</h2>
                {if $radius_enabled}
                <span class="badge"
                    style="font-size: 11px; padding: 2px 8px; border-radius: 9999px; font-weight: 500; {if $radius_online}background: rgba(72, 187, 120, 0.15); color: #48bb78;{else}background: rgba(245, 101, 101, 0.15); color: #f56565;{/if}"
                    title="FreeRADIUS AAA Engine">
                    <i class="fa fa-server" style="margin-right: 4px;"></i>RADIUS {if
                    $radius_online}{Lang::T('Online')}{else}{Lang::T('Offline')}{/if}
                </span>
                {/if}
            </div>
            <div class="isp-flex isp-align-center" style="gap: 6px;">
                <span
                    class="isp-status-indicator {if $offline_network_nodes == 0}isp-status-online{else}isp-status-warning{/if}"></span>
                <span id="network-status-uptime" class="isp-text-xs">{$network_uptime}% {Lang::T('uptime')}</span>
            </div>
        </div>
        <div class="isp-chart-container" style="position: relative;">
            <canvas id="networkChart"></canvas>
        </div>

        <div style="margin-top: 14px; border-top: 1px solid var(--border-color, rgba(0,0,0,0.06)); padding-top: 12px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                <div class="isp-flex isp-space-x-1" id="network-tab-btns">
                    <button type="button" class="net-tab-btn active" data-tab="routers">
                        <i class="fa fa-sitemap" style="margin-right: 4px;"></i>{Lang::T('Routers')} ({$routers_total})
                    </button>
                    <button type="button" class="net-tab-btn" data-tab="online">
                        <i class="fa fa-users" style="margin-right: 4px;"></i>{Lang::T('Online Users')} (<span
                            id="net-tab-online-count">{$online_users.total|default:0}</span>)
                    </button>
                    {if $radius_enabled}
                    <button type="button" class="net-tab-btn" data-tab="radius">
                        <i class="fa fa-server" style="margin-right: 4px;"></i>{Lang::T('Radius')} ({if
                        $radius_nas_count > 0}{$radius_nas_count} NAS{else}Server{/if})
                    </button>
                    {/if}
                    <button type="button" class="net-tab-btn" data-tab="all">
                        {Lang::T('All Nodes')} ({$total_network_nodes})
                    </button>
                </div>
                <div id="network-action-link">
                    <a href="{Text::url('routers')}" id="net-manage-link" data-routers-url="{Text::url('routers')}"
                        data-radius-url="{Text::url('radius/nas-list')}" data-online-url="{Text::url('customers/list')}"
                        style="font-size: 11px; color: #4299e1; text-decoration: none; font-weight: 500;">{Lang::T('Manage
                        Routers')}</a>
                </div>
            </div>

            <!-- Tab 1: Routers List -->
            <div id="net-tab-routers" class="net-tab-content">
                {if !empty($routers_list)}
                <div style="display: flex; flex-direction: column; gap: 6px; max-height: 120px; overflow-y: auto;">
                    {foreach $routers_list as $r}
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; border-radius: 6px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.04);">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span
                                class="isp-status-indicator {if strcasecmp($r['status'], 'online') == 0}isp-status-online{else}isp-status-warning{/if}"></span>
                            <div>
                                <strong style="font-size: 12px;">{$r['name']}</strong>
                                {if !empty($r['ip_address'])}
                                <span style="font-size: 11px; opacity: 0.6; margin-left: 6px;">{$r['ip_address']}</span>
                                {/if}
                            </div>
                        </div>
                        <span
                            class="badge {if strcasecmp($r['status'], 'online') == 0}badge-success{else}badge-danger{/if}"
                            style="font-size: 10px; padding: 2px 8px; font-weight: 500;">
                            {$r['status']}
                        </span>
                    </div>
                    {/foreach}
                </div>
                {else}
                <div style="padding: 10px; font-size: 12px; opacity: 0.6; text-align: center;">
                    {Lang::T('No Routers Configured')}
                </div>
                {/if}
            </div>

            <!-- Tab 2: Radius Overview & NAS Clients -->
            {if $radius_enabled}
            <div id="net-tab-radius" class="net-tab-content" style="display: none;">
                <!-- Radius Server Status Row -->
                <div
                    style="display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; border-radius: 6px; background: rgba(66, 153, 225, 0.04); border: 1px solid rgba(66, 153, 225, 0.12); margin-bottom: 6px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span
                            class="isp-status-indicator {if $radius_online}isp-status-online{else}isp-status-warning{/if}"></span>
                        <div>
                            <strong style="font-size: 12px;">FreeRADIUS AAA Engine</strong>
                            <span style="font-size: 11px; opacity: 0.6; margin-left: 6px;">{$radius_host}</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        {if $radius_active_sessions > 0}
                        <span class="badge"
                            style="font-size: 10px; padding: 2px 6px; background: rgba(66, 153, 225, 0.15); color: #3182ce;">
                            <i class="fa fa-users"></i> {$radius_active_sessions} {Lang::T('Sessions')}
                        </span>
                        {/if}
                        <span class="badge {if $radius_online}badge-success{else}badge-danger{/if}"
                            style="font-size: 10px; padding: 2px 8px; font-weight: 500;">
                            {if $radius_online}Online{else}Offline{/if}
                        </span>
                    </div>
                </div>

                <!-- Radius NAS Clients List -->
                {if !empty($radius_nas_list)}
                <div style="display: flex; flex-direction: column; gap: 6px; max-height: 100px; overflow-y: auto;">
                    {foreach $radius_nas_list as $nas}
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding: 5px 10px; border-radius: 6px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.04);">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span
                                class="isp-status-indicator {if strcasecmp($nas['status'], 'online') == 0}isp-status-online{else}isp-status-warning{/if}"></span>
                            <div>
                                <strong style="font-size: 12px;">{$nas['shortname']}</strong>
                                <span style="font-size: 11px; opacity: 0.6; margin-left: 6px;">{$nas['nasname']}</span>
                                {if !empty($nas['routers'])}
                                <span
                                    style="font-size: 10px; opacity: 0.5; margin-left: 4px;">({$nas['routers']})</span>
                                {/if}
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span class="badge"
                                style="font-size: 9px; padding: 1px 5px; opacity: 0.7; background: rgba(0,0,0,0.05);">{$nas['type']}</span>
                            <span
                                class="badge {if strcasecmp($nas['status'], 'online') == 0}badge-success{else}badge-danger{/if}"
                                style="font-size: 10px; padding: 2px 8px; font-weight: 500;">
                                {$nas['status']}
                            </span>
                        </div>
                    </div>
                    {/foreach}
                </div>
                {else}
                <div
                    style="padding: 8px 10px; font-size: 11px; opacity: 0.7; text-align: center; background: rgba(0,0,0,0.02); border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                    <span>{Lang::T('No Radius NAS configured yet.')}</span>
                    <a href="{Text::url('radius/nas-add')}" class="btn btn-xs btn-primary"
                        style="font-size: 10px; padding: 2px 8px;">+ {Lang::T('Add NAS')}</a>
                </div>
                {/if}
            </div>
            {/if}

            <!-- Tab 3: All Nodes -->
            <div id="net-tab-all" class="net-tab-content" style="display: none;">
                <div style="display: flex; flex-direction: column; gap: 6px; max-height: 120px; overflow-y: auto;">
                    {if $radius_enabled}
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding: 5px 10px; border-radius: 6px; background: rgba(66, 153, 225, 0.04); border: 1px solid rgba(66, 153, 225, 0.12);">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span
                                class="isp-status-indicator {if $radius_online}isp-status-online{else}isp-status-warning{/if}"></span>
                            <div>
                                <strong style="font-size: 12px;">Radius Server</strong>
                                <span style="font-size: 11px; opacity: 0.6; margin-left: 6px;">{$radius_host}</span>
                            </div>
                        </div>
                        <span class="badge {if $radius_online}badge-success{else}badge-danger{/if}"
                            style="font-size: 10px; padding: 2px 8px; font-weight: 500;">
                            {if $radius_online}Online{else}Offline{/if}
                        </span>
                    </div>
                    {/if}
                    {foreach $routers_list as $r}
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding: 5px 10px; border-radius: 6px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.04);">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span
                                class="isp-status-indicator {if strcasecmp($r['status'], 'online') == 0}isp-status-online{else}isp-status-warning{/if}"></span>
                            <div>
                                <strong style="font-size: 12px;">[Router] {$r['name']}</strong>
                                {if !empty($r['ip_address'])}
                                <span style="font-size: 11px; opacity: 0.6; margin-left: 6px;">{$r['ip_address']}</span>
                                {/if}
                            </div>
                        </div>
                        <span
                            class="badge {if strcasecmp($r['status'], 'online') == 0}badge-success{else}badge-danger{/if}"
                            style="font-size: 10px; padding: 2px 8px; font-weight: 500;">
                            {$r['status']}
                        </span>
                    </div>
                    {/foreach}
                    {if !empty($radius_nas_list)}
                    {foreach $radius_nas_list as $nas}
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding: 5px 10px; border-radius: 6px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.04);">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span
                                class="isp-status-indicator {if strcasecmp($nas['status'], 'online') == 0}isp-status-online{else}isp-status-warning{/if}"></span>
                            <div>
                                <strong style="font-size: 12px;">[NAS] {$nas['shortname']}</strong>
                                <span style="font-size: 11px; opacity: 0.6; margin-left: 6px;">{$nas['nasname']}</span>
                            </div>
                        </div>
                        <span
                            class="badge {if strcasecmp($nas['status'], 'online') == 0}badge-success{else}badge-danger{/if}"
                            style="font-size: 10px; padding: 2px 8px; font-weight: 500;">
                            {$nas['status']}
                        </span>
                    </div>
                    {/foreach}
                    {/if}
                </div>
            </div>

            <!-- Tab 4: Online Users Breakdown & Real-Time Sessions -->
            <div id="net-tab-online" class="net-tab-content" style="display: none;">
                <!-- 3 Technologies Overview Mini Grid -->
                <div
                    style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin-bottom: 10px;">
                    <!-- Tech 1: Mikrotik RouterOS API -->
                    <div
                        style="padding: 8px 10px; border-radius: 6px; background: rgba(66, 153, 225, 0.05); border: 1px solid rgba(66, 153, 225, 0.15);">
                        <div class="isp-flex isp-justify-between isp-align-center" style="margin-bottom: 4px;">
                            <strong style="font-size: 11px; color: #3182ce;"><i class="fa fa-microchip"
                                    style="margin-right: 4px;"></i>Mikrotik API</strong>
                            <span class="badge badge-primary" style="font-size: 10px;"
                                id="tab-mt-total">{$online_users.mikrotik|default:0}</span>
                        </div>
                        <div style="font-size: 10px; opacity: 0.75; display: flex; justify-content: space-between;">
                            <span>Hotspot: <b id="tab-mt-hotspot">{$online_users.mikrotik_hotspot|default:0}</b></span>
                            <span>PPPoE: <b id="tab-mt-pppoe">{$online_users.mikrotik_pppoe|default:0}</b></span>
                        </div>
                    </div>

                    <!-- Tech 2: Radius REST API (rad_acct table via radius.php) -->
                    <div
                        style="padding: 8px 10px; border-radius: 6px; background: rgba(72, 187, 120, 0.05); border: 1px solid rgba(72, 187, 120, 0.15);">
                        <div class="isp-flex isp-justify-between isp-align-center" style="margin-bottom: 4px;">
                            <strong style="font-size: 11px; color: #38a169;"><i class="fa fa-exchange"
                                    style="margin-right: 4px;"></i>Radius REST</strong>
                            <span class="badge badge-success" style="font-size: 10px;"
                                id="tab-rest-total">{$online_users.rest|default:0}</span>
                        </div>
                        <div style="font-size: 10px; opacity: 0.75; display: flex; justify-content: space-between;">
                            <span>Table: <code>rad_acct</code></span>
                            <span>Unique: <b id="tab-rest-users">{$online_users.rest_users|default:0}</b></span>
                        </div>
                    </div>

                    <!-- Tech 3: Radius FreeRADIUS MySQL (radacct table) -->
                    <div
                        style="padding: 8px 10px; border-radius: 6px; background: rgba(159, 122, 234, 0.05); border: 1px solid rgba(159, 122, 234, 0.15);">
                        <div class="isp-flex isp-justify-between isp-align-center" style="margin-bottom: 4px;">
                            <strong style="font-size: 11px; color: #805ad5;"><i class="fa fa-database"
                                    style="margin-right: 4px;"></i>Radius MySQL</strong>
                            <span class="badge"
                                style="font-size: 10px; background: rgba(159, 122, 234, 0.2); color: #805ad5;"
                                id="tab-sql-total">{$online_users.mysql|default:0}</span>
                        </div>
                        <div style="font-size: 10px; opacity: 0.75; display: flex; justify-content: space-between;">
                            <span>Table: <code>radacct</code></span>
                            <span>Unique: <b id="tab-sql-users">{$online_users.mysql_users|default:0}</b></span>
                        </div>
                    </div>
                </div>

                <!-- Sessions List / Details -->
                <div style="max-height: 110px; overflow-y: auto;">
                    {if !empty($online_users.rest_sessions)}
                    <div style="font-size: 10px; font-weight: 600; opacity: 0.7; margin: 4px 0 2px;"><i
                            class="fa fa-exchange" style="color: #38a169; margin-right: 4px;"></i>Radius REST (rad_acct)
                        Sessions:</div>
                    {foreach $online_users.rest_sessions as $rs}
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding: 4px 8px; border-radius: 4px; background: rgba(0,0,0,0.02); margin-bottom: 3px; font-size: 11px;">
                        <span><i class="fa fa-user"
                                style="opacity: 0.6; margin-right: 4px;"></i><strong>{$rs.username|escape}</strong> {if
                            !empty($rs.framedipaddress)}({$rs.framedipaddress|escape}){/if}</span>
                        <span style="opacity: 0.6; font-size: 10px;">{$rs.acctstarttime|escape}</span>
                    </div>
                    {/foreach}
                    {/if}
                    {if !empty($online_users.mysql_sessions)}
                    <div style="font-size: 10px; font-weight: 600; opacity: 0.7; margin: 4px 0 2px;"><i
                            class="fa fa-database" style="color: #805ad5; margin-right: 4px;"></i>Radius MySQL (radacct)
                        Sessions:</div>
                    {foreach $online_users.mysql_sessions as $ms}
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding: 4px 8px; border-radius: 4px; background: rgba(0,0,0,0.02); margin-bottom: 3px; font-size: 11px;">
                        <span><i class="fa fa-user"
                                style="opacity: 0.6; margin-right: 4px;"></i><strong>{$ms.username|escape}</strong> {if
                            !empty($ms.framedipaddress)}({$ms.framedipaddress|escape}){/if}</span>
                        <span style="opacity: 0.6; font-size: 10px;">{$ms.acctstarttime|escape}</span>
                    </div>
                    {/foreach}
                    {/if}
                    {if empty($online_users.rest_sessions) && empty($online_users.mysql_sessions) && $online_users.total
                    == 0}
                    <div
                        style="padding: 10px; font-size: 11px; opacity: 0.65; text-align: center; background: rgba(0,0,0,0.02); border-radius: 6px;">
                        <i class="fa fa-info-circle" style="margin-right: 4px;"></i>{Lang::T('No active sessions
                        currently found in rad_acct (REST), radacct (MySQL), or connected Mikrotik routers.')}
                    </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions by Service (replaces dummy Support Tickets) -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="services-trx-title" class="isp-text-lg isp-font-semibold">{Lang::T('Transactions by Service')}</h2>
            <div class="isp-flex isp-space-x-2" id="trx-metric-btns">
                <button type="button" class="isp-button isp-button-day active"
                    data-metric="totals">{Lang::T('Revenue')}</button>
                <button type="button" class="isp-button isp-button-secondary"
                    data-metric="counts">{Lang::T('Count')}</button>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="ticketsChart"></canvas>
        </div>
    </div>
</div>
</div>

<!-- Third Row with smaller charts -->
<div class="dash-block" data-block="charts3" data-title="{Lang::T('Router, day and plan activity')|escape}" style="order:{$dash_order['charts3']|default:0}">
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-md-2 isp-grid-cols-lg-3 isp-gap-6 isp-margin-bottom-6">
    <!-- Router Activity / Usage -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="hotspot-usage-title" class="isp-text-lg isp-font-semibold">{Lang::T('Router Activity')}</h2>
            <a href="{Text::url('routers')}" class="isp-text-xs isp-text-blue">{Lang::T('View All')}</a>
        </div>
        <div class="isp-chart-container isp-small-chart">
            <canvas id="hotspotChart"></canvas>
        </div>
    </div>

    <!-- Day of Week Activity -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="portal-logins-title" class="isp-text-lg isp-font-semibold">{Lang::T('Day of Week Activity')}</h2>
            <a href="{Text::url('reports')}" class="isp-text-xs isp-text-blue">{Lang::T('View All')}</a>
        </div>
        <div class="isp-chart-container isp-small-chart">
            <canvas id="portalChart"></canvas>
        </div>
    </div>

    <!-- Plan Distribution by Type -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="bandwidth-usage-title" class="isp-text-lg isp-font-semibold">{Lang::T('Plan Distribution')}</h2>
            <a href="{Text::url('plan/list')}" class="isp-text-xs isp-text-blue">{Lang::T('View All')}</a>
        </div>
        <div class="isp-chart-container isp-small-chart">
            <canvas id="bandwidthChart"></canvas>
        </div>
    </div>
</div>
</div>

<!-- Fourth Row: Top Selling Plans & Hourly Activity -->
<div class="dash-block" data-block="charts4" data-title="{Lang::T('Top plans and hourly activity')|escape}" style="order:{$dash_order['charts4']|default:0}">
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6 isp-margin-bottom-6">
    <!-- Top Selling Plans -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="top-plans-title" class="isp-text-lg isp-font-semibold">{Lang::T('Top Selling Plans')}</h2>
            <div class="isp-flex isp-space-x-2" id="topplans-metric-btns">
                <button type="button" class="isp-button isp-button-day active"
                    data-metric="counts">{Lang::T('Count')}</button>
                <button type="button" class="isp-button isp-button-secondary"
                    data-metric="totals">{Lang::T('Revenue')}</button>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="consumptionChart"></canvas>
        </div>
    </div>

    <!-- Hourly Activity (24 Hours) -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="hourly-activity-title" class="isp-text-lg isp-font-semibold">{Lang::T('Hourly Activity (24h)')}</h2>
            <div class="isp-flex isp-align-center">
                <span class="isp-status-indicator isp-status-online"></span>
                <span id="hourly-peak-time" class="isp-text-xs"><i class="fas fa-clock isp-margin-right-1"></i>Peak:
                    {$peak_hour_str}</span>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="latencyChart"></canvas>
        </div>
    </div>
</div>
</div>

<!-- Fifth Row: Peak Usage & System Inventory -->
<div class="dash-block" data-block="charts5" data-title="{Lang::T('Peak usage and inventory')|escape}" style="order:{$dash_order['charts5']|default:0}">
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6">
    <!-- Peak Usage Hours (3-Hour Blocks) -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="peak-usage-title" class="isp-text-lg isp-font-semibold">{Lang::T('Peak Usage Hours')}</h2>
            <span class="isp-text-xs isp-text-blue">{Lang::T('Last 30 days')}</span>
        </div>
        <div class="isp-chart-container">
            <canvas id="peakUsageChart"></canvas>
        </div>
    </div>

    <!-- System Inventory -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="equipment-status-title" class="isp-text-lg isp-font-semibold">{Lang::T('System Inventory')}</h2>
            <div class="isp-flex isp-align-center isp-space-x-2">
                <span class="isp-flex isp-align-center isp-text-xs">
                    <span
                        class="isp-status-indicator {if $routers_offline == 0}isp-status-online{else}isp-status-warning{/if} isp-margin-right-1"></span>
                    {$network_uptime}% {Lang::T('Online')}
                </span>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="equipmentChart"></canvas>
        </div>
    </div>
</div>
</div><!-- /dash-blocks -->
</div>

<!-- Real Database Data Payload -->
<script id="dashboard-raw-data" type="application/json">
{$dashboard_data_json}
</script>
<script>
    window.dashboardCurrencyCode = "{$_c['currency_code']}";
</script>

{literal}
<script>
    // Initialize chart variables
    let salesChart, customersChart, networkChart, ticketsChart, hotspotChart, portalChart, bandwidthChart;
    let consumptionChart, latencyChart, peakUsageChart, equipmentChart;
    let diskChart, ramChart, cpuChart;

    // Load real data from payload
    let dData = {};
    try {
        const rawEl = document.getElementById('dashboard-raw-data');
        if (rawEl && rawEl.textContent) {
            dData = JSON.parse(rawEl.textContent);
        }
    } catch (e) {
        console.error('Failed to parse dashboard data payload:', e);
    }

    const curCode = window.dashboardCurrencyCode || '';

    function formatMoney(val) {
        const num = Number(val) || 0;
        return (curCode ? curCode + ' ' : '') + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function getThemeColors() {
        const isDark = document.body.classList.contains('dark-mode') || document.body.classList.contains('dark');
        return {
            isDark: isDark,
            blue: '#4299e1',
            blueHover: '#3182ce',
            green: '#48bb78',
            red: '#f56565',
            orange: '#ed8936',
            purple: '#9f7aea',
            indigo: '#667eea',
            yellow: '#ecc94b',
            teal: '#38b2ac',
            gridColor: isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)',
            textColor: isDark ? '#a0aec0' : '#718096'
        };
    }

    function initAllDashboardCharts() {
        const colors = getThemeColors();
        const isDark = colors.isDark;
        const gridColor = colors.gridColor;
        const textColor = colors.textColor;

        // 1. Sales Report Chart (Day, Week, Month, Year)
        const salesCanvas = document.getElementById('salesChart');
        if (salesCanvas && dData.sales && dData.sales.day) {
            const salesCtx = salesCanvas.getContext('2d');
            const salesGrad = salesCtx.createLinearGradient(0, 0, 0, 240);
            salesGrad.addColorStop(0, 'rgba(66, 153, 225, 0.40)');
            salesGrad.addColorStop(0.7, 'rgba(66, 153, 225, 0.08)');
            salesGrad.addColorStop(1, 'rgba(66, 153, 225, 0.00)');

            salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: dData.sales.day.labels,
                    datasets: [{
                        label: 'Sales Revenue',
                        data: dData.sales.day.values,
                        borderColor: colors.blue,
                        backgroundColor: salesGrad,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: colors.blue,
                        pointHoverBorderColor: isDark ? '#1a202c' : '#ffffff',
                        pointHoverBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return 'Revenue: ' + formatMoney(ctx.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor, borderDash: [4, 4] },
                            ticks: {
                                color: textColor,
                                callback: function (val) {
                                    return (curCode ? curCode + ' ' : '') + Number(val).toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }

        // 2. Customer Growth Chart (All, New, Churned)
        const custCanvas = document.getElementById('customersChart');
        if (custCanvas && dData.customerGrowth) {
            const custCtx = custCanvas.getContext('2d');
            customersChart = new Chart(custCtx, {
                type: 'bar',
                data: {
                    labels: dData.customerGrowth.labels,
                    datasets: [
                        {
                            label: 'New Customers',
                            data: dData.customerGrowth.new,
                            backgroundColor: colors.green,
                            borderRadius: 6
                        },
                        {
                            label: 'Expired / Churned',
                            data: dData.customerGrowth.churned,
                            backgroundColor: colors.red,
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: { color: textColor, usePointStyle: true, boxWidth: 8 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor, borderDash: [4, 4] },
                            ticks: { color: textColor, precision: 0 }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }

        // 3. Network Status Chart (Online vs Offline Routers & Radius Nodes)
        const netCanvas = document.getElementById('networkChart');
        if (netCanvas && dData.network) {
            const netCtx = netCanvas.getContext('2d');
            const totalNodes = dData.network.total !== undefined ? dData.network.total : (dData.network.values ? (dData.network.values[0] + dData.network.values[1]) : 0);
            const onlineCount = dData.network.online !== undefined ? dData.network.online : (dData.network.values ? dData.network.values[0] : 0);
            const offlineCount = dData.network.offline !== undefined ? dData.network.offline : (dData.network.values ? dData.network.values[1] : 0);
            const uptimePct = dData.network.uptime !== undefined ? dData.network.uptime : (totalNodes > 0 ? Math.round((onlineCount / totalNodes) * 100) : 100);

            const chartData = totalNodes > 0 ? [onlineCount, offlineCount] : [1];
            const chartColors = totalNodes > 0 ? [colors.green, colors.red] : [isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)'];
            const chartLabels = totalNodes > 0 ? ['Online', 'Offline'] : ['No Nodes'];

            const centerPlugin = {
                id: 'networkCenterText',
                beforeDraw: function (chart) {
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return;
                    ctx.save();
                    const centerX = (chartArea.left + chartArea.right) / 2;
                    const centerY = (chartArea.top + chartArea.bottom) / 2;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    if (totalNodes > 0) {
                        ctx.font = 'bold 24px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                        ctx.fillStyle = isDark ? '#ffffff' : '#1a202c';
                        ctx.fillText(uptimePct + '%', centerX, centerY - 10);

                        ctx.font = '12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                        ctx.fillStyle = isDark ? '#a0aec0' : '#718096';
                        ctx.fillText(onlineCount + '/' + totalNodes + ' Online', centerX, centerY + 16);
                    } else {
                        ctx.font = 'bold 18px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                        ctx.fillStyle = isDark ? '#a0aec0' : '#718096';
                        ctx.fillText('0 Nodes', centerX, centerY - 6);

                        ctx.font = '11px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                        ctx.fillText('None configured', centerX, centerY + 14);
                    }
                    ctx.restore();
                }
            };

            networkChart = new Chart(netCtx, {
                type: 'doughnut',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        data: chartData,
                        backgroundColor: chartColors,
                        borderWidth: 0
                    }]
                },
                plugins: [centerPlugin],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: textColor,
                                usePointStyle: true,
                                boxWidth: 8,
                                generateLabels: function (chart) {
                                    if (totalNodes === 0) {
                                        return [{
                                            text: 'No Nodes Configured',
                                            fillStyle: isDark ? '#4a5568' : '#cbd5e0',
                                            strokeStyle: isDark ? '#4a5568' : '#cbd5e0',
                                            lineWidth: 0,
                                            pointStyle: 'circle'
                                        }];
                                    }
                                    return [
                                        {
                                            text: 'Online: ' + onlineCount + ' (' + uptimePct + '%)',
                                            fillStyle: colors.green,
                                            strokeStyle: colors.green,
                                            lineWidth: 0,
                                            pointStyle: 'circle'
                                        },
                                        {
                                            text: 'Offline: ' + offlineCount,
                                            fillStyle: colors.red,
                                            strokeStyle: colors.red,
                                            lineWidth: 0,
                                            pointStyle: 'circle'
                                        }
                                    ];
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    if (totalNodes === 0) return ' No network nodes found';
                                    return ' ' + ctx.label + ': ' + ctx.parsed + (ctx.parsed === 1 ? ' Node' : ' Nodes');
                                },
                                afterLabel: function (ctx) {
                                    if (ctx.label === 'Online' && dData.network.radius_enabled) {
                                        const rOn = dData.network.routers_online || 0;
                                        const rTot = dData.network.routers_total || 0;
                                        const radOn = dData.network.radius_online ? 'Online' : 'Offline';
                                        const nasCount = dData.network.radius_nas_count || 0;
                                        return ' Routers: ' + rOn + '/' + rTot + ' | Radius: ' + radOn + (nasCount ? ' (' + nasCount + ' NAS)' : '');
                                    }
                                    return '';
                                }
                            }
                        }
                    }
                }
            });
        }

        // 4. Transactions by Service (formerly Tickets Chart)
        const trxCanvas = document.getElementById('ticketsChart');
        if (trxCanvas && dData.transactions) {
            const trxCtx = trxCanvas.getContext('2d');
            ticketsChart = new Chart(trxCtx, {
                type: 'bar',
                data: {
                    labels: dData.transactions.labels,
                    datasets: [{
                        label: 'Revenue',
                        data: dData.transactions.totals,
                        backgroundColor: colors.blue,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return formatMoney(ctx.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor, borderDash: [4, 4] },
                            ticks: {
                                color: textColor,
                                callback: function (val) {
                                    return (curCode ? curCode + ' ' : '') + Number(val).toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }

        // 5. Router Activity Chart (formerly Hotspot Chart)
        const hsCanvas = document.getElementById('hotspotChart');
        if (hsCanvas && dData.routerUsage) {
            const hsCtx = hsCanvas.getContext('2d');
            const hsColors = [
                'rgba(66, 153, 225, 0.85)',
                'rgba(102, 126, 234, 0.85)',
                'rgba(159, 122, 234, 0.85)',
                'rgba(237, 137, 54, 0.85)',
                'rgba(72, 187, 120, 0.85)',
                'rgba(245, 101, 101, 0.85)'
            ];
            hotspotChart = new Chart(hsCtx, {
                type: 'bar',
                data: {
                    labels: dData.routerUsage.labels,
                    datasets: [{
                        label: 'Transactions',
                        data: dData.routerUsage.values,
                        backgroundColor: hsColors.slice(0, dData.routerUsage.labels.length),
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor, borderDash: [4, 4] },
                            ticks: { color: textColor, precision: 0 }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }

        // 6. Day of Week Activity Radar Chart (formerly Portal Chart)
        const portalCanvas = document.getElementById('portalChart');
        if (portalCanvas && dData.portalLogins) {
            const portalCtx = portalCanvas.getContext('2d');
            portalChart = new Chart(portalCtx, {
                type: 'radar',
                data: {
                    labels: dData.portalLogins.labels,
                    datasets: [{
                        label: 'Activity',
                        data: dData.portalLogins.values,
                        backgroundColor: 'rgba(102, 126, 234, 0.25)',
                        borderColor: colors.indigo,
                        borderWidth: 2,
                        pointBackgroundColor: colors.indigo
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        r: {
                            angleLines: { color: gridColor },
                            grid: { color: gridColor },
                            ticks: { color: textColor, backdropColor: 'transparent', precision: 0 },
                            pointLabels: { color: textColor },
                            suggestedMin: 0
                        }
                    }
                }
            });
        }

        // 7. Plan Distribution Polar Chart (formerly Bandwidth Chart)
        const bwCanvas = document.getElementById('bandwidthChart');
        if (bwCanvas && dData.planTypes) {
            const bwCtx = bwCanvas.getContext('2d');
            const ptColors = [
                'rgba(66, 153, 225, 0.8)',
                'rgba(72, 187, 120, 0.8)',
                'rgba(159, 122, 234, 0.8)',
                'rgba(237, 137, 54, 0.8)',
                'rgba(245, 101, 101, 0.8)'
            ];
            bandwidthChart = new Chart(bwCtx, {
                type: 'polarArea',
                data: {
                    labels: dData.planTypes.labels,
                    datasets: [{
                        data: dData.planTypes.values,
                        backgroundColor: ptColors.slice(0, dData.planTypes.labels.length),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { color: textColor, usePointStyle: true, boxWidth: 8 }
                        }
                    },
                    scales: {
                        r: {
                            grid: { color: gridColor },
                            angleLines: { color: gridColor },
                            ticks: { color: textColor, backdropColor: 'transparent', precision: 0 }
                        }
                    }
                }
            });
        }

        // 8. Top Selling Plans (formerly Data Consumption Chart)
        const consCanvas = document.getElementById('consumptionChart');
        if (consCanvas && dData.topPlans) {
            const consCtx = consCanvas.getContext('2d');
            consumptionChart = new Chart(consCtx, {
                type: 'bar',
                data: {
                    labels: dData.topPlans.labels,
                    datasets: [{
                        label: 'Sold Count',
                        data: dData.topPlans.counts,
                        backgroundColor: colors.purple,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor, borderDash: [4, 4] },
                            ticks: { color: textColor, precision: 0 }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }

        // 9. Hourly Activity 24h (formerly Network Latency Chart)
        const latCanvas = document.getElementById('latencyChart');
        if (latCanvas && dData.hourly) {
            const latCtx = latCanvas.getContext('2d');
            const ltGrad = latCtx.createLinearGradient(0, 0, 0, 220);
            ltGrad.addColorStop(0, 'rgba(237, 137, 54, 0.35)');
            ltGrad.addColorStop(1, 'rgba(237, 137, 54, 0.00)');

            latencyChart = new Chart(latCtx, {
                type: 'line',
                data: {
                    labels: dData.hourly.labels,
                    datasets: [{
                        label: 'Transactions',
                        data: dData.hourly.values,
                        borderColor: colors.orange,
                        backgroundColor: ltGrad,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: colors.orange,
                        pointHoverBorderColor: isDark ? '#1a202c' : '#ffffff',
                        pointHoverBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor, borderDash: [4, 4] },
                            ticks: { color: textColor, precision: 0 }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }

        // 10. Peak Usage Chart (3-Hour Blocks)
        const pkCanvas = document.getElementById('peakUsageChart');
        if (pkCanvas && dData.peakUsage) {
            const pkCtx = pkCanvas.getContext('2d');
            const pkColors = [
                'rgba(66, 153, 225, 0.85)',
                'rgba(66, 153, 225, 0.85)',
                'rgba(66, 153, 225, 0.85)',
                'rgba(102, 126, 234, 0.85)',
                'rgba(159, 122, 234, 0.85)',
                'rgba(237, 137, 54, 0.85)',
                'rgba(237, 137, 54, 0.85)',
                'rgba(102, 126, 234, 0.85)'
            ];
            peakUsageChart = new Chart(pkCtx, {
                type: 'bar',
                data: {
                    labels: dData.peakUsage.labels,
                    datasets: [{
                        label: 'Transactions',
                        data: dData.peakUsage.values,
                        backgroundColor: pkColors,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor, borderDash: [4, 4] },
                            ticks: { color: textColor, precision: 0 }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }

        // 11. System Inventory Chart (formerly Equipment Chart)
        const eqCanvas = document.getElementById('equipmentChart');
        if (eqCanvas && dData.equipment) {
            const eqCtx = eqCanvas.getContext('2d');
            const eqColors = [
                '#4299e1',
                '#667eea',
                '#9f7aea',
                '#ed8936',
                '#48bb78',
                '#f56565'
            ];
            equipmentChart = new Chart(eqCtx, {
                type: 'doughnut',
                data: {
                    labels: dData.equipment.labels,
                    datasets: [{
                        data: dData.equipment.values,
                        backgroundColor: eqColors.slice(0, dData.equipment.labels.length),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { color: textColor, usePointStyle: true, boxWidth: 8 }
                        }
                    }
                }
            });
        }

        // 12. System Status Charts: Disk Storage
        const diskCanvas = document.getElementById('diskChart');
        if (diskCanvas && dData.systemStatus && dData.systemStatus.disk) {
            const diskCtx = diskCanvas.getContext('2d');
            const diskData = dData.systemStatus.disk;
            const diskPct = Number(diskData.percent) || 0;
            const diskUsed = Number(diskData.used) || 0;
            const diskFree = Number(diskData.free) || 0;
            const diskColor = diskPct >= 90 ? colors.red : (diskPct >= 80 ? colors.orange : colors.blue);

            const diskPlugin = {
                id: 'diskCenterText',
                beforeDraw: function (chart) {
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return;
                    ctx.save();
                    const centerX = (chartArea.left + chartArea.right) / 2;
                    const centerY = (chartArea.top + chartArea.bottom) / 2;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    ctx.font = 'bold 18px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                    ctx.fillStyle = isDark ? '#ffffff' : '#1a202c';
                    const dk = (dData.systemStatus && dData.systemStatus.disk) || diskData;
                    ctx.fillText((Number(dk.percent) || 0) + '%', centerX, centerY - 6);

                    ctx.font = '10px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                    ctx.fillStyle = isDark ? '#a0aec0' : '#718096';
                    ctx.fillText('Used: ' + dk.used_formatted, centerX, centerY + 14);
                    ctx.restore();
                }
            };

            diskChart = new Chart(diskCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Used', 'Free'],
                    datasets: [{
                        data: [diskUsed, diskFree],
                        backgroundColor: [diskColor, isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)'],
                        borderWidth: 0
                    }]
                },
                plugins: [diskPlugin],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '74%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    const dk = (dData.systemStatus && dData.systemStatus.disk) || diskData;
                                    return ctx.label + ': ' + (ctx.dataIndex === 0 ? dk.used_formatted : dk.free_formatted);
                                }
                            }
                        }
                    }
                }
            });
        }

        // 13. System Status Charts: Memory (RAM)
        const ramCanvas = document.getElementById('ramChart');
        if (ramCanvas && dData.systemStatus && dData.systemStatus.ram) {
            const ramCtx = ramCanvas.getContext('2d');
            const ramData = dData.systemStatus.ram;
            const ramPct = Number(ramData.percent) || 0;
            const ramUsed = Number(ramData.used) || 0;
            const ramFree = Number(ramData.free) || 0;
            const ramColor = ramPct >= 90 ? colors.red : (ramPct >= 80 ? colors.orange : colors.purple);

            const ramPlugin = {
                id: 'ramCenterText',
                beforeDraw: function (chart) {
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return;
                    ctx.save();
                    const centerX = (chartArea.left + chartArea.right) / 2;
                    const centerY = (chartArea.top + chartArea.bottom) / 2;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    ctx.font = 'bold 18px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                    ctx.fillStyle = isDark ? '#ffffff' : '#1a202c';
                    const rm = (dData.systemStatus && dData.systemStatus.ram) || ramData;
                    ctx.fillText((Number(rm.percent) || 0) + '%', centerX, centerY - 6);

                    ctx.font = '10px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                    ctx.fillStyle = isDark ? '#a0aec0' : '#718096';
                    ctx.fillText('Used: ' + rm.used_formatted, centerX, centerY + 14);
                    ctx.restore();
                }
            };

            ramChart = new Chart(ramCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Used', 'Free'],
                    datasets: [{
                        data: [ramUsed, ramFree],
                        backgroundColor: [ramColor, isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)'],
                        borderWidth: 0
                    }]
                },
                plugins: [ramPlugin],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '74%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    const rm = (dData.systemStatus && dData.systemStatus.ram) || ramData;
                                    return ctx.label + ': ' + (ctx.dataIndex === 0 ? rm.used_formatted : rm.free_formatted);
                                }
                            }
                        }
                    }
                }
            });
        }

        // 14. System Status Charts: CPU Utilization
        const cpuCanvas = document.getElementById('cpuChart');
        if (cpuCanvas && dData.systemStatus && dData.systemStatus.cpu) {
            const cpuCtx = cpuCanvas.getContext('2d');
            const cpuData = dData.systemStatus.cpu;
            const cpuPct = Number(cpuData.percent) || 0;
            const idlePct = Math.max(0, 100 - cpuPct);
            const cpuColor = cpuPct >= 90 ? colors.red : (cpuPct >= 75 ? colors.orange : colors.teal);

            const cpuPlugin = {
                id: 'cpuCenterText',
                beforeDraw: function (chart) {
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return;
                    ctx.save();
                    const centerX = (chartArea.left + chartArea.right) / 2;
                    const centerY = (chartArea.top + chartArea.bottom) / 2;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    ctx.font = 'bold 18px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                    ctx.fillStyle = isDark ? '#ffffff' : '#1a202c';
                    const cp = (dData.systemStatus && dData.systemStatus.cpu) || cpuData;
                    ctx.fillText((Number(cp.percent) || 0) + '%', centerX, centerY - 6);

                    ctx.font = '10px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                    ctx.fillStyle = isDark ? '#a0aec0' : '#718096';
                    ctx.fillText((cp.cores || 1) + ' Cores', centerX, centerY + 14);
                    ctx.restore();
                }
            };

            cpuChart = new Chart(cpuCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Load', 'Idle'],
                    datasets: [{
                        data: [cpuPct, idlePct],
                        backgroundColor: [cpuColor, isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)'],
                        borderWidth: 0
                    }]
                },
                plugins: [cpuPlugin],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '74%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return ctx.label + ': ' + ctx.parsed + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    function setupInteractiveButtons() {
        // 1. Sales Report Timeframe Toggle
        const salesBtns = document.getElementById('sales-timeframe-btns');
        if (salesBtns && dData.sales) {
            salesBtns.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', function () {
                    salesBtns.querySelectorAll('button').forEach(b => {
                        b.classList.remove('isp-button-day', 'active');
                        b.classList.add('isp-button-secondary');
                    });
                    this.classList.remove('isp-button-secondary');
                    this.classList.add('isp-button-day', 'active');

                    const tf = this.getAttribute('data-timeframe');
                    if (salesChart && dData.sales[tf]) {
                        salesChart.data.labels = dData.sales[tf].labels;
                        salesChart.data.datasets[0].data = dData.sales[tf].values;
                        salesChart.update();
                    }
                });
            });
        }

        // 2. Customer Growth Filter Toggle
        const custBtns = document.getElementById('customer-filter-btns');
        if (custBtns && dData.customerGrowth) {
            custBtns.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', function () {
                    custBtns.querySelectorAll('button').forEach(b => {
                        b.classList.remove('isp-button-day', 'active');
                        b.classList.add('isp-button-secondary');
                    });
                    this.classList.remove('isp-button-secondary');
                    this.classList.add('isp-button-day', 'active');

                    const filter = this.getAttribute('data-filter');
                    if (customersChart) {
                        if (filter === 'all') {
                            customersChart.data.datasets[0].hidden = false;
                            customersChart.data.datasets[1].hidden = false;
                        } else if (filter === 'new') {
                            customersChart.data.datasets[0].hidden = false;
                            customersChart.data.datasets[1].hidden = true;
                        } else if (filter === 'churned') {
                            customersChart.data.datasets[0].hidden = true;
                            customersChart.data.datasets[1].hidden = false;
                        }
                        customersChart.update();
                    }
                });
            });
        }

        // 3. Transactions by Service Metric Toggle
        const trxBtns = document.getElementById('trx-metric-btns');
        if (trxBtns && dData.transactions) {
            trxBtns.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', function () {
                    trxBtns.querySelectorAll('button').forEach(b => {
                        b.classList.remove('isp-button-day', 'active');
                        b.classList.add('isp-button-secondary');
                    });
                    this.classList.remove('isp-button-secondary');
                    this.classList.add('isp-button-day', 'active');

                    const metric = this.getAttribute('data-metric');
                    if (ticketsChart) {
                        ticketsChart.data.datasets[0].label = metric === 'totals' ? 'Revenue' : 'Count';
                        ticketsChart.data.datasets[0].data = dData.transactions[metric];
                        ticketsChart.options.plugins.tooltip.callbacks.label = function (ctx) {
                            return metric === 'totals' ? formatMoney(ctx.parsed.y) : ctx.parsed.y + ' transactions';
                        };
                        ticketsChart.update();
                    }
                });
            });
        }

        // 4. Top Selling Plans Metric Toggle
        const topPlansBtns = document.getElementById('topplans-metric-btns');
        if (topPlansBtns && dData.topPlans) {
            topPlansBtns.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', function () {
                    topPlansBtns.querySelectorAll('button').forEach(b => {
                        b.classList.remove('isp-button-day', 'active');
                        b.classList.add('isp-button-secondary');
                    });
                    this.classList.remove('isp-button-secondary');
                    this.classList.add('isp-button-day', 'active');

                    const metric = this.getAttribute('data-metric');
                    if (consumptionChart) {
                        consumptionChart.data.datasets[0].label = metric === 'totals' ? 'Revenue' : 'Sold Count';
                        consumptionChart.data.datasets[0].data = dData.topPlans[metric];
                        consumptionChart.options.plugins.tooltip.callbacks = {
                            label: function (ctx) {
                                return metric === 'totals' ? formatMoney(ctx.parsed.y) : ctx.parsed.y + ' plans';
                            }
                        };
                        consumptionChart.update();
                    }
                });
            });
        }

        // 5. Network Status Tabs (Routers vs Radius vs All Nodes)
        const netTabBtns = document.getElementById('network-tab-btns');
        const netManageLink = document.getElementById('net-manage-link');
        if (netTabBtns) {
            netTabBtns.querySelectorAll('.net-tab-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    netTabBtns.querySelectorAll('.net-tab-btn').forEach(b => {
                        b.classList.remove('active');
                    });
                    this.classList.add('active');

                    const tab = this.getAttribute('data-tab');
                    document.querySelectorAll('.net-tab-content').forEach(c => {
                        c.style.display = 'none';
                    });
                    const target = document.getElementById('net-tab-' + tab);
                    if (target) {
                        target.style.display = 'block';
                    }

                    if (netManageLink) {
                        if (tab === 'radius') {
                            netManageLink.innerHTML = 'Manage Radius NAS';
                            netManageLink.href = netManageLink.getAttribute('data-radius-url');
                        } else if (tab === 'online') {
                            netManageLink.innerHTML = 'View Customers';
                            netManageLink.href = netManageLink.getAttribute('data-online-url');
                        } else {
                            netManageLink.innerHTML = 'Manage Routers';
                            netManageLink.href = netManageLink.getAttribute('data-routers-url');
                        }
                    }
                });
            });
        }

        // 6. Live System Status Refresh
        const btnRefreshSys = document.getElementById('btn-refresh-sysinfo');
        if (btnRefreshSys) {
            btnRefreshSys.addEventListener('click', function () {
                const icon = document.getElementById('sysinfo-refresh-icon');
                if (icon) icon.classList.add('fa-spin');
                btnRefreshSys.disabled = true;

                fetch(appUrl + '/?_route=dashboard&ajax_system_status=1')
                    .then(res => res.json())
                    .then(data => {
                        if (data) {
                            dData.systemStatus = data;
                            const colors = getThemeColors();
                            const setBadge = function (badgeId, valId, status, text) {
                                const b = document.getElementById(badgeId);
                                if (b) {
                                    b.classList.remove('badge-success', 'badge-warning', 'badge-danger');
                                    b.classList.add(status === 'danger' ? 'badge-danger' : (status === 'warning' ? 'badge-warning' : 'badge-success'));
                                }
                                const v = document.getElementById(valId);
                                if (v && text !== undefined) v.textContent = text;
                            };

                            // 1. Update Disk
                            if (diskChart && data.disk) {
                                const diskUsed = Number(data.disk.used) || 0;
                                const diskFree = Number(data.disk.free) || 0;
                                const diskPct = Number(data.disk.percent) || 0;
                                diskChart.data.datasets[0].data = [diskUsed, diskFree];
                                diskChart.data.datasets[0].backgroundColor[0] = diskPct >= 90 ? colors.red : (diskPct >= 80 ? colors.orange : colors.blue);
                                diskChart.update();
                                setBadge('disk-pct-badge', 'disk-pct-val', data.disk.status, data.disk.percent);
                                const usedEl = document.getElementById('disk-used-val');
                                if (usedEl) usedEl.textContent = data.disk.used_formatted;
                                const freeEl = document.getElementById('disk-free-val');
                                if (freeEl) freeEl.textContent = data.disk.free_formatted;
                                const totalEl = document.getElementById('disk-total-val');
                                if (totalEl) totalEl.textContent = data.disk.total_formatted;
                            }

                            // 2. Update RAM
                            if (ramChart && data.ram) {
                                const ramUsed = Number(data.ram.used) || 0;
                                const ramFree = Number(data.ram.free) || 0;
                                const ramPct = Number(data.ram.percent) || 0;
                                ramChart.data.datasets[0].data = [ramUsed, ramFree];
                                ramChart.data.datasets[0].backgroundColor[0] = ramPct >= 90 ? colors.red : (ramPct >= 80 ? colors.orange : colors.purple);
                                ramChart.update();
                                setBadge('ram-pct-badge', 'ram-pct-val', data.ram.status, data.ram.percent);
                                const rUsedEl = document.getElementById('ram-used-val');
                                if (rUsedEl) rUsedEl.textContent = data.ram.used_formatted;
                                const rFreeEl = document.getElementById('ram-free-val');
                                if (rFreeEl) rFreeEl.textContent = data.ram.free_formatted;
                            }

                            // 3. Update CPU
                            if (cpuChart && data.cpu) {
                                const cpuPct = Number(data.cpu.percent) || 0;
                                cpuChart.data.datasets[0].data = [cpuPct, Math.max(0, 100 - cpuPct)];
                                cpuChart.data.datasets[0].backgroundColor[0] = cpuPct >= 90 ? colors.red : (cpuPct >= 75 ? colors.orange : colors.teal);
                                cpuChart.update();
                                setBadge('cpu-cores-badge', 'cpu-cores-val', data.cpu.status, data.cpu.cores);
                                const loadEl = document.getElementById('cpu-load-val');
                                if (loadEl) loadEl.textContent = data.cpu.load_string;
                            }

                            // 4. Update Uptime & DB Latency
                            const uptimeEl = document.getElementById('sys-uptime-val');
                            if (uptimeEl && data.uptime) uptimeEl.textContent = data.uptime.formatted;
                            const subUptimeEl = document.getElementById('sys-uptime-subtitle');
                            if (subUptimeEl && data.uptime) subUptimeEl.textContent = data.uptime.formatted;
                            if (window.sysClockSet && data.time) window.sysClockSet(data.time);
                            const latencyEl = document.getElementById('db-latency-val');
                            if (latencyEl && data.database) latencyEl.textContent = data.database.latency_ms + ' ms';
                            if (data.database) setBadge('db-status-badge', 'db-status-val', data.database.status === 'online' ? 'success' : 'danger', data.database.status);

                            // 5. Update Online Users (Live - Mikrotik API, Radius REST, Radius MySQL)
                            if (data.online_users) {
                                const ouTotal = document.getElementById('online-users-total');
                                if (ouTotal) ouTotal.textContent = data.online_users.total ?? 0;
                                const ouMt = document.getElementById('online-users-mt');
                                if (ouMt) ouMt.textContent = data.online_users.mikrotik ?? 0;
                                const ouRest = document.getElementById('online-users-rest');
                                if (ouRest) ouRest.textContent = data.online_users.rest ?? 0;
                                const ouSql = document.getElementById('online-users-mysql');
                                if (ouSql) ouSql.textContent = data.online_users.mysql ?? 0;

                                // Tab values
                                const tabNetCount = document.getElementById('net-tab-online-count');
                                if (tabNetCount) tabNetCount.textContent = data.online_users.total ?? 0;
                                const tabMtTot = document.getElementById('tab-mt-total');
                                if (tabMtTot) tabMtTot.textContent = data.online_users.mikrotik ?? 0;
                                const tabMtHs = document.getElementById('tab-mt-hotspot');
                                if (tabMtHs) tabMtHs.textContent = data.online_users.mikrotik_hotspot ?? 0;
                                const tabMtPp = document.getElementById('tab-mt-pppoe');
                                if (tabMtPp) tabMtPp.textContent = data.online_users.mikrotik_pppoe ?? 0;
                                const tabRestTot = document.getElementById('tab-rest-total');
                                if (tabRestTot) tabRestTot.textContent = data.online_users.rest ?? 0;
                                const tabRestUsers = document.getElementById('tab-rest-users');
                                if (tabRestUsers) tabRestUsers.textContent = data.online_users.rest_users ?? 0;
                                const tabSqlTot = document.getElementById('tab-sql-total');
                                if (tabSqlTot) tabSqlTot.textContent = data.online_users.mysql ?? 0;
                                const tabSqlUsers = document.getElementById('tab-sql-users');
                                if (tabSqlUsers) tabSqlUsers.textContent = data.online_users.mysql_users ?? 0;

                                // Dedicated Big Card elements
                                const bigTotal = document.getElementById('big-card-total-online');
                                if (bigTotal) bigTotal.textContent = data.online_users.total ?? 0;
                                const bigMtTot = document.getElementById('big-col-mt-total');
                                if (bigMtTot) bigMtTot.textContent = data.online_users.mikrotik ?? 0;
                                const bigMtHs = document.getElementById('big-col-mt-hotspot');
                                if (bigMtHs) bigMtHs.textContent = data.online_users.mikrotik_hotspot ?? 0;
                                const bigMtPp = document.getElementById('big-col-mt-pppoe');
                                if (bigMtPp) bigMtPp.textContent = data.online_users.mikrotik_pppoe ?? 0;
                                const bigRestTot = document.getElementById('big-col-rest-total');
                                if (bigRestTot) bigRestTot.textContent = data.online_users.rest ?? 0;
                                const bigRestUsers = document.getElementById('big-col-rest-users');
                                if (bigRestUsers) bigRestUsers.textContent = data.online_users.rest_users ?? 0;
                                const bigSqlTot = document.getElementById('big-col-mysql-total');
                                if (bigSqlTot) bigSqlTot.textContent = data.online_users.mysql ?? 0;
                                const bigSqlUsers = document.getElementById('big-col-mysql-users');
                                if (bigSqlUsers) bigSqlUsers.textContent = data.online_users.mysql_users ?? 0;
                            }

                            // 6. Update Services Status Matrix
                            if (data.services && Array.isArray(data.services)) {
                                const sGrid = document.getElementById('services-status-matrix');
                                if (sGrid) {
                                    const timeCard = document.getElementById('sys-time-card'); // not a service: keep it
                                    sGrid.innerHTML = data.services.map(svc => {
                                        const isSuccess = svc.badge_class === 'success';
                                        const isDanger = svc.badge_class === 'danger';
                                        const isWarning = svc.badge_class === 'warning';
                                        const badgeClass = isSuccess ? 'badge-success' : (isDanger ? 'badge-danger' : (isWarning ? 'badge-warning' : 'badge-default badge-secondary'));
                                        const iconBg = isSuccess ? 'rgba(72, 187, 120, 0.12)' : (isDanger ? 'rgba(245, 101, 101, 0.12)' : (isWarning ? 'rgba(237, 137, 54, 0.12)' : 'rgba(160, 174, 192, 0.12)'));
                                        const iconColor = isSuccess ? '#48bb78' : (isDanger ? '#f56565' : (isWarning ? '#ed8936' : '#718096'));
                                        return `
                                            <div class="sys-service-card">
                                                <div style="display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1;">
                                                    <div style="width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: ${iconBg}; color: ${iconColor};">
                                                        <i class="${svc.icon}" style="font-size: 14px;"></i>
                                                    </div>
                                                    <div style="min-width: 0; flex: 1;">
                                                        <div style="font-size: 12px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${svc.name}</div>
                                                        <div style="font-size: 10px; opacity: 0.65; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${svc.info}">${svc.info}</div>
                                                    </div>
                                                </div>
                                                <span class="badge ${badgeClass}">
                                                    ${svc.status_label}
                                                </span>
                                            </div>
                                        `;
                                    }).join('');
                                    if (timeCard) sGrid.appendChild(timeCard);
                                }
                            }
                        }
                    })
                    .catch(err => console.error('Failed to refresh system info:', err))
                    .finally(() => {
                        if (icon) icon.classList.remove('fa-spin');
                        btnRefreshSys.disabled = false;
                    });
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAllDashboardCharts();
        setupInteractiveButtons();
    });
</script>
{/literal}

{include file="admin/update-notice.tpl"}

{literal}
<script>
    // Server (OS) / PHP / database clocks: ticking from the server's own timestamps (their timezone, not the browser's)
    (function () {
        var rows = ['srv', 'php', 'db'].map(function (k) {
            return { k: k, el: document.getElementById(k + '-time-val'), tz: document.getElementById(k + '-time-tz'), base: null };
        });
        var badge = document.getElementById('time-drift-badge');
        if (!rows[0].el) return;
        var t0 = Date.now();
        function parse(s) { return s ? Date.parse(String(s).replace(' ', 'T') + 'Z') : null; }
        function p2(n) { return String(n).padStart(2, '0'); }
        function fmt(ms) {
            var d = new Date(ms);
            return d.getUTCFullYear() + '-' + p2(d.getUTCMonth() + 1) + '-' + p2(d.getUTCDate()) + ' ' +
                p2(d.getUTCHours()) + ':' + p2(d.getUTCMinutes()) + ':' + p2(d.getUTCSeconds());
        }
        function tick() {
            var el = Date.now() - t0;
            rows.forEach(function (r) { r.el.textContent = r.base !== null ? fmt(r.base + el) : '-'; });
        }
        window.sysClockSet = function (t) {
            if (!t) return;
            var src = { srv: [t.server, t.server_tz], php: [t.php, t.php_tz], db: [t.database, t.database_tz] };
            rows.forEach(function (r) {
                r.base = parse(src[r.k][0]);
                if (r.tz) r.tz.textContent = src[r.k][1] ? '(' + src[r.k][1] + ')' : '';
            });
            t0 = Date.now();
            if (badge) {
                var skew = Number(t.skew) || 0, ok = badge.getAttribute('data-ok'), warn = badge.getAttribute('data-warn');
                var bad = skew >= 60 || !!t.tz_mismatch;
                badge.className = (badge.getAttribute('data-base') || '') + ' ' + (bad ? warn : ok);
                badge.textContent = skew >= 60 ? badge.getAttribute('data-t-skew') + ' ' + (skew >= 3600 ? (skew / 3600).toFixed(1) + ' h' : Math.round(skew / 60) + ' min')
                    : (t.tz_mismatch ? badge.getAttribute('data-t-tz') : badge.getAttribute('data-t-ok'));
            }
            tick();
        };
        rows.forEach(function (r) { r.base = parse(r.el.getAttribute('data-ts')); });
        tick();
        setInterval(tick, 1000);
    })();
</script>
{/literal}

{include file="sections/footer.tpl"}