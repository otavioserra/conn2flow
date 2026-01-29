$(document).ready(function () {
	if ($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0) {
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
	}

	if ($('#_gestor-interface-listar').length > 0) {
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
	}
});