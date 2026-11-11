{include file="sections/header.tpl"}
<link rel="stylesheet" type="text/css" href="{$app_url}/ui/ui/styles/jquery.dataTables.min.css">

<div class="ls mc">
    <form class="mc-grid" method="get" role="form" id="bulkMessageForm" action="" onsubmit="return false">
        <input type="hidden" id="csrf_token" value="{$csrf_token}">
        <div class="ls-card mc-main">
            <fieldset class="mc-block">
                <legend class="mc-label">{Lang::T('Audience')}</legend>
                <div class="mc-cols">
                    <div>
                        <label class="mc-sublabel" for="router">{Lang::T('Router')}</label>
                        <select style="width: 100%;" class="mc-input select2" name="router" id="router">
                            <option value="">{Lang::T('All Routers')}</option>
                            {if $_c['radius_enable']}
                            <option value="radius">{Lang::T('Radius')}</option>
                            {/if}
                            {foreach $routers as $router}
                            <option value="{$router['id']}">{$router['name']|escape}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div>
                        <label class="mc-sublabel" for="service">{Lang::T('Service Type')}</label>
                        <select class="mc-input" name="service" id="service">
                            <option value="all">{Lang::T('All')}</option>
                            <option value="PPPoE">{Lang::T('PPPoE')}</option>
                            <option value="Hotspot">{Lang::T('Hotspot')}</option>
                            <option value="VPN">{Lang::T('VPN')}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mc-sublabel" for="group">{Lang::T('Customers')}</label>
                        <select class="mc-input" name="group" id="group">
                            <option value="all">{Lang::T('All Customers')}</option>
                            <option value="active">{Lang::T('Active Customers')}</option>
                            <option value="expired">{Lang::T('Expired Customers')}</option>
                            <option value="new">{Lang::T('New Customers')}</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            {include file="admin/message/composer.tpl" mc_message=$message|default:""}

            <fieldset class="mc-block">
                <legend class="mc-label">{Lang::T('Delivery')}</legend>
                <div class="mc-rows">
                    <label class="mc-row mc-row--switch" for="test">
                        <input name="test" id="test" type="checkbox" checked>
                        <span class="mc-row__icon" aria-hidden="true"><i class="fa fa-flask"></i></span>
                        <span class="mc-row__text">
                            <b>{Lang::T('Test run')}</b>
                            <small class="mc-on">{Lang::T('Lists who would get the message without sending anything.')}</small>
                            <small class="mc-off">{Lang::T('Off: the message goes to real customers.')}</small>
                        </span>
                        <span class="mc-switch__track" aria-hidden="true"></span>
                    </label>
                    <div class="mc-row">
                        <span class="mc-row__icon" aria-hidden="true"><i class="fa fa-tachometer"></i></span>
                        <span class="mc-row__text">
                            <label for="batch"><b>{Lang::T('Messages per batch')}</b></label>
                            <small>{Lang::T('Use 20 and above if you are sending to many customers')}</small>
                        </span>
                        <select class="mc-input mc-row__control" name="batch" id="batch">
                            {foreach [5, 10, 15, 20, 30, 40, 50, 60, 100] as $b}
                            <option value="{$b}" {if $b == 20}selected{/if}>{$b}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
            </fieldset>

            <div class="mc-foot">
                <a href="{Text::url('dashboard')}" class="ls-btn"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                <button type="button" id="startBulk" class="ls-btn ls-btn--primary"><i class="fa fa-play" aria-hidden="true"></i> <span>{Lang::T('Start test run')}</span></button>
            </div>
        </div>

        <aside class="mc-side">
            {include file="admin/message/composer-side.tpl" mc_sample_name='Jane Doe'}
        </aside>
    </form>

    <div class="ls-card mc-run" id="mcRun" hidden>
        <div class="mc-run__head">
            <div class="mc-run__title">
                <i class="fa fa-circle-notch" id="mcRunIcon" aria-hidden="true"></i>
                <div><b id="mcRunTitle">{Lang::T('Sending')}</b><span id="status" role="status" aria-live="polite"></span></div>
            </div>
            <span class="ls-pill" id="mcRunMode"></span>
        </div>
        <div class="mc-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="mcBar"><span></span></div>
        <div class="mc-stats">
            <div class="mc-stat"><small>{Lang::T('Customers')}</small><b id="mcProcessed">0 / 0</b></div>
            <div class="mc-stat mc-stat--ok"><small>{Lang::T('Sent')}</small><b id="mcSent">0</b></div>
            <div class="mc-stat mc-stat--bad"><small>{Lang::T('Failed')}</small><b id="mcFailed">0</b></div>
            <div class="mc-stat"><small>{Lang::T('Skipped')}</small><b id="mcSkipped">0</b></div>
        </div>
    </div>

    <div class="ls-card mc-history">
        <div class="mc-side-head"><span><i class="fa fa-history" aria-hidden="true"></i> {Lang::T('Message Sending History')}</span></div>
        <div class="ls-scroll">
            <table class="ls-table" id="historyTable">
                <thead>
                    <tr>
                        <th>{Lang::T('Customer')}</th>
                        <th>{Lang::T('Sent To')}</th>
                        <th>{Lang::T('Channel')}</th>
                        <th>{Lang::T('Status')}</th>
                        <th>{Lang::T('Message')}</th>
                        <th>{Lang::T('Router')}</th>
                        <th>{Lang::T('Service Type')}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script src="{$app_url}/ui/ui/scripts/jquery.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/jquery.dataTables.min.js"></script>
