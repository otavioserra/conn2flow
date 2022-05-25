$(document).ready(function(){
	
	function start(){
		// ===== Configurações do calendário.
		
		var calendario = gestor.calendario;
		
		// ===== Datas disponíveis para agendamento.
		
		var datasDisponiveis = [];
		
		for(var data in calendario.datas_disponiveis){
			var dateObj = new Date(data.replace(/-/g, '\/')); // Bug no objeto Date() do javascript. Basta trocar o '-' por '/' que a data funciona corretamente. Senão fica um dia a mais do dia correto.
			
			datasDisponiveis.push(dateObj);
		}
		
		// ===== Form Agendamentos.
		
		var formId = 'formAgendamentos';
		var formSelector = '#formAgendamentos';
		
		$(formSelector)
			.form({
				fields : (gestor.formulario[formId].regrasValidacao ? gestor.formulario[formId].regrasValidacao : {}),
				onSuccess(event, fields){
					
				}
			});
			
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
			inline: true,
			initialDate: new Date(),
			minDate: new Date(calendario.ano_inicio+'/01/01'),
			maxDate: new Date(calendario.ano_fim+'/12/31'),
			eventClass: 'inverted blue',
			enabledDates: datasDisponiveis,
			eventDates: datasDisponiveis,
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
				$(this).parent().find('.dataSelecionada').show();
				$(this).parent().find('.dataSelecionada').find('.dataSelecionadaValor').html(dateFormated);
				
				$(formSelector).form('validate form');
			}
		}
		
		// ===== Iniciar calendário.
		
		$('.ui.calendar').calendar(calendarDatasOpt);
		
		// ===== Acompanhantes dropdown.
		
		$('.ui.dropdown').dropdown({
			onChange: function(value){
				var objPai = $(this).parents('.field');
				var acompanhantesCont = objPai.find('.acompanhantesCont');
				var acompanhantesTemplateCont = objPai.find('.acompanhantesTemplateCont');
				var numAcom = acompanhantesCont.find('.field').length;
				
				value = parseInt(value);
				
				if(value > numAcom){
					for(var i=numAcom;i<value;i++){
						var field = acompanhantesTemplateCont.find('.field').clone();
						var num = (i+1);
						
						field.attr('data-num',num);
						field.find('label').html('Acompanhante '+num);
						field.find('input').prop('name','acompanhante-'+num);
						field.find('input').attr('data-validate','acompanhante'+num);
						
						acompanhantesCont.append(field);
						
						$(formSelector).form('add rule', ('acompanhante'+num),{ rules : gestor.formulario[formId].regrasValidacao[('acompanhante'+num)].rules });
					}
				} else {
					var num = 0;
					
					acompanhantesCont.find('.field').each(function(){
						num++;
						
						$(formSelector).form('remove fields', ['acompanhante'+num]);
						
						if(num > value){
							$(this).hide();
						} else {
							$(this).show();
							$(formSelector).form('add rule', ('acompanhante'+num),{ rules : gestor.formulario[formId].regrasValidacao[('acompanhante'+num)].rules });
						}
					});
				}
			}
		});
		
		// ===== Tratamento de telas.
		
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
		
		if('tela' in gestor){
			switch(gestor.tela){
				case 'agendamentos-anteriores':
					$('.agendamentosTela').hide();
					$('.agendamentos').show();
				break;
			}
		}
	}
	
	start();
	
});