<?php
/**
 * Biblioteca de Interface Administrativa v2
 * 
 * Reescrita OOP completa da biblioteca interface.php usando PHP 8.5+.
 * Fornece classes fluent (method chaining) para construção de interfaces
 * administrativas: listagem, formulários, CRUD, validações, histórico,
 * backups, alertas, widgets e componentes.
 * 
 * Uso:
 *   InterfaceV2::criar()
 *       ->coluna('nome', 'Nome')
 *       ->coluna('data', 'Data', formato: FormatoTipo::DataHora)
 *       ->acao('editar', icone: 'edit', cor: 'blue')
 *       ->listar();
 * 
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 2.0.0
 * @requires PHP 8.5+
 */

global $_GESTOR;

$_GESTOR['biblioteca-interface-v2'] = [
	'versao' => '2.0.0',
];

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                                  ENUMS                                      ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

/**
 * Tipos de formatação de dados disponíveis.
 */
enum FormatoTipo: string {
	case DinheiroReais   = 'dinheiroReais';
	case Data            = 'data';
	case DataHora        = 'dataHora';
	case OutraTabela     = 'outraTabela';
	case OutroConjunto   = 'outroConjunto';
	case OutroArray      = 'outroArray';
	case Encapsular      = 'encapsular';
}

/**
 * Regras de validação de formulário (client + server side).
 */
enum RegraValidacao: string {
	case NaoVazio                       = 'nao-vazio';
	case MaiorOuIgualZero              = 'maior-ou-igual-a-zero';
	case TextoObrigatorio              = 'texto-obrigatorio';
	case TextoObrigatorioVerificar     = 'texto-obrigatorio-verificar-campo';
	case SelecaoObrigatorio            = 'selecao-obrigatorio';
	case Email                         = 'email';
	case Senha                         = 'senha';
	case Dominio                       = 'dominio';
	case EmailComparacao               = 'email-comparacao';
	case SenhaComparacao               = 'senha-comparacao';
	case EmailComparacaoVerificar      = 'email-comparacao-verificar-campo';
}

/**
 * Operações CRUD suportadas pela interface.
 */
enum OperacaoCrud: string {
	case Adicionar       = 'adicionar';
	case Editar          = 'editar';
	case Excluir         = 'excluir';
	case Clonar          = 'clonar';
	case Visualizar      = 'visualizar';
	case Status          = 'status';
	case Listar          = 'listar';
	case Config          = 'config';
	case Alteracoes      = 'alteracoes';
	case Simples         = 'simples';
	case AdicionarIncomum = 'adicionar-incomum';
	case EditarIncomum   = 'editar-incomum';
}

/**
 * Tipos de campo de formulário suportados.
 */
enum TipoCampo: string {
	case Select          = 'select';
	case ImagePick       = 'imagepick';
	case ImagePickHosts  = 'imagepick-hosts';
	case TemplatesHosts  = 'templates-hosts';
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                            VALUE OBJECTS                                    ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

/**
 * Configuração imutável de uma coluna de tabela.
 * Suporta clone() com named assignments via PHP 8.5 clone with.
 */
final class ColunaConfig {
	public function __construct(
		public readonly string $id,
		public readonly string $nome,
		public readonly ?FormatoTipo $formato = null,
		public readonly bool $ordenavel = true,
		public readonly bool $procuravel = true,
		public readonly bool $visivel = true,
		public readonly ?string $ordem = null,
		public readonly ?string $className = null,
		public readonly ?array $formatoParams = null,
	) {}

	/**
	 * Cria cópia com valores alterados (PHP 8.5 clone with).
	 */
	#[NoDiscard("O clone retornado deve ser utilizado.")]
	public function com(
		?string $nome = null,
		?FormatoTipo $formato = null,
		?bool $ordenavel = null,
		?bool $procuravel = null,
		?bool $visivel = null,
		?string $ordem = null,
		?string $className = null,
		?array $formatoParams = null,
	): self {
		return clone $this with {
			nome: $nome ?? $this->nome,
			formato: $formato ?? $this->formato,
			ordenavel: $ordenavel ?? $this->ordenavel,
			procuravel: $procuravel ?? $this->procuravel,
			visivel: $visivel ?? $this->visivel,
			ordem: $ordem ?? $this->ordem,
			className: $className ?? $this->className,
			formatoParams: $formatoParams ?? $this->formatoParams,
		};
	}
}

/**
 * Configuração imutável de um botão de ação.
 */
final class BotaoConfig {
	public function __construct(
		public readonly string $id,
		public readonly string $rotulo,
		public readonly string $tooltip,
		public readonly string $icone,
		public readonly string $cor = '',
		public readonly ?string $url = null,
		public readonly ?string $callback = null,
		public readonly ?string $icone2 = null,
		public readonly ?string $target = null,
	) {}

	/**
	 * Cria cópia com valores alterados.
	 */
	#[NoDiscard("O clone retornado deve ser utilizado.")]
	public function com(
		?string $rotulo = null,
		?string $tooltip = null,
		?string $icone = null,
		?string $cor = null,
		?string $url = null,
		?string $callback = null,
	): self {
		return clone $this with {
			rotulo: $rotulo ?? $this->rotulo,
			tooltip: $tooltip ?? $this->tooltip,
			icone: $icone ?? $this->icone,
			cor: $cor ?? $this->cor,
			url: $url ?? $this->url,
			callback: $callback ?? $this->callback,
		};
	}
}

/**
 * Configuração imutável de uma opção de ação na listagem.
 */
final class AcaoConfig {
	public function __construct(
		public readonly string $id,
		public readonly string $tooltip,
		public readonly string $icone,
		public readonly string $cor = '',
		public readonly ?string $url = null,
		public readonly ?string $operacao = null,
		public readonly ?string $statusAtual = null,
		public readonly ?string $statusMudar = null,
	) {}
}

/**
 * Configuração imutável de um campo de formulário.
 */
final class CampoConfig {
	public function __construct(
		public readonly string $id,
		public readonly string $nome,
		public readonly TipoCampo $tipo,
		public readonly array $opcoes = [],
		public readonly ?string $tabelaBanco = null,
		public readonly ?string $campoBanco = null,
		public readonly ?string $campoTexto = null,
		public readonly ?string $where = null,
		public readonly ?string $ordemBanco = null,
		public readonly ?string $categoriaId = null,
		public readonly ?string $imagemId = null,
		public readonly ?string $caminho = null,
		public readonly bool $procuravel = false,
		public readonly bool $limpavel = false,
		public readonly bool $multiplo = false,
		public readonly ?string $placeholder = null,
		public readonly ?string $valor = null,
	) {}
}

/**
 * Configuração imutável de uma regra de validação.
 */
final class ValidacaoConfig {
	public function __construct(
		public readonly string $campo,
		public readonly RegraValidacao $regra,
		public readonly string $label,
		public readonly ?string $identificador = null,
		public readonly ?array $comparacao = null,
		public readonly ?array $regrasExtra = null,
		public readonly ?array $removerRegra = null,
		public readonly ?bool $language = null,
	) {}
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                        FORMATADOR (Utility Estático)                        ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

/**
 * Formatação de dados para exibição na interface.
 * 
 * Fornece métodos estáticos puros para transformar dados brutos em
 * representações formatadas para a interface. Pode ser usado com 
 * o pipe operator do PHP 8.5.
 * 
 * Exemplo com pipe operator:
 *   $resultado = $dado
 *       |> FormatadorInterface::dataHora(...)
 *       |> fn($v) => FormatadorInterface::encapsular($v, 'span', 'classe-data');
 */
final class FormatadorInterface {

	/**
	 * Converte datetime para texto formatado.
	 *
	 * @param string $dataHora Datetime no formato YYYY-MM-DD HH:MM:SS
	 * @param string|false $formato Formato personalizado com marcadores: D, ME, A, H, MI, S
	 * @return string Data/hora formatada
	 */
	#[NoDiscard("O valor formatado deve ser utilizado.")]
	public static function dataHora(string $dataHora, string|false $formato = false): string {
		if (!$dataHora) return '';

		$partes = explode(' ', $dataHora);
		[$ano, $mes, $dia] = explode('-', $partes[0]);
		[$hora, $minuto, $segundo] = explode(':', $partes[1] ?? '00:00:00');

		if ($formato === false) {
			return "{$dia}/{$mes}/{$ano} {$hora}h{$minuto}";
		}

		return $formato
			|> (fn(string $f) => str_replace('D', $dia, $f))
			|> (fn(string $f) => str_replace('ME', $mes, $f))
			|> (fn(string $f) => str_replace('A', $ano, $f))
			|> (fn(string $f) => str_replace('H', $hora, $f))
			|> (fn(string $f) => str_replace('MI', $minuto, $f))
			|> (fn(string $f) => str_replace('S', $segundo, $f));
	}

	/**
	 * Converte datetime para data formatada (sem hora).
	 *
	 * @param string $dataHora Datetime no formato YYYY-MM-DD HH:MM:SS
	 * @param string|false $formato Formato personalizado
	 * @return string Data formatada
	 */
	#[NoDiscard("O valor formatado deve ser utilizado.")]
	public static function data(string $dataHora, string|false $formato = false): string {
		if (!$dataHora) return '';

		$partes = explode(' ', $dataHora);
		[$ano, $mes, $dia] = explode('-', $partes[0]);

		if ($formato === false) {
			return "{$dia}/{$mes}/{$ano}";
		}

		return $formato
			|> (fn(string $f) => str_replace('D', $dia, $f))
			|> (fn(string $f) => str_replace('ME', $mes, $f))
			|> (fn(string $f) => str_replace('A', $ano, $f));
	}

	/**
	 * Formata valor monetário em Reais brasileiros.
	 *
	 * @param string|float $valor Valor numérico
	 * @return string Valor formatado (ex: "R$ 1.234,56")
	 */
	#[NoDiscard("O valor formatado deve ser utilizado.")]
	public static function dinheiroReais(string|float $valor): string {
		return 'R$ ' . number_format((float) $valor, 2, ',', '.');
	}

	/**
	 * Substitui valor por lookup em outra tabela do banco.
	 * Usa cache em $_GESTOR para evitar queries repetidas.
	 *
	 * @param string $dado Valor a buscar
	 * @param array $params Parâmetros: tabela, campo_valor, campo_texto, where, cache_key
	 * @return string Valor encontrado ou dado original
	 */
	#[NoDiscard("O valor resolvido deve ser utilizado.")]
	public static function outraTabela(string $dado, array $params): string {
		global $_GESTOR;

		if (!$dado) return '';
		
		$tabela = $params['tabela'];
		$campoValor = $params['campo_valor'];
		$campoTexto = $params['campo_texto'];
		$where = $params['where'] ?? '';
		$cacheKey = "interface-v2-cache-{$tabela}-{$campoValor}-{$dado}";

		// Verificar cache
		if (isset($_GESTOR[$cacheKey])) {
			return $_GESTOR[$cacheKey];
		}

		$resultado = banco_v2()->selectName(
			BancoV2::camposVirgulas([$campoTexto]),
			$tabela,
			"WHERE {$campoValor}='{$dado}'" . ($where ? " AND {$where}" : "")
		);

		$valor = $resultado ? $resultado[0][$campoTexto] : $dado;
		$_GESTOR[$cacheKey] = $valor;

		return $valor;
	}

	/**
	 * Substitui valor por lookup em um conjunto de dados do módulo.
	 *
	 * @param string $dado Valor a buscar
	 * @param array $params Parâmetros: conjunto (array de ['valor','texto']), campo_valor, campo_texto
	 * @return string Texto correspondente ou dado original
	 */
	#[NoDiscard("O valor resolvido deve ser utilizado.")]
	public static function outroConjunto(string $dado, array $params): string {
		if (!$dado) return '';

		$conjunto = $params['conjunto'] ?? $params['dados'] ?? [];
		$campoValor = $params['campo_valor'] ?? 'valor';
		$campoTexto = $params['campo_texto'] ?? 'texto';

		foreach ($conjunto as $item) {
			if ($item[$campoValor] === $dado) {
				return $item[$campoTexto];
			}
		}

		return $dado;
	}

	/**
	 * Substitui valor por lookup em um array simples.
	 *
	 * @param string $dado Valor (chave) a buscar
	 * @param array $params Parâmetros: array associativo [valor => texto]
	 * @return string Texto correspondente ou dado original
	 */
	#[NoDiscard("O valor resolvido deve ser utilizado.")]
	public static function outroArray(string $dado, array $params): string {
		if (!$dado) return '';

		$array = $params['array'] ?? [];
		return $array[$dado] ?? $dado;
	}

	/**
	 * Encapsula valor em tag HTML.
	 *
	 * @param string $dado Valor a encapsular
	 * @param string $tag Tag HTML (ex: 'span', 'strong')
	 * @param string $classe Classe CSS opcional
	 * @param string $estilo Estilo inline opcional
	 * @return string HTML encapsulado
	 */
	#[NoDiscard("O valor encapsulado deve ser utilizado.")]
	public static function encapsular(
		string $dado,
		string $tag = 'span',
		string $classe = '',
		string $estilo = '',
	): string {
		$attrs = '';
		if ($classe) $attrs .= " class=\"{$classe}\"";
		if ($estilo) $attrs .= " style=\"{$estilo}\"";

		return "<{$tag}{$attrs}>{$dado}</{$tag}>";
	}

