<?php
/**
 * Biblioteca OOP de operações com banco de dados via PDO.
 *
 * Reescrita completa da banco.php usando PDO para suporte a MySQL e PostgreSQL,
 * com API fluente (method chaining), prepared statements, e recursos modernos do PHP 8.5.
 *
 * Uso principal (API fluente):
 *   $banco = banco_v2();
 *   $users = $banco->tabela('users')->campos(['nome','email'])->where("status = ?", ['A'])->select();
 *
 * Uso alternativo (métodos diretos/legados):
 *   $banco->sql("SELECT * FROM users WHERE id = ?", [1]);
 *   $banco->selectName('nome,email', 'users', "WHERE status='A'");
 *
 * PHP 8.5 features: pipe operator |>, clone with, #[NoDiscard], enums,
 *   readonly classes, match expressions, constructor promotion, array_first/array_last.
 *
 * @package    Conn2Flow
 * @subpackage Bibliotecas
 * @version    2.0.0
 * @requires   PHP 8.5+
 * @requires   ext-pdo
 */

// ===== Registro da versão da biblioteca no sistema global =====
$_GESTOR['biblioteca-banco-v2'] = [
	'versao' => '2.0.0',
];

// =====================================================================
//  ENUM — Driver do banco de dados
// =====================================================================

/**
 * Enum que define os drivers de banco de dados suportados.
 *
 * Cada caso mapeia para o prefixo DSN do PDO correspondente e fornece
 * informações padrão de porta e charset para cada driver.
 */
enum DriverBanco: string
{
	case MySQL      = 'mysql';
	case PostgreSQL = 'pgsql';

	/** Prefixo DSN utilizado pelo PDO para este driver. */
	#[NoDiscard]
	public function dsnPrefixo(): string
	{
		return match ($this) {
			self::MySQL      => 'mysql',
			self::PostgreSQL => 'pgsql',
		};
	}

	/** Porta padrão do servidor para este driver. */
	#[NoDiscard]
	public function portaPadrao(): int
	{
		return match ($this) {
			self::MySQL      => 3306,
			self::PostgreSQL => 5432,
		};
	}

	/** Charset padrão recomendado para este driver. */
	#[NoDiscard]
	public function charsetPadrao(): string
	{
		return match ($this) {
			self::MySQL      => 'utf8mb4',
			self::PostgreSQL => 'UTF8',
		};
	}

	/**
	 * Cria instância do enum a partir de string de configuração legada.
	 *
	 * Aceita valores como 'mysqli', 'mysql', 'pdo_mysql', 'pgsql', 'pdo_pgsql', 'postgresql'.
	 */
	#[NoDiscard]
	public static function fromLegado(string $tipo): self
	{
		return match (strtolower(trim($tipo))) {
			'mysqli', 'mysql', 'pdo_mysql' => self::MySQL,
			'pgsql', 'pdo_pgsql', 'postgresql', 'postgres' => self::PostgreSQL,
			default => self::MySQL,
		};
	}
}

// =====================================================================
//  READONLY CLASS — Expressão SQL crua (raw)
// =====================================================================

/**
 * Representa uma expressão SQL crua que NÃO deve ser escapada/parametrizada.
 *
 * Usada para valores como NOW(), NULL, expressões numéricas, etc.
 * Em prepared statements, o valor é inserido diretamente no SQL.
 *
 * @example $banco->tabela('logs')->insert(['data' => BancoV2::raw('NOW()')]);
 */
readonly class ExpressaoSQL implements \Stringable
{
	public function __construct(
		public string $expressao,
	) {}

	#[NoDiscard]
	public function __toString(): string
	{
		return $this->expressao;
	}
}

// =====================================================================
//  READONLY CLASS — Configuração de conexão
// =====================================================================

/**
 * Objeto de valor imutável com as configurações de conexão do banco.
 *
 * Suporta criação a partir do array global $_BANCO (legado) e
 * geração de variantes para outro driver via `clone with`.
 */
readonly class ConfigBanco
{
	/** @var array<int,mixed> Opções extras do PDO. */
	public array $opcoes;

	public function __construct(
		public DriverBanco $driver  = DriverBanco::MySQL,
		public string      $host    = 'localhost',
		public int         $porta   = 3306,
		public string      $nome    = '',
		public string      $usuario = '',
		public string      $senha   = '',
		public string      $charset = 'utf8mb4',
		array              $opcoes  = [],
	) {
		// Opções PDO padrão para robustez e segurança
		$this->opcoes = $opcoes + [
			\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			\PDO::ATTR_EMULATE_PREPARES   => false,
			\PDO::ATTR_STRINGIFY_FETCHES  => false,
		];
	}

	/**
	 * Monta a string DSN para o PDO.
	 *
	 * @example "mysql:host=localhost;port=3306;dbname=conn2flow;charset=utf8mb4"
	 */
	#[NoDiscard]
	public function dsn(): string
	{
		$prefixo = $this->driver->dsnPrefixo();

		return match ($this->driver) {
			DriverBanco::MySQL => "{$prefixo}:host={$this->host};port={$this->porta};dbname={$this->nome};charset={$this->charset}",
			DriverBanco::PostgreSQL => "{$prefixo}:host={$this->host};port={$this->porta};dbname={$this->nome}",
		};
	}

	/**
	 * Cria variante MySQL desta configuração (via clone with).
	 */
	#[NoDiscard]
	public function paraMySQL(): static
	{
		return clone $this with {
			driver:  DriverBanco::MySQL,
			porta:   DriverBanco::MySQL->portaPadrao(),
			charset: DriverBanco::MySQL->charsetPadrao(),
		};
	}

	/**
	 * Cria variante PostgreSQL desta configuração (via clone with).
	 */
	#[NoDiscard]
	public function paraPostgreSQL(): static
	{
		return clone $this with {
			driver:  DriverBanco::PostgreSQL,
			porta:   DriverBanco::PostgreSQL->portaPadrao(),
			charset: DriverBanco::PostgreSQL->charsetPadrao(),
		};
	}

	/**
	 * Cria ConfigBanco a partir do array global $_BANCO (compatibilidade legada).
	 *
	 * Lê $_BANCO['tipo'], $_BANCO['host'], $_BANCO['nome'], etc.
	 */
	#[NoDiscard]
	public static function fromGlobal(): static
	{
		global $_BANCO;

		$driver = DriverBanco::fromLegado($_BANCO['tipo'] ?? 'mysqli');

		return new static(
			driver:  $driver,
			host:    $_BANCO['host']    ?? 'localhost',
			porta:   (int) ($_BANCO['porta'] ?? $driver->portaPadrao()),
			nome:    $_BANCO['nome']    ?? '',
			usuario: $_BANCO['usuario'] ?? '',
			senha:   $_BANCO['senha']   ?? '',
			charset: $_BANCO['charset'] ?? $driver->charsetPadrao(),
		);
	}
}

// =====================================================================
//  CLASS — Resultado de query (wrapper PDOStatement)
// =====================================================================

/**
 * Encapsula o resultado de uma query PDO.
 *
 * Fornece métodos fluentes para extrair dados do PDOStatement com
 * nomes intuitivos em português, mantendo consistência com a v1.
 */
class ResultadoBanco
{
	private int $colunaCount = -1;
	private ?array $nomesColunas = null;

	public function __construct(
		private readonly \PDOStatement $stmt,
	) {}

	/** Acesso direto ao PDOStatement subjacente. */
	#[NoDiscard]
	public function statement(): \PDOStatement
	{
		return $this->stmt;
	}

	/** Número de linhas afetadas/retornadas. */
	#[NoDiscard]
	public function contagem(): int
	{
		return $this->stmt->rowCount();
	}

	/** Número total de colunas no resultado. */
	#[NoDiscard]
	public function totalCampos(): int
	{
		if ($this->colunaCount < 0) {
			$this->colunaCount = $this->stmt->columnCount();
		}
		return $this->colunaCount;
	}

	/** Nome de uma coluna específica pelo índice (0-based). */
	#[NoDiscard]
	public function nomeCampo(int $indice): string
	{
		$meta = $this->stmt->getColumnMeta($indice);
		return $meta['name'] ?? '';
	}

	/** Array com os nomes de todas as colunas. */
	#[NoDiscard]
	public function nomesCampos(): array
	{
		if ($this->nomesColunas === null) {
			$this->nomesColunas = [];
			$total = $this->totalCampos();
			for ($i = 0; $i < $total; $i++) {
				$this->nomesColunas[] = $this->nomeCampo($i);
			}
		}
		return $this->nomesColunas;
	}

	/** Próxima linha como array indexado (fetch_row). */
	#[NoDiscard]
	public function linha(): ?array
	{
		$row = $this->stmt->fetch(\PDO::FETCH_NUM);
		return $row !== false ? $row : null;
	}

	/** Próxima linha como array associativo (fetch_assoc). */
	#[NoDiscard]
	public function linhaAssoc(): ?array
	{
		$row = $this->stmt->fetch(\PDO::FETCH_ASSOC);
		return $row !== false ? $row : null;
	}

	/** Próxima linha como array misto — associativo + indexado (fetch_array). */
	#[NoDiscard]
	public function linhaMista(): ?array
	{
		$row = $this->stmt->fetch(\PDO::FETCH_BOTH);
		return $row !== false ? $row : null;
	}

