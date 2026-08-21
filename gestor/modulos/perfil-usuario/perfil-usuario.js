$(document).ready(function(){

	// req-086: este arquivo nasceu para telas Fomantic e é servido em TODAS as rotas do módulo —
	// inclusive nas migradas para Tailwind puro, onde o Fomantic não é carregado. Ali `$.fn.form` e
	// `$.fn.checkbox` não existem e a chamada lança `TypeError`, abortando o resto do `ready()` e
	// levando junto a lógica de produto que vem depois (alternador de método de login, 2FA, QR Code).
	//
	// Nas telas Tailwind quem valida e envia o formulário é `interface-tailwind.js`, que intercepta o
	// `submit` nativo — então o caminho correto aqui é simplesmente NÃO chamar o plugin e deixar o
	// envio seguir. As duas checagens abaixo separam o que é do Fomantic do que é do produto.
	var temFormFomantic = (typeof $.fn.form === 'function');
	var temCheckboxFomantic = (typeof $.fn.checkbox === 'function');

	if($('#_gestor-form-signup').length > 0){
		if(temCheckboxFomantic){
			$('.radio.checkbox')
				.checkbox();
		}

		var formSelector = '#_gestor-form-signup';
		var googleRecaptchaDone = false;
		var submitBtnClicked = false;

		// `$.formReiniciar` e `$.formSubmit` são do `interface.js` legado, que não é carregado nas
		// telas Tailwind — ali o runtime é o `interface-tailwind.js`, e chamar estes helpers
		// lançaria `TypeError` antes mesmo de o formulário existir na tela.
		if(temFormFomantic && typeof $.formReiniciar === 'function')
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

			if(temFormFomantic){
				$(formSelector).form('submit');
			}
		});
		
	}
	
	var $authForm = $('#_gestor-form-logar').length > 0 ? $('#_gestor-form-logar') : $('#_gestor-form-autenticar');
	if($authForm.length > 0){
		// Sem o plugin, o `<input type="checkbox">` nativo já é interativo por conta própria — o
		// Fomantic só o substituía por um controle estilizado.
		if(temCheckboxFomantic){
			$('.checkbox')
				.checkbox();
		}

		var formSelector2 = '#' + $authForm.attr('id');
		var submitBtnClicked = false;
		var setLoginMethod = function(method){
			var $form = $(formSelector2);
			var $senha = $form.find('input[name="senha"]');
			var $button = $form.find('.login-submit-button');
			var isEmail = method === 'email';

			$form.find('#login_method').val(isEmail ? 'email' : 'password');
			$form.find('.login-method-toggle').removeClass('active');
			$form.find('.login-method-toggle[data-method="' + (isEmail ? 'email' : 'password') + '"]').addClass('active');
			$form.find('.password-login-field').toggle(!isEmail);
			$senha.prop('disabled', isEmail);

			if($button.length > 0){
				$button.text(isEmail ? $button.attr('data-email-label') : $button.attr('data-password-label'));
			}
		};

		$(formSelector2).find('.login-method-toggle').on('click', function(e){
			e.preventDefault();
			setLoginMethod($(this).attr('data-method') || 'password');
		});

		setLoginMethod($(formSelector2).find('#login_method').val() || 'password');

		// A validação e o gancho do reCAPTCHA abaixo são do Fomantic. Em Tailwind, `interface-tailwind.js`
		// valida pelas mesmas `gestor.interface.regrasValidacao` e intercepta o `submit` nativo.
		if(temFormFomantic)
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

			// Com Fomantic, o envio passa pelo `.form('submit')` para validar antes. Sem ele, o
			// botão é `type="submit"` e o envio nativo segue sozinho — `interface-tailwind.js` já
			// está escutando esse `submit`. Forçar algo aqui enviaria o formulário duas vezes.
			if(temFormFomantic){
				$(formSelector2).form('submit');
			}
		});
	}

	if($('#_gestor-form-forgot-password').length > 0){
		var formSelector3 = '#_gestor-form-forgot-password';
		
		var googleRecaptcha = false;
		var submitBtnClicked = false;

		if(temFormFomantic)
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

			// req-086: era `formSelector2` — a variável do bloco de LOGIN, que nesta tela nunca foi
			// atribuída. `$(undefined).form(...)` lançava `TypeError` a cada clique no botão de
			// recuperar senha, mesmo com o Fomantic presente.
			if(temFormFomantic){
				$(formSelector3).form('submit');
			}
		});
	}

	if($('#_gestor-validar-usuario').length > 0){
		
	}
	
	if($('#_gestor-restrict-area').length > 0 && temFormFomantic){
		$('.ui.form')
			.form({
				fields : (gestor.interface.regrasValidacao ? gestor.interface.regrasValidacao : {}),
			});
	}

	if($('#_gestor-redefine-password').length > 0 && temFormFomantic){
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

	// ===== As ações de Segurança (2FA + contas sociais) migraram para o runtime vanilla do painel,
	//       no fim deste arquivo (req-118): a página do perfil roda em Tailwind puro e as mensagens
	//       antigas usavam as classes `positive`/`negative` do Fomantic, que não existem mais lá.

});