	/**
	 * Dispatcher de formatação — aplica formato correto baseado no tipo.
	 * Mapeia 1:1 com a v1 `interface_formatar_dado()`.
	 *
	 * @param string $dado Dado a formatar
	 * @param FormatoTipo $formato Tipo de formatação
	 * @param array $params Parâmetros adicionais para o formatador
	 * @return string Dado formatado
	 */
	#[NoDiscard("O dado formatado deve ser utilizado.")]
	public static function formatar(string $dado, FormatoTipo $formato, array $params = []): string {
		return match ($formato) {
			FormatoTipo::DinheiroReais => self::dinheiroReais($dado),
			FormatoTipo::Data         => self::data($dado),
			FormatoTipo::DataHora     => self::dataHora($dado),
			FormatoTipo::OutraTabela  => self::outraTabela($dado, $params),
			FormatoTipo::OutroConjunto => self::outroConjunto($dado, $params),
			FormatoTipo::OutroArray   => self::outroArray($dado, $params),
			FormatoTipo::Encapsular   => self::encapsular($dado, ...array_values($params)),
		};
	}
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                            ALERTA - Gerenciamento                           ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

/**
 * Gerencia alertas da interface (via sessão ou imediato).
 */
final class AlertaInterface {

	/**
	 * Exibe ou armazena um alerta.
	 *
	 * @param string $mensagem Mensagem do alerta
	 * @param bool $imediato Se true, mostra diretamente na página (não via sessão)
	 * @param string|null $redirect URL para redirecionar após armazenar alerta
	 */
	public static function mostrar(string $mensagem, bool $imediato = false, ?string $redirect = null): void {
		global $_GESTOR;

		if ($imediato) {
			$_GESTOR['javascript-vars']['interface']['alerta'] = ['msg' => $mensagem];
			return;
		}

		// Guardar em sessão para exibir no próximo carregamento
		gestor_sessao_variavel(
			$_GESTOR['modulo'] . '-' . 'interface-alerta-' . ($_GESTOR['usuario-id'] ?? 'guest'),
			$mensagem
		);

		if ($redirect) {
			gestor_redirecionar($redirect);
		}
	}

	/**
	 * Recupera alerta da sessão e injeta como variável JS para exibição.
	 */
	public static function imprimir(): void {
		global $_GESTOR;

		$chave = $_GESTOR['modulo'] . '-' . 'interface-alerta-' . ($_GESTOR['usuario-id'] ?? 'guest');
		$msg = gestor_sessao_variavel($chave);

		if (existe($msg)) {
			$_GESTOR['javascript-vars']['interface']['alerta'] = ['msg' => $msg];
			gestor_sessao_variavel($chave, '');
		}
	}
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                        HISTÓRICO - Audit Trail                              ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

/**
 * Sistema de histórico/auditoria de alterações.
 */
final class HistoricoInterface {

	/**
	 * Registra alterações no histórico.
	 *
	 * @param array $alteracoes Array de alterações, cada uma com: campo, alteracao, valor_antes, valor_depois
	 * @param bool $deletar Se true, marca como deleção no histórico
	 * @param string|null $moduloOverride ID do módulo (usa atual se null)
	 */
	public static function incluir(
		array $alteracoes,
		bool $deletar = false,
		?string $moduloOverride = null,
	): void {
		global $_GESTOR;

		$modulo = $moduloOverride ?? $_GESTOR['modulo-id'];
		$moduloConfig = $_GESTOR['modulo#' . $modulo] ?? null;

		if (!$moduloConfig) return;

		$tabela = $moduloConfig['tabela'];
		$registroId = $_GESTOR['modulo-registro-id'] ?? null;

		if (!$registroId && !$deletar) return;

		// Obter ID numérico
		$idNumerico = null;
		if (isset($tabela['id_numerico']) && $registroId) {
			$resultado = banco_v2()->selectName(
				BancoV2::camposVirgulas([$tabela['id_numerico']]),
				$tabela['nome'],
				"WHERE {$tabela['id']}='{$registroId}'"
				. " AND " . ($tabela['status'] ?? 'status') . "!='D'"
			);
			if ($resultado) {
				$idNumerico = $resultado[0][$tabela['id_numerico']];
			}
		}

		// Versão atual
		$versao = 1;
		if (isset($tabela['versao']) && $registroId) {
			$resVersao = banco_v2()->selectName(
				BancoV2::camposVirgulas([$tabela['versao']]),
				$tabela['nome'],
				"WHERE {$tabela['id']}='{$registroId}'"
				. " AND " . ($tabela['status'] ?? 'status') . "!='D'"
			);
			if ($resVersao) {
				$versao = (int) $resVersao[0][$tabela['versao']];
			}
		}

		// Inserir cada alteração no histórico
		foreach ($alteracoes as $alteracao) {
			$campos = null;

			$campo_nome = 'id_referencia'; $campo_valor = $registroId ?? '';
			$campos[] = [$campo_nome, $campo_valor, null];

			if ($idNumerico) {
				$campo_nome = 'id_numerico'; $campo_valor = $idNumerico;
				$campos[] = [$campo_nome, $campo_valor, null];
			}

			$campo_nome = 'modulo'; $campo_valor = $modulo;
			$campos[] = [$campo_nome, $campo_valor, null];

			$campo_nome = 'versao'; $campo_valor = $versao;
			$campos[] = [$campo_nome, $campo_valor, null];

			$campo_nome = 'alteracao'; $campo_valor = $alteracao['alteracao'];
			$campos[] = [$campo_nome, $campo_valor, null];

			if (isset($alteracao['campo'])) {
				$campo_nome = 'campo'; $campo_valor = $alteracao['campo'];
				$campos[] = [$campo_nome, $campo_valor, null];
			}

			if (isset($alteracao['valor_antes'])) {
				$campo_nome = 'valor_antes'; $campo_valor = banco_v2()->escape($alteracao['valor_antes']);
				$campos[] = [$campo_nome, $campo_valor, null];
			}

			if (isset($alteracao['valor_depois'])) {
				$campo_nome = 'valor_depois'; $campo_valor = banco_v2()->escape($alteracao['valor_depois']);
				$campos[] = [$campo_nome, $campo_valor, null];
			}

			$campo_nome = 'id_usuarios'; $campo_valor = $_GESTOR['usuario-id'] ?? '';
			$campos[] = [$campo_nome, $campo_valor, null];

			$campo_nome = 'data'; $campo_valor = 'NOW()';
			$campos[] = [$campo_nome, $campo_valor, true];

			banco_v2()->tabela('historico')->insertName($campos);
		}
	}

	/**
	 * Renderiza seção de histórico em HTML.
	 *
	 * @param string|null $id ID do registro
	 * @param string $modulo ID do módulo
	 * @param string|null $pagina HTML da página para injetar
	 * @param bool $semId Se true, busca histórico sem filtro por ID
	 * @param int $paginaNum Número da página para paginação
	 * @return string HTML do histórico ou página com histórico injetado
	 */
	#[NoDiscard("O HTML renderizado deve ser utilizado.")]
	public static function renderizar(
		?string $id = null,
		string $modulo = '',
		?string $pagina = null,
		bool $semId = false,
		int $paginaNum = 0,
	): string {
		global $_GESTOR;

		$moduloConfig = $_GESTOR['modulo#' . $modulo] ?? null;
		if (!$moduloConfig || !isset($moduloConfig['tabela'])) {
			return $pagina ?? '';
		}

		$tabela = $moduloConfig['tabela'];
		$registroId = $id ?? ($_GESTOR['modulo-registro-id'] ?? '');
		$porPagina = 5;

		// Consultar total  
		$whereHistorico = $semId
			? "WHERE modulo='{$modulo}'"
			: "WHERE id_referencia='{$registroId}' AND modulo='{$modulo}'";

		$total = banco_v2()->selectName(
			'COUNT(*) as total',
			'historico',
			$whereHistorico
		);

		$totalRegistros = $total ? (int) $total[0]['total'] : 0;
		$totalPaginas = max(1, ceil($totalRegistros / $porPagina));
		$offset = $paginaNum * $porPagina;

		// Consultar registros
		$historicos = banco_v2()->selectName(
			BancoV2::camposVirgulas([
				'id_historico', 'alteracao', 'campo', 'valor_antes',
				'valor_depois', 'id_usuarios', 'data', 'versao',
			]),
			'historico',
			$whereHistorico . " ORDER BY data DESC LIMIT {$offset},{$porPagina}"
		);

		// Construir HTML
		$html = '';
		if ($historicos) {
			foreach ($historicos as $h) {
				$dataFormatada = FormatadorInterface::dataHora($h['data']);
				$alteracaoLabel = gestor_variaveis(['modulo' => 'interface', 'id' => $h['alteracao']]) ?: $h['alteracao'];

				// Resolver nome do usuário
				$nomeUsuario = '';
				if (existe($h['id_usuarios'])) {
					$user = banco_v2()->selectName(
						BancoV2::camposVirgulas(['nome']),
						'usuarios',
						"WHERE id_usuarios='{$h['id_usuarios']}'"
					);
					$nomeUsuario = $user ? $user[0]['nome'] : $h['id_usuarios'];
				}

				$campoLabel = '';
				if (existe($h['campo'])) {
					$campoLabel = gestor_variaveis(['modulo' => $modulo, 'id' => $h['campo']]) ?: $h['campo'];
				}

				$html .= '<div class="iv2-historico-item">';
				$html .= '<div class="iv2-historico-meta">';
				$html .= '<span class="iv2-historico-data"><i class="clock outline icon"></i>' . $dataFormatada . '</span>';
				if ($nomeUsuario) {
					$html .= '<span class="iv2-historico-usuario"><i class="user icon"></i>' . htmlspecialchars($nomeUsuario) . '</span>';
				}
				$html .= '<span class="iv2-historico-versao">v' . $h['versao'] . '</span>';
				$html .= '</div>';
				$html .= '<div class="iv2-historico-body">';
				$html .= '<strong>' . htmlspecialchars($alteracaoLabel) . '</strong>';
				if ($campoLabel) {
					$html .= ' — <em>' . htmlspecialchars($campoLabel) . '</em>';
				}
				$html .= '</div></div>';
			}

			// Botão "carregar mais"
			if ($totalPaginas > 1 && $paginaNum === 0) {
				$html .= '<div class="ui center aligned basic segment">';
				$html .= '<div id="_iv2-historico-mais" class="ui basic button"><i class="angle double down icon"></i>';
				$html .= gestor_variaveis(['modulo' => 'interface', 'id' => 'history-load-more']) ?: 'Carregar mais';
				$html .= '</div></div>';

				$_GESTOR['javascript-vars']['interface-v2']['historico'] = [
					'totalPaginas' => $totalPaginas,
				];
			}
		}

		// Injetar na página ou retornar
		if ($pagina !== null) {
			if (str_contains($pagina, '#historico#')) {
				$pagina = modelo_var_troca($pagina, '#historico#', $html);
			} else {
				$pagina = modelo_tag_in(
					$pagina,
					'<!-- historico < -->',
					'<!-- historico > -->',
					$html ? $html : ''
				);
			}
			return $pagina;
		}

		return $html;
	}
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                          BACKUP - Field Versioning                          ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

/**
 * Sistema de backup de campos com versionamento.
 */
final class BackupInterface {

	/**
	 * Inclui backup de um campo.
	 */
	public static function incluir(
		string $campo,
		int $idNumerico,
		int $versao,
		string $valor,
		?string $modulo = null,
		int $maxCopias = 20,
	): void {
		global $_GESTOR;

		$modulo ??= $_GESTOR['modulo-id'];

		$campos = null;
		$campos[] = ['modulo', $modulo, null];
		$campos[] = ['id', $idNumerico, null];
		$campos[] = ['versao', $versao, null];
		$campos[] = ['campo', $campo, null];
		$campos[] = ['valor', banco_v2()->escape($valor), null];
		$campos[] = ['data', 'NOW()', true];

		banco_v2()->tabela('backup_campos')->insertName($campos);

		// Limpar cópias antigas
		$backups = banco_v2()->selectName(
			BancoV2::camposVirgulas(['id_backup_campos']),
			'backup_campos',
			"WHERE id='{$idNumerico}' AND modulo='{$modulo}' AND campo='{$campo}' ORDER BY data ASC"
		);

		if ($backups && count($backups) > $maxCopias) {
			banco_v2()->deletar(
				'backup_campos',
				"WHERE id_backup_campos='{$backups[0]['id_backup_campos']}'"
			);
		}
	}

