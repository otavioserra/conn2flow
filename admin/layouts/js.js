$(document).ready(function(){
	sep = "../../";
	var teste_area = $("#teste_area");
	
	teste_area.dialog({
		autoOpen: false,
		width: 1000,
		height: 700,
		modal: true,
		title: 'Teste',
		buttons: { "Ok": function() { $(this).dialog("close"); }}
	});
	
	if($("#testar_flag").val()){
		teste_area.dialog("open");
	}
	
	$("#form").submit(function() {
		var enviar = true;
		var campo;
		var mens;
		var mens_extra;
		var cor1 = "#FF5B5B";
		var cor2 = "#0C9";
		
		//campo = "nome"; if(!$("#"+campo).val()){ $("#"+campo).css('background-color',cor1); enviar = false; } else { $("#"+campo).css('background-color',cor2); }
		
		if(!enviar){
			alerta.html("� obrigat�rio preencher os campos marcados em vermelho!" + ( mens_extra ? "\n\nNOTA: " + mens_extra : ""));
			alerta.dialog("open");
			return false;
		}
	});
	
	
});