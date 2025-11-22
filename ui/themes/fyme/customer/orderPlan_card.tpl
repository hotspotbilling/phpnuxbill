
<div class="col-md-4 mb-4">
    <div class="pricing-card">
        <div class="pricing-header">
            <div class="pricing-title">{$plan['name_plan']}</div>
            <div class="pricing-price">
                {Lang::moneyFormat($plan['price'])}
                {if !empty($plan['price_old'])}
                    <span style="text-decoration: line-through; color: var(--text-light); font-size: 1rem; font-weight: normal;">
                        {Lang::moneyFormat($plan['price_old'])}
                    </span>
                {/if}
            </div>
            <div class="pricing-validity">{$plan['validity']} {$plan['validity_unit']}</div>
        </div>

        <div class="pricing-features">
            <div class="pricing-feature-item">
                <span>{Lang::T('Type')}</span>
                <span style="font-weight: 600; color: var(--primary);">{$plan['type']}</span>
            </div>
            {if $_c['show_bandwidth_plan'] == 'yes'}
                <div class="pricing-feature-item">
                    <span>{Lang::T('Bandwidth')}</span>
                    <span api-get-text="{Text::url('autoload_user/bw_name/')}{$plan['id_bw']}"></span>
                </div>
            {/if}
        </div>

        <div class="pricing-actions">
            {if isset($router['id'])}
                <a href="{Text::url('order/gateway/',$router['id'],'/',$plan['id'],'&stoken=',App::getToken())}"
                    onclick="return ask(this, '{Lang::T('Buy this? your active package will be overwrite')}')"
                    class="btn-fyme btn-fyme-primary">
                    {Lang::T('Buy Now')} <i data-feather="shopping-cart" style="width: 16px; margin-left: 6px;"></i>
                </a>
                {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes' && $_user['balance']>=$plan['price']}
                    <a href="{Text::url('order/send/',$router['id'],'/',$plan['id'],'&stoken=',App::getToken())}"
                        onclick="return ask(this, '{Lang::T('Buy this for friend account?')}')"
                        class="btn-fyme btn-fyme-outline">
                        {Lang::T('Gift to Friend')} <i data-feather="gift" style="width: 16px; margin-left: 6px;"></i>
                    </a>
                {/if}
            {else}
                <!-- Fallback for generic types where router ID might be implicit or handled by type -->
                {if $plan['type'] == 'PPPoE'}
                    <a href="{Text::url('order/gateway/pppoe/',$plan['id'],'&stoken=',App::getToken())}"
                        onclick="return ask(this, '{Lang::T('Buy this? your active package will be overwrite')}')"
                        class="btn-fyme btn-fyme-primary">
                        {Lang::T('Buy Now')} <i data-feather="shopping-cart" style="width: 16px; margin-left: 6px;"></i>
                    </a>
                     {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes' && $_user['balance']>=$plan['price']}
                        <a href="{Text::url('order/send/pppoe/',$plan['id'],'&stoken=',App::getToken())}"
                            onclick="return ask(this, '{Lang::T('Buy this for friend account?')}')"
                            class="btn-fyme btn-fyme-outline">
                            {Lang::T('Gift to Friend')} <i data-feather="gift" style="width: 16px; margin-left: 6px;"></i>
                        </a>
                    {/if}
                {elseif $plan['type'] == 'Hotspot'}
                     <a href="{Text::url('order/gateway/hotspot/',$plan['id'],'&stoken=',App::getToken())}"
                        onclick="return ask(this, '{Lang::T('Buy this? your active package will be overwrite')}')"
                        class="btn-fyme btn-fyme-primary">
                        {Lang::T('Buy Now')} <i data-feather="shopping-cart" style="width: 16px; margin-left: 6px;"></i>
                    </a>
                     {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes' && $_user['balance']>=$plan['price']}
                        <a href="{Text::url('order/send/hotspot/',$plan['id'],'&stoken=',App::getToken())}"
                            onclick="return ask(this, '{Lang::T('Buy this for friend account?')}')"
                            class="btn-fyme btn-fyme-outline">
                            {Lang::T('Gift to Friend')} <i data-feather="gift" style="width: 16px; margin-left: 6px;"></i>
                        </a>
                    {/if}
                {else}
                    <!-- Generic Radius Fallback -->
                     <a href="{Text::url('order/gateway/radius/',$plan['id'],'&stoken=',App::getToken())}"
                        onclick="return ask(this, '{Lang::T('Buy this? your active package will be overwrite')}')"
                        class="btn-fyme btn-fyme-primary">
                        {Lang::T('Buy Now')} <i data-feather="shopping-cart" style="width: 16px; margin-left: 6px;"></i>
                    </a>
                     {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes' && $_user['balance']>=$plan['price']}
                        <a href="{Text::url('order/send/radius/',$plan['id'],'&stoken=',App::getToken())}"
                            onclick="return ask(this, '{Lang::T('Buy this for friend account?')}')"
                            class="btn-fyme btn-fyme-outline">
                            {Lang::T('Gift to Friend')} <i data-feather="gift" style="width: 16px; margin-left: 6px;"></i>
                        </a>
                    {/if}
                {/if}
            {/if}
        </div>
    </div>
</div>