	/**
	 * Renderiza dropdown de seleção de versões de backup.
	 */
	#[NoDiscard("O HTML do seletor deve ser utilizado.")]
	public static function seletor(
		string $campo,
		int $idNumerico,
		string $callback = 'callBackNotSet',
		?string $campoForm = null,
		?string $modulo = null,
	): string {
		global $_GESTOR;

		$modulo ??= $_GESTOR['modulo-id'];
		$campoForm ??= $campo;

		$backups = banco_v2()->selectName(
			BancoV2::camposVirgulas(['id_backup_campos', 'versao', 'data']),
			'backup_campos',
			"WHERE id='{$idNumerico}' AND modulo='{$modulo}' AND campo='{$campo}' ORDER BY data DESC"
		);

		if (!$backups) return '';

		$dropdown = gestor_componente(['id' => 'interface-backup-dropdown']);

		$cel_nome = 'item';
		$cel[$cel_nome] = modelo_tag_val($dropdown, '<!-- ' . $cel_nome . ' < -->', '<!-- ' . $cel_nome . ' > -->');
		$dropdown = modelo_tag_in($dropdown, '<!-- ' . $cel_nome . ' < -->', '<!-- ' . $cel_nome . ' > -->', '<!-- ' . $cel_nome . ' -->');

		$dropdown = modelo_var_troca($dropdown, '#id-numerico#', (string) $idNumerico);
		$dropdown = modelo_var_troca($dropdown, '#campo#', $campo);
		$dropdown = modelo_var_troca($dropdown, '#campo_form#', $campoForm);
		$dropdown = modelo_var_troca($dropdown, '#callback#', $callback);

		// Versão atual
		$moduloConfig = $_GESTOR['modulo#' . $_GESTOR['modulo-id']];
		$versaoAtual = interface_modulo_variavel_valor(['variavel' => $moduloConfig['tabela']['versao']]);

		$dropdown = modelo_var_troca($dropdown, '#versao-atual-label#', 'Versão Atual Selecionada');
		$dropdown = modelo_var_troca($dropdown, '#versao-atual-description#', 'Versão ' . $versaoAtual);
		$dropdown = modelo_var_troca($dropdown, '#versao-atual-icon#', 'file alternate');

		// Item versão atual
		$celAux = $cel[$cel_nome];
		$celAux = modelo_var_troca($celAux, '#id#', '');
		$celAux = modelo_var_troca($celAux, '#data#', 'Versão Atual Selecionada');
		$celAux = modelo_var_troca($celAux, '#versao#', 'Versão ' . $versaoAtual);
		$celAux = modelo_var_troca($celAux, '#icon#', 'file alternate');
		$dropdown = modelo_var_in($dropdown, '<!-- ' . $cel_nome . ' -->', $celAux);

		// Items de backup
		foreach ($backups as $backup) {
			$dataFmt = FormatadorInterface::dataHora($backup['data']);
			$versaoNum = ((int) $backup['versao'] - 1);

			$celAux = $cel[$cel_nome];
			$celAux = modelo_var_troca($celAux, '#id#', $backup['id_backup_campos']);
			$celAux = modelo_var_troca($celAux, '#data#', $dataFmt);
			$celAux = modelo_var_troca($celAux, '#versao#', 'Versão ' . $versaoNum);
			$celAux = modelo_var_troca($celAux, '#icon#', 'file alternate outline');
			$dropdown = modelo_var_in($dropdown, '<!-- ' . $cel_nome . ' -->', $celAux);
		}

		$dropdown = modelo_var_troca($dropdown, '<!-- ' . $cel_nome . ' -->', '');
		return $dropdown;
	}
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                      COMPONENTES - Modal / UI Manager                       ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

/**
 * Gerenciador de componentes (modais, iframes, etc.).
 */
final class ComponentesInterface {
	/** @var array<string, bool> Componentes enfileirados para inclusão  */
	private static array $fila = [];

	/** Mapeamento de slug → componente HTML e módulo extra */
	private const MAPA = [
		'modal-carregamento' => ['id' => 'interface-carregando-modal', 'modulos' => ['interface']],
		'modal-delecao'      => ['id' => 'interface-delecao-modal',    'modulos' => ['interface']],
		'modal-alerta'       => ['id' => 'interface-alerta-modal',     'modulos' => ['interface']],
		'modal-iframe'       => ['id' => 'interface-iframe-modal',     'modulos' => ['interface']],
	];

	/**
	 * Enfileira componentes para inclusão.
	 *
	 * @param string ...$componentes Slugs dos componentes
	 */
	public static function incluir(string ...$componentes): void {
		foreach ($componentes as $comp) {
			self::$fila[$comp] = true;
		}
	}

	/**
	 * Renderiza e injeta todos os componentes enfileirados na página.
	 */
	public static function renderizar(): void {
		global $_GESTOR;

		foreach (self::$fila as $slug => $_) {
			$mapa = self::MAPA[$slug] ?? null;
			if (!$mapa) continue;

			$componente = gestor_componente([
				'id' => $mapa['id'],
				'modulosExtra' => $mapa['modulos'],
			]);

			// Substituir variáveis padrão dos componentes
			switch ($slug) {
				case 'modal-delecao':
					$componente = modelo_var_troca($componente, '#titulo#', gestor_variaveis(['modulo' => 'interface', 'id' => 'delete-confirm-title']));
					$componente = modelo_var_troca($componente, '#mensagem#', gestor_variaveis(['modulo' => 'interface', 'id' => 'delete-confirm-menssage']));
					$componente = modelo_var_troca($componente, '#botao-cancelar#', gestor_variaveis(['modulo' => 'interface', 'id' => 'delete-confirm-button-cancel']));
					$componente = modelo_var_troca($componente, '#botao-confirmar#', gestor_variaveis(['modulo' => 'interface', 'id' => 'delete-confirm-button-confirm']));
					break;
			}

			// Injetar no final da página
			if (isset($_GESTOR['pagina'])) {
				$_GESTOR['pagina'] .= $componente;
			}
		}

		self::$fila = [];
	}
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                      BOTÕES - Header/Footer Builder                         ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

/**
 * Construtor de botões para cabeçalho e rodapé.
 */
final class BotoesInterface {

	/**
	 * Renderiza botões do cabeçalho.
	 *
	 * @param BotaoConfig[] $botoes
	 * @return string HTML dos botões
	 */
	#[NoDiscard("O HTML de botões deve ser incluído na página.")]
	public static function cabecalho(array $botoes): string {
		$html = '';

		foreach ($botoes as $botao) {
			$html .= match ($botao->id) {
				'excluir' => self::renderBotaoExcluir($botao),
				default   => $botao->callback
					? self::renderBotaoCallback($botao)
					: self::renderBotaoLink($botao),
			};
		}

		return $html;
	}

	/**
	 * Renderiza botões do rodapé.
	 *
	 * @param BotaoConfig[] $botoes
	 * @return string HTML dos botões
	 */
	#[NoDiscard("O HTML de botões deve ser incluído na página.")]
	public static function rodape(array $botoes): string {
		// Mesma lógica do cabeçalho
		return self::cabecalho($botoes);
	}

	private static function renderBotaoExcluir(BotaoConfig $b): string {
		return <<<HTML
		<div class="ui button excluir {$b->cor}" data-href="{$b->url}" data-content="{$b->tooltip}" data-id="{$b->id}">
			<i class="{$b->icone} icon"></i>
			{$b->rotulo}
		</div>
		HTML;
	}

	private static function renderBotaoCallback(BotaoConfig $b): string {
		return <<<HTML
		<div class="ui button {$b->callback} {$b->cor}" data-content="{$b->tooltip}" data-id="{$b->id}">
			<i class="{$b->icone} icon"></i>
			{$b->rotulo}
		</div>
		HTML;
	}

	private static function renderBotaoLink(BotaoConfig $b): string {
		$target = $b->target ? " target=\"{$b->target}\"" : '';
		$iconHtml = $b->icone2
			? "<i class=\"icons\"><i class=\"{$b->icone} icon\"></i><i class=\"{$b->icone2} icon\"></i></i>"
			: "<i class=\"{$b->icone} icon\"></i>";

		return <<<HTML
		<a class="ui button {$b->cor}" href="{$b->url}" data-content="{$b->tooltip}" data-id="{$b->id}"{$target}>
			{$iconHtml}
			{$b->rotulo}
		</a>
		HTML;
	}
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║                   INTERFACE V2 - Classe Principal (Facade)                  ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

/**
 * Classe principal da Interface V2 — Facade com method chaining.
 * 
 * Centraliza toda a construção de interfaces administrativas
 * usando um padrão fluent builder.
 * 
 * Exemplo de uso:
 * 
 *   InterfaceV2::criar()
 *       ->banco('paginas', 'id', status: 'status')
 *       ->where("language='pt-br'")
 *       ->coluna('nome', 'Nome')
 *       ->coluna('data_modificacao', 'Data', formato: FormatoTipo::DataHora, ordem: 'desc')
 *       ->acao('editar', icone: 'edit', cor: 'blue', tooltip: 'Editar')
 *       ->acao('excluir', icone: 'trash', cor: 'red', tooltip: 'Excluir')
 *       ->botao('adicionar', rotulo: 'Nova Página', icone: 'plus', cor: 'green', url: '?opcao=adicionar')
 *       ->listar();
 */
final class InterfaceV2 {

	// === Estado interno ===

	/** @var ColunaConfig[] Colunas da tabela */
	private array $colunas = [];

	/** @var AcaoConfig[] Ações de cada linha  */
	private array $acoes = [];

	/** @var BotaoConfig[] Botões de cabeçalho */
	private array $botoes = [];

	/** @var BotaoConfig[] Botões de rodapé */
	private array $botoesRodape = [];

	/** @var CampoConfig[] Campos de formulário */
	private array $campos = [];

	/** @var ValidacaoConfig[] Regras de validação */
	private array $validacoes = [];

	/** @var array Configuração do banco de dados */
	private array $bancoConfig = [];

	/** @var string|null Cláusula WHERE adicional */
	private ?string $whereClause = null;

	/** @var bool Mostrar rodapé na tabela */
	private bool $comRodape = false;

	/** @var array<string,mixed> Variáveis para substituir depois */
	private array $variaveisTrocar = [];

	/** @var array<string,mixed> Meta dados do registro */
	private array $metaDados = [];

	/** @var bool Remover checkbox "não alterar ID" */
	private bool $removerNaoAlterarId = false;

	/** @var bool Remover botão editar */
	private bool $removerBotaoEditar = false;

	/** @var bool Remover botão padrão */
	private bool $semBotaoPadrao = false;

	/** @var string|null Cabeçalho HTML personalizado da listagem */
	private ?string $cabecalhoHtml = null;

	/** @var string[]|null Colunas extras para busca */
	private ?array $colunasExtraBusca = null;

	/** @var string|null Campo do título (default: 'nome') */
	private ?string $campoTitulo = null;

	/** @var bool Forçar sem ID */
	private bool $forcarSemId = false;

	/** @var string|null ID forçado para operações */
	private ?string $forcarId = null;

	/** @var callable|null Callback após exclusão */
	private $callbackExclusao = null;

	/** @var Closure|null Callback para sucesso do form */
	private ?\Closure $formOnSuccess = null;

	// === Factory ===

	/**
	 * Cria nova instância de InterfaceV2.
	 * 
	 * @return self Nova instância pronta para configuração
	 */
	public static function criar(): self {
		return new self();
	}

	private function __construct() {}

	// === Configuração de Banco/Tabela ===

	/**
	 * Define a tabela do banco de dados para operações.
	 *
	 * @param string $nome Nome da tabela
	 * @param string $id Campo ID principal
	 * @param string $status Campo de status (default: 'status')
	 * @param string|null $idNumerico Campo ID numérico
	 * @param string|null $versao Campo de versão
	 * @param string|null $dataCriacao Campo de data criação
	 * @param string|null $dataModificacao Campo de data modificação
	 * @param string|null $nomeEspecifico Campo nome específico
	 * @return self
	 */
	public function banco(
		string $nome,
		string $id,
		string $status = 'status',
		?string $idNumerico = null,
		?string $versao = null,
		?string $dataCriacao = null,
		?string $dataModificacao = null,
		?string $nomeEspecifico = null,
	): self {
		$this->bancoConfig = [
			'nome' => $nome,
			'id' => $id,
			'status' => $status,
			'id_numerico' => $idNumerico ?? "id_{$nome}",
			'versao' => $versao ?? 'versao',
			'data_criacao' => $dataCriacao ?? 'data_criacao',
			'data_modificacao' => $dataModificacao ?? 'data_modificacao',
			'nome_especifico' => $nomeEspecifico,
		];
		return $this;
	}

	/**
	 * Define cláusula WHERE adicional.
	 */
	public function where(string $where): self {
		$this->whereClause = $where;
		return $this;
	}

	// === Colunas ===

	/**
	 * Adiciona coluna à tabela de listagem.
	 *
	 * @param string $id ID do campo no banco
	 * @param string $nome Label da coluna
	 * @param FormatoTipo|null $formato Tipo de formatação
	 * @param array|null $formatoParams Parâmetros extras do formatador
	 * @param bool $ordenavel Se pode ser ordenada
	 * @param bool $procuravel Se pode ser buscada
	 * @param bool $visivel Se é visível
	 * @param string|null $ordem Direção de ordenação inicial ('asc' ou 'desc')
	 * @param string|null $className Classe CSS extra
	 * @return self
	 */
	public function coluna(
		string $id,
		string $nome,
		?FormatoTipo $formato = null,
		?array $formatoParams = null,
		bool $ordenavel = true,
		bool $procuravel = true,
		bool $visivel = true,
		?string $ordem = null,
		?string $className = null,
	): self {
		$this->colunas[] = new ColunaConfig(
			id: $id,
			nome: $nome,
			formato: $formato,
			ordenavel: $ordenavel,
			procuravel: $procuravel,
			visivel: $visivel,
			ordem: $ordem,
			className: $className,
			formatoParams: $formatoParams,
		);
		return $this;
	}

