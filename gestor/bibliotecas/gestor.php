<?php
/**
 * Biblioteca Central do Gestor Conn2Flow
 *
 * Sistema de gerenciamento central que coordena:
 * - Componentes e layouts dinâmicos
 * - Variáveis globais do sistema
 * - Sessões e autenticação
 * - Redirecionamentos e navegação
 * - Inclusão de bibliotecas e módulos
 *
 * @package Conn2Flow
 * @subpackage Gestor
 * @version 1.0.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-gestor']							=	Array(
	'versao' => '1.0.0',
);

// =========================== Funções Auxiliares

/**
 * Verifica se um dado existe e não está vazio.
 *
 * Testa diferentes tipos de dados:
 * - Array: verifica se tem elementos (count > 0)
 * - String: verifica se tem caracteres (strlen > 0)
 * - Outros tipos: verifica se é truthy
 *
 * @param mixed $dado Dado a ser verificado.
 * @return bool True se existe e não está vazio, false caso contrário.
 */
function existe($dado = false){
	switch(gettype($dado)){
		case 'array':
			if(count($dado) > 0){
				return true;
			} else {
				return false;
			}
		break;
		case 'string':
			if(strlen($dado) > 0){
				return true;
			} else {
				return false;
			}
		break;
		default:
			if($dado){
				return true;
			} else {
				return false;
			}
	}
}

/** Resolve token de cache de um diretório de assets, com fallback semântico. */
function gestor_asset_version($owner = null, $fallback = null){
	global $_GESTOR;
	if(is_string($owner) && $owner !== '' && isset($_GESTOR['asset-versions']['owners'][$owner])){
		return $_GESTOR['asset-versions']['owners'][$owner];
	}
	if(isset($_GESTOR['asset-version'])) return $_GESTOR['asset-version'];
	if($fallback !== null && $fallback !== '') return $fallback;
	return $_GESTOR['versao'] ?? '1';
}

/** Resolve token de cache do módulo sem alterar sua versão semântica. */
function gestor_modulo_asset_version($modulo){
	global $_GESTOR;
	if(is_string($modulo)) $modulo = $_GESTOR['modulo#'.$modulo] ?? [];
	if(is_array($modulo)){
		if(!empty($modulo['asset_version'])) return $modulo['asset_version'];
		if(!empty($modulo['versao'])) return $modulo['versao'];
	}
	return gestor_asset_version();
}

// =========================== Funções do Gestor

/**
 * Ordena sidecars Tailwind pela responsabilidade na cascata.
 *
 * A ordem dos CSS Cascade Layers é definida na primeira aparição de cada camada. O layout precisa
 * declarar `theme, base, components, utilities` antes que páginas/componentes emitam apenas
 * `utilities`; caso contrário o Preflight pode ficar acima das utilities e sobrescrever controles.
 * A ordenação é estável dentro de cada papel.
 */
function gestor_css_precompiled_ordenar($styles){
	$buckets = Array(
		'layout-precompiled' => Array(),
		'dependency-precompiled' => Array(),
		'page-precompiled' => Array(),
		'resource-precompiled' => Array(),
		'other' => Array(),
	);

	foreach((array)$styles as $style){
		$role = 'other';
		if(is_string($style) && preg_match('/data-tailwind-role="([^"]+)"/', $style, $match)){
			$candidate = $match[1];
			if(isset($buckets[$candidate])) $role = $candidate;
		}
		$buckets[$role][] = $style;
	}

	// Uma página pode optar por um bundle Tailwind canônico que já foi compilado
	// com seu layout e dependências. Nesse modo, concatenar os sidecars isolados
	// reintroduziria conflitos de ordem entre utilitários globais (`hidden` x
	// `lg:flex`, por exemplo). CSS autoral/compilado online permanece no pipeline.
	if(!empty($GLOBALS['_GESTOR']['tailwind-page-bundle']) && $buckets['page-precompiled']){
		return array_merge($buckets['page-precompiled'], $buckets['other']);
	}

	return array_merge(
		$buckets['layout-precompiled'],
		$buckets['dependency-precompiled'],
		$buckets['page-precompiled'],
		$buckets['resource-precompiled'],
		$buckets['other']
	);
}

/**
 * Inclui recursos de página (CSS, CSS compilado e HTML extra head) no pipeline global.
 * Controla duplicidades via hash MD5 para evitar inclusão redundante quando múltiplos
 * blocos do mesmo widget/componente são inseridos na mesma página (req-028 / DEC-041).
 *
 * @param array $params
 * @param string $params['css']
 * @param string $params['css_precompiled']
 * @param string $params['css_precompiled_role'] Papel opcional para diagnóstico no DOM.
 * @param string $params['css_compiled']
 * @param string $params['html_extra_head']
 */
function gestor_pagina_recursos_incluir($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

	if(!isset($_GESTOR['recursos-incluidos-hashes'])){
		$_GESTOR['recursos-incluidos-hashes'] = Array();
	}

	// 1) HTML Extra Head
	if(isset($html_extra_head) && existe($html_extra_head)){
		$hash = md5($html_extra_head);
		if(!isset($_GESTOR['recursos-incluidos-hashes'][$hash])){
			$_GESTOR['recursos-incluidos-hashes'][$hash] = true;
			$html_extra_head_formatted = preg_replace("/(^|\n)/m", "\n    ", $html_extra_head);
			$_GESTOR['html-extra-head'][] = $html_extra_head_formatted."\n";
		}
	}

	// 2) CSS Customizado
	if(isset($css) && existe($css)){
		$hash = md5($css);
		if(!isset($_GESTOR['recursos-incluidos-hashes'][$hash])){
			$_GESTOR['recursos-incluidos-hashes'][$hash] = true;
			$css_formatted = preg_replace("/(^|\n)/m", "\n        ", $css);

			$_GESTOR['css'][] = '<style>'."\n";
			$_GESTOR['css'][] = $css_formatted."\n";
			$_GESTOR['css'][] = '</style>'."\n";
		}
	}

	// 3) CSS pré-compilado offline (req-114)
	if(isset($css_precompiled) && existe($css_precompiled)){
		$hash = md5($css_precompiled);
		if(!isset($_GESTOR['recursos-incluidos-hashes'][$hash])){
			$_GESTOR['recursos-incluidos-hashes'][$hash] = true;
			$css_precompiled_formatted = preg_replace("/(^|\n)/m", "\n        ", $css_precompiled);

			$role = (isset($css_precompiled_role) && in_array($css_precompiled_role, ['layout-precompiled','page-precompiled','resource-precompiled','dependency-precompiled'], true))
				? $css_precompiled_role
				: 'resource-precompiled';
			$_GESTOR['css-precompiled'][] = '<style data-tailwind-role="'.$role.'">'."\n"
				.$css_precompiled_formatted."\n"
				.'</style>'."\n";
		}
	}

	// 4) CSS compilado pelo editor online
	if(isset($css_compiled) && existe($css_compiled)){
		$hash = md5($css_compiled);
		if(!isset($_GESTOR['recursos-incluidos-hashes'][$hash])){
			$_GESTOR['recursos-incluidos-hashes'][$hash] = true;
			$css_compiled_formatted = preg_replace("/(^|\n)/m", "\n        ", $css_compiled);

			$_GESTOR['css-compiled'][] = '<style>'."\n";
			$_GESTOR['css-compiled'][] = $css_compiled_formatted."\n";
			$_GESTOR['css-compiled'][] = '</style>'."\n";
		}
	}
}

/**
 * Detecta crawlers/scrapers sociais e de busca pelo User-Agent (req-109 / BATCH-109).
 *
 * O WhatsApp, o Meta, o Twitter/X e afins buscam a URL UMA vez, sem cookies e sem seguir o
 * fluxo de verificação de cookie do gestor. Qualquer `Location:` intermediário faz o preview
 * do link chegar vazio (ou com o HTML da página de cookies obrigatórios) e as tags OpenGraph
 * da página real nunca são lidas. Por isso o bootstrap precisa reconhecê-los e isentá-los.
 *
 * A comparação é por SUBSTRING em caixa baixa: os User-Agents desses robôs carregam versão,
 * URL de contato e sufixos variáveis, então casar o token é mais estável que uma lista fechada.
 *
 * Função PURA (sem dependência de $_GESTOR) para ser testável isoladamente.
 *
 * @param string|null $userAgent User-Agent da requisição ($_SERVER['HTTP_USER_AGENT']).
 * @return bool
 */
