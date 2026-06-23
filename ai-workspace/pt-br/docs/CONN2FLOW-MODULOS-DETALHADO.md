# Conn2Flow - Módulos Detalhado

## 📋 Índice
- [Visão Geral](#visão-geral)
- [Estrutura de Módulo](#estrutura-de-módulo)
- [Vinculação Página-Módulo](#vinculação-página-módulo)
- [Módulos Reais vs Inválidos](#módulos-reais-vs-inválidos)
- [Boas Práticas](#boas-práticas)
- [Exemplos](#exemplos)
- [Histórico de Decisões](#histórico-de-decisões)

---

## 🎯 Visão Geral

Módulos são responsáveis pela lógica específica de páginas no Conn2Flow. Cada módulo pode conter arquivos PHP e JS, além de assets próprios.

---

## 🏗️ Estrutura de Módulo
- Localização: `gestor/modulos/{modulo}/`
- Arquivo principal: `{modulo}.php` (e/ou `{modulo}.js`)
- Assets: CSS, JS, imagens, etc.
- Funções específicas: inicialização, menu, toasts, etc.

```
modulo-nome/
├── modulo-nome.php       # Lógica backend (PHP)
├── modulo-nome.js        # Lógica frontend (JavaScript)
├── modulo-nome.json      # Configurações, metadados e mapeamento dos recursos.
└── resources/            # Recursos visuais por idioma
    └── pt-br/
        ├── layouts/      # Layouts específicos
        ├── pages/        # Páginas HTML
        ├── components/   # Componentes reutilizáveis
```

### 🎛️ **Sistema de Configuração JSON**
Cada módulo possui um arquivo JSON com:
- **versao**: Versionamento do módulo
- **bibliotecas**: Dependências de bibliotecas
- **tabela**: Configuração de banco de dados
- **resources**: Recursos por idioma (páginas, componentes, variáveis)
- **Configurações específicas**: Parâmetros únicos do módulo

---

## 🔗 Vinculação Página-Módulo
- Páginas podem ser vinculadas a um módulo.
- O roteador (`gestor.php`) inclui automaticamente o módulo ao renderizar a página.
- Exemplo: página dashboard vinculada ao módulo dashboard.

---

## ✅ Módulos Reais vs Inválidos
- Módulo real: possui `{modulo}.php` ou `{modulo}.js` na pasta.
- Módulo inválido: pasta sem arquivo principal, não deve receber páginas exportadas.
- Exportação automatizada só cria pastas para módulos reais.

---

## 📝 Boas Práticas
- Sempre criar `{modulo}.php` para módulos novos.
- Documentar funções e pontos de entrada.
- Manter assets organizados na pasta do módulo.
- Evitar duplicidade de lógica entre módulos.

---

## 💡 Exemplos
- Módulo dashboard: `gestor/modulos/dashboard/dashboard.php`
- Módulo host-configuracao: integrações cPanel, assets próprios.

---

## 🧱 Bloco `tabela` do Manifesto e Sincronização de Recursos (BATCH-056)

No manifesto `<modulo>.json`, a chave `"tabela"` descreve como a(s) tabela(s) do módulo entram na esteira de dados. A sub-chave `"config"` aceita um **objeto** (1 tabela) ou um **array de objetos** (N tabelas, cada um com `"tabela_nome"`), com as propriedades:

- `strategy` (`natural_key`/`pk`), `natural_key_columns`, `preserve_on_user_modified`, `insert_only`.
- `sync_resources`, `resources_dir`, `metadata_file`, `field_types` (`json` / `file:<ext>`) — geram `<PascalCase>Data.json` automaticamente a partir dos recursos físicos/inline.
- `deletar` e `forcar_atualizacao` — listas de registros (`pk` / `natural_key`) para remoção ou sobrescrita forçada no deploy.

O gerador (`atualizacao-dados-recursos.php`) consolida esses blocos (módulos + global) no contrato `schema-metadata.json`.

---

## 📜 Histórico de Decisões
- Validação de módulos reais implementada em agosto/2025.
- Exportação de páginas para módulos inválidos bloqueada.
- Estrutura de módulos padronizada para facilitar manutenção.
- Sincronização declarativa de tabelas customizadas (`config` objeto/array, `sync_resources`, `field_types`, `forcar_atualizacao`) adicionada em 2026-06 (BATCH-056).
