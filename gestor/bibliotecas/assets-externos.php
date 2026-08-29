<?php
/**
 * Biblioteca de Assets Externos.
 *
 * Ponto único do sistema para bibliotecas de terceiros (CodeMirror, SortableJS, jQuery, PDF.js…):
 * nome, VERSÃO e arquivos ficam declarados aqui, e os módulos apenas pedem
 * `assets_externos_incluir('codemirror')`.
 *
 * Por que existe (req-143). O inventário medido antes desta biblioteca: 11 bibliotecas de terceiros
 * referenciadas em 27 arquivos, com três problemas concretos —
 *
 *  1. `sortablejs@latest` em 16 ocorrências: versão NÃO fixada. Qualquer publicação no npm entrava
 *     direto em produção sem revisão; um release quebrado derrubaria os CRUDs de ordenação sem que
 *     ninguém tivesse mudado nada no projeto.
 *  2. jQuery vindo de DOIS CDNs diferentes, com risco de duas versões na mesma tela.
 *  3. O IP de cada visitante do site publicado entregue a jsdelivr, Cloudflare e unpkg — para
 *     projetos como o do Ministério Público isso é conformidade, não preferência.
 *
 * E o argumento histórico a favor do CDN não vale mais: o cache compartilhado entre sites acabou
 * quando os navegadores passaram a particionar o cache HTTP por origem. Hoje se paga DNS e TLS
 * extras por domínio sem ganhar cache.
 *
 * Estratégia de resolução: serve o arquivo LOCAL quando ele existe em `assets/vendor/<lib>/<versao>/`
 * e cai no CDN quando não existe. O fallback é deliberado — instalação que ainda não baixou os
 * arquivos continua funcionando, e a migração pode ser feita biblioteca por biblioteca.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-assets-externos'] = Array(
	'versao' => '1.0.0',
);

if (!function_exists('assets_externos_registro')) {
	/**
	 * Registro central: nome => versão + arquivos.
	 *
	 * `cdn` é o molde da URL remota, com `{v}` no lugar da versão e `{f}` no lugar do arquivo.
	 * `local` é o subdiretório em `assets/vendor/` onde o mesmo arquivo é procurado primeiro.
	 *
	 * @return array<string, array{versao: string, cdn: string, css: list<string>, js: list<string>}>
	 */
	function assets_externos_registro() {
		return Array(
			'sortablejs' => Array(
				// Estava como `@latest`: a versão passa a ser explícita para que uma publicação no
				// npm não entre em produção sem revisão.
				'versao' => '1.15.6',
				'cdn' => 'https://cdn.jsdelivr.net/npm/sortablejs@{v}/{f}',
				'css' => Array(),
				'js' => Array('Sortable.min.js'),
			),
			'qrcodejs' => Array(
				'versao' => '1.0.0',
				'cdn' => 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/{v}/{f}',
				'css' => Array(),
				'js' => Array('qrcode.min.js'),
			),
			'fingerprintjs' => Array(
				'versao' => '4',
				'cdn' => 'https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@{v}/dist/{f}',
				'css' => Array(),
				'js' => Array('fp.min.js'),
			),
			'jquery' => Array(
				// Estava em QUATRO lugares, com TRÊS versões e QUATRO hosts: 3.5.1 de
				// `ajax.googleapis.com` em `gestor.php` (toda página do gestor), 3.7.1 de jsdelivr no
				// editor HTML, 3.7.1 de cdnjs na toolbar e 3.6.0 de jsdelivr no widget de idioma. Duas
				// versões na mesma tela quebram plugins de um jeito difícil de diagnosticar, porque
				// quem carrega por último vence.
				'versao' => '3.7.1',
				'cdn' => 'https://cdn.jsdelivr.net/npm/jquery@{v}/dist/{f}',
				'css' => Array(),
				'js' => Array('jquery.min.js'),
			),
			'codemirror' => Array(
				'versao' => '5.65.20',
				'cdn' => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/{v}/{f}',
				'css' => Array(
					'codemirror.min.css',
					'theme/tomorrow-night-bright.css',
					'addon/dialog/dialog.css',
					'addon/display/fullscreen.css',
					'addon/search/matchesonscrollbar.css',
				),
				// A ORDEM importa: `codemirror.min.js` define o objeto que todo addon estende.
				'js' => Array(
					'codemirror.min.js',
					'addon/selection/active-line.js',
					'addon/dialog/dialog.js',
					'addon/search/searchcursor.js',
					'addon/search/search.js',
					'addon/scroll/annotatescrollbar.js',
					'addon/search/matchesonscrollbar.js',
					'addon/search/jump-to-line.js',
					'addon/edit/matchbrackets.js',
					'addon/display/fullscreen.js',
					'mode/xml/xml.js',
					'mode/css/css.js',
					'mode/htmlmixed/htmlmixed.js',
					'mode/javascript/javascript.js',
					'mode/markdown/markdown.js',
				),
			),
			'fomantic-ui' => Array(
				'versao' => '2.9.4',
				'cdn' => 'https://cdn.jsdelivr.net/npm/fomantic-ui@{v}/dist/{f}',
				'css' => Array('semantic.min.css'),
				'js' => Array('semantic.min.js'),
				// `arquivos`: baixados junto, mas SEM virar tag — o CSS os pede sozinho, por caminho
				// RELATIVO a ele próprio. Servido do CDN, `url(themes/...)` resolvia contra o domínio
				// do CDN; servido do disco, passa a resolver contra `vendor/fomantic-ui/2.9.4/`. Sem
				// estes arquivos os ÍCONES DO GESTOR SOMEM — foi o que aconteceu ao migrar o CSS sem
				// eles. A biblioteca só está local de verdade quando tudo que ela pede está local.
				//
				// O Lato vem junto: com estes arquivos, o Fomantic deixa de buscar a fonte fora.
				'arquivos' => Array(
					'themes/default/assets/fonts/Lato-Bold.woff2',
					'themes/default/assets/fonts/Lato-Bold.woff',
					'themes/default/assets/fonts/Lato-BoldItalic.woff2',
					'themes/default/assets/fonts/Lato-BoldItalic.woff',
					'themes/default/assets/fonts/Lato-Italic.woff2',
					'themes/default/assets/fonts/Lato-Italic.woff',
					'themes/default/assets/fonts/Lato-Regular.woff2',
					'themes/default/assets/fonts/Lato-Regular.woff',
					'themes/default/assets/fonts/LatoLatin-Bold.woff2',
					'themes/default/assets/fonts/LatoLatin-Bold.woff',
					'themes/default/assets/fonts/LatoLatin-BoldItalic.woff2',
					'themes/default/assets/fonts/LatoLatin-BoldItalic.woff',
					'themes/default/assets/fonts/LatoLatin-Italic.woff2',
					'themes/default/assets/fonts/LatoLatin-Italic.woff',
					'themes/default/assets/fonts/LatoLatin-Regular.woff2',
					'themes/default/assets/fonts/LatoLatin-Regular.woff',
					'themes/default/assets/fonts/brand-icons.woff2',
					'themes/default/assets/fonts/brand-icons.woff',
					'themes/default/assets/fonts/icons.woff2',
					'themes/default/assets/fonts/icons.woff',
					'themes/default/assets/fonts/outline-icons.woff2',
					'themes/default/assets/fonts/outline-icons.woff',
				),
			),
			'quill' => Array(
				'versao' => '2.0.3',
				'cdn' => 'https://cdn.jsdelivr.net/npm/quill@{v}/dist/{f}',
				'css' => Array('quill.snow.css'),
				'js' => Array('quill.min.js'),
			),
		);
	}
}

