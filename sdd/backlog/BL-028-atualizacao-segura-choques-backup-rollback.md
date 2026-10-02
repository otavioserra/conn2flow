# BL-028 — Atualização segura: core canibalizável, choques, exclusão de dados, backup e rollback

- **Tipo**: Epic / Architecture
- **Status**: PROMOTED (2026-09-30) — [req-197](../human-requests/archive/req-197.md) (fase 1), [req-198](../human-requests/archive/req-198.md) (A, B.1, D) e [req-199](../human-requests/archive/req-199.md) (C, G)
- **Severidade sugerida**: ALTA (hoje uma atualização pode desfazer a customização de um projeto em silêncio, e não há volta)
- **Origem**: Humano, 2026-09-29, junto com o hotfix [req-194](../human-requests/archive/req-194.md) (migrações obsoletas)
- **Componentes**: `controladores/atualizacoes/atualizacoes-sistema.php`, `atualizacoes-banco-de-dados.php`, `atualizacoes-migracoes.php`, `controladores/api/api.php` (`api_project_update`), `ai-workspace/en/scripts/projects/{deploy-project-v2,synchronize-project,sync-core-to-project}.sh`, `cli/src/Commands/ProjectUpdateAllCommand.php`

## Princípio (definido pelo Humano)

O core pode ser **canibalizado**: um projeto (e um plugin, que roda sobre o core) pode sobrepor **qualquer** arquivo do core — até o `index.php` — e mesmo assim continuar recebendo atualizações. Choques de arquivos e de tabelas são normais e permitidos; o sistema precisa **prever, registrar e tratar** esses choques, não impedi-los. Toda remoção (arquivos ou dados) acontece **no ambiente em execução**, nunca no repositório.

## Diagnóstico (leitura do código em 2026-09-29)

### Caminhos de entrega

| Caminho | Como aplica arquivos | Remove algo? |
|---|---|---|
| Atualização do sistema (CLI) | extrai o release em staging e move/mescla por cima (`moverConteudoStaging`, merge recursivo de pastas); `--wipe` opcional apaga tudo menos `contents/ logs/ backups/ temp/ autenticacoes/` | apaga `db/` inteiro depois do banco |
| Atualização do sistema (web, por etapas) | igual ao CLI | **não** apaga `db/` (diverge do CLI) |
| Deploy de projeto por API (`/_api/project/update`) | extrai o ZIP e copia por cima (`api_copy_directory`) | nada (req-194 passou a limpar só migrações) |
| Pipeline por rsync (`project:update-all`, Lab/VM) | `rsync -avu` sem `--delete` | nada (req-194: só migrações) |
| Sync do core para projeto | `rsync -u` (arquivo local mais novo vence) | nada |

### Achados

1. **Sem registro de dono por arquivo.** Não há como saber se um arquivo do servidor veio do core, de um plugin ou do projeto. Consequência: o projeto sobrepõe um arquivo do core, a próxima **atualização do sistema sobrescreve a sobreposição sem aviso**, e o site roda com a versão do core até o próximo deploy do projeto (estado híbrido, silencioso).
2. **Arquivos retirados nunca saem.** Um arquivo removido do core ou do projeto fica no servidor para sempre (só as migrações foram tratadas na req-194). Um controlador ou biblioteca órfão continua carregável.
3. **Exclusão de dados é só imperativa.** O atualizador de banco faz UPSERT; remover registro exige declará-lo na lista `deletar` do `schema-metadata.json` (`executarDelecoes`). Recurso retirado do repositório (página, variável, template, widget) segue no banco; órfãos são só logados (`--orphans-mode`).
4. **`--backup` quebra.** `atualizacoes-sistema.php` chama `backupTotal()`, que não existe em lugar nenhum: a atualização com backup morre com erro fatal. Não há backup de banco antes das migrações nem rollback de nenhum tipo.
5. **Sem atomicidade nem trava.** A cópia por cima pode falhar no meio (estado híbrido). Nada impede dois deploys simultâneos no mesmo ambiente — no Lab, agentes em paralelo trocam o `path` do projeto e sobrescrevem o código um do outro.
6. **Ordem do pipeline.** `project:update-all` roda o banco (etapa 2) antes de enviar os arquivos (etapa 4); qualquer lixo no destino trava a etapa 2 (foi assim que a migração duplicada bloqueou o Lab).
7. **Choque de numeração entre agentes.** Duas migrações com a mesma versão criadas em paralelo (`20260929140000`) só aparecem no servidor, como erro do Phinx.
8. **Plugins.** A sincronização de hooks apaga por id de módulo sem considerar o plugin: dois módulos com o mesmo id (core e plugin) apagam os hooks um do outro. Tabelas de plugin e de projeto não têm dono registrado.
9. **Primeira execução da req-194.** Sem manifestos, a regra da migração renomeada não distingue os donos (limitação registrada no BATCH-198).

