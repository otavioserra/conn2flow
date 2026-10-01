---
name: c2f-database-operations
description: "LEIA ANTES de executar queries SQL, operações CRUD via banco.php ou criar migrações Phinx. Se não ler: consultas quebram por falta de escape, quebram em multi-idioma ou corrompem dados em produção."
user-invocable: false
---

# Operações de Banco de Dados e Migrações (`banco.php` / Phinx)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Criar ou modificar consultas SQL, operações CRUD (`banco_select`, `banco_insert_name`, `banco_update`, `banco_delete`) ou migrações de banco com Phinx.
- **SKIP APENAS SE**: Tarefa puramente de frontend/CSS sem qualquer interação com banco de dados.
- **CONSEQUÊNCIA DE IGNORAR**: Falhas silenciosas de escape (`banco_escape_field`), quebra de consultas em multi-idioma por falta de `language` ou erro de integridade/snapshot em edições.

---

Consulte e aplique as seguintes convenções ao realizar seleções, inserções, edições, deleções e migrações no Conn2Flow:

## 1. Operações CRUD (`gestor/bibliotecas/banco.php`)

* **Seleção Múltipla (`banco_select_name`)**:
  ```php
  $registros = banco_select_name(
      banco_campos_virgulas(['campo1', 'campo2']),
      'nome_da_tabela',
      "WHERE status='A' ORDER BY nome ASC"
  );
  if ($registros) {
      foreach ($registros as $item) {
          $campo1 = $item['campo1'];
      }
  }
  ```

* **Seleção Única (`banco_select`)**:
  ```php
  $linha = banco_select([
      'unico' => true,
      'tabela' => 'nome_da_tabela',
      'campos' => ['campo1', 'campo2'],
      'extra' => "WHERE id='meu-id' AND status='A'"
  ]);
  ```

* **Inserção de Dados (`banco_insert_name`)**:
  ```php
  $campos = null;
  $campos[] = ['campo_nome', 'valor_texto', false]; // Texto (com aspas)
  if (isset($_REQUEST['post_nome'])) {
      $campos[] = ['campo_nome2', banco_escape_field($_REQUEST['post_nome']), false]; // Escape seguro
  }
  $campos[] = ['data_criacao', 'NOW()', true]; // Sem aspas simples (função MySQL/numérico)

  banco_insert_name($campos, 'nome_da_tabela');
  ```

* **Edição de Dados (`banco_update` / `banco_update_campo`)**:
  - Verificação de integridade antes da edição: `banco_select_campos_antes_iniciar(...)`.
  - Edição direta de campo:
    ```php
    banco_update_campo('status', 'I');       // Com aspas simples (texto)
    banco_update_campo('versao', 2, true);   // Sem aspas simples (numérico/função)
    banco_update_executar('tabela', "WHERE id='id-alvo'");
    ```

* **Exclusão de Dados (`banco_delete`)**:
  ```php
  banco_delete('nome_da_tabela', "WHERE id='id-alvo'");
  ```

---

## 2. Migrações Phinx (`Phinx\Migration\AbstractMigration`)

* **Nomenclatura do Arquivo**: `YYYYMMDDHHIISS_create_nome_table.php` (ex: `20260502100001_create_skeleton_table.php`).
* **Estrutura Padrão de Tabela Conn2Flow**:
  ```php
  <?php
  declare(strict_types=1);

  use Phinx\Migration\AbstractMigration;

  final class CreateSkeletonTable extends AbstractMigration
  {
      public function change(): void
      {
          $table = $this->table('skeleton', ['id' => 'id_skeleton']);
          
          $table
              // Vínculo de usuário
              ->addColumn('id_usuarios', 'integer', ['null' => true, 'signed' => false, 'default' => 1])
              
              // Identificação do registro
              ->addColumn('id', 'string', ['limit' => 255, 'null' => false, 'comment' => 'ID textual'])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Nome exibido'])
              
              // Campos padrões de governança
              ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
              ->addColumn('status', 'char', ['limit' => 1, 'null' => false, 'default' => 'A', 'comment' => 'A=Ativo, I=Inativo, E=Excluído'])
              ->addColumn('versao', 'integer', ['null' => false, 'default' => 1])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
              
              // Índices
              ->addIndex(['id', 'language'], ['unique' => true])
              ->addIndex(['language'])
              
              ->create();
      }
  }
  ```

---

## 3. Concorrência e Patch Atômico em JSON (`JSON_MERGE_PATCH`)

### 3.1 Proibição de Reescrita Integral de Campos JSON em Contextos Concorrentes

- Em cenários com escritas concorrentes (ex: webhook de gateway de pagamento vs envio de formulário pelo usuário), **NUNCA** leia-modifique-reescreva colunas JSON inteiras como `fields_values`.
- Isso causa **race conditions** onde um escritor sobrescreve as alterações do outro.
- Exemplo do padrão **ERRADO**:
```php
// ❌ ERRADO — Race condition entre webhook e formulário
$dados = json_decode(banco_select(...), true);
$dados['status_pagamento'] = 'pago';
banco_update_campo('fields_values', json_encode($dados));
```

