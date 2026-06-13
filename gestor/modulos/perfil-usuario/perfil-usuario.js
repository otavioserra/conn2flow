$(document).ready(function(){
	
	if($('#_gestor-form-signup').length > 0){
		$('.radio.checkbox')
			.checkbox();
			
		var formSelector = '#_gestor-form-signup';
		var googleRecaptchaDone = false;
		var submitBtnClicked = false;
		
		$.formReiniciar({
			formOnSuccessCalback : 'reCaptcha',
			formOnSuccessCalbackFunc : function(){
				if('googleRecaptchaActive' in gestor){
					var action = 'signup'; // Action 
					var googleSiteKey = gestor.googleRecaptchaSite; // Google Site Key
					
					if(submitBtnClicked){
						if(!googleRecaptchaDone){
							grecaptcha.ready(function() {
								grecaptcha.execute(googleSiteKey, {action: action}).then(function(token) {
									$(formSelector).append('<input type="hidden" name="token" value="'+token+'">');
									$(formSelector).append('<input type="hidden" name="action" value="'+action+'">');
									
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
					}
				}
				
				if(!submitBtnClicked){
					return false;
				} else {
					$.formSubmit({
						id : 'formOnSuccessCalback',
					});
				}
			}
		});
		
		$(formSelector).find('button').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			if($(this).hasClass('disabled')){
				return false;
			}
			
			submitBtnClicked = true;
			
			$(formSelector).form('submit');
		});
		
	}
	
	if($('#_gestor-form-logar').length > 0){
		$('.checkbox')
			.checkbox();
		
		var formSelector2 = '#_gestor-form-logar';
		var submitBtnClicked = false;
		
		$(formSelector2)
			.form({
				fields : (gestor.interface.regrasValidacao ? gestor.interface.regrasValidacao : {}),
				onSuccess(event, fields){
					if('googleRecaptchaActive' in gestor){
						var action = 'logar'; // Action 
						var googleSiteKey = gestor.googleRecaptchaSite; // Google Site Key
						
						if(submitBtnClicked){
							grecaptcha.ready(function() {
								grecaptcha.execute(googleSiteKey, {action: action}).then(function(token) {
									$(formSelector2).append('<input type="hidden" name="token" value="'+token+'">');
									$(formSelector2).append('<input type="hidden" name="action" value="'+action+'">');
									
									$(formSelector2).unbind('submit').submit();
								});
							});
							
							return false;
						}
					}
					
					if(!submitBtnClicked){
						return false;
					}
				}
			});
			
		$(formSelector2).find('button').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			if($(this).hasClass('disabled')){
				return false;
			}
			
			submitBtnClicked = true;
			
			$(formSelector2).form('submit');
		});
	}
	
	if($('#_gestor-form-forgot-password').length > 0){
		var formSelector3 = '#_gestor-form-forgot-password';
		
		var googleRecaptcha = false;
		var submitBtnClicked = false;
		
		$(formSelector3)
			.form({
				fields : (gestor.interface.regrasValidacao ? gestor.interface.regrasValidacao : {}),
				onSuccess(event, fields){
					if('googleRecaptchaActive' in gestor){
						var action = 'forgotPassword'; // Action 
						var googleSiteKey = gestor.googleRecaptchaSite; // Google Site Key
						
						grecaptcha.ready(function() {
							if(submitBtnClicked){
								grecaptcha.execute(googleSiteKey, {action: action}).then(function(token) {
									$(formSelector3).append('<input type="hidden" name="token" value="'+token+'">');
									$(formSelector3).append('<input type="hidden" name="action" value="'+action+'">');
									
									$(formSelector3).unbind('submit').submit();
								});
								
								return false;
							}
						});
					}
					
					if(!submitBtnClicked){
						return false;
					}
				}
			});
			
		$(formSelector3).find('button').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			if($(this).hasClass('disabled')){
				return false;
			}
			
			submitBtnClicked = true;
			
			$(formSelector2).form('submit');
		});
	}
	
	if($('#_gestor-validar-usuario').length > 0){
		
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

	// ===== QR Code e alternância de método 2FA (Segurança do perfil e tela de login 2FA) — req-030
	var $qr2fa = $('#seg-2fa-qr');
	if($qr2fa.length > 0 && typeof QRCode !== 'undefined'){
		new QRCode($qr2fa.get(0), { text: $qr2fa.attr('data-otpauth'), width: 180, height: 180 });
	}

	$('#seg-2fa-metodo').on('change', function(){
		if($(this).val() === 'email'){
			$('#seg-2fa-app-bloco').hide();
			$('#seg-2fa-email-bloco').show();
		} else {
			$('#seg-2fa-app-bloco').show();
			$('#seg-2fa-email-bloco').hide();
		}
	}).trigger('change');

	// ===== Rota de Segurança (2FA + contas sociais) — req-030
	if($('#seg-seguranca').length > 0){
		var segMsg = function(type, msg){
			var $m = $('#seg-msg');
			$m.removeClass('positive negative').addClass(type === 'success' ? 'positive' : 'negative').html(msg || '').show();
		};

		var segAjax = function(data, onOk){
			$.ajax({
				type: 'POST',
				url: window.location.href,
				data: data,
				dataType: 'json',
				beforeSend: function(){ $('#gestor-listener').trigger('carregar_abrir'); },
				success: function(dados){
					$('#gestor-listener').trigger('carregar_fechar');
					if(dados.status === 'success'){ onOk(dados); }
					else { segMsg('error', dados.message); }
				},
				error: function(txt){
					$('#gestor-listener').trigger('carregar_fechar');
					if(txt.status === 401){ window.open(gestor.raiz + 'signin/', '_self'); return; }
					segMsg('error', 'Error.');
				}
			});
		};

		$('#btn-2fa-email-enviar').on('click', function(){
			segAjax({ ajax: 'sim', ajaxOpcao: 'seguranca-2fa-email-enviar' }, function(d){ segMsg('success', d.message); });
		});

		$('#btn-2fa-ativar').on('click', function(){
			segAjax({ ajax: 'sim', ajaxOpcao: 'seguranca-2fa-ativar', metodo: ($('#seg-2fa-metodo').val() || 'app'), codigo: $('#seg-2fa-codigo').val() }, function(){ window.location.reload(); });
		});

		$('#btn-2fa-desativar').on('click', function(){
			segAjax({ ajax: 'sim', ajaxOpcao: 'seguranca-2fa-desativar', senha: $('#seg-2fa-senha').val(), codigo: $('#seg-2fa-codigo').val() }, function(){ window.location.reload(); });
		});

		$('.btn-social-vincular').on('click', function(){
			segAjax({ ajax: 'sim', ajaxOpcao: 'seguranca-social-vincular', provider: $(this).attr('data-provider') }, function(d){ if(d.redirect){ window.open(d.redirect, '_self'); } });
		});

		$('.btn-social-desvincular').on('click', function(){
			segAjax({ ajax: 'sim', ajaxOpcao: 'seguranca-social-desvincular', provider: $(this).attr('data-provider') }, function(){ window.location.reload(); });
		});
	}

});