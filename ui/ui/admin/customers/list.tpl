{include file="sections/header.tpl"}

<div class="cl">
    <div class="cl-top">
        <div>
            <p class="cl-sub">{$total_customers|number_format} {Lang::T('customers')}{if $filter neq 'All'}, {$status_counts[$filter]|default:0|number_format} {Lang::T($filter)}{/if}</p>
        </div>
        <div class="cl-actions">
            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
            <a class="cl-btn" title="{Lang::T('This will export to CSV')}" href="{Text::url('customers/csv&token=', $csrf_token)}"
                onclick="return ask(this, '{Lang::T('This will export to CSV')}?')"><i class="fa fa-download" aria-hidden="true"></i> CSV</a>
            {/if}
            <a href="{Text::url('customers/add')}" class="cl-btn cl-btn--primary"><i class="fa fa-user-plus" aria-hidden="true"></i> {Lang::T('Add')}</a>
        </div>
    </div>

    <div class="cl-tabs" role="tablist" aria-label="{Lang::T('Status')}">
        {$tabUrl = "&order={$order|escape:'url'}&orderby={$orderby|escape:'url'}&search={$search|escape:'url'}&filter="}
        <a class="cl-tab{if $filter eq 'All'} is-on{/if}" href="{Text::url('customers', $tabUrl, 'All')}" role="tab" aria-selected="{if $filter eq 'All'}true{else}false{/if}">{Lang::T('All')} <small>{$total_customers|number_format}</small></a>
        {foreach $statuses as $status}
        <a class="cl-tab{if $filter eq $status} is-on{/if}" href="{Text::url('customers', $tabUrl, $status|escape:'url')}" role="tab" aria-selected="{if $filter eq $status}true{else}false{/if}">{Lang::T($status)} <small>{$status_counts[$status]|default:0|number_format}</small></a>
        {/foreach}
    </div>

    <div class="cl-card">
        <form id="site-search" class="cl-bar" method="post" action="{Text::url('customers')}">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <input type="hidden" name="filter" value="{$filter|escape}">
            <div class="cl-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="search" placeholder="{Lang::T('Search')}..." value="{$search|escape}" aria-label="{Lang::T('Search')}">
            </div>
            <label class="cl-field"><span>{Lang::T('Order ')}</span>
                <select id="order" name="order" onchange="this.form.submit()">
                    <option value="username" {if $order eq 'username'}selected{/if}>{Lang::T('Username')}</option>
                    <option value="fullname" {if $order eq 'fullname'}selected{/if}>{Lang::T('First Name')}</option>
                    <option value="lastname" {if $order eq 'lastname'}selected{/if}>{Lang::T('Last Name')}</option>
                    <option value="created_at" {if $order eq 'created_at'}selected{/if}>{Lang::T('Created Date')}</option>
                    <option value="balance" {if $order eq 'balance'}selected{/if}>{Lang::T('Balance')}</option>
                    <option value="status" {if $order eq 'status'}selected{/if}>{Lang::T('Status')}</option>
                </select>
                <select id="orderby" name="orderby" onchange="this.form.submit()" aria-label="{Lang::T('Order ')}">
                    <option value="asc" {if $orderby eq 'asc'}selected{/if}>{Lang::T('Ascending')}</option>
                    <option value="desc" {if $orderby eq 'desc'}selected{/if}>{Lang::T('Descending')}</option>
                </select>
            </label>
            <label class="cl-field"><span>{Lang::T('Show')}</span>
                <select id="per_page" name="per_page" onchange="changePerPage(this)">
                    {foreach $per_page_options as $n}
                    <option value="{$n}" {if $cookie eq $n}selected{/if}>{$n}</option>
                    {/foreach}
                </select>
            </label>
            <button class="cl-btn cl-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            <button class="cl-btn" type="submit" name="export" value="csv" title="{Lang::T('This will export to CSV')}"><i class="fa fa-download" aria-hidden="true"></i> CSV</button>
        </form>

        <div class="cl-bulk" id="clBulk" hidden>
            <span><b id="clBulkN">0</b> {Lang::T('selected')}</span>
            <button type="button" id="sendMessageToSelected" class="cl-btn cl-btn--primary"><i class="fa fa-paper-plane" aria-hidden="true"></i> {Lang::T('Send Message')}</button>
        </div>

        <div class="cl-scroll">
            <table id="customerTable" class="cl-table cl-click table-with-loader">
                <thead>
                    <tr>
                        <th class="cl-check"><input type="checkbox" id="select-all" aria-label="{Lang::T('Select all')}"></th>
                        <th>{Lang::T('Full Name')}</th>
                        <th>{Lang::T('Contact')}</th>
                        <th>{Lang::T('Package')}</th>
                        <th>{Lang::T('Service Type')}</th>
                        <th class="cl-num">{Lang::T('Balance')}</th>
                        <th>{Lang::T('Status')}</th>
                        <th>{Lang::T('Created On')}</th>
                        <th class="cl-end">{Lang::T('Manage')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $d as $ds}
                    <tr>
                        <td class="cl-check"><input type="checkbox" name="customer_ids[]" value="{$ds['id']}" aria-label="{$ds['username']|escape}"></td>
                        <td data-label="{Lang::T('Full Name')}">
                            <a class="cl-who" href="{Text::url('customers/view/', $ds['id'])}">
                                {if empty($ds['photo']) || strpos($ds['photo'], 'default') !== false}
                                <img class="customer-avatar-circle img-circle" src="{$app_url}/{$UPLOAD_PATH}/user.default.jpg" width="38" height="38" alt="">
                                {else}
                                <img class="customer-avatar-circle img-circle" src="{$app_url}/{$UPLOAD_PATH}{$ds['photo']}.thumb.jpg"
                                    onerror="this.src='{$app_url}/{$UPLOAD_PATH}/user.default.jpg'" width="38" height="38" alt="">
                                {/if}
                                <span>
                                    <b>{$ds['fullname']|escape}</b>
                                    <small>{$ds['username']|escape}{if $ds['account_type']} <em class="cl-chip">{$ds['account_type']|escape}</em>{/if}</small>
                                </span>
                            </a>
                        </td>
                        <td data-label="{Lang::T('Contact')}" class="cl-contact">
                            {if strlen(preg_replace('/\D/', '', $ds['phonenumber'])) >= 5}<a href="tel:{$ds['phonenumber']|escape}" title="{Lang::T('Phone Number')}"><i class="fa fa-phone" aria-hidden="true"></i> {$ds['phonenumber']|escape}</a>{/if}
                            {if strpos($ds['email'], '@') !== false}<a href="mailto:{$ds['email']|escape}" title="{$ds['email']|escape}"><i class="fa fa-envelope" aria-hidden="true"></i> {$ds['email']|escape}</a>{/if}
                            {if $ds['coordinates']}<a href="https://www.google.com/maps/dir//{$ds['coordinates']|escape:'url'}/" target="_blank" rel="noopener" title="{$ds['coordinates']|escape}"><i class="fa fa-map-marker" aria-hidden="true"></i> {Lang::T('Map')}</a>{/if}
                        </td>
                        <td data-label="{Lang::T('Package')}" class="cl-pkg" api-get-text="{Text::url('autoload/plan_is_active/')}{$ds['id']}"><span class="cl-dim">&bull;</span></td>
                        <td data-label="{Lang::T('Service Type')}">
                            <span>{$ds['service_type']|escape}
                            {if !empty($ds['pppoe_username']) || !empty($ds['pppoe_ip'])}<small class="cl-dim">{$ds['pppoe_username']|escape}{if !empty($ds['pppoe_username']) && !empty($ds['pppoe_ip'])}:{/if}{$ds['pppoe_ip']|escape}</small>{/if}</span>
                        </td>
                        <td data-label="{Lang::T('Balance')}" class="cl-num">{Lang::moneyFormat($ds['balance'])}</td>
                        <td data-label="{Lang::T('Status')}"><span class="cl-pill cl-pill--{$ds['status']|lower|escape}">{Lang::T($ds['status'])}</span></td>
                        <td data-label="{Lang::T('Created On')}" class="cl-dim cl-when" title="{Lang::dateTimeFormat($ds['created_at'])}">{Lang::dateFormat($ds['created_at'])}</td>
                        <td class="cl-end">
                            <div class="cl-row-actions">
                                <a href="{Text::url('customers/view/')}{$ds['id']}" class="cl-btn cl-btn--sm">{Lang::T('View')}</a>
                                <a href="{Text::url('plan/recharge/', $ds['id'], '&token=', $csrf_token)}" class="cl-btn cl-btn--sm cl-btn--primary">{Lang::T('Recharge')}</a>
                                <span class="dropdown">
                                    <button type="button" class="cl-btn cl-btn--sm cl-btn--icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{Lang::T('Manage')}"><i class="fa fa-ellipsis-h" aria-hidden="true"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-right cl-menu">
                                        <li><a href="{Text::url('customers/edit/', $ds['id'], '&token=', $csrf_token)}"><i class="fa fa-pencil" aria-hidden="true"></i> {Lang::T('Edit')}</a></li>
                                        <li><a href="{Text::url('customers/sync/', $ds['id'], '&token=', $csrf_token)}"><i class="fa fa-refresh" aria-hidden="true"></i> {Lang::T('Sync')}</a></li>
                                    </ul>
                                </span>
                            </div>
                        </td>
                    </tr>
                    {foreachelse}
                    <tr class="cl-none"><td colspan="9">
                        <div class="cl-empty"><b>{Lang::T('No customers found')}</b>
                            {if $search neq ''}{Lang::T('Nothing matches your search in this status.')} <a href="{Text::url('customers', '&filter=All')}">{Lang::T('Search all statuses')}</a>
                            {else}<a href="{Text::url('customers/add')}">{Lang::T('Add')}</a>{/if}
                        </div>
                    </td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {include file="pagination.tpl"}
    </div>
