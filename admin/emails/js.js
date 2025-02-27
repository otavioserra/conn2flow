$(document).ready(function(){
	sep = "../../";
	var cor1 = '#F00'; // Vermelho
	var cor2 = '#0C6'; // Verde
	var cadastrar_email;
	var email_validador_contador = 0;
	
	$("#email").blur(function() {
		validar_email(false);
	});
	
	$("#form").submit(function() {
		var enviar = true;
		var mostrar_dialog = true;
		var campo;
		var mens;
		var mens_extra;
		var cor1 = "#FF5B5B";
		var cor2 = "#0C9";
		
		campo = "nome"; if(!$("#"+campo).val()){ $("#"+campo).css('background-color',cor1); enviar = false; } else { $("#"+campo).css('background-color',cor2); }
		campo = "email"; if(!$("#"+campo).val()){ $("#"+campo).css('background-color',cor1); enviar = false; } else { $("#"+campo).css('background-color',cor2); }
		
		if(!cadastrar_email){
			enviar = false;
			validar_email(true);
			
			if(email_validador_contador == 0){
				mostrar_dialog = false;
			}
		}
		
		if(!enviar){
			if(mostrar_dialog){
				alerta.html("<p>É obrigatório preencher os campos marcados em vermelho!</p>" + ( mens_extra ? "<p>NOTA: " + mens_extra + "</p>" : ""));
				alerta.dialog('open');
			}
			return false;
		}
	});
	
	function validar_email(form){
		if($("#email").val()){
			var mail = $("#email").val();
			var mens;
			var cor;
			
			if(!checkMail(mail)){
				mens = "E-mail inválido.";
				alerta.html(mens);
				alerta.dialog('open');
				cor = cor1;
				cadastrar_email = false;
			} else {
				$.ajax({
					type: 'POST',
					url: url_name(),
					data: { ajax : 'sim' , email : ($("#email").val()?$("#email").val():'') , edit_email : ($("#edit_email").val()?$("#edit_email").val():'') },
					beforeSend: function(){
						//
					},
					success: function(txt){
						var valido = true;
						var mens = "";
						var cor;
						
						if(txt == 'sim'){
							valido = false;
							mens = "E-mail já está em uso! Escolha outro!";
							alerta.html(mens);
							alerta.dialog('open');
						}
						
						if(!valido){
							cor = cor1;
							cadastrar_email = false;
						} else {
							cor = cor2;
							cadastrar_email = true;
							
							if(form){
								$("#form").submit();
							}
						}
						
						$("#mens_email").css('font-weight','bold');
						$("#mens_email").css('color',cor);
						$("#mens_email").html(mens);
					},
					error: function(txt){
						
					}
				});
			}
			
			$("#mens_email").css('font-weight','bold');
			$("#mens_email").css('color',cor);
			$("#mens_email").html(mens);
			
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
	
});