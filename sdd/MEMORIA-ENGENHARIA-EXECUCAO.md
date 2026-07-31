# Memória de Engenharia — Execução

> **Propósito**: manter contexto operacional recente. Regras consolidadas vivem em `.claude/skills/` e `.cursor/skills/` e são carregadas sob demanda.
>
> **Política**: preservar 3 a 5 tarefas recentes, mirar ~5 KB e podar antes de 10 KB. A memória de Chefia permanece somente leitura.

## Skills Core destiladas

- `c2f-json-resources-sync`: versões/checksums de recursos são recalculados pelo deploy.
- `c2f-widget-development`: recursos desduplicados, contrato AJAX e tokens `item#var`.
- `c2f-gd-image-safety`: suporte opcional a formatos GD e captura de `\Throwable`.
- `c2f-database-testing`: SQLite em memória ou MySQL isolado `conn2flow_test`.
- `c2f-mysql-utf8-emoji-encoding`: JSON ASCII-safe para MySQL `utf8` de 3 bytes.

## Tarefas recentes

### BATCH-103 — busca normalizada e paginação sem salto

- Comparações textuais de UI devem usar lowercase + NFD sem marcas combinantes. Monte o range Unicode por código (`String.fromCharCode`) para não depender da normalização do arquivo-fonte.
- Em paginação AJAX, não esconda a lista já renderizada: a perda de altura desloca a rolagem para o topo. Mostre o indicador depois da lista e esconda apenas na primeira carga/substituição.
- Assets do menu precisam ser incluídos antes de `gestor_pagina_extra_head_e_javascript()` ou concatenados ao HTML do menu; a fila já foi consumida quando `gestor_pagina_menu()` roda.

### BATCH-102 — precedência do diretório no picker

- O diretório inicial do `admin-arquivos` segue `?dir=` explícito > cache > raiz. O modo iframe não elimina a intenção do usuário e também deve ler/gravar o cache quando não há diretório explícito.

### BATCH-101 — mídia sem fonte vazia

- Nunca gere `src=""` ou `data=""`: o navegador requisita a própria página como mídia, falha na decodificação e pode colapsar o player.
- Em geradores de markup, omita completamente o atributo de fonte quando não houver valor.
- Hipóteses não sustentadas pela evidência devem ser revertidas; a assinatura `; ` no style ajudou a provar que o elemento havia passado pelo modal.

### BATCH-100 — arquivos com espaço, Range e embeds

- Apache 2.4.53+ pode recusar rewrite de caminho decodificado com espaço (`AH10411`). A regra precisa da flag `[B]`; codificar `%20` no cliente não corrige a decodificação interna.
- Servir áudio/vídeo exige `Accept-Ranges`, `Content-Length`, resposta 206/416 e streaming por blocos; não anexar charset a tipos binários.
- Tamanhos padrão dependem do tipo: áudio sem altura, vídeo 360px, iframe/embed 400px e documento 600px. Codifique apenas a URL de exibição; preserve o caminho cru como identificador do backend.

### BATCH-099 — picker é o gerenciador completo

- `?paginaIframe=sim` usa o mesmo `admin-arquivos`. O fluxo de upload e o retorno precisam permanecer acessíveis; diferenças legítimas são seleção em vez de cópia de URL e parâmetros de retorno.
- Antes de esconder ferramentas por modo, confirme os links e handlers já implementados para não tornar um fluxo existente inalcançável.

## Pendências

- Testes que executam o compilador de recursos podem regenerar data files/checksums. Conferir `git status` e manter apenas alterações pertencentes ao batch corrente.
- Detalhes anteriores à BATCH-099 permanecem recuperáveis no histórico Git.
