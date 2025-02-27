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
		
	}
	
};

// ======================================= Instalar fun&ccedil;&otilde;es ============================

$.aplicar_scripts_add('aplicar_scripts_autenticar');