	/** Todas as linhas como arrays associativos. */
	#[NoDiscard]
	public function todos(): ?array
	{
		$rows = $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
		return !empty($rows) ? $rows : null;
	}

	/** Todas as linhas como arrays indexados. */
	#[NoDiscard]
	public function todosIndexados(): ?array
	{
		$rows = $this->stmt->fetchAll(\PDO::FETCH_NUM);
		return !empty($rows) ? $rows : null;
	}

	/** Todas as linhas como arrays mistos — associativo + indexado. */
	#[NoDiscard]
	public function todosMistos(): ?array
	{
		$rows = $this->stmt->fetchAll(\PDO::FETCH_BOTH);
		return !empty($rows) ? $rows : null;
	}

	/** Valor da primeira coluna da próxima linha. */
	#[NoDiscard]
	public function primeiroValor(): mixed
	{
		return $this->stmt->fetchColumn(0);
	}

	/** Todos os valores de uma coluna específica. */
	#[NoDiscard]
	public function coluna(int $indice = 0): array
	{
		return $this->stmt->fetchAll(\PDO::FETCH_COLUMN, $indice);
	}
}

// =====================================================================
//  CLASS — Query Builder fluente (ConsultaBanco)
// =====================================================================

/**
 * Construtor de queries com API fluente e method chaining.
 *
 * Criado via BancoV2::tabela(). Cada chamada de método setter retorna $this
 * para encadeamento. Métodos de execução rodam a query e retornam dados.
 *
 * @example
 *   $banco->tabela('users')
 *         ->campos(['nome', 'email', 'status'])
 *         ->where("status = ? AND criado > ?", ['A', '2024-01-01'])
 *         ->orderBy('nome ASC')
 *         ->limit(10, 0)
 *         ->select();
 */
class ConsultaBanco
{
	private array|string $campos    = '*';
	private ?string $where          = null;
	private array   $whereParams    = [];
	private ?string $orderBy        = null;
	private ?int    $limit          = null;
	private ?int    $offset         = null;
	private ?string $extra          = null;
	private array   $updateCampos   = [];
	private array   $insertCampos   = [];

	public function __construct(
		private readonly BancoV2 $banco,
		private readonly string  $tabela,
	) {}

	// -----------------------------------------------------------------
	//  Métodos de encadeamento (setters fluentes)
	// -----------------------------------------------------------------

	/**
	 * Define os campos/colunas para SELECT.
	 *
	 * @param array|string $campos Array de nomes ou string com colunas separadas por vírgula.
	 */
	public function campos(array|string $campos): static
	{
		$this->campos = $campos;
		return $this;
	}

	/**
	 * Define a cláusula WHERE com parâmetros para prepared statement.
	 *
	 * @param string $condicao Condição SQL com placeholders ? para binding.
	 * @param array  $params   Valores para os placeholders.
	 *
	 * @example ->where("id = ? AND status = ?", [1, 'A'])
	 */
	public function where(string $condicao, array $params = []): static
	{
		$this->where = $condicao;
		$this->whereParams = $params;
		return $this;
	}

	/**
	 * Adiciona condição AND ao WHERE existente.
	 *
	 * @param string $condicao Condição SQL com placeholders.
	 * @param array  $params   Valores para os placeholders.
	 */
	public function eWhere(string $condicao, array $params = []): static
	{
		if ($this->where !== null) {
			$this->where .= " AND ({$condicao})";
		} else {
			$this->where = $condicao;
		}
		$this->whereParams = [...$this->whereParams, ...$params];
		return $this;
	}

	/**
	 * Adiciona condição OR ao WHERE existente.
	 *
	 * @param string $condicao Condição SQL com placeholders.
	 * @param array  $params   Valores para os placeholders.
	 */
	public function ouWhere(string $condicao, array $params = []): static
	{
		if ($this->where !== null) {
			$this->where .= " OR ({$condicao})";
		} else {
			$this->where = $condicao;
		}
		$this->whereParams = [...$this->whereParams, ...$params];
		return $this;
	}

	/** Define cláusula ORDER BY. */
	public function orderBy(string $ordem): static
	{
		$this->orderBy = $ordem;
		return $this;
	}

	/** Define LIMIT e opcionalmente OFFSET. */
	public function limit(int $limite, ?int $offset = null): static
	{
		$this->limit  = $limite;
		$this->offset = $offset;
		return $this;
	}

	/**
	 * Define cláusula SQL extra (raw) — WHERE, ORDER, LIMIT juntos.
	 *
	 * Para compatibilidade com v1 onde `$extra` era passado como string completa.
	 * Quando usado, sobrescreve where/orderBy/limit individuais.
	 *
	 * @param string $extra SQL extra, ex: "WHERE id='1' ORDER BY nome LIMIT 10"
	 */
	public function extra(string $extra): static
	{
		$this->extra = $extra;
		return $this;
	}

	// -----------------------------------------------------------------
	//  Métodos de execução — SELECT
	// -----------------------------------------------------------------

	/**
	 * Executa SELECT e retorna todas as linhas como arrays associativos.
	 *
	 * @return array<int, array<string, mixed>>|null Linhas encontradas ou null.
	 */
	#[NoDiscard]
	public function select(): ?array
	{
		[$sql, $params] = $this->montarSqlSelect();
		$resultado = $this->banco->query($sql, $params);

		if ($resultado->contagem() === 0) {
			// Para SELECT, rowCount pode não funcionar em todos os drivers
			// Usamos fetch para verificar se há dados
			$rows = $resultado->todos();
			return $rows;
		}

		return $resultado->todos();
	}

	/**
	 * Executa SELECT e retorna apenas a primeira linha.
	 *
	 * Equivalente ao uso de `unico => true` no banco_select v1.
	 *
	 * @return array<string, mixed>|null Primeira linha ou null.
	 */
	#[NoDiscard]
	public function selectUnico(): ?array
	{
		// Adiciona LIMIT 1 se não houver limit definido e sem extra
		$limiteOriginal = $this->limit;
		if ($this->limit === null && $this->extra === null) {
			$this->limit = 1;
		}

		[$sql, $params] = $this->montarSqlSelect();
		$this->limit = $limiteOriginal;

		$resultado = $this->banco->query($sql, $params);
		return $resultado->linhaAssoc();
	}

	/**
	 * Executa SELECT para edição — retorna primeira linha e define flag de resultado.
	 *
	 * Equivalente a banco_select_editar(). Para interface-v2, prefira selectUnico().
	 *
	 * @return array<string, mixed>|null Primeira linha ou null.
	 */
	#[NoDiscard]
	public function selectEditar(): ?array
	{
		global $_GESTOR;

		[$sql, $params] = $this->montarSqlSelect();
		$resultado = $this->banco->query($sql, $params);
		$row = $resultado->linhaAssoc();

		$_GESTOR['banco-resultado'] = ($row !== null);

		return $row;
	}

	/**
	 * Executa SELECT e armazena o resultado como "dados anteriores" para comparação.
	 *
	 * Equivalente a banco_select_campos_antes_iniciar(). Os dados ficam disponíveis
	 * via $banco->campoAnterior($campo) ou $this->campoAnterior($campo).
	 *
	 * @return bool True se encontrou e armazenou dados.
	 */
	public function selectAntes(): bool
	{
		[$sql, $params] = $this->montarSqlSelect();
		$resultado = $this->banco->query($sql, $params);
		$row = $resultado->linhaAssoc();

		if ($row !== null) {
			$this->banco->setDadosAnteriores($row);
			return true;
		}
		return false;
	}

	/**
	 * Retorna o valor anterior de um campo armazenado por selectAntes().
	 *
	 * Equivalente a banco_select_campos_antes().
	 */
	#[NoDiscard]
	public function campoAnterior(string $campo): mixed
	{
		return $this->banco->campoAnterior($campo);
	}

	/**
	 * Retorna COUNT(*) da tabela com as condições aplicadas.
	 */
	#[NoDiscard]
	public function count(): int
	{
		$camposOriginal = $this->campos;
		$this->campos = 'COUNT(*) as total_record';

		[$sql, $params] = $this->montarSqlSelect();
		$this->campos = $camposOriginal;

		$resultado = $this->banco->query($sql, $params);
		$row = $resultado->linhaAssoc();

		return (int) ($row['total_record'] ?? 0);
	}

	// -----------------------------------------------------------------
	//  Métodos de execução — UPDATE
	// -----------------------------------------------------------------

	/**
	 * Executa UPDATE na tabela com os dados fornecidos.
	 *
	 * @param array<string, mixed> $dados Array associativo campo => valor.
	 *                                     Use BancoV2::raw() para expressões SQL.
	 *
	 * @example
	 *   $banco->tabela('users')
	 *         ->where("id = ?", [1])
	 *         ->update(['nome' => 'João', 'atualizado_em' => BancoV2::raw('NOW()')]);
	 */
	public function update(array $dados): static
	{
		$sets   = [];
		$params = [];

		foreach ($dados as $campo => $valor) {
			$identificador = $this->banco->quoteIdentifier($campo);
			if ($valor instanceof ExpressaoSQL) {
				$sets[] = "{$identificador} = {$valor->expressao}";
			} elseif ($valor === null) {
				$sets[] = "{$identificador} = NULL";
			} else {
				$sets[] = "{$identificador} = ?";
				$params[] = $valor;
			}
		}

		$sql = "UPDATE {$this->tabela} SET " . implode(', ', $sets);

		// Adiciona WHERE
		$whereExtra = $this->montarWhereExtra();
		if ($whereExtra !== '') {
			$sql .= ' ' . $whereExtra;
			$params = [...$params, ...$this->whereParams];
		}

		$this->banco->executar($sql, $params);
		return $this;
	}

