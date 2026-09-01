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

// req-028: todos os geradores de tag <script>/<link> resolvem a URL por `recursos_url()`.
// O `config.php` já carrega a biblioteca no fluxo normal; o guard cobre as entradas que
// montam o $_GESTOR por conta própria (CLI de pipeline e bootstrap de testes).
if(!function_exists('recursos_url')){
	require_once(__DIR__ . DIRECTORY_SEPARATOR . 'recursos.php');
}

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
 * Resolve o framework CSS efetivo de uma requisição a partir do layout E da página (req-118).
 *
 * Uma página final é layout + página, e as DUAS carregam a coluna `framework_css`. A decisão nunca
 * pode sair de um lado só: uma página Tailwind servida por layout Fomantic continua precisando do
 * Fomantic (é o layout que desenha menu, topo e modais), e o inverso também vale.
 *
 * Função PURA — é ela que decide quais bibliotecas entram na página e qual variante de componente a
 * interface entrega; errar aqui deixa a tela sem CSS ou sem runtime, sem erro em lugar nenhum.
 *
 * Regras (as mesmas que `gestor_pagina_css()` já aplicava, agora em um lugar só):
 * - Fomantic entra quando qualquer um dos dois o declara OU quando NENHUM declara nada (legado).
 * - Tailwind entra quando qualquer um dos dois o declara.
 *
 * @param string|null $layoutFramework Valor de `layouts.framework_css`.
 * @param string|null $paginaFramework Valor de `paginas.framework_css`.
 *
 * @return array{fomantic:bool,tailwind:bool,modo:string} `modo` é `fomantic-ui`, `tailwindcss` ou
 *                                                        `hibrido` (os dois ativos ao mesmo tempo).
 */
function gestor_framework_css_resolver($layoutFramework = null, $paginaFramework = null){
	$layout = is_string($layoutFramework) ? trim($layoutFramework) : '';
	$pagina = is_string($paginaFramework) ? trim($paginaFramework) : '';

	$fomantic = ($layout === 'fomantic-ui' || $pagina === 'fomantic-ui' || ($layout === '' && $pagina === ''));
	$tailwind = ($layout === 'tailwindcss' || $pagina === 'tailwindcss');

	if($fomantic && $tailwind){
		$modo = 'hibrido';
	} else if($tailwind){
		$modo = 'tailwindcss';
	} else {
		$modo = 'fomantic-ui';
	}

	return Array(
		'fomantic' => $fomantic,
		'tailwind' => $tailwind,
		'modo' => $modo,
	);
}

/**
 * Atalho para a resolução de framework da requisição corrente.
 *
 * @return array{fomantic:bool,tailwind:bool,modo:string}
 */
function gestor_framework_css_atual(){
	global $_GESTOR;

	return gestor_framework_css_resolver(
		$_GESTOR['layout#framework_css'] ?? null,
		$_GESTOR['pagina#framework_css'] ?? null
	);
}

// =========================== Detecção de schema (req-119 / BATCH-122)
//
// O código do sistema é atualizado por ARQUIVOS e o schema por MIGRAÇÕES, e as duas coisas não
// chegam juntas em toda instalação. `atualizacoes-banco-de-dados.php` roda as migrações no deploy,
// mas há caminhos reais em que o código novo alcança um banco antigo:
//
//   - a migração falha (permissão, lock, timeout) e o restante do deploy prossegue;
//   - a atualização é só de arquivos (`Synchronize => Files`), sem tocar o banco;
//   - o operador usa `--skip-migrate`;
//   - a janela entre os arquivos chegarem e a migração terminar — qualquer requisição nesse
//     intervalo executa código novo contra schema velho.
//
// Nesses casos o desfecho aceitável é a funcionalidade nova NÃO APARECER, nunca um erro 500 numa
// tela que já funcionava. Estas duas funções são o gate para isso: memoizadas por requisição,
// silenciosas e sem exceção.

/**
 * Diz se uma tabela existe no banco corrente.
 *
 * `SHOW TABLES` é executado UMA vez por requisição e o resultado inteiro fica memoizado — verificar
 * tabela a tabela custaria uma ida ao banco por checagem, em código que roda no caminho de render.
 *
 * @param string $tabela Nome da tabela.
 *
 * @return bool False também quando o banco não pôde ser consultado (falha fechado: sem certeza de
 *               que o schema está pronto, a funcionalidade nova não é oferecida).
 */
function gestor_schema_tabela_existe($tabela){
	global $_GESTOR;

	$tabela = (string)$tabela;
	if($tabela === '') return false;

	if(!isset($_GESTOR['schema-tabelas'])){
		$lista = Array();

		try {
			if(function_exists('banco_tabelas_lista')){
				$resultado = banco_tabelas_lista();
				if(is_array($resultado)) $lista = $resultado;
			}
		} catch (\Throwable $e){
			// Banco indisponível não pode derrubar a página: o gate simplesmente nega.
			$lista = Array();
		}

		$_GESTOR['schema-tabelas'] = array_flip($lista);
	}

	return isset($_GESTOR['schema-tabelas'][$tabela]);
}

/**
 * Diz se um campo existe em uma tabela do banco corrente.
 *
 * A existência da TABELA é verificada primeiro: `SHOW COLUMNS` sobre tabela inexistente é um erro
 * de SQL, e a ideia aqui é justamente não produzir nenhum.
 *
 * @param string $campo Nome da coluna.
 * @param string $tabela Nome da tabela.
 *
 * @return bool
 */
