(function () {
	'use strict';

	// Nome do campo oculto de CSRF. É o contrato lido pelo backend em
	// `seguranca_csrf_token_requisicao()` (gestor/bibliotecas/seguranca.php).
	var CSRF_CAMPO = '_csrf_token';
	var CSRF_HEADER = 'X-CSRF-Token';

	// req-109: o token é procurado em três lugares, nesta ordem, porque o editor visual roda dentro
	// de um iframe `srcdoc` — que herda a origem, mas não o <head> da página hospedeira, e portanto
	// não tem a <meta name="csrf-token">. Sem o fallback pelo `gestor` do pai, toda requisição de
	// modificação disparada de dentro do editor volta com 403 "Token CSRF inválido ou ausente.".
	function csrfToken() {
		try {
			var meta = document.querySelector('meta[name="csrf-token"]');
			if (meta && meta.getAttribute('content')) return meta.getAttribute('content');
		} catch (error) {
			// document indisponível (contexto sem DOM); segue para os fallbacks.
		}

		try {
			if (window.gestor && window.gestor.csrfToken) return window.gestor.csrfToken;
		} catch (error) {
			// gestor ausente nesta janela.
		}

		try {
			if (window.parent && window.parent !== window && window.parent.gestor && window.parent.gestor.csrfToken) {
				return window.parent.gestor.csrfToken;
			}
		} catch (error) {
			// Acesso ao pai bloqueado (iframe cross-origin) — sem token disponível.
		}

		return '';
	}

	// req-109: anexa (ou atualiza) o campo oculto de CSRF num formulário.
	// Exposto porque formulários submetidos por CÓDIGO — `form.submit()` nativo ou
	// `$(form).submit()` do jQuery — não disparam o evento `submit`, e portanto não passam pelo
	// listener abaixo. É o caso do salvamento do Editor Visual (`$.formSubmitNormal`).
	function aplicarCsrfNoFormulario(form) {
		if (!form || form.nodeType !== 1) return false;
		if (String(form.method || 'GET').toUpperCase() === 'GET') return false;

		var token = csrfToken();
		if (!token) return false;
		if (!mesmaOrigem(form.getAttribute('action') || window.location.href)) return false;

		var input = form.querySelector('input[name="' + CSRF_CAMPO + '"]');
		if (!input) {
			input = document.createElement('input');
			input.type = 'hidden';
			input.name = CSRF_CAMPO;
			form.appendChild(input);
		}
		input.value = token;
		return true;
	}

	function metodoMutavel(method) {
		return ['POST', 'PUT', 'PATCH', 'DELETE'].indexOf(String(method || 'GET').toUpperCase()) !== -1;
	}

	function mesmaOrigem(input) {
		try {
			var alvo = typeof input === 'string' ? input : input.url;
			return new URL(alvo, window.location.href).origin === window.location.origin;
		} catch (error) {
			return false;
		}
	}

	var redirecionandoParaLogin = false;

	function redirecionarParaLogin(destino) {
		if (redirecionandoParaLogin || !destino) return;
		try {
			var url = new URL(destino, window.location.href);
			if (url.origin !== window.location.origin || url.href === window.location.href) return;
			redirecionandoParaLogin = true;
			window.location.assign(url.href);
		} catch (error) {
			// Ignora destinos invalidos recebidos de respostas externas ou malformadas.
		}
	}

	function tratarFalhaAutenticacaoXhr(xhr) {
		if (!xhr || xhr.status !== 401) return;
		var destino = xhr.getResponseHeader('X-Gestor-Auth-Redirect');
		var resposta = xhr.responseJSON;
		if (!destino && resposta && resposta.code === 'AUTH_REQUIRED') {
			destino = String((window.gestor && gestor.raiz) || '/') + String(resposta.redirect || 'signin/');
		}
		if (destino) redirecionarParaLogin(destino);
	}

	// req-175: renovação silenciosa de CSRF (silent refresh) e repetição transparente (retry).
	//
	// Uma aba parada perde o token quando a sessão vence: o cookie de sessão expira em
	// `SESSION_LIFETIME` e a linha no banco é varrida. A próxima ação mutável voltava 403 e o
	// usuário via um erro técnico. Agora o 403 marcado com `CSRF_INVALID_OR_EXPIRED` busca um token
	// novo em `_gestor-csrf-token/` e repete a requisição uma única vez. Um 403 SEM essa marca
	// (ACL, perfil, site restrito) segue direto para quem chamou: nunca entra em retry.
	var CSRF_ERRO_CODIGO = 'CSRF_INVALID_OR_EXPIRED';
	var CSRF_ERRO_HEADER = 'X-Gestor-Csrf-Error';
	var CSRF_ROTA = '_gestor-csrf-token/';
	var CSRF_INTERVALO_MINIMO = 30000;

	// Capturado antes do envelope abaixo: a renovação não pode passar pelo próprio interceptor.
	var fetchNativo = window.fetch ? window.fetch.bind(window) : null;
	var renovacaoCsrf = null;
	var filaCsrf = [];
	var ultimaRenovacaoCsrf = 0;

	function gestorRaiz() {
		try {
			if (window.gestor && window.gestor.raiz) return String(window.gestor.raiz);
		} catch (error) {
			// gestor ausente nesta janela.
		}

		try {
			if (window.parent && window.parent !== window && window.parent.gestor && window.parent.gestor.raiz) {
				return String(window.parent.gestor.raiz);
			}
		} catch (error) {
			// Acesso ao pai bloqueado.
		}

		return '/';
	}

	// O `retorno` deixa o backend gravar `redirecionar-local`, para o login devolver o usuário à
	// mesma tela quando a sessão tiver expirado de vez.
	function urlRenovacaoCsrf() {
		var raiz = gestorRaiz();
		if (raiz.charAt(raiz.length - 1) !== '/') raiz += '/';

		var retorno = '';
		try {
			var caminho = String(window.location.pathname || '');
			var base = new URL(raiz, window.location.href).pathname;
			if (caminho.indexOf(base) === 0) retorno = caminho.slice(base.length);
		} catch (error) {
			// Sem caminho confiável (ex.: iframe srcdoc): o login usa o destino padrão.
		}

		return raiz + CSRF_ROTA + (retorno ? '?retorno=' + encodeURIComponent(retorno) : '');
	}

	function atualizarTokenNoDocumento(doc, token) {
		if (!doc || typeof doc.querySelector !== 'function') return;

		var meta = doc.querySelector('meta[name="csrf-token"]');
		if (meta) meta.setAttribute('content', token);

		// Campos ocultos já anexados: um `$(form).serialize()` posterior levaria o token vencido.
		if (typeof doc.querySelectorAll === 'function') {
			var campos = doc.querySelectorAll('input[name="' + CSRF_CAMPO + '"]');
			for (var i = 0; i < campos.length; i++) campos[i].value = token;
		}
	}

	// A <meta> vence `gestor.csrfToken` em `csrfToken()`, então as duas precisam mudar juntas — na
	// janela atual e, dentro do iframe do editor, também na página hospedeira.
	function aplicarNovoTokenCsrf(token) {
		if (!token) return;

		try {
			atualizarTokenNoDocumento(document, token);
		} catch (error) {
			// Sem DOM: segue para as variáveis.
		}

		try {
			if (window.gestor) window.gestor.csrfToken = token;
		} catch (error) {
			// gestor ausente nesta janela.
		}

		try {
			if (window.parent && window.parent !== window) {
				if (window.parent.gestor) window.parent.gestor.csrfToken = token;
				atualizarTokenNoDocumento(window.parent.document, token);
			}
		} catch (error) {
			// Pai de outra origem: nada a propagar.
		}
	}

	function requisitarTokenCsrf() {
		var url = urlRenovacaoCsrf();

		if (fetchNativo) {
			return fetchNativo(url, {
				method: 'GET',
				credentials: 'same-origin',
				cache: 'no-store',
				headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
			}).then(function (resposta) {
				return resposta.json().catch(function () {
					return null;
				}).then(function (corpo) {
					var destino = '';
					try {
						destino = resposta.headers.get('X-Gestor-Auth-Redirect') || '';
					} catch (error) {
						// Cabeçalho inacessível.
					}
					return { status: resposta.status, corpo: corpo, destino: destino };
				});
			});
		}

		return new Promise(function (resolve, reject) {
			var xhr = new XMLHttpRequest();
			xhr.open('GET', url, true);
			xhr.setRequestHeader('Accept', 'application/json');
			xhr.onload = function () {
				var corpo = null;
				try {
					corpo = JSON.parse(xhr.responseText);
				} catch (error) {
					// Corpo não-JSON: tratado como falha abaixo.
				}
				resolve({ status: xhr.status, corpo: corpo, destino: xhr.getResponseHeader('X-Gestor-Auth-Redirect') || '' });
			};
			xhr.onerror = function () {
				reject(new Error('csrf-refresh-network'));
			};
			xhr.send();
		});
	}

	// Sessão de login encerrada de vez: leva ao login (o backend já guardou a tela de retorno). De
	// dentro de um iframe, quem navega é a janela de cima — o iframe srcdoc não tem URL própria.
	function recuperarSessaoExpirada(destino) {
		try {
			if (window.top && window.top !== window && window.top.gestorCsrf && window.top.gestorCsrf.recuperarSessao) {
				window.top.gestorCsrf.recuperarSessao(destino);
				return;
			}
		} catch (error) {
			// Topo de outra origem: navega esta janela.
		}

		redirecionarParaLogin(destino || gestorRaiz() + 'signin/');
	}

	// Uma única renovação por vez. Quem chega enquanto ela corre entra na fila e recebe o MESMO
	// token — N requisições falhando juntas disparam uma só chamada a `_gestor-csrf-token/`.
	function renovarCsrf(opcoes) {
		var redirecionar = !(opcoes && opcoes.redirecionar === false);
		var promessa = new Promise(function (resolve, reject) {
			filaCsrf.push({ resolve: resolve, reject: reject, redirecionar: redirecionar });
		});

		if (!renovacaoCsrf) {
			ultimaRenovacaoCsrf = Date.now();
			renovacaoCsrf = requisitarTokenCsrf().then(function (resultado) {
				var corpo = resultado.corpo || {};
				if (resultado.status === 200 && corpo.status === 'success' && corpo.token) {
					aplicarNovoTokenCsrf(String(corpo.token));
					return String(corpo.token);
				}

				var erro = new Error('csrf-refresh-failed');
				erro.sessaoExpirada = resultado.status === 401 || corpo.code === 'AUTH_EXPIRED';
				erro.destino = resultado.destino || (erro.sessaoExpirada ? gestorRaiz() + String(corpo.redirect || 'signin/') : '');
				throw erro;
			}).then(function (token) {
				var fila = filaCsrf;
				filaCsrf = [];
				renovacaoCsrf = null;
				fila.forEach(function (item) {
					item.resolve(token);
				});
			}, function (erro) {
				var fila = filaCsrf;
				filaCsrf = [];
				renovacaoCsrf = null;
				var redirecionarAgora = erro && erro.sessaoExpirada && fila.some(function (item) {
					return item.redirecionar;
				});
				if (redirecionarAgora) recuperarSessaoExpirada(erro.destino);
				fila.forEach(function (item) {
					item.reject(erro);
				});
			});
		}

		return promessa;
	}

	// Uma requisição que saiu com o token antigo e falhou DEPOIS de uma renovação já concluída não
	// precisa de outra: basta repetir com o token atual.
	function tokenParaNovaTentativa(tokenUsado) {
		if (renovacaoCsrf) return renovarCsrf();

		var atual = csrfToken();
		if (atual && tokenUsado && atual !== tokenUsado) return Promise.resolve(atual);

		return renovarCsrf();
	}

	function atualizarTokenNoCorpo(corpo, token) {
		try {
			if (typeof FormData !== 'undefined' && corpo instanceof FormData) {
				if (corpo.has(CSRF_CAMPO)) corpo.set(CSRF_CAMPO, token);
				return corpo;
			}
			if (typeof URLSearchParams !== 'undefined' && corpo instanceof URLSearchParams) {
				if (corpo.has(CSRF_CAMPO)) corpo.set(CSRF_CAMPO, token);
				return corpo;
			}
			if (typeof corpo === 'string' && corpo.indexOf(CSRF_CAMPO + '=') !== -1) {
				return corpo.replace(new RegExp('(^|&)' + CSRF_CAMPO + '=[^&]*'), '$1' + CSRF_CAMPO + '=' + encodeURIComponent(token));
			}
		} catch (error) {
			// Corpo desconhecido: vai como está; o cabeçalho já leva o token novo.
		}
		return corpo;
	}

	function corpoIndicaCsrf(corpo) {
		return !!corpo && typeof corpo === 'object' && corpo.code === CSRF_ERRO_CODIGO;
	}

	function textoIndicaCsrf(texto) {
		if (typeof texto !== 'string' || texto.indexOf(CSRF_ERRO_CODIGO) === -1) return false;
		try {
			return corpoIndicaCsrf(JSON.parse(texto));
		} catch (error) {
			return false;
		}
	}

	function xhrFalhouPorCsrf(xhr) {
		if (!xhr || xhr.status !== 403) return false;

		try {
			if (xhr.getResponseHeader(CSRF_ERRO_HEADER) === CSRF_ERRO_CODIGO) return true;
		} catch (error) {
			// Cabeçalho inacessível: tenta o corpo.
		}

		try {
			if (xhr.responseType === 'json') return corpoIndicaCsrf(xhr.response);
			if (!xhr.responseType || xhr.responseType === 'text') return textoIndicaCsrf(xhr.responseText);
		} catch (error) {
			// Corpo ilegível.
		}

		return false;
	}

	function respostaFalhouPorCsrf(resposta) {
		if (!resposta || resposta.status !== 403) return Promise.resolve(false);

		try {
			if (resposta.headers && resposta.headers.get(CSRF_ERRO_HEADER) === CSRF_ERRO_CODIGO) return Promise.resolve(true);
		} catch (error) {
			// Cabeçalho inacessível: tenta o corpo.
		}

		if (typeof resposta.clone !== 'function') return Promise.resolve(false);

		return resposta.clone().text().then(textoIndicaCsrf, function () {
			return false;
		});
	}

	// Todos os $.ajax do painel recebem o mesmo cabeçalho, inclusive módulos legados.
	if (window.jQuery) {
		window.jQuery.ajaxPrefilter(function (options, originalOptions, xhr) {
			var token = csrfToken();
			if (token && !options.crossDomain && metodoMutavel(options.type)) xhr.setRequestHeader(CSRF_HEADER, token);
		});
		window.jQuery(document).ajaxError(function (event, xhr) {
			tratarFalhaAutenticacaoXhr(xhr);
		});

		// req-109: `$(form).submit()` percorre a propagação SIMULADA do jQuery, que não aciona
		// listeners nativos registrados com addEventListener. Um handler delegado no document é o
		// único ponto que enxerga esses envios — usado pelo salvamento do Editor Visual.
		window.jQuery(document).on('submit', 'form', function () {
			aplicarCsrfNoFormulario(this);
		});
	}

	// req-109: rede de segurança final. `HTMLFormElement.prototype.submit` é o que o jQuery chama
	// como ação padrão do trigger e o que módulos legados chamam direto — e ele NÃO dispara evento
	// `submit` algum. Sem este envelope, o campo oculto nunca seria anexado nesses caminhos.
	if (typeof HTMLFormElement !== 'undefined' && HTMLFormElement.prototype && !HTMLFormElement.prototype.__c2fCsrf) {
		var submitOriginal = HTMLFormElement.prototype.submit;
		HTMLFormElement.prototype.submit = function () {
			try {
				aplicarCsrfNoFormulario(this);
			} catch (error) {
				// Nunca impedir o envio por causa do token: o backend decide se aceita.
			}
			return submitOriginal.apply(this, arguments);
		};
		HTMLFormElement.prototype.__c2fCsrf = true;
	}

	// Cobre os fluxos modernos que usam fetch diretamente.
	if (window.fetch) {
		var fetchOriginal = window.fetch.bind(window);
		var tratarRespostaFetch = function (response) {
			if (response.status === 401) {
				var destino = response.headers.get('X-Gestor-Auth-Redirect');
				if (destino) redirecionarParaLogin(destino);
			}
			return response;
		};

		window.fetch = function (input, init) {
			init = init || {};
			var token = csrfToken();
			var protegida = mesmaOrigem(input) && metodoMutavel(init.method);
			if (token && protegida) {
				var headers = new Headers(init.headers || {});
				headers.set(CSRF_HEADER, token);
				init.headers = headers;
			}

			// req-175: um `Request` só pode ser consumido uma vez; a cópia para o retry sai antes do
			// envio. Corpo em stream não é repetível e fica fora do retry.
			var reenvio = null;
			if (protegida && !(typeof ReadableStream !== 'undefined' && init.body instanceof ReadableStream)) {
				try {
					reenvio = (typeof Request !== 'undefined' && input instanceof Request) ? input.clone() : input;
				} catch (error) {
					reenvio = null;
				}
			}

			return fetchOriginal(input, init).then(function (response) {
				if (reenvio === null || response.status !== 403) return tratarRespostaFetch(response);

				return respostaFalhouPorCsrf(response).then(function (falhaCsrf) {
					if (!falhaCsrf) return response;

					return tokenParaNovaTentativa(token).then(function (novoToken) {
						var novoInit = Object.assign({}, init);
						var novosHeaders = new Headers(init.headers || {});
						novosHeaders.set(CSRF_HEADER, novoToken);
						novoInit.headers = novosHeaders;
						novoInit.body = atualizarTokenNoCorpo(init.body, novoToken);
						// Direto no fetch nativo: a repetição acontece uma única vez, sem laço.
						return fetchOriginal(reenvio, novoInit).then(tratarRespostaFetch);
					}, function () {
						// Renovação impossível: quem chamou recebe o 403 original, como antes.
						return response;
					});
				});
			});
		};
	}

	// req-163: `XMLHttpRequest` cru. `fetch` não expõe `upload.onprogress`, então uploads com barra
	// de progresso recorrem ao XHR — e voltavam 403 com o usuário logado, porque nem o prefilter do
	// jQuery nem o envelope do fetch os alcançam. O `$.ajax` também passa por aqui (o jqXHR delega
	// ao XHR nativo); o registro de cabeçalhos manuais evita duplicar o token que o prefilter já pôs.
	if (typeof XMLHttpRequest !== 'undefined' && XMLHttpRequest.prototype && !XMLHttpRequest.prototype.__c2fCsrf) {
		var xhrOpenOriginal = XMLHttpRequest.prototype.open;
		var xhrSetRequestHeaderOriginal = XMLHttpRequest.prototype.setRequestHeader;
		var xhrSendOriginal = XMLHttpRequest.prototype.send;

		XMLHttpRequest.prototype.open = function (method, url) {
			this.__c2fMetodo = method;
			this.__c2fUrl = url;
			this.__c2fCabecalhos = {};
			// req-175: o necessário para reabrir o MESMO objeto no retry.
			this.__c2fValores = [];
			this.__c2fAbertura = Array.prototype.slice.call(arguments);
			// Objeto reaproveitado pelo chamador para outra requisição volta a ter direito a um retry.
			if (!this.__c2fReabrindo) this.__c2fCsrfRepetido = false;
			this.__c2fReabrindo = false;
			return xhrOpenOriginal.apply(this, arguments);
		};

		XMLHttpRequest.prototype.setRequestHeader = function (nome, valor) {
			if (this.__c2fCabecalhos && nome) this.__c2fCabecalhos[String(nome).toLowerCase()] = true;
			if (this.__c2fValores && nome) this.__c2fValores.push([String(nome), valor]);
			return xhrSetRequestHeaderOriginal.apply(this, arguments);
		};

		var xhrDeveRepetirPorCsrf = function (xhr) {
			if (xhr.__c2fCsrfRepetido || !xhr.__c2fAbertura) return false;
			// XHR síncrono não pode esperar a renovação.
			if (xhr.__c2fAbertura.length > 2 && xhr.__c2fAbertura[2] === false) return false;
			if (!metodoMutavel(xhr.__c2fMetodo) || !mesmaOrigem(String(xhr.__c2fUrl || ''))) return false;
			return xhrFalhouPorCsrf(xhr);
		};

		// Entrega ao chamador a resposta 403 original que o interceptor segurou.
		var xhrLiberarFalha = function (xhr) {
			xhr.__c2fCsrfSegurando = false;
			['readystatechange', 'load', 'loadend'].forEach(function (tipo) {
				try {
					xhr.dispatchEvent(new Event(tipo));
				} catch (error) {
					// Sem dispatchEvent: nada mais a entregar.
				}
			});
		};

		// Sempre numa tarefa nova (setTimeout): uma microtask poderia reabrir o objeto entre o
		// `readystatechange` e o `load` da resposta original, ainda em despacho.
		var xhrRepetirComNovoToken = function (xhr) {
			tokenParaNovaTentativa(xhr.__c2fTokenUsado).then(function (novoToken) {
				setTimeout(function () {
					var valores = xhr.__c2fValores || [];
					xhr.__c2fCsrfSegurando = false;
					xhr.__c2fReabrindo = true;
					xhr.open.apply(xhr, xhr.__c2fAbertura);
					valores.forEach(function (par) {
						if (par[0].toLowerCase() !== CSRF_HEADER.toLowerCase()) xhr.setRequestHeader(par[0], par[1]);
					});
					xhr.setRequestHeader(CSRF_HEADER, novoToken);
					xhr.send(atualizarTokenNoCorpo(xhr.__c2fCorpo, novoToken));
				}, 0);
			}, function () {
				setTimeout(function () {
					xhrLiberarFalha(xhr);
				}, 0);
			});
		};

		XMLHttpRequest.prototype.send = function () {
			var xhr = this;
			try {
				xhr.__c2fCorpo = arguments[0];
				var jaInformado = xhr.__c2fCabecalhos && xhr.__c2fCabecalhos[CSRF_HEADER.toLowerCase()];
				var token = csrfToken();
				if (token && !jaInformado && metodoMutavel(xhr.__c2fMetodo) && mesmaOrigem(String(xhr.__c2fUrl || ''))) {
					xhrSetRequestHeaderOriginal.call(xhr, CSRF_HEADER, token);
					if (xhr.__c2fCabecalhos) xhr.__c2fCabecalhos[CSRF_HEADER.toLowerCase()] = true;
					if (xhr.__c2fValores) xhr.__c2fValores.push([CSRF_HEADER, token]);
				}
				xhr.__c2fTokenUsado = '';
				(xhr.__c2fValores || []).forEach(function (par) {
					if (par[0].toLowerCase() === CSRF_HEADER.toLowerCase()) xhr.__c2fTokenUsado = String(par[1]);
				});

				// req-175: ouvintes de CAPTURA no próprio XHR disparam antes dos ouvintes comuns e do
				// `onload`/`onreadystatechange` (ordem at-target do DOM). No 403 de CSRF eles seguram
				// o evento, renovam o token e reenviam o mesmo objeto: quem chamou — inclusive o
				// jQuery — só enxerga a resposta da repetição.
				if (!xhr.__c2fOuvinteCsrf && typeof xhr.addEventListener === 'function') {
					xhr.__c2fOuvinteCsrf = true;
					var interceptar = function (evento) {
						if (xhr.readyState !== 4) return;
						if (!xhr.__c2fCsrfSegurando && evento.type === 'readystatechange' && xhrDeveRepetirPorCsrf(xhr)) {
							xhr.__c2fCsrfSegurando = true;
							xhr.__c2fCsrfRepetido = true;
							xhrRepetirComNovoToken(xhr);
						}
						if (xhr.__c2fCsrfSegurando && evento && typeof evento.stopImmediatePropagation === 'function') {
							evento.stopImmediatePropagation();
						}
					};
					xhr.addEventListener('readystatechange', interceptar, true);
					xhr.addEventListener('load', interceptar, true);
					xhr.addEventListener('loadend', interceptar, true);
				}
				if (!xhr.__c2fOuvinte401) {
					xhr.__c2fOuvinte401 = true;
					xhr.addEventListener('load', function () {
						if (xhr.status !== 401) return;
						var destino = '';
						try {
							destino = xhr.getResponseHeader('X-Gestor-Auth-Redirect');
						} catch (error) {
							// Cabeçalho inacessível (CORS): sem destino confiável.
						}
						if (destino) redirecionarParaLogin(destino);
					});
				}
			} catch (error) {
				// Nunca impedir o envio por causa do token: o backend decide se aceita.
			}
			return xhrSendOriginal.apply(this, arguments);
		};

		XMLHttpRequest.prototype.__c2fCsrf = true;
	}

	document.addEventListener('submit', function (event) {
		aplicarCsrfNoFormulario(event.target);
	}, true);

	// req-175: ao voltar para uma aba que ficou em segundo plano, o token é renovado ANTES do
	// primeiro clique. Limitado a uma consulta a cada 30 s e só depois de 30 s fora de vista. A
	// checagem proativa nunca redireciona para o login: se a sessão morreu, quem avisa é a próxima
	// ação do usuário — assim um formulário meio preenchido não some só porque a aba ganhou foco.
	var ocultoDesde = 0;

	function aoMudarVisibilidade() {
		if (document.visibilityState === 'hidden') {
			ocultoDesde = Date.now();
			return;
		}
		if (document.visibilityState !== 'visible' || !ocultoDesde) return;

		var agora = Date.now();
		var ausencia = agora - ocultoDesde;
		ocultoDesde = 0;

		if (ausencia < CSRF_INTERVALO_MINIMO || agora - ultimaRenovacaoCsrf < CSRF_INTERVALO_MINIMO) return;
		if (!csrfToken()) return;

		// Dentro de um iframe da mesma origem, a página hospedeira já faz essa checagem.
		try {
			if (window.parent && window.parent !== window && window.parent.gestorCsrf) return;
		} catch (error) {
			// Pai de outra origem: este frame cuida de si.
		}

		renovarCsrf({ redirecionar: false }).catch(function () {
			// Silencioso: a próxima requisição mutável tenta de novo e trata a sessão expirada.
		});
	}

	if (typeof document.addEventListener === 'function') {
		document.addEventListener('visibilitychange', aoMudarVisibilidade);
	}

	// req-111 (CR-001): o neutralizador de `fbq`/`dataLayer`/`gtag` do req-109 §4 foi REMOVIDO.
	// Nenhuma página do sistema bloqueia coletor de analytics — o problema original era o laço de
	// redirecionamento de cookie empurrando clientes sem cookie para `cookies-is-mandatory/`, e ele
	// foi resolvido no backend, na origem.

	// API pública mínima — usada pelo editor visual (iframe srcdoc) e testável isoladamente.
	window.gestorCsrf = {
		campo: CSRF_CAMPO,
		header: CSRF_HEADER,
		token: csrfToken,
		aplicarNoFormulario: aplicarCsrfNoFormulario,
		// req-175
		codigoErro: CSRF_ERRO_CODIGO,
		renovar: renovarCsrf,
		aplicarToken: aplicarNovoTokenCsrf,
		recuperarSessao: recuperarSessaoExpirada
	};

	// req-156: resolvedor de assets de terceiro para o JavaScript.
	//
	// O BATCH-146 tirou o gestor do CDN, mas varreu as tags montadas no PHP. As que o CLIENTE monta
	// — iframes de preview por `srcdoc`, Editbar e previews de widget — ficaram com host e versão
	// escritos à mão, paralelos ao registro: `unpkg.com` para o Tailwind Browser em seis arquivos,
	// `cdnjs` para jQuery e CodeMirror na Editbar. `gestor.assetsUrls` chega pronto do
	// `assets_externos_urls_js()`, que aplica a regra do registro: disco primeiro, CDN só como
	// fallback (DEC-122).
	//
	// A busca pela janela PAI existe pelo mesmo motivo do token de CSRF acima: um iframe `srcdoc`
	// herda a origem, mas não as variáveis da página hospedeira.
	function assetsUrlsMapa() {
		try {
			if (window.gestor && window.gestor.assetsUrls) return window.gestor.assetsUrls;
		} catch (error) {
			// gestor ausente nesta janela.
		}

		try {
			if (window.parent && window.parent !== window && window.parent.gestor && window.parent.gestor.assetsUrls) {
				return window.parent.gestor.assetsUrls;
			}
		} catch (error) {
			// origem distinta: nada a fazer.
		}

		return {};
	}

	// Devolve string vazia quando o registro não conhece o arquivo, em vez de um CDN embutido: uma
	// tag vazia falha de modo visível e rastreável, enquanto uma URL remota escrita aqui recriaria
	// em silêncio a dependência que este trabalho remove.
	function assetUrl(biblioteca, arquivo) {
		var mapa = assetsUrlsMapa();
		var lib = mapa[biblioteca] || {};
		var url = lib[arquivo];

		if (typeof url === 'string' && url !== '') return url;

		try { console.warn('gestorAssets: asset nao resolvido pelo registro: ' + biblioteca + '/' + arquivo); } catch (e) { }
		return '';
	}

	window.gestorAssets = {
		url: assetUrl,
		mapa: assetsUrlsMapa
	};
})();

