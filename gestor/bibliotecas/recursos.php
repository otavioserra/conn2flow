<?php
/*********
	Descrição: resolução de URLs dos recursos estáticos publicados (req-028 / BATCH-023).

	O código PHP do gestor vive FORA da pasta pública. Os assets já processados (CSS compilado,
	JS minificado, imagens e fontes de módulos e layouts) são publicados fisicamente em
	`public_html/dist/` pelo pipeline (`c2f assets:publish`, chamado por `resources:sync` e pelo
	deploy), onde o Nginx e o Apache os entregam direto do disco, sem acionar o PHP-FPM.

	Duas garantias sustentam a retrocompatibilidade:

	1. O caminho DENTRO de `dist/` é idêntico ao caminho da URL pública. `interface/interface.js`
	   é publicado como `dist/interface/interface.js`, e `admin-arquivos/js.js` como
	   `dist/admin-arquivos/js.js`. Não há tradução a manter em dois lugares.
	2. `recursos_url()` só aponta para `dist/` quando o arquivo consta no manifesto publicado.
	   Sem manifesto — ambiente de desenvolvimento, instalação recém-criada, asset novo ainda não
	   publicado — a URL emitida é exatamente a de antes, resolvida pelo controlador
	   `arquivo-estatico`. Nenhum módulo legado quebra por não ter sido republicado.
**********/

/** Nome da pasta pública de assets publicados. */
if(!defined('RECURSOS_DIST_DIR')) define('RECURSOS_DIST_DIR', 'dist');

/**
 * Nome do manifesto gravado pelo pipeline dentro de `dist/`.
 *
 * Comeca com ponto de proposito: ele e o inventario interno do build (caminhos, tamanhos e
 * hashes de tudo que foi publicado) e nao tem por que ser legivel pela web. O template Nginx
 * ja nega `location ~ /\.(?!well-known/)` antes de qualquer outra regra, e o `.htaccess`
 * gravado em `dist/` faz o mesmo no Apache.
 */
if(!defined('RECURSOS_DIST_MANIFESTO')) define('RECURSOS_DIST_MANIFESTO', '.manifest.json');

/**
 * Normaliza um caminho de asset para a forma usada como chave do manifesto.
 *
 * Função PURA. Remove barras iniciais, colapsa duplicadas, descarta query string e âncora, e
 * recusa qualquer caminho com traversal — a chave do manifesto nunca pode sair da árvore.
 *
 * @param string $caminho Caminho relativo do asset, como aparece na URL.
 * @return string Caminho normalizado, ou '' quando o caminho é inválido.
 */
function recursos_caminho_normalizar($caminho){
	$caminho = str_replace('\\', '/', (string)$caminho);

	// A query (`?v=`) e a âncora não fazem parte da identidade do arquivo.
	$corte = strcspn($caminho, '?#');
	$caminho = substr($caminho, 0, $corte);

	$caminho = preg_replace('#/+#', '/', $caminho);
	$caminho = ltrim((string)$caminho, '/');

	if($caminho === '') return '';
	if(strpos($caminho, '..') !== false) return '';
	if(strpos($caminho, "\0") !== false) return '';

	return $caminho;
}

/** Indica se a instalação tem assets publicados e habilitados para entrega direta. */
function recursos_dist_ativo(){
	global $_GESTOR;

	return !empty($_GESTOR['dist-ativo']) && !empty($_GESTOR['dist-manifest']);
}

/**
 * Informa se um asset consta no manifesto publicado em `dist/`.
 *
 * @param string $caminho Caminho relativo do asset (com ou sem query string).
 * @return bool
 */
function recursos_publicado($caminho){
	global $_GESTOR;

	if(!recursos_dist_ativo()) return false;

	$chave = recursos_caminho_normalizar($caminho);
	if($chave === '') return false;

	return isset($_GESTOR['dist-manifest'][$chave]);
}

/**
 * URL pública de um asset.
 *
 * Devolve o caminho em `dist/` quando o arquivo está publicado, e a URL histórica — servida pelo
 * controlador `arquivo-estatico` — em qualquer outro caso.
 *
 * O `dist/` usa `url-raiz-sem-lang` de propósito: o roteador acrescenta o prefixo de idioma a
 * `url-raiz`, e um mesmo arquivo físico não deve existir em duas URLs por causa do idioma.
 *
 * O parâmetro `$base` existe para os geradores PUROS — `editor_texto_assets_publicacao()`, por
 * exemplo — que recebem a raiz pública por argumento e não podem passar a depender do `$_GESTOR`
 * da requisição corrente. Ele afeta apenas o caminho de FALLBACK: um asset publicado mora em
 * `dist/`, que é único por instalação.
 *
 * @param string $caminho Caminho relativo do asset, opcionalmente com query string.
 * @param string|null $base Raiz pública usada no fallback; null usa `url-raiz`.
 * @return string URL pronta para o atributo `src`/`href`.
 */
function recursos_url($caminho, $base = null){
	global $_GESTOR;

	$caminho = str_replace('\\', '/', (string)$caminho);
	$caminho = ltrim($caminho, '/');

	$urlRaiz = ($base !== null && $base !== '') ? (string)$base : ($_GESTOR['url-raiz'] ?? '/');

	if(!recursos_publicado($caminho)) return $urlRaiz.$caminho;

	$distUrl = $_GESTOR['dist-url'] ?? (($_GESTOR['url-raiz-sem-lang'] ?? '/').RECURSOS_DIST_DIR.'/');

	return $distUrl.$caminho;
}

