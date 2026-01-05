# Prompt Interactive Programming - Atualização Dados Recursos V 2.0.

## 🎯 Contexto Inicial
- FUNDAMENTAL: Analise o contexto anterior antes de seguir com as orientações abaixo que foi registrado no arquivo : `ai-workspace\prompts\arquitetura\atualização-dados-recursos.md`.

## 📖 Bibliotecas

## 📝 Orientações para o Agente

## 🧭 Estrutura do código-fonte
```
main():
    // Lógica principal do script
    

main()
```

## 🤔 Dúvidas e 📝 Sugestões

---
## 🚀 PLANEJAMENTO COLABORATIVO - VERSÃO 2.0

### Contexto
O processo de atualização dos dados dos recursos será simplificado: não haverá mais controle manual de identificadores numéricos para os recursos `paginas`, `layouts`, `variaveis` e `componentes`. A responsabilidade pelos identificadores será do próprio banco de dados, utilizando auto incremento nas chaves primárias (isso já está implementado nas migrações). Isso elimina a complexidade e os problemas de duplicidade que ocorriam no fluxo anterior.

Arquivos envolvidos:
- Script principal a ser alterado: `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`
- Contexto e regras originais: `ai-workspace/prompts/arquitetura/atualização-dados-recursos.md`
- Planejamento e registro de decisões: `ai-workspace/prompts/arquitetura/atualização-dados-recursos-v2.md` (este arquivo)

### Requisitos

#### Regras de referência e unicidade dos recursos
1. **Referência de layout nas páginas**:
    - O campo `layout_id` do destino (`PaginasData.json`) será preenchido com o valor do campo `layout` da origem (`pages.json`).
    - O campo `id_layouts` não existe mais na tabela `paginas` e não será gerado.

2. **Regras de unicidade dos recursos**:
    - Todos os recursos (`layouts`, `paginas`, `componentes`, `variaveis`):
      - O campo `id` deve ser único dentro da mesma `language`. Pode repetir em `language` diferentes.
    - Recurso `paginas`:
      - Permite `id` igual em mesma `language` se estiver em `modulos` diferentes.
      - O campo `caminho` deve ser único dentro da mesma `language` (pode repetir em `language` diferentes).
    - Recurso `variaveis`:
      - Permite `id` igual em mesma `language` e `modulo` se o campo `grupo` for diferente.

3. **Migração de páginas**:
    - Analisar se a migração `gestor/db/migrations/20250723165530_create_paginas_table.php` está compatível com as regras acima, especialmente quanto à unicidade de `id` e `caminho`.

4. **Regra dos órfãos**:
  - Qualquer registro que não atender aos critérios de unicidade, tipo de dados ou regras específicas será movido para a pasta de órfãos: `gestor/db/orphans`.
  - Para cada tipo de recurso (`paginas`, `layouts`, `componentes`, `variaveis`), será criado um arquivo JSON contendo apenas os registros problemáticos. Use o mesmo padrão de nomes usado para gravar os recursos na pasta: `gestor\db\data`
  - Os dados válidos seguem para os arquivos finais na pasta correta; os órfãos ficam disponíveis para consulta e análise futura.


4. **Checklist de regras**:
  - [x] Referência de layout nas páginas ajustada (campo `layout_id` vindo do campo `layout` da origem)
  - [x] Campo `id_layouts` removido do fluxo
  - [x] Regras de unicidade dos campos `id` e `caminho` implementadas conforme especificação
  - [x] Migração de páginas revisada para garantir compatibilidade (schema já usa `layout_id` string)
  - [x] Fluxo de separação dos órfãos implementado (arquivos em `gestor/db/orphans`)

### Plano de ação
1. Mapear todos os pontos do script e dos fluxos onde há manipulação de identificadores numéricos manuais.
2. Documentar as alterações necessárias para eliminar essa lógica.
3. Garantir que a estrutura dos arquivos Data.json e dos seeders não dependa mais de IDs manuais.
4. Registrar todas as decisões e alternativas consideradas neste arquivo.

