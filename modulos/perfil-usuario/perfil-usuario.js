$(document).ready(function(){
	
	if($('#_gestor-form-signup').length > 0){
		$('.radio.checkbox')
			.checkbox();
			
		var formSelector = '#_gestor-form-signup';
		var googleRecaptchaDone = false;
		
		$.formReiniciar({
			formOnSuccessCalback : 'reCaptcha',
			formOnSuccessCalbackFunc : function(){
				if(typeof gestor.googleRecaptchaActive !== typeof undefined && gestor.googleRecaptchaActive !== false){
					var action = 'signup'; // Action 
					var googleSiteKey = gestor.googleRecaptchaSite; // Google Site Key
					
					if(!googleRecaptchaDone){
						grecaptcha.ready(function() {
							grecaptcha.execute(googleSiteKey, {action: action}).then(function(token) {
								$(formSelector).append('<input type="hidden" name="token" value="'+token+'">');
								$(formSelector).append('<input type="hidden" name="action" value="'+action+'">');
								//$(formSelector).unbind('submit').submit();
								
								$.formSubmit({
									id : 'formOnSuccessCalback',
								});
								
								googleRecaptchaDone = true;
							});
						});
					} else {
						$.formSubmit({
							id : 'formOnSuccessCalback',
						});
					}
					
					return false;
				}
			}
		});
		
	}
	
	if($('#_gestor-form-logar').length > 0){
		$('.checkbox')
			.checkbox();
		
		var formSelector = '#_gestor-form-logar';
		
		$('#_gestor-form-logar')
			.form({
				fields : (gestor.interface.regrasValidacao ? gestor.interface.regrasValidacao : {}),
				onSuccess(event, fields){
					if(typeof gestor.googleRecaptchaActive !== typeof undefined && gestor.googleRecaptchaActive !== false){
						var action = 'logar'; // Action 
						var googleSiteKey = gestor.googleRecaptchaSite; // Google Site Key
						
						grecaptcha.ready(function() {
							grecaptcha.execute(googleSiteKey, {action: action}).then(function(token) {
								$(formSelector).append('<input type="hidden" name="token" value="'+token+'">');
								$(formSelector).append('<input type="hidden" name="action" value="'+action+'">');
								$(formSelector).unbind('submit').submit();
							});
						});
						
						return false;
					}
				}
			});
		
		/* function initFingerprintJS(){
			// Initialize an agent at application startup.
			const fpPromise = FingerprintJS.load();

			// Get the visitor identifier when you need it.
			fpPromise
				.then(fp => fp.get())
				.then(result => {
					// This is the visitor identifier:
					const visitorId = result.visitorId;
					$('#_gestor-fingerprint').val(visitorId);
				})
		}
		
		initFingerprintJS(); */
	}
	
	if($('#_gestor-form-forgot-password').length > 0){
		var formSelector = '#_gestor-form-forgot-password';
		
		$('#_gestor-form-forgot-password')
			.form({
				fields : (gestor.interface.regrasValidacao ? gestor.interface.regrasValidacao : {}),
				onSuccess(event, fields){
					if(typeof gestor.googleRecaptchaActive !== typeof undefined && gestor.googleRecaptchaActive !== false){
						var action = 'forgotPassword'; // Action 
						var googleSiteKey = gestor.googleRecaptchaSite; // Google Site Key
						
						grecaptcha.ready(function() {
							grecaptcha.execute(googleSiteKey, {action: action}).then(function(token) {
								$(formSelector).append('<input type="hidden" name="token" value="'+token+'">');
								$(formSelector).append('<input type="hidden" name="action" value="'+action+'">');
								$(formSelector).unbind('submit').submit();
							});
						});
						
						return false;
					}
				}
			});
	}
	
	if($('#_gestor-validar-usuario').length > 0){
		/* function initFingerprintJS(){
			// Initialize an agent at application startup.
			const fpPromise = FingerprintJS.load();

			// Get the visitor identifier when you need it.
			fpPromise
				.then(fp => fp.get())
				.then(result => {
					// This is the visitor identifier:
					const visitorId = result.visitorId;
					$('#_gestor-validar-usuario-fingerprint').val(visitorId);
					$('#_gestor-form-validar-usuario').submit();
				})
		}
		
		$('#gestor-listener').trigger('carregar_abrir');
		initFingerprintJS(); */
	}
	
	if($('#_gestor-restrict-area').length > 0){
		$('.ui.form')
			.form({
				fields : (gestor.interface.regrasValidacao ? gestor.interface.regrasValidacao : {}),
			});
	}

	if($('#_gestor-redefine-password').length > 0){
		$('#_gestor-form-redefine-password')
			.form({
				fields : (gestor.interface.regrasValidacao ? gestor.interface.regrasValidacao : {}),
			});
	}
	
});