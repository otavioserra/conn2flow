$(document).ready(function(){
	
	if($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0){
		// ===== TinyMCE
		
		var editorHtmlAtivo = true;
		
		if(editorHtmlAtivo){
			tinymce.init({
				menubar: false,
				selector: 'textarea.tinymce',
				toolbar: 'undo redo | styleselect | bold italic underline | link image | alignleft aligncenter alignright alignjustify | fontselect fontsizeselect',
				plugins: "image imagetools link",
				directionality: 'pt_BR',
				min_height: 450,
				language: 'pt_BR',
				language_url: gestor.raiz+'tinymce/langs/pt_BR.js',
				font_formats: 'Verdana=Verdana;Arial=arial,helvetica,sans-serif;',
				branding: false,
				valid_elements: '*[*]',
				init_instance_callback: tinyMCEReady
			});
		}
		
		function tinyMCEReady(){
			
		}
		
		// ===== Format caminho
		
		$(document.body).on('keyup blur','input[name="nome"]',function(e){
			var value = $(this).val();
			
			$.input_delay_to_change({
				trigger_selector:'#gestor-listener',
				trigger_event:'nome-change',
				value:value
			});
		});
		
		$(document.body).on('nome-change','#gestor-listener',function(e,value,p){
			if(!p) p = {};
			
			value = formatar_nome(value);
			
			if($('#_gestor-interface-edit-dados').length > 0){
				if($('input[name="paginaCaminho"]').val().length == 0) $('input[name="paginaCaminho"]').val(formatar_url(value));
			} else {
				$('input[name="paginaCaminho"]').val(formatar_url(value));
			}
		});
		
		$(document.body).on('blur','input[name="paginaCaminho"]',function(e){
			var value = $(this).val();
			
			$('input[name="paginaCaminho"]').val(formatar_url(value));
		});
		
		function formatar_url(url){
			url = url + '/';
			url = url.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); // Trocar todos os caracteres com acentos pelos seus similares sem acento.
			url = url.replace(/[^a-zA-Z0-9 \-\/]/g,''); // Remover todos os caracteres que não são alfanuméricos ou espaço ou traço ou barra.
			url = url.toLowerCase(); // Passar para letras minúsculas
			url = url.trim(); // Remover espaço do início e fim.
			url = url.replace(/\s/g,'-'); // Trocar todos os espaços por traço.
			url = url.replace(/\-{2,}/g,'-'); // Remover a repetição de traços para um único traço.
			url = url.replace(/\/{2,}/g,'/'); // Remover a repetição de barras para uma única barra.
			
			return url;
		}
		
		function formatar_nome(nome){
			var string = nome;
			
			string = string.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); // Trocar todos os caracteres com acentos pelos seus similares sem acento.
			string = string.replace(/[^a-zA-Z0-9 \-]/g,''); // Remover todos os caracteres que não são alfanuméricos ou espaço ou traço.
			string = string.toLowerCase(); // Passar para letras minúsculas
			string = string.trim(); // Remover espaço do início e fim.
			string = string.replace(/\s/g,'-'); // Trocar todos os espaços por traço.
			string = string.replace(/\-{2,}/g,'-'); // Remover a repetição de traços para um único traço.
			
			return string;
		}
		
		// ===== Variáveis globais de lotes.
		
		var formSelector = '#servicos';
		var dadosServidor = {};
		var dadosLocal = {};
		
		// ===== Funções de manipulação dos lotes.
		
		function salvarLotesDadosServidor(){
			var lotesProcessados = [];
			
			$('.loteCont').each(function(){
				// ===== Pegar os dados principais do lote.
				
				var loteObj = $(this);
				
				var loteValue = loteObj.attr('data-value');
				var loteNum = loteObj.attr('data-num');
				var loteID = (loteObj.attr('data-id') !== undefined ? loteObj.attr('data-id') : false);
				
				// ===== Pegar o nome do lote.
				
				var loteNome = loteObj.find('.loteNome').val();
				
				// ===== Pegar a visibilidade do lote e os campos data.
				
				var loteVisibilidade = loteObj.find('.loteVisibilidade').dropdown('get value');
				var dataInicio = '';
				var dataFim = '';
				
				switch(loteVisibilidade){
					case 'datainicio':
						dataInicio = loteObj.find('.startdate').find('.inputDataInicio').val();
					break;
					case 'datafim':
						dataFim = loteObj.find('.enddate').find('.inputDataFim').val();
					break;
					case 'periodo':
						dataInicio = loteObj.find('.rangestart').find('.inputDataInicio').val();
						dataFim = loteObj.find('.rangeend').find('.inputDataFim').val();
					break;
				}
				
				// ===== Varrer as variações e pegar seus dados.
				
				var variacoes = [];
				var numVariacoes = 0;
				
				loteObj.find('.varItem').each(function(){
					// ===== Pegar os dados principais da variação.
					var variacaoObj = $(this);
					
					var variacaoValue = variacaoObj.attr('data-value');
					var variacaoNum = variacaoObj.attr('data-num');
					var variacaoID = (variacaoObj.attr('data-id') !== undefined ? variacaoObj.attr('data-id') : false);
					
					// ===== Incrementar a quantidade de variações.
					
					numVariacoes++;
					
					// ===== Pegar o nome, preço e quantidade da variação.
					
					var variacaoNome = variacaoObj.find('.variacaoNome').val();
					var variacaoPreco = variacaoObj.find('.variacaoPreco').val();
					var variacaoQuantidade = variacaoObj.find('.variacaoQuantidade').val();
					
					// ===== Verificar a opção gratuita da variação.
					
					var variacaoGratuito = variacaoObj.find('.cheVariacaoGratuito').checkbox('is checked');
					
					// ===== Incluir a variação no array de variacoes.
					
					var variacao = {
						value : variacaoValue,
						num : variacaoNum,
						nome : variacaoNome,
						preco : variacaoPreco,
						quantidade : variacaoQuantidade,
						gratuito : variacaoGratuito,
					};
					
					if(variacaoID !== false){
						variacao.id = variacaoID;
					}
					
					variacoes.push(variacao);
				});
				
				// ===== Criar o objeto do lote e incluir o mesmo nos 'lotesProcessados'.
			
				var lote = {
					value : loteValue,
					num : loteNum,
					numVariacoes,
					nome : loteNome,
					visibilidade : loteVisibilidade,
					dataInicio,
					dataFim,
					variacoes,
				};
				
				if(loteID !== false){
					lote.id = loteID;
				}
				
				lotesProcessados.push(lote);
			});
			
			// ===== Atualizar os lotes da variável 'dadosServidor'.
			
			dadosServidor.lotes = lotesProcessados;
		}
		
		function atualizarDadosServidor(){
			$('#dadosServidor').val(JSON.stringify(dadosServidor));
		}
		
		function removerLote(p){
			// ===== Parâmetros recebidos.
			
			var value = p.value;
			
			// ===== Atualizar dados do servidor.
			
			dadosServidor.lotes = dadosServidor.lotes.filter(function( obj ) {
				return obj.value !== value;
			});
			atualizarDadosServidor();
			
			// ===== Exlcuir o cont.
			
			$('.loteCont[data-value="'+value+'"]').remove();
			
			// ===== Atualizar menu.
			
			dadosServidor.dropdownValues = dadosServidor.dropdownValues.filter(function( obj ) {
				return obj.value !== value;
			});
			
			$('.lotesMenu').dropdown('setup menu',{ values: dadosServidor.dropdownValues });
			
			// ===== Definições padrões.
			
			var nomeLotes = dadosServidor.definicoes.nomeLotes;
			var idLotes = dadosServidor.definicoes.idLotes;
			
			// ===== Mudar para o lote inicial.
			
			$('.lotesMenu').dropdown('set selected',idLotes+'-1');
			$('.loteCont[data-value="'+idLotes+'-1"]').show();
		}
		
		function adicionarLote(){
			// ===== Incrementar a quantidade máxima atual.
			
			dadosServidor.numLotes++;
			var numLotes = dadosServidor.numLotes;
			var numVariacao = 1;
			
			// ===== Definições padrões.
			
			var nomeLotes = dadosServidor.definicoes.nomeLotes;
			var idLotes = dadosServidor.definicoes.idLotes;
			var nomeVariacao = dadosServidor.definicoes.nomeVariacao;
			var idVariacao = dadosServidor.definicoes.idVariacao;
			
			// ===== Criar o objeto do lote e incluir o mesmo nos 'dadosServidor'.
			
			var lote = {
				value : idLotes+'-'+numLotes,
				num : numLotes,
				numVariacoes : numVariacao,
				nome : nomeLotes+' '+numLotes,
				variacoes : [{
					value : idVariacao+'-'+numVariacao,
					num : numVariacao,
					nome : nomeVariacao+' '+numVariacao,
				}],
			};
			
			dadosServidor.lotes.push(lote);
			atualizarDadosServidor();
			
			// ===== Cópia dos modelos das definições de um lote.
			
			var ele = dadosLocal.loteCont.clone();
			
			// ===== Troca dos valores padrões pelas referências dos lotes.
			
			ele.attr('data-value',idLotes+'-'+numLotes);
			ele.attr('data-num',numLotes);
			
			ele.find('.loteNome').val(nomeLotes+' '+numLotes);
			
			// ===== Remover botão excluir de variação.
			
			ele.find('.varExcluir').remove();
			
			// ===== Padrão do nome da variação.
			
			ele.find('.varItem').attr('data-value',idVariacao+'-'+numVariacao);
			ele.find('.varItem').attr('data-num',numVariacao);
			ele.find('.variacaoNome').val(nomeVariacao+' '+numVariacao);
			
			// ===== Adicionar tanto o menu quanto o lote.
			
			dadosServidor.dropdownValues.push({value : idLotes+'-'+numLotes, text : nomeLotes+' '+numLotes, name : nomeLotes+' '+numLotes});
			
			$('.lotesMenu').dropdown('setup menu',{ values: dadosServidor.dropdownValues });
			$('.lotesContsFim').before(ele);
			
			// ===== Mudar o menu para o lote adicionado.
			
			$('.lotesMenu').dropdown('set selected',idLotes+'-'+numLotes);
			$('.loteCont').hide();
			
			ele.show();
			
			ele.find('.loteNome').focus();
			ele.find('.loteNome').select();
			
			// ===== Operações do lote.
			
			lotesOperacoes({
				loteValue : idLotes+'-'+numLotes
			});
			
			// ===== Operações da variação.
			
			variacoesOperacoes({
				loteValue : idLotes+'-'+numLotes
			});
		}
		
		function duplicarLote(p = {}){
			// ===== Identificador do lote a ser duplicado.
			
			var loteValue = p.loteValue;
			
			// ===== Clone do objeto lote alvo.
			
			var ele = $('.loteCont[data-value="'+loteValue+'"]').clone();
			
			// ===== Remover a referência do ID caso haja.
			
			ele.removeAttr('data-id');
			
			// ===== Lote visibilidade selected value.
			
			var selectedValue = $('.loteCont[data-value="'+loteValue+'"]').find('.loteVisibilidade').dropdown('get value');
			
			// ===== Incrementar a quantidade máxima atual.
			
			dadosServidor.numLotes++;
			var numLotes = dadosServidor.numLotes;
			var numVariacao = 1;
			
			// ===== Definições padrões.
			
			var nomeLotes = dadosServidor.definicoes.nomeLotes;
			var idLotes = dadosServidor.definicoes.idLotes;
			var nomeVariacao = dadosServidor.definicoes.nomeVariacao;
			var idVariacao = dadosServidor.definicoes.idVariacao;
			
			// ===== Pegar as variações do lote alvo do 'dadosServidor'.
			
			var loteAlvo = dadosServidor.lotes.filter(function( obj ) {
				return obj.value === loteValue;
			});
			
			// ===== Criar o objeto do lote e incluir o mesmo nos 'dadosServidor'.
			
			var lote = {
				value : idLotes+'-'+numLotes,
				num : numLotes,
				numVariacoes : loteAlvo[0].variacoes.length,
				nome : nomeLotes+' '+numLotes,
				variacoes : loteAlvo[0].variacoes,
			};
			
			dadosServidor.lotes.push(lote);
			atualizarDadosServidor();
			
			// ===== Troca dos valores padrões pelas referências dos lotes.
			
			ele.attr('data-value',idLotes+'-'+numLotes);
			ele.attr('data-num',numLotes);
			
			ele.find('.loteNome').val(nomeLotes+' '+numLotes);
			
			// ===== Incluir o botão excluir caso necessário.
			
			if(ele.find('.loteExcluir').length == 0){
				var excluir = dadosLocal.loteCont.find('.loteExcluir').clone();
				
				ele.prepend(excluir);
			}
			
			// ===== Adicionar tanto o menu quanto o lote.
			
			dadosServidor.dropdownValues.push({value : idLotes+'-'+numLotes, text : nomeLotes+' '+numLotes, name : nomeLotes+' '+numLotes});
			
			$('.lotesMenu').dropdown('setup menu',{ values: dadosServidor.dropdownValues });
			$('.lotesContsFim').before(ele);
			
			// ===== Mudar o menu para o lote adicionado.
			
			$('.lotesMenu').dropdown('set selected',idLotes+'-'+numLotes);
			$('.loteCont').hide();
			
			ele.show();
			
			ele.find('.loteNome').focus();
			ele.find('.loteNome').select();
			
			// ===== Operações do lote.
			
			lotesOperacoes({
				loteValue : idLotes+'-'+numLotes,
				selectedValue
			});
			
			// ===== Operações da variação.
			
			variacoesOperacoes({
				loteValue : idLotes+'-'+numLotes
			});
		}
		
		function removerVariacao(p){
			// ===== Parâmetros recebidos.
			
			var value = p.value;
			var variacaoValue = p.variacaoValue;
			
			// ===== Pegar o índice do lote alvo no 'dadosServidor'.
			
			var loteIndex = dadosServidor.lotes.findIndex((obj => obj.value === value));
			
			// ===== Atualizar dados do servidor.
			
			dadosServidor.lotes[loteIndex].variacoes = dadosServidor.lotes[loteIndex].variacoes.filter(function( obj ) {
				return obj.value !== variacaoValue;
			});
			
			atualizarDadosServidor();
			
			// ===== Exlcuir o cont da variação.
			
			$('.loteCont[data-value="'+value+'"] .varItem[data-value="'+variacaoValue+'"]').remove();
			
		}
		
		function adicionarVariacao(p = {}){
			// ===== Identificador do lote da variação.
			
			var loteValue = p.loteValue;
			
			// ===== Pegar o índice do lote alvo no 'dadosServidor'.
			
			var loteIndex = dadosServidor.lotes.findIndex((obj => obj.value == loteValue));
			
			// ===== Incrementar a quantidade máxima de variações do lote alvo.
			
			dadosServidor.lotes[loteIndex].numVariacoes++;
			var numVariacao = dadosServidor.lotes[loteIndex].numVariacoes;
			
			// ===== Definições padrões.
			
			var nomeVariacao = dadosServidor.definicoes.nomeVariacao;
			var idVariacao = dadosServidor.definicoes.idVariacao;
			
			// ===== Criar o objeto do lote e incluir o mesmo nos 'dadosServidor'.
			
			var variacao = {
				value : idVariacao+'-'+numVariacao,
				num : numVariacao,
				nome : nomeVariacao+' '+numVariacao,
			};
			
			dadosServidor.lotes[loteIndex].variacoes.push(variacao);
			atualizarDadosServidor();
			
			// ===== Cópia dos modelos das definições de uma variação.
			
			var ele = dadosLocal.varItem.clone();
			
			// ===== Padrão do nome da variação.
			
			ele.attr('data-value',idVariacao+'-'+numVariacao);
			ele.attr('data-num',numVariacao);
			ele.find('.variacaoNome').val(nomeVariacao+' '+numVariacao);
			
			// ===== Incluir a variação no lote.
			
			$('.loteCont[data-value="'+loteValue+'"] .varItems').append(ele);
			
			// ===== Focar no nome da nova variação.
			
			ele.find('.variacaoNome').focus();
			ele.find('.variacaoNome').select();
			
			// ===== Adicionar validação no formulário.
			
			$(formSelector).form('add rule', nomeVariacao+' '+numVariacao, gestor.interface.regrasValidacao.variacaoNome.rules);
			
			// ===== Operações da variação.
			
			variacoesOperacoes({
				loteValue
			});
		}
		
		function lotesOperacoes(p = {}){
			// ===== Parâmetros recebidos.
			
			var loteValue = p.loteValue;
			
			// ===== Lote alvo.
			
			var lote = '.loteCont[data-value="'+loteValue+'"]';
			
			// ===== Trocar tipo de visibilidade.
			
			dropdownObj = {
				onChange: function(value, text, $choice){
					$(lote).find('.data-visibilidade').addClass('escondido');
					
					$(lote).find('.data-visibilidade').find('input').removeAttr('data-validate');
					
					switch(value){
						case 'datainicio': 
							$(lote).find('.data-inicio').removeClass('escondido');
							$(lote).find('.data-inicio').find('input.inputDataInicio').attr('data-validate','loteDataInicio');
						break;
						case 'datafim':
							$(lote).find('.data-fim').removeClass('escondido');
							$(lote).find('.data-fim').find('input.inputDataFim').attr('data-validate','loteDataFim');
						break;
						case 'periodo':
							$(lote).find('.data-periodo').removeClass('escondido');
							$(lote).find('.data-periodo').find('input.inputDataInicio').attr('data-validate','loteDataInicio');
							$(lote).find('.data-periodo').find('input.inputDataFim').attr('data-validate','loteDataFim');
						break;
					}
					
					// ===== Atualizar validação do formulário.
					
					datasFormularioAtualizar();
				}
			};
			
			if('selectedValue' in p){
				$(lote).find('.loteVisibilidade').find('select').find('option[value="'+p.selectedValue+'"]').attr('selected','selected');
			}
			
			$(lote).find('.loteVisibilidade')
				.dropdown(dropdownObj)
			;
			
			// ===== Calendário.
			
			var text = {
				days: ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'],
				months: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Júlio', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
				monthsShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
				today: 'Hoje',
				now: 'Agora',
				am: 'AM',
				pm: 'PM'
			};
			
			// ===== Pegar os dados em 'dadosServidor'.
			
			var loteAux = dadosServidor.lotes.filter(function( obj ) {
				return obj.value === loteValue;
			});
			var loteAtual = loteAux[0];
			
			$(lote).find('.rangestart').calendar({
				text: text,
				today: true,
				ampm: false,
				type: 'datetime',
				endCalendar: $(lote).find('.rangeend'),
				initialDate: ('dataInicioDatetime' in loteAtual ? (loteAtual.dataInicioDatetime.length > 0 ? new Date(loteAtual.dataInicioDatetime) : null) : null),
				formatter: {
					datetime: function (date, settings) {
						if (!date) return '';
						
						var day = (date.getDate() < 10 ? '0' : '') + date.getDate();
						var month = ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1);
						var year = date.getFullYear();
						var hour = (date.getHours() < 10 ? '0' : '') + date.getHours();
						var minutes = (date.getMinutes() < 10 ? '0' : '') + date.getMinutes();
						
						return day + '/' + month + '/' + year + ' ' + hour + ':' + minutes;
					}
				}
			});
			
			$(lote).find('.rangeend').calendar({
				text: text,
				today: true,
				ampm: false,
				type: 'datetime',
				startCalendar: $(lote).find('.rangestart'),
				initialDate: ('dataFimDatetime' in loteAtual ? (loteAtual.dataFimDatetime.length > 0 ? new Date(loteAtual.dataFimDatetime) : null) : null),
				formatter: {
					datetime: function (date, settings) {
						if (!date) return '';
						
						var day = (date.getDate() < 10 ? '0' : '') + date.getDate();
						var month = ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1);
						var year = date.getFullYear();
						var hour = (date.getHours() < 10 ? '0' : '') + date.getHours();
						var minutes = (date.getMinutes() < 10 ? '0' : '') + date.getMinutes();
						
						return day + '/' + month + '/' + year + ' ' + hour + ':' + minutes;
					}
				}
			});
			
			$(lote).find('.startdate').calendar({
				text: text,
				today: true,
				ampm: false,
				type: 'datetime',
				initialDate: ('dataInicioDatetime' in loteAtual ? (loteAtual.dataInicioDatetime.length > 0 ? new Date(loteAtual.dataInicioDatetime) : null) : null),
				formatter: {
					datetime: function (date, settings) {
						if (!date) return '';
						
						var day = (date.getDate() < 10 ? '0' : '') + date.getDate();
						var month = ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1);
						var year = date.getFullYear();
						var hour = (date.getHours() < 10 ? '0' : '') + date.getHours();
						var minutes = (date.getMinutes() < 10 ? '0' : '') + date.getMinutes();
						
						return day + '/' + month + '/' + year + ' ' + hour + ':' + minutes;
					}
				}
			});
			
			$(lote).find('.enddate').calendar({
				text: text,
				today: true,
				ampm: false,
				type: 'datetime',
				initialDate: ('dataFimDatetime' in loteAtual ? (loteAtual.dataFimDatetime.length > 0 ? new Date(loteAtual.dataFimDatetime) : null) : null),
				formatter: {
					datetime: function (date, settings) {
						if (!date) return '';
						
						var day = (date.getDate() < 10 ? '0' : '') + date.getDate();
						var month = ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1);
						var year = date.getFullYear();
						var hour = (date.getHours() < 10 ? '0' : '') + date.getHours();
						var minutes = (date.getMinutes() < 10 ? '0' : '') + date.getMinutes();
						
						return day + '/' + month + '/' + year + ' ' + hour + ':' + minutes;
					}
				}
			});
			
			// ===== Botão duplicar lote.
			
			$(lote).find('.loteDuplicar').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				duplicarLote({
					loteValue:$(this).parents('.loteCont').attr('data-value')
				});
			});
			
			// ===== Botão adicionar variação.
			
			$(lote).find('.varAdicionar').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				adicionarVariacao({
					loteValue: $(this).parents('.loteCont').attr('data-value')
				});
			});
			
			// ===== Adicionar regras ao formulário.
			
			$(lote).find('.loteNome').attr('data-validate',loteValue+'-nome');
			
			$('.ui.form')
				.form('add rule', loteValue+'-nome', {
					rules: [
						{
							type   : 'empty',
							prompt : 'É obrigatório preencher o <b>nome do lote</b>'
						}
					]
				})
			;
		}
		
		function variacoesOperacoes(p = {}){
			// ===== Lote alvo.
			
			var lote = '.loteCont[data-value="'+p.loteValue+'"]';
			
			// ===== Mask Input
			
			$('.preco').mask("#.##0,00", {reverse: true});
			$('.quantidade').mask("#.##0", {reverse: true});
			
			// ===== Controle de exclusão de variação.
			
			$(lote).find('.varExcluir').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				if($(this).parents('.loteCont').attr('data-value') !== undefined){
					removerVariacao({
						value: $(this).parents('.loteCont').attr('data-value'),
						variacaoValue: $(this).parents('.varItem').attr('data-value')
					});
				}
			});
			
			// ===== Checkbox de gratuito.
			
			$(lote).find('.cheVariacaoGratuito')
				.checkbox({
					onChecked: function() {
						var preco = $(this).parents('.varItem').find('.variacaoPreco');
						
						if(preco.val().length == 0){
							preco.val('0');
						}
					},
					onUnchecked: function() {
						
					},
				});
			
		}
		
		function servicoTipo(id, start = false){
			switch(id){
				case 'simples':
					$('.simplesCont').show();
					$('.lotesVariacoesCont').hide();
					$('.controleTipo[data-id="simples"]').addClass(['active','blue']);
					$('.controleTipo[data-id="lotes-variacoes"]').removeClass(['active','blue']);
					
					dadosServidor.simples = true;
					
					$(formSelector).form('remove fields', ['variacaoNome','variacaoPreco','variacaoQuantidade']);
					$(formSelector).form('add rule', 'preco',{ rules : gestor.interface.regrasValidacao.preco.rules });
					$(formSelector).form('add rule', 'quantidade',{ rules : gestor.interface.regrasValidacao.quantidade.rules });
				break;
				case 'lotes-variacoes':
					$('.simplesCont').hide();
					$('.lotesVariacoesCont').show();
					$('.controleTipo[data-id="simples"]').removeClass(['active','blue']);
					$('.controleTipo[data-id="lotes-variacoes"]').addClass(['active','blue']);
					
					dadosServidor.simples = false;
					
					$(formSelector).form('remove fields', ['preco','quantidade']);
					$(formSelector).form('add rule', 'variacaoNome',{ rules : gestor.interface.regrasValidacao.variacaoNome.rules });
					$(formSelector).form('add rule', 'variacaoPreco',{ rules : gestor.interface.regrasValidacao.variacaoPreco.rules });
					$(formSelector).form('add rule', 'variacaoQuantidade',{ rules : gestor.interface.regrasValidacao.variacaoQuantidade.rules });
				break;
			}
			
			atualizarDadosServidor();
			
			$('input[name="tipo"]').val(id);
		}
		
		function datasFormularioAtualizar(){
			$(formSelector).form('remove fields', ['loteDataInicio','loteDataFim']);
			$(formSelector).form('add rule', 'loteDataInicio',{ rules : gestor.interface.regrasValidacao.loteDataInicio.rules });
			$(formSelector).form('add rule', 'loteDataFim',{ rules : gestor.interface.regrasValidacao.loteDataFim.rules });
		}
		
		function lotesStart(){
			// ===== Iniciação dos dados locais.
			
			dadosLocal = {
				loteCont : $('.lotesModelos .loteCont').clone(),
				varItem : $('.lotesModelos .varItem').clone()
			};
			
			$('.lotesModelos').html('');
			
			// ===== Formulário de validação.
			
			$(formSelector)
				.form({
					fields : (gestor.interface.regrasValidacao ? gestor.interface.regrasValidacao : {}),
					onSuccess(event, fields){
						salvarLotesDadosServidor();
						atualizarDadosServidor();
					}
				});
				
			// ===== Mask Input de preço e quantidade
			
			$('.preco').mask("#.##0,00", {reverse: true});
			$('.quantidade').mask("#.##0", {reverse: true});
			
			// ===== Dados do servidor iniciar.
			
			dadosServidor = JSON.parse($('#dadosServidor').val());
			
			// ===== Criar os itens do menu de lotes.
			
			var dropdownValues = new Array();
			
			for(var key in dadosServidor.lotes){
				var obj = dadosServidor.lotes[key];
				
				dropdownValues.push({
					value : obj.value,
					text : obj.nome,
					name : obj.nome
				});
			}
			
			// ===== Iniciação da variável global lotes.
			
			dadosServidor.numLotes = dadosServidor.lotes.length;
			dadosServidor.dropdownValues = dropdownValues;
			
			$('.loteCont[data-value="lote-1"]').show();
			
			// ===== Tipo de Serviço.
			
			servicoTipo((dadosServidor.simples ? 'simples' : 'lotes-variacoes'));
			
			$('.controleTipo').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var id = $(this).attr('data-id');
				
				servicoTipo(id);
			});
			
			// ===== Adicionar lote.
			
			$('.loteAdicionar').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				adicionarLote();
			});
			
			// ===== Controle de exclusão de lote.
			
			$(document.body).on('mouseup tap','.loteExcluir',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				removerLote({
					value: $(this).parents('.loteCont').attr('data-value')
				});
			});
			
			// ===== Alterar nome do lote no menu.
			
			$(document.body).on('blur','input.loteNome',function(e){
				var value = $(this).val();
				var objPai = $(this).parents('.loteCont');
				
				if(value.length == 0){
					value = 'Lote '+ objPai.attr('data-num');
					$(this).val(value);
				}
				
				dadosServidor.dropdownValues = dadosServidor.dropdownValues.map(item => {
					if(item.value === objPai.attr('data-value')){
						return {...item, text : value, name : value };
					} else {
						return item;
					}
				});
				
				$('.lotesMenu').dropdown('setup menu',{ values: dadosServidor.dropdownValues });
				$('.lotesMenu').dropdown('set selected',objPai.attr('data-value'));
			});
			
			// ===== Trocar entre lotes.
			
			$('.lotesMenu')
				.dropdown({
					allowCategorySelection: true,
					onChange: function(value, text, $choice){
						$('.loteCont').hide();
						$('.loteCont[data-value="'+value+'"]').show();
					}
				})
			;
			
			// ===== Listener da opção gratuita.
			
			$('.cheGratuito')
				.checkbox({
					onChecked: function() {
						if($('.preco[name="preco"]').val().length == 0){
							$('.preco[name="preco"]').val('0');
						}
					},
					onUnchecked: function() {
						
					},
				});
			
			// ===== Iniciar operações de lotes e variações.
			
			for(var key in dadosServidor.lotes){
				var value = dadosServidor.lotes[key].value;
				
				// ===== Operações do lote inicial.
				
				lotesOperacoes({
					loteValue : value
				});
				
				// ===== Operações da variação inicial.
				
				variacoesOperacoes({
					loteValue : value
				});
			}
		}
		
		lotesStart();
	}
});