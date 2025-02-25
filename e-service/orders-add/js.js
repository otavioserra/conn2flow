$(document).ready(function(){
	sep = "../../";
	var cor1 = '#F00'; // Vermelho
	var cor2 = '#0C6'; // Verde
	var cadastrar_email;
	var email_validador_contador = 0;
	
	$("#email").blur(function() {
		validar_email(false);
	});
	
	$(".servicos-quant").numeric();
	
	$(".cortesia-td").bind('click touchstart',function(event){
		event.stopPropagation();
		var $checkbox = $(this).find(':checkbox');
		$checkbox.attr('checked', !$checkbox.attr('checked'));
	});
	
	$("#cortesia,#presente").bind('click touchstart',function(event){
		event.stopPropagation();
	});
	
	$("#imprimir").bind('click touchstart',function(){
		window.open(sep+"includes/ecommerce/print.php","Imprimir","menubar=0,location=0,height=700,width=700");
	});
	
	$("#form").submit(function() {
		var enviar = true;
		var mostrar_dialog = true;
		var campo;
		var mens;
		var mens_extra;
		var cor1 = "#FF5B5B";
		var cor2 = "#0C9";
		
		campo = "servicos"; if(!$('#lista-servicos').val()){ $("#"+campo).css('background-color',cor1); enviar = false; } else { $("#"+campo).css('background-color',cor2); }
		campo = "layout"; if(!$("#layout_id").val()){ $("#"+campo).css('background-color',cor1); enviar = false; } else { $("#"+campo).css('background-color',cor2); }
		
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
	
	$(".servicos-excluir").live('click touchstart',function(){
		var id = $(this).attr('id').replace(/servico-excluir-/gi,'');
		
		var itens = $('#lista-servicos').val();
		var itens_after = '';
		
		var itens_arr = itens.split(',');
		for(var i=0;i<itens_arr.length;i++){
			if(itens_arr[i] == id){
				var continua = true;
			} else {
				itens_after = (itens_after.length > 0 ? itens_after+',':'' ) + itens_arr[i];
			}
		}
		
		$('#lista-servicos').val(itens_after);
		
		$('#servico-'+id).remove();
		
		if(itens_after.length == 0){
			$('#table-servicos').remove();
		}
	});
	
	var ajaxCache = {};
	
	$("#servicos").autocomplete({
		source: function(request, response) {
			//what are we searching for
			var query_id = $(this).attr('element').attr('id');
			//the cacheterm that we use to save it in the cache
			var cachedTerm = (request.term + '' + query_id) . toLowerCase();
			//if the data is in the cache and the data is not too long, use it
			
			$.ajax({
				url: url_name(),
				dataType: "json",
				data: {
					ajax: 1,
					query_id: query_id,
					query: request.term
				},
				success: function(data) {
					if(data){
						//cache the data for later
						ajaxCache[cachedTerm] = data;
						//map the data into a response that will be understood by the autocomplete widget
						response($.map(data, function(item) {
							return {
								label: item.value,
								value: item.value,
								id: item.id
							}
						}));
					}
				}
			});
		},
		//start looking at 3 characters because mysql's limit is 4
		minLength: 1,
		//when you have selected something
		select: function(event, ui) {
			//close the drop down
			
			var flag = false;
			
			if($('#table-servicos').length > 0 ){
				var cont = true;
			} else {
				var table = $('<table border="0" cellspacing="5" class="tabela_lista" id="table-servicos"><tr><td class="lista_header">Serviços</td><td class="lista_header">Quantidade</td><td class="lista_header">Opção</td></tr></table>');
				table.appendTo('#servicos-lista');
			}
			
			var itens = $('#lista-servicos').val();
			
			if(itens.length > 0){
				var itens_arr = itens.split(',');
				for(var i=0;i<itens_arr.length;i++){
					if(itens_arr[i] == ui.item.id){
						flag = true;
						break;
					}
				}
			}
			
			if(flag){
				alerta.html("<p>Não é possível adicionar o serviço <b>"+ui.item.value+"</b> mais de uma vez</p><p>Favor modificar a quantidade na lista de serviços para o valor desejado.</p>");
				alerta.dialog('open');
			} else {
				var item = $('<tr id="servico-'+ui.item.id+'"><td class="lista_cel">'+ui.item.value+'</td><td class="lista_cel" align="center"><input name="servico-quant-'+ui.item.id+'" type="text" id="servico-quant-'+ui.item.id+'" class="servicos-quant" value="1" size="3" maxlength="2" /></td><td class="lista_cel" align="center"><img src="../../images/icons/excluir.png" id="servico-excluir-'+ui.item.id+'" class="servicos-excluir"></td></tr></table>');
				
				item.appendTo('#table-servicos');
				
				$(".servicos-quant").numeric();
				
				$('#lista-servicos').val((itens.length > 0?itens+',':'')+ui.item.id);
			}
			
			ui.item.value = '';
			this.close;
		},
		//show the drop down
		open: function() {
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
		},
		//close the drop down
		close: function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
	
	var ajaxCache2 = {};
	
	$("#layout").autocomplete({
		source: function(request, response) {
			//what are we searching for
			var query_id = $(this).attr('element').attr('id');
			//the cacheterm that we use to save it in the cache
			var cachedTerm = (request.term + '' + query_id) . toLowerCase();
			//if the data is in the cache and the data is not too long, use it
			
			$.ajax({
				url: url_name(),
				dataType: "json",
				data: {
					ajax: 1,
					query_id: query_id,
					query: request.term
				},
				success: function(data) {
					if(data){
						//cache the data for later
						ajaxCache2[cachedTerm] = data;
						//map the data into a response that will be understood by the autocomplete widget
						response($.map(data, function(item) {
							return {
								label: item.value,
								value: item.value,
								id: item.id
							}
						}));
					}
				}
			});
		},
		//start looking at 3 characters because mysql's limit is 4
		minLength: 1,
		//when you have selected something
		select: function(event, ui) {
			//close the drop down
			
			$('#layout_id').val(ui.item.id);
			
			this.close;
		},
		//show the drop down
		open: function() {
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
		},
		//close the drop down
		close: function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
	
});