function gestor_crawler_detectar($userAgent = null, $tokensExtra = null){
	if(!is_string($userAgent)) return false;

	$userAgent = strtolower(trim($userAgent));
	if($userAgent === '') return false;

	$tokens = gestor_crawler_tokens_padrao();

	if($tokensExtra === null) $tokensExtra = gestor_crawler_tokens_extra();
	if(is_array($tokensExtra) && $tokensExtra) $tokens = array_merge($tokens, $tokensExtra);

	foreach($tokens as $token){
		$token = strtolower(trim((string)$token));
		if($token === '') continue;
		if(strpos($userAgent, $token) !== false) return true;
	}

	return false;
}

/**
 * Lista embutida de tokens de robô (req-109, ampliada no req-111 / CR-001).
 *
 * Sempre ativa. São nomes estáveis — WhatsApp, `facebookexternalhit`, Googlebot e afins não mudam
 * de identificador há anos —, então esta lista não é fonte de manutenção recorrente. O que muda com
 * o tempo entra pela lista configurável (`gestor_crawler_tokens_extra`), sem deploy.
 *
 * Função PURA para ser testável isoladamente.
 *
 * @return array
 */
function gestor_crawler_tokens_padrao(){
	return Array(
		// Redes sociais e mensageiros — preview de link.
		'whatsapp',
		'facebookexternalhit',
		'facebookcatalog',
		'facebot',
		'meta-externalagent',
		'meta-externalfetcher',
		'instagram',
		'twitterbot',
		'linkedinbot',
		'telegrambot',
		'discordbot',
		'slackbot',
		'slack-imgproxy',
		'pinterest',
		'redditbot',
		'skypeuripreview',
		'embedly',
		'quora link preview',
		'vkshare',

		// Buscadores.
		'googlebot',
		'google-inspectiontool',
		'googleother',
		'google-extended',
		'storebot-google',
		'google-site-verification',
		'bingbot',
		'bingpreview',
		'duckduckbot',
		'yandexbot',
		'baiduspider',
		'applebot',
		'petalbot',
		'amazonbot',

		// Anúncios e auditoria de landing page (req-111 / CR-001).
		'adsbot-google',
		'mediapartners-google',
		'google-read-aloud',
		'chrome-lighthouse',
		'google page speed insights',
		'gtmetrix',
		'ahrefsbot',
		'semrushbot',
		'mj12bot',
		'dotbot',
		'screaming frog',

		// Monitoramento de disponibilidade e validadores.
		'uptimerobot',
		'pingdom',
		'statuscake',
		'better uptime',
		'w3c_validator',
		'developers.google.com/+/web/snippet',
	);
}

/**
 * Tokens adicionais definidos pelo operador em Ambiente → Configurações do Site (req-111 / CR-001).
 *
 * Desligada por padrão. Existe para o caso de um site com fluxo alto de robôs específicos: o
 * operador acrescenta o token e o efeito vale na hora, sem release do core.
 *
 * IMPORTANTE: esta lista é um COMPLEMENTO. A correção do laço de cookie (req-111) não depende de
 * reconhecer robô algum — cliente sem cookie recebe a página pública de qualquer forma. Estes
 * tokens servem ao outro uso da detecção: entregar só o `<head>` com OpenGraph em página protegida.
 *
 * @return array
 */
function gestor_crawler_tokens_extra(){
	global $_CONFIG;

	if(empty($_CONFIG['crawler-tokens-extra-ativo'])) return Array();

	$bruto = $_CONFIG['crawler-tokens-extra'] ?? '';
	if(is_array($bruto)) return array_values(array_filter(array_map('trim', $bruto), 'strlen'));

	return gestor_crawler_tokens_normalizar($bruto);
}

/**
 * Converte o texto livre da configuração numa lista de tokens.
 *
 * Aceita separação por vírgula, ponto e vírgula ou quebra de linha — o operador digita como quiser.
 *
 * Função PURA para ser testável isoladamente.
 *
 * @param string $bruto
 * @return array
 */
function gestor_crawler_tokens_normalizar($bruto){
	if(is_array($bruto)) $bruto = implode(',', $bruto);
	if(!is_string($bruto) || trim($bruto) === '') return Array();

	$partes = preg_split('/[,;\r\n]+/', $bruto);
	$tokens = Array();

	foreach($partes as $parte){
		$parte = strtolower(trim($parte));
		if($parte === '') continue;
		if(in_array($parte, $tokens, true)) continue;
		$tokens[] = $parte;
	}

	return $tokens;
}

/**
 * Identifica páginas de sistema que NÃO devem receber scripts de rastreamento (req-109 / BATCH-109).
 *
 * `cookies-is-mandatory/`, as páginas de erro e as rotas internas do gestor não são conteúdo
 * do site: disparar GTM/Meta Pixel nelas polui a analítica, duplica o Pixel (`Duplicate Pixel ID`)
 * e — na página de cookies obrigatórios — provoca chamadas CAPI malformadas justamente porque o
 * ambiente de cookie ainda não está provisionado.
 *
 * Função PURA para ser testável isoladamente.
 *
 * @param string $caminho Caminho da página já normalizado (`$_GESTOR['caminho-total']` com barra final).
 * @return bool
 */
function gestor_pagina_rota_sistema($caminho = ''){
	if(!is_string($caminho)) return false;

	$caminho = strtolower(trim($caminho));
	$caminho = trim($caminho, '/');

	$sistema = Array(
		'cookies-is-mandatory',
		'_gestor-cookie-verify',
		'404',
		'403',
		'500',
		'503',
	);

	foreach($sistema as $rota){
		if($caminho === $rota || strpos($caminho, $rota.'/') === 0) return true;
	}

	return false;
}

/**
 * Monta as metatags OpenGraph do `<head>` (req-109 / BATCH-109).
 *
 * Os valores vêm de `$_GESTOR['pagina#og']` quando a página define metadados próprios (o CRUD
 * dessas colunas é escopo do req-110) e caem, de forma graciosa, para o nome da página, o
 * `site-name` do `config.php` e o banner/logo padrão do projeto. Nenhuma tag é emitida com valor
 * vazio: um `og:image` vazio faz o WhatsApp exibir o card sem imagem em vez de usar o fallback.
 *
 * Função PURA para ser testável isoladamente.
 *
 * @param array $params
 * @param string $params['title']       Título da página.
 * @param string $params['description'] Descrição/resumo.
 * @param string $params['image']       URL absoluta da imagem de compartilhamento.
 * @param string $params['url']         URL canônica da página.
 * @param string $params['site_name']   Nome do site.
 * @param string $params['type']        Tipo OpenGraph (padrão `website`).
 * @param bool   $params['twitter']     Emite também o par mínimo de Twitter Cards (padrão true).
 * @return array Lista de tags `<meta …>`.
 */
function gestor_open_graph_tags($params = false){
	$valores = is_array($params) ? $params : Array();

	$campos = Array(
		'og:title'       => (string)($valores['title'] ?? ''),
		'og:description' => (string)($valores['description'] ?? ''),
		'og:image'       => (string)($valores['image'] ?? ''),
		'og:url'         => (string)($valores['url'] ?? ''),
		'og:site_name'   => (string)($valores['site_name'] ?? ''),
		'og:type'        => (string)($valores['type'] ?? 'website'),
	);

	$tags = Array();
	$temImagem = false;

	foreach($campos as $propriedade => $conteudo){
		$conteudo = trim(preg_replace('/\s+/', ' ', $conteudo));
		if($conteudo === '') continue;

		if($propriedade === 'og:image') $temImagem = true;

		$tags[] = '<meta property="'.$propriedade.'" content="'.htmlspecialchars($conteudo, ENT_QUOTES, 'UTF-8').'">';
	}

	// Twitter/X não lê `og:title` sem o `twitter:card`; o restante ele herda do OpenGraph.
	$twitter = !isset($valores['twitter']) || $valores['twitter'];

	if($twitter && $tags){
		$tags[] = '<meta name="twitter:card" content="'.($temImagem ? 'summary_large_image' : 'summary').'">';
	}

	return $tags;
}