/**
 * Painel do Perfil do Usuário — abas, força de senha, 2FA/social e sessões ativas (req-118).
 *
 * JavaScript nativo por decisão de projeto: a página roda sob `layout-administrativo-tailwind`, sem
 * Fomantic. Só age quando `#perfil-usuario-painel` existe, então as demais telas do módulo
 * (login, 2FA, cadastro, recuperação) seguem exatamente como estavam.
 */
(function () {
	'use strict';

	var CHAVE_ABA = 'c2f-perfil-aba';

	var CLASSES_ABA_ATIVA = ['border-emerald-600', 'text-slate-900'];
	var CLASSES_ABA_INATIVA = ['border-transparent', 'text-slate-500'];

	function texto(id, padrao) {
		var dicionario = (window.gestor && gestor.perfilUsuario) ? gestor.perfilUsuario : {};
		return (dicionario && dicionario[id]) ? dicionario[id] : padrao;
	}

	// ===================================================================================
	// Mensagens
	// ===================================================================================

	var CLASSES_MENSAGEM = {
		sucesso: ['border-emerald-200', 'bg-emerald-50', 'text-emerald-800'],
		erro: ['border-red-200', 'bg-red-50', 'text-red-700']
	};

	function mensagem(seletor, tipo, conteudo) {
		var el = document.querySelector(seletor);
		if (!el) return;

		Object.keys(CLASSES_MENSAGEM).forEach(function (chave) {
			el.classList.remove.apply(el.classList, CLASSES_MENSAGEM[chave]);
		});

		el.classList.add.apply(el.classList, CLASSES_MENSAGEM[tipo] || CLASSES_MENSAGEM.erro);
		el.innerHTML = conteudo || '';
		el.classList.remove('hidden');
	}

	// ===================================================================================
	// Abas
	// ===================================================================================

	function abaDaUrl() {
		var busca = window.location.search;

		// A querystring vence o resto: quem chega por "Alterar e-mail" ou "Configurar 2FA" precisa
		// cair na aba do que pediu, ainda que a última aba visitada tenha sido outra.
		if (/[?&]configurar-seguranca=/.test(busca)) return 'seguranca';
		if (/[?&]mudar-(nome|email|usuario|senha)=/.test(busca)) return 'dados';

		var hash = (window.location.hash || '').replace('#', '');
		if (hash === 'dados' || hash === 'seguranca' || hash === 'sessoes') return hash;

		return null;
	}

	function iniciarAbas(painel) {
		var abas = Array.prototype.slice.call(painel.querySelectorAll('[data-perfil-aba]'));
		var paineis = Array.prototype.slice.call(painel.querySelectorAll('[data-perfil-painel]'));

		if (!abas.length) return null;

		function ativar(nome, persistir) {
			var encontrada = abas.some(function (aba) { return aba.getAttribute('data-perfil-aba') === nome; });
			if (!encontrada) nome = abas[0].getAttribute('data-perfil-aba');

			abas.forEach(function (aba) {
				var ativa = aba.getAttribute('data-perfil-aba') === nome;

				aba.classList.remove.apply(aba.classList, ativa ? CLASSES_ABA_INATIVA : CLASSES_ABA_ATIVA);
				aba.classList.add.apply(aba.classList, ativa ? CLASSES_ABA_ATIVA : CLASSES_ABA_INATIVA);
				aba.setAttribute('aria-selected', ativa ? 'true' : 'false');
			});

			paineis.forEach(function (secao) {
				secao.classList.toggle('hidden', secao.getAttribute('data-perfil-painel') !== nome);
			});

			if (persistir) {
				try { window.localStorage.setItem(CHAVE_ABA, nome); } catch (e) { /* sem persistência */ }
			}

			return nome;
		}

		abas.forEach(function (aba) {
			aba.addEventListener('click', function () {
				ativar(aba.getAttribute('data-perfil-aba'), true);
			});
		});

		var inicial = abaDaUrl();

		if (!inicial) {
			try { inicial = window.localStorage.getItem(CHAVE_ABA); } catch (e) { inicial = null; }
		}

		ativar(inicial || painel.getAttribute('data-perfil-aba-padrao') || 'dados', false);

		return ativar;
	}

	// ===================================================================================
	// Medidor de força de senha
	// ===================================================================================

	var NIVEIS = [
		{ rotulo: 'password-strength-weak', padrao: 'Fraca', largura: 'w-1/4', cor: 'bg-red-500', texto: 'text-red-600' },
		{ rotulo: 'password-strength-fair', padrao: 'Razoável', largura: 'w-2/4', cor: 'bg-amber-500', texto: 'text-amber-600' },
		{ rotulo: 'password-strength-good', padrao: 'Boa', largura: 'w-3/4', cor: 'bg-sky-500', texto: 'text-sky-600' },
		{ rotulo: 'password-strength-strong', padrao: 'Forte', largura: 'w-full', cor: 'bg-emerald-500', texto: 'text-emerald-600' }
	];

	/**
	 * Pontua a senha de 0 a 4.
	 *
	 * O comprimento mínimo é 12 porque é o que o backend exige (`min => 12` em
	 * `perfil_usuario_editar`): um medidor que aprovasse 8 caracteres estaria mentindo para o
	 * usuário, que só descobriria no POST.
	 */
	function forcaDaSenha(senha) {
		senha = String(senha || '');
		if (!senha) return 0;

		var pontos = 0;
		if (senha.length >= 12) pontos++;
		if (/[a-z]/.test(senha) && /[A-Z]/.test(senha)) pontos++;
		if (/[0-9]/.test(senha)) pontos++;
		if (/[^A-Za-z0-9]/.test(senha)) pontos++;

		// Senha curta nunca passa de "fraca", por mais variada que seja.
		if (senha.length < 12) pontos = Math.min(pontos, 1);

		return pontos;
	}

	function iniciarMedidor(painel) {
		var campo = painel.querySelector('[data-perfil-senha]');
		var barra = painel.querySelector('[data-perfil-senha-barra]');
		var rotulo = painel.querySelector('[data-perfil-senha-rotulo]');

		if (!campo || !barra || !rotulo) return;

		var todasLarguras = NIVEIS.map(function (n) { return n.largura; }).concat(['w-0']);
		var todasCores = NIVEIS.map(function (n) { return n.cor; }).concat(['bg-slate-300']);
		var todosTextos = NIVEIS.map(function (n) { return n.texto; }).concat(['text-slate-500']);

		function atualizar() {
			var pontos = forcaDaSenha(campo.value);

			barra.classList.remove.apply(barra.classList, todasLarguras);
			barra.classList.remove.apply(barra.classList, todasCores);
			rotulo.classList.remove.apply(rotulo.classList, todosTextos);

			if (pontos === 0) {
				barra.classList.add('w-0', 'bg-slate-300');
				rotulo.classList.add('text-slate-500');
				rotulo.textContent = texto('password-strength-empty', 'Digite uma senha para ver a força.');
				return;
			}

			var nivel = NIVEIS[Math.min(pontos, NIVEIS.length) - 1];

			barra.classList.add(nivel.largura, nivel.cor);
			rotulo.classList.add(nivel.texto);
			rotulo.textContent = texto(nivel.rotulo, nivel.padrao);
		}

		campo.addEventListener('input', atualizar);
		atualizar();
	}

	// ===================================================================================
	// AJAX do painel
	// ===================================================================================

	function carregando(abrir) {
		if (window.gestorInterfaceTailwind) {
			if (abrir) { gestorInterfaceTailwind.carregarAbrir(); } else { gestorInterfaceTailwind.carregarFechar(); }
			return;
		}

		if (window.jQuery) {
			window.jQuery('#gestor-listener').trigger(abrir ? 'carregar_abrir' : 'carregar_fechar');
		}
	}

	/**
	 * POST no endpoint AJAX do próprio módulo.
	 *
	 * O token CSRF não é anexado aqui de propósito: `global.js` envelopa `window.fetch` e o injeta
	 * em toda requisição mutável de mesma origem. Duplicar isso criaria duas fontes de verdade.
	 */
	function enviar(dados, alvoMensagem) {
		var corpo = new URLSearchParams();
		corpo.set('ajax', 'sim');
		Object.keys(dados).forEach(function (chave) { corpo.set(chave, dados[chave]); });

		carregando(true);

		return window.fetch(window.location.href, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: corpo.toString(),
			credentials: 'same-origin'
		}).then(function (resposta) {
			if (resposta.status === 401) {
				var raiz = (window.gestor && gestor.raiz) ? gestor.raiz : '/';
				window.location.href = raiz + 'signin/';
				return null;
			}
			return resposta.json();
		}).then(function (json) {
			carregando(false);

			if (!json) return null;

			if (json.status !== 'success') {
				mensagem(alvoMensagem, 'erro', json.message || '');
				return null;
			}

			return json;
		}).catch(function () {
			carregando(false);
			mensagem(alvoMensagem, 'erro', texto('sessions-revoke-error', 'Erro.'));
			return null;
		});
	}

	function valorDe(id) {
		var el = document.getElementById(id);
		return el ? el.value : '';
	}

	function iniciarSeguranca() {
		if (!document.getElementById('seg-seguranca')) return;

		var alvo = '#seg-msg';

		function aoClicar(seletor, acao) {
			Array.prototype.forEach.call(document.querySelectorAll(seletor), function (botao) {
				botao.addEventListener('click', function () { acao(botao); });
			});
		}

		aoClicar('#btn-2fa-email-enviar', function () {
			enviar({ ajaxOpcao: 'seguranca-2fa-email-enviar' }, alvo).then(function (json) {
				if (json) mensagem(alvo, 'sucesso', json.message || '');
			});
		});

		aoClicar('#btn-2fa-ativar', function () {
			enviar({
				ajaxOpcao: 'seguranca-2fa-ativar',
				metodo: valorDe('seg-2fa-metodo') || 'app',
				codigo: valorDe('seg-2fa-codigo')
			}, alvo).then(function (json) {
				if (!json) return;

				// Os códigos de recuperação existem em claro UMA única vez, nesta resposta (req-119).
				// Recarregar aqui — como o fluxo fazia antes — os destruiria antes de o usuário
				// conseguir anotá-los, e não há endpoint que os recupere.
				if (mostrarRecoveryCodes(json)) {
					mensagem(alvo, 'sucesso', json.message || '');
					return;
				}

				window.location.reload();
			});
		});

		aoClicar('#btn-2fa-desativar', function () {
			enviar({
				ajaxOpcao: 'seguranca-2fa-desativar',
				senha: valorDe('seg-2fa-senha'),
				codigo: valorDe('seg-2fa-codigo')
			}, alvo).then(function (json) { if (json) window.location.reload(); });
		});

		aoClicar('.btn-social-vincular', function (botao) {
			enviar({
				ajaxOpcao: 'seguranca-social-vincular',
				provider: botao.getAttribute('data-provider')
			}, alvo).then(function (json) {
				if (json && json.redirect) window.location.href = json.redirect;
			});
		});

		aoClicar('.btn-social-desvincular', function (botao) {
			enviar({
				ajaxOpcao: 'seguranca-social-desvincular',
				provider: botao.getAttribute('data-provider')
			}, alvo).then(function (json) { if (json) window.location.reload(); });
		});
	}

	// ===================================================================================
	// Sessões ativas
	// ===================================================================================

	function iniciarSessoes() {
		var raiz = document.getElementById('seg-sessoes');
		if (!raiz) return;

		var alvo = '#sessoes-msg';

		Array.prototype.forEach.call(raiz.querySelectorAll('.btn-sessao-revogar'), function (botao) {
			botao.addEventListener('click', function () {
				if (!window.confirm(texto('sessions-revoke-confirm', 'Deseja revogar esta sessão?'))) return;

				var pubID = botao.getAttribute('data-pubid');

				enviar({ ajaxOpcao: 'sessoes-revogar', pubID: pubID }, alvo).then(function (json) {
					if (!json) return;

					// O cartão sai da tela na hora: recarregar a página inteira jogaria o usuário de
					// volta para a primeira aba e perderia o contexto do que ele acabou de fazer.
					var cartao = raiz.querySelector('[data-sessao-pubid="' + pubID + '"]');
					if (cartao && cartao.parentNode) cartao.parentNode.removeChild(cartao);

					if (!raiz.querySelector('.btn-sessao-revogar')) {
						var botaoOutras = document.getElementById('btn-sessoes-revogar-outras');
						if (botaoOutras) botaoOutras.classList.add('hidden');
					}

					mensagem(alvo, 'sucesso', json.message || '');
				});
			});
		});

		var revogarOutras = document.getElementById('btn-sessoes-revogar-outras');

		if (revogarOutras) {
			revogarOutras.addEventListener('click', function () {
				if (!window.confirm(texto('sessions-revoke-others-confirm', 'Deseja encerrar todas as outras sessões?'))) return;

				enviar({ ajaxOpcao: 'sessoes-revogar-outras' }, alvo).then(function (json) {
					if (!json) return;

					Array.prototype.forEach.call(raiz.querySelectorAll('[data-sessao-pubid]'), function (cartao) {
						if (cartao.querySelector('.btn-sessao-revogar')) cartao.parentNode.removeChild(cartao);
					});

					revogarOutras.classList.add('hidden');
					mensagem(alvo, 'sucesso', json.message || '');
				});
			});
		}
	}

	// ===================================================================================
	// Chaves de API pessoais (req-119)
	// ===================================================================================

	function iniciarApiTokens() {
		var raiz = document.getElementById('seg-api-tokens');
		if (!raiz) return;

		var alvo = '#api-tokens-msg';
		var formulario = document.getElementById('api-token-form');
		var caixaToken = document.getElementById('api-token-novo');
		var valorToken = document.getElementById('api-token-valor');

		function aoClicar(id, acao) {
			var el = document.getElementById(id);
			if (el) el.addEventListener('click', acao);
		}

		aoClicar('btn-api-token-novo', function () {
			if (formulario) formulario.classList.remove('hidden');
			var nome = document.getElementById('api-token-nome');
			if (nome) nome.focus();
		});

		aoClicar('btn-api-token-cancelar', function () {
			if (formulario) formulario.classList.add('hidden');
		});

		aoClicar('btn-api-token-criar', function () {
			var nome = valorDe('api-token-nome').trim();

			if (!nome) {
				mensagem(alvo, 'erro', texto('api-tokens-name-required', 'Informe um nome para a chave.'));
				return;
			}

			var escopos = Array.prototype.slice
				.call(raiz.querySelectorAll('.api-token-escopo:checked'))
				.map(function (campo) { return campo.value; });

			var dados = {
				ajaxOpcao: 'api-token-gerar',
				nome: nome,
				expiracao: valorDe('api-token-expiracao') || '0'
			};

			// URLSearchParams não serializa array: cada escopo vai como `escopos[]`, que é o formato
			// que o PHP monta de volta em array.
			var corpo = new URLSearchParams();
			corpo.set('ajax', 'sim');
			Object.keys(dados).forEach(function (chave) { corpo.set(chave, dados[chave]); });
			escopos.forEach(function (escopo) { corpo.append('escopos[]', escopo); });

			carregando(true);

			window.fetch(window.location.href, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: corpo.toString(),
				credentials: 'same-origin'
			}).then(function (resposta) {
				return resposta.status === 401 ? null : resposta.json();
			}).then(function (json) {
				carregando(false);

				if (!json || json.status !== 'success') {
					mensagem(alvo, 'erro', (json && json.message) || texto('api-tokens-create-error', 'Erro.'));
					return;
				}

				// Esta é a ÚNICA vez que o token existe em claro: o banco só tem o hash.
				if (valorToken) valorToken.textContent = json.token;
				if (caixaToken) caixaToken.classList.remove('hidden');
				if (formulario) formulario.classList.add('hidden');

				mensagem(alvo, 'sucesso', json.message || '');
			}).catch(function () {
				carregando(false);
				mensagem(alvo, 'erro', texto('api-tokens-create-error', 'Erro.'));
			});
		});

		aoClicar('btn-api-token-copiar', function () {
			if (!valorToken) return;

			var valor = valorToken.textContent || '';

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(valor).then(function () {
					mensagem(alvo, 'sucesso', texto('api-tokens-copied', 'Chave copiada.'));
				});
				return;
			}

			// Sem Clipboard API (contexto não seguro), selecionar o texto ainda permite Ctrl+C.
			var faixa = document.createRange();
			faixa.selectNodeContents(valorToken);
			var selecao = window.getSelection();
			selecao.removeAllRanges();
			selecao.addRange(faixa);
		});

		Array.prototype.forEach.call(raiz.querySelectorAll('.btn-api-token-revogar'), function (botao) {
			botao.addEventListener('click', function () {
				if (!window.confirm(texto('api-tokens-revoke-confirm', 'Deseja revogar esta chave?'))) return;

				var id = botao.getAttribute('data-id');

				enviar({ ajaxOpcao: 'api-token-revogar', id: id }, alvo).then(function (json) {
					if (!json) return;

					// A linha permanece na tabela por auditoria; só o botão e o estado mudam.
					var linha = raiz.querySelector('[data-token-id="' + id + '"]');

					if (linha) {
						var etiqueta = linha.querySelector('span');
						if (etiqueta) {
							etiqueta.className = etiqueta.className
								.replace('bg-emerald-50', 'bg-slate-100')
								.replace('text-emerald-700', 'text-slate-600');
							etiqueta.textContent = texto('api-tokens-status-revogado', 'Revogada');
						}
						botao.remove();
					}

					mensagem(alvo, 'sucesso', json.message || '');
				});
			});
		});
	}

	// ===================================================================================
	// Códigos de recuperação exibidos após ativar o 2FA (req-119)
	// ===================================================================================

	function mostrarRecoveryCodes(json) {
		if (!json || !json.recovery_codes || !json.recovery_codes.length) return false;

		var destino = document.getElementById('seg-seguranca');
		if (!destino) return false;

		var caixa = document.createElement('div');
		caixa.id = 'seg-recovery-codes';
		caixa.className = 'rounded-xl border border-amber-300 bg-amber-50 p-4';

		var titulo = document.createElement('h4');
		titulo.className = 'mb-2 text-sm font-semibold text-amber-900';
		titulo.textContent = json.recovery_title || texto('recovery-codes-title', 'Códigos de recuperação');

		var ajuda = document.createElement('p');
		ajuda.className = 'mb-3 text-sm text-amber-900';
		ajuda.textContent = json.recovery_help || texto('recovery-codes-help', '');

		var lista = document.createElement('ul');
		lista.className = 'grid grid-cols-2 gap-2 sm:grid-cols-5';

		json.recovery_codes.forEach(function (codigo) {
			var item = document.createElement('li');
			item.className = 'rounded-md bg-white px-2 py-1 text-center font-mono text-sm tracking-wider text-slate-800';
			// textContent (nunca innerHTML): o valor vem do servidor, mas não há razão para abrir
			// caminho de injeção num bloco que exibe segredo.
			item.textContent = codigo;
			lista.appendChild(item);
		});

		caixa.appendChild(titulo);
		caixa.appendChild(ajuda);
		caixa.appendChild(lista);

		destino.insertBefore(caixa, destino.firstChild);

		return true;
	}

	function iniciar() {
		var painel = document.getElementById('perfil-usuario-painel');
		if (!painel) return;

		iniciarAbas(painel);
		iniciarMedidor(painel);
		iniciarSeguranca();
		iniciarSessoes();
		iniciarApiTokens();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', iniciar);
	} else {
		iniciar();
	}

	window.perfilUsuarioPainel = {
		iniciar: iniciar,
		forcaDaSenha: forcaDaSenha,
		abaDaUrl: abaDaUrl,
		mostrarRecoveryCodes: mostrarRecoveryCodes
	};
})();
