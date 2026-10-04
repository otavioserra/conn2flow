# BATCH-235: Dashboard V3.0 — Grid Interativo de Módulos (P/M/G), Grid Flexível de Widgets e Links Canônicos de Documentação (req-226)

Implementação da vitrine da Versão 3.0 para o Dashboard inicial do Gestor descrita na [req-226](../human-requests/req-226.md).

**Status**: `in-review`. Implementado e validado em ambiente isolado na branch `feat/req-226`.

---

## 1. O que mudou

| Onde | Mudança |
|---|---|
| `gestor/db/migrations/20261004140000_create_usuarios_preferencias_table.php` | Migration Phinx criando a tabela `usuarios_preferencias` (`id_usuarios`, `chave`, `valor`, índice único `[id_usuarios, chave]`) para persistência de preferências do usuário no backend entre navegadores e dispositivos. |
| `gestor/modulos/dashboard/dashboard.php` | • `dashboard_modulo_documentacao()`: Resolução canônica de links de documentação e manuais (`https://conn2flow.com/docs/reference/modules/<id>/` e `https://conn2flow.com/docs/manual/modules/<id>/` para core; rota interna para hosts; botão desabilitado com tooltip descritivo quando ausente - CA-1).<br>• `dashboard_modulo_visual_e_atalhos()`: Suporte a thumbnails/covers físicas (`cover.webp`, `cover.png`, `thumbnail.webp`) ou declaradas no JSON, com fallback gracioso para `#modulo-svg#` e injeção do botão de atalho "Novo Registro" para visão expandida G (CA-5).<br>• Persistência de preferências: funções `dashboard_preferencias_obter()` e `dashboard_preferencias_salvar()`.<br>• Endpoints AJAX em `dashboard_start()`: `salvar-preferencias`, `widgets-catalogo` e `widget-render`.<br>• Atualização do `dashboard_3d()` para usar a mesma resolução canônica de documentação. |
| `gestor/modulos/dashboard/resources/{pt-br,en}/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html` | • Navegação por abas nativas Tailwind no topo ("Módulos" vs "Widgets & Métricas" - CA-3).<br>• Seletor de densidade com botões P, M, G ao lado da busca (CA-2).<br>• Grid flexível de widgets com container dinâmico, empty state e modal para seleção no catálogo (CA-4). |
| `gestor/modulos/dashboard/resources/{pt-br,en}/components/dashboard-cards-tailwind/dashboard-cards-tailwind.css` | • Regras de responsividade para densidades `.density-p` (2 a 6 cols, cards compactos, mini-ícone 32px), `.density-m` (1 a 4 cols, ícone 64-80px) e `.density-g` (1 a 2 cols, cover expandida, descrição completa e atalhos rápidos contextuais).<br>• Regras para `.has-cover` vs `.no-cover`.<br>• Regras de chaveamento de abas e colunas de widgets (`.col-span-1`, `.col-span-2`, `.col-span-full`) com colapso responsivo para mobile (390px). |
| `gestor/modulos/dashboard/dashboard.js` & `dashboard.min.js` | • `initDashboardDensity()`: alternância imediata de densidade na UI e sincronização com backend/localStorage.<br>• `initDashboardTabs()`: chaveamento fluido de abas preservando estado da busca.<br>• `initDashboardWidgets()`: catálogo modal, renderização AJAX, redimensionamento de colunas, remoção e ordenação via SortableJS com persistência no backend.<br>• Minificação compilada com terser em `dashboard.min.js`. |
| `gestor/modulos/dashboard/dashboard.json` | • Bump de versão do módulo para `1.0.23`.<br>• Bump de versão do componente `dashboard-cards-tailwind` para `1.2` com checksums MD5 recalculados.<br>• Bump de versão da página `dashboard` para `1.6` (pt-br) / `1.5` (en).<br>• 23 novas variáveis bilíngues cadastradas para abas, densidade, tooltips e catálogo de widgets. |

---

## 2. Validação e Testes

- **Sintaxe PHP:** `php -l` em `dashboard.php` e na migration de `usuarios_preferencias` sem erros.
- **Sintaxe JS:** `node -c` em `dashboard.js` e `dashboard.min.js` sem erros.
- **Validação JSON:** `json_decode` em `dashboard.json` sem erros e checksums recalculados.
- **PHPUnit:**
  - `PainelTailwindReq224Test`: 2 testes, 221 asserções (100% aprovado).
  - `TailwindRecursosTest`: 23 testes, 343 asserções (100% aprovado).
  - `TailwindGuardasTest`: 20 testes, 21 asserções (100% aprovado).
- **Critérios de Aceite:**
  - `[x]` **CA-1 (Docs Canônicos):** URLs públicas no site canônico, sem URLs quebradas para GitHub antigo.
  - `[x]` **CA-2 (Seletor P/M/G):** Chaveamento instantâneo sem reload e com persistência.
  - `[x]` **CA-3 (Abas):** Chaveamento de abas fluido mantendo filtro de busca.
  - `[x]` **CA-4 (Grid de Widgets):** Widgets adicionáveis, redimensionáveis e ordenáveis com persistência no backend.
  - `[x]` **CA-5 (Imagens & Fallbacks):** Thumbnails/covers renderizadas quando existentes, fallback limpo para SVG.
  - `[x]` **CA-6 (Qualidade & Mobile):** Sem erros de console e responsivo a 390px.
