{include file="sections/header.tpl"}

<div class="ls">
    <div class="ls-top">
        <div>
            <p class="ls-sub">{$total_customers|number_format} {Lang::T('customers')}{if $filter neq 'All'}, {$status_counts[$filter]|default:0|number_format} {Lang::T($filter)}{/if}</p>
        </div>
        <div class="ls-actions">
            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
            <a class="ls-btn" title="{Lang::T('This will export to CSV')}" href="{Text::url('customers/csv&token=', $csrf_token)}"
                onclick="return ask(this, '{Lang::T('This will export to CSV')}?')"><i class="fa fa-download" aria-hidden="true"></i> CSV</a>
            {/if}
            <a href="{Text::url('customers/add')}" class="ls-btn ls-btn--primary"><i class="fa fa-user-plus" aria-hidden="true"></i> {Lang::T('Add')}</a>
        </div>
    </div>

    <div class="ls-tabs" role="tablist" aria-label="{Lang::T('Status')}">
        {$tabUrl = "&order={$order|escape:'url'}&orderby={$orderby|escape:'url'}&search={$search|escape:'url'}&filter="}
        <a class="ls-tab{if $filter eq 'All'} is-on{/if}" href="{Text::url('customers', $tabUrl, 'All')}" role="tab" aria-selected="{if $filter eq 'All'}true{else}false{/if}">{Lang::T('All')} <small>{$total_customers|number_format}</small></a>
        {foreach $statuses as $status}
        <a class="ls-tab{if $filter eq $status} is-on{/if}" href="{Text::url('customers', $tabUrl, $status|escape:'url')}" role="tab" aria-selected="{if $filter eq $status}true{else}false{/if}">{Lang::T($status)} <small>{$status_counts[$status]|default:0|number_format}</small></a>
        {/foreach}
    </div>

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="post" action="{Text::url('customers')}">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <input type="hidden" name="filter" value="{$filter|escape}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="search" placeholder="{Lang::T('Search')}..." value="{$search|escape}" aria-label="{Lang::T('Search')}">
            </div>
            <label class="ls-field"><span>{Lang::T('Order ')}</span>
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
            <label class="ls-field"><span>{Lang::T('Show')}</span>
                <select id="per_page" name="per_page" onchange="changePerPage(this)">
                    {foreach $per_page_options as $n}
                    <option value="{$n}" {if $cookie eq $n}selected{/if}>{$n}</option>
                    {/foreach}
                </select>
            </label>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            <button class="ls-btn" type="submit" name="export" value="csv" title="{Lang::T('This will export to CSV')}"><i class="fa fa-download" aria-hidden="true"></i> CSV</button>
        </form>

        <div class="ls-bulk" id="clBulk" hidden>
            <span><b id="clBulkN">0</b> {Lang::T('selected')}</span>
            <button type="button" id="sendMessageToSelected" class="ls-btn ls-btn--primary"><i class="fa fa-paper-plane" aria-hidden="true"></i> {Lang::T('Send Message')}</button>
        </div>

        <div class="ls-scroll">
            <table id="customerTable" class="ls-table ls-click table-with-loader">
                <thead>
                    <tr>
                        <th class="ls-check"><input type="checkbox" id="select-all" aria-label="{Lang::T('Select all')}"></th>
                        <th>{Lang::T('Full Name')}</th>
                        <th>{Lang::T('Contact')}</th>
                        <th>{Lang::T('Package')}</th>
                        <th>{Lang::T('Service Type')}</th>
                        <th class="ls-num">{Lang::T('Balance')}</th>
                        <th>{Lang::T('Status')}</th>
                        <th>{Lang::T('Created On')}</th>
                        <th class="ls-end">{Lang::T('Manage')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $d as $ds}
                    <tr>
                        <td class="ls-check"><input type="checkbox" name="customer_ids[]" value="{$ds['id']}" aria-label="{$ds['username']|escape}"></td>
                        <td data-label="{Lang::T('Full Name')}">
                            <a class="ls-who" href="{Text::url('customers/view/', $ds['id'])}">
                                {if empty($ds['photo']) || strpos($ds['photo'], 'default') !== false}
                                <img class="customer-avatar-circle img-circle" src="{$app_url}/{$UPLOAD_PATH}/user.default.jpg" width="38" height="38" alt="">
                                {else}
                                <img class="customer-avatar-circle img-circle" src="{$app_url}/{$UPLOAD_PATH}{$ds['photo']}.thumb.jpg"
                                    onerror="this.src='{$app_url}/{$UPLOAD_PATH}/user.default.jpg'" width="38" height="38" alt="">
                                {/if}
                                <span>
                                    <b>{$ds['fullname']|escape}</b>
                                    <small>{$ds['username']|escape}{if $ds['account_type']} <em class="ls-chip">{$ds['account_type']|escape}</em>{/if}</small>
                                </span>
                            </a>
                        </td>
                        <td data-label="{Lang::T('Contact')}" class="ls-contact">
                            {if strlen(preg_replace('/\D/', '', $ds['phonenumber'])) >= 5}<a href="tel:{$ds['phonenumber']|escape}" title="{Lang::T('Phone Number')}"><i class="fa fa-phone" aria-hidden="true"></i> {$ds['phonenumber']|escape}</a>{/if}
                            {if strpos($ds['email'], '@') !== false}<a href="mailto:{$ds['email']|escape}" title="{$ds['email']|escape}"><i class="fa fa-envelope" aria-hidden="true"></i> {$ds['email']|escape}</a>{/if}
                            {if $ds['coordinates']}<a href="https://www.google.com/maps/dir//{$ds['coordinates']|escape:'url'}/" target="_blank" rel="noopener" title="{$ds['coordinates']|escape}"><i class="fa fa-map-marker" aria-hidden="true"></i> {Lang::T('Map')}</a>{/if}
                        </td>
                        <td data-label="{Lang::T('Package')}" class="ls-pkg" api-get-text="{Text::url('autoload/plan_is_active/')}{$ds['id']}"><span class="ls-dim">&bull;</span></td>
                        <td data-label="{Lang::T('Service Type')}">
                            <span>{$ds['service_type']|escape}
                            {if !empty($ds['pppoe_username']) || !empty($ds['pppoe_ip'])}<small class="ls-dim">{$ds['pppoe_username']|escape}{if !empty($ds['pppoe_username']) && !empty($ds['pppoe_ip'])}:{/if}{$ds['pppoe_ip']|escape}</small>{/if}</span>
                        </td>
                        <td data-label="{Lang::T('Balance')}" class="ls-num">{Lang::moneyFormat($ds['balance'])}</td>
                        <td data-label="{Lang::T('Status')}"><span class="ls-pill ls-pill--{$ds['status']|lower|escape}">{Lang::T($ds['status'])}</span></td>
                        <td data-label="{Lang::T('Created On')}" class="ls-dim ls-when" title="{Lang::dateTimeFormat($ds['created_at'])}">{Lang::dateFormat($ds['created_at'])}</td>
                        <td class="ls-end">
                            <div class="ls-row-actions">
                                <a href="{Text::url('customers/view/')}{$ds['id']}" class="ls-btn ls-btn--sm">{Lang::T('View')}</a>
                                <a href="{Text::url('plan/recharge/', $ds['id'], '&token=', $csrf_token)}" class="ls-btn ls-btn--sm ls-btn--primary">{Lang::T('Recharge')}</a>
                                <span class="dropdown">
                                    <button type="button" class="ls-btn ls-btn--sm ls-btn--icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{Lang::T('Manage')}"><i class="fa fa-ellipsis-h" aria-hidden="true"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-right ls-menu">
                                        <li><a href="{Text::url('customers/edit/', $ds['id'], '&token=', $csrf_token)}"><i class="fa fa-pencil" aria-hidden="true"></i> {Lang::T('Edit')}</a></li>
                                        <li><a href="{Text::url('customers/sync/', $ds['id'], '&token=', $csrf_token)}"><i class="fa fa-refresh" aria-hidden="true"></i> {Lang::T('Sync')}</a></li>
                                    </ul>
                                </span>
                            </div>
                        </td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="9">
                        <div class="ls-empty"><b>{Lang::T('No customers found')}</b>
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
<div id="sendMessageModal" class="modal fade cm-modal" tabindex="-1" role="dialog" aria-labelledby="sendMessageModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="cm-head">
                <span class="cm-ico"><i class="fa fa-paper-plane" aria-hidden="true"></i></span>
                <div class="cm-head__text">
                    <h5 class="cm-title" id="sendMessageModalLabel">{Lang::T('Send Message')}</h5>
                    <p class="cm-sub"><b id="cmCount">0</b> {Lang::T('customers selected')}</p>
                </div>
                <button type="button" class="cm-x" data-dismiss="modal" aria-label="{Lang::T('Close')}"><i class="fa fa-times" aria-hidden="true"></i></button>
            </div>
            <div class="cm-body">
                <fieldset class="cm-group">
                    <legend>{Lang::T('Send through')}</legend>
                    <div class="cm-chips" id="via">
                        <label class="cm-chip"><input type="checkbox" id="sms" name="sms" value="1"><span><i class="fa fa-mobile" aria-hidden="true"></i> {Lang::T('SMS')}</span></label>
                        <label class="cm-chip"><input type="checkbox" id="wa" name="wa" value="1"><span><i class="fa fa-comment" aria-hidden="true"></i> {Lang::T('WA')}</span></label>
                        <label class="cm-chip"><input type="checkbox" id="email" name="email" value="1"><span><i class="fa fa-envelope" aria-hidden="true"></i> {Lang::T('Email')}</span></label>
                        <label class="cm-chip"><input type="checkbox" id="inbox" name="inbox" value="1"><span><i class="fa fa-inbox" aria-hidden="true"></i> {Lang::T('Inbox')}</span></label>
                        <label class="cm-chip"><input type="checkbox" id="push" name="push" value="1"><span><i class="fa fa-bell" aria-hidden="true"></i> {Lang::T('Push')}</span></label>
                    </div>
                </fieldset>
                <div class="cm-field" id="cmSubjectBox" hidden>
                    <label for="subject-content">{Lang::T('Subject')}</label>
                    <input type="text" class="cm-input" id="subject-content" autocomplete="off"
                        placeholder="{Lang::T('Enter message subject here')}">
                </div>
                <div class="cm-field">
                    <label for="messageContent">{Lang::T('Message')}</label>
                    <textarea id="messageContent" class="cm-input" rows="5"
                        placeholder="{Lang::T('Enter your message here...')}"></textarea>
                    <small class="cm-hint"><span id="cmChars">0</span> {Lang::T('characters')}</small>
                </div>
            </div>
            <div class="cm-foot">
                <button type="button" class="ls-btn" data-dismiss="modal"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Close')}</button>
                <button type="button" id="sendMessageButton" class="ls-btn ls-btn--primary"><i class="fa fa-paper-plane" aria-hidden="true"></i> {Lang::T('Send Message')}</button>
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
        const subjectBox = document.getElementById('cmSubjectBox');
        const content = document.getElementById('messageContent');
        const chars = document.getElementById('cmChars');

        function toggleSubjectField() {
            if (emailCheckbox.checked || inboxCheckbox.checked || pushCheckbox.checked) {
                subjectBox.hidden = false;
                subjectInput.required = true;
            } else {
                subjectBox.hidden = true;
                subjectInput.required = false;
                subjectInput.value = '';
            }
        }
        content.addEventListener('input', function () { chars.textContent = content.value.length; });

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
                var a = tr.querySelector('.ls-who');
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

            $('#cmCount').text(selectedCustomerIds.length);
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
                    $('#messageContent').val('').trigger('input'); // Clear the message content
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
                    $('#sendMessageButton').prop('disabled', false).html('<i class="fa fa-paper-plane" aria-hidden="true"></i> ' + $('<div>').text('{Lang::T('Send Message')}').html());
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