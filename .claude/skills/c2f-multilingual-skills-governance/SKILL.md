---
name: c2f-multilingual-skills-governance
description: "LEIA ao criar, renomear ou editar skills e ao adicionar idiomas de templates. Mantém traduções e hashes de origem alinhados sem sobrescrever traduções."
user-invocable: false
---

# Governança multilíngue de skills — REQ-078

# ⚡ Gatilho Obrigatório

- **TRIGGER**: Criar, renomear ou editar uma skill; adicionar um idioma aos templates.
- **SKIP APENAS SE**: Alteração sem impacto nas skills ou no catálogo de traduções.
- **CONSEQUÊNCIA DE IGNORAR**: Tradução desatualizada protegida silenciosamente ou sobrescrita por conteúdo no idioma errado.

## Tríade de autoria

1. Matriz PT-BR: `.gemini/skills/<nome>/SKILL.md` é a fonte canônica do procedimento.
2. Templates traduzidos: `templates/<idioma>/templates/<kit>/<cliente>/skills/<nome>/SKILL.md` recebem tradução revisada. Nome de skill, funções PHP, comandos c2f, constantes, caminhos e superglobais permanecem intactos. Traduza explicações, gatilhos e descrição YAML; preserve chaves, valores de controle e alertas Markdown.
3. Satélites: `node scripts/skills/sync-skills.cjs --apply --all` propaga a matriz pelos kits existentes e preserva traduções registradas e skills locais. Não copie manualmente para espelhos de teste.

## Sincronização restrita à matriz

Use estes comandos em lotes locais aprovados somente para a matriz. A seleção inclui os cinco kits da matriz e os templates. Os templates EN mantêm as traduções registradas.

```sh
node scripts/skills/sync-skills.cjs --apply --all --repos conn2flow-ai-workspace
node scripts/skills/sync-skills.cjs --repos conn2flow-ai-workspace --report completions/skills-audit.json
```

## Escrita seletiva e variantes customizadas

`--apply <skills>` restringe a escrita às skills selecionadas. A auditoria continua inspecionando o catálogo completo dos destinos selecionados e pode apontar divergências preexistentes em outras skills.

Revise o diff da variante em relação à fonte e incorpore a atualização preservando a customização legítima. Reconheça o novo par de hashes com `mdd sync --customize <kit>/skills/<skill>/SKILL.md --matrix <matriz> --path <destino>` e repita a auditoria.

## Hash pairing e revisão

O registro `scripts/skills/translated.json` mapeia idioma → skill → `source_sha256`, `translated_at` e `translator`. O SHA-256 identifica a fonte PT-BR revisada. O sincronizador converte CRLF para LF em memória antes de calcular o hash e comparar cópias de skills. O conteúdo gravado mantém os bytes da fonte, e os hashes de reconhecimento de customizações continuam usando os bytes exatos.

Antes de atualizar hashes, confira branch e alterações com `git branch --show-current` e `git status --short`. Use `git ls-files --eol -- .gemini/skills` para comparar índice (`i/`) e checkout (`w/`), e `git diff --ignore-cr-at-eol -- .gemini/skills` para revisar mudanças de conteúdo. Um hash calculado sobre o arquivo cru pode mudar só por CRLF/LF, inclusive em arquivos com finais mistos. Revise as traduções antes de registrar mudanças reais da fonte.

Antes de mudar a matriz, consulte as traduções ativas. Após a mudança, atualize as traduções no escopo ou registre explicitamente a pendência no lote. Auditoria padrão e `mdd sync --audit --matrix <raiz> --path <destino>` emitem `[DRIFT-WARNING]`/`DRIFT_WARNING` se a fonte divergir. Warning é não bloqueante; divergências reais de cópia continuam FAIL.

Depois de revisar o conteúdo traduzido em todos os kits pertinentes, reconheça a nova origem:

```sh
node scripts/skills/sync-skills.cjs --update-translation-hash <skill> <idioma> --translator mdd-agent
node scripts/skills/sync-skills.cjs --repos conn2flow-ai-workspace --report completions/skills-audit.json
```

Testes que leem a matriz (ex.: `pytest -q tools/mdd-client/tests`) devem rodar **depois** de reconhecer os hashes de tradução (`--update-translation-hash`), para evitar `DRIFT_WARNING` transitório durante a rodada. Ordem: sync → hashes → auditoria → `pytest`.

Nunca atualize hashes automaticamente num sync normal, antes da revisão ou só para obter PASS. A proteção contra sobrescrita continua mesmo em drift. Renomear exige migrar o nome nos kits e no registro, preservando o histórico e revendo hashes; não crie dupla fonte de autoria.

## Novo idioma

Crie os kits do idioma com a mesma estrutura; traduza cada procedimento completo e seus metadados descritivos. Registre somente traduções existentes e revisadas, com origem SHA-256, data ISO e responsável. Confira equivalência de regras, blocos de código, funções, comandos, variáveis e links. Execute auditoria, testes do sincronizador e uma mutação da fonte numa fixture para demonstrar o warning sem sobrescrita. Inclua o idioma na seleção de templates do sincronizador antes de alegar suporte à propagação. Produção e homologação humana não são autorizadas por essa manutenção.