/**
 * Decide o desfecho da verificação de cookie do navegador (req-111 / CR-001).
 *
 * Extraída de `gestor_cookie_verificacao()` para ser testável: o laço de redirecionamento que essa
 * regra evita foi um defeito MEDIDO em produção, e a diretriz de blindagem de bugs pede um teste
 * que falhe se ele voltar.
 *
 * Desfechos:
 *  - `ignorar`      — nada a fazer (robô, ou cookie já presente).
 *  - `emitir`       — emite o `Set-Cookie` e SEGUE renderizando a página pedida.
 *  - `redirecionar` — emite o cookie e faz o round-trip por `_gestor-cookie-verify/`.
 *
 * A regra decisiva contra o laço: numa ROTA DE SISTEMA nunca se redireciona. A
 * `cookies-is-mandatory/` é uma página como qualquer outra e, ao ser renderizada, reentra nesta
 * verificação — sem esta trava, a tela que existe para explicar o problema fica presa nele.
 *
 * Função PURA (sem $_GESTOR, $_COOKIE ou headers) para ser testável isoladamente.
 *
 * @param array $params
 * @param bool   $params['crawler']       Requisição identificada como robô.
 * @param bool   $params['tem_cookie']    Já existe cookie de verificação ou de autenticação.
 * @param bool   $params['exigir_sessao'] Fluxo que precisa PROVAR o cookie (login/cadastro).
 * @param string $params['caminho']       Caminho da requisição corrente.
 * @return string
 */
function gestor_cookie_verificacao_desfecho($params = false){
	$p = is_array($params) ? $params : Array();

	if(!empty($p['crawler'])) return 'ignorar';
	if(!empty($p['tem_cookie'])) return 'ignorar';

	// Trava anti-laço: rota de sistema emite o cookie, mas nunca redireciona.
	if(gestor_pagina_rota_sistema((string)($p['caminho'] ?? ''))) return 'emitir';

	if(empty($p['exigir_sessao'])) return 'emitir';

	return 'redirecionar';
}

/**
 * Extrai os metadados OpenGraph gravados no registro da página (req-110 / BATCH-110).
 *
 * Devolve apenas as chaves REALMENTE preenchidas: `gestor_open_graph_dados()` trata chave ausente
 * como "usar o fallback", enquanto uma chave presente e vazia venceria o nome da página.
 *
 * Função PURA para ser testável isoladamente.
 *
 * @param array $pagina Linha da tabela `paginas`.
 * @return array
 */
function gestor_pagina_og_do_registro($pagina = Array()){
	if(!is_array($pagina)) return Array();

	$mapa = Array(
		'og_titulo' => 'title',
		'og_descricao' => 'description',
		'imagem_destaque' => 'image',
		// req-112: meta tags clássicas viajam no mesmo pacote, com chaves próprias.
		'meta_descricao' => 'meta_description',
		'meta_keywords' => 'meta_keywords',
	);

	$og = Array();

	foreach($mapa as $coluna => $chave){
		if(!isset($pagina[$coluna])) continue;

		$valor = trim((string)$pagina[$coluna]);
		if($valor === '') continue;

		$og[$chave] = $valor;
	}

	return $og;
}

/**
 * Monta as meta tags clássicas de SEO do `<head>` (req-112 / BATCH-112).
 *
 * Complementa o OpenGraph, não o substitui: buscador lê `description`/`keywords`, rede social lê
 * `og:*`. Um site pode querer textos diferentes para cada público, então são campos separados.
 *
 * `keywords` só é emitida quando há valor — nenhum buscador relevante a usa para ranquear há anos, e
 * emitir a tag vazia é ruído puro.
 *
 * Função PURA para ser testável isoladamente.
 *
 * @param array $params
 * @param string $params['description']
 * @param string $params['keywords'] Lista separada por vírgula.
 * @return array Lista de tags `<meta …>`.
 */
function gestor_meta_seo_tags($params = false){
	$valores = is_array($params) ? $params : Array();
	$tags = Array();

	$descricao = trim(preg_replace('/\s+/', ' ', (string)($valores['description'] ?? '')));
	if($descricao !== ''){
		$tags[] = '<meta name="description" content="'.htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8').'">';
	}

	$keywords = gestor_meta_keywords_normalizar($valores['keywords'] ?? '');
	if($keywords !== ''){
		$tags[] = '<meta name="keywords" content="'.htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8').'">';
	}

	return $tags;
}

/**
 * Normaliza a lista de palavras-chave digitada pelo usuário (req-112 / BATCH-112).
 *
 * Aceita vírgula, ponto e vírgula ou quebra de linha; devolve separado por `, `, sem duplicatas,
 * sem entradas vazias e preservando a caixa original (marca própria pode ter maiúscula).
 *
 * Função PURA para ser testável isoladamente.
 *
 * @param string|array $bruto
 * @return string
 */
function gestor_meta_keywords_normalizar($bruto){
	if(is_array($bruto)) $bruto = implode(',', $bruto);
	if(!is_string($bruto) || trim($bruto) === '') return '';

	$partes = preg_split('/[,;\r\n]+/', $bruto);
	$palavras = Array();
	$vistas = Array();

	foreach($partes as $parte){
		$parte = trim(preg_replace('/\s+/', ' ', $parte));
		if($parte === '') continue;

		$chave = mb_strtolower($parte, 'UTF-8');
		if(isset($vistas[$chave])) continue;

		$vistas[$chave] = true;
		$palavras[] = $parte;
	}

	return implode(', ', $palavras);
}

/**
 * Detecta se um HTML já traz metatags de descrição/keywords próprias (req-112 / BATCH-112).
 *
 * Mesmo cuidado do OpenGraph: página ou layout que já traga a sua não recebe a do core — duas
 * `description` fazem o buscador escolher arbitrariamente.
 *
 * Função PURA para ser testável isoladamente.
 *
 * @param string|array $html
 * @return bool
 */
function gestor_meta_seo_existe($html){
	if(is_array($html)) $html = implode("\n", $html);
	if(!is_string($html) || $html === '') return false;

	return (bool)preg_match('/name\s*=\s*("|\')description\1/i', $html);
}

/**
 * Detecta se um HTML já traz metatags OpenGraph próprias (req-109 / BATCH-109).
 *
 * Layouts e páginas antigos podem trazer OpenGraph gravado à mão no `html_extra_head`. Nesse caso
 * o core não injeta o seu conjunto — duas tags `og:title` fazem o scraper escolher arbitrariamente.
 *
 * Função PURA para ser testável isoladamente.
 *
 * @param string|array $html HTML (ou lista de trechos) já enfileirado para o `<head>`.
 * @return bool
 */
function gestor_open_graph_existe($html){
	if(is_array($html)) $html = implode("\n", $html);
	if(!is_string($html) || $html === '') return false;

	return (bool)preg_match('/property\s*=\s*("|\')og:(title|image|description)\1/i', $html);
}

/**
 * Detecta se um HTML de página usa o motor de exibição PDF.js (req-096 / BATCH-096).
 *
 * O Editor HTML grava o motor B como o contêiner `<div class="conn2flow-pdfjs" data-pdf-src="…">`.
 * A detecção é feita sobre o HTML final (depois dos widgets) para que os assets do PDF.js sejam
 * incluídos apenas nas páginas que realmente possuem um leitor — nunca em todo o site.
 *
 * Função PURA (sem dependência de $_GESTOR) para ser testável isoladamente.
 *
 * @param string $html HTML da página já montada.
 * @return bool
 */
function gestor_pdf_viewer_detectar($html){
	if(!is_string($html) || $html === '') return false;
	if(strpos($html,'conn2flow-pdfjs') === false) return false;

	// Compara TOKEN por TOKEN do atributo class. Uma checagem por substring (ou por \b) daria falso
	// positivo em classes derivadas como `conn2flow-pdfjs-legacy`, incluindo assets sem necessidade.
	if(!preg_match_all('/class\s*=\s*("|\')([^"\']*)\1/i', $html, $matches, PREG_SET_ORDER)) return false;

	foreach($matches as $match){
		$classes = preg_split('/\s+/', trim($match[2]));

		if($classes && in_array('conn2flow-pdfjs', $classes, true)) return true;
	}

	return false;
}

/**
 * Tags de inclusão dos assets do motor PDF.js (req-096 / BATCH-096).
 *
 * A biblioteca vem da CDN (mesma estratégia do CodeMirror no Editor HTML) e o inicializador é um
 * asset de core servido por `interface/pdf-viewer.js`.
 *
 * Função PURA para ser testável isoladamente.
 *
 * @param string $urlRaiz Raiz pública do projeto ($_GESTOR['url-raiz']).
 * @param string $versao Versão do sistema, usada para cache-bust.
 * @return array Lista de tags <script>.
 */
function gestor_pdf_viewer_assets($urlRaiz = '', $versao = ''){
	return Array(
		'<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>',
		'<script src="'.$urlRaiz.'interface/pdf-viewer.js?v='.$versao.'"></script>',
	);
}

/** Monta a condição agrupada de IDs usada pela inclusão múltipla de componentes. */
function gestor_componente_ids_condicao($ids, $escape = null){
	if($escape === null) $escape = 'banco_escape_field';
	$condicoes = Array();

	foreach((array)$ids as $id){
		if(!is_scalar($id) || (string)$id === '') continue;
		$condicoes[(string)$id] = "id='".call_user_func($escape, (string)$id)."'";
	}

	return $condicoes ? '('.implode(' OR ', array_values($condicoes)).')' : '';
}

