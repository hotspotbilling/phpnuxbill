<style>
    .intro-text {
        clear: both;
        padding-top: 10px;
    }

    .referral-box {
        margin: 15px 0;
    }

    .share-section {
        margin-top: 15px;
    }

    .share-section p {
        margin-bottom: 10px;
    }
</style>

{if $_c.referral_enabled !== 'no'}
<div class="box box-primary box-solid mb30">
    <div class="box-header">
        <h3 class="box-title"><i class="fa fa-users"></i> {Lang::T('Referral')}</h3>
        <div class="box-tools pull-right">
            <a href="{$_url}referrals" class="btn btn-xs btn-primary">{Lang::T('View Details')}</a>
        </div>
    </div>
    <div class="box-body">
        <!-- Referral intro -->
        <p class="text-muted intro-text">{Lang::T('Share your referral link with friends and earn rewards when they sign up!')}</p>
        
        <!-- Referral code and link -->
        <div class="referral-box">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-key"></i></span>
                    <input type="text" class="form-control" value="{$referral_code}" readonly placeholder="{Lang::T('Your Referral Code')}">
                    <span class="input-group-btn">
                        <button class="btn btn-default copy-button" type="button" data-clipboard-text="{$referral_code}">
                            <i class="fa fa-copy"></i> {Lang::T('Copy')}
                        </button>
                    </span>
                </div>
                <small class="text-muted">{Lang::T('Your unique referral code')}</small>
            </div>
        </div>
        
        <!-- Quick stats -->
        <div class="row">
            <div class="col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{count($referred_customers)}</h3>
                        <p>{Lang::T('Referred')}</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-person-add"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-xs-6">
                <div class="small-box bg-blue">
                    <div class="inner">
                        {assign var="total_rewards" value=0}
                        {foreach $referral_rewards as $rw}
                            {if $rw->status == 'processed'}
                                {assign var="total_rewards" value=$total_rewards+$rw->reward_amount}
                            {/if}
                        {/foreach}
                        <h3><sup>{$_c['currency_code']}</sup> {number_format($total_rewards,2)}</h3>
                        <p>{Lang::T('Earned')}</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-social-usd"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Share options -->
        <div class="share-section">
            <p><strong>{Lang::T('Share your link')}:</strong></p>
            <div class="btn-group btn-group-justified">
                <a href="https://wa.me/?text={urlencode($referral_link)}" target="_blank" class="btn btn-success">
                    <i class="fa fa-whatsapp"></i> WhatsApp
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={urlencode($referral_link)}" target="_blank" class="btn btn-primary">
                    <i class="fa fa-facebook"></i> Facebook
                </a>
                <a href="https://x.com/intent/tweet?url={urlencode($referral_link)}&text={urlencode(Lang::T('Check out this website!'))}" target="_blank" class="btn btn-info">
                    <i class="fa fa-twitter"></i> Twitter
                </a>
            </div>
        </div>
        
        {if $referrer}
        <!-- Referrer information -->
        <div class="referrer-info">
            <hr>
            <p>{Lang::T('You were referred by')}: <strong>{$referrer->referrer_name}</strong></p>
        </div>
        {/if}
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
<script>
    $(document).ready(function () {
        var clipboard = new ClipboardJS('.copy-button');

        clipboard.on('success', function (e) {
            Swal.fire({
                icon: 'success',
                title: '{Lang::T("Copied!")}',
                text: '{Lang::T("The text has been copied to your clipboard.")}',
                showConfirmButton: false,
                timer: 1800
            });
            e.clearSelection();
        });

        clipboard.on('error', function (e) {
            Swal.fire({
                icon: 'error',
                title: '{Lang::T("Oops...")}',
                text: '{Lang::T("Failed to copy. Please try manually.")}'
            });
        });
    });
</script>
{/if}