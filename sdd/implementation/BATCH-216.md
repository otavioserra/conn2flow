# BATCH-216: módulos `presentations` e `cookie-consent` (req-208)

Execução da [req-208](../human-requests/req-208.md).

**Status**: `in-review` (validado no Lab com o `conn2flow-site-local`; revisão humana pendente).

## O que mudou

| Onde | Mudança |
|---|---|
| `gestor/modulos/presentations/` (novo) | CRUD, widget, controlador público, modelo `presentations-deck`, modo de IA, páginas e variáveis nos dois idiomas |
| `gestor/modulos/cookie-consent/` (novo) | CRUD, widget, controlador público, modelos `cookie-consent-card` e `cookie-consent-card-light`, modo de IA, páginas e variáveis nos dois idiomas |
| `gestor/db/migrations/20261002110000_create_presentations_and_cookie_consent_tables.php` | Tabelas `presentations` e `cookie_consent`, na estrutura de `galleries`, já com `project` |
| `gestor/resources/{pt-br,en}/modules.json`, `user_profiles_modules.json` | Registro dos módulos e permissão do perfil administrador |
| `ai-workspace/{pt-br,en}/docs/reference/modules/{presentations,cookie-consent}.md` | Documentação |
| `tests/Unit/PHP/PresentationsAndCookieConsentReq208Test.php` | 12 testes |
| `tests/Unit/PHP/Req203LanguageAgnosticResourcesTest.php` | Contagem de permissões: 37 para 39 |

## Decisões

- **Base no `forms`, não no `galleries`.** O `galleries` é quase todo seletor de imagens. O CRUD dos dois módulos segue o `forms` e usa o editor HTML com alvo próprio, sem alterar `html-editor.php` (como o módulo de planos do site já fazia).
- **CRUD compartilhado e declarativo.** As duas telas têm o mesmo JavaScript: campo com `data-schema-key` grava no `fields_schema` pelo caminho indicado; tabela com `data-schema-list` edita uma lista de objetos. Opção nova entra no HTML da página, sem código.
- **Slide é marcação, não registro.** O autor escreve `<section data-slide>` no editor; o widget conta as seções. Uma lista de slides no `fields_schema` tiraria o conteúdo do editor visual e do CSS compilado.
- **O que depende da contagem sai do servidor; o comportamento, do controlador público.** A lógica do layout feito à mão foi portada por deck: sem função global, sem `onclick`, mais de uma apresentação por página.
- **Aviso de cookies com CSS próprio.** Classes `c2f-cc-` e cores em variáveis: o aviso tem de aparecer igual em qualquer layout, inclusive sem Tailwind.
- **Textos padrão em variáveis do módulo.** Campo de texto vazio no registro usa o padrão do idioma.
- **Decisão só no navegador.** O cookie `c2f_consent` guarda versão, instante e um booleano por categoria. Registro no servidor ficou fora.

## Defeitos achados na validação

- Comentário do modelo que cita `<section data-slide>` era contado como slide: a contagem passou a ignorar comentários.
- Mudar só o `#slide-N` no endereço não trocava de slide: o controlador passou a ouvir `hashchange`.

## Validação

- `PresentationsAndCookieConsentReq208Test`: 12 testes, 212 asserções.
- Suíte completa no Lab: 1400 testes; falha só `ProjectSshDeployReq034Test::testBibliotecaDeTransporteExisteEEhSintaticamenteValida`, anterior a este lote (fim de linha do script na árvore do Windows).
- `resources:sync`, `assets:minify` e `project:update-all conn2flow-site-local`: saída 0; migração aplicada.
- Navegador no Lab (`sdd/validation/website/req090-cookies-slides-legal.cjs`, no `conn2flow-site`): 114/114, nos dois idiomas, a 1280 e 390 px, incluindo listagem, edição, pré-visualização e adição dos dois módulos.
- `docs:audit`: 0 erros.

## Limites

- A gravação pelo formulário do painel (salvar, clonar, excluir) não foi exercitada no navegador: as telas abrem, carregam o registro e a pré-visualização funciona.
- O aviso só bloqueia script marcado com `type="text/plain"` e `data-cookie-category`.
- Pinça e arrasto em tela cheia foram portados do original e não foram testados em aparelho com toque.

## Pendências

- Revisão humana.
