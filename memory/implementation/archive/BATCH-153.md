# BATCH-153 — Poda e Modularização de Documentação Raiz (req-151)

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-151.md` (originado por `sdd/backlog/BL-013-modularizacao-readme-changelog-ai-workspace.md`)
- **Data**: 2026-08-31
- **Classificação**: governança de documentação, eficiência de contexto de IA, arquitetura de documentação

---

## 1. Motivação e Diagnóstico

Os arquivos de documentação na raiz do repositório (`README.md`, `README-PT-BR.md`, `CHANGELOG.md`, `CHANGELOG-PT-BR.md`) acumulavam mais de 2.300 linhas de texto estático, consumindo desnecessariamente fatias expressivas da janela de contexto dos agentes de IA a cada leitura da raiz.

### Métricas Iniciais (Antes):
- `README.md`: 605 linhas
- `README-PT-BR.md`: 612 linhas
- `CHANGELOG.md`: 409 linhas (versões desde 2.10.0 até 1.0.0)
- `CHANGELOG-PT-BR.md`: 730 linhas (versões desde 2.10.0 até 1.0.0)
- **Total Inicial**: 2.356 linhas

---

## 2. Mudanças Implementadas

### A. Modularização do Guia de Ambiente de Desenvolvimento
O conteúdo aprofundado que detalhava a árvore física de diretórios, configurações locais de Docker, tabelas de tarefas do VS Code, guias de suíte de testes e instruções de permissões foi extraído para manuais dedicados:
- `ai-workspace/en/docs/CONN2FLOW-DEVELOPMENT-ENVIRONMENT.md`
- `ai-workspace/pt-br/docs/CONN2FLOW-AMBIENTE-DESENVOLVIMENTO.md`
Ambos foram devidamente indexados em `ai-workspace/en/docs/README.md` e `ai-workspace/pt-br/docs/README.md`.

### B. Poda Executiva dos READMEs da Raiz
Ambos os arquivos foram condensados em resumos executivos objetivos contendo:
- Identidade visual e switchers de idioma.
- Destaques da versão atual (`v2.10.0`).
- Quickstart e download direto da release `instalador-v2.0.0`.
- Tabela de navegação modular apontando para os documentos especializados em `ai-workspace/`.
- Comandos rápidos de teste (`composer test`, `npm run test`) e sincronização CLI (`./c2f manager:update-all`).
- Informações de comunidade e licença.

### C. Arquivamento e Poda dos CHANGELOGs
- **Versões Mantidas na Raiz**: Exclusivamente a release corrente (`[2.10.0]`) e a série imediatamente anterior (`[2.9.51]`, `[2.9.39]`, `[2.9.0]`), preservando contexto recente para CI e inspeção humana.
- **Versões Arquivadas em `ai-workspace/`**:
  - `ai-workspace/en/docs/changelogs/CHANGELOG-archive-v2-legacy.md` (`[2.8.4]` a `[2.0.21]`)
  - `ai-workspace/en/docs/changelogs/CHANGELOG-archive-v1.md` (`[1.16.0]` a `[1.0.0]`)
  - `ai-workspace/pt-br/docs/changelogs/CHANGELOG-archive-v2-legacy.md` (`[2.8.4]` a `[2.0.21]`)
  - `ai-workspace/pt-br/docs/changelogs/CHANGELOG-archive-v1.md` (`[1.16.0]` a `[1.0.0]`)
- Seção de rodapé em `CHANGELOG.md` e `CHANGELOG-PT-BR.md` com links diretos para os arquivos históricos.

---

## 3. Métricas Finais e Redução Observada

| Arquivo | Linhas Antes | Linhas Depois | Redução | Status |
|---|---|---|---|---|
| `README.md` | 605 | 91 | **-85.0%** | <150 linhas |
| `README-PT-BR.md` | 612 | 91 | **-85.1%** | <150 linhas |
| `CHANGELOG.md` | 409 | 128 | **-68.7%** | <150 linhas |
| `CHANGELOG-PT-BR.md` | 730 | 128 | **-82.5%** | <150 linhas |
| **Total Raiz** | **2.356** | **438** | **-81.4%** | Conforme critério |

---

## 4. Evidências de Validação

1. **Release Gate Integrity**:
   - `grep -Fq "v2.10.0" README.md`: OK (linha 11)
   - `grep -Fq "v2.10.0" README-PT-BR.md`: OK (linha 11)
   - `grep -Fq "[2.10.0]" CHANGELOG.md`: OK (linha 8)
   - `grep -Fq "instalador-v2.0.0" README.md`: OK (linhas 18, 29, 33, 37)
   - `grep -Fq "instalador-v2.0.0" README-PT-BR.md`: OK (linhas 18, 29, 33, 37)
   - `php ai-workspace/en/scripts/releases/version.php patch --dry-run`: 2.10.1 (OK)
2. **Integridade de Links Relativos**:
   - 24/24 referências testadas e confirmadas no disco via PowerShell.
3. **Suíte Automatizada**:
   - PHPUnit (`composer test`): **965 testes, 4.192 asserções, 0 falhas**.
   - Vitest (`npm run test`): **26 arquivos, 378 testes, 0 falhas**.
