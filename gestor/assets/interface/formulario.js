$(document).ready(function () {

	function start() {
		if ($('._forms-submissions-controller').length > 0) {
			const formObj = $('._forms-submissions-controller');
			const formDefinition = ('form' in gestor && gestor.form) ? gestor.form : null;

			formObj.each(function () {
				var form = $(this);
				if (formDefinition) {
					// Verifica se há um blockWrapper (ex.: mensagem de bloqueio do backend)
					if ('blockWrapper' in formDefinition && formDefinition.blockWrapper) {
						// Substitui o conteúdo do formulário pelo wrapper de bloqueio
						form.html(formDefinition.blockWrapper);
					} else {
						// Caso não haja bloqueio, inicializa o controlador normalmente
						initFormController(form, formDefinition);
					}
				}
			});

			function initFormController(form, data) {
				// Verificar se o formulário está ativo
				if (data.formStatus !== 'A') {
					form.find('input, textarea, select, button').prop('disabled', true);
					form.html(data.ui.components.formDisabled);
					return;
				}

				data.fields.forEach(function (field) {
					var input = form.find('[name="' + field.name + '"]');
					if (input.length) {
						input.on('blur input', function () {
							validateField(input, field, data.prompts, data.framework, data);
						});
					}
				});
				// Validação geral no submit
				form.on('submit', function (e) {
					e.preventDefault();
					if (validateAllFields(form, data.fields, data.prompts, data.framework, data)) {
						submitForm(form, data);
					}
				});
			}

			function validateAllFields(form, fields, prompts, framework, data) {
				var allValid = true;
				fields.forEach(function (field) {
					var input = form.find('[name="' + field.name + '"]');
					if (input.length) {
						var isFieldValid = validateField(input, field, prompts, framework, data);
						if (!isFieldValid) {
							allValid = false;
						}
					}
				});
				return allValid;
			}

			function validateField(input, field, prompts, framework, data) {
				var value = input.val().trim();
				var isValid = true;
				var errorMsg = '';

				if (field.required && !value) {
					isValid = false;
					errorMsg = prompts.empty.replace('#label#', field.label);
				} else if (field.type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
					isValid = false;
					errorMsg = prompts.email.replace('#label#', field.label);
				}
				// Adicione mais regras (ex.: minLength, regex) baseadas em field.rules

				updateFieldUI(input, isValid, errorMsg, framework, data);
				return isValid;
			}

			function updateFieldUI(input, isValid, errorMsg, framework, data) {
				var fieldContainer = input.closest('.field') || input.parent(); // Assume estrutura com .field (Fomantic) ou div pai
				fieldContainer.find('.error-msg').remove(); // Remove erro anterior

				if (!isValid) {
					var errorElement = data.ui.components.errorElement.replace('#message#', errorMsg);
					var errorElementObj = $(errorElement);
					// Ajuste classes conforme framework
					if (framework === 'fomantic') {
						errorElementObj.addClass('ui red pointing label'); // Fomantic: label vermelha
						input.addClass('error').removeClass('success');
					} else if (framework === 'tailwind') {
						errorElementObj.addClass('text-red-500 text-sm mt-1'); // Tailwind: texto vermelho pequeno
						input.addClass('border-red-500').removeClass('border-green-500');
					}
					fieldContainer.append(errorElementObj);
				} else {
					// Sucesso: remova erros e adicione classe positiva
					input.removeClass('error').addClass('success');
					if (framework === 'fomantic') {
						input.addClass('valid');
					} else if (framework === 'tailwind') {
						input.addClass('border-green-500').removeClass('border-red-500');
					}
				}
			}

			function submitForm(form, data) {
				// Gerar fingerprint (usando FingerprintJS)
				import('https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@3/dist/fp.min.js').then(FingerprintJS => {
					FingerprintJS.load().then(fp => {
						fp.get().then(result => {
							const currentTimestamp = data.serverTimestamp + (Date.now() / 1000 - data.serverTimestamp); // Ajuste com offset do cliente
							const fingerprint = result.visitorId;
							form.append('<input type="hidden" name="fingerprint" value="' + fingerprint + '">');
							form.append('<input type="hidden" name="timestamp" value="' + currentTimestamp + '">');
							form.append('<input type="text" name="honeypot" style="display:none;" value="">');

							// Sempre tentar v3 primeiro (se ativo)
							if ('googleRecaptchaActive' in data && data.googleRecaptchaActive) {
								var action = ('googleRecaptchaAction' in data && data.googleRecaptchaAction) ? data.googleRecaptchaAction : 'submit';
								var googleSiteKey = data.googleRecaptchaSite;

								grecaptcha.ready(function () {
									grecaptcha.execute(googleSiteKey, { action: action }).then(function (token) {
										form.append('<input type="hidden" name="token" value="' + token + '">');
										form.append('<input type="hidden" name="action" value="' + action + '">');
										performAjaxSubmit(form, data);
									});
								});
							} else {
								performAjaxSubmit(form, data);
							}
						});
					});
				});
			}

			function addDimmer(form, data) {
				var dimmerKey = (data.framework === 'fomantic') ? 'dimmerFomantic' : 'dimmerTailwind';
				var dimmerHtml = data.ui.components[dimmerKey].replace('#loadingText#', data.ui.texts.loading);
				var dimmer = $(dimmerHtml);
				form.addClass('relative').append(dimmer);
				dimmer.addClass('active'); // Ativar dimmer
			}

			function removeDimmer(form) {
				form.find('.dimmer, .fixed').remove();
				form.removeClass('relative');
			}

			function performAjaxSubmit(form, data) {
				addDimmer(form, data); // Adicionar dimmer

				var formData = new FormData(form[0]);
				formData.append('ajax', '1');
				formData.append('ajaxOpcao', data.ajaxOpcao || 'forms-process');
				formData.append('_formId', data.formId);

				$.ajax({
					url: data.formAction || form.attr('action') || window.location.href,
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					timeout: 10000,
					success: function (response) {
						removeDimmer(form); // Remover dimmer
						if (response.status === 'success') {
							window.location.href = response.redirect;
						} else if (response.status === 'require_v2' && 'googleRecaptchaV2Active' in data && data.googleRecaptchaV2Active) {
							injectRecaptchaV2(form, data);
						} else {
							showError(response.message, data);
						}
					},
					error: function (xhr, status, error) {
						removeDimmer(form); // Remover dimmer
						if (status === 'timeout') {
							showError(data.ui.texts.timeoutError, data);
						} else {
							showError(data.ui.texts.generalError, data);
						}
					}
				});
			}

			function injectRecaptchaV2(form, data) {
				if (!form.find('.g-recaptcha').length) {
					// Carregar script do reCAPTCHA se necessário
					if (typeof grecaptcha === 'undefined') {
						var script = document.createElement('script');
						script.src = 'https://www.google.com/recaptcha/api.js';
						script.async = true;
						script.defer = true;
						document.head.appendChild(script);
						script.onload = function () {
							proceedWithRecaptchaV2(form, data);
						};
					} else {
						proceedWithRecaptchaV2(form, data);
					}
				}
			}

			function proceedWithRecaptchaV2(form, data) {
				var recaptchaHtml = data.ui.components.recaptchaV2.replace('#siteKey#', data.googleRecaptchaV2Site);
				var recaptchaDiv = $(recaptchaHtml);
				form.append(recaptchaDiv);
				grecaptcha.render(recaptchaDiv[0]);
				// Mostrar mensagem para usuário completar v2
				showError(data.ui.texts.requireV2Message, data);
				// Re-bind submit para tentar novamente após v2
				form.off('submit').on('submit', function (e) {
					e.preventDefault();
					if (validateAllFields(form, data.fields, data.prompts, data.framework)) {
						performAjaxSubmit(form, data);
					}
				});
			}

			function showError(message, data) {
				var errorElement = data.ui.components.errorElement.replace('#message#', message);
				var errorDiv = $(errorElement);
				$('._forms-submissions-controller').prepend(errorDiv);
			}
		}
	}

	start();
});