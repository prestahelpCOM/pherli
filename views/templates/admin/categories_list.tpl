<div class="panel">
    <div class="panel-heading"><i class="icon-cog"></i> {l s='Lista kategorii produktów do zaimportowania do erli.pl' mod='pherli'}</div>
    <div class="form-horizontal">
        {if empty($categories)}
            <p class="alert alert-warning">{l s='Nie ma jeszcze dodanych żadnych kategorii w sklepie' mod='pherli'}</p>
        {else}
            <form method="post">
                <table class="table tablePackageList">
                    <thead>
                    <tr>
                        <th><input type="checkbox" name="all" class="selectAllCheckbox" /></th>
                        <th>{l s='ID' mod='pherli'}</th>
                        <th>{l s='Nazwa' mod='pherli'}</th>
                        <th>{l s='Kategoria nadrzędna' mod='pherli'}</th>
                        <th>{l s='Jest na erli.pl' mod='pherli'}</th>
                        <th>{l s='Ilość produktów w kategorii' mod='pherli'}</th>
                        <th>{l s='Ilość produktów w kategorii na erli.pl' mod='pherli'}</th>
                    </tr>
                    </thead>
                    <tbody>
                        {foreach $categories as $category}
                        <tr class="{if $category.erli == 1}success{else}{if $category.erli_count > 0}warning{/if}{/if}">
                            <td>
                                <input class="categoryChkBox" type="checkbox" id="bulk_action_selected_products-{$category.id_category}" name="categories[]" value="{$category.id_category}">
                            </td>
                            <td>{$category.id_category}</td>
                            <td>{$category.name}</td>
                            <td>-</td>
                            <td>
                                {if $category.erli == 1}
                                    <i class="material-icons action-enabled">check</i>
                                {else}
                                    <i class="material-icons action-disabled">clear</i>
                                {/if}
                            </td>
                            <td>
                                {$category.product_count}
                            </td>
                            <td>
                                {$category.erli_count}
                            </td>
                        </tr>
                            {if !empty($category.subcategories)}
                                {foreach $category.subcategories as $cs}
                                    <tr class="{if $cs.erli == 1}success{else}{if $cs.erli_count > 0}warning{/if}{/if}">
                                        <td>
                                            <input class="categoryChkBox" type="checkbox" id="bulk_action_selected_products-{$cs.id_category}" name="categories[]" value="{$cs.id_category}">
                                        </td>
                                        <td>{$cs.id_category}</td>
                                        <td>&nbsp;&nbsp;&nbsp;&nbsp;> {$cs.name}</td>
                                        <td>{$category.name}</td>
                                        <td>
                                            {if $cs.erli == 1}
                                                <i class="material-icons action-enabled">check</i>
                                            {else}
                                                <i class="material-icons action-disabled">clear</i>
                                            {/if}
                                        </td>
                                        <td>
                                            {$cs.product_count}
                                        </td>
                                        <td>
                                            {$cs.erli_count}
                                        </td>
                                    </tr>
                                    {if !empty($cs.subcategories)}
                                        {foreach $cs.subcategories as $cs2}
                                            <tr class="{if $cs2.erli == 1}success{else}{if $cs2.erli_count > 0}warning{/if}{/if}">
                                                <td>
                                                    <input class="categoryChkBox" type="checkbox" id="bulk_action_selected_products-{$cs2.id_category}" name="categories[]" value="{$cs2.id_category}">
                                                </td>
                                                <td>{$cs2.id_category}</td>
                                                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;> {$cs2.name}</td>
                                                <td>{$category.name} > {$cs.name}</td>
                                                <td>
                                                    {if $cs2.erli == 1}
                                                        <i class="material-icons action-enabled">check</i>
                                                    {else}
                                                        <i class="material-icons action-disabled">clear</i>
                                                    {/if}
                                                </td>
                                                <td>
                                                    {$cs2.product_count}
                                                </td>
                                                <td>
                                                    {$cs2.erli_count}
                                                </td>
                                            </tr>
                                        {/foreach}
                                    {/if}
                                {/foreach}
                            {/if}
                        {/foreach}
                    </tbody>
                </table>
                <div class="">
                    <div class="d-inline-block" bulkurl="{$bulkUrl}active_all"  redirecturl="{$bulkUrl}" redirecturlnextpage="{$bulkUrl}">
                        <div class="btn-group dropdown bulk-catalog">
                            <button type="button" id="product_bulk_menu" class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                                Działania masowe
                                <i class="icon-caret-up"></i>
                            </button>
                            <div class="dropdown-menu">
                                <button type="submit" class="dropdown-item" name="add_to_erli">
                                    <i class="material-icons">radio_button_checked</i>
                                    Dodaj do erli.pl
                                </button>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item" name="remove_from_erli">
                                    <i class="material-icons">delete</i>
                                    Usuń z erli.pl
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        {/if}
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $('.selectAllCheckbox').on('click', function () {
            var sel = false;
            if ($(this).is(':checked')) {
                sel = true;
            }
            $('.categoryChkBox').each(function(){
                if (sel === true) {
                    $(this).attr('checked', 'checked');
                } else {
                    $(this).removeAttr('checked');
                }
            });
        });
    });
</script>
