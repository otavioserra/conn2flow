$(document).ready(function(){
	
	if($('.enviarEmailTeste').length > 0){
		$('.enviarEmailTeste').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			gestor.interface.emailTesteURL = './?_gestor-atualizar=sim';
			
			$('.ui.modal.confirm').modal('show');
		});
		
		
		var aprovado = false;
		
		$('.ui.modal.confirm').modal({
			onShow: function() {
				aprovado = false;
			},
			onApprove: function() {
				aprovado = true;
			},
			onHidden: function() {
				if(aprovado){
					$('#gestor-listener').trigger('carregar_abrir');
					//window.open(gestor.interface.emailTesteURL,"_self");
				}
			}
		});
	}
	
});