$(document).ready(function () {
    function exampleCalls() {
        // When making an AJAX request, use the following model.
        $(document.body).on('mouseup tap', 'selector', function (e) {
            if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

            var ajaxOption = 'option'; // Option that will be called in the AJAX interface in `module_id.php`
            var params = {}; // Additional parameters you wish to send to the server.

            $.ajax({
                type: 'POST',
                url: gestor.root + gestor.modulePath + '/',
                data: {
                    option: gestor.moduleOption,
                    ajax: 'yes',
                    ajaxOption,
                    params
                },
                dataType: 'json',
                beforeSend: function () {
                    $.load_open();
                },
                success: function (data) {
                    switch (data.status) {
                        case 'ok':
                            // Logic for when the response is OK
                            break;
                        default:
                            console.log('ERROR - ' + ajaxOption + ' - Data:');
                            console.log(data);

                    }

                    $.load_close();
                },
                error: function (txt) {
                    switch (txt.status) {
                        case 401: window.open(gestor.root + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                        default:
                            console.log('ERROR AJAX - ' + ajaxOption + ' - Data:');
                            console.log(txt);
                            $.load_close();
                    }
                }
            });
        });
    }

    function start() {
        // Initialization logic

        exampleCalls();
    }

    start();
});