### 3.2 Uso Mandatório de `JSON_MERGE_PATCH` (MariaDB 11.8+ / MySQL 8+)

- Use `JSON_MERGE_PATCH()` para atualizações parciais atômicas:
```php
// ✅ CORRETO — Patch atômico sem race condition
$patch = json_encode(['status_pagamento' => 'pago']);
banco_update_campo('fields_values', "JSON_MERGE_PATCH(fields_values, '$patch')", true);
banco_update_executar('tabela', "WHERE id='id-alvo'");
```

- O parâmetro `true` (sem aspas) é **CRÍTICO** pois `JSON_MERGE_PATCH()` é uma função SQL.

### 3.3 Armadilha do `null` no `JSON_MERGE_PATCH` (RFC 7396)

- Conforme RFC 7396, um valor `null` no patch **REMOVE a chave** do objeto alvo.
- Isso significa que `{"campo": null}` vai **DELETAR** `campo` do JSON, NÃO definir como null.
- **Validação Mandatória**: Sempre valide com `JSON_VALID()` antes de fazer o merge:
```php
// ✅ Validação defensiva antes do merge
$patch = json_encode($dados_parciais);
if ($patch !== null && $patch !== 'null') {
    $sql = "UPDATE tabela SET fields_values = JSON_MERGE_PATCH(fields_values, '" . banco_escape_field($patch) . "') WHERE id='$id' AND JSON_VALID(fields_values)";
}
```

- **Prevenção de Corrupção por NULL SQL**: Se a coluna em si for SQL `NULL` (não JSON `null`), `JSON_MERGE_PATCH(NULL, ...)` retorna `NULL`. Sempre garanta que a coluna tenha um default JSON válido (`'{}'`):
```sql
ALTER TABLE tabela MODIFY fields_values JSON NOT NULL DEFAULT '{}';
```

---

## 4. Limpeza de Migrações Phinx Renumeradas

### 4.1 Problema: Duplicate Migration Error

A sincronização de arquivos do pipeline (`project:update-all`) opera em **modo aditivo** e **não remove** arquivos ausentes na origem. Quando uma migração Phinx é renumerada (ex: para resolver conflito de timestamp com outro agente), o arquivo com o nome antigo permanece no diretório de destino.

```
❌ Phinx aborta com erro fatal:
"Duplicate migration - 20260915120000_create_orders_table has the same version as 20260915120000"
```

### 4.2 Diretriz Normativa

Ao renumerar uma migração Phinx para resolver conflito de timestamp:

1. **Remover manualmente** o arquivo com o nome antigo do diretório de destino (`gestor/db/migrations/` no ambiente remoto ou espelho) **antes** de rodar o pipeline.
2. Em ambientes remotos, usar SSH para deletar:
```bash
ssh usuario@host "rm /path/to/gestor/db/migrations/YYYYMMDDHHIISS_old_name.php"
```
3. No espelho local:
```bash
rm dev-environment/data/sites/localhost/<site>/gestor/db/migrations/YYYYMMDDHHIISS_old_name.php
```

### 4.3 Regra de Desempate entre Agentes

Em colisões de timestamp entre migrações criadas por agentes concorrentes, a regra de desempate determina que cada agente **renumere a própria migração** para um timestamp mais recente (nunca a do outro agente).

> [!WARNING]
> O erro `Duplicate migration` é **fatal** — o Phinx interrompe toda a execução de migrações, impedindo que migrações subsequentes sejam aplicadas. A limpeza manual é obrigatória.

---

## 5. Sincronização de dados no deploy: o que protege e o que apaga

O deploy não é só `INSERT`/`UPDATE`: o sincronizador aplica regras do contrato `schema-metadata.json`, e errar uma delas altera dados reais de uma instalação.

| Regra | Efeito |
| --- | --- |
| `insert_only: true` | O registro que já existe nunca é atualizado. É o que protege `usuarios`: sem isso, a semente regrava login, e-mail e senha do administrador |
| `preserve_on_user_modified` | Campos listados não são sobrescritos quando `user_modified=1` |
| `strategy` (`pk` ou `natural_key`) | Define o ramo de `sincronizarTabela()`. Toda proteção precisa existir nos dois |
| Retirada por dono | O que o dono deixou de entregar vira `status='D'`; o que volta é reativado pelo manifesto |

### Regras de trabalho

- **Semente não é dado de instalação.** O que a semente cria é o estado inicial. Tudo o que o operador muda depois tem de sobreviver a um deploy.
- **Log de sincronização não pode trazer segredo.** Coluna de senha, token ou chave é mascarada antes de ir para o log.
- **Fotografe antes.** Consulta de leitura das tabelas de identidade e permissão antes e depois de validar uma regra de dados num ambiente; `diff` dos dois resultados.
- **Teste os dois ramos.** Regra nova no sincronizador tem teste com `strategy: pk` e com `strategy: natural_key`.
- **MariaDB e MySQL divergem**: `CAST(... AS JSON)` quebra no MariaDB; `JSON_QUERY` não existe no MySQL. Use `JSON_EXTRACT(x, '$')` e teste nos dois.
