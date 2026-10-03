<?php
/**
 * Controles do painel (req-219 / BATCH-227): inclusão do runtime e ajudantes de HTML.
 *
 * O runtime (`assets/interface/controles.js` e `.css`) não depende de framework: entra em toda página do
 * painel (pela `interface_assets_incluir()`) e, quando o Fomantic não está na página, faz a ponte da API do
 * Fomantic que o core usa (`$.fn.dropdown`, `checkbox`, `tab`, `modal`, `dimmer`, `popup`).
 *
 * Os ajudantes geram HTML com classes Tailwind e atributos `data-c2f-*`; o JavaScript liga o comportamento.
 * Tudo o que vem de fora é escapado aqui.
 */

/** Textos dos controles para o JavaScript (variáveis globais `controles-*`). */
function controles_textos(){
	$textos = Array();
	foreach(Array('ok', 'cancelar', 'buscar', 'semResultado' => 'sem-resultado', 'carregando', 'fechar', 'selecione', 'confirmar', 'remover', 'atencao') as $chave => $id){
		if(is_int($chave)) $chave = $id;
		$valor = (string)gestor_variaveis(Array('id' => 'controles-' . $id));
		if($valor !== '') $textos[$chave] = $valor;
	}
	return $textos;
}

/** Enfileira o runtime e os textos uma vez por página. */
function controles_incluir(){
	global $_GESTOR;
	if(!empty($_GESTOR['controles-incluidos'])) return;
	$_GESTOR['controles-incluidos'] = true;
	$versao = $_GESTOR['biblioteca-interface']['versao'] ?? ($_GESTOR['versao'] ?? null);
	$_GESTOR['css'][] = recursos_tag_css('interface/controles.css', $versao);
	// Antes do runtime da interface: quem chama `$.fn.dropdown` no `ready` já encontra a ponte.
	if(!isset($_GESTOR['javascript']) || !is_array($_GESTOR['javascript'])) $_GESTOR['javascript'] = Array();
	array_unshift($_GESTOR['javascript'], recursos_tag_js('interface/controles.js', $versao));
	gestor_js_variavel_incluir('controlesTextos', controles_textos());
}

