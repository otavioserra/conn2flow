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

## Hash pairing e revisão

O registro `scripts/skills/translated.json` mapeia idioma → skill → `source_sha256`, `translated_at` e `translator`. O hash SHA-256 é dos bytes da fonte PT-BR revisada, incluindo finais de linha; não é o hash da tradução e não prova qualidade linguística.

Antes de mudar a matriz, consulte as traduções ativas. Após a mudança, atualize as traduções no escopo ou registre explicitamente a pendência no lote. Auditoria padrão e `mdd sync --audit --matrix <raiz> --path <destino>` emitem `[DRIFT-WARNING]`/`DRIFT_WARNING` se a fonte divergir. Warning é não bloqueante; divergências reais de cópia continuam FAIL.

Depois de revisar o conteúdo traduzido em todos os kits pertinentes, reconheça a nova origem:

```sh
node scripts/skills/sync-skills.cjs --update-translation-hash <skill> <idioma> --translator mdd-agent
node scripts/skills/sync-skills.cjs --report completions/skills-audit.json
```

Nunca atualize hashes automaticamente num sync normal, antes da revisão ou só para obter PASS. A proteção contra sobrescrita continua mesmo em drift. Renomear exige migrar o nome nos kits e no registro, preservando o histórico e revendo hashes; não crie dupla fonte de autoria.

## Novo idioma

Crie os kits do idioma com a mesma estrutura; traduza cada procedimento completo e seus metadados descritivos. Registre somente traduções existentes e revisadas, com origem SHA-256, data ISO e responsável. Confira equivalência de regras, blocos de código, funções, comandos, variáveis e links. Execute auditoria, testes do sincronizador e uma mutação da fonte numa fixture para demonstrar o warning sem sobrescrita. Inclua o idioma na seleção de templates do sincronizador antes de alegar suporte à propagação. Produção e homologação humana não são autorizadas por essa manutenção.
