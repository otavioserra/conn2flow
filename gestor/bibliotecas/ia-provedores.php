<?php
/**
 * Provedores de IA (REQ-260): uma entrada e uma saída para todos.
 *
 * Cada provedor sabe montar o pedido HTTP e ler a resposta. Quem chama não conhece o formato de ninguém:
 *
 *   ia_provedor_gerar_texto($servidor, ['sistema' => ..., 'mensagens' => [['papel' => 'user', 'texto' => ...]]])
 *   ia_provedor_gerar_imagem($servidor, ['prompt' => ..., 'tamanho' => '1024x1024'])
 *
 * `$servidor` é o registro de `servidores_ia` com a chave já decifrada: `tipo`, `chave`, `url_base`, `modelo`,
 * `modelo_imagem`. Campo vazio usa o padrão do provedor.
 *
 * Provedores: `gemini`, `anthropic`, `openai` e `openai-compativel` (qualquer serviço que fale o formato da
 * OpenAI, inclusive modelo local, trocando o endereço base).
 *
 * A chave vai sempre em cabeçalho, nunca no endereço, e nunca entra em mensagem de erro.
 * Montar o pedido e ler a resposta são funções puras, testadas sem rede; só `ia_provedor_http()` fala com fora.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 */

global $_GESTOR;

$_GESTOR['biblioteca-ia-provedores'] = Array(
	'versao' => '1.0.0',
);

/** Provedores conhecidos: nome, endereço base padrão, modelos padrão e o que cada um faz. */
function ia_provedores(){
	return Array(
		'gemini' => Array(
			'nome' => 'Google Gemini',
			'url_base' => 'https://generativelanguage.googleapis.com/v1beta',
			'modelo' => 'models/gemini-3-flash-preview',
			'modelo_imagem' => 'models/gemini-2.5-flash-image',
			'imagem' => true,
			'url_base_obrigatoria' => false,
		),
		'anthropic' => Array(
			'nome' => 'Anthropic Claude',
			'url_base' => 'https://api.anthropic.com/v1',
			'modelo' => 'claude-sonnet-5-5',
			'modelo_imagem' => '',
			'imagem' => false,
			'url_base_obrigatoria' => false,
		),
		'openai' => Array(
			'nome' => 'OpenAI',
			'url_base' => 'https://api.openai.com/v1',
			'modelo' => 'gpt-4.1-mini',
			'modelo_imagem' => 'gpt-image-1',
			'imagem' => true,
			'url_base_obrigatoria' => false,
		),
		'openai-compativel' => Array(
			'nome' => 'Compatível com OpenAI',
			'url_base' => '',
			'modelo' => '',
			'modelo_imagem' => '',
			'imagem' => false,
			'url_base_obrigatoria' => true,
		),
	);
}

function ia_provedor_dados($tipo){
	$provedores = ia_provedores();

	return $provedores[(string)$tipo] ?? null;
}

/** Endereço base aceito: http(s), sem usuário, sem parâmetros; devolve sem a barra final, ou vazio. */
function ia_provedor_url_base_normalizar($valor){
	$valor = trim((string)$valor);
	if($valor === '' || strlen($valor) > 255) return '';
	if(!preg_match('#^https?://[A-Za-z0-9.\-]+(:\d{1,5})?(/[A-Za-z0-9._~\-/]*)?$#', $valor)) return '';

	return rtrim($valor, '/');
}

/** Nome de modelo aceito: letras, números e `. _ - : /`, até 150 caracteres; ou vazio. */
function ia_provedor_modelo_normalizar($valor){
	$valor = trim((string)$valor);

	return preg_match('#^[A-Za-z0-9][A-Za-z0-9._:/\-]{0,149}$#', $valor) ? $valor : '';
}

