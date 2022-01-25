$(document).ready(function(){
	
	function start(){
	
		// ===== Form Esqueceu Senha.
		
		var formId_1 = 'formRedefinirSenha';
		var formSelector_1 = '#formRedefinirSenha';
		
		$(formSelector_1)
			.form({
				fields : (gestor.formulario[formId_1].regrasValidacao ? gestor.formulario[formId_1].regrasValidacao : {}),
				onSuccess(event, fields){
					
				}
			});
	}
	
	start();
});