---
name: c2f-documentation-governance
description: Use ao ler, verificar, auditá-las ou atualizar documentações técnicas (docs/, *.md) no Conn2Flow para evitar desatualização e desacoplamento do código-fonte real.
user-invocable: false
---

# Governança e Verificação de Documentação (`c2f-documentation-governance`)

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
