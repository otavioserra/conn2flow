# BATCH-216: módulo `cookie-consent` (req-208)

Execução da [req-208](../../human-requests/archive/req-208.md).

**Status**: `in-review` (validado no Lab; revisão humana pendente).

## O que mudou

| Onde | Mudança |
|---|---|
| `gestor/modulos/cookie-consent/` (novo) | CRUD, widget, controlador público, modelos `cookie-consent-card` e `cookie-consent-card-light`, modo de IA, páginas e variáveis nos dois idiomas |
| `gestor/db/migrations/20261002110001_create_cookie_consent_table.php` | Tabela `cookie_consent`, na estrutura de `galleries`, já com `project` |
| `gestor/resources/{pt-br,en}/modules.json`, `user_profiles_modules.json` | Registro do módulo e permissão do perfil administrador |
| `ai-workspace/{pt-br,en}/docs/reference/modules/cookie-consent.md` | Documentação |
| `tests/Unit/PHP/CookieConsentReq208Test.php` | Testes |

## Decisões

- **Base no `forms`.** O CRUD usa o editor HTML com alvo próprio, sem alterar `html-editor.php`.
- **Tela declarativa.** Campo com `data-schema-key` grava no `fields_schema` pelo caminho indicado; tabela com `data-schema-list` edita uma lista de objetos. Opção nova entra no HTML da página, sem código.
- **CSS próprio.** Classes `c2f-cc-` e cores em variáveis: o aviso tem de aparecer igual em qualquer layout, inclusive sem Tailwind.
- **Textos padrão em variáveis do módulo.** Campo de texto vazio no registro usa o padrão do idioma.
- **Decisão só no navegador.** O cookie `c2f_consent` guarda versão, instante e um booleano por categoria.

## Validação

- `CookieConsentReq208Test`.
- Navegador no Lab (`req090-cookies-slides-legal.cjs`, no projeto que usa o módulo): aviso, cartão, recusa, aceite por categoria, script bloqueado, Consent Mode e telas administrativas.
- `docs:audit`: 0 erros.

## Limites

- A gravação pelo formulário do painel deste módulo não foi exercitada no navegador: as telas abrem, carregam o registro e a pré-visualização funciona.
- O aviso só bloqueia script marcado com `type="text/plain"` e `data-cookie-category`.

## Pendências

- Revisão humana.

## Nota

Este lote criou também um módulo de apresentações em slides, que não é do core e foi retirado na [req-210](../../human-requests/archive/req-210.md). A migração original criava as duas tabelas; a atual (`20261002110001`) cria só a `cookie_consent`.
