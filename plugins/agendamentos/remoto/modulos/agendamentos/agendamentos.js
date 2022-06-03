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
	
	function expiradoOuNaoEncontrado(){
		// ===== Mostrar a tela de confirmação pública.
		
		$('.expiradoOuNaoEncontrado').show();
	}
	
	function agendamentoAtivo(){
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
		
		// ===== Cupom de prioridade mask.
		
		$('.cupom').mask('AAAA-AAAA', {clearIfNotMatch: true});
		
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
		
		// ===== Iniciar popup.
		
		$('.button').popup({addTouchEvents:false});
		
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
					$('.agendamentos').show();
				break;
				default:
					$('.agendar').show();
			}
		} else {
			$('.agendar').show();
		}
		
		// ===== Tab de informações dos agendamentos.
		
		$('.tabular.menu .item').tab();
		
		// ===== Informações de um agendamento.
		
		$(document.body).on('mouseup tap','.dadosAgendamentoBtn',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			// ===== Verificar o tipo de agendamento.
			
			var tipo = '';
			if($(this).hasClass('preAgendamento')){tipo = 'preAgendamento';}
			if($(this).hasClass('agendamento')){tipo = 'agendamento';}
			if($(this).hasClass('agendamentoAntigo')){tipo = 'agendamentoAntigo';}
			
			// ===== Buscar os dados no servidor e montar na tela o resultado.
			
			var opcao = 'agendamentos';
			var ajaxOpcao = 'dados-do-agendamento';
			
			$.ajax({
				type: 'POST',
				url: gestor.raiz + gestor.moduloId + '/',
				data: {
					opcao : opcao,
					ajax : 'sim',
					ajaxPagina : 'sim',
					ajaxOpcao : ajaxOpcao,
					tipo : tipo,
					agendamento_id : $(this).attr('data-id')
				},
				dataType: 'json',
				beforeSend: function(){
					carregando('abrir');
				},
				success: function(dados){
					switch(dados.status){
						case 'OK':
							modal({mensagem:dados.dadosAgendamentos});
						break;
						case 'ERROR':
							modal({mensagem:dados.msg});
						break;
						default:
							console.log('ERROR - '+opcao+' - '+dados.status);
							carregando('fechar');
						
					}
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
		});
		
		// ===== Regras para ler mais entradas de agendamentos.
		
		var carregarObjs = {};
		var button_id = '.carregarMaisPre,.carregarMaisAgendamentos,.carregarMaisAntigos';
		
		$(button_id).on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = this;
			
			// ===== Verificar o tipo de agendamento.
			
			var tipo = '';
			if($(obj).hasClass('carregarMaisPre')){tipo = 'carregarMaisPre';}
			if($(obj).hasClass('carregarMaisAgendamentos')){tipo = 'carregarMaisAgendamentos';}
			if($(obj).hasClass('carregarMaisAntigos')){tipo = 'carregarMaisAntigos';}
			
			// ===== Carregar objetos.
			
			if(!(tipo in carregarObjs)){
				carregarObjs[tipo] = {
					maxPaginas : parseInt($(obj).attr('data-num-paginas')),
					paginaAtual : 0
				};
			}
			
			// ===== Carregar dados do servidor.
			
			var opcao = 'agendamentos';
			var ajaxOpcao = 'mais-resultados';
			
			carregarObjs[tipo].paginaAtual++;
			
			var paginaAtual = carregarObjs[tipo].paginaAtual;
			
			$.ajax({
				type: 'POST',
				url: gestor.raiz + gestor.moduloId + '/',
				data: { 
					opcao,
					ajax : 'sim',
					ajaxPagina : 'sim',
					ajaxOpcao,
					tipo,
					paginaAtual
				},
				dataType: 'json',
				beforeSend: function(){
					carregando('abrir');
				},
				success: function(dados){
					switch(dados.status){
						case 'OK':
							// ===== Incluir os registros nas tabelas correspondentes aos tipos de agendamento.
							
							switch(tipo){
								case 'carregarMaisPre': $('.tabelaPreAgendamentos').append(dados.registros); break;
								case 'carregarMaisAgendamentos': $('.tabelaAgendamentos').append(dados.registros); break;
								case 'carregarMaisAntigos': $('.tabelaAgendamentosAntigos').append(dados.registros); break;
							}
							
							// ===== Esconder o botão quando chegar na última página.
							
							if(carregarObjs[tipo].paginaAtual >= carregarObjs[tipo].maxPaginas - 1){
								$(obj).parent().hide();
							}
							
							// ===== Iniciar popup.
							
							$('.button').popup({addTouchEvents:false});
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
		});
		
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
		
		function modal(p={}){
			if(p.mensagem){
				$('.ui.modal.informativo .content').html(p.mensagem);
			}
			
			$('.ui.modal.informativo').modal({
				dimmerSettings:{
					dimmerName:'paginaCarregando' //className, NOT id (!)
				}
			}).modal('show');
		}
		
		
	}
	
	function start(){
		// ===== Agendamento ativo.
		
		if($('.agendamento-ativo').length > 0){ agendamentoAtivo(); }
		
		// ===== Tratar alterações do agendamento.
		
		if('confirmarPublico' in gestor){ confirmarPublico(); }
		if('expiradoOuNaoEncontrado' in gestor){ expiradoOuNaoEncontrado(); }
	}
	
	start();
	
});