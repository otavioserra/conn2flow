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
						// Salvar preferência
						localStorage.setItem('gestor-language-preference', lang);

						// Redirecionar (Lógica Centralizada)
						var rootUrl = gestor.raiz;
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
			var savedLang = localStorage.getItem('gestor-language-preference');
			var currentLang = gestor.language;

			// 1. Prioridade: URL Atual (Manual Override)
			// Se o usuário já tem uma preferência salva, mas está acessando uma URL de outro idioma,
			// assumimos que ele mudou intencionalmente (digitou ou clicou).
			// Atualizamos a preferência para evitar o "loop" de redirecionamento.
			if (savedLang && savedLang !== currentLang) {
				localStorage.setItem('gestor-language-preference', currentLang);
				savedLang = currentLang; // Atualiza para evitar processamento desnecessário abaixo
			}

			// Se não tem preferência salva (primeira visita ou cache limpo), tentamos detectar pelo navegador.
			if (!savedLang) {
				var browserLang = navigator.language || navigator.userLanguage;
				browserLang = browserLang.toLowerCase();
				var targetLang = browserLang;

				// Verificar se a linguagem alvo é suportada
				var isSupported = false;
				var supportedLang = '';

				if (gestor.languages.codigos) {
					for (var i = 0; i < gestor.languages.codigos.length; i++) {
						if (gestor.languages.codigos[i].codigo == targetLang) {
							isSupported = true;
							supportedLang = targetLang;
							break;
						}
					}

					// Tentar matching parcial se não encontrou exato (ex: pt-BR -> pt)
					if (!isSupported) {
						var shortLang = targetLang.split('-')[0];
						for (var i = 0; i < gestor.languages.codigos.length; i++) {
							if (gestor.languages.codigos[i].codigo == shortLang) {
								isSupported = true;
								supportedLang = shortLang;
								break;
							}
						}
					}
				}

				// Se suportada e diferente da atual, redirecionar
				if (isSupported) {
					if (supportedLang != currentLang) {
						var rootUrl = gestor.raiz;
						var pathname = window.location.pathname;
						var relativePath = pathname;

						if (pathname.indexOf(rootUrl) === 0) {
							relativePath = pathname.substring(rootUrl.length);
						}

						// Verificação de segurança: se a URL já começa com a linguagem suportada, não redirecionar.
						if (relativePath.startsWith(supportedLang + '/')) {
							return;
						}

						// Se a URL atual começa com a linguagem atual, removemos para trocar
						if (relativePath.startsWith(currentLang + '/')) {
							relativePath = relativePath.substring(currentLang.length + 1);
						}

						var newUrl = rootUrl + supportedLang + '/' + relativePath + window.location.search + window.location.hash;
						window.location.href = newUrl;
					} else {
						// Se já estamos na linguagem do navegador, salvamos como preferência para visitas futuras.
						localStorage.setItem('gestor-language-preference', currentLang);
					}
				}
			}
		}
	}

});