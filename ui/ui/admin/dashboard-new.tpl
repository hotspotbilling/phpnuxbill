{include file="sections/header.tpl"}
<style>
    .isp-button {
        padding: 5px 12px !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        border-radius: 6px !important;
        border: 1px solid transparent !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
        outline: none !important;
        line-height: 1.3 !important;
    }
    .isp-button-day,
    .isp-button.active {
        background-color: #4299e1 !important;
        color: #ffffff !important;
        border-color: #4299e1 !important;
        font-weight: 600 !important;
        box-shadow: 0 1px 2px rgba(66, 153, 225, 0.3) !important;
    }
    .dark-mode .isp-button-day,
    .dark-mode .isp-button.active,
    .dark .isp-button-day,
    .dark .isp-button.active {
        background-color: #4299e1 !important;
        color: #ffffff !important;
        border-color: #4299e1 !important;
        box-shadow: 0 1px 2px rgba(66, 153, 225, 0.35) !important;
    }
    .isp-button-secondary {
        background-color: #edf2f7 !important;
        color: #4a5568 !important;
        border: 1px solid #e2e8f0 !important;
    }
    .isp-button-secondary:hover {
        background-color: #e2e8f0 !important;
        color: #2d3748 !important;
    }
    .dark-mode .isp-button-secondary,
    .dark .isp-button-secondary {
        background-color: #2d3748 !important;
        color: #cbd5e0 !important;
        border: 1px solid #4a5568 !important;
    }
    .dark-mode .isp-button-secondary:hover,
    .dark .isp-button-secondary:hover {
        background-color: #4a5568 !important;
        color: #ffffff !important;
    }
    .isp-text-blue {
        color: #4299e1 !important;
        font-weight: 500 !important;
        cursor: pointer;
        transition: opacity 0.15s ease;
        text-decoration: none !important;
    }
    .isp-text-blue:hover {
        opacity: 0.8;
    }
    .dark-mode .isp-text-blue,
    .dark .isp-text-blue {
        color: #63b3ed !important;
    }
    .net-tab-btn {
        border: none;
        background: transparent;
        color: #718096;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .net-tab-btn:hover {
        color: #2d3748;
    }
    .net-tab-btn.active {
        background: rgba(66, 153, 225, 0.15) !important;
        color: #3182ce !important;
    }
    .dark-mode .net-tab-btn, .dark .net-tab-btn {
        color: #a0aec0;
    }
    .dark-mode .net-tab-btn:hover, .dark .net-tab-btn:hover {
        color: #edf2f7;
    }
    .dark-mode .net-tab-btn.active, .dark .net-tab-btn.active {
        background: rgba(66, 153, 225, 0.25) !important;
        color: #63b3ed !important;
    }
</style>

<!-- Stats Cards (100% Real Database Values) -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-md-2 isp-grid-cols-lg-4 isp-gap-6 isp-margin-bottom-6">
    <!-- Card 1: Total Customers -->
    <div class="isp-stat-card isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between">
            <div id="total-customers-content">
                <p class="isp-text-sm isp-font-medium isp-text-gray-dark">{Lang::T('Total Customers')}</p>
                <p class="isp-text-2xl isp-font-bold isp-margin-top-1">{$c_all}</p>
                <p class="isp-text-sm {if $c_percentage_change >= 0}isp-text-green{else}isp-text-red{/if} isp-margin-top-1 isp-flex isp-align-center">
                    <i class="fas {if $c_percentage_change >= 0}fa-arrow-up{else}fa-arrow-down{/if} isp-margin-right-1"></i>
                    {$c_percentage_change}% {Lang::T('from last month')}
                </p>
            </div>
            <div class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-blue-light isp-flex isp-align-center isp-justify-center">
                <i class="fas fa-users isp-text-blue"></i>
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
                    <i class="fas fa-signal isp-margin-right-1"></i>
                    {$u_act_pct}% {Lang::T('Active')} {if $u_expired > 0}• {$u_expired} {Lang::T('Expired')}{/if}
                </p>
            </div>
            <div class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-green-light isp-flex isp-align-center isp-justify-center">
                <i class="fas fa-wifi isp-text-green"></i>
            </div>
        </div>
    </div>

    <!-- Card 3: Monthly Revenue -->
    <div class="isp-stat-card isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between">
            <div id="monthly-revenue-content">
                <p class="isp-text-sm isp-font-medium isp-text-gray-dark">{Lang::T('Monthly Revenue')}</p>
                <p class="isp-text-2xl isp-font-bold isp-margin-top-1">{Lang::moneyFormat($imonth)}</p>
                <p class="isp-text-sm {if $rev_percentage_change >= 0}isp-text-green{else}isp-text-red{/if} isp-margin-top-1 isp-flex isp-align-center">
                    <i class="fas {if $rev_percentage_change >= 0}fa-arrow-up{else}fa-arrow-down{/if} isp-margin-right-1"></i>
                    {$rev_percentage_change}% {Lang::T('from last month')}
                </p>
            </div>
            <div class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-purple-light isp-flex isp-align-center isp-justify-center">
                <i class="fas fa-dollar-sign isp-text-purple"></i>
            </div>
        </div>
    </div>

    <!-- Card 4: Today Sales -->
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
            <div class="isp-width-12 isp-height-12 isp-rounded-full isp-bg-yellow-light isp-flex isp-align-center isp-justify-center">
                <i class="fas fa-coins isp-text-yellow"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main Charts Row -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6 isp-margin-bottom-6">
    <!-- Sales Report Chart -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="sales-report-title" class="isp-text-lg isp-font-semibold">{Lang::T('Sales Report')}</h2>
            <div class="isp-flex isp-space-x-2" id="sales-timeframe-btns">
                <button type="button" class="isp-button isp-button-day active" data-timeframe="day">{Lang::T('Day')}</button>
                <button type="button" class="isp-button isp-button-secondary" data-timeframe="week">{Lang::T('Week')}</button>
                <button type="button" class="isp-button isp-button-secondary" data-timeframe="month">{Lang::T('Month')}</button>
                <button type="button" class="isp-button isp-button-secondary" data-timeframe="year">{Lang::T('Year')}</button>
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
                <button type="button" class="isp-button isp-button-day active" data-filter="all">{Lang::T('All')}</button>
                <button type="button" class="isp-button isp-button-secondary" data-filter="new">{Lang::T('New')}</button>
                <button type="button" class="isp-button isp-button-secondary" data-filter="churned">{Lang::T('Churned')}</button>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="customersChart"></canvas>
        </div>
    </div>