## Propostas

### A. Manifesto de instalação por camada (base de tudo)

Cada entrega (core, cada plugin, projeto) grava no servidor um manifesto `caminho → hash` do que entregou (`installation/manifests/<camada>.json`, fora de `db/`). Com ele o atualizador sabe, para cada arquivo:

- **intacto** (hash do core = hash no disco);
- **sobreposto** (o projeto/plugin entregou outro conteúdo no mesmo caminho);
- **editado localmente** (ninguém entregou o hash atual — alguém mexeu no servidor);
- **retirado** (a camada entregou antes e não entrega mais).

Generaliza o manifesto de migrações da req-194.

### B. Política de choque (opções para o Humano decidir)

1. **Precedência de camada no disco (recomendado agora).** Ordem `projeto > plugin > core`. A atualização do core **não sobrescreve** arquivo sobreposto: guarda a versão nova do core em `backups/overrides/<versão>/<caminho>` e registra o choque "o core mudou um arquivo que você sobrepôs", com diff. O projeto decide quando incorporar.
2. **Overlay em runtime.** O projeto não escreve em cima do core: suas sobreposições ficam em `project/overrides/<caminho>` e o carregador resolve (`require` pela camada mais alta). Atualizar o core vira trocar a pasta do core inteira. Mais limpo, mas exige tocar todos os `require`/`include` do núcleo.
3. **Patches.** O projeto guarda diffs sobre o core e o atualizador reaplica (merge em 3 vias). Flexível, mas frágil quando o core muda muito.

Em qualquer opção: **registro de choques** (tabela `atualizacoes_choques` ou JSON + aba em `admin-atualizacoes`) com camada, caminho, tipo (arquivo, tabela, migração, hook), versões e resolução.

### C. Exclusão de dados no ambiente em execução

- Cada deploy leva o manifesto de **recursos** por dono (ids naturais por tabela: páginas, layouts, componentes, variáveis, templates, widgets, hooks).
- O atualizador compara com o manifesto anterior do **mesmo dono** e remove (ou marca `status='D'`) o que saiu.
- Registro editado online (`user_modified`) **não** é apagado: vira choque para decisão humana.
- Modo simulação com relatório antes de aplicar; a lista `deletar` imperativa continua para casos pontuais.

### D. Backup e rollback automáticos

1. **Antes de aplicar:** snapshot só dos arquivos que serão sobrescritos ou removidos (não backup total) + dump das tabelas que as migrações e o sync vão tocar; tudo ligado ao id da execução em `atualizacoes_execucoes`.
2. **Health check depois:** rotas-chave com HTTP 200, sem fatal no log, migrações sem pendência.
3. **Rollback:** restaura o snapshot de arquivos automaticamente se o health check falhar; o banco volta pelo dump (automático quando só houve migrações da própria execução; confirmado pelo operador quando houve escrita de usuários no meio).
4. Retenção de N execuções; comando `c2f update:rollback <execução>`.
5. Corrigir o `backupTotal()` inexistente (achado 4) já no primeiro lote.

### E. Atomicidade e trava

- Extrair em staging completo e trocar por `rename` de pastas (troca quase atômica) em vez de copiar arquivo a arquivo.
- Trava de deploy por ambiente (arquivo de lock com dono, execução e TTL) respeitada por API, atualização do sistema e pipeline; no Lab, o lock também identifica o agente.

### G. Diff e merge dos choques (pedido do Humano, 2026-09-29)

Quando uma entrega vai sobrescrever um arquivo que está **diferente** no ar (o projeto sobre o core, o core sobre o projeto, ou uma edição feita no servidor), o sistema oferece:

1. **Lista de choques + as duas cópias.** Antes de aplicar (ou em modo simulação), o ambiente devolve a lista dos arquivos em choque e um pacote com a versão **que vai ser publicada** e a versão **que está no ar** de cada um (com o hash e a camada de origem).
2. **Diff e merge no repositório.** Um comando do CLI (ex.: `c2f update:conflicts <projeto> [--pull]`) baixa esse pacote para uma pasta local e abre o diff; a pessoa decide por arquivo: sobrescrever, manter o que está no ar ou **mesclar partes** (ex.: o usuário trocou só um texto). O resultado volta como resolução (`c2f update:resolve`) e a atualização aplica o arquivo mesclado.
3. **Extensão VS Code do ai-workspace** (repositório `conn2flow-ai-workspace`, pasta `vscode-extension/`; a implementação lá fica referenciada daqui quando o item for promovido). A extensão ganha a mesma função pelo CLI: listar choques, abrir o diff lado a lado no editor nativo, marcar a resolução e enviar.
4. **Diff online.** O módulo `admin-atualizacoes` mostra os mesmos choques com diff no navegador e as mesmas três escolhas, para quem atualiza pelo painel (o Humano vai enviar o módulo para a análise).
5. As resoluções ficam no registro de choques (A/B) e viram a regra das próximas atualizações daquele arquivo (ex.: "sempre manter a versão do projeto" até o core mudar de novo).

