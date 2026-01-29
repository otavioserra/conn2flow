$(document).ready(function () {

	function updateQueryStringParameter(uri, key, value) {
		var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
		var separator = uri.indexOf('?') !== -1 ? "&" : "?";
		if (value === '') {
			// Remover o parâmetro e limpar & ou ? no final se necessário
			return uri.replace(re, '$1').replace(/[?&]$/, '');
		} else if (uri.match(re)) {
			return uri.replace(re, '$1' + key + "=" + value + '$2');
		} else {
			return uri + separator + key + "=" + value;
		}
	}

	if ($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0) {

		// ===== Quill editor

		const quillEditors = {};
		let quillEditorsCount = 0;
		let activeQuill = null;

		$('.quill-editor').each(function () {
			const obj = this;

			quillEditorsCount++;

			const quill = new Quill(this, {
				theme: "snow",
				modules: {
					toolbar: {
						container: [
							[{ 'header': [1, 2, 3, 4, false] }],
							['bold', 'italic', 'underline', 'strike'],
							[{ 'color': [] }, { 'background': [] }],
							['blockquote', 'code-block'],
							[{ 'list': 'ordered' }, { 'list': 'bullet' }, { 'list': 'check' }],
							['link'],
							[{ 'script': 'sub' }, { 'script': 'super' }],      // superscript/subscript
							[{ 'indent': '-1' }, { 'indent': '+1' }],          // outdent/indent
							[{ 'align': [] }],
							[{ 'direction': 'rtl' }],                         // text direction
							['custom-fullscreen'] // Botão customizado
						],
						handlers: {
							'custom-fullscreen': function () {
								const editor = quill.root; // Referência ao root do Quill
								const toolbar = quill.container.previousElementSibling; // Toolbar
								editor.classList.toggle('fullscreen');
								toolbar.classList.toggle('fullscreen');
							}
						}
					}
				}
			});

			quill.on('text-change', function (delta, oldDelta, source) {
				// Disparar atualização apenas se a mudança veio do usuário (não de API)
				if (source === 'user') {
					$.input_delay_to_change({
						trigger_selector: '#gestor-listener',
						trigger_event: 'htmlfield-change',
						value: {
							parent: $(obj).parents('.pfc-field-html') // Ou usar a referência armazenada
						}
					});
				}
			});

			// Rastrear foco no editor
			quill.root.addEventListener('focus', function () {
				activeQuill = quill; // Define o editor ativo
			});

			// Limpar activeQuill no unfocus
			quill.root.addEventListener('blur', function () {
				activeQuill = null;
			});

			quillEditors[quillEditorsCount] = {
				quill,
				parent: $(obj).parents('.pfc-field-html')
			};
		});

		if (quillEditorsCount > 0) {
			// Traduzir tooltips após inicialização
			if (gestor.language != 'en' && gestor.quillTranslation !== undefined) {
				setTimeout(() => {
					// Traduzir mensagens via CSS dinâmico
					const style = document.createElement('style');
					style.textContent = gestor.quillTranslation;
					document.head.appendChild(style);
				}, 100); // Pequeno delay para garantir que os editores sejam renderizados
			}

			document.addEventListener('keydown', function (e) {
				if (e.key === 'Escape') {
					const editor = activeQuill.root;
					const toolbar = activeQuill.container.previousElementSibling;
					editor.classList.remove('fullscreen');
					toolbar.classList.remove('fullscreen');
				}

				if (e.key === 'F11') {
					if (activeQuill) {
						e.preventDefault(); // Previne fullscreen do navegador
						const editor = activeQuill.root;
						const toolbar = activeQuill.container.previousElementSibling;
						editor.classList.toggle('fullscreen');
						toolbar.classList.toggle('fullscreen');
					}
				}
			});
		}

		function valorAtualizadoQuillEditor(id) {
			if (!id) return '';
			if (Object.keys(quillEditors).length == 0) return '';

			for (let key in quillEditors) {
				const editorObj = quillEditors[key];

				if (editorObj && editorObj.parent)
					if (editorObj.parent.data('id') == id) {
						let html = editorObj.quill.root.innerHTML;
						html = html === '<p><br></p>' ? '' : html;

						const htmlProcessed = html != '' ? gestor.quillShowContainer.replace(/\[\[field-value\]\]/g, html) : '';

						return htmlProcessed;
					}
			}

			return '';
		}

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

			if ('publisherPathPrefix' in gestor && gestor.publisherPathPrefix.length > 0) {
				value = gestor.publisherPathPrefix + '/' + value;
			}

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

		// ===== Dropdown

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

		// ===== Dropdown do publisher

		$('.publisherDropdown')
			.dropdown({
				onChange: function (value, text, $choice) {
					if (value.length > 0) {
						var currentUrl = window.location.href;
						var newUrl = updateQueryStringParameter(currentUrl, 'publisher_id', value);
						window.location.href = newUrl;
					}
				}
			});

		// ===== Atualização dos valores atualizar automaticamente o Editor HTML

		// Changing images fields

		window.addEventListener('message', function (e) {
			try {
				var data = JSON.parse(e.data);

				switch (data.moduloId) {
					case 'admin-arquivos':
					case 'arquivos':
						var dados = JSON.parse(decodeURI(data.data));

						if (dados.tipo.match(/image\//) == 'image/') {
							publisherValuesUpdate();
						}
						break;
				}
			} catch (error) {
				return;
			}
		});

		$(document.body).on('mouseup tap', '._gestor-widgetImage-btn-del', function (e, value, p) {
			setTimeout(() => {
				publisherValuesUpdate();
			}, 100);
		});

		// Changing text & textarea fields

		$(document.body).on('keyup', '.pfc-field input[type="text"], .pfc-field textarea', function (e) {
			if (e.which == 9) return false;

			var value = $(this).val();

			$.input_delay_to_change({
				trigger_selector: '#gestor-listener',
				trigger_event: 'textfield-change',
				value: value
			});
		});

		$(document.body).on('textfield-change', '#gestor-listener', function (e, value, p) {
			if (!p) p = {};

			publisherValuesUpdate();
		});

		// Changing HTML fields

		$(document.body).on('htmlfield-change', '#gestor-listener', function (e, value, p) {
			if (!p) p = {};

			const parent = value.parent;
			const id = parent.data('id');
			const html = valorAtualizadoQuillEditor(id);

			parent.find('input[type="hidden"]').val(html);

			publisherValuesUpdate();
		});

		// ===== Atualização dos valores pelo ou no Editor HTML

		function publisherValuesUpdate() {
			if ('publisherValuesUpdate' in window) {
				window.publisherValuesUpdate();
			}

			publisherVariablesUpdate();
		}

		function publisherVariablesUpdate() {
			if ('publisherGetAllVariables' in window) {
				const variables = window.publisherGetAllVariables();

				$('.field-variable').each(function () {
					const variableName = $(this).text().trim();

					if (variables.includes(variableName)) {
						$(this).addClass('teal');
					} else {
						$(this).removeClass('teal');
					}
				});
			}
		}

		publisherVariablesUpdate();

		function updatedCodeMirrorHtml() {
			setTimeout(() => {
				publisherVariablesUpdate();
			}, 200);
		}

		window.updatedCodeMirrorHtml = updatedCodeMirrorHtml;

		function pegarValoresAtualizadosDoPublisherPagina(params = {}) {
			values = {};

			// Pegar os valores atualizados das variaveis do Publisher Página.
			$('.pfc-field').each(function () {
				const fieldId = $(this).data('id');
				const fieldType = $(this).data('type');
				const fieldVariable = $(this).find('.field-variable').text().trim() || '';

				let fieldValue = '';

				switch (fieldType) {
					case 'image':
						const inputHidden = $(this).find('._gestor-widgetImage-file-caminho');
						if (inputHidden.length > 0) {
							fieldValue = gestor.raiz + inputHidden.val();
						}
						break;
					case 'textarea':
						const textarea = $(this).find('textarea');
						if (textarea.length > 0) {
							fieldValue = textarea.val().replace(/\n/g, '<br>');
						}
						break;
					case 'html':
						fieldValue = valorAtualizadoQuillEditor(fieldId);
						break;
					default:
						const inputElement = $(this).find('input[type="text"]');
						if (inputElement.length > 0) {
							fieldValue = inputElement.val();
						}
				}

				values[fieldId] = {
					fieldValue,
					fieldType,
					fieldVariable
				};
			});

			return values;
		}

		window.pegarValoresAtualizadosDoPublisherPagina = pegarValoresAtualizadosDoPublisherPagina;
	}

	if ($('#_gestor-interface-listar').length > 0) {
		$('.ui.radio.checkbox').checkbox({
			onChange: function () {
				const tipo = $(this).val();

				var currentUrl = window.location.href;
				var newUrl = updateQueryStringParameter(currentUrl, 'tipo', tipo);
				window.location.href = newUrl;
			}
		});

		$('.gestorModule')
			.dropdown({
				onChange: function (value, text, $choice) {
					var currentUrl = window.location.href;
					var newUrl = updateQueryStringParameter(currentUrl, 'module_id', value);
					window.location.href = newUrl;
				}
			});

		$('.publisherDropdown')
			.dropdown({
				onChange: function (value, text, $choice) {
					var currentUrl = window.location.href;
					var newUrl = updateQueryStringParameter(currentUrl, 'publisher_id', value);
					window.location.href = newUrl;
				}
			});
	}
});