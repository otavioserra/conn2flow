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

		// ===== Semantic UI

		$('.menu .item').tab({
			onLoad: function (tabPath, parameterArray, historyEvent) {
				switch (tabPath) {
					case 'codigo-html':
						CodeMirrorHtml.refresh();
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

		var tabActive = localStorage.getItem(gestor.moduloId + 'tabActive');

		if (tabActive !== null) {
			$('.menu .item').tab('change tab', tabActive);
		}

		$('.ui.accordion').accordion();

		// ===== Backup Campo Mudar

		$('#gestor-listener').on('adminLayoutsBackupCampo', function (e, p) {
			var campo = p.campo;
			var valor = p.valor;

			switch (campo) {
				case 'html':
					CodeMirrorHtml.getDoc().setValue(valor);
					CodeMirrorHtml.refresh();
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

		// ===== Pré-visualização

		// Função para inserir bibliotecas CSS no layout HTML
		function inserirBibliotecasNoLayout(html, tipo) {
			let bibliotecas = '';
			if (tipo === 'fomantic-ui') {
				bibliotecas = `\n<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.4/dist/semantic.min.css">\n<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>\n<script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.4/dist/semantic.min.js"></script>\n`;
			} else {
				bibliotecas = `\n<script src="https://cdn.tailwindcss.com"></script>\n`;
			}

			if (html.includes('<!-- pagina#css -->')) {
				return html.replace('<!-- pagina#css -->', bibliotecas);
			} else {
				// Se não existir, insere antes do </head>
				return html.replace(/<head>([\s\S]*?)(<\/head>)/i, function (match, p1, p2) {
					return `<head>${p1}${bibliotecas}${p2}`;
				});
			}
		}

		// Função para gerar o conteúdo da página de preview com Tailwind CSS
		function gerarPreviewHtmlTailwind(htmlDoUsuario) {
			return inserirBibliotecasNoLayout(htmlDoUsuario, 'tailwindcss');
		}

		// Função para gerar o conteúdo da página de preview com Fomantic UI
		function gerarPreviewHtmlFomantic(htmlDoUsuario) {
			return inserirBibliotecasNoLayout(htmlDoUsuario, 'fomantic-ui');
		}

		$(document.body).on('mouseup tap', '.previsualizar.button', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			const htmlDoUsuario = CodeMirrorHtml.getDoc().getValue();

			const idFramework = $('#framework-css').parent().find('.menu').find('.item.active.selected').data('value');

			const gerarPreviewHtml = idFramework === 'fomantic-ui' ? gerarPreviewHtmlFomantic : gerarPreviewHtmlTailwind;

			$('#iframe-preview').attr('srcdoc', gerarPreviewHtml(htmlDoUsuario));

			$('.previsualizar.modal')
				.modal('show')
				;
		});

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

		$(document.body).on('mouseup tap', '.previsualizarConfirmar', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			const idFramework = $('#framework-css').parent().find('.menu').find('.item.active.selected').data('value');

			if (idFramework === 'tailwindcss') {
				const iframe = $('#iframe-preview')[0];
				const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
				const allStyleTags = $(iframeDoc).find('head > style');

				const tailwindStyleElement = allStyleTags[allStyleTags.length - 1];

				if (tailwindStyleElement) {
					const generatedCss = tailwindStyleElement.innerHTML;

					codeMirrorCssCompiled.getDoc().setValue(generatedCss);
				}
			}

			$.formSubmitNormal();
		});
	}

});