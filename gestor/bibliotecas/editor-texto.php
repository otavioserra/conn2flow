<?php
/**
 * Biblioteca do Editor de Texto (Quill).
 *
 * Ponto único do sistema para tudo que envolve o editor de texto rico: versão da biblioteca, CSS e
 * JavaScript do EDITOR, CSS de RENDERIZAÇÃO do conteúdo publicado e a paridade visual entre os dois.
 *
 * Existe pelo mesmo motivo que `html-editor.php`: antes desta biblioteca, cada módulo que abria um
 * editor repetia a inclusão do CDN, a configuração e os ajustes de estilo. Trocar a versão do Quill,
 * corrigir um detalhe de aparência ou mudar a barra de ferramentas exigia caçar as ocorrências uma a
 * uma — e o que fosse esquecido divergia em silêncio.
 *
 * Divisão de responsabilidade dentro do arquivo:
 *  - EDITOR: o que o operador usa para escrever (roda no painel administrativo).
 *  - PUBLICAÇÃO: o que o visitante recebe (roda no site).
 *  - PARIDADE: o que faz os dois se parecerem.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-editor-texto'] = Array(
	'versao' => '1.0.0',
);

// ===== Configuração

if (!function_exists('editor_texto_assets_externos_carregar')) {
	/**
	 * Garante que o registro de assets externos esteja carregado.
	 *
	 * Sem isto o `function_exists()` dos pontos de uso falhava em silêncio e o editor caía no CDN
	 * mesmo com os arquivos já presentes em `assets/vendor/` — a migração parecia feita e não era.
	 */
	function editor_texto_assets_externos_carregar() {
		global $_GESTOR;

		if (function_exists('assets_externos_registro')) return;
		if (empty($_GESTOR['bibliotecas-path'])) return;

		$arquivo = $_GESTOR['bibliotecas-path'].'assets-externos.php';
		if (is_file($arquivo)) require_once($arquivo);
	}
}

if (!function_exists('editor_texto_versao_cdn')) {
	/**
	 * Versão do Quill servida pelo CDN.
	 *
	 * Fica aqui, e não espalhada em cada módulo, para que a atualização seja uma linha só. O
	 * `quill-content.css` publicado é gerado a partir da MESMA versão — divergir entre os dois faz o
	 * conteúdo renderizar diferente do que o autor viu ao escrever.
	 *
	 * @return string
	 */
	function editor_texto_versao_cdn() {
		global $_GESTOR;

		$versao = isset($_GESTOR['editor-texto-versao']) ? trim((string)$_GESTOR['editor-texto-versao']) : '';
		if($versao !== '') return $versao;

		// req-143 / BATCH-146: o default sai do REGISTRO de assets externos, para haver uma única
		// fonte da versão. O default anterior era `'2'` — uma faixa, não uma versão: qualquer
		// publicação 2.x entrava em produção sem revisão, exatamente o problema que este lote existe
		// para eliminar. E como `quill-content.css` é gerado a partir DESTA versão, uma faixa aqui
		// significa o editor e a página publicada podendo desenhar diferente sem ninguém ter mexido.
		editor_texto_assets_externos_carregar();

		if(function_exists('assets_externos_registro')){
			$registro = assets_externos_registro();
			if(isset($registro['quill']['versao'])) return (string)$registro['quill']['versao'];
		}

		return '2.0.3';
	}
}

// ===== EDITOR (painel administrativo)

