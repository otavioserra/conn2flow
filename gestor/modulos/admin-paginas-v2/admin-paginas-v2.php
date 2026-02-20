<?php
/**
 * Módulo admin-paginas-v2 — Demonstração da Interface V2
 * 
 * Reescrita completa do módulo admin-paginas usando a biblioteca interface-v2.
 * Demonstra a API fluent/OOP com method chaining, PHP 8.5 features,
 * enums, value objects e redução drástica de código boilerplate.
 * 
 * Comparação:
 *   - admin-paginas.php (v1): ~1277 linhas com arrays aninhados e código repetitivo
 *   - admin-paginas-v2.php (v2): ~600 linhas com API fluent, limpa e expressiva
 * 
 * @package Conn2Flow
 * @subpackage Modulos
 * @version 1.0.0
 * @requires PHP 8.5+
 * @requires interface-v2
 */

global $_GESTOR;

$_GESTOR['modulo-id'] = 'admin-paginas-v2';
$_GESTOR['modulo#' . $_GESTOR['modulo-id']] = json_decode(
	file_get_contents(__DIR__ . '/admin-paginas-v2.json'),
	true,
);

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                    HELPERS ESPECÍFICOS DO MÓDULO                             ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

/**
 * Atalho para acessar o módulo config.
 */
function admin_paginas_v2_modulo(): array {
	global $_GESTOR;
	return $_GESTOR['modulo#' . $_GESTOR['modulo-id']];
}

/**
 * Atalho para acessar uma variável do módulo.
 */
function admin_paginas_v2_var(string $id): string {
	global $_GESTOR;
	return gestor_variaveis(['modulo' => $_GESTOR['modulo-id'], 'id' => $id]);
}

/**
 * Atalho para acessar uma variável da interface.
 */
function admin_paginas_v2_var_interface(string $id): string {
	return gestor_variaveis(['modulo' => 'interface', 'id' => $id]);
}

/**
 * Processa substituição de variáveis globais (open/close tags).
 * 
 * Usa o pipe operator (|>) do PHP 8.5 para encadear
 * as substituições de regex de forma elegante.
 *
 * @param string ...$campos Nomes dos campos em $_REQUEST para processar
 */
function admin_paginas_v2_processar_variaveis_globais(string ...$campos): void {
	global $_GESTOR;

	$open = preg_quote($_GESTOR['variavel-global']['open']);
	$close = preg_quote($_GESTOR['variavel-global']['close']);
	$openText = preg_quote($_GESTOR['variavel-global']['openText']);
	$closeText = preg_quote($_GESTOR['variavel-global']['closeText']);

	foreach ($campos as $campo) {
		if (!isset($_REQUEST[$campo])) continue;

		$_REQUEST[$campo] = $_REQUEST[$campo]
			|> (fn(string $v) => preg_replace("/{$openText}(.+?){$closeText}/", strtolower($_GESTOR['variavel-global']['open'] . "$1" . $_GESTOR['variavel-global']['close']), $v));
	}
}

/**
 * Restaura variáveis globais em valores do banco para exibição.
 * 
 * Operação inversa de admin_paginas_v2_processar_variaveis_globais().
 *
 * @param string ...$valores Referências para processar
 * @return array Os valores processados na mesma ordem
 */
function admin_paginas_v2_restaurar_variaveis_globais(string ...$valores): array {
	global $_GESTOR;

	$open = preg_quote($_GESTOR['variavel-global']['open']);
	$close = preg_quote($_GESTOR['variavel-global']['close']);
	$openText = $_GESTOR['variavel-global']['openText'];
	$closeText = $_GESTOR['variavel-global']['closeText'];

	return array_map(
		fn(string $v) => preg_replace("/{$open}(.+?){$close}/", strtolower($openText . "$1" . $closeText), $v),
		$valores,
	);
}

/**
 * Configuração comum de validação para adicionar/clonar.
 * 
 * Retorna o InterfaceV2 pré-configurado com as validações e campos
 * de select comuns a adicionar e clonar.
 */