function controles_esc($valor){
	return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Select com busca (e AJAX, opcional) sobre um <select> nativo.
 *
 * @param array $params name, id, opcoes ([valor => rótulo] ou [['valor','rotulo','grupo']]), valor (string ou lista),
 *                      multiplo, busca, placeholder, desabilitado, obrigatorio, classe,
 *                      ajax (['opcao' => ajaxOpcao, 'url' => ..., 'minimo' => 2]).
 */
function controles_select(array $params){
	$nome = (string)($params['name'] ?? '');
	$multiplo = !empty($params['multiplo']);
	$valores = array_map('strval', (array)($params['valor'] ?? Array()));
	$atributos = Array('data-c2f-select' => '1', 'name' => $nome . ($multiplo && $nome !== '' && substr($nome, -2) !== '[]' ? '[]' : ''),
		'class' => trim('c2fc-campo-entrada ' . ($params['classe'] ?? '')));
	if(!empty($params['id'])) $atributos['id'] = (string)$params['id'];
	if($multiplo) $atributos['multiple'] = 'multiple';
	if(!empty($params['busca'])) $atributos['data-c2f-busca'] = '1';
	if(!empty($params['placeholder'])) $atributos['data-c2f-placeholder'] = (string)$params['placeholder'];
	if(!empty($params['desabilitado'])) $atributos['disabled'] = 'disabled';
	if(!empty($params['obrigatorio'])) $atributos['required'] = 'required';
	if(!empty($params['ajax']['opcao'])){
		$atributos['data-c2f-ajax-opcao'] = (string)$params['ajax']['opcao'];
		if(!empty($params['ajax']['url'])) $atributos['data-c2f-ajax-url'] = (string)$params['ajax']['url'];
		$atributos['data-c2f-ajax-minimo'] = (string)(int)($params['ajax']['minimo'] ?? 2);
	}
	foreach((array)($params['atributos'] ?? Array()) as $chave => $valor){
		if(preg_match('/^(data-[a-z0-9-]+|aria-[a-z]+)$/', (string)$chave)) $atributos[$chave] = (string)$valor;
	}
	$html = '<select';
	foreach($atributos as $chave => $valor) $html .= ' ' . $chave . '="' . controles_esc($valor) . '"';
	$html .= '>';
	if(!$multiplo) $html .= '<option value="">' . controles_esc($params['placeholder'] ?? '') . '</option>';
	$grupos = Array();
	foreach((array)($params['opcoes'] ?? Array()) as $chave => $opcao){
		if(is_array($opcao)){
			$valor = (string)($opcao['valor'] ?? $opcao['value'] ?? '');
			$rotulo = (string)($opcao['rotulo'] ?? $opcao['text'] ?? $valor);
			$grupo = (string)($opcao['grupo'] ?? '');
		} else {
			$valor = (string)$chave;
			$rotulo = (string)$opcao;
			$grupo = '';
		}
		$grupos[$grupo][] = '<option value="' . controles_esc($valor) . '"' . (in_array($valor, $valores, true) ? ' selected' : '') . '>' . controles_esc($rotulo) . '</option>';
	}
	foreach($grupos as $grupo => $itens){
		$html .= $grupo === '' ? implode('', $itens) : '<optgroup label="' . controles_esc($grupo) . '">' . implode('', $itens) . '</optgroup>';
	}
	return $html . '</select>';
}

/** Chave liga/desliga sobre um checkbox nativo (envia `1` quando ligada). */
function controles_chave(array $params){
	$nome = (string)($params['name'] ?? '');
	$html = '<label class="c2fc-chave">';
	if(!empty($params['oculto_quando_desligada'])) $html .= '<input type="hidden" name="' . controles_esc($nome) . '" value="0">';
	$html .= '<input type="checkbox" name="' . controles_esc($nome) . '" value="' . controles_esc($params['valor'] ?? '1') . '"'
		. (!empty($params['id']) ? ' id="' . controles_esc($params['id']) . '"' : '')
		. (!empty($params['marcado']) ? ' checked' : '') . (!empty($params['desabilitado']) ? ' disabled' : '') . '>'
		. '<span class="c2fc-chave-trilho" aria-hidden="true"></span>';
	if(isset($params['rotulo']) && $params['rotulo'] !== '') $html .= '<span class="c2fc-chave-rotulo">' . controles_esc($params['rotulo']) . '</span>';
	return $html . '</label>';
}

/**
 * Abas: [['id' => 'dados', 'rotulo' => '...', 'conteudo' => '<html já pronto>'], ...]. O conteúdo é HTML do
 * próprio sistema (não escapado); os rótulos são escapados.
 */
function controles_abas(array $abas, $ativa = null){
	$ativa = $ativa ?? ($abas[0]['id'] ?? '');
	$botoes = '';
	$paineis = '';
	foreach($abas as $aba){
		$id = preg_replace('/[^a-z0-9_-]/i', '', (string)($aba['id'] ?? ''));
		$botoes .= '<button type="button" data-c2f-aba="' . $id . '" aria-selected="' . ($id === $ativa ? 'true' : 'false') . '">' . controles_esc($aba['rotulo'] ?? $id) . '</button>';
		$paineis .= '<div data-c2f-painel="' . $id . '"' . ($id === $ativa ? '' : ' hidden') . '>' . ($aba['conteudo'] ?? '') . '</div>';
	}
	return '<div data-c2f-abas><div>' . $botoes . '</div>' . $paineis . '</div>';
}

// =========================== padrão de campos e botões (req-219)
//
// Todo campo de formulário do painel em Tailwind sai daqui: o mesmo invólucro (rótulo, ajuda, erro), as
// mesmas classes e a mesma acessibilidade (rótulo ligado ao campo, ajuda e erro em `aria-describedby`).

// Classes da própria biblioteca (`controles.css`), e não utilitários do Tailwind: HTML gerado pelo PHP não passa
// pela compilação do CSS das páginas, que só lê o HTML gravado.
const CONTROLES_CLASSE_ENTRADA = 'c2fc-campo-entrada';

/** Atributos HTML escapados; `true` vira atributo sem valor e `false`/`null` some. */
function controles_atributos(array $atributos){
	$html = '';
	foreach($atributos as $chave => $valor){
		if($valor === null || $valor === false || !preg_match('/^[a-zA-Z_:][a-zA-Z0-9_:.-]*$/', (string)$chave)) continue;
		$html .= $valor === true ? ' ' . $chave : ' ' . $chave . '="' . controles_esc($valor) . '"';
	}
	return $html;
}

/**
 * Campo completo do formulário.
 *
 * @param array $p tipo (texto|email|senha|numero|data|data-hora|url|area|select|chave|oculto), name, id, rotulo, ajuda,
 *                 erro, valor, placeholder, obrigatorio, desabilitado, somente_leitura, atributos (extras), e os do
 *                 tipo: opcoes/multiplo/busca/ajax (select), linhas (area), min/max/passo (numero), marcado (chave).
 */
function controles_campo(array $p){
	$tipo = (string)($p['tipo'] ?? 'texto');
	$nome = (string)($p['name'] ?? '');
	$id = (string)($p['id'] ?? ('c2fc-' . preg_replace('/[^a-z0-9_-]/i', '-', $nome !== '' ? $nome : uniqid())));
	if($tipo === 'oculto') return '<input type="hidden"' . controles_atributos(Array('name' => $nome, 'id' => $p['id'] ?? null, 'value' => (string)($p['valor'] ?? ''))) . '>';

	$descricao = Array();
	if(!empty($p['ajuda'])) $descricao[] = $id . '-ajuda';
	if(!empty($p['erro'])) $descricao[] = $id . '-erro';
	$comuns = Array('id' => $id, 'name' => $nome, 'required' => !empty($p['obrigatorio']), 'disabled' => !empty($p['desabilitado']),
		'readonly' => !empty($p['somente_leitura']), 'aria-describedby' => $descricao ? implode(' ', $descricao) : null,
		'aria-invalid' => !empty($p['erro']) ? 'true' : null);
	foreach((array)($p['atributos'] ?? Array()) as $chave => $valor) $comuns[$chave] = $valor;

	switch($tipo){
		case 'select':
			$controle = controles_select(array_merge($p, Array('id' => $id, 'atributos' => array_filter(Array('aria-describedby' => $comuns['aria-describedby'])) + (array)($p['atributos'] ?? Array()))));
			break;
		case 'chave':
			$controle = controles_chave(array_merge($p, Array('id' => $id)));
			break;
		case 'area':
			$controle = '<textarea' . controles_atributos($comuns + Array('rows' => (string)(int)($p['linhas'] ?? 4), 'placeholder' => $p['placeholder'] ?? null,
				'class' => CONTROLES_CLASSE_ENTRADA)) . '>' . controles_esc($p['valor'] ?? '') . '</textarea>';
			break;
		default:
			$mapa = Array('texto' => 'text', 'email' => 'email', 'senha' => 'password', 'numero' => 'number', 'data' => 'date', 'data-hora' => 'datetime-local', 'url' => 'url', 'telefone' => 'tel');
			$controle = '<input' . controles_atributos($comuns + Array('type' => $mapa[$tipo] ?? 'text', 'value' => $tipo === 'senha' ? null : (string)($p['valor'] ?? ''),
				'placeholder' => $p['placeholder'] ?? null, 'min' => $p['min'] ?? null, 'max' => $p['max'] ?? null, 'step' => $p['passo'] ?? null,
				'autocomplete' => $p['autocomplete'] ?? null, 'class' => CONTROLES_CLASSE_ENTRADA)) . '>';
	}

	$html = '<div class="c2fc-campo" data-c2fc-campo="' . controles_esc($tipo) . '">';
	if(isset($p['rotulo']) && $p['rotulo'] !== '' && $tipo !== 'chave'){
		$html .= '<label for="' . controles_esc($id) . '" class="c2fc-campo-rotulo">' . controles_esc($p['rotulo'])
			. (!empty($p['obrigatorio']) ? ' <span class="c2fc-campo-obrigatorio" aria-hidden="true">*</span>' : '') . '</label>';
	}
	$html .= $controle;
	if(!empty($p['ajuda'])) $html .= '<p id="' . controles_esc($id) . '-ajuda" class="c2fc-campo-ajuda">' . controles_esc($p['ajuda']) . '</p>';
	if(!empty($p['erro'])) $html .= '<p id="' . controles_esc($id) . '-erro" class="c2fc-campo-erro">' . controles_esc($p['erro']) . '</p>';
	return $html . '</div>';
}

/**
 * Botão padrão. variante: primario (padrão) | secundario | perigo | fantasma; tipo: button|submit; url vira <a>.
 * `icone` é o nome de um ícone Lucide (`data-lucide`), desenhado pelo layout.
 */
function controles_botao(array $p){
	$variantes = Array('primario' => 'c2fc-botao-primario', 'secundario' => '', 'perigo' => 'c2fc-botao-perigo', 'fantasma' => 'c2fc-botao-fantasma');
	$classe = trim('c2fc-botao ' . ($variantes[$p['variante'] ?? 'primario'] ?? $variantes['primario']) . (isset($p['classe']) ? ' ' . $p['classe'] : ''));
	$conteudo = (!empty($p['icone']) ? '<i data-lucide="' . controles_esc($p['icone']) . '" class="c2fc-icone" aria-hidden="true"></i>' : '') . controles_esc($p['rotulo'] ?? '');
	$atributos = (array)($p['atributos'] ?? Array());
	if(!empty($p['url'])){
		return '<a' . controles_atributos($atributos + Array('href' => (string)$p['url'], 'class' => $classe)) . '>' . $conteudo . '</a>';
	}
	return '<button' . controles_atributos($atributos + Array('type' => ($p['tipo'] ?? 'button') === 'submit' ? 'submit' : 'button', 'class' => $classe,
		'disabled' => !empty($p['desabilitado']), 'name' => $p['name'] ?? null, 'value' => $p['valor'] ?? null)) . '>' . $conteudo . '</button>';
}

