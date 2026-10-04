# BATCH-236 — Capas referenciais dos módulos (req-227)

- **Status:** `in-review`
- **Data:** 2026-10-04
- **Requisição:** [req-227](../human-requests/req-227.md)
- **Repositórios:** `conn2flow` e `conn2flow-site`, em `C:/Users/otavi/OneDrive/Documentos/GIT/`.

## Escopo

Criar as 22 capas individuais do inventário da requisição, com linguagem 3D isométrica unificada, WebP abaixo de 120.000 bytes e catálogo com miniaturas. Assets do core em `gestor/assets/modulos/covers/`; assets do site no mesmo caminho relativo do projeto.

### Complemento autorizado no chat

O usuário acrescentou a [REQ-102 do site](../../../conn2flow-site/sdd/human-requests/website/req-102-assets-visuais-e-imagens-referenciais-modulos-site.md), BATCH-096. A união dos inventários soma **37 imagens: 14 do core e 23 do site**. Seis módulos são comuns aos dois inventários e recebem uma única capa cada; os 15 módulos adicionais do site usam a mesma referência visual.

## Checklist vivo

- [x] Intake e leitura da governança.
- [x] Referência visual do dashboard gerada com a ferramenta integrada `image_gen`.
- [x] Gerar os demais assets com a referência visual: 37/37.
- [x] Converter e conferir dimensões, formato, tamanho e integridade.
- [x] Catálogo com miniaturas, manifesto e registro dos prompts finais.
- [x] Conferir o consumo das capas no Dashboard por testes do renderer e dos cartões.
- [x] Registrar evidências dos critérios CA-1 a CA-4.

## Coordenação

Há agentes trabalhando simultaneamente no core e no site. Alterações alheias foram preservadas. A inspeção inicial encontrou o Dashboard 2D usando `dashboard_gerar_svg_modulo()` e o 3D usando ícones via `getModuleIcon()`. O lote acrescenta o consumo das capas instaladas nesses trechos, mantendo o ícone quando o arquivo está ausente. Não houve sincronização, deploy nem alteração de banco do painel compartilhado.

## Direção visual

Vidro fosco e cerâmica arredondada sobre fundo azul profundo, acentos ciano e violeta, câmera isométrica, luz suave superior esquerda, composição central sem texto, números, marcas ou pessoas. Cada módulo terá assunto distinto e o mesmo tratamento visual.

## Evidências

- **CA-1:** galeria conjunta inspecionada: câmera isométrica, vidro fosco, azul profundo/ciano/violeta e luz suave consistentes nas 37 cenas. [Screenshot desktop](../validation/req227-evidence/catalog-1440.png).
- **CA-2:** 37 WebP, 1024 × 1024, conteúdo distinto, decodificação íntegra. Maior: **97.452 bytes**; total: **2.991.526 bytes**. Core: 14 / 1.158.130 bytes; site: 23 / 1.833.396 bytes. Todos atendem ao limite com qualidade WebP **90**, método 6, sem reduzir a resolução final.
- **CA-3:** assets em `gestor/assets/modulos/covers/` dos dois repositórios, com `manifest.json` (versão, dimensões, tamanho, SHA-256). `dashboard-covers.php` valida o identificador e confere a presença do arquivo; preserva o prefixo da URL e usa `filemtime` para invalidar cache. O renderer 2D mantém o slot SVG existente, com a imagem externa decorativa. O 3D recebe `thumbnail`, conserva proporção quadrada e reposiciona texto quando há capa. `dashboard.js` inclui a versão do módulo nas URLs dos scripts 3D. Versão do módulo: `1.0.23`.
- **CA-4:** [catálogo com miniaturas](../validation/req227-catalog.md), [prompts e medições](../validation/req227-covers.json) e [galeria do site](../../../conn2flow-site/sdd/validation/req102-catalog.md).

## Validação técnica

- `php sdd/validation/req227-covers-test.php`: **13/13**, cobrindo prefixo, arquivo ausente, identificadores inválidos/travessia de diretório, escape do atributo SVG, proporção, acessibilidade e invalidação do cache.
- `node sdd/validation/req227-cards-test.cjs`: **6/6**, exercitando `createCards()` real. A mesma verificação contra a versão anterior do script falhou na ausência de thumbnail com a opção global desativada, comprovando o antes/depois.
- `node sdd/validation/req227-covers-browser.cjs`: **37/37** imagens decodificadas em **1440 px e 390 px**, SVGs de 80 × 80, sem overflow horizontal, sem falha de requisição ou exceção JS. [JSON](../validation/req227-evidence/browser.json), [mobile](../validation/req227-evidence/catalog-390.png).
- Lint PHP e parse JS dos arquivos alterados passaram. `assets:minify` regenerou exclusivamente os dois scripts alterados; `assets:minify --verificar`: **0 derivados desatualizados**.
- Escopo da inspeção: fixture HTTP local isolada com arquivos reais e renderer PHP real. Não confere a rota autenticada publicada, a cena GPU do A-Frame nem um deploy no ambiente compartilhado. Isso não impede a entrega dos assets pedida nas duas requisições.
- Sem commit, push ou troca de branch na árvore compartilhada; arquivos disponíveis para revisão dos agentes responsáveis pela integração.
- Integridade dos links do relatório e dos dois catálogos: **75/75** destinos locais existentes.
