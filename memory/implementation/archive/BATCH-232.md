# BATCH-232 — Módulos de administração em Tailwind (req-223)

- **Status:** `in-progress`
- **Requisição:** [req-223](../../human-requests/archive/req-223.md)
- **Worktree:** `conn2flow-req223`, branch `feat/req-223`
- **Restrições desta execução:** sem CSS sync/build, deploy, atualização real, gravação de `.env`, commit ou push.

## Escopo

Migrar os módulos administrativos listados na req-223, incluindo o widget compartilhado `configuracao_administracao` e as páginas `variables` e `modulos-variaveis`. Preservar componentes legados e os contratos de formulário/AJAX existentes.

## Progresso

- [x] `admin-arquivos`, `admin-categorias`, `admin-ia`, `admin-modos-ia`, `admin-prompts-ia`, `admin-plugins` e `admin-atualizacoes` migrados.
- [x] `admin-environment` migrado; teste focalizado registrado na continuação anterior.
- [x] Variantes pt-br/en do widget e dos campos geradas por transformação Node; validação estrutural executada.
- [x] Adaptar o JS compartilhado do configurador sem plugins Fomantic/CodeMirror no caminho Tailwind.
- [x] Migrar `variables` e `modulos-variaveis`, incluindo dependências e templates pt-br/en.
- [x] Executar testes estáticos/focados disponíveis e registrar resultados neste batch e no checklist.
- [x] Adicionar roteiro de homologação humana em `conn2flow-site/sdd/PENDENCIAS-HUMANAS.md`.
- [ ] CSS sync/build e inspeção visual no Lab/390 px; não executados por restrição explícita desta execução.

## Validação

- O teste focalizado de `admin-environment` passou: 1 teste, 7 asserções.
- `php -l` passou em `gestor/bibliotecas/configuracao.php`.
- `node --check` passou nos três fontes e nos três `.min.js`; os minificados conferem com Terser e o `minify-manifest.json` foi atualizado somente para esses arquivos.
- Transformação/verificação Node confirmou os quatro templates sem `class="ui "`, manifests JSON válidos, dependências globais existentes e tokens determinísticos de assets coerentes.
- Vitest completo: 41 arquivos, 507/507 testes; teste focado dos selects: 5/5.
- PHPUnit focado (`AdminModulesTailwindReq223Test|VariablesTelaEEditorTextoTest`): 13/13 testes, 463 asserções; 3 depreciações.
- PHPUnit completo: 1.523 testes, 14.782 asserções; 4 erros (OpenSSL sem configuração e três testes Stripe do ambiente) e 1 falha de comparação LF/CRLF em `CssRegeneracaoTest::testCssRebuildReconheceCheckoutCoreEInstalacaoPlana`.
- CSS sync/build e validação visual não foram executados por restrição explícita. Checksums de recursos de página permanecem para atualização pelo fluxo oficial.

## Pendências

Não declarar o batch concluído até a sincronização/build autorizada, a inspeção visual e a validação manual registradas em `conn2flow-site/sdd/PENDENCIAS-HUMANAS.md`; a suíte PHPUnit completa também mantém as falhas de ambiente descritas acima.