</div>

<!-- Second Charts Row -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6 isp-margin-bottom-6">
    <!-- Network Status Chart (Routers + Radius Server & NAS) -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <div class="isp-flex isp-align-center" style="gap: 8px;">
                <h2 id="network-status-title" class="isp-text-lg isp-font-semibold">{Lang::T('Network Status')}</h2>
                {if $radius_enabled}
                    <span class="badge" style="font-size: 11px; padding: 2px 8px; border-radius: 9999px; font-weight: 500; {if $radius_online}background: rgba(72, 187, 120, 0.15); color: #48bb78;{else}background: rgba(245, 101, 101, 0.15); color: #f56565;{/if}" title="FreeRADIUS AAA Engine">
                        <i class="fa fa-server" style="margin-right: 4px;"></i>RADIUS {if $radius_online}{Lang::T('Online')}{else}{Lang::T('Offline')}{/if}
                    </span>
                {/if}
            </div>
            <div class="isp-flex isp-align-center" style="gap: 6px;">
                <span class="isp-status-indicator {if $offline_network_nodes == 0}isp-status-online{else}isp-status-warning{/if}"></span>
                <span id="network-status-uptime" class="isp-text-xs">{$network_uptime}% {Lang::T('uptime')}</span>
            </div>
        </div>
        <div class="isp-chart-container" style="position: relative;">
            <canvas id="networkChart"></canvas>
        </div>

        <div style="margin-top: 14px; border-top: 1px solid var(--border-color, rgba(0,0,0,0.06)); padding-top: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                <div class="isp-flex isp-space-x-1" id="network-tab-btns">
                    <button type="button" class="net-tab-btn active" data-tab="routers">
                        <i class="fa fa-sitemap" style="margin-right: 4px;"></i>{Lang::T('Routers')} ({$routers_total})
                    </button>
                    {if $radius_enabled}
                        <button type="button" class="net-tab-btn" data-tab="radius">
                            <i class="fa fa-server" style="margin-right: 4px;"></i>{Lang::T('Radius')} ({if $radius_nas_count > 0}{$radius_nas_count} NAS{else}Server{/if})
                        </button>
                    {/if}
                    <button type="button" class="net-tab-btn" data-tab="all">
                        {Lang::T('All Nodes')} ({$total_network_nodes})
                    </button>
                </div>
                <div id="network-action-link">
                    <a href="{Text::url('routers')}" id="net-manage-link" data-routers-url="{Text::url('routers')}" data-radius-url="{Text::url('radius/nas-list')}" style="font-size: 11px; color: #4299e1; text-decoration: none; font-weight: 500;">{Lang::T('Manage Routers')} &rarr;</a>
                </div>
            </div>

            <!-- Tab 1: Routers List -->
            <div id="net-tab-routers" class="net-tab-content">
                {if !empty($routers_list)}
                    <div style="display: flex; flex-direction: column; gap: 6px; max-height: 120px; overflow-y: auto;">
                        {foreach $routers_list as $r}
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; border-radius: 6px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.04);">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="isp-status-indicator {if strcasecmp($r['status'], 'online') == 0}isp-status-online{else}isp-status-warning{/if}"></span>
                                    <div>
                                        <strong style="font-size: 12px;">{$r['name']}</strong>
                                        {if !empty($r['ip_address'])}
                                            <span style="font-size: 11px; opacity: 0.6; margin-left: 6px;">{$r['ip_address']}</span>
                                        {/if}
                                    </div>
                                </div>
                                <span class="badge {if strcasecmp($r['status'], 'online') == 0}badge-success{else}badge-danger{/if}" style="font-size: 10px; padding: 2px 8px; font-weight: 500;">
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
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; border-radius: 6px; background: rgba(66, 153, 225, 0.04); border: 1px solid rgba(66, 153, 225, 0.12); margin-bottom: 6px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="isp-status-indicator {if $radius_online}isp-status-online{else}isp-status-warning{/if}"></span>
                        <div>
                            <strong style="font-size: 12px;">FreeRADIUS AAA Engine</strong>
                            <span style="font-size: 11px; opacity: 0.6; margin-left: 6px;">{$radius_host}</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        {if $radius_active_sessions > 0}
                            <span class="badge" style="font-size: 10px; padding: 2px 6px; background: rgba(66, 153, 225, 0.15); color: #3182ce;">
                                <i class="fa fa-users"></i> {$radius_active_sessions} {Lang::T('Sessions')}
                            </span>
                        {/if}
                        <span class="badge {if $radius_online}badge-success{else}badge-danger{/if}" style="font-size: 10px; padding: 2px 8px; font-weight: 500;">
                            {if $radius_online}Online{else}Offline{/if}
                        </span>
                    </div>
                </div>

                <!-- Radius NAS Clients List -->
                {if !empty($radius_nas_list)}
                    <div style="display: flex; flex-direction: column; gap: 6px; max-height: 100px; overflow-y: auto;">
                        {foreach $radius_nas_list as $nas}
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 5px 10px; border-radius: 6px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.04);">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="isp-status-indicator {if strcasecmp($nas['status'], 'online') == 0}isp-status-online{else}isp-status-warning{/if}"></span>
                                    <div>
                                        <strong style="font-size: 12px;">{$nas['shortname']}</strong>
                                        <span style="font-size: 11px; opacity: 0.6; margin-left: 6px;">{$nas['nasname']}</span>
                                        {if !empty($nas['routers'])}
                                            <span style="font-size: 10px; opacity: 0.5; margin-left: 4px;">({$nas['routers']})</span>
                                        {/if}
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span class="badge" style="font-size: 9px; padding: 1px 5px; opacity: 0.7; background: rgba(0,0,0,0.05);">{$nas['type']}</span>
                                    <span class="badge {if strcasecmp($nas['status'], 'online') == 0}badge-success{else}badge-danger{/if}" style="font-size: 10px; padding: 2px 8px; font-weight: 500;">
                                        {$nas['status']}
                                    </span>
                                </div>
                            </div>
                        {/foreach}
                    </div>
                {else}
                    <div style="padding: 8px 10px; font-size: 11px; opacity: 0.7; text-align: center; background: rgba(0,0,0,0.02); border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                        <span>{Lang::T('No Radius NAS configured yet.')}</span>
                        <a href="{Text::url('radius/nas-add')}" class="btn btn-xs btn-primary" style="font-size: 10px; padding: 2px 8px;">+ {Lang::T('Add NAS')}</a>
                    </div>
                {/if}
            </div>
            {/if}

            <!-- Tab 3: All Nodes -->
            <div id="net-tab-all" class="net-tab-content" style="display: none;">
                <div style="display: flex; flex-direction: column; gap: 6px; max-height: 120px; overflow-y: auto;">
                    {if $radius_enabled}
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 5px 10px; border-radius: 6px; background: rgba(66, 153, 225, 0.04); border: 1px solid rgba(66, 153, 225, 0.12);">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="isp-status-indicator {if $radius_online}isp-status-online{else}isp-status-warning{/if}"></span>
                                <div>
                                    <strong style="font-size: 12px;">Radius Server</strong>
                                    <span style="font-size: 11px; opacity: 0.6; margin-left: 6px;">{$radius_host}</span>
                                </div>
                            </div>
                            <span class="badge {if $radius_online}badge-success{else}badge-danger{/if}" style="font-size: 10px; padding: 2px 8px; font-weight: 500;">
                                {if $radius_online}Online{else}Offline{/if}
                            </span>
                        </div>
                    {/if}
                    {foreach $routers_list as $r}
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 5px 10px; border-radius: 6px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.04);">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="isp-status-indicator {if strcasecmp($r['status'], 'online') == 0}isp-status-online{else}isp-status-warning{/if}"></span>
                                <div>
                                    <strong style="font-size: 12px;">[Router] {$r['name']}</strong>
                                    {if !empty($r['ip_address'])}
                                        <span style="font-size: 11px; opacity: 0.6; margin-left: 6px;">{$r['ip_address']}</span>
                                    {/if}
                                </div>
                            </div>
                            <span class="badge {if strcasecmp($r['status'], 'online') == 0}badge-success{else}badge-danger{/if}" style="font-size: 10px; padding: 2px 8px; font-weight: 500;">
                                {$r['status']}
                            </span>
                        </div>
                    {/foreach}
                    {if !empty($radius_nas_list)}
                        {foreach $radius_nas_list as $nas}
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 5px 10px; border-radius: 6px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.04);">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="isp-status-indicator {if strcasecmp($nas['status'], 'online') == 0}isp-status-online{else}isp-status-warning{/if}"></span>
                                    <div>
                                        <strong style="font-size: 12px;">[NAS] {$nas['shortname']}</strong>
                                        <span style="font-size: 11px; opacity: 0.6; margin-left: 6px;">{$nas['nasname']}</span>
                                    </div>
                                </div>
                                <span class="badge {if strcasecmp($nas['status'], 'online') == 0}badge-success{else}badge-danger{/if}" style="font-size: 10px; padding: 2px 8px; font-weight: 500;">
                                    {$nas['status']}
                                </span>
                            </div>
                        {/foreach}
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
                <button type="button" class="isp-button isp-button-day active" data-metric="totals">{Lang::T('Revenue')}</button>
                <button type="button" class="isp-button isp-button-secondary" data-metric="counts">{Lang::T('Count')}</button>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="ticketsChart"></canvas>
        </div>
    </div>
