<ol class="breadcrumb">
    <li>{Lang::dateFormat($start_date)}</li>
    <li>{Lang::dateFormat($current_date)}</li>
    {if $_c['enable_balance'] == 'yes' && in_array($_admin['user_type'],['SuperAdmin','Admin', 'Report'])}
        <li onclick="window.location.href = '{Text::url('customers&search=&order=balance&filter=Active&orderby=desc')}'" style="cursor: pointer;">
            {Lang::T('Customer Balance')} <sup>{$_c['currency_code']}</sup>
            <b>{number_format($cb,0,$_c['dec_point'],$_c['thousands_sep'])}</b>
        </li>
    {/if}
</ol>