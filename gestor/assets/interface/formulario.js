$(document).ready(function () {

	function start() {
		// Marcador clássico (._forms-submissions-controller) + marcador padrão de widgets (.conn2flow-form).
		var formObj = $('form._forms-submissions-controller, form.conn2flow-form');
		if (formObj.length > 0) {
			var formStore = ('form' in gestor && gestor.form) ? gestor.form : null;

			function ensureFormInstanceId(form, formKey, index) {
				var instanceId = form.attr('data-form-instance-id');
				if (instanceId) return instanceId;

				var base = (formKey && String(formKey).trim() !== '') ? String(formKey).trim() : 'form';
				instanceId = base + '--instance-' + (index + 1);
				form.attr('data-form-instance-id', instanceId);
				return instanceId;
			}

			function resolveFormConfig(form, formStore) {
				function pickScalar(value, fallback) {
					if (Array.isArray(value)) {
						for (var i = 0; i < value.length; i++) {
							var picked = pickScalar(value[i], '');
							if (picked !== '') return picked;
						}
						return (typeof fallback === 'string') ? fallback : '';
					}

					if (value === null || typeof value === 'undefined') return (typeof fallback === 'string') ? fallback : '';
					if (typeof value === 'string') return value.trim();
					if (typeof value === 'number' || typeof value === 'boolean') return String(value);
					return (typeof fallback === 'string') ? fallback : '';
				}

				function toBool(value, fallback) {
					if (Array.isArray(value)) value = pickScalar(value, '');
					if (typeof value === 'boolean') return value;
					if (typeof value === 'number') return value !== 0;
					if (typeof value === 'string') {
						var v = value.trim().toLowerCase();
						if (v === 'true' || v === '1' || v === 'yes' || v === 'on') return true;
						if (v === 'false' || v === '0' || v === 'no' || v === 'off' || v === '') return false;
					}
					return !!fallback;
				}

				function normalizeFormConfig(data, fallbackFormKey) {
					if (!data || typeof data !== 'object') return data;
					var normalized = $.extend(true, {}, data);

					normalized.formId = pickScalar(normalized.formId, fallbackFormKey || '');
					normalized.formAction = pickScalar(normalized.formAction, '');
					normalized.ajaxOpcao = pickScalar(normalized.ajaxOpcao, 'forms-process');
					normalized.formStatus = pickScalar(normalized.formStatus, 'A');
					normalized.framework = pickScalar(normalized.framework, 'tailwindcss');
					normalized.serverTimestamp = parseFloat(pickScalar(normalized.serverTimestamp, '0')) || 0;
					normalized.blockWrapper = pickScalar(normalized.blockWrapper, '');
					normalized.googleRecaptchaActive = toBool(normalized.googleRecaptchaActive, false);
					normalized.googleRecaptchaSite = pickScalar(normalized.googleRecaptchaSite, '');
					normalized.googleRecaptchaAction = pickScalar(normalized.googleRecaptchaAction, 'submit');
					normalized.googleRecaptchaV2Active = toBool(normalized.googleRecaptchaV2Active, false);
					normalized.googleRecaptchaV2Site = pickScalar(normalized.googleRecaptchaV2Site, '');

					if (normalized.ui && typeof normalized.ui === 'object') {
						if (normalized.ui.texts && typeof normalized.ui.texts === 'object') {
							Object.keys(normalized.ui.texts).forEach(function (k) {
								normalized.ui.texts[k] = pickScalar(normalized.ui.texts[k], '');
							});
						}
						if (normalized.ui.components && typeof normalized.ui.components === 'object') {
							Object.keys(normalized.ui.components).forEach(function (k) {
								normalized.ui.components[k] = pickScalar(normalized.ui.components[k], '');
							});
						}
					}

					return normalized;
				}

				var formKey = form.attr('data-form-id') || form.attr('id') || form.attr('name') || '';

				if (formKey && Object.prototype.hasOwnProperty.call(formStore, formKey) && typeof formStore[formKey] === 'object') {
					return { key: formKey, data: normalizeFormConfig(formStore[formKey], formKey) };
				}

				// Retrocompatibilidade: somente quando gestor.form já é uma config de formulário único.
				var looksLikeSingleFormConfig = !!(formStore && typeof formStore === 'object' && Array.isArray(formStore.fields) && formStore.ui);
				if (looksLikeSingleFormConfig) {
					return { key: formKey || formStore.formId || '', data: normalizeFormConfig(formStore, formKey || formStore.formId || '') };
				}

				return { key: formKey, data: null };
			}

			formObj.each(function (index) {
				var form = $(this);
				if (!formStore) return;

				// Resolve por data-form-id (mesmo formulário lógico) e separa por instância.
				var resolved = resolveFormConfig(form, formStore);
				var formKey = resolved.key;
				var data = resolved.data;

				if (!data || typeof data !== 'object') return;

				ensureFormInstanceId(form, formKey, index);

				// Verifica se há um blockWrapper (ex.: mensagem de bloqueio do backend)
				var blockWrapperHtml = (typeof data.blockWrapper === 'string') ? data.blockWrapper.trim() : '';
				if (blockWrapperHtml !== '') {
					// Substitui o conteúdo do formulário pelo wrapper de bloqueio
					form.html(blockWrapperHtml);
				} else {
					// Caso não haja bloqueio, inicializa o controlador normalmente
					initFormController(form, data);
				}
			});

			function initFormController(form, data) {
				if (form.data('c2fFormControllerReady')) return;
				form.data('c2fFormControllerReady', true);

				// Verificar se o formulário está ativo
				var normalizedFormStatus = (typeof data.formStatus === 'string')
					? data.formStatus.trim().toUpperCase()
					: '';
				if (normalizedFormStatus !== '' && normalizedFormStatus !== 'A') {
					form.find('input, textarea, select, button').prop('disabled', true);
					var formDisabledHtml = (data && data.ui && data.ui.components && typeof data.ui.components.formDisabled === 'string')
						? data.ui.components.formDisabled.trim()
						: '';
					if (formDisabledHtml !== '') {
						form.html(formDisabledHtml);
					}
					return;
				}

				data.fields.forEach(function (field) {
					var input = form.find('[name="' + field.name + '"]');
					if (input.length) {
						// apply maxlength (schema override or defaults: text/email=254, textarea=10000)
						var defaultMaxLength = field.max_length ? parseInt(field.max_length, 10) : (['text', 'email'].indexOf(field.type) !== -1 ? 254 : (field.type === 'textarea' ? 10000 : null));
						var existingMaxLength = input.attr('maxlength');
						var maxLength = existingMaxLength ? parseInt(existingMaxLength, 10) : defaultMaxLength;
						if (maxLength) {
							if (!existingMaxLength) input.attr('maxlength', maxLength);
							var counter = getCharCounter(input);
							if (!counter.length) {
								input.after('<div class="field-counter"><small class="char-counter">0 / ' + maxLength + '</small></div>');
								counter = getCharCounter(input);
							}
							// initial update and live update
							updateCharCounter(input, maxLength);
							input.on('input', function () { updateCharCounter($(this), maxLength); });
						}

					}
				});

				// ===== Melhoria progressiva (Progressive Enhancement)
				// Date picker Fomantic quando a biblioteca estiver disponível; caso contrário, mantém
				// o comportamento nativo do navegador para <input type="date">.
				if (typeof $.fn.calendar !== 'undefined') {
					form.find('.forms-date-picker').each(function () {
						var $inp = $(this);
						try {
							$inp.calendar({
								type: 'date',
								formatter: {
									date: function (date) {
										if (!date) return '';
										var y = date.getFullYear();
										var m = ('0' + (date.getMonth() + 1)).slice(-2);
										var d = ('0' + date.getDate()).slice(-2);
										return y + '-' + m + '-' + d;
									}
								}
							});
						} catch (e) { }
					});
				}

				// Dropdowns Fomantic interativos sobre selects marcados com .ui.dropdown; senão <select> nativo.
				if (typeof $.fn.dropdown !== 'undefined') {
					try { form.find('select.ui.dropdown').dropdown(); } catch (e) { }
				}

				// Alternador de visibilidade de senha (Vanilla, delegado, funciona em qualquer framework).
				form.on('click', '.forms-password-toggle', function (e) {
					e.preventDefault();
					var $toggle = $(this);
					var $wrapper = $(this).closest('.forms-password-wrapper');
					var $input = $wrapper.find('input').first();
					if (!$input.length) return;
					var showing = $input.attr('type') === 'password';
					$input.attr('type', showing ? 'text' : 'password');
					$toggle.find('.forms-password-icon-eye').css('display', showing ? 'none' : '');
					$toggle.find('.forms-password-icon-eye-slash').css('display', showing ? '' : 'none');
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

				// Máscara para telefone
				form.find('input[type="tel"]').on('input', function () {
					this.value = formatarTelefone(this.value);
				});
			}

			function formatarTelefone(value) {
				value = value.replace(/\D/g, '');

				if (value.length > 11) {
					value = value.slice(0, 11);
				}

				if (value.length <= 2) {
					return value ? '(' + value : '';
				}

				if (value.length <= 6) {
					return '(' + value.slice(0, 2) + ') ' + value.slice(2);
				}

				if (value.length <= 10) {
					return '(' + value.slice(0, 2) + ') ' + value.slice(2, 6) + '-' + value.slice(6);
				}

				return '(' + value.slice(0, 2) + ') ' + value.slice(2, 7) + '-' + value.slice(7);
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

			function getUiText(data, key, fallback) {
				if (data && data.ui && data.ui.texts && typeof data.ui.texts[key] === 'string') return data.ui.texts[key];
				return (typeof fallback === 'string') ? fallback : '';
			}

			function getUiComponent(data, key, fallback) {
				if (data && data.ui && data.ui.components && typeof data.ui.components[key] === 'string') return data.ui.components[key];
				return (typeof fallback === 'string') ? fallback : '';
			}

			// Extrai diretivas de limite (min/max/step) das linhas do campo "Opções" de um campo.
			function parseFieldLimits(field) {
				var res = { min: null, max: null, step: null };
				var opts = field ? field.options : null;
				if (typeof opts === 'string') opts = opts.split(/\r\n|\r|\n/);
				if (!Array.isArray(opts)) return res;
				opts.forEach(function (line) {
					if (typeof line !== 'string') return;
					var m = line.match(/^\s*(min|max|step)\s*:\s*(.+?)\s*$/i);
					if (m) res[m[1].toLowerCase()] = m[2];
				});
				return res;
			}

			function pickPrompt(prompts, key, fallback) {
				if (prompts && typeof prompts[key] === 'string' && prompts[key].trim() !== '') return prompts[key];
				return fallback;
			}

			function fillPrompt(tpl, field, vars) {
				var out = String(tpl).split('#label#').join(field.label || field.name || '');
				if (vars) Object.keys(vars).forEach(function (k) { out = out.split('#' + k + '#').join(vars[k]); });
				return out;
			}

			function validateField(input, field, prompts, framework, data) {
				var value = input.val().trim();
				var isValid = true;
				var errorMsg = '';
				var emptyPromptTpl = (prompts && typeof prompts.empty === 'string') ? prompts.empty : 'Campo #label# e obrigatorio.';
				var emailPromptTpl = (prompts && typeof prompts.email === 'string') ? prompts.email : 'Campo #label# precisa de um e-mail valido.';

				if (field.required && !value) {
					isValid = false;
					errorMsg = emptyPromptTpl.replace('#label#', field.label);
				} else if (field.type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
					isValid = false;
					errorMsg = emailPromptTpl.replace('#label#', field.label);
				} else if (value) {
					// Validações customizadas via campo "Opções" (limite de caracteres, valor numérico, faixa de data, URL).
					var limits = parseFieldLimits(field);
					if (field.type === 'text' || field.type === 'textarea') {
						var len = value.length;
						if (limits.min !== null && len < parseInt(limits.min, 10)) {
							isValid = false;
							errorMsg = fillPrompt(pickPrompt(prompts, 'minLength', 'O campo #label# deve ter pelo menos #min# caracteres.'), field, { min: parseInt(limits.min, 10) });
						} else if (limits.max !== null && len > parseInt(limits.max, 10)) {
							isValid = false;
							errorMsg = fillPrompt(pickPrompt(prompts, 'maxLength', 'O campo #label# deve ter no máximo #max# caracteres.'), field, { max: parseInt(limits.max, 10) });
						}
					} else if (field.type === 'number') {
						var num = parseFloat(value.replace(',', '.'));
						if (limits.min !== null && !isNaN(num) && num < parseFloat(limits.min)) {
							isValid = false;
							errorMsg = fillPrompt(pickPrompt(prompts, 'minValue', 'O campo #label# deve ter valor maior ou igual a #min#.'), field, { min: limits.min });
						} else if (limits.max !== null && !isNaN(num) && num > parseFloat(limits.max)) {
							isValid = false;
							errorMsg = fillPrompt(pickPrompt(prompts, 'maxValue', 'O campo #label# deve ter valor menor ou igual a #max#.'), field, { max: limits.max });
						}
					} else if (field.type === 'date') {
						if (limits.min !== null && value < limits.min) {
							isValid = false;
							errorMsg = fillPrompt(pickPrompt(prompts, 'minDate', 'O campo #label# deve ter data a partir de #min#.'), field, { min: limits.min });
						} else if (limits.max !== null && value > limits.max) {
							isValid = false;
							errorMsg = fillPrompt(pickPrompt(prompts, 'maxDate', 'O campo #label# deve ter data até #max#.'), field, { max: limits.max });
						}
					} else if (field.type === 'url') {
						if (!/^[a-z][a-z0-9+.-]*:\/\/.+/i.test(value)) {
							isValid = false;
							errorMsg = fillPrompt(pickPrompt(prompts, 'url', 'O campo #label# precisa de uma URL válida.'), field, null);
						}
					}
				}

				updateFieldUI(input, isValid, errorMsg, framework, data);
				return isValid;
			}

			function updateFieldUI(input, isValid, errorMsg, framework, data) {
				var fieldContainer = input.closest('.field') || input.parent(); // Assume estrutura com .field (Fomantic) ou div pai
				fieldContainer.find('.error-msg').remove(); // Remove erro anterior

				if (!isValid) {
					var errorElementKey = (framework === 'fomantic-ui') ? 'errorElementFomantic' : 'errorElementTailwind';
					var errorElementTpl = getUiComponent(data, errorElementKey, '<small class="error-msg" style="color:#dc2626;display:block;margin-top:6px;">#message#</small>');
					var errorElement = errorElementTpl.replace('#message#', errorMsg);
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

			function getCharCounter(input) {
				var $input = (input instanceof jQuery) ? input : $(input);
				var $container = $input.closest('.field, .conn2flow-form-field');
				if ($container.length) {
					var fromContainer = $container.find('.char-counter').first();
					if (fromContainer.length) return fromContainer;
				}

				var fromSibling = $input.siblings('.field-counter').find('.char-counter').first();
				if (fromSibling.length) return fromSibling;

				var fromNext = $input.nextAll('.field-counter').find('.char-counter').first();
				if (fromNext.length) return fromNext;

				var fromParent = $input.parent().find('.char-counter').first();
				if (fromParent.length) return fromParent;

				return $();
			}

			function updateCharCounter(input, maxLength) {
				var $input = (input instanceof jQuery) ? input : $(input);
				var val = $input.val() || '';
				var length = val.length;
				var counter = getCharCounter($input);
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

				var serverTimestamp = (typeof data.serverTimestamp === 'number' && !isNaN(data.serverTimestamp)) ? data.serverTimestamp : 0;
				const currentTimestamp = serverTimestamp > 0 ? (serverTimestamp + (Date.now() / 1000 - serverTimestamp)) : (Date.now() / 1000);
				form.append('<input type="hidden" name="timestamp" value="' + currentTimestamp + '">');
				form.append('<input type="text" name="honeypot" style="display:none;" value="">');

				// Sempre tentar v3 primeiro (se ativo e com site key valida)
				if (isRecaptchaV3Configured(data)) {
					var action = ('googleRecaptchaAction' in data && data.googleRecaptchaAction) ? data.googleRecaptchaAction : 'submit';
					var googleSiteKey = getRecaptchaSiteKey(data);

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

			function getRecaptchaSiteKey(data) {
				if (!data) return '';
				var key = (typeof data.googleRecaptchaSite === 'string') ? data.googleRecaptchaSite.trim() : '';
				return key;
			}

			function isRecaptchaV3Configured(data) {
				if (!data || !data.googleRecaptchaActive) return false;
				var key = getRecaptchaSiteKey(data);
				if (key === '') return false;
				// Chaves com placeholders ou apenas separadores não são válidas para render.
				if (/^[,\s]+$/.test(key)) return false;
				if (key.indexOf('#') !== -1 || key.indexOf('placeholder') !== -1) return false;
				return true;
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
				var dimmerTpl = getUiComponent(data, dimmerKey, '');
				if (dimmerTpl === '') return;
				var loadingText = getUiText(data, 'loading', 'Carregando...');
				var dimmerHtml = dimmerTpl.replace('#loadingText#', loadingText);
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
				const formData = new FormData(form[0]);
				var formId = (typeof data.formId === 'string' && data.formId.trim() !== '') ? data.formId.trim() : (form.attr('data-form-id') || '');
				var ajaxOpcao = (typeof data.ajaxOpcao === 'string' && data.ajaxOpcao.trim() !== '') ? data.ajaxOpcao.trim() : 'forms-process';
				var formAction = (typeof data.formAction === 'string' && data.formAction.trim() !== '') ? data.formAction.trim() : '';

				formData.append('ajax', '1');
				formData.append('ajaxOpcao', ajaxOpcao);
				formData.append('_formId', formId);

				$.ajax({
					url: formAction || form.attr('action') || window.location.href,
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
							showError(getUiText(data, 'timeoutError', 'Tempo limite excedido.'), data, clickedButton, form);
						} else {
							showError(getUiText(data, 'generalError', 'Erro ao enviar formulario.'), data, clickedButton, form);
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
				showError(getUiText(data, 'requireV2Message', 'Confirme o captcha para continuar.'), data, clickedButton, form, true);

				var recaptchaHtml = getUiComponent(data, 'recaptchaV2', '');
				if (recaptchaHtml === '') return;
				var recaptchaDiv = $(recaptchaHtml);
				if (clickedButton && clickedButton.length) {
					clickedButton.before(recaptchaDiv);
				} else {
					form.append(recaptchaDiv);
				}
				grecaptcha.render(recaptchaDiv[0], {
					'sitekey': (typeof data.googleRecaptchaV2Site === 'string') ? data.googleRecaptchaV2Site : ''
				});
				// Re-bind submit para tentar novamente após v2
				form.off('submit').on('submit', function (e) {
					e.preventDefault();
					if (validateAllFields(form, data.fields, data.prompts, data.framework, data)) {
						performAjaxSubmit(form, data, clickedButton);
					}
				});
			}

			function showError(message, data, clickedButton = null, form, isRecaptchaV2 = false) {
				clearError(data, form);
				var errorMessageKey = (data.framework === 'fomantic-ui') ? 'errorMessageFomantic' : 'errorMessageTailwind';
				var errorMessageTpl = getUiComponent(data, errorMessageKey, '<div class="component-error-message-tailwind" style="margin:0 0 1rem 0;color:#b91c1c;">#message#</div>');
				var errorMessage = errorMessageTpl.replace('#message#', message);
				var errorDiv = $(errorMessage);

				// Ajustar estilo do formulário para acomodar o reCAPTCHAv2, se necessário
				if (isRecaptchaV2) {
					errorDiv.css('margin-bottom', '1em');
				}

				if (clickedButton && clickedButton.length) {
					clickedButton.before(errorDiv);
				} else {
					form.prepend(errorDiv);
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