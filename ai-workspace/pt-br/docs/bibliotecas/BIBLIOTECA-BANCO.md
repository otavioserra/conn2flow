# Biblioteca: banco.php

> 🗄️ Operações completas de banco de dados MySQL/MySQLi

## Visão Geral

A biblioteca `banco.php` é a **base fundamental** de todas as operações de dados do sistema Conn2Flow. Fornece uma camada de abstração completa para interação com banco de dados MySQL/MySQLi, incluindo:
- Gerenciamento de conexões
- Operações CRUD (Create, Read, Update, Delete)
- Helpers para construção de queries
- Funções de escape e segurança
- Utilitários para campos e tabelas

**Localização**: `gestor/bibliotecas/banco.php`  
**Versão**: 1.2.0  
**Total de Funções**: 45  
**Tipo de Banco**: MySQLi

## Dependências

- **Extensão PHP**: MySQLi
- **Variáveis Globais**: `$_BANCO`, `$_GESTOR`

## Variáveis Globais

```php
// Configuração da biblioteca
$_GESTOR['biblioteca-banco'] = Array(
    'versao' => '1.2.0',
);

// Configuração de conexão (definida na inicialização)
$_BANCO = Array(
    'tipo' => 'mysqli',
    'host' => 'localhost',
    'usuario' => 'usuario_db',
    'senha' => 'senha_db',
    'nome' => 'nome_db',
    'conexao' => null,  // Objeto de conexão MySQLi
    'RECONECT' => 0,    // Contador de reconexões
);

// Arrays temporários para operações
$_BANCO['update-campos'] = Array();        // Campos para UPDATE
$_BANCO['insert-name-campos'] = Array();   // Campos para INSERT
```

---

## Funções de Conexão

### banco_conectar()

Estabelece conexão com o banco de dados MySQL usando MySQLi.

**Assinatura:**
```php
function banco_conectar()
```

**Parâmetros:**
- Nenhum (usa variável global `$_BANCO`)

**Retorno:**
- (void) - Termina execução com erro se falhar

**Comportamento:**
- Usa credenciais de `$_BANCO`
- Define charset UTF-8
- Ativa relatório de erros MySQLi
- Mata execução em caso de falha

**Exemplo de Uso:**
```php
// Normalmente chamada automaticamente pelas funções
// Mas pode ser chamada manualmente
banco_conectar();
```

**Notas:**
- A conexão é automaticamente estabelecida quando necessário
- Erros são exibidos com debug trace

---

### banco_ping()

Verifica se a conexão com o banco está ativa.

**Assinatura:**
```php
function banco_ping()
```

**Parâmetros:**
- Nenhum

**Retorno:**
- (void) - Incrementa contador de reconexões se falhar

**Exemplo de Uso:**
```php
// Verificar conexão antes de operação crítica
banco_ping();
```

---

### banco_fechar_conexao()

Fecha a conexão com o banco de dados.

**Assinatura:**
```php
function banco_fechar_conexao()
```

**Parâmetros:**
- Nenhum

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
// Fechar conexão ao final do script
banco_fechar_conexao();
```

**Notas:**
- Remove a variável `$_BANCO['conexao']`
- Útil para scripts de longa duração

---

## Funções de Consulta Básica

### banco_query()

Executa uma query SQL no banco de dados.

**Assinatura:**
```php
function banco_query($query)
```

**Parâmetros:**
- `$query` (string) - **Obrigatório** - Consulta SQL a ser executada

**Retorno:**
- (mysqli_result|bool) - Resultado da query ou false em caso de erro

**Exemplo de Uso:**
```php
$result = banco_query("SELECT * FROM usuarios WHERE status='A'");

if ($result) {
    // Processar resultado
}
```

**Notas:**
- Conecta automaticamente se necessário
- Registra erros no error_log
- Retorna false em caso de exceção

---

### banco_sql()

Executa query e retorna todos os resultados como array bidimensional.

**Assinatura:**
```php
function banco_sql($sql)
```

**Parâmetros:**
- `$sql` (string) - **Obrigatório** - Consulta SQL

**Retorno:**
- (array|null) - Array com todos os resultados ou null se vazio

**Exemplo de Uso:**
```php
$usuarios = banco_sql("SELECT id, nome, email FROM usuarios WHERE status='A'");

if ($usuarios) {
    foreach ($usuarios as $usuario) {
        echo $usuario[0]; // ID
        echo $usuario[1]; // Nome
        echo $usuario[2]; // Email
    }
}
```

---

### banco_sql_names()

Executa query e retorna resultados como array associativo com nomes de campos.

**Assinatura:**
```php
function banco_sql_names($sql, $campos)
```

**Parâmetros:**
- `$sql` (string) - **Obrigatório** - Consulta SQL
- `$campos` (string) - **Obrigatório** - Nomes dos campos separados por vírgula ou '*'

**Retorno:**
- (array|null) - Array associativo com resultados ou null

**Exemplo de Uso:**
```php
$sql = "SELECT id, nome, email FROM usuarios WHERE status='A'";
$usuarios = banco_sql_names($sql, 'id,nome,email');

if ($usuarios) {
    foreach ($usuarios as $usuario) {
        echo $usuario['id'];
        echo $usuario['nome'];
        echo $usuario['email'];
    }
}

