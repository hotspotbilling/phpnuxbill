{include file="sections/header.tpl"}



<!-- TOP COMMAND BAR -->
<div class="row">
    <div class="col-xs-12">
        <div class="customer-view-header">
            <div class="customer-heading-meta">
                <a href="{Text::url('customers/list')}" class="btn-back-round" title="{Lang::T('Back')}">
                    <i class="fa fa-arrow-left"></i>
                </a>
                <div class="customer-title-block">
                    <h3 class="customer-name-heading">
                        <span>{$d['fullname']}</span>
                        {if $d['status'] == 'Active'}
                            <span class="c-badge c-badge-success"><span class="c-dot c-dot-success"></span> {Lang::T('Active')}</span>
                        {else}
                            <span class="c-badge c-badge-danger"><span class="c-dot c-dot-danger"></span> {Lang::T($d['status'])}</span>
                        {/if}
                    </h3>
                    <div class="customer-subtitle">
                        <span class="customer-sub-item"><i class="fa fa-user-circle-o"></i> @{$d['username']}</span>
                        {if $d['phonenumber']}
                            <span class="customer-sub-divider">•</span>
                            <span class="customer-sub-item"><i class="fa fa-phone"></i> {$d['phonenumber']}</span>
                        {/if}
                        {if $d['email']}
                            <span class="customer-sub-divider">•</span>
                            <span class="customer-sub-item"><i class="fa fa-envelope-o"></i> {$d['email']}</span>
                        {/if}
                    </div>
                </div>
            </div>

            <div class="customer-action-toolbar">
                <a href="{Text::url('customers/login/', $d['id'], '&token=', $csrf_token)}" target="_blank"
                    class="btn btn-info" title="{Lang::T('Login as Customer')}">
                    <i class="fa fa-external-link"></i> <span class="hidden-xs">{Lang::T('Login as Customer')}</span>
                </a>
                <a href="{Text::url('message/send/', $d['id'], '&token=', $csrf_token)}"
                    class="btn btn-primary" title="{Lang::T('Send Message')}">
                    <i class="fa fa-commenting-o"></i> <span class="hidden-xs">{Lang::T('Send Message')}</span>
                </a>
                <a href="{Text::url('customers/sync/', $d['id'], '&token=', $csrf_token)}"
                    onclick="return ask(this, '{Lang::T('This will sync Customer to Mikrotik')}?')"
                    class="btn btn-default" title="{Lang::T('Sync')}">
                    <i class="fa fa-refresh"></i> <span class="hidden-xs">{Lang::T('Sync')}</span>
                </a>
                <a href="{Text::url('customers/edit/', $d['id'], '&token=', $csrf_token)}"
                    class="btn btn-warning" title="{Lang::T('Edit')}">
                    <i class="fa fa-pencil"></i> <span class="hidden-xs">{Lang::T('Edit')}</span>
                </a>
                <a href="{Text::url('customers/delete/', $d['id'], '&token=', $csrf_token)}" id="{$d['id']}"
                    class="btn btn-danger"
                    onclick="return ask(this, '{Lang::T('Delete')}?')" title="{Lang::T('Delete')}">
                    <i class="fa fa-trash"></i> <span class="hidden-xs">{Lang::T('Delete')}</span>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- LEFT COLUMN: Profile Overview, Credentials, Contact & Location -->
    <div class="col-sm-12 col-md-4 col-lg-4">
        <!-- Profile & Overview Card -->
        <div class="cv-card">
            <div class="cv-card-body">
                <div class="profile-hero">
                    <div class="profile-avatar-wrap">
                        {if empty($d['photo']) || strpos($d['photo'], 'default') !== false}
                            <img class="profile-avatar-img"
                                src="{$app_url}/{$UPLOAD_PATH}/user.default.jpg"
                                alt="avatar">
                        {else}
                            <img class="profile-avatar-img"
                                onclick="window.location.href = '{$app_url}/{$UPLOAD_PATH}{$d['photo']}'"
                                src="{$app_url}/{$UPLOAD_PATH}{$d['photo']}.thumb.jpg"
                                onerror="this.src='{$app_url}/{$UPLOAD_PATH}/user.default.jpg'"
                                title="{Lang::T('Click to view full image')}" alt="avatar">
                        {/if}
                        <span class="profile-avatar-badge {if $d['status']=='Active'}c-dot-success{else}c-dot-danger{/if}"></span>
                    </div>
                    <h4 class="profile-hero-name">{$d['fullname']}</h4>
                    <span class="profile-username-tag">
                        <span>@{$d['username']}</span>
                        <button type="button" class="btn-tag-copy" onclick="copyText(this, '{$d['username']}')" title="{Lang::T('Copy Username')}">
                            <i class="fa fa-clone"></i>
                        </button>
                    </span>
                </div>

                <!-- 2x2 Metric Tile Grid -->
                <div class="metric-grid">
                    <div class="metric-tile">
                        <div class="metric-tile-label">{Lang::T('Balance')}</div>
                        <div class="metric-tile-val text-success">{Lang::moneyFormat($d['balance'])}</div>
                    </div>
                    <div class="metric-tile">
                        <div class="metric-tile-label">{Lang::T('Account Type')}</div>
                        <div class="metric-tile-val">{Lang::T($d['account_type'])}</div>
                    </div>
                    <div class="metric-tile">
                        <div class="metric-tile-label">{Lang::T('Service Type')}</div>
                        <div class="metric-tile-val">{Lang::T($d['service_type'])}</div>
                    </div>
                    <div class="metric-tile">
                        <div class="metric-tile-label">{Lang::T('Auto Renewal')}</div>
                        <div class="metric-tile-val">
                            {if $d['auto_renewal']}
                                <span class="text-success"><i class="fa fa-check-circle"></i> {Lang::T('Yes')}</span>
                            {else}
                                <span class="text-muted"><i class="fa fa-times-circle"></i> {Lang::T('No')}</span>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security & Network Credentials Card -->
        <div class="cv-card">
            <div class="cv-card-header">
                <h4 class="cv-card-title"><i class="fa fa-key text-warning"></i> {Lang::T('Credentials & Access')}</h4>
            </div>
            <div class="cv-card-body">
                <ul class="cv-list">
                    <li class="cv-list-item">
                        <span class="cv-list-label"><i class="fa fa-user"></i> {Lang::T('Username')}</span>
                        <span class="cv-list-val">
                            <span class="cred-box">
                                <span style="font-family: monospace; font-size: 12px; font-weight: 600;">{$d['username']}</span>
                                <button type="button" class="btn-cred-action" onclick="copyText(this, '{$d['username']}')" title="{Lang::T('Copy')}">
                                    <i class="fa fa-clone"></i>
                                </button>
                            </span>
                        </span>
                    </li>
                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li class="cv-list-item">
                            <span class="cv-list-label"><i class="fa fa-lock"></i> {Lang::T('Password')}</span>
                            <span class="cv-list-val">
                                <span class="cred-box">
                                    <input type="password" id="cust_pass" value="{$d['password']}" readonly class="cred-input">
                                    <button type="button" class="btn-cred-action" onclick="toggleSecret('cust_pass', this)" title="{Lang::T('Show/Hide')}">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn-cred-action" onclick="copyText(this, '{$d['password']}')" title="{Lang::T('Copy')}">
                                        <i class="fa fa-clone"></i>
                                    </button>
                                </span>
                            </span>
                        </li>
                    {/if}
                    {if $d['pppoe_username'] != ''}
                        <li class="cv-list-item">
                            <span class="cv-list-label"><i class="fa fa-plug"></i> PPPoE {Lang::T('Username')}</span>
                            <span class="cv-list-val">
                                <span class="cred-box">
                                    <span style="font-family: monospace; font-size: 12px; font-weight: 600;">{$d['pppoe_username']}</span>
                                    <button type="button" class="btn-cred-action" onclick="copyText(this, '{$d['pppoe_username']}')" title="{Lang::T('Copy')}">
                                        <i class="fa fa-clone"></i>
                                    </button>
                                </span>
                            </span>
                        </li>
                    {/if}
                    {if $d['pppoe_password'] != '' && in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li class="cv-list-item">
                            <span class="cv-list-label"><i class="fa fa-shield"></i> PPPoE {Lang::T('Password')}</span>
                            <span class="cv-list-val">
                                <span class="cred-box">
                                    <input type="password" id="pppoe_pass" value="{$d['pppoe_password']}" readonly class="cred-input">
                                    <button type="button" class="btn-cred-action" onclick="toggleSecret('pppoe_pass', this)" title="{Lang::T('Show/Hide')}">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn-cred-action" onclick="copyText(this, '{$d['pppoe_password']}')" title="{Lang::T('Copy')}">
                                        <i class="fa fa-clone"></i>
                                    </button>
                                </span>
                            </span>
                        </li>
                    {/if}
                    {if $d['pppoe_ip'] != ''}
                        <li class="cv-list-item">
                            <span class="cv-list-label"><i class="fa fa-sitemap"></i> {Lang::T('PPPoE Remote IP')}</span>
                            <span class="cv-list-val">
                                <span class="c-badge c-badge-info" style="font-family: monospace;">{$d['pppoe_ip']}</span>
                            </span>
                        </li>
                    {/if}
                </ul>
            </div>
        </div>

        <!-- Contact Information Card -->
        <div class="cv-card">
            <div class="cv-card-header">
                <h4 class="cv-card-title"><i class="fa fa-phone text-primary"></i> {Lang::T('Contact Details')}</h4>
            </div>
            <div class="cv-card-body">
                <ul class="cv-list">
                    <li class="cv-list-item">
                        <span class="cv-list-label"><i class="fa fa-phone"></i> {Lang::T('Phone Number')}</span>
                        <span class="cv-list-val">
                            {if $d['phonenumber']}
                                <a href="tel:{$d['phonenumber']}" style="color: inherit; text-decoration: none; font-weight: 600;">{$d['phonenumber']}</a>
                                <button type="button" class="btn-cred-action" onclick="copyText(this, '{$d['phonenumber']}')" title="{Lang::T('Copy')}">
                                    <i class="fa fa-clone"></i>
                                </button>
                            {else}
                                <span class="text-muted">-</span>
                            {/if}
                        </span>
                    </li>
                    <li class="cv-list-item">
                        <span class="cv-list-label"><i class="fa fa-envelope-o"></i> {Lang::T('Email')}</span>
                        <span class="cv-list-val">
                            {if $d['email']}
                                <a href="mailto:{$d['email']}" style="color: inherit; text-decoration: none; font-weight: 600;">{$d['email']}</a>
                                <button type="button" class="btn-cred-action" onclick="copyText(this, '{$d['email']}')" title="{Lang::T('Copy')}">
                                    <i class="fa fa-clone"></i>
                                </button>
                            {else}
                                <span class="text-muted">-</span>
                            {/if}
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Dedicated Address & Geographic Location Card -->
        <div class="cv-card">
            <div class="cv-card-header">
                <h4 class="cv-card-title"><i class="fa fa-map-marker text-danger"></i> {Lang::T('Address & Location')}</h4>
                <a href="{Text::url('customers/edit/', $d['id'], '&token=', $csrf_token)}" class="btn btn-xs btn-default" title="{Lang::T('Edit')}">
                    <i class="fa fa-pencil"></i> {Lang::T('Edit')}
                </a>
            </div>
            <div class="cv-card-body">
                <ul class="cv-list">
                    <li class="cv-list-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                        <span class="cv-list-label"><i class="fa fa-home"></i> {Lang::T('Address')}</span>
                        <span class="cv-list-val" style="text-align: left; font-weight: normal; color: var(--cv-fg); width: 100%;">
                            {if $d['address']}
                                {Lang::nl2br($d['address'])}
                            {else}
                                <span class="text-muted"><i class="fa fa-info-circle"></i> {Lang::T('No address provided')}</span>
                            {/if}
                        </span>
                    </li>
                    <li class="cv-list-item">
                        <span class="cv-list-label">{Lang::T('City')}</span>
                        <span class="cv-list-val">{if $d['city']}{$d['city']}{else}<span class="text-muted">-</span>{/if}</span>
                    </li>
                    <li class="cv-list-item">
                        <span class="cv-list-label">{Lang::T('District')}</span>
                        <span class="cv-list-val">{if $d['district']}{$d['district']}{else}<span class="text-muted">-</span>{/if}</span>
                    </li>
                    <li class="cv-list-item">
                        <span class="cv-list-label">{Lang::T('State')}</span>
                        <span class="cv-list-val">{if $d['state']}{$d['state']}{else}<span class="text-muted">-</span>{/if}</span>
                    </li>
                    <li class="cv-list-item">
                        <span class="cv-list-label">{Lang::T('Zip')}</span>
                        <span class="cv-list-val">{if $d['zip']}{$d['zip']}{else}<span class="text-muted">-</span>{/if}</span>
                    </li>
                    <li class="cv-list-item" style="flex-direction: column; align-items: stretch; gap: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px;">
                            <span class="cv-list-label"><i class="fa fa-compass text-danger"></i> {Lang::T('Coordinates')}</span>
                            {if $d['coordinates']}
                                <div style="display: flex; gap: 4px; align-items: center;">
                                    <span class="cred-box">
                                        <span style="font-family: monospace; font-size: 11px; font-weight: 600;">{$d['coordinates']}</span>
                                        <button type="button" class="btn-cred-action" onclick="copyText(this, '{$d['coordinates']}')" title="{Lang::T('Copy')}">
                                            <i class="fa fa-clone"></i>
                                        </button>
                                    </span>
                                    <a href="https://www.google.com/maps/dir//{$d['coordinates']}/" target="_blank"
                                        class="btn btn-xs btn-default" style="border-radius: 4px;" title="{Lang::T('Get Directions')}">
                                        <i class="fa fa-external-link"></i> <span class="hidden-xs">{Lang::T('Get Directions')}</span>
                                    </a>
                                </div>
                            {else}
                                <a href="{Text::url('customers/edit/', $d['id'], '&token=', $csrf_token)}" class="btn btn-xs btn-default" style="border-radius: 4px;">
                                    <i class="fa fa-map-marker text-primary"></i> {Lang::T('Set Location')}
                                </a>
                            {/if}
                        </div>
                        {if $d['coordinates']}
                            <div class="customer-map-wrapper">
                                <div id="map"></div>
                            </div>
                        {else}
                            <div style="padding: 14px; border: 1px dashed var(--cv-border); border-radius: 8px; text-align: center; background: var(--cv-accent);">
                                <i class="fa fa-map-o text-muted" style="font-size: 20px; margin-bottom: 4px; display: block;"></i>
                                <span class="text-muted" style="font-size: 12px;">{Lang::T('No geographic coordinates saved')}</span>
                            </div>
                        {/if}
                    </li>
                </ul>
            </div>
        </div>

        <!-- Custom Fields & Account Meta Card -->
        <div class="cv-card">
            <div class="cv-card-header">
                <h4 class="cv-card-title"><i class="fa fa-info-circle text-info"></i> {Lang::T('Account Details')}</h4>
            </div>
            <div class="cv-card-body">
                <ul class="cv-list">
                    <!-- Customers Attributes / Custom Fields -->
                    {if $customFields}
                        {foreach $customFields as $customField}
                            <li class="cv-list-item">
                                <span class="cv-list-label">{$customField.field_name}</span>
                                <span class="cv-list-val">
                                    {if strpos($customField.field_value, ':0') === false}
                                        {$customField.field_value}
                                    {else}
                                        <span class="c-badge c-badge-success">{Lang::T('Paid')}</span>
                                    {/if}
                                </span>
                            </li>
                        {/foreach}
                    {/if}
                    <li class="cv-list-item">
                        <span class="cv-list-label"><i class="fa fa-calendar-plus-o"></i> {Lang::T('Created On')}</span>
                        <span class="cv-list-val text-muted">{Lang::dateTimeFormat($d['created_at'])}</span>
                    </li>
                    <li class="cv-list-item">
                        <span class="cv-list-label"><i class="fa fa-sign-in"></i> {Lang::T('Last Login')}</span>
                        <span class="cv-list-val text-muted">
                            {if $d['last_login']}
                                {Lang::dateTimeFormat($d['last_login'])}
                            {else}
                                <span class="text-muted">-</span>
                            {/if}
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Subscribed Plans & History -->
    <div class="col-sm-12 col-md-8 col-lg-8">
        <!-- Subscribed Plans Section -->
        <div class="cv-card">
            <div class="cv-card-header">
                <h4 class="cv-card-title">
                    <i class="fa fa-cubes text-primary"></i> {Lang::T('Subscribed Plans')}
                    <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.3);">
                        {Lang::arrayCount($packages)}
                    </span>
                </h4>
            </div>
            <div class="cv-card-body">
                {if Lang::arrayCount($packages)}
                    {foreach $packages as $package}
                        <div class="subscription-card">
                            <div class="sub-header">
                                <div class="sub-title">
                                    <span style="font-size: 15px;">{$package['type']} - {$package['namebp']}</span>
                                    <span api-get-text="{Text::url('autoload/customer_is_active/')}{$package['username']}/{$package['plan_id']}"></span>
                                </div>
                                <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                                    {if $package['status']=='on'}
                                        <span class="c-badge c-badge-success"><span class="c-dot c-dot-success"></span> {Lang::T('Active')}</span>
                                    {else}
                                        <span class="c-badge c-badge-danger"><span class="c-dot c-dot-danger"></span> {Lang::T('Inactive')}</span>
                                    {/if}
                                    {if $package['prepaid'] eq 'yes'}
                                        <span class="c-badge c-badge-muted">Prepaid</span>
                                    {else}
                                        <span class="c-badge c-badge-info">{Lang::T('Postpaid')}</span>
                                    {/if}
                                </div>
                            </div>

                            <div class="sub-meta-grid">
                                <div class="sub-meta-item">
                                    <span class="sub-meta-item-label"><i class="fa fa-tachometer"></i> {Lang::T('Bandwidth')}</span>
                                    <span class="sub-meta-item-value">{$package['name_bw']}</span>
                                </div>
                                <div class="sub-meta-item">
                                    <span class="sub-meta-item-label"><i class="fa fa-server"></i> {Lang::T('Routers')} &amp; {Lang::T('Method')}</span>
                                    <span class="sub-meta-item-value">{$package['routers']} <span class="text-muted">({$package['method']})</span></span>
                                </div>
                                <div class="sub-meta-item">
                                    <span class="sub-meta-item-label"><i class="fa fa-calendar-check-o text-success"></i> {Lang::T('Created On')}</span>
                                    <span class="sub-meta-item-value">{Lang::dateAndTimeFormat($package['recharged_on'],$package['recharged_time'])}</span>
                                </div>
                                <div class="sub-meta-item">
                                    <span class="sub-meta-item-label"><i class="fa fa-calendar-times-o text-danger"></i> {Lang::T('Expires On')}</span>
                                    <span class="sub-meta-item-value text-danger">{Lang::dateAndTimeFormat($package['expiration'],$package['time'])}</span>
                                </div>
                            </div>

                            <div class="sub-actions">
                                <a href="{Text::url('customers/deactivate/', $d['id'],'/',$package['plan_id'], '&token=', $csrf_token)}"
                                    id="{$d['id']}" class="btn btn-sm btn-default" style="color: #dc2626;"
                                    onclick="return ask(this, '{Lang::T('This will deactivate Customer Plan, and make it expired')}')">
                                    <i class="fa fa-ban"></i> {Lang::T('Deactivate')}
                                </a>
                                <a href="{Text::url('customers/recharge/', $d['id'], '/', $package['plan_id'], '&token=', $csrf_token)}"
                                    class="btn btn-sm btn-success">
                                    <i class="fa fa-bolt"></i> {Lang::T('Recharge')}
                                </a>
                            </div>
                        </div>
                    {/foreach}
                {else}
                    <div class="cv-empty">
                        <i class="fa fa-cube cv-empty-icon"></i>
                        <p>{Lang::T('No active subscription plans found.')}</p>
                    </div>
                {/if}
            </div>
        </div>

        <!-- History Tabs Card -->
        <div class="cv-card">
            <div class="cv-tabs">
                <a href="{Text::url('customers/view/', $d['id'], '/activation')}"
                    class="cv-tab-item {if $v=='activation'}active{/if}">
                    <i class="fa fa-history"></i> 30 {Lang::T('Activation History')}
                </a>
                <a href="{Text::url('customers/view/', $d['id'], '/order')}"
                    class="cv-tab-item {if $v=='order'}active{/if}">
                    <i class="fa fa-shopping-cart"></i> 30 {Lang::T('Order History')}
                </a>
            </div>

            <div class="table-responsive" style="margin: 0; border: none;">
                {if $v == 'activation'}
                    {if Lang::arrayCount($activation)}
                        <table class="table cv-table cv-table-clickable">
                            <thead>
                                <tr>
                                    <th>{Lang::T('Invoice')}</th>
                                    <th>{Lang::T('Username')}</th>
                                    <th>{Lang::T('Plan Name')}</th>
                                    <th>{Lang::T('Plan Price')}</th>
                                    <th>{Lang::T('Type')}</th>
                                    <th>{Lang::T('Created On')}</th>
                                    <th>{Lang::T('Expires On')}</th>
                                    <th>{Lang::T('Method')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach $activation as $ds}
                                    <tr onclick="window.location.href = '{Text::url('plan/view/', $ds['id'])}'">
                                        <td><span class="c-badge c-badge-muted" style="font-family: monospace;">{$ds['invoice']}</span></td>
                                        <td style="font-weight: 500;">{$ds['username']}</td>
                                        <td><span class="text-primary" style="font-weight: 600;">{$ds['plan_name']}</span></td>
                                        <td style="font-weight: 700;">{Lang::moneyFormat($ds['price'])}</td>
                                        <td><span class="c-badge c-badge-info">{$ds['type']}</span></td>
                                        <td class="text-success" style="font-size: 12px;">
                                            {Lang::dateAndTimeFormat($ds['recharged_on'],$ds['recharged_time'])}
                                        </td>
                                        <td class="text-danger" style="font-size: 12px;">
                                            {Lang::dateAndTimeFormat($ds['expiration'],$ds['time'])}
                                        </td>
                                        <td><span class="c-badge c-badge-muted">{$ds['method']}</span></td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    {else}
                        <div class="cv-empty">
                            <i class="fa fa-history cv-empty-icon"></i>
                            <p>{Lang::T('No activation history records found.')}</p>
                        </div>
                    {/if}
                {/if}

                {if $v == 'order'}
                    {if Lang::arrayCount($order)}
                        <table class="table cv-table">
                            <thead>
                                <tr>
                                    <th>{Lang::T('Plan Name')}</th>
                                    <th>{Lang::T('Gateway')}</th>
                                    <th>{Lang::T('Routers')}</th>
                                    <th>{Lang::T('Type')}</th>
                                    <th>{Lang::T('Plan Price')}</th>
                                    <th>{Lang::T('Created On')}</th>
                                    <th>{Lang::T('Expires On')}</th>
                                    <th>{Lang::T('Date Done')}</th>
                                    <th>{Lang::T('Status')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach $order as $ds}
                                    <tr>
                                        <td style="font-weight: 600;">{$ds['plan_name']}</td>
                                        <td>{$ds['gateway']}</td>
                                        <td>{$ds['routers']}</td>
                                        <td><span class="c-badge c-badge-info">{$ds['payment_channel']}</span></td>
                                        <td style="font-weight: 700;">{Lang::moneyFormat($ds['price'])}</td>
                                        <td class="text-muted" style="font-size: 12px;">{Lang::dateTimeFormat($ds['created_date'])}</td>
                                        <td class="text-danger" style="font-size: 12px;">{Lang::dateTimeFormat($ds['expired_date'])}</td>
                                        <td class="text-success" style="font-size: 12px;">
                                            {if $ds['status']!=1}{Lang::dateTimeFormat($ds['paid_date'])}{else}-{/if}
                                        </td>
                                        <td>
                                            {if $ds['status']==1}
                                                <span class="c-badge c-badge-warning">{Lang::T('UNPAID')}</span>
                                            {elseif $ds['status']==2}
                                                <span class="c-badge c-badge-success">{Lang::T('PAID')}</span>
                                            {elseif $ds['status']==3}
                                                <span class="c-badge c-badge-danger">{$_L['FAILED']}</span>
                                            {elseif $ds['status']==4}
                                                <span class="c-badge c-badge-muted">{Lang::T('CANCELED')}</span>
                                            {elseif $ds['status']==5}
                                                <span class="c-badge c-badge-muted">{Lang::T('UNKNOWN')}</span>
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    {else}
                        <div class="cv-empty">
                            <i class="fa fa-shopping-cart cv-empty-icon"></i>
                            <p>{Lang::T('No order history records found.')}</p>
                        </div>
                    {/if}
                {/if}
            </div>

            <div style="padding: 12px 16px; border-top: 1px solid var(--cv-border);">
                {include file="pagination.tpl"}
            </div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT HELPERS -->
