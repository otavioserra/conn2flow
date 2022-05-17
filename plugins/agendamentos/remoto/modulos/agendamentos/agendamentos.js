$(document).ready(function(){
	
	function start(){
		$('.agendarBtn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('.agendamentosTela').hide();
			$('.agendar').show();
		});
		
		$('.agendamentosBtn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('.agendamentosTela').hide();
			$('.agendamentos').show();
		});
	}
	
	start();
	
});