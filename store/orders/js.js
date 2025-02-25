if(window.location.protocol == "http:" && window.location.hostname != 'localhost'){
	var restOfUrl = window.location.href.substr(5);
	window.location = "https:" + restOfUrl;
}

$(document).ready(function(){
	sep = "../../";
	
	$("#imprimir").bind('click touchstart',function(){
		window.open(sep+"includes/ecommerce/print.php","Imprimir","menubar=0,location=0,height=700,width=700");
	});

	$("#form").submit(function() {
		return false;
	});
	
	$(".inteiro").numeric();
	$(".telefone").mask("(99) 9999-9999?9");
	
	$('.caixa-validade-fechar').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var tipo = ($('#validade_tipo').val() ? $('#validade_tipo').val() : 'P');
		
		if(tipo == 'P'){
			$(this).parent().hide();
		} else {
			$(this).parent().hide();
		}
	});
	
	$(".alterar-validade").bind('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var tipo = ($('#validade_tipo').val() ? $('#validade_tipo').val() : 'P');
		
		if(tipo == 'P'){
			$(this).parent().find('.caixa-validade').show();
			$(this).parent().find('.caixa-validade').find('.data').val($('#validade_data').val());
			$(this).parent().find('.caixa-validade').find('.data').focus();
		} else {
			$(this).parent().find('.caixa-validade-data').show();
			$(this).parent().find('.caixa-validade-data').find('.data').val($('#validade_data').val());
			$(this).parent().find('.caixa-validade-data').find('.data').focus();
			$(this).parent().find('.caixa-validade-data').find('.hora').val($('#validade_hora').val());
		}
	});
	
	$(".botao-validade").bind('click touchstart',function(){
		var tempo_animacao = 150;
		var pai = $(this).parent();
		var id = pai.parent().find('.alterar-validade').attr('data-id');
		var pedido_id = $('#pedido-codigo').html();
		var data_pedido = pai.parent().find('.alterar-validade').attr('data-data');
		var validade = pai.parent().find('.validade-value');
		var data = pai.find('.data');
		var hora = pai.find('.hora');
		var validade_tipo = ($('#validade_tipo').val() ? $('#validade_tipo').val() : 'P');
		
		pai.hide();
		
		if(data.val().length > 0 && data.val() != '__/__/____'){			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , validade : data.val() ,validade_hora : hora.val() ,validade_tipo : validade_tipo , data_pedido : data_pedido , id : id , pedido_id : pedido_id },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					
					
					if(txt.length > 0){
						alerta.html(txt);
						alerta.dialog("open");
					} else {
						if(validade_tipo == 'P'){
							validade.html(data.val());
						} else {
							validade.html(data.val()+' '+hora.val());
							$('#validade_hora').val(hora.val());
						}
						
						$('#validade_data').val(data.val());
					}
					data.val('');
					hora.val('');
				},
				error: function(txt){
					
				}
			});
		}
	});
	
	$('.caixa-identidade-fechar').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		$(this).parent().hide();
	});
	
	$(".alterar-identidade").bind('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var pai = $(this).parent();
		var nome = pai.find('input[name="nome"]').val();
		var doc = pai.find('input[name="doc"]').val();
		var tel = pai.find('input[name="tel"]').val();
		var identificacao_cont = $(this).parent().parent().parent().parent().find('.identificacao-cont');
		
		pai.find('input[name="nome"]').val(identificacao_cont.find('.identificacao-nome').html());
		pai.find('input[name="doc"]').val(identificacao_cont.find('.identificacao-doc').html());
		pai.find('input[name="tel"]').val(identificacao_cont.find('.identificacao-tel').html());
		pai.find('.caixa-identidade').show();
		pai.find('.caixa-identidade').find('.primeiro-nome').focus();

	});
	
	$(".botao-identidade").bind('click touchstart',function(){
		var tempo_animacao = 150;
		var pai = $(this).parent();
		var id_pedidos = pai.attr('data-id_pedidos');
		var id_pedidos_servicos = pai.attr('data-id_pedidos_servicos');
		var nome = pai.find('input[name="nome"]').val();
		var doc = pai.find('input[name="doc"]').val();
		var tel = pai.find('input[name="tel"]').val();
		var identificacao_cont = pai.parent().parent().parent().parent().find('.identificacao-cont');
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				identidade : 'sim',
				id_pedidos : id_pedidos,
				id_pedidos_servicos : id_pedidos_servicos,
				nome : nome,
				doc : doc,
				tel : tel
			},
			beforeSend: function(){
				$('#ajax_lendo').fadeIn(tempo_animacao);
			},
			success: function(txt){
				$('#ajax_lendo').fadeOut(tempo_animacao);
				
				if(txt.length > 0){
					alerta.html(txt);
					alerta.dialog("open");
				} else {
					identificacao_cont.find('.identificacao-nome').html(nome);
					identificacao_cont.find('.identificacao-doc').html(doc);
					identificacao_cont.find('.identificacao-tel').html(tel);
					
					pai.hide();
				}
			},
			error: function(txt){
				
			}
		});
	});
	
	$('.caixa-baixar-fechar').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		$(this).parent().hide();
	});
	
	$(".baixar-manual-voucher").bind('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		$(this).parent().find('.caixa-baixar').show();
		$(this).parent().find('.caixa-baixar').find('.caixa-baixar-textarea').focus();
	});
	
	$(".botao-baixar").bind('click touchstart',function(){
		var tempo_animacao = 150;
		var pai = $(this).parent();
		var observacao = pai.find('.caixa-baixar-textarea').val();
		var pedido_id = pai.parent().find('.baixar-manual-voucher').attr('data-id_pedidos');
		var pedido_servico_id = pai.parent().find('.baixar-manual-voucher').attr('data-id_pedidos_servicos');
		
		console.log(pedido_servico_id);
		
		if(confirm('Tem certeza que deseja baixar este serviço?')){			
			pai.hide();
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					baixar_voucher : 'sim',
					pedido_id : pedido_id,
					pedido_servico_id : pedido_servico_id,
					observacao : observacao
				},
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					
					if(txt.length > 0){
						alerta.html(txt);
						alerta.dialog("open");
					} else {
						window.open(window.location.href,'_self');
					}
				},
				error: function(txt){
					
				}
			});
		}
	});
	
	$(".hora").mask("99:99:99");
	$(".data,.data2").mask("99/99/9999",{completed:function(){
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

	$("input.data,input.nome,input.hora,.caixa-baixar-textarea").keyup(function(e){
		var code = e.keyCode || e.which;
		
		if(code == 27) { //ESC keycode
			var pai = $(this).parent();
			
			pai.hide();
		}
	});
	
	$("#envio_id").bind('change',function(){
		var selected = $(this).find(':selected').val();
		
		$('#codigo_rastreio-cont').hide();
		
		switch(selected){
			// Entregue
			case 'F':
				mudar_envio(selected,'');
			break;
			// Enviado
			case 'E':
				status_enviado();
			break;
			// Não enviado
			case 'N':
				mudar_envio(selected,'');
			break;
			case 'M':
				mudar_envio(selected,'');
			break;
			
		}
	});
	
	function status_enviado(){
		$('#codigo_rastreio-cont').show();
	}
	
	$("#codigo_rastreio-btn").bind('click touchstart',function(){
		var codigo = $('#codigo_rastreio-txt').val();
		
		if(codigo.length > 0){
			mudar_envio('E',codigo);
			$('#codigo_rastreio-td').html(codigo);
			$('#codigo_rastreio-txt').val('');
			$('#codigo_rastreio-cont').hide();
		} else {
			alerta.html('<p>Defina o código de rastreio antes de enviar ou então escolha a opção <b>Sem Código</b></p>');
			alerta.dialog("open");
		}
	});
	
	$("#codigo_rastreio-btn2").bind('click touchstart',function(){
		mudar_envio('E','');
		$('#codigo_rastreio-cont').hide();
	});
	
	function mudar_envio(opcao,codigo){
		var id = $('#id').val();
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { ajax : 'sim' , mudar_envio : 'sim' , opcao : opcao , codigo : codigo , id : id },
			beforeSend: function(){
				$('#ajax_lendo').fadeIn(150);
			},
			success: function(txt){
				$('#ajax_lendo').fadeOut(150);
				
				if(txt){
					/* if($('#teste').length == 0){
						var teste = $('<div id="teste"></div>');
						
						teste.appendTo('#form');
					} else {
						var teste = $('#teste');
					}
					
					teste.html(txt); */
					alerta.html(txt);
					alerta.dialog("open");
				} else {
					switch(opcao){
						// Entregue
						case 'F':
							$('#envio-td').html('<b><span style="color:green;">Entregue</span></b>');
						break;
						// Enviado
						case 'E':
							$('#envio-td').html('<b><span style="color:blue;">Enviado</span></b>');
						break;
						// Não enviado
						case 'N':
							$('#envio-td').html('<b><span style="color:red;">Não enviado</span></b>');
						break;
						case 'M':
							$('#envio-td').html('<b><span style="color:green;">Retirado em Mãos</span></b>');
						break;
						
					}
				}
			},
			error: function(txt){
				
			}
		});
	}
	
	b2make.purchases = {};
	
	var tempo_animacao = 300;
	
	function voucher_view_open(p = {}){
		if(!b2make.purchases.voucher_cont){
			b2make.purchases.voucher_cont = $('<div id="b2make-purchases-voucher-view-cont"><div id="b2make-purchases-voucher-service-view-tit"></div><div id="b2make-purchases-voucher-service-view-voucher"></div><div id="b2make-purchases-voucher-service-view-close">X</div></div>');
			b2make.purchases.voucher_cont.appendTo('body');
		}
		
		$('#b2make-purchases-voucher-service-view-tit').html(p.titulo);
		$('#b2make-purchases-voucher-service-view-voucher').html(p.voucher);
		
		b2make.purchases.voucher_cont.show();
	}
	
	function voucher_view_close(){
		if(b2make.purchases.voucher_cont){
			b2make.purchases.voucher_cont.hide();
		}
	}

	$(document.body).on('mouseup tap','#b2make-purchases-voucher-service-view-close',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		voucher_view_close();
	});
	
	$(document.body).on('mouseup tap','.visualizar-voucher',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var pedido_id = $(this).attr('data-id_pedidos');
		var pedido_servico_id = $(this).attr('data-id_pedidos_servicos');
		
		$.ajax({
			type: 'POST',
			url: variaveis_js.site_raiz,
			data: { ajax : 'sim' , opcao : 'e-services/'+variaveis_js.b2make_loja_atual+'/voucher-view',pedido_id:pedido_id,pedido_servico_id:pedido_servico_id,store_admin:true },
			beforeSend: function(){
				$('#ajax_lendo').fadeIn(tempo_animacao);
			},
			success: function(txt){
				var dados = eval('(' + txt + ')');
				
				switch(dados.status){
					case 'Ok':
						voucher_view_open({
							voucher : dados.voucher,
							titulo : dados.titulo
						});
					break;
					case 'semAutorizacao':
						alerta.html(dados.msg);
						alerta.dialog("open");
					break;
				}
				
				$('#ajax_lendo').fadeOut(tempo_animacao);
			},
			error: function(txt){
				$('#ajax_lendo').fadeOut(tempo_animacao);
			}
		});
	});
	
	$(document.body).on('mouseup tap','.imprimir-voucher',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var pedido_id = $(this).attr('data-id_pedidos');
		var pedido_servico_id = $(this).attr('data-id_pedidos_servicos');
		
		window.open(variaveis_js.site_raiz+"e-services/"+variaveis_js.b2make_loja_atual+"/voucher-print/"+pedido_id+"/"+pedido_servico_id,"Imprimir","menubar=0,location=no,height=700,width=700");
	});
	
});