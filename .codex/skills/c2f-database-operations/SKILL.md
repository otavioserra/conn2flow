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
