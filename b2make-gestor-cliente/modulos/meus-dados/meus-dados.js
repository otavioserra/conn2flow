$(document).ready(function(){
	
	function start(){
		// ===== Form formMeusDadosAlterar.
		
		if('formulario' in gestor){
			var formId = 'formMeusDadosAlterar';
			var formSelector = '.formMeusDadosAlterar';
			
			$(formSelector)
				.form({
					fields : (gestor.formulario[formId].regrasValidacao ? gestor.formulario[formId].regrasValidacao : {}),
					onSuccess(event, fields){
					}
				});
		}
		
		// ===== CPF e CNPJ controles.
		
		if($('.documentoCampos').length > 0){
			if(gestor.meusDados.cnpj_ativo == 'sim'){
				$(formSelector).form('remove fields', ['cpf']);
			} else {
				$(formSelector).form('remove fields', ['cnpj']);
			}
			
			$('.cpf').mask('000.000.000-00', {clearIfNotMatch: true});
			$('.cnpj').mask('00.000.000/0000-00', {clearIfNotMatch: true});
			
			$('.controleDoc').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var id = $(this).attr('data-id');
				
				switch(id){
					case 'cpf':
						$('.cpf').parent().show();
						$('.cnpj').parent().hide();
						$('.controleDoc[data-id="cpf"]').addClass('active');
						$('.controleDoc[data-id="cnpj"]').removeClass('active');
						$('input[name="cnpj_ativo"]').val('nao');
						$(formSelector).form('remove fields', ['cnpj']);
						$(formSelector).form('add rule', 'cpf',{ rules : gestor.formulario[formId].regrasValidacao['cpf'].rules });
					break;
					case 'cnpj':
						$('.cpf').parent().hide();
						$('.cnpj').parent().show();
						$('.controleDoc[data-id="cpf"]').removeClass('active');
						$('.controleDoc[data-id="cnpj"]').addClass('active');
						$('input[name="cnpj_ativo"]').val('sim');
						$(formSelector).form('remove fields', ['cpf']);
						$(formSelector).form('add rule', 'cnpj',{ rules : gestor.formulario[formId].regrasValidacao['cnpj'].rules });
					break;
				}
			});
		}
		
		// ===== Telefone controle.
		
		if($('.telefoneCampos').length > 0){
			var SPMaskBehavior = function (val) {
				return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
			},
			spOptions = {
				onKeyPress: function(val, e, field, options) {
					field.mask(SPMaskBehavior.apply({}, arguments), options);
				},
				clearIfNotMatch: true
			};

			$('.telefone').mask(SPMaskBehavior, spOptions);
		}
	}
	
	start();
	
});