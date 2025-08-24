{if $_c['disable_voucher'] != 'yes' && $stocks['unused']>0 || $stocks['used']>0}
    <div class="panel panel-primary mb20 panel-hovered project-stats table-responsive">
        <div class="panel-heading">Vouchers Stock</div>
        <div class="table-responsive">
            <table class="table table-condensed">
                <thead>
                    <tr>
                        <th>{Lang::T('Package Name')}</th>
                        <th>unused</th>
                        <th>used</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $plans as $stok}
                        <tr>
                            <td>{$stok['name_plan']}</td>
                            <td>{$stok['unused']}</td>
                            <td>{$stok['used']}</td>
                        </tr>
                    </tbody>
                {/foreach}
                <tr>
                    <td>Total</td>
                    <td>{$stocks['unused']}</td>
                    <td>{$stocks['used']}</td>
                </tr>
            </table>
        </div>
    </div>
{/if}