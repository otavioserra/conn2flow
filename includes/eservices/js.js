if(window.location.protocol == "http:" && window.location.hostname != 'localhost'){
	var restOfUrl = window.location.href.substr(5);
	window.location = "https:" + restOfUrl;
}

window.aplicar_scripts_eservices = function(params){
	var getUrlParameter = function getUrlParameter(sParam) {
		var sPageURL = decodeURIComponent(window.location.search.substring(1)),
			sURLVariables = sPageURL.split('&'),
			sParameterName,
			i;

		for (i = 0; i < sURLVariables.length; i++) {
			sParameterName = sURLVariables[i].split('=');

			if (sParameterName[0] === sParam) {
				return sParameterName[1] === undefined ? true : sParameterName[1];
			}
		}
	};

	(function($) {
		$.extend($.fn, {
			makeCssInline: function() {
				this.each(function(idx, el) {
					var style = el.style;
					var properties = [];
					for(var property in style) { 
						if($(this).css(property)) {
							properties.push(property + ':' + $(this).css(property));
						}
					}
					this.style.cssText = properties.join(';');
					$(this).children().makeCssInline();
				});
			}
		});
	}(jQuery));
	
	if(ajax_vars.b2make_loja_iframe_redirect){
		window.parent.postMessage({opcao:'redirect',url:ajax_vars.b2make_loja_url_atual}, '*');
	}
	
	if($('#b2make-retorno-pagamento-processando').length){
		var opcao = 'payment-process';
		var count = 0;
		
		function pagamento_conferir(){
			count++;
			
			$.ajax({
				type: 'POST',
				url: variaveis_js.site_raiz,
				data: { 
					ajax : 'sim',
					opcao : opcao,
					count : count,
					ajax_option : 'e-services'
				},
				beforeSend: function(){
				},
				success: function(txt){
					if(
						txt.charAt(0) == "{" || 
						txt.charAt(0) == "["
					){
						var dados = eval('(' + txt + ')');
						
						switch(dados.status){
							case 'Ok':
								if(dados.continuar){
									pagamento_conferir();
								} else {
									if(ajax_vars.b2make_loja_iframe){
										window.parent.postMessage({opcao:'redirect',url:ajax_vars.b2make_loja_url_base + 'payment-return'}, '*');
									} else {
										window.open('payment-return','_self');
									}
								}
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
				}
			});
		}
		
		pagamento_conferir();
	}
	
	if($('#esqueceu_senha2').length){
		$.google_recaptcha_load();
		
		$("#esqueceu_senha2").bind('submit',function(){
			var enviar = true;
			var campo;
			var post;
			var mens;
			var campos = Array();
			var posts = Array();
			var opcao = 'e-services/'+ajax_vars.b2make_loja_atual+'/forgot-your-password-request';
			var form_id = 'esqueceu_senha2';
			var href = '';
			var limpar_campos = true;
			var mudar_pagina = false;
			
			campo = "esqueceu_senha-email"; mens = "&Eacute; obrigat&oacute;rio definir o E-mail!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			// Checar email
			campo = "esqueceu_senha-email"; mens = "E-mail inv&aacute;lido, preencha o campo de e-mail v&aacute;lido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			if(!enviar){
				return false;
			} else {
				if($.google_recaptcha_is_answered()){
					$.google_recaptcha_reset();
					
					form_serialize = $('#'+form_id).serialize();
					$('#'+form_id)[0].reset();
					window.enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
				} else {
					$.alerta_open("<p>&Eacute; necess&aacute;rio validar o reCAPTCHA para comprovar que voc&ecirc; n&atilde;o &eacute; um robo!</p>",false,false);
				}
			}
			
			return false;
		});
	}
	
	if($('#redefinir_senha2').length){
		var eservices_cadastrar_senha = false;
		
		$("#mens_senha").hide();
		
		$("#cadastro-senha").blur(function() {
			eservices_validar_senha();
		});
		
		$("#cadastro-senha2").blur(function() {
			eservices_validar_senha();
		});
		
		$("#cadastro-senha").keyup(function(eventObject){
			var perc;
			var bpos;
			
			if(eventObject.keyCode != 9){
				if($('#cadastro-usuario').val()){
					$('#result').html(passwordStrength($('#cadastro-senha').val(),$('#cadastro-usuario').val())) ; 
					perc = passwordStrengthPercent($('#cadastro-senha').val(),$('#cadastro-usuario').val());
					
					bpos=" $('#colorbar').css( {backgroundPosition: \"0px -" ;
					bpos = bpos + perc + "px";
					bpos = bpos + "\" } );";
					bpos=bpos +" $('#colorbar').css( {width: \"" ;
					bpos = bpos + (perc * 1.9) + "px";
					bpos = bpos + "\" } );";
					eval(bpos);
						$('#percent').html(" " + perc  + "% ");
				} else {
					$.alerta_open("<p>Defina o usuário antes da senha!</p>",false,false);
				}
			}
		});
		
		$("#redefinir_senha2").bind('submit',function(){
			var enviar = true;
			var campo;
			var post;
			var mens;
			var campos = Array();
			var posts = Array();
			var opcao = 'e-services/'+ajax_vars.b2make_loja_atual+'/password-reset-request';
			var form_id = 'redefinir_senha2';
			var href = '';
			var limpar_campos = true;
			var mudar_pagina = false;
			
			campo = "cadastro-senha"; mens = "&Eacute; obrigat&oacute;rio definir a Senha!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			campo = "cadastro-senha2"; mens = "&Eacute; obrigat&oacute;rio definir o Redigite a Senha!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			if(
				!eservices_cadastrar_senha
			){ eservices_validar_senha(); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			if(!enviar){
				return false;
			} else {
				form_serialize = $('#'+form_id).serialize();
				$('#'+form_id)[0].reset();
				window.enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
			}
			
			return false;
		});
		
		function eservices_validar_senha(){
			var mens;
			var cor;
			
			if($("#cadastro-senha").val() && $("#cadastro-senha2").val()){
				var valido = true;
				
				if($("#cadastro-senha").val() != $("#cadastro-senha2").val()){
					valido = false;
					mens = "Senha e Redigite a Senha s&atilde;o diferentes.";
					$.alerta_open(mens,false,false);
				}
				
				if(checkStr($("#cadastro-senha").val())){
					valido = false;
					mens = "Caracteres inv&aacute;lidos, apenas caracteres alfanum&eacute;ricos e ( _ ou @ ou . )";
					$.alerta_open(mens,false,false);
				}
				
				if(!limites_str($("#cadastro-senha").val(),4,20)){
					valido = false;
					mens = "Senha no m&iacute;nimo 4 e no m&aacute;x 20 caracteres.";
					$.alerta_open(mens,false,false);
				}
				
				$("#mens_senha").removeClass('ui-state-highlight');
				$("#cadastro-senha2").removeClass('input-vazio');
				$("#cadastro-senha").removeClass('input-vazio');
				$("#mens_senha").removeClass('input-vazio');
				$("#mens_senha").show();
				
				if(!valido){
					cor = cor1;
					eservices_cadastrar_senha = false;
					$("#cadastro-senha").val('');
					$("#cadastro-senha2").val('');
					$("#cadastro-senha").addClass('input-vazio');
					$("#cadastro-senha2").addClass('input-vazio');
					$("#mens_senha").addClass('input-vazio');
				} else {
					mens = "Senha OK.";
					cor = cor2;
					eservices_cadastrar_senha = true;
					$("#mens_senha").addClass('ui-state-highlight');
				}
				
				$("#mens_senha").html(mens);
			}
		}
	}
	
	if($('#b2make-signin-login-cont').length > 0){
		var login_clicou_botao = false;
		
		$('#b2make-signin-cadastro-facebook').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'e-services/'+ajax_vars.b2make_loja_atual+'/signin-facebook','_self');
		});
		
		$('#b2make-signin-login-entrar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			signin();
		});
		$(document.body).on('keyup','#b2make-signin-login #senha-login',function(e){
			if(e.keyCode == 13){
				signin();
			}
		});
		
		
		function signin(){
			var enviar = true;
			var campo;
			var mens;
			
			campo = "b2make-signin-login #usuario"; mens = "Preencha o email"; if(!$("#"+campo).val() || $("#"+campo).val() == $("#"+campo).prop('defaultValue')){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "b2make-signin-login #senha-login"; mens = "Preencha a senha"; if(!$("#"+campo).val() || $("#"+campo).val() == $("#"+campo).prop('defaultValue')){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			
			login_clicou_botao = true;
			
			if(enviar){
				window.enviar_form_simples('b2make-signin-login');
			}
			
			return false;
		}
	}
	
	if($('#b2make-signup-cadastro').length > 0){
		// ==================================== Atualização Cadastro =============================
		
		$.b2make_check_box_load();
		
		$('#cnpj').hide();
		
		$(".cnpj").mask("00.000.000/0000-00", {clearIfNotMatch: true});
		$(".cpf").mask("000.000.000-00", {clearIfNotMatch: true});
		
		var MaskBehavior = function (val) {
			return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
		},
		MaskOptions = {
			clearIfNotMatch: true,
			onKeyPress: function(val, e, field, options) {
				field.mask(MaskBehavior.apply({}, arguments), options);
			}
		};
		$('.telefone').mask(MaskBehavior, MaskOptions);
		
		$('#cpf-cnpj').on('b2make-check-box-clicked',function(){
			var val = $('#cpf-cnpj-check').val();
			
			if(val == 'CPF'){
				$('#cpf').show();
				$('#cnpj').hide();
				$('#cnpj').trigger('reset');
			} else {
				$('#cpf').hide();
				$('#cpf').trigger('reset');
				$('#cnpj').show();
			}
		});
		
		// ==================================== Facebook Pixel =============================
		
		fbq('track', 'InitiateCheckout');
		if(ajax_vars.recaptcha_enable){
			$.google_recaptcha_load();
		} else {
			$('#b2make-label-recaptcha').hide();
		}
		
		$(document.body).on('resetar','.b2make-cadastro-validacao,.b2make-cadastro-validacao-2',function(){
			var id = $(this).attr('id');
			
			if($('#b2make-cadastro-validacao-'+id).length){
				$('#b2make-cadastro-validacao-'+id).remove();
				$(this).removeClass('b2make-cadastro-invalido');
			}
		});
		
		$(document.body).on('focus','.b2make-cadastro-validacao',function(){
			$(this).removeClass('b2make-campo-validado');
		});
		
		$(document.body).on('validar','.b2make-cadastro-validacao',function(){
			var obj = this;
			var id = $(obj).attr('id');
			var mens = '';
			var validado = true;
			
			if($(this).hasClass('b2make-campo-validado')){
				return false;
			}
			
			switch(id){
				case 'nome':
					if(!$(obj).val()){
						mens = '&Eacute; obrigat&oacute;rio definir o Primeiro Nome!';
						validado = false;
					} else {
						$(obj).addClass('b2make-campo-validado');
					}
				break;
				case 'ultimo_nome':
					if(!$(obj).val()){
						mens = '&Eacute; obrigat&oacute;rio definir o &Uacute;ltimo Nome!';
						validado = false;
					} else {
						$(obj).addClass('b2make-campo-validado');
					}
				break;
				case 'email':
					if(!$(obj).val()){
						mens = '&Eacute; obrigat&oacute;rio definir o E-mail!';
						validado = false;
					} else if(!checkMail($(obj).val())){
						mens = 'E-mail inv&aacute;lido!';
						validado = false;
					} else {
						if(!b2make.verificando_email){
							b2make.verificando_email = true;
							
							$.ajax({
								type: 'POST',
								url: '.',
								data: { ajax : 'sim' , opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/email-verificar' , email_usuario : $(obj).val() },
								beforeSend: function(){
									$('#ajax_lendo').fadeIn(tempo_animacao);
								},
								success: function(txt){
									$('#ajax_lendo').fadeOut(tempo_animacao);
									var dados = eval('(' + txt + ')');
									
									var mens = "";
									var cor;
									
									if(dados.status == 'EmUso'){
										validado = false;
										mens = 'Este email j&aacute; est&aacute; em uso! Escolha outro.';
										
										cadastro_validacao_operacoes({
											obj : obj,
											mens : mens,
											id : id,
											validado : validado
										});
									} else {
										$(obj).addClass('b2make-campo-validado');
										cadastro_local();
									}
									
									b2make.verificando_email = false;
								},
								error: function(txt){
									b2make.verificando_email = false;
								}
							});
						}
					}
				break;
				case 'senha':
					b2make.cadastro_senha_validado = false;
					if(!$(obj).val()){
						mens = '&Eacute; obrigat&oacute;rio definir a Senha!';
						validado = false;
					} else if(checkStr($(obj).val())){
						mens = 'Caracteres inv&aacute;lidos, apenas caracteres alfanum&eacute;ricos e ( _ ou @ ou . )';
						validado = false;
					} else if(!limites_str($(obj).val(),4,20)){
						mens = 'Senha no m&iacute;nimo 4 e no m&aacute;x 20 caracteres.';
						validado = false;
					} else {
						b2make.cadastro_senha_validado = true;
						$(obj).addClass('b2make-campo-validado');
					}
				break;
				case 'senha2':
					if(!$(obj).val()){
						mens = '&Eacute; obrigat&oacute;rio definir a Senha!';
						validado = false;
					} else if(checkStr($(obj).val())){
						mens = 'Caracteres inv&aacute;lidos, apenas caracteres alfanum&eacute;ricos e ( _ ou @ ou . )';
						validado = false;
					} else if(!limites_str($(obj).val(),4,20)){
						mens = 'Senha no m&iacute;nimo 4 e no m&aacute;x 20 caracteres.';
						validado = false;
					} else if(b2make.cadastro_senha_validado){
						if($(obj).val() != $('#senha').val()){
							mens = 'Senha e Confirme Sua Senha s&atilde;o diferentes.';
							validado = false;
						} else {
							$(obj).addClass('b2make-campo-validado');
						}
					}
				break;
				case 'telefone':
					if(!$(obj).val()){
						mens = '&Eacute; obrigat&oacute;rio definir o Telefone!';
						validado = false;
					} else {
						$(obj).addClass('b2make-campo-validado');
					}
				break;
				case 'cpf':
					if(!$(obj).val() && $('#cpf-cnpj-check').val() == 'CPF'){
						mens = '&Eacute; obrigat&oacute;rio definir o CPF!';
						validado = false;
					} else if(!validarCPF($(obj).val())){
						mens = 'CPF informado n&atilde;o &eacute; v&aacute;lido. Favor informar um CPF v&aacute;lido!';
						validado = false;
					} else {
						$(obj).addClass('b2make-campo-validado');
					}
				break;
				case 'cnpj':
					if(!$(obj).val() && $('#cpf-cnpj-check').val() == 'CNPJ'){
						mens = '&Eacute; obrigat&oacute;rio definir o CNPJ!';
						validado = false;
					} else {
						$(obj).addClass('b2make-campo-validado');
					}
				break;
				
			}
			
			cadastro_validacao_operacoes({
				obj : obj,
				mens : mens,
				id : id,
				validado : validado
			});
		});
		
		function cadastro_validacao_operacoes(p){
			if(!p.validado){
				if(!$('#b2make-cadastro-validacao-'+p.id).length){
					$(p.obj_after ? p.obj_after : p.obj).after($('<div id="b2make-cadastro-validacao-'+p.id+'" class="b2make-cadastro-invalido-mens"></div>'));
					$(p.obj).addClass('b2make-cadastro-invalido');
				}
				
				$('#b2make-cadastro-validacao-'+p.id).html(p.mens);
			}
		}
		
		var sep = "";
		var cadastrar_usuario = false;
		var cadastrar_senha = false;
		var edit_email_id = 'edit_email';
		var email_id = 'email';
		var email2_id = 'email2';
		var senha_id = 'senha';
		var senha2_id = 'senha2';
		var nome_id = 'nome';
		var form_id = 'b2make-signup-cadastro-form';
		var cor1 = '#F00'; // Vermelho
		var cor2 = '#0C6'; // Verde
		
		$('#b2make-signup-cadastrar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if(!b2make.cadastrar_clicked_anti_loop){
				if(!b2make.site_host_ok){
					b2make.cadastrar_clicked = true;
				}
			} else {
				b2make.cadastrar_clicked_anti_loop = true;
			}
			
			if(b2make.cadastro_facebook){
				cadastro_facebook();
			} else {
				cadastro_local();
			}
		});
		
		$('#b2make-signup-cadastro-facebook').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			b2make.cadastro_facebook = true;
			$('#b2make-signup-cadastrar').trigger('mouseup');
		});
		
		$("input#"+senha_id+",input#"+senha2_id+",#"+email2_id+",#"+email_id+",#"+nome_id).on('mouseup touchstart focus',function() {
			b2make.cadastro_facebook = false;
		});
		
		function cadastro_local(){
			var enviar = true;
			var campo;
			var post;
			var mens;
			var campos = Array();
			var posts = Array();
			var opcao = '';
			var href = '';
			var limpar_campos = true;
			var mudar_pagina = false;
			
			$('.b2make-cadastro-validacao').trigger('resetar');
			
			$('.b2make-cadastro-validacao').each(function(){
				$(this).trigger('validar');
				
				if(!$(this).hasClass('b2make-campo-validado')){
					enviar = false;
				}
			});
			
			if(!enviar){
				return false;
			} else {
				if(ajax_vars.recaptcha_enable){
					if($.google_recaptcha_is_answered()){
						$.google_recaptcha_reset();
						
						window.form_serialize = $('#'+form_id).serialize();
						window.enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
						$('#'+form_id)[0].reset();
						cadastrar_usuario = false;
						cadastrar_senha = false;
					} else {
						$.alerta_open("<p>&Eacute; necess&aacute;rio validar o reCAPTCHA para comprovar que voc&ecirc; n&atilde;o &eacute; um robo!</p>",false,false);
					}
				} else {
					$('#ajax_lendo').fadeIn(tempo_animacao);
					window.form_serialize = $('#'+form_id).serialize();
					window.enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
					$('#'+form_id)[0].reset();
					cadastrar_usuario = false;
					cadastrar_senha = false;
				}
			}
		}
		
		function cadastro_facebook(){
			var enviar = true;
			var campo;
			var post;
			var mens;
			var campos = Array();
			var posts = Array();
			var opcao = '';
			var href = '';
			var limpar_campos = true;
			var mudar_pagina = false;
			
			$('#ajax_lendo').fadeIn(tempo_animacao);
			window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'e-services/'+ajax_vars.b2make_loja_atual+'/signup-facebook','_self');
		}
	}
	
	if($('#b2make-voucher-cont').length > 0){
		if($( "#b2make-voucher-lista-voucher option:selected" ).val() == '1'){
			$( "#b2make-voucher-lista-servicos-cont").show();
		} else {
			$( "#b2make-voucher-lista-servicos-cont").hide();
		}
	
		$("#b2make-voucher-imprimir").bind('click touchstart',function(){
			window.open(variaveis_js.site_raiz+"includes/eservices/print.php","Imprimir","menubar=0,location=0,height=700,width=700");
		});
		
		$("#b2make-voucher-alterar-campos").bind('click touchstart',function(){
			$('#b2make-voucher-cont').hide();
			$('#b2make-voucher-form-presente').show();
			$('#b2make-voucher-form-email').hide();
			$('#b2make-voucher-lay-concluir').hide();
			$('#b2make-voucher-lay-layouts').hide();
		});
		
		$("#b2make-voucher-visulizar").bind('click touchstart',function(){
			$('#b2make-voucher-cont').show();
			$('#b2make-voucher-form-presente').hide();
			$('#b2make-voucher-form-email').hide();
			$('#b2make-voucher-lay-concluir').hide();
			$('#b2make-voucher-lay-layouts').hide();
		});
		
		$("#b2make-voucher-enviar-email").bind('click touchstart',function(){
			$('#b2make-voucher-cont').hide();
			$('#b2make-voucher-form-presente').hide();
			$('#b2make-voucher-form-email').show();
			$('#b2make-voucher-lay-concluir').hide();
			$('#b2make-voucher-lay-layouts').hide();
		});
		
		$("#b2make-voucher-concluir").bind('click touchstart',function(){
			$('#b2make-voucher-cont').hide();
			$('#b2make-voucher-form-presente').hide();
			$('#b2make-voucher-form-email').hide();
			$('#b2make-voucher-lay-concluir').show();
			$('#b2make-voucher-lay-layouts').hide();
		});
		
		$("#b2make-voucher-tema").bind('click touchstart',function(){
			$('#b2make-voucher-cont').hide();
			$('#b2make-voucher-form-presente').hide();
			$('#b2make-voucher-form-email').hide();
			$('#b2make-voucher-lay-concluir').hide();
			$('#b2make-voucher-lay-layouts').show();
		});
		
		$('#b2make-voucher-form-presente input,#b2make-voucher-form-presente textarea,#b2make-voucher-form-email input').on('blur',function(){
			if(!$(this).val()){
				$(this).val($(this).attr('data-default'));
			}
		});
		
		$(".b2make-voucher-temas").bind('click touchstart',function(){
			var id = $(this).attr('data-id');
			var id_pedidos = $('#b2make-voucher-lay-layouts').attr('data-id');

			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/voucher-temas' , id : id , id_pedidos : id_pedidos },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					if(!access_denied(dados))return;
					
					global_vars.link_nao_mudar_scroll = true;
					$.link_trigger('e-services/'+ajax_vars.b2make_loja_atual+'/voucher');
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$("#b2make-voucher-lista-pedidos").bind('change',function(){
			var id = $(this).val();

			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/voucher-orders' , id : id },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					if(!access_denied(dados))return;
					
					global_vars.link_nao_mudar_scroll = true;
					$.link_trigger('e-services/'+ajax_vars.b2make_loja_atual+'/voucher');
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$("#b2make-voucher-lista-voucher").bind('change',function(){
			var id = $("#b2make-voucher-lista-pedidos").val();
			
			if($(this).val() == '1'){
				$( "#b2make-voucher-lista-servicos-cont").show();
			} else {
				$( "#b2make-voucher-lista-servicos-cont").hide();
			}

			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/voucher-orders' , voucher : 'sim' , voucher_opcao : $(this).val() , id : id },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					if(!access_denied(dados))return;
					
					global_vars.link_nao_mudar_scroll = true;
					$.link_trigger('e-services/'+ajax_vars.b2make_loja_atual+'/voucher');
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$("#b2make-voucher-lista-servicos").bind('change',function(){
			var id = $("#b2make-voucher-lista-pedidos").val();

			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/voucher-orders' , servico : 'sim' , servico_opcao : $(this).val() , id : id },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					if(!access_denied(dados))return;
					
					global_vars.link_nao_mudar_scroll = true;
					$.link_trigger('e-services/'+ajax_vars.b2make_loja_atual+'/voucher');
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$("#b2make-voucher-para-presente,#b2make-voucher-para-voce").bind('click touchstart',function(){
			var flag = $("#b2make-voucher-presente-flag").val();
			
			if(flag == '1'){
				$("#b2make-voucher-presente-flag").val('2');
				$('#b2make-voucher-cont').hide();
				$('#b2make-voucher-form-presente').show();
				$('#b2make-voucher-form-email').hide();
				$('#b2make-voucher-lay-concluir').hide();
				$('#b2make-voucher-lay-layouts').hide();
			} else {
				$("#b2make-voucher-presente-flag").val('1');
				$('#b2make-voucher-cont').show();
				$('#b2make-voucher-form-presente').hide();
				$('#b2make-voucher-form-email').hide();
				$('#b2make-voucher-lay-concluir').hide();
				$('#b2make-voucher-lay-layouts').hide();
			}
			
			voucher_mudar_campos();
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/voucher-gift' , flag : flag },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					if(!access_denied(dados))return;
					
					if(flag == '2'){
						global_vars.link_nao_mudar_scroll = true;
						$.link_trigger('e-services/'+ajax_vars.b2make_loja_atual+'/voucher');
					} else 
						$('#ajax_lendo').fadeOut(tempo_animacao);
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$("#b2make-voucher-form-presente").bind('submit',function() {
			var enviar = true;
			var campo;
			var mens;
			
			campo = "b2make-voucher-form-presente-de"; mens = "Preencha o campo De"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "b2make-voucher-form-presente-para"; mens = "Preencha o campo Para"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "b2make-voucher-form-presente-mensagem"; mens = "Preencha o campo Mensagem"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			
			if(enviar){
				window.enviar_form_simples('b2make-voucher-form-presente');
			}
			
			return false;
		});
		
		function voucher_mudar_campos(){
			var flag = $("#b2make-voucher-presente-flag").val();
			
			if(flag == '2'){
				$('#b2make-voucher-lay-destinatario').show();
				$('#b2make-voucher-alterar-campos').show();
				$('#b2make-voucher-para-voce').show();
				$('#b2make-voucher-para-presente').hide();
				$('#b2make-voucher-lay-concluir').hide();
				$('#b2make-voucher-lay-layouts').hide();
			} else {
				$('#b2make-voucher-lay-destinatario').hide();
				$('#b2make-voucher-alterar-campos').hide();
				$('#b2make-voucher-para-voce').hide();
				$('#b2make-voucher-para-presente').show();
				$('#b2make-voucher-lay-concluir').hide();
				$('#b2make-voucher-lay-layouts').hide();
			}
		}
		
		voucher_mudar_campos();
		
		function checkMail(mail){
			var er = new RegExp(/^[A-Za-z0-9_\-\.]+@[A-Za-z0-9_\-\.]{2,}\.[A-Za-z0-9]{2,}(\.[A-Za-z0-9])?/);
			if(typeof(mail) == "string"){
				if(er.test(mail)){ return true; }
			}else if(typeof(mail) == "object"){
				if(er.test(mail.value)){
							return true;
						}
			}else{
				return false;
				}
		}
		
		$("#b2make-voucher-botao-enviar-email").bind('click touchstart',function(){
			var campo = 'b2make-voucher-email'
			if($("#"+campo).val()){
				var email = $("#"+campo).val();
				var mens;
				
				if(!checkMail(email)){
					mens = "E-mail incorreto.";
					$.alerta_open(mens,false,false);
					$("#"+campo).addClass('input-vazio');
				} else {
					$("#"+campo).removeClass('input-vazio');
					var email_cont = $('<div></div>');
					
					email_cont.css('position','absolute');
					email_cont.css('top','-11000px');
					email_cont.css('left','50px');
					email_cont.css('backgroundColor','#CCC');
					email_cont.width(1000);
					email_cont.height(10000);
					
					email_cont.html($('#b2make-voucher-cont').html());
					email_cont.appendTo('body');
					email_cont.makeCssInline();
					
					if($("#b2make-voucher-presente-flag").val() == '1')email_cont.find('#b2make-voucher-lay-destinatario').remove();
					
					var email_txt = email_cont.html();
					
					$.ajax({
						type: 'POST',
						url: '.',
						data: { ajax : 'sim' , opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/voucher-send-mail' , voucher : email_txt , email : email , id_pedidos : $('#b2make-voucher-lista-pedidos option:selected').val() },
						beforeSend: function(){
							$('#ajax_lendo').fadeIn(tempo_animacao);
						},
						success: function(txt){
							$('#ajax_lendo').fadeOut(tempo_animacao);
							
							var dados = eval('(' + txt + ')');
							if(!access_denied(dados))return;
							
							if(dados.ok){
								mens = "E-mail enviado com sucesso.";
							} else {
								if(dados.erro){
									mens = dados.erro;
								} else {
									mens = "Ocorreu um erro indefinido.";
								}
							}
							
							$.alerta_open(mens,false,false);
							$('#b2make-voucher-cont').show();
							$('#b2make-voucher-form-presente').hide();
							$('#b2make-voucher-form-email').hide();
						},
						error: function(txt){
							$('#ajax_lendo').fadeOut(tempo_animacao);
						}
					});
				}
			} else {
				mens = "Defina o Email antes de enviar!";
				$.alerta_open(mens,false,false);
				$("#"+campo).addClass('input-vazio');
			}
		});
		
		$("#b2make-voucher-concluir").bind('click touchstart',function(){
			var mens;
			
			var email_cont = $('<div></div>');
			
			email_cont.css('position','absolute');
			email_cont.css('top','-11000px');
			email_cont.css('left','50px');
			email_cont.css('backgroundColor','#CCC');
			email_cont.width(1000);
			email_cont.height(10000);
			
			email_cont.html($('#b2make-voucher-cont').html());
			email_cont.appendTo('body');
			email_cont.makeCssInline();
			
			if($("#b2make-voucher-presente-flag").val() == '1')email_cont.find('#b2make-voucher-lay-destinatario').remove();
			
			var email_txt = email_cont.html();
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/voucher-complete' , voucher : email_txt , id_pedidos : $('#b2make-voucher-lista-pedidos option:selected').val() },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					
					var dados = eval('(' + txt + ')');
					if(!access_denied(dados))return;
					
					if(dados.ok){
						mens = "Foi enviado o voucher para o seu e-mail do cadastro com sucesso.";
					} else {
						if(dados.erro){
							mens = dados.erro;
						} else {
							mens = "Ocorreu um erro indefinido.";
						}
					}
					
					$.alerta_open(mens,false,false);
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$(".b2make-voucher-menu-botoes").bind('mouseover mouseout',function(e){
			e.stopPropagation();
		});
		
		$("#b2make-voucher-menu").bind('mouseover',function(e){
			$(this).css('backgroundColor','#3C6298');
		});
		
		$("#b2make-voucher-menu").bind('mouseout',function(e){
			$(this).css('backgroundColor','#A1BC31');
		});
	}
	
	var mais_resultados = {};

	function checkMail(mail){
		var er = new RegExp(/^[A-Za-z0-9_\-\.]+@[A-Za-z0-9_\-\.]{2,}\.[A-Za-z0-9]{2,}(\.[A-Za-z0-9])?/);
		if(typeof(mail) == "string"){
			if(er.test(mail)){ return true; }
		}else if(typeof(mail) == "object"){
			if(er.test(mail.value)){
						return true;
					}
		}else{
			return false;
			}
	}
	
	function checkStr(pass){
		var er = new RegExp(/[^A-Za-z0-9_@.]/);
		if(typeof(pass) == "string"){
			if(er.test(pass)){ return true; }
		}else if(typeof(pass) == "object"){
			if(er.test(pass.value)){
						return true;
					}
		}else{
			return false;
			}
	}
	
	function limites_str(str,l1,l2){
		if(str.length >= l1 && str.length <= l2 )	
			return true;
		else
			return false;
	}
	
	function access_denied(dados){
		if(!dados)dados = {};
		if(dados.access_denied){
			window.link_trigger.attr('href',document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+(dados.redirect_local ? dados.redirect_local : '')).trigger('click');
			return false;
		} else {
			return true;
		}
	}

	function formatMoney(n, c, d, t){
		c = isNaN(c = Math.abs(c)) ? 2 : c, 
		d = d == undefined ? "." : d, 
		t = t == undefined ? "," : t, 
		s = n < 0 ? "-" : "", 
		i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", 
		j = (j = i.length) > 3 ? j % 3 : 0;
		return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
	}

	// ================================= Carrinho ===========================
	
	if($('#b2make-cart-cont').length > 0){
		$.input_delay_to_change = function(p){
			if(!b2make.input_delay){
				b2make.input_delay = new Array();
				b2make.input_delay_count = 0;
			}
			
			b2make.input_delay_count++;
			
			var valor = b2make.input_delay_count;
			
			b2make.input_delay.push(valor);
			b2make.input_value = p.value;
			
			setTimeout(function(){
				if(b2make.input_delay[b2make.input_delay.length - 1] == valor){
					input_change_after_delay({value:b2make.input_value,trigger_selector:p.trigger_selector,trigger_event:p.trigger_event,trigger_params:p.trigger_params});
				}
			},b2make.input_delay_timeout);
		}
		
		function input_change_after_delay(p){
			$(p.trigger_selector).trigger(p.trigger_event,[p.value,p.trigger_params]);
			
			b2make.input_delay = false;
		}
		
		function input_delay(){
			if(!b2make.input_delay_timeout) b2make.input_delay_timeout = 400;
		}
		
		input_delay();
		
		function carrinho(){
			var cart_cor_1 = ajax_vars.b2make_cart_cor_1;
			var cart_cor_2 = ajax_vars.b2make_cart_cor_2;
			var cart_cor_3 = ajax_vars.b2make_cart_cor_3;
			
			if(ajax_vars.b2make_add_cart_id){
				var disponibilidade = ajax_vars.b2make_disponibilidade;
				var dados = disponibilidade[ajax_vars.b2make_add_cart_id];
				
				window.parent.postMessage({opcao:'google_analytics', evento:'add_to_cart', item_id:ajax_vars.b2make_add_cart_id , item_quant:1 ,item_dados:dados}, '*');
			}
			
			$(".inteiro").numeric();
			$(document.body).on('selectstart dragstart','.b2make-noselect', function(evt){ evt.preventDefault(); return false; });
			
			if($('.b2make-store-cart-row').length > 0){
				$('#b2make-store-cart-without-services').hide();
				$('#b2make-store-cart-checkout').show();
			} else {
				$('#b2make-store-cart-without-services').show();
				$('#b2make-store-cart-checkout').hide();
			}
			
			if(ajax_vars.b2make_carrinho_zerado)$('#b2make-store-cart-checkout').hide();
			
			// Aplicação das cores no botão de exclusão de serviço do carrinho.
			
			$('.b2make-store-cart-remove').css('color',cart_cor_1);
			$('.b2make-store-cart-remove').css('background-color',cart_cor_2);
			$('.b2make-store-cart-remove').css('border-color',cart_cor_1);
			$('.b2make-store-cart-remove').css('border-style','solid');
			$('.b2make-store-cart-remove').css('border-width','1px');
			
			$(".b2make-store-cart-remove").hover(
				function(){
					$(this).css('color',cart_cor_2);
					$(this).css('background-color',cart_cor_1);
				},
				function(){
					$(this).css('color',cart_cor_1);
					$(this).css('background-color',cart_cor_2);
				}
			);
			
			// Aplicação das cores no botão de diminuir/aumentar quantidade de serviço do carrinho.
			
			$('.b2make-store-cart-quantity-change-minus,.b2make-store-cart-quantity-change-plus').css('color',cart_cor_2);
			$('.b2make-store-cart-quantity-change-minus,.b2make-store-cart-quantity-change-plus').css('background-color',cart_cor_1);
			$('.b2make-store-cart-quantity-change-minus,.b2make-store-cart-quantity-change-plus').css('border-color',cart_cor_1);
			$('.b2make-store-cart-quantity-change-minus,.b2make-store-cart-quantity-change-plus').css('border-style','solid');
			$('.b2make-store-cart-quantity-change-minus,.b2make-store-cart-quantity-change-plus').css('border-width','1px');
			
			$(".b2make-store-cart-quantity-change-minus,.b2make-store-cart-quantity-change-plus").hover(
				function(){
					$(this).css('color',cart_cor_1);
					$(this).css('background-color',cart_cor_2);
				},
				function(){
					$(this).css('color',cart_cor_2);
					$(this).css('background-color',cart_cor_1);
				}
			);
			
			// Aplicação das cores no botão de continuar comprando.
			
			$('#b2make-store-keep-buying').css('color',cart_cor_1);
			$('#b2make-store-keep-buying').css('background-color',cart_cor_2);
			$('#b2make-store-keep-buying').css('border-color',cart_cor_1);
			$('#b2make-store-keep-buying').css('border-style','solid');
			$('#b2make-store-keep-buying').css('border-width','1px');
			
			$("#b2make-store-keep-buying").hover(
				function(){
					$(this).css('color',cart_cor_2);
					$(this).css('background-color',cart_cor_1);
				},
				function(){
					$(this).css('color',cart_cor_1);
					$(this).css('background-color',cart_cor_2);
				}
			);
			
			$(document.body).on('keyup','.b2make-store-cart-quantity',function(e){
				var value = parseInt($(this).val());
				var id = $(this).parent().parent().parent().attr('data-id');
				var disponibilidade = ajax_vars.b2make_disponibilidade;
				var dados = disponibilidade[id];
				
				if(value > 100){
					value = 100;
					$(this).val(value);
				}
				
				if(!value){
					value = 0;
				}
				
				if(value > parseInt(dados.quantidade)){
					value = dados.quantidade;
					
					$(this).val(value);
					$.alerta_open('N&atilde;o &eacute; poss&iacute;vel inserir mais deste servi&ccedil;o no carrinho pois s&oacute; &eacute; permitido no m&aacute;ximo <b>'+value+'</b> deste servi&ccedil;o nesta compra',false,false);
				}
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'quantidade-change',
					trigger_params:{
						id:id
					},
					value:value
				});
			});
			
			$('.b2make-store-cart-quantity-change-minus,.b2make-store-cart-quantity-change-plus').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var id = $(this).parent().parent().parent().attr('data-id');
				var disponibilidade = ajax_vars.b2make_disponibilidade;
				var dados = disponibilidade[id];
				
				var minus = false;
				if($(this).hasClass('b2make-store-cart-quantity-change-minus')){
					minus = true;
				}
				
				var value = parseInt($(this).parent().find('.b2make-store-cart-quantity').val());
				
				if(minus){
					value--;
				} else {
					value++;
				}
				
				if(value > 100){
					value = 100;
				}
				
				if(!value || value < 0 || value == 0){
					store_cart_remove({
						id:id
					});
				} else {
					if(value > parseInt(dados.quantidade)){
						value = dados.quantidade;
						$.alerta_open('N&atilde;o &eacute; poss&iacute;vel inserir mais deste servi&ccedil;o no carrinho pois s&oacute; &eacute; permitido no m&aacute;ximo <b>'+value+'</b> deste servi&ccedil;o nesta compra',false,false);
					}
					
					$(this).parent().find('.b2make-store-cart-quantity').val(value);
					
					$('#b2make-listener').trigger('quantidade-change',[value,{
						id:id
					}]);
				}
			});
			
			$(document.body).on('quantidade-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var id = p.id;
				var opcao = 'e-services/'+ajax_vars.b2make_loja_atual+'/carrinho-quantidade';
				var quant = $('.b2make-store-cart-row[data-id="'+id+'"]').find('.b2make-scc-3').find('.b2make-store-cart-quantity-change-mask').find('.b2make-store-cart-quantity').val();
				var data = { 
					ajax : 'sim',
					opcao : opcao,
					id : id,
					quantidade : quant
				};
				
				if((typeof getUrlParameter('_iframe_session') !== typeof undefined && getUrlParameter('_iframe_session') !== false)){
					data._iframe_session = getUrlParameter('_iframe_session');
				}
				
				$.ajax({
					type: 'POST',
					url: '.',
					data: data,
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						if(
							txt.charAt(0) == "{" || 
							txt.charAt(0) == "["
						){
							var dados = eval('(' + txt + ')');
							
							switch(dados.status){
								case 'Ok':
									var disponibilidade = ajax_vars.b2make_disponibilidade;
									var dados = disponibilidade[id];
									var total = 0;
									
									for(var id_servico in disponibilidade) { 
										var preco = parseFloat(disponibilidade[id_servico].preco);
										var quantidade = parseInt($('.b2make-store-cart-row[data-id="'+id_servico+'"]').find('.b2make-scc-3').find('.b2make-store-cart-quantity-change-mask').find('.b2make-store-cart-quantity').val());
										var sub_total = preco*quantidade;
										
										$('.b2make-store-cart-row[data-id="'+id_servico+'"]').find('.b2make-scc-4').html('R$ '+formatMoney(sub_total,2,',','.'));
										
										total = total + sub_total;
									}
									
									if(total > 0){
										$('#b2make-store-cart-checkout').show();
									} else {
										$('#b2make-store-cart-checkout').hide();
									}
									
									$('.b2make-scsc-2').html('R$ '+formatMoney(total,2,',','.'));
									
									$('#ajax_lendo').fadeOut(tempo_animacao);
									
									var quant_int = parseInt(quant);
									var evento;
									var quant_changed;
									var item_dados = {
										nome : ajax_vars.b2make_carrinho[id].nome,
										preco : ajax_vars.b2make_carrinho[id].preco
									};
									
									if(quant_int > ajax_vars.b2make_carrinho[id].quantidade){
										quant_changed = quant_int - ajax_vars.b2make_carrinho[id].quantidade;
										evento = 'add_to_cart';
									} else {
										quant_changed = ajax_vars.b2make_carrinho[id].quantidade - quant_int;
										evento = 'remove_from_cart';
									}
									
									ajax_vars.b2make_carrinho[id].quantidade = quant_int;
									
									window.parent.postMessage({opcao:'google_analytics', evento:evento, item_id:id , item_quant:quant_changed ,item_dados:item_dados}, '*');
								break;
								default:
									$('#ajax_lendo').fadeOut(tempo_animacao);
									
									console.log('ERROR - '+opcao+' - '+dados.status);
							}
						} else {
							$('#ajax_lendo').fadeOut(tempo_animacao);
							console.log('ERROR - '+opcao+' - '+txt);
						}
					},
					error: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						console.log('ERROR AJAX - '+opcao+' - '+txt);
					}
				});
			});
			
			$('.b2make-store-cart-remove').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var id = $(this).parent().parent().parent().attr('data-id');
				
				store_cart_remove({
					id:id
				});
			});
			
			function store_cart_remove(p={}){
				var id = p.id;
				var opcao = 'e-services/'+ajax_vars.b2make_loja_atual+'/carrinho-excluir';
				var data = { 
					ajax : 'sim',
					opcao : opcao,
					id : id
				};
				
				if((typeof getUrlParameter('_iframe_session') !== typeof undefined && getUrlParameter('_iframe_session') !== false)){
					data._iframe_session = getUrlParameter('_iframe_session');
				}
				
				$.ajax({
					type: 'POST',
					url: '.',
					data: data,
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						if(
							txt.charAt(0) == "{" || 
							txt.charAt(0) == "["
						){
							var dados = eval('(' + txt + ')');
							
							switch(dados.status){
								case 'Ok':
									$('.b2make-store-cart-row[data-id="'+id+'"]').remove();
									
									if($('.b2make-store-cart-row').length > 0){
										$('#b2make-store-cart-without-services').hide();
										$('#b2make-store-cart-checkout').show();
									} else {
										$('#b2make-store-cart-without-services').show();
										$('#b2make-store-cart-checkout').hide();
									}
									
									var disponibilidade = ajax_vars.b2make_disponibilidade;
									var dados = disponibilidade[id];
									var total = 0;
									var disponibilidade_novo = {};
									
									for(var id_servico in disponibilidade) {
										if(id_servico != id){
											var preco = parseFloat(disponibilidade[id_servico].preco);
											var quantidade = parseInt($('.b2make-store-cart-row[data-id="'+id_servico+'"]').find('.b2make-scc-3').find('.b2make-store-cart-quantity-change-mask').find('.b2make-store-cart-quantity').val());
											var sub_total = preco*quantidade;
											
											$('.b2make-store-cart-row[data-id="'+id_servico+'"]').find('.b2make-scc-4').html('R$ '+formatMoney(sub_total,2,',','.'));
											
											total = total + sub_total;
											
											disponibilidade_novo[id_servico] = {
												quantidade : disponibilidade[id_servico].quantidade,
												preco : disponibilidade[id_servico].preco
											};
										}
									}
									
									ajax_vars.b2make_disponibilidade = disponibilidade_novo;
									
									$('.b2make-scsc-2').html('R$ '+formatMoney(total,2,',','.'));
									
									$('#ajax_lendo').fadeOut(tempo_animacao);
								break;
								default:
									$('#ajax_lendo').fadeOut(tempo_animacao);
									
									console.log('ERROR - '+opcao+' - '+dados.status);
							}
						} else {
							$('#ajax_lendo').fadeOut(tempo_animacao);
							console.log('ERROR - '+opcao+' - '+txt);
						}
					},
					error: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						console.log('ERROR AJAX - '+opcao+' - '+txt);
					}
				});
			}
			
			$('#b2make-store-cart-checkout').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var data = '';
				var value = 0;
				var itens = new Array();
				var evento = 'begin_checkout';
				
				$('.b2make-store-cart-row').each(function(){
					var id_servico = $(this).attr('data-id');
					var quant = $(this).find('.b2make-scc-3').find('.b2make-store-cart-quantity-change-mask').find('.b2make-store-cart-quantity').val();
					
					data = data + (data ? '_':'') + id_servico + '-' + quant;
					
					itens.push({
						"id": id_servico,
						"name": ajax_vars.b2make_carrinho[id_servico].nome,
						"price": ajax_vars.b2make_carrinho[id_servico].preco,
						"quantity": quant,
					});
					
					value = value + ((parseFloat(ajax_vars.b2make_carrinho[id_servico].preco)) * (parseInt(quant)));
				});
				
				window.parent.postMessage({opcao:'google_analytics', evento:evento, itens:itens, value:value}, '*');
				
				window.open(variaveis_js.site_raiz+'e-services/'+ajax_vars.b2make_loja_atual+'/pre-checkout/'+data+((typeof getUrlParameter('_iframe_session') !== typeof undefined && getUrlParameter('_iframe_session') !== false) ?'/?_iframe_session='+getUrlParameter('_iframe_session'):''),'_self');
			});
		}
		
		carrinho();
	}
	
	// ================================= Cadastro ===========================
	
	if($('#b2make-signup-2-cont').length > 0){
		var cart_cor_1 = ajax_vars.b2make_cart_cor_1;
		var cart_cor_2 = ajax_vars.b2make_cart_cor_2;
		
		$.b2make_check_box_load();
		
		$(".cnpj").mask("00.000.000/0000-00", {clearIfNotMatch: true});
		$(".cpf").mask("000.000.000-00", {clearIfNotMatch: true});
		
		var MaskBehavior = function (val) {
			return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
		},
		MaskOptions = {
			clearIfNotMatch: true,
			onKeyPress: function(val, e, field, options) {
				field.mask(MaskBehavior.apply({}, arguments), options);
			}
		};
		$('.telefone').mask(MaskBehavior, MaskOptions);
		
		$('#cpf').show();
		$('#cnpj').hide();
		
		$('#cpf-cnpj').find('div').addClass('b2make-noselect');
		
		function start_cpf_cnpj(){
			$('#cpf-cnpj').find('div').css('lineHeight','35px');
			$('#cpf-cnpj').find('div').css('padding','0px 15px 0px 15px');
			$('#cpf-cnpj').find('div').css('fontSize','16px');
			
			$('#cpf-cnpj').find('div').css('background-color',cart_cor_2);
			$('#cpf-cnpj').find('div[data-checked="checked"]').css('background-color',cart_cor_1);
			
			$('#cpf-cnpj').find('div').css('border-color',cart_cor_1);
			$('#cpf-cnpj').find('div').css('border-style','solid');
			$('#cpf-cnpj').find('div').css('border-width','1px');
			
			$('#cpf-cnpj').find('div').css('color',cart_cor_1);
			$('#cpf-cnpj').find('div[data-checked="checked"]').css('color',cart_cor_2);
		}
		
		start_cpf_cnpj();
		
		$('#cpf-cnpj').find('div').hover(
			function(){
				if(!$(this).attr('data-checked')){
					$(this).css('color',cart_cor_2);
					$(this).css('background-color',cart_cor_1);
				}
			},
			function(){
				if(!$(this).attr('data-checked')){
					$(this).css('color',cart_cor_1);
					$(this).css('background-color',cart_cor_2);
				}
			}
		);
		
		$('#cpf-cnpj').on('b2make-check-box-clicked',function(){
			var val = $('#cpf-cnpj-check').val();
			
			if(val == 'CPF'){
				$('#cpf').show();
				$('#cnpj').hide();
			} else {
				$('#cpf').hide();
				$('#cnpj').show();
			}
			
			start_cpf_cnpj();
		});
		
		$(document.body).on('mouseup tap','#b2make-signup-login-link',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('#b2make-signup-login-cont').toggle();
		});
		
		$('.b2make-check-box-item').each(function(){
			var campo = $(this).attr('data-request-field');
			var checked = $(this).attr('data-checked');

			$(this).html('<div class="b2make-check-box-item-txt">'+$(this).html()+'</div>');
			$(this).prepend('<div class="b2make-check-box-item-obj"></div>');
			
			var input = $('<input type="hidden" value="'+(checked ? '1':'')+'" name="'+campo+'" id="'+campo+'">');
			$(this).after(input);
		});
		
		$(document.body).on('mouseup tap','.b2make-check-box-item',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var lembre_me = JSON.parse(localStorage.getItem('b2make-store-remember-me')); if(!lembre_me) lembre_me = {};
			
			var checked = $(this).attr('data-checked');
			var campo = $(this).attr('data-request-field');
			
			if(checked){
				$(this).removeAttr('data-checked');
				$(this).find('.b2make-check-box-item-obj').css('background-color','#FFF');
				$('#'+campo).val('');
			} else {
				$(this).attr('data-checked',true);
				$(this).find('.b2make-check-box-item-obj').css('background-color',cart_cor_1);
				$('#'+campo).val('1');
			}
		});
		
		// ============= Login =============
		
		if(ajax_vars.bad_login){
			$('#b2make-signup-login-cont').toggle();
		}
		
		var remember_me = JSON.parse(localStorage.getItem('b2make-signup-remember-me'));
		
		if(remember_me)
		if(remember_me.ativo == true){
			$('#b2make-signup-login-lembre-me').attr('data-checked',true);
			$('#b2make-signup-login-lembre-me').find('.b2make-check-box-item-obj').css('background-color',cart_cor_1);
			$('#'+$('#b2make-signup-login-lembre-me').attr('data-request-field')).val('1');
			
			$("#b2make-signup-login-form #login-email").val(remember_me.email);
			$("#b2make-signup-login-form #login-senha").val(remember_me.senha);
		}
		
		$('#b2make-signup-login-btn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			signup_signin();
		});
		
		$(document.body).on('keyup','#b2make-signup-login-form #login-senha',function(e){
			if(e.keyCode == 13){
				signup_signin();
			}
		});
		
		function signup_signin(){
			var enviar = true;
			var campo;
			var mens;
			var cache = {};
			
			campo = "b2make-signup-login-form #login-email"; mens = "Preencha o email"; if(!$("#"+campo).val() || $("#"+campo).val() == $("#"+campo).prop('defaultValue')){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); cache.email = $("#"+campo).val(); }
			campo = "b2make-signup-login-form #login-senha"; mens = "Preencha a senha"; if(!$("#"+campo).val() || $("#"+campo).val() == $("#"+campo).prop('defaultValue')){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); cache.senha = $("#"+campo).val(); }
			
			if(enviar){
				if($('#b2make-signup-login-lembre-me').attr('data-checked')){
					cache.ativo = true;
				} else {
					cache.ativo = false;
					cache.email = false;
					cache.senha = false;
				}
				
				var checkout_options = {
					"checkout_step": 1,
					"checkout_option": "signup",
					"value": "signin"
				};
				
				window.parent.postMessage({opcao:'google_analytics', evento:'set_checkout_option', checkout_options:checkout_options}, '*');
				
				localStorage.setItem('b2make-signup-remember-me',JSON.stringify(cache));
				
				window.enviar_form_simples('b2make-signup-login-form');
			}
			
			return false;
		}
		
		// =============== Cadastro
		
		var email_validado = false;
		var cadastrar_clicked = false;
		
		$.google_recaptcha_load();
		
		$('#email').on('blur',function(e){
			validar_email();
		});
		
		$('.form-field').on('keydown',function(e){
			$(this).removeClass('input-vazio');
		});
		
		$('#b2make-signup-2-cadastrar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			cadastrar();
		});
		
		function validar_email(){
			var sel = '#email';
			
			$(sel).removeClass('input-vazio');
			if(!$(sel).val()){
				mens = 'Preencha o E-mail';$.alerta_open(mens,false,false); $(sel).addClass('input-vazio');
			} else if(!checkMail($(sel).val())){
				mens = 'E-mail inv&aacute;lido!';$.alerta_open(mens,false,false); $(sel).addClass('input-vazio');
			} else {
				email_validado = false;
				
				$.ajax({
					type: 'POST',
					url: '.',
					data: { ajax : 'sim' , opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/email-verificar' , email_usuario : $(sel).val() },
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						var dados = eval('(' + txt + ')');
						
						var mens = "";
						
						if(dados.status == 'EmUso'){
							mens = 'Este email j&aacute; est&aacute; em uso! Escolha outro.';$.alerta_open(mens,false,false); $("#email").addClass('input-vazio');
						} else {
							email_validado = true;$("#email").removeClass('input-vazio');
							
							if(cadastrar_clicked){
								cadastrar();
							}
						}
					},
					error: function(txt){
						
					}
				});
			}
		}
		
		function cadastrar(){
			var enviar = true;
			var campo;
			var post;
			var mens;
			var campos = Array();
			var posts = Array();
			var opcao = '';
			var href = '';
			var limpar_campos = true;
			var mudar_pagina = false;
			var form_id = 'b2make-signup-cadastro-form';
			
			cadastrar_clicked = true;
			
			campo = "b2make-signup-cadastro-fields-2 #primeiro-nome"; mens = "Preencha o Primeiro Nome"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "b2make-signup-cadastro-fields-2 #ultimo-nome"; mens = "Preencha o &Uacute;ltimo Nome"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "b2make-signup-cadastro-fields-2 #email"; mens = "Preencha o E-mail"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "b2make-signup-cadastro-fields-2 #senha"; mens = "Preencha a Senha"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "b2make-signup-cadastro-fields-2 #senha2"; mens = "Preencha o Confirme sua Senha"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "b2make-signup-cadastro-fields-2 #telefone"; mens = "Preencha o Telefone"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			
			if($("#cpf-cnpj-check").val() == 'CPF'){
				campo = "b2make-signup-cadastro-fields-2 #cpf"; mens = "Preencha o CPF"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
				campo = "b2make-signup-cadastro-fields-2 #cpf"; mens = "CPF informado n&atilde;o &eacute; v&aacute;lido. Favor informar um CPF v&aacute;lido!"; if(!validarCPF($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			} else {
				campo = "b2make-signup-cadastro-fields-2 #cnpj"; mens = "Preencha o CNPJ"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			}
			
			if($("#senha").val() && $("#senha2").val()){
				if($("#senha").val() != $("#senha2").val()){
					mens = "O campo Senha e o campo Confirme sua Senha s&atilde;o diferentes. Preencha o mesmo valor para ambos os campos."; $.alerta_open(mens,false,false); $("#senha").addClass('input-vazio'); $("#senha2").addClass('input-vazio'); enviar = false;
				} else {
					$("#senha").removeClass('input-vazio'); $("#senha2").removeClass('input-vazio');
				}
			}
			
			if(!email_validado){
				enviar = false;
				validar_email();
			}
			
			if(!enviar){
				return false;
			} else {
				if(ajax_vars.recaptcha_enable){
					if($.google_recaptcha_is_answered()){
						$.google_recaptcha_reset();
						
						var checkout_options = {
							"checkout_step": 1,
							"checkout_option": "signup",
							"value": "signup"
						};
						
						window.parent.postMessage({opcao:'google_analytics', evento:'set_checkout_option', checkout_options:checkout_options}, '*');
						
						window.form_serialize = $('#'+form_id).serialize();
						window.enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
						$('#'+form_id)[0].reset();
					} else {
						$.alerta_open("<p>&Eacute; necess&aacute;rio validar o reCAPTCHA para comprovar que voc&ecirc; n&atilde;o &eacute; um robo!</p>",false,false);
					}
				} else {
					$('#ajax_lendo').fadeIn(tempo_animacao);
					
					var checkout_options = {
						"checkout_step": 1,
						"checkout_option": "signup",
						"value": "signup"
					};
					
					window.parent.postMessage({opcao:'google_analytics', evento:'set_checkout_option', checkout_options:checkout_options}, '*');
					
					window.form_serialize = $('#'+form_id).serialize();
					window.enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
					$('#'+form_id)[0].reset();
				}
			}
		}
	}
	
	// ================================= Emissão ===========================
	
	if($('#b2make-emission-cont').length > 0){
		b2make.emission = {};
		
		function emission(){
			var MaskBehavior = function (val) {
				return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
			},
			MaskOptions = {
				clearIfNotMatch: true,
				onKeyPress: function(val, e, field, options) {
					field.mask(MaskBehavior.apply({}, arguments), options);
				}
			};
			$('.telefone').mask(MaskBehavior, MaskOptions);
			
			$('#b2make-emission-cadastrar').on('mouseup tap', function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var enviar = true;
				
				$('.b2make-store-emission-cel').each(function(){
					var codigo = $(this).attr('data-id');
					var pai = $(this);
					
					if(
						!pai.find('#servico_'+codigo+'_nome_id').val() ||
						!pai.find('#servico_'+codigo+'_doc_id').val() ||
						!pai.find('#servico_'+codigo+'_tel_id').val()
					){
						enviar = false;
						$.alerta_open('&Eacute; necess&aacute;rio identificar o nome, documento e telefone de cada servi&ccedil;o para continuar. Para isso basta preencher todos os campos e clicar em PAGAR.',false,false);
						return true;
					}
				});
				
				if(enviar){
					cms.nao_parar_lendo = true;
					window.enviar_form_simples('b2make-emission-form');
				}
			});
			
			$('.b2make-store-emission-radio-cont label').on('mouseup tap', function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				e.stopPropagation();
			});
			
			$('.b2make-store-emission-radio-cont input').on('mouseup tap change', function(e){
				var codigo = $(this).parent().parent().attr('data-id');
				var opcao = $('input[name='+$(this).attr('name')+']:checked').val();
				var nome_cont = $('#servico_'+codigo+'_nome_id');
				var documento_cont = $('#servico_'+codigo+'_doc_id');
				var telefone_cont = $('#servico_'+codigo+'_tel_id');
				
				switch(opcao){
					case 'para_voce':
						nome_cont.val(ajax_vars.usuario_nome_completo);
						documento_cont.val(ajax_vars.usuario_documento);
						telefone_cont.val(ajax_vars.usuario_telefone);
					break;
					case 'para_terceiro':
						nome_cont.val('');
						documento_cont.val('');
						telefone_cont.val('');
					break;
				}
				
				e.stopPropagation();
			});
			
			$('.b2make-store-emission-cel').each(function(){
				var codigo = $(this).attr('data-id');
				var pai = $(this);
				
				pai.find('#servico_'+codigo+'_nome_id').val(ajax_vars.usuario_nome_completo);
				pai.find('#servico_'+codigo+'_doc_id').val(ajax_vars.usuario_documento);
				pai.find('#servico_'+codigo+'_tel_id').val(ajax_vars.usuario_telefone);
				
			});
		}
		
		emission();
	}
	
	// ================================= User Update ===========================
	
	if($('#b2make-user-update-cont').length > 0){
		$.b2make_check_box_load();
		
		$('#b2make-user-update-cnpj').hide();
		
		$(".cnpj").mask("00.000.000/0000-00", {clearIfNotMatch: true});
		$(".cpf").mask("000.000.000-00", {clearIfNotMatch: true});
		
		var MaskBehavior = function (val) {
			return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
		},
		MaskOptions = {
			clearIfNotMatch: true,
			onKeyPress: function(val, e, field, options) {
				field.mask(MaskBehavior.apply({}, arguments), options);
			}
		};
		$('.telefone').mask(MaskBehavior, MaskOptions);
		
		cpf_cnpj_check();
		$('#b2make-user-update-cpf-cnpj').on('b2make-check-box-clicked',cpf_cnpj_check);
		
		$('#b2make-user-update-btn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var enviar = true;
			var campo;
			var mens;
			
			campo = "b2make-user-update-nome"; mens = "Preencha o Nome"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "b2make-user-update-ultimo-nome"; mens = "Preencha o &Uacute;ltimo Nome"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "b2make-user-update-telefone"; mens = "Preencha o Telefone"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			
			if($('#cnpj_selecionado').val() == 'nao'){
				campo = "b2make-user-update-cpf"; mens = "Preencha o CPF"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
				campo = "b2make-user-update-cpf"; mens = "CPF informado n&atilde;o &eacute; v&aacute;lido. Favor informar um CPF v&aacute;lido!"; if(!validarCPF($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			} else {
				campo = "b2make-user-update-cnpj"; mens = "Preencha o CNPJ"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			}
			
			if(enviar){
				window.enviar_form_simples('b2make-user-update-form');
			}
			
			return false;
		});
		
		function cpf_cnpj_check(){
			var val = $('#cnpj_selecionado').val();
			
			if(val == 'nao'){
				$('#b2make-user-update-cpf').show();
				$('#b2make-user-update-cnpj').hide();
			} else {
				$('#b2make-user-update-cpf').hide();
				$('#b2make-user-update-cnpj').show();
			}
		}
	}
	
	// ================================= Pay Plus ===========================
	
	if($('.b2make-payment-cont').length > 0){
		b2make.ppplus = {};
		
		payment_formas_cont();
		
		$('#b2make-payment-formas-menu li').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var url = '';
			
			switch($(this).attr('data-id')){
				case 'outro-pagador':	url = '/other-payer'; break;
				case 'paypal': url = '/paypal'; break;
			}
			
			$('#ajax_lendo').fadeIn(tempo_animacao);
			
			if(ajax_vars.b2make_loja_iframe){
				window.parent.postMessage({opcao:'redirect',url:ajax_vars.b2make_loja_url_base + 'payment'+url}, '*');
			} else {
				window.open(variaveis_js.site_raiz+'e-services/'+ajax_vars.b2make_loja_atual+'/payment'+url,'_self');
			}
		});
		
		function payment_formas_cont(){
			var selecionado_id = $('#b2make-payment-formas-menu li[data-selecionado="sim"]').attr('data-id');
			
			switch(selecionado_id){
				case 'paypal':
					$('#b2make-payment-paypal').show();
				break;
			}
		}
		
		if($('#paypal-cont').length > 0){
			function paypal_start(p = {}){
				var CREATE_PAYMENT_URL  = document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'e-services/'+ajax_vars.b2make_loja_atual+'/paypal-button-create-pay';
				var EXECUTE_PAYMENT_URL  = document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'e-services/'+ajax_vars.b2make_loja_atual+'/paypal-button-execute-pay';
				
				paypal.Button.render({
					env: (ajax_vars.ppp_ativo == 'sim' ? "production" : "sandbox"), // Or 'sandbox',

					commit: true, // Show a 'Pay Now' button
					locale: 'pt_BR',
					
					style: {
						label: 'pay',
						fundingicons: true,
						shape: 'rect',
						color: 'blue',
						size:'responsive'
					},

					payment: function(data, actions) {
						/*
						* Set up the payment here
						*/
						
						return paypal.request.post(CREATE_PAYMENT_URL).then(function(data) {
							return data.id;
						});
					},

					onAuthorize: function(data, actions) {
						/*
						* Execute the payment here
						*/
						
						b2make.ppplus.ppp_id = data.paymentID;
						
						ppplus_continue({
							payerID : data.payerID,
							paypalButton : true,
							rememberedCard : ' ',
							installmentsValue : ' ',
							outroPagador : 'sim'
						});
					},

					onCancel: function(data, actions) {
						/*
						* Buyer cancelled the payment
						*/
					},

					onError: function(err) {
						/*
						* An error occurred during the transaction
						*/
					}
				}, '#b2make-payment-paypal');
				
				var checkout_options = {
					"checkout_step": 2,
					"checkout_option": "payment",
					"value": "PayPal"
				};
				
				window.parent.postMessage({opcao:'google_analytics', evento:'set_checkout_option', checkout_options:checkout_options}, '*');
			}
			
			function paypal_load(p = {}){
				if(!b2make.ppplus.paypal_loaded){
					$.getScript('https://www.paypalobjects.com/api/checkout.js',function() {
						b2make.ppplus.paypal_loaded = true;
						paypal_start(p);
					});
				} else {
					paypal_start(p);
				}
			}
			
			paypal_load();
		}
		
		if($('#paypal-plus-outro-comprador-cont').length > 0){
			$.b2make_check_box_load();
			
			$('#paypal-plus-cf-cnpj').hide();
			
			$(".cnpj").mask("00.000.000/0000-00", {clearIfNotMatch: true});
			$(".cpf").mask("000.000.000-00", {clearIfNotMatch: true});
			
			var MaskBehavior = function (val) {
				return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
			},
			MaskOptions = {
				clearIfNotMatch: true,
				onKeyPress: function(val, e, field, options) {
					field.mask(MaskBehavior.apply({}, arguments), options);
				}
			};
			$('.telefone').mask(MaskBehavior, MaskOptions);
			
			$('#paypal-plus-cf-cpf-cnpj').on('b2make-check-box-clicked',function(){
				var val = $('#paypal-plus-cf-cpf-cnpj-check').val();
				
				if(val == 'CPF'){
					$('#paypal-plus-cf-cpf').show();
					$('#paypal-plus-cf-cnpj').hide();
				} else {
					$('#paypal-plus-cf-cpf').hide();
					$('#paypal-plus-cf-cnpj').show();
				}
			});
			
			$('#paypal-plus-cf-btn').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var enviar = true;
				var campo;
				var mens;
				
				campo = "paypal-plus-cf-first-name"; mens = "Preencha o Nome"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
				campo = "paypal-plus-cf-last-name"; mens = "Preencha o &Uacute;ltimo Nome"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
				campo = "paypal-plus-cf-email"; mens = "Preencha o Email"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
				campo = "paypal-plus-cf-telefone"; mens = "Preencha o Telefone"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
				
				campo = "paypal-plus-cf-email"; mens = "E-mail inválido, preencha o campo de e-mail válido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
	
				if($('#paypal-plus-cf-cpf-cnpj-check').val() == 'CPF'){
					campo = "paypal-plus-cf-cpf"; mens = "Preencha o CPF"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
				} else {
					campo = "paypal-plus-cf-cnpj"; mens = "Preencha o CNPJ"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
				}
				
				if(enviar){
					var opcao = 'e-services/'+ajax_vars.b2make_loja_atual+'/ppplus-other-buyer';
					var id = 'id';
					
					var data = { 
						ajax : 'sim',
						opcao : opcao,
						nome : $('#paypal-plus-cf-first-name').val(),
						ultimo_nome : $('#paypal-plus-cf-last-name').val(),
						email : $('#paypal-plus-cf-email').val(),
						telefone : $('#paypal-plus-cf-telefone').val(),
						cpf_cnpj_check : $('#paypal-plus-cf-cpf-cnpj-check').val(),
						cpf : $('#paypal-plus-cf-cpf').val(),
						cnpj : $('#paypal-plus-cf-cnpj').val()
					};
					
					if((typeof getUrlParameter('_iframe_session') !== typeof undefined && getUrlParameter('_iframe_session') !== false)){
						data._iframe_session = getUrlParameter('_iframe_session');
					}
					
					$.ajax({
						type: 'POST',
						url: '.',
						data: data,
						beforeSend: function(){
							$('#ajax_lendo').fadeIn(tempo_animacao);
						},
						success: function(txt){
							if(
								txt.charAt(0) == "{" || 
								txt.charAt(0) == "["
							){
								var dados = eval('(' + txt + ')');
								
								switch(dados.status){
									case 'Ok':
										b2make.ppplus.ppp_id = dados.ppp_id;
										
										$('#b2make-payment-outro-pagador-finalizar').on('mouseup tap',function(e){
											if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
											
											ppp2.doContinue();
											return false;
										});
										
										ppplus_load({
											outro_pagador : true,
											dados : dados
										});
									break;
									default:
										$('#ajax_lendo').fadeOut(tempo_animacao);
										
										if(dados.msg){
											$.alerta_open(dados.msg,false,false);
										}
										
										console.log('ERROR - '+opcao+' - '+dados.status);
									
								}
							} else {
								$('#ajax_lendo').fadeOut(tempo_animacao);
								console.log('ERROR - '+opcao+' - '+txt);
							}
						},
						error: function(txt){
							$('#ajax_lendo').fadeOut(tempo_animacao);
							console.log('ERROR AJAX - '+opcao+' - '+txt);
						}
					});
				}
				
				return false;
			});
			
			var checkout_options = {
				"checkout_step": 2,
				"checkout_option": "payment",
				"value": "Other Payer Form"
			};
			
			window.parent.postMessage({opcao:'google_analytics', evento:'set_checkout_option', checkout_options:checkout_options}, '*');
		}
		
		if($('#paypal-plus-comprador-cont').length > 0){
			$('#b2make-payment-finalizar').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				ppp.doContinue();
				return false;
			});
			
			ppplus_load();
		}
		
		function ppplus_start(p = {}){
			if(p.outro_pagador){
				window.ppp2 = PAYPAL.apps.PPP({
					"approvalUrl": p.dados.ppp_link_approval_url,
					"placeholder": "b2make-ppplusDiv-2",
					"mode": (p.dados.ppp_ativo == 'sim' ? "live" : "sandbox"),
					"payerFirstName": p.dados.ppp_first_name,
					"payerLastName": p.dados.ppp_last_name,
					"payerEmail": p.dados.ppp_email,
					"payerPhone": p.dados.ppp_telefone,
					"payerTaxId": p.dados.ppp_document,
					"payerTaxIdType": p.dados.ppp_document_type,
					"language": "pt_BR",
					"country": "BR",
					"rememberedCards": (p.dados.ppp_remembered_card_hash ? p.dados.ppp_remembered_card_hash : ' '),
					"disableContinue": 'b2make-payment-outro-pagador-finalizar',
					"enableContinue": 'b2make-payment-outro-pagador-finalizar',
					"onLoad": function(){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						$('#b2make-payment-outro-pagador-finalizar').show();
						$('#paypal-plus-comprador-tit').hide();
						$('#paypal-plus-comprador-form').hide();
					},
					"onError": onErrorPPP
				});
				
				var checkout_options = {
					"checkout_step": 2,
					"checkout_option": "payment",
					"value": "Other Payer PayPal Plus"
				};
				
				window.parent.postMessage({opcao:'google_analytics', evento:'set_checkout_option', checkout_options:checkout_options}, '*');
			} else {
				window.ppp = PAYPAL.apps.PPP({
					"approvalUrl": ajax_vars.ppp_link_approval_url,
					"placeholder": "b2make-ppplusDiv",
					"mode": (ajax_vars.ppp_ativo == 'sim' ? "live" : "sandbox"),
					"payerFirstName": ajax_vars.ppp_first_name,
					"payerLastName": ajax_vars.ppp_last_name,
					"payerEmail": ajax_vars.ppp_email,
					"payerPhone": ajax_vars.ppp_telefone,
					"payerTaxId": ajax_vars.ppp_document,
					"payerTaxIdType": ajax_vars.ppp_document_type,
					"language": "pt_BR",
					"country": "BR",
					"rememberedCards": (ajax_vars.ppp_remembered_card_hash ? ajax_vars.ppp_remembered_card_hash : ' '),
					"disableContinue": 'b2make-payment-finalizar',
					"enableContinue": 'b2make-payment-finalizar',
					"onLoad": function(){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						$('#b2make-payment-finalizar').show();
					},
					"onError": onErrorPPP
				});
				
				var checkout_options = {
					"checkout_step": 2,
					"checkout_option": "payment",
					"value": "PayPal Plus"
				};
				
				window.parent.postMessage({opcao:'google_analytics', evento:'set_checkout_option', checkout_options:checkout_options}, '*');
			}
			
			if(window.addEventListener){
				window.addEventListener("message", ppplus_receiveMessage, false);
				console.log("addEventListener successful", "debug");
			} else if (window.attachEvent){
				window.attachEvent("onmessage", ppplus_receiveMessage);
				console.log("attachEvent successful", "debug");
			} else {
				console.log("Could not attach message listener", "debug");
				throw new Error("Can't attach message listener");
			}
			
			function onErrorPPP(txt){
				if(ajax_vars.b2make_loja_iframe){
					var url = ajax_vars.b2make_loja_url_base;
				} else {
					var url = variaveis_js.site_raiz+'e-services/'+ajax_vars.b2make_loja_atual+'/';
				}
				
				if(
					txt[0].match(/payerLastName/) == 'payerLastName' ||
					txt[0].match(/payerFirstName/) == 'payerFirstName' ||
					txt[0].match(/payerEmail/) == 'payerEmail' ||
					txt[0].match(/payerPhone/) == 'payerPhone' ||
					txt[0].match(/payerTaxId/) == 'payerTaxId'
				){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					$.alerta_open('<p>Ocorreu um erro na confer&ecirc;ncia dos seus dados pelo gateway de pagamento: CPF ou CNPJ, nome, &uacute;ltimo nome, telefone n&atilde;o foram aceitos pelo gateway de pagamento. Favor acesse <a href="'+url+'account" target="_parent">Sua Conta</a> , modifique seus dados, e ent&atilde;o acesse <a href="'+url+'purchases" target="_parent">Suas Compras</a> e clique no bot&atilde;o Pagar e tente novamente.</p>',false,false,'b2make-ppplus-redirect');
				} else {
					$('#ajax_lendo').fadeOut(tempo_animacao);
					$.alerta_open('<p>Ocorreu um erro inesperado, favor entrar em contato com o suporte e informar o seguinte: <b>'+txt[0]+'</b>.</p>',false,false,'b2make-ppplus-redirect');
				}
			}
			
			function ppplus_receiveMessage(event){
				try {
					var message = JSON.parse(event.data);
					
					if (typeof message['cause'] !== 'undefined'){ //iFrame error handling
						ppplusError = message['cause'].replace (/['"]+/g,""); //log & attach this error into the order if possible
						// <<Insert Code Here>>
						switch (ppplusError){
							case "INTERNAL_SERVICE_ERROR": //javascript fallthrough
							case "SOCKET_HANG_UP": //javascript fallthrough
							case "socket hang up": //javascript fallthrough
							case "connect ECONNREFUSED": //javascript fallthrough
							case "connect ETIMEDOUT": //javascript fallthrough
							case "UNKNOWN_INTERNAL_ERROR": //javascript fallthrough
							case "fiWalletLifecycle_unknown_error": //javascript fallthrough
							case "Failed to decrypt term info": //javascript fallthrough
							case "RESOURCE_NOT_FOUND": //javascript fallthrough
							case "INTERNAL_SERVER_ERROR":
								//Generic error, inform the customer to try again; generate a new approval_url andreload the iFrame.
								b2make.ppplus.erro = true;
								$.alerta_open('<p>Ocorreu um erro inesperado, por favor tente novamente.</p>',false,false,'b2make-ppplus-redirect');
							break;
							case "RISK_N_DECLINE": //javascript fallthrough
							case "NO_VALID_FUNDING_SOURCE_OR_RISK_REFUSED": //javascript fallthrough
							case "TRY_ANOTHER_CARD": //javascript fallthrough
							case "NO_VALID_FUNDING_INSTRUMENT":
								//Risk denial, inform the customer to try again; generate a new approval_url and reload the iFrame.
								b2make.ppplus.erro = true;
								$.alerta_open('<p>Seu pagamento n&atilde;o foi aprovado. Por favor utilize outro cart&atilde;o, caso o problema persista entre em contato com o PayPal (0800-047-4482).</p>',false,false,'b2make-ppplus-redirect');
							break;
							case "CARD_ATTEMPT_INVALID":
								//03 maximum payment attempts with error, inform the customer to try again; generate a new approval_url and reload the iFrame.
								b2make.ppplus.erro = true;
								$.alerta_open('<p>Ocorreu um erro inesperado, por favor tente novamente.</p>',false,false,'b2make-ppplus-redirect');
							break;
							case "INVALID_OR_EXPIRED_TOKEN":
								//User session is expired, inform the customer to try again; generate a new approval_url and reload the iFrame.
								b2make.ppplus.erro = true;
								$.alerta_open('<p>A sua sess&atilde;o expirou, por favor tente novamente.</p>',false,false,'b2make-ppplus-redirect');
							break;
							case "CHECK_ENTRY":
								//Missing or invalid credit card information, inform your customer to check the inputs.
								$.alerta_open('<p>Por favor revise os dados de Cart&atilde;o de Cr&eacute;dito inseridos.</p>',false,false);
							break;
							default: //unknown error & reload payment flow
								//Generic error, inform the customer to try again; generate a new approval_url and reload the iFrame.
								b2make.ppplus.erro = true;
								$.alerta_open('<p>Ocorreu um erro inesperado, por favor tente novamente.</p>',false,false,'b2make-ppplus-redirect');
						}
					}
					
					if(message['action'] == 'checkout') { //PPPlus session approved, do logic here
						var rememberedCard = null;
						var payerID = null;
						var installmentsValue= null;
						
						rememberedCard = message['result']['rememberedCards']; //save on user BD record
						payerID = message['result']['payer']['payer_info']['payer_id']; //use it on executePayment API
						
						if("term" in message){
							installmentsValue = message['result']['term']['term']; //installments value
						} else {
							installmentsValue=1; //no installments
						}
						
						/* Next steps:
						1) Save the rememberedCard value on the user record on your Database.
						2) Save the installmentsValue value into the order (Optional).
						3) Call executePayment API using payerID value to capture the payment.
						*/
						
						ppplus_continue({
							payerID : payerID,
							rememberedCard : rememberedCard,
							installmentsValue : installmentsValue,
							outroPagador : (p.outro_pagador ? 'sim' : '')
						});
					}
				} catch (e){ //treat exceptions here
					//b2make.ppplus.erro = true;
					//$.alerta_open('<p>Ocorreu um erro inesperado, por favor tente novamente.</p>',false,false);
					
					console.log('PayPal Plus exception: '+e.message);
				}
			}
		}
		
		function ppplus_redirect(obj = {}){
			if(b2make.ppplus.erro){
				if(ajax_vars.b2make_loja_iframe){
					window.parent.postMessage({opcao:'redirect',url:ajax_vars.b2make_loja_url_base + 'payment'}, '*');
				} else {
					window.open(variaveis_js.site_raiz+'e-services/'+ajax_vars.b2make_loja_atual+'/payment','_self');
				}
			}
			
			if(b2make.ppplus.erro2 || b2make.ppplus.orders){
				if(ajax_vars.b2make_loja_iframe){
					window.parent.postMessage({opcao:'redirect',url:ajax_vars.b2make_loja_url_base + 'purchases'}, '*');
				} else {
					window.open(variaveis_js.site_raiz+'e-services/'+ajax_vars.b2make_loja_atual+'/purchases','_self');
				}
			}
			
			if(b2make.ppplus.voucher){
				if(ajax_vars.b2make_loja_iframe){
					window.parent.postMessage({opcao:'redirect',url:ajax_vars.b2make_loja_url_base + 'purchases'}, '*');
				} else {
					window.open(variaveis_js.site_raiz+'e-services/'+ajax_vars.b2make_loja_atual+'/purchases','_self');
				}
			}
			
			b2make.ppplus.erro = false;
			b2make.ppplus.erro2 = false;
			b2make.ppplus.voucher = false;
			b2make.ppplus.orders = false;
		}
		
		$('#b2make-listener').on('b2make-ppplus-redirect',function(e){
			ppplus_redirect();
		});
		
		function ppplus_continue(obj = {}){
			b2make.ppplus.erro = false;
			
			$('.b2make-payment-cont').hide();
			
			var data = { 
				ajax : 'sim',
				ajax_option : 'e-services',
				opcao : 'ppplus-pay',
				ppp_id : (obj.outroPagador == 'sim' ? b2make.ppplus.ppp_id : ajax_vars.ppp_id),
				paypalButton : (obj.paypalButton ? 'sim' : 'nao'),
				payerID : obj.payerID,
				outroPagador : obj.outroPagador,
				rememberedCard : obj.rememberedCard,
				installmentsValue : obj.installmentsValue
			};
			
			if((typeof getUrlParameter('_iframe_session') !== typeof undefined && getUrlParameter('_iframe_session') !== false)){
				data._iframe_session = getUrlParameter('_iframe_session');
			}
			
			$.ajax({
				type: 'POST',
				url: variaveis_js.site_raiz,
				data: data,
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					
					if(!txt){
						b2make.ppplus.erro = true;
						$.alerta_open('<p>Ocorreu um erro inesperado, por favor tente novamente.</p>',false,false,'b2make-ppplus-redirect');
					} else {
						var dados = eval('(' + txt + ')');
						
						switch(dados.status){
							case 'Ok':
								if(dados.msg){
									if(dados.pending){
										b2make.ppplus.orders = true;
									} else {
										b2make.ppplus.voucher = true;
									}
									
									var purchase_options = {
										transaction_id : dados.transaction_id,
										value : dados.total,
										currency : "BRL",
										items : dados.itens
									};
									
									window.parent.postMessage({opcao:'google_analytics', evento:'purchase', purchase_options:purchase_options}, '*');
									
									$.alerta_open(dados.msg,false,false,'b2make-ppplus-redirect');
								}
							break;
							default:
								if(dados.erro && dados.erro_msg){
									b2make.ppplus.erro2 = true;
									$.alerta_open(dados.erro_msg,false,false,'b2make-ppplus-redirect');
								}
						}
					}
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		}
		
		function ppplus_load(p = {}){
			if(ajax_vars.ppplus_testes){
				$('#ajax_lendo').fadeIn(tempo_animacao);
				
				setTimeout(function(){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					$('#paypal-plus-comprador-testes-img').show();
					$('#b2make-payment-finalizar').show();
				},1000);
				
				return false;
			}
			
			$('#ajax_lendo').fadeIn(tempo_animacao);
			if(!b2make.ppplus.loaded){
				$.getScript('https://www.paypalobjects.com/webstatic/ppplusdcc/ppplusdcc.min.js',function() {
					b2make.ppplus.loaded = true;
					ppplus_start(p);
				});
			} else {
				ppplus_start(p);
			}
		}
		
		
	}

	// ================================= Compras ===========================
	
	if($('#b2make-purchases-cont').length > 0){
		b2make.purchases = {};
		
		$(document.body).off('mouseup tap','.b2make-purchases-voucher-email-btn-enviar');
		$(document.body).on('mouseup tap','.b2make-purchases-voucher-email-btn-enviar',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var pedido_id = $(this).parent().parent().parent().parent().parent().attr('data-id');
			var pedido_servico_id = $(this).parent().parent().attr('data-id');
			var email = $(this).parent().find('input[name="email"]').val();
			
			var cont = $(this).parent();
			
			var data = { 
				ajax : 'sim' , 
				opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/purchase-voucher-send-mail',
				pedido_id:pedido_id,
				pedido_servico_id:pedido_servico_id,
				email:email
			};
			
			if((typeof getUrlParameter('_iframe_session') !== typeof undefined && getUrlParameter('_iframe_session') !== false)){
				data._iframe_session = getUrlParameter('_iframe_session');
			}
			
			$.ajax({
				type: 'POST',
				url: variaveis_js.site_raiz,
				data: data,
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							cont.hide();
							$.alerta_open('E-mail enviado com sucesso!',false,false);
						break;
						default:
							if(dados.erro){
								$.alerta_open(dados.erro,false,false);
							}
					}
					
					$('#ajax_lendo').fadeOut(tempo_animacao);
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$(document.body).on('mouseup tap','.b2make-purchases-voucher-service-mail',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('.b2make-purchases-voucher-identification-cont').hide();
			
			$(this).parent().find('.b2make-purchases-voucher-email-cont').toggle();
		});
		
		$(document.body).on('click touchstart',".b2make-purchases-payment-button",function(){
			var id = $(this).attr('data-id');
			
			var data = { ajax : 'sim' , opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/purchase-pay' , id : id };

			if((typeof getUrlParameter('_iframe_session') !== typeof undefined && getUrlParameter('_iframe_session') !== false)){
				data._iframe_session = getUrlParameter('_iframe_session');
			}
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: data,
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					if(!access_denied(dados))return;
					
					if(ajax_vars.b2make_loja_iframe){
						window.parent.postMessage({opcao:'redirect',url:ajax_vars.b2make_loja_url_base + 'payment'}, '*');
					} else {
						window.open("payment",'_self');
					}
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$(document.body).on('keypress','.b2make-purchases-voucher-presente-cont textarea',function(e){
			if(e.keyCode == 13){
				e.preventDefault();
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-purchases-voucher-service-print',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var pedido_id = $(this).parent().parent().parent().parent().attr('data-id');
			var pedido_servico_id = $(this).parent().attr('data-id');
			
			if(ajax_vars.b2make_loja_iframe){
				var data = { ajax : 'sim' , opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/voucher-print' , pedido_id : pedido_id , pedido_servico_id : pedido_servico_id };
				
				if((typeof getUrlParameter('_iframe_session') !== typeof undefined && getUrlParameter('_iframe_session') !== false)){
					data._iframe_session = getUrlParameter('_iframe_session');
				}
				
				$.ajax({
					type: 'POST',
					url: '.',
					data: data,
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						var dados = eval('(' + txt + ')');
						
						switch(dados.status){
							case 'Ok':
								window.open(ajax_vars.b2make_loja_url_base+"voucher-print","Imprimir","menubar=0,location=no,height=700,width=700");
							break;
							default:
								console.log('b2make-purchases-voucher-service-print: '+txt);
						}
						
						$('#ajax_lendo').fadeOut(tempo_animacao);
					},
					error: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
					}
				});
			} else {
				window.open(variaveis_js.site_raiz+"e-services/"+ajax_vars.b2make_loja_atual+"/voucher-print/"+pedido_id+"/"+pedido_servico_id,"Imprimir","menubar=0,location=no,height=700,width=700");
			}
		});
		
		$(document.body).on('mouseup tap','#b2make-purchases-voucher-service-view-close',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			voucher_view_close();
		});
		
		$(document.body).on('mouseup tap','.b2make-purchases-voucher-service-view',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var pedido_id = $(this).parent().parent().parent().parent().attr('data-id');
			var pedido_servico_id = $(this).parent().attr('data-id');
			
			var data = { ajax : 'sim' , opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/voucher-view',pedido_id:pedido_id,pedido_servico_id:pedido_servico_id };
			
			if((typeof getUrlParameter('_iframe_session') !== typeof undefined && getUrlParameter('_iframe_session') !== false)){
				data._iframe_session = getUrlParameter('_iframe_session');
			}
			
			$.ajax({
				type: 'POST',
				url: variaveis_js.site_raiz,
				data: data,
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							voucher_view_open({
								voucher : dados.voucher,
								titulo : dados.titulo
							});
						break;
					}
					
					$('#ajax_lendo').fadeOut(tempo_animacao);
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$(document.body).on('mouseup tap','.b2make-purchases-payment-button-2',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$(this).parent().find('.b2make-purchases-payment-details').toggle();
		});
		
		$(document.body).on('mouseup tap','.b2make-purchases-voucher-checkbox-cont input,.b2make-purchases-voucher-checkbox-cont label',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$(this).parent().parent().find('.b2make-purchases-voucher-checkbox-cont').find('input').each(function(){
				$(this).prop('checked',false);
			});
			
			var value = '';
			
			if($(this).is('input')){
				value = $(this).val();
				$(this).prop('checked',false);
			} else {
				value = $(this).parent().find('input').val();
				$(this).parent().find('input').prop('checked',false);
			}
			
			var present_cont = $(this).parent().parent().find('.b2make-purchases-voucher-presente-cont');
			var edit_btn = $(this).parent().parent().parent().find('.b2make-purchases-voucher-service-btn-editar');
			var pedido_id = $(this).parent().parent().parent().parent().parent().attr('data-id');
			var pedido_servico_id = $(this).parent().parent().attr('data-id');
			
			switch(value){
				case 'presente':
					if(present_cont.attr('data-edit')){
						edit_btn.show();
					} else {
						present_cont.show();
					}
				break;
				case 'pessoal':
					edit_btn.hide();
					present_cont.hide();
				break;
			}
			
			var data = { ajax : 'sim' , opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/voucher-presente',pedido_id:pedido_id,pedido_servico_id:pedido_servico_id,tipo:value };
			
			if((typeof getUrlParameter('_iframe_session') !== typeof undefined && getUrlParameter('_iframe_session') !== false)){
				data._iframe_session = getUrlParameter('_iframe_session');
			}
			
			$.ajax({
				type: 'POST',
				url: variaveis_js.site_raiz,
				data: data,
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					
					$('#ajax_lendo').fadeOut(tempo_animacao);
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$(document.body).on('mouseup tap','.b2make-purchases-voucher-service-btn-salvar',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$(this).parent().parent().parent().find('.b2make-purchases-voucher-service-btn-editar').show();
			$(this).parent().attr('data-edit','true');
			$(this).parent().hide();
			
			var pedido_id = $(this).parent().parent().parent().parent().parent().attr('data-id');
			var pedido_servico_id = $(this).parent().parent().attr('data-id');
			
			var de = $(this).parent().find('input[name="de"]').val();
			var para = $(this).parent().find('input[name="para"]').val();
			var mensagem = $(this).parent().find('textarea[name="mensagem"]').val();
			var img = $(this).parent().find('.b2make-purchases-voucher-service-img-tema').attr('data-id');
			
			var data = { 
				ajax : 'sim' , 
				opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/voucher-dados-salvar',
				pedido_id:pedido_id,
				pedido_servico_id:pedido_servico_id,
				de:de,
				para:para,
				mensagem:mensagem,
				img:img
			};
			
			if((typeof getUrlParameter('_iframe_session') !== typeof undefined && getUrlParameter('_iframe_session') !== false)){
				data._iframe_session = getUrlParameter('_iframe_session');
			}
			
			$.ajax({
				type: 'POST',
				url: variaveis_js.site_raiz,
				data: data,
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					
					$('#ajax_lendo').fadeOut(tempo_animacao);
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$(document.body).on('mouseup tap','.b2make-purchases-voucher-service-btn-editar',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$(this).hide();
			$(this).parent().find('.b2make-purchases-voucher-escolha-pessoa').find('.b2make-purchases-voucher-presente-cont').removeAttr('data-edit');
			$(this).parent().find('.b2make-purchases-voucher-escolha-pessoa').find('.b2make-purchases-voucher-presente-cont').show();
		});
		
		mais_resultados.opcao = 'e-services/'+ajax_vars.b2make_loja_atual+'/purchases';
		
		if($("#b2make-orders-mais").length){
			mais_resultados.page = 1;
			
			$(document.body).on('mouseup tap',"#b2make-orders-mais",function(){
				var ajax_dados = eval('(' + ajax_vars.mais_resultados + ')');
				
				$.ajax({
					type: 'POST',
					url: variaveis_js.site_raiz,
					data: { ajax : 'sim' , opcao : mais_resultados.opcao, limite : ajax_dados.limite, page : mais_resultados.page },
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						var dados = eval('(' + txt + ')');
						if(!access_denied(dados))return;
						
						$('#ajax_lendo').fadeOut(tempo_animacao);
						$("#b2make-orders-mais").before(dados.pagina+'<div class="clear"></div>');
						
						if(dados.sem_mais){
							$("#b2make-orders-mais").hide();
						} else {
							mais_resultados.page++;
						}
					},
					error: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
					}
				});
			});
		}
		
		mais_resultados.opcao2 = 'e-services/'+ajax_vars.b2make_loja_atual+'/voucher-layouts';
		
		$(document.body).on('mouseup tap','.b2make-purchases-voucher-service-img-tema,.b2make-purchases-voucher-service-btn-tema',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('.b2make-purchases-voucher-service-tema-cont').hide();
			
			var tema_cont = $(this).parent().find('.b2make-purchases-voucher-service-tema-cont');
			var img_holder = $(this).parent().find('.b2make-purchases-voucher-service-img-tema');
			
			if(!b2make.purchases.temas){
				b2make.purchases.temas = $('<div id="b2make-voucher-layouts-temas"></div>');
			}
			
			tema_cont.html('');
			b2make.purchases.temas.html('');
			b2make.purchases.temas.appendTo(tema_cont);
			mais_resultados.page2 = 0;
			
			var data = { ajax : 'sim' , opcao : mais_resultados.opcao2 };
			
			if((typeof getUrlParameter('_iframe_session') !== typeof undefined && getUrlParameter('_iframe_session') !== false)){
				data._iframe_session = getUrlParameter('_iframe_session');
			}
			
			$.ajax({
				type: 'POST',
				url: variaveis_js.site_raiz,
				data: data,
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					if(!access_denied(dados))return;
					
					var img_id = img_holder.attr('data-id');
					
					$('#ajax_lendo').fadeOut(tempo_animacao);
					
					b2make.purchases.temas.append('<div class="b2make-voucher-layouts-img b2make-noselect" data-id="-1">NENHUMA IMAGEM</div>');
					b2make.purchases.temas.append(dados.pagina);
					
					b2make.purchases.temas.find('.b2make-voucher-layouts-img').each(function(){
						var id = $(this).attr('data-id');
						
						if(img_id == id){
							$(this).attr('data-selected','true');
						} else {
							$(this).removeAttr('data-selected');
						}
					});
					
					tema_cont.show();
					
					if($("#b2make-voucher-layouts-mais").length){
						mais_resultados.page2 = 1;
						
						$(document.body).off('mouseup tap',"#b2make-voucher-layouts-mais");
						
						$(document.body).on('mouseup tap',"#b2make-voucher-layouts-mais",function(){
							var ajax_dados = eval('(' + ajax_vars.mais_resultados + ')');
							
							$.ajax({
								type: 'POST',
								url: variaveis_js.site_raiz,
								data: { ajax : 'sim' , opcao : mais_resultados.opcao2, limite : ajax_dados.limite2, page : mais_resultados.page2 },
								beforeSend: function(){
									$('#ajax_lendo').fadeIn(tempo_animacao);
								},
								success: function(txt){
									var dados = eval('(' + txt + ')');
									if(!access_denied(dados))return;
									
									$('#ajax_lendo').fadeOut(tempo_animacao);
									$("#b2make-voucher-layouts-mais").before(dados.pagina+'<div class="clear"></div>');
									
									b2make.purchases.temas.find('.b2make-voucher-layouts-img').each(function(){
										var id = $(this).attr('data-id');
										
										if(img_id == id){
											$(this).attr('data-selected','true');
										} else {
											$(this).removeAttr('data-selected');
										}
									});
									
									if(dados.sem_mais){
										$("#b2make-voucher-layouts-mais").hide();
									} else {
										mais_resultados.page2++;
									}
								},
								error: function(txt){
									$('#ajax_lendo').fadeOut(tempo_animacao);
								}
							});
						});
					}
					
					$(document.body).off('mouseup tap','.b2make-voucher-layouts-img');
					
					$(document.body).on('mouseup tap','.b2make-voucher-layouts-img',function(e){
						if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
						
						var id = $(this).attr('data-id');
						var url = $(this).attr('data-url');
						
						var imagem = $(this).parent().parent().parent().find('.b2make-purchases-voucher-service-img-tema');
						
						imagem.attr('data-id',id);
						
						if(id == '-1'){
							imagem.html('NENHUMA IMAGEM');
							imagem.removeAttr('style');
						} else {
							imagem.html('');
							imagem.css('background-image','url('+url+')');
						}
						
						tema_cont.hide();
					});
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		function voucher_view_open(p = {}){
			if(!b2make.purchases.voucher_cont){
				b2make.purchases.voucher_cont = $('<div id="b2make-purchases-voucher-view-cont"><div id="b2make-purchases-voucher-service-view-tit"></div><div id="b2make-purchases-voucher-service-view-voucher"></div><div id="b2make-purchases-voucher-service-view-close">X</div></div>');
				b2make.purchases.voucher_cont.appendTo('body');
			}
			
			$('#b2make-purchases-voucher-service-view-tit').html(p.titulo);
			$('#b2make-purchases-voucher-service-view-voucher').html(p.voucher);
			
			b2make.purchases.voucher_cont.show();
		}
		
		function voucher_view_close(){
			if(b2make.purchases.voucher_cont){
				b2make.purchases.voucher_cont.hide();
			}
		}
		
		// Identificação
		
		var MaskBehavior = function (val) {
			return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
		},
		MaskOptions = {
			clearIfNotMatch: true,
			onKeyPress: function(val, e, field, options) {
				field.mask(MaskBehavior.apply({}, arguments), options);
			}
		};
		$('.telefone').mask(MaskBehavior, MaskOptions);
		
		$(document.body).on('mouseup tap','.b2make-purchases-voucher-service-identification',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('.b2make-purchases-voucher-email-cont').hide();
			
			$(this).parent().parent().find('.b2make-purchases-voucher-identification-cont').toggle();
		});
		
		$(document.body).off('mouseup tap','.b2make-purchases-voucher-identification-btn-salvar');
		$(document.body).on('mouseup tap','.b2make-purchases-voucher-identification-btn-salvar',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var pedido_id = $(this).parent().parent().parent().parent().attr('data-id');
			var pedido_servico_id = $(this).parent().attr('data-id');
			var identificacao_nome = $(this).parent().find('input[name="identificacao_nome"]').val();
			var identificacao_documento = $(this).parent().find('input[name="identificacao_documento"]').val();
			var identificacao_telefone = $(this).parent().find('input[name="identificacao_telefone"]').val();
			
			var cont = $(this).parent();
			
			var data = { 
				ajax : 'sim' , 
				opcao : 'e-services/'+ajax_vars.b2make_loja_atual+'/purchase-voucher-identification-change',
				pedido_id:pedido_id,
				pedido_servico_id:pedido_servico_id,
				identificacao_nome:identificacao_nome,
				identificacao_documento:identificacao_documento,
				identificacao_telefone:identificacao_telefone
			};
			
			if((typeof getUrlParameter('_iframe_session') !== typeof undefined && getUrlParameter('_iframe_session') !== false)){
				data._iframe_session = getUrlParameter('_iframe_session');
			}
			
			$.ajax({
				type: 'POST',
				url: variaveis_js.site_raiz,
				data: data,
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							cont.hide();
							$.alerta_open('Identifica&ccedil;&atilde;o alterada com sucesso!',false,false);
						break;
						default:
							if(dados.erro){
								$.alerta_open(dados.erro,false,false);
							}
					}
					
					$('#ajax_lendo').fadeOut(tempo_animacao);
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
	}
	
	// ================================= Menu Principal ===========================
	
	$(window).resize(function() {
		eservices_menu_principal();
	});
	
	function eservices_menu_principal(){
		var page_found = false;
		var ww = parseInt($(window).width());
		var ww_corte = 1000;
		
		if(ajax_vars.permissao_loja){
			$('#b2make-eservices-login').hide();
			$('#b2make-eservices-checkout').show();
			$('#b2make-eservices-payment').show();
			$('#b2make-eservices-orders').show();
			$('#b2make-eservices-meus-dados').show();
			$('#b2make-eservices-voucher').show();
			$('#b2make-eservices-logout').show();
			
			if(ajax_vars.b2make_eservices_pedido_atual_pago){
				$('#b2make-eservices-checkout').hide();
				$('#b2make-eservices-payment').hide();
				$('#b2make-eservices-voucher').show();
			} else {
				$('#b2make-eservices-checkout').show();
				$('#b2make-eservices-payment').show();
				$('#b2make-eservices-voucher').hide();
			}
		} else {
			$('#b2make-eservices-login').show();
			$('#b2make-eservices-checkout').hide();
			$('#b2make-eservices-payment').hide();
			$('#b2make-eservices-orders').hide();
			$('#b2make-eservices-meus-dados').hide();
			$('#b2make-eservices-voucher').hide();
			$('#b2make-eservices-logout').hide();
		}
		
		$('#b2make-pagina-menu-principal li a').each(function(){
			var iframe = ajax_vars.b2make_loja_iframe;
			var url_atual = ajax_vars.b2make_loja_url_atual;
			
			if(iframe){
				if($(this).attr('href') == url_atual){
					page_found = true;
				}
			} else {
				if($(this).attr('href') == window.location.href){
					page_found = true;
				}
			}
		});
		
		$('#b2make-pagina-menu-principal-btn').off();
		$('#b2make-pagina-menu-principal-fechar-btn').off();
		$('#b2make-pagina-menu-principal-fechar-btn').hide();
		
		if(ww > ww_corte){
			$('#b2make-pagina-menu-principal-btn').hide();
			
			$('#b2make-pagina-menu-principal').css('position','');
			$('#b2make-pagina-menu-principal').css('top','');
			$('#b2make-pagina-menu-principal').css('left','');
			$('#b2make-pagina-menu-principal').css('width','170px');
			$('#b2make-pagina-menu-principal').css('zIndex','');
			
			$('#b2make-logo-menu-float').css('left','50px');
			$('#b2make-logo-menu-float').css('right','auto');
			$('#b2make-logo-menu-float').css('width','auto');
			
			if(page_found){
				$('#b2make-pagina-menu-principal').show();
				$('#b2make-pagina-menu-principal').height($('#cont_principal').height());
				
				$('#cont_principal').css('width','calc(100% - 240px)');
				$('#cont_principal').css('float','right');
				$('#cont_principal').css('margin','0px 20px 0px 0px');
			} else {
				$('#b2make-pagina-menu-principal').hide();
				
				$('#cont_principal').css('width','calc(100% - 100px)');
				$('#cont_principal').css('float','none');
				$('#cont_principal').css('margin','0px 50px');
			}
			
			$('#b2make-pagina-menu-principal li a').css('fontSize','18px');
			$('#b2make-pagina-menu-principal li a').css('lineHeight','22px');
		} else {
			$('#b2make-pagina-menu-principal-btn').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				$('#b2make-pagina-menu-principal').show();
				$('#b2make-pagina-menu-principal-fechar-btn').show();
			});
			
			$('#b2make-pagina-menu-principal-fechar-btn').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				$('#b2make-pagina-menu-principal').hide();
				$('#b2make-pagina-menu-principal-fechar-btn').hide();
			});
			
			$('#b2make-pagina-menu-principal-btn').show();
			$('#b2make-pagina-menu-principal').hide();
			
			$('#b2make-pagina-menu-principal').css('position','fixed');
			$('#b2make-pagina-menu-principal').css('top','0px');
			$('#b2make-pagina-menu-principal').css('left','0px');
			$('#b2make-pagina-menu-principal').css('width','calc(100% - 30px)');
			$('#b2make-pagina-menu-principal').css('height','100vh');
			$('#b2make-pagina-menu-principal').css('zIndex','101');
			
			$('#cont_principal').css('width','calc(100% - 40px)');
			$('#cont_principal').css('float','none');
			$('#cont_principal').css('margin','0px 20px');
			
			$('#b2make-logo-menu-float').css('left','0px');
			$('#b2make-logo-menu-float').css('right','0px');
			$('#b2make-logo-menu-float').css('margin-left','auto');
			$('#b2make-logo-menu-float').css('margin-right','auto');
			$('#b2make-logo-menu-float').css('width',$('#b2make-logo-menu-float').find('img').width()+'px');
			
			$('#b2make-pagina-menu-principal li a').css('fontSize','28px');
			$('#b2make-pagina-menu-principal li a').css('lineHeight','40px');
		}
		
		if(!ajax_vars.permissao_loja){
			$('#b2make-pagina-menu-principal').hide();
		}
	}
	
	eservices_menu_principal();
	
	// ============ Funções Auxiliares =====================
	
	function validarCPF(cpf) {	
		cpf = cpf.replace(/[^\d]+/g,'');
		if(cpf == '') return false;	
		// Elimina CPFs invalidos conhecidos	
		if (cpf.length != 11 || 
			cpf == "00000000000" || 
			cpf == "11111111111" || 
			cpf == "22222222222" || 
			cpf == "33333333333" || 
			cpf == "44444444444" || 
			cpf == "55555555555" || 
			cpf == "66666666666" || 
			cpf == "77777777777" || 
			cpf == "88888888888" || 
			cpf == "99999999999")
				return false;		
		// Valida 1o digito	
		add = 0;	
		for (i=0; i < 9; i ++)		
			add += parseInt(cpf.charAt(i)) * (10 - i);	
			rev = 11 - (add % 11);	
			if (rev == 10 || rev == 11)		
				rev = 0;	
			if (rev != parseInt(cpf.charAt(9)))		
				return false;		
		// Valida 2o digito	
		add = 0;	
		for (i = 0; i < 10; i ++)		
			add += parseInt(cpf.charAt(i)) * (11 - i);	
		rev = 11 - (add % 11);	
		if (rev == 10 || rev == 11)	
			rev = 0;
		if (rev != parseInt(cpf.charAt(10)))
			return false;
		return true;
	}
	
};

// ======================================= Instalar fun&ccedil;&otilde;es ============================

$.aplicar_scripts_add('aplicar_scripts_eservices');