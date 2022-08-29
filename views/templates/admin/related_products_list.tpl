<div class="panel">
    <div class="panel-heading"><i class="icon-cog"></i> {l s='Lista produktów sklep - erli.pl' mod='pherli'}<span class="badge">{$products|@count}</span></div>
    <div class="form-horizontal">
        {if !empty($products)}
        <table class="table tableProductList tablePackageList">
            <thead>
            <tr>
                <th>{l s='ID' mod='pherli'}</th>
                <th>{l s='Nazwa w sklepie' mod='pherli'}</th>
                <th>{l s='Indeks w sklepie' mod='pherli'}</th>
                <th>{l s='Takie same?' mod='pherli'}</th>
                <th>{l s='Indeks na erli.pl' mod='pherli'}</th>
                <th>{l s='Nazwa na erli.pl' mod='pherli'}</th>
                <th>{l s='Różnica sklep - erli.pl' mod='pherli'}</th>
                <th>{l s='W sklepie?' mod='pherli'}</th>
                <th>{l s='Akcja' mod='pherli'}</th>
            </tr>
            </thead>
            <tbody>
            {foreach $products as $product}
                <tr{if $product.typ == 'danger'} class="danger"{/if}>
                    <td>{$product.externalId}</td>
                    <td>{$product.shopName}</td>
                    <td>{$product.reference}</td>
                    <td>{if $product.sku != $product.reference}<i class="material-icons action-disabled">clear</i>{else}<i class="material-icons action-enabled">check</i>{/if}</td>
                    <td>{$product.sku}</td>
                    <td>{$product.name}</td>
                    <td>{$product.similar}%</td>
                    <td>{if $product.inShop == 0}<i class="material-icons action-disabled">clear</i>{else}<i class="material-icons action-enabled">check</i>{/if}</td>
                    <td>{if $product.inShop == 1}<a href="{$product.link}" target="_blank">przejdź do produktu</a>{/if}</td>
                </tr>
            {/foreach}
            </tbody>
            <tfoot>
            <tr>
                <td colspan="6"></td>
                <td>{$added}</td>
                <td colspan="6"></td>
            </tr>
            </tfoot>
        </table>
        {else}
            <p class="alert alert-info">
                Brak produktów.
                {if $count == 0}
                    <br />
                    Możesz wykonać skrypt klikając w <a href="{$cronLink}" target="_blank">ten link</a>.
                {/if}
            </p>
        {/if}
    </div>
