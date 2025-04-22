{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">Edit Plan</h3>
            </div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{Text::url('')}plan/edit-post">
                    <input type="hidden" name="id" value="{$d['id']}">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Select Account')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="username" name="username"
                                value="{$d['username']}" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Service Plan')}</label>
                        <div class="col-md-6">

                            <select id="id_plan" name="id_plan" class="form-control select2">
                                {foreach $p as $ps}
                                <option value="{$ps['id']}" {if $d['plan_id'] eq $ps['id']} selected {/if}>
                                    {if $ps['enabled'] neq 1}DISABLED PLAN &bull; {/if}
                                    {$ps['name_plan']} &bull;
                                    {Lang::moneyFormat($ps['price'])}
                                    {if $ps['prepaid'] neq 'yes'} &bull; POSTPAID {/if}
                                </option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Created On')}</label>
                        <div class="col-md-4">
                            <input type="date" class="form-control" readonly value="{$d['recharged_on']}">
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control" placeholder="00:00:00" readonly
                                value="{$d['recharged_time']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Expires On')}</label>
                        <div class="col-md-4">
                            <input type="date" class="form-control" id="expiration" name="expiration"
                                value="{$d['expiration']}">
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control" id="time" name="time" placeholder="00:00:00"
                                value="{$d['time']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Send Notification')}</label>
                        <div class="col-md-4">
                            <label class="switch">
                                <input type="checkbox" id="notify" value="1" name="notify">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group" id="method" style="display: none;">
                        <label class="col-md-2 control-label">{Lang::T('Notification via')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox" name="sms" value="1">
                            {Lang::T('SMS')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox" name="wa" value="1">
                            {Lang::T('WA')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox" name="mail" value="1">
                            {Lang::T('Email')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox" name="inbox" value="1">
                            {Lang::T('Inbox')}</label>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-success"
                                onclick="return ask(this, '{Lang::T('Continue the package change process')}?')"
                                type="submit">{Lang::T('Edit')}</button>
                            Or <a href="{Text::url('')}plan/list">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sendWelcomeCheckbox = document.getElementById('notify');
        var methodSection = document.getElementById('method');

        function toggleMethodSection() {
            if (sendWelcomeCheckbox.checked) {
                methodSection.style.display = 'block';
            } else {
                methodSection.style.display = 'none';
            }
        }

        toggleMethodSection();

        sendWelcomeCheckbox.addEventListener('change', toggleMethodSection);
        document.querySelector('form').addEventListener('submit', function (event) {
            if (sendWelcomeCheckbox.checked) {
                var methodCheckboxes = methodSection.querySelectorAll('input[type="checkbox"]');
                var oneChecked = Array.from(methodCheckboxes).some(function (checkbox) {
                    return checkbox.checked;
                });

                if (!oneChecked) {
                    event.preventDefault();
                    alert('Please choose at least one method notification.');
                    methodSection.focus();
                }
            }
        });
    });
</script>

{include file="sections/footer.tpl"}