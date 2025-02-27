$(document).ready(function(){
	
	if($('#files-list-cont').length > 0){
		function files_list(){
			// ===== Filtragem de Resultados
			
			var text = {
				days: ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'],
				months: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Júlio', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
				monthsShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
				today: 'Hoje',
				now: 'Agora',
				am: 'AM',
				pm: 'PM'
			};
			
			$('#rangestart').calendar({
				text: text,
				type: 'month',
				endCalendar: $('#rangeend')
			});
			$('#rangeend').calendar({
				text: text,
				type: 'month',
				startCalendar: $('#rangestart')
			});
			
			$('.ui.dropdown')
				.dropdown()
			;
			
			// ===== Popup do botão adicionar
			
			$('.segment .button').popup({
				delay: {
					show: 150,
					hide: 0
				},
				position:'top right',
				variation:'inverted'
			});
			
			// ===== Arquivos Regras Iniciais
			
			var filtrado = false;
			var clear = false;
			var button_id = 'lista-mais-resultados';
			var listaPaginaAtual = 0;
			var totalPaginas = gestor.arquivos.totalPaginas;
			
			if(parseInt(gestor.arquivos.totalArquivosSemFiltrar) == 0){
				$('.withoutResultsCont').removeClass('hidden');
			} else {
				$('.filesFilterCont').removeClass('hidden');
				
				if(parseInt(gestor.arquivos.total) > 0){
					$('.listFilesCont').removeClass('hidden');
					
					if(parseInt(gestor.arquivos.totalPaginas) > 1){
						$('.listMoreResultsCont').removeClass('hidden');
					}
				} else {
					$('.withoutFilesCont').removeClass('hidden');
				}
			}
			
			// ===== Dados iniciais dos campos de filtro
			
			if(typeof gestor.arquivos.dataDe !== typeof undefined && gestor.arquivos.dataDe !== false){
				$('#rangestart').calendar('set date',gestor.arquivos.dataDe);
				filtrado = true;
			}
			
			if(typeof gestor.arquivos.dataAte !== typeof undefined && gestor.arquivos.dataAte !== false){
				$('#rangeend').calendar('set date',gestor.arquivos.dataAte);
				filtrado = true;
			}
			
			if(typeof gestor.arquivos.categorias !== typeof undefined && gestor.arquivos.categorias !== false){
				$('#categories').dropdown('set selected',gestor.arquivos.categorias);
				filtrado = true;
			}
			
			if(typeof gestor.arquivos.order !== typeof undefined && gestor.arquivos.order !== false){
				$('#order').dropdown('set selected',gestor.arquivos.order);
				filtrado = true;
			}
			
			// ===== Carregar Mais Arquivos, Filtrar e Liberar
			
			$('.clearButton').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				$('#rangestart').calendar('clear');
				$('#rangeend').calendar('clear');
				$('#categories').dropdown('clear');
				$('#order').dropdown('set selected','alphabetical-asc');
				
				clear = true;
			});
			
			$('.filterButton').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				filtrado = true;
			});
			
			$('#'+button_id+',.filterButton,.clearButton').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var ajaxOpcao = 'lista-mais-resultados';
				
				if($(this).attr('id') == button_id){
					listaPaginaAtual++;
				} else {
					if(!filtrado){
						return;
					}
					
					listaPaginaAtual = 0;
				}
				
				var pagina = listaPaginaAtual;
				var opcao = 'listar-arquivos';
				
				// ===== Filtros
				
				var filtros = {};
				
				if($('#rangestart').calendar('get date')) filtros.dataDe = $('#rangestart').calendar('get date');
				if($('#rangeend').calendar('get date')) filtros.dataAte = $('#rangeend').calendar('get date');
				if($('#categories').dropdown('get value').length > 0) filtros.categorias = $('#categories').dropdown('get value');
				
				filtros.order = $('#order').dropdown('get value');
				
				// ===== Buscar no servidor os dados
				
				var data = {
					opcao : opcao,
					ajax : 'sim',
					ajaxOpcao : ajaxOpcao,
					pagina : pagina,
					filtros : JSON.stringify(filtros)
				};
				
				if('paginaIframe' in gestor){
					data.paginaIframe = true;
				}
				
				$.ajax({
					type: 'POST',
					url: gestor.raiz + gestor.moduloId + '/',
					data: data,
					dataType: 'json',
					beforeSend: function(){
						$('#gestor-listener').trigger('carregar_abrir');
					},
					success: function(dados){
						switch(dados.status){
							case 'Ok':
							// ===== Atualizar o conteiner da lista de arquivos
								if(listaPaginaAtual == 0){
									$('#files-list-cont').html('');
								}
								
								$('#files-list-cont').append(dados.pagina);
								
								// ===== Mostrar ou Ocultar os conteiners
								
								if(parseInt(dados.totalArquivosSemFiltrar) == 0){
									$('.withoutResultsCont').removeClass('hidden');
									$('.withoutFilesCont').addClass('hidden');
									$('.listFilesCont').addClass('hidden');
									$('.filesFilterCont').addClass('hidden');
								} else {
									$('.withoutResultsCont').addClass('hidden');
									
									if(parseInt(dados.total) > 0){
										$('.listFilesCont').removeClass('hidden');
										$('.withoutFilesCont').addClass('hidden');
										
										if(parseInt(dados.totalPaginas) > 1){
											$('.listMoreResultsCont').removeClass('hidden');
										}
									} else {
										$('.listFilesCont').addClass('hidden');
										$('.withoutFilesCont').removeClass('hidden');
									}
								}
								
								// ===== Mostrar ou Ocultar o botão mais dados
								
								totalPaginas = parseInt(dados.totalPaginas);
								
								if(listaPaginaAtual >= totalPaginas - 1){
									$('#'+button_id).hide();
								} else {
									$('#'+button_id).show();
								}
								
								if(clear){
									clear = false;
									filtrado = false;
								}
							break;
							default:
								console.log('ERROR - '+ajaxOpcao+' - '+dados.status);
							
						}
						
						$('#gestor-listener').trigger('carregar_fechar');
					},
					error: function(txt){
						switch(txt.status){
							case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"),"_self"); break;
							default:
								console.log('ERROR AJAX - '+ajaxOpcao+' - Dados:');
								console.log(txt);
								$('#gestor-listener').trigger('carregar_fechar');
						}
					}
				});
			});
			
			// ===== Funções dos Botões
			
			$(document.body).on('mouseup tap','.fileSelect',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var sendMessage = function (msg) {
					// Make sure you are sending a string, and to stringify JSON
					window.parent.postMessage(msg, '*');
				};
				
				var messageParent = {
					moduloId : gestor.moduloId,
					moduloOpcao : gestor.moduloOpcao,
					data : $(this).attr('data-dados'),
				};
				
				sendMessage(JSON.stringify(messageParent));
			});
			
			$(document.body).on('mouseup tap','.fileCopyUrl',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				navigator.clipboard.writeText($(this).attr('data-url'));
			});
			
			$(document.body).on('mouseup tap','.fileDelete',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var id = $(this).attr('data-id');
				var opcao = 'listar-arquivos';
				var ajaxOpcao = 'excluir-arquivo';
				
				$('.ui.modal.confirm').modal({
					onApprove: function() {
						$.ajax({
							type: 'POST',
							url: gestor.raiz + gestor.moduloId + '/',
							data: { 
								opcao : opcao,
								ajax : 'sim',
								ajaxOpcao : ajaxOpcao,
								id : id
							},
							dataType: 'json',
							beforeSend: function(){
								//$('#gestor-listener').trigger('carregar_abrir');
							},
							success: function(dados){
								switch(dados.status){
									case 'Ok':
										$('.fileCont[data-id="'+id+'"]').remove();
									break;
									default:
										console.log('ERROR - '+ajaxOpcao+' - '+dados.status);
									
								}
								
								//$('#gestor-listener').trigger('carregar_fechar');
							},
							error: function(txt){
								switch(txt.status){
									case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"),"_self"); break;
									default:
										console.log('ERROR AJAX - '+ajaxOpcao+' - Dados:');
										console.log(txt);
										//$('#gestor-listener').trigger('carregar_fechar');
								}
							}
						});
					}
				});
				
				$('.ui.modal.confirm').modal('show');
			});
		}
		
		files_list();
	}
	
	// ===== Dados Iniciais do File Uploader
	
	var fileUploadSelector = '#fileupload';
	var fileUploadObj = {
		imgAtual: {},
		files: new Array(),
		total: 0,
		subTotal: 0,
	};
	
	if($(fileUploadSelector).length > 0){
		
		$(fileUploadSelector).fileupload({
			url: gestor.raiz + gestor.moduloId + '/',
			dataType: 'json',
			sequentialUploads: true,
			formData: function(form) {
				var data = new Array(
					{ name:'opcao' , value:'upload'},
					{ name:'ajax' , value:'sim'},
					{ name:'ajaxOpcao' , value:'uploadFile'}
				);
				
				var categorias = $('.ui.dropdown').dropdown('get value');
				
				if(categorias.length > 0){
					data.push({ name:'categorias' , value:categorias});
				}
				
				return data;
			},
			change: function(e, data) {
				fileUploadObj.subTotal = 0;
			},
			drop: function(e, data) {
				fileUploadObj.subTotal = 0;
			},
			add: function(e, data) {
				var file = data.files[0];
				var originalFile = data.originalFiles[fileUploadObj.subTotal];
				var imgSrc = '';
				var limitText = 40;
				var limitTextEnd = 10;
				var textSep = '...';
				
				// ===== Imagem padrão caso não seja um arquivo de imagem
				
				if(file.type.match(/image\//) == 'image/'){
					imgSrc = gestor.raiz+'images/imagem-padrao.png';
				} else if(file.type.match(/video\//) == 'video/'){
					imgSrc = gestor.raiz+'images/video-padrao.png';
				} else if(file.type.match(/audio\//) == 'audio/'){
					imgSrc = gestor.raiz+'images/audio-padrao.png';
				} else {
					imgSrc = gestor.raiz+'images/file-padrao.png';
				}
				
				// ===== Formatação dos campos
				
				var fileLastModified = (new Date(file.lastModified)).toLocaleString();
				var fileName = file.name;
				var fileType = file.type;
				var fileSize = formatBytes(file.size);
				
				if(fileName.length > limitText){
					fileName = fileName.substr(0, limitText - limitTextEnd) + textSep + fileName.substr(-limitTextEnd, limitTextEnd);
				}
				
				// ===== População dos campos 
				
				var fileCel = gestor.arquivosCel;
				
				fileCel = fileCel.replace("#file-img-id#",'file-'+fileUploadObj.total);
				fileCel = fileCel.replace("#file-img-src#",imgSrc);
				fileCel = fileCel.replace("#file-name#",fileName);
				fileCel = fileCel.replace("#file-last-modified#",fileLastModified);
				fileCel = fileCel.replace("#file-size#",fileSize);
				fileCel = fileCel.replace("#file-type#",fileType);
				
				// ====== Inclusão do contexto no data e criação do listenner click
				
				var fileObj = $(fileCel)
					.prependTo('#files-cont');
				
				fileObj.find('.fileSend')
					.click(function () {
						fileObj.find('.fileProgress').removeClass('hidden');
						fileObj.find('.fileWait').remove();
						$('.fileProgressAll').removeClass('hidden');
						$('.fileWaitAll').addClass('hidden');
						data.submit();
						$(this).remove();
					});
				
				fileObj.find('.fileCancel')
					.click(function () {
						if(!data.done){
							fileObj.remove();
						}
						
						data.cancelar = true;
						
						// ===== Remover controles globais caso todos os arquivos forem 'done'
						
						var doneAll = true;
						var file = data.files[0];
						var id = '#file-'+data.id;
						var files = fileUploadObj.files;
						
						for(var i=0;i<files.length;i++){
							if(id == files[i].id){
								fileUploadObj.files[i].cancelar = true;
							} else if(!files[i].done && !files[i].cancelar){
								doneAll = false;
							}
						}
						
						if(doneAll){
							$('.fileButtonsAll').addClass('hidden');
							$('.fileWaitAll').addClass('hidden');
							$('.filesHeader').removeClass('hidden');
							$('.fileProgressAll').addClass('hidden');
						}
						
						// ===== Abortar upload
						
						data.abort();
					});
				
				data.context = fileObj;
				
				// ===== Mostrar controles globais
				
				$('.fileButtonsAll').removeClass('hidden');
				$('.fileWaitAll').removeClass('hidden');
				$('.filesHeader').removeClass('hidden');
				$('.fileProgressAll').addClass('hidden');
				
				// ===== Atualizar imagem do arquivo
				
				if(file.type.match(/image\//) == 'image/'){
					originalFile.id = '#file-'+fileUploadObj.total;
					if(originalFile){
						
						var reader = new FileReader();
						reader.onload = (function(fileBefore){
							var fileBeforeAux = fileBefore;
							return function(e){
								$(fileBeforeAux.id).attr('src', e.target.result);
							};
						})(originalFile);
						reader.readAsDataURL(originalFile);
					}
				}
				
				// ===== Modificar o array global com todos os arquivos.
				
				data.id = fileUploadObj.total;
				
				fileUploadObj.files.push({
					id : '#file-'+fileUploadObj.total,
					fileObj : fileObj,
					size : file.size,
				});
				
				fileUploadObj.total++;
				fileUploadObj.subTotal++;
				
				// ===== Progresso todos arquivos.
				
				progressAll(0);
			},
			submit: function(e, data) {
				// ===== Ativar a flag submit"
				
				var file = data.files[0];
				var id = file.id;
				var files = fileUploadObj.files;
				
				for(var i=0;i<files.length;i++){
					if(id == files[i].id){
						fileUploadObj.files[i].submit = true;
					}
				}
			},
			fail: function(e, data) {
				var result = data.jqXHR;
				var ajaxOpcao = 'uploadFile';
				
				if(result){
					switch(result.status){
						case 401: window.open(gestor.raiz + (result.redirect ? result.redirect : "signin/"),"_self"); break;
						default:
							console.log('ERROR - jQuery File Upload - '+ajaxOpcao+' - Dados:');
							console.log(data);
					}
				}
			},
			progress: function(e, data) {
				// ===== Progresso arquivo atual.
				
				var progress = parseInt((data.loaded / data.total) * 100, 10);
				
				if(progress >= 100){
					progress = 100;
					data.context.find('.fileProgress').find('.progress').progress({
						percent: progress,
						text: {
							active: gestor.arquivosProcessando
						}
					});
				} else {
					data.context.find('.fileProgress').find('.progress').progress({
						percent: progress
					});
				}
				
				// ===== Progresso todos arquivos.
				
				progressAll(data.loaded);
			},
			done: function(e, data) {
				var result = data.result;
				
				// ===== Caso haja mensagem de alerta, imprimir no console
				
				if(result.warning_msg){
					console.log("WARNING! jQuery File Upload - done: "+result.warning_msg);
				}
				
				// ===== Mudar interface conforme retorno.
				
				if(result.error){
					data.context.find('.fileError').find('.fileErrorBody').html(result.error);
					data.context.find('.fileError').removeClass('hidden');
					
					data.context.find('.fileProgress').find('.progress').progress('set error',gestor.arquivosErro);
				} else {
					data.context.find('.fileProgress').find('.progress').progress({
						percent: 100,
						text : {
							active: gestor.arquivosConcluido,
							success: gestor.arquivosConcluido,
						}
					});
					
					setTimeout(function(){
						data.context.find('.fileProgress').find('.progress').progress({
							percent: 100,
							text : {
								active: gestor.arquivosConcluido,
								success: gestor.arquivosConcluido,
							}
						});
					},200);
					
					data.context.find('.fileDone').removeClass('hidden');
					
					if(data.context.find('.fileDone').find('.fileCopyClipboard').length > 0){
						var urlFile = result.url;
						
						data.context.find('.fileDone').find('.fileCopyClipboard').click(function () {
							navigator.clipboard.writeText(urlFile);
						});
					}
					
					if(data.context.find('.fileDone').find('.fileSelect').length > 0){
						var idStr = result.id;
						var imgSrc = result.imgSrc;
						var nome = result.nome;
						var dataStr = result.data;
						var tipo = result.tipo;
						
						data.context.find('.fileDone').find('.fileSelect').click(function () {
							var imgData = JSON.stringify({
								id:idStr,
								imgSrc:imgSrc,
								nome:nome,
								data:dataStr,
								tipo:tipo
							});
							
							var sendMessage = function (msg) {
								// Make sure you are sending a string, and to stringify JSON
								window.parent.postMessage(msg, '*');
							};
							
							var messageParent = {
								moduloId : gestor.moduloId,
								moduloOpcao : gestor.moduloOpcao,
								data : imgData,
							};
							
							sendMessage(JSON.stringify(messageParent));
						});
					}
				}
				
				data.context.find('.fileCancel').remove();
				
				// ===== Ativar a flag 'done' 
				
				var doneAll = true;
				var file = data.files[0];
				var id = '#file-'+data.id;
				var files = fileUploadObj.files;
				
				for(var i=0;i<files.length;i++){
					if(id == files[i].id){
						fileUploadObj.files[i].done = true;
						if(result.error){
							fileUploadObj.files[i].error = true;
						}
					} else if(!files[i].done && !files[i].cancelar){
						doneAll = false;
					}
				}
				
				// ===== Remover controles globais caso todos os arquivos forem 'done'
				
				if(doneAll){
					$('.fileButtonsAll').addClass('hidden');
					$('.fileProgressAll').addClass('hidden');
				}
				
				// ===== Progresso todos arquivos.
				
				progressAll(file.size,true);
			}
		});
		
		function formatBytes(a,b=2){if(0===a)return"0 Bytes";const c=0>b?0:b,d=Math.floor(Math.log(a)/Math.log(1024));return parseFloat((a/Math.pow(1024,d)).toFixed(c))+" "+["Bytes","KB","MB","GB","TB","PB","EB","ZB","YB"][d]}
		
		function progressAll(fileLoaded,done = false){
			// ===== Progresso todos arquivos.
			
			var files = fileUploadObj.files;
			var total = 0;
			var loaded = 0;
			
			for(var i=0;i<files.length;i++){
				if(!files[i].error && !files[i].cancelar){
					total = total + parseInt(files[i].size);
					
					if(files[i].done){
						loaded = loaded + parseInt(files[i].size);
					}
				}
			}
			
			if(!done){
				loaded = loaded + fileLoaded;
			}
			
			var progressTotal = parseInt((loaded / total) * 100, 10);
			
			if(progressTotal >= 100){
				progressTotal = 100;
				
				if(done){
					$('.fileProgressAll').find('.progress').progress({
						percent: progressTotal,
						text : {
							active: gestor.arquivosConcluido,
							success: gestor.arquivosConcluido,
						}
					});
				} else {
					$('.fileProgressAll').find('.progress').progress({
						percent: progressTotal,
						text: {
							active: gestor.arquivosProcessando
						}
					});
				}
			} else {
				$('.fileProgressAll').find('.progress').progress({
					percent: progressTotal
				});
			}
		}
		
		$('.fileSendAll').click(function () {
			$('.fileSend').trigger('click');
		});
		
		$('.fileCancelAll').click(function () {
			$('.fileCancel').trigger('click');
			$('.fileButtonsAll').addClass('hidden');
			$('.fileProgressAll').addClass('hidden');
		});
		
		$('.ui.dropdown')
		  .dropdown()
		;
	}
	
});