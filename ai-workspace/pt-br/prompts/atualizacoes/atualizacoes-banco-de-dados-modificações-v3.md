# Prompt Interactive Programming - Atualizações Banco de Dados - Modificações V 3.0


## 🎯 Contexto Inicial
- Este planejamento V3 tem como objetivo adaptar o fluxo de atualização do banco de dados para refletir as mudanças profundas realizadas na estrutura dos recursos (layouts, páginas, componentes, variáveis) conforme a versão 2 do processo de atualização dos dados dos recursos.
- Referências principais:
    - Planejamento e registro das mudanças dos recursos: `ai-workspace/prompts/arquitetura/atualização-dados-recursos-v2.md`
    - Script de atualização do banco: `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`

### Diagnóstico das Mudanças nos Recursos (V2)
1. **IDs numéricos removidos**: Os arquivos Data.json dos recursos não possuem mais campos de identificadores numéricos (ex: id_layouts, id_paginas, etc). O controle de PK é feito exclusivamente pelo banco (auto incremento).
2. **Referência de layout nas páginas**: O campo `layout_id` agora é textual, vindo diretamente do campo `layout` da origem. Não existe mais `id_layouts` nas páginas.
3. **Regras de unicidade**:
     - layouts/componentes: id+language únicos
     - páginas: id+language+modulo únicos; caminho único por language
     - variáveis: id+language+modulo (+grupo permite múltiplos)
4. **Órfãos**: Registros que violam unicidade ou regras são segregados em arquivos próprios na pasta `gestor/db/orphans`.
5. **Seeders**: Não será executado diretamente porque a rotina de atualização insere os dados caso os mesmos não existam. Os seeders são usandos no processo de instalação. Aqui é processo de atualização.
6. **Versão/Checksum**: Incremento apenas quando html/css mudam; checksum consolidado como JSON string.

### Impactos e Adaptações Necessárias no Script de Atualização do Banco
1. **Remover dependência de IDs numéricos**: Todos os fluxos de insert/update devem usar apenas os dados naturais dos recursos. PKs são gerados pelo banco.
2. **Atualizar lógica de referência de layouts**: Garantir que páginas usem `layout_id` textual e não tentem mapear para campos numéricos.
3. **Revisar regras de unicidade**: Validar que a sincronização respeite as novas regras e segregue órfãos corretamente.
4. **Remover campos obsoletos**: Eliminar qualquer lógica, campo ou referência a notificações, datas ou identificadores que não existem mais na estrutura dos recursos. As datas por exemplo são geradas automaticamente dentro do MySQL, tanto na criação quanto na atualização. O mesmo para o `id_usuarios`, que tem o default 1 no MySQL.
5. **Órfãos**: Implementar/ajustar lógica para exportar registros problemáticos para a pasta de órfãos, conforme padrão dos recursos. `gestor/db/orphans`
6. **Versionamento**: Garantir que o versionamento/checksum siga o novo padrão.

### Checklist de Planejamento para Adaptação do Script
- [x] Mapear todos os pontos do script que dependem de IDs numéricos ou lógica antiga de referência.
- [x] Listar campos e fluxos obsoletos a serem removidos.
- [x] Planejar adaptação dos inserts/updates para usar apenas dados naturais.
- [x] Validar e adaptar regras de unicidade e segregação de órfãos. (implementado: export/log/ignore com --orphans-mode)
- [x] Documentar todas as decisões e alternativas consideradas.
- [x] Revisar e aprovar planejamento colaborativamente antes de iniciar implementação.

### Observações
- O planejamento será iterativo e colaborativo. Novos pontos podem ser adicionados conforme análise e discussão.
- Após validação de todos os itens, será iniciada a implementação das adaptações no script de atualização do banco.

## 📝 Orientações para o Agente

## 🧭 Estrutura do código-fonte
```
...Demais Funções...

main():
    ... Demais Lógicas ...
    
    ... Demais Lógicas ...

main()
```

## 🤔 Dúvidas e 📝 Sugestões

## ✅ Progresso da Implementação

### Alteração 1: Suporte a Chaves Naturais (layouts, paginas, componentes, variaveis)
- Refatorada função `sincronizarTabela` para operar em dois modos: `pk` (legado) e `natural` (novo).
- Modo natural ativo quando a tabela está na lista de recursos e o JSON não contém a PK numérica.
- Regras de chave natural implementadas:
    - layouts/componentes: language|id
    - páginas: language|modulo|id
    - variáveis: language|modulo|grupo|id
- Preservação de campos `html/css/valor` quando `user_modified=1` mantida para ambos os modos.
- Inserções filtram campos que iniciem com `id_` para deixar o auto incremento atuar.
- Atualizações usam PK numérica se disponível ou cláusula WHERE composta pelos campos naturais.
- Logs diferenciados (`*_NAT`) para clareza operacional.

Alteração 2: Segregação de Órfãos
- Implementado modo de detecção e exportação de registros órfãos para `gestor/db/orphans/bd/` com timestamp.
- Suporta `--orphans-mode=export|log|ignore` (default export).
- Exporta JSON com metadados (tabela, total, timestamp).
- Logs: `ORPHANS_EXPORTED`, `ORPHANS_LOGGED`, `ORPHANS_IGNORED`.
Próxima etapa: Testar em ambiente Docker (sincronização + execução) e validar integridade dos arquivos de órfãos.

## ☑️ Processo Pós-Implementação
- [x] Executar o script gerado para ver se funciona corretamente.
- [x] Gerar mensagem detalhada, substituir pelo texto abaixo e executar o script do GIT à seguir:

./ai-workspace/git/scripts/commit.sh "Atualização do script de sincronização do banco de dados:
- Refatoração para suporte total a chaves naturais em layouts, páginas, componentes e variáveis.
- Remoção de dependência de IDs numéricos nos inserts/updates.
- Implementação de fallback para evitar duplicação de registros quando linguagem estava nula (corrige bug histórico).
- Adaptação das cláusulas WHERE para usar dinamicamente o campo correto de linguagem.
- Exportação e segregação de órfãos conforme regras de unicidade dos recursos.
- Sincronização idempotente: segunda execução não insere duplicatas.
- Testes completos em ambiente Docker, banco limpo e migrações aplicadas."

---
**Data:** dataAtual()
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow v1.10.18