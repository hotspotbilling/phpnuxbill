{include file="customer/header.tpl"}
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="box-title">{Lang::T('Referral Program')}</h3>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <p>{Lang::T('Share your referral link with friends and earn rewards when they sign up!')}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5>{Lang::T('Your Referral Code')}</h5>
                            </div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label>{Lang::T('Referral Code')}</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="{$referral_code|escape:'html'}"
                                            readonly>
                                        <div class="input-group-btn">
                                            <button class="btn btn-default copy-button" type="button"
                                                data-clipboard-text="{$referral_code|escape:'html'}">{Lang::T('Copy')}</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mt-3">
                                    <label>{Lang::T('Referral Link')}</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="{$referral_link|escape:'html'}"
                                            readonly>
                                        <div class="input-group-btn">
                                            <button class="btn btn-default copy-button" type="button"
                                                data-clipboard-text="{$referral_link|escape:'html'}">{Lang::T('Copy')}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {if $referrer}
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5>{Lang::T('You were referred by')}</h5>
                            </div>
                            <div class="panel-body">
                                <p><strong>{$referrer->referrer_name|escape:'html'}</strong></p>
                            </div>
                        </div>
                    </div>
                </div>
                {/if}

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5>{Lang::T('People You\'ve Referred')}</h5>
                            </div>
                            <div class="panel-body">
                                {if count($referred_customers) > 0}
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>{Lang::T('Name')}</th>
                                                <th>{Lang::T('Username')}</th>
                                                <th>{Lang::T('Date')}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {foreach $referred_customers as $rc}
                                            <tr>
                                            <tr>
                                                <td>{$rc->referred_fullname|escape:'html'}</td>
                                                <td>{$rc->referred_username|escape:'html'}</td>
                                                <td>{$rc->created_at|escape:'html'}</td>
                                            </tr>
                                            </tr>
                                            {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                                {else}
                                <div class="alert alert-info">
                                    {Lang::T('You haven\'t referred anyone yet. Share your referral link to get
                                    started!')}
                                </div>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5>{Lang::T('Your Referral Rewards')}</h5>
                            </div>
                            <div class="panel-body">
                                {if $referral_rewards|@count > 0}
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>{Lang::T('Date')}</th>
                                                <th>{Lang::T('Amount')}</th>
                                                <th>{Lang::T('Type')}</th>
                                                <th>{Lang::T('Status')}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {foreach $referral_rewards as $reward}
                                            <tr>
                                                <td>{$reward->created_at|escape:'html'}</td>
                                                <td>{$_c['currency_code']} {number_format($reward->reward_amount,2)}
                                                </td>
                                                <td>{$reward->reward_type|escape:'html'}</td>
                                                <td>
                                                    {if $reward->status == 'pending'}
                                                    <span class="label label-warning">{Lang::T('Pending')}</span>
                                                    {elseif $reward->status == 'processed'}
                                                    <span class="label label-success">{Lang::T('Processed')}</span>
                                                    {else}
                                                    <span class="label label-default">{$reward->status}</span>
                                                    {/if}
                                                </td>
                                            </tr>
                                            {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                                {else}
                                <div class="alert alert-info">
                                    {Lang::T('You don\'t have any rewards yet. Refer friends to earn rewards!')}
                                </div>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Variables for JS to avoid XSS issues
    var copiedTitle = "{Lang::T('Copied!')|escape:'javascript'}";
    var copiedText = "{Lang::T('The text has been copied to your clipboard.')|escape:'javascript'}";
    var errorTitle = "{Lang::T('Oops...')|escape:'javascript'}";
    var errorText = "{Lang::T('Failed to copy. Please try manually.')|escape:'javascript'}";

    $(document).ready(function () {
        var clipboard = new ClipboardJS('.copy-button');

        clipboard.on('success', function (e) {
            Swal.fire({
                icon: 'success',
                title: copiedTitle,
                text: copiedText,
                showConfirmButton: false,
                timer: 1800
            });
            e.clearSelection();
        });

        clipboard.on('error', function (e) {
            Swal.fire({
                icon: 'error',
                title: errorTitle,
                text: errorText
            });
        });
    });
</script>

{include file="customer/footer.tpl"}