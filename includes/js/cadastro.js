$(document).ready(function(){
	var alerta = $("#alerta");
	var campos_obrigatorios = $("#campos_obrigatorios");
	var formulario_id;
	
	if(campos_obrigatorios){
		formulario_id = $("#formulario_id").val();
		$("#button").click(cadastro_validar_campos(false));
		$("#"+formulario_id).submit(cadastro_validar_campos(true));
		
		alerta.dialog({
			autoOpen: false,
			modal: true,
			title: 'Alerta',
			buttons: { "Ok": function() { $(this).dialog("close"); }}
		});
	}
	
	function cadastro_validar_campos(submit){
		var campos = campos_obrigatorios.split('<#>');
		var dados;
		var campo;
		var mens;
		var tipos_str;
		var tipos;
		var enviar = true;
		var flags = new Array();
		
		if(campos){
			for(var i=0;i<campos.length;i++){
				dados = campos[i].split('<,>');
				campo = dados[0];
				mens = (dados[1] ? dados[1] : 'o campo');
				tipos_str = (dados[2] ? dados[2] : 'comum');
				
				tipos = tipos_str.split(',');
				
				for(var j=0;j<tipos.length;j++){
					if(tipos[j] == 'email') flags['email'] = true; else flags['email'] = false;
					if(tipos[j] == 'select') flags['select'] = true; else flags['select'] = false;
					if(tipos[j] == 'radio') flags['radio'] = true; else flags['radio'] = false;
					if(tipos[j] == 'checkbox') flags['checkbox'] = true; else flags['checkbox'] = false;
					if(tipos[j] == 'tamanho') flags['tamanho'] = true; else flags['tamanho'] = false;
					if(tipos[j] == 'formato') flags['formato'] = true; else flags['formato'] = false;
				}
				
				if(
					!flags['select'] &&
					!flags['radio']
				){
					mens = "É obrigatório preencher " + mens + "!"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
				}
				
				if(flags['email']){
					mens = "E-mail inválido, preencha o campo de e-mail válido!";if(!cadastro_checkMail($("#"+campo).val())){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
				}
				
				if(flags['tamanho']){
					mens = "Defina o campo com no mínimo 3 e no máximo 30 caracteres!"; var str = $("#"+campo).val(); if(str.length < 3 || str.length > 30){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
				}
				
				if(flags['formato']){
					mens = "Formato do Campo Inválido!<br /><br />Só é permitido utilizar caracteres alfanuméricos ou _ ou @ ou ."; if(cadastro_checkStr($("#"+campo).val())){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
				}
				
				if(flags['select']){
					mens = "É obrigatório escolher pelo menos um" + mens + "!"; if($("#"+campo).attr('selectedIndex') == 0){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
				}
				
				if(flags['radio']){
					mens = "É obrigatório escolher pelo menos um" + mens + "!"; if($("input[@name="+campo+"]:checked")){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
				}
				
				if(flags['checkbox']){
					mens = "É obrigatório marcar " + mens + "!"; if($("input[@id="+campo+"]:checked")){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
				}
				
			}
		}
		
		if(!enviar){
			return false;
		}
		
		if(submit){
			$("#"+formulario_id).submit();
		}
	}
	
	function cadastro_checkMail(mail){
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
	
	function cadastro_checkStr(pass){
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

});