</div>
<!-- Modal for Sending Messages -->
<div id="sendMessageModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="sendMessageModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sendMessageModalLabel">{Lang::T('Send Message')}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group" id="via" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center; margin-bottom: 15px;">
                    <label class="control-label" style="display: inline-flex; align-items: center; gap: 6px; margin: 0; cursor: pointer;"><input type="checkbox" id="sms" name="sms" value="1">
                        {Lang::T('SMS')}</label>
                    <label class="control-label" style="display: inline-flex; align-items: center; gap: 6px; margin: 0; cursor: pointer;"><input type="checkbox" id="wa" name="wa" value="1">
                        {Lang::T('WA')}</label>
                    <label class="control-label" style="display: inline-flex; align-items: center; gap: 6px; margin: 0; cursor: pointer;"><input type="checkbox" id="email" name="email" value="1">
                        {Lang::T('Email')}</label>
                    <label class="control-label" style="display: inline-flex; align-items: center; gap: 6px; margin: 0; cursor: pointer;"><input type="checkbox" id="inbox" name="inbox" value="1">
                        {Lang::T('Inbox')}</label>
                    <label class="control-label" style="display: inline-flex; align-items: center; gap: 6px; margin: 0; cursor: pointer;"><input type="checkbox" id="push" name="push" value="1">
                        {Lang::T('Push')}</label>
                </div>
                <input type="text" style="margin-bottom: 10px; display: none;" class="form-control" id="subject-content"
                    placeholder="{Lang::T('Enter message subject here')}">
                <textarea id="messageContent" class="form-control" rows="4"
                    placeholder="{Lang::T('Enter your message here...')}"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{Lang::T('Close')}</button>
                <button type="button" id="sendMessageButton" class="btn btn-primary">{Lang::T('Send Message')}</button>
            </div>
        </div>
    </div>