/**
 * Token de cache busting de um asset publicado.
 *
 * O manifesto guarda o hash do CONTEÚDO publicado; ele é mais preciso que a versão do dono do
 * asset e muda somente quando o arquivo entregue muda. Sem manifesto, vale a versão informada
 * pelo chamador (comportamento histórico).
 *
 * @param string $caminho Caminho relativo do asset.
 * @param string|null $versaoPadrao Versão usada quando o asset não está publicado.
 * @return string
 */
function recursos_versao($caminho, $versaoPadrao = null){
	global $_GESTOR;

	$chave = recursos_caminho_normalizar($caminho);
	if($chave !== '' && recursos_dist_ativo() && !empty($_GESTOR['dist-manifest'][$chave]['v'])){
		return (string)$_GESTOR['dist-manifest'][$chave]['v'];
	}

	if($versaoPadrao !== null && $versaoPadrao !== '') return (string)$versaoPadrao;

	return function_exists('gestor_asset_version') ? gestor_asset_version() : '1';
}

/**
 * URL de um asset já com o parâmetro de cache busting.
 *
 * @param string $caminho Caminho relativo do asset.
 * @param string|null $versao Versão a usar quando o asset não está publicado.
 * @param string|null $base Raiz pública do fallback; null usa `url-raiz`.
 * @return string
 */
function recursos_url_versionada($caminho, $versao = null, $base = null){
	return recursos_url($caminho, $base).'?v='.rawurlencode(recursos_versao($caminho, $versao));
}

/**
 * Tag `<script>` de um asset do gestor.
 *
 * @param string $caminho Caminho relativo do asset.
 * @param string|null $versao Versão usada quando o asset não está publicado.
 * @param string $extra Atributos adicionais já formatados (ex.: `defer`).
 * @param string|null $base Raiz pública do fallback; null usa `url-raiz`.
 * @return string
 */
function recursos_tag_js($caminho, $versao = null, $extra = '', $base = null){
	$extra = ($extra === '' ? '' : ' '.trim($extra));

	return '<script src="'.recursos_url_versionada($caminho, $versao, $base).'"'.$extra.'></script>';
}

/**
 * Tag `<link rel="stylesheet">` de um asset do gestor.
 *
 * @param string $caminho Caminho relativo do asset.
 * @param string|null $versao Versão usada quando o asset não está publicado.
 * @param string $extra Atributos adicionais já formatados (ex.: `data-c2f-css-role="quill"`).
 * @param string|null $base Raiz pública do fallback; null usa `url-raiz`.
 * @return string
 */
function recursos_tag_css($caminho, $versao = null, $extra = '', $base = null){
	$extra = ($extra === '' ? '' : ' '.trim($extra));

	return '<link rel="stylesheet" type="text/css" media="all" href="'.recursos_url_versionada($caminho, $versao, $base).'"'.$extra.' />';
}

/**
 * Contrato de mapeamento entre o arquivo FONTE no gestor e o caminho publicado em `dist/`.
 *
 * Função PURA — não toca disco. É a fonte única do contrato: o pipeline de publicação
 * (`c2f assets:publish`) e o controlador `arquivo-estatico` precisam concordar sobre qual URL
 * corresponde a qual arquivo, e uma divergência aqui produziria 404 em produção que não aparece
 * em desenvolvimento.
 *
 * Regras:
 *   `assets/<qualquer/caminho>`          -> `<qualquer/caminho>`
 *   `modulos/<m>/<m>.js`                 -> `<m>/js.js`
 *   `modulos/<m>/<m>.css`                -> `<m>/css.css`
 *   `modulos/<m>/<m>.<opcao>.js|css`     -> `<m>/<opcao>.js|css`
 *
 * O derivado `.min.js` não gera caminho próprio: ele é a VARIANTE preferida do arquivo de
 * autoria, escolhida no momento da publicação — a mesma decisão que
 * `arquivo_estatico_preferir_minificado()` toma em runtime.
 *
 * @param string $relativo Caminho do arquivo relativo à raiz do gestor, com `/` como separador.
 * @return string Caminho dentro de `dist/`, ou '' quando o arquivo não é publicável.
 */
function recursos_dist_mapear_fonte($relativo){
	$relativo = recursos_caminho_normalizar($relativo);
	if($relativo === '') return '';

	$segmentos = explode('/', $relativo);
	$raiz = array_shift($segmentos);

	if($raiz === 'assets'){
		return empty($segmentos) ? '' : implode('/', $segmentos);
	}

	if($raiz !== 'modulos' || count($segmentos) !== 2) return '';

	list($modulo, $arquivo) = $segmentos;
	if($modulo === '' || $arquivo === '') return '';

	// Só interessam os assets de entrega do próprio módulo: `<modulo>.<...>.<js|css>`.
	$prefixo = $modulo.'.';
	if(strpos($arquivo, $prefixo) !== 0) return '';

	$resto = substr($arquivo, strlen($prefixo));
	$partes = explode('.', $resto);
	$extensao = strtolower((string)array_pop($partes));
	if(!in_array($extensao, ['js', 'css'], true)) return '';

	// `<modulo>.min.js` é derivado de `<modulo>.js`, não um asset independente.
	if($partes === ['min']) return '';

	// `<modulo>.js` -> `<modulo>/js.js`; `<modulo>.dashboard.js` -> `<modulo>/dashboard.js`.
	$opcao = empty($partes) ? $extensao : implode('.', $partes);
	if($opcao === '' || strpos($opcao, '/') !== false) return '';

	return $modulo.'/'.$opcao.'.'.$extensao;
}
