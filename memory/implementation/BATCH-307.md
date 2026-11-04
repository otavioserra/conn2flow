---
id: "BATCH-307"
title: "REQ-299 — CRUD de membros, abas de equipe e bloqueio por assinatura do titular"
status: "implemented-pending-homologation"
date: "2026-11-04"
author: "executor"
target_repo: "conn2flow"
request: "REQ-299"
topology: "solo"
autonomy: "autonomo_monitorado"
summary_short: "Gestão completa de membros e bloqueio herdado da assinatura local do titular."
summary_medium: "CRUD de membros e abas PT/EN validados em 1280/390 px. Sessões abertas bloqueadas por assinatura e reativadas após regularização; 17 verificações E2E e 700 testes JS aprovados."
---

# BATCH-307 — Equipe e assinatura (REQ-299)

Projeto `conn2flow`, raiz `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`, branch `main`, conforme instrução humana expressa. Escopo: REQ-299; integração com `lumix` somente por leitura e sincronização do Core no Lab.

## Checklist vivo

- [x] Edição de nome, e-mail/login e perfil, com validação do dono e do perfil.
- [x] Redefinição de senha com Argon2i e revogação das sessões web.
- [x] Exclusão física, confirmação e liberação imediata do assento.
- [x] Abas sem rolagem vertical e indicador inferior ativo, PT/EN.
- [x] Bloqueio local da assinatura do titular na hierarquia e em gestor_acesso.
- [x] PHPUnit focado: 67 testes da hierarquia e 22 de permissões/rota inicial, sem falhas.
- [x] Regressão e auto-revisão solo.
- [x] Sincronização e E2E no Lab.
- [x] Evidências; consolidação por commit com caminhos específicos autorizada pelo humano.

## Decisões de implementação

As novas ações usam a trava de equipe existente e transação para gravar o cadastro e revogar sessões. Toda mutação restringe o usuário por `id_usuarios_pai` no WHERE. O identificador estável do usuário e a organização são preservados na edição.

A assinatura é lida da tabela opcional `subscriptions` pelo `user_id` do titular, sem consulta ao gateway. Usa o registro mais recente não excluído; estados inativos/cancelados/suspensos/vencidos e falhas de pagamento bloqueiam somente os membros. Instalações sem a tabela e titulares sem assinatura continuam com a política anterior. O status do membro não é alterado e a reativação é reconhecida na verificação seguinte. Sessão web bloqueada leva ao login com orientação para procurar o titular.

## Coordenação

2026-11-04: humano informou agente concorrente em outro repositório. Não editar nem publicar fontes em andamento do `lumix`. Conferir branch, stage, alterações e locks antes de sincronizar/commitar. Não executar produção nem push sem necessidade.

## Evidências

Resultados verificáveis: [results.json](../validation/req299-evidence/results.json). Roteiros: [navegador](../validation/req299-browser.cjs), [bridge SSH](../validation/req299-lab.cjs), [sonda/fixtures PHP](../validation/req299-lab.php).

| Verificação | Resultado |
| --- | --- |
| `UsuarioHierarquiaReq297Test` (inclui os cenários REQ-299 em processo isolado, SQLite) | 67 testes aprovados: edição/login/perfil, senha com aspas, exclusão física/cota, IDOR entre equipes, perfil estrangeiro, transação/rollback, estados de assinatura e restauração |
| Permissões/rota inicial REQ-298/131 | 22 testes, 35 asserções aprovados |
| Hierarquia + RBAC real da API + derivados minificados | 89 testes, 382 asserções, sem falhas; 4 depreciações do PHPUnit |
| Conferência final: hierarquia + REQ-298/131 + API CRUD + minificados | 111 testes, 417 asserções, sem falhas ou depreciações nesta chamada |
| Suíte PHP completa | 1.992 testes, 21.568 asserções; 21 falhas CRLF já registradas no BATCH-306 (Dashboard REQ-247/251/258 e Manutencao REQ-210), 9 skips, 4 depreciações. Nenhuma falha nova; OpenSSL configurado |
| Vitest | 63 arquivos, 700 testes aprovados; happy-dom emite avisos de iframe, sem falha |
| Playwright em `https://snapphoton.local/` | 17/17 verificações, incluindo abas em 1280/390 px, CRUD via UI, senha conferida no hash, sessão invalidada, GET 405, POST sem CSRF 403, cancelada/suspensa/vencida e reativação |
| `assets:minify --verificar`, sintaxe PHP/JS e diff | Derivados coerentes, sem erro de sintaxe ou whitespace |

