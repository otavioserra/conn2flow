# 02 [DRAFT - PROJETO CONCLUÍDO - AGUARDANDO AUTORIZAÇÃO] Especificação Normativa: Sincronização de Recursos de Banco de Dados

> [!WARNING]
> **ESTADO DO LOTE:** Planejamento Arquitetural Concluído. **AGUARDANDO AUTORIZAÇÃO PARA IMPLEMENTAÇÃO.**
> Este arquivo representa um brainstorming de projeto futuro e não deve ser consumido por agentes de execução ativa neste momento.

## Objetivo e Contexto

Reestruturar os mecanismos de empacotamento local de recursos (Geração) e de persistência e sincronização de dados (Consumo no Deploy) do Conn2Flow. O objetivo é remover as definições e acoplamentos rígidos (*hardcoded*) em código PHP, unificar as regras de negócio de tabelas em arquivos de metadados JSON dedicados, prover segurança contra sobrecargas de buffer de rede de banco de dados (`max_allowed_packet`), padronizar o logging utilizando a biblioteca oficial do sistema, unificar o uso do termo `language` e possibilitar a expansão dinâmica por meio de ganchos de dados dedicados (`data-hooks.php`).

---

## Detalhamento Técnico e Arquitetural

### 1. Descentralização de Metadados de Tabelas (Contrato Único)

A definição de regras estruturais de tabelas de banco de dados deve residir nas fontes geradoras de dados em formato JSON, mantendo arquivos PHP como o [resources.map.php](../gestor/resources/resources.map.php) limpos de definições internas de colunas e focados apenas em mapeamento de caminhos.

- **Recursos Globais**: Definidos no arquivo JSON físico dedicado [tables_config.json](../gestor/resources/tables_config.json).
- **Módulos**: Definidos dentro do JSON descritor de cada módulo (ex: [admin-paginas.json](../gestor/modulos/admin-paginas/admin-paginas.json)) na chave `"tabela"`.

#### Estrutura do Manifesto de Metadados por Tabela:
```json
{
  "nome": "nome_da_tabela",
  "strategy": "natural_key",            // "natural_key" ou "primary_key"
  "natural_key_columns": ["language", "modulo", "id"], // Colunas da chave natural
  "pk_column": "id_numerico_tabela",     // PK numérica legada se houver
  "insert_only": false,                 // Se true, só insere (não faz updates)
  "preserve_on_user_modified": [        // Colunas protegidas de sobrescrita se user_modified=1
    "nome", "html", "css"
  ]
}
```

O script gerador (`atualizacao-dados-recursos.php`) agrupa todos esses manifestos locais de módulos e o manifesto global e gera o arquivo consolidado [schema-metadata.json](../gestor/db/data/schema-metadata.json). O atualizador online de deploy consumirá exclusivamente esse JSON compilado como ponto único de verdade.

---

### 2. Ganchos de Interoperabilidade Descentralizados (`data-hooks.php`)

Para estender e realizar pré e pós-processamento de recursos de dados sem modificar os scripts principais:
- O motor de sincronização deve procurar por arquivos nomeados `data-hooks.php` nas pastas dos recursos globais ou nos diretórios de módulos locais.
- Se múltiplos arquivos `data-hooks.php` forem identificados no escopo de processamento, eles devem ser carregados e executados em escala sequencial (pipeline) para processar os dados em cadeia.
- **Funções Padronizadas de Hook**:
  - `beforeDataCollect(array $data, string $resourceType): array`: Processa os dados brutos antes de serem compactados nos JSONs compilados.
  - `afterDataCollect(array $data, string $resourceType): array`: Executa validações de integridade adicionais.

---

### 3. Prevenção de Packet Overflow e Chunks Dinâmicos (Threshold de 30%)

Ao realizar inserções ou atualizações em lote (*Bulk Operations*), o script de sincronização de banco de dados deve autoproteger seu fluxo contra limites de tamanho de query:
- Obter o tamanho máximo permitido por pacote no MySQL dinamicamente executando a consulta:
  ```sql
  SHOW VARIABLES LIKE 'max_allowed_packet';
  ```
  *(Se inacessível ou falhar, assume-se o valor padrão seguro de 4.194.304 bytes - 4MB)*.
- Estabelecer uma janela de segurança correspondente a **30% abaixo do limite útil** (ou seja, limite de transmissão seguro de $70\%$ do `max_allowed_packet`).
- Calcular o tamanho do payload total de dados a ser sincronizado por tabela.
- Se o payload ultrapassar a janela segura, o motor deve fracionar a transmissão em sub-lotes (*chunks*) dinâmicos e calculados com base no peso real das strings de dados, executando a importação em lotes menores que respeitem a margem segura.

---

### 4. Padronização da Identidade de Idioma (`language`)

- **Ação:** Identificar todos os locais que utilizam o termo legado `linguagem_codigo` e alterá-los para `language`.
- **Migrações Phinx:** Modificar as migrações sob a pasta [migrations](../gestor/db/migrations) para que a estrutura de banco de dados utilize consistentemente a coluna `language` em todas as tabelas (incluindo `variaveis`, perfis, etc.).
- **Impacto de Código:** Remove a necessidade de adaptadores, mapeadores dinâmicos de linguagem e fallbacks redundantes no atualizador.

---

### 5. Deleção Imperativa de Dados via Metadados

Dada a coexistência de dados de diferentes projetos em um mesmo ecossistema de banco de dados, a deleção automática de órfãos (registros no banco que sumiram do JSON) é desativada por padrão devido ao risco de corrupção cruzada.
- **Estratégia de Limpeza:** A deleção de dados é imperativa e intencional.
- **Implementação:** Os JSONs descritores de recursos (globais ou locais de módulo) poderão declarar chaves explícias contendo IDs/chaves naturais a serem removidas:
  ```json
  "deletar": [
    "caminho-ou-id-a-ser-removido-1",
    "caminho-ou-id-a-ser-removido-2"
  ]
  ```
- O gerador de dados reúne essas solicitações no contrato de metadados, e o script de deploy executa a limpeza controlada:
  ```sql
  DELETE FROM tabela WHERE id IN (lista_solicitada_para_remocao);
  ```

---

### 6. Padronização de Logging (Biblioteca Oficial do Sistema)

Para sanar a dispersão e desorganização dos logs de atualização:
- Toda operação de logging gerada pelo gerador de recursos ou pelo atualizador de banco de dados deve utilizar obrigatoriamente a biblioteca de logs oficial do sistema: [log.php](../gestor/bibliotecas/log.php).
- As chamadas de logging devem ser padronizadas pela função `log_disco()`.
- Evita-se a criação manual de implementações locais de gravação de arquivos de log nos scripts de sincronização, garantindo que o sistema de logs nativo unifique e formate todas as mensagens.
- **Fim do Silenciamento Cego (`@`):** Funções críticas de sistema de arquivos como `file_put_contents`, `mkdir` ou `rename` não usarão o caractere de silenciamento de erros `@` sem um tratamento explícito correspondente. Exceções e erros de permissão de escrita devem ser logados com severidade apropriada para diagnóstico imediato.
