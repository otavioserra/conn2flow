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
	
	// ===== Host Plugins.

	if($('#_gestor-host-plugins').length > 0){
		$('.ui.checkbox').checkbox();
		
		$('input[type="checkbox"]').each(function(){
			if(typeof $(this).attr('data-checked') !== typeof undefined && $(this).attr('data-checked') !== false){
				if($(this).attr('data-checked') == 'checked'){
					$(this).prop( "checked", true );
				}
			}
		});
		
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
		
		// ===== Formulário.
		
		$(formSelector)
			.form({
				fields : (gestor.interface.regrasValidacao ? gestor.interface.regrasValidacao : {}),
				onSuccess(event, fields){
					
				}
			});
			
		$(formSelector).form('remove fields', ['dominio_proprio_url']);
		
		// ===== Controle de tipo de domínio.
		
		var dominioProprio = false;
		
		function dominioTipo(id, start = false){
			switch(id){
				case 'sistema':
					$('.contProprio').hide();
					$('.contRecaptcha').hide();
					$('.controleDominio[data-id="sistema"]').addClass(['active','blue']);
					$('.controleDominio[data-id="proprio"]').removeClass(['active','blue']);
					
					$(formSelector).form('remove fields', ['dominio_proprio_url']);
					
					dominioProprio = false;
				break;
				case 'proprio':
					$('.contProprio').show();
					$('.contRecaptcha').show();
					$('.controleDominio[data-id="sistema"]').removeClass(['active','blue']);
					$('.controleDominio[data-id="proprio"]').addClass(['active','blue']);
					
					$(formSelector).form('add rule', 'dominio_proprio_url',{ rules : gestor.interface.regrasValidacao.dominio_proprio_url.rules });
					
					dominioProprio = true;
				break;
			}
			
			$('input[name="tipo"]').val(id);
		}
		
		dominioTipo((gestor.host.dominioProprio ? 'proprio' : 'sistema'),true);
		
		$('.controleDominio').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var id = $(this).attr('data-id');
			
			dominioTipo(id);
		});
		
		// ===== Controle do reCAPTCHA.
		
		var googleRecaptchaTipo = gestor.host.googleRecaptchaTipo;
		function controleRecaptcha(id, start = false){
			$('.google-recaptcha-instalacao').addClass('escondido');
			$('.google-recaptcha-instalacao-v2').addClass('escondido');
			
			$('input[name="google-recaptcha-tipo"]').val('');
			$('input[name="google-recaptcha-comando"]').val('');
			
			switch(id){
				case 'nenhum':
					if(dominioProprio){
						$('.contRecaptcha').hide();
					}
					
					$('.controleRecaptcha[data-id="nenhum"]').addClass(['active','blue']);
					$('.controleRecaptcha[data-id="recaptcha-v2"]').removeClass(['active','blue']);
					$('.controleRecaptcha[data-id="recaptcha-v3"]').removeClass(['active','blue']);
				break;
				case 'recaptcha-v2':
					if(dominioProprio){
						$('.contRecaptcha').show();
						
						if('googleRecaptchaV2Instalado' in gestor.host){
							$('.google-recaptcha-ativo').removeClass('escondido');
						} else {
							$('.google-recaptcha-ativo').addClass('escondido');
							$('.google-recaptcha-instalacao-v2').removeClass('escondido');
							
							$('input[name="google-recaptcha-tipo"]').val(id);
							$('input[name="google-recaptcha-comando"]').val('instalar');
						}
					}
					
					$('.controleRecaptcha[data-id="nenhum"]').removeClass(['active','blue']);
					$('.controleRecaptcha[data-id="recaptcha-v2"]').addClass(['active','blue']);
					$('.controleRecaptcha[data-id="recaptcha-v3"]').removeClass(['active','blue']);
				break;
				case 'recaptcha-v3':
					if(dominioProprio){
						$('.contRecaptcha').show();
						
						if('googleRecaptchaInstalado' in gestor.host){
							$('.google-recaptcha-ativo').removeClass('escondido');
						} else {
							$('.google-recaptcha-ativo').addClass('escondido');
							$('.google-recaptcha-instalacao').removeClass('escondido');
							
							$('input[name="google-recaptcha-tipo"]').val(id);
							$('input[name="google-recaptcha-comando"]').val('instalar');
						}
					}
					
					$('.controleRecaptcha[data-id="nenhum"]').removeClass(['active','blue']);
					$('.controleRecaptcha[data-id="recaptcha-v2"]').removeClass(['active','blue']);
					$('.controleRecaptcha[data-id="recaptcha-v3"]').addClass(['active','blue']);
				break;
			}
			
			$('input[name="recaptcha-tipo"]').val(id);
			googleRecaptchaTipo = id;
		}
		
		controleRecaptcha(googleRecaptchaTipo,true);
		
		$('.controleRecaptcha').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var id = $(this).attr('data-id');
			
			controleRecaptcha(id);
		});
		
		// ===== Google reCAPTCHA inicialização.
		
		var recaptchaInstalado = false;
		var excluirChecked = false;
		
		switch(googleRecaptchaTipo){
			case 'recaptcha-v2':
				if('googleRecaptchaV2Instalado' in gestor.host){
					recaptchaInstalado = true;
				}
			break;
			case 'recaptcha-v3':
				if('googleRecaptchaInstalado' in gestor.host){
					recaptchaInstalado = true;
				}
			break;
		}
		
		if(recaptchaInstalado){
			$('.google-recaptcha-ativo').removeClass('escondido');
		} else {
			switch(googleRecaptchaTipo){
				case 'recaptcha-v2': $('.google-recaptcha-instalacao-v2').removeClass('escondido'); break;
				case 'recaptcha-v3': $('.google-recaptcha-instalacao').removeClass('escondido'); break;
			}
			
			$('input[name="google-recaptcha-tipo"]').val(googleRecaptchaTipo);
		}
		
		$('.gr-controle').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('.gr-controle').removeClass('active');
			
			const action = $(this).attr('data-action');
			switch(action){
				case 'reinstalar':
					$('.google-recaptcha-ativo').addClass('escondido');
					switch(googleRecaptchaTipo){
						case 'recaptcha-v2': $('.google-recaptcha-instalacao-v2').removeClass('escondido'); break;
						case 'recaptcha-v3': $('.google-recaptcha-instalacao').removeClass('escondido'); break;
					}
					
					$('input[name="google-recaptcha-tipo"]').val(googleRecaptchaTipo);
					$('input[name="google-recaptcha-comando"]').val('reinstalar');
				break;
				case 'excluir':
					if(excluirChecked){
						$(this).removeClass('active');
						$(this).removeClass('orange');
						$(this).addClass('negative');
						$(this).find('.icon').removeClass('check');
						$(this).find('.icon').addClass('times');
						excluirChecked = false;
						$('input[name="google-recaptcha-tipo"]').val('');
						$('input[name="google-recaptcha-comando"]').val('');
					} else {
						$(this).addClass('active');
						$(this).addClass('orange');
						$(this).removeClass('negative');
						$(this).find('.icon').removeClass('times');
						$(this).find('.icon').addClass('check');
						excluirChecked = true;
						$('input[name="google-recaptcha-tipo"]').val(googleRecaptchaTipo);
						$('input[name="google-recaptcha-comando"]').val('excluir');
					}
				break;
				default:
					
			}
		});
	}
	
});