### F. Outros

- Alinhar CLI e web da atualização do sistema (limpeza de `db/`).
- Rever a ordem do `project:update-all` (banco antes dos arquivos).
- `c2f db:check-migrations`: acusa versão duplicada, e classe duplicada entre core, plugins e projeto, **antes** do deploy (pre-commit ou pipeline), evitando o choque entre agentes.
- Dono por hook e por tabela (achado 8).
- Leitura de tabelas em streaming ([BL-027](BL-027-sincronizacao-banco-memoria.md)).

## Fases sugeridas

1. Corrigir `backupTotal`; trava de deploy; `db:check-migrations`; alinhar CLI/web.
2. Manifesto por camada (A) + registro de choques + política B.1.
3. Backup seletivo + health check + rollback (D).
4. Exclusão declarativa de dados (C).
5. Diff e merge dos choques (G): CLI, extensão VS Code e `admin-atualizacoes`.
6. Avaliar overlay em runtime (B.2) como evolução.

## Critérios de aceite (rascunho)

- Um projeto sobrepõe um arquivo do core, o core é atualizado: a sobreposição continua valendo, o choque aparece no painel com o diff da versão nova.
- Um recurso removido do repositório some do banco no próximo deploy, exceto se editado online (vira choque).
- Uma atualização que quebra o site volta sozinha ao estado anterior, com relatório.
- Dois deploys no mesmo ambiente não rodam ao mesmo tempo.

## Parecer do Arquiteto (2026-09-30, depois do ciclo do e-commerce)

**Recomendação:** promover em **três requisições**, nesta ordem.

1. **Fase 1 agora, como hotfix curto** (1 lote):
   - trava de deploy por ambiente;
   - corrigir `backupTotal()`;
   - `c2f db:check-migrations`;
   - alinhar CLI e web.

   A trava deixou de ser teórica. Em 2026-09-30 dois pipelines de agentes diferentes cruzaram no Lab duas vezes: um deles, meu, rodou mesmo depois de a checagem acusar o outro em execução. Um desses cruzamentos já tinha causado o erro 1020 do MariaDB. Hoje a proteção depende de disciplina de cada agente.
2. **Fases 2 e 3 juntas** (A + B.1 + D), numa requisição de arquitetura com 2 ou 3 lotes:
   - manifesto por camada, precedência `projeto > plugin > core` no disco e registro de choques;
   - backup seletivo com health check e rollback.

   É o que resolve os dois riscos altos (a sobreposição apagada em silêncio e a atualização sem volta). A deve vir antes de D, porque o backup seletivo precisa do manifesto para saber o que vai ser sobrescrito.
3. **Fases 4 e 5 depois** (C e G), quando A estiver no ar há algumas versões:
   - C, a exclusão declarativa, só é segura com manifesto de recursos confiável;
   - G, o diff e merge no CLI, na extensão e no `admin-atualizacoes`, depende do registro de choques.

   B.2 (overlay em runtime) continua como avaliação futura: custa mexer em todo `require` do núcleo e não é necessário para os critérios de aceite.

**Achados do ciclo do e-commerce para somar ao F:**
- **Página Tailwind nova sai sem CSS na primeira rodada do `project:update-all`:** o `*.precompiled.css` é gerado depois de o `PaginasData.json` ser montado, e a página vai para o banco com `css_precompiled` vazio. Precisou de uma segunda rodada nas REQ-078, 079 e 080 do site. Rever a ordem dentro da etapa de recursos, junto com a ordem banco → arquivos (achado 6).
- **Trava do OneDrive no `rename` do Tailwind** ("arquivo em uso"): falha transitória, que se resolve repetindo. Uma nova tentativa automática curta na substituição atômica evitaria rodar o pipeline inteiro de novo.

**Fora do escopo do BL-028, mas vale uma requisição pequena no core:** o `banco_select` usa a expressão inteira como chave do resultado (`'COUNT(*) AS n'` vira a chave `'COUNT(*) AS n'`, não `n`). No site isso anulava, sem erro, dois limites de segurança (pedidos e CEP por IP) e a nota das avaliações. O core já contorna com `reset()` nos seus widgets. Proposta: o `banco_select` passar a usar o alias quando houver `AS`, mantendo também a chave antiga, para não quebrar quem já lê pela expressão.

