$(document).ready(function(){
	
	var formVars = new Array();
	
	$.formReiniciar = function(p={}){
		formIniciar(p);
	}
	
	function formIniciar(p={}){
		var formId = ('formId' in p ? p.formId : 'formId');
		
		formVars[formId] = {
			optionsToValidate : new Array(),
			selector : ('selector' in p ? p.selector : '.ui.form.formularioPadrao')
		}
		
		if('validarCampos' in gestor.formulario[formId]){
			var validarCampos = gestor.formulario[formId].validarCampos;
			
			function validarCamposFinalizar(){
				var peloMenosUmEhInvalido = false;
				var errorObjcs = {};
				
				for(campo in validarCampos){
					var valido = false;
					var campoId = '';
					
					if('valido' in validarCampos[campo]){
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
						$(formVars[formId].selector).form('add prompt', campoId, [erro]);
					} else {
						$(formVars[formId].selector).form('validate field', campoId);
					}
				}
				
				carregar_fechar();
				
				if(peloMenosUmEhInvalido){
					$(formVars[formId].selector).form('add errors', errorObjcs);
				} else {
					$(formVars[formId].selector).form('remove errors');
					
					if(typeof formVars[formId].formDontAutoSubmit !== typeof undefined && formVars[formId].formDontAutoSubmit !== false){
						formSubmit({
							id : 'validarCampos',
						});
					} else {
						$(formVars[formId].selector).unbind('submit').submit();
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
		
		function formSubmit(p={}){
			var id = p.id;
			var submit = true;
			for(var i=0;i<formVars[formId].optionsToValidate.length;i++){
				if(id == formVars[formId].optionsToValidate[i].id){
					formVars[formId].optionsToValidate[i].valido = true;
				}
				
				if(!formVars[formId].optionsToValidate[i].valido){
					submit = false;
				}
			}
			
			if(submit){
				$(formVars[formId].selector).unbind('submit').submit();
			}
		}
		
		$(formVars[formId].selector)
			.form({
				fields : (gestor.formulario[formId].regrasValidacao ? gestor.formulario[formId].regrasValidacao : {}),
				onSuccess: function(event, fields){
					var retorno = true;
					
					if(typeof validarCampos !== typeof undefined && validarCampos !== false){
						formVars[formId].optionsToValidate.push({
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
						formVars[formId].optionsToValidate.push({
							id : 'formOnSuccessCalback',
							valido : false,
						});
						
						formVars[formId].formDontAutoSubmit = true;
						
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
	
	function start(){
		$('.ui.checkbox')
			.checkbox();
			
		// ===== Triggers externos.
		
		
		$('#gestor-listener').on('formulario-iniciar',function(e,p){
			formIniciar(p);
		});
		
	}
	
	start();
});