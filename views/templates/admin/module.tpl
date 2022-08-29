{if $update}
    <div class="alert alert-warning">
        Zaktualizuj moduł klikając na poniższy przycisk.<br /><br />
        <form method="post">
            <button type="submit" name="updateModule" class="btn btn-success">{l s='Aktualizuj moduł' mod='pherli'}</button>
        </form>
    </div>
{/if}

<div class="alert alert-info">
    <p>Twoja wersja modułu <b>{$module_name}</b> to <b>{$module_version}</b></p>
</div>

<form method="post">
    <div class="panel">
        <div class="panel-heading"><i class="icon-cog"></i> {l s='Ustawienia' mod='pherli'}</div>
        <div class="form-horizontal">
            <div class="rows">
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='API Token:' mod='pherli'}<sup>*</sup></label>
                    <div class="col-lg-4">
                        <input type="text" name="ERLI_API_TOKEN" value="{$api_token}" class="form-control" required />
                    </div>
                </div>
                    <div class="form-group">
                        <label class="control-label col-lg-3 text-right">{l s='Tryb testowy?' mod='pherli'}</label>
                        <div class="col-lg-9">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input id="sandbox_on" onClick="toggleDraftWarning(false);showOptions(true);showRedirectProductOptions(false);" name="ERLI_API_SANDBOX" value="1" type="radio" {if $api_sand == 1}checked="checked"{/if} />
                                <label class="radioCheck" for="sandbox_on">{l s='TAK' mod='pherli'}</label>
                                <input id="sandbox_off" onClick="toggleDraftWarning(true);showOptions(false);showRedirectProductOptions(true);" name="ERLI_API_SANDBOX" value="0" type="radio" {if $api_sand == 0}checked="checked"{/if} />
                                <label class="radioCheck" for="sandbox_off">{l s='NIE' mod='pherli'}</label>
                                <a class="slide-button btn"></a>
                            </span>
                        </div>
                    </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Czy eksportować nowo dodane produkty?' mod='pherli'}</label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input id="export_new_on" onClick="toggleDraftWarning(false);showOptions(true);showRedirectProductOptions(false);" name="ERLI_EXPORT_NEW" value="1" type="radio" {if $export_new == 1}checked="checked"{/if} />
                            <label class="radioCheck" for="export_new_on">{l s='TAK' mod='pherli'}</label>
                            <input id="export_new_off" onClick="toggleDraftWarning(true);showOptions(false);showRedirectProductOptions(true);" name="ERLI_EXPORT_NEW" value="0" type="radio" {if $export_new == 0}checked="checked"{/if} />
                            <label class="radioCheck" for="export_new_off">{l s='NIE' mod='pherli'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Czy automatycznie dodać zamówienie z importu?' mod='pherli'}</label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input id="order_import_on" onClick="toggleDraftWarning(false);showOptions(true);showRedirectProductOptions(false);" name="ERLI_ORDER_IMPORT" value="1" type="radio" {if $order_import == 1}checked="checked"{/if} />
                            <label class="radioCheck" for="order_import_on">{l s='TAK' mod='pherli'}</label>
                            <input id="order_import_off" onClick="toggleDraftWarning(true);showOptions(false);showRedirectProductOptions(true);" name="ERLI_ORDER_IMPORT" value="0" type="radio" {if $order_import == 0}checked="checked"{/if} />
                            <label class="radioCheck" for="order_import_off">{l s='NIE' mod='pherli'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Sprawdzaj zmiany w produktach?' mod='pherli'}</label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input id="product_change_on" onClick="toggleDraftWarning(false);showOptions(true);showRedirectProductOptions(false);" name="ERLI_PRODUCT_CHANGE" value="1" type="radio" {if $product_change == 1}checked="checked"{/if} />
                            <label class="radioCheck" for="product_change_on">{l s='TAK' mod='pherli'}</label>
                            <input id="product_change_off" onClick="toggleDraftWarning(true);showOptions(false);showRedirectProductOptions(true);" name="ERLI_PRODUCT_CHANGE" value="0" type="radio" {if $product_change == 0}checked="checked"{/if} />
                            <label class="radioCheck" for="product_change_off">{l s='NIE' mod='pherli'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                    <small class="hint">{l s='Włączenie tej opcji spowoduje spowolnienie działania sklepu podczas aktualizacji produktów.' mod='pherli'}</small>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Wymuś aktualizację wszystkich pól' mod='pherli'}</label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input id="product_changeall_on" onClick="toggleDraftWarning(false);showOptions(true);showRedirectProductOptions(false);" name="ERLI_PRODUCT_CHANGE_ALL" value="1" type="radio" {if $ERLI_PRODUCT_CHANGE_ALL == 1}checked="checked"{/if} />
                            <label class="radioCheck" for="product_changeall_on">{l s='TAK' mod='pherli'}</label>
                            <input id="product_changeall_off" onClick="toggleDraftWarning(true);showOptions(false);showRedirectProductOptions(true);" name="ERLI_PRODUCT_CHANGE_ALL" value="0" type="radio" {if $ERLI_PRODUCT_CHANGE_ALL == 0}checked="checked"{/if} />
                            <label class="radioCheck" for="product_changeall_off">{l s='NIE' mod='pherli'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                    <label class="control-label col-lg-3 text-right"></label>
                    <small class="hint">{l s='Gdy wartość SPRAWDZAJ ZMIANY W PRODUKTACH jest na NIE. Włączenie tej opcji spowoduje spowolnienie działania sklepu podczas aktualizacji produktów.' mod='pherli'}</small>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Co robimy ze zdjęciami?' mod='pherli'}</label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-xl">
                            <input id="image_on" onClick="toggleDraftWarning(false);showOptions(true);showRedirectProductOptions(false);" name="ERLI_IMAGE_ACTION" value="1" type="radio" {if $image_action == 1}checked="checked"{/if} />
                            <label class="radioCheck" for="image_on">{l s='Zamieniamy' mod='pherli'}</label>
                            <input id="image_off" onClick="toggleDraftWarning(true);showOptions(false);showRedirectProductOptions(true);" name="ERLI_IMAGE_ACTION" value="0" type="radio" {if $image_action == 0}checked="checked"{/if} />
                            <label class="radioCheck" for="image_off">{l s='Aktualizujemy' mod='pherli'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Domyślny cennik' mod='pherli'}:</label>
                    <div class="col-lg-3">
                        <select name="ERLI_DELIVERY_PRICES">
                            <option value=""> {l s='- wybierz cennik -' mod='pherli'} </option>
                            {foreach $deliveryPrices as $dp}
                                <option value="{$dp}"{if $dp == $delivery_prices} selected="selected"{/if}> {$dp} </option>
                            {/foreach}
                        </select>
                    </div>
                    {if empty($deliveryPrices)}
                    <div class="col-lg-6">
                        <p class="alert alert-info">{l s='Dodaj cenniki w panelu sklepu na erli.pl' mod='pherli'}</p>
                    </div>
                    {/if}
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Czas wysyłki' mod='pherli'}:</label>
                    <div class="col-lg-2">
                        <select name="ERLI_DELIVERY_TIME_DEFAULT">
                            <option value=""> {l s='- wybierz czas -' mod='pherli'} </option>
                            {foreach $deliveryTime as $key => $dt}
                            <option value="{$key}"{if $key == $delivery_time} selected="selected"{/if}> {$dt} </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='DEV MODE?' mod='pherli'}</label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input id="dev_mode_on" onClick="toggleDraftWarning(false);showOptions(true);showRedirectProductOptions(false);" name="ERLI_DEV_MODE" value="1" type="radio" {if $erli_dm == 1}checked="checked"{/if} />
                            <label class="radioCheck" for="dev_mode_on">{l s='TAK' mod='pherli'}</label>
                            <input id="dev_mode_off" onClick="toggleDraftWarning(true);showOptions(false);showRedirectProductOptions(true);" name="ERLI_DEV_MODE" value="0" type="radio" {if $erli_dm == 0}checked="checked"{/if} />
                            <label class="radioCheck" for="dev_mode_off">{l s='NIE' mod='pherli'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Status zamówienia: W trakcie przygotowania' mod='pherli'}</label>
                    <div class="col-lg-2">
                        <select name="ERLI_STATUS_SHOP_preparing">
                            <option value=""> {l s='- wybierz status -' mod='pherli'} </option>
                            {foreach $states as $state}
                                <option value="{$state.id_order_state}"{if $state.id_order_state == $status_o1} selected="selected"{/if}> {$state.name} </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Status zamówienia: Gotowe do nadania' mod='pherli'}</label>
                    <div class="col-lg-2">
                        <select name="ERLI_STATUS_SHOP_readyToPickup">
                            <option value=""> {l s='- wybierz status -' mod='pherli'} </option>
                            {foreach $states as $state}
                                <option value="{$state.id_order_state}"{if $state.id_order_state == $status_o2} selected="selected"{/if}> {$state.name} </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Status zamówienia: Przygotowane, czeka na kuriera' mod='pherli'}</label>
                    <div class="col-lg-2">
                        <select name="ERLI_STATUS_SHOP_waitingForCourier">
                            <option value=""> {l s='- wybierz status -' mod='pherli'} </option>
                            {foreach $states as $state}
                                <option value="{$state.id_order_state}"{if $state.id_order_state == $status_o3} selected="selected"{/if}> {$state.name} </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Status zamówienia: Wysłano' mod='pherli'}</label>
                    <div class="col-lg-2">
                        <select name="ERLI_STATUS_SHOP_sent">
                            <option value=""> {l s='- wybierz status -' mod='pherli'} </option>
                            {foreach $states as $state}
                                <option value="{$state.id_order_state}"{if $state.id_order_state == $status_o4} selected="selected"{/if}> {$state.name} </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Status zamówienia dla płatności online - zapłacono:' mod='pherli'}</label>
                    <div class="col-lg-2">
                        <select name="ERLI_STATUS_SHOP_paid">
                            <option value=""> {l s='- wybierz status -' mod='pherli'} </option>
                            {foreach $states as $state}
                                <option value="{$state.id_order_state}"{if $state.id_order_state == $status_o5} selected="selected"{/if}> {$state.name} </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Status zamówienia dla płatności online - niezapłacono:' mod='pherli'}</label>
                    <div class="col-lg-2">
                        <select name="ERLI_STATUS_SHOP_nopaid">
                            <option value=""> {l s='- wybierz status -' mod='pherli'} </option>
                            {foreach $states as $state}
                                <option value="{$state.id_order_state}"{if $state.id_order_state == $status_o6} selected="selected"{/if}> {$state.name} </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Status zamówienia gdy anulowano:' mod='pherli'}</label>
                    <div class="col-lg-2">
                        <select name="ERLI_STATUS_SHOP_canceled">
                            <option value=""> {l s='- wybierz status -' mod='pherli'} </option>
                            {foreach $states as $state}
                                <option value="{$state.id_order_state}"{if $state.id_order_state == $status_o7} selected="selected"{/if}> {$state.name} </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Zaokrąglanie cen?' mod='pherli'}</label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input id="round_on" onClick="toggleDraftWarning(false);showOptions(true);showRedirectProductOptions(false);" name="ERLI_PRICE_ROUND" value="1" type="radio" {if $round == 1}checked="checked"{/if} />
                            <label class="radioCheck" for="round_on">{l s='TAK' mod='pherli'}</label>
                            <input id="round_off" onClick="toggleDraftWarning(true);showOptions(false);showRedirectProductOptions(true);" name="ERLI_PRICE_ROUND" value="0" type="radio" {if $round == 0}checked="checked"{/if} />
                            <label class="radioCheck" for="round_off">{l s='NIE' mod='pherli'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Sposób zaokrąglania' mod='pherli'}</label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input id="round_type_on" onClick="toggleDraftWarning(false);showOptions(true);showRedirectProductOptions(false);" name="ERLI_PRICE_ROUND_TYPE" value="1" type="radio" {if $round_type == 1}checked="checked"{/if} />
                            <label class="radioCheck" for="round_type_on">{l s='W górę' mod='pherli'}</label>
                            <input id="round_type_off" onClick="toggleDraftWarning(true);showOptions(false);showRedirectProductOptions(true);" name="ERLI_PRICE_ROUND_TYPE" value="0" type="radio" {if $round_type == 0}checked="checked"{/if} />
                            <label class="radioCheck" for="round_type_off">{l s='W dół' mod='pherli'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='FirstSync - przywróć archiwalne produkty istniejące w integracji' mod='pherli'}</label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input id="fs_archived_on" onClick="toggleDraftWarning(false);showOptions(true);showRedirectProductOptions(false);" name="ERLI_FS_ARCHIVED" value="1" type="radio" {if $fs_archived == 1}checked="checked"{/if} />
                            <label class="radioCheck" for="fs_archived_on">{l s='TAK' mod='pherli'}</label>
                            <input id="fs_archived_off" onClick="toggleDraftWarning(true);showOptions(false);showRedirectProductOptions(true);" name="ERLI_FS_ARCHIVED" value="0" type="radio" {if $fs_archived == 0}checked="checked"{/if} />
                            <label class="radioCheck" for="fs_archived_off">{l s='NIE' mod='pherli'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Ustaw metody płatności z erli.pl np. Erli.pl - PayU - Płatność z ING' mod='pherli'}:</label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input id="checked_on" onClick="toggleDraftWarning(false);showOptions(true);showRedirectProductOptions(false);" name="ERLI_CHECK_PAYMENT" value="1" type="radio" {if $ERLI_CHECK_PAYMENT == 1}checked="checked"{/if} />
                            <label class="radioCheck" for="checked_on">{l s='TAK' mod='pherli'}</label>
                            <input id="checked_off" onClick="toggleDraftWarning(true);showOptions(false);showRedirectProductOptions(true);" name="ERLI_CHECK_PAYMENT" value="0" type="radio" {if $ERLI_CHECK_PAYMENT == 0}checked="checked"{/if} />
                            <label class="radioCheck" for="checked_off">{l s='NIE' mod='pherli'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                        <small>Domyślnie ustawione jest: Płatność on-line na Erli.pl</small>
                    </div>
                </div>

            </div>{* end row *}
        </div>
        <div class="panel-footer">
            <button id="configuration_form_submit_btn" class="btn btn-default pull-right" type="submit" value="1" name="submitSaveApiInfo"><i class="process-icon-save"></i> {l s='Save' mod='pherli'}</button>
        </div>
    </div>
</form>

<form method="post">
    <div class="panel">
        <div class="panel-heading"><i class="icon-cog"></i> {l s='Ceny' mod='pherli'}</div>
        <div class="form-horizontal row">
            <p class="alert alert-info">
                {l s='Tu możesz ustawić cenę doliczaną do produktów na erli.pl: kwotowo lub procentowo.' mod='pherli'}
            </p>
            <div class="form-group">
                <label class="control-label col-lg-3 text-right">{l s='Aktywować dodanie ceny do produktów?' mod='pherli'}</label>
                <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input id="erli_price_on" onClick="toggleDraftWarning(false);showOptions(true);showRedirectProductOptions(false);" name="ERLI_PRODUCT_PRICE" value="1" type="radio" {if $erli_price == 1}checked="checked"{/if} />
                            <label class="radioCheck" for="erli_price_on">{l s='TAK' mod='pherli'}</label>
                            <input id="erli_price_off" onClick="toggleDraftWarning(true);showOptions(false);showRedirectProductOptions(true);" name="ERLI_PRODUCT_PRICE" value="0" type="radio" {if $erli_price == 0}checked="checked"{/if} />
                            <label class="radioCheck" for="erli_price_off">{l s='NIE' mod='pherli'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3 text-right">{l s='Typ ceny' mod='pherli'}:</label>
                <div class="col-lg-9">
                    <div class="radio">
                        <label for="apply_discount_percent">
                            <input type="radio" name="ERLI_PRODUCT_PRICE_TYPE" id="apply_discount_percent" value="1"{if $erli_type == 1} checked="checked"{/if}>
                            {l s='Procentowy (%)' mod='pherli'}
                        </label>
                    </div>
                    <div class="radio">
                        <label for="apply_discount_percent2">
                            <input type="radio" name="ERLI_PRODUCT_PRICE_TYPE" id="apply_discount_percent2" value="2"{if $erli_type == 2} checked="checked"{/if}>
                            {l s='Kwotowy (zł)' mod='pherli'}
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3 text-right">{l s='Typ akcji' mod='pherli'}:</label>
                <div class="col-lg-9">
                    <div class="radio">
                        <label for="apply_discount_percent3">
                            <input type="radio" name="ERLI_PRODUCT_PRICE_ACTION" id="apply_discount_percent3" value="1"{if $erli_action == 1} checked="checked"{/if}>
                            {l s='Obniżka' mod='pherli'}
                        </label>
                    </div>
                    <div class="radio">
                        <label for="apply_discount_percent4">
                            <input type="radio" name="ERLI_PRODUCT_PRICE_ACTION" id="apply_discount_percent4" value="2"{if $erli_action == 2} checked="checked"{/if}>
                            {l s='Podwyżka' mod='pherli'}
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3 text-right">{l s='Wartość' mod='pherli'}:</label>
                <div class="col-lg-2">
                    <input type="text" name="ERLI_PRODUCT_PRICE_VALUE" class="form-control" value="{$erli_value}">
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3 text-right">{l s='Rodzaj ceny' mod='pherli'}:</label>
                <div class="col-lg-2">
                    <select name="ERLI_PRODUCT_PRICE_CUR">
                        <option value=""> </option>
                        <option value="1"{if $erli_cur == 1} selected="selected"{/if}> {l s='netto' mod='pherli'} </option>
                        <option value="2"{if $erli_cur == 2} selected="selected"{/if}> {l s='brutto' mod='pherli'} </option>
                    </select>
                </div>
            </div>

        </div>
        <div class="panel-footer">
            <button id="configuration_form_submit_btn" class="btn btn-default pull-right" type="submit" value="1" name="submitSaveErliInfo"><i class="process-icon-save"></i> {l s='Save' mod='pherli'}</button>
        </div>
    </div>
</form>

<form method="post">
    <div class="panel">
        <div class="panel-heading"><i class="icon-cog"></i> {l s='Import' mod='pherli'}</div>
        <div class="form-horizontal row">
            <div class="col-lg-2">
                <button type="submit" name="importErliDelivery" class="btn btn-success btn-block">Importuj metody dostawy z erli.pl</button>
                <br /><br />
                <a href="{$current_url}mapDelivery" class="btn btn-primary btn-block">Mapuj metody dostawy z erli.pl</a>
            </div>
            <div class="col-lg-2">
                <button type="submit" name="importErliPrices" class="btn btn-success btn-block">Importuj cenniki dostaw z erli.pl</button>
            </div>
            <div class="col-lg-2">
                <a href="{$current_url}hideProducts" class="btn btn-danger btn-block">Usuń produkty z erli.pl</a>
            </div>
        </div>
    </div>
</form>

<div class="panel">
    <div class="panel-heading"><i class="icon-cog"></i> {l s='Zadania CRON' mod='pherli'}</div>
    <div class="form-horizontal">

        <div class="form-group">
            <label class="control-label col-lg-3 text-right">{l s='Aktualizacja zamówień' mod='pherli'}:</label>
            <div class="col-lg-9">
                <input type="text" class="form-control" value="{$cron_order}" readonly />
            </div>
            <label class="control-label col-lg-3 text-right"></label>
            <small class="hint">Aktualizację zamówień należy ustawić najlepiej co 5 min. <a href="{$cron_order}" target="_blank">Kliknij tu aby otworzyć w przeglądarce.</a></small>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3 text-right">{l s='Aktualizacja produktów' mod='pherli'}:</label>
            <div class="col-lg-9">
                <input type="text" class="form-control" value="{$cron_products}" readonly />
            </div>
            <label class="control-label col-lg-3 text-right"></label>
            <small class="hint">Aktualizację produktów należy ustawić najlepiej co 30 min lub w zależności od częstotliwości aktualizowania produktów. <a href="{$cron_products}" target="_blank">Kliknij tu aby otworzyć w przeglądarce.</a></small>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3 text-right">{l s='Aktualizacja stanów i cen' mod='pherli'}:</label>
            <div class="col-lg-9">
                <input type="text" class="form-control" value="{$cron_stocks}" readonly />
            </div>
            <label class="control-label col-lg-3 text-right"></label>
            <small class="hint">Opcjonalnie. Aktualizację stanów magazynowych i cen należy ustawić najlepiej co 5 min. Aktualizowane są ceny i ilości wszystkich produktów dodanych na erli.pl.  <a href="{$cron_stocks}" target="_blank">Kliknij tu aby otworzyć w przeglądarce.</a></small>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3 text-right">{l s='First Sync - synchronizacja produktów z erli.pl w sklepie' mod='pherli'}:</label>
            <div class="col-lg-9">
                <input type="text" class="form-control" value="{$cron_first}" readonly />
            </div>
            <label class="control-label col-lg-3 text-right"></label>
            <small class="hint">Opcjonalnie. Aktualizację First Sync należy ustawić najlepiej co 5 min. Aktualizowany jest stan na erli.pl wszystkich produktów dodanych występujących na erli.pl.  <a href="{$cron_first}" target="_blank">Kliknij tu aby otworzyć w przeglądarce.</a></small>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3 text-right">{l s='Synchronizacja listy cenników dostaw z erli.pl' mod='pherli'}:</label>
            <div class="col-lg-9">
                <input type="text" class="form-control" value="{$cron_prices}" readonly />
            </div>
            <label class="control-label col-lg-3 text-right"></label>
            <small class="hint">Opcjonalnie. Zadanie należy ustawić najlepiej co 1h.  <a href="{$cron_prices}" target="_blank">Kliknij tu aby otworzyć w przeglądarce.</a></small>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3 text-right">{l s='Wysyłka numerów przewozowych z zamówień na erli.pl' mod='pherli'}:</label>
            <div class="col-lg-9">
                <input type="text" class="form-control" value="{$cron_tracking}" readonly />
            </div>
            <label class="control-label col-lg-3 text-right"></label>
            <small class="hint">Opcjonalnie. Zadanie należy ustawić najlepiej co 10min.  <a href="{$cron_tracking}" target="_blank">Kliknij tu aby otworzyć w przeglądarce.</a></small>
        </div>
        {if $all_products > 0}
        <div class="form-group">
            <label class="control-label col-lg-3 text-right">&nbsp;</label>
            <div class="col-lg-9">
                <p class="alert alert-info">
                    Ilość produktów do sprawdzenia: <b>{$all_products}</b><br />
                    Ilość sprawdzonych produktów: <b>{$checked_products} ({$prc} %)</b><br />
                    <small>Aby sprawdzić postęp - odśwież stronę.</small>
                </p>
            </div>
        </div>
        {/if}

        {if $checkBA == 0}
        <div class="form-group">
            <label class="control-label col-lg-3 text-right">{l s='Zarejestrowanie hooka checkBuyability' mod='pherli'}:</label>
            <div class="col-lg-9">
                <input type="text" class="form-control" value="{$cron_hook}" readonly />
            </div>
            <label class="control-label col-lg-3 text-right"></label>
            <small class="hint">Skopiuj i wklej link w przeglądarkę.</small>
        </div>
        {/if}

    </div>
</div>
