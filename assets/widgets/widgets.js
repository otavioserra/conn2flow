$(document).ready(function(){
	function widgets_formulario_contato(){
		// ===== Form.
		
		if('formulario' in gestor){
			var formId = '_widgets-form-contato';
			var formSelector = '#_widgets-form-contato';
			
			$(formSelector)
				.form({
					fields : (gestor.formulario[formId].regrasValidacao ? gestor.formulario[formId].regrasValidacao : {}),
					onSuccess(event, fields){
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
	
	function widgets_index(){
		if($('#_widgets-formulario-contato').length > 0){ widgets_formulario_contato(); }
	}
	
	widgets_index();
});