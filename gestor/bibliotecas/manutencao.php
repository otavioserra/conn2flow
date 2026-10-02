<?php
/**
 * Modo de manutenção durante deploy — req-210 / BATCH-218.
 *
 * Enquanto um deploy troca arquivos e sincroniza o banco, uma requisição pode pegar o sistema pela
 * metade e responder 500. Em vez do erro, o visitante recebe uma tela dizendo que o sistema está
 * sendo atualizado, com nova tentativa automática para o mesmo endereço.
 *
 * Quem faz o deploy liga a manutenção no começo e desliga no fim: um arquivo `temp/maintenance.json`
 * no Gestor de destino. O `gestor.php` consulta esse arquivo antes de sessão, banco e roteador.
 *
 * - O arquivo tem validade (TTL). Deploy que morre sem desligar não deixa o site fora do ar: vencido
 *   o prazo, o arquivo é ignorado.
 * - Linha de comando e rotas `_api/` não são bloqueadas: é por elas que o próprio deploy acontece.
 * - Biblioteca PURA: não depende do Gestor nem do banco, porque roda antes de tudo e também no CLI.
 *   Por isso os textos da tela ficam aqui, e não nas variáveis do banco, que pode estar em migração.
 */

/** Validade padrão da manutenção (segundos). */
const MANUTENCAO_TTL = 900;

/** Segundos entre as novas tentativas automáticas da tela. */
const MANUTENCAO_RETRY = 8;

function manutencao_arquivo(string $base): string {
	return rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'maintenance.json';
}

/**
 * Liga a manutenção.
 *
 * @param string $base   Raiz do Gestor.
 * @param array  $dados  ['owner' => quem ligou, 'detail' => texto livre, 'message' => ['pt-br' => ..., 'en' => ...] (opcional)].
 * @param int    $ttl    Validade em segundos.
 * @return bool false quando o disco recusa.
 */
function manutencao_ligar(string $base, array $dados = [], int $ttl = MANUTENCAO_TTL): bool {
	$arquivo = manutencao_arquivo($base);
	$pasta = dirname($arquivo);
	if (!is_dir($pasta) && !@mkdir($pasta, 0775, true) && !is_dir($pasta)) return false;

	$agora = time();
	$conteudo = [
		'owner' => (string)($dados['owner'] ?? 'deploy'),
		'detail' => isset($dados['detail']) ? (string)$dados['detail'] : null,
		'started_at' => $agora,
		'expires_at' => $agora + max(30, $ttl),
	];
	if (isset($dados['message']) && is_array($dados['message'])) $conteudo['message'] = $dados['message'];

	return @file_put_contents($arquivo, json_encode($conteudo), LOCK_EX) !== false;
}

/** Desliga a manutenção. Arquivo que já não existe conta como desligado. */
function manutencao_desligar(string $base): bool {
	$arquivo = manutencao_arquivo($base);
	return !is_file($arquivo) || @unlink($arquivo);
}

/**
 * Estado da manutenção.
 *
 * @return array|null Conteúdo do arquivo quando a manutenção está ligada e dentro da validade; null caso contrário.
 */
function manutencao_estado(string $base, ?int $agora = null): ?array {
	$arquivo = manutencao_arquivo($base);
	if (!is_file($arquivo)) return null;
	$dados = json_decode((string)@file_get_contents($arquivo), true);
	// Arquivo ilegível ou sem validade não derruba o site: é ignorado.
	if (!is_array($dados) || (int)($dados['expires_at'] ?? 0) <= ($agora ?? time())) return null;
	return $dados;
}

/** A requisição fica fora do bloqueio? Linha de comando e rotas `_api/` são o caminho do próprio deploy. */
function manutencao_requisicao_isenta(string $sapi, string $uri): bool {
	if ($sapi === 'cli' || $sapi === 'phpdbg') return true;
	$caminho = (string)parse_url($uri, PHP_URL_PATH);
	return (bool)preg_match('#(^|/)_api(/|$)#', $caminho);
}

/** Idioma da tela: `/en/` no endereço, depois o cabeçalho do navegador; português como padrão. */
function manutencao_idioma(string $uri, string $acceptLanguage): string {
	$caminho = (string)parse_url($uri, PHP_URL_PATH);
	if (preg_match('#(^|/)en(/|$)#', $caminho)) return 'en';
	if (preg_match('#(^|/)pt-br(/|$)#i', $caminho)) return 'pt-br';
	$primeiro = strtolower(trim(explode(',', $acceptLanguage)[0]));
	if ($primeiro !== '' && strpos($primeiro, 'pt') !== 0 && strpos($primeiro, 'en') === 0) return 'en';
	return 'pt-br';
}