	/**
	 * Ativa rodapé na tabela de listagem.
	 */
	public function rodape(bool $ativo = true): self {
		$this->comRodape = $ativo;
		return $this;
	}

	/**
	 * Define colunas extras para busca (IDs de campos não-visíveis).
	 */
	public function colunasExtraBusca(string ...$colunas): self {
		$this->colunasExtraBusca = $colunas;
		return $this;
	}

	/**
	 * Define HTML personalizado no cabeçalho da listagem.
	 */
	public function cabecalho(string $html): self {
		$this->cabecalhoHtml = $html;
		return $this;
	}

	// === Ações (opções por registro na listagem) ===

	/**
	 * Adiciona ação na listagem.
	 *
	 * @param string $id Identificador único
	 * @param string $icone Classe do ícone Semantic UI
	 * @param string $tooltip Texto do tooltip
	 * @param string $cor Cor do botão Semantic UI
	 * @param string|null $url URL de destino
	 * @param string|null $operacao Operação CRUD (alternativa a url)
	 * @return self
	 */
	public function acao(
		string $id,
		string $icone,
		string $tooltip,
		string $cor = '',
		?string $url = null,
		?string $operacao = null,
	): self {
		$this->acoes[] = new AcaoConfig(
			id: $id,
			tooltip: $tooltip,
			icone: $icone,
			cor: $cor,
			url: $url,
			operacao: $operacao ?? $id,
		);
		return $this;
	}

	/**
	 * Atalho para ação de excluir.
	 */
	public function acaoExcluir(
		string $tooltip = 'Excluir',
		string $icone = 'trash alternate',
		string $cor = 'red',
	): self {
		return $this->acao('excluir', icone: $icone, tooltip: $tooltip, cor: $cor, operacao: 'excluir');
	}

	/**
	 * Adiciona ação de mudança de status.
	 */
	public function acaoStatus(
		string $id,
		string $icone,
		string $tooltip,
		string $cor,
		string $statusAtual,
		string $statusMudar,
	): self {
		$this->acoes[] = new AcaoConfig(
			id: $id,
			tooltip: $tooltip,
			icone: $icone,
			cor: $cor,
			operacao: 'status',
			statusAtual: $statusAtual,
			statusMudar: $statusMudar,
		);
		return $this;
	}

	// === Botões ===

	/**
	 * Adiciona botão ao cabeçalho.
	 */
	public function botao(
		string $id,
		string $rotulo,
		string $tooltip,
		string $icone,
		string $cor = '',
		?string $url = null,
		?string $callback = null,
		?string $icone2 = null,
		?string $target = null,
	): self {
		$this->botoes[] = new BotaoConfig(
			id: $id, rotulo: $rotulo, tooltip: $tooltip, icone: $icone,
			cor: $cor, url: $url, callback: $callback, icone2: $icone2, target: $target,
		);
		return $this;
	}

	/**
	 * Adiciona botão ao rodapé.
	 */
	public function botaoRodape(
		string $id,
		string $rotulo,
		string $tooltip,
		string $icone,
		string $cor = '',
		?string $url = null,
		?string $callback = null,
	): self {
		$this->botoesRodape[] = new BotaoConfig(
			id: $id, rotulo: $rotulo, tooltip: $tooltip, icone: $icone,
			cor: $cor, url: $url, callback: $callback,
		);
		return $this;
	}

	// === Formulário: Campos ===

	/**
	 * Adiciona campo de formulário.
	 */
	public function campo(
		string $id,
		string $nome,
		TipoCampo $tipo,
		array $opcoes = [],
		?string $tabelaBanco = null,
		?string $campoBanco = null,
		?string $campoTexto = null,
		?string $where = null,
		?string $ordemBanco = null,
		?string $categoriaId = null,
		?string $imagemId = null,
		?string $caminho = null,
		bool $procuravel = false,
		bool $limpavel = false,
		bool $multiplo = false,
		?string $placeholder = null,
		?string $valor = null,
	): self {
		$this->campos[] = new CampoConfig(
			id: $id, nome: $nome, tipo: $tipo, opcoes: $opcoes,
			tabelaBanco: $tabelaBanco, campoBanco: $campoBanco, campoTexto: $campoTexto,
			where: $where, ordemBanco: $ordemBanco, categoriaId: $categoriaId,
			imagemId: $imagemId, caminho: $caminho, procuravel: $procuravel,
			limpavel: $limpavel, multiplo: $multiplo, placeholder: $placeholder, valor: $valor,
		);
		return $this;
	}

	/**
	 * Atalho: adiciona campo select.
	 */
	public function select(
		string $id,
		string $nome,
		array $opcoes = [],
		?string $tabelaBanco = null,
		?string $campoBanco = null,
		?string $campoTexto = null,
		?string $where = null,
		?string $ordemBanco = null,
		bool $procuravel = false,
		bool $limpavel = false,
		bool $multiplo = false,
		?string $placeholder = null,
		?string $valor = null,
	): self {
		return $this->campo(
			id: $id, nome: $nome, tipo: TipoCampo::Select, opcoes: $opcoes,
			tabelaBanco: $tabelaBanco, campoBanco: $campoBanco, campoTexto: $campoTexto,
			where: $where, ordemBanco: $ordemBanco, procuravel: $procuravel,
			limpavel: $limpavel, multiplo: $multiplo, placeholder: $placeholder, valor: $valor,
		);
	}

	/**
	 * Atalho: adiciona campo image picker.
	 */
	public function imagePick(
		string $id,
		string $nome,
		?string $imagemId = null,
		?string $caminho = null,
	): self {
		return $this->campo(
			id: $id, nome: $nome, tipo: TipoCampo::ImagePick,
			imagemId: $imagemId, caminho: $caminho,
		);
	}

	// === Formulário: Validação ===

	/**
	 * Adiciona regra de validação client-side.
	 */
	public function validacao(
		string $campo,
		RegraValidacao $regra,
		string $label,
		?string $identificador = null,
		?array $comparacao = null,
		?array $regrasExtra = null,
		?array $removerRegra = null,
		?bool $language = null,
	): self {
		$this->validacoes[] = new ValidacaoConfig(
			campo: $campo, regra: $regra, label: $label,
			identificador: $identificador, comparacao: $comparacao,
			regrasExtra: $regrasExtra, removerRegra: $removerRegra, language: $language,
		);
		return $this;
	}

	// === Meta / Layout ===

	/**
	 * Adiciona meta dado ao formulário de edição.
	 */
	public function metaDado(string $titulo, string $dado): self {
		$this->metaDados[] = ['titulo' => $titulo, 'dado' => $dado];
		return $this;
	}

	/**
	 * Adiciona variável para substituir depois da renderização.
	 */
	public function variavel(string $chave, string $valor): self {
		$this->variaveisTrocar[$chave] = $valor;
		return $this;
	}

	/**
	 * Remove checkbox "não alterar ID" no formulário de edição.
	 */
	public function removerNaoAlterarId(bool $remover = true): self {
		$this->removerNaoAlterarId = $remover;
		return $this;
	}

	/**
	 * Remove botão editar do formulário.
	 */
	public function removerBotaoEditar(bool $remover = true): self {
		$this->removerBotaoEditar = $remover;
		return $this;
	}

	/**
	 * Remove botão padrão (submit) do formulário.
	 */
	public function semBotaoPadrao(bool $sem = true): self {
		$this->semBotaoPadrao = $sem;
		return $this;
	}

	/**
	 * Define campo de título para a página.
	 */
	public function campoTitulo(string $campo): self {
		$this->campoTitulo = $campo;
		return $this;
	}

	/**
	 * Força um ID específico para edição/visualização.
	 */
	public function forcarId(string $id): self {
		$this->forcarId = $id;
		return $this;
	}

	/**
	 * Define callback após exclusão.
	 */
	public function aoExcluir(callable $callback): self {
		$this->callbackExclusao = $callback;
		return $this;
	}

	/**
	 * Define callback personalizado para sucesso do formulário (JS).
	 */
	public function aoSalvar(\Closure $callback): self {
		$this->formOnSuccess = $callback;
		return $this;
	}

	// ╔════════════════════════════════════════════════════════════════╗
	// ║                 OPERAÇÕES: Executores Finais                   ║
	// ╚════════════════════════════════════════════════════════════════╝

	/**
	 * Executa a operação de LISTAGEM.
	 * Monta a tabela DataTable com os dados, colunas, ações e botões configurados.
	 */
	public function listar(): void {
		global $_GESTOR;

		$banco = $this->construirBancoArray();
		$tabela = $this->construirTabelaArray();
		$opcoes = $this->construirOpcoesArray();
		
		// === Modal de deleção ===
		ComponentesInterface::incluir('modal-delecao');

		// === Layout da lista ===
		$pagina = gestor_componente(['id' => 'interface-listar']);

		$listaTabelaHtml = $this->renderizarTabela($banco, $tabela, $opcoes);

		// Modal de deleção
		$modalDelecao = gestor_componente(['id' => 'interface-delecao-modal']);
		$modalDelecao = modelo_var_troca($modalDelecao, '#titulo#', gestor_variaveis(['modulo' => 'interface', 'id' => 'delete-confirm-title']));
		$modalDelecao = modelo_var_troca($modalDelecao, '#mensagem#', gestor_variaveis(['modulo' => 'interface', 'id' => 'delete-confirm-menssage']));
		$modalDelecao = modelo_var_troca($modalDelecao, '#botao-cancelar#', gestor_variaveis(['modulo' => 'interface', 'id' => 'delete-confirm-button-cancel']));
		$modalDelecao = modelo_var_troca($modalDelecao, '#botao-confirmar#', gestor_variaveis(['modulo' => 'interface', 'id' => 'delete-confirm-button-confirm']));

		$pagina = modelo_var_troca($pagina, '#titulo#', $_GESTOR['pagina#titulo']);
		$pagina = modelo_var_troca($pagina, '#lista#', $listaTabelaHtml . $modalDelecao);

		// Botões
		if ($this->botoes) {
			$pagina = modelo_var_troca($pagina, '#botoes#', BotoesInterface::cabecalho($this->botoes));
		} else {
			$pagina = modelo_tag_in($pagina, '<!-- botoes < -->', '<!-- botoes > -->', '');
		}

		// Cabeçalho personalizado
		if ($this->cabecalhoHtml) {
			$pagina = modelo_var_troca($pagina, '#cabecalho#', $this->cabecalhoHtml);
		} else {
			$pagina = modelo_tag_in($pagina, '<!-- cabecalho < -->', '<!-- cabecalho > -->', '');
		}

		// Incluir na página
		$_GESTOR['pagina'] = (isset($_GESTOR['pagina']) ? $_GESTOR['pagina'] . $pagina : $pagina);

		// Assets
		$this->incluirAssets(datatable: true);
	}

	/**
	 * Executa a operação de ADICIONAR.
	 * Renderiza formulário de inclusão com validação.
	 */
	public function adicionar(): void {
		global $_GESTOR;

		// Formulário de inclusão
		$pagina = gestor_componente(['id' => 'interface-formulario-inclusao']);

		$pagina = modelo_var_troca($pagina, '#titulo#', $_GESTOR['pagina#titulo']);
		$pagina = modelo_var_troca($pagina, '#form-id#', $_GESTOR['modulo']);
		$pagina = modelo_var_troca($pagina, '#form-name#', $_GESTOR['modulo']);
		$pagina = modelo_var_troca($pagina, '#form-action#', $_GESTOR['url-raiz'] . $_GESTOR['caminho-total']);
		$pagina = modelo_var_troca($pagina, '#form-button-title#', gestor_variaveis(['modulo' => 'interface', 'id' => 'form-button-title']));

		if ($this->semBotaoPadrao) {
			$pagina = modelo_tag_del($pagina, '<!-- botao-padrao < -->', '<!-- botao-padrao > -->', '');
		}

		$pagina = modelo_var_troca($pagina, '#form-button-value#', gestor_variaveis(['modulo' => 'interface', 'id' => 'form-button-value']));

		// Botões cabeçalho
		if ($this->botoes) {
			$pagina = modelo_var_troca($pagina, '#botoes#', BotoesInterface::cabecalho($this->botoes));
		} else {
			$pagina = modelo_tag_del($pagina, '<!-- botoes < -->', '<!-- botoes > -->', '');
		}

		// Botões rodapé
		if ($this->botoesRodape) {
			$pagina = modelo_var_troca($pagina, '#botoes-rodape#', BotoesInterface::rodape($this->botoesRodape));
		} else {
			$pagina = modelo_tag_del($pagina, '<!-- botoes-rodape < -->', '<!-- botoes-rodape > -->', '');
		}

		$pagina = modelo_var_troca($pagina, '#form-page#', $_GESTOR['pagina']);

		// Substituir variáveis de formulário do módulo
		$pagina = $this->substituirVariaveisFormulario($pagina);
		$pagina = $this->substituirVariaveisExtras($pagina);

		$_GESTOR['pagina'] = $pagina;

		// Processar campos e validação
		$this->processarFormulario();
		$this->incluirAssets();
	}

