# banco-v2.php — Documentação Técnica

## Visão Geral

Reescrita OOP completa de `banco.php` usando **PDO** com suporte a **MySQL** e **PostgreSQL**.
Exige **PHP 8.5+** (usa pipe operator `|>`, `clone with`, `#[NoDiscard]`, enums, readonly classes).

---

## Arquitetura

```
┌──────────────────────────────────────────────────────────┐
│                    Funções Globais                        │
│  banco_v2()  banco_v2_config()  banco_v2_raw()           │
│  banco_v2_campos_virgulas()  banco_v2_retirar_acentos()  │
└──────────────────────┬───────────────────────────────────┘
                       │
┌──────────────────────▼───────────────────────────────────┐
│                    BancoV2 (main)                         │
│  conectar() desconectar() ping() tabela()                │
│  query() executar() sql() sqlAssoc()                     │
│  escape() quoteIdentifier() ultimoId()                   │
│  totalRegistros() camposInfo() tabelasLista()            │
│  selectName() selectEditar() updateSQL() deletar()       │
│  transacao() identificador() identificadorUnico()        │
│  selectLegado() insertLegado()                           │
│  static: raw() camposVirgulas() retirarAcentos()         │
└──────────────────────┬───────────────────────────────────┘
                       │
        ┌──────────────┼──────────────┐
        ▼              ▼              ▼
┌──────────────┐ ┌──────────┐ ┌──────────────┐
│ ConsultaBanco│ │ConfigBanco│ │ResultadoBanco│
│ (fluent API) │ │ (readonly)│ │  (PDO wrap)  │
│              │ │           │ │              │
│ campos()     │ │ driver    │ │ contagem()   │
│ where()      │ │ host      │ │ linhaAssoc() │
│ eWhere()     │ │ porta     │ │ todos()      │
│ ouWhere()    │ │ nome      │ │ nomesCampos()│
│ orderBy()    │ │ usuario   │ │ primeiroVal()│
│ limit()      │ │ senha     │ │ coluna()     │
│ extra()      │ │ charset   │ │              │
│              │ │           │ └──────────────┘
│ select()     │ │ dsn()     │
│ selectUnico()│ │ paraMySQL │  ┌──────────┐
│ selectEditar│ │ paraPostgr│  │DriverBanc│
│ selectAntes()│ │ fromGlobal│  │ (enum)   │
│ count()      │ └───────────┘  │ MySQL    │
│ update()     │                │ PostgreSQL│
│ insert()     │ ┌───────────┐  └──────────┘
│ insertName() │ │ExpressaoSQL│
│ insertUpdate│ │ (readonly) │
│ delete()     │ │ raw SQL    │
│ deleteVarios│ │ __toString()│
└──────────────┘ └────────────┘
```

---

## Uso Básico

### Singleton

```php
// Obtém instância (conecta automaticamente via $_BANCO global)
$db = banco_v2();

// Ou com configuração explícita
$db = banco_v2(new ConfigBanco(
    driver: DriverBanco::MySQL,
    host: 'localhost',
    nome: 'meu_banco',
    usuario: 'root',
    senha: 'pass',
));
```

### Fluent Query Builder

```php
// SELECT
$usuarios = banco_v2()
    ->tabela('usuarios')
    ->campos(['id', 'nome', 'email'])
    ->where("status != 'D'")
    ->orderBy('nome ASC')
    ->limit(10)
    ->select();

// SELECT único
$user = banco_v2()
    ->tabela('usuarios')
    ->campos(['id', 'nome'])
    ->where("id = ?", [$id])
    ->selectUnico();

// COUNT
$total = banco_v2()
    ->tabela('usuarios')
    ->where("status = 'A'")
    ->count();

// INSERT
banco_v2()
    ->tabela('usuarios')
    ->campos(['nome', 'email', 'status'])
    ->insert(['João', 'joao@email.com', 'A']);

// INSERT com campos nomeados
banco_v2()->tabela('historico')->insertName([
    ['campo', 'nome', null],
    ['valor_antes', 'antigo', null],
    ['valor_depois', 'novo', null],
]);

// UPDATE
banco_v2()
    ->tabela('usuarios')
    ->where("id = ?", [$id])
    ->update(['nome' => 'Novo Nome', 'email' => 'novo@email.com']);

// DELETE
banco_v2()
    ->tabela('usuarios')
    ->where("id = ?", [$id])
    ->delete();

// INSERT ON DUPLICATE KEY UPDATE
banco_v2()
    ->tabela('configuracoes')
    ->campos(['chave', 'valor'])
    ->insertUpdate(['tema', 'dark'], ['valor' => 'dark']);
```

### Métodos Legados (compatibilidade com banco.php)