	/**
	 * Executa UPDATE usando string SET crua (compatibilidade legada).
	 *
	 * @param string $sets String SQL com os campos SET, ex: "nome='João',email='j@t.com'"
	 */
	public function updateRaw(string $sets): static
	{
		$sql = "UPDATE {$this->tabela} SET {$sets}";

		$whereExtra = $this->montarWhereExtra();
		if ($whereExtra !== '') {
			$sql .= ' ' . $whereExtra;
		}

		$this->banco->executar($sql, $this->whereParams);
		return $this;
	}

	/**
	 * Acumula campo para update em lote (builder pattern).
	 *
	 * Equivalente a banco_update_campo(). Execute com executarUpdate().
	 *
	 * @param string $nome       Nome do campo.
	 * @param mixed  $valor      Valor a atribuir.
	 * @param bool   $semAspas   Se true, não parametriza (expressão raw).
	 * @param bool   $escapar    Se true e $semAspas true, escapa o valor.
	 */
	public function setCampo(string $nome, mixed $valor, bool $semAspas = false, bool $escapar = true): static
	{
		if ($semAspas) {
			if ($escapar && is_string($valor)) {
				$valor = $this->banco->escape($valor);
			}
			$this->updateCampos[] = "{$nome}={$valor}";
		} else {
			if ($escapar && is_string($valor)) {
				$valor = $this->banco->escape($valor);
			}
			$this->updateCampos[] = "{$nome}='{$valor}'";
		}
		return $this;
	}

	/**
	 * Executa UPDATE com campos acumulados via setCampo() e limpa o acumulador.
	 *
	 * Equivalente a banco_update_executar().
	 */
	public function executarUpdate(): static
	{
		if (!empty($this->updateCampos)) {
			$sets = implode(',', $this->updateCampos);
			$this->updateCampos = [];

			$sql = "UPDATE {$this->tabela} SET {$sets}";
			$whereExtra = $this->montarWhereExtra();
			if ($whereExtra !== '') {
				$sql .= ' ' . $whereExtra;
			}

			$this->banco->executar($sql, $this->whereParams);
		}
		return $this;
	}

	/**
	 * Atualiza múltiplos registros em massa usando CASE (batch update).
	 *
	 * Equivalente a banco_update_varios(). Divide automaticamente queries > 1MB.
	 *
	 * @param array  $dados     Array de arrays [id, valor].
	 * @param string $campoNome Nome do campo a atualizar.
	 * @param string $campoId   Nome do campo ID para a cláusula CASE.
	 */
	public function updateVarios(array $dados, string $campoNome, string $campoId): static
	{
		if (empty($dados)) return $this;

		$qi  = $this->banco->quoteIdentifier(...);
		$tab = $this->tabela;

		$sqlBase    = "UPDATE {$tab} SET {$qi($campoNome)} = CASE {$qi($campoId)}\n";
		$sqlFechar  = "ELSE {$qi($campoNome)}\nEND";
		$sql        = $sqlBase;
		$executado  = false;

		foreach ($dados as $campo) {
			$sql .= "WHEN '{$campo[0]}' THEN '{$campo[1]}'\n";
			$executado = false;

			// Divide se SQL ficar muito grande (> 1MB)
			if (strlen($sql) + strlen($sqlFechar) > 1_000_000) {
				$this->banco->executar($sql . $sqlFechar);
				$sql = $sqlBase;
				$executado = true;
			}
		}

		if (!$executado) {
			$this->banco->executar($sql . $sqlFechar);
		}

		return $this;
	}

	// -----------------------------------------------------------------
	//  Métodos de execução — INSERT
	// -----------------------------------------------------------------

	/**
	 * Insere registro usando array associativo campo => valor.
	 *
	 * Usa prepared statements para segurança. Suporta BancoV2::raw() para expressões.
	 *
	 * @param array<string, mixed> $dados Array associativo campo => valor.
	 *
	 * @example
	 *   $banco->tabela('users')->insert([
	 *       'nome'  => 'João',
	 *       'email' => 'joao@test.com',
	 *       'criado_em' => BancoV2::raw('NOW()'),
	 *   ]);
	 */
	public function insert(array $dados): static
	{
		$nomes  = [];
		$places = [];
		$params = [];

		foreach ($dados as $campo => $valor) {
			$nomes[] = $this->banco->quoteIdentifier($campo);
			if ($valor instanceof ExpressaoSQL) {
				$places[] = $valor->expressao;
			} elseif ($valor === null) {
				$places[] = 'NULL';
			} else {
				$places[] = '?';
				$params[] = $valor;
			}
		}

		$sql = "INSERT INTO {$this->tabela} (" . implode(', ', $nomes) . ") VALUES (" . implode(', ', $places) . ")";
		$this->banco->executar($sql, $params);
		return $this;
	}

	/**
	 * Insere registro usando formato legado de tuplas [nome, valor, sem_aspas].
	 *
	 * Equivalente a banco_insert_name(). Cada elemento do array é:
	 *   [0] => nome do campo
	 *   [1] => valor
	 *   [2] => (opcional) se true, valor é expressão raw (sem aspas)
	 *
	 * @param array<int, array{0: string, 1: mixed, 2?: bool}> $dados
	 */
	public function insertName(array $dados): static
	{
		$nomes  = [];
		$places = [];
		$params = [];

		foreach ($dados as $dado) {
			if (!is_array($dado) || !isset($dado[0], $dado[1])) continue;

			$semAspas = $dado[2] ?? false;
			$nomes[] = $dado[0];

			if ($semAspas) {
				$places[] = (string) $dado[1];
			} else {
				$places[] = '?';
				$params[] = $dado[1];
			}
		}

		if (empty($nomes)) return $this;

		$sql = "INSERT INTO {$this->tabela} (" . implode(',', $nomes) . ") VALUES (" . implode(',', $places) . ")";
		$this->banco->executar($sql, $params);
		return $this;
	}

	/**
	 * Acumula campo para inserção em lote (builder pattern).
	 *
	 * Equivalente a banco_insert_name_campo(). Use insertNameCampos() para recuperar e limpar.
	 */
	public function addCampoInsert(string $nome, mixed $valor, bool $semAspas = false, bool $escapar = true): static
	{
		if ($escapar && is_string($valor)) {
			$valor = $this->banco->escape($valor);
		}
		$this->insertCampos[] = [$nome, $valor, $semAspas];
		return $this;
	}

	/**
	 * Retorna e limpa campos acumulados para inserção.
	 *
	 * Equivalente a banco_insert_name_campos().
	 *
	 * @return array<int, array{0: string, 1: mixed, 2: bool}>
	 */
	#[NoDiscard]
	public function obterCamposInsert(): array
	{
		$campos = $this->insertCampos;
		$this->insertCampos = [];
		return $campos;
	}

	/**
	 * Executa os campos acumulados como INSERT.
	 */
	public function executarInsert(): static
	{
		$campos = $this->obterCamposInsert();
		if (!empty($campos)) {
			$this->insertName($campos);
		}
		return $this;
	}

	/**
	 * Insere múltiplos registros em massa com estrutura parametrizada.
	 *
	 * Equivalente a banco_insert_name_varios().
	 *
	 * @param array $params Parâmetros estruturados:
	 *   'campos' => [
	 *       ['nome' => 'campo1', 'valores' => ['a','b','c'], 'sem_aspas_simples' => false],
	 *       ['nome' => 'campo2', 'valores' => [1,2,3], 'sem_aspas_simples' => true],
	 *   ]
	 */
	public function insertNameVarios(array $params): static
	{
		$campos = $params['campos'] ?? [];
		if (empty($campos)) return $this;

		$nomesColunas = [];
		$linhaCount   = 0;

		// Determina número de linhas a partir do primeiro campo com valores
		foreach ($campos as $campo) {
			$nomesColunas[] = $campo['nome'];
			if (isset($campo['valores'])) {
				$linhaCount = max($linhaCount, count($campo['valores']));
			}
		}

		if ($linhaCount === 0) return $this;

		// Monta VALUES para cada linha
		$conjuntoValores = [];
		for ($linha = 0; $linha < $linhaCount; $linha++) {
			$valores = [];
			foreach ($campos as $campo) {
				$semAspas = isset($campo['sem_aspas_simples']);
				$valor = $campo['valores'][$linha] ?? null;

				if ($valor === null) {
					$valores[] = 'NULL';
				} elseif ($semAspas) {
					$valores[] = (string) $valor;
				} else {
					$valores[] = "'" . $this->banco->escape((string) $valor) . "'";
				}
			}
			$conjuntoValores[] = '(' . implode(',', $valores) . ')';
		}

		$sql = "INSERT INTO {$this->tabela} (" . implode(',', $nomesColunas) . ") VALUES \n" . implode(",\n", $conjuntoValores);
		$this->banco->executar($sql);
		return $this;
	}