// Com todos os campos
$todos = banco_sql_names("SELECT * FROM usuarios", '*');
```

**Notas:**
- Aplica `banco_smartstripslashes()` em campos não numéricos
- Mais legível que `banco_sql()`

---

## Funções SELECT

### banco_select()

Função principal para seleção de dados com parâmetros estruturados.

**Assinatura:**
```php
function banco_select($params = false)
```

**Parâmetros (Array Associativo):**
- `campos` (array|string) - **Obrigatório** - Array de campos ou '*'
- `tabela` (string) - **Obrigatório** - Nome da tabela
- `extra` (string) - **Opcional** - Cláusulas WHERE, ORDER BY, LIMIT, etc.
- `unico` (bool) - **Opcional** - Se true, retorna apenas primeiro resultado (array unidimensional)

**Retorno:**
- (array|null) - Array de resultados ou null

**Exemplo de Uso:**
```php
// Seleção simples
$usuarios = banco_select(Array(
    'campos' => Array('id', 'nome', 'email'),
    'tabela' => 'usuarios',
    'extra' => "WHERE status='A' ORDER BY nome LIMIT 10"
));

// Retornar todos os campos
$produtos = banco_select(Array(
    'campos' => '*',
    'tabela' => 'produtos',
    'extra' => "WHERE categoria='eletronicos'"
));

// Retornar único resultado
$usuario = banco_select(Array(
    'campos' => Array('id', 'nome', 'email'),
    'tabela' => 'usuarios',
    'extra' => "WHERE id='123'",
    'unico' => true
));

// Acessar dados
if ($usuario) {
    echo $usuario['nome'];
    echo $usuario['email'];
}

// Múltiplos resultados
if ($usuarios) {
    foreach ($usuarios as $u) {
        echo $u['nome'] . '<br>';
    }
}
```

**Notas:**
- Retorna array associativo com nomes de campos
- Com `unico => true`, retorna diretamente o primeiro registro
- Ideal para a maioria das consultas SELECT

---

### banco_select_name()

Versão simplificada de select com parâmetros diretos.

**Assinatura:**
```php
function banco_select_name($campos, $tabela, $extra)
```

**Parâmetros:**
- `$campos` (string) - **Obrigatório** - Campos separados por vírgula ou '*'
- `$tabela` (string) - **Obrigatório** - Nome da tabela
- `$extra` (string) - **Opcional** - Cláusulas extras

**Retorno:**
- (array|null) - Array associativo de resultados

**Exemplo de Uso:**
```php
$usuarios = banco_select_name(
    'id,nome,email',
    'usuarios',
    "WHERE status='A' ORDER BY nome"
);
```

**Notas:**
- Aplica `banco_smartstripslashes()` automaticamente
- Interface mais simples que `banco_select()`

---

### banco_select_editar()

Seleciona dados para edição, retornando apenas o primeiro registro.

**Assinatura:**
```php
function banco_select_editar($campos, $tabela, $extra)
```

**Parâmetros:**
- `$campos` (string) - **Obrigatório** - Campos separados por vírgula
- `$tabela` (string) - **Obrigatório** - Nome da tabela
- `$extra` (string) - **Opcional** - Cláusulas extras

**Retorno:**
- (array|null) - Array associativo com primeiro resultado

**Exemplo de Uso:**
```php
$usuario = banco_select_editar(
    'id,nome,email,telefone',
    'usuarios',
    "WHERE id='123'"
);

if ($usuario) {
    echo $usuario['nome'];
}

// Verificar se encontrou resultado
global $_GESTOR;
if ($_GESTOR['banco-resultado'] === true) {
    // Registro encontrado
} else {
    // Nenhum registro
}
```

**Notas:**
- Define `$_GESTOR['banco-resultado']` como true ou false
- Útil para formulários de edição

---

### banco_select_campos_antes_iniciar()

Armazena dados de um registro para comparação posterior.

**Assinatura:**
```php
function banco_select_campos_antes_iniciar($campos, $tabela, $extra)
```

**Parâmetros:**
- `$campos` (string) - **Obrigatório** - Campos a armazenar
- `$tabela` (string) - **Obrigatório** - Nome da tabela
- `$extra` (string) - **Opcional** - Cláusulas extras

**Retorno:**
- (bool) - true se encontrou, false caso contrário

**Exemplo de Uso:**
```php
// Armazenar valores anteriores
$existe = banco_select_campos_antes_iniciar(
    'nome,email,status',
    'usuarios',
    "WHERE id='123'"
);

if ($existe) {
    // Usar banco_select_campos_antes() para acessar valores
}
```

**Notas:**
- Armazena em `$_GESTOR['banco-antes']`
- Use com `banco_select_campos_antes()` para recuperar

---

### banco_select_campos_antes()

Recupera valor armazenado por `banco_select_campos_antes_iniciar()`.

**Assinatura:**
```php
function banco_select_campos_antes($campo)
```

**Parâmetros:**
- `$campo` (string) - **Obrigatório** - Nome do campo

**Retorno:**
- (mixed|null) - Valor do campo ou null

**Exemplo de Uso:**
```php
// Armazenar estado anterior
banco_select_campos_antes_iniciar(
    'nome,email,status',
    'usuarios',
    "WHERE id='123'"
);

// Recuperar valores
$nome_anterior = banco_select_campos_antes('nome');
$email_anterior = banco_select_campos_antes('email');

// Comparar com novos valores
if ($nome_novo != $nome_anterior) {
    // Nome foi alterado
}
```

**Caso de Uso:**
- Detectar mudanças em campos
- Criar logs de auditoria
- Validações baseadas em estado anterior

---

## Funções UPDATE

### banco_update()

Executa UPDATE SQL diretamente.

**Assinatura:**
```php
function banco_update($campos, $tabela, $extra)
```

**Parâmetros:**
- `$campos` (string) - **Obrigatório** - String de atribuições (campo='valor')
- `$tabela` (string) - **Obrigatório** - Nome da tabela
- `$extra` (string) - **Opcional** - Cláusula WHERE e outras

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
banco_update(
    "nome='João Silva', email='joao@email.com', status='A'",
    'usuarios',
    "WHERE id='123'"
);
```

