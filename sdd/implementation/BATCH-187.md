# BATCH-187: Documentação do Core — Conceitos, Novidades e Limpeza do Legado (onda 5)

Execução da [req-183](../human-requests/req-183.md), por um agente em outra infraestrutura. Ondas irmãs: [BATCH-186](BATCH-186.md) (onda 4) e [BATCH-188](BATCH-188.md) (onda 6).

**Status**: `complete` (2026-09-26).

## Progresso

| Doc (pt-br + en) | Legado removido | Achados do código |
|---|---|---|
| `concepts/architecture.md` | gestor detalhado, módulos overview/detalhado e sistema de conhecimento | `gestor.php` roteia e monta a página; módulos e recursos são compilados para SQL, e a interface CRUD é opcional. |
| `concepts/widgets.md` | — | `widgets_get()` só procura `gestor/modulos/`; ID simples não consulta banco; retorno vazio deixa marcador original; AJAX tenta callback `_ajax`. |
| `concepts/multilingual.md` | dois documentos do sistema multilíngue | Prefixo de idioma na URL precede cookie válido; página é filtrada por `language`; `lang.php` usa dicionário distinto, sem arquivo padrão no core. |
| `concepts/plugins.md` | arquitetura de plugins | Instalador exige `id/name/version`; checksum SHA-256 só autentica integridade quando há arquivo externo; colisão de id de módulo afeta hooks e widgets de plugin não são encontrados pelo renderizador atual. |
| `concepts/projects.md` | sistema de projetos e proteção de banco | `project:update-all` inclui banco, recursos e CSS derivado; registros de projeto e campos editados no painel têm regras próprias; deploy não atualiza `sitemap.xml`. |
| `concepts/css-and-tailwind.md` | framework CSS | `css`, `css_precompiled` e `css_compiled` têm papéis distintos; `css_source_hash` marca procedência; dependência escolhida em runtime precisa entrar em `tailwind_dependencies`. |
| `concepts/admin-interface.md` | preview modals, menu responsivo e arquitetura v2 en | Menu muda por tipo da tela; modais de layout/componente usam iframe; alertas também aparecem em páginas públicas e seus estilos são dependência do sistema. |
| `concepts/system-updates.md` | atualizações do sistema | Bootstrap troca o próprio atualizador antes de aplicar; fluxo web tem quatro etapas; cinco pastas são protegidas; `--only-files` e `--only-db` são excludentes. |
| `concepts/ai-security.md` | estratégia de segurança pública de IA | Filtro de listagem de prompts não autoriza escrita; aponta as onze classes de achados da req-181 como propostas, sem detalhes de exploração. |
| `concepts/vision.md` | `visao/*` / `vision/*` | API, CLI e recursos são capacidades implementadas; gateway de IA e clientes complementares foram mantidos como direção de produto, não como contrato do core. |
| `whats-new/index.md`, `2.10.md`, `2.9.md` e seis versões destacadas | changelog history, `changelogs/*` e README da raiz de docs | Notas reescritas do `CHANGELOG.md`; páginas próprias para 2.10.13, 2.10.12, 2.10.11, 2.10.0, 2.9.51 e 2.9.39. |
| Limpeza de índices legados | `bibliotecas/README.md`, `bibliotecas/ROADMAP.md`, `libraries/README.md`, `libraries/ROADMAP.md` | `ai-workspace/{pt-br,en}/README.md` agora aponta o estado real da migração. |

## Commits

- `047ccc78` — `docs(core): concepts and release notes (BATCH-187 / req-183)`; 38 páginas novas em pares pt-br/en, 51 arquivos legados removidos, READMEs revisados. Enviado a `origin/main`.
- O commit de fechamento deste relatório registra o hash acima e a validação final.

## Validação

- `docs:audit --json`: todos os 38 arquivos novos de `concepts/` e `whats-new/` com score 0; 1 erro, 3 warnings e `legacy=2` em cada idioma no acervo total. As quatro entradas legadas remanescentes são `BIBLIOTECA-FORMULARIO`, `BIBLIOTECA-USUARIO` e os pares en, da onda 2; o erro é o arquivo `formulario.md` pt-br ainda sem par en. Fora do escopo de edição da req-183.
- Verificação local: todos os links `.md` das novas páginas resolvem; as fontes declaradas nos 38 arquivos novos existem; `git diff --check` sem erros.
- Verificação de links do acervo inteiro: 267 Markdown examinados, 10 links quebrados, todos nos quatro arquivos legados `BIBLIOTECA-{FORMULARIO,USUARIO}` / `LIBRARY-{FORM,USER}` que a onda 2 ainda deve substituir. Nenhum link quebrado foi introduzido nas páginas desta onda.
- Publicação e pipeline não executados, por regra da req-183; pertencem à req-184.

## Fechamento (2026-09-26)

- Com a onda 2 concluída (BATCH-184), `legacy` = 0 nos dois idiomas; o critério global da req-183 foi atingido. Publicado no Lab junto com o BATCH-188.