	/**
	 * Executa a operação de EDITAR.
	 * Renderiza formulário de edição com histórico, meta-dados e validação.
	 */
	public function editar(): void {
		global $_GESTOR;

		// Componentes
		ComponentesInterface::incluir('modal-carregamento', 'modal-delecao', 'modal-alerta');

		// Formulário de edição
		$pagina = gestor_componente(['id' => 'interface-formulario-edicao']);

		// Remover "não alterar id"
		if ($this->removerNaoAlterarId) {
			$pagina = modelo_tag_in($pagina, '<!-- nao-alterar-id < -->', '<!-- nao-alterar-id > -->', '');
		}

		// Variáveis do layout
		$pagina = modelo_var_troca($pagina, '#titulo#', $_GESTOR['pagina#titulo']);

		$modulo = $_GESTOR['modulo#' . $_GESTOR['modulo-id']];
		$campoNome = $modulo['tabela']['nome_especifico'] ?? 'nome';
		$_GESTOR['pagina#titulo-extra'] = interface_modulo_variavel_valor(['variavel' => $campoNome]) . ' - ';

		$pagina = modelo_var_troca($pagina, '#form-id#', $_GESTOR['modulo']);
		$pagina = modelo_var_troca($pagina, '#form-name#', $_GESTOR['modulo']);
		$pagina = modelo_var_troca($pagina, '#form-action#', $_GESTOR['url-raiz'] . $_GESTOR['caminho-total']);
		$pagina = modelo_var_troca($pagina, '#form-registro-id#', $_GESTOR['modulo-registro-id']);
		$pagina = modelo_var_troca($pagina, '#form-button-title#', gestor_variaveis(['modulo' => 'interface', 'id' => 'form-button-title']));
		$pagina = modelo_var_troca($pagina, '#form-button-value#', gestor_variaveis(['modulo' => 'interface', 'id' => 'form-button-value']));
		$pagina = modelo_var_troca($pagina, '#form-nao-alterar-id-label#', gestor_variaveis(['modulo' => 'interface', 'id' => 'form-nao-alterar-id-label']));

		if ($this->semBotaoPadrao) {
			$pagina = modelo_tag_in($pagina, '<!-- botao-padrao < -->', '<!-- botao-padrao > -->', '');
		}

		if ($this->removerBotaoEditar) {
			$pagina = modelo_tag_in($pagina, '<!-- botao-editar < -->', '<!-- botao-editar > -->', '');
		}

		// Botões cabeçalho
		if ($this->botoes) {
			$pagina = modelo_var_troca($pagina, '#botoes#', BotoesInterface::cabecalho($this->botoes));
		} else {
			$pagina = modelo_tag_in($pagina, '<!-- botoes < -->', '<!-- botoes > -->', '');
		}

		// Botões rodapé
		if ($this->botoesRodape) {
			$pagina = modelo_var_troca($pagina, '#botoes-rodape#', BotoesInterface::rodape($this->botoesRodape));
		} else {
			$pagina = modelo_tag_del($pagina, '<!-- botoes-rodape < -->', '<!-- botoes-rodape > -->', '');
		}

		$pagina = modelo_var_troca($pagina, '#form-page#', $_GESTOR['pagina']);

		// Meta dados
		$pagina = $this->renderizarMetaDados($pagina);

		// Substituir variáveis
		$pagina = $this->substituirVariaveisFormulario($pagina);

		// Histórico
		$pagina = HistoricoInterface::renderizar(
			modulo: $_GESTOR['modulo-id'],
			pagina: $pagina,
		);

		$pagina = $this->substituirVariaveisExtras($pagina);

		$_GESTOR['pagina'] = $pagina;

		// Processar campos e validação
		$this->processarFormulario();
		$this->incluirAssets();

		$_GESTOR['javascript-vars']['moduloRegistroId'] = $_GESTOR['modulo-registro-id'];
	}

	/**
	 * Executa a operação de VISUALIZAR.
	 */
	public function visualizar(): void {
		global $_GESTOR;

		ComponentesInterface::incluir('modal-delecao', 'modal-alerta');

		$pagina = gestor_componente(['id' => 'interface-formulario-visualizacao']);

		$pagina = modelo_var_troca($pagina, '#titulo#', $_GESTOR['pagina#titulo']);

		if (!$this->forcarSemId) {
			$campoTitulo = $this->campoTitulo ?? 'nome';
			$_GESTOR['pagina#titulo-extra'] = interface_modulo_variavel_valor(['variavel' => $campoTitulo]) . ' - ';
		}

		// Botões
		if ($this->botoes) {
			$pagina = modelo_var_troca($pagina, '#botoes#', BotoesInterface::cabecalho($this->botoes));
		} else {
			$pagina = modelo_tag_in($pagina, '<!-- botoes < -->', '<!-- botoes > -->', '');
		}

		$pagina = modelo_var_troca($pagina, '#page#', $_GESTOR['pagina']);

		// Meta dados
		$pagina = $this->renderizarMetaDados($pagina);

		// Histórico
		if (!$this->forcarSemId) {
			$pagina = HistoricoInterface::renderizar(
				modulo: $_GESTOR['modulo-id'],
				pagina: $pagina,
			);
		}

		$pagina = $this->substituirVariaveisExtras($pagina);

		$_GESTOR['pagina'] = $pagina;
		$this->incluirAssets();

		$_GESTOR['javascript-vars']['moduloRegistroId'] = $_GESTOR['modulo-registro-id'];
	}

	/**
	 * Executa a operação de CONFIGURAÇÕES.
	 */
	public function config(): void {
		global $_GESTOR;

		ComponentesInterface::incluir('modal-carregamento', 'modal-delecao', 'modal-alerta');

		$pagina = gestor_componente(['id' => 'interface-formulario-configuracoes']);

		$pagina = modelo_var_troca($pagina, '#titulo#', $_GESTOR['pagina#titulo']);
		$pagina = modelo_var_troca($pagina, '#form-id#', $_GESTOR['modulo']);
		$pagina = modelo_var_troca($pagina, '#form-name#', $_GESTOR['modulo']);
		$pagina = modelo_var_troca($pagina, '#form-action#', $_GESTOR['url-raiz'] . $_GESTOR['caminho-total']);
		$pagina = modelo_var_troca($pagina, '#form-button-title#', gestor_variaveis(['modulo' => 'interface', 'id' => 'form-button-title']));
		$pagina = modelo_var_troca($pagina, '#form-button-value#', gestor_variaveis(['modulo' => 'interface', 'id' => 'form-button-value']));

		if ($this->removerBotaoEditar) {
			$pagina = modelo_tag_in($pagina, '<!-- botao-editar < -->', '<!-- botao-editar > -->', '');
		}

		if ($this->botoes) {
			$pagina = modelo_var_troca($pagina, '#botoes#', BotoesInterface::cabecalho($this->botoes));
		} else {
			$pagina = modelo_tag_in($pagina, '<!-- botoes < -->', '<!-- botoes > -->', '');
		}

		$pagina = modelo_var_troca($pagina, '#form-page#', $_GESTOR['pagina']);

		// Histórico
		$pagina = HistoricoInterface::renderizar(
			id: $_GESTOR['modulo-id'],
			modulo: $_GESTOR['modulo-id'],
			pagina: $pagina,
			semId: true,
		);

		$pagina = $this->substituirVariaveisExtras($pagina);

		$_GESTOR['pagina'] = $pagina;
		$this->processarFormulario();
		$this->incluirAssets();
	}

	/**
	 * Executa a operação de SIMPLES (sem registro).
	 */
	public function simples(): void {
		global $_GESTOR;

		ComponentesInterface::incluir('modal-carregamento', 'modal-alerta');

		$pagina = gestor_componente(['id' => 'interface-simples']);

		$pagina = modelo_var_troca($pagina, '#titulo#', $_GESTOR['pagina#titulo']);

		if ($this->botoes) {
			$pagina = modelo_var_troca($pagina, '#botoes#', BotoesInterface::cabecalho($this->botoes));
		} else {
			$pagina = modelo_tag_in($pagina, '<!-- botoes < -->', '<!-- botoes > -->', '');
		}

		$pagina = modelo_var_troca($pagina, '#form-page#', $_GESTOR['pagina']);

		// Histórico
		$pagina = HistoricoInterface::renderizar(
			id: $_GESTOR['modulo-id'],
			modulo: $_GESTOR['modulo-id'],
			pagina: $pagina,
			semId: true,
		);

		$pagina = $this->substituirVariaveisExtras($pagina);

		$_GESTOR['pagina'] = $pagina;
		$this->processarFormulario();
		$this->incluirAssets();
	}

	// ╔════════════════════════════════════════════════════════════════╗
	// ║              OPERAÇÕES: Iniciar/Finalizar (Conectores)        ║
	// ╚════════════════════════════════════════════════════════════════╝

	/**
	 * Conector _iniciar para operação de adicionar.
	 */
	public function adicionarIniciar(): void {
		global $_GESTOR;
		if (isset($_REQUEST['_gestor-adicionar'])) {
			$_GESTOR['adicionar-banco'] = true;
		}
	}

