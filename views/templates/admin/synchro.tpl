<div class="panel">
    <div class="panel-heading"><i class="icon-cog"></i> {l s='Lista synchronizacji' mod='pherli'}</div>
    <div class="form-horizontal">
        {if empty($sync_list)}
            <p class="alert alert-warning">{l s='Nie ma jeszcze żadnych synchronizacji z erli.pl' mod='pherli'}</p>
        {else}
        <button type="button" class="btn btn-success btnRefresh"><i class="material-icons" style="font-size: 12px;">replay</i> Odśwież</button>
        <form method="post">
            <table class="table table-hovered tableSyncList">
                <thead>
                <tr>
                    <th>{l s='ID' mod='pherli'}</th>
                    <th>{l s='Typ' mod='pherli'}</th>
                    <th>{l s='Data startu' mod='pherli'}</th>
                    <th>{l s='Data zakończenia' mod='pherli'}</th>
                    <th>{l s='Błędy' mod='pherli'}</th>
                    <th>{l s='Ilość błędów' mod='pherli'}</th>
                    <th>{l s='Akcje' mod='pherli'}</th>
                </tr>
                </thead>
                <tbody>
                {foreach $sync_list as $sync}
                    <tr>
                        <td>{$sync.id_sync}</td>
                        <td>{$sync.name}</td>
                        <td>{$sync.date_add}</td>
                        <td>{$sync.date_end}</td>
                        <td style="color: {if $sync.isError == 1}#ff0000{else}#72c279{/if};">
                            {if $sync.isError == 1}
                                <i class="material-icons">error</i>
                            {else}
                                <i class="material-icons">check</i>
                            {/if}
                        </td>
                        <td{if $sync.isError == 1} style="color: #ff0000;"{/if}>
                            {if $sync.isError == 1}
                                {$sync.no}
                            {else}
                                --
                            {/if}
                        </td>
                        <td>
                            <a href="{$sync.url}" class="btn btn-xs btn-primary">Szczegóły</a>
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
