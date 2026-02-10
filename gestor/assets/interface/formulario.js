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
						const blockWrapperHtml = $(formDefinition.blockWrapper);
						form.html(blockWrapperHtml);
					} else {
						// Caso não haja bloqueio, inicializa o controlador normalmente
						initFormController(form, formDefinition);
					}
				}
			});

			function initFormController(form, data) {
				data.fields.forEach(function (field) {
					var input = form.find('[name="' + field.name + '"]');
					if (input.length) {
						input.on('blur input', function () {
							validateField(input, field, data.prompts, data.framework);
						});
					}
				});
				// Validação geral no submit
				form.on('submit', function (e) {
					e.preventDefault();
					if (validateAllFields(form, data.fields, data.prompts, data.framework)) {
						submitForm(form, data);
					}
				});
			}

			function validateAllFields(form, fields, prompts, framework) {
				var allValid = true;
				fields.forEach(function (field) {
					var input = form.find('[name="' + field.name + '"]');
					if (input.length) {
						var isFieldValid = validateField(input, field, prompts, framework);
						if (!isFieldValid) {
							allValid = false;
						}
					}
				});
				return allValid;
			}

			function validateField(input, field, prompts, framework) {
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

				updateFieldUI(input, isValid, errorMsg, framework);
				return isValid;
			}

			function updateFieldUI(input, isValid, errorMsg, framework) {
				var fieldContainer = input.closest('.field') || input.parent(); // Assume estrutura com .field (Fomantic) ou div pai
				fieldContainer.find('.error-msg').remove(); // Remove erro anterior

				if (!isValid) {
					var errorElement = $('<div class="error-msg">').text(errorMsg);
					// Ajuste classes conforme framework
					if (framework === 'fomantic') {
						errorElement.addClass('ui red pointing label'); // Fomantic: label vermelha
						input.addClass('error').removeClass('success');
					} else if (framework === 'tailwind') {
						errorElement.addClass('text-red-500 text-sm mt-1'); // Tailwind: texto vermelho pequeno
						input.addClass('border-red-500').removeClass('border-green-500');
					}
					fieldContainer.append(errorElement);
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
				// Para submissão normal (POST), simplesmente submeta o formulário
				// Se houver reCAPTCHA, integre aqui antes de submeter
				if ('googleRecaptchaActive' in formDefinition && formDefinition.googleRecaptchaActive) {
					var action = ('googleRecaptchaAction' in formDefinition && formDefinition.googleRecaptchaAction) ? formDefinition.googleRecaptchaAction : 'submit'; // Action 
					var googleSiteKey = formDefinition.googleRecaptchaSite; // Google Site Key

					grecaptcha.ready(function () {
						grecaptcha.execute(googleSiteKey, { action: action }).then(function (token) {
							form.append('<input type="hidden" name="token" value="' + token + '">');
							form.append('<input type="hidden" name="action" value="' + action + '">');
							form[0].submit(); // Submete o formulário após reCAPTCHA
						});
					});
				} else {
					form[0].submit(); // Submete diretamente se não houver reCAPTCHA
				}
			}
		}
	}

	start();
});