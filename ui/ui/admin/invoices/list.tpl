{include file="sections/header.tpl"}

<div class="ls">
    <div class="ls-card">
        <div class="ls-scroll">
            <table class="ls-table table-with-loader" id="invoiceTable">
                <thead>
                    <tr>
                        <th>{Lang::T('Invoice No')}</th>
                        <th>{Lang::T('Customer Name')}</th>
                        <th>{Lang::T('Email')}</th>
                        <th>{Lang::T('Address')}</th>
                        <th class="ls-num">{Lang::T('Amount')}</th>
                        <th>{Lang::T('Status')}</th>
                        <th>{Lang::T('Created Date')}</th>
                        <th>{Lang::T('Due Date')}</th>
                        <th class="ls-end">{Lang::T('Actions')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $invoices as $invoice}
                    <tr>
                        <td data-label="{Lang::T('Invoice No')}"><b class="ls-strong">{$invoice->number|escape}</b></td>
                        <td data-label="{Lang::T('Customer Name')}">{$invoice->fullname|escape}</td>
                        <td data-label="{Lang::T('Email')}">{$invoice->email|escape}</td>
                        <td data-label="{Lang::T('Address')}" class="ls-dim">{$invoice->address|escape}</td>
                        <td data-label="{Lang::T('Amount')}" class="ls-num">{$invoice->amount|escape}</td>
                        <td data-label="{Lang::T('Status')}">
                            {if $invoice->status == 'paid'}<span class="ls-pill ls-pill--active">{Lang::T('Paid')}</span>
                            {elseif $invoice->status == 'unpaid'}<span class="ls-pill ls-pill--banned">{Lang::T('Unpaid')}</span>
                            {else}<span class="ls-pill ls-pill--inactive">{Lang::T('Pending')}</span>{/if}
                        </td>
                        <td data-label="{Lang::T('Created Date')}" class="ls-dim ls-when">{$invoice->created_at|escape}</td>
                        <td data-label="{Lang::T('Due Date')}" class="ls-dim ls-when">{$invoice->due_date|escape}</td>
                        <td class="ls-end"><div class="ls-row-actions"><a href="{$app_url}/system/uploads/invoices/{$invoice->filename|escape:'url'}" class="ls-btn ls-btn--sm">{Lang::T('View')}</a></div></td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="{$app_url}/ui/ui/scripts/jquery.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/jquery.dataTables.min.js"></script>
<script>
    new DataTable('#invoiceTable');
</script>

{include file="sections/footer.tpl"}