/** Servidor pronto para uso: tipo conferido, endereço e modelos resolvidos com os padrões do provedor. */
function ia_provedor_servidor($servidor){
	$tipo = (string)($servidor['tipo'] ?? '');
	$dados = ia_provedor_dados($tipo);
	if(!$dados) return Array('erro' => 'Tipo de servidor de IA não suportado.');
	$chave = (string)($servidor['chave'] ?? '');
	if($chave === '') return Array('erro' => 'Servidor de IA sem chave.');
	$base = ia_provedor_url_base_normalizar($servidor['url_base'] ?? '');
	if($base === '') $base = $dados['url_base'];
	if($base === '') return Array('erro' => 'Informe o endereço base do servidor de IA.');
	$modelo = ia_provedor_modelo_normalizar($servidor['modelo'] ?? '');
	$modeloImagem = ia_provedor_modelo_normalizar($servidor['modelo_imagem'] ?? '');

	return Array(
		'tipo' => $tipo,
		'chave' => $chave,
		'url_base' => $base,
		'modelo' => $modelo !== '' ? $modelo : $dados['modelo'],
		'modelo_imagem' => $modeloImagem !== '' ? $modeloImagem : $dados['modelo_imagem'],
		'imagem' => $dados['imagem'] || $modeloImagem !== '',
	);
}

/** Mensagens de uma conversa: só papéis `user` e `assistant`, com texto. */
function ia_provedor_mensagens($mensagens){
	$saida = Array();
	foreach(is_array($mensagens) ? $mensagens : Array() as $mensagem){
		$texto = (string)($mensagem['texto'] ?? '');
		if($texto === '') continue;
		$saida[] = Array('papel' => ($mensagem['papel'] ?? '') === 'assistant' ? 'assistant' : 'user', 'texto' => $texto);
	}

	return $saida;
}

// ===== Texto

/**
 * Pedido HTTP de texto. `$pedido`: `sistema` (instrução, opcional), `mensagens`, `modelo` (opcional, vence o do
 * servidor) e `max_tokens` (opcional). Devolve `url`, `cabecalhos` e `corpo`, ou `erro`.
 */
function ia_provedor_pedido_texto($servidor, $pedido){
	$s = ia_provedor_servidor($servidor);
	if(isset($s['erro'])) return $s;
	$mensagens = ia_provedor_mensagens($pedido['mensagens'] ?? null);
	if(!$mensagens) return Array('erro' => 'Pedido de IA sem mensagem.');
	$modelo = ia_provedor_modelo_normalizar($pedido['modelo'] ?? '');
	if($modelo === '') $modelo = $s['modelo'];
	if($modelo === '') return Array('erro' => 'Informe o modelo do servidor de IA.');
	$sistema = trim((string)($pedido['sistema'] ?? ''));
	$maximo = (int)($pedido['max_tokens'] ?? 0);

	switch($s['tipo']){
		case 'gemini':
			$corpo = Array('contents' => Array());
			foreach($mensagens as $m){
				$corpo['contents'][] = Array('role' => $m['papel'] === 'assistant' ? 'model' : 'user', 'parts' => Array(Array('text' => $m['texto'])));
			}
			if($sistema !== '') $corpo['systemInstruction'] = Array('parts' => Array(Array('text' => $sistema)));
			if($maximo > 0) $corpo['generationConfig'] = Array('maxOutputTokens' => $maximo);
			return Array(
				'url' => $s['url_base'].'/'.(strpos($modelo, '/') === false ? 'models/'.$modelo : $modelo).':generateContent',
				'cabecalhos' => Array('Content-Type: application/json', 'x-goog-api-key: '.$s['chave']),
				'corpo' => $corpo,
				'modelo' => $modelo,
			);
		case 'anthropic':
			$corpo = Array('model' => $modelo, 'max_tokens' => $maximo > 0 ? $maximo : 4096, 'messages' => Array());
			foreach($mensagens as $m){
				$corpo['messages'][] = Array('role' => $m['papel'], 'content' => $m['texto']);
			}
			if($sistema !== '') $corpo['system'] = $sistema;
			return Array(
				'url' => $s['url_base'].'/messages',
				'cabecalhos' => Array('Content-Type: application/json', 'x-api-key: '.$s['chave'], 'anthropic-version: 2023-06-01'),
				'corpo' => $corpo,
				'modelo' => $modelo,
			);
		default:
			$corpo = Array('model' => $modelo, 'messages' => Array());
			if($sistema !== '') $corpo['messages'][] = Array('role' => 'system', 'content' => $sistema);
			foreach($mensagens as $m){
				$corpo['messages'][] = Array('role' => $m['papel'], 'content' => $m['texto']);
			}
			// A OpenAI passou a pedir `max_completion_tokens`; os serviços compatíveis ainda usam `max_tokens`.
			if($maximo > 0) $corpo[$s['tipo'] === 'openai' ? 'max_completion_tokens' : 'max_tokens'] = $maximo;
			return Array(
				'url' => $s['url_base'].'/chat/completions',
				'cabecalhos' => Array('Content-Type: application/json', 'Authorization: Bearer '.$s['chave']),
				'corpo' => $corpo,
				'modelo' => $modelo,
			);
	}
}

