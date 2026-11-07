{include file="sections/header.tpl"}

<div class="ls">
    <div class="ls-top">
        <div></div>
        <div class="ls-actions">
            <a href="{Text::url('coupons/add')}" class="ls-btn ls-btn--primary"><i class="fa fa-plus" aria-hidden="true"></i> {Lang::T('Add Coupon')}</a>
        </div>
    </div>

    <div class="ls-card">
        <form id="coupon-search" class="ls-bar" method="post" action="{Text::url('coupons')}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="search" value="{$search|escape}" placeholder="{Lang::T('Search')}..." aria-label="{Lang::T('Search')}">
            </div>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search Coupons')}</button>
            <a class="ls-btn" href="{Text::url('coupons')}"><i class="fa fa-undo" aria-hidden="true"></i> {Lang::T('Reset')}</a>
        </form>

        {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
        <div class="ls-bulk" id="lsBulk" hidden>
            <span><b id="lsBulkN">0</b> {Lang::T('selected')}</span>
            <button type="button" id="deleteSelectedCoupons" class="ls-btn ls-btn--danger"><i class="fa fa-trash" aria-hidden="true"></i> {Lang::T('Delete Selected')}</button>
        </div>
        {/if}

        <div class="ls-scroll">
            <table id="datatable" class="ls-table">
                <thead>
                    <tr>
                        <th class="ls-check"><input type="checkbox" id="select-all" aria-label="{Lang::T('Select all')}"></th>
                        <th>{Lang::T('Code')}</th>
                        <th>{Lang::T('Type')}</th>
                        <th class="ls-num">{Lang::T('Value')}</th>
                        <th>{Lang::T('Description')}</th>
                        <th>{Lang::T('Max Usage')}</th>
                        <th>{Lang::T('Usage Count')}</th>
                        <th>{Lang::T('Status')}</th>
                        <th>{Lang::T('Min Order')}</th>
                        <th>{Lang::T('Max Discount')}</th>
                        <th>{Lang::T('Start Date')}</th>
                        <th>{Lang::T('End Date')}</th>
                        <th>{Lang::T('Created Date')}</th>
                        <th>{Lang::T('Updated Date')}</th>
                        <th class="ls-end">{Lang::T('Action')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $coupons as $coupon}
                    <tr>
                        <td class="ls-check"><input type="checkbox" name="coupon_ids[]" value="{$coupon['id']}" aria-label="{$coupon['code']|escape}"></td>
                        <td data-label="{Lang::T('Code')}"><span class="ls-secret" tabindex="0" title="{Lang::T('Hover or tap to show the code')}">{$coupon['code']|escape}</span></td>
                        <td data-label="{Lang::T('Type')}"><em class="ls-chip">{$coupon['type']|escape}</em></td>
                        <td data-label="{Lang::T('Value')}" class="ls-num">{$coupon['value']|escape}</td>
                        <td data-label="{Lang::T('Description')}" class="ls-dim">{$coupon['description']|escape}</td>
                        <td data-label="{Lang::T('Max Usage')}">{$coupon['max_usage']|escape}</td>
                        <td data-label="{Lang::T('Usage Count')}">{$coupon['usage_count']|escape}</td>
                        <td data-label="{Lang::T('Status')}">
                            {if $coupon['status'] == 'inactive'}<span class="ls-pill ls-pill--banned">{Lang::T('Inactive')}</span>
                            {elseif $coupon['status'] == 'active'}<span class="ls-pill ls-pill--active">{Lang::T('Active')}</span>
                            {else}<span class="ls-pill">{Lang::T('Unknown')}</span>{/if}
                        </td>
                        <td data-label="{Lang::T('Min Order')}">{$coupon['min_order_amount']|escape}</td>
                        <td data-label="{Lang::T('Max Discount')}">{$coupon['max_discount_amount']|escape}</td>
                        <td data-label="{Lang::T('Start Date')}" class="ls-dim ls-when">{$coupon['start_date']|escape}</td>
                        <td data-label="{Lang::T('End Date')}" class="ls-dim ls-when">{$coupon['end_date']|escape}</td>
                        <td data-label="{Lang::T('Created Date')}" class="ls-dim ls-when">{$coupon['created_at']|escape}</td>
                        <td data-label="{Lang::T('Updated Date')}" class="ls-dim ls-when">{$coupon['updated_at']|escape}</td>
                        <td class="ls-end">
                            <div class="ls-row-actions">
                                <a href="{Text::url('coupons/edit/', $coupon['id'], '&token=', $csrf_token)}" class="ls-btn ls-btn--sm">{Lang::T('Edit')}</a>
                                {if $coupon['status'] neq 'inactive'}
                                <a href="javascript:void(0);" class="ls-btn ls-btn--sm ls-btn--danger"
                                    onclick="confirmAction('{Text::url('coupons/status/&coupon_id=', $coupon['id'], '&status=inactive&csrf_token=', $csrf_token)}', '{Lang::T('Block')}')">{Lang::T('Block')}</a>
                                {else}
                                <a href="javascript:void(0);" class="ls-btn ls-btn--sm"
                                    onclick="confirmAction('{Text::url('coupons/status/&coupon_id=', $coupon['id'], '&status=active&csrf_token=', $csrf_token)}', '{Lang::T('Unblock')}')">{Lang::T('Unblock')}</a>
                                {/if}
                            </div>
                        </td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="15">
                        <div class="ls-empty"><b>{Lang::T('No coupons found.')}</b>
                            <a href="{Text::url('coupons/add')}">{Lang::T('Add Coupon')}</a>
                        </div>
                    </td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {include file="pagination.tpl"}
    </div>
</div>

<script src="{$app_url}/ui/ui/scripts/jquery.min.js"></script>
<script>
    function deleteCoupons(couponIds) {
        if (couponIds.length > 0) {
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
                    xhr.open('POST', '{Text::url('coupons/delete')}', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
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
                                text: 'Failed to delete coupons. Please try again.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    };
                    xhr.send('couponIds=' + JSON.stringify(couponIds));
                }
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: 'No coupons selected to delete.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    }

    // Example usage for selected coupons
    var delBtnC = document.getElementById('deleteSelectedCoupons');
    if (delBtnC) delBtnC.addEventListener('click', function() {
        var selectedCoupons = [];
        document.querySelectorAll('input[name="coupon_ids[]"]:checked').forEach(function(checkbox) {
            selectedCoupons.push(checkbox.value);
        });

        if (selectedCoupons.length > 0) {
            deleteCoupons(selectedCoupons);
        } else {
            Swal.fire({
                title: 'Error!',
                text: 'Please select at least one coupon to delete.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });

    // Example usage for single coupon deletion
    document.querySelectorAll('.delete-coupon').forEach(function(button) {
        button.addEventListener('click', function() {
            var couponId = this.getAttribute('data-id');
            deleteCoupons([couponId]);
        });
    });


    // Select or deselect all checkboxes
    document.getElementById('select-all').addEventListener('change', function() {
        var checkboxes = document.querySelectorAll('input[name="coupon_ids[]"]');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });
</script>
{literal}
    <script>
        function confirmAction(url, action) {
            Swal.fire({
                title: 'Are you sure?',
                text: `Do you really want to ${action.toLowerCase()} this coupon?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, proceed!',
                cancelButtonText: 'No, cancel!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }
    </script>
{/literal}
<script>
    const $j = jQuery.noConflict();

    $j(document).ready(function() {
        $j('#datatable').DataTable({
            "pagingType": "full_numbers",
            "order": [
                [1, 'desc']
            ]
        });
    });
</script>
<script>
    (function () {
        var bar = document.getElementById('lsBulk'), n = document.getElementById('lsBulkN'), all = document.getElementById('select-all');
        function boxes() { return document.querySelectorAll('input[name="coupon_ids[]"]'); }
        function sync() {
            var c = 0, t = 0;
            boxes().forEach(function (b) { t++; if (b.checked) { c++; } });
            if (bar) { n.textContent = c; bar.hidden = c === 0; }
            all.checked = t > 0 && c === t;
            all.indeterminate = c > 0 && c < t;
        }
        all.addEventListener('change', sync);
        boxes().forEach(function (b) { b.addEventListener('change', sync); });
    })();
</script>
{include file="sections/footer.tpl"}