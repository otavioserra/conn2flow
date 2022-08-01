$(document).ready(function(){
	function confirmarPublico(){
		// ===== Mostrar a tela de confirmação pública.
		
		$('.confirmarPublico').show();
		
		// ===== Iniciar popup.
		
		$('.button').popup({addTouchEvents:false});
		
		// ===== Form da confirmacao.
		
		var formSelector = '.confirmacaoPublicaForm';
		
		$(formSelector)
			.form({
				
			});
		
		// ===== Botão de confirmação.
		
		$('.confirmarPublicoAgendamentoBtn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$(formSelector).find('input[name="escolha"]').val('confirmar');
			$(formSelector).form('submit');
		});
		
		// ===== Botão de cancelamento.
		
		$('.cancelarPublicoAgendamentoBtn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$(formSelector).form('submit');
		});
	}
	
	function cancelarPublico(){
		// ===== Mostrar a tela de confirmação pública.
		
		$('.cancelarPublico').show();
		
		// ===== Iniciar popup.
		
		$('.button').popup({addTouchEvents:false});
		
		// ===== Form da confirmacao.
		
		var formSelector = '.cancelamentoPublicoForm';
		
		$(formSelector)
			.form({
				
			});
		
		// ===== Botão de cancelamento.
		
		$('.cancelarPublicoAgendamentoBtn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$(formSelector).form('submit');
		});
	}
	
	function start(){
		// ===== Tratar alterações do agendamento.
		
		if('confirmarPublico' in gestor){ confirmarPublico(); }
		if('cancelarPublico' in gestor){ cancelarPublico(); }
	}
	
	start();
	
});