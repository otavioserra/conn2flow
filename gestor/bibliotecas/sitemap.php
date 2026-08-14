<?php

/**
 * Biblioteca de geração e sincronização do `sitemap.xml` (req-110 / BATCH-110, ajustada no req-112).
 *
 * O arquivo vive em `assets/` (req-112). Ficava na raiz, dependendo da regra
 * `RewriteCond %{SCRIPT_FILENAME} !-f` do `.htaccess` — que varia por instalação. Em `assets/` quem
 * entrega é o controlador de estáticos do próprio gestor (`arquivo-estatico.php`), que já resolve
 * qualquer extensão desconhecida contra `assets-path`; `https://dominio/sitemap.xml` funciona sem
 * depender de reescrita específica.
 *
 * Estratégia: geração completa quando o arquivo não existe (ou está corrompido) e atualização
 * INCREMENTAL quando existe. O incremental importa porque o CRUD de páginas é chamado uma vez por
 * edição: reescrever o sitemap inteiro a cada salvamento faria uma varredura de tabela por clique,
 * e num site grande isso aparece no tempo de resposta do painel.
 *
 * As funções de montagem/edição do XML são PURAS (string → string) para serem testáveis sem banco.
 */

// ===== Elegibilidade

/**
 * Decide se uma página entra no sitemap.
 *
 * Critério principal: a página tem de ser PÚBLICA (`sem_permissao`) e ativa. O painel administrativo
 * inteiro cai fora por exigir permissão.
 *
 * Excluídas ainda: rotas utilitárias do gestor (`cookies-is-mandatory`, erros, `_gestor-*`), rotas
 * públicas que não são conteúdo (ver `sitemap_caminho_nao_indexavel`) e páginas fora da janela de
 * publicação.
 *
 * req-112: o `tipo` deixou de ser critério — telas públicas de sistema (`/signin/`, `/signup/`,
 * `/forgot-password/`) passam a ser indexáveis.
 *
 * Função PURA (sem dependência de $_GESTOR) para ser testável isoladamente.
 *
 * @param array $pagina Linha da tabela `paginas`.
 * @param int|null $agora Timestamp de referência (padrão: `time()`), usado nos testes.
 * @return bool
 */
function sitemap_pagina_elegivel($pagina, $agora = null){
	if(!is_array($pagina)) return false;

	$agora = ($agora === null) ? time() : (int)$agora;

	// Status: só páginas ativas.
	if(isset($pagina['status']) && $pagina['status'] !== 'A') return false;

	// Permissão: `sem_permissao` verdadeiro significa "aberta ao público". É o critério PRINCIPAL —
	// o que exige login não pode ser indexado.
	if(empty($pagina['sem_permissao'])) return false;

	// req-112: o tipo deixou de ser critério. Antes só `tipo='pagina'` entrava, o que deixava de fora
	// telas públicas legítimas como `/signin/`, `/signup/` e `/forgot-password/`, gravadas como
	// `tipo='sistema'`. Como o painel administrativo inteiro exige permissão, a regra de permissão
	// acima já dá conta dele.

	$caminho = isset($pagina['caminho']) ? strtolower(trim((string)$pagina['caminho'])) : '';
	if($caminho === '') return false;

	// Rotas utilitárias do gestor (cookies-is-mandatory, erros, `_gestor-*`).
	if(function_exists('gestor_pagina_rota_sistema') && gestor_pagina_rota_sistema($caminho)){
		return false;
	}

	if(sitemap_caminho_nao_indexavel($caminho)) return false;

	// Janela de publicação (BATCH-075): fora dela a página responde 404.
	$inicio = $pagina['data_publicacao_inicio'] ?? null;
	if($inicio !== null && $inicio !== '' && $inicio !== '0000-00-00 00:00:00'){
		$ts = strtotime((string)$inicio);
		if($ts !== false && $ts > $agora) return false;
	}

	$fim = $pagina['data_publicacao_fim'] ?? null;
	if($fim !== null && $fim !== '' && $fim !== '0000-00-00 00:00:00'){
		$ts = strtotime((string)$fim);
		if($ts !== false && $ts < $agora) return false;
	}

	return true;
}