function admin_paginas_v2_validacao_formulario(InterfaceV2 $iv2, bool $comFrameworkCss = true): InterfaceV2 {
	global $_GESTOR;

	$modulo = admin_paginas_v2_modulo();

	$iv2
		// === Validações client-side ===
		->validacao(
			campo: 'nome',
			regra: RegraValidacao::TextoObrigatorio,
			label: admin_paginas_v2_var('form-name-label'),
			identificador: 'pagina-nome',
		)
		->validacao(
			campo: 'caminho',
			regra: RegraValidacao::TextoObrigatorioVerificar,
			label: admin_paginas_v2_var('form-path-label'),
			identificador: 'paginaCaminho',
			language: true,
			regrasExtra: [
				[
					'regra' => 'regexNecessary',
					'regex' => '/^.*\/$/gi',
					'regexNecessaryChars' => admin_paginas_v2_var('path-necessary-chars'),
				],
			],
			removerRegra: ['minLength[3]'],
		)
		->validacao(
			campo: 'layout',
			regra: RegraValidacao::SelecaoObrigatorio,
			label: admin_paginas_v2_var('form-layout-label'),
			identificador: 'layout',
		)
		->validacao(
			campo: 'tipo',
			regra: RegraValidacao::SelecaoObrigatorio,
			label: admin_paginas_v2_var('form-type-label'),
			identificador: 'tipo',
		)

		// === Campos select ===
		->select(
			id: 'layout',
			nome: 'layout',
			procuravel: true,
			limpavel: true,
			placeholder: admin_paginas_v2_var('form-layout-placeholder'),
			tabelaBanco: 'layouts',
			campoBanco: 'id',
			campoTexto: 'nome',
			where: 'language="' . $_GESTOR['linguagem-codigo'] . '"',
		)
		->select(
			id: 'module',
			nome: 'modulo',
			procuravel: true,
			limpavel: true,
			placeholder: admin_paginas_v2_var('form-module-placeholder'),
			tabelaBanco: 'modulos',
			campoBanco: 'id',
			campoTexto: 'nome',
			where: "modulo_grupo_id!='bibliotecas' AND language='" . $_GESTOR['linguagem-codigo'] . "'",
		)
		->select(
			id: 'type',
			nome: 'tipo',
			placeholder: admin_paginas_v2_var('form-type-placeholder'),
			opcoes: $modulo['resources'][$_GESTOR['linguagem-codigo']]['selectDadosTipo'],
		)
		->select(
			id: 'framework-css',
			nome: 'framework_css',
			placeholder: admin_paginas_v2_var('form-framework-css-label'),
			opcoes: $modulo['selectDadosFrameworkCSS'],
		);

	if ($comFrameworkCss) {
		$iv2->validacao(
			campo: 'framework_css',
			regra: RegraValidacao::SelecaoObrigatorio,
			label: admin_paginas_v2_var('form-framework-css-label'),
			identificador: 'framework_css',
		);
	}

	return $iv2;
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                           OPERAÇÃO: ADICIONAR                               ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

function admin_paginas_v2_adicionar(): void {
	global $_GESTOR;

	$modulo = admin_paginas_v2_modulo();
	$iv2 = interface_v2();

	// ═══ Gravar registro no banco ═══

	if (isset($_GESTOR['adicionar-banco'])) {
		$usuario = gestor_usuario();

		// Validação server-side via API fluent
		$iv2
			->validarServidor('pagina-nome', RegraValidacao::TextoObrigatorio, admin_paginas_v2_var('form-name-label'))
			->validarServidor('paginaCaminho', RegraValidacao::TextoObrigatorio, admin_paginas_v2_var('form-path-label'), min: 1);

		// Identificador
		$id = banco_identificador([
			'id' => banco_escape_field($_REQUEST['pagina-nome']),
			'tabela' => [
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
				'where' => "language='" . $_GESTOR['linguagem-codigo'] . "'",
			],
		]);

		// Verificar unicidade do caminho
		$exiteCampo = interface_verificar_campos([
			'campo' => 'caminho',
			'valor' => banco_escape_field($_REQUEST['paginaCaminho']),
			'language' => true,
		]);

		if ($exiteCampo) {
			$alerta = admin_paginas_v2_var_interface('alert-there-is-a-field')
				|> (fn(string $a) => modelo_var_troca_tudo($a, '#label#', admin_paginas_v2_var('form-path-label')))
				|> (fn(string $a) => modelo_var_troca($a, '#value#', banco_escape_field($_REQUEST['paginaCaminho'])));

			AlertaInterface::mostrar($alerta, redirect: $_GESTOR['modulo-id'] . '/adicionar/');
		}

		// Processar variáveis globais
		admin_paginas_v2_processar_variaveis_globais('html', 'css', 'css_compiled', 'html_extra_head');

		// Montar campos para inserção
		$campos = [
			['id_usuarios', $usuario['id_usuarios'], false],
			['nome', banco_escape_field($_REQUEST['pagina-nome']), null],
			['id', $id, false],
		];

		// Campos dinâmicos (só insere se preenchido)
		$camposDinamicos = [
			'layout_id'      => 'layout',
			'tipo'           => 'tipo',
			'framework_css'  => 'framework_css',
			'modulo'         => 'modulo',
			'opcao'          => 'pagina-opcao',
			'caminho'        => 'paginaCaminho',
			'html'           => 'html',
			'css'            => 'css',
			'css_compiled'   => 'css_compiled',
			'html_extra_head' => 'html_extra_head',
		];

		foreach ($camposDinamicos as $campoBd => $campoRequest) {
			if (!empty($_REQUEST[$campoRequest])) {
				$campos[] = [$campoBd, banco_escape_field($_REQUEST[$campoRequest]), null];
			}
		}

		// Checkbox raiz
		if (!empty($_REQUEST['raiz'])) {
			$campos[] = ['raiz', '1', true];
		}

		// Permissão de página
		if (gestor_acesso('permissao-pagina') && !empty($_REQUEST['sem_permissao'])) {
			$campos[] = ['sem_permissao', '1', true];
		}

		// Campos padrão
		$campos[] = ['language ', $_GESTOR['linguagem-codigo'], false];
		$campos[] = [$modulo['tabela']['status'], 'A', false];
		$campos[] = [$modulo['tabela']['versao'], '1', false];
		$campos[] = [$modulo['tabela']['data_criacao'], 'NOW()', true];
		$campos[] = [$modulo['tabela']['data_modificacao'], 'NOW()', true];

		banco_insert_name($campos, $modulo['tabela']['nome']);

		gestor_redirecionar($_GESTOR['modulo-id'] . '/editar/?' . $modulo['tabela']['id'] . '=' . $id);
	}

	// ═══ Permissão de páginas ═══

	if (!gestor_acesso('permissao-pagina')) {
		$cel_nome = 'permissao-pagina';
		$_GESTOR['pagina'] = modelo_tag_in(
			$_GESTOR['pagina'],
			'<!-- ' . $cel_nome . ' < -->',
			'<!-- ' . $cel_nome . ' > -->',
			'<!-- ' . $cel_nome . ' -->',
		);
	}

	// ═══ Editor HTML ═══

	$_GESTOR['pagina'] = modelo_var_troca(
		$_GESTOR['pagina'],
		'#html-editor#',
		html_editor_componente(['alvos' => 'paginas']),
	);

	// ═══ Inclusão JS do módulo ═══

	gestor_pagina_javascript_incluir();

	// ═══ Interface: Configurar e executar ═══

	admin_paginas_v2_validacao_formulario($iv2, comFrameworkCss: true);

	$iv2->adicionar();
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                            OPERAÇÃO: EDITAR                                 ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

function admin_paginas_v2_editar(): void {
	global $_GESTOR;

	$modulo = admin_paginas_v2_modulo();
	$iv2 = interface_v2();
	$id = $_GESTOR['modulo-registro-id'];

	// ═══ Definição dos campos do banco ═══

	$camposBanco = [
		'nome', 'caminho', 'layout_id', 'modulo', 'tipo', 'opcao',
		'raiz', 'sem_permissao', 'html', 'css', 'css_compiled',
		'html_extra_head', 'framework_css',
	];

	$camposBancoPadrao = [
		$modulo['tabela']['status'],
		$modulo['tabela']['versao'],
		$modulo['tabela']['data_criacao'],
		$modulo['tabela']['data_modificacao'],
	];

	$camposBancoEditar = [...$camposBanco, ...$camposBancoPadrao];

	// ═══ Gravar atualizações ═══

	if (isset($_GESTOR['atualizar-banco'])) {

		// Recuperar estado anterior
		if (!banco_select_campos_antes_iniciar(
			banco_campos_virgulas($camposBanco),
			$modulo['tabela']['nome'],
			"WHERE {$modulo['tabela']['id']}='{$id}'"
			. " AND {$modulo['tabela']['status']}!='D'"
			. " AND language='" . $_GESTOR['linguagem-codigo'] . "'",
		)) {
			AlertaInterface::mostrar(
				admin_paginas_v2_var_interface('alert-database-field-before-error'),
				redirect: '',
			);
			gestor_redirecionar_raiz();
		}

		// Validação server-side
		$iv2
			->validarServidor('pagina-nome', RegraValidacao::TextoObrigatorio, admin_paginas_v2_var('form-name-label'))
			->validarServidor('paginaCaminho', RegraValidacao::TextoObrigatorio, admin_paginas_v2_var('form-path-label'), min: 1);

		// Verificar unicidade do caminho
		$exiteCampo = interface_verificar_campos([
			'campo' => 'caminho',
			'valor' => banco_escape_field($_REQUEST['paginaCaminho']),
			'language' => true,
		]);

		if ($exiteCampo) {
			$alerta = admin_paginas_v2_var_interface('alert-there-is-a-field')
				|> (fn(string $a) => modelo_var_troca_tudo($a, '#label#', admin_paginas_v2_var('form-path-label')))
				|> (fn(string $a) => modelo_var_troca($a, '#value#', banco_escape_field($_REQUEST['paginaCaminho'])));

			AlertaInterface::mostrar($alerta, redirect: $_GESTOR['modulo-id'] . '/editar/?' . $modulo['tabela']['id'] . '=' . $id);
		}

		// Processar variáveis globais
		admin_paginas_v2_processar_variaveis_globais('html', 'css', 'css_compiled', 'html_extra_head');

		// ── Construir diff de edição ──

		$editar = [
			'tabela' => $modulo['tabela']['nome'],
			'extra' => "WHERE {$modulo['tabela']['id']}='{$id}' AND {$modulo['tabela']['status']}!='D' AND language='" . $_GESTOR['linguagem-codigo'] . "'",
		];

		$alteracoes = [];
		$backups = [];
		$alterar_id = false;

		/**
		 * Helper interno: compara campo com valor anterior e registra diff.
		 * Usa variável de referência para construir array de edição.
		 */
		$diff = function (
			string $campoBd,
			string $campoRequest,
			string $alteracaoLabel,
			bool $comBackup = false,
		) use (&$editar, &$alteracoes, &$backups) {
			$valorAtual = banco_select_campos_antes($campoBd);
			$valorNovo = isset($_REQUEST[$campoRequest]) ? $_REQUEST[$campoRequest] : null;

			if ($valorAtual != $valorNovo) {
				$editar['dados'][] = "{$campoBd}='" . banco_escape_field($valorNovo) . "'";
				$alteracoes[] = [
					'campo' => 'form-' . $alteracaoLabel . '-label',
					'valor_antes' => $valorAtual,
					'valor_depois' => banco_escape_field($valorNovo),
				];

				if ($comBackup && $valorAtual) {
					$backups[] = [
						'campo' => $campoBd,
						'valor' => addslashes($valorAtual),
					];
				}

				return true;
			}
			return false;
		};

		// Campo nome — pode alterar o ID
		if ($diff('nome', 'pagina-nome', 'name')) {
			if (!isset($_REQUEST['_gestor-nao-alterar-id'])) {
				$alterar_id = true;
			}
		}

		// Se mudou o nome, recalcular o identificador
		if ($alterar_id) {
			$registro = banco_select_name(
				banco_campos_virgulas([$modulo['tabela']['id_numerico']]),
				$modulo['tabela']['nome'],
				"WHERE {$modulo['tabela']['id']}='{$id}'",
			);

			if ($registro) {
				$id_novo = banco_identificador([
					'id' => banco_escape_field($_REQUEST['pagina-nome']),
					'tabela' => [
						'nome' => $modulo['tabela']['nome'],
						'campo' => $modulo['tabela']['id'],
						'id_nome' => $modulo['tabela']['id_numerico'],
						'id_valor' => $registro[0][$modulo['tabela']['id_numerico']],
						'where' => "language='" . $_GESTOR['linguagem-codigo'] . "'",
					],
				]);

				$alteracoes[] = ['campo' => 'field-id', 'valor_antes' => $id, 'valor_depois' => $id_novo];
				$editar['dados'][] = "{$modulo['tabela']['id']}='{$id_novo}'";
				$_GESTOR['modulo-registro-id'] = $id_novo;
			}
		}

		// Demais campos
		$diff('layout_id', 'layout', 'layout');
		$diff('tipo', 'tipo', 'type');
		$diff('framework_css', 'framework_css', 'framework-css');
		$diff('modulo', 'modulo', 'module');
		$diff('opcao', 'pagina-opcao', 'option');

		// Caminho — com detecção de mudança para criar 301
		$caminhoMudou = null;
		if (banco_select_campos_antes('caminho') != ($_REQUEST['paginaCaminho'] ?? null)) {
			$caminhoMudou = banco_select_campos_antes('caminho');
			$diff('caminho', 'paginaCaminho', 'path');
		}

		// Checkbox raiz
		if (banco_select_campos_antes('raiz') != (isset($_REQUEST['raiz']) ? '1' : null)) {
			$editar['dados'][] = "raiz=" . (isset($_REQUEST['raiz']) ? '1' : 'NULL');
			$alteracoes[] = [
				'campo' => 'form-root-label',
				'filtro' => 'checkbox',
				'valor_antes' => banco_select_campos_antes('raiz') ? '1' : '0',
				'valor_depois' => isset($_REQUEST['raiz']) ? '1' : '0',
			];
		}

		// Permissão
		if (gestor_acesso('permissao-pagina')) {
			if (banco_select_campos_antes('sem_permissao') != (isset($_REQUEST['sem_permissao']) ? '1' : null)) {
				$editar['dados'][] = "sem_permissao=" . (isset($_REQUEST['sem_permissao']) ? '1' : 'NULL');
				$alteracoes[] = [
					'campo' => 'form-permission-label',
					'filtro' => 'checkbox',
					'valor_antes' => banco_select_campos_antes('sem_permissao') ? '1' : '0',
					'valor_depois' => isset($_REQUEST['sem_permissao']) ? '1' : '0',
				];
			}
		}

		// Campos com backup
		$diff('html', 'html', 'html', comBackup: true);
		$diff('css', 'css', 'css', comBackup: true);
		$diff('css_compiled', 'css_compiled', 'css-compiled', comBackup: true);
		$diff('html_extra_head', 'html_extra_head', 'html-extra-head', comBackup: true);

		// ── Executar atualização se houve alterações ──

		if (isset($editar['dados'])) {
			$editar['dados'][] = "user_modified = 1";
			$editar['dados'][] = "{$modulo['tabela']['versao']} = {$modulo['tabela']['versao']} + 1";
			$editar['dados'][] = "{$modulo['tabela']['data_modificacao']}=NOW()";

			$editar['sql'] = banco_campos_virgulas($editar['dados']);

			if ($editar['sql']) {
				banco_update($editar['sql'], $editar['tabela'], $editar['extra']);
			}

			// Backups dos campos
			foreach ($backups as $backup) {
				interface_backup_campo_incluir([
					'id_numerico' => interface_modulo_variavel_valor(['variavel' => $modulo['tabela']['id_numerico']]),
					'versao' => interface_modulo_variavel_valor(['variavel' => $modulo['tabela']['versao']]),
					'campo' => $backup['campo'],
					'valor' => $backup['valor'],
				]);
			}

			// Histórico
			interface_historico_incluir([
				'id' => $id,
				'tabela' => [
					'nome' => $modulo['tabela']['nome'],
					'id_numerico' => $modulo['tabela']['id_numerico'],
					'versao' => $modulo['tabela']['versao'],
				],
				'alteracoes' => $alteracoes,
			]);

			// Criar 301 se caminho mudou
			if ($caminhoMudou) {
				banco_insert_name(
					[
						['id_paginas', interface_modulo_variavel_valor(['variavel' => $modulo['tabela']['id_numerico']]), null],
						['caminho', $caminhoMudou, null],
						['data_criacao', 'NOW()', true],
					],
					'paginas_301',
				);
			}
		}

		// Reler URL
		gestor_redirecionar(
			$_GESTOR['modulo-id'] . '/editar/?' . $modulo['tabela']['id'] . '=' . ($id_novo ?? $id),
		);
	}

	// ═══ Permissão de páginas ═══

	if (!gestor_acesso('permissao-pagina')) {
		$cel_nome = 'permissao-pagina';
		$_GESTOR['pagina'] = modelo_tag_in(
			$_GESTOR['pagina'],
			'<!-- ' . $cel_nome . ' < -->',
			'<!-- ' . $cel_nome . ' > -->',
			'<!-- ' . $cel_nome . ' -->',
		);
	}

	// ═══ Selecionar dados do banco ═══

	$retorno_bd = banco_select_editar(
		banco_campos_virgulas($camposBancoEditar),
		$modulo['tabela']['nome'],
		"WHERE {$modulo['tabela']['id']}='{$id}'"
		. " AND {$modulo['tabela']['status']}!='D'"
		. " AND language='" . $_GESTOR['linguagem-codigo'] . "'",
	);

	if (!$_GESTOR['banco-resultado']) {
		gestor_redirecionar_raiz();
	}

	// Extrair valores com null coalescing
	$nome = $retorno_bd['nome'] ?? '';
	$caminho = $retorno_bd['caminho'] ?? '';
	$layout_id = $retorno_bd['layout_id'] ?? '';
	$bd_modulo = $retorno_bd['modulo'] ?? '';
	$tipo = $retorno_bd['tipo'] ?? '';
	$framework_css = $retorno_bd['framework_css'] ?? '';
	$opcao = $retorno_bd['opcao'] ?? '';
	$html = isset($retorno_bd['html']) ? htmlentities($retorno_bd['html']) : '';
	$css = $retorno_bd['css'] ?? '';
	$css_compiled = $retorno_bd['css_compiled'] ?? '';
	$html_extra_head = $retorno_bd['html_extra_head'] ?? '';
	$raiz = isset($retorno_bd['raiz']);
	$sem_permissao = isset($retorno_bd['sem_permissao']);

	// Restaurar variáveis globais para exibição
	[$html, $css, $css_compiled, $html_extra_head] = admin_paginas_v2_restaurar_variaveis_globais(
		$html, $css, $css_compiled, $html_extra_head,
	);

	// Detectar Quill editor
	if (str_contains($html, 'ql-container ql-snow')) {
		gestor_js_variavel_incluir('publisherQuillClassDetected', true);
	}

	// Substituir variáveis na página
	$_GESTOR['pagina'] = $_GESTOR['pagina']
		|> (fn(string $p) => modelo_var_troca_tudo($p, '#url#', 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['SERVER_NAME'] . $_GESTOR['url-raiz'] . ($caminho === '/' ? '' : $caminho)))
		|> (fn(string $p) => modelo_var_troca_tudo($p, '#pagina-nome#', $nome))
		|> (fn(string $p) => modelo_var_troca_tudo($p, '#caminho#', $caminho))
		|> (fn(string $p) => modelo_var_troca_tudo($p, '#id#', $id))
		|> (fn(string $p) => modelo_var_troca_tudo($p, '#opcao#', $opcao))
		|> (fn(string $p) => modelo_var_troca($p, '#raiz#', $raiz ? 'checked' : ''))
		|> (fn(string $p) => modelo_var_troca($p, '#sem_permissao#', $sem_permissao ? 'checked' : ''));

	// Editor HTML
	$_GESTOR['pagina'] = modelo_var_troca(
		$_GESTOR['pagina'],
		'#html-editor#',
		html_editor_componente([
			'editar' => true,
			'modulo' => $modulo,
			'alvos' => 'paginas',
		]),
	);

	// JS do módulo
	gestor_pagina_javascript_incluir();

	// ═══ Montar interface de edição ═══

	$status_atual = $retorno_bd[$modulo['tabela']['status']] ?? '';

	// Configurar via API fluent
	admin_paginas_v2_validacao_formulario($iv2, comFrameworkCss: false);

	$iv2
		// Meta dados
		->metaDado(
			admin_paginas_v2_var_interface('field-date-start'),
			FormatadorInterface::dataHora($retorno_bd[$modulo['tabela']['data_criacao']] ?? ''),
		)
		->metaDado(
			admin_paginas_v2_var_interface('field-date-modification'),
			FormatadorInterface::dataHora($retorno_bd[$modulo['tabela']['data_modificacao']] ?? ''),
		)
		->metaDado(
			admin_paginas_v2_var_interface('field-version'),
			$retorno_bd[$modulo['tabela']['versao']] ?? '',
		)
		->metaDado(
			admin_paginas_v2_var_interface('field-status'),
			$status_atual === 'A'
				? '<div class="ui center aligned green message"><b>' . admin_paginas_v2_var_interface('field-status-active') . '</b></div>'
				: '<div class="ui center aligned brown message"><b>' . admin_paginas_v2_var_interface('field-status-inactive') . '</b></div>',
		)

		// Variáveis para substituir após renderização
		->variavel('pagina-css', $css)
		->variavel('pagina-css-compiled', $css_compiled)
		->variavel('pagina-html', $html)
		->variavel('pagina-html-extra-head', $html_extra_head)

		// Botões
		->botao(
			id: 'adicionar',
			rotulo: admin_paginas_v2_var_interface('label-button-insert'),
			tooltip: admin_paginas_v2_var_interface('tooltip-button-insert'),
			icone: 'plus circle',
			cor: 'blue',
			url: $_GESTOR['url-raiz'] . $_GESTOR['modulo-id'] . '/adicionar/',
		)
		->botao(
			id: 'clonar',
			rotulo: admin_paginas_v2_var_interface('label-button-clone'),
			tooltip: admin_paginas_v2_var_interface('tooltip-button-clone'),
			icone: 'clone',
			cor: 'teal',
			url: $_GESTOR['url-raiz'] . $_GESTOR['modulo-id'] . '/clonar/?' . $modulo['tabela']['id'] . '=' . $id,
		)
		->botao(
			id: 'status',
			rotulo: $status_atual === 'A' ? admin_paginas_v2_var_interface('label-button-desactive') : admin_paginas_v2_var_interface('label-button-active'),
			tooltip: $status_atual === 'A' ? admin_paginas_v2_var_interface('tooltip-button-desactive') : admin_paginas_v2_var_interface('tooltip-button-active'),
			icone: $status_atual === 'A' ? 'eye' : 'eye slash',
			cor: $status_atual === 'A' ? 'green' : 'brown',
			url: $_GESTOR['url-raiz'] . $_GESTOR['modulo-id'] . '/?opcao=status&' . $modulo['tabela']['status'] . '=' . ($status_atual === 'A' ? 'I' : 'A') . '&' . $modulo['tabela']['id'] . '=' . $id . '&redirect=' . urlencode($_GESTOR['modulo-id'] . '/editar/?' . $modulo['tabela']['id'] . '=' . $id),
		)
		->botao(
			id: 'excluir',
			rotulo: admin_paginas_v2_var_interface('label-button-delete'),
			tooltip: admin_paginas_v2_var_interface('tooltip-button-delete'),
			icone: 'trash alternate',
			cor: 'red',
			url: $_GESTOR['url-raiz'] . $_GESTOR['modulo-id'] . '/?opcao=excluir&' . $modulo['tabela']['id'] . '=' . $id,
		)

		// Executar
		->editar();
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                            OPERAÇÃO: CLONAR                                 ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

function admin_paginas_v2_clonar(): void {
	global $_GESTOR;

	$modulo = admin_paginas_v2_modulo();
	$iv2 = interface_v2();
	$id = $_GESTOR['modulo-registro-id'];

	// ═══ Campos para clonar ═══

	$camposBanco = [
		'caminho', 'layout_id', 'modulo', 'tipo', 'opcao',
		'raiz', 'sem_permissao', 'html', 'css', 'css_compiled',
		'html_extra_head', 'framework_css',
	];

	$camposBancoPadrao = [
		$modulo['tabela']['status'],
		$modulo['tabela']['versao'],
		$modulo['tabela']['data_criacao'],
		$modulo['tabela']['data_modificacao'],
	];

	$camposBancoClonar = [...$camposBanco, ...$camposBancoPadrao];

	// ═══ Gravar registro clonado ═══

	if (isset($_GESTOR['adicionar-banco'])) {
		$usuario = gestor_usuario();

		// Validação server-side
		$iv2
			->validarServidor('pagina-nome', RegraValidacao::TextoObrigatorio, admin_paginas_v2_var('form-name-label'))
			->validarServidor('paginaCaminho', RegraValidacao::TextoObrigatorio, admin_paginas_v2_var('form-path-label'), min: 1);

		// Identificador
		$id = banco_identificador([
			'id' => banco_escape_field($_REQUEST['pagina-nome']),
			'tabela' => [
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
				'where' => "language='" . $_GESTOR['linguagem-codigo'] . "'",
			],
		]);

		// Verificar unicidade do caminho
		$exiteCampo = interface_verificar_campos([
			'campo' => 'caminho',
			'valor' => banco_escape_field($_REQUEST['paginaCaminho']),
			'language' => true,
		]);

		if ($exiteCampo) {
			$alerta = admin_paginas_v2_var_interface('alert-there-is-a-field')
				|> (fn(string $a) => modelo_var_troca_tudo($a, '#label#', admin_paginas_v2_var('form-path-label')))
				|> (fn(string $a) => modelo_var_troca($a, '#value#', banco_escape_field($_REQUEST['paginaCaminho'])));

			AlertaInterface::mostrar($alerta, redirect: $_GESTOR['modulo-id'] . '/adicionar/');
		}

		// Processar variáveis globais
		admin_paginas_v2_processar_variaveis_globais('html', 'css', 'css_compiled', 'html_extra_head');

		// Montar campos
		$campos = [
			['id_usuarios', $usuario['id_usuarios'], false],
			['nome', banco_escape_field($_REQUEST['pagina-nome']), null],
			['id', $id, false],
		];

		$camposDinamicos = [
			'layout_id'      => 'layout',
			'tipo'           => 'tipo',
			'framework_css'  => 'framework_css',
			'modulo'         => 'modulo',
			'opcao'          => 'pagina-opcao',
			'caminho'        => 'paginaCaminho',
			'html'           => 'html',
			'css'            => 'css',
			'css_compiled'   => 'css_compiled',
			'html_extra_head' => 'html_extra_head',
		];

		foreach ($camposDinamicos as $campoBd => $campoRequest) {
			if (!empty($_REQUEST[$campoRequest])) {
				$campos[] = [$campoBd, banco_escape_field($_REQUEST[$campoRequest]), null];
			}
		}

		if (!empty($_REQUEST['raiz'])) {
			$campos[] = ['raiz', '1', true];
		}

		if (gestor_acesso('permissao-pagina') && !empty($_REQUEST['sem_permissao'])) {
			$campos[] = ['sem_permissao', '1', true];
		}

		$campos[] = ['language ', $_GESTOR['linguagem-codigo'], false];
		$campos[] = [$modulo['tabela']['status'], 'A', false];
		$campos[] = [$modulo['tabela']['versao'], '1', false];
		$campos[] = [$modulo['tabela']['data_criacao'], 'NOW()', true];
		$campos[] = [$modulo['tabela']['data_modificacao'], 'NOW()', true];

		banco_insert_name($campos, $modulo['tabela']['nome']);

		gestor_redirecionar($_GESTOR['modulo-id'] . '/editar/?' . $modulo['tabela']['id'] . '=' . $id);
	}

	// ═══ Permissão de páginas ═══

	if (!gestor_acesso('permissao-pagina')) {
		$cel_nome = 'permissao-pagina';
		$_GESTOR['pagina'] = modelo_tag_in(
			$_GESTOR['pagina'],
			'<!-- ' . $cel_nome . ' < -->',
			'<!-- ' . $cel_nome . ' > -->',
			'<!-- ' . $cel_nome . ' -->',
		);
	}

	// ═══ Selecionar dados do registro original ═══

	$retorno_bd = banco_select_editar(
		banco_campos_virgulas($camposBancoClonar),
		$modulo['tabela']['nome'],
		"WHERE {$modulo['tabela']['id']}='{$id}'"
		. " AND {$modulo['tabela']['status']}!='D'"
		. " AND language='" . $_GESTOR['linguagem-codigo'] . "'",
	);

	if (!$_GESTOR['banco-resultado']) {
		gestor_redirecionar_raiz();
	}

	// Extrair valores
	$caminho = $retorno_bd['caminho'] ?? '';
	$layout_id = $retorno_bd['layout_id'] ?? '';
	$bd_modulo = $retorno_bd['modulo'] ?? '';
	$tipo = $retorno_bd['tipo'] ?? '';
	$framework_css = $retorno_bd['framework_css'] ?? '';
	$opcao = $retorno_bd['opcao'] ?? '';
	$html = isset($retorno_bd['html']) ? htmlentities($retorno_bd['html']) : '';
	$css = $retorno_bd['css'] ?? '';
	$css_compiled = $retorno_bd['css_compiled'] ?? '';
	$html_extra_head = $retorno_bd['html_extra_head'] ?? '';
	$raiz = isset($retorno_bd['raiz']);
	$sem_permissao = isset($retorno_bd['sem_permissao']);

	// Restaurar variáveis globais
	[$html, $css, $css_compiled, $html_extra_head] = admin_paginas_v2_restaurar_variaveis_globais(
		$html, $css, $css_compiled, $html_extra_head,
	);

	// Quill
	if (str_contains($html, 'ql-container ql-snow')) {
		gestor_js_variavel_incluir('publisherQuillClassDetected', true);
	}

	// Substituir variáveis
	$_GESTOR['pagina'] = $_GESTOR['pagina']
		|> (fn(string $p) => modelo_var_troca_tudo($p, '#caminho#', $caminho))
		|> (fn(string $p) => modelo_var_troca_tudo($p, '#opcao#', $opcao))
		|> (fn(string $p) => modelo_var_troca($p, '#raiz#', $raiz ? 'checked' : ''))
		|> (fn(string $p) => modelo_var_troca($p, '#sem_permissao#', $sem_permissao ? 'checked' : ''));

	// Editor HTML
	$_GESTOR['pagina'] = modelo_var_troca(
		$_GESTOR['pagina'],
		'#html-editor#',
		html_editor_componente([
			'editar' => true,
			'modulo' => $modulo,
			'alvos' => 'paginas',
		]),
	);

	// JS do módulo
	gestor_pagina_javascript_incluir();

	// ═══ Configurar interface de clonagem ═══

	admin_paginas_v2_validacao_formulario($iv2, comFrameworkCss: false);

	$iv2
		->variavel('pagina-css', $css)
		->variavel('pagina-css-compiled', $css_compiled)
		->variavel('pagina-html', $html)
		->variavel('pagina-html-extra-head', $html_extra_head)
		->adicionar(); // Clonar usa o layout de adicionar
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                     LISTAGEM: Cabeçalho / Filtros                           ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

/**
 * Monta o cabeçalho da listagem com filtro de tipo (página/sistema/ambos).
 * Reutiliza o componente 'lista-pagina-ou-sistema' do módulo.
 */
function admin_paginas_v2_listar_cabecalho(): array {
	global $_GESTOR;

	if ($_GESTOR['opcao'] === 'listar') {
		gestor_pagina_javascript_incluir();
	}

	$tipo = banco_escape_field($_REQUEST['tipo'] ?? 'pagina');
	$module_id = banco_escape_field($_REQUEST['module_id'] ?? '');

	$checked = ' checked="checked"';

	$componente_cabecalho = gestor_componente([
		'id' => 'lista-pagina-ou-sistema',
		'modulo' => $_GESTOR['modulo-id'],
	]);

	// Variáveis do componente — pipe operator para substituição encadeada
	$componente_cabecalho = $componente_cabecalho
		|> (fn(string $c) => modelo_var_troca($c, '#ambos#', admin_paginas_v2_var('lista-ambos-label')))
		|> (fn(string $c) => modelo_var_troca($c, '#pagina#', admin_paginas_v2_var('lista-pagina-label')))
		|> (fn(string $c) => modelo_var_troca($c, '#sistema#', admin_paginas_v2_var('lista-sistema-label')))
		|> (fn(string $c) => modelo_var_troca($c, '#tipo#', admin_paginas_v2_var('lista-tipo-label')))
		|> (fn(string $c) => modelo_var_troca($c, '#checked_ambos#', $tipo === 'ambos' ? $checked : ''))
		|> (fn(string $c) => modelo_var_troca($c, '#checked_pagina#', $tipo === 'pagina' ? $checked : ''))
		|> (fn(string $c) => modelo_var_troca($c, '#checked_sistema#', $tipo === 'sistema' ? $checked : ''));

	// Dropdown de módulos (quando tipo != pagina)
	if ($tipo !== 'pagina') {
		$modulos = banco_select_name(
			banco_campos_virgulas(['nome', 'id']),
			'modulos',
			"WHERE language='" . $_GESTOR['linguagem-codigo'] . "' AND status!='D'",
		);

		if ($modulos) {
			$selected = ' selected="selected"';
			foreach ($modulos as $mod) {
				$componente_cabecalho = modelo_var_in(
					$componente_cabecalho,
					'<!-- modulos-opcoes -->',
					'<option value="' . $mod['id'] . '"' . ($mod['id'] === $module_id ? $selected : '') . '>' . $mod['nome'] . '</option>',
				);
			}
		} else {
			$componente_cabecalho = modelo_tag_del($componente_cabecalho, '<!-- modulos-cel < -->', '<!-- modulos-cel > -->');
		}
	} else {
		$module_id = '';
		$componente_cabecalho = modelo_tag_del($componente_cabecalho, '<!-- modulos-cel < -->', '<!-- modulos-cel > -->');
	}

	return [
		'cabecalho' => $componente_cabecalho,
		'tipo' => $tipo,
		'module_id' => $module_id,
	];
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                     INTERFACES PADRÕES (Listagem + CRUD)                    ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

/**
 * Configura as interfaces padrões do módulo.
 * 
 * Na v1, isso preenchia $_GESTOR['interface'][$_GESTOR['opcao']]['finalizar']
 * com arrays enormes. Na v2, basta usar a API fluent para configurar
 * colunas, ações e botões — tudo encadeado e autoexplicativo.
 */
function admin_paginas_v2_interfaces_padroes(): void {
	global $_GESTOR;

	$modulo = admin_paginas_v2_modulo();
	$iv2 = interface_v2();

	$dados = admin_paginas_v2_listar_cabecalho();
	$tipo_nao_visivel = $dados['tipo'] !== 'ambos';

	if ($_GESTOR['opcao'] !== 'listar') return;

	// ── Construir WHERE dinâmico ──
	$where = "language='" . $_GESTOR['linguagem-codigo'] . "'";
	if ($dados['tipo'] !== 'ambos' && $dados['tipo'] !== '') {
		$where .= " AND tipo='{$dados['tipo']}'";
	}
	if ($dados['module_id'] !== '') {
		$where .= " AND modulo='{$dados['module_id']}'";
	}

	// ── Configuração fluent da listagem ──
	// Note como o mesmo resultado de ~80 linhas de array aninhado (v1)
	// é obtido com ~50 linhas de API encadeada, tipada e autocompletável.

	$iv2
		->banco(
			nome: $modulo['tabela']['nome'],
			id: $modulo['tabela']['id'],
			status: $modulo['tabela']['status'],
			idNumerico: $modulo['tabela']['id_numerico'],
			versao: $modulo['tabela']['versao'],
			dataCriacao: $modulo['tabela']['data_criacao'],
			dataModificacao: $modulo['tabela']['data_modificacao'],
		)
		->where($where)
		->rodape(true)
		->cabecalho($dados['cabecalho'])

		// ── Colunas ──
		->coluna(
			id: 'nome',
			nome: admin_paginas_v2_var_interface('field-name'),
			ordem: 'asc',
		)
		->coluna(
			id: 'tipo',
			nome: admin_paginas_v2_var('form-type-label'),
			formato: FormatoTipo::OutroConjunto,
			formatoParams: [
				'conjunto' => [
					['valor' => 'sistema', 'texto' => admin_paginas_v2_var('form-type-system')],
					['valor' => 'pagina', 'texto' => admin_paginas_v2_var('form-type-page')],
				],
			],
			visivel: !$tipo_nao_visivel,
		)
		->coluna(
			id: 'modulo',
			nome: gestor_variaveis(['modulo' => 'modulos', 'id' => 'module-name']),
			formato: FormatoTipo::OutraTabela,
			formatoParams: [
				'tabela' => 'modulos',
				'campo_valor' => 'id',
				'campo_texto' => 'nome',
				'where' => 'language="' . $_GESTOR['linguagem-codigo'] . '"',
				'valor_senao_existe' => '<span class="ui info text">N/A</span>',
			],
		)
		->coluna(
			id: 'caminho',
			nome: admin_paginas_v2_var_interface('field-url-path'),
			ordem: 'asc',
		)
		->coluna(
			id: $modulo['tabela']['data_modificacao'],
			nome: admin_paginas_v2_var_interface('field-date-modification'),
			formato: FormatoTipo::DataHora,
			procuravel: false,
		)

		// ── Ações por registro ──
		->acao(
			id: 'editar',
			icone: 'edit',
			tooltip: admin_paginas_v2_var_interface('tooltip-button-edit'),
			cor: 'basic blue',
			url: 'editar/',
		)
		->acao(
			id: 'clonar',
			icone: 'clone',
			tooltip: admin_paginas_v2_var_interface('tooltip-button-clone'),
			cor: 'basic teal',
			url: 'clonar/',
		)
		->acaoStatus(
			id: 'ativar',
			icone: 'eye slash',
			tooltip: admin_paginas_v2_var_interface('tooltip-button-active'),
			cor: 'basic brown',
			statusAtual: 'I',
			statusMudar: 'A',
		)
		->acaoStatus(
			id: 'desativar',
			icone: 'eye',
			tooltip: admin_paginas_v2_var_interface('tooltip-button-desactive'),
			cor: 'basic green',
			statusAtual: 'A',
			statusMudar: 'I',
		)
		->acaoExcluir(
			tooltip: admin_paginas_v2_var_interface('tooltip-button-delete'),
		)

		// ── Botão adicionar ──
		->botao(
			id: 'adicionar',
			rotulo: admin_paginas_v2_var_interface('label-button-insert'),
			tooltip: admin_paginas_v2_var_interface('tooltip-button-insert'),
			icone: 'plus circle',
			cor: 'blue',
			url: 'adicionar/',
		)

		// ── Executar listagem ──
		->listar();
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                              AJAX                                           ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

function admin_paginas_v2_ajax_editor_html_switch(): void {
	global $_GESTOR;

	gestor_variaveis_alterar([
		'modulo' => $_GESTOR['modulo-id'],
		'id' => 'editor-html-switch',
		'tipo' => 'bool',
		'valor' => $_REQUEST['editor_checked'] === 'sim',
	]);

	$_GESTOR['ajax-json'] = ['status' => 'Ok'];
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                              START                                          ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

/**
 * Ponto de entrada do módulo.
 * 
 * Demonstra o fluxo completo com interface-v2:
 *   1. Carrega bibliotecas (incluindo interface-v2)
 *   2. Se AJAX: processa com interface_v2()->processarAjax() + handlers do módulo
 *   3. Se não AJAX: configura interfaces padrões → inicia conectores → executa operação → finaliza
 * 
 * O fluxo é muito mais limpo que o v1 porque:
 *   - interface_v2()->processarAjax() substitui interface_ajax_iniciar/finalizar
 *   - Os conectores (editarIniciar, excluirIniciar etc.) são chamados na instância
 *   - interface_v2()->finalizar() substitui interface_finalizar()
 */
function admin_paginas_v2_start(): void {
	global $_GESTOR;

	$iv2 = interface_v2();

	// Carregar bibliotecas do módulo
	gestor_incluir_bibliotecas();

	// Configurar banco (necessário para excluir/status)
	$modulo = admin_paginas_v2_modulo();
	$iv2->banco(
		nome: $modulo['tabela']['nome'],
		id: $modulo['tabela']['id'],
		status: $modulo['tabela']['status'],
		idNumerico: $modulo['tabela']['id_numerico'],
		versao: $modulo['tabela']['versao'],
		dataCriacao: $modulo['tabela']['data_criacao'],
		dataModificacao: $modulo['tabela']['data_modificacao'],
	)->where("language='" . $_GESTOR['linguagem-codigo'] . "'");

	if ($_GESTOR['ajax']) {
		// ── Modo AJAX ──
		// interface_v2()->processarAjax() cuida de: listar, verificar-campo,
		// backup-campos-mudou, historico-mais-resultados
		$iv2->processarAjax();

		// Handlers AJAX específicos do módulo
		match ($_GESTOR['ajax-opcao'] ?? '') {
			'editor-html-switch' => admin_paginas_v2_ajax_editor_html_switch(),
			default => null,
		};

	} else {
		// ── Modo Normal ──

		// Configurar interfaces padrões (listagem)
		admin_paginas_v2_interfaces_padroes();

		// Executar operação baseada em $_GESTOR['opcao']
		match ($_GESTOR['opcao']) {
			'adicionar' => (function () use ($iv2) {
				$iv2->adicionarIniciar();
				admin_paginas_v2_adicionar();
			})(),

			'editar' => (function () use ($iv2) {
				$iv2->editarIniciar();
				admin_paginas_v2_editar();
			})(),

			'clonar' => (function () use ($iv2) {
				$iv2->clonarIniciar();
				admin_paginas_v2_clonar();
			})(),

			'excluir' => (function () use ($iv2) {
				$iv2->excluirIniciar();
				$iv2->excluirFinalizar();
			})(),

			'status' => (function () use ($iv2) {
				$iv2->statusIniciar();
				$iv2->statusFinalizar();
			})(),

			default => null, // listar já foi configurado acima
		};

		// Finalizar — imprime alertas e componentes
		$iv2->finalizar();
	}
}

admin_paginas_v2_start();

?>
