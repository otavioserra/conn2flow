
$(document).ready(function(){
	
	function start(){
		// ===== Aplicar a classe large para os labels.
		
		$('.servicoCol .label').addClass('large');
		
		// ===== Iniciação dos dados dos vouchers.
		
		var vouchersDados = {};
		if('dados' in gestor.voucher){
			for(var key in gestor.voucher.dados){
				vouchersDados[key] = gestor.voucher.dados[key];
			}
		}
		
		// ===== Botão que controla as opções do voucher.
		
		$('.voucherBtn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var id = $(this).attr('data-id');
			var objPai = $(this).parents('.voucherCell');
			
			switch(id){
				case 'identidade':
					informativo({
						titulo : 'Alteração de Identidade',
						mensagem : $('.contAlteracaoIdentidade').html()
					});
					
					$('.cancel').on('mouseup tap',function(e){
						if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
						
						informativo({fechar:true});
					});
					
					formAlterarIdentificacao({
						objPai : objPai
					});
				break;
				case 'email':
					informativo({
						titulo : 'Enviar por Email',
						mensagem : $('.contEnviarEmail').html()
					});
					
					$('.cancel').on('mouseup tap',function(e){
						if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
						
						informativo({fechar:true});
					});
					
					formEnviarEmail({
						objPai : objPai
					});
				break;
				case 'imprimir':
					gerarPDF({
						objPai,
						imprimir:true
					});
				break;
				case 'presente':
					
				break;
				case 'visualizar':
					if(!isMobile()){
						informativo({
							titulo : 'Visualizar',
							mensagem : '<iframe id="imprimir-iframe" name="imprimir-iframe" style="width: 100%; height: 800px; position: absolute; top: 0; left: 0; z-index: 2; border: none;"></iframe>',
						});
					}
					
					gerarPDF({
						objPai
					});
				break;
				default:
					
			}
		});
		
		function gerarPDF(p={}){
			window.jsPDF = window.jspdf.jsPDF;
			
			var objPai = p.objPai;
			var codigo = objPai.attr('data-id');
			
			// ===== Verificar se é loteVariacao.
			
			var loteVariacao = vouchersDados[codigo].loteVariacao;
			
			// ===== Dados do voucher.
			
			var titMaxLen = 37;
			
			var voucherTitulo = vouchersDados[codigo].titulo;
			var voucherSubTitulo = '';
			
			if(loteVariacao){
				voucherSubTitulo = vouchersDados[codigo].subtitulo;
				voucherSubTitulo = voucherSubTitulo.length > titMaxLen ? voucherSubTitulo.substring(0,titMaxLen)+"..." : voucherSubTitulo;
			}
			
			voucherTitulo = voucherTitulo.length > titMaxLen ? voucherTitulo.substring(0,titMaxLen)+"..." : voucherTitulo;
			
			var nome = objPai.find('.campoNome').html();
			var documento = objPai.find('.campoDocumento').html();
			var telefone = objPai.find('.campoTelefone').html();
			
			var nomeArr = nome.split(' ');
			
			if(nomeArr.length > 1){
				nome = nomeArr[0] + ' ' + nomeArr[nomeArr.length - 1];
			}
			
			var idMaxLen = 28;
			
			nome = nome.length > idMaxLen ? nome.substring(0,idMaxLen)+"..." : nome;
			documento = documento.length > idMaxLen ? documento.substring(0,idMaxLen)+"..." : documento;
			telefone = telefone.length > idMaxLen ? telefone.substring(0,idMaxLen)+"..." : telefone;
			
			var labelNome = objPai.find('.labelNome').html();
			var labelDocumento = objPai.find('.labelDocumento').html();
			var labelTelefone = objPai.find('.labelTelefone').html();
			
			var servicoImg = objPai.find('.campoImagem').attr('src');
			var qrCodeImg = objPai.find('.campoQRCode').attr('src');
			var logoPrincipal = '/images/logo-principal.png';
			
			var doc = new jsPDF();
			
			// ===== Coordenadas de título e identificação.
			
			if(loteVariacao){
				var xLab = 85;
				var xCam = 120;
				
				var yTit = 20;
				var ySubTit = 27;
				var yNom = 40;
				var yDoc = 50;
				var yTel = 60;
			} else {
				var xLab = 85;
				var xCam = 120;
				
				var yTit = 20;
				var yNom = 30;
				var yDoc = 40;
				var yTel = 50;
			}
			
			// ===== Dados de título.
			
			doc.setTextColor(0,0,0);
			doc.setFont("helvetica", "bold");
			doc.setFontSize(16);
			
			doc.text(voucherTitulo, xLab, yTit);
			
			if(loteVariacao){
				doc.setTextColor(140,140,140);
				doc.setFont("helvetica", "normal");
				doc.setFontSize(15);
				
				doc.text(voucherSubTitulo, xLab, ySubTit);
			}
			
			// ===== Dados da identidade.
			
			doc.setTextColor(0,0,0);
			doc.setFont("helvetica", "normal");
			doc.setFontSize(15);
			
			doc.text(labelNome+':', xLab, yNom);
			doc.text(labelDocumento+':', xLab, yDoc);
			doc.text(labelTelefone+':', xLab, yTel);
			
			doc.text(nome, xCam, yNom);
			doc.text(documento, xCam, yDoc);
			doc.text(telefone, xCam, yTel);
			
			// ===== Linhas de separação dos nomes.
			
			var line1X = ((yNom + yDoc) / 2) - 2;
			var line2X = ((yDoc + yTel) / 2) - 2;
			
			doc.setDrawColor(200, 200, 200);
			doc.setLineWidth(0.3);
			
			doc.line(xLab, line1X, 200, line1X);
			doc.line(xLab, line2X, 200, line2X);
			
			// ===== Imagem do serviço e do qrCode.
			
			doc.addImage(servicoImg, "JPEG", 15, 15, 60, 60);
			doc.addImage(qrCodeImg, "PNG", 40, 90, 130, 130);
			doc.addImage(logoPrincipal, "PNG", 80, 260, 60, 22);
			
			if(isMobile()){
				doc.save(voucherTitulo+'.pdf');
			} else {
				// ===== Imprimir o pdf.
				
				if('imprimir' in p){
					doc.autoPrint();

					const hiddFrame = document.createElement('iframe');
					hiddFrame.style.position = 'fixed';
					hiddFrame.style.width = '1px';
					hiddFrame.style.height = '1px';
					hiddFrame.style.opacity = '0.01';
					const isSafari = /^((?!chrome|android).)*safari/i.test(window.navigator.userAgent);
					
					if(isSafari){
						hiddFrame.onload = () => {
							setTimeout(function(){
								try {
									hiddFrame.contentWindow.document.execCommand('print', false, null);
								} catch (e) {
									hiddFrame.contentWindow.print();
								}
							},10);
						};
					}
					
					hiddFrame.src = doc.output('bloburl',{filename : voucherTitulo+'.pdf'});
					document.body.appendChild(hiddFrame);
				} else {
					document.getElementById('imprimir-iframe').setAttribute('src', doc.output('bloburl'));
				}
			}
		}
		
		function formAlterarIdentificacao(p={}){
			// ===== Forms Alterar Identificação.
			
			var formId = 'formAlterarIdentificacao';
			var formSelector = '.formAlterarIdentificacao';
			var objPai = p.objPai;
			
			if('formAlterarIdentificacao' in gestor){
				delete gestor.formAlterarIdentificacao;
			}
			
			$(formSelector)
				.form({
					fields : (gestor.formulario[formId].regrasValidacao ? gestor.formulario[formId].regrasValidacao : {}),
					onSuccess(event, fields){
						// ===== Evitar que envie várias vezes a requisição.
						
						if('formAlterarIdentificacao' in gestor){
							return false;
						}
						
						gestor.formAlterarIdentificacao = true;
						
						// ===== Objeto target.
						
						var obj = $(event.target);
						
						// ===== Pegar os campos do formulário.
						
						var nome = obj.find('input[name="nome"]').val();
						var documento = obj.find('input[name="documento"]').val();
						var telefone = obj.find('input[name="telefone"]').val();
						
						// ===== Ajax para alterar no servidor.
						
						ajaxAlterarIdentificacao({
							objPai,
							nome,
							documento,
							telefone,
						});
						
						return false;
					}
				});
		}
		
		function ajaxAlterarIdentificacao(p = {}){
			var opcao = 'voucher';
			var ajaxOpcao = 'alterar-identificacao';
			var objPai = p.objPai;
			var voucherID = objPai.attr('data-id');
			
			$.ajax({
				type: 'POST',
				url: gestor.raiz + 'voucher/',
				data: {
					opcao,
					ajax : 'sim',
					ajaxOpcao,
					codigo : gestor.voucher.codigo,
					voucherID,
					nome : p.nome,
					documento : p.documento,
					telefone : p.telefone,
				},
				dataType: 'json',
				beforeSend: function(){
					carregando('abrir');
				},
				success: function(dados){
					switch(dados.status){
						case 'OK':
							// ===== Colocar eles na identificação do voucher.
							
							objPai.find('.campoNome').html(p.nome);
							objPai.find('.campoDocumento').html(p.documento);
							objPai.find('.campoTelefone').html(p.telefone);
							
							alerta({mensagem:dados.msg});
						break;
						case 'API_ERROR':
							alerta({mensagem:dados.msg});
						break;
						case 'JWT_EXPIRED':
							location.reload();
						break;
						default:
							console.log('ERROR - '+opcao+' - '+dados.status);
							carregando('fechar');
						
					}
				},
				error: function(txt){
					switch(txt.status){
						case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "identificacao/"),"_self"); break;
						default:
							console.log('ERROR AJAX - '+opcao+' - Dados:');
							console.log(txt);
							carregando('fechar');
					}
				}
			});
		}
		
		function formEnviarEmail(p={}){
			// ===== Forms Alterar Identificação.
			
			var formId = 'formEnviarEmail';
			var formSelector = '.formEnviarEmail';
			var objPai = p.objPai;
			
			if('formEnviarEmail' in gestor){
				delete gestor.formEnviarEmail;
			}
			
			$(formSelector)
				.form({
					fields : (gestor.formulario[formId].regrasValidacao ? gestor.formulario[formId].regrasValidacao : {}),
					onSuccess(event, fields){
						// ===== Evitar que envie várias vezes a requisição.
						
						if('formEnviarEmail' in gestor){
							return false;
						}
						
						gestor.formEnviarEmail = true;
						
						// ===== Objeto target.
						
						var obj = $(event.target);
						
						// ===== Pegar os campos do formulário.
						
						var email = obj.find('input[name="email"]').val();
						
						// ===== Requisição AJAX para enviar o email.
						
						ajaxEnviarEmail({
							objPai,
							email,
						});
						
						return false;
					}
				});
		}
		
		function ajaxEnviarEmail(p = {}){
			var opcao = 'voucher';
			var ajaxOpcao = 'enviar-email';
			var objPai = p.objPai;
			var voucherID = objPai.attr('data-id');
			
			$.ajax({
				type: 'POST',
				url: gestor.raiz + 'voucher/',
				data: {
					opcao,
					ajax : 'sim',
					ajaxOpcao,
					codigo : gestor.voucher.codigo,
					voucherID,
					email : p.email,
				},
				dataType: 'json',
				beforeSend: function(){
					carregando('abrir');
				},
				success: function(dados){
					switch(dados.status){
						case 'OK':
							alerta({mensagem:dados.msg});
						break;
						case 'API_ERROR':
							alerta({mensagem:dados.msg});
						break;
						case 'JWT_EXPIRED':
							window.reload();
						break;
						default:
							console.log('ERROR - '+opcao+' - '+dados.status);
							carregando('fechar');
						
					}
				},
				error: function(txt){
					switch(txt.status){
						case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "identificacao/"),"_self"); break;
						default:
							console.log('ERROR AJAX - '+opcao+' - Dados:');
							console.log(txt);
							carregando('fechar');
					}
				}
			});
		}
		
		function carregando(opcao){
			switch(opcao){
				case 'abrir':
					$('.ui.modal.carregando').modal({
						inverted: true,
						allowMultiple: false,
					}).modal('show');
				break;
				case 'fechar':
					$('.ui.modal.carregando').modal('hide');
				break;
			}
		}
		
		function alerta(p={}){
			if(p.mensagem){
				$('.ui.modal.alerta .content p').html(p.mensagem);
			}
			
			$('.ui.modal.alerta').modal({
				allowMultiple: false,
				onHidden: function(){
					carregando('fechar');
				},
			}).modal('show');
		}
		
		function informativo(p={}){
			if(p.titulo){
				$('.ui.modal.informativo .header').html(p.titulo);
			}
			
			if(p.mensagem){
				$('.ui.modal.informativo .content').html(p.mensagem);
			}
			
			if(p.fechar){
				$('.ui.modal.informativo').modal('hide');
			} else {
				$('.ui.modal.informativo').modal({
					allowMultiple: false,
				}).modal('show');
			}
		}
		
		function isMobile(){
			if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)){
				return true;
			} else {
				return false;
			}
		}
	}
	
	start();
	
});