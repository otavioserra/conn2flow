---
name: c2f-multilingual-system
description: "LEIA ANTES de adicionar novos idiomas, rotas i18n ou consultas multi-idioma. Se não ler: conteúdo de idiomas diferentes vaza na mesma página ou URLs internacionais quebram com erro 404."
user-invocable: false
---

# Sistema Híbrido Multilíngue Conn2Flow (`CONN2FLOW-MULTILINGUAL-HYBRID-SYSTEM.md`)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Manipular tabelas com coluna `language`, registrar rotas internacionalizadas (`/en/`, `/pt-br/`) ou gerenciar dicionários de idiomas.
- **SKIP APENAS SE**: Módulos utilitários globais agnósticos a idioma.
- **CONSEQUÊNCIA DE IGNORAR**: Vazamento de registros em inglês na visualização em português (e vice-versa), falha na resolução de slugs traduzidos e desindexação de SEO.

---

Consulte e aplique as seguintes convenções ao desenvolver para suporte a múltiplos idiomas no Conn2Flow:

## 1. Estrutura de Idiomas e Resolução

* **Idiomas Suportados**: `pt-br` (padrão), `en`, `es`.
* **Código de Idioma Ativo**: Acessível via `$_GESTOR['linguagem-codigo']`.
* **Estrutura FÍSICA de Pastas**:
  - Recursos globais: `gestor/resources/<idioma>/` (ex: `pt-br/pages/`, `en/pages/`).
  - Recursos de módulo: `modulos/<modulo>/resources/<idioma>/`.

---

## 2. Tabelas Relacionais e Chave Natural (`language`)

* Tabelas com suporte multilíngue possuem a coluna `language VARCHAR(10) NOT NULL DEFAULT 'pt-br'`.
* A chave única natural é geralmente composta por `['id', 'language']` (ou `['language', 'module', 'id']`).

---

## 3. Ferramentas de Tradução

* Scripts de automação em `ai-workspace/en/scripts/translates/` para geração e sincronização de dicionários e recursos em novos idiomas.
