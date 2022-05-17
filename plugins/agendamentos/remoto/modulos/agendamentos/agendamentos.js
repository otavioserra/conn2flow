$(document).ready(function(){
	
	function start(){
		
		// ===== Calendário ptBR.
		
		var calendarPtBR = {
			days: ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'],
			months: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Júlio', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
			monthsShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
			today: 'Hoje',
			now: 'Agora',
			am: 'AM',
			pm: 'PM'
		};
		
		// ===== Variáveis do componente 'calendar' datas-multiplas.
		
		var calendarDatasOpt = {
			text: calendarPtBR,
			type: 'date',
			closable: false,
			inline: true,
			formatter: {
				date: function (date, settings) {
					if (!date) return '';
					
					var day = (date.getDate() < 10 ? '0' : '') + date.getDate();
					var month = ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1);
					var year = date.getFullYear();
					
					return day + '/' + month + '/' + year;
				}
			},
			onChange: function(date,dateFormated,mode){
				$(this).parent().find('.agendamentoData').val(dateFormated);
			}
		}
		
		// ===== Iniciar calendário.
		
		$('.ui.calendar').calendar(calendarDatasOpt);
		
		// ===== Iniciar dropdown.
		
		$('.ui.dropdown').dropdown();
		
		// ===== Listeners principais.
		
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