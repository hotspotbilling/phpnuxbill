{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}settings/notifications-post">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    {Lang::T('User Notification')}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Expired Notification Message')}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="expired"
                                name="expired"
                                placeholder="Hello [[name]], your internet package [[package]] has been expired"
                                rows="3">{if $_json['expired']!=''}{Lang::htmlspecialchars($_json['expired'])}{else}Hello [[name]], your internet package [[package]] has been expired.{/if}</textarea>
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('<b>[[name]]</b> will be replaced with Customer Name. <b>[[package]]</b> will be replaced with Package name.')}
                        </p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Reminder 7 days')}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="reminder_7_day" name="reminder_7_day"
                                rows="3">{Lang::htmlspecialchars($_json['reminder_7_day'])}</textarea>
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('<b>[[name]]</b> will be replaced with Customer Name. <b>[[package]]</b> will be replaced with Package name.')}
                        </p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Reminder 3 days')}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="reminder_3_day" name="reminder_3_day"
                                rows="3">{Lang::htmlspecialchars($_json['reminder_3_day'])}</textarea>
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('<b>[[name]]</b> will be replaced with Customer Name. <b>[[package]]</b> will be replaced with Package name.')}
                        </p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Reminder 1 day')}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="reminder_1_day" name="reminder_1_day"
                                rows="3">{Lang::htmlspecialchars($_json['reminder_1_day'])}</textarea>
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('<b>[[name]]</b> will be replaced with Customer Name. <b>[[package]]</b> will be replaced with Package name.')}
                        </p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Invoice Notification Payment')}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="invoice_paid" name="invoice_paid"
                                placeholder="Hello [[name]], your internet package [[package]] has been expired"
                                rows="20">{Lang::htmlspecialchars($_json['invoice_paid'])}</textarea>
                        </div>
                        <p class="col-md-4 help-block">
                            <b>[[company_name]]</b> Your Company Name at Settings.<br>
                            <b>[[address]]</b> Your Company Address at Settings.<br>
                            <b>[[phone]]</b> Your Company Phone at Settings.<br>
                            <b>[[invoice]]</b> invoice number.<br>
                            <b>[[date]]</b> Date invoice created.<br>
                            <b>[[payment_gateway]]</b> Payment gateway user paid from.<br>
                            <b>[[payment_channel]]</b> Payment channel user paid from.<br>
                            <b>[[type]]</b> is Hotspot/PPPOE.<br>
                            <b>[[plan_name]]</b> Internet Package.<br>
                            <b>[[plan_price]]</b> Internet Package Prices.<br>
                            <b>[[user_name]]</b> Username internet.<br>
                            <b>[[user_password]]</b> User password.<br>
                            <b>[[expired_date]]</b> Expired datetime.
                        </p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Balance Notification Payment')}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="invoice_balance" name="invoice_balance"
                                placeholder="Hello [[name]], your internet package [[package]] has been expired"
                                rows="20">{Lang::htmlspecialchars($_json['invoice_balance'])}</textarea>
                        </div>
                        <p class="col-md-4 help-block">
                            <b>[[company_name]]</b> Your Company Name at Settings.<br>
                            <b>[[address]]</b> Your Company Address at Settings.<br>
                            <b>[[phone]]</b> Your Company Phone at Settings.<br>
                            <b>[[invoice]]</b> invoice number.<br>
                            <b>[[date]]</b> Date invoice created.<br>
                            <b>[[payment_gateway]]</b> Payment gateway user paid from.<br>
                            <b>[[payment_channel]]</b> Payment channel user paid from.<br>
                            <b>[[type]]</b> is Hotspot/PPPOE.<br>
                            <b>[[plan_name]]</b> Internet Package.<br>
                            <b>[[plan_price]]</b> Internet Package Prices.<br>
                            <b>[[user_name]]</b> Username internet.<br>
                            <b>[[user_password]]</b> User password.<br>
                            <b>[[trx_date]]</b> Transaction datetime.
                        </p>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <div class="form-group">
                    <button class="btn btn-success btn-block waves-effect waves-light"
                        type="submit">{$_L['Save']}</button>
                </div>
            </div>
        </div>
    </div>
</form>
{include file="sections/footer.tpl"}