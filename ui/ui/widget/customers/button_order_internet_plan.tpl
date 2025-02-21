<div class="box-footer">
    {if $_c['payment_gateway'] != 'none' or $_c['payment_gateway'] == '' }
        <a href="{Text::url('order/package')}" class="btn btn-primary btn-block">
            <i class="ion ion-ios-cart"></i>
            {Lang::T('Order Package')}
        </a>
    {/if}
</div>