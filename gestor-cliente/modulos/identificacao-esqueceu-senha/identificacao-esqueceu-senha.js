$(document).ready(function(){
	
	function start(){
	
		// ===== Form Esqueceu Senha.
		
		var formId_1 = 'formEsqueceuSenha';
		var formSelector = '#formEsqueceuSenha';
		
		$(formSelector)
			.form({
				fields : (gestor.formulario[formId_1].regrasValidacao ? gestor.formulario[formId_1].regrasValidacao : {}),
				onSuccess(event, fields){
					if('googleRecaptchaActive' in gestor){
						var action = 'esqueceuSenha'; // Action 
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
		
		// ===== Mudar o nome da janela.
		
		if('janelaNome' in gestor.identificacao){
			window.name = gestor.identificacao['janelaNome'];
		}
	}
	
	start();
});