{if $errors}
    <div class="alert alert-errors">
        {$errors}
    </div>
{/if}

<div class="card mt-2" id="view_erli_order_block">
    <div class="card-header">
        <h3 class="card-header-title">
            {l s='Zakupy z erli.pl' mod='pherli'}
        </h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-3">
                <div class="panel">
                    <div class="form-horizontal">
                        <table class="table">
                            <tbody>
                            <tr>
                                <td><b>{l s='Numer zamówienia na erli.pl' mod='pherli'}</b></td>
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
                                            <button type="submit" name="addPaymentInfo" value="{$order.id_order}" class="btn btn-warning btn-block" >Sprawdź metodę płatności</button>
                                        </form>
                                    </td>
                                </tr>
                            {else}
                                <tr>
                                    <td><b>Płatność</b></td>
                                    <td><span>[{$payment.operator}] {$payment.type}</span></td>
                                </tr>
                            {/if}
                            {if !empty($addressInvoiceErli)}
                                <tr>
                                    <td colspan="2">
                                        <form method="post">
                                            <button type="submit" name="addInvoiceAddress" value="{$order.id_order}" class="btn btn-danger btn-block" >Dodaj adres do faktury <small>- funkcja eksperymentalna</small></button>
                                        </form>
                                    </td>
                                </tr>
                            {/if}
                            </tbody>
                        </table>
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

        </div>
        <div class="row">
            {if !$isPayment}
                <div class="col-lg-2">
                    <form method="post">
                        <input type="hidden" name="id_order_erli" value="{$order.id_order}" />
                        <input type="hidden" name="id_order" value="{$order.id_order_shop}" />
                        <button type="submit" name="updatePayment" class="btn btn-warning">{l s='Zaktualizuj metodę płatności' mod='pherli'}</button>
                    </form>
                </div>
            {/if}
            {if !$isDelivery}
                <div class="col-lg-2">
                    <form method="post">
                        <input type="hidden" name="id_order_erli" value="{$order.id_order}" />
                        <input type="hidden" name="id_order" value="{$order.id_order_shop}" />
                        <button type="submit" name="updateDelivery" class="btn btn-warning">{l s='Zaktualizuj metodę dostawy' mod='pherli'}</button>
                    </form>
                </div>
            {/if}
            {if $delivery.sendTracking == 0}
                <div class="col-lg-3">
                    <form method="post">
                        <input type="hidden" name="id_order_erli" value="{$order.id_order}" />
                        <input type="hidden" name="id_order" value="{$order.id_order_shop}" />
                        <button type="submit" name="updateTrackingNumber" class="btn btn-success">{l s='Dodaj numer śledzenia do zamówienia na erli.pl' mod='pherli'}</button>
                    </form>
                </div>
            {/if}
        </div>
    </div>
</div>
