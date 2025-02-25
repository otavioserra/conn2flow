window.aplicar_scripts_ecommerce = function(params){
	(function($) {
		$.extend($.fn, {
			makeCssInline: function() {
				this.each(function(idx, el) {
					var style = el.style;
					var properties = [];
					for(var property in style) { 
						if($(this).css(property)) {
							properties.push(property + ':' + $(this).css(property));
						}
					}
					this.style.cssText = properties.join(';');
					$(this).children().makeCssInline();
				});
			}
		});
	}(jQuery));

	if($('#_indique-form').length){
		Recaptcha.create(ajax_vars.recaptcha_public_key, 'recaptcha_div', {
			lang : 'pt',
			theme: "clean"
		});
		
		$(".telefone").mask("(99) 9999-9999?9");
		
		$("#_indique-enviar").bind('click touchstart',function(){
			var enviar = true;
			var campo;
			var post;
			var mens;
			var campos = Array();
			var posts = Array();
			var form_id = '_indique-form';
			var opcao = '';
			var href = '';
			var limpar_campos = true;
			var mudar_pagina = false;
			
			campo = '_indique-nome'; mens = "&Eacute; obrigat&oacute;rio definir o Seu Nome!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			campo = '_indique-email'; mens = "&Eacute; obrigat&oacute;rio definir o Seu E-mail!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			campo = '_indique-nome_amigo'; mens = "&Eacute; obrigat&oacute;rio definir o Nome amigo(a)!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			campo = '_indique-email_amigo'; mens = "&Eacute; obrigat&oacute;rio definir o E-mail amigo(a)!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			// Checar email
			campo = '_indique-email'; mens = "E-mail inv&aacute;lido, preencha o campo de e-mail v&aacute;lido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			campo = '_indique-email_amigo'; mens = "E-mail inv&aacute;lido, preencha o campo de e-mail v&aacute;lido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			if(!enviar){
				return false;
			} else {
				$.ajax({
					type: 'POST',
					url: url_name(),
					data: { ajax : 'sim' , recaptcha : 'sim' , recaptcha_challenge_field : Recaptcha.get_challenge() , recaptcha_response_field : Recaptcha.get_response() },
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						
						if(txt == 'sim'){
							window.form_serialize = $('#'+form_id).serialize();
							window.enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
							$('#'+form_id)[0].reset();
							cadastrar_usuario = false;
							cadastrar_senha = false;
						} else {
							Recaptcha.reload();
							mens = "<p>C&oacute;digo de valida&ccedil;&atilde;o <b style=\"color:red;\">inv&aacute;lido</b>!<p></p>Favor preencher o c&oacute;digo de valida&ccedil;&atilde;o novamente!</p>";
							$.alerta_open(mens,false,false);
						}
					},
					error: function(txt){
						
					}
				});
			}
		});
		
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
		
		function url_name(){
			var url_aux = location.pathname;
			var url_parts;
			
			url_parts = url_aux.split('/');
			
			if(url_parts[url_parts.length-1])
				return url_parts[url_parts.length-1];
			else
				return '.';
		}
	}
	
	if($('#_duvidas-form').length){
		/* Recaptcha.create(ajax_vars.recaptcha_public_key, 'recaptcha_div2', {
			lang : 'pt',
			theme: "clean"
		}); */
		
		$(".telefone").mask("(99) 9999-9999?9");
		
		$("#_duvidas-enviar").bind('click touchstart',function(){
			var enviar = true;
			var campo;
			var post;
			var mens;
			var campos = Array();
			var posts = Array();
			var form_id = '_duvidas-form';
			var opcao = '';
			var href = '';
			var limpar_campos = true;
			var mudar_pagina = false;
			
			campo = '_duvidas-nome'; mens = "&Eacute; obrigat&oacute;rio definir o Seu Nome!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			campo = '_duvidas-email'; mens = "&Eacute; obrigat&oacute;rio definir o E-mail!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			campo = '_duvidas-telefone'; mens = "&Eacute; obrigat&oacute;rio definir o Telefone!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			campo = '_duvidas-cidade'; mens = "&Eacute; obrigat&oacute;rio definir a Cidade!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			campo = '_duvidas-duvida'; mens = "&Eacute; obrigat&oacute;rio definir a D&uacute;vida!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			// Checar email
			campo = '_duvidas-email'; mens = "E-mail inv&aacute;lido, preencha o campo de e-mail v&aacute;lido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
			
			if(!enviar){
				return false;
			} else {
				$.ajax({
					type: 'POST',
					url: url_name(),
					data: { ajax : 'sim' , recaptcha : 'sim' , recaptcha_challenge_field : Recaptcha.get_challenge() , recaptcha_response_field : Recaptcha.get_response() },
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						
						if(txt == 'sim'){
							window.form_serialize = $('#'+form_id).serialize();
							window.enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
							$('#'+form_id)[0].reset();
							cadastrar_usuario = false;
							cadastrar_senha = false;
						} else {
							Recaptcha.reload();
							mens = "<p>C&oacute;digo de valida&ccedil;&atilde;o <b style=\"color:red;\">inv&aacute;lido</b>!<p></p>Favor preencher o c&oacute;digo de valida&ccedil;&atilde;o novamente!</p>";
							$.alerta_open(mens,false,false);
						}
					},
					error: function(txt){
						
					}
				});
			}
		});
		
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
		
		function url_name(){
			var url_aux = location.pathname;
			var url_parts;
			
			url_parts = url_aux.split('/');
			
			if(url_parts[url_parts.length-1])
				return url_parts[url_parts.length-1];
			else
				return '.';
		}
	}
	
	if($('#_pedido-cont').length){
		if(!global_vars.pedido){
			global_vars.pedido = {};
		}
		
		var stored = localStorage['pedido_itens'];
		
		if (stored){
			var item_cache = {};
			
			item_cache.itens = JSON.parse(stored);
			
			if(!global_vars.pedido.itens_tr){
				global_vars.pedido.itens_tr = $('<table></table>');
			
				for(var i=0;i<item_cache.itens.length;i++){
					item_cache.item = item_cache.itens[i];
					item_cache.item_subtotal = item_cache.item.quant*item_cache.item.preco;
					
					item_cache.item_tr = '<tr id="_carrinho-item-'+item_cache.item.id+'">';
					//item_cache.item_tr = item_cache.item_tr + '	<td><a href="'+item_cache.item.href+'">'+item_cache.item.titulo+'</a></td>';
					item_cache.item_tr = item_cache.item_tr + '	<td><b>'+item_cache.item.titulo+'</b>' + (item_cache.item.validade? '<br>Validade de <b>'+item_cache.item.validade+'</b> dia(s)' : '') + (item_cache.item.observacao? '<br>Observa&ccedil;&atilde;o: '+item_cache.item.observacao : '') + '</td>';
					item_cache.item_tr = item_cache.item_tr + '	<td>'+item_cache.item.quant+'</td>';
					item_cache.item_tr = item_cache.item_tr + '	<td style="min-width:110px;">'+item_cache.item.preco.formatMoney(2, "R$ ", ".", ",")+'</td>';
					item_cache.item_tr = item_cache.item_tr + '	<td style="min-width:110px;">'+item_cache.item_subtotal.formatMoney(2, "R$ ", ".", ",")+'</td>';
					item_cache.item_tr = item_cache.item_tr + '</tr>';
					
					global_vars.pedido.itens_tr.append(item_cache.item_tr);
				}
			}
		}
		
		$('#_pedido-cont').append(global_vars.pedido.itens_tr.html());
		
		function atualizar_valores_2(){
			var itens = item_cache.itens;
			var valor_total = 0;
			var quantidades = 0;
			
			for(var i=0;i<itens.length;i++){
				valor_total = valor_total + (itens[i].preco * itens[i].quant);
				quantidades = quantidades + itens[i].quant;
			}
			
			$('#_pedido-total').html(valor_total.formatMoney(2, "R$ ", ".", ","));
			$('#_pedido-num-itens').html(itens.length);
		}
		
		atualizar_valores_2();
	}
	
	if($('#_endereco_entrega').length){
		$("#_carrinho-continuar-2").bind('click touchstart',function(){
			if(
				$('#_endereco_entrega-dest_uf').val() &&
				$('#_endereco_entrega-dest_cidade').val() &&
				$('#_endereco_entrega-dest_bairro').val() &&
				$('#_endereco_entrega-dest_num').val() &&
				$('#_endereco_entrega-dest_endereco').val()
			){
				window.enviar_form_simples('_endereco_entrega');
			} else {
				$.alerta_open('<p>&egrave; obrigat&oacute;rio preencher o endere&ccedil;o, n&uacute;mero, bairro, cidade e uf antes de enviar.</p>',false,false);
			}
		});
	}
	
	if($('#_carrinho-cont').length){
		var stored = localStorage['ecommerce_itens'];
		
		if(ajax_vars.ecommerce_limpar && global_vars.ecommerce){
			global_vars.ecommerce = {};
			localStorage['ecommerce_itens'] = JSON.stringify(Array());
		}
		
		if(!global_vars.ecommerce){
			global_vars.ecommerce = {};
		}
		
		if($("._carrinho-cep").length > 0){
			$("._carrinho-cep").mask("99.999-999");
			
			global_vars.ecommerce.produtos = true;
		}
		
		if(!global_vars.ecommerce.etapa)global_vars.ecommerce.etapa = 1;
		if(!global_vars.ecommerce.frete)global_vars.ecommerce.frete = 0;
		if(!global_vars.ecommerce.frete_codigo)global_vars.ecommerce.frete_codigo = '0';
		if(!global_vars.ecommerce.cupom_desconto_txt)global_vars.ecommerce.cupom_desconto_txt = '';
		if(!global_vars.ecommerce.cupom_desconto)global_vars.ecommerce.cupom_desconto = 0;
		
		if(!global_vars.ecommerce.itens){
			if (stored){
				global_vars.ecommerce.itens = JSON.parse(stored);
			} else {
				global_vars.ecommerce.itens = Array();
			}
		}
		
		if(!global_vars.ecommerce.itens_tr){
			global_vars.ecommerce.itens_tr = $('<table></table>');
			
			if (stored){
				var item_cache = {};
				
				item_cache.itens = global_vars.ecommerce.itens;
				
				for(var i=0;i<item_cache.itens.length;i++){
					item_cache.item = item_cache.itens[i];
					item_cache.item_subtotal = item_cache.item.quant*item_cache.item.preco;
					
					item_cache.item_tr = '<tr id="_carrinho-item-'+item_cache.item.id+'">';
					item_cache.item_tr = item_cache.item_tr + '	<td><a href="'+item_cache.item.href+'">'+item_cache.item.titulo+'</a>' + (item_cache.item.validade? '<br>Validade de <b>'+item_cache.item.validade+'</b> dia(s)' : '') + (item_cache.item.observacao? '<br>Observa&ccedil;&atilde;o: '+item_cache.item.observacao : '') + '</td>';
					item_cache.item_tr = item_cache.item_tr + '	<td>'+item_cache.item.quant+'</td>';
					item_cache.item_tr = item_cache.item_tr + '	<td><img src="'+variaveis_js.site_raiz+(variaveis_js.ecommerce_img_deletar?variaveis_js.ecommerce_img_deletar:'images/icons/ecom-deletar-item.gif')+'" class="_carrinho-excluir-item"></td>';
					item_cache.item_tr = item_cache.item_tr + '	<td style="min-width:110px;">'+item_cache.item.preco.formatMoney(2, "R$ ", ".", ",")+'</td>';
					item_cache.item_tr = item_cache.item_tr + '	<td style="min-width:110px;">'+item_cache.item_subtotal.formatMoney(2, "R$ ", ".", ",")+'</td>';
					item_cache.item_tr = item_cache.item_tr + '</tr>';
					
					global_vars.ecommerce.itens_tr.append(item_cache.item_tr);
				}
			}
		}
		
		$('#_carrinho-cont').append(global_vars.ecommerce.itens_tr.html());
		$('#_carrinho-etapa').html(global_vars.ecommerce.etapa);
		
		if(global_vars.ecommerce.atualizar_item){
			var item_atualizar = global_vars.ecommerce.atualizar_item;
			var item = global_vars.ecommerce.itens[item_atualizar.item_num];
			var id = item.id;
			
			global_vars.ecommerce.itens[item_atualizar.item_num].quant = item_atualizar.quant;
			
			var item_subtotal = item_atualizar.quant * global_vars.ecommerce.itens[item_atualizar.item_num].preco;
			
			$('#_carrinho-item-'+id+' td:nth-child(2)').html(item_atualizar.quant);
			$('#_carrinho-item-'+id+' td:nth-child(5)').html(item_subtotal.formatMoney(2, "R$ ", ".", ","));
			global_vars.ecommerce.itens_tr.find('#_carrinho-item-'+id+' td:nth-child(2)').html(item_atualizar.quant);
			global_vars.ecommerce.itens_tr.find('#_carrinho-item-'+id+' td:nth-child(5)').html(item_subtotal.formatMoney(2, "R$ ", ".", ","));
			
			global_vars.ecommerce.atualizar_item = null;
			global_vars.ecommerce.frete = 0;
		}
		
		if(global_vars.ecommerce.item_add){
			var item = global_vars.ecommerce.item_add;
			
			var item_subtotal = item.quant*item.preco;
			
			var item_tr = '<tr id="_carrinho-item-'+item.id+'">';
			item_tr = item_tr + '	<td><a href="'+item.href+'">'+item.titulo+'</a>' + (item.validade? '<br>Validade de <b>'+item.validade+'</b> dia(s)' : '') + (item.observacao? '<br>Observa&ccedil;&atilde;o: '+item.observacao : '') + '</td>';
			item_tr = item_tr + '	<td>'+item.quant+'</td>';
			item_tr = item_tr + '	<td><img src="'+variaveis_js.site_raiz+(variaveis_js.ecommerce_img_deletar?variaveis_js.ecommerce_img_deletar:'images/icons/ecom-deletar-item.gif')+'" class="_carrinho-excluir-item"></td>';
			item_tr = item_tr + '	<td>'+item.preco.formatMoney(2, "R$ ", ".", ",")+'</td>';
			item_tr = item_tr + '	<td>'+item_subtotal.formatMoney(2, "R$ ", ".", ",")+'</td>';
			item_tr = item_tr + '</tr>';
			
			global_vars.ecommerce.itens_tr.append(item_tr);
			$('#_carrinho-cont').append(item_tr);
			
			global_vars.ecommerce.itens.push(item);
			global_vars.ecommerce.item_add = null;
			global_vars.ecommerce.frete = 0;
		}
		
		$("._carrinho-excluir-item").bind('click touchstart',function(){
			var id_str = $(this).parent().parent().attr('id');
			var id = id_str.replace(/_carrinho-item-/gi,'');
			var itens = global_vars.ecommerce.itens;
			
			for(var i=0;i<itens.length;i++){
				if(itens[i].id == id){
					global_vars.ecommerce.itens.splice(i, 1);
					break;
				}
			}
			
			$('#'+id_str).remove();
			global_vars.ecommerce.itens_tr.find('#'+id_str).remove();
			atualizar_valores();
			
			localStorage['ecommerce_itens'] = JSON.stringify(global_vars.ecommerce.itens);
		});
		
		$("#_carrinho-frete-button").bind('click touchstart',function(){
			var itens = global_vars.ecommerce.itens;
			var produtos = '';
			var quantidades = '';
			
			for(var i=0;i<itens.length;i++){
				produtos = produtos + (produtos.length > 0 ? ',':'') + itens[i].id;
				quantidades = quantidades + (quantidades.length > 0 ? ',':'') + itens[i].quant;
			}
			
			if($('#_carrinho-frete-input').val()){
				$.ajax({
					type: 'POST',
					url: '.',
					data: { ajax : 'sim' , opcao : 'calcular-frete' , cep : $('#_carrinho-frete-input').val() , produtos : produtos , quantidades : quantidades },
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						
						if(
							txt.charAt(0) == "{" || 
							txt.charAt(0) == "["
						){
							var dados = eval('(' + txt + ')');
							var frete = 0;
							var frete_codigo = '3';
							var opcao_retirar_local = $('<li class="_carrinho-frete-sel2" data-valor="0" data-codigo="0"><div class="_carrinho-frete-result-tit">RETIRAR NO LOCAL</div><div class="_carrinho-frete-result-preco">R$ 0,00</div><div class="clear"></div></li>');
							var opcao_frete = '';
							
							$("#_carrinho-frete-result").html('');
							
							for(var i=0;i<dados.fretes.length;i++){
								dados.fretes[i].valor = parseFloat(dados.fretes[i].valor);
								if(i==0){
									frete = dados.fretes[i].valor;
									frete_codigo = dados.fretes[i].codigo;
								}
								
								opcao_frete = $('<li class="_carrinho-frete-sel'+(i==0?'':'2')+'" data-valor="'+dados.fretes[i].valor+'" data-codigo="'+dados.fretes[i].codigo+'"><div class="_carrinho-frete-result-tit">'+dados.fretes[i].tipo+'</div><div class="_carrinho-frete-result-preco">'+dados.fretes[i].valor.formatMoney(2, "R$ ", ".", ",")+'</div><div class="_carrinho-frete-result-prazo">'+dados.fretes[i].prazo+(parseInt(dados.fretes[i].prazo) > 1 ? ' dias &uacute;teis' : ' dia &uacute;til')+'</div><div class="clear"></div></li>');
								opcao_frete.appendTo("#_carrinho-frete-result");
							}
							
							if(variaveis_js.ecommerce_frete_observacao){
								if($('#_carrinho-frete-observacao').length == 0){
									var frete_observacao = $('<div class="clear"></div><div id="_carrinho-frete-observacao">'+variaveis_js.ecommerce_frete_observacao+'</div>');
									
									$("#_carrinho-frete-result").after(frete_observacao);
								}
							}
							
							if(!variaveis_js.ecommerce_nao_retirar_no_local)opcao_retirar_local.appendTo("#_carrinho-frete-result");
							global_vars.ecommerce.frete = frete;
							global_vars.ecommerce.frete_codigo = frete_codigo;
							
							$("#_carrinho-frete-result li").bind('click touchstart',function(){
								$(this).parent().find('li').removeClass('_carrinho-frete-sel');
								$(this).parent().find('li').addClass('_carrinho-frete-sel2');
							
								$(this).removeClass('_carrinho-frete-sel2');
								$(this).addClass('_carrinho-frete-sel');
								
								var frete_local = parseFloat($(this).attr('data-valor'));
								var codigo_local = parseFloat($(this).attr('data-codigo'));
								
								global_vars.ecommerce.frete = frete_local;
								global_vars.ecommerce.frete_codigo = codigo_local;
							
								atualizar_valores();
							});
							
							atualizar_valores();
						} else {
							$.alerta_open('<p>O site dos CORREIOS n&atilde;o est&aacute; respondendo. Tente novamente mais tarde.</p>',false,false);
						}
					},
					error: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
					}
				});
			}
		});
		
		$("#_carrinho-continuar").bind('click touchstart',function(){
			var itens = global_vars.ecommerce.itens;
			var frete_codigo_str = (global_vars.ecommerce.frete_codigo == '0' ? '' : global_vars.ecommerce.frete_codigo);
			var intens_str = '';
			var cupom_str = $('#_carrinho-cupom-value').val();
			var cep_str = $('#_carrinho-frete-input').val();
			
			if(!$('#_carrinho-cupom-input').val()){
				if(itens.length > 0){
					var continuar = true;
					if(global_vars.ecommerce.produtos){
						if(!cep_str){
							continuar = false;
						}
					}
					
					if(continuar){
						for(var i=0;i<itens.length;i++){
							intens_str = intens_str + (intens_str.length > 0 ? ';':'') + itens[i].id + ',' + itens[i].quant;
						}
						
						if($('#_carrinho-form').length){
							$('#_carrinho-form-itens').val(intens_str);
							$('#_carrinho-form-cupom').val(cupom_str);
							$('#_carrinho-form-cep').val(cep_str);
							$('#_carrinho-form-frete_codigo').val(frete_codigo_str);
						} else {
							var form = $('<form id="_carrinho-form"></form>');
							var opcao = $('<input type="hidden" name="opcao" value="form-autenticar">');
							var input = $('<input type="hidden" name="ecommerce-itens" id="_carrinho-form-itens" value="'+intens_str+'">');
							var cupom = $('<input type="hidden" name="ecommerce-cupom" id="_carrinho-form-cupom" value="'+cupom_str+'">');
							var cep = $('<input type="hidden" name="ecommerce-cep" id="_carrinho-form-cep" value="'+cep_str+'">');
							var frete_codigo = $('<input type="hidden" name="ecommerce-frete_codigo" id="_carrinho-form-frete_codigo" value="'+frete_codigo_str+'">');
							
							input.appendTo(form);
							opcao.appendTo(form);
							cupom.appendTo(form);
							cep.appendTo(form);
							frete_codigo.appendTo(form);
							form.appendTo($('#cont_principal'));
						}
						
						localStorage['pedido_itens'] = localStorage['ecommerce_itens'];
						
						window.enviar_form_simples('_carrinho-form');
					} else {
						$.alerta_open('<p>&Eacute; necess&aacute;rio definir o CEP de destino antes de Finalizar a Compra.</p>',false,false);
					}
				} else {
					$.alerta_open('<p>&Eacute; necess&aacute;rio pelo menos incluir um servi&ccedil;o/produto antes de Finalizar a Compra.</p>',false,false);
				}
			} else {
				$.alerta_open('<p>&Eacute; necess&aacute;rio clicar em <b>Validar Cupom</b> antes de <b>Finalizar Compra</b>.</p>',false,false);
			}
		});
		
		$('#_carrinho-cupom-input').keyup(function(e){
			var code = e.keyCode || e.which;
			var flag = true;
			
			if($(this).val()){
				$('#_carrinho-validar-cupom').show();
			} else {
				$('#_carrinho-validar-cupom').hide();
			}
			
			switch(code){
				case 37:
				case 27:
				case 45:
					flag = false;
				break;
			}
			
			if(flag){
				var cartePos = doGetCaretPosition(this);
				if($(this).val().match(/[^a-zA-Z0-9_-]/))cartePos--;
				var val = $(this).val().replace(/[^a-zA-Z0-9_-]/g,'').toUpperCase();
				$(this).val(val);
				doSetCaretPosition(this, cartePos);
			}
		});
		
		$("#_carrinho-validar-cupom").bind('click touchstart',function(){
			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'ecommerce-cupom' , cupom : $('#_carrinho-cupom-input').val() },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					
					if(
						txt.charAt(0) == "{" || 
						txt.charAt(0) == "["
					){
						var dados = eval('(' + txt + ')');
						
						if(dados.erro){
							$.alerta_open(dados.erro,false,false);
							$('#_carrinho-cupom-value').val('');
							$('#_carrinho-cupom-cont').hide();
						} else {
							global_vars.ecommerce.cupom_desconto_txt = $('#_carrinho-cupom-input').val();
							global_vars.ecommerce.cupom_desconto = parseInt(dados.desconto);
							
							atualizar_valores();
						}
						
						$('#_carrinho-cupom-input').val('');
						$('#_carrinho-validar-cupom').hide();
					} else {
						console.log(txt);
					}
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$("#_carrinho-continuar-comprando").bind('click touchstart',function(){
			window.link_trigger.attr('href',document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'loja-online').trigger('click');
		});
		
		function atualizar_valores(){
			var itens = global_vars.ecommerce.itens;
			var frete = global_vars.ecommerce.frete;
			var cupom_desconto = global_vars.ecommerce.cupom_desconto;
			var cupom_desconto_txt = global_vars.ecommerce.cupom_desconto_txt;
			
			var valor_total = 0;
			var valor_total_produtos = 0;
			var valor_total_com_frete = 0;
			var quantidades = 0;
			
			if(cupom_desconto > 0){
				$('#_carrinho-cupom-value').val(cupom_desconto_txt);
				$('#_carrinho-cupom-inputed').html(cupom_desconto_txt);
				$('#_carrinho-cupom-desconto').html(cupom_desconto+' %');
				
				var valor_total_sem_desconto = 0;
				
				for(var i=0;i<itens.length;i++){
					if(itens[i].desconto){
						valor_total_sem_desconto = valor_total_sem_desconto + (itens[i].preco * itens[i].quant);
					} else {
						valor_total = valor_total + (itens[i].preco * itens[i].quant);
					}
					quantidades = quantidades + itens[i].quant;
				}
				
				valor_total_produtos = valor_total + valor_total_sem_desconto;
				
				valor_total = valor_total - ((valor_total*parseInt(cupom_desconto)) / 100);
				
				valor_total = valor_total + valor_total_sem_desconto;
				
				$('#_carrinho-cupom-valor').html(valor_total.formatMoney(2, "R$ ", ".", ","));
				$('#_carrinho-cupom-cont').show();
				
				if(valor_total_sem_desconto > 0)$('#_carrinho-cupom-nao-aplicavel').show();
			} else {
				for(var i=0;i<itens.length;i++){
					valor_total = valor_total + (itens[i].preco * itens[i].quant);
					quantidades = quantidades + itens[i].quant;
				}
				
				valor_total_produtos = valor_total;
			}
			
			valor_total_com_frete = valor_total;
			
			if(frete > 0) valor_total_com_frete = valor_total_com_frete + frete;
			
			$('#_carrinho-total').html(valor_total_produtos.formatMoney(2, "R$ ", ".", ","));
			$('#_carrinho-total-pedido').html(valor_total_com_frete.formatMoney(2, "R$ ", ".", ","));
			$('#_carrinho-num-itens').html(global_vars.ecommerce.itens.length);
			
			if(valor_total > 0){
				if($('#_carrinho-widget-holder-quant').length > 0){
					$('#_carrinho-widget-quant').html(quantidades);
					$('#_carrinho-widget-holder-quant').show();
				}
				if($('#_carrinho-widget-holder-val').length > 0){
					$('#_carrinho-widget-val').html($('#_carrinho-total').html());
					$('#_carrinho-widget-holder-val').show();
				}
				if($('#_carrinho-widget-holder-empty').length > 0){
					$('#_carrinho-widget-holder-empty').hide();
				}
			} else {
				if(variaveis_js.ecommerce_carrinho_quant_show){
					$('#_carrinho-widget-quant').html('0');
					$('#_carrinho-widget-holder-quant').show();
				} else {
					if($('#_carrinho-widget-holder-quant').length > 0){
						$('#_carrinho-widget-holder-quant').hide();
					}
				}
				if($('#_carrinho-widget-holder-val').length > 0){
					$('#_carrinho-widget-holder-val').hide();
				}
				if($('#_carrinho-widget-holder-empty').length > 0){
					$('#_carrinho-widget-holder-empty').show();
				}
			}
		}
		
		atualizar_valores();
		localStorage['ecommerce_itens'] = JSON.stringify(global_vars.ecommerce.itens);
		
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
	}
	
	if($('#_voucher-cont').length > 0){
		if($( "#_voucher-lista-voucher option:selected" ).val() == '1'){
			$( "#_voucher-lista-servicos-cont").show();
		} else {
			$( "#_voucher-lista-servicos-cont").hide();
		}
	
		$("#_voucher-imprimir").bind('click touchstart',function(){
			window.open(variaveis_js.site_raiz+"includes/ecommerce/print.php","Imprimir","menubar=0,location=0,height=700,width=700");
		});
		
		$("#_voucher-alterar-campos").bind('click touchstart',function(){
			$('#_voucher-cont').hide();
			$('#_voucher-form-presente').show();
			$('#_voucher-form-email').hide();
			$('#_voucher-lay-concluir').hide();
			$('#_voucher-lay-layouts').hide();
		});
		
		$("#_voucher-visulizar").bind('click touchstart',function(){
			$('#_voucher-cont').show();
			$('#_voucher-form-presente').hide();
			$('#_voucher-form-email').hide();
			$('#_voucher-lay-concluir').hide();
			$('#_voucher-lay-layouts').hide();
		});
		
		$("#_voucher-enviar-email").bind('click touchstart',function(){
			$('#_voucher-cont').hide();
			$('#_voucher-form-presente').hide();
			$('#_voucher-form-email').show();
			$('#_voucher-lay-concluir').hide();
			$('#_voucher-lay-layouts').hide();
		});
		
		$("#_voucher-concluir").bind('click touchstart',function(){
			$('#_voucher-cont').hide();
			$('#_voucher-form-presente').hide();
			$('#_voucher-form-email').hide();
			$('#_voucher-lay-concluir').show();
			$('#_voucher-lay-layouts').hide();
		});
		
		$("#_voucher-tema").bind('click touchstart',function(){
			$('#_voucher-cont').hide();
			$('#_voucher-form-presente').hide();
			$('#_voucher-form-email').hide();
			$('#_voucher-lay-concluir').hide();
			$('#_voucher-lay-layouts').show();
		});
		
		$("._voucher-temas").bind('click touchstart',function(){
			var id = $(this).attr('data-id');
			var id_pedidos = $('#_voucher-lay-layouts').attr('data-id');

			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'voucher-temas' , id : id , id_pedidos : id_pedidos },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					global_vars.link_nao_mudar_scroll = true;
					$.link_trigger('voucher');
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$("#_voucher-lista-pedidos").bind('change',function(){
			var id = $(this).val();

			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'voucher-pedidos' , id : id },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					global_vars.link_nao_mudar_scroll = true;
					$.link_trigger('voucher');
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$("#_voucher-lista-voucher").bind('change',function(){
			var id = $("#_voucher-lista-pedidos").val();
			
			if($(this).val() == '1'){
				$( "#_voucher-lista-servicos-cont").show();
			} else {
				$( "#_voucher-lista-servicos-cont").hide();
			}

			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'voucher-pedidos' , voucher : 'sim' , voucher_opcao : $(this).val() , id : id },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					global_vars.link_nao_mudar_scroll = true;
					$.link_trigger('voucher');
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$("#_voucher-lista-servicos").bind('change',function(){
			var id = $("#_voucher-lista-pedidos").val();

			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'voucher-pedidos' , servico : 'sim' , servico_opcao : $(this).val() , id : id },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					global_vars.link_nao_mudar_scroll = true;
					$.link_trigger('voucher');
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$("#_voucher-para-presente,#_voucher-para-voce").bind('click touchstart',function(){
			var flag = $("#_voucher-presente-flag").val();
			
			if(flag == '1'){
				$("#_voucher-presente-flag").val('2');
				$('#_voucher-cont').hide();
				$('#_voucher-form-presente').show();
				$('#_voucher-form-email').hide();
				$('#_voucher-lay-concluir').hide();
				$('#_voucher-lay-layouts').hide();
			} else {
				$("#_voucher-presente-flag").val('1');
				$('#_voucher-cont').show();
				$('#_voucher-form-presente').hide();
				$('#_voucher-form-email').hide();
				$('#_voucher-lay-concluir').hide();
				$('#_voucher-lay-layouts').hide();
			}
			
			voucher_mudar_campos();
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'voucher-presente' , flag : flag },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					
					if(flag == '2'){
						global_vars.link_nao_mudar_scroll = true;
						$.link_trigger('voucher');
					}
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$("#_voucher-form-presente").bind('submit',function() {
			var enviar = true;
			var campo;
			var mens;
			
			campo = "_voucher-form-presente-de"; mens = "Preencha o campo De"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "_voucher-form-presente-para"; mens = "Preencha o campo Para"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "_voucher-form-presente-mensagem"; mens = "Preencha o campo Mensagem"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			
			if(enviar){
				window.enviar_form_simples('_voucher-form-presente');
			}
			
			return false;
		});
		
		function voucher_mudar_campos(){
			var flag = $("#_voucher-presente-flag").val();
			
			if(flag == '2'){
				$('#_voucher-lay-destinatario').show();
				$('#_voucher-alterar-campos').show();
				$('#_voucher-para-voce').show();
				$('#_voucher-para-presente').hide();
				$('#_voucher-lay-concluir').hide();
				$('#_voucher-lay-layouts').hide();
			} else {
				$('#_voucher-lay-destinatario').hide();
				$('#_voucher-alterar-campos').hide();
				$('#_voucher-para-voce').hide();
				$('#_voucher-para-presente').show();
				$('#_voucher-lay-concluir').hide();
				$('#_voucher-lay-layouts').hide();
			}
		}
		
		voucher_mudar_campos();
		
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
		
		$("#_voucher-botao-enviar-email").bind('click touchstart',function(){
			var campo = '_voucher-email'
			if($("#"+campo).val()){
				var email = $("#"+campo).val();
				var mens;
				
				if(!checkMail(email)){
					mens = "E-mail incorreto.";
					$.alerta_open(mens,false,false);
					$("#"+campo).addClass('input-vazio');
				} else {
					$("#"+campo).removeClass('input-vazio');
					var email_cont = $('<div></div>');
					
					email_cont.css('position','absolute');
					email_cont.css('top','-11000px');
					email_cont.css('left','50px');
					email_cont.css('backgroundColor','#CCC');
					email_cont.width(1000);
					email_cont.height(10000);
					
					email_cont.html($('#_voucher-cont').html());
					email_cont.appendTo('body');
					email_cont.makeCssInline();
					
					if($("#_voucher-presente-flag").val() == '1')email_cont.find('#_voucher-lay-destinatario').remove();
					
					var email_txt = email_cont.html();
					
					$.ajax({
						type: 'POST',
						url: '.',
						data: { ajax : 'sim' , opcao : 'voucher-enviar-email' , voucher : email_txt , email : email , id_pedidos : $('#_voucher-lista-pedidos option:selected').val() },
						beforeSend: function(){
							$('#ajax_lendo').fadeIn(tempo_animacao);
						},
						success: function(txt){
							$('#ajax_lendo').fadeOut(tempo_animacao);
							
							var dados = eval('(' + txt + ')');
							
							if(dados.ok){
								mens = "E-mail enviado com sucesso.";
							} else {
								if(dados.erro){
									mens = dados.erro;
								} else {
									mens = "Ocorreu um erro indefinido.";
								}
							}
							
							$.alerta_open(mens,false,false);
							$('#_voucher-cont').show();
							$('#_voucher-form-presente').hide();
							$('#_voucher-form-email').hide();
						},
						error: function(txt){
							$('#ajax_lendo').fadeOut(tempo_animacao);
						}
					});
				}
			} else {
				mens = "Defina o Email antes de enviar!";
				$.alerta_open(mens,false,false);
				$("#"+campo).addClass('input-vazio');
			}
		});
		
		$("#_voucher-concluir").bind('click touchstart',function(){
			var mens;
			
			var email_cont = $('<div></div>');
			
			email_cont.css('position','absolute');
			email_cont.css('top','-11000px');
			email_cont.css('left','50px');
			email_cont.css('backgroundColor','#CCC');
			email_cont.width(1000);
			email_cont.height(10000);
			
			email_cont.html($('#_voucher-cont').html());
			email_cont.appendTo('body');
			email_cont.makeCssInline();
			
			if($("#_voucher-presente-flag").val() == '1')email_cont.find('#_voucher-lay-destinatario').remove();
			
			var email_txt = email_cont.html();
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'voucher-concluir' , voucher : email_txt , id_pedidos : $('#_voucher-lista-pedidos option:selected').val() },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					
					var dados = eval('(' + txt + ')');
					
					if(dados.ok){
						mens = "Foi enviado o voucher para o seu e-mail do cadastro com sucesso.";
					} else {
						if(dados.erro){
							mens = dados.erro;
						} else {
							mens = "Ocorreu um erro indefinido.";
						}
					}
					
					$.alerta_open(mens,false,false);
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
	}
	
	var mais_resultados = {};
	
	if($('#_loja-online-cont').length){
		mais_resultados.cel = 'loja-online';
		
		if($("#cont-"+mais_resultados.cel+"-mais").length){
			mais_resultados.page = 1;
			
			$("#cont-"+mais_resultados.cel+"-mais").bind('click touchstart',function(){
				var ajax_dados = eval('(' + ajax_vars.mais_resultados + ')');
				
				$.ajax({
					type: 'POST',
					url: variaveis_js.site_raiz,
					data: { ajax : 'sim' , opcao : ajax_dados.opcao, limite : ajax_dados.limite, page : mais_resultados.page },
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						var dados = eval('(' + txt + ')');
						$('#ajax_lendo').fadeOut(tempo_animacao);
						$("#cont-"+mais_resultados.cel+"-mais").before(dados.pagina+'<div class="clear"></div>');
						
						if(dados.sem_mais){
							$("#cont-"+mais_resultados.cel+"-mais").hide();
						} else {
							mais_resultados.page++;
						}
					},
					error: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
					}
				});
			});
		}
	}
	
	if($('#_meus-pedidos-cont').length){
		mais_resultados.cel2 = 'meus-pedidos';
		
		if($("#cont-"+mais_resultados.cel2+"-mais").length){
			mais_resultados.page = 1;
			
			$("#cont-"+mais_resultados.cel2+"-mais").bind('click touchstart',function(){
				var ajax_dados = eval('(' + ajax_vars.mais_resultados + ')');
				var categorias_produtos = $(this).attr('data-categorias_produtos');
				
				$.ajax({
					type: 'POST',
					url: variaveis_js.site_raiz,
					data: { ajax : 'sim' , opcao : ajax_dados.opcao, limite : ajax_dados.limite, page : mais_resultados.page, categorias_produtos : categorias_produtos },
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						var dados = eval('(' + txt + ')');
						$('#ajax_lendo').fadeOut(tempo_animacao);
						$("#cont-"+mais_resultados.cel2+"-mais").before(dados.pagina+'<div class="clear"></div>');
						
						if(dados.sem_mais){
							$("#cont-"+mais_resultados.cel2+"-mais").hide();
						} else {
							mais_resultados.page++;
						}
					},
					error: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
					}
				});
			});
		}
	
		$(document.body).on('click touchstart',"._meus-pedidos-voucher",function(){
			var id = $(this).attr('data-id');

			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'voucher-pedidos' , id : id },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					$.link_trigger('voucher');
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$(document.body).on('click touchstart',"._meus-pedidos-pagar",function(){
			var id = $(this).attr('data-id');

			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'pagar-pedidos' , id : id },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					var dados = eval('(' + txt + ')');
					var itens = Array();
					var itens_obj;
					
					for(var i=0;i<dados.length;i++){
						itens_obj = {
							id:dados[i].id,
							preco:parseFloat(dados[i].preco),
							quant:parseInt(dados[i].quant),
							titulo:dados[i].titulo,
							href:dados[i].href
						}
						
						itens.push(itens_obj);
					}
					
					global_vars.pedido = null;
					localStorage['pedido_itens'] = JSON.stringify(itens);
					$.link_trigger('pagamento');
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		
	}
};

// ======================================= Instalar fun&ccedil;&otilde;es ============================

$.aplicar_scripts_add('aplicar_scripts_ecommerce');