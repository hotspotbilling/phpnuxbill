{include file="sections/header.tpl"}

<div class="ls mc">
    <form class="mc-grid" method="post" role="form" action="{Text::url('message/send-post')}" id="mcForm">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
        <div class="ls-card mc-main">
            <div class="mc-block">
                <label class="mc-label" for="{if $cust}mcCustomer{else}personSelect{/if}">{Lang::T('Customer')}</label>
                <select {if $cust}id="mcCustomer"{else}id="personSelect"{/if} class="form-control select2" name="id_customer" style="width: 100%"
                    data-placeholder="{Lang::T('Search by name, username, phone or email')}..." required>
                    {if $cust}
                    <option value="{$cust['id']}" selected>{$cust['username']|escape} - {$cust['fullname']|escape} - {$cust['email']|escape}</option>
                    {/if}
                </select>
            </div>

            {include file="admin/message/composer.tpl"}

            <div class="mc-foot">
                <a href="{Text::url('dashboard')}" class="ls-btn"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                <button class="ls-btn ls-btn--primary" type="submit" id="mcSend"
                    onclick="return document.getElementById('mcForm').reportValidity() && mcConfirm(this, '{Lang::T('Continue the process of sending messages')|escape:'javascript'}?')">
                    <i class="fa fa-paper-plane" aria-hidden="true"></i> {Lang::T('Send Message')}</button>
            </div>
        </div>

        <aside class="mc-side">
            {include file="admin/message/composer-side.tpl"}
        </aside>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sel = document.getElementById('personSelect') || document.getElementById('mcCustomer');
        function pick() { if (window.mcSetCustomer && sel.selectedIndex > -1) { window.mcSetCustomer(sel.options[sel.selectedIndex].text); } }
        if (window.jQuery) { jQuery(sel).on('change select2:select', pick); } else { sel.addEventListener('change', pick); }
        pick();
    });
</script>

{include file="sections/footer.tpl"}