/**
 * Renderiza um componente HTML/CSS dinâmico.
 *
 * Busca e processa componentes do banco de dados com suporte a:
 * - Substituição de variáveis do sistema
 * - CSS compilado e inline
 * - HTML extra para <head>
 * - Componentes únicos ou múltiplos (array de IDs)
 * - Módulos extras para variáveis
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] ID descritivo do componente (ou array de IDs).
 * @param int $params['id_componentes'] ID numérico do componente (alternativa ao 'id').
 * @param string $params['modulo'] Módulo específico (opcional).
 * @param bool $params['return_css'] Se true, retorna array ['html' => ..., 'css' => ...], senão string HTML.
 * @param array $params['modulosExtra'] Módulos extras para busca de variáveis.
 * @param string $params['linguagem'] Código do idioma (padrão: idioma atual).
 *
 * @return string|array|false HTML do componente ou array com HTML+CSS, ou false se não encontrado.
 */
function gestor_componente($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

	$lang = (isset($linguagem) ? (string)$linguagem : (string)$_GESTOR['linguagem-codigo']);
	$lang_sql = banco_escape_field($lang);
	$modulo_sql = isset($modulo) ? banco_escape_field((string)$modulo) : null;
	$return_css_ativo = !empty($return_css);
	
	// ===== Parâmetros
	
	// Obrigatórios
	
	// id - String|Array - Identificador(es) descritivo do componente. (É obrigatório OU id OU id_componentes)
	// id_componentes - Int - Identificador numérico do componente no banco de dados. (É obrigatório OU id OU id_componentes)

	// Opcionais
	
	// modulo - String - Identificador descritivo do módulo.
	// return_css - Bool - Se ativo retorna Array com HTML e CSS, senão retorna String com o HTML do componente.
	// modulosExtra - Array - Se definido, incluir módulos extras para procura automática de variáveis nestes módulos.
	// linguagem - String - Código do idioma (padrão: idioma atual).
	
	// ===== 
	
	if(isset($modulosExtra)){
		gestor_pagina_variaveis_modulos(Array(
			'modulosExtra' => $modulosExtra,
		));
	}
	
	$componentes = false;
	
	if(isset($id_componentes)){
		$componentes = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id',
				'html',
				'css',
				'css_precompiled',
				'css_compiled',
				'html_extra_head',
				'modulo',
			))
			,
			"componentes",
			"WHERE id_componentes=".(int)$id_componentes
			." AND language='".$lang_sql."'"
			.(isset($modulo) ? " AND modulo='".$modulo_sql."'" : "")
		);
	}
	
	if(isset($id)){
		switch(gettype($id)){
			case 'array':
				$return_array = true;
				$ids = gestor_componente_ids_condicao($id);
				
				if(existe($ids)){
					$componentes = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id',
							'html',
							'css',
							'css_precompiled',
							'css_compiled',
							'html_extra_head',
							'modulo',
						))
						,
						"componentes",
						"WHERE ".$ids
						." AND language='".$lang_sql."'"
						.(isset($modulo) ? " AND modulo='".$modulo_sql."'" : "")
					);
				}
			break;
			default:
				$componentes = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id',
						'html',
						'css',
						'css_precompiled',
						'css_compiled',
						'html_extra_head',
						'modulo',
					))
					,
					"componentes",
					"WHERE id='".banco_escape_field((string)$id)."'"
					." AND language='".$lang_sql."'"
					.(isset($modulo) ? " AND modulo='".$modulo_sql."'" : "")
				);
		}
	}
	
	if(isset($return_array)){
		if($componentes){
			$return = Array();
			
			foreach($componentes as $componente){
				$id = $componente['id'];
				$modulo = $componente['modulo'];

				if(!empty($_GESTOR['development-env'])){
					if(existe($modulo)){
						$html_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/components/'.$id.'/'.$id.'.html';
						$css_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/components/'.$id.'/'.$id.'.css';
						$css_precompiled_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/components/'.$id.'/'.$id.'.precompiled.css';
					} else {
						$html_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/components/'.$id.'/'.$id.'.html';
						$css_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/components/'.$id.'/'.$id.'.css';
						$css_precompiled_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/components/'.$id.'/'.$id.'.precompiled.css';
					}

					$html = (file_exists($html_path)) ? file_get_contents($html_path) : '';
					$css = (file_exists($css_path)) ? file_get_contents($css_path) : '';
					$css_precompiled = (file_exists($css_precompiled_path)) ? file_get_contents($css_precompiled_path) : ($componente['css_precompiled'] ?? '');
				} else {
					$html = $componente['html'];
					$css = $componente['css'];
					$css_precompiled = $componente['css_precompiled'] ?? '';
				}
				
				$html_extra_head = $componente['html_extra_head'];
				$css_compiled = $componente['css_compiled'];
				
				if($return_css_ativo){
					$return[$id] = Array(
						'html' => $html,
						'html_extra_head' => $html_extra_head,
						'css' => $css,
						'css_precompiled' => $css_precompiled,
						'css_compiled' => $css_compiled,
					);
				} else {
					gestor_pagina_recursos_incluir(Array(
						'css' => $css,
						'css_precompiled' => $css_precompiled,
						'css_precompiled_role' => 'resource-precompiled',
						'css_compiled' => $css_compiled,
						'html_extra_head' => $html_extra_head,
					));

					$return[$id] = Array(
						'html' => $html,
					);
				}
			}
			
			return $return;
		} else {
			return Array();
		}
	} else {
		if($componentes){
			$id = $componentes[0]['id'];
			$modulo = $componentes[0]['modulo'];

			if(!empty($_GESTOR['development-env'])){
				if(existe($modulo)){
					$html_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/components/'.$id.'/'.$id.'.html';
					$css_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/components/'.$id.'/'.$id.'.css';
					$css_precompiled_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/components/'.$id.'/'.$id.'.precompiled.css';
				} else {
					$html_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/components/'.$id.'/'.$id.'.html';
					$css_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/components/'.$id.'/'.$id.'.css';
					$css_precompiled_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/components/'.$id.'/'.$id.'.precompiled.css';
				}

				$html = (file_exists($html_path)) ? file_get_contents($html_path) : '';
				$css = (file_exists($css_path)) ? file_get_contents($css_path) : '';
				$css_precompiled = (file_exists($css_precompiled_path)) ? file_get_contents($css_precompiled_path) : ($componentes[0]['css_precompiled'] ?? '');
			} else {
				$html = $componentes[0]['html'];
				$css = $componentes[0]['css'];
				$css_precompiled = $componentes[0]['css_precompiled'] ?? '';
			}
			
			$css_compiled = $componentes[0]['css_compiled'];
			$html_extra_head = $componentes[0]['html_extra_head'];

			if($return_css_ativo){
				return Array(
					'html' => $html,
					'css' => $css,
					'html_extra_head' => $html_extra_head,
					'css_precompiled' => $css_precompiled,
					'css_compiled' => $css_compiled,
				);
			} else {
				gestor_pagina_recursos_incluir(Array(
					'css' => $css,
					'css_precompiled' => $css_precompiled,
					'css_precompiled_role' => 'resource-precompiled',
					'css_compiled' => $css_compiled,
					'html_extra_head' => $html_extra_head,
				));

				return $html;
			}
		} else {
			return '';
		}
	}
}

/**
 * Renderiza um layout HTML/CSS completo da página.
 *
 * Busca e processa layouts do banco de dados com suporte a:
 * - Estrutura HTML completa (<!DOCTYPE>, <html>, <head>, <body>)
 * - CSS compilado e frameworks CSS
 * - Substituição de variáveis do sistema
 * - Layout padrão se não encontrado
 * - Layouts únicos ou múltiplos (array de IDs)
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] ID descritivo do layout (ou array de IDs).
 * @param int $params['id_layouts'] ID numérico do layout (alternativa ao 'id').
 * @param bool $params['return_css'] Se true, retorna array ['html' => ..., 'css' => ...], senão string HTML.
 * @param array $params['modulosExtra'] Módulos extras para busca de variáveis.
 *
 * @return string|array|false HTML do layout ou array com HTML+CSS, ou false se não encontrado.
 */
