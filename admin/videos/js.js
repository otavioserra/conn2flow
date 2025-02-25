$(document).ready(function(){
	sep = "../../";
	
	if($("a[rel^='prettyPhoto']").length){
		setTimeout(function(){ $("a[rel^='prettyPhoto']").prettyPhoto({animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false, show_title: true}); }, 100);
	}
	
	$("#form").submit(function() {
		if(!submit_form("form",false)){
			return false;
		}
	});
	
	$("#form2").submit(function() {
		if(!submit_form("form2",false)){
			return false;
		}
	});
	
	$("#grupos").submit(function() {
		if(!submit_form("grupos",false)){
			return false;
		}
	});
	
	function submit_form(form_id,status){
		var enviar = true;
		var campo;
		var mens;
		
		switch(form_id){
			case "form":
				campo = "nome"; mens = "Preencha o nome"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
			break;
			case "form2":
				campo = "titulo"; mens = "Preencha o título"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
			break;
			case "grupos":
				campo = "grupo"; mens = "Preencha o nome do grupo"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
			break;
			
		}
		
		if(!enviar){
			return false;
		}
		
		if(status)
			$("#"+form_id).submit();
		
		return true;
	}
	
	$(".videos_escolher").bind("click touchstart", function() {
		parent.videos_escolher($(this).attr('videos'));
	});
	
	$(".videos_grupo_escolher").bind("click touchstart", function() {
		parent.videos_grupo_escolher($(this).attr('videos_grupo'));
	});
	
	
});