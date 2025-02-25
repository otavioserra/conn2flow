$(document).ready(function(){
	sep = "../../";
	
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
	
	$(".inteiro").numeric();
	
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
	});
	
	$("#codigo").keyup(function(e){
		var code = e.keyCode || e.which;
		var flag = true;
		
		switch(code){
			case 37:
			case 27:
			case 45:
				flag = false;
			break;
		}
		
		if(flag){
			var cartePos = doGetCaretPosition(this);
			if($("#codigo").val().match(/[^a-zA-Z0-9_-]/))cartePos--;
			var val = $("#codigo").val().replace(/[^a-zA-Z0-9_-]/g,'').toUpperCase();
			$("#codigo").val(val);
			doSetCaretPosition(this, cartePos);
		}
	});
	
	/*
	** Returns the caret (cursor) position of the specified text field.
	** Return value range is 0-oField.value.length.
	*/
	function doGetCaretPosition(oField){
		// Initialize
		var iCaretPos = 0;

		// IE Support
		if (document.selection){

			// Set focus on the element
			oField.focus ();

			// To get cursor position, get empty selection range
			var oSel = document.selection.createRange ();

			// Move selection start to 0 position
			oSel.moveStart ('character', -oField.value.length);

			// The caret position is selection length
			iCaretPos = oSel.text.length;
		}

		// Firefox support
		else if (oField.selectionStart || oField.selectionStart == '0')
		iCaretPos = oField.selectionStart;

		// Return results
		return (iCaretPos);
	}
	
	/*
	**  Sets the caret (cursor) position of the specified text field.
	**  Valid positions are 0-oField.length.
	*/
	function doSetCaretPosition(oField, iCaretPos){

		// IE Support
		if (document.selection) { 

			// Set focus on the element
			oField.focus();

			// Create empty selection range
			var oSel = document.selection.createRange ();

			// Move selection start and end to 0 position
			oSel.moveStart ('character', -oField.value.length);

			// Move selection start and end to desired position
			oSel.moveStart ('character', iCaretPos);
			oSel.moveEnd ('character', 0);
			oSel.select ();
		}

		// Firefox support
		else if (oField.selectionStart || oField.selectionStart == '0') {
			oField.selectionStart = iCaretPos;
			oField.selectionEnd = iCaretPos;
			oField.focus ();
		}
	}
});