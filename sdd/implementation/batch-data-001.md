# BATCH-DATA-001 [DRAFT - PROJETO CONCLUÍDO - AGUARDANDO AUTORIZAÇÃO] Reestruturação e Otimização de Dados e Sincronização

> [!WARNING]
> **STATUS:** Planejamento Arquitetural Concluído. **NÃO AUTORIZADO PARA IMPLEMENTAÇÃO.**
> Este arquivo é um rascunho de projeto e não deve ser executado por agentes ativos de codificação.

## Escopo do Lote
Este lote implementa a reestruturação arquitetural de dados do Conn2Flow, separando a compilação de metadados em desenvolvimento da persistência genérica e otimizada em produção, além de padronizar colunas de controle, unificar logs via biblioteca oficial do sistema, suportar ganchos locais `data-hooks.php` e deleção imperativa de dados.

## Checklist de Implementação

### 1. Banco de Dados e Migrações (Phinx)
- [ ] Mapear todas as migrações antigas que usam `linguagem_codigo`.
- [ ] Alterar as classes de migração sob [migrations/](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/gestor/db/migrations/) para usar `language`.
- [ ] Atualizar o banco de dados local para validar a alteração da coluna.

### 2. Metadados nos Arquivos de Origem (Dev)
- [ ] Adicionar o bloco `"tabela"` com regras de sincronização (`strategy`, `natural_key_columns`, `preserve_on_user_modified`, `insert_only`) nos JSONs de módulo (ex: [admin-paginas.json](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/gestor/modulos/admin-paginas/admin-paginas.json)).
- [ ] Criar o arquivo de metadados das tabelas globais [tables_config.json](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/gestor/resources/tables_config.json).
- [ ] Garantir que o mapeamento global [resources.map.php](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/gestor/resources/resources.map.php) permaneça inalterado e focado apenas no direcionamento dos arquivos.
- [ ] Implementar a chave `"deletar"` nos JSONs locais e globais para indicar registros que precisam ser removidos do banco no deploy.

### 3. Refatoração do Script Gerador (`atualizacao-dados-recursos.php`)
- [ ] Implementar o motor de coleta genérico baseado no *Registry Pattern* para remover os grandes loops específicos de cada recurso.
- [ ] Adicionar lógica para ler e consolidar as regras de `"tabela"` dos módulos e do arquivo global [tables_config.json](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/gestor/resources/tables_config.json).
- [ ] Agregar e consolidar as listas de deleção imperativa.
- [ ] Exportar o arquivo [schema-metadata.json](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/gestor/db/data/schema-metadata.json).
- [ ] Adicionar suporte ao carregamento em pipeline e execução em cadeia de múltiplos arquivos locales e globais nomeados `data-hooks.php`.
- [ ] Padronizar todos os registros de log para usar a função nativa `log_disco()` da biblioteca oficial [log.php](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/gestor/bibliotecas/log.php).
- [ ] Remover tratamento silenciado `@` nas funções de escrita/leitura e substituir por verificações robustas.

### 4. Refatoração do Script Atualizador (`atualizacoes-banco-de-dados.php`)
- [ ] Implementar leitura dinâmica de [schema-metadata.json](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/gestor/db/data/schema-metadata.json).
- [ ] Remover arrays hardcoded (`$preserveMap`, `$tabelasChaveNatural`, `$tabelasInsertOnly`).
- [ ] Implementar query para obter `max_allowed_packet` do MySQL dinamicamente.
- [ ] Codificar o loteador dinâmico de inserts/updates (*Threshold-Based Batching*) que divide as queries em chunks menores quando estas ultrapassam 70% do tamanho máximo do pacote MySQL.
- [ ] Unificar os fluxos de comparação PK e Chave Natural em um motor genérico que lê o contrato.
- [ ] Codificar a remoção física de dados que constarem no bloco de deleção imperativa do contrato de metadados.
- [ ] Garantir suporte de transações PDO completas (`beginTransaction()`, `commit()`, `rollBack()`) na execução da sincronização das tabelas.
- [ ] Padronizar todas as chamadas de log para a biblioteca oficial do sistema [log.php](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/gestor/bibliotecas/log.php).
- [ ] Remover o silenciamento cego `@` nas gravações de backups JSON.

## Validação Esperada
O lote será considerado completo após passar por todos os critérios definidos na checklist de validação de `BATCH-DATA-001`.