/** Textos da tela, por idioma. `message` do arquivo de manutenção substitui o texto principal. */
function manutencao_textos(string $idioma, ?array $estado = null): array {
	$textos = [
		'pt-br' => [
			'title' => 'Estamos atualizando o sistema',
			'text' => 'A atualização leva pouco tempo. Esta página vai abrir sozinha assim que terminar.',
			'retry' => 'Tentar novamente',
			'status' => 'Nova tentativa automática em %s s.',
			'checking' => 'Verificando...',
		],
		'en' => [
			'title' => 'We are updating the system',
			'text' => 'The update takes a short while. This page will open by itself as soon as it finishes.',
			'retry' => 'Try again',
			'status' => 'Automatic retry in %s s.',
			'checking' => 'Checking...',
		],
	];
	$t = $textos[$idioma] ?? $textos['pt-br'];
	$personalizado = $estado['message'][$idioma] ?? null;
	if (is_string($personalizado) && trim($personalizado) !== '') $t['text'] = trim($personalizado);
	return $t;
}

/**
 * Logo da tela, como `data:` URI. O projeto pode pôr a sua em `assets/manutencao/logo.(svg|png|webp)`;
 * sem ela, vale a logo do Conn2Flow. A imagem vai embutida na resposta: um endereço de arquivo
 * passaria pelo Gestor e receberia a própria tela de manutenção.
 *
 * @return string `data:` URI, ou vazio quando não há arquivo legível de até 150 KB.
 */
function manutencao_logo(string $base): string {
	$base = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
	$tipos = ['svg' => 'image/svg+xml', 'png' => 'image/png', 'webp' => 'image/webp'];
	$candidatos = [];
	foreach (array_keys($tipos) as $ext) $candidatos[] = $base . 'manutencao' . DIRECTORY_SEPARATOR . 'logo.' . $ext;
	$candidatos[] = $base . 'images' . DIRECTORY_SEPARATOR . 'Logomarca200.png';

	foreach ($candidatos as $arquivo) {
		if (!is_file($arquivo)) continue;
		$tamanho = (int)@filesize($arquivo);
		if ($tamanho <= 0 || $tamanho > 153600) continue;
		$conteudo = @file_get_contents($arquivo);
		if ($conteudo === false || $conteudo === '') continue;
		return 'data:' . $tipos[strtolower(pathinfo($arquivo, PATHINFO_EXTENSION))] . ';base64,' . base64_encode($conteudo);
	}
	return '';
}

