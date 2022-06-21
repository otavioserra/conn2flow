$(document).ready(function() {
	// ===== iFrame Comunication
	
	// addEventListener support for IE8
	function bindEvent(element, eventName, eventHandler) {
		if (element.addEventListener){
			element.addEventListener(eventName, eventHandler, false);
		} else if (element.attachEvent) {
			element.attachEvent('on' + eventName, eventHandler);
		}
	}
	
	// ===== Dropdown
	
	$.dropdown = function(p){
		$('.ui.dropdown')
		  .dropdown()
		;
	}
	
	// ===== Input delay
	
	$.input_delay_to_change = function(p){
		if(!gestor.input_delay){
			gestor.input_delay = new Array();
			gestor.input_delay_count = 0;
		}
		
		gestor.input_delay_count++;
		
		var valor = gestor.input_delay_count;
		
		gestor.input_delay.push(valor);
		gestor.input_value = p.value;
		
		setTimeout(function(){
			if(gestor.input_delay[gestor.input_delay.length - 1] == valor){
				input_change_after_delay({value:gestor.input_value,trigger_selector:p.trigger_selector,trigger_event:p.trigger_event,obj_ref : ('obj_ref' in p ? p.obj_ref : undefined)});
			}
		},gestor.input_delay_timeout);
	}
	
	function input_change_after_delay(p){
		$(p.trigger_selector).trigger(p.trigger_event,[p.value,(p.obj_ref !== undefined ? {obj : p.obj_ref} : gestor.input_delay_params)]);
		
		gestor.input_delay = false;
	}
	
	function input_delay(){
		if(!gestor.input_delay_timeout) gestor.input_delay_timeout = 600;
		
	}
	
	input_delay();
	
	// ===== Form
	
	var formVars = {
		optionsToValidate : new Array()
	};
	
	$.formReiniciar = function(p={}){
		formIniciar(p);
	}
	
	$.formSubmit = function(p={}){
		var id = p.id;
		var submit = true;
		for(var i=0;i<formVars.optionsToValidate.length;i++){
			if(id == formVars.optionsToValidate[i].id){
				formVars.optionsToValidate[i].valido = true;
			}
			
			if(!formVars.optionsToValidate[i].valido){
				submit = false;
			}
		}
		
		if(submit){
			$('.ui.form.interfaceFormPadrao').unbind('submit').submit();
		}
	}
	
	function formIniciar(p={}){
		if(typeof gestor.interface.validarCampos !== typeof undefined && gestor.interface.validarCampos !== false){
			var validarCampos = gestor.interface.validarCampos;
		}
		
		if(typeof validarCampos !== typeof undefined && validarCampos !== false){
			function validarCamposFinalizar(){
				var peloMenosUmEhInvalido = false;
				var errorObjcs = {};
				
				for(campo in validarCampos){
					var valido = false;
					var campoId = '';
					
					if(typeof validarCampos[campo].valido !== typeof undefined && validarCampos[campo].valido !== false){
						if(validarCampos[campo].valido){
							valido = true;
						} else {
							peloMenosUmEhInvalido = true;
						}
					} else {
						peloMenosUmEhInvalido = true;
					}
					
					if(validarCampos[campo].campo){
						campoId = validarCampos[campo].campo;
					} else {
						campoId = campo;
					}
					
					if(!valido){
						var erro = campoId+"_erro";
						
						errorObjcs[erro] = validarCampos[campo].prompt;
						$('.ui.form.interfaceFormPadrao').form('add prompt', campoId, [erro]);
					} else {
						$('.ui.form.interfaceFormPadrao').form('validate field', campoId);
					}
				}
				
				carregar_fechar();
				
				if(peloMenosUmEhInvalido){
					$('.ui.form.interfaceFormPadrao').form('add errors', errorObjcs);
				} else {
					$('.ui.form.interfaceFormPadrao').form('remove errors');
					
					if(typeof formVars.formDontAutoSubmit !== typeof undefined && formVars.formDontAutoSubmit !== false){
						$.formSubmit({
							id : 'validarCampos',
						});
					} else {
						$('.ui.form.interfaceFormPadrao').unbind('submit').submit();
					}
				}
			}
			
			function validarCampoCallback(campo,status){
				// ===== Validar o campo
				
				validarCampos[campo].valido = status;
				validarCampos[campo].verificado = true;
				
				// ===== Verificar se todos foram validados, caso positivo finalizar validação
				
				var todosVerificados = true;
				for(campoAux in validarCampos){
					if(!validarCampos[campo].verificado){
						todosVerificados = false;
						break;
					}
				}
				
				if(todosVerificados){
					validarCamposFinalizar();
				}
			}
			
			function validarCampo(value,id,campo = false){
				var ajaxOpcao = 'verificar-campo';
				
				$.ajax({
					type: 'POST',
					url: gestor.raiz + gestor.moduloCaminho,
					data: {
						opcao : gestor.moduloOpcao,
						ajax : 'sim',
						ajaxOpcao : ajaxOpcao,
						ajaxRegistroId : gestor.moduloRegistroId,
						campo : (campo ? campo : id),
						valor : value
					},
					dataType: 'json',
					beforeSend: function(){
						
					},
					success: function(dados){
						switch(dados.status){
							case 'Ok':
								validarCampoCallback(id,(dados.campoExiste ? false : true));
							break;
							default:
								console.log('ERROR - '+ajaxOpcao+' - Dados:');
								console.log(dados);
							
						}
					},
					error: function(txt){
						switch(txt.status){
							case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"),"_self"); break;
							default:
								console.log('ERROR AJAX - '+ajaxOpcao+' - Dados:');
								console.log(txt);
						}
					}
				});
			}
		}
		
		$('.ui.form.interfaceFormPadrao')
			.form({
				fields : (gestor.interface.regrasValidacao ? gestor.interface.regrasValidacao : {}),
				onSuccess: function(event, fields){
					var retorno = true;
					
					if(typeof validarCampos !== typeof undefined && validarCampos !== false){
						formVars.optionsToValidate.push({
							id : 'validarCampos',
							valido : false,
						});
						
						var carregar = false;
						
						for(field in fields){
							if(validarCampos[field]){
								var validar = false;
								
								if(typeof validarCampos[field].valor !== typeof undefined){
									if(validarCampos[field].valor != fields[field] || !validarCampos[field].valido){
										validar = true;
									}
								} else {
									validar = true;
								}
								
								if(validar){
									if(!carregar){
										carregar = true;
										carregar_abrir();
									}
									
									validarCampos[field].verificado = false;
									validarCampos[field].valor = fields[field];
									
									if(typeof validarCampos[field].campo !== typeof undefined){
										validarCampo(fields[field],field,validarCampos[field].campo);
									} else {
										validarCampo(fields[field],field);
									}
								}
							}
						}
					
						retorno = false;
					}
					
					if(typeof p.formOnSuccessCalback !== typeof undefined && p.formOnSuccessCalback !== false){
						formVars.optionsToValidate.push({
							id : 'formOnSuccessCalback',
							valido : false,
						});
						
						formVars.formDontAutoSubmit = true;
						
						if(p.formOnSuccessCalbackFunc()){
							retorno = true;
						} else {
							retorno = false;
						}
						
						retorno = false;
					}
					
					return retorno;
				},
			});
	}
	
	// ===== Interfaces
	
	function alerta(p={}){
		if(p.msg){
			$('.ui.modal.alerta .content p').html(p.msg);
		}
		
		$('.ui.modal.alerta').modal('show');
	}
	
	function deletar_confirmacao(){
		$('.ui.modal.confirm').modal({
			onApprove: function() {
				window.open(gestor.interface.excluir_url,"_self");
				
				return false;
			}
		});
		
		$('.ui.modal.confirm').modal('show');
	}
	
	function carregar_abrir(){
		var timeOut = 5000;
		
		if(!gestor.carregandoNum){
			gestor.carregandoNum = 1;
			
			$('.ui.modal.carregando').modal({
				closable : false,
				onShow: function(){
					var num = gestor.carregandoNum;
					setTimeout(function(){
						if(num == gestor.carregandoNum && gestor.carregando){
							$('.ui.modal.carregando').modal('hide');
							alerta({msg: gestor.componentes.ajaxTimeoutMessage});
						}
					},timeOut);
				}
			});
			
			$('.ui.modal.carregando').modal('setting', "duration", "0");
		} else {
			gestor.carregandoNum++;
		}
		
		$('.ui.modal.carregando').modal('show');
		gestor.carregando = true;
		gestor.carregandoTime = Date.now();
	}
	
	function carregar_fechar(){
		gestor.carregando = false;
		
		var timeTransition = 200;
		var timeOut = timeTransition - (Date.now() - gestor.carregandoTime);
		
		if(timeOut > 0){
			setTimeout(function(){
				$('.ui.modal.carregando').modal('hide');
			},timeOut);
		} else {
			$('.ui.modal.carregando').modal('hide');
		}
	}
	
	function interface_start(){
		// ===== Widget ImagePick
		
		if($('._gestor-widgetImage-cont').length > 0){
			$('._gestor-widgetImage-btn-add').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				// ===== Mudar dados do modal
				
				if(!gestor.interface.imagepick.start){
					gestor.interface.imagepick.start = true;
					
					$('.iframePagina').find('.header').html(gestor.interface.imagepick.modal.head);
					$('.iframePagina').find('.cancel.button').html(gestor.interface.imagepick.modal.cancel);
				}
				
				// ===== Guardar o obj do pai
				
				gestor.interface.imagepick.objPai = $(this).closest('._gestor-widgetImage-cont');
				
				// ===== Atualizar o iframe e abrir o modal
				
				$('.ui.modal.iframePagina').find('iframe').get(0).contentWindow.document.write('<body></body>');
				$('.ui.modal.iframePagina').find('iframe').attr('src',gestor.interface.imagepick.modal.url);
				$('.ui.modal.iframePagina').find('iframe').on('load',function (){
					$('.ui.modal.iframePagina').dimmer('hide');
				});
				
				$('.ui.modal.iframePagina').dimmer('show');
				$('.ui.modal.iframePagina').modal('show');
			});
			
			$('._gestor-widgetImage-btn-del').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				// ===== Resetar para valores padrões
				
				var objPai = $(this).closest('._gestor-widgetImage-cont');
				
				objPai.find('input').val(gestor.interface.imagepick.padroes.fileId);
				objPai.find('.widgetImage-image').attr('src',gestor.interface.imagepick.padroes.imgSrc);
				objPai.find('.widgetImage-nome').html(gestor.interface.imagepick.padroes.nome);
				objPai.find('.widgetImage-data').find('.icon').get(0).nextSibling.remove();
				objPai.find('.widgetImage-data').append(gestor.interface.imagepick.padroes.data);
				objPai.find('.widgetImage-tipo').find('.icon').get(0).nextSibling.remove();
				objPai.find('.widgetImage-tipo').append(gestor.interface.imagepick.padroes.tipo);
			});
			
			bindEvent(window, 'message', function (e) {
				var data = JSON.parse(e.data);
				
				switch(data.moduloId){
					case 'admin-arquivos':
					case 'arquivos':
						var dados = JSON.parse(decodeURI(data.data));
						
						if(dados.tipo.match(/image\//) == 'image/'){
							var objPai = gestor.interface.imagepick.objPai;
							
							objPai.find('input').val(dados.id);
							objPai.find('.widgetImage-image').attr('src',dados.imgSrc);
							objPai.find('.widgetImage-nome').html(dados.nome);
							objPai.find('.widgetImage-data').find('.icon').get(0).nextSibling.remove();
							objPai.find('.widgetImage-data').append(dados.data);
							objPai.find('.widgetImage-tipo').find('.icon').get(0).nextSibling.remove();
							objPai.find('.widgetImage-tipo').append(dados.tipo);
							
							$('.ui.modal.iframePagina').modal('hide');
						} else {
							alerta({msg:gestor.interface.imagepick.alertas.naoImagem});
						}
					break;
				}
			});
		}
		
		// ===== Widget Templates
		
		if($('._gestor-widgetTemplate-cont').length > 0){
			$('._gestor-widgetTemplate-btn-change').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				// ===== Mudar dados do modal
				
				if(!gestor.interface.templates.start){
					gestor.interface.templates.start = true;
					
					$('.iframePagina').find('.header').html(gestor.interface.templates.modal.head);
					$('.iframePagina').find('.cancel.button').html(gestor.interface.templates.modal.cancel);
				}
				
				// ===== Guardar o obj do pai
				
				gestor.interface.templates.objPai = $(this).closest('._gestor-widgetTemplate-cont');
				
				// ===== Atualizar o iframe e abrir o modal
				
				$('.ui.modal.iframePagina').find('iframe').get(0).contentWindow.document.write('<body></body>');
				$('.ui.modal.iframePagina').find('iframe').attr('src',gestor.interface.templates.modal.url);
				$('.ui.modal.iframePagina').find('iframe').on('load',function (){
					$('.ui.modal.iframePagina').dimmer('hide');
				});
				
				$('.ui.modal.iframePagina').dimmer('show');
				$('.ui.modal.iframePagina').modal('show');
			});
			
			bindEvent(window, 'message', function (e) {
				var data = JSON.parse(e.data);
				
				switch(data.moduloId){
					case 'templates':
						var dados = JSON.parse(decodeURI(data.data));
						
						var objPai = gestor.interface.templates.objPai;
						
						objPai.find('input.widgetTemplate-templateId').val(dados.templateId);
						objPai.find('input.widgetTemplate-templateTipo').val(dados.templateTipo);
						objPai.find('.widgetTemplate-image').attr('src',dados.imgSrc);
						objPai.find('.widgetTemplate-nome').html(dados.nome);
						objPai.find('.widgetTemplate-data').find('.icon').get(0).nextSibling.remove();
						objPai.find('.widgetTemplate-data').append(dados.data);
						objPai.find('.widgetTemplate-tipo').find('.icon').get(0).nextSibling.remove();
						objPai.find('.widgetTemplate-tipo').append(dados.tipo);
						
						$('.ui.modal.iframePagina').modal('hide');
					break;
				}
			});
		}
		
		// ===== Autorização Provisória
		
		if($('.autorizacaoProvisoria').length > 0){
			$('.ui.modal.autorizacaoProvisoria').modal({
				closable : false,
				onApprove: function(){
					return false;
				},
				onDeny: function(){
					return false;
				},
			});
			
			$('.ui.modal.autorizacaoProvisoria').modal('show');
		}
		
		if($('#_gestor-interface-insert-dados').length > 0){
			function adicionar(){
				// ===== Shortcuts teclado
				
				$(document).keydown(function(event) {
						if((event.ctrlKey || event.metaKey)){
							if(event.which == 83){ // CTRL + S - Enviar formulário
								$('.interfaceFormPadrao').form('submit');
								
								event.preventDefault();
								return false;
							}
						}
					}
				);
				
				// ===== Dropdown
				
				$.dropdown();
				
				// ===== Form validation
				
				formIniciar();
				
				// ===== Tooltip

				$('.segment .button').popup({
					delay: {
						show: 150,
						hide: 0
					},
					position:'top right',
					variation:'inverted'
				});
				
				// ===== Hack para que quando um checkbox esteja no estado checked o componente checkbox fique "checked".
				
				$('.checkbox')
					.checkbox();
				
				$('input[type="checkbox"]').each(function(){
					if(typeof $(this).attr('data-checked') !== typeof undefined && $(this).attr('data-checked') !== false){
						if($(this).attr('data-checked') == 'checked'){
							$(this).prop( "checked", true );
						}
					}
				});
				
				gestor.checkboxesReady = true;
			}
			
			adicionar();
		}
		
		if($('#_gestor-interface-visualizar-dados').length > 0){
			function visualizar(){
				// ===== Regras para ler mais entradas do histórico.
				
				var historicoPaginaAtual = 0;
				var button_id = '_gestor-interface-edit-historico-mais';
				
				$('#'+button_id).on('mouseup tap',function(e){
					if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
					
					var ajaxOpcao = 'historico-mais-resultados';
					
					if(typeof gestor.interface.id !== typeof undefined && gestor.interface.id !== false){
						var id = gestor.interface.id;
					} else {
						var id = '';
					}
					
					historicoPaginaAtual++;
					
					var pagina = historicoPaginaAtual;
					var opcao = gestor.moduloOpcao;
					
					$.ajax({
						type: 'POST',
						url: gestor.raiz + gestor.moduloId + '/',
						data: { 
							opcao : gestor.moduloOpcao,
							ajax : 'sim',
							ajaxOpcao : ajaxOpcao,
							ajaxRegistroId : gestor.moduloRegistroId,
							pagina : pagina,
							id : id
						},
						dataType: 'json',
						beforeSend: function(){
							carregar_abrir();
						},
						success: function(dados){
							switch(dados.status){
								case 'Ok':
									$('#'+button_id).parent().parent().before(dados.pagina);
									
									var totalPaginas = gestor.interface.totalPaginas;
									if(historicoPaginaAtual >= parseInt(totalPaginas) - 1){
										$('#'+button_id).hide();
									}
									
								break;
								default:
									console.log('ERROR - '+opcao+' - '+dados.status);
								
							}
							
							carregar_fechar();
						},
						error: function(txt){
							switch(txt.status){
								case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"),"_self"); break;
								default:
									console.log('ERROR AJAX - '+opcao+' - Dados:');
									console.log(txt);
									carregar_fechar();
							}
						}
					});
				});
			}
			
			visualizar();
		}
		
		if($('#_gestor-interface-config-dados').length > 0 || $('#_gestor-interface-simples').length > 0){
			function config(){
				// ===== Shortcuts teclado
				
				$(document).keydown(function(event) {
						if((event.ctrlKey || event.metaKey)){
							if(event.which == 83){ // CTRL + S - Enviar formulário
								$('.interfaceFormPadrao').form('submit');
								
								event.preventDefault();
								return false;
							}
						}
					}
				);
				
				// ===== Regras para ler mais entradas do histórico.
				
				var historicoPaginaAtual = 0;
				var button_id = '_gestor-interface-edit-historico-mais';
				
				$('#'+button_id).on('mouseup tap',function(e){
					if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
					
					var ajaxOpcao = 'historico-mais-resultados';
					
					if(typeof gestor.interface.id !== typeof undefined && gestor.interface.id !== false){
						var id = gestor.interface.id;
					} else {
						var id = '';
					}
					
					historicoPaginaAtual++;
					
					var pagina = historicoPaginaAtual;
					var opcao = gestor.moduloOpcao;
					
					$.ajax({
						type: 'POST',
						url: gestor.raiz + gestor.moduloCaminho,
						data: { 
							opcao : gestor.moduloOpcao,
							ajax : 'sim',
							ajaxOpcao : ajaxOpcao,
							ajaxRegistroId : gestor.moduloRegistroId,
							pagina : pagina,
							sem_id : 'sim',
							id : id
						},
						dataType: 'json',
						beforeSend: function(){
							carregar_abrir();
						},
						success: function(dados){
							switch(dados.status){
								case 'Ok':
									$('#'+button_id).parent().parent().before(dados.pagina);
									
									var totalPaginas = gestor.interface.totalPaginas;
									if(historicoPaginaAtual >= parseInt(totalPaginas) - 1){
										$('#'+button_id).hide();
									}
									
								break;
								default:
									console.log('ERROR - '+opcao+' - '+dados.status);
								
							}
							
							carregar_fechar();
						},
						error: function(txt){
							switch(txt.status){
								case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"),"_self"); break;
								default:
									console.log('ERROR AJAX - '+opcao+' - Dados:');
									console.log(txt);
									carregar_fechar();
							}
						}
					});
				});
				
				// ===== Tooltip

				$('.segment .button').popup({
					delay: {
						show: 150,
						hide: 0
					},
					position:'top right',
					variation:'inverted'
				});
			}
			
			config();
		}
		
		if($('#_gestor-interface-edit-dados').length > 0){
			function editar(){
				// ===== Shortcuts teclado
				
				$(document).keydown(function(event) {
						if((event.ctrlKey || event.metaKey)){
							if(event.which == 83){ // CTRL + S - Enviar formulário
								$('.interfaceFormPadrao').form('submit');
								
								event.preventDefault();
								return false;
							}
						}
					}
				);
				
				// ===== Dropdown
				
				$.dropdown();
				
				// ===== Backup Dropdown
				
				$('.backupDropdown')
					.dropdown({
						onChange: function(value, text, $choice){
							var id = value;
							var ajaxOpcao = 'backup-campos-mudou';
							var campo = $(this).attr('data-campo');
							var campo_form = $(this).attr('data-campo-form');
							var callback = $(this).attr('data-callback');
							var id_numerico = $(this).attr('data-id');
							var opcao = 'editar';
							
							$.ajax({
								type: 'POST',
								url: gestor.raiz + gestor.moduloId + '/',
								data: {
									opcao : 'editar',
									ajax : 'sim',
									ajaxOpcao : ajaxOpcao,
									ajaxRegistroId : gestor.moduloRegistroId,
									campo : campo,
									id_numerico : id_numerico,
									id : id
								},
								dataType: 'json',
								beforeSend: function(){
									carregar_abrir();
								},
								success: function(dados){
									switch(dados.status){
										case 'Ok':
											$('#gestor-listener').trigger(callback, {
												valor : dados.valor,
												campo : campo_form
											});
										break;
										default:
											console.log('ERROR - '+opcao+' - '+dados.status);
										
									}
									
									carregar_fechar();
								},
								error: function(txt){
									switch(txt.status){
										case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"),"_self"); break;
										default:
											console.log('ERROR AJAX - '+opcao+' - Dados:');
											console.log(txt);
											carregar_fechar();
									}
								}
							});
						}
					})
				;
				
				// ===== Form validation
				
				formIniciar();
				
				// ===== Inicialização do popup tooltips
				
				$('.segment .button').popup({
					delay: {
						show: 150,
						hide: 0
					},
					position:'top right',
					variation:'inverted'
				});
				
				// ===== Hack para que quando um checkbox esteja no estado checked o componente checkbox fique "checked".
				
				$('.checkbox')
					.checkbox();
				
				$('input[type="checkbox"]').each(function(){
					if(typeof $(this).attr('data-checked') !== typeof undefined && $(this).attr('data-checked') !== false){
						if($(this).attr('data-checked') == 'checked'){
							$(this).prop( "checked", true );
						}
					}
				});
				
				gestor.checkboxesReady = true;
				
				// ===== Regras de exclusão
				
				$(document.body).on('mouseup tap','.excluir',function(e){
					if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
					
					gestor.interface.excluir_url = $(this).attr('data-href');
					deletar_confirmacao();
				});
				
				// ===== Regras para ler mais entradas do histórico.
				
				var historicoPaginaAtual = 0;
				var button_id = '_gestor-interface-edit-historico-mais';
				
				$('#'+button_id).on('mouseup tap',function(e){
					if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
					
					var ajaxOpcao = 'historico-mais-resultados';
					
					if(typeof gestor.interface.id !== typeof undefined && gestor.interface.id !== false){
						var id = gestor.interface.id;
					} else {
						var id = '';
					}
					
					historicoPaginaAtual++;
					
					var pagina = historicoPaginaAtual;
					var opcao = gestor.moduloOpcao;
					
					$.ajax({
						type: 'POST',
						url: gestor.raiz + gestor.moduloId + '/',
						data: { 
							opcao : gestor.moduloOpcao,
							ajax : 'sim',
							ajaxOpcao : ajaxOpcao,
							ajaxRegistroId : gestor.moduloRegistroId,
							pagina : pagina,
							id : id
						},
						dataType: 'json',
						beforeSend: function(){
							carregar_abrir();
						},
						success: function(dados){
							switch(dados.status){
								case 'Ok':
									$('#'+button_id).parent().parent().before(dados.pagina);
									
									var totalPaginas = gestor.interface.totalPaginas;
									if(historicoPaginaAtual >= parseInt(totalPaginas) - 1){
										$('#'+button_id).hide();
									}
									
								break;
								default:
									console.log('ERROR - '+opcao+' - '+dados.status);
								
							}
							
							carregar_fechar();
						},
						error: function(txt){
							switch(txt.status){
								case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"),"_self"); break;
								default:
									console.log('ERROR AJAX - '+opcao+' - Dados:');
									console.log(txt);
									carregar_fechar();
							}
						}
					});
				});
			}
			
			editar();
		}
		
		if($('#_gestor-interface-lista-tabela').length > 0){
			$(document).keydown(function(event) {
					if((event.ctrlKey || event.metaKey)){
						if(event.which == 70){
							$('input[type="search"]').focus();
							
							event.preventDefault();
							return false;
						}
					}
				}
			);

			function listar_deletar_confirmacao(){
				return false;
			}
			
			function listar(){
				var lista = gestor.interface.lista;
				var dataTableName = '_gestor-interface-lista-tabela';
				
				var dtableInstance = $('#'+dataTableName).DataTable( {
					"processing": true,
					"serverSide": true,
					responsive: {
						details: {
							display: $.fn.dataTable.Responsive.display.modal( {
								header: function ( row ) {
									var data = row.data();
									
									return 'Detalhes do registro: '+data[lista.id];
								}
							} ),
							renderer: $.fn.dataTable.Responsive.renderer.tableAll( {
								tableClass: 'ui celled table responsive nowrap unstackable'
							} )
						}
					},
					"deferLoading": parseInt(lista.deferLoading),
					"pageLength": parseInt(lista.pageLength),
					"displayStart": parseInt(lista.displayStart),
					"columns": lista.columns,
					"order": lista.order,
					"ajax": {
						"url": gestor.raiz + lista.url,
						"type": "POST",
						"data": function(d){
							d.opcao = "listar";
							d.ajax = "true";
							d.ajaxOpcao = "listar";
						}
					},
					columnDefs: [
						{	responsivePriority: 1,
							targets: 0
						},
						{
							targets: -1,
							responsivePriority: 2,
							className: 'dt-head-center',
							render: function (data, type, row, meta){
								if(lista.opcoes){
									var botoes = '';
									
									if(lista.status){
										var status = row[lista.status];
										
										for(var id in lista.opcoes){
											var opcoes = lista.opcoes[id];
											
											if(opcoes.opcao == 'status'){
												if(opcoes.status_atual == status){
													botoes = botoes + '<a class="ui button '+opcoes.cor+'" href="?opcao='+opcoes.opcao+'&status='+opcoes.status_mudar+'&id='+data+'" data-content="'+opcoes.tooltip+'" data-id="'+id+'"><i class="'+opcoes.icon+' icon"></i></a>';
												}
											} else {
												if(opcoes.url){
													botoes = botoes + '<a class="ui button '+opcoes.cor+'" href="'+opcoes.url+'?id='+data+'" data-content="'+opcoes.tooltip+'" data-id="'+id+'"><i class="'+opcoes.icon+' icon"></i></a>';
												} else {
													switch(id){
														case 'excluir':
															botoes = botoes + '<div class="ui button '+opcoes.cor+' excluir" data-href="?opcao='+opcoes.opcao+'&id='+data+'" data-content="'+opcoes.tooltip+'" data-id="'+id+'"><i class="'+opcoes.icon+' icon"></i></div>';
														break;
														default:
															botoes = botoes + '<a class="ui button '+opcoes.cor+'" href="?opcao='+opcoes.opcao+'&id='+data+'" data-content="'+opcoes.tooltip+'" data-id="'+id+'"><i class="'+opcoes.icon+' icon"></i></a>';
													}
												}
											}
										}
									}
									
									return '<div class="ui icon buttons">'+botoes+'</div>';
								} else {
									return '';
								}
							}
						},
					],
					language: {
						url: gestor.raiz + 'datatables/1.10.23/pt_br.json'
					},
					initComplete: function() {
						var api = this.api();
						var searchWait = 0;
						var searchWaitInterval;
						// Grab the datatables input box and alter how it is bound to events
						
						$(".dataTables_filter input")
						.unbind() // Unbind previous default bindings
						.bind("input", function(e) { // Bind our desired behavior
							var item = $(this);
							searchWait = 0;
							if(!searchWaitInterval) searchWaitInterval = setInterval(function(){
								searchTerm = $(item).val();
								if(searchTerm.length == 0 || searchTerm.length >= 3 || e.keyCode == 13) {
									clearInterval(searchWaitInterval);
									searchWaitInterval = '';
									// Call the API search function
									api.search(searchTerm).draw();
									searchWait = 0;
								}
								searchWait++;
							},750);                       
							return;
						});
						
						// ===== Quando terminar de ler, caso não haja opções, ocultar a coluna opções
						
						if(!lista.opcoes){
							var opcoes = dtableInstance.column(-1);
							opcoes.visible(false);
						}
						
						// ===== Colocar width com 100%;
						
						$('#'+dataTableName).css('width','100%');
					},
					drawCallback: function(){
						// ===== Tooltip regras
						
						$('.buttons .button').popup({
							delay: {
								show: 150,
								hide: 0
							},
							position:'top center',
							variation:'inverted'
						});
					}
				});
				
				$.fn.dataTable.ext.errMode = function ( settings, helpPage, message ) { 
					switch(settings.jqXHR.status){
						case 401: window.open(gestor.raiz + (settings.jqXHR.responseJSON.redirect ? settings.jqXHR.responseJSON.redirect : "signin/"),"_self"); break;
					}
				};
				
				// ===== Regras de exclusão
				
				$(document.body).on('mouseup tap','.excluir',function(e){
					if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
					
					gestor.interface.excluir_url = $(this).attr('data-href');
					
					deletar_confirmacao();
				});
				
			}
			
			listar();
		}
		
		if($('#_gestor-interface-listar').length > 0){
			// ===== Popup dos botões
			
			$('.segment .button').popup({
				delay: {
					show: 150,
					hide: 0
				},
				position:'top right',
				variation:'inverted'
			});
		}
		
		if(typeof gestor.interface !== typeof undefined && gestor.interface !== false){
			if(typeof gestor.interface.alerta !== typeof undefined && gestor.interface.alerta !== false){
				alerta(gestor.interface.alerta);
			}
		}
		
		// ===== Triggers principais
		
		$('#gestor-listener').on('carregar_abrir',function(e){
			carregar_abrir();
		});
		
		$('#gestor-listener').on('carregar_fechar',function(e){
			carregar_fechar();
		});
		
		$('#gestor-listener').on('alerta',function(e,p){
			alerta(p);
		});
	}
	
	interface_start();
});