/**
 * Rotas públicas que NÃO são conteúdo indexável (req-112).
 *
 * Ao abrir o sitemap para páginas `tipo='sistema'`, um levantamento das páginas públicas do core
 * mostrou que a maioria delas não é tela de conteúdo: são callbacks de OAuth, processadores de
 * formulário, telas intermediárias de 2FA e páginas de confirmação. Indexar isso desperdiça
 * orçamento de rastreio e, no caso dos callbacks, expõe endpoint que só faz sentido dentro de um
 * fluxo com parâmetros. `/signin/`, `/signup/` e `/forgot-password/` — as que o intake nomeia —
 * continuam entrando normalmente.
 *
 * Função PURA para ser testável isoladamente.
 *
 * @param string $caminho Caminho da página, em minúsculas.
 * @return bool
 */
function sitemap_caminho_nao_indexavel($caminho){
	$caminho = trim(strtolower((string)$caminho), '/');
	if($caminho === '') return false;

	// Callbacks, processadores e telas intermediárias de fluxo.
	$exatos = Array(
		'oauth-callback',
		'oauth-authenticate',
		'oauth-authenticate-2fa',
		'social-login',
		'signin-2fa',
		'validate-user',
		'email-confirmation',
		'forms-submissions-process',
		'pagina-de-impressao',
		'instalacao-sucesso',
		'dashboard-site-toolbar',
	);

	if(in_array($caminho, $exatos, true)) return true;

	// Páginas de confirmação ("…-confirmation", "…/success"): são o destino de um POST, não uma
	// entrada de navegação — indexá-las leva o visitante a uma tela sem contexto.
	if(substr($caminho, -13) === '-confirmation') return true;
	if(substr($caminho, -8) === '/success') return true;

	// Área administrativa que porventura esteja marcada como pública (ex.: emissões de teste).
	$primeiro = explode('/', $caminho)[0];
	if(strpos($primeiro, 'admin-') === 0) return true;

	return false;
}

// ===== Montagem e edição do XML (funções puras)

/**
 * Monta o documento completo do sitemap a partir de uma lista de URLs.
 *
 * @param array $urls Lista de `['loc' => string, 'lastmod' => string|null]`.
 * @return string XML pronto para gravação.
 */
function sitemap_xml_montar($urls = Array()){
	$xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
	$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

	if(is_array($urls))
	foreach($urls as $url){
		$loc = trim((string)(is_array($url) ? ($url['loc'] ?? '') : $url));
		if($loc === '') continue;

		$xml .= "\t".'<url>'."\n";
		$xml .= "\t\t".'<loc>'.htmlspecialchars($loc, ENT_QUOTES, 'UTF-8').'</loc>'."\n";

		$lastmod = is_array($url) ? sitemap_data_w3c($url['lastmod'] ?? null) : null;
		if($lastmod) $xml .= "\t\t".'<lastmod>'.$lastmod.'</lastmod>'."\n";

		$xml .= "\t".'</url>'."\n";
	}

	$xml .= '</urlset>'."\n";

	return $xml;
}

/**
 * Converte uma data do banco para o formato W3C exigido pelo protocolo de sitemap.
 *
 * @param string|null $data
 * @return string|null `null` quando a data é inválida ou ausente (a tag é então omitida).
 */
function sitemap_data_w3c($data = null){
	if($data === null || $data === '' || $data === '0000-00-00 00:00:00') return null;

	$ts = is_numeric($data) ? (int)$data : strtotime((string)$data);
	if($ts === false || $ts <= 0) return null;

	return date('c', $ts);
}

/**
 * Insere ou atualiza a entrada de uma URL num sitemap existente (upsert incremental).
 *
 * Trabalha por manipulação de string, e não por DOM, para preservar byte a byte as entradas que não
 * mudaram — inclusive as que outra ferramenta (ou o operador) tenha acrescentado à mão.
 *
 * @param string $xml XML atual.
 * @param string $loc URL absoluta da página.
 * @param string|null $lastmod Data de modificação.
 * @return string XML atualizado.
 */
function sitemap_xml_upsert($xml, $loc, $lastmod = null){
	$loc = trim((string)$loc);
	if($loc === '') return (string)$xml;

	if(!is_string($xml) || strpos($xml, '<urlset') === false){
		return sitemap_xml_montar(Array(Array('loc' => $loc, 'lastmod' => $lastmod)));
	}

	$xml = sitemap_xml_remover($xml, $loc);

	$bloco = "\t".'<url>'."\n";
	$bloco .= "\t\t".'<loc>'.htmlspecialchars($loc, ENT_QUOTES, 'UTF-8').'</loc>'."\n";

	$data = sitemap_data_w3c($lastmod);
	if($data) $bloco .= "\t\t".'<lastmod>'.$data.'</lastmod>'."\n";

	$bloco .= "\t".'</url>'."\n";

	$posicao = strripos($xml, '</urlset>');
	if($posicao === false) return $xml;

	return substr($xml, 0, $posicao).$bloco.substr($xml, $posicao);
}

