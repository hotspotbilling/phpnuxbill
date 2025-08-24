<div class="row">
    {if in_array($_admin['user_type'],['SuperAdmin','Admin', 'Report'])}
    <a href="{Text::url('reports/by-date')}">
        <div class="col-md-3">
            <div class="summary-card">
                <h5>{Lang::T('Income Today')}</h5>
                <div class="value">{$_c['currency_code']} {number_format($iday,0,$_c['dec_point'],$_c['thousands_sep'])}
                </div>
            </div>
        </div>
    </a>
    <a href="{Text::url('reports/by-period')}">
        <div class="col-md-3">
            <div class="summary-card">
                <h5>{Lang::T('Income This Month')}</h5>
                <div class="value"> {$_c['currency_code']}
                    {number_format($imonth,0,$_c['dec_point'],$_c['thousands_sep'])}
                </div>
            </div>
        </div>
    </a>
    {/if}
    <a href="{Text::url('plan/list')}">
        <div class="col-md-3">
            <div class="summary-card">
                <h5>{Lang::T('Active')}/{Lang::T('Expired')} </h5>
                <div class="value">{$u_act}/{$u_all-$u_act}</div>
            </div>
        </div>
    </a>
    <a href="{Text::url('customers/list')}">
        <div class="col-md-3">
            <div class="summary-card">
                <h5>{Lang::T('Customers')}</h5>
                <div class="value">{$c_all}</div>
            </div>
        </div>
    </a>
</div>