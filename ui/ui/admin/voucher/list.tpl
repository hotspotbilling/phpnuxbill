{include file="sections/header.tpl"}

<div class="ls">
    <div class="ls-top">
        <div></div>
        <div class="ls-actions">
            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
            <a class="ls-btn ls-btn--danger" title="{Lang::T('Remove used Voucher')}" href="{Text::url('')}plan/remove-voucher"
                onclick="return ask(this, '{Lang::T('Delete all used voucher code more than 3 months?')}')"><i class="fa fa-trash" aria-hidden="true"></i> {Lang::T('Delete')} &gt; {Lang::T('3 Months')}</a>
            {/if}
            <a href="{Text::url('')}plan/print-voucher" target="print_voucher" class="ls-btn"><i class="fa fa-print" aria-hidden="true"></i> {Lang::T('Print')}</a>
            <a href="{Text::url('')}plan/add-voucher" class="ls-btn ls-btn--primary"><i class="fa fa-plus" aria-hidden="true"></i> {Lang::T('Vouchers')}</a>
        </div>
    </div>

    <div class="ls-card">
        <form id="voucher-search-form" class="ls-bar" method="post" action="{Text::url('')}plan/voucher/">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="search" placeholder="{Lang::T('Code Voucher')}" value="{$search|escape}" aria-label="{Lang::T('Search by voucher code')}">
            </div>
            <label class="ls-field"><span>{Lang::T('Location')}</span>
                <select id="router" name="router">
                    <option value="">{Lang::T('All')}</option>
                    {foreach $routers as $r}
                    <option value="{$r|escape}" {if $router eq $r}selected{/if}>{$r|escape}</option>
                    {/foreach}
                </select>
            </label>
            <label class="ls-field"><span>{Lang::T('Batch')}</span>
                <select id="batch_name" name="batch_name">
                    <option value="">{Lang::T('All')}</option>
                    {foreach $batches as $batch}
                    <option value="{$batch.batch_name|escape}" {if $batch_name eq $batch.batch_name}selected{/if}>{$batch.batch_name|escape}</option>
                    {/foreach}
                </select>
            </label>
            <label class="ls-field"><span>{Lang::T('Plan Name')}</span>
                <select id="plan" name="plan">
                    <option value="">{Lang::T('All')}</option>
                    {foreach $plans as $p}
                    <option value="{$p['id']}" {if $plan eq $p['id']}selected{/if}>{$p['name_plan']|escape}</option>
                    {/foreach}
                </select>
            </label>
            <label class="ls-field"><span>{Lang::T('Status')}</span>
                <select id="status" name="status">
                    <option value="">{Lang::T('All')}</option>
                    <option value="1" {if $status eq '1'}selected{/if}>{Lang::T('Used')}</option>
                    <option value="0" {if $status eq '0'}selected{/if}>{Lang::T('Not Used')}</option>
                </select>
            </label>
            <label class="ls-field"><span>{Lang::T('Customer')}</span>
                <select id="customer" name="customer">
                    <option value="">{Lang::T('All')}</option>
                    {foreach $customers as $c}
                    <option value="{$c['user']|escape}" {if $customer eq $c['user']}selected{/if}>{$c['user']|escape}</option>
                    {/foreach}
                </select>
            </label>
            <label class="ls-field"><span>{Lang::T('Show')}</span>
                <select id="per_page" name="per_page" onchange="changePerPage(this)">
                    {foreach [10, 25, 50, 100, 200, 500, 1000] as $n}
                    <option value="{$n}" {if $cookie eq $n}selected{/if}>{$n}</option>
                    {/foreach}
                </select>
            </label>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            <a href="{Text::url('')}plan/voucher/" class="ls-btn" title="{Lang::T('Reset Search')}"><i class="fa fa-undo" aria-hidden="true"></i> {Lang::T('Reset')}</a>
        </form>

        {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
        <div class="ls-bulk" id="lsBulk" hidden>
            <span><b id="lsBulkN">0</b> {Lang::T('selected')}</span>
            <button type="button" id="deleteSelectedVouchers" class="ls-btn ls-btn--danger"><i class="fa fa-trash" aria-hidden="true"></i> {Lang::T('Delete Selected')}</button>
        </div>
        {/if}

        <div class="ls-scroll">
            <table id="datatable" class="ls-table">
                <thead>
                    <tr>
                        <th class="ls-check"><input type="checkbox" id="select-all" aria-label="{Lang::T('Select all')}"></th>
                        <th>ID</th>
                        <th>{Lang::T('Batch Name ')}</th>
                        <th>{Lang::T('Type')}</th>
                        <th>{Lang::T('Routers')}</th>
                        <th>{Lang::T('Plan Name')}</th>
                        <th>{Lang::T('Code Voucher')}</th>
                        <th>{Lang::T('Status Voucher')}</th>
                        <th>{Lang::T('Customer')}</th>
                        <th>{Lang::T('Create Date')}</th>
                        <th>{Lang::T('Used Date')}</th>
                        <th>{Lang::T('Generated By')}</th>
                        <th class="ls-end">{Lang::T('Manage')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $d as $ds}
                    <tr>
                        <td class="ls-check"><input type="checkbox" name="voucher_ids[]" value="{$ds['id']}" aria-label="{$ds['code']|escape}"></td>
                        <td data-label="ID" class="ls-dim">{$ds['id']}</td>
                        <td data-label="{Lang::T('Batch Name ')}">{if empty($ds['batch_name'])}<span class="ls-dim">{Lang::T('None')}</span>{else}{$ds['batch_name']|escape}{/if}</td>
                        <td data-label="{Lang::T('Type')}">{$ds['type']|escape}</td>
                        <td data-label="{Lang::T('Routers')}">{$ds['routers']|escape}</td>
                        <td data-label="{Lang::T('Plan Name')}">{$ds['name_plan']|escape}</td>
                        <td data-label="{Lang::T('Code Voucher')}"><span class="ls-secret" tabindex="0" title="{Lang::T('Hover or tap to show the code')}">{$ds['code']|escape}</span></td>
                        <td data-label="{Lang::T('Status Voucher')}">{if $ds['status'] eq '0'}<span class="ls-pill ls-pill--active">{Lang::T('Not Use')}</span>{else}<span class="ls-pill ls-pill--banned">{Lang::T('Used')}</span>{/if}</td>
                        <td data-label="{Lang::T('Customer')}">{if $ds['user'] eq '0'}<span class="ls-dim">-</span>{else}<a class="ls-strong" href="{Text::url('')}customers/viewu/{$ds['user']|escape:'url'}">{$ds['user']|escape}</a>{/if}</td>
                        <td data-label="{Lang::T('Create Date')}" class="ls-dim ls-when">{if $ds['created_at']}{Lang::dateTimeFormat($ds['created_at'])}{/if}</td>
                        <td data-label="{Lang::T('Used Date')}" class="ls-dim ls-when">{if $ds['used_date']}{Lang::dateTimeFormat($ds['used_date'])}{/if}</td>
                        <td data-label="{Lang::T('Generated By')}">{if $ds['generated_by']}<a href="{Text::url('')}settings/users-view/{$ds['generated_by']}">{$admins[$ds['generated_by']]|escape}</a>{else}<span class="ls-dim">-</span>{/if}</td>
                        <td class="ls-end">
                            <div class="ls-row-actions">
                                {if $ds['status'] neq '1'}
                                <a href="{Text::url('')}plan/voucher-view/{$ds['id']}" class="ls-btn ls-btn--sm">{Lang::T('View')}</a>
                                {/if}
                                {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                <a href="{Text::url('')}plan/voucher-delete/{$ds['id']}" class="ls-btn ls-btn--sm ls-btn--icon ls-btn--danger" aria-label="{Lang::T('Delete')}"
                                    onclick="return ask(this, '{Lang::T('Delete')}?')"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                {/if}
                            </div>
                        </td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="13">
                        <div class="ls-empty"><b>{Lang::T('No vouchers found')}</b>
                            {if $search neq '' || $router neq '' || $batch_name neq '' || $plan neq '' || $status neq '' || $customer neq ''}{Lang::T('Nothing matches your filters.')} <a href="{Text::url('')}plan/voucher/">{Lang::T('Reset Search')}</a>
                            {else}<a href="{Text::url('')}plan/add-voucher">{Lang::T('Vouchers')}</a>{/if}
                        </div>
                    </td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {include file="pagination.tpl"}
    </div>
</div>
<script>
    function deleteVouchers(voucherIds) {
        if (voucherIds.length > 0) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You won\'t be able to revert this!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '{Text::url('')}plan/voucher-delete-many', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            var response = JSON.parse(xhr.responseText);

                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    location.reload(); // Reload the page after confirmation
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to delete vouchers. Please try again.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    };
                    xhr.send('voucherIds=' + JSON.stringify(voucherIds));
                }
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: 'No vouchers selected to delete.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    }

    var bulkBar = document.getElementById('lsBulk'), bulkN = document.getElementById('lsBulkN'), allBox = document.getElementById('select-all');
    function voucherBoxes() { return document.querySelectorAll('input[name="voucher_ids[]"]'); }
    function syncBulk() {
        var c = 0, total = 0;
        voucherBoxes().forEach(function (b) { total++; if (b.checked) { c++; } });
        if (bulkBar) { bulkN.textContent = c; bulkBar.hidden = c === 0; }
        allBox.checked = total > 0 && c === total;
        allBox.indeterminate = c > 0 && c < total;
    }
    allBox.addEventListener('change', function () {
        voucherBoxes().forEach(function (b) { b.checked = allBox.checked; });
        syncBulk();
    });
    voucherBoxes().forEach(function (b) { b.addEventListener('change', syncBulk); });

    var delBtn = document.getElementById('deleteSelectedVouchers');
    if (delBtn) {
        delBtn.addEventListener('click', function () {
            var selected = [];
            voucherBoxes().forEach(function (b) { if (b.checked) { selected.push(b.value); } });
            if (selected.length > 0) {
                deleteVouchers(selected);
            } else {
                Swal.fire({ title: 'Error!', text: 'Please select at least one voucher to delete.', icon: 'error', confirmButtonText: 'OK' });
            }
        });
    }

    function changePerPage(select) {
        setCookie('voucher_per_page', select.value, 365);
        location.reload();
    }
</script>
{include file="sections/footer.tpl"}
