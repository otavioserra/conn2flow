---
name: c2f-documentation-governance
description: "LEIA ANTES de escrever ou atualizar documentações técnicas, especificações e manuais. Se não ler: documentação diverge do código-fonte real (Princípio da Autoridade do Código) e gera alucinações em agentes futuros."
user-invocable: false
---

# Governança e Verificação de Documentação (`c2f-documentation-governance`)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Criar, atualizar ou validar qualquer documento técnico em `docs/`, `sdd/` ou guias de arquitetura.
- **SKIP APENAS SE**: Correção de pequenos erros de digitação (typos) em arquivos que não alterem contratos normativos.
- **CONSEQUÊNCIA DE IGNORAR**: Doc drift (documentação desatualizada), propagação de premissas falsas e violação do Princípio da Autoridade do Código-Fonte.

---

Consulte e aplique este protocolo de verificação para garantir a sincronia e autoridade do código em relação a documentações técnicas:

## 1. Princípio da Autoridade do Código-Fonte

* **Documentações (`docs/`, `*.md`)**: Expressam a intenção arquitetural, histórico e manuais de uso.
* **Código-Fonte (`gestor/`, `modulos/`, `scripts/`, `db/`)**: É a **Fonte Suprema da Verdade**.
* **Regra de Ouro**: NUNCA assuma que assinaturas de função, caminhos de arquivo, parâmetros de API ou estruturas de tabela em documentações estão 100% atualizados sem antes inspecionar o código PHP/JS/SQL autoritativo correspondente via `view_file` ou `grep_search`.

---

## 2. Protocolo de Auditoria e Verificação

Sempre que utilizar uma documentação para orientar uma implementação ou criar uma Skill:
1. **Verificação de Caminho**: Confirme se os arquivos de controladores, bibliotecas ou scripts citados realmente existem no disco.
2. **Verificação de Assinatura**: Abra o arquivo `.php` / `.js` real e verifique o nome exato da função, ordem dos argumentos e tipos de retorno.
3. **Verificação de Metadados de Banco**: Consulte os arquivos de migração Phinx (`gestor/db/migrations/`) e manifestos `schema-metadata.json` para validar a existência de tabelas e colunas.
4. **Resolução de Divergências**: Se houver divergência entre a documentação e o código-fonte real, **o código-fonte prevalece**, devendo a documentação ser corrigida.

---

## 3. Manutenção da Documentação em Tarefas SDD

- Ao adicionar novos parâmetros, funções centrais, hooks ou rotas, atualize a documentação correspondente sob `ai-workspace/` ou `sdd/`.
- Registre depreciações ou quebras de contrato no log do lote (`batch-XXX.md`) e atualize os guias técnicos afetados.
