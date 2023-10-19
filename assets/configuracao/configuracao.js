$(document).ready(function(){
	
	var eventDates = [];

	function configuracao_tipos_plugins(obj = null){
		// ===== TinyMCE opções.
		
		var tinySettings = {
			menubar: false,
			selector: 'textarea.tinymce',
			toolbar: 'code | bold italic underline | link image | alignleft aligncenter alignright alignjustify | styleselect fontselect fontsizeselect',
			plugins: "image link code",
			directionality: 'pt_BR',
			min_height: 450,
			language: 'pt_BR',
			language_url: gestor.raiz+'tinymce/langs/pt_BR.js',
			font_formats: 'Verdana=Verdana;Arial=arial,helvetica,sans-serif;',
			branding: false,
			valid_elements: '*[*]',
			init_instance_callback: tinyMCEReady
		};
		
		function tinyMCEReady(){
			
		}
		
		if(!gestor.configuracao.tinySettings){
			gestor.configuracao.tinySettings = {
				totalEditors : 0,
			};
		}
		
		// ===== Codemirror.
		
		var codeMirrorSettings = {
			lineNumbers: true,
			lineWrapping: true,
			styleActiveLine: true,
			matchBrackets: true,
			mode: "css",
			htmlMode: true,
			indentUnit: 4,
			theme: "tomorrow-night-bright",
			extraKeys: {
				"F11": function(cm) {
					cm.setOption("fullScreen", !cm.getOption("fullScreen"));
				},
				"Esc": function(cm) {
					if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
				}
			}
		};
		
		// ===== Calendário ptBR.
		
		var calendarPtBR = {
			days: ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'],
			months: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Júlio', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
			monthsShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
			today: 'Hoje',
			now: 'Agora',
			am: 'AM',
			pm: 'PM'
		};
		
		// ===== Variáveis do componente 'calendar' datas-multiplas.
		
		var calendarDatasMultiplasOpt = {
			text: calendarPtBR,
			type: 'date',
			closable: false,
			inline: true,
			formatter: {
				date: function (date, settings) {
					if (!date) return '';
					
					var day = (date.getDate() < 10 ? '0' : '') + date.getDate();
					var month = ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1);
					var year = date.getFullYear();
					
					return day + '/' + month + '/' + year;
				}
			},
			onChange: function(date,dateFormated,mode){
				var parentCont = $(this).parents('.datas-multiplas');
				var datesStr = parentCont.find('.calendar-dates-input').val();
				var dateFound = false;
				var id = parentCont.attr('data-id');

				if(date === null){
					return;
				}

				var dateFormatedID = (date.getDate() < 10 ? '0' : '') + date.getDate() + '/' + ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1) + '/' + date.getFullYear();
				
				if(datesStr !== undefined){
					var datesArr = datesStr.split('|');
					
					$.each(datesArr, function(index, date) {
						if(date == dateFormated){
							dateFound = true;
							return false;
						}
					});
				} else {
					datesStr = '';
				}
				
				if(!dateFound){
					var dateBtn = $('<a class="ui label transition noselect date-value" data-value="'+dateFormated+'">'+dateFormated+'<i class="delete icon date-delete"></i></a>');
					
					parentCont.find('.calendar-dates').append(dateBtn);
					
					parentCont.find('.calendar-dates-input').val(datesStr + (datesStr.length > 0 ? '|' : '') + dateFormated);

					eventDates[id].push({
						date,
						message: 'Data selecionada',
						class: 'green',
						variation: 'green',
						dateFormatedID
					});
				} else {
					var dateStrNew = '';
					$.each(datesArr, function(index, date) {
						if(date != dateFormated){
							dateStrNew = dateStrNew + (dateStrNew.length > 0 ? '|' : '') + date;
						}
					});

					parentCont.find('.calendar-dates').find('a[data-value="'+dateFormated+'"]').remove();

					parentCont.find('.calendar-dates-input').val(dateStrNew);

					eventDates[id] = eventDates[id].filter((item) => item.dateFormatedID !== dateFormatedID);
				}
				
				calendarDatasMultiplasOpt.eventDates = eventDates[id];
				$(this).calendar('destroy').html('').calendar(calendarDatasMultiplasOpt);
			}
		}

		window.calendarDatasMultiplasOpt = calendarDatasMultiplasOpt;

		// ===== Variáveis do componente 'calendar' data-hora.
		
		var calendarDataHoraOpt = {
			text: calendarPtBR,
			type: 'datetime',
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
			},
		}
		
		// ===== Variáveis do componente 'calendar' data.
		
		var calendarDataOpt = {
			text: calendarPtBR,
			type: 'date',
			formatter: {
				date: function (date, settings) {
					if (!date) return '';
					
					var day = (date.getDate() < 10 ? '0' : '') + date.getDate();
					var month = ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1);
					var year = date.getFullYear();
					
					return day + '/' + month + '/' + year;
				}
			},
		}

		// ===== Tratar diferença entre objeto e leitura inicial.
		
		if(obj !== null){
			if(obj.find('.dinheiro').length > 0){ obj.find('.dinheiro').mask("#.##0,00", {reverse: true}); }
			if(obj.find('.quantidade').length > 0){ obj.find('.quantidade').mask("#.##0", {reverse: true}); }
			if(obj.find('.ui.checkbox').length > 0){ obj.find('.ui.checkbox').checkbox(); }
			if(obj.find('.ui.calendar.multiplo').length > 0){ 
				obj.find('.ui.datas-multiplas').each(function(){
					var parentCont = $(this);
					var datesStr = parentCont.find('.calendar-dates-input').val();
					var dates = new Array();
					var id = parentCont.attr('data-id');

					if(datesStr !== undefined){
						if(datesStr.length > 0){
							var datesArr = datesStr.split('|');
							
							$.each(datesArr, function(index, dateFormated) {
								var dateBtn = $('<a class="ui label transition noselect date-value" data-value="'+dateFormated+'">'+dateFormated+'<i class="delete icon date-delete"></i></a>');
								
								parentCont.find('.calendar-dates').append(dateBtn);

								var dateArr = dateFormated.split('/');
								var date = new Date(parseInt(dateArr[2]), (parseInt(dateArr[1])-1), parseInt(dateArr[0]));
								var dateFormatedID = (date.getDate() < 10 ? '0' : '') + date.getDate() + '/' + ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1) + '/' + date.getFullYear();

								dates.push({
									date,
									message: 'Data selecionada',
									class: 'green',
									variation: 'green',
									dateFormatedID
								});
							});
						}
					}
				
					eventDates[id] = dates;
					calendarDatasMultiplasOpt.eventDates = dates;
				});
				obj.find('.ui.calendar.multiplo').calendar(calendarDatasMultiplasOpt);
			}
			
			if(obj.find('.ui.calendar.data-hora').length > 0){
				obj.find('.ui.calendar.data-hora').calendar(calendarDataHoraOpt);
				var dataHora = obj.find('.ui.calendar.data-hora').find('input.calendarInput').attr('value');
				obj.find('.ui.calendar.data-hora').calendar('set date',dataHora);
			}
			if(obj.find('.ui.calendar.data').length > 0){ 
				obj.find('.ui.calendar.data').calendar(calendarDataOpt);
				var dataHora = obj.find('.ui.calendar.data').find('input.calendarInput').attr('value');
				obj.find('.ui.calendar.data').calendar('set date',dataHora);
			}
			
			if(obj.find('.tinymce').length > 0){
				gestor.configuracao.tinySettings.totalEditors++;
				
				var id = gestor.configuracao.tinySettings.totalEditors;
				obj.find('.tinymce').attr('id','tinymce-'+id);
				
				var ed = new tinymce.Editor('tinymce-'+id, tinySettings, tinymce.EditorManager);

				ed.render();
			}
			
			if(obj.find('.js').length > 0 || obj.find('.html').length > 0 || obj.find('.css').length > 0){
				obj.find('.js,.html,.css').each(function(){
					if($(this).hasClass('js')) codeMirrorSettings.mode = 'javascript';
					if($(this).hasClass('html')) codeMirrorSettings.mode = 'htmlmixed';
					if($(this).hasClass('css')) codeMirrorSettings.mode = 'css';
					
					var codemirrorEle = $(this).get(0);
					
					var CodeMirrorInstance = CodeMirror.fromTextArea(codemirrorEle,codeMirrorSettings);
					
					CodeMirrorInstance.setSize('100%', 500);
					
					$(this).data('CodeMirrorInstance', CodeMirrorInstance);
				});
			}
		} else {
			$('.variavelCont').find('.dinheiro').mask("#.##0,00", {reverse: true});
			$('.variavelCont').find('.quantidade').mask("#.##0", {reverse: true});
			$('.variavelCont').find('.ui.checkbox').checkbox();
			$('.variavelCont').find('.ui.calendar.data-hora').calendar(calendarDataHoraOpt);
			$('.variavelCont').find('.ui.calendar.data').calendar(calendarDataOpt);
			$('.variavelCont').find('.ui.datas-multiplas').each(function(){
				var parentCont = $(this);
				var datesStr = parentCont.find('.calendar-dates-input').val();
				var dates = new Array();
				var id = parentCont.attr('data-id');
				
				if(datesStr !== undefined){
					if(datesStr.length > 0){
						var datesArr = datesStr.split('|');
						
						$.each(datesArr, function(index, dateFormated) {
							var dateBtn = $('<a class="ui label transition noselect date-value" data-value="'+dateFormated+'">'+dateFormated+'<i class="delete icon date-delete"></i></a>');
							
							parentCont.find('.calendar-dates').append(dateBtn);

							var dateArr = dateFormated.split('/');
							var date = new Date(parseInt(dateArr[2]), (parseInt(dateArr[1])-1), parseInt(dateArr[0]));
							var dateFormatedID = (date.getDate() < 10 ? '0' : '') + date.getDate() + '/' + ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1) + '/' + date.getFullYear();

							dates.push({
								date,
								message: 'Data selecionada',
								class: 'green',
								variation: 'green',
								dateFormatedID
							});
						});
					}
				}
				
				eventDates[id] = dates;
				calendarDatasMultiplasOpt.eventDates = dates;
			});
			$('.variavelCont').find('.ui.calendar.multiplo').calendar(calendarDatasMultiplasOpt);
			
			$('.variavelCont').find('.tinymce').each(function(){
				gestor.configuracao.tinySettings.totalEditors++;
				
				var id = gestor.configuracao.tinySettings.totalEditors;
				$(this).attr('id','tinymce-'+id);
				
				var ed = new tinymce.Editor('tinymce-'+id, tinySettings, tinymce.EditorManager);

				ed.render();
			});
			
			$('.variavelCont').find('.js,.html,.css').each(function(){
				if($(this).hasClass('js')) codeMirrorSettings.mode = 'javascript';
				if($(this).hasClass('html')) codeMirrorSettings.mode = 'htmlmixed';
				if($(this).hasClass('css')) codeMirrorSettings.mode = 'css';
				
				var codemirrorEle = $(this).get(0);
				
				var CodeMirrorInstance = CodeMirror.fromTextArea(codemirrorEle,codeMirrorSettings);
				
				CodeMirrorInstance.setSize('100%', 500);
				
				$(this).data('CodeMirrorInstance', CodeMirrorInstance);
			});
		}
	}
	
	function configuracao_administracao_alterar_tipo(obj,campo){
		// ===== Pegar o número do campo.
		
		var num = obj.attr('data-num');
		
		// ===== Pegar campo atual.
		
		var campoAtual = obj.find('.variavelValor').find('.campo');
		
		// ===== Pegar o valor do campo.
		
		var campoValor = '';
		
		var classList = campoAtual.attr('class').split(/\s+/);
		$.each(classList, function(index, item) {
			switch(item){
				case 'bool':
					campoValor = campoAtual.find('input').prop('checked');
					return false;
				break;
				case 'string':
				case 'text':
				case 'number':
				case 'quantidade':
				case 'dinheiro':
				case 'js':
				case 'css':
				case 'html':
					campoValor = campoAtual.val();
					return false;
				break;
				case 'datas-multiplas':
					campoValor = campoAtual.find('input.calendar-dates-input').val();
					return false;
				break;
				case 'data-hora':
				case 'data':
					campoValor = campoAtual.find('input.calendarInput').val();
					return false;
				break;
				case 'tinymce':
					campoValor = tinymce.get(campoAtual.attr('id')).getContent();
					return false;
				break;
			}
		});
		
		// ===== Pegar o campo do modelo.
		
		var campoObj = $('.camposModelos').find('.campo.'+campo).clone();
		
		// ===== Alterar informações do campo.
		
		switch(campo){
			case 'bool':
				campoObj.find('input').attr('name','valor-'+num);
				if(!campoValor) campoObj.find('input').prop('checked',false);
			break;
			case 'text':
			case 'tinymce':
			case 'js':
			case 'css':
			case 'html':
				campoObj.attr('name','valor-'+num);
				campoObj.html(campoValor);
			break;
			case 'datas-multiplas':
				campoObj.find('input.calendar-dates-input').attr('name','valor-'+num);
				campoObj.find('input.calendar-dates-input').attr('value',campoValor);
			break;
			case 'data-hora':
			case 'data':
				campoObj.find('input.calendarInput').attr('name','valor-'+num);
				campoObj.find('input.calendarInput').attr('value',campoValor);
			break;
			default:
				campoObj.attr('name','valor-'+num);
				campoObj.attr('value',campoValor);
		}
		
		// ===== Incluir o campo no componete adicionar.
		
		obj.find('.variavelValor').html(campoObj);
		
		// ===== Listeners do campo.
		
		configuracao_tipos_plugins(obj);
	}
	
	function configuracao_administracao_variavel_remover(){
		// ===== Atualizar o total de itens.
		
		gestor.configuracao.totalItens--;
		
		// ===== Caso o total de variáveis seja zero, remover o botão adicionar abaixo.
		
		if(gestor.configuracao.totalItens <= 0){
			$('.componenteAdicionarBaixo').addClass('escondido');
		}
		
		// ===== Reiniciar o formulário.
		
		$.formReiniciar();
	}
	
	function configuracao_administracao_variavel_adicionar(abaixo){
		// ===== Pegar o total de variáveis.
		
		var variaveisTotal = parseInt($('#variaveis-total').val());
		var num = variaveisTotal;
		
		// ===== Pegar o modelo do formulário.
		
		var adicionar = gestor.configuracao.modelos['adicionar'].clone();
		
		// ===== Definir o num do objeto.
		
		adicionar.attr('data-num',num);
		
		// ===== Alterar informações do componente.
		
		adicionar.find('.identificador').attr('name','id-'+num);
		adicionar.find('.grupo').attr('name','grupo-'+num);
		adicionar.find('.tipo').attr('name','tipo-'+num);
		adicionar.find('.descricao').attr('name','descricao-'+num);
		
		// ===== Pegar o campo do modelo tipo string.
		
		var campo = $('.camposModelos').find('.campo.string').clone();
		
		// ===== Alterar informações do campo.
		
		campo.attr('name','valor-'+num);
		campo.attr('value','');
		
		// ===== Incluir o campo no componete adicionar.
		
		adicionar.find('.variavelValor').html(campo);
		
		// ===== Incluir o modelo na tela.
		
		if(abaixo){
			adicionar.appendTo('.variaveisCont');
		} else {
			adicionar.prependTo('.variaveisCont');
		}
		
		// ===== Incluir o botão adicionar abaixo caso o mesmo esteja escondido.
		
		if($('.componenteAdicionarBaixo').hasClass('escondido')){
			$('.componenteAdicionarBaixo').removeClass('escondido');
		}
		
		// ===== Atualizar o total de variáveis e itens.
		
		variaveisTotal++;
		gestor.configuracao.totalItens++;
		$('#variaveis-total').val(variaveisTotal);
		
		// ===== Listeners deste componente.
		
		adicionar.find('.adicionarBtnCancelar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = $(this);
			
			obj.parents('.adicionar').remove();
			
			configuracao_administracao_variavel_remover();
		});
		
		adicionar.find('.ui.dropdown').dropdown({
			onChange : function(value, text){
				configuracao_administracao_alterar_tipo(adicionar,value);
			}
		});
		
		configuracao_tipos_plugins(adicionar);
		
		// ===== Reiniciar o formulário.
		
		$.formReiniciar();
	}
	
	function configuracao_administracao_variavel_editar(obj){
		// ===== Pegar os dados do objeto antes de editar.
		
		var variavelCont = obj.parents('.variavelCont');
		
		var variavelReferencia = variavelCont.attr('data-id');
		var variavelTipo = variavelCont.attr('data-tipo');
		var variavelNum = variavelCont.attr('data-num');
		var valorObj = variavelCont.find('.variavelValor').find('.campo');
		
		var variavelNome = variavelCont.find('.variavelNome').html();
		var variavelGrupo = variavelCont.find('.variavelGrupo').html();
		var variavelDescricao = variavelCont.find('.variavelDescricao').html();
		
		// ===== Pegar o valor da variável.
		
		var valor = '';
		
		switch(variavelTipo){
			case 'bool':
				valor = valorObj.find('input').prop('checked');
			break;
			case 'tinymce':
				valor = tinymce.get(valorObj.attr('id')).getContent();
			break;
			case 'js':
			case 'css':
			case 'html':
				var myInstance = valorObj.data('CodeMirrorInstance');
				
				valor = myInstance.getValue();
			break;
			case 'datas-multiplas':
				valor = valorObj.find('input.calendar-dates-input').val();
			break;
			case 'data-hora':
			case 'data':
				valor = valorObj.find('input.calendarInput').val();
			break;
			default:
				valor = valorObj.val();
		}
		
		// ===== Pegar o número da variável.
		
		var num = variavelNum;
		
		// ===== Pegar o modelo do formulário.
		
		var editar = gestor.configuracao.modelos['editar'].clone();
		
		// ===== Definir o num do objeto.
		
		editar.attr('data-num',num);
		
		// ===== Alterar informações do componente.
		
		editar.find('.identificador').attr('name','id-'+num);
		editar.find('.grupo').attr('name','grupo-'+num);
		editar.find('.tipo').attr('name','tipo-'+num);
		editar.find('.descricao').attr('name','descricao-'+num);
		
		// ===== Alterar valores dos campos.
		
		editar.find('.identificador').attr('value',variavelNome);
		editar.find('.grupo').attr('value',variavelGrupo);
		editar.find('.descricao').attr('value',variavelDescricao);
		
		// ===== Pegar o campo do modelo tipo string.
		
		var campo = $('.camposModelos').find('.campo.'+variavelTipo).clone();
		
		// ===== Alterar informações do campo.
		
		switch(variavelTipo){
			case 'bool':
				campo.find('input').attr('name','valor-'+num);
				campo.find('input').prop('checked',valor);
			break;
			case 'text':
			case 'tinymce':
			case 'css':
			case 'js':
			case 'html':
				campo.attr('name','valor-'+num);
				campo.html(valor);
			break;
			case 'datas-multiplas':
				campo.find('input.calendar-dates-input').attr('name','valor-'+num);
				campo.find('input.calendar-dates-input').attr('value',valor);
			break;
			case 'data-hora':
			case 'data':
				campo.find('input.calendarInput').attr('name','valor-'+num);
				campo.find('input.calendarInput').attr('value',valor);
			break;
			default:
				campo.attr('name','valor-'+num);
				campo.attr('value',valor);
		}
		
		// ===== Alterar a referência da variável.
		
		editar.find('.variavelReferencia').attr('name','ref-'+num);
		editar.find('.variavelReferencia').attr('value',variavelReferencia);
		
		// ===== Incluir o campo no componete editar.
		
		editar.find('.variavelValor').html(campo);
		
		// ===== Incluir o modelo na tela.
		
		variavelCont.after(editar);
		
		// ===== Remover o componente atual.
		
		variavelCont.remove();
		
		// ===== Listeners deste componente.
		
		editar.find('.editarBtnCancelar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj2 = $(this);
			
			obj2.parents('.editar').after(variavelCont);
			obj2.parents('.editar').remove();
			
			switch(variavelTipo){
				case 'tinymce':
					var campo2 = $('.camposModelos').find('.campo.tinymce').clone();
					
					campo2.attr('name','valor-'+num);
					campo2.html(valor);
					
					variavelCont.find('.variavelValor').html(campo2);
				break;
				case 'js':
				case 'css':
				case 'html':
					var campo2 = $('.camposModelos').find('.campo.'+variavelTipo).clone();
					
					campo2.attr('name','valor-'+num);
					campo2.html(valor);
					
					variavelCont.find('.variavelValor').html(campo2);
				break;
			}
			
			configuracao_tipos_plugins(variavelCont);
		});
		
		editar.find('.ui.dropdown').dropdown();
		editar.find('.ui.dropdown').dropdown('set selected',variavelTipo);
		editar.find('.ui.dropdown').dropdown({
			onChange : function(value, text){
				configuracao_administracao_alterar_tipo(editar,value);
			}
		});
		
		configuracao_tipos_plugins(editar);
		
		// ===== Reiniciar o formulário.
		
		$.formReiniciar();
	}
	
	function configuracao_administracao_iniciar(){
		// ===== Pegar os modelos.
		
		gestor.configuracao.modelos = new Array();
		gestor.configuracao.modelosRemove = true;
		
		// ===== Pegar o total de itens.
		
		gestor.configuracao.totalItens = parseInt($('#variaveis-total').val());
		
		// ===== Modelos de itens.
		
		var modelos = ['adicionar','mostrar','editar'];
		
		$('.modeloItens .card').each(function(){
			var obj = $(this);
			
			modelos.forEach(function (ele){
				if(obj.hasClass(ele)){
					gestor.configuracao.modelos[ele] = obj.clone();
					if(gestor.configuracao.modelosRemove)obj.remove();
				}
			});
		});
		
		// ===== Listeners principais.
		
		$('.variavelBtnAdicionar,.variavelBtnAdicionarAbaixo').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var abaixo = false;
			var obj = $(this);
			
			if(obj.hasClass('variavelBtnAdicionarAbaixo')){
				abaixo = true;
			}
			
			configuracao_administracao_variavel_adicionar(abaixo);
		});
		
		$(document.body).on('mouseup tap','.variavelBtnExcluir',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = $(this);
			
			$('.ui.modal.confirm').modal({
				onApprove: function() {
					obj.parents('.variavelCont').remove();
					
					configuracao_administracao_variavel_remover();
				}
			});
			
			$('.ui.modal.confirm').modal('show');
		});
		
		$(document.body).on('mouseup tap','.variavelBtnEditar',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = $(this);
			
			configuracao_administracao_variavel_editar(obj);
		});
		
		$(document.body).on('mouseup tap','.variavelNome',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			navigator.clipboard.writeText($(this).html());
		});
		
		$(document.body).on('mouseup tap','.date-value',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var parentCont = $(this).parents('.calendar-dates');
			var thisDate = this;
			
			if(e.ctrlKey || e.shiftKey){
				if(e.shiftKey){
					var makeActive = false;
					parentCont.find('.date-value').each(function(){
						if(thisDate === this || $(this).hasClass('last-active')){
							if(!makeActive){
								makeActive = true;
							} else {
								return false;
							}
						} else {
							if(makeActive){
								$(this).addClass('active');
							}
						}
					});
				}
			} else {
				parentCont.find('.date-value').each(function(){
					$(this).removeClass('active');
				});
			}
			
			parentCont.find('.date-value').removeClass('last-active');
			
			$(thisDate).addClass('active');
			$(thisDate).addClass('last-active');
		});
		
		$(document.body).on('mouseup tap','.date-delete',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var parentCont = $(this).parents('.calendar-dates');
			var datesInput = $(this).parents('.datas-multiplas').find('.calendar-dates-input');
			var datesStr = datesInput.val();
			var inputRemoveDates = [];
			var id = parentCont.attr('data-id');
			
			var dateObj = $(this).parents('.date-value');
			inputRemoveDates.push(dateObj.attr('data-value'));
			
			dateObj.remove();
			
			parentCont.find('.date-value').each(function(){
				if($(this).hasClass('active')){
					inputRemoveDates.push($(this).attr('data-value'));
					$(this).remove();
				}
			});
			
			if(datesStr !== undefined){
				var datesArr = datesStr.split('|');
				var datesUpdated = '';
				
				$.each(datesArr, function(index, currentDate) {
					var found = false;
					$.each(inputRemoveDates, function(index2, removeDate) {
						if(currentDate == removeDate){
							found = true;
							return false;
						}
					});
					
					if(!found){
						datesUpdated = datesUpdated + (datesUpdated.length > 0 ? '|' : '') + currentDate;
					} else {
						eventDates[id] = eventDates[id].filter((item) => item.dateFormatedID !== currentDate);
					}
				});
				
				datesInput.val(datesUpdated);
			}

			var calendarDatasMultiplasOpt = window.calendarDatasMultiplasOpt;

			calendarDatasMultiplasOpt.eventDates = eventDates[id];
			parentCont.parents('.datas-multiplas').find('.ui.calendar.multiplo').calendar('destroy').html('').calendar(calendarDatasMultiplasOpt);
			
			e.stopPropagation();
		});
		
		configuracao_tipos_plugins();
		
		// ===== Campo Identificador e Grupo
		
		$(document.body).on('keyup','.identificador',function(e){
			if(e.which == 9) return false;
			
			var value = $(this).val();
			
			$.input_delay_to_change({
				obj_ref:this,
				trigger_selector:'#gestor-listener',
				trigger_event:'identificador-change',
				value:value
			});
		});
		
		$(document.body).on('identificador-change','#gestor-listener',function(e,value,p){
			if(!p) p = {};
			
			value = formatar_id(value);
			$(p.obj).val(value);
		});
		
		$(document.body).on('keyup','.grupo',function(e){
			if(e.which == 9) return false;
			
			var value = $(this).val();
			
			$.input_delay_to_change({
				obj_ref:this,
				trigger_selector:'#gestor-listener',
				trigger_event:'grupo-change',
				value:value
			});
		});
		
		$(document.body).on('grupo-change','#gestor-listener',function(e,value,p){
			if(!p) p = {};
			
			value = formatar_id(value);
			$(p.obj).val(value);
		});
		
		// ===== Formatar ID regras.
		
		function formatar_id(id){
			id = id.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); // Trocar todos os caracteres com acentos pelos seus similares sem acento.
			id = id.replace(/[^a-zA-Z0-9 \-]/g,''); // Remover todos os caracteres que não são alfanuméricos ou espaço ou traço.
			id = id.toLowerCase(); // Passar para letras minúsculas
			id = id.trim(); // Remover espaço do início e fim.
			id = id.replace(/\s/g,'-'); // Trocar todos os espaços por traço.
			id = id.replace(/\-{2,}/g,'-'); // Remover a repetição de traços para um único traço.
			
			return id;
		}
	}
	
	function configuracao_hosts_iniciar(){
		// ===== Listeners principais.
		
		$(document.body).on('mouseup tap','.variavelNome',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			navigator.clipboard.writeText($(this).html());
		});
		
		$('.variavelValorBTN')
			.popup()
		;
		
		$('.escondido').hide();
		
		$(document.body).on('mouseup tap','.variavelValorBTN',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var variavelCont = $(this).parents('.variavelCont');
			var valorPadrao = variavelCont.find('.valorPadrao').html();
			var variavelTipo = variavelCont.attr('data-tipo');
			var valorObj = variavelCont.find('.variavelValor').find('.campo');
			
			switch(variavelTipo){
				case 'bool':
					valorObj.find('input').prop('checked',valorPadrao);
				break;
				case 'tinymce':
					tinymce.get(valorObj.attr('id')).setContent(valorPadrao);
				break;
				case 'js':
				case 'css':
				case 'html':
					var myInstance = valorObj.data('CodeMirrorInstance');
					
					myInstance.getDoc().setValue(valorPadrao);
				break;
				case 'datas-multiplas':
					valorObj.find('input.calendar-dates-input').val(valorPadrao);
				break;
				case 'data-hora':
				case 'data':
					valorObj.find('input.calendarInput').val(valorPadrao);
				break;
				default:
					valorObj.val(valorPadrao);
			}
		});
		
		// ===== Listeners principais.
		
		$(document.body).on('mouseup tap','.date-value',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var parentCont = $(this).parents('.calendar-dates');
			var thisDate = this;
			
			if(e.ctrlKey || e.shiftKey){
				if(e.shiftKey){
					var makeActive = false;
					parentCont.find('.date-value').each(function(){
						if(thisDate === this || $(this).hasClass('last-active')){
							if(!makeActive){
								makeActive = true;
							} else {
								return false;
							}
						} else {
							if(makeActive){
								$(this).addClass('active');
							}
						}
					});
				}
			} else {
				parentCont.find('.date-value').each(function(){
					$(this).removeClass('active');
				});
			}
			
			parentCont.find('.date-value').removeClass('last-active');
			
			$(thisDate).addClass('active');
			$(thisDate).addClass('last-active');
		});
		
		$(document.body).on('mouseup tap','.date-delete',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var parentCont = $(this).parents('.calendar-dates');
			var datesInput = $(this).parents('.datas-multiplas').find('.calendar-dates-input');
			var datesStr = datesInput.val();
			var inputRemoveDates = [];
			var id = parentCont.attr('data-id');
			
			var dateObj = $(this).parents('.date-value');
			inputRemoveDates.push(dateObj.attr('data-value'));
			
			dateObj.remove();
			
			parentCont.find('.date-value').each(function(){
				if($(this).hasClass('active')){
					inputRemoveDates.push($(this).attr('data-value'));
					$(this).remove();
				}
			});
			
			if(datesStr !== undefined){
				var datesArr = datesStr.split('|');
				var datesUpdated = '';
				
				$.each(datesArr, function(index, currentDate) {
					var found = false;
					$.each(inputRemoveDates, function(index2, removeDate) {
						if(currentDate == removeDate){
							found = true;
							return false;
						}
					});
					
					if(!found){
						datesUpdated = datesUpdated + (datesUpdated.length > 0 ? '|' : '') + currentDate;
					} else {
						eventDates[id] = eventDates[id].filter((item) => item.dateFormatedID !== currentDate);
					}
				});
				
				datesInput.val(datesUpdated);
			}
			
			var calendarDatasMultiplasOpt = window.calendarDatasMultiplasOpt;

			calendarDatasMultiplasOpt.eventDates = eventDates[id];

			parentCont.parents('.datas-multiplas').find('.ui.calendar.multiplo').calendar('destroy').html('').calendar(calendarDatasMultiplasOpt);
			
			e.stopPropagation();
		});
		
		configuracao_tipos_plugins();
	}
	
	function configuracao(){
		// ===== Identificador da opção de administracao.
		
		if($('#_gestor-configuracao-administracao').length > 0){
			configuracao_administracao_iniciar();
		}
		// ===== Identificador da opção de administracao.
		
		if($('#_gestor-configuracao-hosts').length > 0){
			configuracao_hosts_iniciar();
		}
	}
	
	configuracao();
	
});