**Notas:**
- Para uso direto, sem builders
- Prefira `banco_update_campo()` + `banco_update_executar()`

---

### banco_update_campo()

Adiciona campo ao builder de UPDATE.

**Assinatura:**
```php
function banco_update_campo($nome, $valor, $sem_aspas_simples = false, $escape_field = true)
```

**Parâmetros:**
- `$nome` (string) - **Obrigatório** - Nome do campo
- `$valor` (mixed) - **Obrigatório** - Valor do campo
- `$sem_aspas_simples` (bool) - **Opcional** - Se true, não adiciona aspas
- `$escape_field` (bool) - **Opcional** - Se true, escapa valor (padrão: true)

**Retorno:**
- (void) - Armazena em `$_BANCO['update-campos']`

**Exemplo de Uso:**
```php
// Campos com aspas (strings)
banco_update_campo('nome', 'João Silva');
banco_update_campo('email', 'joao@email.com');

// Campos sem aspas (números, NULL)
banco_update_campo('idade', 25, true);
banco_update_campo('ativo', 1, true);

// Executar UPDATE
banco_update_executar('usuarios', "WHERE id='123'");
```

---

### banco_update_executar()

Executa UPDATE com campos acumulados por `banco_update_campo()`.

**Assinatura:**
```php
function banco_update_executar($tabela, $extra = '')
```

**Parâmetros:**
- `$tabela` (string) - **Obrigatório** - Nome da tabela
- `$extra` (string) - **Opcional** - Cláusula WHERE

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
// Acumular campos
banco_update_campo('nome', $_POST['nome']);
banco_update_campo('email', $_POST['email']);
banco_update_campo('telefone', $_POST['telefone']);
banco_update_campo('atualizado_em', 'NOW()', true, false);

// Executar
banco_update_executar('usuarios', "WHERE id='" . $_POST['id'] . "'");

// Os campos são automaticamente limpos após execução
```

**Notas:**
- Limpa `$_BANCO['update-campos']` após execução
- Ideal para formulários dinâmicos

---

### banco_update_varios()

Atualiza múltiplos registros com diferentes valores em uma única query.

**Assinatura:**
```php
function banco_update_varios($campos, $tabela, $campo_nome, $id_nome)
```

**Parâmetros:**
- `$campos` (array) - **Obrigatório** - Array de arrays [id, valor]
- `$tabela` (string) - **Obrigatório** - Nome da tabela
- `$campo_nome` (string) - **Obrigatório** - Campo a ser atualizado
- `$id_nome` (string) - **Obrigatório** - Nome do campo ID

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
// Atualizar ordem de vários itens
$campos = Array(
    Array('1', '10'),  // ID 1 -> ordem 10
    Array('2', '20'),  // ID 2 -> ordem 20
    Array('3', '30'),  // ID 3 -> ordem 30
);

banco_update_varios(
    $campos,
    'produtos',
    'ordem',     // campo a atualizar
    'id'         // campo identificador
);

// Gera SQL otimizado:
// UPDATE `produtos` SET `ordem` = CASE `id`
// WHEN '1' THEN '10'
// WHEN '2' THEN '20'
// WHEN '3' THEN '30'
// ELSE `ordem`
// END
```

**Notas:**
- Muito mais eficiente que múltiplos UPDATEs
- Divide automaticamente em lotes se SQL > 1MB
- Usa CASE WHEN para performance

---

## Funções INSERT

### banco_insert()

Insere registro com ID auto-incrementado (legado).

**Assinatura:**
```php
function banco_insert($campos, $tabela)
```

**Parâmetros:**
- `$campos` (string) - **Obrigatório** - Valores separados por vírgula
- `$tabela` (string) - **Obrigatório** - Nome da tabela

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
// Insere VALUES('0', ...)
banco_insert("'João','joao@email.com','A'", 'usuarios');
```

**Notas:**
- Adiciona '0' para ID auto-incremento
- Prefira `banco_insert_name()` para código moderno

---

### banco_insert_name()

Insere registro especificando nomes de campos.

**Assinatura:**
```php
function banco_insert_name($dados, $tabela)
```

**Parâmetros:**
- `$dados` (array) - **Obrigatório** - Array de arrays [nome, valor, sem_aspas]
- `$tabela` (string) - **Obrigatório** - Nome da tabela

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
$dados = Array(
    Array('nome', 'João Silva', false),
    Array('email', 'joao@email.com', false),
    Array('idade', 25, true),  // sem aspas
    Array('status', 'A', false)
);

banco_insert_name($dados, 'usuarios');

// Gera: INSERT INTO usuarios (nome,email,idade,status) 
//       VALUES ('João Silva','joao@email.com',25,'A')
```

---

### banco_insert_name_campo()

Adiciona campo ao builder de INSERT.

**Assinatura:**
```php
function banco_insert_name_campo($nome, $valor, $sem_aspas_simples = false, $escape_field = true)
```

**Parâmetros:**
- `$nome` (string) - **Obrigatório** - Nome do campo
- `$valor` (mixed) - **Obrigatório** - Valor do campo
- `$sem_aspas_simples` (bool) - **Opcional** - Sem aspas (números, NULL)
- `$escape_field` (bool) - **Opcional** - Escapar valor

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
banco_insert_name_campo('nome', 'João Silva');
banco_insert_name_campo('email', 'joao@email.com');
banco_insert_name_campo('idade', 25, true);
banco_insert_name_campo('criado_em', 'NOW()', true, false);

