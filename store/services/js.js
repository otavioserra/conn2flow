if(window.location.protocol == "http:" && window.location.hostname != 'localhost'){
	var restOfUrl = window.location.href.substr(5);
	window.location = "https:" + restOfUrl;
}

b2makeAdmin.stop_enter_preventDefaults = true;

$(document).ready(function(){
	sep = "../../";
	var tempo_animacao = 150;
	
	function sem_permissao_redirect(){
		window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'signin','_self');
	}
	
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
	$(".hora").mask("99:99");
	
	$("#form").submit(function() {
		var enviar = true;
		var campo;
		var mens;
		var mens_extra;
		var cor1 = "#FF5B5B";
		var cor2 = "#0C9";
		
		campo = "nome"; if(!$("#"+campo).val()){ $("#"+campo).css('background-color',cor1); enviar = false; } else { $("#"+campo).css('background-color',cor2); }
		
		if($('#validade_tipo').val() == 'P'){
			campo = "validade"; if(!$("#"+campo).val()){ $("#"+campo).css('background-color',cor1); enviar = false; } else { $("#"+campo).css('background-color',cor2); }
		} else {
			campo = "validade_data"; if(!$("#"+campo).val()){ $("#"+campo).css('background-color',cor1); enviar = false; } else { $("#"+campo).css('background-color',cor2); }
		}
		
		if(!enviar){
			alerta.html("É obrigatório preencher os campos marcados em vermelho!" + ( mens_extra ? "\n\nNOTA: " + mens_extra : ""));
			alerta.dialog("open");
			return false;
		}
	});
	
	$(".servicos_escolher").bind("click touchstart", function() {
		parent.servicos_escolher($(this).attr('servicos'));
	});
	
	var option = $('#validade-trocar').find("[value='" + $('#validade_tipo').val() + "']");
	option.attr('selected', 'selected');
	
	validade_alterar_tipo($('#validade_tipo').val());
	
	$('#validade-trocar').on('change',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var value = $(this).val();
		
		validade_alterar_tipo(value);
		$('#validade_tipo').val(value);
	});
	
	function validade_alterar_tipo(t){
		if(t == 'P'){
			$('#validade-periodo-cont').show();
			$('#validade-data-cont').hide();
		} else {
			$('#validade-periodo-cont').hide();
			$('#validade-data-cont').show();
		}
	}
	
	// ============================= Biblioteca de imagens ========================
	
	function biblioteca_imagens_lista(){
		var id_func = 'biblioteca_imagens_lista';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func
			},
			beforeSend: function(){
				$('#b2make-biblioteca-imagens-lista').find('.b2make-loading-box').remove();
				$('<div class="b2make-loading-box"></div>').appendTo('#b2make-biblioteca-imagens-lista');
			},
			success: function(txt){
				$('#b2make-biblioteca-imagens-lista').find('.b2make-loading-box').remove();
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					switch(dados.status){
						case 'Ok':
							if($('#biblioteca-imagens-id').val())dados.imagem_selecionada = $('#biblioteca-imagens-id').val();
							$('#b2make-biblioteca-imagens-lista').trigger('start',dados);
						break;
						case 'NaoHaImagens':
							$('#b2make-biblioteca-imagens-lista').trigger('start',dados);
						break;
						case 'NaoExisteId':
						break;
						default:
							console.log('ERROR - '+id_func+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+id_func+' - '+txt);
				}
			},
			error: function(txt){
				$('#b2make-biblioteca-imagens-lista').find('.b2make-loading-box').remove();
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	}
	
	function biblioteca_imagens_delete(p={}){
		var id = p.unidade.attr('data-id');
		
		var id_func = 'biblioteca_imagens_delete';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : id
			},
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
					
					switch(dados.status){
						case 'Ok':
							$('#b2make-biblioteca-imagens-lista').trigger('remover-unidade',p);
						break;
						case 'IdVinculadoServico':
							alerta.html(dados.msg);
							alerta.dialog("open");
						break;
						default:
							console.log('ERROR - '+id_func+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+id_func+' - '+txt);
				}
			},
			error: function(txt){
				$('#ajax_lendo').fadeOut(tempo_animacao);
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	}
	
	function biblioteca_imagens_upload_callback(dados){
		var id_func = 'biblioteca_imagens_upload_callback';
		
		switch(dados.status){
			case 'Ok':
				$('#b2make-biblioteca-imagens-lista').trigger('adicionar-unidade',dados);
			break;
			case 'SemPermissao':
				sem_permissao_redirect();
			break;
			default:
				console.log('ERROR - '+id_func+' - '+dados.status);
			
		}
	}
	
	function biblioteca_imagens_upload(){
		$.upload_files_start({
			url_php : 'uploadimg.php',
			input_selector : '#b2make-biblioteca-imagens-input',
			file_type : 'imagem',
			callback : biblioteca_imagens_upload_callback
		});
	}
	
	function biblioteca_imagens(){
		biblioteca_imagens_upload();
		biblioteca_imagens_lista();
		
		$('#b2make-biblioteca-imagens-lista').on('remover-selecao',function(e){
			$('#biblioteca-imagens-id').val('');
		});
		
		$('#b2make-biblioteca-imagens-lista').on('selecionar-unidade',function(e,id){
			$('#biblioteca-imagens-id').val(id);
		});
		
		$('#b2make-biblioteca-imagens-lista').on('remover-unidade-verificar',function(e,p){
			biblioteca_imagens_delete(p);
		});
	}
	
	biblioteca_imagens();
	
	// ============================= Observação ========================
	
	function observacao(){
		var observacao = new Array();
		var observacao_loaded = false;
		
		observacao['1'] = '';
		observacao['2'] = '';
		
		function carregar_padrao(){
			var id_func = 'observacao_padrao';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : id_func
				},
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
						
						switch(dados.status){
							case 'Ok':
								observacao_loaded = true;
								observacao['1'] = $('#observacao').val();
								$('#observacao').val(dados.padrao);
							break;
							default:
								console.log('ERROR - '+id_func+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+id_func+' - '+txt);
					}
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					console.log('ERROR AJAX - '+id_func+' - '+txt);
				}
			});
		}
		
		var observacao_anterior = '1';
		
		$('#observacao-menu').on('botao-clicked',function(e,num){
			if(observacao_anterior != num)
			switch(num){
				case '1':
					observacao['2'] = $('#observacao').val();
					$('#observacao').val(observacao['1']);
				break;
				case '2':
					if(!observacao_loaded){
						carregar_padrao();
					} else {
						observacao['1'] = $('#observacao').val();
						$('#observacao').val(observacao['2']);
					}
				break;
			}
			
			observacao_anterior = num;
		});
	}
	
	observacao();
	// ============================= Descrição ========================
	
	function descricao(){
		var descricao = new Array();
		var descricao_loaded = false;
		
		descricao['1'] = '';
		descricao['2'] = '';
		
		function carregar_padrao(){
			var id_func = 'descricao_padrao';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : id_func
				},
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
						
						switch(dados.status){
							case 'Ok':
								descricao_loaded = true;
								descricao['1'] = $('#descricao').val();
								$('#descricao').val(dados.padrao);
							break;
							default:
								console.log('ERROR - '+id_func+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+id_func+' - '+txt);
					}
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					console.log('ERROR AJAX - '+id_func+' - '+txt);
				}
			});
		}
		
		var descricao_anterior = '1';
		
		$('#descricao-menu').on('botao-clicked',function(e,num){
			if(descricao_anterior != num)
			switch(num){
				case '1':
					descricao['2'] = $('#descricao').val();
					$('#descricao').val(descricao['1']);
				break;
				case '2':
					if(!descricao_loaded){
						carregar_padrao();
					} else {
						descricao['1'] = $('#descricao').val();
						$('#descricao').val(descricao['2']);
					}
				break;
			}
			
			descricao_anterior = num;
		});
	}
	
	descricao();
	
	// ============================= Categoria ========================
	
	function categorias(){
		var categorias_id = $('#categorias').val();
		
		if(categorias_id == '-1'){
			$('#categoria-editar-btn').hide();
			$('#categoria-remover-btn').hide();
		}
		
		$('#b2make-admin-listener').on('dialog-ui-close',function(){
			switch(b2makeAdmin.dialog_close_option){
				case 'add-close-name-empty':
					$('#categorias-box').show();
				break;
			}
			
			b2makeAdmin.dialog_close_option = false;
		});
		
		$('#categorias').on('change',function(e){
			var categorias_id = $(this).val();
			
			if(categorias_id == '-1'){
				$('#categoria-editar-btn').hide();
				$('#categoria-remover-btn').hide();
			} else {
				$('#categoria-editar-btn').show();
				$('#categoria-remover-btn').show();
			}
		});
		
		$('#categorias-box').on('add-open',function(e){
			$('#categoria-nome').val('');
		});
		
		$('#categorias-box').on('add-close',function(e){
			var nome = $('#categoria-nome').val();
			var criar = true;
			var id_func = 'categoria-add';
			
			if(nome.length == 0){
				alerta.html("É obrigatório preencher o nome da categoria!");
				alerta.dialog("open");
				b2makeAdmin.dialog_close_option = 'add-close-name-empty';
				$('#categorias-box').show();
				criar = false;
			}
			
			if(criar){
				$.ajax({
					type: 'POST',
					url: '.',
					data: { 
						ajax : 'sim',
						nome,
						opcao : id_func
					},
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						if(
							txt.charAt(0) == "{" || 
							txt.charAt(0) == "["
						){
							var dados = eval('(' + txt + ')');
							
							switch(dados.status){
								case 'Ok':
									location.reload();
								break;
								default:
									console.log('ERROR - '+id_func+' - '+dados.status);
								
							}
							
						} else {
							console.log('ERROR - '+id_func+' - '+txt);
						}
						
						$('#ajax_lendo').fadeOut(tempo_animacao);
					},
					error: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						console.log('ERROR AJAX - '+id_func+' - '+txt);
					}
				});
			}
		});
		
		$('#categorias-box').on('edit-open',function(e){
			$('#categoria-nome').val($('#categorias option:selected').text());
		});
		
		$('#categorias-box').on('edit-close',function(e){
			var nome = $('#categoria-nome').val();
			var id = $('#categorias').val();
			var editar = true;
			var id_func = 'categoria-edit';
			
			if(nome.length == 0){
				alerta.html("É obrigatório preencher o nome da categoria!");
				alerta.dialog("open");
				b2makeAdmin.dialog_close_option = 'add-close-name-empty';
				$('#categorias-box').show();
				editar = false;
			}
			
			if(editar){
				$.ajax({
					type: 'POST',
					url: '.',
					data: { 
						ajax : 'sim',
						nome,
						id,
						opcao : id_func
					},
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						if(
							txt.charAt(0) == "{" || 
							txt.charAt(0) == "["
						){
							var dados = eval('(' + txt + ')');
							
							switch(dados.status){
								case 'Ok':
									location.reload();
								break;
								default:
									console.log('ERROR - '+id_func+' - '+dados.status);
								
							}
							
						} else {
							console.log('ERROR - '+id_func+' - '+txt);
						}
						
						$('#ajax_lendo').fadeOut(tempo_animacao);
					},
					error: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						console.log('ERROR AJAX - '+id_func+' - '+txt);
					}
				});
			}
		});
	
		$('#categoria-remover-btn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var id = $('#categorias').val();
			var id_func = 'categoria-del';
			
			if(confirm("Tem certeza que você deseja remover esta categoria?")){
				$.ajax({
					type: 'POST',
					url: '.',
					data: { 
						ajax : 'sim',
						id,
						opcao : id_func
					},
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						if(
							txt.charAt(0) == "{" || 
							txt.charAt(0) == "["
						){
							var dados = eval('(' + txt + ')');
							
							switch(dados.status){
								case 'Ok':
									location.reload();
								break;
								default:
									console.log('ERROR - '+id_func+' - '+dados.status);
								
							}
							
						} else {
							console.log('ERROR - '+id_func+' - '+txt);
						}
						
						$('#ajax_lendo').fadeOut(tempo_animacao);
					},
					error: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						console.log('ERROR AJAX - '+id_func+' - '+txt);
					}
				});
			}
		});
	
	}
	
	categorias();
	
	// ============================= Visibilidade ========================
	
	function visibilidade_inputs(visibilidade){
		switch(visibilidade){
			case 'sempre':
				$('#visibilidade-data-inicio').hide();
				$('#visibilidade-data-fim').hide();
				$('#visibilidade-data-periodo').hide();
			break;
			case 'dataInicio':
				$('#visibilidade-data-inicio').show();
				$('#visibilidade-data-fim').hide();
				$('#visibilidade-data-periodo').hide();
			break;
			case 'dataFim':
				$('#visibilidade-data-inicio').hide();
				$('#visibilidade-data-fim').show();
				$('#visibilidade-data-periodo').hide();
			break;
			case 'dataPeriodo':
				$('#visibilidade-data-inicio').hide();
				$('#visibilidade-data-fim').hide();
				$('#visibilidade-data-periodo').show();
			break;
		}
	}
	
	function visibilidade(){
		visibilidade_inputs($('#visibilidade').val());
		
		$('#visibilidade-select').on('change',function(e){
			visibilidade_inputs($(this).find("option:selected").val());
		});
		
		var locale = {
			"format": "DD/MM/YYYY HH:mm",
			"separator": " até ",
			"applyLabel": "Aplicar",
			"cancelLabel": "Cancelar",
			"fromLabel": "De",
			"toLabel": "Para",
			"customRangeLabel": "Personalizado",
			"weekLabel": "S",
			"daysOfWeek": [
				"Dom",
				"Seg",
				"Ter",
				"Qua",
				"Qui",
				"Sex",
				"Sab"
			],
			"monthNames": [
				"Janeiro",
				"Fevereiro",
				"Março",
				"Abril",
				"Maio",
				"Junho",
				"Julho",
				"Agosto",
				"Setembro",
				"Outubro",
				"Novembro",
				"Dezembro"
			],
			"firstDay": 1
		};
		
		var anoInicio = parseInt(new Date().getFullYear()) - 1;
		var anoFim = parseInt(new Date().getFullYear()) + 11;
		
		$('#data-inicio').daterangepicker({
			"singleDatePicker": true,
			"timePicker": true,
			"timePicker24Hour": true,
			"timePickerIncrement": 10,
			"parentEl": "#visibilidade-pai",
			"drops": "auto",
			"autoApply": true,
			"showDropdowns": true,
			"minYear": anoInicio,
			"maxYear": anoFim,
			"locale": locale
		});
		
		$('#data-fim').daterangepicker({
			"singleDatePicker": true,
			"timePicker": true,
			"timePicker24Hour": true,
			"timePickerIncrement": 10,
			"parentEl": "#visibilidade-pai",
			"drops": "auto",
			"autoApply": true,
			"showDropdowns": true,
			"minYear": anoInicio,
			"maxYear": anoFim,
			"locale": locale
		});
		
		$('#data-periodo').daterangepicker({
			"timePicker": true,
			"timePicker24Hour": true,
			"timePickerIncrement": 10,
			"parentEl": "#visibilidade-pai",
			"drops": "auto",
			"autoApply": true,
			"showDropdowns": true,
			"minYear": anoInicio,
			"maxYear": anoFim,
			"locale": locale
		});
	}
	
	visibilidade();
	
	// ============================= Lotes ========================
	
	function lotes_html(lote = {}){
		var nome = $('<label for="lote-nome-'+lote.id+'">Nome</label><input name="lote-nome-'+lote.id+'" type="text" data-type="nome" id="lote-nome-'+lote.id+'"'+(lote.nome ? ' value="'+lote.nome+'"' : '')+' size="30" maxlength="100">');
		var quantidade = $('<label for="lote-quantidade-'+lote.id+'">Quantidade</label><input name="lote-quantidade-'+lote.id+'" type="text" data-type="quantidade" class="inteiro" id="lote-quantidade-'+lote.id+'"'+(lote.quantidade ? ' value="'+lote.quantidade+'"' : '')+' size="9" maxlength="9">');
		var preco = $('<label for="lote-preco-'+lote.id+'">Preço</label><input name="lote-preco-'+lote.id+'" type="text" data-type="preco" class="float" id="lote-preco-'+lote.id+'"'+(lote.preco ? ' value="'+lote.preco+'"' : '')+' size="10" maxlength="10">');
		var periodo = $('<label for="lote-periodo-'+lote.id+'">Período De</label><input name="lote-periodo-'+lote.id+'" type="text" class="lote-periodo" id="lote-periodo-'+lote.id+'"'+(lote.periodo ? ' value="'+lote.periodo+'"' : '')+' size="32" maxlength="40" readonly="readonly">');
		var identificador = $('<input name="lote-identificador-'+lote.id+'" type="hidden" class="lote-identificador" id="lote-identificador-'+lote.id+'"'+(lote.id_servicos_lotes ? ' value="'+lote.id_servicos_lotes+'"' : '')+'>');
		var excluir = $('<div id="lote-excluir-'+lote.id+'" data-id="'+lote.id+'" class="bi-noselect lote-excluir" title="Remover este lote."><div style="-webkit-mask-position:-70px -14px;mask-position:-70px -14px;background-color:#AEAEAE;" class="icon"></div><div class="label">Excluir</div></div>');
		
		var col1 = $('<div class="lote-coluna-1"></div>');
		var col2 = $('<div class="lote-coluna-2"></div>');
		
		col1.append(nome);
		col1.append(quantidade);
		col1.append(preco);
		col1.append(periodo);
		col1.append(identificador);
		
		if(!lote.nao_excluir) col2.append(excluir);
		
		var cont = $('<div id="lote-cont-'+lote.id+'" data-id="'+lote.id+'" class="lote-unidade"></div>');
		
		cont.append(col1);
		cont.append(col2);
		
		return cont;
	}
	
	function lotes_cont(){
		var lotes = b2make.lotes;
		
		var locale = {
			"format": "DD/MM/YYYY HH:mm",
			"separator": " até ",
			"applyLabel": "Aplicar",
			"cancelLabel": "Cancelar",
			"fromLabel": "De",
			"toLabel": "Para",
			"customRangeLabel": "Personalizado",
			"weekLabel": "S",
			"daysOfWeek": [
				"Dom",
				"Seg",
				"Ter",
				"Qua",
				"Qui",
				"Sex",
				"Sab"
			],
			"monthNames": [
				"Janeiro",
				"Fevereiro",
				"Março",
				"Abril",
				"Maio",
				"Junho",
				"Julho",
				"Agosto",
				"Setembro",
				"Outubro",
				"Novembro",
				"Dezembro"
			],
			"firstDay": 1
		};
		
		var anoInicio = parseInt(new Date().getFullYear()) - 1;
		var anoFim = parseInt(new Date().getFullYear()) + 11;
		
		if(!lotes){
			var date1 = moment().format('DD/MM/YYYY')+' 00:00';
			var date2 = moment(date1,'DD/MM/YYYY').add(7, 'days').format('DD/MM/YYYY HH:mm');
			
			lotes = Array({
				id:'1',
				nome:'Lote 1',
				visivel_de:date1,
				visivel_ate:date2,
				periodo:date1+' até '+date2
			});
			
			b2make.lotes = lotes;
		}
		
		$('#lotes-quantidade').val(b2make.lotes.length);
		
		var adicionar = '<div id="lote-adicionar" class="b2make-uploads-btn bi-noselect" title="Adicionar um lote"><div style="-webkit-mask-position:-42px -14px;mask-position:-42px -14px;background-color:#FFF;" class="icon"></div><div class="label">Adicionar</div><input id="b2make-biblioteca-imagens-input" accept="image/jpeg,image/gif,image/png" class="b2make-uploads-input" type="file" name="files[]" multiple=""></div>';
		
		if($('#lote-variado .lote-unidade').length > 0){
			b2make.lotes = b2make.lotes.filter(function(value, index, arr){
				var id = value.id;
				
				$('#lote-variado .lote-unidade[data-id="'+id+'"] input').each(function(){
					var input = $(this);
					switch(input.attr('data-type')){
						case 'nome': value.nome = input.val(); break;
						case 'quantidade': value.quantidade = input.val(); break;
						case 'preco': value.preco = input.val(); break;
					}
				});
				
				return true;
			});
		}
		
		$('#lote-variado').html('');
		$('#lote-variado').append(adicionar);
		
		for(var i=0;i<lotes.length;i++){
			if(i == 0){
				lotes[i].nao_excluir = true;
			}
			
			$('#lote-variado').append(lotes_html(lotes[i]));
			
			$('#lote-periodo-'+lotes[i].id).daterangepicker({
				"timePicker": true,
				"timePicker24Hour": true,
				"timePickerIncrement": 10,
				"parentEl": "#lote-cont",
				"drops": "auto",
				"opens": "left",
				"autoApply": true,
				"showDropdowns": true,
				"minYear": anoInicio,
				"maxYear": anoFim,
				"locale": locale
			});
			
			$('#lote-periodo-'+lotes[i].id).on('apply.daterangepicker', function(ev, picker){
				var id = $(this).parent().parent().attr('data-id');
				var T1 = moment(picker.startDate,'DD/MM/YYYY HH:mm').valueOf();
				var T2 = moment(picker.endDate,'DD/MM/YYYY HH:mm').valueOf();
				
				var lotes_aux = b2make.lotes;
				
				for(var i=0;i<lotes_aux.length;i++){
					if(id != lotes_aux[i].id){
						var visivel_de = lotes_aux[i].visivel_de;
						var visivel_ate = lotes_aux[i].visivel_ate;
						
						var t1 = moment(visivel_de,'DD/MM/YYYY HH:mm').valueOf();
						var t2 = moment(visivel_ate,'DD/MM/YYYY HH:mm').valueOf();
						
						var choque = false;
						
						if(T1 < t1 && T2 > t1){choque = true;}
						if(T1 < t2 && T2 > t2){choque = true;}
						if(T1 > t1 && T2 < t2){choque = true;}
						if(T1 < t1 && T2 > t2){choque = true;}
						
						if(choque){
							alerta.html("Esse período é inválido pois choca com o lote <b>"+lotes_aux[i].nome+"</b> com período <b>de "+lotes_aux[i].periodo+"</b>. Favor preencher um período que não choque com nenhum outro lote.");
							alerta.dialog("open");
							break;
						}
					}
				}
				
				if(choque){
					$(this).data('daterangepicker').setStartDate(picker.oldStartDate);
					$(this).data('daterangepicker').setEndDate(picker.oldEndDate);
				} else {
					for(var i=0;i<lotes_aux.length;i++){
						if(id == lotes_aux[i].id){
							lotes_aux[i].visivel_de = moment(picker.startDate,'DD/MM/YYYY HH:mm').format('DD/MM/YYYY HH:mm');
							lotes_aux[i].visivel_ate = moment(picker.endDate,'DD/MM/YYYY HH:mm').format('DD/MM/YYYY HH:mm');
							lotes_aux[i].periodo = lotes_aux[i].visivel_de+' até '+lotes_aux[i].visivel_ate;
						}
					}
					
					b2make.lotes = lotes_aux;
				}
			});
		}
		
		$(".float").maskMoney({showSymbol:false,decimal:",",thousands:".",precision:2});
		$(".inteiro").numeric();
		
		$('#lote-adicionar').off();
		$('#lote-adicionar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var id = b2make.lotes.length+1;
			var max = 10;
			
			if(parseInt(id) > max){
				alerta.html("Só é permitido definir no máximo "+max+" lotes.");
				alerta.dialog("open");
			} else {
				var lotes_aux = b2make.lotes;
				var date_last = lotes_aux[lotes_aux.length - 1].visivel_ate;
				
				var date1 = moment(date_last,'DD/MM/YYYY HH:mm').format('DD/MM/YYYY HH:mm')+' 00:00';
				var date2 = moment(date1,'DD/MM/YYYY').add(7, 'days').format('DD/MM/YYYY HH:mm');
				
				b2make.lotes.push({
					id:id,
					nome:'Lote ' + id,
					visivel_de:date1,
					visivel_ate:date2,
					periodo:date1+' até '+date2
				});
				
				lotes_cont();
			}
		});
		
		$('.lote-excluir').off();
		$('.lote-excluir').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var id = $(this).attr('data-id');
			
			b2make.lotes = b2make.lotes.filter(function(value, index, arr){
				if(value.id == id){
					return false;
				} else {
					return true;
				}
			});
			
			lotes_cont();
		});
		
	}
	
	function lotes_start(){
		var dados = $('#lotes-dados').val();
		
		if(dados.length > 0){
			var obj = JSON.parse(dados);
			
			if(obj){
				b2make.lotes = new Array();
				
				obj = obj.filter(function(value, index, arr){
					b2make.lotes.push({
						id:value.id,
						id_servicos_lotes:value.id_servicos_lotes,
						nome:value.nome,
						quantidade:value.quantidade,
						preco:value.preco,
						visivel_de:value.visivel_de,
						visivel_ate:value.visivel_ate,
						periodo:value.periodo
					});
					
					return true;
				});
			}
		}
		
	}
	
	function lote_inputs(lote){
		switch(lote){
			case 'unico':
				$('#lote-unico').show();
				$('#lote-variado').hide();
			break;
			case 'variado':
				$('#lote-unico').hide();
				$('#lote-variado').show();
			break;
		}
	}
	
	function lote(){
		if($('#lote-cont').length > 0){
			lotes_start();
			lotes_cont();
			lote_inputs($('#lote').val());
			
			$('#lote').on('change',function(e){
				lote_inputs($(this).find("option:selected").val());
			});
		}
	}
	
	lote();
	
});