### Checklist
- [x] Mapeamento dos pontos de manipulação de IDs manuais realizado (substituídos por chaves naturais)
- [x] Alterações necessárias documentadas
- [x] Estrutura dos arquivos Data.json e seeders revisada (remoção de ids numéricos, manutenção de versao/checksum)
- [x] Decisões e alternativas registradas
- [x] Planejamento aprovado para implementação

### Decisões
- O controle de identificadores numéricos será exclusivamente do banco de dados (auto incremento).
- O fluxo de geração e atualização dos recursos será simplificado, focando apenas nos dados relevantes e em IDs.
- Todo o processo será documentado e validado colaborativamente antes de qualquer alteração no código.

## ✅ Progresso da Implementação
- [x] Refatoração do script `atualizacao-dados-recursos.php` para V2 (removido controle IDs numéricos)
- [x] Implementação das regras de unicidade e segregação de órfãos
- [x] Geração de arquivos atualizados sem erros de unicidade (0 órfãos na execução atual)
- [x] Seeders verificados/criados quando ausentes

## ☑️ Processo Pós-Implementação
- [x] Executar o script gerado para ver se funciona corretamente.
- [x] Gerar mensagem detalhada, substituir string no comando e executar: `./ai-workspace/git/scripts/commit.sh "feat(recursos): refatoração V2 atualização dados recursos (IDs naturais, órfãos, layout_id, unicidades, seeders)"`

### Resumo Técnico da Implementação V2
| Aspecto | Status |
|---------|--------|
| Controle IDs numéricos | Removido dos Data.json (PK autoincrement só no banco) |
| layout_id em páginas | Derivado diretamente de `layout` origem |
| Unicidade páginas (id + modulo) | Implementada; permite mesmo id em módulos distintos |
| Unicidade caminho página (language) | Enforced; duplicados viram órfãos |
| Unicidade variáveis | id+mod+lang permite múltiplos groups distintos; violações viram órfãos |
| Órfãos | Gravados em `gestor/db/orphans/*Data.json` |
| Versão/Checksum | Incremento apenas quando html/css mudam; checksum armazenado como JSON string |
| Seeders | Gerados se inexistentes (não sobrescreve existentes) |

### Próximos Passos Sugeridos
1. Executar commit automatizado com mensagem detalhada.
2. Validar seeders em ambiente de teste (phinx migrate/seed).
3. Ajustar quaisquer consumidores que ainda esperem ids numéricos nos Data.json.

## ♻️ Alterações e Correções 1.0

## ✅ Progresso da Implementação das Alterações e Correções

## ☑️ Processo Pós Alterações e Correções
- [x] Executar o script gerado para ver se funciona corretamente.
- [x] Gerar mensagem detalhada, substituir string e executar commit automatizado.

### Mensagem de Commit Utilizada
```
feat(recursos): refatoração V2 atualização dados recursos (IDs naturais, órfãos, layout_id, unicidades, seeders)

Implementa versão 2 do processo de geração de recursos:
- Remove completamente controle manual de IDs numéricos (layouts, páginas, componentes, variáveis) dos Data.json
- Adota somente chaves naturais para versionamento e reutilização de versao/checksum
- Introduz fluxo de segregação de órfãos (duplicidades, inconsistências) em gestor/db/orphans
- Converte referência de layout em páginas para campo layout_id (string) direto da origem
- Atualiza módulo admin-paginas para persistir layout_id textual (mapeando seleção numérica)
- Ajusta gestor/gestor.php para consumir layout_id
- Mantém incrementos de versao somente em alterações de html/css (checksum consolidado JSON string)
- Gera seeders apenas quando ausentes (sem sobrescrever existentes)
- Garante unicidade:
  * layouts/components: id+language
  * páginas: id+language+modulo e caminho único por language
  * variáveis: id+language+modulo (+grupo permite múltiplos) 
- Execução resultou em 0 órfãos (sanidade validada)

Arquivos chave: atualizacao-dados-recursos.php, PaginasData.json, LayoutsData.json, ComponentesData.json, VariaveisData.json, admin-paginas.php, planejamento v2.

Próximos passos sugeridos (não bloqueadores):
1. Rodar phinx migrate/seed em ambiente de teste
2. Auditar demais consumidores ainda usando id_layouts para leitura
3. Internacionalizar novas mensagens de log
```

---
**Data:** 15/08/2025
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow v1.10.17