function gestor_schema_campo_existe($campo, $tabela){
	global $_GESTOR;

	$campo = (string)$campo;
	$tabela = (string)$tabela;

	if($campo === '' || !gestor_schema_tabela_existe($tabela)) return false;

	$chave = $tabela.'.'.$campo;

	if(!isset($_GESTOR['schema-campos'])) $_GESTOR['schema-campos'] = Array();
	if(isset($_GESTOR['schema-campos'][$chave])) return $_GESTOR['schema-campos'][$chave];

	$existe = false;

	try {
		if(function_exists('banco_campo_existe')){
			$existe = (bool)banco_campo_existe($campo,$tabela);
		}
	} catch (\Throwable $e){
		$existe = false;
	}

	$_GESTOR['schema-campos'][$chave] = $existe;

	return $existe;
}

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
		// F2 do review de 2026-08-15: o descarte silencioso é o que impede o diagnóstico.
		//
		// `resource-precompiled` é o bucket de quem NÃO declarou papel — e é exatamente por aí que
		// entra um recurso escolhido em RUNTIME. O caso medido: a lista de templates de uma rota é
		// DADO, não código (`SELECT ... FROM templates WHERE target=... AND status='A'`), então um
		// template novo fica selecionável pelo operador, nasce fora do bundle declarado em build-time
		// e renderiza sem estilo. Sem este log, não há sinal nenhum de que isso aconteceu.
		//
		// Layout e dependências também são descartados aqui, mas por desenho: o bundle já os
		// compilou junto. Só o bucket anônimo é sintoma.
		if($buckets['resource-precompiled'] && function_exists('log_disco') && empty($GLOBALS['_GESTOR']['tailwind-bundle-descarte-logado'])){
			$GLOBALS['_GESTOR']['tailwind-bundle-descarte-logado'] = true;
			log_disco(
				'Bundle canônico descartou '.count($buckets['resource-precompiled']).' sidecar(es) sem papel declarado'
				.' na rota '.((string)($GLOBALS['_GESTOR']['caminho-total'] ?? '?')).'.'
				.' Recurso escolhido em runtime (ex.: template por target) precisa entrar em tailwind_dependencies,'
				.' senão renderiza sem estilo.',
				'tailwind'
			);
		}

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

			// req-117: o papel viaja no DOM. A Editbar roda o Tailwind Browser NA PÁGINA PÚBLICA e
			// precisa distinguir a folha que o runtime do Tailwind gera das folhas que o PHP emitiu.
			$_GESTOR['css'][] = '<style data-c2f-css-role="authored">'."\n";
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

			// req-117: este é o mais crítico dos papéis. O `css_compiled` gravado contém
			// `@layer utilities` — exatamente a assinatura pela qual a captura reconhece a saída do
			// Tailwind Browser. Sem a marca, a Editbar releria o próprio valor antigo como se fosse
			// a compilação nova e a página congelaria no CSS da edição anterior.
			$_GESTOR['css-compiled'][] = '<style data-c2f-css-role="compiled">'."\n";
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
 * Detecta o fallback terminal da rota de erro para impedir redirecionamento 404 -> 404.
 *
 * A pagina de erro normalmente e um recurso do banco. Se esse recurso ainda nao foi
 * sincronizado, redirecionar novamente para a mesma rota cria um loop HTTP infinito.
 *
 * @param int|string $codigo Codigo HTTP em processamento.
 * @param mixed $caminho Caminho atual recebido pelo roteador.
 * @return bool
 */
function gestor_roteador_erro_terminal($codigo, $caminho = ''){
	if((int)$codigo !== 404 || !is_string($caminho)) return false;

	return trim(strtolower(trim($caminho)), '/') === '404';
}

/**
 * Retorna o status HTTP que deve ser preservado ao renderizar uma pagina de erro existente.
 *
 * @param mixed $caminho Caminho da pagina encontrada pelo roteador.
 * @return int|null Status HTTP explicito ou null para paginas comuns.
 */
function gestor_roteador_pagina_status_http($caminho = ''){
	return gestor_roteador_erro_terminal(404, $caminho) ? 404 : null;
}

/**
 * Registra um redirecionamento 301 de um caminho liberado por uma página (F10 do review 2026-08-15).
 *
 * Antes daqui, `admin-paginas` e `publisher-pages` traziam o mesmo bloco duplicado, com dedup por
 * `WHERE caminho='…'` — sem página nem idioma. Duas consequências:
 *
 * 1. **Caminho reciclado aponta para a página errada.** Se `/promo/` foi liberado pela página X e
 *    depois assumido pela página Y, ao renomear Y o `if(!$ja_existe)` pulava a gravação e `/promo/`
 *    continuava redirecionando para X — silenciosamente, porque o único `log_disco` do bloco cobria
 *    outro ramo.
 * 2. **Só o primeiro idioma ganhava 301.** `paginas_301` não tem coluna `language` e o `caminho`
 *    gravado é agnóstico de idioma, então a linha do pt-br bloqueava a do en para a MESMA página.
 *
 * A dedup passa a ser por par (`caminho`, `id_paginas`): a repetição A → B → A → B continua sem
 * gerar linha nova, os dois idiomas convivem, e caminho reciclado gera uma linha por dona. Quem
 * desempata na leitura é `gestor_roteador_301()`, que prefere o registro mais recente que resolva
 * para uma página ativa no idioma corrente.
 *
 * @param int|string $id_paginas Id NUMÉRICO da página que liberou o caminho.
 * @param string $caminho Caminho antigo.
 * @return bool True quando uma linha foi inserida.
 */