/** Mensagem de erro que o provedor devolveu, sem nada do pedido. */
function ia_provedor_erro($http, $dados){
	$detalhe = '';
	if(is_array($dados)){
		$erro = $dados['error'] ?? null;
		if(is_array($erro)) $detalhe = (string)($erro['message'] ?? '');
		elseif(is_string($erro)) $detalhe = $erro;
		if($detalhe === '' && isset($dados['message']) && is_string($dados['message'])) $detalhe = $dados['message'];
	}
	// O provedor pode ecoar o pedido na mensagem: nada que pareça chave passa.
	$detalhe = preg_replace('/(sk-|AIza|key[-_=:])[A-Za-z0-9_\-]{8,}/', '***', mb_substr(trim($detalhe), 0, 300));

	return 'Erro do servidor de IA (HTTP '.(int)$http.')'.($detalhe !== '' ? ': '.$detalhe : '.');
}

/** Lê a resposta de texto de um provedor. Devolve `status`, e em caso de sucesso `texto` e os tokens. */
function ia_provedor_resposta_texto($tipo, $http, $dados){
	if((int)$http !== 200 || !is_array($dados)) return Array('status' => 'error', 'message' => ia_provedor_erro($http, $dados));
	$texto = ''; $entrada = null; $saida = null; $total = null;

	switch((string)$tipo){
		case 'gemini':
			foreach(($dados['candidates'][0]['content']['parts'] ?? Array()) as $parte){
				if(isset($parte['text'])) $texto .= $parte['text'];
			}
			$entrada = $dados['usageMetadata']['promptTokenCount'] ?? null;
			$saida = $dados['usageMetadata']['candidatesTokenCount'] ?? null;
			$total = $dados['usageMetadata']['totalTokenCount'] ?? null;
			break;
		case 'anthropic':
			foreach(($dados['content'] ?? Array()) as $bloco){
				if(($bloco['type'] ?? '') === 'text') $texto .= (string)($bloco['text'] ?? '');
			}
			$entrada = $dados['usage']['input_tokens'] ?? null;
			$saida = $dados['usage']['output_tokens'] ?? null;
			break;
		default:
			$conteudo = $dados['choices'][0]['message']['content'] ?? '';
			// Alguns serviços devolvem o conteúdo em partes.
			if(is_array($conteudo)){
				foreach($conteudo as $parte){
					if(is_array($parte) && isset($parte['text'])) $texto .= (string)$parte['text'];
				}
			} else {
				$texto = (string)$conteudo;
			}
			$entrada = $dados['usage']['prompt_tokens'] ?? null;
			$saida = $dados['usage']['completion_tokens'] ?? null;
			$total = $dados['usage']['total_tokens'] ?? null;
	}
	if($texto === '') return Array('status' => 'error', 'message' => 'O servidor de IA respondeu sem conteúdo.');
	if($total === null && $entrada !== null && $saida !== null) $total = (int)$entrada + (int)$saida;

	return Array('status' => 'success', 'texto' => $texto, 'tokens_entrada' => $entrada, 'tokens_saida' => $saida, 'tokens_total' => $total);
}

