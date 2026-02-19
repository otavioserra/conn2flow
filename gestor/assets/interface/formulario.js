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
						// apply maxlength (schema override or defaults: text/email=254, textarea=10000)
						var maxLength = field.max_length ? parseInt(field.max_length, 10) : (['text', 'email'].indexOf(field.type) !== -1 ? 254 : (field.type === 'textarea' ? 10000 : null));
						if (maxLength) {
							input.attr('maxlength', maxLength);
							// try to find existing counter inside .field, fallback to siblings/parent
							var counter = input.closest('.field').find('.char-counter');
							if (!counter.length) {
								input.after('<div class="field-counter"><small class="char-counter">0 / ' + maxLength + '</small></div>');
								counter = input.closest('.field').find('.char-counter');
								if (!counter.length) counter = input.siblings('.field-counter').find('.char-counter');
								if (!counter.length) counter = input.nextAll('.field-counter').find('.char-counter');
								if (!counter.length) counter = input.parent().find('.char-counter');
							}
							// initial update and live update
							updateCharCounter(input, maxLength);
							input.on('input', function () { updateCharCounter($(this), maxLength); });
						}

					}
				});

				// Capturar botão clicado
				form.find('button[type="submit"], input[type="submit"]').on('click', function () {
					form.data('clickedButton', $(this));
					clearError(data, form);
				});

				// Validação geral no submit
				form.on('submit', function (e) {
					e.preventDefault();
					var clickedButton = form.data('clickedButton');
					if (validateAllFields(form, data.fields, data.prompts, data.framework, data)) {
						submitForm(form, data, clickedButton);
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
					var errorElementKey = (framework === 'fomantic-ui') ? 'errorElementFomantic' : 'errorElementTailwind';
					var errorElement = data.ui.components[errorElementKey].replace('#message#', errorMsg);
					var errorElementObj = $(errorElement);
					errorElementObj.addClass('error-msg');
					// Ajuste classes conforme framework
					if (framework === 'fomantic-ui') {
						input.addClass('error').removeClass('success');
					} else if (framework === 'tailwindcss') {
						input.addClass('border-red-500').removeClass('border-green-500');
					}
					fieldContainer.append(errorElementObj);
				} else {
					// Sucesso: remova erros e adicione classe positiva
					input.removeClass('error').addClass('success');
					input.parent().find('.error-msg').remove();
					if (framework === 'fomantic-ui') {
						input.addClass('valid');
					} else if (framework === 'tailwindcss') {
						input.addClass('border-green-500').removeClass('border-red-500');
					}
				}
			}

			function updateCharCounter(input, maxLength) {
				var $input = (input instanceof jQuery) ? input : $(input);
				var val = $input.val() || '';
				var length = val.length;
				var counter = $input.closest('.field').find('.char-counter');
				if (!counter.length) counter = $input.siblings('.field-counter').find('.char-counter');
				if (!counter.length) counter = $input.nextAll('.field-counter').find('.char-counter');
				if (!counter.length) counter = $input.parent().find('.char-counter');
				if (counter.length) {
					counter.text(length + ' / ' + maxLength);
					if (length > maxLength) {
						counter.css('color', '#dc2626');
					} else {
						counter.css('color', '');
					}
				}
			}

			function submitForm(form, data, clickedButton = null) {
				addDimmer(form, data); // Adicionar dimmer

				// Gerar fingerprint usando uma abordagem mais simples e confiável
				const generateFingerprint = () => {
					return new Promise((resolve) => {
						// Usar uma combinação de propriedades do navegador para criar um fingerprint simples
						const canvas = document.createElement('canvas');
						const ctx = canvas.getContext('2d');
						ctx.textBaseline = 'top';
						ctx.font = '14px Arial';
						ctx.fillText('Fingerprint', 2, 2);

						const fingerprint = [
							navigator.userAgent,
							navigator.language,
							screen.width + 'x' + screen.height,
							new Date().getTimezoneOffset(),
							!!window.sessionStorage,
							!!window.localStorage,
							!!window.indexedDB,
							canvas.toDataURL()
						].join('|');

						// Criar hash simples do fingerprint
						let hash = 0;
						for (let i = 0; i < fingerprint.length; i++) {
							const char = fingerprint.charCodeAt(i);
							hash = ((hash << 5) - hash) + char;
							hash = hash & hash; // Converter para 32 bits
						}

						resolve(Math.abs(hash).toString(36));
					});
				};

				// Tentar FingerprintJS primeiro, fallback para método simples
				const tryFingerprintJS = () => {
					return import('https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@4/dist/fp.min.js')
						.then(FingerprintJS => FingerprintJS.load())
						.then(fp => fp.get())
						.then(result => result.visitorId || result.requestId)
						.catch(() => generateFingerprint());
				};

				tryFingerprintJS().then(fingerprint => {
					performPreAjaxSubmit(form, data, fingerprint, clickedButton);
				}).catch(error => {
					// Fallback final - continuar sem fingerprint
					console.warn('All fingerprint methods failed, continuing without fingerprint:', error);

					performPreAjaxSubmit(form, data, null, clickedButton);
				});
			}

			function performPreAjaxSubmit(form, data, fingerprint = null, clickedButton = null) {
				if (fingerprint) {
					form.append('<input type="hidden" name="fingerprint" value="' + fingerprint + '">');
				}

				const currentTimestamp = data.serverTimestamp + (Date.now() / 1000 - data.serverTimestamp);
				form.append('<input type="hidden" name="timestamp" value="' + currentTimestamp + '">');
				form.append('<input type="text" name="honeypot" style="display:none;" value="">');

				// Sempre tentar v3 primeiro (se ativo)
				if ('googleRecaptchaActive' in data && data.googleRecaptchaActive) {
					var action = ('googleRecaptchaAction' in data && data.googleRecaptchaAction) ? data.googleRecaptchaAction : 'submit';
					var googleSiteKey = data.googleRecaptchaSite;

					// Carregar script do reCAPTCHA v3 dinamicamente se necessário
					if (typeof grecaptcha === 'undefined') {
						var script = document.createElement('script');
						script.src = 'https://www.google.com/recaptcha/api.js?render=' + googleSiteKey;
						script.async = true;
						script.defer = true;
						document.head.appendChild(script);
						script.onload = function () {
							executeRecaptchaV3(form, data, googleSiteKey, action, clickedButton);
						};
						script.onerror = function () {
							// Fallback se o script falhar - continuar sem reCAPTCHA
							console.warn('reCAPTCHA v3 script failed to load, continuing without reCAPTCHA');
							performAjaxSubmit(form, data, clickedButton);
						};
					} else {
						executeRecaptchaV3(form, data, googleSiteKey, action, clickedButton);
					}
				} else {
					performAjaxSubmit(form, data, clickedButton);
				}
			}

			function executeRecaptchaV3(form, data, googleSiteKey, action, clickedButton = null) {
				grecaptcha.ready(function () {
					grecaptcha.execute(googleSiteKey, { action: action }).then(function (token) {
						form.append('<input type="hidden" name="token" value="' + token + '">');
						form.append('<input type="hidden" name="action" value="' + action + '">');
						performAjaxSubmit(form, data, clickedButton);
					}).catch(function (error) {
						// Fallback se a execução falhar - continuar sem reCAPTCHA
						console.warn('reCAPTCHA v3 execution failed, continuing without reCAPTCHA:', error);
						performAjaxSubmit(form, data, clickedButton);
					});
				});
			}

			function addDimmer(form, data) {
				var dimmerKey = (data.framework === 'fomantic-ui') ? 'dimmerFomantic' : 'dimmerTailwind';
				var dimmerHtml = data.ui.components[dimmerKey].replace('#loadingText#', data.ui.texts.loading);
				var dimmer = $(dimmerHtml);
				form.addClass('relative').append(dimmer);
				if (data.framework === 'fomantic-ui') {
					dimmer.find('.dimmer').addClass('active visible');
				} else {
					dimmer.addClass('active');
				}
			}

			function removeDimmer(form, data) {
				if (data.framework === 'fomantic-ui') {
					form.find('.component-dimmer-fomantic').remove();
				} else {
					form.find('.component-dimmer-tailwind').remove();
				}
			}

			function performAjaxSubmit(form, data, clickedButton = null) {
				if (!('formData' in data) || !data.formData) {
					data.formData = new FormData(form[0]);

					data.formData.append('ajax', '1');
					data.formData.append('ajaxOpcao', data.ajaxOpcao || 'forms-process');
					data.formData.append('_formId', data.formId);
				}

				const formData = data.formData ?? {};

				$.ajax({
					url: data.formAction || form.attr('action') || window.location.href,
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					timeout: 15000,
					success: function (response) {
						if (response.status === 'success') {
							window.location.href = gestor.raiz + response.redirect;
						} else if (response.status === 'require_v2' && 'googleRecaptchaV2Active' in data && data.googleRecaptchaV2Active) {
							removeDimmer(form, data);
							injectRecaptchaV2(form, data, clickedButton);
						} else {
							removeDimmer(form, data);
							showError(response.message, data, clickedButton, form);
						}
					},
					error: function (xhr, status, error) {
						removeDimmer(form, data); // Remover dimmer
						if (status === 'timeout') {
							showError(data.ui.texts.timeoutError, data, clickedButton, form);
						} else {
							showError(data.ui.texts.generalError, data, clickedButton, form);
						}
					}
				});
			}

			function injectRecaptchaV2(form, data, clickedButton = null) {
				if (!form.find('.g-recaptcha').length) {
					// Carregar script do reCAPTCHA se necessário
					if (typeof grecaptcha === 'undefined') {
						var script = document.createElement('script');
						script.src = 'https://www.google.com/recaptcha/api.js';
						script.async = true;
						script.defer = true;
						document.head.appendChild(script);
						script.onload = function () {
							proceedWithRecaptchaV2(form, data, clickedButton);
						};
					} else {
						proceedWithRecaptchaV2(form, data, clickedButton);
					}
				}
			}

			function proceedWithRecaptchaV2(form, data, clickedButton = null) {
				// Mostrar mensagem para usuário completar v2
				showError(data.ui.texts.requireV2Message, data, clickedButton, form);

				var recaptchaHtml = data.ui.components.recaptchaV2;
				var recaptchaDiv = $(recaptchaHtml);
				if (clickedButton && clickedButton.length) {
					clickedButton.before(recaptchaDiv);
				} else {
					form.append(recaptchaDiv);
				}
				grecaptcha.render(recaptchaDiv[0], {
					'sitekey': data.googleRecaptchaV2Site
				});
				// Re-bind submit para tentar novamente após v2
				form.off('submit').on('submit', function (e) {
					e.preventDefault();
					if (validateAllFields(form, data.fields, data.prompts, data.framework)) {
						performAjaxSubmit(form, data, clickedButton);
					}
				});
			}

			function showError(message, data, clickedButton = null, form) {
				clearError(data, form);
				var errorMessageKey = (data.framework === 'fomantic-ui') ? 'errorMessageFomantic' : 'errorMessageTailwind';
				var errorMessage = data.ui.components[errorMessageKey].replace('#message#', message);
				var errorDiv = $(errorMessage);
				if (clickedButton && clickedButton.length) {
					clickedButton.before(errorDiv);
				} else {
					$('._forms-submissions-controller').prepend(errorDiv);
				}
			}

			function clearError(data, form) {
				if (data.framework === 'fomantic-ui') {
					form.find('.component-error-message-fomantic').remove();
				} else {
					form.find('.component-error-message-tailwind').remove();
				}
			}
		}
	}

	start();
});