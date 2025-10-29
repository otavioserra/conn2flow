$(document).ready(function () {

	if ($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0) {
		// ===== Codemirror 

		var codemirrors_instances = new Array();

		var codemirror_css = document.getElementsByClassName("codemirror-css");

		if (codemirror_css.length > 0) {
			for (var i = 0; i < codemirror_css.length; i++) {
				var codeMirrorCss = CodeMirror.fromTextArea(codemirror_css[i], {
					lineNumbers: true,
					lineWrapping: true,
					styleActiveLine: true,
					matchBrackets: true,
					mode: "css",
					htmlMode: true,
					indentUnit: 4,
					theme: "tomorrow-night-bright",
					extraKeys: {
						"F11": function (cm) {
							cm.setOption("fullScreen", !cm.getOption("fullScreen"));
						},
						"Esc": function (cm) {
							if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
						}
					}
				});

				codeMirrorCss.setSize('100%', 500);
				codemirrors_instances.push(codeMirrorCss);
			}
		}

		var codemirror_css_compiled = document.getElementsByClassName("codemirror-css-compiled");

		if (codemirror_css_compiled.length > 0) {
			for (var i = 0; i < codemirror_css_compiled.length; i++) {
				var codeMirrorCssCompiled = CodeMirror.fromTextArea(codemirror_css_compiled[i], {
					lineNumbers: true,
					lineWrapping: true,
					styleActiveLine: true,
					matchBrackets: true,
					mode: "css",
					htmlMode: true,
					indentUnit: 4,
					theme: "tomorrow-night-bright",
					extraKeys: {
						"F11": function (cm) {
							cm.setOption("fullScreen", !cm.getOption("fullScreen"));
						},
						"Esc": function (cm) {
							if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
						}
					}
				});

				codeMirrorCssCompiled.setSize('100%', 500);
				codemirrors_instances.push(codeMirrorCssCompiled);
			}
		}

		var codemirror_html = document.getElementsByClassName("codemirror-html");

		if (codemirror_html.length > 0) {
			for (var i = 0; i < codemirror_html.length; i++) {
				var CodeMirrorHtml = CodeMirror.fromTextArea(codemirror_html[i], {
					lineNumbers: true,
					lineWrapping: true,
					styleActiveLine: true,
					matchBrackets: true,
					mode: "htmlmixed",
					htmlMode: true,
					indentUnit: 4,
					theme: "tomorrow-night-bright",
					extraKeys: {
						"F11": function (cm) {
							cm.setOption("fullScreen", !cm.getOption("fullScreen"));
						},
						"Esc": function (cm) {
							if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
						}
					}
				});

				CodeMirrorHtml.setSize('100%', 500);
				codemirrors_instances.push(CodeMirrorHtml);
			}
		}

		var codemirror_html_extra_head = document.getElementsByClassName("codemirror-html-extra-head");

		if (codemirror_html_extra_head.length > 0) {
			for (var i = 0; i < codemirror_html_extra_head.length; i++) {
				var CodeMirrorHtmlExtraHead = CodeMirror.fromTextArea(codemirror_html_extra_head[i], {
					lineNumbers: true,
					lineWrapping: true,
					styleActiveLine: true,
					matchBrackets: true,
					mode: "htmlmixed",
					htmlMode: true,
					indentUnit: 4,
					theme: "tomorrow-night-bright",
					extraKeys: {
						"F11": function (cm) {
							cm.setOption("fullScreen", !cm.getOption("fullScreen"));
						},
						"Esc": function (cm) {
							if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
						}
					}
				});

				CodeMirrorHtmlExtraHead.setSize('100%', 500);
				codemirrors_instances.push(CodeMirrorHtmlExtraHead);
			}
		}

		// ===== Semantic UI

		function activeTabRefreshCodeMirror() {
			var tabActive = localStorage.getItem(gestor.moduloId + 'tabActive');

			if (tabActive !== null) {
				$('.menuPaginas .item').tab('change tab', tabActive);

				switch (tabActive) {
					case 'codigo-html':
						CodeMirrorHtml.refresh();
						break;
					case 'html-extra-head':
						CodeMirrorHtmlExtraHead.refresh();
						break;
					case 'css':
						codeMirrorCss.refresh();
						break;
					case 'css-compiled':
						codeMirrorCssCompiled.refresh();
						break;
				}
			}
		}

		activeTabRefreshCodeMirror();

		$('.menuPaginas .item').tab({
			onLoad: function (tabPath, parameterArray, historyEvent) {
				switch (tabPath) {
					case 'codigo-html':
						CodeMirrorHtml.refresh();
						break;
					case 'html-extra-head':
						CodeMirrorHtmlExtraHead.refresh();
						break;
					case 'css':
						codeMirrorCss.refresh();
						break;
					case 'css-compiled':
						codeMirrorCssCompiled.refresh();
						break;
				}

				localStorage.setItem(gestor.moduloId + 'tabActive', tabPath);
			}
		});

		$('.ui.accordion').accordion();

		// ===== Backup Campo Mudar

		$('#gestor-listener').on('adminPaginasBackupCampo', function (e, p) {
			var campo = p.campo;
			var valor = p.valor;

			switch (campo) {
				case 'html':
					if (gestor.editorHtmlAtivo) {
						if (codeHtmlChanged) {
							valor = indentHtml(valor);

							CodeMirrorHtml.getDoc().setValue(valor);
							CodeMirrorHtml.refresh();
						} else {
							tinymce.activeEditor.setContent(valor, { format: 'raw' });
						}
					} else {
						CodeMirrorHtml.getDoc().setValue(valor);
						CodeMirrorHtml.refresh();
					}
					break;
				case 'html-extra-head':
					CodeMirrorHtmlExtraHead.getDoc().setValue(valor);
					CodeMirrorHtmlExtraHead.refresh();
					break;
				case 'css':
					codeMirrorCss.getDoc().setValue(valor);
					codeMirrorCss.refresh();
					break;
				case 'css_compiled':
					codeMirrorCssCompiled.getDoc().setValue(valor);
					codeMirrorCssCompiled.refresh();
					break;
			}
		});

		// ===== Input delay

		$.input_delay_to_change = function (p) {
			if (!gestor.input_delay) {
				gestor.input_delay = new Array();
				gestor.input_delay_count = 0;
			}

			gestor.input_delay_count++;

			var valor = gestor.input_delay_count;

			gestor.input_delay.push(valor);
			gestor.input_value = p.value;

			setTimeout(function () {
				if (gestor.input_delay[gestor.input_delay.length - 1] == valor) {
					input_change_after_delay({ value: gestor.input_value, trigger_selector: p.trigger_selector, trigger_event: p.trigger_event });
				}
			}, gestor.input_delay_timeout);
		}

		function input_change_after_delay(p) {
			$(p.trigger_selector).trigger(p.trigger_event, [p.value, gestor.input_delay_params]);

			gestor.input_delay = false;
		}

		function input_delay() {
			if (!gestor.input_delay_timeout) gestor.input_delay_timeout = 400;

		}

		input_delay();

		// ===== Format opcao e caminho

		$(document.body).on('keyup', 'input[name="pagina-opcao"]', function (e) {
			if (e.which == 9) return false;

			var value = $(this).val();

			$.input_delay_to_change({
				trigger_selector: '#gestor-listener',
				trigger_event: 'opcao-change',
				value: value
			});
		});

		$(document.body).on('opcao-change', '#gestor-listener', function (e, value, p) {
			if (!p) p = {};

			value = formatar_opcao(value);

			var modulo = $('.ui.dropdown.gestorModule').dropdown('get value');

			if (modulo.length > 0) {
				var caminho = formatar_caminho(modulo, value);

				if ($('#_gestor-interface-edit-dados').length > 0) {
					if ($('input[name="paginaCaminho"]').val().length == 0) $('input[name="paginaCaminho"]').val(formatar_url(caminho));
				} else {
					$('input[name="paginaCaminho"]').val(formatar_url(caminho));
				}
			} else if (value.length > 0) {
				$('input[name="paginaCaminho"]').val(formatar_url(value));
			} else {
				$('input[name="paginaCaminho"]').val(formatar_url($('input[name="pagina-nome"]').val()));
			}

			$('input[name="pagina-opcao"]').val(value);
		});

		$(document.body).on('keyup', 'input[name="pagina-nome"]', function (e) {
			if (e.which == 9) return false;

			var value = $(this).val();

			$.input_delay_to_change({
				trigger_selector: '#gestor-listener',
				trigger_event: 'caminho-change',
				value: value
			});
		});

		$(document.body).on('caminho-change', '#gestor-listener', function (e, value, p) {
			if (!p) p = {};

			$('input[name="paginaCaminho"]').val(formatar_url(value));
		});

		function formatar_url(url) {
			url = url.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); // Trocar todos os caracteres com acentos pelos seus similares sem acento.
			url = url.replace(/[^a-zA-Z0-9 \-\/]/g, ''); // Remover todos os caracteres que não são alfanuméricos ou espaço ou traço ou barra.
			url = url.toLowerCase(); // Passar para letras minúsculas
			url = url.trim(); // Remover espaço do início e fim.
			url = url.replace(/\s/g, '-'); // Trocar todos os espaços por traço.
			url = url.replace(/\-{2,}/g, '-'); // Remover a repetição de traços para um único traço.
			url = url.replace(/\/{2,}/g, '/'); // Remover a repetição de barras para uma única barra.

			// Sempre adicionar uma barra no final, ou retornar apenas "/" se estiver vazio
			return url.length > 0 ? url + '/' : '/';
		}

		function formatar_caminho(modulo, opcao) {
			var caminho = '';

			if (modulo.length > 0 && opcao.length > 0) {
				caminho = modulo + '/' + opcao;
			} else if (modulo.length > 0) {
				caminho = modulo;
			} else if (opcao.length > 0) {
				caminho = opcao;
			}

			return caminho;
		}

		function formatar_opcao(opcao) {
			opcao = opcao.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); // Trocar todos os caracteres com acentos pelos seus similares sem acento.
			opcao = opcao.replace(/[^a-zA-Z0-9 \-]/g, ''); // Remover todos os caracteres que não são alfanuméricos ou espaço ou traço.
			opcao = opcao.toLowerCase(); // Passar para letras minúsculas
			opcao = opcao.trim(); // Remover espaço do início e fim.
			opcao = opcao.replace(/\s/g, '-'); // Trocar todos os espaços por traço.
			opcao = opcao.replace(/\-{2,}/g, '-'); // Remover a repetição de traços para um único traço.

			return opcao;
		}

		// ===== Dropddown

		$('.gestorModule')
			.dropdown({
				onChange: function (value, text, $choice) {
					var opcao = $('input[name="pagina-opcao"]').val();
					var caminho = formatar_caminho(value, opcao);

					if ($('#_gestor-interface-edit-dados').length > 0) {
						if ($('input[name="paginaCaminho"]').val().length == 0) $('input[name="paginaCaminho"]').val(formatar_url(caminho));
					} else {
						$('input[name="paginaCaminho"]').val(formatar_url(caminho));
					}
				}
			});

		// ===== Pré-visualização

		// Função para filtrar o HTML e apenas devolver o que tah dentro do <body>, caso o <body> exista. Senão retornar o HTML completo.
		function filtrarHtmlBody(html) {
			const bodyMatch = html.match(/<body[^>]*>([\s\S]*?)<\/body>/i);
			return bodyMatch ? bodyMatch[1] : html;
		}

		// Função para gerar o conteúdo da página de preview
		function gerarPreviewHtml(htmlDoUsuario, cssDoUsuario, framework = 'fomantic-ui') {
			// Clonar o modal de edição
			const modalHtml = $('#html-editor-modal').clone().wrap('<div/>').parent().html();

			// Incluir o script do editor HTML e variáveis
			let js_vars = '';
			let js_script = '';
			if ('html_editor' in gestor) {
				if ('script' in gestor.html_editor) {
					js_script = gestor.html_editor.script;
				}
				if ('overlay_title' in gestor.html_editor) {
					js_vars += '<script>\n';
					js_vars += `	const html_editor = { overlay_title: '${gestor.html_editor.overlay_title}' };\n`;
					js_vars += '</script>\n';
				}
			}

			// Incluir o CSS do usuário, se existir
			if (cssDoUsuario && cssDoUsuario.length > 0) {
				cssDoUsuario = `<style>${cssDoUsuario}</style>`;
			} else {
				cssDoUsuario = '';
			}

			let tailwindConfigScript = '';

			if (framework === 'tailwindcss') {
				tailwindConfigScript = `<!-- CDN do TailwindCSS -->
				<script src="https://cdn.tailwindcss.com"></script>`;
			}

			return `
			<!DOCTYPE html>
			<html lang="pt-br">
			<head>
				<meta charset="UTF-8">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title>Preview Tailwind</title>
				${tailwindConfigScript}
				<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.2/dist/semantic.min.css">
				<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
				<script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.2/dist/semantic.min.js"></script>
				${js_vars}
				${js_script}
				${cssDoUsuario}
			</head>
			<body>
				${htmlDoUsuario}
				${modalHtml}
			</body>
			</html>
		`;
		}

		$(document.body).on('mouseup tap', '.previsualizar.button', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			previsualizarPagina();
		});

		function previsualizarPagina() {
			// Pegar o HTML do usuário e filtrar o que está dentro do <body>
			const htmlDoUsuario = filtrarHtmlBody(CodeMirrorHtml.getDoc().getValue()).trim();
			const cssDoUsuario = filtrarHtmlBody(codeMirrorCss.getDoc().getValue()).trim();

			// Atualizar o CodeMirror com o HTML filtrado.
			CodeMirrorHtml.getDoc().setValue(htmlDoUsuario);

			const idFramework = $('#framework-css').parent().find('.menu').find('.item.active.selected').data('value');

			$('#iframe-preview').attr('srcdoc', gerarPreviewHtml(htmlDoUsuario, cssDoUsuario, idFramework));

			$('.previsualizar.modal')
				.modal('show')
				;
		}

		$(document.body).on('mouseup tap', '.screenPagina', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			$('.previsualizar.modal').removeClass('longer tiny fullscreen');

			switch ($(this).data('option')) {
				case 'desktop':
					$('.previsualizar.modal').addClass('fullscreen');
					break;
				case 'tablet':
					$('.previsualizar.modal').addClass('longer');
					break;
				case 'mobile':
					$('.previsualizar.modal').addClass('tiny');
					break;
			}
		});

		$(document.body).on('mouseup tap', '.previsualizarConfirmar, .previsualizarVoltar', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			const idFramework = $('#framework-css').parent().find('.menu').find('.item.active.selected').data('value');
			const iframe = $('#iframe-preview')[0];
			const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

			// Atualizar o código CSS no conteúdo do CodeMirror
			if (idFramework === 'tailwindcss') {
				const allStyleTags = $(iframeDoc).find('head > style');

				const tailwindStyleElement = allStyleTags[allStyleTags.length - 1];

				if (tailwindStyleElement) {
					const generatedCss = tailwindStyleElement.innerHTML;

					codeMirrorCssCompiled.getDoc().setValue(generatedCss);
				}
			}

			// Remover elementos de sistema adicionados pelo Fomantic UI ou Tailwind CSS ou Editor HTML
			// Garantir que todas as ocorrências sejam removidas
			while ($(iframeDoc).find('#html-editor-modal').length > 0) {
				$(iframeDoc).find('#html-editor-modal').remove();
			}
			while ($(iframeDoc).find('#html-editor-overlay').length > 0) {
				$(iframeDoc).find('#html-editor-overlay').remove();
			}
			while ($(iframeDoc).find('.ui.dimmer.modals').length > 0) {
				$(iframeDoc).find('.ui.dimmer.modals').remove();
			}

			// Atualizar o código HTML no conteúdo do CodeMirror
			const body = $(iframeDoc).find('body');
			const bodyElement = body[0];

			let updatedHtml = bodyElement.innerHTML;

			// Remover linhas em branco no início e fim do código.
			// E também remover linhas que estejam completamente em branco no meio do código.
			updatedHtml = updatedHtml.split('\n').filter(line => line.trim() !== '').join('\n').trim();

			// Atualizar o CodeMirror com o HTML atualizado.
			CodeMirrorHtml.getDoc().setValue(updatedHtml);

			// Fechar o modal de pré-visualização se o botão clicado for o de voltar.
			if ($(this).hasClass('previsualizarVoltar')) {
				return;
			}

			$.formSubmitNormal();
		});

		// ===== Editor de Código

		$(document.body).on('mouseup tap', '.editorCodigoMostrar, .editorCodigoOcultar', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			if ($(this).hasClass('editorCodigoMostrar')) {
				$(this).addClass('hidden');
				$('.editorCodigoOcultar').removeClass('hidden');
				$('.editor-codigo-container').removeClass('hidden');
				activeTabRefreshCodeMirror();
				const editorContainer = document.querySelector('.editor-codigo-container');
				if (editorContainer) {
					editorContainer.scrollIntoView({
						behavior: 'smooth',
						block: 'start'
					});
				}
			} else {
				$(this).addClass('hidden');
				$('.editorCodigoMostrar').removeClass('hidden');
				$('.editor-codigo-container').addClass('hidden');
			}
		});

		// ===== Módulos

		function visibilidadeModulos() {
			const pagina_tipo = $('.ui.dropdown.pagina-tipo').dropdown('get value');

			if (pagina_tipo === 'sistema') {
				$('.pagina-modulos-container').removeClass('hidden');
			} else {
				$('.pagina-modulos-container').addClass('hidden');
			}
		}

		visibilidadeModulos();

		$('.ui.dropdown.pagina-tipo')
			.dropdown({
				onChange: function (value, text, $choice) {
					visibilidadeModulos();
				}
			});

		// ===== IA

		let total_sessoes = 0;

		function totalDeSessoes() {
			let total = 0;
			const html = CodeMirrorHtml.getDoc().getValue();

			// Contar a quantidade total de tag sessions no HTML e retornar esse valor.
			const regex = /<session\b[^>]*>([\s\S]*?)<\/session>/gi;
			let match;
			while ((match = regex.exec(html)) !== null) {
				total++;
			}

			return total;
		}

		function menuDeSessoes() {
			const html = CodeMirrorHtml.getDoc().getValue();

			const regex = /<session\b[^>]*>([\s\S]*?)<\/session>/gi;
			let match;

			let sessoes = [];

			while ((match = regex.exec(html)) !== null) {
				const sessionTag = match[0];
				const idMatch = sessionTag.match(/data-id=["']([^"']+)["']/i);
				const nomeMatch = sessionTag.match(/data-title=["']([^"']+)["']/i);

				const id = idMatch ? idMatch[1] : null;
				const nome = nomeMatch ? nomeMatch[1] : 'Sem nome';

				if (id) {
					sessoes.push({ id: id, nome: nome });
				}
			}

			const select = $('.ui.dropdown.ai-prompt-session-select');
			const currentValue = select.dropdown('get value');

			select.find('select').find('option').remove();

			if (sessoes.length > 0) {
				sessoes.forEach(function (sessao) {
					const option = $('<option></option>').attr('value', sessao.id).text(sessao.nome);
					select.find('select').append(option);
				});

				select.dropdown('refresh');

				if (sessoes.find(sessao => sessao.id === currentValue)) {
					select.dropdown('set selected', currentValue, true);
				} else {
					select.dropdown('set selected', sessoes[0].id, true);
				}

				select.parent().removeClass('disabled');
			}
		}

		function menuPages(opcao, alertar = false) {
			total_sessoes = totalDeSessoes();

			if (opcao === 'sessao') {
				if (total_sessoes > 0) {
					menuDeSessoes();
					$('.ai-prompt-session-container').removeClass('hidden');
				} else {
					if (alertar) $('#gestor-listener').trigger('alerta', { msg: 'Não foram detectadas sessões. Crie uma página nova e adicione sessões para utilizar esse recurso.' });
					$('.ai-prompt-session-container').addClass('hidden');
					setTimeout(function () {
						$('.ui.dropdown.ai-prompt-page-select').dropdown('set selected', 'tudo', true);
					});
				}
			} else {
				$('.ai-prompt-session-container').addClass('hidden');
			}
		}

		CodeMirrorHtml.on("change", function (instance, changeObj) {
			//var newContent = instance.getValue();

			const total_atual = totalDeSessoes();

			if (total_atual != total_sessoes) {
				total_sessoes = total_atual;
				menuDeSessoes();

				const tipo_prompt = $('.ui.dropdown.ai-prompt-page-select').dropdown('get value');
				menuPages(tipo_prompt);
			}
		});

		$('.ui.dropdown.ai-prompt-page-select')
			.dropdown({
				onChange: function (value, text, $selectedItem) {
					menuPages(value, true);
				}
			});

		function iaRequestsCallback(p = {}) {
			var html_gerado = p.data.html_gerado ? p.data.html_gerado : '';
			var css_gerado = p.data.css_gerado ? p.data.css_gerado : '';
			var sessao_id = p.data.sessao_id ? p.data.sessao_id : '';
			var sessao_opcao = p.data.sessao_opcao ? p.data.sessao_opcao : '';

			if (sessao_id && sessao_id.length > 0 && sessao_opcao && sessao_opcao.length > 0) {
				// Pegar o HTML completo atual
				let html_completo = CodeMirrorHtml.getDoc().getValue();

				// Marcar sessão alvo com data-menu-alvo="true" para manter a seleção
				html_completo = html_completo.replace(new RegExp(`(<session\\b[^>]*data-id=["']${sessao_id}["'][^>]*)>`, 'i'), '$1 data-menu-alvo="true">');

				switch (sessao_opcao) {
					case 'target':
						// Extrair o outerHTML da sessão.
						const regex = new RegExp(`<session\\b[^>]*data-id=["']${sessao_id}["'][^>]*>([\\s\\S]*?)<\\/session>`, 'i');
						const match = html_completo.match(regex);

						if (match && match[0]) {
							// Substituir a sessão no HTML completo
							const novo_html_completo = html_completo.replace(regex, html_gerado);

							html_gerado = novo_html_completo;
						}
						break;
					case 'new-before':
						// Colocar o html_gerado logo antes da sessão alvo
						const regexBefore = new RegExp(`(<session\\b[^>]*data-id=["']${sessao_id}["'][^>]*>([\\s\\S]*?)<\\/session>)`, 'i');
						html_gerado = html_completo.replace(regexBefore, html_gerado + '\n$1');
						break;
					case 'new-after':
						// Colocar o html_gerado logo depois da sessão alvo
						const regexAfter = new RegExp(`(<session\\b[^>]*data-id=["']${sessao_id}["'][^>]*>([\\s\\S]*?)<\\/session>)`, 'i');
						html_gerado = html_completo.replace(regexAfter, '$1\n' + html_gerado);
						break;
				}
			}

			// Remover linhas em branco no início e fim do código.
			// E também remover linhas que estejam completamente em branco no meio do código.
			html_gerado = html_gerado.split('\n').filter(line => line.trim() !== '').join('\n').trim();
			css_gerado = css_gerado.split('\n').filter(line => line.trim() !== '').join('\n').trim();

			// Atualizar os `data-id` das sessões para evitar duplicidade. Começar sempre no `1` e ir somando.
			let sessionCounter = 1;
			let oldIds = [];
			html_gerado = html_gerado.replace(/<session\b[^>]*>/gi, function (match) {
				const idMatch = match.match(/data-id=["']([^"']+)["']/i);
				const oldId = idMatch ? idMatch[1] : null;
				oldIds.push(oldId);
				// Substituir ou adicionar data-id
				if (match.includes('data-id=')) {
					return match.replace(/data-id=["'][^"']*["']/i, 'data-id="' + sessionCounter++ + '"');
				} else {
					return match.replace('<session', '<session data-id="' + sessionCounter++ + '"');
				}
			});

			// Atualizar os CodeMirror com o código gerado.
			CodeMirrorHtml.getDoc().setValue(html_gerado);
			codeMirrorCss.getDoc().setValue(css_gerado);

			CodeMirrorHtml.refresh();
			codeMirrorCss.refresh();

			// Agora, após o menu ser atualizado pelo evento change, selecionar a sessão alvo e remover o atributo
			const htmlAtual = CodeMirrorHtml.getDoc().getValue();
			const alvoMatch = htmlAtual.match(/<session\b[^>]*data-menu-alvo="true"[^>]*>/i);
			if (alvoMatch) {
				const alvoTag = alvoMatch[0];
				const idMatch = alvoTag.match(/data-id=["']([^"']+)["']/i);
				if (idMatch) {
					const alvoId = idMatch[1];
					$('.ui.dropdown.ai-prompt-session-select').dropdown('set selected', alvoId, true);
					// Remover o atributo data-menu-alvo
					const htmlSemAlvo = htmlAtual.replace(/ data-menu-alvo="true"/gi, '');
					CodeMirrorHtml.getDoc().setValue(htmlSemAlvo);
					CodeMirrorHtml.refresh();
				}
			}

			// Abrir o preview da página.
			previsualizarPagina();
		}

		function iaRequestsData(p = {}) {
			const tipo_prompt = $('.ui.dropdown.ai-prompt-page-select').dropdown('get value');

			let html = '';
			let css = codeMirrorCss.getDoc().getValue();
			let sessao_id = '';
			let sessao_opcao = '';

			// Se for sessão, validar se uma sessão foi selecionada.
			if (tipo_prompt === 'sessao') {
				const id_sessao = $('.ui.dropdown.ai-prompt-session-select').dropdown('get value');
				const sessao_options = ['target', 'new-before', 'new-after'];

				sessao_options.forEach(function (opcao) {
					const checkbox = $('input[name="ai-prompt-session-option"][value="' + opcao + '"]').parent();
					if (checkbox.checkbox('is checked')) {
						sessao_opcao = opcao;
						return false;
					}
				});

				// Se não tiver sessão selecionada, retornar sem enviar a request.
				if (id_sessao && id_sessao.length > 0) {
					sessao_id = id_sessao;
					if (sessao_opcao == 'target') {
						const html_completo = CodeMirrorHtml.getDoc().getValue();
						// Extrair o outerHTML da sessão.
						const regex = new RegExp(`<session\\b[^>]*data-id=["']${id_sessao}["'][^>]*>([\\s\\S]*?)<\\/session>`, 'i');
						const match = html_completo.match(regex);

						if (match && match[0]) {
							html = match[0].trim();
						}
					}
				}
			} else {
				html = CodeMirrorHtml.getDoc().getValue();
			}

			return {
				ajaxOpcao: 'ia-requests', data: {
					html: html,
					css: css,
					framework_css: $('#framework-css').dropdown('get value'),
					sessao_id,
					sessao_opcao
				}
			};
		}

		$('#gestor-listener').trigger('ia-data', {
			requestsCallback: iaRequestsCallback,
			requestsData: iaRequestsData
		});
	}

	if ($('#_gestor-interface-listar').length > 0) {
		$('.ui.radio.checkbox').checkbox({
			onChange: function () {
				window.open(gestor.raiz + gestor.moduloCaminho + '?tipo=' + $(this).val(), '_self');
			}
		});
	}
});