// ===== Imagem

/** Tamanhos de imagem aceitos. */
function ia_provedor_tamanhos_imagem(){
	return Array('1024x1024', '1536x1024', '1024x1536');
}

/** Pedido HTTP de imagem. `$pedido`: `prompt`, `tamanho` (opcional) e `modelo` (opcional). */
function ia_provedor_pedido_imagem($servidor, $pedido){
	$s = ia_provedor_servidor($servidor);
	if(isset($s['erro'])) return $s;
	$prompt = trim((string)($pedido['prompt'] ?? ''));
	if($prompt === '') return Array('erro' => 'Descreva a imagem.');
	$prompt = mb_substr($prompt, 0, 4000);
	$modelo = ia_provedor_modelo_normalizar($pedido['modelo'] ?? '');
	if($modelo === '') $modelo = $s['modelo_imagem'];
	if(!$s['imagem'] || $modelo === '') return Array('erro' => 'Este servidor de IA não gera imagem.');
	$tamanho = in_array((string)($pedido['tamanho'] ?? ''), ia_provedor_tamanhos_imagem(), true) ? (string)$pedido['tamanho'] : '1024x1024';

	if($s['tipo'] === 'gemini'){
		// O Gemini não recebe o tamanho em pixels: a proporção vai junto da descrição.
		$proporcao = Array('1024x1024' => 'square (1:1)', '1536x1024' => 'landscape (3:2)', '1024x1536' => 'portrait (2:3)');
		return Array(
			'url' => $s['url_base'].'/'.(strpos($modelo, '/') === false ? 'models/'.$modelo : $modelo).':generateContent',
			'cabecalhos' => Array('Content-Type: application/json', 'x-goog-api-key: '.$s['chave']),
			'corpo' => Array(
				'contents' => Array(Array('role' => 'user', 'parts' => Array(Array('text' => $prompt."\n\nAspect ratio: ".$proporcao[$tamanho].'.')))),
				'generationConfig' => Array('responseModalities' => Array('TEXT', 'IMAGE')),
			),
			'modelo' => $modelo,
		);
	}

	return Array(
		'url' => $s['url_base'].'/images/generations',
		'cabecalhos' => Array('Content-Type: application/json', 'Authorization: Bearer '.$s['chave']),
		'corpo' => Array('model' => $modelo, 'prompt' => $prompt, 'size' => $tamanho, 'n' => 1),
		'modelo' => $modelo,
	);
}

/** Lê a resposta de imagem. Em caso de sucesso devolve `imagens`: lista de `mime` e `base64`. */
function ia_provedor_resposta_imagem($tipo, $http, $dados){
	if((int)$http !== 200 || !is_array($dados)) return Array('status' => 'error', 'message' => ia_provedor_erro($http, $dados));
	$imagens = Array();
	$mimes = Array('image/png', 'image/jpeg', 'image/webp');

	if((string)$tipo === 'gemini'){
		foreach(($dados['candidates'][0]['content']['parts'] ?? Array()) as $parte){
			$embutido = $parte['inlineData'] ?? ($parte['inline_data'] ?? null);
			if(!is_array($embutido) || empty($embutido['data'])) continue;
			$mime = (string)($embutido['mimeType'] ?? ($embutido['mime_type'] ?? 'image/png'));
			if(in_array($mime, $mimes, true)) $imagens[] = Array('mime' => $mime, 'base64' => (string)$embutido['data']);
		}
	} else {
		foreach(($dados['data'] ?? Array()) as $item){
			if(!empty($item['b64_json'])) $imagens[] = Array('mime' => 'image/png', 'base64' => (string)$item['b64_json']);
		}
	}
	// Só entra o que é base64 de verdade.
	$imagens = array_values(array_filter($imagens, function($imagem){ return base64_decode($imagem['base64'], true) !== false; }));
	if(!$imagens) return Array('status' => 'error', 'message' => 'O servidor de IA não devolveu imagem.');

	return Array('status' => 'success', 'imagens' => $imagens);
}

