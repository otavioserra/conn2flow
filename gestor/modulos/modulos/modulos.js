$(document).ready(function () {

	if ($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0) {
		var dropdowns = $('.ui.dropdown').not('[data-c2f-select]');
		if ($.fn.dropdown && dropdowns.length) dropdowns.dropdown();
	}

});