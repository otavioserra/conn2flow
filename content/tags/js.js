if(window.location.protocol == "http:" && window.location.hostname != 'localhost'){
	var restOfUrl = window.location.href.substr(5);
	window.location = "https:" + restOfUrl;
}

b2makeAdmin.stop_enter_preventDefaults = true;

$(document).ready(function(){
	sep = "../../";
	
	$("#cor").colorpicker({
		parts: 'full',
		alpha: true
	});
	
	$(".float").maskMoney({showSymbol:false,decimal:",",thousands:".",precision:2});
	$(".inteiro").numeric();
	$(".data").mask("99/99/9999",{completed:function(){
		var data = this.val();
		var data_aux = data.split('/');
		var alerta = "Data inválida";
		var bissexto = false;
		var dia_str;
		var mes_str;
		var ano_str;
		var dia_aux = data_aux[0];
		var mes_aux = data_aux[1];
		
		if(dia_aux[0] == '0') dia_str = dia_aux[1]; else dia_str = dia_aux;
		if(mes_aux[0] == '0') mes_str = mes_aux[1]; else mes_str = mes_aux;
		ano_str = data_aux[2];
		
		var dia = parseInt(dia_str);
		var mes = parseInt(mes_str);
		var ano = parseInt(ano_str);
		
		if(mes > 12 || mes == 0){
			this.val('');
			alert(alerta);
			return false;
		}
		
		switch(mes){
			case 1:
			case 3:
			case 5:
			case 7:
			case 8:
			case 10:
			case 12:
				if(dia > 31){
					this.val('');
					alert(alerta);
					return false;
				}
			break;
			case 4:
			case 6:
			case 9:
			case 11:
				if(dia > 30){
					this.val('');
					alert(alerta);
					return false;
				}
			break;
			case 2:
				if(dia > 28){
					if(ano % 4 == 0){
						bissexto = true;
					}
					if(ano % 100 == 0){
						bissexto = false;
					}
					if(ano % 400 == 0){
						bissexto = true;
					}
					
					if(bissexto == true){
						if(dia > 29){
							this.val('');
							alert(alerta);
							return false;
						}
					} else {
						this.val('');
						alert(alerta);
						return false;
					}
				}
			break;
		}
		
		if(ano < 1875 || ano > 2200){
			this.val('');
			alert(alerta);
			return false;
		}
	}});

	$("#form").submit(function() {
		var enviar = true;
		var campo;
		var mens;
		var mens_extra;
		var cor1 = "#FF5B5B";
		var cor2 = "#0C9";
		
		campo = "nome"; if(!$("#"+campo).val()){ $("#"+campo).css('background-color',cor1); enviar = false; } else { $("#"+campo).css('background-color',cor2); }
		
		if(!enviar){
			alerta.html("É obrigatório preencher os campos marcados em vermelho!" + ( mens_extra ? "\n\nNOTA: " + mens_extra : ""));
			alerta.dialog("open");
			return false;
		}
		
		$("#"+campo).css('background-color',cor2);
	});
	
});