// Recuperar campos acumulados
$campos = banco_insert_name_campos();

// Inserir
banco_insert_name($campos, 'usuarios');
```

---

### banco_insert_name_campos()

Retorna e limpa campos acumulados para INSERT.

**Assinatura:**
```php
function banco_insert_name_campos()
```

**Parâmetros:**
- Nenhum

**Retorno:**
- (array) - Array de campos ou array vazio

**Exemplo de Uso:**
```php
// Acumular campos
banco_insert_name_campo('nome', 'João');
banco_insert_name_campo('email', 'joao@email.com');

// Recuperar
$campos = banco_insert_name_campos();

// Inserir
banco_insert_name($campos, 'usuarios');
```

---

### banco_insert_name_varios()

Insere múltiplos registros de uma vez.

**Assinatura:**
```php
function banco_insert_name_varios($params = false)
```

**Parâmetros (Array Associativo):**
- `tabela` (string) - **Obrigatório** - Nome da tabela
- `campos` (array) - **Obrigatório** - Array de configuração de campos
  - `nome` (string) - Nome do campo
  - `valores` (array) - Array de valores
  - `sem_aspas_simples` (bool) - Opcional

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
banco_insert_name_varios(Array(
    'tabela' => 'produtos',
    'campos' => Array(
        Array(
            'nome' => 'nome',
            'valores' => Array('Produto 1', 'Produto 2', 'Produto 3')
        ),
        Array(
            'nome' => 'preco',
            'valores' => Array('10.50', '20.00', '15.75'),
            'sem_aspas_simples' => false
        ),
        Array(
            'nome' => 'estoque',
            'valores' => Array(100, 200, 150),
            'sem_aspas_simples' => true
        )
    )
));

// Gera: INSERT INTO produtos (nome,preco,estoque) VALUES
// ('Produto 1','10.50',100),
// ('Produto 2','20.00',200),
// ('Produto 3','15.75',150)
```

**Notas:**
- Muito eficiente para imports e migrations
- Todos os arrays de valores devem ter mesmo tamanho

---

### banco_insert_varios()

Insere múltiplos registros com ID auto-incremento (legado).

**Assinatura:**
```php
function banco_insert_varios($campos, $tabela)
```

**Parâmetros:**
- `$campos` (array) - Array de strings de valores
- `$tabela` (string) - Nome da tabela

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
$campos = Array(
    "'João','joao@email.com'",
    "'Maria','maria@email.com'",
    "'Pedro','pedro@email.com'"
);

banco_insert_varios($campos, 'usuarios');
```

---

### banco_last_id()

Retorna o último ID inserido.

**Assinatura:**
```php
function banco_last_id()
```

**Parâmetros:**
- Nenhum

**Retorno:**
- (int) - ID do último registro inserido

**Exemplo de Uso:**
```php
banco_insert_name($dados, 'usuarios');
$novo_id = banco_last_id();

echo "Usuário criado com ID: " . $novo_id;
```

---

## Funções DELETE

### banco_delete()

Executa DELETE SQL.

**Assinatura:**
```php
function banco_delete($tabela, $extra)
```

**Parâmetros:**
- `$tabela` (string) - **Obrigatório** - Nome da tabela
- `$extra` (string) - **Obrigatório** - Cláusula WHERE e outras

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
// Deletar usuário específico
banco_delete('usuarios', "WHERE id='123'");

// Deletar múltiplos com condição
banco_delete('logs', "WHERE data < '2024-01-01'");

// CUIDADO: sem WHERE deleta tudo!
banco_delete('temp_data', '');
```

**⚠️ Atenção:**
- Sempre use WHERE para evitar deletar toda a tabela
- Para soft delete, prefira UPDATE com status='D'

---

### banco_delete_varios()

Deleta múltiplos registros usando IN.

**Assinatura:**
```php
function banco_delete_varios($tabela, $campo_ids, $array_ids)
```

**Parâmetros:**
- `$tabela` (string) - **Obrigatório** - Nome da tabela
- `$campo_ids` (string|array) - **Obrigatório** - Nome(s) do(s) campo(s) ID
- `$array_ids` (array) - **Obrigatório** - IDs a deletar

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
// Deletar por um campo
$ids = Array('1', '2', '3', '5', '8');
banco_delete_varios('usuarios', 'id', $ids);
// DELETE FROM usuarios WHERE id IN ('1','2','3','5','8')

// Deletar por múltiplos campos
$campos = Array('campo1', 'campo2');
$valores = Array(
    'campo1' => Array('1', '2'),
    'campo2' => Array('A', 'B')
);
banco_delete_varios('tabela', $campos, $valores);
// DELETE FROM tabela WHERE campo1 IN ('1','2') AND campo2 IN ('A','B')
```

---

## Funções de Dados de Resultado

### banco_num_rows()

Retorna número de linhas em um resultado.

**Assinatura:**
```php
function banco_num_rows($result)
```

**Parâmetros:**
- `$result` (mysqli_result) - **Obrigatório** - Resultado de query

**Retorno:**
- (int) - Número de linhas

**Exemplo de Uso:**
```php
$result = banco_query("SELECT * FROM usuarios");
$total = banco_num_rows($result);

echo "Encontrados: " . $total . " usuários";
```

---

### banco_num_fields()

Retorna número de campos em um resultado.

**Assinatura:**
```php
function banco_num_fields($result)
```

**Parâmetros:**
- `$result` (mysqli_result) - **Obrigatório** - Resultado de query

**Retorno:**
- (int) - Número de campos

**Exemplo de Uso:**
```php
$result = banco_query("SELECT id, nome, email FROM usuarios LIMIT 1");
$num_campos = banco_num_fields($result);

