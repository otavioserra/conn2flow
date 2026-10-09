# BATCH-234 — Conferência final da UI Tailwind (req-225)

**Projeto:** `conn2flow` em `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`.
**Site auditado:** `conn2flow-site` em `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-site`.
**Data:** 2026-10-04. **Status:** `in-progress`, preparação executada; ajustes finais aguardam as frentes dependentes.
**Intake:** [req-225](../../human-requests/archive/req-225.md). Autonomia: `autonomo_monitorado`.

## Dependências verificadas

A requisição determina execução depois da req-221 a req-224 e REQ-100/101 do site. Na leitura inicial, CURRENT do core registra req-221/224 em revisão, req-222 em andamento e req-223 pronta para intake. CURRENT do site e intakes REQ-100/101 registram ambos prontos para intake. Não foram encontrados relatórios BATCH-231/232 nem BATCH-094/095 do site. Há alterações não commitadas nos dois repositórios, inclusive em recursos dessas frentes. Isso permite um levantamento preliminar, mas não comprova entrega nem integração.

Nenhum arquivo de produto, recurso ou banco foi alterado nesta etapa. Nenhum pipeline, deploy, commit ou push foi executado. A implementação corretiva em branch/worktree isolada e a validação do Lab ficam para depois da integração das dependências. Não foi solicitado ao humano que execute a validação técnica.

## Checklist vivo

- [x] Ler briefing, baseline e workflow; conferir estado das dependências.
- [x] Criar levantamento reproduzível dos módulos com `layout-administrativo-tailwind`, em ambos os idiomas e repositórios.
- [x] Criar teste PHPUnit contra marcação Fomantic iniciada em `ui` e `title` em botão; provar detecção com fixtures e fontes atuais.
- [ ] Regerar o inventário após a integração das frentes e conferir controles/componentes gerados em runtime.
- [ ] Ajustar divergências, preservar ganchos/nomes/CSRF e incrementar versões.
- [ ] Obter PHPUnit verde; validar JS conforme as mudanças.
- [ ] Pipeline oficial sequencial no Lab, com trava compartilhada.
- [ ] Navegador em todas as páginas: documento sem Fomantic, 390 px sem overflow e sem erro de script.
- [ ] Inventário sem pendências e revisão final; roteiro humano consolidado no arquivão do site.

## Evidências da preparação

- [Gerador](../../validation/req225-inventory.php), [inventário legível](../../validation/req225-inventory.md) e [detalhes JSON](../../validation/archive/req225-inventory.json).
- `php sdd/validation/req225-inventory.php`: 236 linhas página/idioma, core 108 e site 128; 132 linhas com apontamentos estáticos (core 4, site 128). São dados das árvores locais, incluindo trabalho sem commit, não do SQL publicado.
- O levantamento cobre metadados de módulos. Os `gestor/resources/{pt-br,en}/pages.json` dos dois repositórios foram conferidos e não contêm páginas nesse layout neste snapshot.
- Core: 2 linhas com `title` em botão (`perfil-usuario/Area-restrita`, en/pt-br); os outros apontamentos são bundle ausente. Site: 22 linhas com marcação proibida; 493 ocorrências. Contagem inclui os dois idiomas, não significa 493 telas distintas.
- `php -l` no gerador e teste: ambos sem erro.
- `php vendor/bin/phpunit --configuration phpunit.xml --filter PainelTailwindDriftReq225Test`: **3 testes, 12 asserções, 2 falhas, 3 depreciações PHPUnit**. Detector sintético passou; auditorias core/site falharam pelas fontes indicadas. Resultado vermelho é evidência de deriva atual, não aceite CA-3 concluído. Testes exclusivamente de arquivos, sem acesso SQL; executados no PHP Windows 8.5.8. A suíte completa no Linux fica para a fase corretiva.

## Limites e revisão da preparação

As contagens de `c2fc-*` são indícios de autoria. Não aprovam foco, hover, cores, acessibilidade, diálogos JS, seletor gerado pelo PHP, carregamento de assets ou persistência. Todas as páginas permanecem com runtime pendente. Listagem `listar` sem HTML próprio é geração do `interface`, não arquivo perdido. DEC-132 permite ganchos `ui` depois da classe visual; o teste guarda a deriva `class="ui …"`, sem remover esses ganchos.

O teste não usa baseline/allowlist de falhas: continuará vermelho até a correção. O site é auditado quando disponível como irmão ou via `CONN2FLOW_SITE_ROOT`; no CI exclusivo do core o caso do site é explicitamente pulado. Markup de componentes e HTML gerado requer inventário adicional e browser; não está aprovado pelo detector das páginas.

Aceites CA-1/2/3/4 permanecem abertos. A preparação não deve ser consolidada como lote concluído nem publicada enquanto o teste estiver vermelho.