	/**
	 * Insere múltiplos registros usando arrays associativos.
	 *
	 * API moderna para inserção em massa com prepared statements.
	 *
	 * @param array<int, array<string, mixed>> $registros Array de arrays associativos.
	 *
	 * @example
	 *   $banco->tabela('logs')->insertVarios([
	 *       ['tipo' => 'info', 'msg' => 'Login'],
	 *       ['tipo' => 'warn', 'msg' => 'Timeout'],
	 *   ]);
	 */
	public function insertVarios(array $registros): static
	{
		if (empty($registros)) return $this;

		$primeiro = array_first($registros);
		$nomes    = array_keys($primeiro);
		$nomesSQL = array_map($this->banco->quoteIdentifier(...), $nomes);

		$placeholderLinha = '(' . implode(',', array_fill(0, count($nomes), '?')) . ')';
		$allPlaceholders  = [];
		$allParams        = [];

		foreach ($registros as $registro) {
			$allPlaceholders[] = $placeholderLinha;
			foreach ($nomes as $nome) {
				$valor = $registro[$nome] ?? null;
				if ($valor instanceof ExpressaoSQL) {
					// Substituir o ? correspondente pela expressão
					// Isso é complexo com prepared statements, usar escape manual
					$allParams[] = null; // placeholder
				} else {
					$allParams[] = $valor;
				}
			}
		}

		// Para expressões raw, construir SQL manualmente
		$temRaw = false;
		foreach ($registros as $reg) {
			foreach ($reg as $v) {
				if ($v instanceof ExpressaoSQL) { $temRaw = true; break 2; }
			}
		}

		if ($temRaw) {
			// Construção manual para suportar expressões raw
			$linhas = [];
			foreach ($registros as $registro) {
				$valores = [];
				foreach ($nomes as $nome) {
					$valor = $registro[$nome] ?? null;
					if ($valor instanceof ExpressaoSQL) {
						$valores[] = $valor->expressao;
					} elseif ($valor === null) {
						$valores[] = 'NULL';
					} else {
						$valores[] = "'" . $this->banco->escape((string) $valor) . "'";
					}
				}
				$linhas[] = '(' . implode(',', $valores) . ')';
			}
			$sql = "INSERT INTO {$this->tabela} (" . implode(',', $nomesSQL) . ") VALUES " . implode(",\n", $linhas);
			$this->banco->executar($sql);
		} else {
			// Prepared statements puros (mais seguro)
			$allParams = [];
			foreach ($registros as $registro) {
				foreach ($nomes as $nome) {
					$allParams[] = $registro[$nome] ?? null;
				}
			}
			$sql = "INSERT INTO {$this->tabela} (" . implode(',', $nomesSQL) . ") VALUES " . implode(",\n", $allPlaceholders);
			$this->banco->executar($sql, $allParams);
		}

		return $this;
	}

	/**
	 * INSERT + UPDATE (upsert) baseado em existência do registro.
	 *
	 * Equivalente a banco_insert_update(). Verifica se o registro existe via SELECT,
	 * depois faz INSERT ou UPDATE conforme necessário.
	 *
	 * @param array      $dados       Dados a inserir/atualizar (chave => valor).
	 * @param string     $campoId     Nome do campo ID para verificar existência.
	 * @param array|null $tipos       Tipos dos campos: 'bool'|'int'|'string' (opcional).
	 * @param string     $extraUpdate Cláusula extra para o UPDATE (opcional).
	 */
	public function insertUpdate(array $dados, string $campoId, ?array $tipos = null, string $extraUpdate = ''): static
	{
		if (empty($dados) || !isset($dados[$campoId])) return $this;

		$idValor = $this->banco->escape((string) $dados[$campoId]);

		// Verifica se registro já existe
		$existe = $this->banco->tabela($this->tabela)
			->campos([$campoId])
			->extra("WHERE {$campoId}='{$idValor}'")
			->selectUnico();

		if ($existe) {
			// UPDATE — Remover campo ID dos dados
			$dadosUpdate = $dados;
			unset($dadosUpdate[$campoId]);

			$sets   = [];
			$params = [];

			foreach ($dadosUpdate as $chave => $valor) {
				$tipo = $tipos[$chave] ?? 'string';

				$set = match ($tipo) {
					'bool' => !empty($valor) && $valor !== '' ? "{$chave}=1" : "{$chave}=NULL",
					'int'  => !empty($valor) && $valor !== '' ? "{$chave}={$valor}" : "{$chave}=NULL",
					default => "{$chave}='" . $this->banco->escape((string) $valor) . "'",
				};
				$sets[] = $set;
			}

			$whereUpdate = "WHERE {$campoId}='{$idValor}'";
			if ($extraUpdate !== '') {
				$whereUpdate .= ' ' . $extraUpdate;
			}

			$this->banco->executar(
				"UPDATE {$this->tabela} SET " . implode(',', $sets) . " {$whereUpdate}"
			);
		} else {
			// INSERT
			$camposInsert = [];

			foreach ($dados as $chave => $valor) {
				$tipo = $tipos[$chave] ?? 'string';

				$campoInsert = match ($tipo) {
					'bool' => [$chave, (!empty($valor) && $valor !== '' ? '1' : 'NULL'), true],
					'int'  => [$chave, (!empty($valor) && $valor !== '' ? $valor : 'NULL'), true],
					default => [$chave, $this->banco->escape((string) $valor), false],
				};
				$camposInsert[] = $campoInsert;
			}

			$this->banco->tabela($this->tabela)->insertName($camposInsert);
		}

		return $this;
	}

	// -----------------------------------------------------------------
	//  Métodos de execução — DELETE
	// -----------------------------------------------------------------

	/**
	 * Executa DELETE na tabela com as condições aplicadas.
	 *
	 * @example
	 *   $banco->tabela('logs')->where("data < ?", ['2023-01-01'])->delete();
	 */
	public function delete(): static
	{
		$sql = "DELETE FROM {$this->tabela}";

		$whereExtra = $this->montarWhereExtra();
		if ($whereExtra !== '') {
			$sql .= ' ' . $whereExtra;
		}

		$this->banco->executar($sql, $this->whereParams);
		return $this;
	}

	/**
	 * Deleta múltiplos registros usando cláusula IN.
	 *
	 * Equivalente a banco_delete_varios().
	 *
	 * @param string|array $campoIds Nome(s) do(s) campo(s) ID.
	 * @param array        $ids      IDs a deletar.
	 */
	public function deleteVarios(string|array $campoIds, array $ids): static
	{
		if (empty($ids)) return $this;

		if (is_array($campoIds) && count($campoIds) > 1) {
			// Múltiplos campos — AND entre INs
			$condicoes = [];
			foreach ($campoIds as $campo) {
				if (!isset($ids[$campo]) || empty($ids[$campo])) continue;
				$idsStr = implode(',', array_map(fn($id) => "'" . $this->banco->escape((string) $id) . "'", $ids[$campo]));
				$condicoes[] = "{$campo} IN ({$idsStr})";
			}
			if (!empty($condicoes)) {
				$sql = "DELETE FROM {$this->tabela} WHERE " . implode(' AND ', $condicoes);
				$this->banco->executar($sql);
			}
		} else {
			// Campo único
			$campo = is_array($campoIds) ? array_first($campoIds) : $campoIds;
			$idsSimples = is_array($campoIds) ? $ids : $ids;
			$idsStr = implode(',', array_map(fn($id) => "'" . $this->banco->escape((string) $id) . "'", $idsSimples));
			$sql = "DELETE FROM {$this->tabela} WHERE {$campo} IN ({$idsStr})";
			$this->banco->executar($sql);
		}

		return $this;
	}

	// -----------------------------------------------------------------
	//  Métodos internos auxiliares
	// -----------------------------------------------------------------

	/**
	 * Monta o SQL completo para SELECT com parâmetros.
	 *
	 * @return array{0: string, 1: array} [sql, params]
	 */
	private function montarSqlSelect(): array
	{
		$camposStr = match (true) {
			is_array($this->campos) => BancoV2::camposVirgulas($this->campos),
			default                 => $this->campos,
		};

		$sql = "SELECT {$camposStr} FROM {$this->tabela}";
		$params = [];

		if ($this->extra !== null) {
			// Extra raw mode — usa o extra como string direta
			$sql .= ' ' . $this->extra;
			$params = $this->whereParams;
		} else {
			if ($this->where !== null) {
				// Verifica se o WHERE já contém a keyword
				$whereStr = trim($this->where);
				if (!str_starts_with(strtoupper($whereStr), 'WHERE ')) {
					$sql .= ' WHERE ' . $whereStr;
				} else {
					$sql .= ' ' . $whereStr;
				}
				$params = $this->whereParams;
			}
			if ($this->orderBy !== null) {
				$sql .= ' ORDER BY ' . $this->orderBy;
			}
			if ($this->limit !== null) {
				$sql .= ' LIMIT ' . $this->limit;
				if ($this->offset !== null) {
					$sql .= ' OFFSET ' . $this->offset;
				}
			}
		}

		return [$sql, $params];
	}

	/**
	 * Monta a parte WHERE + extras para UPDATE/DELETE.
	 */
	private function montarWhereExtra(): string
	{
		if ($this->extra !== null) {
			return $this->extra;
		}

		$parts = [];

		if ($this->where !== null) {
			$whereStr = trim($this->where);
			if (!str_starts_with(strtoupper($whereStr), 'WHERE ')) {
				$parts[] = 'WHERE ' . $whereStr;
			} else {
				$parts[] = $whereStr;
			}
		}

		return implode(' ', $parts);
	}
}