/**
 * Remove a entrada de uma URL do sitemap (página excluída, despublicada ou que virou privada).
 *
 * @param string $xml XML atual.
 * @param string $loc URL absoluta a remover.
 * @return string XML sem a entrada.
 */
function sitemap_xml_remover($xml, $loc){
	if(!is_string($xml) || $xml === '') return (string)$xml;

	$loc = trim((string)$loc);
	if($loc === '') return $xml;

	$escapado = htmlspecialchars($loc, ENT_QUOTES, 'UTF-8');

	// Casa o bloco <url>…</url> cujo <loc> seja exatamente esta URL (com ou sem escape de entidades).
	$padrao = '#[ \t]*<url>\s*.*?</url>\s*#is';

	$resultado = preg_replace_callback($padrao, function($match) use ($loc, $escapado){
		if(!preg_match('#<loc>\s*(.*?)\s*</loc>#is', $match[0], $m)) return $match[0];

		$atual = trim($m[1]);
		if($atual === $loc || $atual === $escapado || html_entity_decode($atual, ENT_QUOTES, 'UTF-8') === $loc){
			return '';
		}

		return $match[0];
	}, $xml);

	return ($resultado === null) ? $xml : $resultado;
}

// ===== Integração com o gestor

/**
 * Caminho absoluto do arquivo `sitemap.xml` na raiz pública.
 *
 * @return string
 */
function sitemap_caminho_arquivo(){
	global $_GESTOR;

	// req-112: o arquivo passou da raiz para `assets/`. Na raiz ele dependia da regra
	// `RewriteCond %{SCRIPT_FILENAME} !-f` do `.htaccess` — que varia por instalação e, em servidor
	// com o roteador mais restritivo, nunca chegava a servir o XML. Em `assets/` quem entrega é o
	// controlador de estáticos do próprio gestor, que é parte do core e vale em toda instalação.
	$raiz = $_GESTOR['assets-path'] ?? null;

	if(!$raiz){
		$base = $_GESTOR['ROOT_PATH'] ?? (rtrim(sys_get_temp_dir(), '/\\').DIRECTORY_SEPARATOR);
		$raiz = rtrim($base, '/\\').DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR;
	}

	$raiz = rtrim($raiz, '/\\').DIRECTORY_SEPARATOR;

	if(!is_dir($raiz)){
		@mkdir($raiz, 0775, true);
	}

	return $raiz.'sitemap.xml';
}

/**
 * URL pública absoluta de uma página, respeitando o prefixo de idioma quando não for o padrão.
 *
 * @param array $pagina Linha da tabela `paginas`.
 * @return string
 */
function sitemap_url_da_pagina($pagina){
	global $_GESTOR;

	$base = (string)($_GESTOR['url-full-http-sem-lang'] ?? $_GESTOR['url-full-http'] ?? '/');
	$base = rtrim($base, '/').'/';

	$caminho = ltrim((string)($pagina['caminho'] ?? ''), '/');
	$idioma = (string)($pagina['language'] ?? ($_GESTOR['linguagem-codigo'] ?? ''));
	$padrao = (string)($_GESTOR['linguagem-padrao'] ?? '');

	$prefixo = ($idioma !== '' && $padrao !== '' && $idioma !== $padrao) ? $idioma.'/' : '';

	// A raiz do site é gravada como '/'; a URL dela é a própria base.
	if($caminho === '/' || $caminho === '') return $base.$prefixo;

	return $base.$prefixo.$caminho;
}

/**
 * Grava o conteúdo do sitemap em disco.
 *
 * @param string $xml
 * @return bool
 */
function sitemap_gravar($xml){
	$arquivo = sitemap_caminho_arquivo();

	if(@file_put_contents($arquivo, $xml) === false){
		if(function_exists('log_disco')) log_disco('Falha ao gravar '.$arquivo, 'sitemap');
		return false;
	}

	@chmod($arquivo, 0664);

	return true;
}

