<form method="post">
    <div class="panel">
        <div class="panel-heading"><i class="icon-cog"></i> {l s='Usuń produkty z erli.pl' mod='pherli'}</div>
        <div class="form-horizontal">
            <div class="rows">

                <div class="form-group">
                    <label class="control-label col-lg-3 text-right">{l s='Podaj id zewnętrzne produktu:' mod='pherli'}<sup>*</sup></label>
                    <div class="col-lg-2">
                        <input type="text" name="externalId" value="" class="form-control" required />
                    </div>
                </div>

            </div>
        </div>
        <div class="panel-footer">
            <a href="{$backUrl}" class="btn btn-default pull-left"><i class="process-icon-back"></i> powrót</a>
            <button id="configuration_form_submit_btn" class="btn btn-default pull-right" type="submit" value="1" name="submitHideProduct"><i class="process-icon-save"></i> {l s='Usuń' mod='pherli'}</button>
        </div>
    </div>
</form>
