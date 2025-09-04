$(document).ready(function(){
	function widgets_formulario_contato(){
		// ===== Form.
		
		if('formulario' in gestor){
			var formId = '_widgets-form-contato';
			var formSelector = '#_widgets-form-contato';
			var submitBtnClicked = false;
			
			$(formSelector)
				.form({
					fields : (gestor.formulario[formId].regrasValidacao ? gestor.formulario[formId].regrasValidacao : {}),
					onSuccess(event, fields){
						if('googleRecaptchaActive' in gestor){
							var action = 'formulario-contato'; // Action 
							var googleSiteKey = gestor.googleRecaptchaSite; // Google Site Key
							
							if(submitBtnClicked){
								grecaptcha.ready(function() {
									grecaptcha.execute(googleSiteKey, {action: action}).then(function(token) {
										$(formSelector).append('<input type="hidden" name="token" value="'+token+'">');
										$(formSelector).append('<input type="hidden" name="action" value="'+action+'">');
										
										$(formSelector).unbind('submit').submit();
									});
								});
								
								return false;
							}
						}
						
						if(!submitBtnClicked){
							return false;
						}
					}
				});
			
			$(formSelector).find('button').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				if($(this).hasClass('disabled')){
					return false;
				}
				
				submitBtnClicked = true;
				
				$(formSelector).form('submit');
			});
		}
		
		// ===== Telefone controle.
		
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
	
	function widgets_index(){
		if($('#_widgets-formulario-contato').length > 0){ widgets_formulario_contato(); }
	}
	
	widgets_index();
});