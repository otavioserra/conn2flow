$(document).ready(function(){
	
	function start(){
		// ===== Form formCadastrar.
		
		var formId = 'formCadastrar';
		var formSelector = '#formCadastrar';
		
		$(formSelector)
			.form({
				fields : (gestor.formulario[formId].regrasValidacao ? gestor.formulario[formId].regrasValidacao : {}),
				onSuccess(event, fields){
					if('googleRecaptchaActive' in gestor){
						var action = 'cadastrar'; // Action 
						var googleSiteKey = gestor.googleRecaptchaSite; // Google Site Key
						
						grecaptcha.ready(function() {
							grecaptcha.execute(googleSiteKey, {action: action}).then(function(token) {
								$(formSelector).append('<input type="hidden" name="token" value="'+token+'">');
								$(formSelector).append('<input type="hidden" name="action" value="'+action+'">');
							});
						});
					}
					
					return false;
				}
			});
		
		$(formSelector).find('button').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			if($(this).hasClass('disabled')){
				return false;
			}
			
			$(formSelector).unbind('submit').submit();
		});
		
		// ===== Google reCAPTCHA V2 condições.
		
		if('googleRecaptchaActiveV2' in gestor){
			var documentId = 'google-recaptcha-v2';
			
			$('#'+documentId).css('paddingBottom','15px');
			var googleSiteKey = gestor.googleRecaptchaSite; // Google Site Key
			
			grecaptcha.ready(function() {
				var widgetId = grecaptcha.render(document.getElementById(documentId), {
					'sitekey' : googleSiteKey,
					'callback' : verifyCallback,
				});
			});
			
			$('#'+documentId).parent().find('button').addClass('disabled');
			
			function verifyCallback(){
				$('#'+documentId).parent().find('button').removeClass('disabled');
			}
		}
		
		// ===== CPF e CNPJ controles.
		
		$(formSelector).form('remove fields', ['cnpj']);
		
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
	
	start();
});