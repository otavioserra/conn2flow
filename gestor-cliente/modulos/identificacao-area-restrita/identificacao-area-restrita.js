$(document).ready(function(){
	
	function start(){
		
		// ===== Form restrictArea.
		
		var formId = 'restrictArea';
		var formSelector = '#restrictArea';
		
		$(formSelector)
			.form({
				fields : (gestor.formulario[formId].regrasValidacao ? gestor.formulario[formId].regrasValidacao : {}),
				onSuccess(event, fields){
				}
			});
	}
	
	start();
});