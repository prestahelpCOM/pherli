<div class="panel">
    <div class="panel-heading"><i class="icon-cog"></i> {l s='Szczegóły synchronizacji' mod='pherli'}</div>
    <div class="form-horizontal">
        <div class="row">
            <div class="col-lg-4">
                <table class="table">
                    <tbody>
                        <tr><td>Typ synchronizacji:</td><td><b>{$sync.name}</b></td></tr>
                        <tr><td>Start:</td><td><b>{$sync.date_add}</b></td></tr>
                        <tr><td>Koniec:</td><td><b>{$sync.date_end}</b></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="col-lg-4">
                {if $sync.type == 1}
                    <table class="table">
                        <tbody>
                        <tr><td>Nowych zamówień:</td><td><b>{$sync.orders_add_all}</b></td></tr>
                        <tr{if $sync.orders_add < $sync.orders_add_all} class="danger"{/if}><td>Dodano:</td><td><b>{$sync.orders_add}</b></td></tr>
                        <tr><td>Zaktualizowano zamówienia:</td><td><b>{$sync.orders_update}</b></td></tr>
                        </tbody>
                    </table>
                {/if}
                {if $sync.type == 2}
                    <table class="table">
                        <tbody>
                            <tr><td>Produkty do dodania:</td><td><b>{$sync.product_add_all}</b></td></tr>
                            <tr{if $sync.product_add < $sync.product_add_all} class="danger"{/if}><td>Dodano:</td><td><b>{$sync.product_add}</b></td></tr>
                            <tr><td>Produkty do aktualizacji:</td><td><b>{$sync.product_update_all}</b></td></tr>
                            <tr{if $sync.product_update < $sync.product_update_all} class="danger"{/if}><td>Zaktualizowano:</td><td><b>{$sync.product_update}</b></td></tr>
                        </tbody>
                    </table>
                {/if}
                {if $sync.type == 3}
                    <table class="table">
                        <tbody>
                            <tr><td>Produkty do aktualizacji:</td><td><b>{$sync.product_update_all}</b></td></tr>
                            <tr{if $sync.product_update < $sync.product_update_all} class="danger"{/if}><td>Zaktualizowano:</td><td><b>{$sync.product_update}</b></td></tr>
                        </tbody>
                    </table>
                {/if}
            </div>
        </div>
        {if !empty($errors)}
        <div class="row">
            <div class="col-lg-12">
                <hr />
                <h4>Lista błędów <span class="badge badge-danger">{$errors|count}</span></h4>
                <hr />
                <div class="">
                    {foreach $errors as $error}
                        <div style="margin-bottom: 35px;">
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td style="width: 100px;">Produkt:</td>
                                        <td><b>{if isset($error.product_name)}{$error.product_name}{else}--{/if}</b></td>
                                    </tr>
                                    <tr>
                                        <td>Kod błędu:</td>
                                        <td>{$error.status}</td>
                                    </tr>
                                    <tr>
                                        <td>Data błędu:</td>
                                        <td>{$error.date_add}</td>
                                    </tr>
                                    <tr>
                                        <td>Rodzaj błędu:</td>
                                        <td>
                                            {if $error.status == 409}
                                                <b>{$error.error}</b>
                                            {/if}
                                            {if $error.status == 400}
                                                <ul>
                                                {foreach $error.error as $er}
                                                    <li><b>{$er}</b></li>
                                                {/foreach}
                                                </ul>
                                            {/if}
                                            {if $error.status == 429 || $error.status == 404}
                                                <b>{$error.error}</b>
                                            {/if}
                                            {if $error.status == 503}
                                                <b>E#503 - Serwis niedostępny</b>
                                            {/if}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    {/foreach}
                </div>
            </div>
        </div>
        {/if}
    </div>
</div>

<a href="{$orderShop}" class="btn btn-primary">{l s='powrót do listy synchronizacji' mod='pherli'}</a>