echo "Campos selecionados: " . $num_campos; // 3
```

---

### banco_field_name()

Retorna nome de um campo específico do resultado.

**Assinatura:**
```php
function banco_field_name($result, $num_field)
```

**Parâmetros:**
- `$result` (mysqli_result) - **Obrigatório** - Resultado de query
- `$num_field` (int) - **Obrigatório** - Índice do campo (começa em 0)

**Retorno:**
- (string) - Nome do campo

**Exemplo de Uso:**
```php
$result = banco_query("SELECT id, nome, email FROM usuarios LIMIT 1");

echo banco_field_name($result, 0); // 'id'
echo banco_field_name($result, 1); // 'nome'
echo banco_field_name($result, 2); // 'email'
```

---

### banco_fields_names()

Retorna array com todos os nomes de campos de uma tabela.

**Assinatura:**
```php
function banco_fields_names($table)
```

**Parâmetros:**
- `$table` (string) - **Obrigatório** - Nome da tabela

**Retorno:**
- (array|null) - Array de nomes de campos ou null

**Exemplo de Uso:**
```php
$campos = banco_fields_names('usuarios');

if ($campos) {
    foreach ($campos as $campo) {
        echo $campo . '<br>';
    }
}
// Saída:
// id
// nome
// email
// status
// criado_em
```

---

### banco_row()

Retorna próxima linha como array numérico.

**Assinatura:**
```php
function banco_row($result)
```

**Parâmetros:**
- `$result` (mysqli_result) - **Obrigatório** - Resultado de query

**Retorno:**
- (array|null) - Array numérico ou null

**Exemplo de Uso:**
```php
$result = banco_query("SELECT id, nome FROM usuarios");

while ($row = banco_row($result)) {
    echo $row[0] . ' - ' . $row[1] . '<br>';
}
```

---

### banco_row_array()

Retorna próxima linha como array (numérico e associativo).

**Assinatura:**
```php
function banco_row_array($result)
```

**Parâmetros:**
- `$result` (mysqli_result) - **Obrigatório** - Resultado de query

**Retorno:**
- (array|null) - Array misto ou null

**Exemplo de Uso:**
```php
$result = banco_query("SELECT id, nome FROM usuarios LIMIT 1");
$row = banco_row_array($result);

echo $row[0];        // Por índice
echo $row['nome'];   // Por nome
```

---

### banco_fetch_assoc()

Retorna próxima linha como array associativo.

**Assinatura:**
```php
function banco_fetch_assoc($result)
```

**Parâmetros:**
- `$result` (mysqli_result) - **Obrigatório** - Resultado de query

**Retorno:**
- (array|null) - Array associativo ou null

**Exemplo de Uso:**
```php
$result = banco_query("SELECT * FROM usuarios");

while ($usuario = banco_fetch_assoc($result)) {
    echo $usuario['id'];
    echo $usuario['nome'];
    echo $usuario['email'];
}
```

---

## Funções Utilitárias

### banco_escape_field()

Escapa string para prevenir SQL injection.

**Assinatura:**
```php
function banco_escape_field($field)
```

**Parâmetros:**
- `$field` (string) - **Obrigatório** - String a ser escapada

**Retorno:**
- (string) - String escapada

**Exemplo de Uso:**
```php
$nome = banco_escape_field($_POST['nome']);
$email = banco_escape_field($_POST['email']);

$sql = "INSERT INTO usuarios (nome, email) VALUES ('$nome', '$email')";
banco_query($sql);
```

**⚠️ Importante:**
- Sempre escape valores de entrada do usuário
- Não necessário ao usar `banco_update_campo()` e similares (fazem automaticamente)

---

### banco_smartstripslashes()

Remove slashes de string de forma inteligente.

**Assinatura:**
```php
function banco_smartstripslashes($str)
```

**Parâmetros:**
- `$str` (string) - **Obrigatório** - String a processar

**Retorno:**
- (string) - String processada

**Exemplo de Uso:**
```php
$texto = banco_smartstripslashes($texto_do_banco);
```

**Notas:**
- Usado internamente pelas funções SELECT
- Converte para string

---

### banco_campos_virgulas()

Converte array de campos em string separada por vírgulas.

**Assinatura:**
```php
function banco_campos_virgulas($campos)
```

**Parâmetros:**
- `$campos` (array) - **Obrigatório** - Array de campos

**Retorno:**
- (string) - Campos separados por vírgula

**Exemplo de Uso:**
```php
$campos = Array('id', 'nome', 'email', 'status');
$campos_str = banco_campos_virgulas($campos);

echo $campos_str; // 'id,nome,email,status'

// Uso em SELECT
$sql = "SELECT " . $campos_str . " FROM usuarios";
```

---

### banco_total_rows()

Conta total de registros em uma tabela.

**Assinatura:**
```php
function banco_total_rows($tabela, $extra = null)
```

**Parâmetros:**
- `$tabela` (string) - **Obrigatório** - Nome da tabela
- `$extra` (string) - **Opcional** - Cláusula WHERE

**Retorno:**
- (int) - Total de registros

**Exemplo de Uso:**
```php
// Total geral
$total = banco_total_rows('usuarios');
echo "Total de usuários: " . $total;

// Com condição
$ativos = banco_total_rows('usuarios', "WHERE status='A'");
echo "Usuários ativos: " . $ativos;
```

---

### banco_campos_nomes()

Retorna informações completas sobre campos de uma tabela.

**Assinatura:**
```php
function banco_campos_nomes($tabela)
```

**Parâmetros:**
- `$tabela` (string) - **Obrigatório** - Nome da tabela

**Retorno:**
- (array) - Array de arrays associativos com informações dos campos

**Exemplo de Uso:**
```php
$campos = banco_campos_nomes('usuarios');

