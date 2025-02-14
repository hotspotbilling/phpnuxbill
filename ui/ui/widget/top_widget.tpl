<div class="row">
    {if in_array($_admin['user_type'],['SuperAdmin','Admin', 'Report'])}
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h4 class="text-bold" style="font-size: large;"><sup>{$_c['currency_code']}</sup>
                        {number_format($iday,0,$_c['dec_point'],$_c['thousands_sep'])}</h4>
                </div>
                <div class="icon">
                    <i class="ion ion-clock"></i>
                </div>
                <a href="{Text::url('reports/by-date')}" class="small-box-footer">{Lang::T('Income Today')}</a>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h4 class="text-bold" style="font-size: large;"><sup>{$_c['currency_code']}</sup>
                        {number_format($imonth,0,$_c['dec_point'],$_c['thousands_sep'])}</h4>
                </div>
                <div class="icon">
                    <i class="ion ion-android-calendar"></i>
                </div>
                <a href="{Text::url('reports/by-period')}" class="small-box-footer">{Lang::T('Income This Month')}</a>
            </div>
        </div>
    {/if}
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-yellow">
            <div class="inner">
                <h4 class="text-bold" style="font-size: large;">{$u_act}/{$u_all-$u_act}</h4>
            </div>
            <div class="icon">
                <i class="ion ion-person"></i>
            </div>
            <a href="{Text::url('plan/list')}" class="small-box-footer">{Lang::T('Active')}/{Lang::T('Expired')}</a>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-red">
            <div class="inner">
                <h4 class="text-bold" style="font-size: large;">{$c_all}</h4>
            </div>
            <div class="icon">
                <i class="ion ion-android-people"></i>
            </div>
            <a href="{Text::url('customers/list')}" class="small-box-footer">{Lang::T('Customers')}</a>
        </div>
    </div>
</div>