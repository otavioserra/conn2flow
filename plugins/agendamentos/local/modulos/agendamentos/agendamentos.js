$(document).ready(function(){
	function cupoms_de_prioridade(){
		$('.quantidade').mask("000", {reverse: true});
	}
	
	function agendamentos_atualizar(p={}){
		// ===== Caso não exista, criar o objeto de controle.
		
		if(!('agendamentos' in gestor)){
			gestor.agendamentos = {};
		}
		
		// ===== Modificar conforme enviado.
		
		if('data' in p){gestor.agendamentos.data = p.data;}
		if('status' in p){gestor.agendamentos.status = p.status;}
		
		// ===== Esconder manualmente conteiners não necessários devido os componentes do fomantic-ui se auto-mostrarem no início da DOM.
		
		if(
			!('data' in gestor.agendamentos) || 
			!('status' in gestor.agendamentos)
		){
			$('.imprimirBtn').hide();
			$('.tabelaPessoas').hide();
		}
		
		// ===== Mostrar o conteiner de resultados.
		
		if(
			('data' in gestor.agendamentos)
		){
			$('.resultados').show();
		}
		
		// ===== Somente atualizar caso esteja definido 'data' e 'status'.
		
		if(
			('data' in gestor.agendamentos) && 
			('status' in gestor.agendamentos)
		){
			// ===== Requisição para atualizar os agendamentos conforme opção.
			
			var opcao = 'agendamentos';
			var ajaxOpcao = 'atualizar';
			
			$.ajax({
				type: 'POST',
				url: gestor.raiz + gestor.moduloId + '/',
				data: {
					opcao : opcao,
					ajax : 'sim',
					ajaxOpcao : ajaxOpcao,
					ajaxPagina : 'sim',
					data : gestor.agendamentos.data,
					status : gestor.agendamentos.status
				},
				dataType: 'json',
				beforeSend: function(){
					carregando('abrir');
				},
				success: function(dados){
					switch(dados.status){
						case 'OK':
							// ===== Montar a tabela.
							
							$('.tabelaPessoas').html(dados.tabela);
							
							// ===== Atualizar o total de pessoas.
							
							$('.totalValor').html(dados.total);
							
							// ===== Mostrar ou não o botão imprimir.
							
							if(dados.imprimir){
								$('.imprimirBtn').show();
							} else {
								$('.imprimirBtn').hide();
							}
							
							// ===== Mostrar os conteiners de informação dos resultados.
							
							$('.totalPessoas').show();
							$('.tabelaPessoas').show();
						break;
						default:
							console.log('ERROR - '+opcao+' - '+dados.status);
						
					}
					
					carregando('fechar');
				},
				error: function(txt){
					switch(txt.status){
						case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"),"_self"); break;
						default:
							console.log('ERROR AJAX - '+opcao+' - Dados:');
							console.log(txt);
							carregando('fechar');
					}
				}
			});
		}
	}
	
	function agendamentos(){
		// ===== Configurações do calendário.
		
		var calendario = gestor.calendario;
		
		// ===== Datas disponíveis para agendamento.
		
		var datasDisponiveis = [];
		
		for(var data in calendario.datas_disponiveis){
			var dateObj = new Date(data.replace(/-/g, '\/')); // Bug no objeto Date() do javascript. Basta trocar o '-' por '/' que a data funciona corretamente. Senão fica um dia a mais do dia correto.
			
			datasDisponiveis.push(dateObj);
		}
		
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
		
		// ===== Variáveis do componente 'calendar'.
		
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
				$('.agendamentoData').val(dateFormated);
				$('.dataSelecionada').find('.dataSelecionadaValor').html(dateFormated);
				
				var day = (date.getDate() < 10 ? '0' : '') + date.getDate();
				var month = ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1);
				var year = date.getFullYear();
				
				var data = year + '-' + month + '-' + day;
				
				agendamentos_atualizar({data});
			}
		}
		
		// ===== Iniciar calendário.
		
		$('.ui.calendar').calendar(calendarDatasOpt);
		
		// ===== Acompanhantes dropdown.
		
		$('.ui.dropdown').dropdown({
			onChange: function(value){
				agendamentos_atualizar({status:value});
			}
		});
		
		// ===== Imprimir.
		
		$('.imprimirBtn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			window.open(gestor.raiz+"pagina-de-impressao/","Imprimir","menubar=0,location=0,height=700,width=1024");
		});
	}
	
	function start(){
		if($('#formAgendamentos').length > 0){
			agendamentos();
		}
		
		if($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0){
			cupoms_de_prioridade();
		}
	}
	
	start();
	
	function carregando(opcao){
		switch(opcao){
			case 'abrir':
				if(!('carregando' in gestor)){
					$('.paginaCarregando').dimmer({
						closable: false
					});
					
					gestor.carregando = true;
				}
				
				$('.paginaCarregando').dimmer('show');
			break;
			case 'fechar':
				$('.paginaCarregando').dimmer('hide');
			break;
		}
	}
	
});