if (!function_exists('editor_texto_assets_editor')) {
	/**
	 * Tags de CSS e JavaScript do editor de texto.
	 *
	 * Função PURA (testável): monta as tags e não toca no pipeline nem no disco.
	 *
	 * @param string $urlRaiz Raiz pública do projeto (`$_GESTOR['url-raiz']`).
	 * @param string $versaoAsset Versão dos assets do core, para cache-bust.
	 * @return array{css: list<string>, javascript: list<string>}
	 */
	function editor_texto_assets_editor($urlRaiz = '', $versaoAsset = '', $vendorFisico = '', $vendorPublico = '') {
		editor_texto_assets_externos_carregar();

		$versaoCdn = editor_texto_versao_cdn();

		// req-143 / BATCH-146: as URLs do Quill vêm do registro de assets externos, que serve do
		// disco quando `assets/vendor/` existe e do CDN enquanto não existir. A versão continua
		// saindo de `editor_texto_versao_cdn()` porque `quill-content.css` é gerado a partir DELA:
		// divergir entre as duas faz o editor e a página publicada desenharem diferente.
		$tagsQuill = function_exists('assets_externos_tags')
			? assets_externos_tags('quill', $vendorFisico, $vendorPublico)
			: Array('css' => Array(), 'js' => Array());

		$cssQuill = $tagsQuill['css']
			? $tagsQuill['css'][0]
			: '<link rel="stylesheet" type="text/css" media="all" href="https://cdn.jsdelivr.net/npm/quill@'.$versaoCdn.'/dist/quill.snow.css" />';

		// O papel marca a folha como "do Quill" para a auditoria de CSS e para o desmonte por papel
		// (req-117): sem ele esta folha vira CSS anônimo no meio da cascata.
		$cssQuill = str_replace(' />', ' data-c2f-css-role="quill" />', $cssQuill);

		$jsQuill = $tagsQuill['js']
			? $tagsQuill['js'][0]
			: '<script src="https://cdn.jsdelivr.net/npm/quill@'.$versaoCdn.'/dist/quill.min.js"></script>';

		return Array(
			'css' => Array(
				// Tema do editor: barra de ferramentas, seleção, tooltip de link.
				$cssQuill,
				// O MESMO CSS de conteúdo que a página publicada recebe: é o que garante que o
				// alinhamento, a indentação e as listas apareçam no editor como aparecerão no site.
				'<link rel="stylesheet" type="text/css" media="all" href="'.$urlRaiz.'interface/quill-content.css?v='.$versaoAsset.'" data-c2f-css-role="quill" />',
			),
			'javascript' => Array(
				$jsQuill,
				// Runtime compartilhado: cria os editores sobre `<textarea>` e mantém o campo do
				// formulário sincronizado. Sem ele, cada tela repetiria a configuração do editor.
				'<script src="'.$urlRaiz.'interface/editor-texto.js?v='.$versaoAsset.'"></script>',
			),
		);
	}
}

if (!function_exists('editor_texto_paridade_css')) {
	/**
	 * CSS que dá à área de edição a aparência da página publicada.
	 *
	 * O Quill roda DENTRO da página do gestor, não num iframe: ele herda a tipografia do painel
	 * administrativo, e não a do projeto. Por isso o operador escrevia com uma fonte e via outra
	 * depois de publicar.
	 *
	 * A ponte NÃO pode ser injetar o contrato do projeto inteiro na página: isso traria o Preflight e
	 * as regras do site para dentro do painel e quebraria o próprio gestor. O que viaja é apenas o
	 * necessário — os tokens de tema — e escopado em `.ql-editor`, onde vive o conteúdo do autor.
	 *
	 * Função PURA (testável).
	 *
	 * @param string $contrato Conteúdo do `browser-contract.css` do projeto (pode ser vazio).
	 * @return string Bloco `<style>` escopado, ou string vazia quando não há o que injetar.
	 */
	function editor_texto_paridade_css($contrato) {
		$contrato = (string)$contrato;
		if (trim($contrato) === '') {
			return '';
		}

		// Só as custom properties: elas são a marca (cor, fonte, medida). Regras de layout do projeto
		// não entram — dentro do painel competiriam com o CSS do gestor.
		// O `;` final é opcional: a última declaração antes de `}` costuma vir sem ele, e exigi-lo
		// fazia o token mais próximo do fecho ser ignorado em silêncio.
		if (!preg_match_all('/(--[A-Za-z0-9_-]+)\s*:\s*([^;{}]+)(?=[;}])/', $contrato, $encontrados, PREG_SET_ORDER)) {
			return '';
		}

		$tokens = Array();
		foreach ($encontrados as $par) {
			$valor = trim($par[2]);

			// Valor com `url()`/`data:` é arte embutida: pesa muito e não muda a tipografia do editor.
			if ($valor === '' || strlen($valor) > 160) {
				continue;
			}
			if (stripos($valor, 'data:') !== false || stripos($valor, 'url(') !== false) {
				continue;
			}

			$tokens[$par[1]] = $valor;
		}

		if (!$tokens) {
			return '';
		}

		$linhas = Array();
		foreach ($tokens as $nome => $valor) {
			$linhas[] = "\t".$nome.': '.$valor.';';
		}

		// `--font-sans` e `--color-*` são os nomes que o Tailwind v4 deriva do `@theme` do projeto:
		// com eles a área de edição adota a tipografia da marca sem depender de classe alguma.
		return '<style data-c2f-css-role="quill-editor-parity">'."\n"
			.'.ql-editor {'."\n".implode("\n", $linhas)."\n"
			."\tfont-family: var(--font-sans, inherit);\n"
			."\tcolor: var(--color-mp-ink, inherit);\n"
			.'}'."\n"
			.'</style>'."\n";
	}
}

