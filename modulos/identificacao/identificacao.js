$(document).ready(function(){
	
	function start(){
		
		if($('.identidadeConfirmar').length > 0){
			// ===== Identidade Confirmar botão.
			
			$('.identidadeConfirmar').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				window.open(gestor.identificacao.identidadeConfirmar, '_self');
			});
			
			// ===== Identidade Outra Conta botão.
			
			$('.identidadeOutraConta').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				window.open(gestor.identificacao.identidadeOutraConta, '_self');
			});
		}
		
		if($('#formLogin').length > 0){
			// ===== Form Login.
			
			var formId_1 = 'formLogin';
			var formSelector_1 = '#formLogin';
			
			$(formSelector_1)
				.form({
					fields : (gestor.formulario[formId_1].regrasValidacao ? gestor.formulario[formId_1].regrasValidacao : {}),
					onSuccess(event, fields){
						if('googleRecaptchaActive' in gestor){
							var action = 'logar'; // Action 
							var googleSiteKey = gestor.googleRecaptchaSite; // Google Site Key
							
							grecaptcha.ready(function() {
								grecaptcha.execute(googleSiteKey, {action: action}).then(function(token) {
									$(formSelector_1).append('<input type="hidden" name="token" value="'+token+'">');
									$(formSelector_1).append('<input type="hidden" name="action" value="'+action+'">');
									$(formSelector_1).unbind('submit').submit();
								});
							});
							
							return false;
						}
					}
				});
			
			// ===== Google reCAPTCHA V2 condições.
			
			if('googleRecaptchaActiveV2' in gestor){
				var googleSiteKey = gestor.googleRecaptchaSite; // Google Site Key
				
				var widgetId = grecaptcha.render(document.getElementById('google-recaptcha-v2-login'), {
					'sitekey' : googleSiteKey
				});
			}
			
			// ===== Form formCriarConta.
			
			var formId_2 = 'formCriarConta';
			var formSelector_2 = '#formCriarConta';
			
			$(formSelector_2)
				.form({
					fields : (gestor.formulario[formId_2].regrasValidacao ? gestor.formulario[formId_2].regrasValidacao : {}),
					onSuccess(event, fields){
						if('googleRecaptchaActive' in gestor){
							var action = 'criarConta'; // Action 
							var googleSiteKey = gestor.googleRecaptchaSite; // Google Site Key
							
							grecaptcha.ready(function() {
								grecaptcha.execute(googleSiteKey, {action: action}).then(function(token) {
									$(formSelector_2).append('<input type="hidden" name="token" value="'+token+'">');
									$(formSelector_2).append('<input type="hidden" name="action" value="'+action+'">');
									$(formSelector_2).unbind('submit').submit();
								});
							});
							
							return false;
						}
					}
				});
			
			// ===== Google reCAPTCHA V2 condições.
			
			if('googleRecaptchaActiveV2' in gestor){
				var googleSiteKey = gestor.googleRecaptchaSite; // Google Site Key
				
				var widgetId2 = grecaptcha.render(document.getElementById('google-recaptcha-v2-criar-conta'), {
					'sitekey' : googleSiteKey
				});
			}
		}
	}
	
	start();
});