foreach ($campos as $campo) {
    echo "Campo: " . $campo['Field'] . '<br>';
    echo "Tipo: " . $campo['Type'] . '<br>';
    echo "Null: " . $campo['Null'] . '<br>';
    echo "Key: " . $campo['Key'] . '<br>';
    echo "Default: " . $campo['Default'] . '<br>';
    echo "Extra: " . $campo['Extra'] . '<br>';
    echo '<br>';
}
```

**Campos Retornados:**
- `Field` - Nome do campo
- `Type` - Tipo do campo (varchar, int, etc.)
- `Null` - YES/NO
- `Key` - PRI (Primary), MUL (Multiple), etc.
- `Default` - Valor padrão
- `Extra` - auto_increment, etc.

---

### banco_tabelas_lista()

Lista todas as tabelas do banco de dados.

**Assinatura:**
```php
function banco_tabelas_lista()
```

**Parâmetros:**
- Nenhum

**Retorno:**
- (array) - Array com nomes das tabelas

**Exemplo de Uso:**
```php
$tabelas = banco_tabelas_lista();

foreach ($tabelas as $tabela) {
    echo $tabela . '<br>';
}
```

---

### banco_retirar_acentos()

Remove acentos e caracteres especiais de string.

**Assinatura:**
```php
function banco_retirar_acentos($var, $retirar_espaco = true)
```

**Parâmetros:**
- `$var` (string) - **Obrigatório** - String a processar
- `$retirar_espaco` (bool) - **Opcional** - Se true, substitui espaços por hífen

**Retorno:**
- (string) - String normalizada

**Exemplo de Uso:**
```php
$titulo = "Programação em São Paulo 2025!";
$slug = banco_retirar_acentos($titulo);
// Retorna: "programacao-em-sao-paulo-2025"

// Manter espaços
$nome = banco_retirar_acentos("José da Silva", false);
// Retorna: "jose da silva"
```

**Transformações:**
- Converte para minúsculas
- Remove acentos (á -> a, ç -> c, etc.)
- Remove caracteres especiais
- Substitui espaços por hífen (opcional)
- Remove hífens duplicados

---

## Funções Avançadas

### banco_identificador()

Gera identificador único para um registro (slug).

**Assinatura:**
```php
function banco_identificador($params = false)
```

**Parâmetros (Array Associativo):**
- `id` (string) - **Obrigatório** - String base para o identificador
- `tabela` (array) - **Obrigatório** - Configuração da tabela
  - `nome` (string) - Nome da tabela
  - `campo` (string) - Nome do campo identificador
  - `id_nome` (string) - Nome do campo ID
  - `id_valor` (string) - Opcional - ID a excluir (para edição)
  - `status` (string) - Opcional - Nome do campo status
  - `sem_status` (bool) - Opcional - Ignorar verificação de status
  - `where` (string) - Opcional - Cláusula WHERE adicional
- `sem_traco` (bool) - **Opcional** - Remover hífens do resultado

**Retorno:**
- (string) - Identificador único

**Exemplo de Uso:**
```php
// Criar slug para produto
$identificador = banco_identificador(Array(
    'id' => 'Notebook Dell Inspiron 15',
    'tabela' => Array(
        'nome' => 'produtos',
        'campo' => 'slug',
        'id_nome' => 'id'
    )
));
// Retorna: "notebook-dell-inspiron-15"
// Se já existe, retorna: "notebook-dell-inspiron-15-1"

// Para edição (não verificar o próprio registro)
$identificador = banco_identificador(Array(
    'id' => 'Notebook Dell Inspiron 15',
    'tabela' => Array(
        'nome' => 'produtos',
        'campo' => 'slug',
        'id_nome' => 'id',
        'id_valor' => '123'  // ID do registro sendo editado
    )
));

// Sem hífens
$identificador = banco_identificador(Array(
    'id' => 'Produto ABC',
    'tabela' => Array(
        'nome' => 'produtos',
        'campo' => 'codigo',
        'id_nome' => 'id'
    ),
    'sem_traco' => true
));
// Retorna: "produtoabc" ou "produtoabc1"
```

**Notas:**
- Remove acentos automaticamente
- Adiciona sufixo numérico se já existir (-1, -2, etc.)
- Limita tamanho a 90 caracteres
- Útil para URLs amigáveis e códigos únicos

---

### banco_identificador_unico()

Função auxiliar recursiva para `banco_identificador()`.

**Assinatura:**
```php
function banco_identificador_unico($params = false)
```

**Notas:**
- Uso interno
- Verifica disponibilidade do identificador
- Incrementa sufixo até encontrar um único

---

### banco_insert_update()

Insere ou atualiza registro automaticamente.

**Assinatura:**
```php
function banco_insert_update($params = false)
```

**Parâmetros (Array Associativo):**
- `dados` (array) - **Obrigatório** - Array associativo [campo => valor]
- `tabela` (array) - **Obrigatório** - Configuração da tabela
  - `nome` (string) - Nome da tabela
  - `id` (string) - Nome do campo ID
  - `extra` (string) - Opcional - WHERE adicional
- `dadosTipo` (array) - **Opcional** - Tipos de dados [campo => tipo]
  - Tipos: 'bool', 'int', (padrão: string)

**Retorno:**
- (void)

**Exemplo de Uso:**
```php
// Dados do formulário
$dados = Array(
    'id' => $_POST['id'],
    'nome' => $_POST['nome'],
    'email' => $_POST['email'],
    'idade' => $_POST['idade'],
    'ativo' => isset($_POST['ativo']) ? 1 : 0
);