$(document).ready(function () {
	// ===== Menu Principal do gestor.

	if ($('.menuComputerCont').length > 0) {
		// ===== Configurações do Menu
		var menuConfig = {
			defaultWidth: 250,
			minWidth: 200,
			maxWidth: 450,
			mobileBreakpoint: 1024, // Inclui tablets no comportamento mobile (sidebar overlay)
			storageKeys: {
				width: 'gestor-menu-width',
				closed: 'gestor-menu-closed',
				scroll: 'menuComputerContScroll',
				scrollMobile: 'menuMobileContScroll'
			}
		};

		// ===== Funções Auxiliares do Menu
		function isMobile() {
			return window.innerWidth <= menuConfig.mobileBreakpoint;
		}

		function getMenuState() {
			return {
				width: parseInt(localStorage.getItem(menuConfig.storageKeys.width)) || menuConfig.defaultWidth,
				closed: localStorage.getItem(menuConfig.storageKeys.closed) === 'true'
			};
		}

		function saveMenuState(state) {
			if (state.width !== undefined) {
				localStorage.setItem(menuConfig.storageKeys.width, state.width);
			}
			if (state.closed !== undefined) {
				localStorage.setItem(menuConfig.storageKeys.closed, state.closed);
			}
		}

		function setMenuWidth(width) {
			width = Math.max(menuConfig.minWidth, Math.min(menuConfig.maxWidth, width));
			$('.menuComputerCont').css('width', width + 'px');
			if (!isMobile()) {
				$('.paginaCont').css('margin-left', width + 'px');
			}
			return width;
		}

		function openMenu() {
			if (isMobile()) {
				// Mobile: usar comportamento de sidebar overlay
				$('body').addClass('menu-mobile-open');
			} else {
				// Desktop: comportamento padrão
				$('body').removeClass('menu-closed');
				var state = getMenuState();
				$('.paginaCont').css('margin-left', state.width + 'px');
				saveMenuState({ closed: false });
			}
		}

		function closeMenu() {
			if (isMobile()) {
				// Mobile: fechar sidebar overlay
				$('body').removeClass('menu-mobile-open');
			} else {
				// Desktop: comportamento padrão
				$('body').addClass('menu-closed');
				$('.paginaCont').css('margin-left', '0');
				saveMenuState({ closed: true });
			}
		}

		function toggleMenu() {
			if (isMobile()) {
				if ($('body').hasClass('menu-mobile-open')) {
					closeMenu();
				} else {
					openMenu();
				}
			} else {
				if ($('body').hasClass('menu-closed')) {
					openMenu();
				} else {
					closeMenu();
				}
			}
		}

		// ===== Inicialização do Menu (SEM animação)

		// Adicionar classe para desabilitar transições durante a inicialização
		$('body').addClass('menu-no-transition');

		var menuState = getMenuState();

		// Aplicar largura salva ao menu
		$('.menuComputerCont').css('width', menuState.width + 'px');

		// Aplicar estado aberto/fechado SEM animação
		if (isMobile()) {
			// Em mobile, sempre começa fechado (sidebar overlay)
			$('.paginaCont').css('margin-left', '0');
		} else {
			// Desktop: aplicar estado salvo
			if (menuState.closed) {
				$('body').addClass('menu-closed');
				$('.paginaCont').css('margin-left', '0');
			} else {
				$('.paginaCont').css('margin-left', menuState.width + 'px');
			}
		}

		// Remover classe após um pequeno delay para reabilitar transições
		requestAnimationFrame(function () {
			requestAnimationFrame(function () {
				$('body').removeClass('menu-no-transition');
			});
		});

		// ===== Event Listeners dos Botões

		// Botão Fechar Menu
		$('#menu-close-btn').on('click', function () {
			closeMenu();
		});

		// Botão Toggle (abrir menu quando fechado)
		$('#menu-toggle-btn').on('click', function () {
			openMenu();
		});

		// Overlay mobile (fechar menu ao clicar)
		$('.menu-mobile-overlay').on('click', function () {
			closeMenu();
		});

		// Botão Dashboard 3D
		$('#menu-dashboard3d-btn').on('click', function () {
			// Navegar para o dashboard 3D
			var dashboard3dUrl = (typeof gestor !== 'undefined' && gestor.raiz)
				? gestor.raiz + 'dashboard-3d/'
				: '/dashboard-3d/';

			// Adicionar parâmetro para ativar modo 3D
			window.location.href = dashboard3dUrl;
		});

		// ===== Listener de Resize da Janela
		var resizeTimeout;
		$(window).on('resize', function () {
			clearTimeout(resizeTimeout);
			resizeTimeout = setTimeout(function () {
				var state = getMenuState();

				if (isMobile()) {
					// Em mobile, fechar menu e garantir margin-left 0
					$('body').removeClass('menu-closed');
					$('.paginaCont').css('margin-left', '0');
				} else {
					// Em desktop, restaurar estado salvo
					$('body').removeClass('menu-mobile-open');
					if (state.closed) {
						$('body').addClass('menu-closed');
						$('.paginaCont').css('margin-left', '0');
					} else {
						$('.paginaCont').css('margin-left', state.width + 'px');
					}
				}
			}, 100);
		});

		// ===== Lógica de Redimensionamento do Menu (apenas desktop)
		var isResizing = false;
		var startX = 0;
		var startWidth = 0;

		$('#menu-resize-handle').on('mousedown', function (e) {
			e.preventDefault();
			isResizing = true;
			startX = e.clientX;
			startWidth = $('.menuComputerCont').width();
			$('body').addClass('menu-resizing');

			// Desabilitar transições durante o resize
			$('.menuComputerCont').css('transition', 'none');
			$('.paginaCont').css('transition', 'none');
		});

		$(document).on('mousemove', function (e) {
			if (!isResizing) return;

			var diff = e.clientX - startX;
			var newWidth = startWidth + diff;
			var finalWidth = setMenuWidth(newWidth);

			// Salvar largura em tempo real para feedback visual
			saveMenuState({ width: finalWidth });
		});

		$(document).on('mouseup', function () {
			if (isResizing) {
				isResizing = false;
				$('body').removeClass('menu-resizing');

				// Reabilitar transições
				$('.menuComputerCont').css('transition', '');
				$('.paginaCont').css('transition', '');

				// Salvar estado final
				var finalWidth = $('.menuComputerCont').width();
				saveMenuState({ width: finalWidth });
			}
		});

		// ===== Suporte a Touch para dispositivos móveis
		$('#menu-resize-handle').on('touchstart', function (e) {
			e.preventDefault();
			isResizing = true;
			startX = e.originalEvent.touches[0].clientX;
			startWidth = $('.menuComputerCont').width();
			$('body').addClass('menu-resizing');

			$('.menuComputerCont').css('transition', 'none');
			$('.paginaCont').css('transition', 'none');
		});

		$(document).on('touchmove', function (e) {
			if (!isResizing) return;

			var diff = e.originalEvent.touches[0].clientX - startX;
			var newWidth = startWidth + diff;
			var finalWidth = setMenuWidth(newWidth);
			saveMenuState({ width: finalWidth });
		});

		$(document).on('touchend', function () {
			if (isResizing) {
				isResizing = false;
				$('body').removeClass('menu-resizing');

				$('.menuComputerCont').css('transition', '');
				$('.paginaCont').css('transition', '');

				var finalWidth = $('.menuComputerCont').width();
				saveMenuState({ width: finalWidth });
			}
		});

		// ===== Atalho de Teclado (Ctrl/Cmd + B para toggle menu)
		$(document).on('keydown', function (e) {
			if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
				e.preventDefault();
				toggleMenu();
			}
		});

		// ===== Double-click no handle para resetar largura padrão
		$('#menu-resize-handle').on('dblclick', function () {
			setMenuWidth(menuConfig.defaultWidth);
			saveMenuState({ width: menuConfig.defaultWidth });
		});

		// ===== Manter a posição do scroll dos dois menus de maneira persistente entre páginas.

		$('.menuComputerCont').on('scroll', function (e) {
			sessionStorage.setItem(menuConfig.storageKeys.scroll, $(this).scrollTop());
		});

		if (sessionStorage.getItem(menuConfig.storageKeys.scroll)) {
			$('.menuComputerCont').scrollTop(sessionStorage.getItem(menuConfig.storageKeys.scroll));
		}

		$('#conn2flow-menu-principal').on('scroll', function (e) {
			sessionStorage.setItem(menuConfig.storageKeys.scrollMobile, $(this).scrollTop());
		});

		if (sessionStorage.getItem(menuConfig.storageKeys.scrollMobile)) {
			$('#conn2flow-menu-principal').scrollTop(sessionStorage.getItem(menuConfig.storageKeys.scrollMobile));
		}
	}

	if ('languages' in gestor) {
		// ===== Funções Auxiliares de Cookie

		function setCookie(cname, cvalue, exdays) {
			var d = new Date();
			d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
			var expires = "expires=" + d.toUTCString();
			var sameSite = "SameSite=Lax";
			var secure = (location.protocol === 'https:') ? "; Secure" : "";
			document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/;" + sameSite + secure;
		}

		function getCookie(cname) {
			var name = cname + "=";
			var ca = document.cookie.split(';');
			for (var i = 0; i < ca.length; i++) {
				var c = ca[i];
				while (c.charAt(0) == ' ') {
					c = c.substring(1);
				}
				if (c.indexOf(name) === 0) {
					return c.substring(name.length, c.length);
				}
			}
			return "";
		}

		function areCookiesEnabled() {
			// Tentar definir um cookie de teste
			setCookie('testCookie', 'test', 1);
			// Tentar lê-lo
			var testValue = getCookie('testCookie');
			// Remover o cookie de teste
			document.cookie = 'testCookie=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
			// Retornar se conseguiu ler
			return testValue === 'test';
		}

		// ===== Widget de Seleção de Linguagem (Via Iframe)

		if (gestor.languages.widgetActive && Array.isArray(gestor.pageLanguages) && gestor.pageLanguages.length > 1 && (gestor.moduloId != 'dashboard' && gestor.moduloOpcao != 'dashboard-site-toolbar')) {
			// Criar Iframe para isolar o ambiente (Fomantic UI / jQuery)
			var iframeSrc = gestor.raiz + 'global/language-widget.html';
			var iframeId = 'gestor-language-iframe';

			var iframe = document.createElement('iframe');
			iframe.id = iframeId;
			iframe.src = iframeSrc;
			iframe.style.position = 'fixed';
			iframe.style.bottom = '20px';
			iframe.style.right = '20px';
			iframe.style.width = '0px'; // Começa invisível até carregar
			iframe.style.height = '0px';
			iframe.style.border = 'none';
			iframe.style.zIndex = '99999';
			iframe.style.overflow = 'hidden';
			iframe.allowTransparency = "true"; // Para navegadores antigos

			document.body.appendChild(iframe);

			// Escutar mensagens do Iframe
			window.addEventListener('message', function (event) {
				// Verificar origem se necessário (aqui é mesmo domínio/subdomínio geralmente)

				var data = event.data;

				if (data.type === 'resize') {
					$('#' + iframeId).css({
						'width': data.width + 'px',
						'height': data.height + 'px'
					});
				}

				if (data.type === 'changeLang') {
					var lang = data.lang;
					if (lang != gestor.language) {
						// Redirecionar (Lógica Centralizada)
						var rootUrl = gestor.raizSemLang;
						var pathname = window.location.pathname;
						var relativePath = pathname;

						if (pathname.indexOf(rootUrl) === 0) {
							relativePath = pathname.substring(rootUrl.length);
						}

						// Verificar se o caminho relativo começa com a linguagem atual
						if (relativePath.startsWith(gestor.language + '/')) {
							relativePath = relativePath.substring(gestor.language.length + 1);
						}

						// Montar nova URL
						var newUrl = rootUrl + lang + '/' + relativePath + window.location.search + window.location.hash;

						window.location.href = newUrl;
					}
				}
			});

			// Inicializar o Iframe quando carregar
			iframe.onload = function () {
				iframe.contentWindow.postMessage({
					type: 'init',
					config: gestor.languages,
					currentLang: gestor.language
				}, '*');
			};
		}

		// ===== Detecção Automática

		if (gestor.languages.autoDetect) {
			var cookieName = gestor.languageCookie;
			var cookieDays = 30;
			var savedLang = '';
			let cookieEnabled = false;

			// Pegar o cookie se existe
			savedLang = getCookie(cookieName);

			// Fallback para localStorage caso cookies não estejam disponíveis.
			if (!savedLang) {
				try {
					if (!areCookiesEnabled()) {
						savedLang = localStorage.getItem(cookieName);
					} else { cookieEnabled = true; }
				} catch (e) { }
			} else { cookieEnabled = true; }

			// Detectar linguagem do navegador
			var browserLang = navigator.language || navigator.userLanguage;
			browserLang = browserLang.toLowerCase();
			var targetLang = browserLang;

			// Se ainda não tem preferência salva (primeira visita ou navegador não suportado), tentar detectar. Ou se a preferência salva é diferente do navegador (mudança de linguagem do navegador).
			if (!savedLang || savedLang != targetLang) {
				// Linguagem padrão do sistema no servidor
				var systemLang = gestor.languageSystem;

				// Verificar se a linguagem do navegador é suportada
				var isBrowserSupported = false;
				var supportedBrowserLang = '';

				// Verificação se a linguagem do navegador está na lista de linguagens suportadas
				if (gestor.languages.codigos) {
					for (var i = 0; i < gestor.languages.codigos.length; i++) {
						if (gestor.languages.codigos[i].codigo == targetLang) {
							isBrowserSupported = true;
							supportedBrowserLang = targetLang;
							break;
						}
					}

					// Tentar matching parcial se não encontrou exato (ex: pt-BR -> pt)
					if (!isBrowserSupported) {
						var shortLang = targetLang.split('-')[0];
						for (var i = 0; i < gestor.languages.codigos.length; i++) {
							if (gestor.languages.codigos[i].codigo == shortLang) {
								isBrowserSupported = true;
								supportedBrowserLang = shortLang;
								break;
							}
						}
					}
				}

				// Se a linguagem do navegador é suportada, avaliar redirecionamento
				if (isBrowserSupported) {
					var rootUrl = gestor.raiz;
					var pathname = window.location.pathname;
					var relativePath = pathname;

					if (pathname.indexOf(rootUrl) === 0) {
						relativePath = pathname.substring(rootUrl.length);
					}

					// Verificação de segurança: se a URL já começa com a linguagem suportada, não redirecionar (usuário escolheu manualmente URL em outra linguagem).
					if (relativePath.startsWith(supportedBrowserLang + '/')) {
						return;
					}

					// Salvar cookie para enviar na próxima requisição para o servidor a linguagem do navegador.
					if (cookieEnabled) {
						setCookie(cookieName, supportedBrowserLang, cookieDays);
					} else {
						try { localStorage.setItem(cookieName, supportedBrowserLang); } catch (e) { }
					}

					// Reler página se diferente da linguagem do navegador salva para trocar a linguagem automaticamente.
					if (supportedBrowserLang != systemLang) {
						// Reload na nova linguagem
						window.location.reload();
					}
				} else {
					if (savedLang != systemLang) {
						// Linguagem do navegador não suportada, salvar a linguagem padrão do sistema.
						if (cookieEnabled) {
							setCookie(cookieName, systemLang, cookieDays);
						} else {
							try { localStorage.setItem(cookieName, systemLang); } catch (e) { }
						}

						// Reler página se diferente da linguagem do navegador salva para trocar a linguagem automaticamente.
						window.location.reload();
					}
				}
			}
		}
	}

});