if (!function_exists('assets_externos_url')) {
	/**
	 * URL de um arquivo da biblioteca: local quando existe, CDN como fallback.
	 *
	 * Função PURA em relação à decisão (recebe a raiz física e a pública), para ser testável sem
	 * depender de `$_GESTOR` nem de um servidor.
	 *
	 * @param array $lib Entrada do registro.
	 * @param string $nome Identificador da biblioteca (subdiretório em `vendor/`).
	 * @param string $arquivo Nome do arquivo dentro da biblioteca.
	 * @param string $vendorFisico Caminho físico de `assets/vendor/` (com separador final).
	 * @param string $vendorPublico URL pública de `vendor/` (com barra final).
	 * @return string
	 */
	function assets_externos_url($lib, $nome, $arquivo, $vendorFisico, $vendorPublico) {
		$versao = (string)($lib['versao'] ?? '');
		$relativo = $nome.'/'.$versao.'/'.$arquivo;

		if ($vendorFisico !== '' && is_file($vendorFisico.str_replace('/', DIRECTORY_SEPARATOR, $relativo))) {
			return $vendorPublico.$relativo;
		}

		return str_replace(Array('{v}', '{f}'), Array($versao, $arquivo), (string)($lib['cdn'] ?? ''));
	}
}

if (!function_exists('assets_externos_tags')) {
	/**
	 * Tags de uma biblioteca registrada.
	 *
	 * @param string $nome Identificador no registro.
	 * @param string $vendorFisico Caminho físico de `assets/vendor/`.
	 * @param string $vendorPublico URL pública de `vendor/`.
	 * @return array{css: list<string>, js: list<string>}
	 */
	function assets_externos_tags($nome, $vendorFisico = '', $vendorPublico = '') {
		$registro = assets_externos_registro();
		if (!isset($registro[$nome])) {
			return Array('css' => Array(), 'js' => Array());
		}

		$lib = $registro[$nome];
		$tags = Array('css' => Array(), 'js' => Array());

		foreach ((array)($lib['css'] ?? []) as $arquivo) {
			$url = assets_externos_url($lib, $nome, $arquivo, $vendorFisico, $vendorPublico);
			$tags['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$url.'" />';
		}

		foreach ((array)($lib['js'] ?? []) as $arquivo) {
			$url = assets_externos_url($lib, $nome, $arquivo, $vendorFisico, $vendorPublico);
			$tags['js'][] = '<script src="'.$url.'"></script>';
		}

		return $tags;
	}
}

if (!function_exists('assets_externos_incluir')) {
	/**
	 * Inclui uma biblioteca de terceiro no pipeline da página.
	 *
	 * É o ponto que os módulos chamam. Trocar a versão, migrar do CDN para local ou acrescentar um
	 * arquivo passa a ser uma alteração no registro acima.
	 *
	 * @param string $nome Identificador no registro (ex.: `sortablejs`).
	 * @return bool false quando a biblioteca não está registrada.
	 */
	function assets_externos_incluir($nome) {
		global $_GESTOR;

		$vendorFisico = ($_GESTOR['assets-path'] ?? '').'vendor'.DIRECTORY_SEPARATOR;
		$vendorPublico = ($_GESTOR['url-raiz'] ?? '/').'vendor/';

		$tags = assets_externos_tags($nome, $vendorFisico, $vendorPublico);
		if (!$tags['css'] && !$tags['js']) {
			return false;
		}

		foreach ($tags['css'] as $tag) {
			gestor_pagina_css_incluir($tag);
		}

		foreach ($tags['js'] as $tag) {
			gestor_pagina_javascript_incluir($tag);
		}

		return true;
	}
}
