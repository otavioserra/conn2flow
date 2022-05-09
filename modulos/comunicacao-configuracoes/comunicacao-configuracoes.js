$(document).ready(function(){
	
	if($('.enviarEmailTeste').length > 0){
		console.log('enviarEmailTeste loaded');
		
		$('enviarEmailTeste').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			gestor.interface.emailTesteURL = './?_gestor-atualizar=sim';
			
			$('.ui.modal.confirm').modal('show');
			
			console.log('enviarEmailTeste click');
		});
		
		$('.ui.modal.confirm').modal({
			onApprove: function() {
				$('#gestor-listener').trigger('carregar_abrir');
				window.open(gestor.interface.emailTesteURL,"_self");
				
				return false;
			}
		});
	}
	
});