function gestor_layout($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// Obrigatórios
	
	// id - String|Array - Identificador(es) descritivo do layout. (É obrigatório OU id OU id_layouts)
	// id_layouts - Int - Identificador numérico do layout no banco de dados. (É obrigatório OU id OU id_layouts)

	// Opcionais
	
	// return_css - Bool - Se ativo retorna Array com HTML e CSS, senão retorna String com o HTML do layout.
	// modulosExtra - Array - Se definido, incluir módulos extras para procura automática de variáveis nestes módulos.
	
	// ===== 

	$layoutHTMLIfNoExists = '<!DOCTYPE html>
<html>
<head>
    <!-- pagina#titulo -->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- pagina#css -->
    <!-- pagina#js -->
</head>
<body>
    @[[pagina#corpo]]@
</body>
</html>';
	
	if(isset($modulosExtra)){
		gestor_pagina_variaveis_modulos(Array(
			'modulosExtra' => $modulosExtra,
		));
	}
	
	$lang = $_GESTOR['linguagem-codigo'];
	$layouts = false;
	
	if(isset($id_layouts)){
		$layouts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id',
				'html',
				'css',
				'css_precompiled',
				'css_compiled',
				'framework_css',
				'plugin',
				'modulo',
			))
			,
			"layouts",
			"WHERE id_layouts='".$id_layouts."'"
			." AND language='".$_GESTOR['linguagem-codigo']."'"
		);
	}
	
	if(isset($id)){
		switch(gettype($id)){
			case 'array':
				$return_array = true;
				$ids = '';
				foreach($id as $i){
					$ids .= (existe($ids) ? ' OR ' : '') .  "id='".$i."'";
				}
				
				if(existe($ids)){
					$layouts = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id',
							'html',
							'css',
							'css_precompiled',
							'css_compiled',
							'framework_css',
							'plugin',
							'modulo',
						))
						,
						"layouts",
						"WHERE ".$ids
						." AND language='".$_GESTOR['linguagem-codigo']."'"
					);
				}
			break;
			default:
				$layouts = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id',
						'html',
						'css',
						'css_precompiled',
						'css_compiled',
						'framework_css',
						'plugin',
						'modulo',
					))
					,
					"layouts",
					"WHERE id='".$id."'"
					." AND language='".$_GESTOR['linguagem-codigo']."'"
				);
		}
	}
	
	if(isset($return_array)){
		if($layouts){
			$return = Array();
			
			foreach($layouts as $layout){
				$id = $layout['id'];
				$modulo = $layout['modulo'];
				$plugin = $layout['plugin'];
				$framework_css = $layout['framework_css'];

				$_GESTOR['layout#framework_css'] = $framework_css;

				if($_GESTOR['development-env']){
					if(existe($modulo)){
						if(existe($plugin)){
							$html_path = $_GESTOR['plugins-path'].$plugin.'/modules/'.$modulo.'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.html';
							$css_path = $_GESTOR['plugins-path'].$plugin.'/modules/'.$modulo.'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.css';
							$css_precompiled_path = $_GESTOR['plugins-path'].$plugin.'/modules/'.$modulo.'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.precompiled.css';
						} else {
							$html_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.html';
							$css_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.css';
							$css_precompiled_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.precompiled.css';
						}
					} else {
						$html_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.html';
						$css_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.css';
						$css_precompiled_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.precompiled.css';
					}

					$html = (file_exists($html_path)) ? file_get_contents($html_path) : '';
					$css = (file_exists($css_path)) ? file_get_contents($css_path) : '';
					$css_precompiled = (file_exists($css_precompiled_path)) ? file_get_contents($css_precompiled_path) : ($layout['css_precompiled'] ?? '');
				} else {
					$html = $layout['html'];
					$css = $layout['css'];
					$css_precompiled = $layout['css_precompiled'] ?? '';
				}
				
				$css_compiled = $layout['css_compiled'];

				if(isset($return_css)){
					$return[$id] = Array(
						'html' => $html,
						'css' => $css,
						'css_precompiled' => $css_precompiled,
						'css_compiled' => $css_compiled,
						'framework_css' => $framework_css,
					);
				} else {
					gestor_pagina_recursos_incluir(Array(
						'css' => $css,
						'css_precompiled' => $css_precompiled,
						'css_compiled' => $css_compiled,
					));
					
					$return[$id] = Array(
						'html' => $html,
						'framework_css' => $framework_css,
					);
				}
			}
			
			return $return;
		} else {
			return Array();
		}
	} else {
		if($layouts){
			$id = $layouts[0]['id'];
			$modulo = $layouts[0]['modulo'];
			$plugin = $layouts[0]['plugin'];

			if($_GESTOR['development-env']){
				if(existe($modulo)){
					if(existe($plugin)){
						$html_path = $_GESTOR['plugins-path'].$plugin.'/modules/'.$modulo.'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.html';
						$css_path = $_GESTOR['plugins-path'].$plugin.'/modules/'.$modulo.'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.css';
						$css_precompiled_path = $_GESTOR['plugins-path'].$plugin.'/modules/'.$modulo.'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.precompiled.css';
					} else {
						$html_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.html';
						$css_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.css';
						$css_precompiled_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.precompiled.css';
					}
				} else {
					$html_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.html';
					$css_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.css';
					$css_precompiled_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/layouts/'.$id.'/'.$id.'.precompiled.css';
				}

				$html = (file_exists($html_path)) ? file_get_contents($html_path) : '';
				$css = (file_exists($css_path)) ? file_get_contents($css_path) : '';
				$css_precompiled = (file_exists($css_precompiled_path)) ? file_get_contents($css_precompiled_path) : ($layouts[0]['css_precompiled'] ?? '');
			} else {
				$html = $layouts[0]['html'];
				$css = $layouts[0]['css'];
				$css_precompiled = $layouts[0]['css_precompiled'] ?? '';
			}
			
			$css_compiled = $layouts[0]['css_compiled'];
			$framework_css = $layouts[0]['framework_css'];

			$_GESTOR['layout#framework_css'] = $framework_css;
			
			if(isset($return_css)){
				return Array(
					'html' => $html,
					'css' => $css,
					'css_precompiled' => $css_precompiled,
					'css_compiled' => $css_compiled,
					'framework_css' => $framework_css,
				);
			} else {
				gestor_pagina_recursos_incluir(Array(
					'css' => $css,
					'css_precompiled' => $css_precompiled,
					'css_compiled' => $css_compiled,
				));
				
				return $html;
			}
		} else {
			if(isset($return_css)){
				return Array(
					'html' => $layoutHTMLIfNoExists,
					'css' => '',
				);
			} else {
				return $layoutHTMLIfNoExists;
			}
		}
	}
}

/**
 * Inclui todas as bibliotecas do sistema.
 *
 * Carrega automaticamente todos os arquivos PHP da pasta bibliotecas,
 * exceto o próprio arquivo gestor.php para evitar recursão.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 * @return void
 */
function gestor_incluir_bibliotecas(){
	global $_GESTOR;
	
	$bibliotecas = $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['bibliotecas'];
	
	if($bibliotecas){
		foreach($bibliotecas as $biblioteca){
			$_GESTOR['bibliotecas-inseridas'][$biblioteca] = true;
			$caminhos = $_GESTOR['bibliotecas-dados'][$biblioteca];
			
			if($caminhos)
			foreach($caminhos as $caminho){
				require_once($_GESTOR['bibliotecas-path'].$caminho);
			}
		}
	}
}

/**
 * Inclui uma biblioteca específica do sistema.
 *
 * Carrega arquivo PHP da pasta bibliotecas usando require_once
 * para evitar inclusões duplicadas.
 *
 * @param string $biblioteca Nome do arquivo da biblioteca (sem .php).
 * @return void
 */
function gestor_incluir_biblioteca($biblioteca){
	global $_GESTOR;
	
	if(isset($biblioteca)){
		switch(gettype($biblioteca)){
			case 'array':
				foreach($biblioteca as $bi){
					if(isset($_GESTOR['bibliotecas-inseridas'][$bi])){
						continue;
					}
					
					$caminhos = $_GESTOR['bibliotecas-dados'][$bi];
					
					if($caminhos){
						$_GESTOR['bibliotecas-inseridas'][$bi] = true;
						
						foreach($caminhos as $caminho){
							require_once($_GESTOR['bibliotecas-path'].$caminho);
						}
					}
				}
			break;
			default:
				if(isset($_GESTOR['bibliotecas-inseridas'][$biblioteca])){
					return;
				}
				
				$caminhos = $_GESTOR['bibliotecas-dados'][$biblioteca];
				
				if($caminhos){
					$_GESTOR['bibliotecas-inseridas'][$biblioteca] = true;
					
					foreach($caminhos as $caminho){
						require_once($_GESTOR['bibliotecas-path'].$caminho);
					}
				}
		}
	}
}