// Tipos especiais
$tipos = Array(
    'idade' => 'int',
    'ativo' => 'bool'
);

// Se ID existe, UPDATE, senão INSERT
banco_insert_update(Array(
    'dados' => $dados,
    'tabela' => Array(
        'nome' => 'usuarios',
        'id' => 'id'
    ),
    'dadosTipo' => $tipos
));

// Com WHERE adicional
banco_insert_update(Array(
    'dados' => $dados,
    'tabela' => Array(
        'nome' => 'usuarios',
        'id' => 'id',
        'extra' => "AND empresa='XYZ'"
    ),
    'dadosTipo' => $tipos
));
```

**Comportamento:**
- Verifica se registro com ID existe
- Se existe: UPDATE (remove ID dos dados)
- Se não existe: INSERT
- Escapa automaticamente valores string
- Trata NULL para bool e int vazios

**Caso de Uso:**
- Formulários de criação/edição unificados
- Sincronização de dados
- Imports com upsert

---

### banco_erro_debug()

Gera trace de debug para erros de banco.

**Assinatura:**
```php
function banco_erro_debug()
```

**Parâmetros:**
- Nenhum

**Retorno:**
- (string) - HTML com backtrace

**Exemplo de Uso:**
```php
// Usado internamente em mensagens de erro
// Não é necessário chamar diretamente
```

**Notas:**
- Mostra arquivo, linha e função do erro
- Útil para debugging
- Já usado automaticamente em erros de conexão

---

## Casos de Uso Comuns

### 1. CRUD Completo de Usuário

```php
// CREATE
banco_insert_name_campo('nome', 'João Silva');
banco_insert_name_campo('email', 'joao@email.com');
banco_insert_name_campo('senha', md5('senha123'));
banco_insert_name_campo('status', 'A');
$campos = banco_insert_name_campos();
banco_insert_name($campos, 'usuarios');
$novo_id = banco_last_id();

// READ
$usuario = banco_select(Array(
    'campos' => Array('id', 'nome', 'email', 'status'),
    'tabela' => 'usuarios',
    'extra' => "WHERE id='$novo_id'",
    'unico' => true
));

// UPDATE
banco_update_campo('nome', 'João Pedro Silva');
banco_update_campo('email', 'joaopedro@email.com');
banco_update_executar('usuarios', "WHERE id='$novo_id'");

// DELETE
banco_delete('usuarios', "WHERE id='$novo_id'");
```

### 2. Listagem com Paginação

```php
$por_pagina = 20;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $por_pagina;

// Total de registros
$total = banco_total_rows('produtos', "WHERE status='A'");

// Produtos da página
$produtos = banco_select(Array(
    'campos' => Array('id', 'nome', 'preco', 'estoque'),
    'tabela' => 'produtos',
    'extra' => "WHERE status='A' ORDER BY nome LIMIT $offset, $por_pagina"
));

// Total de páginas
$total_paginas = ceil($total / $por_pagina);
```

### 3. Formulário de Edição com Detecção de Mudanças

```php
$id = $_POST['id'];

// Armazenar valores anteriores
banco_select_campos_antes_iniciar(
    'nome,email,status',
    'usuarios',
    "WHERE id='$id'"
);

// Processar formulário
$nome_novo = $_POST['nome'];
$nome_anterior = banco_select_campos_antes('nome');

if ($nome_novo != $nome_anterior) {
    // Criar log de mudança
    banco_insert_name(Array(
        Array('tabela', 'usuarios'),
        Array('registro_id', $id),
        Array('campo', 'nome'),
        Array('valor_anterior', $nome_anterior),
        Array('valor_novo', $nome_novo),
        Array('usuario_id', $_SESSION['usuario_id'])
    ), 'logs_auditoria');
}

// Atualizar
banco_update_campo('nome', $nome_novo);
banco_update_campo('email', $_POST['email']);
banco_update_executar('usuarios', "WHERE id='$id'");
```

### 4. Import em Massa

```php
// Preparar dados
$nomes = Array();
$emails = Array();
$idades = Array();

foreach ($csv_data as $row) {
    $nomes[] = $row['nome'];
    $emails[] = $row['email'];
    $idades[] = $row['idade'];
}

// Inserir tudo de uma vez
banco_insert_name_varios(Array(
    'tabela' => 'usuarios',
    'campos' => Array(
        Array('nome' => 'nome', 'valores' => $nomes),
        Array('nome' => 'email', 'valores' => $emails),
        Array('nome' => 'idade', 'valores' => $idades, 'sem_aspas_simples' => true),
        Array('nome' => 'status', 'valores' => array_fill(0, count($nomes), 'A'))
    )
));
```

### 5. Atualização em Lote de Ordenação

```php
// Receber nova ordem via AJAX
$nova_ordem = $_POST['ordem']; // Array de [id => posicao]

$campos = Array();
foreach ($nova_ordem as $id => $posicao) {
    $campos[] = Array($id, $posicao);
}

// Atualizar tudo de uma vez
banco_update_varios(
    $campos,
    'menu_itens',
    'ordem',  // campo a atualizar
    'id'      // campo identificador
);
```

### 6. Geração de Slug Único

```php
$titulo = $_POST['titulo'];

$slug = banco_identificador(Array(
    'id' => $titulo,
    'tabela' => Array(
        'nome' => 'artigos',
        'campo' => 'slug',
        'id_nome' => 'id',
        'id_valor' => isset($_POST['id']) ? $_POST['id'] : null
    )
));

