# BATCH-123 — Correção de rumo: HTML para o Sistema de Recursos e classes para as Variáveis

Origem: correção do Chefe em 2026-08-18 sobre os BATCH-119/120/121
Validação: [VALIDATION-CHECKLIST.md#batch-123](../validation/VALIDATION-CHECKLIST.md)
Status: `implemented-pending-homologation` (implementado em 2026-08-18)

---

## O que eu fiz errado

Nos três lotes anteriores eu escrevi **markup direto no PHP** (`$html .= '<div class="…">'`) para as
abas de Segurança, Sessões e Chaves de API, para os blocos das telas de 2FA e para os botões de login
social. E fixei as classes utilitárias em **constantes do próprio arquivo** (`PERFIL_USUARIO_CARTAO`,
`PERFIL_PUBLICO_CAMPO`…).

As duas coisas violam a arquitetura do Conn2Flow:

- **HTML pertence a `resources/`** — é o Sistema de Recursos que compila para `*Data.json` e
  sincroniza com o banco. Markup no PHP não é editável por instalação, não passa pelo pipeline e não
  aparece para o operador.
- **Valor de apresentação pertence às VARIÁVEIS do sistema** — que são dados de banco, versionados e
  editáveis por instalação. Constante em PHP congela o visual no código.

O `.env`/`config.php` **não** era o destino das classes: ele é para constante global estável ou dado
sensível (token, segredo). Essa distinção foi o Chefe quem estabeleceu.

## O que foi feito

### Componentes novos (`resources/<lang>/components/`, pt-br e en)

| Componente | Conteúdo |
| --- | --- |
| `perfil-usuario-seguranca` | Aba Segurança: 4 estados do 2FA + contas sociais |
| `perfil-usuario-sessoes` | Aba Sessões: contêiner, cartão, etiqueta |
| `perfil-usuario-api-tokens` | Aba Chaves de API: formulário, escopo, tabela, linha |
| `perfil-usuario-2fa-campos` | Blocos compartilhados das telas públicas de 2FA |
| `perfil-usuario-login-metodos` | Alternador senha/e-mail e botões sociais |

Cada componente guarda **todas as variantes** de uma tela em blocos nomeados
(`<!-- nome < --> … <!-- nome > -->`); o PHP passou a apenas **escolher qual bloco entra e com que
valores**, via `modelo_tag_val` / `modelo_tag_in` / `modelo_var_in`. Extrair em vez de concatenar é
o que permite ao operador editar qualquer variante sem tocar em código.

### Classes como variáveis do sistema

15 variáveis novas por idioma (`classe-cartao`, `classe-botao-ok`, `classe-publico-campo`…),
consumidas nos componentes como `@[[classe-…]]@`.

**Verificação que decidiu o desenho**: compilei um probe real e confirmei que o extractor do Tailwind
**enxerga classes dentro do JSON de variáveis**. Por isso o `perfil-usuario.json` passou a ser
declarado em `tailwind_sources` das páginas — sem isso o precompiled sairia sem essas utilities.

### Dependências do bundle

Os componentes do módulo foram declarados em `tailwind_dependencies` da página. **Medido**: antes
dessa declaração, `divide-slate-100` (que só existe no componente de Segurança) ficava de fora do
bundle. As telas públicas de 2FA/login ganharam os dois componentes compartilhados.

## A regra virou teste

`PerfilUsuarioRecursosTest` (12 casos, 380 assertions) falha se:

- o PHP voltar a montar tag (`$html .= '<…'`) ou declarar `class="`;
- voltar a existir `const PERFIL_*`;
- um bloco usado pelo PHP sumir do componente (bloco ausente faz `modelo_tag_val` devolver vazio — a
  seção some da tela **sem erro nenhum**);
- um gancho de JS (`btn-sessao-revogar`, `login-method-toggle`, `data-sessao-pubid`…) se perder;
- uma variável de classe sumir ou ficar vazia;
- o JSON do módulo deixar de ser fonte Tailwind, ou o bundle deixar de declarar os componentes.

Escrever `$html .= '<div…'` é sempre mais rápido que criar o bloco no componente — é exatamente o
tipo de defeito que volta sozinho na próxima pressa. Por isso a regra é executável.

## Validação

- `php -l` OK; `node --check` OK.
- Compilador: **40 compilados, 0 erros**; 150 componentes no inventário; apenas os 4 avisos
  pré-existentes.
- Conferência direta no bundle: `divide-slate-100`, `bg-red-700`, `ring-emerald-500`, `bg-amber-50`
  e `font-mono` presentes (24.114 bytes).
- `composer test` → **520/520**; `npx vitest run` → **328/328**.
- **Pendente**: deploy `Update => Core` — os componentes e as variáveis vêm do banco.
