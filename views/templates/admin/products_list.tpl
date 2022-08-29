<div class="panel">
    <div class="panel-heading"><i class="icon-cog"></i> {l s='Lista produktów do zaimportowania do erli.pl' mod='pherli'}</div>
    <div class="form-horizontal">

                <div class="filter-box">
                    <h4>
                        Filtr
                        <span class="show-filtr pull-right">{if $filtr == 1}ukryj filtr{else}pokaż filtr{/if}</span>
                    </h4>
                    <form method="post" class="form-horizontal form-filter"{if $filtr == 1} style="display: block;" {/if}>
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label class="control-label col-lg-3 text-right">{l s='Cennik:' mod='pherli'}</label>
                                    <div class="col-lg-9">
                                        <select name="filter_delivery_price" class="custom-select delivery_price">
                                            <option value=""> {l s='- wybierz cennik -' mod='pherli'} </option>
                                            {foreach $deliveryPrices as $key => $dp}
                                                <option value="{$dp.name}"{if $dp.name == $delivery_price} selected="selected"{/if}> {$dp.name} </option>
                                            {/foreach}
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label class="control-label col-lg-3 text-right">{l s='Czas dostawy:' mod='pherli'}</label>
                                    <div class="col-lg-9">
                                        <select name="filter_delivery_time" class="custom-select delivery_price">
                                            <option value=""> {l s='- wybierz czas dostawy -' mod='pherli'} </option>
                                            {foreach $deliveryTime as $key => $dp}
                                                <option value="{$dp}"{if $dp == $delivery_time} selected="selected"{/if}> {$dp} </option>
                                            {/foreach}
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label class="control-label col-lg-3 text-right">{l s='Status produktu:' mod='pherli'}</label>
                                    <div class="col-lg-9">
                                        <select name="filter_active" class="custom-select delivery_price">
                                            <option value=""> {l s='- wybierz status -' mod='pherli'} </option>
                                            <option value="1"{if $f_active == 1} selected="selected"{/if}> {l s='Aktywny' mod='pherli'} </option>
                                            <option value="2"{if $f_active == 2} selected="selected"{/if}> {l s='Nieaktywny' mod='pherli'} </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label class="control-label col-lg-3 text-right">{l s='Jest na erli.pl:' mod='pherli'}</label>
                                    <div class="col-lg-9">
                                        <select name="filter_erli" class="custom-select delivery_price">
                                            <option value=""> {l s='- wybierz dostępność  na erli.pl -' mod='pherli'} </option>
                                            <option value="1"{if $f_erli == 1} selected="selected"{/if}> {l s='TAK' mod='pherli'} </option>
                                            <option value="2"{if $f_erli == 2} selected="selected"{/if}> {l s='NIE' mod='pherli'} </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                {if $filtr == 1}
                                    &nbsp;&nbsp;<button type="submit" class="btn btn-secondary pull-right" name="submitClearFiltr">{l s='Wyczyść wyniki' mod='pherli'}</button>
                                {/if}
                                <button type="submit" class="btn btn-primary pull-right" name="submitFiltr">{l s='Filtruj' mod='pherli'}</button>
                            </div>
                        </div>
                    </form>
                </div>
        {if empty($products)}
            <p class="alert alert-warning">{l s='Brak produktów' mod='pherli'}</p>
        {else}
            <form method="post">
                <table class="table tablePackageList">
                    <thead>
                    <tr>
                        <th><input type="checkbox" name="all" class="selectAllCheckbox" /></th>
                        <th>{l s='ID' mod='pherli'}</th>
                        <th>{l s='Obraz' mod='pherli'}</th>
                        <th>{l s='Nazwa' mod='pherli'}</th>
                        <th>{l s='Indeks' mod='pherli'}</th>
                        <th>{l s='Kategoria' mod='pherli'}</th>
                        <th>{l s='Atrybuty' mod='pherli'}</th>
                        <th>{l s='Cena netto' mod='pherli'}</th>
                        <th>{l s='Cena brutto' mod='pherli'}</th>
                        <th>{l s='Cennik' mod='pherli'}</th>
                        <th>{l s='Czas dostawy' mod='pherli'}</th>
                        <th>{l s='Status produktu' mod='pherli'}</th>
                        <th>{l s='Jest na erli.pl' mod='pherli'}</th>
                        {if $show_status}
                            <th>{l s='Status erli.pl' mod='pherli'}</th>
                        {/if}
                    </tr>
                    </thead>
                    <tbody>
                        {foreach $products as $product}
                            <tr{if $product.erli == 1} class="success"{/if}>
                                <td>
                                    <input class="productChkBox" type="checkbox" id="bulk_action_selected_products-{$product.id_product}" name="products[]" value="{$product.id_product}">
                                </td>
                                <td>
                                    <a href="{$product.productLink}">{$product.id_product}</a>
                                </td>
                                <td>
                                    <a href="{$product.productLink}"><img src="{$product.imageCover}" alt="" style="max-width: 40px;" /></a>
                                </td>
                                <td><a href="{$product.productLink}">{$product.name}</a></td>
                                <td>{$product.reference}</td>
                                <td>{$product.category_name}</td>
                                <td>
                                    {if $product.attributes > 0}
                                        <i class="material-icons action-enabled">check</i> ({$product.attributes})
                                    {else}
                                        <i class="material-icons action-disabled">clear</i>
                                    {/if}
                                </td>
                                <td>{$product.price|number_format:2:'.':''}</td>
                                <td>{$product.price2|number_format:2:'.':''}</td>
                                <td>{$product.deliveryPrice}</td>
                                <td>{$product.deliveryTime}</td>
                                <td>
                                    {if $product.active == 1}
                                        <i class="material-icons action-enabled">check</i>
                                    {else}
                                        <i class="material-icons action-disabled">clear</i>
                                    {/if}
                                </td>
                                <td>
                                    {if $product.inErli == 1}
                                        <i class="material-icons action-enabled">check</i>
                                    {else}
                                        <i class="material-icons action-disabled">clear</i>
                                    {/if}
                                </td>
                                {if $show_status}
                                <td>
                                    {if $product.erli == 1}
                                        <i class="material-icons action-enabled">check</i>
                                    {else}
                                        <i class="material-icons action-disabled">clear</i>
                                    {/if}
                                </td>
                                {/if}
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
                <div class="paginPackageListBox" style="display: block;min-height: 60px;">
                    <div class="col-lg-12">
                        <div class="pagination">
                            {l s='Display'}
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                {$selected_pagination}
                                <i class="icon-caret-down"></i>
                            </button>
                            <ul class="dropdown-menu">
                                {foreach $pagination AS $value}
                                    <li>
                                        <a href="javascript:void(0);" class="pagination-items-page" data-items="{$value|intval}" data-list-id="1">{$value}</a>
                                    </li>
                                {/foreach}
                            </ul>
                            / {$list_total} {l s='result(s)'}

                        </div>
                        <script type="text/javascript">
                            $('.pagination-items-page').on('click',function(e){
                                e.preventDefault();
                                $('#'+$(this).data("list-id")+'-pagination-items-page').val($(this).data("items")).closest("form").submit();
                            });
                        </script>
                        <ul class="pagination pull-right">
                            <li {if $plPage <= 1}class="disabled"{/if}>
                                <a href="javascript:void(0);" class="pagination-link" data-page="1" data-list-id="1">
                                    <i class="icon-double-angle-left"></i>
                                </a>
                            </li>
                            <li {if $plPage <= 1}class="disabled"{/if}>
                                <a href="javascript:void(0);" class="pagination-link" data-page="{$plPage - 1}" data-list-id="1">
                                    <i class="icon-angle-left"></i>
                                </a>
                            </li>
                            {assign var=p value=0}
                            {while $p++ < $pages_all}
                                {if $p < $plPage-2}
                                    <li class="disabled">
                                        <a href="javascript:void(0);">&hellip;</a>
                                    </li>
                                    {assign var=p value=$plPage-3}
                                {elseif $p > $plPage+2}
                                    <li class="disabled">
                                        <a href="javascript:void(0);">&hellip;</a>
                                    </li>
                                    {assign p $pages_all}
                                {else}
                                    <li {if $p == $plPage}class="active"{/if}>
                                        <a href="javascript:void(0);" class="pagination-link" data-page="{$p}" data-list-id="1">{$p}</a>
                                    </li>
                                {/if}
                            {/while}
                            <li {if $plPage >= $pages_all}class="disabled"{/if}>
                                <a href="javascript:void(0);" class="pagination-link" data-page="{$plPage + 1}" data-list-id="1">
                                    <i class="icon-angle-right"></i>
                                </a>
                            </li>
                            <li {if $plPage >= $pages_all}class="disabled"{/if}>
                                <a href="javascript:void(0);" class="pagination-link" data-page="{$pages_all}" data-list-id="1">
                                    <i class="icon-double-angle-right"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
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
                                    Dodaj do erli.pl (do synchronizacji)
                                </button>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item" name="remove_from_erli">
                                    <i class="material-icons">delete</i>
                                    Usuń z erli.pl (do synchronizacji)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <br />
                <div class="row">
                    <div class="col-lg-3">
                        <div class="">
                            <label>Zmień cennik masowo</label>
                            <div class="">
                                <select name="delivery_price" class="custom-select delivery_price">
                                    <option value=""> {l s='- wybierz cennik -' mod='pherli'} </option>
                                    {foreach $deliveryPrices as $key => $dp}
                                        <option value="{$dp.name}"> {$dp.name} </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="">
                            <label>Zmień czas dostawy</label>
                            <div class="">
                                <select name="delivery_time" class="custom-select delivery_time">
                                    <option value=""> {l s='- wybierz czas -' mod='pherli'} </option>
                                    {foreach $deliveryTime as $key => $dt}
                                        <option value="{$key}"> {$dt} </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <br />
                <button type="submit" class="btn btn-danger" name="saveDeliveryInfo">Zmień</button>
            </form>
            <form method="post">
                <input type="hidden" name="plPage" value="1" id="submitFilterr1" />
                <input type="hidden" name="plUrl" value="{$currentPage}" id="submitFilter1" />
            </form>
            <form method="post">
                <input type="hidden" id="1-pagination-items-page" name="ERLI_PL_PERPAGE" value="{$selected_pagination|intval}" />
            </form>
        {/if}
    </div>
    <div class="panel-footer">
        <form method="post">
            <button type="submit" name="submitClearProducts" class="btn btn-warning btn-md">{l s='Wyczyść rezultat' mod='pherli'}</button>
        </form>
    </div>
</div>

<script type="text/javascript">
    $('.pagination-link').on('click',function(e){
        e.preventDefault();

        if (!$(this).parent().hasClass('disabled'))
            $('#submitFilterr'+$(this).data("list-id")).val($(this).data("page")).closest("form").submit();
    });
    $(document).ready(function() {
        $('.selectAllCheckbox').on('click', function () {
            var sel = false;
            if ($(this).is(':checked')) {
                sel = true;
            }
            $('.productChkBox').each(function(){
                if (sel === true) {
                    $(this).attr('checked', 'checked');
                } else {
                    $(this).removeAttr('checked');
                }
            });
        });
    });
</script>