/**
 * Obtém variáveis do sistema por módulo e idioma.
 *
 * Busca variáveis armazenadas no banco de dados com cache em memória.
 * Suporta busca individual ou conjunto completo de variáveis de um módulo.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['modulo'] Módulo do sistema (padrão: '_global_').
 * @param string $params['id'] Identificador único da variável (obrigatório se não usar 'conjunto').
 * @param bool $params['conjunto'] Se true, retorna todas as variáveis do módulo.
 * @param string $params['padrao'] Filtro de padrão regex para IDs (requer 'conjunto').
 * @param bool $params['reset'] Se true, força releitura do banco de dados.
 *
 * @return string|array Valor da variável, array de variáveis (se conjunto), ou string vazia.
 */
function gestor_variaveis($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// modulo - String - Opcional - Módulo do sistema do valor.
	// id - String - Obrigatório - Identificador único do valor.
	// conjunto - Bool - Opcional - Se definido retornar todos os valores do módulo.
	// padrao - String - Opcional - Só funciona se conjunto for definido. Se informado filtrar com esse valor que contêm nos ids das linguagens.
	// reset - Bool - Opcional - Reler banco de dados.
	
	// ===== 
	
	// ===== Procedimentos de inicialização
	
	if(!isset($_GESTOR['variaveis'])){
		$_GESTOR['variaveis'] = Array();
	}

	// ===== Definir módulo padrão se não informado para global.

	if(!isset($modulo)){
		$modulo = '_global_';
	}
	
	// ===== Buscar no banco de dados caso não tenha sido ainda lido na sessão.
	
	if(!isset($_GESTOR['variaveis'][$modulo]) || isset($reset)){
		$variaveis = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id',
				'valor',
			))
			,
			"variaveis",
			"WHERE language='".$_GESTOR['linguagem-codigo']."'"
			.($modulo == '_global_' ? " AND modulo IS NULL" : " AND modulo='".$modulo."'")
		);
		
		if($variaveis){
			foreach($variaveis as $li){
				$_GESTOR['variaveis'][$modulo][$li['id']] = $li['valor'];
			}
		}
	}
	
	// ===== Se conjunto definido filtrar se existir padrao e retornar o conjunto, senão retornar valor pontual.
	
	if(isset($conjunto)){
		if(isset($_GESTOR['variaveis'][$modulo])){
			if(isset($padrao)){
				$linguagens_aux = $_GESTOR['variaveis'][$modulo];
				$linguagens = Array();
				
				foreach($linguagens_aux as $id_aux => $linguagem_aux){
					if(preg_match('/'.preg_quote($padrao).'/i', $id_aux) > 0){
						$linguagens[$id_aux] = $linguagem_aux;
					}
				}
				
				return $linguagens;
			} else {
				return $_GESTOR['variaveis'][$modulo];
			}
		} else {
			return Array();
		}
	} else {
		return (isset($_GESTOR['variaveis'][$modulo][$id]) ? $_GESTOR['variaveis'][$modulo][$id] : '' );
	}
}

/**
 * Obtém uma variável global específica do sistema.
 *
 * Busca rápida de variável global por ID com cache em memória.
 * Otimizado para variáveis frequentemente acessadas.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] Identificador único da variável (obrigatório).
 * @param bool $params['reset'] Se true, força releitura do banco de dados.
 *
 * @return string|null Valor da variável ou NULL se não encontrada.
 */
function gestor_variaveis_globais($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id - String - Obrigatório - Identificador único do valor.
	// reset - Bool - Opcional - Reler banco de dados.
	
	// ===== 
	
	// ===== Procedimentos de inicialização
	
	if(!isset($_GESTOR['variaveis'])){
		$_GESTOR['variaveis'] = Array();
	}
	
	// ===== Buscar no banco de dados caso não tenha sido ainda lido na sessão.
	
	if(!isset($_GESTOR['variaveis']['_global_'][$id]) || isset($reset)){
		$variaveis = banco_select_name
		(
			banco_campos_virgulas(Array(
				'valor',
			))
			,
			"variaveis",
			"WHERE language='".$_GESTOR['linguagem-codigo']."'"
			." AND id='".$id."'"
		);
		
		if($variaveis){
			$_GESTOR['variaveis']['_global_'][$id] = $variaveis[0]['valor'];
		}
	}
	
	// ===== Retornar valor pontual.

	return (isset($_GESTOR['variaveis']['_global_'][$id]) ? $_GESTOR['variaveis']['_global_'][$id] : NULL );
}

/**
 * Altera o valor de uma variável no banco de dados.
 *
 * Atualiza variável existente com suporte a diferentes tipos de dados.
 * Usado principalmente para configurações dinâmicas do sistema.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['modulo'] Módulo do sistema da variável (obrigatório).
 * @param string $params['id'] Identificador único da variável (obrigatório).
 * @param string $params['tipo'] Tipo da variável: 'bool' ou outros (obrigatório).
 * @param string|null $params['valor'] Valor que deverá ser alterado.
 * @param string $params['linguagem'] Código do idioma (padrão: idioma atual).
 *
 * @return void
 */
function gestor_variaveis_alterar($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// modulo - String - Obrigatório - Módulo do sistema da variável.
	// id - String - Obrigatório - Identificador único da variável.
	// tipo - String - Obrigatório - Tipo da variável.
	// valor - String|NULL - Opcional - Valor que deverá ser alterado.
	// linguagem - String - Opcional - Linguagem da variável que será alterada.
	
	// ===== 
	
	if(isset($modulo) && isset($id) && isset($tipo)){
		switch($tipo){
			case 'bool':
				banco_update
				(
					"valor=".($valor ? '1' : 'NULL'),
					"variaveis",
					"WHERE modulo='".$modulo."'"
					." AND id='".$id."'"
					." AND language='".(isset($linguagem) ? $linguagem : $_GESTOR['linguagem-codigo'])."'"
				);
			break;
			default:
				banco_update
				(
					"valor='".$valor."'",
					"variaveis",
					"WHERE modulo='".$modulo."'"
					." AND id='".$id."'"
					." AND language='".(isset($linguagem) ? $linguagem : $_GESTOR['linguagem-codigo'])."'"
				);
		}
	}
}

/**
 * Redireciona para a página raiz do módulo atual.
 *
 * Busca a página marcada como raiz no banco e redireciona.
 * Útil para retornar à página inicial do módulo após operações.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 * @return void
 */
function gestor_redirecionar_raiz(){
	global $_GESTOR;
	
	$paginas = banco_select_name
	(
		banco_campos_virgulas(Array(
			'caminho',
		))
		,
		"paginas",
		"WHERE modulo='".$_GESTOR['modulo']."'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
		." AND raiz IS NOT NULL"
	);
	
	if(isset($paginas)){
		gestor_redirecionar($paginas[0]['caminho']);
	} else {
		gestor_redirecionar('/');
	}
}

/**
 * Recarrega a URL atual.
 *
 * Redireciona para o caminho atual, útil para atualizar a página
 * após operações que modificam o estado.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 * @return void
 */
function gestor_reload_url(){
	global $_GESTOR;
	
	gestor_redirecionar($_GESTOR['caminho-total']);
}

/**
 * Remove uma variável específica da query string.
 *
 * Filtra uma query string removendo uma variável específica,
 * mantendo as demais.
 *
 * @param string $queryString Query string completa (formato: var1=val1&var2=val2).
 * @param string $removerVariavel Nome da variável a ser removida.
 * @return string Query string processada sem a variável removida.
 */
function gestor_querystring_remover_variavel($queryString,$removerVariavel = ''){
	if(existe($queryString) && existe($removerVariavel)){
		parse_str($queryString, $variaveis);
		
		$queryStringProcessed = '';
		
		if(isset($variaveis)){
			foreach($variaveis as $var => $valor){
				if($removerVariavel != $var){
					$queryStringProcessed .= (existe($queryStringProcessed) ? '&':'') . $var . '=' . $valor;
				}
			}
		}
		
		return $queryStringProcessed;
	} else {
		return '';
	}
}

/**
 * Obtém o valor de uma variável específica da query string.
 *
 * Extrai e retorna o valor de uma variável da query string.
 *
 * @param string $queryString Query string completa.
 * @param string $variavel Nome da variável a buscar.
 * @return string Valor da variável ou string vazia se não encontrada.
 */
function gestor_querystring_variavel($queryString,$variavel = ''){
	if(existe($queryString) && existe($variavel)){
		parse_str($queryString, $variaveis);
		
		$queryStringProcessed = '';
		
		if(isset($variaveis)){
			foreach($variaveis as $var => $valor){
				if($variavel == $var){
					return $valor;
				}
			}
		}
	}
	
	return '';
}