/**
 * Regenera o `sitemap.xml` inteiro a partir das páginas públicas ativas.
 *
 * @return bool
 */
function sitemap_gerar_completo(){
	global $_GESTOR;

	$paginas = banco_select_name
	(
		banco_campos_virgulas(Array(
			'caminho',
			'language',
			'tipo',
			'status',
			'sem_permissao',
			'data_modificacao',
			'data_publicacao_inicio',
			'data_publicacao_fim',
		))
		,
		"paginas",
		// req-112: `tipo` saiu do filtro — telas públicas de sistema (`/signin/`, `/signup/`,
		// `/forgot-password/`) também entram. A triagem fina fica em `sitemap_pagina_elegivel()`.
		"WHERE status='A' AND sem_permissao=1"
		." ORDER BY caminho ASC"
	);

	$urls = Array();
	$vistas = Array();

	if($paginas)
	foreach($paginas as $pagina){
		if(!sitemap_pagina_elegivel($pagina)) continue;

		$loc = sitemap_url_da_pagina($pagina);
		if(isset($vistas[$loc])) continue;

		$vistas[$loc] = true;
		$urls[] = Array('loc' => $loc, 'lastmod' => $pagina['data_modificacao'] ?? null);
	}

	return sitemap_gravar(sitemap_xml_montar($urls));
}

/**
 * Sincroniza UMA página no sitemap, criando o arquivo do zero quando ele ainda não existe.
 *
 * @param array $pagina Linha (ou dados equivalentes) da página alterada.
 * @param bool $remover Força a remoção da entrada (página excluída).
 * @return bool
 */
function sitemap_sincronizar_pagina($pagina, $remover = false, $caminhoAntigo = null){
	$arquivo = sitemap_caminho_arquivo();

	// Arquivo ausente ou corrompido: gera tudo. Também cobre a primeira execução no projeto.
	if(!is_file($arquivo)) return sitemap_gerar_completo();

	$xml = @file_get_contents($arquivo);
	if($xml === false || strpos($xml, '<urlset') === false) return sitemap_gerar_completo();

	// req-112: quando o caminho MUDA, a URL antiga tem de sair ANTES de a nova entrar — senão o XML
	// fica com as duas e o buscador continua visitando um endereço que agora responde 301.
	if($caminhoAntigo !== null && trim((string)$caminhoAntigo) !== ''){
		$paginaAntiga = $pagina;
		$paginaAntiga['caminho'] = (string)$caminhoAntigo;

		$locAntiga = sitemap_url_da_pagina($paginaAntiga);
		$xml = sitemap_xml_remover($xml, $locAntiga);
	}

	$loc = sitemap_url_da_pagina($pagina);

	if($remover || !sitemap_pagina_elegivel($pagina)){
		$xml = sitemap_xml_remover($xml, $loc);
	} else {
		$xml = sitemap_xml_upsert($xml, $loc, $pagina['data_modificacao'] ?? date('Y-m-d H:i:s'));
	}

	return sitemap_gravar($xml);
}

/**
 * Recarrega os dados de uma página pelo identificador e sincroniza o sitemap.
 *
 * Usada pelos hooks do CRUD, onde só o `id` textual é conhecido com certeza.
 *
 * @param string $id Identificador textual da página.
 * @param bool $remover
 * @param string|null $caminhoAntigo Caminho anterior, quando o slug mudou (req-112).
 * @return bool
 */
function sitemap_sincronizar_por_id($id, $remover = false, $caminhoAntigo = null){
	global $_GESTOR;

	$id = (string)$id;
	if($id === '') return false;

	$paginas = banco_select_name
	(
		banco_campos_virgulas(Array(
			'caminho',
			'language',
			'tipo',
			'status',
			'sem_permissao',
			'data_modificacao',
			'data_publicacao_inicio',
			'data_publicacao_fim',
		))
		,
		"paginas",
		"WHERE id='".banco_escape_field($id)."'"
		." AND language='".($_GESTOR['linguagem-codigo'] ?? '')."'"
	);

	// Registro sumiu (exclusão definitiva): sem os dados não há como montar a URL — regenera tudo.
	if(!$paginas) return sitemap_gerar_completo();

	return sitemap_sincronizar_pagina($paginas[0], $remover, $caminhoAntigo);
}

?>
