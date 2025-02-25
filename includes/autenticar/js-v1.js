window.aplicar_scripts_autenticar = function(params){
	if($('#_minha-conta-cont').length){
		$("#_minha-conta-historico").bind('change',function(){
			var id = $(this).val();

			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'minha-conta-historico' , id : id },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					global_vars.link_nao_mudar_scroll = true;
					$.link_trigger('minha-conta');
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$(".cep").mask("99.999-999");
		$(".telefone").mask("(99) 9999-9999");
		
		var cadastrar_usuario = true;
		var cadastrar_senha = true;
		
		$("#minha-conta-usuario").blur(function() {
			if($("#minha-conta-usuario").val()){
				$.ajax({
					type: 'POST',
					url: url_name(),
					data: { ajax : 'sim' , opcao : 'minha-conta-usuario' , usuario : ($("#minha-conta-usuario").val()?$("#minha-conta-usuario").val():'') , edit_usuario : ($("#edit_usuario").val()?$("#edit_usuario").val():'') },
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						var valido = true;
						var mens = "";
						var cor;
						
						if(checkStr($("#minha-conta-usuario").val())){
							valido = false;
							mens = $("#d_caracter_in").html();
							$.alerta_open(mens,false,false);
						}
						
						if(!limites_str($("#minha-conta-usuario").val(),3,20)){
							valido = false;
							mens = $("#d_caracter").html();
							$.alerta_open(mens,false,false);
						}
						
						if(txt == 'sim'){
							valido = false;
							mens = $("#d_usuario").html();
							$.alerta_open(mens,false,false);
						}
						
						$("#mens_usuario").removeClass('ui-state-highlight');
						$("#minha-conta-usuario").removeClass('input-vazio');
						$("#mens_usuario").removeClass('input-vazio');
						
						if(!valido){
							cor = cor1;
							cadastrar_usuario = false;
							$("#minha-conta-usuario").addClass('input-vazio');
							$("#mens_usuario").addClass('input-vazio');
						} else {
							mens = "Usu&aacute;rio OK.";
							cor = cor2;
							cadastrar_usuario = true;
							$("#mens_usuario").addClass('ui-state-highlight');
							$("#minha-conta-senha").focus();
						}
						
						$("#mens_usuario").html(mens);
					},
					error: function(txt){
						
					}
				});
			} else {
				cadastrar_usuario = false;
				$("#minha-conta-usuario").addClass('input-vazio');
				$("#mens_usuario").addClass('input-vazio');
				$("#mens_usuario").html("Preencha o usu&aacute;rio!");
			}
		});
		
		$("#minha-conta-senha").blur(function() {
			validar_senha_novo();
		});
		
		$("#minha-conta-senha2").blur(function() {
			validar_senha_novo();
		});
		
		$("#minha-conta-email").blur(function() {
			if($("#minha-conta-email").val()){
				var mail = $("#minha-conta-email").val();
				var mens;
				var cor;
				
				$("#mens_email").removeClass('ui-state-highlight');
				$("#minha-conta-email").removeClass('input-vazio');
				$("#mens_email").removeClass('input-vazio');
				
				if(!checkMail(mail)){
					mens = "E-mail inv&aacute;lido.";
					$.alerta_open(mens,false,false);
					cor = cor1;
					cadastrar_usuario = false;
					$("#minha-conta-email").addClass('input-vazio');
					$("#mens_email").addClass('input-vazio');
				} else {
					$.ajax({
						type: 'POST',
						url: url_name(),
						data: { ajax : 'sim' , opcao : 'minha-conta-email' , email : ($("#minha-conta-email").val()?$("#minha-conta-email").val():'') , edit_email : ($("#edit_email").val()?$("#edit_email").val():'') },
						beforeSend: function(){
							$('#ajax_lendo').fadeIn(tempo_animacao);
						},
						success: function(txt){
							$('#ajax_lendo').fadeOut(tempo_animacao);
							var valido = true;
							var mens = "";
							var cor;
							
							if(txt == 'sim'){
								valido = false;
								mens = $("#d_email").html();
								$.alerta_open(mens,false,false);
							}
							
							if(!valido){
								cor = cor1;
								cadastrar_usuario = false;
								$("#minha-conta-email").addClass('input-vazio');
								$("#mens_email").addClass('input-vazio');
							} else {
								mens = "E-mail OK.";
								cor = cor2;
								cadastrar_usuario = true;
								$("#mens_email").addClass('ui-state-highlight');
								$("#nome").focus();
							}
							
							$("#mens_email").html(mens);
						},
						error: function(txt){
							
						}
					});
				}
				
				$("#mens_email").html(mens);
				
			} else {
				cadastrar_usuario = false;
				$("#minha-conta-email").addClass('input-vazio');
				$("#mens_email").addClass('input-vazio');
				$("#mens_email").html("Preencha o e-mail!");
			}
		});
		
		$("#minha-conta-senha").keyup(function(eventObject){
			var perc;
			var bpos;
			
			if(eventObject.keyCode != 9){
				if($('#minha-conta-usuario').val()){
					$('#result').html(passwordStrength($('#minha-conta-senha').val(),$('#minha-conta-usuario').val())) ; 
					perc = passwordStrengthPercent($('#minha-conta-senha').val(),$('#minha-conta-usuario').val());
					
					bpos=" $('#colorbar').css( {backgroundPosition: \"0px -" ;
					bpos = bpos + perc + "px";
					bpos = bpos + "\" } );";
					bpos=bpos +" $('#colorbar').css( {width: \"" ;
					bpos = bpos + (perc * 1.9) + "px";
					bpos = bpos + "\" } );";
					eval(bpos);
						$('#percent').html(" " + perc  + "% ");
				} else {
					$.alerta_open("<p>Defina o usu&aacute;rio antes da senha!</p>",false,false);
				}
			}
		});
		
		$("#minha-conta-botao").bind('click touchstart',function(){
			var enviar = true;
			var campo;
			var post;
			var mens;
			var campos = Array();
			var posts = Array();
			var opcao = '';
			var form_id = '_minha-conta-form';
			var href = '';
			var limpar_campos = true;
			var mudar_pagina = true;
			
			campo = "minha-conta-usuario"; mens = "&Eacute; obrigat&oacute;rio definir o Usu&aacute;rio!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			campo = "minha-conta-email"; mens = "&Eacute; obrigat&oacute;rio definir o E-mail!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			if(!$("#minha-conta-senha").val() && !$("#minha-conta-senha2").val()){
				cadastrar_senha = true;
			}
			// Checar email
			campo = "minha-conta-email"; mens = "E-mail inv&aacute;lido, preencha o campo de e-mail v&aacute;lido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			campo = "minha-conta-nome"; mens = "&Eacute; obrigat&oacute;rio definir o Nome!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			if(
				!cadastrar_usuario ||
				!cadastrar_senha
			){ mens = "&Eacute; necess&aacute;rio validar os campos antes de enviar!"; $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			if(!enviar){
				return false;
			} else {
				form_serialize = $('#'+form_id).serialize();
				enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
			}
		});
		
		function validar_senha_novo(){
			var mens;
			var cor;
			
			if($("#minha-conta-senha").val() && $("#minha-conta-senha2").val()){
				var valido = true;
				
				if($("#minha-conta-senha").val() != $("#minha-conta-senha2").val()){
					valido = false;
					mens = "Senha e Redigite a Senha s&atilde;o diferentes.";
					$.alerta_open(mens,false,false);
				}
				
				if(checkStr($("#minha-conta-senha").val())){
					valido = false;
					mens = "Caracteres inv&aacute;lidos, apenas caracteres alfanum&eacute;ricos e ( _ ou @ ou . )";
					$.alerta_open(mens,false,false);
				}
				
				if(!limites_str($("#minha-conta-senha").val(),4,20)){
					valido = false;
					mens = "Senha no m&iacute;nimo 4 e no m&aacute;x 20 caracteres.";
					$.alerta_open(mens,false,false);
				}
				
				$("#mens_senha").removeClass('ui-state-highlight');
				$("#minha-conta-senha2").removeClass('input-vazio');
				$("#minha-conta-senha").removeClass('input-vazio');
				$("#mens_senha").removeClass('input-vazio');
				
				if(!valido){
					cor = cor1;
					cadastrar_senha = false;
					$("#minha-conta-senha").val('');
					$("#minha-conta-senha2").val('');
					$("#minha-conta-senha").addClass('input-vazio');
					$("#minha-conta-senha2").addClass('input-vazio');
					$("#mens_senha").addClass('input-vazio');
				} else {
					mens = "Senha OK.";
					cor = cor2;
					cadastrar_senha = true;
					$("#mens_senha").addClass('ui-state-highlight');
				}
				
				$("#mens_senha").html(mens);
			}
		}
		
	}
	
	if($('#_autenticar-login').length){
		$("#_autenticar-login").bind('submit',function() {
			var enviar = true;
			var campo;
			var mens;
			
			campo = "_autenticar-usuario"; mens = "Preencha o email"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "_autenticar-senha"; mens = "Preencha a senha"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			
			if(enviar){
				window.enviar_form_simples('_autenticar-login');
			}
			
			return false;
		});
	}
	
	if($('#_autenticar-cadastro').length){
		Recaptcha.create(ajax_vars.recaptcha_public_key, 'recaptcha_div', {
			lang : 'pt',
			theme: "clean"
		});
		
		var sep = "";
		var cadastrar_usuario = false;
		var cadastrar_senha = false;
		var edit_email_id = 'edit_email';
		var email_id = 'email';
		var email2_id = 'email2';
		var senha_id = 'senha';
		var senha2_id = 'senha2';
		var nome_id = 'nome';
		var form_id = '_autenticar-cadastro';
		var cor1 = '#F00'; // Vermelho
		var cor2 = '#0C6'; // Verde
		
		$("#"+senha_id).bind('blur',function() {
			validar_senha();
		});
		
		$("#"+senha2_id).bind('blur',function() {
			validar_senha();
		});
		
		$("#"+email_id+",#"+email2_id).bind('blur',function() {
			validar_email();
		});
		
		
		$("#"+email2_id).bind("cut copy paste",function(e) {
			e.preventDefault();
		});
		
		$("#"+senha_id).bind('keyup',function(eventObject){
			var perc;
			var bpos;
			var email = email_id;
			var senha = senha_id;
			
			if(eventObject.keyCode != 9){
				if($('#'+email).val()){
					$('#result').html(passwordStrength($('#'+senha).val(),$('#'+email).val())) ; 
					perc = passwordStrengthPercent($('#'+senha).val(),$('#'+email).val());
					
					bpos=" $('#colorbar').css( {backgroundPosition: \"0px -" ;
					bpos = bpos + perc + "px";
					bpos = bpos + "\" } );";
					bpos=bpos +" $('#colorbar').css( {width: \"" ;
					bpos = bpos + (perc * 1.9) + "px";
					bpos = bpos + "\" } );";
					eval(bpos);
						$('#percent').html(" " + perc  + "% ");
				} else {
					$.alerta_open("<p>Defina o email antes da senha!</p>",false,false);
				}
			}
		});
		
		$("#botao_cadastro_user").bind('click touchstart',function(){
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
			
			campo = email_id; mens = "&Eacute; obrigat&oacute;rio definir o E-mail!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			campo = email2_id; mens = "&Eacute; obrigat&oacute;rio definir o Repita Seu E-mail!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			// Checar email
			campo = email_id; mens = "E-mail inv&aacute;lido, preencha o campo de e-mail v&aacute;lido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			campo = senha_id; mens = "&Eacute; obrigat&oacute;rio definir a Senha!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			campo = senha2_id; mens = "&Eacute; obrigat&oacute;rio definir o Redigite a Senha!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			campo = nome_id; mens = "&Eacute; obrigat&oacute;rio definir o Nome!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
		
			if(
				!cadastrar_usuario ||
				!cadastrar_senha
			){ mens = "&Eacute; necess&aacute;rio validar os campos antes de enviar!"; $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			if(!enviar){
				return false;
			} else {
				$.ajax({
					type: 'POST',
					url: url_name(),
					data: { ajax : 'sim' , recaptcha : 'sim' , recaptcha_challenge_field : Recaptcha.get_challenge() , recaptcha_response_field : Recaptcha.get_response() },
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						
						if(txt == 'sim'){
							window.form_serialize = $('#'+form_id).serialize();
							window.enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
							$('#'+form_id)[0].reset();
							cadastrar_usuario = false;
							cadastrar_senha = false;
						} else {
							Recaptcha.reload();
							mens = "<p>C&oacute;digo de valida&ccedil;&atilde;o <b style=\"color:red;\">inv&aacute;lido</b>!<p></p>Favor preencher o c&oacute;digo de valida&ccedil;&atilde;o novamente!</p>";
							$.alerta_open(mens,false,false);
						}
					},
					error: function(txt){
						
					}
				});
			}
		});
	}
	
	if($('#b2make-signup-cont').length){
		// ==================================== Facebook Pixel =============================
		
		fbq('track', 'InitiateCheckout');
		
		$('#b2make-sce-dominio-proprio-cont').hide();
		
		b2make.cadastro_max_etapas = 3;
		
		if(b2make.selected_plan){
			$('#b2make-signup-cadastro-form #plano').val(b2make.selected_plan);
			if(b2make.selected_plan == '1'){
				$('#b2make-sce-dominio-proprio-cont').hide();
			} else {
				$('#b2make-sce-dominio-proprio-cont').show();
			}
			
			$('.b2make-plans-box').each(function(){
				if(b2make.selected_plan == $(this).attr('data-plan')){
					$('.b2make-plans-box .b2make-plans-box-tit').each(function(){
						if(!$(this).hasClass('b2make-plans-box-disabled')){
							if(!$(this).hasClass('b2make-plans-deselected'))$(this).addClass('b2make-plans-deselected');
							if($(this).hasClass('b2make-plans-selected'))$(this).removeClass('b2make-plans-selected');
						}
					});
					
					$(this).find('.b2make-plans-box-tit').removeClass('b2make-plans-deselected');
					$(this).find('.b2make-plans-box-tit').addClass('b2make-plans-selected');
					
					return;
				}
			});
			
			b2make.selected_plan = false;
			
			b2make.cadastro_etapa_atual = 2;
			$('#b2make-etapas-pontos ul li[data-id="2"]').trigger('mouseup');
		} else {
			b2make.cadastro_etapa_atual = 1;
		}
		
		$('#b2make-signup-continuar').on('mouseup touchstart',function(){
			$('#b2make-etapas-pontos ul li[data-id="'+(b2make.cadastro_etapa_atual+1)+'"]').trigger('mouseup');
		});
		
		$('#b2make-signup-voltar').on('mouseup touchstart',function(){
			$('#b2make-etapas-pontos ul li[data-id="'+(b2make.cadastro_etapa_atual-1)+'"]').trigger('mouseup');
		});
		
		$('#b2make-etapas-pontos ul li').on('mouseup touchstart',function(){
			$('#b2make-etapas-pontos ul li').each(function(){
				$(this).removeClass('b2make-etapa-atual');
			});
			
			$(this).addClass('b2make-etapa-atual');
			
			var id = $(this).attr('data-id');
			
			b2make.cadastro_etapa_atual = parseInt(id);
			
			$('.b2make-etapas-cont').each(function(){
				$(this).hide();
			});
			
			$('.b2make-etapas-cont[data-id="'+id+'"]').show();
			$('#b2make-etapas-descricao').html('Etapa 0'+id+'/0'+b2make.cadastro_max_etapas);
			
			switch(id){
				case '1':
					$('#b2make-etapas-title').html('Plano');
				break;
				case '2':
					$('#b2make-etapas-title').html('Cadastro');
				break;
				case '3':
					$('#b2make-etapas-title').html('Perfil');
				break;
			}
			
			if(b2make.cadastro_max_etapas == b2make.cadastro_etapa_atual){
				$('#b2make-signup-cadastrar').show();
				$('#b2make-signup-continuar').hide();
			} else {
				$('#b2make-signup-cadastrar').hide();
				$('#b2make-signup-continuar').show();
			}
			
			if(b2make.cadastro_etapa_atual == 1){
				$('#b2make-signup-voltar').hide();
			} else {
				$('#b2make-signup-voltar').show();
			}
		});
		
		$('.b2make-plans-box').on('mouseup touchstart',function(){
			if(!$(this).find('.b2make-plans-box-tit').hasClass('b2make-plans-box-disabled')){
				var plan = $(this).attr('data-plan');
				
				$('.b2make-plans-box .b2make-plans-box-tit').each(function(){
					if(!$(this).hasClass('b2make-plans-box-disabled')){
						if(!$(this).hasClass('b2make-plans-deselected'))$(this).addClass('b2make-plans-deselected');
						if($(this).hasClass('b2make-plans-selected'))$(this).removeClass('b2make-plans-selected');
					}
				});
				
				$(this).find('.b2make-plans-box-tit').removeClass('b2make-plans-deselected');
				$(this).find('.b2make-plans-box-tit').addClass('b2make-plans-selected');
				
				$('#b2make-signup-cadastro-form #plano').val(plan);
				if(plan == '1'){
					$('#b2make-sce-dominio-proprio-cont').hide();
				} else {
					$('#b2make-sce-dominio-proprio-cont').show();
				}
				
				$('#b2make-etapas-pontos ul li[data-id="2"]').trigger('mouseup');
			}
		});
		
		if(ajax_vars.recaptcha_enable){
			Recaptcha.create(ajax_vars.recaptcha_public_key, 'recaptcha_div', {
				lang : 'pt',
				theme: "clean"
			});
		} else {
			$('#b2make-label-recaptcha').hide();
		}
		
		$('.b2make-cadastro-validacao-2').on('blur validar',function(){
			var obj = this;
			var obj_after;
			var id = $(obj).attr('id');
			var validado = true;
			var mens = '';
			
			switch(id){
				case 'meu_website':
					obj_after = $('.b2make-help[data-id="cadastro-meu-website"]').get(0);
					if(!$(obj).val() || $(obj).val() == $(obj).prop('defaultValue') ){
						mens = '&Eacute; obrigat&oacute;rio definir o Nome para seu site!';
						validado = false;
					} else {
						if(!b2make.site_host_ok)
							site_host_verificar($(obj).val());
					}
				break;
				case 'dominio_proprio':
					obj_after = $('.b2make-help[data-id="cadastro-dominio-proprio"]').get(0);
					if(!checkDomain($(obj).val())){
						mens = 'O dom&iacute;nio informado n&atilde;o &eacute; v&aacute;lido. Favor preencher um dom&iacute;nio v&aacute;lido!';
						validado = false;
					}
				break;
				
			}
			
			b2make.cadastro_validado2 = validado;
			
			cadastro_validacao_operacoes({
				obj : obj,
				obj_after : obj_after,
				mens : mens,
				id : id,
				validado : validado
			});
		});
		
		$('.b2make-cadastro-validacao,.b2make-cadastro-validacao-2').live('mouseup touchstart focus',function(){
			var id = $(this).attr('id');
			
			if($('#b2make-cadastro-validacao-'+id).length){
				$('#b2make-cadastro-validacao-'+id).remove();
				$(this).removeClass('b2make-cadastro-invalido');
			}
		});
		
		$('.b2make-cadastro-validacao').live('blur validar',function(){
			var obj = this;
			var id = $(obj).attr('id');
			var validado = true;
			var mens = '';
			
			switch(id){
				case 'nome':
					if(!$(obj).val() || $(obj).val() == $(obj).prop('defaultValue') ){
						mens = '&Eacute; obrigat&oacute;rio definir o Nome!';
						validado = false;
					}
				break;
				case 'email':
					b2make.cadastro_email_validado = false;
					if(!$(obj).val() || $(obj).val() == $(obj).prop('defaultValue') ){
						mens = '&Eacute; obrigat&oacute;rio definir o E-mail!';
						validado = false;
					} else if(!checkMail($(obj).val())){
						mens = 'E-mail inv&aacute;lido!';
						validado = false;
					} else {
						$.ajax({
							type: 'POST',
							url: url_name(),
							data: { ajax : 'sim' , email_usuario : $(obj).val() },
							beforeSend: function(){
								$('#ajax_lendo').fadeIn(tempo_animacao);
								b2make.cadastro_email_verificacao = true;
							},
							success: function(txt){
								$('#ajax_lendo').fadeOut(tempo_animacao);
								var valido = true;
								var mens = "";
								var cor;
								
								if(txt == 'sim'){
									validado = false;
									mens = 'Este email j&aacute; est&aacute; em uso! Escolha outro.';
									
									cadastro_validacao_operacoes({
										obj : obj,
										mens : mens,
										id : id,
										validado : validado
									});
								} else {
									b2make.cadastro_email_validado = true;
								}
								
								b2make.cadastro_email_verificacao = false;
							},
							error: function(txt){
								
							}
						});
					}
				break;
				case 'email2':
					if(!$(obj).val() || $(obj).val() == $(obj).prop('defaultValue') ){
						mens = '&Eacute; obrigat&oacute;rio definir o Repita Seu E-mail!';
						validado = false;
					} else if(!checkMail($(obj).val())){
						mens = 'E-mail inv&aacute;lido!';
						validado = false;
					} else if(b2make.cadastro_email_validado){
						if($(obj).val() != $('#email').val()){
							mens = 'E-mail e Repita o E-mail s&atilde;o diferentes!';
							validado = false;
						}
					}
				break;
				case 'senha':
					b2make.cadastro_senha_validado = false;
					if(!$(obj).val() || $(obj).val() == $(obj).prop('defaultValue') ){
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
					}
				break;
				case 'senha2':
					if(!$(obj).val() || $(obj).val() == $(obj).prop('defaultValue') ){
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
						}
					}
				break;
				
			}
			
			b2make.cadastro_validado = validado;
			
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
		
		$('#b2make-signup-cadastrar').on('mouseup touchstart',function(){
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
		
		$('#b2make-signup-cadastro-facebook').on('mouseup touchstart',function(){
			$('#b2make-etapas-pontos ul li[data-id="3"]').trigger('mouseup');
			b2make.cadastro_facebook = true;
		});
		
		$("input#"+senha_id+",input#"+senha2_id+",#"+email2_id+",#"+email_id+",#"+nome_id).on('mouseup touchstart focus',function() {
			b2make.cadastro_facebook = false;
		});
		
		$('#b2make-signup-cadastro-fields input,#meu_website,#dominio_proprio').on('blur',function(){
			if(
				$(this).attr('id') != senha2_id &&
				$(this).attr('id') != email2_id 
			){
				if(!$(this).val()){
					$(this).val($(this).prop('defaultValue'));
				}
			}
		});
		
		$("input#"+senha_id).on('mouseup touchstart focus',function() {
			$(this).remove();
			$('#b2make-signup-cadastro-form #email2').after('<input name="senha" type="password" id="senha" class="b2make-cadastro-validacao">');
			$("input#"+senha_id).focus();
		});
		
		$("input#"+senha2_id).on('mouseup touchstart focus',function() {
			$(this).remove();
			$('#b2make-signup-cadastro-form #pres_senha').after('<input name="senha2" type="password" id="senha2" class="b2make-cadastro-validacao">');
			$("input#"+senha2_id).focus();
		});
		
		/* $("#"+email2_id).bind("cut copy paste",function(e) {
			e.preventDefault();
		}); */
		
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
			
			if(!$('#ajax_lendo2').length){
				$('<div id="ajax_lendo2">Carregando</div>').appendTo('body');
				$('#ajax_lendo2').center();
			}
			
			$('.b2make-cadastro-validacao,.b2make-cadastro-validacao-2').each(function(){
				$(this).trigger('validar');
			});
			
			if(!b2make.cadastro_validado){
				$('#b2make-etapas-pontos ul li[data-id="2"]').trigger('mouseup');
				enviar = false;
			}
			
			if(!b2make.cadastro_validado2){
				enviar = false;
			}
			
			if(!b2make.site_host_ok){
				enviar = false;
			}
			
			if(!enviar){
				return false;
			} else {
				if(ajax_vars.recaptcha_enable){
					$.ajax({
						type: 'POST',
						url: url_name(),
						data: { ajax : 'sim' , recaptcha : 'sim' , recaptcha_challenge_field : Recaptcha.get_challenge() , recaptcha_response_field : Recaptcha.get_response() },
						beforeSend: function(){
							$('#ajax_lendo').fadeIn(tempo_animacao);
						},
						success: function(txt){
							$('#ajax_lendo').fadeOut(tempo_animacao);
							
							if(txt == 'sim'){
								$('#ajax_lendo2').fadeIn(tempo_animacao);
								install_loading(1);
								window.form_serialize = $('#'+form_id).serialize();
								window.enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
								$('#'+form_id)[0].reset();
								cadastrar_usuario = false;
								cadastrar_senha = false;
							} else {
								Recaptcha.reload();
								mens = "<p>C&oacute;digo de valida&ccedil;&atilde;o <b style=\"color:red;\">inv&aacute;lido</b>!<p></p>Favor preencher o c&oacute;digo de valida&ccedil;&atilde;o novamente!</p>";
								$.alerta_open(mens,false,false);
							}
						},
						error: function(txt){
							
						}
					});
				} else {
					$('#ajax_lendo2').fadeIn(tempo_animacao);
					install_loading(1);
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
			
			if(!$('#ajax_lendo2').length){
				$('<div id="ajax_lendo2">Carregando</div>').appendTo('body');
				$('#ajax_lendo2').center();
			}
			
			$('.b2make-cadastro-validacao-2').each(function(){
				$(this).trigger('validar');
			});
			
			if(!b2make.cadastro_validado2){
				enviar = false;
			}
			
			if(!b2make.site_host_ok){
				enviar = false;
			}
			
			if(!enviar){
				return false;
			} else {
				$.ajax({
					type: 'POST',
					url: url_name(),
					data: { ajax : 'sim' , opcao : 'signup_facebook_vars' , modelo : $("#b2make-modelo-selecionado").val() , meu_website : $("#meu_website").val() , layout : $("#layout").val() , dominio_proprio : $("#dominio_proprio").val() , plano : $("#plano").val() },
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						$('#ajax_lendo2').fadeIn(tempo_animacao);
						install_loading(2);
						
						window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'signup-facebook','_self');
					},
					error: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
					}
				});
			}
		}
		
		function help_open(id){
			$.alerta_open(b2make.help_text[id].texto,b2make.msgs.help_title+' - '+b2make.help_text[id].titulo,false);
		}
		
		function help(){
			b2make.help = {};
			
			if(!b2make.msgs.help_title)b2make.msgs.help_title = 'Ajuda';
			if(!b2make.help.dialogbox_height)b2make.help.dialogbox_height = 400;
			if(!b2make.help.dialogbox_width)b2make.help.dialogbox_width = 540;
			
			$('.b2make-help').on('mouseup touchend',function(e){
				var id = $(this).attr('data-id');
				
				if(!b2make.help_text){
					b2make.help_text = Array();
				}
				
				if(!b2make.help_text[id]){
					var opcao = 'help-texto';
					
					$.ajax({
						type: 'POST',
						url: '.',
						data: { 
							ajax : 'sim',
							opcao : opcao,
							id : id
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
										b2make.help_text[id] = {
											texto : dados.texto,
											titulo : dados.titulo
										};
										help_open(id);
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
				} else {
					help_open(id);
				}
			});
		}
		
		function site_host_verificar(value){
			var opcao = 'site-host';
			if(value && value.length >= 3){
				$.ajax({
					type: 'POST',
					url: '.',
					data: { 
						ajax : 'sim',
						opcao : opcao,
						value : value
					},
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						if(
							txt.charAt(0) == "{" || 
							txt.charAt(0) == "["
						){
							var dados = eval('(' + txt + ')');
							var validado = true;
							var mens = '';
							
							switch(dados.status){
								case 'Ok':
									b2make.site_host_ok = true;
								break;
								case 'JaExiste':
									validado = false;
									mens = 'Este endere&ccedil;o j&aacute; est&aacute; em uso! Escolha outro.';
								break;
								default:
									validado = false;
									console.log('ERROR - '+opcao+' - '+txt);
								
							}
							
							cadastro_validacao_operacoes({
								obj : $('#meu_website').get(0),
								obj_after : $('.b2make-help[data-id="cadastro-meu-website"]').get(0),
								mens : mens,
								id : 'meu_website',
								validado : validado
							});
							
							if(b2make.cadastrar_clicked){
								b2make.cadastrar_clicked = false;
								b2make.cadastrar_clicked_anti_loop = true;
								$('#b2make-signup-cadastrar').trigger('mouseup');
							}
						} else {
							console.log('ERROR - '+opcao+' - '+txt);
						}
					},
					error: function(txt){
						console.log('ERROR AJAX - '+opcao+' - '+txt);
					}
				});
			} else {
				cadastro_validacao_operacoes({
					obj : $('#meu_website').get(0),
					obj_after : $('.b2make-help[data-id="cadastro-meu-website"]').get(0),
					mens : 'Este endere&ccedil;o tem que ter no m&iacute;nimo 3 caracteres!',
					id : 'meu_website',
					validado : false
				});
			}
		}
		
		function site_host(){
			b2make.site_host = {};
			if(!b2make.msgs)b2make.msgs = {};
			if(!b2make.site_host.delay_timeout)b2make.site_host.delay_timeout = 600;
			if(!b2make.msgs.siteHostDefinirSemValor)b2make.msgs.siteHostDefinirSemValor = "Valor n&atilde;o definido. &Eacute; necess&aacute;rio definir um nome para seu site antes de enviar.";
			if(!b2make.msgs.siteHostDefinirForaLimites)b2make.msgs.siteHostDefinirForaLimites = "Nome do site tem que ter no m&iacute;nimo 3 caracteres.";
			
			$('#meu_website').keyup(function (e) {
				var value = this.value;
				
				switch(e.keyCode){
					case 20:
					case 27:
					case 35:
					case 36:
					case 37:
					case 39:
						var nao = true;
					break;
					default: 
						value = retirar_acentos(value);
						this.value = value.substring(0, 45);
				}
				
				b2make.site_host_ok = false;
			});
		}
		
		site_host();
		help();
	}
	
	if($('#b2make-signin-login-cont').length > 0){
		var login_clicou_botao = false;
		
		$('#b2make-signin-login label').each(function(){
			var id_pai = $(this).attr('for');
			var top = $('#'+id_pai).position().top;
			var left = $('#'+id_pai).position().left;
			var label = $(this);
			
			label.css({top:top,left:left});
			
			setTimeout(function() {
				if($('#'+id_pai).val()){
					label.hide();
				}
			});
		});
		
		$('#b2make-signin-login input').bind('keyup keydown change blur focus',function(e) {
			if($(this).val()){
				$('label[for="'+$(this).attr('id')+'"]').hide();
			} else {
				$('label[for="'+$(this).attr('id')+'"]').show();
			}
		});
		
		$('#b2make-signin-cadastro-facebook').css('padding','0px');
		$('#b2make-signin-cadastro-line').css('margin','17px 0px 0px 0px');
		
		$('#b2make-signin-cadastro-facebook').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'signin-facebook','_self');
		});
		
		$('#b2make-signin-login-entrar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			signin();
		});
		
		$('#b2make-signin-login #senha').live('keyup',function(e){
			if(e.keyCode == 13){
				signin();
			}
		});
		
		function signin(){
			var enviar = true;
			var campo;
			var mens;
			
			campo = "usuario"; mens = "Preencha o email"; if(!$("#"+campo).val()){ $.alerta_caixa_open({id:campo,mens:mens,top:6,left:10}); enviar = false; }
			campo = "senha"; mens = "Preencha a senha"; if(!$("#"+campo).val()){ $.alerta_caixa_open({id:campo,mens:mens,top:6,left:10}); enviar = false; }
			
			login_clicou_botao = true;
			
			if(enviar){
				window.enviar_form_simples('b2make-signin-login');
			}
			
			return false;
		}
	}
	
	if($('#b2make-signup-success').length || $('#b2make-payment-complete').length){
		// ==================================== Facebook Pixel =============================
		
		fbq('track', 'CompleteRegistration');
		
		if($('#b2make-payment-complete').length){
			fbq('track', 'Purchase', {value: ajax_vars.b2make_analytics_dados.item_preco, currency: 'BRL'});
		}
		
		if(window._gaq && ajax_vars.b2make_analytics_dados){
			var pedido_id = ajax_vars.b2make_analytics_dados.pedido_id;
			var item_id = ajax_vars.b2make_analytics_dados.item_id;
			var item_titulo = ajax_vars.b2make_analytics_dados.item_titulo;
			var item_preco = ajax_vars.b2make_analytics_dados.item_preco;
			
			window._gaq.push(['_addTrans',
				pedido_id,   // order ID
				'B2Make',  // store
				item_preco,  // total
				'0',      	 // tax
				'0',       // shipping
				'Ribeirao Preto',    // city
				'Sao Paulo',   // state
				'Brazil'       // country
			]);
			
			window._gaq.push(['_addItem',
				pedido_id,         			// transaction ID - necessary to associate item with transaction
				item_id,         // SKU/code - required
				item_titulo,      // product name - necessary to associate revenue with product
				'Assinaturas', // category or variation
				item_preco,        // unit price - required
				'1'             // quantity - required
			]);
			
			window._gaq.push(['_set', 'currencyCode', 'BRL']);
			window._gaq.push(['_trackTrans']);
		}
		
		$('#b2make-acess-site').on('mouseup touchstart',function(){
			window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'design','_self');
		});
	}
	
	if($('#b2make-payment').length){
		// ==================================== Facebook Pixel =============================
		
		fbq('track', 'AddPaymentInfo');

		$('#b2make-payment-pagseguro').on('mouseup touchstart',function(){
			window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'pagseguro-subscription','_self');
		});
		
		$('#b2make-payment-paypal').on('mouseup touchstart',function(){
			window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'paypal-subscription','_self');
		});
	}
	
	if($('#b2make-my-profile').length){
		$('#b2make-change-password-button').on('mouseup touchstart',function(){
			$('#b2make-change-password').hide();
			$('#b2make-change-password-2').show();
			
			$('#senha').focus();
		});
		
		$(".uf").mask("aa");
		$(".cep").mask("99.999-999");
		$(".telefone").mask("(99) 9999-9999?9");
		$(".inteiro").numeric();
		
		$('#b2make-my-profile-save').on('mouseup touchstart',function(){
			var enviar = true;
			var campo;
			var post;
			var mens;
			var campos = Array();
			var posts = Array();
			var opcao = '';
			var href = '';
			var form_id = 'b2make-my-profile-form';
			var limpar_campos = true;
			var mudar_pagina = false;
			
			campo = 'nome'; mens = "&Eacute; obrigat&oacute;rio definir o Nome!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			if(!enviar){
				return false;
			} else {
				window.form_serialize = $('#'+form_id).serialize();
				window.enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
			}
		});
		
	}
	
	if($('#b2make-upgrade-plan-cont').length){
		b2make.cadastro_plan_atual = parseInt($('#b2make-upgrade-plan-form #plano').val());
		
		$('.b2make-plans-box').each(function(){
			if(!$(this).find('.b2make-plans-box-tit').hasClass('b2make-plans-box-disabled')){
				var plan = parseInt($(this).attr('data-plan'));

				if(plan == b2make.cadastro_plan_atual){
					$('.b2make-plans-box .b2make-plans-box-tit').each(function(){
						if(!$(this).hasClass('b2make-plans-box-disabled')){
							if(!$(this).hasClass('b2make-plans-deselected'))$(this).addClass('b2make-plans-deselected');
							if($(this).hasClass('b2make-plans-selected'))$(this).removeClass('b2make-plans-selected');
						}
					});
					
					$(this).find('.b2make-plans-box-tit').removeClass('b2make-plans-deselected');
					$(this).find('.b2make-plans-box-tit').addClass('b2make-plans-selected');
				}
			}
		});
		
		$('.b2make-plans-box').on('mouseup touchstart',function(){
			if(!$(this).find('.b2make-plans-box-tit').hasClass('b2make-plans-box-disabled')){
				var plan = parseInt($(this).attr('data-plan'));
				
				if(plan >= b2make.cadastro_plan_atual){
					$('.b2make-plans-box .b2make-plans-box-tit').each(function(){
						if(!$(this).hasClass('b2make-plans-box-disabled')){
							if(!$(this).hasClass('b2make-plans-deselected'))$(this).addClass('b2make-plans-deselected');
							if($(this).hasClass('b2make-plans-selected'))$(this).removeClass('b2make-plans-selected');
						}
					});
					
					$(this).find('.b2make-plans-box-tit').removeClass('b2make-plans-deselected');
					$(this).find('.b2make-plans-box-tit').addClass('b2make-plans-selected');
					
					$('#b2make-upgrade-plan-form #plano').val(plan);
				} else {
					$.alerta_open('Não é possível diminuir o seu plano atual. Apenas manter ou aumentar',false,false);
				}
			}
		});
		
		$('#b2make-upgrade-plan-btn').on('mouseup touchstart',function(){
			var plan = 0;
			$('.b2make-plans-box .b2make-plans-box-tit').each(function(){
				if($(this).hasClass('b2make-plans-selected')){
					plan = parseInt($(this).parent().attr('data-plan'));
				}
			});
			
			if(plan > b2make.cadastro_plan_atual){
				dialogbox_open({
					confirm:true,
					calback_yes: 'b2make-upgrade-plan-yes',
					msg: 'Tem certeza que você deseja mudar seu plano?'
				});
				
				$('.b2make-upgrade-plan-yes').on('mouseup touchend',function(e){
					var campos = Array();
					var posts = Array();
					var opcao = '';
					var href = '';
					var form_id = 'b2make-upgrade-plan-form';
					var limpar_campos = true;
					var mudar_pagina = false;
					
					window.form_serialize = $('#'+form_id).serialize();
					window.enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
				});
			} else {
				$.alerta_open('Escolha um plano superior ao atual e tente novamente.',false,false);
			}
		});
	}
	
	if($('#b2make-signature-cancel').length){
		$('#b2make-signature-cancel-confirm').on('mouseup touchstart',function(){
			window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'signature-cancel-confirm','_self');
		});
	}
	
	function retirar_acentos(val) {
		val = val.toLowerCase();
		
		val = val.replace(/[áàâãÁÀÂÃª]/g,'a');	
		val = val.replace(/[éèêÊÉÈ]/g,'e');	
		val = val.replace(/[íìîÍÌÎ]/g,'i');	
		val = val.replace(/[óòôõÓÒÔÕº]/g,'o');	
		val = val.replace(/[úùûÚÙÛ]/g,'u');	
		val = val.replace(/[\.\\\\,:;<>\/:\?\|_!`~@#\$%\^&\*\'\+=]/g,'');	
		val = val.replace(/[\(\)\{\}\[\]]/g,'');	
		val = val.replace(/ç/g,'c');
		val = val.replace(/Ç/g,'c');
		val = val.replace(/ /g,'');
		val = val.replace(/\-+/g,'');
		val = val.replace(/[^a-z^A-Z^0-9^-]/g,'');	
		
		return val;
	}
	
	function validar_senha(){
		var mens;
		var cor;
		var senha = senha_id;
		var senha2 = senha2_id;
		var pres_senha = 'pres_senha';
		
		if($("#"+senha).val() && $("#"+senha2).val()){
			var valido = true;
			
			if($("#"+senha).val() != $("#"+senha2).val()){
				valido = false;
				mens = "Senha e Redigite a Senha s&atilde;o diferentes.";
				$.alerta_open(mens,false,false);
			}
			
			if(checkStr($("#"+senha).val())){
				valido = false;
				mens = "Caracteres inv&aacute;lidos, apenas caracteres alfanum&eacute;ricos e ( _ ou @ ou . )";
				$.alerta_open(mens,false,false);
			}
			
			if(!limites_str($("#"+senha).val(),4,20)){
				valido = false;
				mens = "Senha no m&iacute;nimo 4 e no m&aacute;x 20 caracteres.";
				$.alerta_open(mens,false,false);
			}
			
			$("#"+pres_senha).removeClass('ui-state-highlight');
			$("#"+senha2).removeClass('input-vazio');
			$("#"+senha).removeClass('input-vazio');
			$("#"+pres_senha).removeClass('input-vazio');
			
			if(!valido){
				cor = cor1;
				cadastrar_senha = false;
				$("#"+senha).val('');
				$("#"+senha2).val('');
				$("#"+senha).addClass('input-vazio');
				$("#"+senha2).addClass('input-vazio');
				$("#"+pres_senha).addClass('input-vazio');
			} else {
				mens = "Senha OK.";
				cor = cor2;
				cadastrar_senha = true;
				$("#"+pres_senha).addClass('ui-state-highlight');
			}
			
			$("#"+pres_senha).html(mens);
		}
	}
	
	function validar_email(){
		var email = email_id;
		var email2 = email2_id;
		var pres_email = 'pres_email';
		
		if($("#"+email).val() && $("#"+email2).val()){
			var mail = $("#"+email).val();
			var mens;
			var cor;
			var valido = true;
			
			if($("#"+email).val() != $("#"+email2).val()){
				valido = false;
				mens = "E-mail e Repita o E-mail s&atilde;o diferentes.";
				$.alerta_open(mens,false,false);
			}
			
			if(!checkMail(mail)){
				valido = false;
				mens = "E-mail inv&aacute;lido.";
				$.alerta_open(mens,false,false);
			}
			
			$("#"+pres_email).removeClass('ui-state-highlight');
			$("#"+email).removeClass('input-vazio');
			$("#"+email2).removeClass('input-vazio');
			$("#"+pres_email).removeClass('input-vazio');
			
			if(!valido){
				cor = cor1;
				cadastrar_usuario = false;
				$("#"+email).val('');
				$("#"+email2).val('');
				$("#"+email).addClass('input-vazio');
				$("#"+email2).addClass('input-vazio');
				$("#"+pres_email).addClass('input-vazio');
			} else {
				$.ajax({
					type: 'POST',
					url: url_name(),
					data: { ajax : 'sim' , email_usuario : ($("#"+email).val()?$("#"+email).val():'') , edit_email : ($("#"+edit_email_id).val()?$("#"+edit_email_id).val():'') },
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						var valido = true;
						var mens = "";
						var cor;
						
						if(txt == 'sim'){
							valido = false;
							mens = $("#d_email").html();
							$.alerta_open(mens,false,false);
						}
						
						if(!valido){
							cor = cor1;
							cadastrar_usuario = false;
							$("#"+email).val('');
							$("#"+email2).val('');
							$("#"+email).addClass('input-vazio');
							$("#"+email2).addClass('input-vazio');
							$("#"+pres_email).addClass('input-vazio');
						} else {
							mens = "E-mail OK.";
							cor = cor2;
							cadastrar_usuario = true;
							$("#"+pres_email).addClass('ui-state-highlight');
						}
						
						$("#"+pres_email).show();
						$("#"+pres_email).html(mens);
					},
					error: function(txt){
						
					}
				});
			}
			
			$("#"+pres_email).html(mens);
			
		} else {
			cadastrar_usuario = false;
		}
	}
	
	function url_name(){
		var url_aux = location.pathname;
		var url_parts;
		
		url_parts = url_aux.split('/');
		
		if(url_parts[url_parts.length-1])
			return url_parts[url_parts.length-1];
		else
			return '.';
	}
	
	function checkDomain(domain){
		var er = new RegExp(/^([a-zA-Z]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z-]{2,6}$/);
		if(typeof(domain) == "string"){
			if(er.test(domain)){ return true; }
		}else if(typeof(domain) == "object"){
			if(er.test(domain.value)){
						return true;
					}
		}else{
			return false;
			}
	}
	
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
	
	$(window).bind('keyup',function(e) {
		if(e.keyCode == 27){ // ESC
			if(b2make.dialogbox){
				dialogbox_close();
			}
		}
	});
	
	function dialogbox_open(p){
		if(!b2make.dialogbox){
			if(!p)p = {};
			b2make.dialogbox = true;
			
			if(!b2make.dialbox_default_width)b2make.dialbox_default_width = $("#b2make-dialogbox").width();
			if(!b2make.dialbox_default_height)b2make.dialbox_default_height = $("#b2make-dialogbox").height();
			
			if(!p.width)if(b2make.dialbox_default_width != $("#b2make-dialogbox").width())$("#b2make-dialogbox").width(b2make.dialbox_default_width);
			if(!p.height)if(b2make.dialbox_default_height != $("#b2make-dialogbox").height())$("#b2make-dialogbox").height(b2make.dialbox_default_height);
			
			if(p.width)$("#b2make-dialogbox").width(p.width);
			if(p.height)$("#b2make-dialogbox").height(p.height);
			
			$("#b2make-dialogbox-head").html((p.title?p.title:(p.confirm?b2make.msgs.confirmTitle:b2make.msgs.alertTitle)));
			if(!p.coneiner)$("#b2make-dialogbox-msg").html((p.msg?p.msg:(p.confirm?b2make.msgs.confirmMsg:b2make.msgs.alertMsg)));
			$("#b2make-dialogbox-btns2").html('');
			
			if(p.coneiner){
				$("#b2make-dialogbox-msg").html('');
				$("#b2make-dialogbox-msg").append($('#'+p.coneiner));
				b2make.dialogbox_conteiner = p.coneiner;
			}
			
			if(!p.no_btn_default){
				if(p.message){
					$('<div class="b2make-dialogbox-btn b2make-dialogbox-btn-click-dont-close'+(p.calback_yes?' '+p.calback_yes:'')+'"'+(p.calback_yes_extra?' '+p.calback_yes_extra:'')+'>'+(p.message_btn_yes_title ? p.message_btn_yes_title : b2make.msgs.messageBtnYes)+'</div>').appendTo("#b2make-dialogbox-btns2");
					
					if(p.more_buttons){
						var btns = p.more_buttons;
						
						for(var i=0;i<btns.length;i++){
							$('<div class="b2make-dialogbox-btn'+(btns[i].calback?' '+btns[i].calback:'')+'"'+(btns[i].calback_extra?' '+btns[i].calback_extra:'')+'>'+btns[i].title+'</div>').appendTo("#b2make-dialogbox-btns2");
						}
					}
					
					$('<div class="b2make-dialogbox-btn'+(p.calback_no?' '+p.calback_no:'')+'"'+(p.calback_no_extra?' '+p.calback_no_extra:'')+'>'+(p.message_btn_no_title ? p.message_btn_no_title : b2make.msgs.messageBtnNo)+'</div>').appendTo("#b2make-dialogbox-btns2");
				} else if(p.confirm){
					$('<div class="b2make-dialogbox-btn'+(p.calback_no?' '+p.calback_no:'')+'"'+(p.calback_no_extra?' '+p.calback_no_extra:'')+'>'+(p.confirm_btn_no_title ? p.confirm_btn_no_title : b2make.msgs.confirmBtnNo)+'</div>').appendTo("#b2make-dialogbox-btns2");
					$('<div class="b2make-dialogbox-btn'+(p.calback_yes?' '+p.calback_yes:'')+'"'+(p.calback_yes_extra?' '+p.calback_yes_extra:'')+'>'+(p.confirm_btn_yes_title ? p.confirm_btn_yes_title : b2make.msgs.confirmBtnYes)+'</div>').appendTo("#b2make-dialogbox-btns2");
				} else {
					$('<div class="b2make-dialogbox-btn'+(p.calback_no?' '+p.calback_alert:'')+'"'+(p.calback_alert_extra?' '+p.calback_alert_extra:'')+'>'+(p.alert_btn_title ? p.alert_btn_title : b2make.msgs.alertBtn)+'</div>').appendTo("#b2make-dialogbox-btns2");
				}
			}
			
			b2make.dialogbox_callback_yes = p.calback_yes;
			
			var top_start = -10 - $("#b2make-dialogbox").height();
			var top_stop = $(window).height()/2 - $("#b2make-dialogbox").height()/2;
			var left = $(window).width()/2 - $("#b2make-dialogbox").width()/2;
			
			$("#b2make-dialogbox").css('top',top_start);
			$("#b2make-dialogbox").css('left',left);
			$("#b2make-dialogbox").show();
			
			$("#b2make-dialogbox").animate({top:top_stop}, b2make.dialogboxAnimateTime, function(){
				if(p.coneiner){
					$('#'+p.coneiner).find('input').filter(':visible:first').focus();
					$('#'+p.coneiner).find('input').filter(':visible:first').tooltip( "close" );
				}
			});
		}
	}
	
	function dialogbox_shake(){
		$("#b2make-dialogbox").stop().effect( "shake" );
	}
	
	function dialogbox_open_after(p){
		setTimeout(function(){
			dialogbox_open(p);
		},b2make.dialogboxAnimateTime);
	}
	
	function dialogbox_close(){
		if(b2make.dialogbox){
			b2make.dialogbox = false;
			
			var top_stop = -10 - $("#b2make-dialogbox").height();
			
			$("#b2make-dialogbox").animate({top:top_stop}, b2make.dialogboxAnimateTime, "swing", function(){
				if(b2make.dialogbox_conteiner){
					formulario_resetar(b2make.dialogbox_conteiner);
					$('#'+b2make.dialogbox_conteiner).appendTo($('#b2make-formularios'));
					b2make.dialogbox_conteiner = false;
				}
			});
		}
	}
	
	function dialogbox_position(){
		if(b2make.dialogbox){
			$("#b2make-dialogbox").stop();
			var top = $(window).height()/2 - $("#b2make-dialogbox").height()/2;
			var left = $(window).width()/2 - $("#b2make-dialogbox").width()/2;
			
			$("#b2make-dialogbox").css('top',top);
			$("#b2make-dialogbox").css('left',left);
		} else {
			var top =  -10 - $("#b2make-dialogbox").height();;
			var left = $(window).width()/2 - $("#b2make-dialogbox").width()/2;
			
			$("#b2make-dialogbox").css('top',top);
			$("#b2make-dialogbox").css('left',left);
		}
	}
	
	function dialogbox(){
		b2make.dialogbox = false;
		if(!b2make.msgs)b2make.msgs = {};
		if(!b2make.dialogboxAnimateTime)b2make.dialogboxAnimateTime = 250;
		if(!b2make.msgs.alertTitle)b2make.msgs.alertTitle = "Alerta";
		if(!b2make.msgs.confirmTitle)b2make.msgs.confirmTitle = "Confirma&ccedil;&atilde;o";
		if(!b2make.msgs.alertMsg)b2make.msgs.alertMsg = "Esta op&ccedil;&atilde;o n&atilde;o est&aacute; ativada";
		if(!b2make.msgs.alertBtn)b2make.msgs.alertBtn = "Ok";
		if(!b2make.msgs.confirmMsg)b2make.msgs.confirmMsg = "Tem certeza que deseja proseguir?";
		if(!b2make.msgs.confirmBtnYes)b2make.msgs.confirmBtnYes = "Sim";
		if(!b2make.msgs.confirmBtnNo)b2make.msgs.confirmBtnNo = "N&atilde;o";
		if(!b2make.msgs.messageBtnNo)b2make.msgs.messageBtnNo = "Cancelar";
		if(!b2make.msgs.messageBtnYes)b2make.msgs.messageBtnYes = "Enviar";
		
		$(".b2make-dialogbox-btn").live('mouseup touchend',function(e){
			if(!$(this).hasClass('b2make-dialogbox-btn-click-dont-close'))dialogbox_close();
		});
		
		var dialogbox_cont = $('<div id="b2make-dialogbox"><div id="b2make-dialogbox-head"></div><div id="b2make-dialogbox-msg"></div><div id="b2make-dialogbox-btns2"></div></div>');
		dialogbox_cont.appendTo('body');
	}
	
	dialogbox();
	
	function install_loading(num){
		b2make.install_host = {};
		
		b2make.install_host.timeOut = 100;
		b2make.install_host.timeTotal = (num == 1 ? 10000 : 15000);
		b2make.install_host.widthMax = 240;
		b2make.install_host.perc = 0;
		b2make.install_host.percPorPasso = b2make.install_host.timeOut / b2make.install_host.timeTotal;
		b2make.install_host.textoEtapas = Array('Criando hospedagem','Criando Base de Dados','Criando Perfil','Preparando ambiente','Finalizando');
		
		$('#ajax_lendo2').append('<div id="b2make-install-loading"><div id="b2make-install-loading-slide"></div></div><div id="b2make-install-loading-perc"></div><div id="b2make-install-loading-label"></div>');
		$('#b2make-install-loading-slide').width(0);
		$('#b2make-install-loading-label').html(b2make.install_host.textoEtapas[0]);
		
		setTimeout(install_proximo,b2make.install_host.timeOut);
	}
	
	function install_proximo(){
		var stop = false;
		b2make.install_host.perc = b2make.install_host.perc + b2make.install_host.percPorPasso;
		
		if(b2make.install_host.perc > 1){
			b2make.install_host.perc = 1;
			stop = true;
			$('#b2make-install-loading-label').html(b2make.install_host.textoEtapas[4]);
		}
		
		if(b2make.install_host.perc > 0.25){
			$('#b2make-install-loading-label').html(b2make.install_host.textoEtapas[1]);
		}
		
		if(b2make.install_host.perc > 0.50){
			$('#b2make-install-loading-label').html(b2make.install_host.textoEtapas[2]);
		}
		
		if(b2make.install_host.perc > 0.75){
			$('#b2make-install-loading-label').html(b2make.install_host.textoEtapas[3]);
		}
		
		if(b2make.install_host.perc >= 1){
			$('#b2make-install-loading-label').html(b2make.install_host.textoEtapas[4]);
		}
		
		var width = Math.floor(b2make.install_host.widthMax * b2make.install_host.perc);
		
		$('#b2make-install-loading-slide').width(width);
		$('#b2make-install-loading-perc').html(Math.floor(b2make.install_host.perc * 100) + '%');
		
		if(!stop)setTimeout(install_proximo,b2make.install_host.timeOut);
		
	}
};

// ======================================= Instalar fun&ccedil;&otilde;es ============================

$.aplicar_scripts_add('aplicar_scripts_autenticar');