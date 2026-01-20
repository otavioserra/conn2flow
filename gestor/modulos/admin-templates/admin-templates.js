$(document).ready(function () {
    if ($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0) {
        // ===== Dropdown alvo IA

        $('.targetDropdown')
            .dropdown({
                onChange: function (value, text, $choice) {
                    if (value.length > 0) {
                        var currentUrl = window.location.href;
                        var newUrl = updateQueryStringParameter(currentUrl, 'target', value);
                        window.location.href = newUrl;
                    }
                }
            });

        function updateQueryStringParameter(uri, key, value) {
            var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
            var separator = uri.indexOf('?') !== -1 ? "&" : "?";
            if (uri.match(re)) {
                return uri.replace(re, '$1' + key + "=" + value + '$2');
            }
            else {
                return uri + separator + key + "=" + value;
            }
        }
    }
});