<script>
function copyText(btn, text) {
    if (!text) return;
    var original = btn.innerHTML;
    function showSuccess() {
        btn.innerHTML = '<i class="fa fa-check text-success"></i>';
        setTimeout(function() {
            btn.innerHTML = original;
        }, 1500);
    }
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(showSuccess).catch(function() {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
    function fallbackCopy(val) {
        var el = document.createElement('textarea');
        el.value = val;
        el.setAttribute('readonly', '');
        el.style.position = 'absolute';
        el.style.left = '-9999px';
        document.body.appendChild(el);
        el.select();
        try {
            document.execCommand('copy');
            showSuccess();
        } catch (err) {}
        document.body.removeChild(el);
    }
}

function toggleSecret(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<i class="fa fa-eye-slash"></i>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<i class="fa fa-eye"></i>';
    }
}
</script>

{if $d['coordinates']}
    {literal}
        <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
        <script>
            function initCustomerMap() {
                var mapEl = document.getElementById('map');
                if (!mapEl || typeof L === 'undefined') return;
                try {
                    var coords = [{/literal}{$d['coordinates']}{literal}];
                    var map = L.map('map', { scrollWheelZoom: false }).setView(coords, 16);
                    L.tileLayer('https://{s}.google.com/vt/lyrs=m&hl=en&x={x}&y={y}&z={z}&s=Ga', {
                        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                        maxZoom: 20
                    }).addTo(map);
                    L.marker(coords).addTo(map);
                    setTimeout(function() {
                        map.invalidateSize();
                    }, 400);
                } catch (e) {
                    console.error('Customer map initialization error:', e);
                }
            }
            if (document.readyState === 'complete') {
                initCustomerMap();
            } else {
                window.addEventListener('load', initCustomerMap);
            }
        </script>
    {/literal}
{/if}

{include file="sections/footer.tpl"}