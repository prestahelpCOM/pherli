$(document).ready(function() {
    $('.tablePackageList').DataTable({
        paging: false,
        "language": {
            "lengthMenu": "Wyświetl _MENU_ records per page",
            "zeroRecords": "Przepraszamy - nic nie znaleziono",
            "info": "",
            "infoEmpty": "Brak wpisów",
            "infoFiltered": "(wyfiltrowano z _MAX_ wpisów)",
            "search" : 'Wyszukaj:'
        },
        "stripeClasses": [ 'strip1', 'strip2', 'strip3' ]
    });

    $('.show-filtr').on('click', function () {
        var obj = $(this);
        var val = obj.text();
        $('.form-filter').toggle('slow', function() {
            if (val == 'pokaż filtr') {
                obj.text('ukryj filtr');
            } else {
                obj.text('pokaż filtr');
            }
        });
    });
});
