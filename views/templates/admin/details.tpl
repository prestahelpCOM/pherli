<div class="row">
    <div class="col-lg-3">
        <div class="panel">
            <div class="form-horizontal">
                <table class="table">
                    <tbody>
                        <tr>
                            <td><b>Numer zamówienia na erli.pl</b></td>
                            <td>{$order.id_payload}</td>
                        </tr>
                        <tr>
                            <td><b>Data zakupu</b></td>
                            <td>{$order.date_add}</td>
                        </tr>
                        <tr>
                            <td><b>Do zapłaty</b></td>
                            <td><span style="font-size: 20px;color: #aacd4e;font-weight: 700;">{$order.total_pay}</span></td>
                        </tr>
                        {if $payment.type == ''}
                        <tr>
                            <td colspan="2">
                                <form method="post">
                                    <button type="submit" name="addPayment" value="{$order.id_order}" class="btn btn-warning btn-block" >Sprawdź metodę płatności</button>
                                </form>
                            </td>
                        </tr>
                        {else}
                        <tr>
                            <td><b>Płatność</b></td>
                            <td><span>[{$payment.operator}] {$payment.type}</span></td>
                        </tr>
                        {/if}
                    </tbody>
                </table>
                {if $order.in_shop == 0}
                    <br />
                    <a href="{$orderShop}&addOrderShop={$order.id_order}" class="btn btn-success">Dodaj zamówienie do sklepu</a>
                {/if}
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="panel">
            <div class="form-horizontal">
                <h3>Dostawa</h3>
                <p>
                    Metoda dostawy: <b>{$delivery.name}</b><br />
                    Koszt: <b>{$delivery.total_pay}</b>
                </p>
                {if $delivery.typeId == 'paczkomat'}
                    <p>
                        {$delivery.pickupPlace}
                    </p>
                {/if}
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="panel">
            <div class="form-horizontal">
                <h3>Dane kupującego</h3>
                <p>
                    {$address.firstname} {$address.lastname}<br />
                    {$address.address}<br />
                    {$address.zip} {$address.city}<br />
                    <br />
                    Email: {$address.email}<br />
                    Telefon: {$address.phone}<br />
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="panel">
            <div class="form-horizontal">
                <h3>Wiadomość od kupującego</h3>
                <p>
                    {$message.message}
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="panel">
            <div class="panel-heading">{l s='Szczegóły zamówienia' mod='pherli'}</div>
            <div class="form-horizontal">
                <table class="table">
                    <thead>
                        <tr>
                            <th colspan="2">Produkt</th>
                            <th>Cena za jednostkę</th>
                            <th>Ilość</th>
                            <th>Razem</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $items as $item}
                            <tr>
                                <td>
                                    <img src="{$item.image}" alt="" style="max-width: 40px;" />
                                </td>
                                <td>
                                    {$item.name}<br />
                                    <label>Indeks:</label> {$item.sku}
                                </td>
                                <td>{$item.unit}</td>
                                <td>{$item.quantity}</td>
                                <td>{$item.total}</td>
                            </tr>
                        {/foreach}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right"><b>Razem:</b></td>
                            <td>{$order.total_product}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-right"><b>Wysyłka:</b></td>
                            <td>{$delivery.total_pay}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-right"><b>Do zapłaty:</b></td>
                            <td><b>{$order.total_pay}</b></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
<a href="{$orderShop}" class="btn btn-primary">powrót do listy zamówień</a>