	/**
	 * Conector _iniciar para operação de editar.
	 */
	public function editarIniciar(): void {
		global $_GESTOR;

		if (isset($_REQUEST['_gestor-registro-id'])) {
			$_GESTOR['modulo-registro-id'] = banco_v2()->escape($_REQUEST['_gestor-registro-id']);
		}

		if (isset($_REQUEST['id']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
			$_GESTOR['modulo-registro-id'] = banco_v2()->escape($_REQUEST['id']);
		}

		if ($this->forcarId && $_SERVER['REQUEST_METHOD'] === 'GET') {
			$_GESTOR['modulo-registro-id'] = $this->forcarId;
		}

		if (!isset($_GESTOR['modulo-registro-id'])) {
			gestor_redirecionar_raiz();
		}

		if (isset($_REQUEST['_gestor-atualizar'])) {
			$_GESTOR['atualizar-banco'] = true;
		}
	}

	/**
	 * Conector _iniciar para operação de clonar.
	 */
	public function clonarIniciar(): void {
		global $_GESTOR;

		if (isset($_REQUEST['_gestor-adicionar'])) {
			$_GESTOR['adicionar-banco'] = true;
		} else {
			if (isset($_REQUEST['id']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
				$_GESTOR['modulo-registro-id'] = banco_v2()->escape($_REQUEST['id']);
			}

			if ($this->forcarId && $_SERVER['REQUEST_METHOD'] === 'GET') {
				$_GESTOR['modulo-registro-id'] = $this->forcarId;
			}

			if (!isset($_GESTOR['modulo-registro-id'])) {
				gestor_redirecionar_raiz();
			}
		}
	}

	/**
	 * Conector _iniciar para operação de excluir.
	 */
	public function excluirIniciar(): void {
		global $_GESTOR;

		if (isset($_REQUEST['id']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
			$_GESTOR['modulo-registro-id'] = banco_v2()->escape($_REQUEST['id']);
		}

		if (!isset($_GESTOR['modulo-registro-id'])) {
			gestor_redirecionar_raiz();
		}
	}

	/**
	 * Executa exclusão lógica (status = 'D').
	 *
	 * @param bool $historico Se true (padrão), inclui no histórico
	 */
	public function excluirFinalizar(bool $historico = true): void {
		global $_GESTOR;

		$id = $_GESTOR['modulo-registro-id'];
		$banco = $this->bancoConfig ?: ($_GESTOR['modulo#' . $_GESTOR['modulo-id']]['tabela'] ?? []);

		// Guardar ID numérico para callback
		if ($this->callbackExclusao) {
			$resultado = banco_v2()->selectName(
				BancoV2::camposVirgulas([$banco['id_numerico']]),
				$banco['nome'],
				"WHERE {$banco['id']}='{$id}' AND {$banco['status']}!='D'"
				. ($this->whereClause ? " AND {$this->whereClause}" : '')
			);
			if ($resultado) {
				$_GESTOR['modulo-registro-id-numerico'] = $resultado[0][$banco['id_numerico']];
			}
		}

		// Update status -> D
		$sets = [
			"{$banco['status']}='D'",
			"{$banco['versao']} = {$banco['versao']} + 1",
			"{$banco['data_modificacao']}=NOW()",
		];

		$whereExtra = "WHERE {$banco['id']}='{$id}'"
			. ($this->whereClause ? " AND {$this->whereClause}" : '')
			. " AND {$banco['status']}!='D'";

		// Histórico
		if ($historico) {
			HistoricoInterface::incluir(
				alteracoes: [['alteracao' => 'historic-delete']],
				deletar: true,
			);
		}

		banco_v2()->updateSQL(
			BancoV2::camposVirgulas($sets),
			$banco['nome'],
			$whereExtra
		);

		// Callback
		if ($this->callbackExclusao) {
			call_user_func($this->callbackExclusao);
		}

		// Redirect
		if (isset($_REQUEST['redirect'])) {
			gestor_redirecionar($_REQUEST['redirect']);
		} else {
			gestor_redirecionar_raiz();
		}
	}

	/**
	 * Conector _iniciar para operação de status.
	 */
	public function statusIniciar(): void {
		global $_GESTOR;

		if (isset($_REQUEST['id']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
			$_GESTOR['modulo-registro-id'] = banco_v2()->escape($_REQUEST['id']);
		}

		if (isset($_REQUEST['status']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
			$_GESTOR['modulo-registro-status'] = banco_v2()->escape($_REQUEST['status']);
		}

		if (!isset($_GESTOR['modulo-registro-id']) || !isset($_GESTOR['modulo-registro-status'])) {
			gestor_redirecionar_raiz();
		}
	}

	/**
	 * Executa mudança de status.
	 */
	public function statusFinalizar(bool $historico = true): void {
		global $_GESTOR;

		$id = $_GESTOR['modulo-registro-id'];
		$mudarStatus = $_GESTOR['modulo-registro-status'];
		$banco = $this->bancoConfig ?: ($_GESTOR['modulo#' . $_GESTOR['modulo-id']]['tabela'] ?? []);

		$sets = [
			"{$banco['status']}='{$mudarStatus}'",
			"{$banco['versao']} = {$banco['versao']} + 1",
			"{$banco['data_modificacao']}=NOW()",
		];

		$whereExtra = "WHERE {$banco['id']}='{$id}'"
			. ($this->whereClause ? " AND {$this->whereClause}" : '')
			. " AND {$banco['status']}!='D'";

		banco_v2()->updateSQL(BancoV2::camposVirgulas($sets), $banco['nome'], $whereExtra);

		// Histórico
		if ($historico) {
			$valorAntes = $mudarStatus === 'A' ? 'field-status-inactive' : 'field-status-active';
			$valorDepois = $mudarStatus === 'A' ? 'field-status-active' : 'field-status-inactive';

			HistoricoInterface::incluir(
				alteracoes: [[
					'campo' => 'field-status',
					'alteracao' => 'historic-change-status',
					'valor_antes' => $valorAntes,
					'valor_depois' => $valorDepois,
				]],
			);
		}

		if (isset($_REQUEST['redirect'])) {
			gestor_redirecionar($_REQUEST['redirect']);
		}
	}

	/**
	 * Conector _iniciar para operação de configuração.
	 */
	public function configIniciar(): void {
		global $_GESTOR;
		if (isset($_REQUEST['_gestor-atualizar'])) {
			$_GESTOR['atualizar-banco'] = true;
		}
	}

	/**
	 * Conector _iniciar para operação de visualizar.
	 */
	public function visualizarIniciar(): void {
		global $_GESTOR;

		if (isset($_REQUEST['_gestor-registro-id'])) {
			$_GESTOR['modulo-registro-id'] = banco_v2()->escape($_REQUEST['_gestor-registro-id']);
		}

		if (isset($_REQUEST['id']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
			$_GESTOR['modulo-registro-id'] = banco_v2()->escape($_REQUEST['id']);
		}

		if ($this->forcarId && $_SERVER['REQUEST_METHOD'] === 'GET') {
			$_GESTOR['modulo-registro-id'] = $this->forcarId;
		}

		if (!isset($_GESTOR['modulo-registro-id'])) {
			gestor_redirecionar_raiz();
		}
	}

	// ╔════════════════════════════════════════════════════════════════╗
	// ║                      AJAX Handlers                            ║
	// ╚════════════════════════════════════════════════════════════════╝

	/**
	 * Processa requisições AJAX da interface v2.
	 * Roteia para o handler correto baseado em ajax-opcao.
	 */
	public function processarAjax(): void {
		global $_GESTOR;

		if (!$_GESTOR['ajax']) return;

		match ($_GESTOR['ajax-opcao'] ?? '') {
			'backup-campos-mudou' => $this->ajaxBackupCampo(),
			'historico-mais-resultados' => $this->ajaxHistoricoMais(),
			'listar' => $this->ajaxListar(),
			'verificar-campo' => $this->ajaxVerificarCampo(),
			default => null,
		};

		// Incluir AJAX interface de bibliotecas
		$bibliotecas = array_merge(
			$_GESTOR['bibliotecas'] ?? [],
			$_GESTOR['modulo#' . $_GESTOR['modulo-id']]['bibliotecas'] ?? [],
		);

		foreach ($bibliotecas as $biblioteca) {
			if ($biblioteca === 'html-editor' && function_exists('html_editor_ajax_interface')) {
				html_editor_ajax_interface();
			}
		}
	}

	/**
	 * AJAX: Restaurar backup de campo.
	 */
	private function ajaxBackupCampo(): void {
		global $_GESTOR;

		$id = isset($_REQUEST['id']) ? banco_v2()->escape($_REQUEST['id']) : null;
		$idNumerico = isset($_REQUEST['id_numerico']) ? banco_v2()->escape($_REQUEST['id_numerico']) : null;
		$campo = isset($_REQUEST['campo']) ? banco_v2()->escape($_REQUEST['campo']) : null;

		if (!$campo || !$idNumerico) {
			$_GESTOR['ajax-json'] = ['status' => 'mandatoryFieldsNotSent'];
			return;
		}

		$modulo = $_GESTOR['modulo-id'];

		if (existe($id)) {
			$backup = banco_v2()->selectName(
				BancoV2::camposVirgulas(['valor']),
				'backup_campos',
				"WHERE id='{$idNumerico}' AND modulo='{$modulo}' AND campo='{$campo}' AND id_backup_campos='{$id}'"
			);
			$valor = $backup[0]['valor'] ?? '';
		} else {
			if (!isset($_GESTOR['modulo-registro-id'])) {
				$_GESTOR['ajax-json'] = ['status' => 'idRecordNotFound'];
				return;
			}
			$moduloConfig = $_GESTOR['modulo#' . $modulo];

			$resultado = banco_v2()->selectName(
				BancoV2::camposVirgulas([$campo]),
				$moduloConfig['tabela']['nome'],
				"WHERE {$moduloConfig['tabela']['id']}='{$_GESTOR['modulo-registro-id']}' AND {$moduloConfig['tabela']['status']}!='D'"
			);
			$valor = $resultado[0][$campo] ?? '';
		}

		// Alterar variáveis globais
		if ($valor) {
			$open = $_GESTOR['variavel-global']['open'];
			$close = $_GESTOR['variavel-global']['close'];
			$openText = $_GESTOR['variavel-global']['openText'];
			$closeText = $_GESTOR['variavel-global']['closeText'];
			$valor = preg_replace("/" . preg_quote($open) . "(.+?)" . preg_quote($close) . "/", strtolower($openText . "$1" . $closeText), $valor);
		}

		$_GESTOR['ajax-json'] = [
			'status' => 'Ok',
			'valor' => stripslashes($valor),
		];
	}

	/**
	 * AJAX: Carregar mais resultados do histórico.
	 */
	private function ajaxHistoricoMais(): void {
		global $_GESTOR;

		$_GESTOR['ajax-json'] = [
			'status' => 'Ok',
			'pagina' => HistoricoInterface::renderizar(
				id: $_REQUEST['id'] ?? '',
				modulo: $_GESTOR['modulo-id'],
				semId: isset($_REQUEST['sem_id']),
				paginaNum: (int) ($_REQUEST['pagina'] ?? 0),
			),
		];
	}

	/**
	 * AJAX: Listagem para DataTable.
	 */
	private function ajaxListar(): void {
		global $_GESTOR;

		$interface = gestor_sessao_variavel(
			$_GESTOR['modulo'] . '-' . $_GESTOR['opcao'] . '-interface-v2-' . $_GESTOR['usuario-id']
		);

		$banco = $interface['banco'];
		$tabela = $interface['tabela'] ?? null;
		$procurar = '';

		// Request variables
		$jsonObj = [];

		if (isset($_REQUEST['draw'])) {
			$draw = is_numeric($_REQUEST['draw']) ? $_REQUEST['draw'] : '1';
			$jsonObj['draw'] = $draw;
		}

		if (isset($_REQUEST['start'])) {
			$start = is_numeric($_REQUEST['start']) ? $_REQUEST['start'] : '0';
			$interface['registroInicial'] = $start !== '0' ? ltrim($start, '0') : $start;
		}

		if (isset($_REQUEST['length'])) {
			$length = is_numeric($_REQUEST['length']) ? $_REQUEST['length'] : '25';
			$interface['registrosPorPagina'] = $length;
		}

		$columns = $_REQUEST['columns'] ?? [];
		$columnsExtraSearch = $_REQUEST['columnsExtraSearch'] ?? [];

		// Order
		if (isset($_REQUEST['order'])) {
			$orderBanco = '';
			foreach ($_REQUEST['order'] as $o) {
				$col = is_numeric($o['column']) ? $o['column'] : '0';
				$dir = $o['dir'] === 'asc' ? 'asc' : 'desc';
				$orderBanco .= (strlen($orderBanco) > 0 ? ',' : '') . $columns[$col]['data'] . ' ' . $dir;
			}
			$banco['order'] = " ORDER BY {$orderBanco}";
		}

		// Search
		if (isset($_REQUEST['search']['value'])) {
			$search = $_REQUEST['search']['value'];
			if (strlen($search) > 0) {
				foreach ($columns as $col) {
					if ($col['searchable'] === 'true') {
						$procurar .= (strlen($procurar) > 0 ? ' OR ' : '') . "UCASE({$col['data']}) LIKE UCASE('%{$search}%')";
					}
				}
				foreach ($columnsExtraSearch as $col) {
					$procurar .= (strlen($procurar) > 0 ? ' OR ' : '') . "UCASE({$col}) LIKE UCASE('%{$search}%')";
				}
			}
		}

		// Query
		$campos = isset($banco['status'])
			? array_merge($banco['campos'], [$banco['id'], $banco['status']])
			: array_merge($banco['campos'], [$banco['id']]);

		$whereBase = "WHERE {$banco['status']}!='D'" . (isset($banco['where']) ? " AND {$banco['where']}" : '');

		if (strlen($procurar) > 0) {
			$preTabela = banco_v2()->selectName($banco['id'], $banco['nome'], "{$whereBase} AND ({$procurar})");

			$tabelaBd = banco_v2()->selectName(
				BancoV2::camposVirgulas($campos),
				$banco['nome'],
				"{$whereBase} AND ({$procurar}){$banco['order']} LIMIT {$interface['registroInicial']},{$interface['registrosPorPagina']}"
			);
		} else {
			$tabelaBd = banco_v2()->selectName(
				BancoV2::camposVirgulas($campos),
				$banco['nome'],
				"{$whereBase}{$banco['order']} LIMIT {$interface['registroInicial']},{$interface['registrosPorPagina']}"
			);
		}

		// Popular JSON
		if ($tabelaBd) {
			$jsonObj['recordsTotal'] = $interface['totalRegistros'];
			$jsonObj['recordsFiltered'] = strlen($procurar) > 0 ? count($preTabela) : $interface['totalRegistros'];

			$data = [];
			foreach ($tabelaBd as $dado) {
				$row = [];
				if ($tabela) {
					foreach ($tabela['colunas'] as $coluna) {
						$valor = $dado[$coluna['id']];
						if (isset($coluna['formatar'])) {
							$valor = FormatadorInterface::formatar(
								(string) $valor,
								FormatoTipo::from($coluna['formatar']),
								$coluna['formatoParams'] ?? [],
							);
						}
						$row[$coluna['id']] = $valor;
					}
				}

				if (isset($banco['status'])) {
					$row[$banco['status']] = $dado[$banco['status']];
				}
				$row[$banco['id']] = $dado[$banco['id']];
				$data[] = $row;
			}

			$jsonObj['data'] = $data;
		}

		// Salvar sessão
		gestor_sessao_variavel(
			$_GESTOR['modulo'] . '-' . $_GESTOR['opcao'] . '-interface-v2-' . $_GESTOR['usuario-id'],
			$interface
		);

		$_GESTOR['ajax-json'] = $jsonObj;
	}

	/**
	 * AJAX: Verificar se campo já existe (unicidade).
	 */
	private function ajaxVerificarCampo(): void {
		global $_GESTOR;

		if (!isset($_GESTOR['usuario-token-id'])) {
			gestor_roteador_erro([
				'codigo' => 401,
				'ajax' => $_GESTOR['ajax'],
			]);
		}

		$campoExiste = interface_verificar_campos([
			'campo' => banco_v2()->escape($_REQUEST['campo']),
			'valor' => banco_v2()->escape($_REQUEST['valor']),
			'language' => $_REQUEST['language'] === 'true' ? true : null,
		]);

		$_GESTOR['ajax-json'] = [
			'status' => 'Ok',
			'campoExiste' => $campoExiste,
		];
	}

	// ╔════════════════════════════════════════════════════════════════╗
	// ║              VALIDAÇÃO SERVER-SIDE                             ║
	// ╚════════════════════════════════════════════════════════════════╝

	/**
	 * Valida campos obrigatórios server-side.
	 * Se falhar, exibe alerta e redireciona.
	 *
	 * @param string $campo Nome do campo em $_REQUEST
	 * @param RegraValidacao $regra Regra de validação
	 * @param string $label Label do campo para mensagem de erro
	 * @param string|null $redirect URL de redirecionamento em caso de erro
	 * @param int $min Mínimo de caracteres (para texto)
	 * @param int $max Máximo de caracteres (para texto)
	 * @return self
	 */
	public function validarServidor(
		string $campo,
		RegraValidacao $regra,
		string $label,
		?string $redirect = null,
		int $min = 3,
		int $max = 100,
	): self {
		$valor = $_REQUEST[$campo] ?? '';

		$naoValidou = match ($regra) {
			RegraValidacao::TextoObrigatorio,
			RegraValidacao::TextoObrigatorioVerificar => strlen($valor) < $min || strlen($valor) > $max,
			RegraValidacao::SelecaoObrigatorio => !existe($valor),
			RegraValidacao::Email => !preg_match('/^[^0-9][_a-z0-9-]+(\.[_a-z0-9-]+)*@([a-z0-9-]{2,})+(\.[a-z0-9-]{2,})*$/', $valor),
			default => !existe($valor),
		};

		if ($naoValidou) {
			$msgId = match ($regra) {
				RegraValidacao::Email => 'validation-email',
				RegraValidacao::SelecaoObrigatorio => 'validation-select',
				default => 'validation-min-max-length',
			};

			$msg = gestor_variaveis(['modulo' => 'interface', 'id' => $msgId]);
			$msg = modelo_var_troca($msg, '#label#', $label);
			$msg = modelo_var_troca($msg, '#min#', (string) $min);
			$msg = modelo_var_troca($msg, '#max#', (string) $max);

			AlertaInterface::mostrar($msg);

			if ($redirect) {
				gestor_redirecionar($redirect);
			} else {
				gestor_reload_url();
			}
		}

		return $this;
	}

	// ╔════════════════════════════════════════════════════════════════╗
	// ║              Registrar no sistema (modo legado)                ║
	// ╚════════════════════════════════════════════════════════════════╝

	/**
	 * Registra a configuração como array em $_GESTOR['interface']
	 * para compatibilidade com o pipeline legado interface_iniciar/finalizar.
	 *
	 * Nota: Na v2, prefira usar os métodos diretos (listar(), editar(), etc.)
	 * em vez deste modo de compatibilidade.
	 */
	public function registrar(): void {
		global $_GESTOR;

		$banco = $this->construirBancoArray();
		$tabela = $this->construirTabelaArray();
		$opcoes = $this->construirOpcoesArray();

		$listarConfig = [
			'banco' => $banco,
			'tabela' => $tabela,
			'opcoes' => $opcoes,
		];

		if ($this->botoes) {
			$listarConfig['botoes'] = [];
			foreach ($this->botoes as $b) {
				$listarConfig['botoes'][$b->id] = [
					'url' => $b->url, 'rotulo' => $b->rotulo, 'tooltip' => $b->tooltip,
					'icon' => $b->icone, 'cor' => $b->cor,
				];
			}
		}

		if ($this->cabecalhoHtml && isset($tabela)) {
			$listarConfig['tabela']['cabecalho'] = $this->cabecalhoHtml;
		}

		$_GESTOR['interface'] = [
			'listar' => ['finalizar' => $listarConfig],
		];
	}

	// ╔════════════════════════════════════════════════════════════════╗
	// ║              Finalizar (Inclusão de assets e alerta)          ║
	// ╚════════════════════════════════════════════════════════════════╝

	/**
	 * Finaliza a interface — imprime alertas e componentes.
	 * Deve ser chamado como último passo no fluxo do módulo.
	 */
	public function finalizar(): void {
		AlertaInterface::imprimir();
		ComponentesInterface::renderizar();

		global $_GESTOR;
		if (!isset($_GESTOR['javascript-vars']['interface-v2'])) {
			$_GESTOR['javascript-vars']['interface-v2'] = [];
		}
	}

	// ╔════════════════════════════════════════════════════════════════╗
	// ║                   MÉTODOS INTERNOS                            ║
	// ╚════════════════════════════════════════════════════════════════╝

	/**
	 * Constroi array de banco no formato legado para listagem.
	 */
	private function construirBancoArray(): array {
		$campos = array_map(fn(ColunaConfig $c) => $c->id, $this->colunas);

		return array_filter([
			'nome' => $this->bancoConfig['nome'] ?? '',
			'campos' => $campos,
			'id' => $this->bancoConfig['id'] ?? 'id',
			'status' => $this->bancoConfig['status'] ?? 'status',
			'where' => $this->whereClause,
		], fn($v) => $v !== null);
	}

	/**
	 * Constroi array de tabela no formato legado para listagem.
	 */
	private function construirTabelaArray(): ?array {
		if (empty($this->colunas)) return null;

		$colunas = [];
		foreach ($this->colunas as $col) {
			$colArray = [
				'id' => $col->id,
				'nome' => $col->nome,
			];

			if ($col->formato) $colArray['formatar'] = $col->formato->value;
			if ($col->formatoParams) $colArray['formatoParams'] = $col->formatoParams;
			if (!$col->ordenavel) $colArray['nao_ordenar'] = true;
			if (!$col->procuravel) $colArray['nao_procurar'] = true;
			if (!$col->visivel) $colArray['nao_visivel'] = true;
			if ($col->ordem) $colArray['ordenar'] = $col->ordem;
			if ($col->className) $colArray['className'] = $col->className;

			$colunas[] = $colArray;
		}

		return [
			'colunas' => $colunas,
			'rodape' => $this->comRodape ?: null,
		];
	}

	/**
	 * Constroi array de opções de ações para listagem.
	 */
	private function construirOpcoesArray(): ?array {
		if (empty($this->acoes)) return null;

		$opcoes = [];
		foreach ($this->acoes as $acao) {
			$opcArray = [
				'tooltip' => $acao->tooltip,
				'icon' => $acao->icone,
				'cor' => $acao->cor,
			];

			if ($acao->url) {
				$opcArray['url'] = $acao->url;
			} else {
				$opcArray['opcao'] = $acao->operacao;
			}

			if ($acao->statusAtual !== null) {
				$opcArray['status_atual'] = $acao->statusAtual;
				$opcArray['status_mudar'] = $acao->statusMudar;
			}

			$opcoes[$acao->id] = $opcArray;
		}

		return $opcoes;
	}

	/**
	 * Renderiza tabela HTML para listagem inicial.
	 */
	private function renderizarTabela(array $banco, ?array $tabela, ?array $opcoes): string {
		global $_GESTOR;

		// Sessão da interface
		$chaveSession = $_GESTOR['modulo'] . '-' . $_GESTOR['opcao'] . '-interface-v2-' . $_GESTOR['usuario-id'];

		if (!existe(gestor_sessao_variavel($chaveSession))) {
			gestor_sessao_variavel($chaveSession, [
				'totalRegistros' => 0,
				'registrosPorPagina' => 25,
				'registroInicial' => 0,
			]);
		}

		$interface = gestor_sessao_variavel($chaveSession);

		// Total registros
		$preTabela = banco_v2()->selectLegado([
			'tabela' => $banco['nome'],
			'campos' => [$banco['id']],
			'extra' => "WHERE {$banco['status']}!='D'" . (isset($banco['where']) ? " AND {$banco['where']}" : ''),
		]);

		if (!$preTabela) {
			return gestor_componente(['id' => 'interface-listar-sem-registros']);
		}

		if ($interface['totalRegistros'] !== count($preTabela)) {
			$interface['totalRegistros'] = count($preTabela);
			$interface['registroInicial'] = 0;
		}

		// Layout tabela
		$listaTabelaHtml = '<table id="_gestor-interface-lista-tabela" class="ui celled table responsive nowrap unstackable">#rows#</table>';
		$cabecalho = "<thead><tr>#rows#</tr></thead>";
		$rodape = "<tfoot><tr>#rows#</tr></tfoot>";

		$interfaceColumns = [];
		$order = false;
		$orderBanco = '';
		$orderDefault = false;
		$count = 0;

		if ($tabela) {
			foreach ($tabela['colunas'] as $coluna) {
				$row = "<th>{$coluna['nome']}</th>";
				$cabecalho = modelo_var_in($cabecalho, '#rows#', $row);
				if ($this->comRodape) $rodape = modelo_var_in($rodape, '#rows#', $row);

				$columns = [
					'data' => $coluna['id'],
					'name' => $coluna['nome'],
				];

				if (isset($coluna['nao_ordenar'])) $columns['orderable'] = false;
				if (isset($coluna['nao_procurar'])) $columns['searchable'] = false;
				if (isset($coluna['nao_visivel'])) $columns['visible'] = false;
				if (isset($coluna['className'])) $columns['className'] = $coluna['className'];
				if (isset($coluna['ordenar'])) {
					$ordem = $coluna['ordenar'] === 'asc' ? 'asc' : 'desc';
					$order[] = [$count, $ordem];
					$orderBanco .= (strlen($orderBanco) > 0 ? ',' : '') . $coluna['id'] . ' ' . $ordem;
				}

				if (!$orderDefault) $orderDefault = $coluna['id'];

				$interfaceColumns[] = $columns;
				$count++;
			}

			// Colunas extras para busca
			$interface['columnsExtraSearch'] = $this->colunasExtraBusca ?? ['id'];

			// Coluna status
			if (isset($banco['status'])) {
				$row = '<th>Status</th>';
				$cabecalho = modelo_var_in($cabecalho, '#rows#', $row);
				if ($this->comRodape) $rodape = modelo_var_in($rodape, '#rows#', $row);

				$interfaceColumns[] = [
					'data' => $banco['status'], 'name' => 'status',
					'orderable' => false, 'searchable' => false, 'visible' => false,
				];
			}

			// Coluna opções
			$opcoesLabel = gestor_variaveis(['modulo' => 'interface', 'id' => 'list-column-options']);
			$row = "<th>{$opcoesLabel}</th>";
			$cabecalho = modelo_var_in($cabecalho, '#rows#', $row);
			if ($this->comRodape) $rodape = modelo_var_in($rodape, '#rows#', $row);

			$interfaceColumns[] = [
				'data' => $banco['id'], 'name' => $opcoesLabel,
				'orderable' => false, 'searchable' => false,
			];

			// Ordenação
			if ($order) {
				$interface['order'] = $order;
				$banco['order'] = " ORDER BY {$orderBanco}";
			} else {
				$interface['order'] = [[0, 'asc']];
				$banco['order'] = " ORDER BY {$orderDefault} asc";
			}

			$cabecalho = modelo_var_troca($cabecalho, '#rows#', '');
			if ($this->comRodape) $rodape = modelo_var_troca($rodape, '#rows#', '');
		}

		$interface['columns'] = $interfaceColumns;

		// Query dados
		$campos = isset($banco['status'])
			? array_merge($banco['campos'], [$banco['id'], $banco['status']])
			: array_merge($banco['campos'], [$banco['id']]);

		$tabelaBd = banco_v2()->selectName(
			BancoV2::camposVirgulas($campos),
			$banco['nome'],
			"WHERE {$banco['status']}!='D'" . (isset($banco['where']) ? " AND {$banco['where']}" : '')
			. $banco['order'] . " LIMIT {$interface['registroInicial']},{$interface['registrosPorPagina']}"
		);

		// Montar corpo
		$tabelaDados = '';
		if ($tabelaBd) {
			$tabelaDados = '<tbody>#cols#</tbody>';
			$countRow = 0;

			foreach ($tabelaBd as $dado) {
				$col = '<tr class="' . ($countRow % 2 === 0 ? 'odd' : 'even') . '">#rows#</tr>';

				if ($tabela) {
					foreach ($tabela['colunas'] as $coluna) {
						$valor = $dado[$coluna['id']];
						if (isset($coluna['formatar'])) {
							$valor = FormatadorInterface::formatar(
								(string) $valor,
								FormatoTipo::from($coluna['formatar']),
								$coluna['formatoParams'] ?? [],
							);
						}
						$col = modelo_var_in($col, '#rows#', "<td>{$valor}</td>");
					}

					if (isset($banco['status'])) {
						$col = modelo_var_in($col, '#rows#', "<td>{$dado[$banco['status']]}</td>");
					}

					$col = modelo_var_in($col, '#rows#', "<td>{$dado[$banco['id']]}</td>");
				}

				$col = modelo_var_troca($col, '#rows#', '');
				$tabelaDados = modelo_var_in($tabelaDados, '#cols#', $col);
				$countRow++;
			}

			$tabelaDados = modelo_var_troca($tabelaDados, '#cols#', '');
		}

		// Montar tabela final
		$listaTabelaHtml = modelo_var_troca(
			$listaTabelaHtml,
			'#rows#',
			$cabecalho . $tabelaDados . ($this->comRodape ? $rodape : '')
		);

		// JS vars
		$caminho = rtrim($_GESTOR['caminho-total'] ?? '', '/') . '/';

		$_GESTOR['javascript-vars']['interface-v2']['lista'] = [
			'url' => $caminho,
			'id' => $banco['id'],
			'status' => $banco['status'] ?? false,
			'deferLoading' => $interface['totalRegistros'],
			'pageLength' => $interface['registrosPorPagina'],
			'displayStart' => (int) $interface['registroInicial'],
			'columns' => $interface['columns'],
			'columnsExtraSearch' => $interface['columnsExtraSearch'] ?? ['id'],
			'order' => $interface['order'],
			'opcoes' => $opcoes,
		];

		// Guardar sessão
		$interface['banco'] = $banco;
		$interface['tabela'] = $tabela;

		gestor_sessao_variavel($chaveSession, $interface);

		return $listaTabelaHtml;
	}

	/**
	 * Processa campos e validações do formulário, injetando na página global.
	 */
	private function processarFormulario(): void {
		global $_GESTOR;

		// Validações client-side
		if ($this->validacoes) {
			$this->processarValidacoes();
		}

		// Campos especiais (select, imagepick, etc.)
		if ($this->campos) {
			$this->processarCampos();
		}
	}

	/**
	 * Processa validações e injeta nas variáveis JS.
	 */
	private function processarValidacoes(): void {
		global $_GESTOR;

		$regrasValidacao = [];
		$validarCampos = [];

		foreach ($this->validacoes as $v) {
			$regra = $v->regra;
			$campo = $v->campo;
			$label = $v->label;

			$rules = match ($regra) {
				RegraValidacao::NaoVazio,
				RegraValidacao::MaiorOuIgualZero => [
					['type' => 'notEmpty', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-empty']), '#label#', $label)],
				],
				RegraValidacao::TextoObrigatorio,
				RegraValidacao::TextoObrigatorioVerificar => [
					['type' => 'notEmpty', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-empty']), '#label#', $label)],
					['type' => 'minLength[3]', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-min-length']), '#label#', $label)],
					['type' => 'maxLength[100]', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-max-length']), '#label#', $label)],
				],
				RegraValidacao::SelecaoObrigatorio => [
					['type' => 'notEmpty', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-select']), '#label#', $label)],
				],
				RegraValidacao::Email => [
					['type' => 'notEmpty', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-empty']), '#label#', $label)],
					['type' => 'email', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-email']), '#label#', $label)],
				],
				RegraValidacao::Senha => [
					['type' => 'notEmpty', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-empty']), '#label#', $label)],
					['type' => 'minLength[12]', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-min-length-password']), '#label#', $label)],
					['type' => 'maxLength[100]', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-max-length']), '#label#', $label)],
					['type' => 'regExp[/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\\$%\\^&\\*])/]', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-password-chars']), '#label#', $label)],
				],
				RegraValidacao::Dominio => [
					['type' => 'notEmpty', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-empty']), '#label#', $label)],
					['type' => 'minLength[3]', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-min-length']), '#label#', $label)],
					['type' => 'maxLength[255]', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-max-length']), '#label#', $label)],
					['type' => 'regExp[/^((?:(?:(?:\\w[\\.\\-\\+]?)*)\\w)+)((?:(?:(?:\\w[\\.\\-\\+]?){0,62})\\w)+)\\.(\\w{2,6})$/]', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-domain']), '#label#', $label)],
				],
				default => [
					['type' => 'notEmpty', 'prompt' => modelo_var_troca(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-empty']), '#label#', $label)],
				],
			};

			// Regras extras
			if ($v->regrasExtra) {
				foreach ($v->regrasExtra as $extra) {
					$rules[] = match ($extra['regra'] ?? '') {
						'regexPermited' => [
							'type' => "regExp[{$extra['regex']}]",
							'prompt' => modelo_var_troca_tudo(
								modelo_var_troca_tudo(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-regex-permited-chars']), '#label#', $label),
								'#permited-chars#', $extra['regexPermitedChars']
							),
						],
						'regexNecessary' => [
							'type' => "regExp[{$extra['regex']}]",
							'prompt' => modelo_var_troca_tudo(
								modelo_var_troca_tudo(gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-regex-necessary-chars']), '#label#', $label),
								'#necessary-chars#', $extra['regexNecessaryChars']
							),
						],
						default => [],
					};
				}
			}

			// Remover regras
			if ($v->removerRegra) {
				$rules = array_filter($rules, fn($rule) => !in_array($rule['type'], $v->removerRegra));
				$rules = array_values($rules);
			}

			$entry = ['rules' => $rules];
			if ($v->identificador) $entry['identifier'] = $v->identificador;

			$regrasValidacao[$campo] = $entry;

			// Verificar campos (validação server-side via AJAX)
			if (in_array($regra, [RegraValidacao::TextoObrigatorioVerificar, RegraValidacao::EmailComparacaoVerificar], true)) {
				$promptVerify = gestor_variaveis(['modulo' => 'interface', 'id' => 'validation-verify-field']);
				$promptVerify = modelo_var_troca_tudo($promptVerify, '#label#', $label);

				$key = $v->identificador ?? $campo;
				$vcEntry = ['prompt' => $promptVerify];

				if ($v->identificador) $vcEntry['campo'] = $campo;
				if ($v->language) $vcEntry['language'] = true;

				$validarCampos[$key] = $vcEntry;
			}
		}

		if ($regrasValidacao) {
			$_GESTOR['javascript-vars']['interface-v2']['regrasValidacao'] = $regrasValidacao;
		}

		if ($validarCampos) {
			$_GESTOR['javascript-vars']['interface-v2']['validarCampos'] = $validarCampos;
		}
	}

	/**
	 * Processa campos especiais (select, imagepick, etc.).
	 */
	private function processarCampos(): void {
		global $_GESTOR;

		foreach ($this->campos as $campo) {
			match ($campo->tipo) {
				TipoCampo::Select => $this->processarCampoSelect($campo),
				TipoCampo::ImagePick => $this->processarCampoImagePick($campo),
				TipoCampo::ImagePickHosts => $this->processarCampoImagePickHosts($campo),
				TipoCampo::TemplatesHosts => $this->processarCampoTemplates($campo),
			};
		}
	}

	/**
	 * Processa campo select — gera dropdown Semantic UI.
	 */
	private function processarCampoSelect(CampoConfig $campo): void {
		global $_GESTOR;

		// Obter opções
		$opcoes = $campo->opcoes;

		if ($campo->tabelaBanco) {
			$resultado = banco_v2()->selectName(
				BancoV2::camposVirgulas([$campo->campoBanco, $campo->campoTexto]),
				$campo->tabelaBanco,
				($campo->where ? "WHERE {$campo->where}" : '')
				. ($campo->ordemBanco ? " ORDER BY {$campo->ordemBanco}" : '')
			);

			if ($resultado) {
				foreach ($resultado as $r) {
					$opcoes[] = ['valor' => $r[$campo->campoBanco], 'texto' => $r[$campo->campoTexto]];
				}
			}
		}

		// Gerar HTML do dropdown
		$classExtra = '';
		if ($campo->procuravel) $classExtra .= ' search';
		if ($campo->limpavel) $classExtra .= ' clearable';
		if ($campo->multiplo) $classExtra .= ' multiple';

		$html = '<div class="ui fluid selection dropdown' . $classExtra . '">';
		$html .= '<input type="hidden" name="' . $campo->nome . '"'
			. ($campo->valor !== null ? ' value="' . htmlspecialchars($campo->valor) . '"' : '') . '>';
		$html .= '<i class="dropdown icon"></i>';
		$html .= '<div class="default text">' . ($campo->placeholder ?? gestor_variaveis(['modulo' => 'interface', 'id' => 'select-default-text'])) . '</div>';
		$html .= '<div class="menu">';

		foreach ($opcoes as $opcao) {
			$valor = $opcao['valor'] ?? '';
			$texto = $opcao['texto'] ?? '';
			$selecionado = ($campo->valor !== null && $campo->valor === $valor) ? ' active selected' : '';
			$html .= '<div class="item' . $selecionado . '" data-value="' . htmlspecialchars($valor) . '">' . htmlspecialchars($texto) . '</div>';
		}

		$html .= '</div></div>';

		// Injetar na página
		$_GESTOR['pagina'] = modelo_var_troca(
			$_GESTOR['pagina'],
			'<span>#select-' . $campo->id . '#</span>',
			$html,
		);
	}

	/**
	 * Processa campo ImagePick (simplificado — delega ao v1 quando complexo).
	 */
	private function processarCampoImagePick(CampoConfig $campo): void {
		// Delega para a função v1 se disponível para manter compatibilidade com templates
		if (function_exists('interface_formulario_campos')) {
			interface_formulario_campos([
				'campos' => [
					[
						'tipo' => 'imagepick',
						'id' => $campo->id,
						'nome' => $campo->nome,
						'id_arquivos' => $campo->imagemId,
						'caminho' => $campo->caminho,
					],
				],
			]);
		}
	}

	/**
	 * Processa campo ImagePickHosts (delega ao v1).
	 */
	private function processarCampoImagePickHosts(CampoConfig $campo): void {
		if (function_exists('interface_formulario_campos')) {
			interface_formulario_campos([
				'campos' => [
					[
						'tipo' => 'imagepick-hosts',
						'id' => $campo->id,
						'nome' => $campo->nome,
						'id_hosts_arquivos' => $campo->imagemId,
					],
				],
			]);
		}
	}

	/**
	 * Processa campo Template (delega ao v1).
	 */
	private function processarCampoTemplates(CampoConfig $campo): void {
		if (function_exists('interface_formulario_campos')) {
			interface_formulario_campos([
				'campos' => [
					[
						'tipo' => 'templates-hosts',
						'id' => $campo->id,
						'nome' => $campo->nome,
						'categoria_id' => $campo->categoriaId,
						'template_id' => $campo->valor,
					],
				],
			]);
		}
	}

	/**
	 * Substitui variáveis de formulário do módulo na página.
	 */
	private function substituirVariaveisFormulario(string $pagina): string {
		global $_GESTOR;

		$formVariaveis = gestor_variaveis([
			'modulo' => $_GESTOR['modulo-id'],
			'conjunto' => true,
			'padrao' => 'form',
		]);

		if ($formVariaveis) {
			foreach ($formVariaveis as $id => $val) {
				$pagina = modelo_var_troca($pagina, "#{$id}#", $val);
			}
		}

		return $pagina;
	}

	/**
	 * Substitui variáveis extras configuradas pelo módulo.
	 */
	private function substituirVariaveisExtras(string $pagina): string {
		foreach ($this->variaveisTrocar as $chave => $valor) {
			$pagina = modelo_var_troca($pagina, "#{$chave}#", $valor);
		}
		return $pagina;
	}

	/**
	 * Renderiza meta-dados na página.
	 */
	private function renderizarMetaDados(string $pagina): string {
		if ($this->metaDados) {
			$celTh = modelo_tag_val($pagina, '<!-- cel-th < -->', '<!-- cel-th > -->');
			$celTd = modelo_tag_val($pagina, '<!-- cel-td < -->', '<!-- cel-td > -->');
			$pagina = modelo_tag_in($pagina, '<!-- cel-th < -->', '<!-- cel-th > -->', '<!-- cel-th -->');
			$pagina = modelo_tag_in($pagina, '<!-- cel-td < -->', '<!-- cel-td > -->', '<!-- cel-td -->');

			foreach ($this->metaDados as $meta) {
				$auxTh = modelo_var_troca($celTh, '#meta-titulo#', $meta['titulo']);
				$pagina = modelo_var_in($pagina, '<!-- cel-th -->', $auxTh);

				$auxTd = modelo_var_troca($celTd, '#meta-dado#', $meta['dado']);
				$pagina = modelo_var_in($pagina, '<!-- cel-td -->', $auxTd);
			}

			$pagina = modelo_var_troca($pagina, '<!-- cel-th -->', '');
			$pagina = modelo_var_troca($pagina, '<!-- cel-td -->', '');
		} else {
			$pagina = modelo_tag_in($pagina, '<!-- meta-dados < -->', '<!-- meta-dados > -->', '');
		}

		return $pagina;
	}

	/**
	 * Inclui CSS e JS da interface v2 na página.
	 */
	private function incluirAssets(bool $datatable = false): void {
		global $_GESTOR;

		$versao = $_GESTOR['biblioteca-interface-v2']['versao'];

		if ($datatable) {
			$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="' . $_GESTOR['url-raiz'] . 'datatables/datatables.min.css" />';
			$_GESTOR['javascript'][] = '<script src="' . $_GESTOR['url-raiz'] . 'datatables/datatables.min.js"></script>';
		}

		$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="' . $_GESTOR['url-raiz'] . 'interface-v2/interface-v2.css?v=' . $versao . '" />';
		$_GESTOR['javascript'][] = '<script src="' . $_GESTOR['url-raiz'] . 'interface-v2/interface-v2.js?v=' . $versao . '"></script>';

		if (!isset($_GESTOR['javascript-vars']['interface-v2'])) {
			$_GESTOR['javascript-vars']['interface-v2'] = [];
		}
	}
}

// ╔══════════════════════════════════════════════════════════════════════════════╗
// ║              FUNÇÕES CONECTORAS GLOBAIS (para pipeline gestor.php)           ║
// ╚══════════════════════════════════════════════════════════════════════════════╝

/**
 * Obtém ou cria a instância singleton de InterfaceV2 no módulo atual.
 * 
 * @return InterfaceV2
 */
function interface_v2(): InterfaceV2 {
	global $_GESTOR;

	if (!isset($_GESTOR['interface-v2-instancia'])) {
		$_GESTOR['interface-v2-instancia'] = InterfaceV2::criar();
	}

	return $_GESTOR['interface-v2-instancia'];
}

?>