/** A requisição espera JSON? (AJAX do painel e do site, `fetch` com `Accept: application/json`) */
function manutencao_espera_json(array $server, array $request): bool {
	if (!empty($request['ajax']) || !empty($request['ajaxOpcao'])) return true;
	if (strtolower((string)($server['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') return true;
	return stripos((string)($server['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
}

/**
 * Corpo JSON da resposta de manutenção. O front usa `title`, `text` e `retry` para montar o aviso
 * sem texto fixo no JavaScript.
 */
function manutencao_json(string $idioma, ?array $estado = null, string $logo = ''): string {
	$t = manutencao_textos($idioma, $estado);
	return (string)json_encode([
		'status' => 'maintenance',
		'maintenance' => true,
		'message' => $t['title'] . ' ' . $t['text'],
		'title' => $t['title'],
		'text' => $t['text'],
		'retry' => $t['retry'],
		'retry_after' => MANUTENCAO_RETRY,
		'logo' => $logo,
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Página da tela de manutenção. Sem dependência externa: nem CSS nem script do sistema, que podem
 * estar sendo trocados neste momento.
 *
 * A nova tentativa consulta o MESMO endereço. Quando ele deixa de responder 503, a página recarrega
 * nele. Formulário enviado por POST não é reenviado: a tela volta ao endereço por GET.
 */
function manutencao_html(string $idioma, string $destino, ?array $estado = null, string $logo = ''): string {
	$t = manutencao_textos($idioma, $estado);
	$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
	$intervalo = MANUTENCAO_RETRY;

	$script = '(function(){var alvo=document.getElementById("c2f-m-retry").getAttribute("href");'
		.'var s=document.getElementById("c2f-m-status");var modelo=s.getAttribute("data-modelo");var verificando=s.getAttribute("data-verificando");'
		.'var resta='.$intervalo.';'
		.'function tentar(){s.textContent=verificando;'
		.'fetch(alvo,{method:"GET",cache:"no-store",credentials:"same-origin",headers:{"X-C2F-Maintenance-Probe":"1"}})'
		.'.then(function(r){if(r.status!==503||!r.headers.get("X-C2F-Maintenance")){window.location.replace(alvo);return;}resta='.$intervalo.';})'
		.'.catch(function(){resta='.$intervalo.';});}'
		.'setInterval(function(){resta--;if(resta<=0){tentar();return;}s.textContent=modelo.replace("%s",resta);},1000);'
		.'s.textContent=modelo.replace("%s",resta);})();';

	return '<!DOCTYPE html>'."\n"
		.'<html lang="'.$e($idioma).'">'."\n"
		.'<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
		.'<meta name="robots" content="noindex"><title>'.$e($t['title']).'</title>'
		.'<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;'
		.'font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f6f7f9;color:#1b1c1d}'
		.'.c2f-box{max-width:520px;margin:16px;padding:32px;background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.12);text-align:center}'
		.'h1{font-size:20px;margin:16px 0 12px}p{margin:0 0 20px;line-height:1.5;color:#5b5f66}'
		.'a.c2f-btn{display:inline-block;padding:10px 22px;border-radius:6px;background:#2185d0;color:#fff;font-size:15px;text-decoration:none}'
		.'.c2f-status{margin:16px 0 0;font-size:13px;color:#7a7f87}'
		.'.c2f-logo{display:block;max-width:180px;max-height:80px;margin:0 auto 24px}'
		.'.c2f-spin{width:36px;height:36px;margin:0 auto;border:4px solid #d9dde3;border-top-color:#2185d0;border-radius:50%;animation:c2f-spin 1s linear infinite}'
		.'@keyframes c2f-spin{to{transform:rotate(360deg)}}@media (prefers-reduced-motion:reduce){.c2f-spin{animation:none}}'
		.'</style></head>'."\n"
		.'<body><div class="c2f-box" data-c2f-maintenance>'
		.($logo !== '' ? '<img class="c2f-logo" src="'.$e($logo).'" alt="">' : '')
		.'<div class="c2f-spin" aria-hidden="true"></div>'
		.'<h1>'.$e($t['title']).'</h1><p>'.$e($t['text']).'</p>'
		.'<a class="c2f-btn" id="c2f-m-retry" href="'.$e($destino).'">'.$e($t['retry']).'</a>'
		.'<p class="c2f-status" id="c2f-m-status" role="status" data-modelo="'.$e($t['status']).'" data-verificando="'.$e($t['checking']).'"></p>'
		.'</div><script>'.$script.'</script></body></html>';
}

/** Endereço da própria requisição, só caminho e consulta: é para onde a tela tenta voltar. */
function manutencao_destino(string $uri): string {
	// Um endereço que não começa por uma barra só (ou começa por `//`) não é desta origem.
	if ($uri === '' || $uri[0] !== '/' || strpos($uri, '//') === 0) return '/';
	return $uri;
}

/**
 * Responde a requisição com a tela de manutenção e encerra, se a manutenção estiver ligada.
 * Chamada pelo `gestor.php` antes de sessão, banco e roteador. Sem manutenção, não faz nada.
 */
function manutencao_verificar(string $base): void {
	$estado = manutencao_estado($base);
	if ($estado === null) return;

	$uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
	if (manutencao_requisicao_isenta(PHP_SAPI, $uri)) return;

	$idioma = manutencao_idioma($uri, (string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
	$logo = manutencao_logo($base);

	if (!headers_sent()) {
		http_response_code(503);
		header('Retry-After: ' . MANUTENCAO_RETRY);
		header('X-C2F-Maintenance: 1');
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header('X-Robots-Tag: noindex');
	}

	if (manutencao_espera_json($_SERVER, $_REQUEST)) {
		if (!headers_sent()) header('Content-Type: application/json; charset=UTF-8');
		echo manutencao_json($idioma, $estado, $logo);
	} else {
		if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');
		echo manutencao_html($idioma, manutencao_destino($uri), $estado, $logo);
	}
	exit;
}
