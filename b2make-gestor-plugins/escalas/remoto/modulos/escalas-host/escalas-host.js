$(document).ready(function(){
	function escalaAtivo(){
		$('.ui.dropdown')
			.dropdown({
				onChange: function(value){
					var dataArr = value.split('_');
					
					window.open('./?mes='+dataArr[0]+'&ano='+dataArr[1],'_self');
				}
			})
		;
		
		$('.datepicker').datepicker({
			format: "dd/mm/yyyy",
			language: "pt-BR",
			maxViewMode: 0,
			datesDisabled: gestor.escalas.datasDesabilitadas,
			todayHighlight: true,
			multidate: true,
			startDate: gestor.escalas.dataInicio,
			endDate: gestor.escalas.dataFim,
			beforeShowDay: function(date) {
				var datasDestacadas = gestor.escalas.datasDestacadas;
				var day = (date.getDate() < 10 ? '0' : '') + date.getDate();
				var month = ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1);
				var year = date.getFullYear();
				var dateFormated = day + '/' + month + '/' + year;
				
				for(var key in datasDestacadas){
					var dataDestacada = datasDestacadas[key];
					
					if(dateFormated == dataDestacada){
						return {classes: 'highlighted', tooltip: gestor.escalas.highlightedTooltip};
					}
				}
			}
		})
		.on('changeDate', function(e) {
			var dates = '';
			
			for(var key in e.dates){
				var date = e.dates[key];
				var i = parseInt(key);
				
				dates += (dates.length > 0 ? ',':'') + e.format(i);
			}
			
			$('input[name="datas"]').val(dates);
		})
		;
		
		// ===== Listener da confirmação.
		
		$('.confirmarBtn,.cancelarBtn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var mes = $('input[name="mes"]').val();
			var ano = $('input[name="ano"]').val();
			
			var escolha = 'cancelar';
			if($(this).hasClass('confirmarBtn')){
				escolha = 'confirmar';
			}
			
			window.open('./?confirmacao=sim&escolha='+escolha+'&mes='+mes+'&ano='+ano,'_self');
		});
	}
	
	function start(){
		
		// ===== Escala ativo.
		
		if($('.escala-ativo').length > 0){ escalaAtivo(); }
	}
	
	start();
	
});