// =====================================================================
//  CLASS — Fachada principal do banco de dados (BancoV2)
// =====================================================================

/**
 * Classe principal da biblioteca de banco de dados v2.
 *
 * Gerencia a conexão PDO, fornece API fluente via ConsultaBanco,
 * e métodos legados compatíveis com banco.php v1.
 *
 * Uso principal:
 *   $banco = banco_v2();
 *   $banco->tabela('users')->campos(['nome', 'email'])->where("id = ?", [1])->select();
 *
 * Uso legado:
 *   $banco->selectName('nome,email', 'users', "WHERE id='1'");
 *
 * @example
 *   // Fluent select
 *   $users = banco_v2()->tabela('users')
 *       ->campos(['nome', 'email'])
 *       ->where("status = ? AND criado > ?", ['A', '2024-01-01'])
 *       ->orderBy('nome ASC')
 *       ->limit(10)
 *       ->select();
 *
 *   // Fluent insert
 *   banco_v2()->tabela('logs')->insert([
 *       'tipo' => 'info',
 *       'msg'  => 'Login efetuado',
 *       'data' => BancoV2::raw('NOW()'),
 *   ]);
 *
 *   // Fluent update
 *   banco_v2()->tabela('users')
 *       ->where("id = ?", [5])
 *       ->update(['nome' => 'Maria']);
 *
 *   // Fluent delete
 *   banco_v2()->tabela('logs')
 *       ->where("data < ?", ['2023-01-01'])
 *       ->delete();
 *
 *   // Transaction
 *   banco_v2()->transacao(function (BancoV2 $db) {
 *       $db->tabela('users')->insert(['nome' => 'Ana']);
 *       $id = $db->ultimoId();
 *       $db->tabela('perfis')->insert(['user_id' => $id, 'tipo' => 'admin']);
 *   });
 */
class BancoV2
{
	private ?\PDO        $conexao           = null;
	private ConfigBanco  $config;
	private int          $reconexoes        = 0;
	private ?array       $dadosAnteriores   = null;
	private array        $updateCampos      = [];
	private array        $insertNameCampos  = [];
	private bool         $debug             = false;

	// =================================================================
	//  Construtor e Conexão
	// =================================================================

	/**
	 * Cria instância do BancoV2.
	 *
	 * @param ConfigBanco|null $config Configuração de conexão. Se null, lê de $_BANCO global.
	 */
	public function __construct(?ConfigBanco $config = null)
	{
		$this->config = $config ?? ConfigBanco::fromGlobal();
	}

	/**
	 * Estabelece conexão PDO com o banco de dados.
	 *
	 * Conexão lazy — chamada automaticamente na primeira operação.
	 */
	public function conectar(): static
	{
		if ($this->conexao !== null) return $this;

		try {
			$this->conexao = new \PDO(
				$this->config->dsn(),
				$this->config->usuario,
				$this->config->senha,
				$this->config->opcoes,
			);

			// Configurações pós-conexão específicas por driver
			match ($this->config->driver) {
				DriverBanco::MySQL => $this->conexao->exec("SET NAMES {$this->config->charset}"),
				DriverBanco::PostgreSQL => $this->conexao->exec("SET client_encoding TO '{$this->config->charset}'"),
			};

		} catch (\PDOException $e) {
			error_log("ERRO BANCO V2: Conexão falhou — " . $e->getMessage());
			throw new \RuntimeException(
				"Conexão com o banco de dados não realizada: " . $e->getMessage(),
				previous: $e,
			);
		}

		return $this;
	}

	/** Reconecta ao banco de dados, fechando conexão anterior se existir. */
	public function reconectar(): static
	{
		$this->desconectar();
		$this->reconexoes++;
		return $this->conectar();
	}

	/** Fecha a conexão com o banco de dados. */
	public function desconectar(): static
	{
		$this->conexao = null;
		return $this;
	}

	/**
	 * Verifica se a conexão está ativa.
	 *
	 * Executa um SELECT 1 para testar. Retorna false se a conexão estiver inativa.
	 */
	#[NoDiscard]
	public function ping(): bool
	{
		try {
			if ($this->conexao === null) return false;
			$this->conexao->query('SELECT 1');
			return true;
		} catch (\PDOException) {
			$this->reconexoes++;
			return false;
		}
	}

	/** Retorna a instância PDO subjacente (cria conexão se necessário). */
	#[NoDiscard]
	public function conexao(): \PDO
	{
		if ($this->conexao === null) $this->conectar();
		return $this->conexao;
	}

	/** Verifica se há uma conexão ativa (sem testar com query). */
	#[NoDiscard]
	public function estaConectado(): bool
	{
		return $this->conexao !== null;
	}

	/** Retorna o driver configurado. */
	#[NoDiscard]
	public function driver(): DriverBanco
	{
		return $this->config->driver;
	}

	/** Retorna o número de reconexões desde a criação da instância. */
	#[NoDiscard]
	public function reconexoes(): int
	{
		return $this->reconexoes;
	}

	// =================================================================
	//  Query Builder — Ponto de entrada fluente
	// =================================================================

	/**
	 * Inicia construção fluente para uma tabela.
	 *
	 * Retorna um novo ConsultaBanco que pode ser encadeado com
	 * campos(), where(), orderBy(), limit() e executado com select(), insert(), etc.
	 *
	 * @param string $tabela Nome da tabela.
	 *
	 * @example $banco->tabela('users')->campos(['nome'])->where("id = ?", [1])->selectUnico();
	 */
	#[NoDiscard]
	public function tabela(string $tabela): ConsultaBanco
	{
		return new ConsultaBanco($this, $tabela);
	}

	// =================================================================
	//  Execução direta de queries
	// =================================================================

	/**
	 * Executa query SQL com prepared statements e retorna ResultadoBanco.
	 *
	 * @param string $sql    Query SQL com placeholders ? para binding.
	 * @param array  $params Parâmetros para os placeholders.
	 *
	 * @return ResultadoBanco Resultado encapsulado.
	 */
	#[NoDiscard]
	public function query(string $sql, array $params = []): ResultadoBanco
	{
		$pdo = $this->conexao();

		try {
			if (!empty($params)) {
				$stmt = $pdo->prepare($sql);
				$stmt->execute($params);
			} else {
				$stmt = $pdo->query($sql);
			}

			if ($this->debug) {
				error_log("BANCO V2 DEBUG: {$sql} | Params: " . json_encode($params));
			}

			return new ResultadoBanco($stmt);

		} catch (\PDOException $e) {
			error_log("ERRO BANCO V2: Consulta Inválida!\nConsulta: {$sql}\nParams: " . json_encode($params) . "\nErro: " . $e->getMessage());

			if ($this->debug) {
				throw new \RuntimeException(
					"Erro na query: {$e->getMessage()}\nSQL: {$sql}\nParams: " . json_encode($params),
					previous: $e,
				);
			}

			// Retorna um statement vazio para não quebrar o fluxo
			$stmtVazio = $pdo->prepare("SELECT 1 WHERE 1=0");
			$stmtVazio->execute();
			return new ResultadoBanco($stmtVazio);
		}
	}

	/**
	 * Executa query SQL e retorna número de linhas afetadas (para INSERT/UPDATE/DELETE).
	 *
	 * @param string $sql    Query SQL.
	 * @param array  $params Parâmetros para prepared statement.
	 *
	 * @return int Número de linhas afetadas.
	 */
	public function executar(string $sql, array $params = []): int
	{
		$pdo = $this->conexao();

		try {
			if (!empty($params)) {
				$stmt = $pdo->prepare($sql);
				$stmt->execute($params);
				$affected = $stmt->rowCount();
			} else {
				$affected = $pdo->exec($sql);
			}

			if ($this->debug) {
				error_log("BANCO V2 DEBUG: {$sql} | Params: " . json_encode($params) . " | Afetados: {$affected}");
			}

			return (int) $affected;

		} catch (\PDOException $e) {
			error_log("ERRO BANCO V2: Execução Inválida!\nConsulta: {$sql}\nParams: " . json_encode($params) . "\nErro: " . $e->getMessage());

			if ($this->debug) {
				throw new \RuntimeException(
					"Erro na execução: {$e->getMessage()}\nSQL: {$sql}",
					previous: $e,
				);
			}

			return 0;
		}
	}

	/**
	 * Executa query SQL e retorna todas as linhas como arrays mistos.
	 *
	 * Equivalente a banco_sql(). Cada linha é um array com chaves numéricas e associativas.
	 *
	 * @param string $sql    Query SQL.
	 * @param array  $params Parâmetros para prepared statement.
	 *
	 * @return array<int, array>|null Linhas ou null se vazio.
	 */
	#[NoDiscard]
	public function sql(string $sql, array $params = []): ?array
	{
		$resultado = $this->query($sql, $params);
		return $resultado->todosMistos();
	}