</div>

<!-- Third Row with smaller charts -->
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

<!-- Fourth Row: Top Selling Plans & Hourly Activity -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6">
    <!-- Top Selling Plans -->
    <div class="isp-card isp-padding-6 isp-rounded-lg">
        <div class="isp-flex isp-align-center isp-justify-between isp-margin-bottom-4">
            <h2 id="top-plans-title" class="isp-text-lg isp-font-semibold">{Lang::T('Top Selling Plans')}</h2>
            <div class="isp-flex isp-space-x-2" id="topplans-metric-btns">
                <button type="button" class="isp-button isp-button-day active" data-metric="counts">{Lang::T('Count')}</button>
                <button type="button" class="isp-button isp-button-secondary" data-metric="totals">{Lang::T('Revenue')}</button>
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
                <span id="hourly-peak-time" class="isp-text-xs"><i class="fas fa-clock isp-margin-right-1"></i>Peak: {$peak_hour_str}</span>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="latencyChart"></canvas>
        </div>
    </div>
</div>

<!-- Fifth Row: Peak Usage & System Inventory -->
<div class="isp-grid isp-grid-cols-1 isp-grid-cols-lg-2 isp-gap-6 isp-margin-top-6">
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
                    <span class="isp-status-indicator {if $routers_offline == 0}isp-status-online{else}isp-status-warning{/if} isp-margin-right-1"></span>
                    {$network_uptime}% {Lang::T('Online')}
                </span>
            </div>
        </div>
        <div class="isp-chart-container">
            <canvas id="equipmentChart"></canvas>
        </div>
    </div>
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
                            netManageLink.innerHTML = 'Manage Radius NAS &rarr;';
                            netManageLink.href = netManageLink.getAttribute('data-radius-url');
                        } else {
                            netManageLink.innerHTML = 'Manage Routers &rarr;';
                            netManageLink.href = netManageLink.getAttribute('data-routers-url');
                        }
                    }
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

{if $_c['new_version_notify'] != 'disable'}
<script>
    window.addEventListener('DOMContentLoaded', function () {
        $.getJSON("./version.json?" + Math.random(), function (data) {
            var localVersion = data.version;
            $('#version').html('Version: ' + localVersion);
            $.getJSON(
                "https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/version.json?" + Math.random(),
                function (data) {
                    var latestVersion = data.version;
                    if (localVersion !== latestVersion) {
                        $('#version').html('Latest Version: ' + latestVersion);
                        if (getCookie(latestVersion) != 'done') {
                            Swal.fire({
                                icon: 'info',
                                title: "New Version Available\nVersion: " + latestVersion,
                                toast: true,
                                position: 'bottom-right',
                                showConfirmButton: true,
                                showCloseButton: true,
                                timer: 30000,
                                confirmButtonText: '<a href="{Text::url('community')}#latestVersion" style="color: white;">Update Now</a>',
                                timerProgressBar: true,
                                didOpen: (toast) => {
                                    toast.addEventListener('mouseenter', Swal.stopTimer);
                                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                                }
                            });
                            setCookie(latestVersion, 'done', 7);
                        }
                    }
                });
        });
    });
</script>
{/if}

{include file="sections/footer.tpl"}