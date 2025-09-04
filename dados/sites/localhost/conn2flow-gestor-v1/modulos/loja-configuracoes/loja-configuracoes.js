$(document).ready(function(){
	
	if($('#_gestor-interface-config-dados').length > 0){
		// ===== Mask Input
		
		$('.cpf').mask('000.000.000-00', {clearIfNotMatch: true});
		$('.cnpj').mask('00.000.000/0000-00', {clearIfNotMatch: true});
		$('.cep').mask('90.000-000', {clearIfNotMatch: true});
		$('.numero').mask("#.##0", {reverse: true});
		$('.uf').mask('SS', {clearIfNotMatch: true, onKeyPress: function(val, e, field, options) {
			field.val(val.toUpperCase());
		}});
		
		var SPMaskBehavior = function (val) {
			return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
		},
		spOptions = {
			onKeyPress: function(val, e, field, options) {
				field.mask(SPMaskBehavior.apply({}, arguments), options);
			},
			clearIfNotMatch: true
		};

		$('.tel').mask(SPMaskBehavior, spOptions);
		
		// ===== Formatar campo continuarComprando.
		
		$(document.body).on('blur','input[name="continuarComprando"]',function(e){
			var value = $(this).val();
			
			$('input[name="continuarComprando"]').val(formatar_url(value));
		});
		
		function formatar_url(url){
			url = '/' + url + '/';
			url = url.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); // Trocar todos os caracteres com acentos pelos seus similares sem acento.
			url = url.replace(/[^a-zA-Z0-9 \-\/]/g,''); // Remover todos os caracteres que não são alfanuméricos ou espaço ou traço ou barra.
			url = url.toLowerCase(); // Passar para letras minúsculas
			url = url.trim(); // Remover espaço do início e fim.
			url = url.replace(/\s/g,'-'); // Trocar todos os espaços por traço.
			url = url.replace(/\-{2,}/g,'-'); // Remover a repetição de traços para um único traço.
			url = url.replace(/\/{2,}/g,'/'); // Remover a repetição de barras para uma única barra.
			
			return url;
		}
	}
	
});