	/**
	 * Executa query SQL e retorna linhas como arrays associativos com nomes de campos.
	 *
	 * Equivalente a banco_sql_names().
	 *
	 * @param string $sql    Query SQL.
	 * @param string $campos Lista de campos separados por vírgula ou '*'.
	 * @param array  $params Parâmetros para prepared statement.
	 *
	 * @return array<int, array<string, mixed>>|null Linhas associativas ou null.
	 */
	#[NoDiscard]
	public function sqlAssoc(string $sql, string $campos = '*', array $params = []): ?array
	{
		$resultado = $this->query($sql, $params);
		return $resultado->todos();
	}

	// =================================================================
	//  Escape e Quoting
	// =================================================================

	/**
	 * Escapa valor para uso seguro em queries SQL.
	 *
	 * Equivalente a banco_escape_field(). Usa PDO::quote() internamente.
	 * NOTA: Prefira prepared statements (?) ao invés de escape manual.
	 *
	 * @param string $value Valor a escapar.
	 * @return string Valor escapado (sem aspas externas do PDO::quote).
	 */
	#[NoDiscard]
	public function escape(string $value): string
	{
		$pdo = $this->conexao();
		$quoted = $pdo->quote($value);
		// PDO::quote() adiciona aspas externas — removemos para compatibilidade
		return substr($quoted, 1, -1);
	}

	/**
	 * Cita um identificador SQL (nome de tabela ou campo).
	 *
	 * MySQL usa backticks, PostgreSQL usa aspas duplas.
	 *
	 * @param string $nome Nome do identificador.
	 */
	#[NoDiscard]
	public function quoteIdentifier(string $nome): string
	{
		// Não cita se já contiver ponto (tabela.campo), asterisco, ou já estiver citado
		if (str_contains($nome, '.') || str_contains($nome, '*') || str_contains($nome, '(')
			|| str_starts_with($nome, '`') || str_starts_with($nome, '"')) {
			return $nome;
		}

		return match ($this->config->driver) {
			DriverBanco::MySQL      => "`{$nome}`",
			DriverBanco::PostgreSQL => "\"{$nome}\"",
		};
	}

	// =================================================================
	//  Utilitários
	// =================================================================

	/**
	 * Retorna o último ID inserido (auto-increment / sequence).
	 *
	 * Para PostgreSQL, pode ser necessário informar o nome da sequência.
	 * Equivalente a banco_last_id().
	 *
	 * @param string|null $sequencia Nome da sequência (PostgreSQL). Se null, usa o padrão.
	 */
	#[NoDiscard]
	public function ultimoId(?string $sequencia = null): int|string
	{
		$pdo = $this->conexao();

		if ($this->config->driver === DriverBanco::PostgreSQL && $sequencia === null) {
			// PostgreSQL padrão: recupera via lastval()
			try {
				$stmt = $pdo->query('SELECT lastval()');
				return (int) $stmt->fetchColumn();
			} catch (\PDOException) {
				return 0;
			}
		}

		return $pdo->lastInsertId($sequencia) ?: 0;
	}

	/**
	 * Retorna o total de registros de uma tabela.
	 *
	 * Equivalente a banco_total_rows().
	 *
	 * @param string      $tabela Nome da tabela.
	 * @param string|null $extra  Cláusula WHERE extra (opcional).
	 * @param array       $params Parâmetros para prepared statement.
	 */
	#[NoDiscard]
	public function totalRegistros(string $tabela, ?string $extra = null, array $params = []): int
	{
		$sql = "SELECT COUNT(*) as total_record FROM {$tabela}";
		if ($extra !== null) {
			$sql .= ' ' . $extra;
		}

		$resultado = $this->query($sql, $params);
		$row = $resultado->linhaAssoc();

		return (int) ($row['total_record'] ?? 0);
	}

	/**
	 * Retorna informações sobre as colunas de uma tabela.
	 *
	 * Equivalente a banco_campos_nomes(). Suporta MySQL e PostgreSQL.
	 *
	 * @param string $tabela Nome da tabela.
	 * @return array<int, array{Field: string, Type: string, Null: string, Key: string, Default: mixed, Extra: string}>
	 */
	#[NoDiscard]
	public function camposInfo(string $tabela): array
	{
		$sql = match ($this->config->driver) {
			DriverBanco::MySQL => "SHOW COLUMNS FROM {$tabela}",
			DriverBanco::PostgreSQL =>
				"SELECT column_name as \"Field\", data_type as \"Type\", " .
				"is_nullable as \"Null\", " .
				"CASE WHEN column_default LIKE 'nextval%' THEN 'PRI' " .
				"     WHEN column_default IS NOT NULL THEN '' ELSE '' END as \"Key\", " .
				"column_default as \"Default\", '' as \"Extra\" " .
				"FROM information_schema.columns " .
				"WHERE table_name = ? AND table_schema = 'public' " .
				"ORDER BY ordinal_position",
		};

		$params = match ($this->config->driver) {
			DriverBanco::MySQL      => [],
			DriverBanco::PostgreSQL => [$tabela],
		};

		$resultado = $this->query($sql, $params);
		$rows = $resultado->todos();

		if (!$rows) return [];

		// Normaliza o formato — remove chaves numéricas (compatibilidade v1)
		$campos = [];
		foreach ($rows as $row) {
			$campos[] = array_filter($row, fn($key) => !is_numeric($key), ARRAY_FILTER_USE_KEY);
		}

		return $campos;
	}

