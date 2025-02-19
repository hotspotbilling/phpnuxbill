{include file="sections/header.tpl"}
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div id="status" class="mb-3"></div>
        <div class="panel panel-primary panel-hovered panel-stacked mb30 {if $page>0 && $totalCustomers >0}hidden{/if}">
            <div class="panel-heading">{Lang::T('Send Bulk Message')}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="get" role="form" id="bulkMessageForm" action="">
                    <input type="hidden" name="page" value="{if $page>0 && $totalCustomers==0}-1{else}{$page}{/if}">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Router')}</label>
                        <div class="col-md-6">
                            <select class="form-control select2" name="router" id="router">
                                <option value="">{Lang::T('All Routers')}</option>
                                {foreach $routers as $router}
                                <option value="{$router['id']}">{$router['name']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Group')}</label>
                        <div class="col-md-6">
                            <select class="form-control" name="group" id="group">
                                <option value="all" {if $group=='all' }selected{/if}>{Lang::T('All Customers')}</option>
                                <option value="new" {if $group=='new' }selected{/if}>{Lang::T('New Customers')}</option>
                                <option value="expired" {if $group=='expired' }selected{/if}>{Lang::T('Expired Customers')}</option>
                                <option value="active" {if $group=='active' }selected{/if}>{Lang::T('Active Customers')}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Send Via')}</label>
                        <div class="col-md-6">
                            <select class="form-control" name="via" id="via">
                                <option value="sms" {if $via=='sms' }selected{/if}>{Lang::T('SMS')}</option>
                                <option value="wa" {if $via=='wa' }selected{/if}>{Lang::T('WhatsApp')}</option>
                                <option value="both" {if $via=='both' }selected{/if}>{Lang::T('SMS and WhatsApp')}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Message per time')}</label>
                        <div class="col-md-6">
                            <select class="form-control" name="batch" id="batch">
                                <option value="5" {if $batch=='5' }selected{/if}>{Lang::T('5 Messages')}</option>
                                <option value="10" {if $batch=='10' }selected{/if}>{Lang::T('10 Messages')}</option>
                                <option value="15" {if $batch=='15' }selected{/if}>{Lang::T('15 Messages')}</option>
                                <option value="20" {if $batch=='20' }selected{/if}>{Lang::T('20 Messages')}</option>
                                <option value="30" {if $batch=='30' }selected{/if}>{Lang::T('30 Messages')}</option>
                                <option value="40" {if $batch=='40' }selected{/if}>{Lang::T('40 Messages')}</option>
                                <option value="50" {if $batch=='50' }selected{/if}>{Lang::T('50 Messages')}</option>
                                <option value="60" {if $batch=='60' }selected{/if}>{Lang::T('60 Messages')}</option>
                            </select>
                            {Lang::T('Use 20 and above if you are sending to all customers to avoid server time out')}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Message')}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="message" name="message" required placeholder="{Lang::T('Compose your message...')}" rows="5">{$message}</textarea>
                            <input name="test" id="test" type="checkbox">
                            {Lang::T('Testing [if checked no real message is sent]')}
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('Use placeholders:')}
                            <br>
                            <b>[[name]]</b> - {Lang::T('Customer Name')}
                            <br>
                            <b>[[user_name]]</b> - {Lang::T('Customer Username')}
                            <br>
                            <b>[[phone]]</b> - {Lang::T('Customer Phone')}
                            <br>
                            <b>[[company_name]]</b> - {Lang::T('Your Company Name')}
                        </p>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button type="button" id="startBulk" class="btn btn-primary">{Lang::T('Start Bulk Messaging')}</button>
                            <a href="{Text::url('dashboard')}" class="btn btn-default">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add a Table for Sent History -->
<div class="panel panel-default">
    <div class="panel-heading">{Lang::T('Message Sending History')}</div>
    <div class="panel-body">
        <div id="status"></div>
        <table class="table table-bordered" id="historyTable">
            <thead>
                <tr>
                    <th>{Lang::T('Customer')}</th>
                    <th>{Lang::T('Phone')}</th>
                    <th>{Lang::T('Status')}</th>
                    <th>{Lang::T('Message')}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
{literal}
<script>
    let page = 0;
    let totalSent = 0;
    let totalFailed = 0;
    let hasMore = true;

    // Initialize DataTable
    let historyTable = $('#historyTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: true
    });

    function sendBatch() {
        if (!hasMore) return;

        $.ajax({
            url: '?_route=message/send_bulk_ajax',
            method: 'POST',
            data: {
                group: $('#group').val(),
                message: $('#message').val(),
                via: $('#via').val(),
                batch: $('#batch').val(),
                router: $('#router').val() || '',
                page: page,
                test: $('#test').is(':checked') ? 'on' : 'off'
            },
            dataType: 'json',
            beforeSend: function () {
                $('#status').html(`
                    <div class="alert alert-info">
                        <i class="fas fa-spinner fa-spin"></i> Sending batch ${page + 1}...
                    </div>
                `);
            },
            success: function (response) {
                console.log("Response received:", response);

                if (response && response.status === 'success') {
                    totalSent += response.totalSent || 0;
                    totalFailed += response.totalFailed || 0;
                    page = response.page || 0;
                    hasMore = response.hasMore || false;

                    $('#status').html(`
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Batch ${page} sent! (Total Sent: ${totalSent}, Failed: ${totalFailed})
                        </div>
                    `);

                    (response.batchStatus || []).forEach(msg => {
                        let statusClass = msg.status.includes('Failed') ? 'danger' : 'success';
                        historyTable.row.add([
                            msg.name,
                            msg.phone,
                            `<span class="text-${statusClass}">${msg.status}</span>`,
                            msg.message || 'No message'
                        ]).draw(false); // Add row without redrawing the table
                    });

                    if (hasMore) {
                        sendBatch();
                    } else {
                        $('#status').html(`
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> All batches sent! Total Sent: ${totalSent}, Failed: ${totalFailed}
                            </div>
                        `);
                    }
                } else {
                    console.error("Unexpected response format:", response);
                    $('#status').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> Error: Unexpected response format.
                        </div>
                    `);
                }
            },
            error: function () {
                $('#status').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> Error: Failed to send batch ${page + 1}.
                    </div>
                `);
            }
        });
    }

    // Start sending on button click
    $('#startBulk').on('click', function () {
        page = 0;
        totalSent = 0;
        totalFailed = 0;
        hasMore = true;
        $('#status').html('<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Starting bulk message sending...</div>');
        historyTable.clear().draw(); // Clear history table before starting
        sendBatch();
    });
</script>
{/literal}

{include file="sections/footer.tpl"}