// Inserir ou atualizar com slug único
$dados = Array(
    'id' => isset($_POST['id']) ? $_POST['id'] : uniqid(),
    'titulo' => $titulo,
    'slug' => $slug,
    'conteudo' => $_POST['conteudo']
);

banco_insert_update(Array(
    'dados' => $dados,
    'tabela' => Array('nome' => 'artigos', 'id' => 'id')
));
```

### 7. Busca com Múltiplos Filtros

```php
$where = Array();
$where[] = "status='A'";

if (!empty($_GET['categoria'])) {
    $categoria = banco_escape_field($_GET['categoria']);
    $where[] = "categoria='$categoria'";
}

if (!empty($_GET['busca'])) {
    $busca = banco_escape_field($_GET['busca']);
    $where[] = "(nome LIKE '%$busca%' OR descricao LIKE '%$busca%')";
}

if (!empty($_GET['preco_min'])) {
    $preco_min = (float)$_GET['preco_min'];
    $where[] = "preco >= $preco_min";
}

$where_sql = implode(' AND ', $where);

$produtos = banco_select(Array(
    'campos' => '*',
    'tabela' => 'produtos',
    'extra' => "WHERE $where_sql ORDER BY nome"
));
```

---

## Padrões e Melhores Práticas

### Segurança

1. **Sempre escape entrada do usuário:**
```php
// ❌ ERRADO - SQL Injection
$nome = $_POST['nome'];
$sql = "SELECT * FROM usuarios WHERE nome='$nome'";

// ✅ CORRETO
$nome = banco_escape_field($_POST['nome']);
$sql = "SELECT * FROM usuarios WHERE nome='$nome'";

// ✅ MELHOR - Usar funções de alto nível
$usuarios = banco_select(Array(
    'campos' => '*',
    'tabela' => 'usuarios',
    'extra' => "WHERE nome='" . banco_escape_field($_POST['nome']) . "'"
));
```

2. **Use prepared statements via builders:**
```php
// ✅ Campos são escapados automaticamente
banco_update_campo('nome', $_POST['nome']);
banco_update_campo('email', $_POST['email']);
banco_update_executar('usuarios', "WHERE id='" . banco_escape_field($_POST['id']) . "'");
```

### Performance

1. **Use INSERT múltiplos em vez de loops:**
```php
// ❌ LENTO - Múltiplas queries
foreach ($dados as $dado) {
    banco_insert_name(Array(
        Array('nome', $dado['nome']),
        Array('valor', $dado['valor'])
    ), 'tabela');
}

// ✅ RÁPIDO - Uma query
banco_insert_name_varios(Array(
    'tabela' => 'tabela',
    'campos' => Array(
        Array('nome' => 'nome', 'valores' => array_column($dados, 'nome')),
        Array('nome' => 'valor', 'valores' => array_column($dados, 'valor'))
    )
));
```

2. **Use UPDATE em lote:**
```php
// ❌ LENTO
foreach ($updates as $id => $valor) {
    banco_update("campo='$valor'", 'tabela', "WHERE id='$id'");
}

// ✅ RÁPIDO
$campos = Array();
foreach ($updates as $id => $valor) {
    $campos[] = Array($id, $valor);
}
banco_update_varios($campos, 'tabela', 'campo', 'id');
```

3. **Selecione apenas campos necessários:**
```php
// ❌ Busca dados desnecessários
$usuarios = banco_select(Array('campos' => '*', 'tabela' => 'usuarios'));

// ✅ Busca apenas o necessário
$usuarios = banco_select(Array(
    'campos' => Array('id', 'nome'),
    'tabela' => 'usuarios'
));
```

### Organização

1. **Use funções de alto nível quando possível:**
```php
// ✅ Preferir banco_select()
$dados = banco_select(Array(
    'campos' => Array('id', 'nome'),
    'tabela' => 'usuarios',
    'extra' => "WHERE status='A'"
));

// Em vez de banco_query() + processamento manual
```

2. **Agrupe operações relacionadas:**
```php
// ✅ BOM - Operações agrupadas
banco_update_campo('nome', $nome);
banco_update_campo('email', $email);
banco_update_campo('telefone', $telefone);
banco_update_campo('atualizado_em', 'NOW()', true, false);
banco_update_executar('usuarios', "WHERE id='$id'");
```

---

## Limitações e Considerações

### Compatibilidade

- **Apenas MySQLi**: Não suporta outros bancos (PostgreSQL, SQLite)
- **PHP 5.4+**: Algumas funções podem requerer versões mais recentes

### Transações

- Não há suporte nativo para transações
- Para transações, use `banco_query()` diretamente:

```php
banco_query("START TRANSACTION");
try {
    banco_insert_name($dados1, 'tabela1');
    banco_insert_name($dados2, 'tabela2');
    banco_query("COMMIT");
} catch (Exception $e) {
    banco_query("ROLLBACK");
}
```

### Prepared Statements

- A biblioteca não usa prepared statements nativos do MySQLi
- Usa escape manual via `mysqli_real_escape_string()`
- Seguro quando usado corretamente, mas menos elegante que PDO

### Conexão Persistente

- Não usa conexões persistentes
- Cada script cria nova conexão
- Para alta performance, considere connection pooling externo

---

## Veja Também

- [BIBLIOTECA-GESTOR.md](./BIBLIOTECA-GESTOR.md) - Funções principais do CMS
- [BIBLIOTECA-AUTENTICACAO.md](./BIBLIOTECA-AUTENTICACAO.md) - Autenticação e segurança
- [BIBLIOTECA-FORMATO.md](./BIBLIOTECA-FORMATO.md) - Formatação de dados
- [Sistema de Conhecimento](../CONN2FLOW-SISTEMA-CONHECIMENTO.md) - Documentação geral

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