	/**
	 * Verifica se um campo existe em uma tabela.
	 *
	 * Equivalente a banco_campo_existe().
	 */
	#[NoDiscard]
	public function campoExiste(string $campo, string $tabela): bool
	{
		$campos = $this->camposInfo($tabela);

		foreach ($campos as $info) {
			if (isset($info['Field']) && $info['Field'] === $campo) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Retorna lista com nomes de todas as tabelas do banco.
	 *
	 * Equivalente a banco_tabelas_lista(). Suporta MySQL e PostgreSQL.
	 */
	#[NoDiscard]
	public function tabelasLista(): array
	{
		$sql = match ($this->config->driver) {
			DriverBanco::MySQL      => "SHOW TABLES",
			DriverBanco::PostgreSQL => "SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'",
		};

		$resultado = $this->query($sql);
		$lista = [];

		while ($row = $resultado->linha()) {
			$lista[] = $row[0];
		}

		return $lista;
	}

	/**
	 * Retorna array com nomes de todos os campos de uma tabela.
	 *
	 * Equivalente a banco_fields_names().
	 */
	#[NoDiscard]
	public function camposNomes(string $tabela): ?array
	{
		$sql = match ($this->config->driver) {
			DriverBanco::MySQL => "SELECT * FROM {$tabela} LIMIT 1",
			DriverBanco::PostgreSQL => "SELECT * FROM {$tabela} LIMIT 1",
		};

		$resultado = $this->query($sql);
		$total = $resultado->totalCampos();

		if ($total === 0) return null;

		$nomes = [];
		for ($i = 0; $i < $total; $i++) {
			$nomes[] = $resultado->nomeCampo($i);
		}

		return $nomes;
	}

	// =================================================================
	//  Dados Anteriores (selectAntes / campoAnterior)
	// =================================================================

	/**
	 * Armazena dados anteriores para comparação posterior.
	 * Usado internamente por ConsultaBanco::selectAntes().
	 *
	 * @internal
	 */
	public function setDadosAnteriores(?array $dados): void
	{
		$this->dadosAnteriores = $dados;

		// Compatibilidade legada com $_GESTOR['banco-antes']
		global $_GESTOR;
		$_GESTOR['banco-antes'] = $dados;
	}

	/**
	 * Retorna valor anterior de um campo armazenado por selectAntes().
	 *
	 * Equivalente a banco_select_campos_antes().
	 */
	#[NoDiscard]
	public function campoAnterior(string $campo): mixed
	{
		return $this->dadosAnteriores[$campo] ?? null;
	}

	// =================================================================
	//  Acumuladores legados (updateCampo / insertNameCampo)
	// =================================================================

	/**
	 * Acumula campo para update em lote.
	 *
	 * Equivalente a banco_update_campo(). Execute com updateExecutar().
	 */
	public function updateCampo(string $nome, string $valor, bool $semAspas = false, bool $escapar = true): static
	{
		if ($escapar) {
			$valor = $this->escape($valor);
		}

		$this->updateCampos[] = $semAspas ? "{$nome}={$valor}" : "{$nome}='{$valor}'";
		return $this;
	}

	/**
	 * Executa update com campos acumulados e limpa o acumulador.
	 *
	 * Equivalente a banco_update_executar().
	 */
	public function updateExecutar(string $tabela, string $extra = ''): static
	{
		if (!empty($this->updateCampos)) {
			$sets = implode(',', $this->updateCampos);
			$this->updateCampos = [];

			$sql = "UPDATE {$tabela} SET {$sets}";
			if ($extra !== '') {
				$sql .= ' ' . $extra;
			}

			$this->executar($sql);
		}

		return $this;
	}

	/**
	 * Acumula campo para inserção em lote.
	 *
	 * Equivalente a banco_insert_name_campo(). Execute com insertNameExecutar().
	 */
	public function insertNameCampo(string $nome, string $valor, bool $semAspas = false, bool $escapar = true): static
	{
		if ($escapar) {
			$valor = $this->escape($valor);
		}

		$this->insertNameCampos[] = [$nome, $valor, $semAspas];
		return $this;
	}

	/**
	 * Retorna e limpa campos acumulados para inserção.
	 *
	 * Equivalente a banco_insert_name_campos().
	 */
	#[NoDiscard]
	public function insertNameCampos(): array
	{
		$campos = $this->insertNameCampos;
		$this->insertNameCampos = [];
		return $campos;
	}

	// =================================================================
	//  Métodos legados (compat com banco.php v1)
	// =================================================================

	/**
	 * Seleciona dados de forma estruturada (legado).
	 *
	 * Equivalente a banco_select($params).
	 *
	 * @param array|false $params Array com 'campos', 'tabela', 'extra', 'unico'.
	 */
	#[NoDiscard]
	public function selectLegado(array|false $params = false): ?array
	{
		if (!$params) return null;

		$campos = $params['campos'] ?? '*';
		$tabelaNome = $params['tabela'] ?? null;
		$extra  = $params['extra'] ?? null;
		$unico  = $params['unico'] ?? false;

		if (!$tabelaNome) return null;

		$consulta = $this->tabela($tabelaNome);

		if ($campos !== '*') {
			$consulta->campos($campos);
		}

		if ($extra !== null) {
			$consulta->extra($extra);
		}

		return $unico ? $consulta->selectUnico() : $consulta->select();
	}

	/**
	 * SELECT com nomes de campos (legado).
	 *
	 * Equivalente a banco_select_name($campos, $tabela, $extra).
	 */
	#[NoDiscard]
	public function selectName(string $campos, string $tabela, ?string $extra = null): ?array
	{
		$sql = "SELECT {$campos} FROM {$tabela}";
		if ($extra) {
			$sql .= ' ' . $extra;
		}

		return $this->sqlAssoc($sql, $campos);
	}

	/**
	 * SELECT para edição (legado).
	 *
	 * Equivalente a banco_select_editar($campos, $tabela, $extra).
	 * Retorna apenas o primeiro registro e define $_GESTOR['banco-resultado'].
	 */
	#[NoDiscard]
	public function selectEditar(string $campos, string $tabela, ?string $extra = null): ?array
	{
		global $_GESTOR;

		$sql = "SELECT {$campos} FROM {$tabela}";
		if ($extra) {
			$sql .= ' ' . $extra;
		}

		$resultado = $this->query($sql);
		$row = $resultado->linhaAssoc();

		$_GESTOR['banco-resultado'] = ($row !== null);

		return $row;
	}

	/**
	 * SELECT e armazena campos anteriores (legado).
	 *
	 * Equivalente a banco_select_campos_antes_iniciar().
	 */
	public function selectCamposAntesIniciar(string $campos, string $tabela, ?string $extra = null): bool
	{
		$sql = "SELECT {$campos} FROM {$tabela}";
		if ($extra) {
			$sql .= ' ' . $extra;
		}

		$resultado = $this->query($sql);
		$row = $resultado->linhaAssoc();

		if ($row !== null) {
			$this->setDadosAnteriores($row);
			return true;
		}
		return false;
	}

	/**
	 * UPDATE simples (legado).
	 *
	 * Equivalente a banco_update($campos, $tabela, $extra).
	 */
	public function updateSQL(string $campos, string $tabela, ?string $extra = null): static
	{
		$sql = "UPDATE {$tabela} SET {$campos}";
		if ($extra) {
			$sql .= ' ' . $extra;
		}
		$this->executar($sql);
		return $this;
	}

	/**
	 * UPDATE em massa via CASE (legado).
	 *
	 * Equivalente a banco_update_varios($campos, $tabela, $campo_nome, $id_nome).
	 */
	public function updateVarios(array $campos, string $tabela, string $campoNome, string $idNome): static
	{
		$this->tabela($tabela)->updateVarios($campos, $campoNome, $idNome);
		return $this;
	}

	/**
	 * INSERT_NAME (legado) — insere usando array de tuplas [nome, valor, sem_aspas].
	 *
	 * Equivalente a banco_insert_name($dados, $tabela).
	 */
	public function insertNameLegado(array $dados, string $tabela): static
	{
		$this->tabela($tabela)->insertName($dados);
		return $this;
	}

	/**
	 * INSERT legado com '0' como primeiro valor (auto-increment MySQL).
	 *
	 * Equivalente a banco_insert($campos, $tabela).
	 * NOTA: Incompatível com PostgreSQL. Prefira insert() com array associativo.
	 */
	public function insertLegado(string $campos, string $tabela): static
	{
		$sql = "INSERT INTO {$tabela} VALUES('0',{$campos})";
		$this->executar($sql);
		return $this;
	}

	/**
	 * INSERT_NAME_VARIOS (legado).
	 *
	 * Equivalente a banco_insert_name_varios($params).
	 */
	public function insertNameVariosLegado(array $params): static
	{
		$tabela = $params['tabela'] ?? '';
		if ($tabela !== '') {
			$this->tabela($tabela)->insertNameVarios($params);
		}
		return $this;
	}

	/**
	 * INSERT_VARIOS legado com '0' como ID.
	 *
	 * Equivalente a banco_insert_varios($campos, $tabela).
	 */
	public function insertVariosLegado(array $campos, string $tabela): static
	{
		$conjuntoValores = [];
		foreach ($campos as $campo) {
			$conjuntoValores[] = "('0',{$campo})";
		}

		$sql = "INSERT INTO {$tabela} VALUES " . implode(',', $conjuntoValores);
		$this->executar($sql);
		return $this;
	}

	/**
	 * INSERT_VARIOS_TUDO legado (sem '0' auto-increment).
	 *
	 * Equivalente a banco_insert_varios_tudo($campos, $tabela).
	 */
	public function insertVariosTudoLegado(array $campos, string $tabela): static
	{
		$conjuntoValores = [];
		foreach ($campos as $campo) {
			$conjuntoValores[] = "({$campo})";
		}

		$sql = "INSERT INTO {$tabela} VALUES " . implode(',', $conjuntoValores);
		$this->executar($sql);
		return $this;
	}

	/**
	 * INSERT com ID (legado).
	 *
	 * Equivalente a banco_insert_id() / banco_insert_tudo().
	 */
	public function insertTudoLegado(string $campos, string $tabela): static
	{
		$sql = "INSERT INTO {$tabela} VALUES({$campos})";
		$this->executar($sql);
		return $this;
	}

	/**
	 * DELETE simples (legado).
	 *
	 * Equivalente a banco_delete($tabela, $extra).
	 */
	public function deletar(string $tabela, string $extra): static
	{
		$sql = "DELETE FROM {$tabela} {$extra}";
		$this->executar($sql);
		return $this;
	}

	/**
	 * DELETE em massa (legado).
	 *
	 * Equivalente a banco_delete_varios($tabela, $campo_ids, $array_ids).
	 */
	public function deletarVarios(string $tabela, string|array $campoIds, array $ids): static
	{
		$this->tabela($tabela)->deleteVarios($campoIds, $ids);
		return $this;
	}

	/**
	 * INSERT/UPDATE baseado em existência (legado).
	 *
	 * Equivalente a banco_insert_update($params).
	 */
	public function insertUpdateLegado(array $params): static
	{
		$tabela    = $params['tabela'] ?? null;
		$dados     = $params['dados'] ?? null;
		$dadosTipo = $params['dadosTipo'] ?? null;

		if (!$tabela || !$dados || !isset($tabela['nome'], $tabela['id'])) {
			return $this;
		}

		$this->tabela($tabela['nome'])->insertUpdate(
			dados: $dados,
			campoId: $tabela['id'],
			tipos: $dadosTipo,
			extraUpdate: $tabela['extra'] ?? '',
		);

		return $this;
	}

	// =================================================================
	//  Identificadores únicos
	// =================================================================

	/**
	 * Gera identificador único recursivamente.
	 *
	 * Equivalente a banco_identificador_unico(). Verifica unicidade no banco
	 * e adiciona sufixo numérico se necessário.
	 *
	 * @param array $params Array com 'id', 'num', 'tabela', 'sem_traco'.
	 */
	#[NoDiscard]
	public function identificadorUnico(array $params): string
	{
		$id       = $params['id'] ?? '';
		$num      = $params['num'] ?? 0;
		$tabConf  = $params['tabela'] ?? null;
		$semTraco = $params['sem_traco'] ?? null;

		$idTeste = $num > 0 ? "{$id}-{$num}" : $id;

		// Verifica se ID já existe no banco
		if ($tabConf) {
			$whereStr = "WHERE {$tabConf['campo']}='{$idTeste}'";

			if (isset($tabConf['id_valor'])) {
				$whereStr .= " AND {$tabConf['id_nome']}!='{$tabConf['id_valor']}'";
			}
			if (!isset($tabConf['sem_status'])) {
				$statusCampo = $tabConf['status'] ?? 'status';
				$whereStr .= " AND {$statusCampo}!='D'";
			}
			if (isset($tabConf['where'])) {
				$whereStr .= " AND ({$tabConf['where']})";
			}

			$resultado = $this->tabela($tabConf['nome'])
				->campos([$tabConf['id_nome']])
				->extra($whereStr)
				->select();
		} else {
			$resultado = null;
		}

		if ($resultado) {
			// ID existe — tenta próximo número recursivamente
			return $this->identificadorUnico([
				'id'        => $id,
				'num'       => $num + 1,
				'tabela'    => $tabConf,
				'sem_traco' => $semTraco,
			]);
		}

		// ID é único
		return $semTraco
			? str_replace('-', '', $idTeste)
			: $idTeste;
	}

	/**
	 * Gera identificador único a partir de string.
	 *
	 * Equivalente a banco_identificador(). Normaliza a string, limita a 90 chars,
	 * e verifica unicidade no banco.
	 *
	 * @param array $params Array com 'id', 'tabela', 'sem_traco'.
	 */
	#[NoDiscard]
	public function identificador(array $params): string
	{
		$id       = $params['id'] ?? '';
		$tabConf  = $params['tabela'] ?? null;
		$semTraco = $params['sem_traco'] ?? null;

		$tamMaxId = 90;

		// Normaliza ID usando pipe operator
		$id = trim($id) |> self::retirarAcentos(...);

		// Limita tamanho do ID a 90 caracteres
		$partes = explode('-', $id);
		$preId  = '';

		foreach ($partes as $i => $parte) {
			if (!$parte) continue;
			$preId .= $parte;

			if (strlen($preId) > $tamMaxId) break;

			if ($i < count($partes) - 1) {
				$preId .= '-';
			}
		}

		$id = $preId;

		// Verifica se ID já termina com sufixo numérico
		$idPartes = explode('-', $id);

		if (count($idPartes) > 1 && is_numeric(array_last($idPartes))) {
			$num = (int) array_last($idPartes);
			$idBase = implode('-', array_slice($idPartes, 0, -1));

			return $this->identificadorUnico([
				'id'        => $idBase,
				'num'       => $num,
				'tabela'    => $tabConf,
				'sem_traco' => $semTraco,
			]);
		}

		return $this->identificadorUnico([
			'id'        => $id,
			'num'       => 0,
			'tabela'    => $tabConf,
			'sem_traco' => $semTraco,
		]);
	}

	// =================================================================
	//  Transações
	// =================================================================

	/**
	 * Executa operações dentro de uma transação.
	 *
	 * Faz commit automático em caso de sucesso e rollback em caso de exceção.
	 *
	 * @param callable(BancoV2): mixed $callback Função que recebe $this.
	 * @return mixed Valor retornado pelo callback.
	 *
	 * @example
	 *   $banco->transacao(function (BancoV2 $db) {
	 *       $db->tabela('users')->insert(['nome' => 'Ana']);
	 *       $db->tabela('perfis')->insert(['user_id' => $db->ultimoId(), 'tipo' => 'admin']);
	 *   });
	 */
	public function transacao(callable $callback): mixed
	{
		$pdo = $this->conexao();
		$pdo->beginTransaction();

		try {
			$resultado = $callback($this);
			$pdo->commit();
			return $resultado;
		} catch (\Throwable $e) {
			$pdo->rollBack();
			throw $e;
		}
	}

	/** Inicia transação manualmente. */
	public function iniciarTransacao(): static
	{
		$this->conexao()->beginTransaction();
		return $this;
	}

	/** Confirma (commit) transação. */
	public function confirmar(): static
	{
		$this->conexao()->commit();
		return $this;
	}

	/** Reverte (rollback) transação. */
	public function reverter(): static
	{
		$this->conexao()->rollBack();
		return $this;
	}

	// =================================================================
	//  Debug
	// =================================================================

	/** Ativa/desativa modo debug (loga todas as queries). */
	public function setDebug(bool $debug): static
	{
		$this->debug = $debug;
		return $this;
	}

	/** Retorna se modo debug está ativo. */
	#[NoDiscard]
	public function isDebug(): bool
	{
		return $this->debug;
	}

	// =================================================================
	//  Métodos estáticos — Utilitários
	// =================================================================

	/**
	 * Cria expressão SQL crua (não parametrizada).
	 *
	 * Usa para valores como NOW(), NULL, expressões aritméticas, etc.
	 *
	 * @param string $expressao Expressão SQL crua.
	 *
	 * @example
	 *   $banco->tabela('logs')->insert([
	 *       'data' => BancoV2::raw('NOW()'),
	 *       'contagem' => BancoV2::raw('contagem + 1'),
	 *   ]);
	 */
	#[NoDiscard]
	public static function raw(string $expressao): ExpressaoSQL
	{
		return new ExpressaoSQL($expressao);
	}

	/**
	 * Converte array de campos em string separada por vírgulas.
	 *
	 * Equivalente a banco_campos_virgulas().
	 *
	 * @param array $campos Array de nomes de campos.
	 * @return string Campos separados por vírgulas.
	 */
	#[NoDiscard]
	public static function camposVirgulas(array $campos): string
	{
		return implode(',', $campos);
	}

	/**
	 * Remove acentos e caracteres especiais de uma string.
	 *
	 * Equivalente a banco_retirar_acentos(). Normaliza para uso em URLs/IDs.
	 *
	 * @param string $var             String a processar.
	 * @param bool   $retirarEspaco   Se true, substitui espaços por hífens.
	 * @return string String normalizada.
	 */
	#[NoDiscard]
	public static function retirarAcentos(string $var, bool $retirarEspaco = true): string
	{
		$var = strtolower($var);

		// Mapa de acentos para ASCII
		$unwanted = [
			'Š'=>'S','š'=>'s','Ž'=>'Z','ž'=>'z','À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A',
			'Å'=>'A','Æ'=>'A','Ç'=>'C','È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E','Ì'=>'I','Í'=>'I',
			'Î'=>'I','Ï'=>'I','Ñ'=>'N','Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','Ø'=>'O',
			'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U','Ý'=>'Y','Þ'=>'B','ß'=>'Ss','à'=>'a','á'=>'a',
			'â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','æ'=>'a','ª'=>'a','ç'=>'c','è'=>'e','é'=>'e',
			'ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ð'=>'o','ñ'=>'n','ò'=>'o',
			'ó'=>'o','ô'=>'o','õ'=>'o','º'=>'o','ö'=>'o','ø'=>'o','ù'=>'u','ú'=>'u','û'=>'u',
			'ý'=>'y','þ'=>'b','ÿ'=>'y',
		];

		$var = strtr($var, $unwanted);

		// Remove caracteres especiais
		$var = preg_replace("/[\.\\\\,:;<>\/:\?\|_!`~@#\$%\^&\*\"'\+=]/", '', $var);
		$var = preg_replace("/[\(\)\{\}\[\]]/", '-', $var);

		if ($retirarEspaco) {
			$var = str_replace(' ', '-', $var);
		}

		// Normaliza hífens
		$var = preg_replace('/\-+/', '-', $var);
		$var = preg_replace("/[^a-z^A-Z^0-9^-]/", '', $var);
		$var = preg_replace("/\-{2,}/", '-', $var);

		return $var;
	}

	/**
	 * Gera informações de debug do backtrace.
	 *
	 * Equivalente a banco_erro_debug().
	 */
	#[NoDiscard]
	public static function erroDebug(): string
	{
		$bt = debug_backtrace();
		$ret = "\n\nDebug:\n";

		foreach ($bt as $frame) {
			$file = $frame['file'] ?? '?';
			$line = $frame['line'] ?? '?';
			$func = $frame['function'] ?? '?';
			$ret .= "\n{$file}:{$line} => {$func}";
		}

		return $ret;
	}
}

// =====================================================================
//  FUNÇÕES GLOBAIS — Singleton e helpers
// =====================================================================

/**
 * Retorna instância singleton do BancoV2.
 *
 * Principal ponto de entrada para toda a API. Na primeira chamada,
 * cria a instância usando $_BANCO global para configuração.
 *
 * @param ConfigBanco|null $config Configuração personalizada (opcional, apenas na primeira chamada).
 *
 * @example
 *   // Select fluente
 *   $users = banco_v2()->tabela('users')
 *       ->campos(['nome', 'email'])
 *       ->where("status = ?", ['A'])
 *       ->select();
 *
 *   // Insert
 *   banco_v2()->tabela('logs')->insert([
 *       'tipo' => 'info',
 *       'msg'  => 'Login',
 *       'data' => BancoV2::raw('NOW()'),
 *   ]);
 */
function banco_v2(?ConfigBanco $config = null): BancoV2
{
	static $instancia = null;

	if ($instancia === null) {
		$instancia = new BancoV2($config);
	}

	return $instancia;
}

/**
 * Cria ConfigBanco a partir do array global $_BANCO.
 *
 * Útil para inspecionar ou modificar a configuração padrão.
 */
#[NoDiscard]
function banco_v2_config(): ConfigBanco
{
	return ConfigBanco::fromGlobal();
}

/**
 * Atalho para BancoV2::raw() — cria expressão SQL crua.
 *
 * @param string $expressao Expressão SQL.
 */
#[NoDiscard]
function banco_v2_raw(string $expressao): ExpressaoSQL
{
	return BancoV2::raw($expressao);
}

/**
 * Atalho para BancoV2::camposVirgulas() — converte array em string CSV.
 *
 * @param array $campos Array de campos.
 */
#[NoDiscard]
function banco_v2_campos_virgulas(array $campos): string
{
	return BancoV2::camposVirgulas($campos);
}

/**
 * Atalho para BancoV2::retirarAcentos() — normaliza string.
 *
 * @param string $var           String a processar.
 * @param bool   $retirarEspaco Se true, substitui espaços por hífens.
 */
#[NoDiscard]
function banco_v2_retirar_acentos(string $var, bool $retirarEspaco = true): string
{
	return BancoV2::retirarAcentos($var, $retirarEspaco);
}

?>