</div>
<script src="{$app_url}/ui/ui/scripts/jquery.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const emailCheckbox = document.getElementById('email');
        const inboxCheckbox = document.getElementById('inbox');
        const pushCheckbox = document.getElementById('push');
        const subjectInput = document.getElementById('subject-content');

        function toggleSubjectField() {
            if (emailCheckbox.checked || inboxCheckbox.checked || pushCheckbox.checked) {
                subjectInput.style.display = 'block';
                subjectInput.required = true;
            } else {
                subjectInput.style.display = 'none';
                subjectInput.required = false;
                subjectInput.value = '';
            }
        }

        emailCheckbox.addEventListener('change', toggleSubjectField);
        inboxCheckbox.addEventListener('change', toggleSubjectField);
        pushCheckbox.addEventListener('change', toggleSubjectField);

    });
</script>
<script>
    // Select or deselect all checkboxes
    (function () {
        var bulk = document.getElementById('clBulk'), n = document.getElementById('clBulkN'), all = document.getElementById('select-all');
        function boxes() { return document.querySelectorAll('input[name="customer_ids[]"]'); }
        function sync() {
            var c = 0, total = 0;
            boxes().forEach(function (b) { total++; if (b.checked) { c++; } });
            n.textContent = c;
            bulk.hidden = c === 0;
            all.checked = total > 0 && c === total;
            all.indeterminate = c > 0 && c < total;
        }
        all.addEventListener('change', function () {
            boxes().forEach(function (b) { b.checked = all.checked; });
            sync();
        });
        boxes().forEach(function (b) { b.addEventListener('change', sync); });
        // a click on the row (not on a link, a button or a box) opens the customer
        document.querySelectorAll('#customerTable tbody tr').forEach(function (tr) {
            tr.addEventListener('click', function (e) {
                if (e.target.closest('a, button, input, .dropdown, .label')) { return; }
                var a = tr.querySelector('.cl-who');
                if (a) { window.location.href = a.href; }
            });
        });
    })();

    $(document).ready(function () {
        // Move modal to body to prevent stacking context or transform trapping issues
        $('#sendMessageModal').appendTo('body');

        // Initialize variables
        let selectedCustomerIds = [];

        // Collect selected customer IDs when the button is clicked
        $('#sendMessageToSelected').on('click', function () {
            selectedCustomerIds = $('input[name="customer_ids[]"]:checked').map(function () {
                return $(this).val();
            }).get();

            if (selectedCustomerIds.length === 0) {
                Swal.fire({
                    title: 'Error!',
                    text: "{Lang::T('Please select at least one customer to send a message.')}",
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Open the modal
            $('#sendMessageModal').modal('show');
        });
        // Handle sending the message
        $('#sendMessageButton').on('click', function () {
            const message = $('#messageContent').val().trim();
            const sms = $('#sms').is(':checked') ? '1' : '0';
            const wa = $('#wa').is(':checked') ? '1' : '0';
            const email = $('#email').is(':checked') ? '1' : '0';
            const push = $('#push').is(':checked') ? '1' : '0';
            const inbox = $('#inbox').is(':checked') ? '1' : '0';
            const subject = $('#subject-content').val().trim();

            if (sms === '0' && wa === '0' && email === '0' && push === '0' && inbox === '0') {
                Swal.fire({
                    title: 'Error!',
                    text: "{Lang::T('Please select at least one channel type.')}",
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            if ((email === '1' || inbox === '1' || push === '1') && !subject) {
                Swal.fire({
                    title: 'Error!',
                    text: "{Lang::T('Please enter a subject for the message.')}",
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            if (!message) {
                Swal.fire({
                    title: 'Error!',
                    text: "{Lang::T('Please enter a message to send.')}",
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }



            // Disable the button and show loading text
            $(this).prop('disabled', true).text('{Lang::T('Sending...')}');

            $.ajax({
                url: '?_route=message/send_bulk_selected',
                method: 'POST',
                data: {
                    csrf_token: '{$csrf_token}',
                    customer_ids: selectedCustomerIds,
                    sms: sms,
                    wa: wa,
                    push: push,
                    email: email,
                    inbox: inbox,
                    subject: subject,
                    message: message
                },
                dataType: 'json',
                success: function (response) {
                    // Handle success response
                    if (response.status === 'success') {
                        const failed = response.totalFailed || 0, skipped = response.totalSkipped || 0;
                        Swal.fire({
                            title: failed > 0 ? 'Done, with problems' : 'Success!',
                            text: "{Lang::T('Message sent successfully.')}" + ' ' + response.totalSent + ' sent, ' + failed + ' failed, ' + skipped + ' skipped.',
                            icon: failed > 0 ? 'warning' : 'success',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: "{Lang::T('Error sending message: ')}" + response.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                    $('#sendMessageModal').modal('hide');
                    $('#messageContent').val(''); // Clear the message content
                },
                error: function () {
                    Swal.fire({
                        title: 'Error!',
                        text: "{Lang::T('The request ended before a result came back. Sending may still be running, so check the message logs before trying again.')}",
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                },
                complete: function () {
                    // Re-enable the button and reset text
                    $('#sendMessageButton').prop('disabled', false).text('{Lang::T('Send Message')}');
                }
            });
        });
    });

    $(document).ready(function () {
        $('#sendMessageModal').on('shown.bs.modal', function () {
            $('#messageContent').focus();
        });
    });
</script>
{include file = "sections/footer.tpl" }