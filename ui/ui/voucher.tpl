{include file="sections/header.tpl"}
<!-- voucher -->
<div class="row" style="padding: 5px">
    <div class="col-lg-3 col-lg-offset-9">
        <div class="btn-group btn-group-justified" role="group">
            <div class="btn-group" role="group">
                <a href="{$_url}plan/add-voucher" class="btn btn-primary"><i class="ion ion-android-add"></i>
                    {Lang::T('Vouchers')}</a>
            </div>
            <div class="btn-group" role="group">
                <a href="{$_url}plan/print-voucher" target="print_voucher" class="btn btn-info"><i
                        class="ion ion-android-print"></i> {Lang::T('Print')}</a>
            </div>
        </div>
    </div>
</div>
<div class="panel panel-hovered mb20 panel-primary">
    <div class="panel-heading">
        {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
        <div class="btn-group pull-right">
            <a class="btn btn-danger btn-xs" title="Remove used Voucher" href="{$_url}plan/remove-voucher"
                onclick="return ask(this, 'Delete all used voucher code more than 3 months?')"><span
                    class="glyphicon glyphicon-trash" aria-hidden="true"></span> {Lang::T('Delete')} &gt; {Lang::T('3
                Months')}</a>
        </div>
        {/if}
        &nbsp;
    </div>
    <div class="panel-body">
        <form id="site-search" method="post" action="{$_url}plan/voucher/">
            <div class="row" style="padding: 5px">
                <div class="col-lg-2">
                    <div class="input-group">
                        <div class="input-group-addon">
                            <span class="fa fa-search"></span>
                        </div>
                        <input type="text" name="search" class="form-control" placeholder="{Lang::T('Code Voucher')}"
                            value="{$search}">
                    </div>
                </div>
                <div class="col-lg-2">
                    <select class="form-control" id="router" name="router">
                        <option value="">{Lang::T('Location')}</option>
                        {foreach $routers as $r}
                        <option value="{$r}" {if $router eq $r }selected{/if}>{$r}
                        </option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-lg-2">
                    <select class="form-control" id="plan" name="plan">
                        <option value="">{Lang::T('Plan Name')}</option>
                        {foreach $plans as $p}
                        <option value="{$p['id']}" {if $plan eq $p['id'] }selected{/if}>{$p['name_plan']}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-lg-2">
                    <select class="form-control" id="status" name="status">
                        <option value="-">{Lang::T('Status')}</option>
                        <option value="1" {if $status eq 1 }selected{/if}>Used</option>
                        <option value="0" {if $status eq 0 }selected{/if}>Not Use</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <select class="form-control" id="customer" name="customer">
                        <option value="">{Lang::T('Customer')}</option>
                        {foreach $customers as $c}
                        <option value="{$c['user']}" {if $customer eq $c['user'] }selected{/if}>{$c['user']}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-lg-2">
                    <div class="btn-group btn-group-justified" role="group">
                        <div class="btn-group" role="group">
                            <button class="btn btn-success btn-block" type="submit"><span
                                    class="fa fa-search"></span></button>
                        </div>
                        <div class="btn-group" role="group">
                            <a class="btn btn-warning btn-block" title="Clear Search Query"
                                href="{$_url}plan/voucher/"><span class="glyphicon glyphicon-remove-circle"></span></a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <div style="margin-left: 5px; margin-right: 5px;">&nbsp;
            <table id="datatable" class="table table-bordered table-striped table-condensed">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>ID</th>
                        <th>{Lang::T('Type')}</th>
                        <th>{Lang::T('Routers')}</th>
                        <th>{Lang::T('Plan Name')}</th>
                        <th>{Lang::T('Code Voucher')}</th>
                        <th>{Lang::T('Status Voucher')}</th>
                        <th>{Lang::T('Customer')}</th>
                        <th>{Lang::T('Create Date')}</th>
                        <th>{Lang::T('Used Date')}</th>
                        <th>{Lang::T('Generated By')}</th>
                        <th>{Lang::T('Manage')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $d as $ds}
                    <tr {if $ds['status'] eq '1' }class="danger" {/if}>
                        <td><input type="checkbox" name="voucher_ids[]" value="{$ds['id']}"></td>
                        <td>{$ds['id']}</td>
                        <td>{$ds['type']}</td>
                        <td>{$ds['routers']}</td>
                        <td>{$ds['name_plan']}</td>
                        <td style="background-color: black; color: black;"
                            onmouseleave="this.style.backgroundColor = 'black';"
                            onmouseenter="this.style.backgroundColor = 'white';">
                            {$ds['code']}</td>
                        <td>{if $ds['status'] eq '0'} <label class="btn-tag btn-tag-success"> Not Use
                            </label> {else} <label class="btn-tag btn-tag-danger">Used</label>
                            {/if}</td>
                        <td>{if $ds['user'] eq '0'} -
                            {else}<a href="{$_url}customers/viewu/{$ds['user']}">{$ds['user']}</a>
                            {/if}</td>
                        <td>{if $ds['created_at']}{Lang::dateTimeFormat($ds['created_at'])}{/if}</td>
                        <td>{if $ds['used_date']}{Lang::dateTimeFormat($ds['used_date'])}{/if}</td>
                        <td>{if $ds['generated_by']}
                            <a
                                href="{$_url}settings/users-view/{$ds['generated_by']}">{$admins[$ds['generated_by']]}</a>
                            {else} -
                            {/if}
                        </td>
                        <td>
                            {if $ds['status'] neq '1'}
                            <a href="{$_url}plan/voucher-view/{$ds['id']}" id="{$ds['id']}" style="margin: 0px;"
                                class="btn btn-success btn-xs">&nbsp;&nbsp;{Lang::T('View')}&nbsp;&nbsp;</a>
                            {/if}
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                            <a href="{$_url}plan/voucher-delete/{$ds['id']}" id="{$ds['id']}"
                                class="btn btn-danger btn-xs" onclick="return ask(this, '{Lang::T('Delete')}?')"><i
                                    class="glyphicon glyphicon-trash"></i></a>
                            {/if}
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="row" style="padding: 5px">
    <div class="col-lg-3 col-lg-offset-9">
        <div class="btn-group btn-group-justified" role="group">
            <div class="btn-group" role="group">
                {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                <button id="deleteSelectedVouchers" class="btn btn-danger">{Lang::T('Delete
                    Selected')}</button>
                {/if}
            </div>
        </div>
    </div>
</div>
{include file="pagination.tpl"}

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
                    xhr.open('POST', '{$_url}plan/voucher-delete-many', true);
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

    // Example usage for selected vouchers
    document.getElementById('deleteSelectedVouchers').addEventListener('click', function () {
        var selectedVouchers = [];
        document.querySelectorAll('input[name="voucher_ids[]"]:checked').forEach(function (checkbox) {
            selectedVouchers.push(checkbox.value);
        });

        if (selectedVouchers.length > 0) {
            deleteVouchers(selectedVouchers);
        } else {
            Swal.fire({
                title: 'Error!',
                text: 'Please select at least one voucher to delete.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });

    document.querySelectorAll('.delete-voucher').forEach(function (button) {
        button.addEventListener('click', function () {
            var voucherId = this.getAttribute('data-id');
            deleteVouchers([voucherId]);
        });
    });


    // Select or deselect all checkboxes
    document.getElementById('select-all').addEventListener('change', function () {
        var checkboxes = document.querySelectorAll('input[name="voucher_ids[]"]');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });
</script>
{include file="sections/footer.tpl"}