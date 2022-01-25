$(document).ready(function(){
	
	function start(){
	
		// ===== Form Esqueceu Senha.
		
		var formId_1 = 'formEsqueceuSenha';
		var formSelector_1 = '#formEsqueceuSenha';
		
		$(formSelector_1)
			.form({
				fields : (gestor.formulario[formId_1].regrasValidacao ? gestor.formulario[formId_1].regrasValidacao : {}),
				onSuccess(event, fields){
					if(typeof gestor.googleRecaptchaActive !== typeof undefined && gestor.googleRecaptchaActive !== false){
						var action = 'esqueceuSenha'; // Action 
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
		
		// ===== Mudar o nome da janela.
		
		if('janelaNome' in gestor.identificacao){
			window.name = gestor.identificacao['janelaNome'];
		}
	}
	
	start();
});