Publicação sequencial, sob a trava nativa do destino compartilhada pelos aliases: `assets:minify`, `project:sync-core snapphoton-local` (com Tailwind), `project:sync-db snapphoton-local --core-resources`, `css:rebuild --project=snapphoton-local --confirmar-remoto`. Todos concluíram com saída 0. Banco recebeu 20 variáveis PT/EN e o HTML/CSS de equipe. O recurso terminou na versão 1.6, módulo 1.0.2, tokens recalculados pelo pipeline.

Para `dist/`, o roteiro chama **AssetsPublishCommand nativo no Lab sobre a autoria já instalada** (raiz temporária com symlink removido no finally). Isso evita que `assets:publish --project` leia e publique assets ainda em edição no outro repositório. Não houve cópia manual de código nem deploy de fontes do `lumix`. Não alterado o modo do `.env`.

Fotografia de dados: antes/depois **7 usuários ativos e 7 perfis ativos**; zero fixtures de usuário restantes. O titular, os membros, perfis e assinatura sintéticos foram removidos no finally do E2E. As páginas do Core passaram de 262 para 264 porque duas páginas EN do Core estavam ausentes no destino; total de páginas de projeto permaneceu 136. Observou-se reclassificação de duas páginas entre donos de projeto durante a janela concorrente; não houve exclusão de páginas de projeto. Trava do Lab liberada ao fim.

Capturas examinadas: [abas mobile](../validation/req299-evidence/390-membros.png), [perfis mobile](../validation/req299-evidence/390-perfis.png), [edição](../validation/req299-evidence/editar-membro.png), [senha](../validation/req299-evidence/redefinir-senha.png), [confirmação de exclusão](../validation/req299-evidence/excluir-confirmacao.png), [bloqueio](../validation/req299-evidence/bloqueio-cancelled.png).

## Defeitos encontrados e corrigidos antes da entrega

- A checagem nova em `gestor_acesso` expôs uma dependência ausente no arnês de RBAC da API: passou a extrair a função real de hierarquia e a fornecer conta própria no SQLite.
- `c2fc-campo { display:flex }` prevalecia sobre `hidden` no campo de senha. Corrigido com `hidden!` compilado pelo Tailwind; o navegador agora exige que os campos alheios à ação estejam efetivamente invisíveis.
- Formulário reaproveitado dizia “Criar acesso” também na edição: os rótulos mudam para “Salvar alterações”/“Nova senha” conforme a ação.
- Todos os endpoints de equipe agora exigem POST; o fluxo normal mantém o CSRF do Core. GET de exclusão não executa a mutação.

## Auto-revisão e limites

Revisão solo: sem finding impeditivo remanescente. Propriedade do membro validada no SELECT e no WHERE de escrita; perfil limitado à equipe; nome/e-mail escapados inclusive nos atributos HTML; hash compatível com o login; cota recalculada após remoção; textos PT/EN via variáveis; versão/derivados coerentes. Nenhum dado de sessão, cookie ou segredo nas evidências versionadas.

Não exercitado no E2E: emissão de OAuth/PAT, gateway real e login novo com CAPTCHA/2FA. As barreiras unitárias existentes cobrem esses pontos de autenticação; o E2E prova sessão web aberta bloqueada com mensagem instrutiva. A mensagem também é usada no login por senha correta quando a hierarquia recusa a conta. Sem tabela/registro de assinatura, preserva-se o comportamento anterior; este lote não cria obrigações de assinatura para contas próprias.

Não houve edição de normativos, skills ou poda de memória (arquivo de execução com 33.124 bytes). `CURRENT.md`/REQ preservados como intake do Arquiteto. A pasta de implementação já ultrapassava dez lotes antes do trabalho; não arquivados artefatos alheios para satisfazer o teto neste lote.

## Handoff humano

Projeto `conn2flow`, raiz `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`, REQ-299, BATCH-307, branch `main`, topologia `solo`, autonomia `autonomo_monitorado`. Implementação, build, testes focados, E2E e auto-revisão realizados. Pendência: homologação humana; nenhuma publicação em produção nem push realizado.
