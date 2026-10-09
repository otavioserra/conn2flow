# BATCH-147 — Cadeia de recursos no galleries, saneamento da tela de variáveis e alvo do css:rebuild

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-144.md` + retorno de homologação do req-141 e do req-143
- **Data de abertura**: 2026-08-29
- **Classificação**: defeito funcional, risco de perda de dados, arquitetura de assets

---

## 1. Galleries — a correção do BATCH-144 não chegava à tela (req-141)

O BATCH-144 acrescentou `pointer-events-none cursor-default` aos quatro templates do core e nada
mudou na home. Duas causas independentes:

**(a) A galeria não renderiza a partir do template.** Ela guarda em `galleries.html` uma **cópia**
do template, tirada quando o operador escolheu o modelo no painel (`galleries.php:977-1002`). A
cópia congela ali e recebe `user_modified = 1` — a flag que, por design, bloqueia o sync de recursos.

**(b) O template em uso é do PROJETO.** A home do `transformamp` usa `galeria-home`, template do
repositório do projeto, que o lote anterior nunca tocou.

Medição que fechou o diagnóstico:

| Registro | `<a href` | marcador `link-disabled-css` |
| --- | --- | --- |
| `templates.galleries-slider` (core) | pos. 612 | pos. 2611 ✅ |
| `templates.galeria-home` (projeto) | pos. 450 | **0** ❌ |
| `galleries.galeria-home` (cópia congelada) | pos. 446 | **0** ❌ |

### Correção: cadeia de recursos

`galleries_widget_css_link_desabilitado()` percorre, nesta ordem:

1. o HTML da própria galeria — permite override por galeria;
2. o template de origem (`fields_schema.template_id`) — **alcança as cópias congeladas**, de modo
   que corrigir o recurso baste, sem exigir que o operador reescolha o modelo em cada galeria
   publicada;
3. `galleries-estados` — novo recurso do core, com `target` próprio para não poluir o dropdown de
   modelos, como rede de segurança para o template de projeto que esquecer o marcador.

Em nenhum degrau a classe nasce em PHP: os três são recursos, visíveis ao compilador Tailwind.

### O segundo meio-caminho

Com a cadeia no lugar, as seis âncoras receberam as classes — e `.cursor-default` **não tinha regra
nenhuma** na página. O HTML do widget só existe em runtime e nunca chega ao compilador. A mesma
falha silenciosa, um degrau adiante.

O recurso que fornece as classes passa a fornecer também o CSS delas (`css_compiled`, ou
`css_precompiled` como alternativa). Medido depois: 6 âncoras com as classes **e**
`.cursor-default{cursor:default}` presente.

## 2. Variables — dois botões destrutivos (req-144)

Detalhado em `sdd/human-requests/req-144.md`. Os quatro botões do scaffold saíram; a tela segue 200
com o card de inclusão de variável intacto.

## 3. Tipo `tinymce` → `editor-texto` (req-144)

Renomeado em PHP, componente, JS e rótulos, com migração Phinx
(`20260829100000_rename_variavel_tipo_tinymce_para_editor_texto.php`).

`configuracao_campo_tipo()` mantém `tinymce` como **alias de leitura**: entre o deploy do código e a
aplicação da migração o banco ainda diz `tinymce`, e sem o alias `$campo[$tipo]` erra a chave e o
campo **some da tela** — pior que um erro visível.

Removido `gestor/assets/tinymce/langs/pt_BR.js` (16 KB, órfão) e a entrada em `asset-versions.json`.

## 4. CLI — `project:update-all` regenerava a base errada

O projeto chega como **argumento**; o `css:rebuild` o lê como **opção** (`--project=`). Repassando o
mesmo `$input`, a etapa caía no default — o ambiente de teste do SISTEMA.

Falhava reportando **sucesso plausível**: `analisados: 235 | regenerados: 0 | já coerentes: 235`,
tendo lido `conn2flow` em vez de `transformamp`. Corrigido com `Input` próprio e repasse de
`--confirmar-remoto`.

## 5. Validação

| Evidência | Resultado |
| --- | --- |
| PHPUnit | 902/902 (16 novos) |
| Vitest | 378/378 |
| Home local — âncoras com as classes | 6 |
| Home local — regra `.cursor-default` | presente |
| `variables/?id=usuarios-perfis` | HTTP 200, 0 links para `adicionar`/`editar`/`status`/`excluir` |
| Rótulo do tipo no banco | 1 por idioma, 0 órfãos de `tinymce` |
| Estágio 6/6 do pipeline de projeto | grava em `transformamp`, `local: true` |

## 6. Pendências

- Homologação visual do operador.
- Deploy em produção (a home publicada ainda tem o comportamento antigo).