<script>
    var MC_T = {
        test: '{Lang::T('Start test run')|escape:'javascript'}',
        real: '{Lang::T('Start sending')|escape:'javascript'}',
        testMode: '{Lang::T('Test run')|escape:'javascript'}',
        liveMode: '{Lang::T('Live')|escape:'javascript'}',
        starting: '{Lang::T('Starting')|escape:'javascript'}',
        sending: '{Lang::T('Sending batch')|escape:'javascript'}',
        done: '{Lang::T('Finished')|escape:'javascript'}',
        doneTest: '{Lang::T('Test run finished: nothing was sent')|escape:'javascript'}',
        failed: '{Lang::T('Stopped')|escape:'javascript'}',
        batchFailed: '{Lang::T('A batch could not be sent. Nothing after it was sent.')|escape:'javascript'}',
        unexpected: '{Lang::T('Unexpected answer from the server')|escape:'javascript'}',
        noChannel: '{Lang::T('Please select at least one channel type')|escape:'javascript'}',
        confirm: '{Lang::T('This sends the message to real customers. Turn on Test run first to preview who would get it.')|escape:'javascript'}',
        nobody: '{Lang::T('No customer matches this audience.')|escape:'javascript'}'
    };
</script>
{literal}
<script>
    let page = 0, totalSent = 0, totalFailed = 0, totalSkipped = 0, hasMore = true, isTest = true;

    let historyTable = $('#historyTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        order: [],
        language: { emptyTable: '—' }
    });

    // everything that comes back from the server is shown as text: customers type their own names
    function esc(v) {
        return String(v === null || v === undefined ? '' : v)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function setRun(state, title, text) {
        var run = document.getElementById('mcRun');
        run.hidden = false;
        run.className = 'ls-card mc-run is-' + state;
        var icon = { busy: 'fa-circle-notch fa-spin', ok: 'fa-check-circle', warn: 'fa-exclamation-triangle', bad: 'fa-times-circle' }[state];
        document.getElementById('mcRunIcon').className = 'fa ' + icon;
        document.getElementById('mcRunTitle').textContent = title;
        document.getElementById('status').textContent = text || '';
    }
    function setProgress(done, total) {
        var pct = total > 0 ? Math.round(done / total * 100) : 100;
        var bar = document.getElementById('mcBar');
        bar.setAttribute('aria-valuenow', pct);
        bar.firstElementChild.style.width = pct + '%';
        document.getElementById('mcProcessed').textContent = done + ' / ' + total;
        document.getElementById('mcSent').textContent = totalSent;
        document.getElementById('mcFailed').textContent = totalFailed;
        document.getElementById('mcSkipped').textContent = totalSkipped;
    }
    function finish() {
        $('#startBulk').prop('disabled', false);
        $('#bulkMessageForm').find('input, select, textarea, button.mc-token').prop('disabled', false);
    }

    function sendBatch() {
        if (!hasMore) return;
        $.ajax({
            url: '?_route=message/send_bulk_ajax',
            method: 'POST',
            data: {
                csrf_token: $('#csrf_token').val(),
                group: $('#group').val(),
                message: $('#message').val(),
                sms: $('#sms').is(':checked') ? '1' : '0',
                wa: $('#wa').is(':checked') ? '1' : '0',
                email: $('#email').is(':checked') ? '1' : '0',
                inbox: $('#inbox').is(':checked') ? '1' : '0',
                push: $('#push').is(':checked') ? '1' : '0',
                batch: $('#batch').val(),
                router: $('#router').val() || '',
                page: page,
                test: isTest ? 'on' : 'off',
                service: $('#service').val(),
                subject: $('#mcSubject').val(),
            },
            dataType: 'json',
            beforeSend: function () {
                setRun('busy', MC_T.sending + ' ' + (page + 1), '');
            },
            success: function (response) {
                if (!(response && response.status === 'success')) {
                    finish();
                    setRun('bad', MC_T.failed, (response && response.message) || MC_T.unexpected);
                    return;
                }
                totalSent += response.totalSent || 0;
                totalFailed += response.totalFailed || 0;
                totalSkipped += response.totalSkipped || 0;
                page = response.page || 0;
                hasMore = response.hasMore || false;
                setProgress(response.processed || 0, response.total || 0);

                (response.batchStatus || []).forEach(function (msg) {
                    const pill = msg.result === 'failed' ? 'ls-pill--banned' : (msg.result === 'sent' ? 'ls-pill--active' : '');
                    historyTable.row.add([
                        esc(msg.name || 'Unknown Customer'),
                        esc(msg.sent || '—'),
                        esc(msg.channel || ''),
                        '<span class="ls-pill ' + pill + '">' + esc(msg.status) + '</span>',
                        '<span class="mc-msg">' + esc(msg.message || '') + '</span>',
                        esc(msg.router || 'All Routers'),
                        esc(msg.service == 'all' ? 'All' : (msg.service || ''))
                    ]).draw(false);
                });

                if (hasMore) {
                    sendBatch();
                    return;
                }
                finish();
                if ((response.total || 0) === 0) {
                    setRun('warn', MC_T.done, MC_T.nobody);
                } else if (isTest) {
                    setRun('ok', MC_T.doneTest, '');
                } else {
                    setRun(totalFailed > 0 ? 'warn' : 'ok', MC_T.done, '');
                }
            },
            error: function () {
                finish();
                setRun('bad', MC_T.failed, MC_T.batchFailed);
            }
        });
    }

    function startBulkRun() {
        page = 0; totalSent = 0; totalFailed = 0; totalSkipped = 0; hasMore = true;
        isTest = $('#test').is(':checked');
        $('#startBulk').prop('disabled', true); // one run at a time
        $('#bulkMessageForm').find('input, select, textarea, button.mc-token').prop('disabled', true);
        document.getElementById('mcRunMode').textContent = isTest ? MC_T.testMode : MC_T.liveMode;
        document.getElementById('mcRunMode').className = 'ls-pill ' + (isTest ? '' : 'ls-pill--active');
        historyTable.clear().draw();
        setProgress(0, 0);
        setRun('busy', MC_T.starting, '');
        document.getElementById('mcRun').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        sendBatch();
    }

    function syncStartLabel() {
        $('#startBulk span').text($('#test').is(':checked') ? MC_T.test : MC_T.real);
        $('#startBulk i').attr('class', $('#test').is(':checked') ? 'fa fa-flask' : 'fa fa-paper-plane');
    }
    $('#test').on('change', syncStartLabel);
    syncStartLabel();

    $('#startBulk').on('click', function () {
        var form = document.getElementById('bulkMessageForm');
        if (!$('#via input:checked').length) {
            setRun('bad', MC_T.failed, MC_T.noChannel);
            return;
        }
        if (!form.reportValidity()) { return; }
        if ($('#test').is(':checked')) {
            startBulkRun(); // nothing is sent in test mode
        } else {
            mcConfirm(this, MC_T.confirm, startBulkRun);
        }
    });
</script>
{/literal}

{include file="sections/footer.tpl"}
