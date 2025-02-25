$(document).ready(function(){
	sep = "../../";
	
	$(".lista_cel").hover(
		function(){
			$(this).css('background-image', 'url(../../images/admin/box-cinza.png)');
			//$(this).find('div.lista_header').css('background-color', '#666666');
			$(this).find('div.nao_mudar_cor').css('background-image', 'url(../../images/admin/box-branco.png)');
		},
		function(){
			$(this).css('background-image', 'url(../../images/admin/box-branco.png)');
			//$(this).find('div.lista_header').css('background-color', '#86C525');
		}
	);
	
	$('.lista_cel').click(function(e){
		if(e.target.nodeName != 'INPUT'){
			var input = $(this).find('input');
			
			if(input.attr('checked'))
				input.attr('checked', false);
			else
				input.attr('checked', true);
		}
	});
	
	$('.div_cel2').click(function(e){
		e.stopPropagation();
		if(e.target.nodeName != 'INPUT'){
			var input = $(this).find('input');
			
			if(input.attr('checked'))
				input.attr('checked', false);
			else
				input.attr('checked', true);
			
			var classes = input.attr('class');
			var classe = classes.replace(/operacao_chk /gi,'');
			var checked = false;
			
			if(input.attr("checked")){
				$("#"+classe).attr("checked",true);
			} else {
				$("."+classe+":checked").each(function (){
					checked = true;
				});
				
				if(!checked){
					$("#"+classe).attr("checked",false);
				}
			}
		}
	});
	
	$(".modulo_chk").click(function (){
		var id_aux = this.id;
		var id = id_aux.replace(/modulo_/gi,'');
		
		if($("#"+this.id).attr("checked")){
			$(".modulo_"+id).attr("checked",true);
		} else {
			$(".modulo_"+id).attr("checked",false);
		}
	});
	
	$(".operacao_chk").click(function (){
		var classes = $(this).attr('class');
		var classe = classes.replace(/operacao_chk /gi,'');
		var checked = false;
		
		if($("#"+this.id).attr("checked")){
			$("#"+classe).attr("checked",true);
		} else {
			$("."+classe+":checked").each(function (){
				checked = true;
			});
			
			if(!checked){
				$("#"+classe).attr("checked",false);
			}
		}
	});
	
	$("#form").submit(function() {
		var checkeds = false;
		var enviar = true;
		var campo;
		var mens;
		var mens_extra;
		var cor1 = "#FF5B5B";
		var cor2 = "#0C9";
		
		campo = "nome"; if(!$("#"+campo).val()){ $("#"+campo).css('background-color',cor1); enviar = false; } else { $("#"+campo).css('background-color',cor2); }
		
		if(!enviar){
			alerta.html("<p>É obrigatório preencher os campos marcados em vermelho!</p>" + ( mens_extra ? "<p>NOTA: " + mens_extra + "</p>" : ""));
			alerta.dialog('open');
			return false;
		}
		
		$(".modulo_chk:checked").each(function (){
			checkeds = true;
		});
		
		if(!checkeds){
			alerta.html("<p>É obrigatório escolher pelo menos um módulo!</p>");
			alerta.dialog('open');
			return false;
		}
	});
	
	
});