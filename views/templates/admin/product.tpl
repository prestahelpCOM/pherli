<div class="panel product-tab" id="product-erli">
    <h3>{l s='Produkt na erli.pl' mod='pherli'}</h3>
    <div class="col-lg-12">
        <div class="form-group">
            <label>{l s='Czy dodać produkt do erli.pl?' mod='pherli'}</label>
            <div class="radio">
                <label>
                    <input class="erliActive" type="radio" name="erli_active" value="0"{if $activeInErli == 0} checked{/if}> {l s='NIE' mod='pherli'}
                </label>
            </div>
            <div class="radio">
                <label>
                    <input class="erliActive" type="radio" name="erli_active" value="1"{if $activeInErli == 1} checked{/if}> {l s='TAK' mod='pherli'}
                </label>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label">{l s='Czas wysyłki' mod='pherli'}:</label>
            <div class="col-lg-5">
                <select name="delivery_time" class="custom-select delivery_time">
                    <option value=""> {l s='- wybierz czas -' mod='pherli'} </option>
                    {foreach $deliveryTime as $key => $dt}
                        <option value="{$key}"{if $key == $delivery_time} selected="selected"{/if}> {$dt} </option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label">{l s='Cennik' mod='pherli'}:</label>
            <div class="col-lg-5">
                <select name="delivery_price" class="custom-select delivery_price">
                    <option value=""> {l s='- wybierz cennik -' mod='pherli'} </option>
                    {foreach $deliveryPrices as $key => $dp}
                        <option value="{$dp.name}"{if $dp.name == $delivery_price} selected="selected"{/if}> {$dp.name} </option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>
    <div id="erliResultInfo" class=""></div>
    <input class="" type="hidden" name="erli_marker" value="1" />
    <input class="idProduct" type="hidden" name="idProduct" value="{$idProduct}" />
    <div class="btn">
        <button id="submitAddProductToErli" type="button" name="submitAddProductToErli" value="{$id_product}" class="btn btn-warning{if $status != 0} disabled{/if}">{l s='Dodaj do erli.pl (ręcznie)' mod='pherli'}</button>
        <button id="submitUpdateProductToErli" type="button" name="submitUpdateProductToErli" value="{$id_product}" class="btn btn-info{if $status == 0} disabled{/if}">{l s='Aktualizuj do erli.pl (ręcznie)' mod='pherli'}</button>
        <button id="submitDeleteProductToErli" type="button" name="submitDeleteProductToErli" value="{$id_product}" class="btn btn-danger{if $status == 0} disabled{/if}">{l s='Usuń z erli.pl (ręcznie)' mod='pherli'}</button>
    </div>
</div>
<script type="text/javascript">
    var urlErli = '{$urlErli}';
    var urlErliUpd = '{$urlErliUpd}';
    var urlErliDel = '{$urlErliDel}';

    $(document).on('click', '#submitAddProductToErli', function() {
        $('#erliResultInfo').removeClass('alert-info').removeClass('alert-warning').removeClass('alert-success');
        $('#erliResultInfo').addClass('alert').addClass('alert-info').html('Trwa dodawanie produktu na erli.pl');
        var idProduct = $('.idProduct').val();
        var deliveryTime = $('.delivery_time').val();
        var deliveryPrice = $('.delivery_price').val();
        var active = 0;
        $('.erliActive').each(function() {
            if ($(this).is(':checked')) {
                active = $(this).val();
            }
        });
        $.ajax({
            type: 'POST',
            url: urlErli,
            async: false,
            cache: false,
            dataType: 'json',
            data: 'idProduct=' + idProduct
                + '&deliveryTime=' + deliveryTime
                + '&deliveryPrice=' + deliveryPrice
                + '&active=' + active,
            success: function (jsonData) {
                $('#erliResultInfo').removeClass('alert-info');
                if (jsonData.status == 202) {
                    $('#erliResultInfo').addClass('alert-success').html(' Pomyślnie dodano!');
                } else {
                    $('#erliResultInfo').addClass('alert-danger').html(' Wystąpił błąd. Spróbuj ponownie');
                }
                setTimeout(function() {
                    $('#erliResultInfo').empty().removeClass('alert-success').removeClass('alert-danger');
                }, 10000);
            },
            complete: function(xxx) {

            },
            error : function(request, status, error) {
                var val = request.responseText;
                alert("error "+val);
            }
        });
    });
    $(document).on('click', '#submitUpdateProductToErli', function() {
        $('#erliResultInfo').removeClass('alert-info').removeClass('alert-warning').removeClass('alert-success');
        $('#erliResultInfo').addClass('alert').addClass('alert-info').html('Trwa aktualizacja produktu na erli.pl');
        var idProduct = $('.idProduct').val();
        var deliveryTime = $('.delivery_time').val();
        var active = 0;
        $('.erliActive').each(function() {
            if ($(this).is(':checked')) {
                active = $(this).val();
            }
        });
        $.ajax({
            type: 'POST',
            url: urlErliUpd,
            async: false,
            cache: false,
            dataType: 'json',
            data: 'idProduct=' + idProduct
                + '&deliveryTime=' + deliveryTime
                + '&active=' + active,
            success: function (jsonData) {
                $('#erliResultInfo').removeClass('alert-info');
                if (jsonData.status == 202) {
                    $('#erliResultInfo').removeClass('alert-danger').addClass('alert-success').html(' Pomyślnie zaktualizowane!');
                } else {
                    $('#erliResultInfo').removeClass('alert-success').addClass('alert-danger').html(' Wystąpił błąd. Spróbuj ponownie');
                }
                setTimeout(function() {
                    $('#erliResultInfo').empty().removeClass('alert-success').removeClass('alert-danger');
                }, 10000);
            },
            complete: function(xxx) {

            },
            error : function(request, status, error) {
                var val = request.responseText;
                alert("error "+val);
            }
        });
    });
    $(document).on('click', '#submitDeleteProductToErli', function() {
        $('#erliResultInfo').removeClass('alert-info').removeClass('alert-warning').removeClass('alert-success');
        $('#erliResultInfo').addClass('alert').addClass('alert-info').html('Trwa usuwanie z erli.pl');
        var idProduct = $('.idProduct').val();
        $.ajax({
            type: 'POST',
            url: urlErliDel,
            async: false,
            cache: false,
            dataType: 'json',
            data: 'idProduct=' + idProduct,
            success: function (jsonData) {
                $('#erliResultInfo').removeClass('alert-info');
                if (jsonData.status == 202) {
                    $('#erliResultInfo').addClass('alert-success').html(' Pomyślnie dodano!');
                } else {
                    $('#erliResultInfo').addClass('alert-danger').html(jsonData.info+'<br />Spróbuj ponownie poźniej');
                }
                setTimeout(function() {
                    $('#erliResultInfo').empty().removeClass('alert-success').removeClass('alert-danger');
                }, 10000);
            },
            complete: function(xxx) {

            },
            error : function(request, status, error) {
                var val = request.responseText;
                alert("error "+val);
            }
        });
    });
    // });
</script>