/**
 * Recupera a query string antes do envio do formulário via campo hidden.
 *
 * @param string $fieldName Nome do campo hidden enviado pelo formulário.
 * @param string $default Valor padrão se o campo não estiver definido.
 * @return string Query string enviada no formulário, sem o "?" inicial.
 */
function gestor_querystring_before_submit($fieldName = '_c2f_query_string_before_submit', $default = '') {
    if (isset($_REQUEST[$fieldName]) && $_REQUEST[$fieldName] !== '') {
        $queryString = $_REQUEST[$fieldName];

        if (substr($queryString, 0, 1) === '?') {
            $queryString = substr($queryString, 1);
        }

        return banco_escape_field($queryString);
    }

    return $default;
}

/**
 * Obtém a query string atual da requisição.
 *
 * Retorna a query string removendo parâmetros internos do gestor
 * (_gestor-caminho) e opcionalmente outras variáveis.
 *
 * @param string $removerVariavel Nome da variável adicional a remover (opcional).
 * @return string Query string processada.
 */
function gestor_querystring($removerVariavel = ''){
	if(!isset($_SERVER['QUERY_STRING'])){
		return '';
	}
	
	$queryString = preg_replace('/'.preg_quote('_gestor-caminho=').'[^&.]*&/i', '', $_SERVER['QUERY_STRING']);
	if(existe($queryString)) $queryString = preg_replace('/'.preg_quote('_gestor-caminho=').'.*/i', '', $queryString);
	
	if(existe($removerVariavel)){
		$queryString = gestor_querystring_remover_variavel($queryString,$removerVariavel);
	}
	
	return (existe($queryString) ? $queryString : '');
}

/**
 * Redireciona para um local específico.
 *
 * Realiza redirecionamento HTTP com suporte a:
 * - URLs internas (relativas à raiz do sistema)
 * - URLs externas (absolutas)
 * - Query strings personalizadas
 * - Alertas de sessão (mantidos após redirecionamento)
 * - Local armazenado em sessão
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param string|false $local Caminho de destino (false = usar sessão ou raiz).
 * @param string $queryString Query string adicional.
 * @param bool $externo Se true, trata como URL externa (não adiciona url-raiz).
 * @return void (executa exit após redirecionar)
 */
function gestor_redirecionar($local = false,$queryString = '',$externo = false){
	global $_GESTOR;
	
	if(!$externo){
		if($local){
			$local = $_GESTOR['url-raiz'] . ($local == '/' ?'':$local);
		} else {
			if(existe(gestor_sessao_variavel("redirecionar-local"))){
				$local = gestor_sessao_variavel("redirecionar-local");
				$local = $_GESTOR['url-raiz'] . ($local == '/' ?'':$local);
				gestor_sessao_variavel_del("redirecionar-local");
			} else {
				$local = $_GESTOR['url-raiz'];
			}
		}
		
		if(isset($_GESTOR['pagina-alerta'])){
			if($_GESTOR['pagina-alerta']){
				gestor_sessao_variavel("alerta",$_GESTOR['pagina-alerta']);
			}
		}
	}
	
	header("Location: ".$local.(existe($queryString) ? '?'.$queryString : ''));
	exit;
	
}

/**
 * Substitui variáveis globais em HTML.
 *
 * Processa template HTML substituindo variáveis delimitadas por marcadores
 * especiais. Suporta:
 * - Variáveis do módulo atual
 * - Variáveis de módulos extras
 * - Variáveis do sistema (pagina#titulo, pagina#url-raiz, etc.)
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['html'] HTML que será processado (obrigatório).
 *
 * @return string HTML com variáveis substituídas.
 */
function gestor_pagina_variaveis_globais($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// html - String - Obrigatório - Html que será trocada as variáveis globais.
	
	// ===== 
	
	$open = $_GESTOR['variavel-global']['open'];
	$close = $_GESTOR['variavel-global']['close'];
	
	// ====== 
	
	if(isset($html)){
		if(isset($_GESTOR['modulo-id'])){
			$pattern = "/".preg_quote($open)."(.+?)".preg_quote($close)."/i";
			preg_match_all($pattern, $html, $matches);
			
			if($matches)
			foreach($matches[1] as $match){
				$valor = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => $match));
				
				if(existe($valor)){
					$html = modelo_var_troca_tudo($html,$open.$match.$close,$valor);
				}
			}
		}
		
		// ===== Módulos extras que devem ser lidos e colocados as variáveis nas páginas.
		
		if(isset($_GESTOR['paginas-variaveis'])){
			$modulosExtra = $_GESTOR['paginas-variaveis'];
			foreach($modulosExtra as $modulo => $valor){
				$pattern = "/".preg_quote($open)."(.+?)".preg_quote($close)."/i";
				preg_match_all($pattern, $html, $matches);
				
				if($matches)
				foreach($matches[1] as $match){
					$valor = gestor_variaveis(Array('modulo' => $modulo,'id' => $match));
					
					if(existe($valor)){
						$html = modelo_var_troca_tudo($html,$open.$match.$close,$valor);
					}
				}
			}
		}
		
		// ===== Procurar variáveis globais restantes.
		
		$pattern = "/".preg_quote($open)."(.+?)".preg_quote($close)."/i";
		preg_match_all($pattern, $html, $matches);
		
		if($matches)
		foreach($matches[1] as $match){
			$valor = '';
			
			switch($match){
				case 'pagina#url-raiz': 					$valor = $_GESTOR['url-raiz']; break;
				case 'pagina#url-full-http': 				$valor = $_GESTOR['url-full-http']; break;
				case 'pagina#titulo': 						$valor = $_GESTOR['pagina#titulo']; break;
				case 'pagina#contato-url': 					$valor = $_GESTOR['pagina#contato-url']; break;
				case 'pagina#url-caminho': 		
					$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
					$caminho = rtrim($caminho,'/').'/';
	
					$valor = $caminho; 
				break;
			}
			
			if(existe($valor)){
				$html = modelo_var_troca_tudo($html,$open.$match.$close,$valor);
			}
		}
		
		return $html;
	} else {
		return '';
	}
}

/**
 * Inclui uma variável JavaScript global na página.
 *
 * Adiciona variável ao array de variáveis JS que serão renderizadas
 * no HTML como objeto JavaScript acessível globalmente.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param string $variavel Nome da variável JavaScript.
 * @param mixed $valor Valor da variável (será convertido para JSON).
 * @return void
 */
function gestor_js_variavel_incluir($variavel,$valor){
	global $_GESTOR;

	if(!isset($variavel) || !isset($valor)){
		return;
	}

	// Se a variável já existe e ambos são arrays, faça merge
    if (isset($_GESTOR['javascript-vars'][$variavel]) && is_array($_GESTOR['javascript-vars'][$variavel]) && is_array($valor)) {
        $_GESTOR['javascript-vars'][$variavel] = array_merge_recursive($_GESTOR['javascript-vars'][$variavel], $valor);
    } else {
        // Caso contrário, sobrescreve normalmente
        $_GESTOR['javascript-vars'][$variavel] = $valor;
    }
}


/**
 * Marca componentes para inclusão na página.
 *
 * Adiciona componente(s) à lista de componentes que serão renderizados.
 * Suporta inclusão individual ou em lote via array.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['id'] ID do componente individual.
 * @param array $params['componentes'] Array de IDs de componentes.
 * @return void
 */
function gestor_componentes_incluir($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id - String - Opcional - ID do componente que será incluso no gestor.
	// componentes - Array - Opcional - IDs dos componentes que serão incluidos no gestor.
	
	// ===== 
	
	if(isset($componentes)){
		switch(gettype($componentes)){
			case 'array':
				if(count($componentes) > 0){
					foreach($componentes as $com){
						$_GESTOR['componentes'][$com] = true;
					}
				}
			break;
		}
	}

	if(isset($id)){
		$_GESTOR['componentes'][$id] = true;
	}
}


/**
 * Renderiza componentes marcados na página.
 *
 * Processa todos os componentes marcados para inclusão e adiciona
 * seu HTML ao conteúdo da página.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param array|false $params Parâmetros da função (atualmente não utilizados).
 * @return void
 */
function gestor_componentes_incluir_pagina($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	
	// ===== 
	
	if(isset($_GESTOR['componentes'])){
		$componentes = $_GESTOR['componentes'];
		
		foreach($componentes as $componente => $valor){
			if(!$valor) continue;
			
			$componente_html = gestor_componente(Array(
				'id' => $componente,
			));
			
			if(existe($componente_html)){
				$_GESTOR['pagina'] .= $componente_html;
			}
		}
		
	}
}

