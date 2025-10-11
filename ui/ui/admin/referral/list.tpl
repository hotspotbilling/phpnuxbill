{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                {Lang::T('Referral Relationships')}
                <a href="{getUrl('referrals/settings')}" class="btn btn-primary btn-xs pull-right"><i class="ion ion-gear-a"></i> {Lang::T('Settings')}</a>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>{Lang::T('Date')}</th>
                                <th>{Lang::T('Referrer')}</th>
                                <th>{Lang::T('Referred')}</th>
                                <th>{Lang::T('Referral Code')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $referrals as $r}
                            <tr>
                                <td>{$r['created_at']}</td>
                                <td>
                                    <strong>{$r['referrer_name']}</strong><br>
                                    <small>{$r['referrer_username']}</small>
                                </td>
                                <td>
                                    <strong>{$r['referred_name']}</strong><br>
                                    <small>{$r['referred_username']}</small>
                                </td>
                                <td>{$r['referral_code']}</td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}