if (!function_exists('editor_texto_incluir')) {
	/**
	 * Inclui no pipeline da página tudo que o editor de texto precisa.
	 *
	 * É o ponto que os módulos chamam: `editor_texto_incluir()` e nada mais. Quem precisar trocar a
	 * versão do Quill, acrescentar um plugin ou ajustar a paridade mexe só aqui.
	 *
	 * @param array $params
	 * @param bool $params['paridade'] Injeta os tokens do projeto na área de edição (padrão: true).
	 * @return void
	 */
	function editor_texto_incluir($params = false) {
		global $_GESTOR;

		$paridade = true;
		if (is_array($params) && array_key_exists('paridade', $params)) {
			$paridade = (bool)$params['paridade'];
		}

		$versaoAsset = function_exists('gestor_asset_version') ? gestor_asset_version('interface') : '1.0.0';
		$assets = editor_texto_assets_editor(
			$_GESTOR['url-raiz'] ?? '/',
			$versaoAsset,
			($_GESTOR['assets-path'] ?? '').'vendor'.DIRECTORY_SEPARATOR,
			($_GESTOR['url-raiz'] ?? '/').'vendor/'
		);

		foreach ($assets['css'] as $tag) {
			gestor_pagina_css_incluir($tag);
		}

		foreach ($assets['javascript'] as $tag) {
			gestor_pagina_javascript_incluir($tag);
		}

		if (!$paridade) {
			return;
		}

		// O contrato do projeto é opcional: instalação sem tema próprio simplesmente não injeta nada.
		$contrato = ($_GESTOR['contents-path'] ?? '').'tailwindcss'.DIRECTORY_SEPARATOR.'browser-contract.css';
		if (!is_file($contrato)) {
			return;
		}

		$css = editor_texto_paridade_css((string)file_get_contents($contrato));
		if ($css !== '') {
			gestor_pagina_css_incluir($css);
		}
	}
}

// ===== PUBLICAÇÃO (site)

if (!function_exists('editor_texto_conteudo_detectar')) {
	/**
	 * Detecta conteúdo formatado pelo editor de texto no HTML final.
	 *
	 * O Quill grava a formatação em CLASSES (`ql-indent-3`, `ql-align-right`), e não em estilo
	 * inline. Na página pública essas classes não tinham definição nenhuma: medido em
	 * `/artigos/teste-de-artigo/`, `.ql-indent-1` chegava sem CSS e a indentação escolhida pelo autor
	 * simplesmente sumia — o que explicava o comportamento errático ("tem hora que alinha, tem hora
	 * que não"), pois dependia de qual botão de formatação tinha sido usado.
	 *
	 * Função PURA (testável).
	 *
	 * @param string $html HTML final da página, já com widgets incluídos.
	 * @return bool
	 */
	function editor_texto_conteudo_detectar($html) {
		if (!is_string($html) || $html === '') {
			return false;
		}

		// Só conta dentro de `class="..."`: a string `ql-` aparece em texto, URL e comentário sem que
		// exista conteúdo formatado na página.
		if (strpos($html, 'ql-') === false) {
			return false;
		}

		if (!preg_match_all('/class\s*=\s*("|\')([^"\']*)\1/i', $html, $matches, PREG_SET_ORDER)) {
			return false;
		}

		// Compara TOKEN por TOKEN, como `gestor_pdf_viewer_detectar()`: checagem por substring daria
		// falso positivo em classe de terceiro que apenas contenha `ql-` no meio do nome.
		$exatas = Array('ql-editor', 'ql-container', 'ql-syntax', 'ql-code-block', 'ql-code-block-container');
		$prefixos = Array('ql-align-', 'ql-indent-', 'ql-size-', 'ql-font-', 'ql-direction-');

		foreach ($matches as $match) {
			$classes = preg_split('/\s+/', trim($match[2]));
			if (!$classes) {
				continue;
			}

			foreach ($classes as $classe) {
				if ($classe === '') {
					continue;
				}
				if (in_array($classe, $exatas, true)) {
					return true;
				}

				foreach ($prefixos as $prefixo) {
					// O sufixo tem de existir: `ql-indent-` sozinho não é formatação do Quill.
					if (strlen($classe) > strlen($prefixo) && str_starts_with($classe, $prefixo)) {
						return true;
					}
				}
			}
		}

		return false;
	}
}

if (!function_exists('editor_texto_assets_publicacao')) {
	/**
	 * Tags do CSS de conteúdo entregue ao visitante.
	 *
	 * É um asset de SISTEMA, e não um valor gravado por página: o CSS do editor é estático — as
	 * mesmas regras para toda publicação — então não há razão para ele viajar dentro do
	 * `css_compiled` de cada registro, onde só servia para contaminar o derivado e disputar a
	 * cascata com as utilities.
	 *
	 * Função PURA (testável).
	 *
	 * @param string $urlRaiz Raiz pública do projeto.
	 * @param string $versao Versão do asset, para cache-bust.
	 * @return array Lista de tags `<link>`.
	 */
	function editor_texto_assets_publicacao($urlRaiz = '', $versao = '') {
		return Array(
			'<link rel="stylesheet" type="text/css" media="all" href="'.$urlRaiz.'interface/quill-content.css?v='.$versao.'" data-c2f-css-role="quill" />',
		);
	}
}
