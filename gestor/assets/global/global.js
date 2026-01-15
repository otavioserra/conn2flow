$(document).ready(function () {
	// ===== Menu Principal do gestor.

	if ($('.menuComputerCont').length > 0) {
		// ===== Manter a posição do scroll dos dois menus de maneira persistente entre páginas.

		$('.menuComputerCont').on('scroll', function (e) {
			sessionStorage.setItem('menuComputerContScroll', $(this).scrollTop());
		});

		if (sessionStorage.getItem("menuComputerContScroll")) {
			$('.menuComputerCont').scrollTop(sessionStorage.getItem("menuComputerContScroll"));
		}

		$('#conn2flow-menu-principal').on('scroll', function (e) {
			sessionStorage.setItem('menuMobileContScroll', $(this).scrollTop());
		});

		if (sessionStorage.getItem("menuMobileContScroll")) {
			$('#conn2flow-menu-principal').scrollTop(sessionStorage.getItem("menuMobileContScroll"));
		}
	}

	if ($('._gestor-menuPrincipalMobile').length > 0) {
		$('#conn2flow-menu-principal')
			.sidebar({
				dimPage: true,
				transition: 'overlay',
				mobileTransition: 'uncover'
			})
			;

		$('._gestor-menuPrincipalMobile').css('cursor', 'pointer');

		$('._gestor-menuPrincipalMobile').on('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			$('#conn2flow-menu-principal').sidebar('toggle');
		});
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

		if (gestor.languages.widgetActive) {
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