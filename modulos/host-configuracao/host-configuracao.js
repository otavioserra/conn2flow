$(document).ready(function(){
	// ===== Host Install
	
	if(typeof gestor.hostCarregando !== typeof undefined && gestor.hostCarregando !== false){
		$('.ui.modal.instalando').modal({
			closable : false,
			onVisible : function(){
				document.location.reload(true);
			}
		});
		
		$('.ui.modal.instalando').modal('show');
	}
	
	if($('#_gestor-host-install').length > 0){
		$.formReiniciar();
	}
	
	// ===== Host Config
	
	if(typeof gestor.hostConfigCarregando !== typeof undefined && gestor.hostConfigCarregando !== false){
		$('.ui.modal.configurando').modal({
			closable : false,
			onVisible : function(){
				document.location.reload(true);
			}
		});
		
		$('.ui.modal.configurando').modal('show');
		
		$('.ui.icon i')
			.transition('set looping')
			.transition('bounce', '900ms')
		;
	}
	
	if($('#_gestor-host-config').length > 0){
		$.formReiniciar();
	}
	
	// ===== Host Update
	
	if(typeof gestor.hostUpdateCarregando !== typeof undefined && gestor.hostUpdateCarregando !== false){
		$('.ui.modal.atualizando').modal({
			closable : false,
			onVisible : function(){
				document.location.reload(true);
			}
		});
		
		$('.ui.modal.atualizando').modal('show');
		
		$('.ui.icon i')
			.transition('set looping')
			.transition('bounce', '900ms')
		;
		
		$('.ui.checkbox')
			.checkbox();
	}
	
	if($('#_gestor-host-update').length > 0){
		$.formReiniciar();
	}
	
	// ===== Host Forgot Password
	
	if($('#_gestor-form-host-redefine-password').length > 0){
		$.formReiniciar();
	}
	
	// ===== Plataforma Testes
	
	if($('#plataforma-testes').length > 0){
		$('.testBtn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var ajaxOpcao = 'plataforma-testes';
			var variavel = 'variavel';
			
			$.ajax({
				type: 'POST',
				url: gestor.raiz + gestor.moduloCaminho + '/',
				data: {
					opcao : gestor.moduloOpcao,
					ajax : 'sim',
					ajaxOpcao : ajaxOpcao
				},
				dataType: 'json',
				beforeSend: function(){
					//$.carregar_abrir();
				},
				success: function(dados){
					switch(dados.status){
						case 'Ok':
							let append = false;
							if(typeof gestor.plataformaTestesBotao !== typeof undefined && gestor.plataformaTestesBotao !== false){
								append = true;
							} else {
								gestor.plataformaTestesBotao = true;
							}
							
							var target = $('#plataforma-testes .content');
							
							if(append){
								target.append('<br>'+dados.dados);
							} else {
								target.html(dados.dados);
							}
						break;
						default:
							console.log('ERROR - '+ajaxOpcao+' - Dados:');
							console.log(dados);
						
					}
					
					//$.carregar_fechar();
				},
				error: function(txt){
					switch(txt.status){
						case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"),"_self"); break;
						default:
							console.log('ERROR AJAX - '+ajaxOpcao+' - Dados:');
							console.log(txt);
							//$.carregar_fechar();
					}
				}
			});
		});
	}
	
	if($('#_gestor-interface-config-dados').length > 0){
		var formSelector = '#host-configuracao';
		
		function dominioTipo(id, start = false){
			switch(id){
				case 'sistema':
					$('.contProprio').hide();
					$('.controleDominio[data-id="sistema"]').addClass(['active','blue']);
					$('.controleDominio[data-id="proprio"]').removeClass(['active','blue']);
					
					$(formSelector).form('remove fields', ['dominio_proprio_url']);
				break;
				case 'proprio':
					$('.contProprio').show();
					$('.controleDominio[data-id="sistema"]').removeClass(['active','blue']);
					$('.controleDominio[data-id="proprio"]').addClass(['active','blue']);
					
					$(formSelector).form('add rule', 'dominio_proprio_url',{ rules : gestor.interface.regrasValidacao.dominio_proprio_url.rules });
				break;
			}
			
			$('input[name="tipo"]').val(id);
		}
		
		// ===== Formulário.
		
		$(formSelector)
			.form({
				fields : (gestor.interface.regrasValidacao ? gestor.interface.regrasValidacao : {}),
				onSuccess(event, fields){
					
				}
			});
			
		$(formSelector).form('remove fields', ['dominio_proprio_url']);
		
		// ===== Controle de tipo de domínio.
		
		dominioTipo((gestor.host.dominioProprio ? 'proprio' : 'sistema'),true);
		
		$('.controleDominio').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var id = $(this).attr('data-id');
			
			dominioTipo(id);
		});
		
		// ===== Google reCAPTCHA inicialização.
		
		if('googleRecaptchaInstalado' in gestor.host){
			var excluirChecked = false;
			
			$('.google-recaptcha-ativo').removeClass('escondido');
			
			$('.gr-controle').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				$('.gr-controle').removeClass('active');
				
				const action = $(this).attr('data-action');
				switch(action){
					case 'reinstalar':
						$('.google-recaptcha-ativo').addClass('escondido');
						$('.google-recaptcha-instalacao').removeClass('escondido');
						$('input[name="google-recaptcha-comando"]').val('reinstalar');
					break;
					case 'excluir':
						if(excluirChecked){
							$(this).removeClass('active');
							$(this).find('.icon').removeClass('check');
							$(this).find('.icon').addClass('times');
							excluirChecked = false;
							$('input[name="google-recaptcha-comando"]').val('');
						} else {
							$(this).addClass('active');
							$(this).find('.icon').removeClass('times');
							$(this).find('.icon').addClass('check');
							excluirChecked = true;
							$('input[name="google-recaptcha-comando"]').val('excluir');
						}
					break;
					default:
						
				}
			});
		} else {
			$('.google-recaptcha-instalacao').removeClass('escondido');
			$('input[name="google-recaptcha-comando"]').val('instalar');
		}
		
		
	}
	
});