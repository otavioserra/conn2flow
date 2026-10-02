# BATCH-202: Atualização segura, fase 2a — manifesto por camada, precedência e choques (req-198)

Execução da [req-198](../../human-requests/archive/req-198.md), itens A e B.1 do [BL-028](../../backlog/BL-028-atualizacao-segura-choques-backup-rollback.md).

**Status**: `complete` (implementado e validado no tenant isolado; homologação humana pendente).
**Worktree**: `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-bl028`, branch `feat/req-197`.
**Ambiente de teste isolado**: `project-test` (`local: true`), usuário HestiaCP `c2ftest`, domínio `c2f-teste.local`, instalação em `/home/c2ftest/web/c2f-teste.local/conn2flow-gestor`, no SSH Lab. Nenhum outro projeto usa esse tenant.
- **Criação manual:** o host-manager do site recusou o SSL `.local` com `HESTIACP_DRIVER=ssh` (conta `hm_6abd2307d1409` ficou `failed_provisioning`, usuário Hestia desfeito). O tenant foi criado à mão: `v-add-user`, `v-add-web-domain`, templates `conn2flow`, SSL autoassinado, `v-add-database`, instalador v2.1.2 com o runner headless.
- **Acesso:** o vhost segue a convenção do Lab (`listen 8088` / `listen 8443 ssl`, sem IP); responde em `https://c2f-teste.local:8443/` a partir do Lab e do Windows (resolvendo para `127.0.0.1`). O domínio não está no Caddy do Lab (80/443); para abrir no navegador sem porta, falta incluí-lo no Caddyfile e no certificado da CA local (ver batch-044 do site).

## Desenho

| Peça | O quê |
|---|---|
| `gestor/bibliotecas/instalacao-manifesto.php` (nova, pura) | manifesto `installation/manifests/<camada>.json` (`caminho → sha256`); `instalacao_planejar` (escrever, preservar, retirar, restaurar, originais, retirar com choque); `instalacao_aplicar` (modo `copiar` ou `preparar`); diff textual; choques pendentes em JSON |
| Precedência | `projeto` > `plugin:<id>` > `core`. A atualização do core não sobrescreve um arquivo sobreposto: a versão nova vai para `backups/overrides/<versão>/` e vira choque `sobreposto`. Arquivo mudado no servidor sem que nenhuma camada tenha entregue aquele conteúdo: choque `editado` |
| Retiradas | o que a camada entregou antes e não entrega mais sai, se estiver intacto; se estiver editado, vira choque `retirado-editado`; se uma camada superior sobrepunha, ela passa a ser a dona |
| Originais | quando o projeto sobrescreve um arquivo do core, a versão do core fica em `installation/originals/`; se o projeto deixar de sobrepor, ela volta (`restaurar`) |
| Primeira entrega | sem manifesto anterior da camada: escreve tudo (comportamento antigo) e grava a linha de base |
| Atualização do sistema | `aplicarManifestoCore()` roda antes de mover o staging (CLI e web): tira do staging o que é preservado, retira o que saiu e grava o manifesto do core; `installation/` passa a ser pasta protegida |
| Deploy por API | `api_project_aplicar_arquivos()` no lugar do `api_copy_directory` para a camada `projeto`. O pacote traz `.c2f-manifest-projeto.json` com a lista completa e os hashes, gerado por `project-file-manifest.php` no `deploy-project-v2.sh`, também no gitDeploy. As pastas fora do manifesto (`contents/`…) são copiadas como antes |
| Choques | JSON pendente em `installation/choques/`; depois da etapa de banco vai para a tabela `atualizacoes_choques` (migração `20260930210000`). Aba "Choques das entregas" em `admin-atualizacoes`, com o diff em `detalhe/?choque=<id>` |
| Pipeline rsync (Lab) | fora deste lote. O `sync-core` roda antes dos arquivos do projeto, e o rsync do projeto reaplica as sobreposições; o estado final fica consistente. O manifesto vale para a atualização do sistema e para o deploy por API, que são os caminhos de produção |

## Validação

### Automatizada
- `InstalacaoManifestoTest` (12) e `AtualizacoesManifestoIntegracaoTest` (6), com os testes do BATCH-201: **33 testes, 150 asserções, verdes**.
- **Suíte completa:** as falhas que aparecem são de ambiente da worktree: `CoreHelpersTest` (RSA sem `openssl.cnf`), três `Stripe*Test` e `CssRegeneracaoTest`, que compara texto de um arquivo do CLI com CRLF da worktree e não é tocado pelo lote.
- `docs:audit`: 0 erros; os 26 avisos são anteriores ao lote.

### Tenant isolado (atualização do sistema real, `--local-artifact`)
Artefatos montados da worktree com um marcador (`c2f-artefato-teste.txt`); a v2 muda `fpdf184/install.txt` e `fpdf184/license.txt` e retira `PHPMailer/SECURITY.md`.

| Verificação | Resultado |
|---|---|
| Primeira entrega com o manifesto | linha de base do core gravada em `installation/manifests/core.json` |
| Projeto sobrepõe `install.txt` (manifesto `projeto.json`) e o servidor edita `license.txt`; entra a v2 | "1 a escrever, 2 preservado(s), 1 retirado(s)"; os dois arquivos continuam como estavam; as versões novas em `backups/overrides/local-artifact/…`; `SECURITY.md` saiu |
| Tabela `atualizacoes_choques` | 2 linhas: `sobreposto` (dono `projeto`) e `editado`, com o diff |
| Mesma situação na atualização seguinte | "0 (mais 4 já pendente(s))": choque igual pendente não vira linha nova |
| Tela `admin-atualizacoes` (Playwright, admin) | seção "Choques das entregas" com as colunas e os rótulos; "Ver diff" abre `detalhe/?choque=6` com o diff e o caminho da versão nova; sem erro de JS |
| Migração `20260930210000` | aplicada pela etapa de banco da atualização; variáveis `updates-clash-*` (+36) sincronizadas |

### Achados corrigidos durante a homologação
- **CLI sem banco:** o `$_BANCO` ficava vazio no CLI (falha anterior ao lote), e a atualização parava em "Configurações de banco não definidas". `atualizacoes_cli_carregar_config()` carrega `config.php` e `banco.php` com o domínio de `--domain`.
- **Linha `running` presa:** o processo do bootstrap abria uma linha em `atualizacoes_execucoes` e nunca a fechava; os `catch` do CLI também não fechavam. Agora a linha é só do processo que faz o trabalho (é o id dela que nomeia o snapshot), e erro fecha com `error`.
- **Biblioteca da versão anterior:** o atualizador novo carregava a `instalacao-manifesto.php` instalada antes da do pacote e caía em fatal ao chamar uma função nova. O pacote vem primeiro.
- **Choque repetido:** cada atualização regravava o mesmo choque pendente; `instalacao_choque_pendente_filtro()` evita, no atualizador e na API.

### Pendências
- **Deploy por API** (`api_project_aplicar_arquivos`): coberto por testes da biblioteca (modo `copiar`) e pela mesma lógica; a execução real depende de um token OAuth do tenant e fica para a homologação humana.
- **Pipeline rsync do Lab:** fora do lote (ver Desenho).
- Homologação humana.
