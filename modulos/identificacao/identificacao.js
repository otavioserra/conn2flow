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
								});
							});
						}
						
						return false;
					}
				});
			
			$(formSelector_1).find('button').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				if($(this).hasClass('disabled')){
					return false;
				}
				
				$(formSelector_1).unbind('submit').submit();
			});
			
			// ===== Google reCAPTCHA V2 condições.
			
			if('googleRecaptchaActiveV2' in gestor){
				var documentId = 'google-recaptcha-v2-login';
				
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
			
			// ===== Form formCriarConta.
			
			var formId_2 = 'formCriarConta';
			var formSelector_2 = '#formCriarConta';
			
			$(formSelector_2)
				.form({
					fields : (gestor.formulario[formId_2].regrasValidacao ? gestor.formulario[formId_2].regrasValidacao : {}),
					onSuccess(event, fields){
						// ===== Aplicar trim no campo email.
						
						var email = $(formSelector_2).find('input[name="email"]').val();
						
						console.log(email);
						email.trim();
						$(formSelector_2).find('input[name="email"]').val(email);
						console.log(email);
						
						// ===== .
						
						if('googleRecaptchaActive' in gestor){
							var action = 'criarConta'; // Action 
							var googleSiteKey = gestor.googleRecaptchaSite; // Google Site Key
							
							grecaptcha.ready(function() {
								grecaptcha.execute(googleSiteKey, {action: action}).then(function(token) {
									$(formSelector_2).append('<input type="hidden" name="token" value="'+token+'">');
									$(formSelector_2).append('<input type="hidden" name="action" value="'+action+'">');
								});
							});
						}
						
						return false;
					}
				});
			
			$(formSelector_2).find('button').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				if($(this).hasClass('disabled')){
					return false;
				}
				
				$(formSelector_2).unbind('submit').submit();
			});
			
			// ===== Google reCAPTCHA V2 condições.
			
			if('googleRecaptchaActiveV2' in gestor){
				var documentId2 = 'google-recaptcha-v2-criar-conta';
				
				$('#'+documentId2).css('paddingBottom','15px');
				var googleSiteKey = gestor.googleRecaptchaSite; // Google Site Key
				
				grecaptcha.ready(function() {
					var widgetId2 = grecaptcha.render(document.getElementById(documentId2), {
						'sitekey' : googleSiteKey,
						'callback' : verifyCallback,
					});
				});
				
				$('#'+documentId2).parent().find('button').addClass('disabled');
				
				function verifyCallback(){
					$('#'+documentId2).parent().find('button').removeClass('disabled');
				}
			}
		}
	}
	
	start();
});