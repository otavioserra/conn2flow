b2makeAdmin.stop_enter_preventDefaults = true;

$(document).ready(function(){
	sep = "../../";
	
	$('#b2make-change-password-button').on('mouseup touchstart',function(){
		$('#b2make-change-password').hide();
		$('#b2make-change-password-2').show();
		
		b2makeAdmin.change_password_clicked = true;
		
		$('#senha').val('');
		$('#senha').focus();
	});
	
	$(".uf").mask("aa");
	$(".cep").mask("99.999-999");
	$(".telefone").mask("(99) 9999-9999?9");
	$(".inteiro").numeric();
	
	$('#b2make-my-profile-save').on('mouseup touchstart',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
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
		
		campo = 'nome'; mens = "&Eacute; obrigat&oacute;rio definir o Nome!"; if(!$("#"+campo).val()){ $("#alerta").html(mens).dialog("open"); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
		
		if(!b2makeAdmin.change_password_clicked){
			$('#senha').val('');
		} else {
			campo = 'senha3'; mens = "&Eacute; obrigat&oacute;rio definir a REDIGITE SENHA NOVA!"; if(!$("#"+campo).val()){ $("#alerta").html(mens).dialog("open"); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			campo = 'senha2'; mens = "&Eacute; obrigat&oacute;rio definir a SENHA NOVA!"; if(!$("#"+campo).val()){ $("#alerta").html(mens).dialog("open"); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			campo = 'senha2'; mens = "O campo SENHA NOVA e REDIGITE SENHA NOVA precisam ser iguais!"; if($("#senha2").val() != $("#senha3").val()){ $("#alerta").html(mens).dialog("open"); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			campo = 'senha'; mens = "&Eacute; obrigat&oacute;rio definir a SENHA ATUAL!"; if(!$("#"+campo).val()){ $("#alerta").html(mens).dialog("open"); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
		}
		
		if(!enviar){
			return false;
		} else {
			$('#'+form_id).submit();
		}
	});
	
	$('#b2make-signature-cancel-confirm').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		window.open('?opcao=signature-cancel-confirm','_self');
	});
	
	$('#b2make-signature-canceled-input').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		window.open('../../','_self');
	});
});