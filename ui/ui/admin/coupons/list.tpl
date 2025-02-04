{include file="sections/header.tpl"}
<style>
    /* Styles for overall layout and responsiveness */
    body {
        background-color: #f8f9fa;
        font-family: 'Arial', sans-serif;
        padding: 0;
        margin: 0;
    }

    .container {
        margin-top: 20px;
        background-color: #d8dfe5;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 20px;
        max-width: 98%;
        overflow-x: auto;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
    }

    /* Styles for table and pagination */
    .table {
        width: 100%;
        margin-bottom: 1rem;
        background-color: #fff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .table th {
        vertical-align: middle;
        border-color: #dee2e6;
        background-color: #343a40;
        color: #fff;
    }

    .table td {
        vertical-align: middle;
        border-color: #dee2e6;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.075);
        color: #333;
        font-weight: bold;
        transition: background-color 0.3s, color 0.3s;
    }

    .pagination .page-item .page-link {
        color: #007bff;
        background-color: #fff;
        border: 1px solid #dee2e6;
        margin: 0 2px;
        padding: 6px 12px;
        transition: background-color 0.3s, color 0.3s;
    }

    .pagination .page-item .page-link:hover {
        background-color: #e9ecef;
        color: #0056b3;
    }

    .pagination .page-item.active .page-link {
        z-index: 1;
        color: #fff;
        background-color: #007bff;
        border-color: #007bff;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        display: inline-block;
        padding: 5px 10px;
        margin-right: 5px;
        border: 1px solid #ccc;
        background-color: #fff;
        color: #333;
        cursor: pointer;
    }

    .hidden-field {
        display: none;
    }
</style>
<style>
    .btn-group-flex {
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn-group-flex .btn {
        flex: 1 1 auto;
        /* Allow buttons to shrink/grow as needed */
        max-width: 150px;
        /* Optional: Limit button width */
    }
</style>

<form id="" method="post" action="">
    <div class="input-group">
        <div class="input-group-addon">
            <a href=""><span class="fa fa-refresh"></span></a>
        </div>
        <input type="text" name="search" class="form-control" value="{$search}" placeholder="{Lang::T('Search')}...">
        <div class="input-group-btn">
            <button class="btn btn-success" type="submit">{Lang::T('Search Coupons')}</button>
        </div>
    </div>
</form>
<br>
<!-- coupon -->
<div class="row" style="padding: 5px">
    <div class="col-lg-3 col-lg-offset-9">
        <div class="btn-group btn-group-justified" role="group">
            <div class="btn-group" role="group">
                <a href="{$_url}coupons/add" class="btn btn-primary">
                    {Lang::T('Add Coupon')}</a>
            </div>
        </div>
    </div>
</div>
<div class="panel panel-hovered mb20 panel-primary">
    <div class="panel-heading">
        &nbsp;
    </div>
    <div class="container">
        <table id="datatable" class="table table-bordered table-striped table-condensed">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all"></th>
                    <th>{Lang::T('Code')}</th>
                    <th>{Lang::T('Type')}</th>
                    <th>{Lang::T('Value')}</th>
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
                    <th>{Lang::T('Action')}</th>
                </tr>
            </thead>
            <tbody>
                {if $coupons}
                {foreach $coupons as $coupon}
                <tr>
                    <td><input type="checkbox" name="coupon_ids[]" value="{$coupon['id']}"></td>
                    <td style="background-color: black; color: black;"
                        onmouseleave="this.style.backgroundColor = 'black';"
                        onmouseenter="this.style.backgroundColor = 'white';">
                        {$coupon['code']}
                    </td>
                    <td>{$coupon['type']}</td>
                    <td>{$coupon['value']}</td>
                    <td>{$coupon['description']}</td>
                    <td>{$coupon['max_usage']}</td>
                    <td>{$coupon['usage_count']}</td>
                    <td>
                        {if $coupon['status'] == 'inactive'}
                        <span class="label label-danger">{Lang::T('Inactive')}</span>
                        {elseif $coupon['status'] == 'active'}
                        <span class="label label-success">{Lang::T('Active')}</span>
                        {else}
                        <span class="label label-primary">{Lang::T('Unknown')}</span>
                        {/if}
                    </td>
                    <td>{$coupon['min_order_amount']}</td>
                    <td>{$coupon['max_discount_amount']}</td>
                    <td>{$coupon['start_date']}</td>
                    <td>{$coupon['end_date']}</td>
                    <td>{$coupon['created_at']}</td>
                    <td>{$coupon['updated_at']}</td>
                    <!-- <td>{if $coupon['admin_name']}
                        <a href="{$_url}settings/users-view/{$coupon['generated_by']}">{$coupon['admin_name']}</a>
                        {else} -
                        {/if}
                    </td> -->
                    <td colspan="10" style="text-align: center;">
                        <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                            <a href="{$_url}coupons/edit/{$coupon['id']}&token={$csrf_token}" id="{$coupon['id']}"
                                class="btn btn-success btn-xs">{Lang::T('Edit')}</a>
                            {if $coupon['status'] neq 'inactive'}
                            <a href="javascript:void(0);"
                                onclick="confirmAction('{$_url}coupons/status/&coupon_id={$coupon['id']}&status=inactive&csrf_token={$csrf_token}', '{Lang::T('Block')}')"
                                id="{$coupon['id']}" class="btn btn-danger btn-xs">
                                {Lang::T('Block')}
                            </a>
                            {else}
                            <a href="javascript:void(0);"
                                onclick="confirmAction('{$_url}coupons/status/&coupon_id={$coupon['id']}&status=active&csrf_token={$csrf_token}', '{Lang::T('Unblock')}')"
                                id="{$coupon['id']}" class="btn btn-warning btn-xs">
                                {Lang::T('Unblock')}
                            </a>
                            {/if}
                        </div>
                    </td>
                </tr>
                {/foreach}
                {else}
                <tr>
                    <td colspan="11" style="text-align: center;">
                        {Lang::T('No coupons found.')}
                    </td>
                </tr>
                {/if}
            </tbody>
        </table>
        {include file="pagination.tpl"}
        <div class="row" style="padding: 5px">
            <div class="col-lg-3 col-lg-offset-9">
                <div class="btn-group btn-group-justified" role="group">
                    <div class="btn-group" role="group">
                        {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <button id="deleteSelectedCoupons" class="btn btn-danger">{Lang::T('Delete
                            Selected')}</button>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
    &nbsp;
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                    xhr.open('POST', '{$_url}coupons/delete', true);
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
    document.getElementById('deleteSelectedCoupons').addEventListener('click', function () {
        var selectedCoupons = [];
        document.querySelectorAll('input[name="coupon_ids[]"]:checked').forEach(function (checkbox) {
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
    document.querySelectorAll('.delete-coupon').forEach(function (button) {
        button.addEventListener('click', function () {
            var couponId = this.getAttribute('data-id');
            deleteCoupons([couponId]);
        });
    });


    // Select or deselect all checkboxes
    document.getElementById('select-all').addEventListener('change', function () {
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

    $j(document).ready(function () {
        $j('#datatable').DataTable({
            "pagingType": "full_numbers",
            "order": [
                [1, 'desc']
            ]
        });
    });
</script>
{include file="sections/footer.tpl"}