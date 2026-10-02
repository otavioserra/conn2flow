# BATCH-200 — Página inicial e layout por perfil (REQ-196)

**Repositório**: `conn2flow` — `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`  
**Status**: `implemented-validated`  
**Intake**: [req-196](../../human-requests/archive/req-196.md)

## Live Todo List

- [x] Criar migração de `usuarios_perfis.pagina_inicial` e `paginas.layouts_users_profiles`.
- [x] Organizar `usuarios-perfis` em abas e implementar autocomplete AJAX de páginas ativas, com busca limitada no servidor.
- [x] Gravar e recuperar a rota inicial do perfil e usar a rota ativa após login, com fallback `dashboard/`.
- [x] Implementar toggle, repetidor e gravação validada em `admin-paginas` e `publisher-pages`, incluindo clone.
- [x] Usar componentes de recursos bilíngues para HTML, `gestor_incluir_biblioteca` para a biblioteca e `interface_formulario_campos` para os selects Fomantic-UI de Layout e Perfil.
- [x] Aplicar o layout do perfil no roteador e preservar o layout padrão para visitantes ou perfis sem mapeamento.
- [x] Serializar e recuperar o mapeamento em `PaginasData.json` e manifests de páginas.
- [x] Executar sincronização, minificação, PHPUnit, Vitest e verificações de sintaxe/diff.
- [x] Aplicar e reverter a migração Phinx em MariaDB isolado, preservando registros legados.
- [x] Publicar no ambiente de teste `conn2flow-site-local`, inspecionar os formulários no navegador autenticado e confirmar gravação no banco.
- [x] Remover os registros temporários criados na homologação.

## Contrato implementado

`usuarios-perfis` grava em `pagina_inicial` o `caminho` de uma página ativa do idioma atual. O campo de busca solicita ao backend resultados após dois caracteres, limita a busca a 20 páginas e não carrega todas as páginas no formulário. O login consulta novamente a rota e usa `dashboard/` quando ela não existe ou está inativa; um destino local salvo na sessão tem precedência.

O mapeamento é um objeto JSON cujas chaves são IDs de layout e cujos valores são IDs de perfil. `layout_id` continua sendo o fallback. Ao salvar, o servidor só aceita pares ativos do idioma atual. Desligar o toggle grava `NULL`. O roteador usa o layout mapeado apenas quando o perfil autenticado corresponde a um par.

O compilador só emite `layouts_users_profiles` quando o manifest declara a propriedade. Assim, sincronizar manifests legados não apaga mapeamentos feitos no Gestor. A recuperação converte o JSON SQL em objeto e omite valores nulos.

Os componentes `layout-profile-mapping` e `layout-profile-row` pertencem aos recursos de `admin-paginas` e `publisher-pages` em `pt-br` e `en`. O autocomplete também usa um componente bilíngue de `usuarios-perfis`. A biblioteca `paginas-layouts-perfis` está registrada em `gestor/config.php` e os módulos a carregam com `gestor_incluir_biblioteca`. Os selects de Layout e Perfil são gerados por `interface_formulario_campos` e inicializados com Fomantic-UI.

## Evidências

- `php cli/c2f.php resources:sync`: exit 0, 2.924 recursos compilados, incluindo os dez componentes novos. Publicação opcional em `dist/` avisou que `PUBLIC_PATH` não está definido.
- `php cli/c2f.php assets:minify --verificar`: exit 0, nenhum derivado JS desatualizado. `git diff --check`: exit 0.
- PHPUnit completo: 1.289 testes, 10.152 asserções, 4 ignorados, exit 0. A suíte emitiu 4 depreciações e 2 depreciações do PHPUnit. `Req196LayoutPorPerfilTest`: 6 testes, 16 asserções.
- Vitest completo: 34 arquivos, 455 testes, exit 0. Inclui teste do autocomplete AJAX e testes dos repetidores dos dois CRUDs.
- Migração Phinx 0.16.10 aplicada e revertida no MariaDB 11.8.8 isolado `conn2flow_req196_test`: `paginas.layouts_users_profiles` era `LONGTEXT NULL`, `usuarios_perfis.pagina_inicial` era `VARCHAR NULL`; registros legados permaneceram íntegros. O banco descartável foi removido.
- Teste PHP em tabelas mínimas de outro banco descartável: 11 verificações passaram para validação dos pares Layout/Perfil, destino inicial e fallbacks. O banco foi removido.
- `project:update-all conn2flow-site-local`: exit 0 em oito estágios, incluindo sincronização do Core, recursos, CSS e minificação. O transporte cwRsync foi ajustado para converter caminhos nativos `C:/...` em `/cygdrive/c/...`; isso desbloqueou o pipeline no Windows.
- Playwright autenticado em `https://conn2flow.local/`: `usuarios-perfis/adicionar/`, `admin-paginas/adicionar/` e `publisher-pages/adicionar/` retornaram HTTP 200 sem erros de console. A aba Acesso e o autocomplete apareceram; busca por `dash` trouxe oito sugestões e selecionou `dashboard/`. O formulário não continha um select com centenas de páginas.
- Playwright criou um perfil temporário pela interface; SQL confirmou `pagina_inicial=dashboard/`; a edição recarregou o valor e o rótulo correto. O registro foi removido.
- Playwright acionou os toggles de ambos os CRUDs, verificou selects Fomantic-UI com 17 layouts e 7 perfis, adicionou/removeu linhas e gravou uma página temporária por CRUD. SQL confirmou `{"layout-administrativo-do-gestor-3d":"administradores"}` em `layouts_users_profiles` do `publisher-pages`; no `admin-paginas`, desligar o toggle na edição gravou `NULL`.
- Para uma página temporária aberta a visitantes, a rota autenticada usou o layout administrativo 3D e a rota anônima usou o layout padrão. Antes da limpeza, SQL confirmou ausência de vínculos das páginas em `paginas_301`/`social_posts` e do perfil em `usuarios`. Uma transação removeu os dois registros de página, o vínculo `publisher_pages` e o perfil; a contagem final das páginas temporárias foi zero.

## Limites

O desvio pós-login foi exercitado por testes PHP com banco isolado nos casos de rota válida e fallback. Não foi feito login interativo com senha de um usuário temporário no Lab; a sessão administrativa de teste foi obtida por `auth:cookie`. Nenhum deploy de produção ou alteração no `project-test` foi feito após a orientação do usuário.

O arquivamento SDD da regra dos 10 moveu `req-186.md` e `BATCH-190.md` para `archive/` e reescreveu sete links. A rotina apontou seis links relativos antigos já quebrados em backlog e arquivos históricos, sem relação com o BATCH-200.
