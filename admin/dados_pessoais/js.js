$(document).ready(function(){
	sep = "../../";
	var cadastrar_usuario = false;
	var cadastrar_senha = false;
	var cor1 = '#F00'; // Vermelho
	var cor2 = '#0C6'; // Verde
	
	var lendo = $("#lendo");
	
	lendo.dialog({
		autoOpen: false,
		modal: true,
		title: 'Lendo'
	});
	
	$("#senha_antiga").val('');
	setTimeout(function(){$("#senha_antiga").val('');},100);
	$("#senha").val('');
	$("#senha2").val('');
	
	$(".dialog").css('display','none');
	
	$(".cep").mask("99.999-999");
	$(".telefone").mask("(99) 9999-9999");
	$(".data").mask("99/99/9999");
	
	if($("#opcao").val() == 'editar_base'){
		cadastrar_usuario = true;
		cadastrar_senha = true;
	}
	
	$("#usuario").blur(function() {
		if($("#usuario").val()){
			$.ajax({
				type: 'POST',
				url: url_name(),
				data: { ajax : 'sim' , usuario : ($("#usuario").val()?$("#usuario").val():'') , edit_usuario : ($("#edit_usuario").val()?$("#edit_usuario").val():'') },
				beforeSend: function(){
					mensagem = '<p style="text-align: center; width: 100%">Validando usuário... aguarde!</p><p style="text-align: center; width: 100%"><img src="'+sep+'images/icons/loading11.gif" /></p>';
					if(!lendo.dialog('isOpen')){
						lendo.html(mensagem);
						lendo.dialog('open');
					}
				},
				success: function(txt){
					lendo.dialog('close');
					var valido = true;
					var mens = "";
					var cor;
					
					if(checkStr($("#usuario").val())){
						valido = false;
						mens = $("#d_caracter_in").html();
						alerta.html(mens);
						alerta.dialog('open');
					}
					
					if(!limites_str($("#usuario").val(),3,20)){
						valido = false;
						mens = $("#d_caracter").html();
						alerta.html(mens);
						alerta.dialog('open');
					}
					
					if(txt == 'sim'){
						valido = false;
						mens = $("#d_usuario").html();
						alerta.html(mens);
						alerta.dialog('open');
					}
					
					$("#mens_usuario").removeClass('ui-state-highlight');
					$("#usuario").removeClass('ui-state-error');
					$("#mens_usuario").removeClass('ui-state-error');
					
					if(!valido){
						cor = cor1;
						cadastrar_usuario = false;
						$("#usuario").addClass('ui-state-error');
						$("#mens_usuario").addClass('ui-state-error');
					} else {
						mens = "Usuário OK.";
						cor = cor2;
						cadastrar_usuario = true;
						$("#mens_usuario").addClass('ui-state-highlight');
						$("#senha").focus();
					}
					
					$("#mens_usuario").html(mens);
				},
				error: function(txt){
					
				}
			});
		} else {
			cadastrar_usuario = false;
			$("#usuario").addClass('ui-state-error');
			$("#mens_usuario").addClass('ui-state-error');
			$("#mens_usuario").html("Preencha o usuário!");
		}
	});
	
	$("#senha").keyup(function(eventObject){
		var perc;
		var bpos;
		
		if(eventObject.keyCode != 9){
			if($('#usuario').val()){
				$('#result').html(passwordStrength($('#senha').val(),$('#usuario').val())) ; 
				perc = passwordStrengthPercent($('#senha').val(),$('#usuario').val());
				
				bpos=" $('#colorbar').css( {backgroundPosition: \"0px -" ;
				bpos = bpos + perc + "px";
				bpos = bpos + "\" } );";
				bpos=bpos +" $('#colorbar').css( {width: \"" ;
				bpos = bpos + (perc * 1.9) + "px";
				bpos = bpos + "\" } );";
				eval(bpos);
					$('#percent').html(" " + perc  + "% ");
			} else {
				alerta.html("<p>Defina o usuário antes da senha!</p>");
				alerta.dialog('open');
			}
		}
	});
	
	$("#senha").blur(function() {
		validar_senha();
	});
	
	$("#senha2").blur(function() {
		validar_senha();
	});
	
	$("#email").blur(function() {
		if($("#email").val()){
			var mail = $("#email").val();
			var mens;
			var cor;
			
			$("#mens_email").removeClass('ui-state-highlight');
			$("#email").removeClass('ui-state-error');
			$("#mens_email").removeClass('ui-state-error');
			
			if(!checkMail(mail)){
				mens = "E-mail inválido.";
				alerta.html(mens);
				alerta.dialog('open');
				cor = cor1;
				cadastrar_usuario = false;
				$("#email").addClass('ui-state-error');
				$("#mens_email").addClass('ui-state-error');
			} else {
				$.ajax({
					type: 'POST',
					url: url_name(),
					data: { ajax : 'sim' , email : ($("#email").val()?$("#email").val():'') , edit_email : ($("#edit_email").val()?$("#edit_email").val():'') },
					beforeSend: function(){
						mensagem = '<p style="text-align: center; width: 100%">Validando e-mail... aguarde!</p><p style="text-align: center; width: 100%"><img src="'+sep+'images/icons/loading11.gif" /></p>';
						if(!lendo.dialog('isOpen')){
							lendo.html(mensagem);
							lendo.dialog('open');
						}
					},
					success: function(txt){
						lendo.dialog('close');
						var valido = true;
						var mens = "";
						var cor;
						
						if(txt == 'sim'){
							valido = false;
							mens = $("#d_email").html();
							alerta.html(mens);
							alerta.dialog('open');
						}
						
						if(!valido){
							cor = cor1;
							cadastrar_usuario = false;
							$("#email").addClass('ui-state-error');
							$("#mens_email").addClass('ui-state-error');
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
			$("#email").addClass('ui-state-error');
			$("#mens_email").addClass('ui-state-error');
			$("#mens_email").html("Preencha o e-mail!");
		}
	});
	
	$("#cep").blur(function() {
		if($("#cep").val() && $("#cep_search").val()){
			$.ajax({
				type: 'POST',
				url: url_name(),
				data: { ajax : 'sim' , cep : ($("#cep").val()?$("#cep").val():'') },
				beforeSend: function(){
					mensagem = '<p style="text-align: center; width: 100%">Buscando CEP... aguarde!</p><p style="text-align: center; width: 100%"><img src="'+sep+'images/icons/loading11.gif" /></p>';
					if(!lendo.dialog('isOpen')){
						lendo.html(mensagem);
						lendo.dialog('open');
					}
				},
				success: function(txt){
					lendo.dialog('close');
					var dados = eval('(' + txt + ')');
					
					$("#endereco").val(dados.endereco);
					$("#bairro").val(dados.bairro);
					$("#cidade").val(dados.cidade);
					$("#uf").val(dados.uf);
					$("#numero").focus();
				},
				error: function(txt){
					//
				}
			});
		}
	});
	
	$("#form").submit(function() {
		var enviar = true;
		var campo;
		var mens;
		
		validar_senha();
		
		campo = "usuario"; mens = "É obrigatório definir o Usuário!"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
		if($("#opcao").val() != "editar_base"){
			campo = "senha"; mens = "É obrigatório definir a Senha!"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
			campo = "senha2"; mens = "É obrigatório definir o Redigite a Senha!"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
		}
		
		// Select
		campo = "perfil"; mens = "É obrigatório escolher pelo menos um Perfil do Usuário!"; if($("#"+campo).attr('selectedIndex') == 0){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
		
		campo = "nome"; mens = "É obrigatório definir o Nome!"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
		campo = "email"; mens = "É obrigatório definir o E-mail!"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
		
		if(
			!cadastrar_usuario ||
			!cadastrar_senha
		){	mens = "É necessário validar os campos antes de enviar!"; if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
		
		if(!enviar){
			return false;
		}
	});
	
	$("#enviar_email").submit(function() {
		if(!$("#assunto").val()){						alert("É obrigatório preencher o Assunto!");	return false;}
		if(!$("#mensagem").val()){						alert("É obrigatório preencher a Mensagem!");	return false;}
	});
	
	function validar_senha(){
		var mens;
		var cor;
		
		if($("#senha").val() && $("#senha2").val()){
			var valido = true;
			
			if($("#senha").val() != $("#senha2").val()){
				valido = false;
				mens = "Senha e Redigite a Senha são diferentes.";
				alerta.html($("#d_senha").html());
				alerta.dialog('open');
			}
			
			if(checkStr($("#senha").val())){
				valido = false;
				mens = "Caracteres inválidos, apenas caracteres alfanuméricos e ( _ ou @ ou . )";
				alerta.html($("#d_caracter_in").html());
				alerta.dialog('open');
			}
			
			if(!limites_str($("#senha").val(),3,20)){
				valido = false;
				mens = "Senha no mínimo 3 e no máx 20 caracteres.";
				alerta.html($("#d_caracter_2").html());
				alerta.dialog('open');
			}
			
			$("#mens_senha").removeClass('ui-state-highlight');
			$("#senha2").removeClass('ui-state-error');
			$("#senha").removeClass('ui-state-error');
			$("#mens_senha").removeClass('ui-state-error');
			
			if(!valido){
				cor = cor1;
				cadastrar_senha = false;
				$("#senha").val('');
				$("#senha2").val('');
				$("#senha").addClass('ui-state-error');
				$("#senha2").addClass('ui-state-error');
				$("#mens_senha").addClass('ui-state-error');
			} else {
				mens = "Senha OK.";
				cor = cor2;
				cadastrar_senha = true;
				$("#mens_senha").addClass('ui-state-highlight');
			}
			
			$("#mens_senha").html(mens);
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
	
});