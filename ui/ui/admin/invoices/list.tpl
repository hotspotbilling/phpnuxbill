{include file="sections/header.tpl"}

<!-- Add a Table for Sent History -->
<div class="panel panel-default">
    <div class="panel-heading">{Lang::T('Invoices')}</div>
    <div class="panel-body" style="overflow: auto;"> 
        <table class="table table-bordered" id="invoiceTable" style="width:100%">
            <thead>
                <tr>
                    <th>{Lang::T('Invoice No')}</th>
                    <th>{Lang::T('Customer Name')}</th>
                    <th>{Lang::T('Email')}</th>
                    <th>{Lang::T('Address')}</th>
                    <th>{Lang::T('Amount')}</th>
                    <th>{Lang::T('Status')}</th>
                    <th>{Lang::T('Created Date')}</th>
                    <th>{Lang::T('Due Date')}</th>
                    <th>{Lang::T('Actions')}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $invoices as $invoice}
                <tr>
                    <td>{$invoice->number}</td>
                    <td>{$invoice->fullname}</td>
                    <td>{$invoice->email}</td>
                    <td>{$invoice->address}</td>
                    <td>{$invoice->amount}</td>
                    <td>
                        {if $invoice->status == 'paid'}
                            <span class="label label-success">{Lang::T('Paid')}</span>
                        {elseif $invoice->status == 'unpaid'}
                            <span class="label label-danger">{Lang::T('Unpaid')}</span>
                        {else}
                            <span class="label label-warning">{Lang::T('Pending')}</span>
                        {/if}
                    </td>
                    <td>{$invoice->created_at}</td>
                    <td>{$invoice->due_date}</td>
                    <td>
                        <a href="{$app_url}/system/uploads/invoices/{$invoice->filename}" class="btn btn-primary btn-xs">{Lang::T('View')}</a>
                        <!-- <a href="javascript:void(0);" class="btn btn-danger btn-xs" onclick="deleteInvoice({$invoice->id});">{Lang::T('Delete')}</a>
                        <a href="javascript:void(0);" class="btn btn-success btn-xs" onclick="sendInvoice({$invoice->id});">{Lang::T('Send')}</a> -->
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<script>
    new DataTable('#invoiceTable');
</script>

{include file="sections/footer.tpl"}