function gestor_pagina_301_registrar($id_paginas, $caminho){
	$caminho = trim((string)$caminho);
	$id_paginas = (int)$id_paginas;

	if($caminho === '' || $id_paginas <= 0) return false;

	$ja_existe = banco_select_name
	(
		banco_campos_virgulas(Array('id_paginas_301')),
		"paginas_301",
		"WHERE caminho='".banco_escape_field($caminho)."'"
		." AND id_paginas='".$id_paginas."'"
	);

	if($ja_existe) return false;

	$campos = Array(
		Array('id_paginas', $id_paginas, null),
		Array('caminho', $caminho, null),
		Array('data_criacao', 'NOW()', true),
	);

	banco_insert_name($campos, "paginas_301");

	return true;
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

/**
 * Classes efetivamente usadas no markup (BATCH-144 / req-141).
 *
 * Base da auditoria de cobertura: comparar o que o HTML PEDE com o que o CSS entregue DEFINE é a
 * única medida direta de "a página chegou estilizada?". Foi assim que se mediu 47% das classes sem
 * CSS em `/artigos/teste-de-pagina/` — número que nenhum log do sistema mostrava.
 *
 * Função PURA (testável).
 *
 * @param string $html HTML da página ou do recurso.
 * @return array Lista de classes distintas, sem repetição.
 */
function gestor_css_classes_usadas($html){
	if(!is_string($html) || $html === '') return Array();

	if(!preg_match_all('/class\s*=\s*("|\')([^"\']*)\1/i', $html, $matches, PREG_SET_ORDER)) return Array();

	$classes = Array();
	foreach($matches as $match){
		foreach(preg_split('/\s+/', trim($match[2])) as $classe){
			if($classe === '') continue;
			// Marcadores de template (`@[[VAR]]@`, `[[item#x]]`, `{{x}}`) não são classes: entram no
			// HTML antes da substituição e contariam como descoberta falsa.
			if(strpos($classe, '[') !== false || strpos($classe, '{') !== false || strpos($classe, '@') !== false) continue;
			$classes[$classe] = true;
		}
	}

	return array_keys($classes);
}

/**
 * Classes definidas por uma folha de estilo (BATCH-144 / req-141).
 *
 * O Tailwind escapa os caracteres especiais do seletor (`.md\:flex`, `.w-1\/2`), então a comparação
 * com o HTML exige desescapar — sem isso toda utility com variante apareceria como descoberta.
 *
 * Função PURA (testável).
 *
 * @param string $css CSS concatenado das folhas entregues.
 * @return array Lista de classes distintas definidas.
 */
function gestor_css_classes_definidas($css){
	if(!is_string($css) || $css === '') return Array();

	if(!preg_match_all('/\.((?:[A-Za-z0-9_-]|\\\\.)+)/', $css, $matches)) return Array();

	$classes = Array();
	foreach($matches[1] as $bruto){
		$classe = preg_replace('/\\\\(.)/', '$1', $bruto);
		if($classe === '' || $classe === null) continue;
		$classes[$classe] = true;
	}

	return array_keys($classes);
}

/**
 * Classes que o HTML usa e nenhuma folha define (BATCH-144 / req-141).
 *
 * É o sintoma que o operador vê como "quebrou o layout": a classe existe no markup, o navegador não
 * encontra regra nenhuma para ela e o elemento cai no estilo padrão.
 *
 * Função PURA (testável).
 *
 * @param string $html HTML entregue.
 * @param string $css CSS entregue.
 * @return array Classes sem definição, em ordem estável.
 */
function gestor_css_classes_descobertas($html, $css){
	$usadas = gestor_css_classes_usadas($html);
	if(!$usadas) return Array();

	$definidas = array_flip(gestor_css_classes_definidas($css));

	// `group` e `peer` são MARCADORES de variante: existem para que `group-hover:*` e `peer-checked:*`
	// tenham um ancestral/irmão a que se referir, e o Tailwind não emite regra própria para elas. Sem
	// esta exceção, toda página que usa `group` sem nenhuma variante correspondente aparece como
	// defeituosa — ruído que faz a métrica acusar piora onde nada piorou.
	$marcadores = Array('group' => true, 'peer' => true);

	$descobertas = Array();
	foreach($usadas as $classe){
		if(isset($marcadores[$classe])) continue;
		if(!isset($definidas[$classe])) $descobertas[] = $classe;
	}

	sort($descobertas);

	return $descobertas;
}

/**
 * Classes de estilo embutidas em código PHP/JS (BATCH-144 / req-141).
 *
 * A norma do projeto é que PHP e JavaScript não carreguem HTML nem classe: o markup vive em
 * COMPONENTES, lidos pelo gestor e incluídos onde for preciso. Quando o código monta classe em
 * runtime (`classList.add('w-0','bg-slate-300')`), o compilador não a encontra pelo HTML — e a
 * saída era declarar o próprio arquivo em `tailwind_sources`, uma lista MANUAL que, esquecida,
 * derruba o estilo em silêncio. Medido no acervo: 40 recursos apoiados nessa declaração, todos do
 * `perfil-usuario`, o mesmo módulo que quebrava a cada atualização de sistema.
 *
 * Esta função encontra a dívida para que ela possa ser eliminada, em vez de administrada.
 *
 * Função PURA (testável).
 *
 * @param string $codigo Conteúdo de um arquivo PHP ou JS.
 * @return array Classes distintas encontradas, em ordem estável.
 */
function gestor_css_classes_em_codigo($codigo){
	if(!is_string($codigo) || $codigo === '') return Array();

	$classes = Array();

	// 1) `class="..."` literal dentro de string de código.
	if(preg_match_all('/class\s*=\s*\\\\?["\']([^"\']*)["\']/i', $codigo, $m)){
		foreach($m[1] as $lista){
			foreach(preg_split('/\s+/', trim($lista)) as $classe){
				if($classe !== '') $classes[$classe] = true;
			}
		}
	}

	// 2) `classList.add('a','b')` / `remove` / `toggle` — o padrão que o JS usa para estado visual.
	//    Em `toggle(classe, condicao)` só o PRIMEIRO argumento é classe: o segundo é a condição, e
	//    lê-lo como classe inventava nomes que não existem (`data-perfil-painel` veio daí).
	if(preg_match_all('/classList\.(add|remove|toggle)\s*\(([^)]*)\)/i', $codigo, $m, PREG_SET_ORDER)){
		foreach($m as $ocorrencia){
			$metodo = strtolower($ocorrencia[1]);
			if(!preg_match_all('/["\']([^"\']+)["\']/', $ocorrencia[2], $itens)) continue;

			$encontradas = ($metodo === 'toggle') ? array_slice($itens[1], 0, 1) : $itens[1];
			foreach($encontradas as $classe){
				$classe = trim($classe);
				if($classe !== '') $classes[$classe] = true;
			}
		}
	}

	// 3) `className = 'a b'` (atribuição direta).
	if(preg_match_all('/className\s*=\s*["\']([^"\']*)["\']/i', $codigo, $m)){
		foreach($m[1] as $lista){
			foreach(preg_split('/\s+/', trim($lista)) as $classe){
				if($classe !== '') $classes[$classe] = true;
			}
		}
	}

	$classes = array_keys($classes);
	sort($classes);

	return $classes;
}

/**
 * Assinatura de procedência do CSS derivado (BATCH-144 / req-141 / CR-002).
 *
 * `html` e `css` são AUTORIA; `css_precompiled` e `css_compiled` são DERIVADOS dela. O sistema
 * tratava os quatro como campos independentes, escritos por três produtores em momentos diferentes
 * (compilador offline, editor online e política de atualização do deploy) — e nenhum registrava de
 * QUE entrada o CSS tinha sido derivado. Sem isso é impossível saber se o conjunto é coerente, e o
 * runtime entrega HTML de uma origem com CSS de outra sem emitir erro nenhum.
 *
 * A assinatura fecha esse buraco: quem gera o derivado carimba as entradas que usou; quem consome
 * recalcula e compara. Divergiu, o derivado está STALE — e isso vira informação, não surpresa.
 *
 * O baseline entra no cálculo porque o `css_compiled` gravado é um DELTA contra a cascata vigente:
 * recompilar o layout muda o que o delta precisa conter, mesmo que o HTML da página não mude.
 *
 * Função PURA (testável): não toca em `$_GESTOR`, banco ou disco.
 *
 * @param array $params
 * @param string $params['html'] HTML autoral do recurso.
 * @param string $params['css'] CSS autoral do recurso.
 * @param string $params['baseline'] Cascata sob a qual o derivado foi gerado (CSS do layout).
 * @return string Assinatura versionada, ou string vazia quando não há autoria nenhuma.
 */
function gestor_css_procedencia_assinatura($params = false){
	$html = '';
	$css = '';
	$baseline = '';

	if(is_array($params)){
		$html = isset($params['html']) ? (string)$params['html'] : '';
		$css = isset($params['css']) ? (string)$params['css'] : '';
		$baseline = isset($params['baseline']) ? (string)$params['baseline'] : '';
	}

	// Recurso sem autoria nenhuma não tem o que assinar: devolver um hash aqui faria um registro
	// vazio parecer íntegro.
	if($html === '' && $css === '') return '';

	// O separador não pode ocorrer no conteúdo, senão mover bytes de um campo para o outro daria a
	// mesma assinatura (`ab`+`c` colidindo com `a`+`bc`).
	$partes = Array(sha1($html), sha1($css), sha1($baseline));

	// O prefixo de versão permite trocar o algoritmo depois sem confundir assinatura antiga com
	// divergência real: quem lê sabe que `v1` foi calculado por esta regra.
	return 'v1:'.sha1(implode("\x1f", $partes));
}

/**
 * Par `campo=valor` da procedência, pronto para entrar num INSERT/UPDATE de recurso (req-141).
 *
 * Todo módulo que grava recurso (`admin-paginas`, `admin-layouts`, `admin-componentes`,
 * `admin-templates`, `publisher-pages`) precisa carimbar a procedência do que gravou — senão o
 * recurso nasce sem assinatura, conta como stale para sempre e a auditoria nunca zera.
 *
 * Resolve o baseline sozinho a partir do layout, porque é essa a cascata sob a qual o CSS do recurso
 * vale: recompilar o layout depois passa a invalidar este carimbo, que é exatamente o sinal que
 * faltava quando "mexi no layout e a página ficou com o CSS antigo".
 *
 * Devolve string vazia quando não há o que assinar ou quando a coluna ainda não existe no schema —
 * assim o chamador pode concatenar sem checar nada.
 *
 * @param string $html HTML autoral que está sendo gravado.
 * @param string $css CSS autoral que está sendo gravado.
 * @param string $layout_id Layout do recurso; vazio para layouts (eles SÃO a base).
 * @param string $tabela Tabela de destino, para checar a coluna.
 * @return string Assinatura, ou string vazia.
 */
function gestor_css_procedencia_para_recurso($html, $css, $layout_id = '', $tabela = 'paginas'){
	global $_GESTOR;

	// `gestor_schema_campo_existe()` é a checagem canônica do core: faz cache por tabela.campo e
	// engole exceção de driver. Chamar `banco_campo_existe()` direto aqui repetiria um SHOW COLUMNS a
	// cada gravação e deixaria um erro de conexão derrubar o salvamento inteiro por causa do carimbo.
	if(!function_exists('gestor_schema_campo_existe') || !gestor_schema_campo_existe('css_source_hash', $tabela)) return '';

	$baseline = '';

	if(is_scalar($layout_id) && trim((string)$layout_id) !== ''){
		$layout = banco_select(Array(
			'unico' => true,
			'tabela' => 'layouts',
			'campos' => Array('css_precompiled'),
			'extra' => "WHERE id='".banco_escape_field((string)$layout_id)."'"
				.' AND language="'.$_GESTOR['linguagem-codigo'].'" AND status!="D"',
		));

		if($layout && is_array($layout) && isset($layout['css_precompiled'])){
			$baseline = (string)$layout['css_precompiled'];
		}
	}

	return gestor_css_procedencia_assinatura(Array(
		'html' => (string)$html,
		'css' => (string)$css,
		'baseline' => $baseline,
	));
}

/**
 * O CSS derivado corresponde à autoria vigente? (BATCH-144 / req-141)
 *
 * Assinatura AUSENTE devolve `false` deliberadamente: todo o acervo gravado antes desta mudança não
 * tem carimbo, e tratá-lo como íntegro esconderia exatamente o que este mecanismo existe para
 * revelar. Quem chama decide o que fazer com um stale (logar, regenerar, avisar) — aqui só se
 * responde a pergunta.
 *
 * Função PURA (testável).
 *
 * @param string $assinaturaGravada Valor da coluna `css_source_hash`.
 * @param array $params Mesmas entradas de gestor_css_procedencia_assinatura().
 * @return bool true quando o derivado corresponde à autoria.
 */
function gestor_css_procedencia_valida($assinaturaGravada, $params = false){
	$assinaturaGravada = trim((string)$assinaturaGravada);
	if($assinaturaGravada === '') return false;

	$atual = gestor_css_procedencia_assinatura($params);
	if($atual === '') return false;

	return hash_equals($atual, $assinaturaGravada);
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
					// F4 do review de 2026-08-15: sem papel declarado, o pré-compilado caía no default
					// `resource-precompiled` e ia para o FIM da ordem, depois das dependências e da
					// página. Como o CSS de layout carrega theme, base e Preflight, isso invertia a
					// cascata. (O review descreveu estes dois pontos como "includes de template"; eles
					// são de `gestor_layout()`, então o papel correto é `layout-precompiled`.)
					gestor_pagina_recursos_incluir(Array(
						'css' => $css,
						'css_precompiled' => $css_precompiled,
						'css_precompiled_role' => 'layout-precompiled',
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
				// F4 do review de 2026-08-15: mesmo motivo do bloco acima — este também é layout.
				gestor_pagina_recursos_incluir(Array(
					'css' => $css,
					'css_precompiled' => $css_precompiled,
					'css_precompiled_role' => 'layout-precompiled',
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

// =========================== req-125 (BATCH-127): vocabulário de rota e de ícone
//
// Funções PURAS, sem estado global, consumidas pelo bootstrap (`gestor/gestor.php`). Vivem
// aqui — e não junto de quem as chama — porque é o que as torna verificáveis por teste: o
// bootstrap termina em `gestor_start()` e não pode ser incluído por um caso de teste.

/**
 * Rotas públicas de identidade cujo formulário carrega um token CSRF de uso único.
 *
 * São as telas em que voltar pelo histórico é ativamente NOCIVO: o navegador restaura o formulário
 * do cache com o token já queimado e a submissão seguinte falha de novo, em laço. A lista vive
 * separada da de `sitemap.php` porque o critério é outro — lá é indexação, aqui é ciclo de token.
 *
 * @return array<int,string> Primeiros segmentos de caminho, sem barras.
 */
function gestor_csrf_rotas_identidade(){
	return Array(
		'signin',
		'signin-2fa',
		'signup',
		'forgot-password',
		'reset-password',
		'validate-user',
	);
}


/**
 * Destino de recarregamento limpo para a tela de erro de CSRF (req-125 / BATCH-127).
 *
 * Função pura: recebe o caminho da requisição que falhou, o referer e a raiz, e devolve a URL que
 * emite um token novo — ou string vazia quando não há como afirmar qual é a tela de origem.
 *
 * Duas fontes, nesta ordem. A PRIMEIRA é o próprio caminho da requisição: o POST de login vai para
 * `/signin/`, então quando ele falha o CSRF é o caminho ATUAL que nomeia a tela — não o referer, que
 * pode estar ausente por política de referrer. A segunda é o referer, para o caso de um POST que
 * saiu do login para outra rota.
 *
 * @param string      $caminhoTotal Caminho da requisição corrente, já sem o prefixo de idioma.
 * @param string|null $referer      Conteúdo de `HTTP_REFERER`, se houver.
 * @param string      $urlRaiz      Raiz do gestor, com barra final e com idioma quando houver.
 * @return string URL absoluta de destino ou '' quando indeterminado.
 */
function gestor_csrf_destino_recarregamento($caminhoTotal, $referer, $urlRaiz){
	$rotas = gestor_csrf_rotas_identidade();
	$urlRaiz = (string)$urlRaiz;

	// ===== 1) Caminho da requisição que falhou.

	$caminho = trim(strtolower((string)$caminhoTotal), '/');

	if($caminho !== ''){
		$primeiro = explode('/', $caminho);
		$primeiro = $primeiro[0];

		if(in_array($primeiro, $rotas, true)){
			return $urlRaiz.$primeiro.'/';
		}
	}

	// ===== 2) Referer.

	$referer = trim((string)$referer);

	if($referer === ''){
		return '';
	}

	$refererCaminho = parse_url($referer, PHP_URL_PATH);

	if(!is_string($refererCaminho) || $refererCaminho === ''){
		return '';
	}

	$segmentos = array_values(array_filter(explode('/', strtolower($refererCaminho)), 'strlen'));

	foreach($segmentos as $segmento){
		if(in_array($segmento, $rotas, true)){
			return $urlRaiz.$segmento.'/';
		}
	}

	return '';
}


/**
 * Um nome é endereçável no catálogo do Lucide? (req-125 / BATCH-127)
 *
 * O Lucide resolve o valor de `data-lucide` por `toPascalCase()`, que colapsa separadores: só um
 * identificador kebab-case de segmentos alfanuméricos chega a algum ícone. Qualquer outra coisa —
 * e em especial os nomes COMPOSTOS do Fomantic, que são várias classes separadas por espaço
 * (`comments outline`, `credit card outline`, `bottom right corner share`) — é garantidamente um
 * `icon name was not found` no console.
 *
 * A pergunta que esta função responde é de FORMA, não de existência: ela não sabe se `box` está no
 * bundle, sabe que `comments outline` não pode estar. É o suficiente para o que o warning custa, e
 * não cria uma segunda cópia do catálogo para manter sincronizada a cada versão do Lucide.
 *
 * @param string $nome Valor já resolvido do ícone.
 * @return bool
 */
function gestor_pagina_menu_icone_lucide_valido($nome){
	$nome = is_string($nome) ? trim($nome) : '';

	if($nome === ''){
		return false;
	}

	return (bool)preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $nome);
}


/**
 * Atributo `data-lucide` pronto para interpolação — ou string vazia (req-125 / BATCH-127).
 *
 * Devolver o atributo INTEIRO (e não só o valor) é o que permite omiti-lo: `createIcons()` varre
 * `[data-lucide]` pela presença do atributo, então `data-lucide=""` é processado e reclama igual.
 *
 * @param string $nome Valor já resolvido do ícone.
 * @return string `data-lucide="…"` ou ''.
 */
function gestor_pagina_menu_icone_lucide_atributo($nome){
	if(!gestor_pagina_menu_icone_lucide_valido($nome)){
		return '';
	}

	return 'data-lucide="'.htmlspecialchars(trim((string)$nome), ENT_QUOTES, 'UTF-8').'"';
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
/**
 * Determina se cookies devem ser emitidos com o atributo Secure.
 *
 * Regras (fail-secure / defesa em profundidade):
 *  1. HTTPS ativo (direto ou via reverse proxy) → true
 *  2. development-env === false (produção, padrão) → true
 *  3. HTTP puro + development-env === true → false (único cenário permissivo)
 *
 * @return bool
 */
function gestor_cookie_is_secure(): bool
{
	global $_GESTOR;

	// 1. HTTPS detectado (direto ou via reverse proxy)
	$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		|| (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
		|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

	if($isHttps){
		return true;
	}

	// 2. Modo produção (padrão) → sempre secure
	if(empty($_GESTOR['development-env'])){
		return true;
	}

	// 3. HTTP puro + modo desenvolvimento → permite cookie não-secure
	return false;
}

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
			'secure' => gestor_cookie_is_secure(),
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
		'secure' => gestor_cookie_is_secure(),
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

/**
 * Decide se o HTML entregue ao navegador deve sair higienizado (req-132).
 *
 * Tres estados, lidos de `HTML_SANITIZE` no `.env` e editaveis pela tela do `admin-environment`:
 *
 * - `auto` (padrao): liga em producao e desliga no ambiente de desenvolvimento. E o comportamento
 *   que quase sempre se quer — o visitante recebe a pagina limpa e quem desenvolve continua com o
 *   HTML legivel para inspecionar;
 * - `on`: liga sempre, inclusive no ambiente local. E assim que se CONFERE o resultado da limpeza
 *   antes de publicar, sem precisar subir nada;
 * - `off`: desliga sempre, inclusive em producao. Existe como escape para depurar um caso em que a
 *   propria limpeza seja suspeita.
 *
 * Valor desconhecido cai em `auto`: uma chave digitada errado no `.env` nao pode desligar em
 * silencio a limpeza de um site em producao.
 */
function gestor_pagina_higienizar_ativo(){
	global $_GESTOR;

	// req-111 / BATCH-138: os comentarios de widget e a formatacao original fazem parte do
	// contrato do Live Editor. Este bypass e absoluto, inclusive com HTML_SANITIZE=on.
	if(function_exists('gestor_dashboard_toolbar_ativo') && gestor_dashboard_toolbar_ativo()){
		return false;
	}

	$modo = strtolower(trim((string)($_ENV['HTML_SANITIZE'] ?? 'auto')));

	if($modo === 'on' || $modo === 'true' || $modo === '1') return true;
	if($modo === 'off' || $modo === 'false' || $modo === '0') return false;

	return empty($_GESTOR['development-env']);
}

/**
 * Remove do HTML o que so interessa a quem escreveu o codigo (req-132).
 *
 * O que sai: comentarios de HTML, comentarios de CSS dentro de `<style>` e a indentacao. Numa
 * pagina real do Photon isso e 36% do arquivo — 50 KB de 138 KB, em 140 blocos de comentario, boa
 * parte deles nota de engenharia interna descrevendo a estrutura do sistema para qualquer visitante
 * que abra o codigo-fonte.
 *
 * TRES COISAS SAO PRESERVADAS, E CADA UMA POR UM MOTIVO DIFERENTE:
 *
 * 1. `<pre>` e `<textarea>`: espaco em branco ali e CONTEUDO. Reindentar um bloco de codigo exibido
 *    na tela ou o texto dentro de um campo muda o que o usuario le e o que ele envia de volta.
 * 2. `<script>`: JavaScript nao se limpa com expressao regular. Um `//` dentro de uma string, uma
 *    expressao regular com `/*`, um template literal com quebras de linha — qualquer um deles vira
 *    codigo quebrado. O ganho nao paga o risco, e o JavaScript ja vai minificado por outro caminho.
 * 3. Comentarios condicionais (`<!--[if ...]>`): sao INSTRUCAO, nao comentario. Removidos, o
 *    conteudo que eles guardam passa a valer para todos os navegadores.
 *
 * A funcao e pura de proposito: recebe e devolve string, sem tocar em `$_GESTOR`. E o que torna
 * possivel testa-la caso a caso, que e a unica forma honesta de mexer em algo que reescreve TODA
 * pagina servida pelo sistema.
 */
function gestor_html_higienizar($html){
	$html = (string) $html;
	if($html === '') return $html;

	// ===== 1. Poe a salvo o que nao pode ser tocado.
	//
	// Os blocos saem do texto e voltam no fim, trocados por marcadores que nenhuma das expressoes
	// seguintes reconhece. Sem isso, seria preciso que cada expressao soubesse sozinha desviar de
	// `<pre>`, `<textarea>` e `<script>` — e bastaria uma esquecer para o defeito aparecer numa
	// pagina qualquer, muito depois.

	$protegidos = Array();
	$html = preg_replace_callback(
		'#<(pre|textarea|script)\b[^>]*>.*?</\1\s*>#is',
		function($m) use (&$protegidos){
			$indice = count($protegidos);

			// `<script>` de JavaScript tem o CONTEUDO higienizado antes de ser posto a salvo; `<pre>`
			// e `<textarea>` entram intactos. Um `<script type="application/json">` ou
			// `type="text/template"` NAO e JavaScript e passa direto: rodar o scanner nele estragaria
			// dado ou markup que so estao ali guardados.
			$bloco = $m[0];
			if(strtolower($m[1]) === 'script' && gestor_pagina_higienizar_js_ativo() && gestor_html_script_e_javascript($m[0])){
				$bloco = preg_replace_callback(
					'#(<script\b[^>]*>)(.*?)(</script\s*>)#is',
					function($p){ return $p[1].gestor_js_higienizar($p[2]).$p[3]; },
					$bloco
				);
			}

			$protegidos[$indice] = $bloco;
			return '<!--c2f:protegido:'.$indice.'-->';
		},
		$html
	);

	// ===== 2. Comentarios de HTML, menos os condicionais e os proprios marcadores.

	$html = preg_replace('/<!--(?!\[if)(?!<!)(?!c2f:protegido:)(?:(?!-->).)*-->/s', '', $html);

	// ===== 3. Comentarios de CSS, apenas dentro de `<style>`.
	//
	// Restrito ao `<style>` de proposito: `/* */` fora dele nao e comentario de CSS, e uma
	// expressao solta pelo documento inteiro acabaria comendo texto de conteudo.

	$html = preg_replace_callback(
		'#(<style\b[^>]*>)(.*?)(</style\s*>)#is',
		function($m){
			$css = preg_replace('#/\*(?:(?!\*/).)*\*/#s', '', $m[2]);
			// Indentacao do CSS: a quebra de linha fica, e e ela que separa as regras.
			$css = preg_replace('/\n[ \t]+/', "\n", $css);
			$css = preg_replace('/\n{2,}/', "\n", $css);
			return $m[1].$css.$m[3];
		},
		$html
	);

	// ===== 4. Indentacao do HTML.
	//
	// A quebra de linha PERMANECE, e isso nao e economia perdida — e correcao. Entre dois elementos
	// em linha, o espaco em branco e renderizado: juntar `<span>a</span>` e `<span>b</span>` faria
	// "ab" aparecer colado na tela. Trocar a indentacao inteira por um unico `\n` tira os bytes sem
	// mudar uma linha do que o visitante enxerga.

	$html = preg_replace('/\n[ \t]+/', "\n", $html);
	$html = preg_replace('/[ \t]+\n/', "\n", $html);
	$html = preg_replace('/\n{2,}/', "\n", $html);

	// ===== 5. Devolve o que estava a salvo.

	if(!empty($protegidos)){
		$html = preg_replace_callback(
			'/<!--c2f:protegido:(\d+)-->/',
			function($m) use ($protegidos){
				return isset($protegidos[(int)$m[1]]) ? $protegidos[(int)$m[1]] : '';
			},
			$html
		);
	}

	return $html;
}

/**
 * Remove comentarios e indentacao de JavaScript (req-132, 2a rodada).
 *
 * NAO E EXPRESSAO REGULAR, E UM SCANNER. A diferenca importa: em JavaScript, `//` e `/*` so sao
 * comentario em UM dos cinco contextos possiveis. Nos outros quatro sao conteudo:
 *
 *     var url  = 'http://exemplo';       // dentro de string simples
 *     var msg  = "diga barra-asterisco isso";  // dentro de string dupla
 *     var t    = `linha // nao comenta`; // dentro de template literal
 *     var re   = /[barra][asterisco]/;      // dentro de regex literal
 *
 * Uma expressao regular nao distingue esses casos, e o erro nao aparece no teste: aparece na
 * pagina de alguem, como JavaScript truncado no meio. Por isso o texto e percorrido caractere a
 * caractere, com estado.
 *
 * O CASO DIFICIL E A BARRA. `/` pode iniciar um regex literal ou ser divisao, e so o token
 * anterior decide: depois de identificador, numero, `)` ou `]` e divisao; em qualquer outro lugar
 * e regex. E a mesma heuristica que os minificadores usam.
 *
 * A QUEBRA DE LINHA PERMANECE, e aqui ela nao e cosmetica: JavaScript insere ponto e virgula
 * automaticamente (ASI). Juntar duas linhas de codigo que dependem disso muda o programa. So a
 * indentacao sai.
 */
function gestor_js_higienizar($js){
	$js = (string) $js;
	if($js === '') return $js;

	$saida = '';
	$total = strlen($js);
	$i = 0;

	// Ultimo caractere significativo ja emitido: e ele que decide se `/` abre regex ou divide.
	$anterior = '';

	while($i < $total){
		$c = $js[$i];
		$prox = ($i + 1 < $total) ? $js[$i + 1] : '';

		// ----- Comentario de linha: some, mas a quebra fica (ASI).
		if($c === '/' && $prox === '/'){
			while($i < $total && $js[$i] !== "\n") $i++;
			continue;
		}

		// ----- Comentario de bloco: some inteiro.
		if($c === '/' && $prox === '*'){
			$fim = strpos($js, '*/', $i + 2);
			// Bloco nao fechado: o resto do arquivo era comentario. Sair aqui evita copiar lixo.
			if($fim === false) break;
			// Se o comentario tinha quebras de linha, uma precisa sobreviver: sem ela, duas
			// instrucoes separadas apenas pelo comentario ficariam na mesma linha e a ASI mudaria.
			if(strpos(substr($js, $i, $fim - $i), "\n") !== false) $saida .= "\n";
			$i = $fim + 2;
			continue;
		}

		// ----- Strings e template literals: copiados como estao, respeitando escape.
		if($c === "'" || $c === '"' || $c === '`'){
			$aspas = $c;
			$saida .= $c;
			$i++;
			while($i < $total){
				$d = $js[$i];
				$saida .= $d;
				if($d === '\\' && $i + 1 < $total){
					$saida .= $js[$i + 1];
					$i += 2;
					continue;
				}
				$i++;
				if($d === $aspas) break;
			}
			$anterior = $aspas;
			continue;
		}

		// ----- Regex literal.
		if($c === '/' && gestor_js_barra_inicia_regex($anterior)){
			$saida .= $c;
			$i++;
			$dentroDeClasse = false;
			while($i < $total){
				$d = $js[$i];
				$saida .= $d;
				if($d === '\\' && $i + 1 < $total){
					$saida .= $js[$i + 1];
					$i += 2;
					continue;
				}
				$i++;
				// Dentro de `[...]` a barra nao fecha o literal: `/[/]/` e valido.
				if($d === '[') $dentroDeClasse = true;
				else if($d === ']') $dentroDeClasse = false;
				else if($d === '/' && !$dentroDeClasse) break;
			}
			$anterior = '/';
			continue;
		}

		// ----- Indentacao: apos quebra de linha, o espaco a esquerda nao significa nada.
		if($c === "\n"){
			$saida .= "\n";
			$i++;
			while($i < $total && ($js[$i] === ' ' || $js[$i] === "\t")) $i++;
			$anterior = "\n";
			continue;
		}

		$saida .= $c;
		if($c !== ' ' && $c !== "\t" && $c !== "\r") $anterior = $c;
		$i++;
	}

	// Linhas que ficaram vazias com a saida dos comentarios.
	$saida = preg_replace('/[ \t]+\n/', "\n", $saida);
	$saida = preg_replace('/\n{2,}/', "\n", $saida);

	return $saida;
}

/**
 * Decide se uma `/` abre um regex literal ou e o operador de divisao (req-132).
 *
 * Depois de um valor — identificador, numero, `)` ou `]` — a barra so pode ser divisao. Em
 * qualquer outra posicao (inicio, apos operador, virgula, abre-parenteses, `return`...) ela abre
 * um literal. Errar para o lado da divisao seria pior: o conteudo do regex passaria a ser lido
 * como codigo, e um `//` dentro dele apagaria o resto da linha.
 */
function gestor_js_barra_inicia_regex($anterior){
	if($anterior === '') return true;
	if($anterior === ')' || $anterior === ']') return false;
	// Identificador ou numero: `a / b`, `2 / 3`. Cobre tambem o fim de `foo.bar / 2`.
	if(preg_match('/[A-Za-z0-9_$]/', $anterior)) return false;
	return true;
}

/**
 * Gate proprio para a limpeza do JavaScript (req-132, 2a rodada).
 *
 * Separado do `HTML_SANITIZE` de proposito. Remover comentario de HTML e de CSS e reversivel na
 * pratica: no pior caso a pagina fica feia. Mexer em JavaScript e outra ordem de risco — um erro
 * derruba a tela inteira. Uma chave propria permite desligar SO essa parte diante de uma suspeita,
 * sem devolver os comentarios internos de HTML e CSS ao visitante.
 *
 * Segue a chave principal quando ausente: quem liga a limpeza espera que ela alcance tudo.
 */
function gestor_pagina_higienizar_js_ativo(){
	$modo = strtolower(trim((string)($_ENV['HTML_SANITIZE_JS'] ?? 'auto')));

	if($modo === 'off' || $modo === 'false' || $modo === '0') return false;
	if($modo === 'on' || $modo === 'true' || $modo === '1') return true;

	return true;
}

/**
 * Diz se a tag `<script>` carrega JavaScript de verdade (req-132, 2a rodada).
 *
 * `<script>` e usado tambem como DEPOSITO: `type="application/json"` guarda dado,
 * `type="text/template"` guarda markup, `type="text/x-handlebars"` guarda template. Nenhum deles e
 * JavaScript, e passar o scanner por cima estragaria o conteudo — um `//` dentro de uma URL no
 * JSON viraria comentario e o resto da linha desapareceria.
 *
 * Sem `type`, ou com os tipos que o HTML define como JavaScript, e codigo. `type="module"` tambem.
 * Na duvida, NAO processa: deixar um comentario na pagina custa bytes; corromper um bloco de dados
 * custa a funcionalidade.
 */
function gestor_html_script_e_javascript($tagCompleta){
	if(!preg_match('#<script\b([^>]*)>#i', (string) $tagCompleta, $m)) return false;

	// `src=` aponta para arquivo externo: nao ha conteudo inline a limpar.
	if(preg_match('/\bsrc\s*=/i', $m[1])) return false;

	if(!preg_match('/\btype\s*=\s*["\']?([^"\'\s>]+)/i', $m[1], $t)) return true;

	$tipo = strtolower(trim($t[1]));

	return in_array($tipo, array(
		'module',
		'text/javascript',
		'application/javascript',
		'text/ecmascript',
		'application/ecmascript',
	), true);
}

?>
