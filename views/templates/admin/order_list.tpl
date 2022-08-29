<div class="panel">
    <div class="panel-heading"><i class="icon-cog"></i> {l s='Lista zamówień zaimportowanych z erli.pl' mod='pherli'}</div>
    <div class="form-horizontal">
        {if empty($orders)}
            <p class="alert alert-warning">{l s='Nie ma jeszcze dodanych zamówień z erli.pl' mod='pherli'}</p>
        {else}
            <button type="button" class="btn btn-success btnRefresh"><i class="material-icons" style="font-size: 12px;">replay</i> Odśwież</button>
            <form method="post">
                <table class="table tablePackageList">
                    <thead>
                    <tr>
                        <th>{l s='ID' mod='pherli'}</th>
                        <th>{l s='ID zamówienia' mod='pherli'}</th>
                        <th>{l s='Indeks erli.pl' mod='pherli'}</th>
                        <th>{l s='Klient' mod='pherli'}</th>
                        <th>{l s='Razem' mod='pherli'}</th>
                        <th>{l s='Dostawa' mod='pherli'}</th>
                        <th>{l s='Status' mod='pherli'}</th>
                        <th>{l s='Data dodania' mod='pherli'}</th>
                        <th>{l s='Akcje' mod='pherli'}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $orders as $order}
                        <tr>
                            <td>{$order.id_order}</td>
                            <td>{if $order.in_shop == 0}--{else}<a href="{$order.link}">{$order.id_order_shop}</a>{/if}</td>
                            <td>{$order.id_payload}</td>
                            <td>{$order.customer.firstname} {$order.customer.lastname}</td>
                            <td>{$order.total_payment}</td>
                            <td>{$order.delivery.name}</td>
                            <td>{$order.status}</td>
                            <td>{$order.date_add}</td>
                            <td>
                                <a href="{$orderShop}&details={$order.id_order}" class="btn btn-xs btn-primary">szczegóły zamówienia</a>
                                {if $order.in_shop == 0} &nbsp;|&nbsp;<a href="{$orderShop}&addOrderShop={$order.id_order}" class="btn btn-xs btn-success">DODAJ ZAMÓWIENIE DO SKLEPU</a>{else}{/if}
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
        {/if}
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $('.btnRefresh').on('click', function () {
            location.reload();
        })
    });
</script>