// =========================== Funções de Sessões e Cookies

/**
 * Inicia uma nova sessão ou recupera sessão existente.
 *
 * Cria cookie seguro com ID de sessão usando:
 * - HttpOnly (proteção contra XSS)
 * - Secure (apenas HTTPS)
 * - SameSite=Lax (proteção CSRF)
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 * @global array $_CONFIG Configurações do sistema.
 * @return void
 */
function gestor_sessao_iniciar(){
	global $_GESTOR;
	global $_CONFIG;
	
	if(!isset($_COOKIE[$_CONFIG['session-authname']])){
		gestor_incluir_biblioteca('seguranca');
		$sessionId = seguranca_token_aleatorio(32);
		
		setcookie($_CONFIG['session-authname'], $sessionId, [
			'expires' => time() + $_CONFIG['session-lifetime'],
			'path' => '/',
			'domain' => $_SERVER['SERVER_NAME'],
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		
		$_GESTOR['session-id'] = $sessionId;
	} else {
		$_GESTOR['session-id'] = $_COOKIE[$_CONFIG['session-authname']];
	}
}

/**
 * Obtém ou cria o ID numérico da sessão no banco de dados.
 *
 * Busca sessão existente pelo ID do cookie ou cria nova entrada.
 * Realiza limpeza aleatória de sessões expiradas (1/50 requisições).
 * Atualiza timestamp de acesso automaticamente.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 * @global array $_CONFIG Configurações do sistema.
 * @return int ID numérico da sessão no banco de dados.
 */
function gestor_sessao_id(){
	global $_GESTOR;
	global $_CONFIG;
	
	$sessoes = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_sessoes',
		))
		,
		"sessoes",
		"WHERE id='".banco_escape_field($_GESTOR['session-id'])."'"
	);
	
	if($sessoes){
		$id_sessoes = $sessoes[0]['id_sessoes'];
		
		// ===== Caso não tenha sido acessado ainda, atualizar o tempo de acesso.
		
		if(!isset($_GESTOR['session-accessed'])){
			banco_update
			(
				"acesso='".time()."'",
				"sessoes",
				"WHERE id_sessoes='".$id_sessoes."'"
			);
			
			$_GESTOR['session-accessed'] = true;
		}
	} else {
		// ===== Senão existir, criar nova sessão no banco.
		
		$campos = null; $campo_sem_aspas_simples = null;
		
		$campo_nome = "id"; $campo_valor = banco_escape_field($_GESTOR['session-id']); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "acesso"; $campo_valor = time(); 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"sessoes"
		);
		
		$id_sessoes = banco_last_id();
	}
	
	// =====  Remover sessões antigas aleatoriamente para não fazer isso toda vez.
	
	if(!isset($_GESTOR['session-accessed-clean'])){
		if(rand(0,50) == 0){
			$res = banco_query(
				"DELETE sess,sess_v 
				FROM sessoes AS sess 
					LEFT JOIN sessoes_variaveis AS sess_v 
						ON sess.id_sessoes=sess_v.id_sessoes 
				WHERE sess.acesso + ".$_CONFIG['session-lifetime']." < ".time()
				);
		}
		
		$_GESTOR['session-accessed-clean'] = true;
	}
	
	return $id_sessoes;
}

/**
 * Deleta a sessão atual do usuário.
 *
 * Remove todos os dados da sessão:
 * - Variáveis de sessão do banco
 * - Registro de sessão do banco
 * - Cookie do navegador
 *
 * @global array $_CONFIG Configurações do sistema.
 * @return void
 */
function gestor_sessao_del(){
	global $_CONFIG;
	
	$id_sessoes = gestor_sessao_id();
	
	$sessoes_variaveis = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_sessoes_variaveis',
		))
		,
		"sessoes_variaveis",
		"WHERE id_sessoes='".$id_sessoes."'"
	);
	
	if($sessoes_variaveis){
		banco_delete
		(
			"sessoes_variaveis",
			"WHERE id_sessoes='".$id_sessoes."'"
		);
	}
	
	banco_delete
	(
		"sessoes",
		"WHERE id_sessoes='".$id_sessoes."'"
	);
	
	setcookie($_CONFIG['session-authname'], "", [
		'expires' => time() - 3600,
		'path' => '/',
		'domain' => $_SERVER['SERVER_NAME'],
		'secure' => true,
		'httponly' => true,
		'samesite' => 'Lax',
	]);
}

/**
 * Obtém ou define uma variável de sessão.
 *
 * Gerencia variáveis de sessão armazenadas no banco de dados.
 * Dados são serializados em JSON para suportar tipos complexos.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param string $variavel Nome da variável de sessão.
 * @param mixed $valor Valor a ser armazenado (NULL para apenas leitura).
 * @return mixed Valor da variável (em modo leitura) ou void (em modo escrita).
 */
function gestor_sessao_variavel($variavel,$valor = NULL){
	global $_GESTOR;
	
	$id_sessoes = gestor_sessao_id();
	
	if(isset($valor)){
		$sessoes_variaveis = banco_select_name
		(
			banco_campos_virgulas(Array(
				'valor',
			))
			,
			"sessoes_variaveis",
			"WHERE id_sessoes='".$id_sessoes."'"
			." AND variavel='".$variavel."'"
		);
		
		if($sessoes_variaveis){
			banco_update
			(
				"valor='".addslashes(json_encode($valor))."'",
				"sessoes_variaveis",
				"WHERE id_sessoes='".$id_sessoes."'"
				." AND variavel='".$variavel."'"
			);
		} else {
			$campos = null; $campo_sem_aspas_simples = null;
			
			$campo_nome = "id_sessoes"; $campo_valor = $id_sessoes; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "variavel"; $campo_valor = $variavel; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "valor"; $campo_valor = addslashes(json_encode($valor)); 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			banco_insert_name
			(
				$campos,
				"sessoes_variaveis"
			);
		}
	} else {
		$sessoes_variaveis = banco_select_name
		(
			banco_campos_virgulas(Array(
				'valor',
			))
			,
			"sessoes_variaveis",
			"WHERE id_sessoes='".$id_sessoes."'"
			." AND variavel='".$variavel."'"
		);
		
		if($sessoes_variaveis){
			return ($sessoes_variaveis[0]['valor'] ? json_decode($sessoes_variaveis[0]['valor'],true) : '');
		} else {
			return '';
		}
	}
}

/**
 * Remove uma variável específica da sessão.
 *
 * Deleta permanentemente uma variável de sessão do banco de dados.
 *
 * @global array $_GESTOR Sistema global de gerenciamento.
 *
 * @param string $variavel Nome da variável a ser removida.
 * @return void
 */
function gestor_sessao_variavel_del($variavel){
	global $_GESTOR;
	
	$id_sessoes = gestor_sessao_id();
	
	$sessoes_variaveis = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_sessoes_variaveis',
		))
		,
		"sessoes_variaveis",
		"WHERE id_sessoes='".$id_sessoes."'"
		." AND variavel='".$variavel."'"
	);
	
	if($sessoes_variaveis){
		banco_delete
		(
			"sessoes_variaveis",
			"WHERE id_sessoes_variaveis='".$sessoes_variaveis[0]['id_sessoes_variaveis']."'"
		);
	}
}

/**
 * Remove TODAS as sessões do sistema.
 *
 * **ATENÇÃO**: Deleta todas as sessões e variáveis de todos os usuários.
 * Usar apenas para manutenção ou reset completo do sistema.
 *
 * @return void
 */
function gestor_sessao_del_all(){
	banco_delete
	(
		"sessoes_variaveis",
		""
	);
	
	banco_delete
	(
		"sessoes",
		""
	);
}


/**
 * Pega os dados de um módulo.
 *
 * Lê o arquivo JSON do módulo e retorna os dados como array.
 * 
 * @param string $modulo_id ID do módulo.
 * @return array|null Dados do módulo ou null se não encontrado.
 */
function gestor_modulos_dados($modulo_id = ''){
	global $_GESTOR;

	// req-112: guarda de existência. Sem ela, um `modulo_id` inválido faz `file_get_contents`
	// emitir warning — que, em página pública, sai no meio do HTML.
	$caminho = $_GESTOR['modulos-path'] .$modulo_id. '/'.$modulo_id.'.json';
	if(!is_file($caminho)) return null;

	$modulo_dados = json_decode(file_get_contents($caminho), true);

	if($modulo_dados) {
		return $modulo_dados;
	} else {
		return null;
	}
}

?>