```php
// selectName — mesmo formato que banco_select_name()
$resultado = banco_v2()->selectName(
    BancoV2::camposVirgulas(['nome', 'email']),
    'usuarios',
    "WHERE id = '{$id}'"
);

// selectEditar — mesmo formato que banco_select_editar()
$dados = banco_v2()->selectEditar('usuarios', "id = '{$id}'");

// updateSQL — mesmo formato que banco_update()
banco_v2()->updateSQL(
    BancoV2::camposVirgulas(["nome='Novo'", "versao = versao + 1"]),
    'usuarios',
    "WHERE id = '{$id}'"
);

// deletar — mesmo formato que banco_delete()
banco_v2()->deletar('usuarios', "WHERE id = '{$id}'");

// selectLegado — mesmo formato que banco_select()
$dados = banco_v2()->selectLegado([
    'tabela' => 'usuarios',
    'campos' => ['id', 'nome'],
    'extra' => "WHERE status = 'A'",
]);

// escape
$seguro = banco_v2()->escape($valorInseguro);

// raw SQL
$resultado = banco_v2()->sql("SELECT NOW()");
```

### Transações

```php
banco_v2()->transacao(function(BancoV2 $db) {
    $db->tabela('contas')
        ->where("id = ?", [1])
        ->update(['saldo' => 900]);

    $db->tabela('contas')
        ->where("id = ?", [2])
        ->update(['saldo' => 1100]);
});
```

### Dual Driver (MySQL ↔ PostgreSQL)

```php
$config = ConfigBanco::fromGlobal();

// Variante MySQL
$mysql = banco_v2($config->paraMySQL());

// Variante PostgreSQL  
$pgsql = banco_v2($config->paraPostgreSQL());
```

---

## Features PHP 8.5 Utilizadas

| Feature | Uso no código |
|---|---|
| Pipe operator `\|>` | `BancoV2::retirarAcentos()`, `identificador()` |
| `clone with` | `ConfigBanco::paraMySQL()`, `ConfigBanco::paraPostgreSQL()` |
| `#[NoDiscard]` | Métodos que retornam dados (queries) |
| Enums | `DriverBanco` (MySQL/PostgreSQL) |
| Readonly classes | `ConfigBanco`, `ExpressaoSQL` |
| `array_first()` / `array_last()` | Utilitários internos |
| Match expressions | SQL dialect switching |
| Constructor promotion | Todas as classes |
| Named arguments | Fluent API internals |

---

## Mapeamento banco.php → banco-v2.php

| banco.php (procedural) | banco-v2.php (OOP) |
|---|---|
| `banco_conectar()` | `banco_v2()->conectar()` (automático) |
| `banco_escape_field($v)` | `banco_v2()->escape($v)` |
| `banco_campos_virgulas($c)` | `BancoV2::camposVirgulas($c)` |
| `banco_select_name($c, $t, $e)` | `banco_v2()->selectName($c, $t, $e)` |
| `banco_select_editar($t, $e)` | `banco_v2()->selectEditar($t, $e)` |
| `banco_select($arr)` | `banco_v2()->selectLegado($arr)` |
| `banco_update($s, $t, $e)` | `banco_v2()->updateSQL($s, $t, $e)` |
| `banco_insert_name($c, $t)` | `banco_v2()->tabela($t)->insertName($c)` |
| `banco_insert($c, $t)` | `banco_v2()->insertLegado($c, $t)` |
| `banco_delete($t, $e)` | `banco_v2()->deletar($t, $e)` |
| `banco_identificador()` | `banco_v2()->identificador()` |
| `banco_identificador_unico($t, $c)` | `banco_v2()->identificadorUnico($t, $c)` |
| `banco_tabelas_lista()` | `banco_v2()->tabelasLista()` |
| `banco_campos_nomes($t)` | `banco_v2()->camposNomes($t)` |
| `banco_retirar_acentos($v)` | `BancoV2::retirarAcentos($v)` |

---

## Arquivos Docker

| Arquivo | Descrição |
|---|---|
| `Dockerfile.php85` | PHP 8.5 + pdo_mysql + pdo_pgsql + mysqli + pgsql |
| `docker-compose.php85-mysql.yml` | PHP 8.5 + MySQL 8.0 + phpMyAdmin |
| `docker-compose.php85-pgsql.yml` | PHP 8.5 + PostgreSQL 17 + pgAdmin 4 |

### Uso

```bash
# MySQL
docker compose -f docker-compose.php85-mysql.yml up -d

# PostgreSQL
docker compose -f docker-compose.php85-pgsql.yml up -d
```

### .env para PostgreSQL

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=conn2flow
DB_USERNAME=conn2flow_user
DB_PASSWORD=conn2flow_pass
```