// ===== Rede

/** Envia o pedido montado e devolve `http` e `dados` (JSON decodificado), ou `erro` de comunicação. */
function ia_provedor_http($pedido, $tempo = 120){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $pedido['url']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pedido['corpo'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	curl_setopt($ch, CURLOPT_HTTPHEADER, $pedido['cabecalhos']);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch, CURLOPT_TIMEOUT, (int)$tempo);
	$resposta = curl_exec($ch);
	$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$falha = curl_error($ch);
	curl_close($ch);
	if($falha) return Array('erro' => 'Erro na comunicação com o servidor de IA: '.$falha);
	$dados = json_decode((string)$resposta, true);

	return Array('http' => $http, 'dados' => is_array($dados) ? $dados : null);
}

/** Texto de ponta a ponta. Devolve o que `ia_provedor_resposta_texto()` devolve, mais `modelo`. */
function ia_provedor_gerar_texto($servidor, $pedido, $tempo = 120){
	$http = ia_provedor_pedido_texto($servidor, $pedido);
	if(isset($http['erro'])) return Array('status' => 'error', 'message' => $http['erro']);
	$retorno = ia_provedor_http($http, $tempo);
	if(isset($retorno['erro'])) return Array('status' => 'error', 'message' => $retorno['erro']);
	$resposta = ia_provedor_resposta_texto($servidor['tipo'] ?? '', $retorno['http'], $retorno['dados']);
	$resposta['modelo'] = $http['modelo'];
	$resposta['resposta_completa'] = $retorno['dados'];

	return $resposta;
}

/** Imagem de ponta a ponta. */
function ia_provedor_gerar_imagem($servidor, $pedido, $tempo = 180){
	$http = ia_provedor_pedido_imagem($servidor, $pedido);
	if(isset($http['erro'])) return Array('status' => 'error', 'message' => $http['erro']);
	$retorno = ia_provedor_http($http, $tempo);
	if(isset($retorno['erro'])) return Array('status' => 'error', 'message' => $retorno['erro']);
	$resposta = ia_provedor_resposta_imagem($servidor['tipo'] ?? '', $retorno['http'], $retorno['dados']);
	$resposta['modelo'] = $http['modelo'];

	return $resposta;
}

/**
 * Teste de conexão: um pedido curto de texto, sem limite de saída (modelo que raciocina gasta o limite antes
 * de responder e o teste falharia à toa). Devolve `ok` e, em falha, `mensagem`.
 */
function ia_provedor_testar($servidor){
	$resposta = ia_provedor_gerar_texto($servidor, Array(
		'mensagens' => Array(Array('papel' => 'user', 'texto' => 'Connection test. Reply with just: OK')),
	), 30);

	return $resposta['status'] === 'success' ? Array('ok' => true, 'modelo' => $resposta['modelo']) : Array('ok' => false, 'mensagem' => (string)($resposta['message'] ?? ''));
}

/**
 * Registro de `servidores_ia` pronto para os provedores: decifra a chave e junta endereço e modelos.
 * `$linha` precisa trazer `tipo` e `chave_api`; `url_base`, `modelo` e `modelo_imagem` quando existirem.
 */
function ia_provedor_servidor_do_banco($linha){
	global $_GESTOR;

	if(!is_array($linha) || empty($linha['chave_api'])) return null;
	gestor_incluir_biblioteca('autenticacao');
	$chavePublica = (string)@file_get_contents($_GESTOR['openssl-path'].'publica.key');
	$chave = autenticacao_decriptar_chave_publica(Array('criptografia' => $linha['chave_api'], 'chavePublica' => $chavePublica));

	return Array(
		'tipo' => (string)($linha['tipo'] ?? ''),
		'chave' => (string)$chave,
		'url_base' => (string)($linha['url_base'] ?? ''),
		'modelo' => (string)($linha['modelo'] ?? ''),
		'modelo_imagem' => (string)($linha['modelo_imagem'] ?? ''),
	);
}
