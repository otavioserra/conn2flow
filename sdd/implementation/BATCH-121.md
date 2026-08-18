# BATCH-121 — Layout público Tailwind e migração das 15 telas de identidade

Origem: [req-120.md](../human-requests/req-120.md)
Validação: [VALIDATION-CHECKLIST.md#batch-121](../validation/VALIDATION-CHECKLIST.md)
Status: `implemented-pending-homologation` (implementado em 2026-08-18)

Fecha o programa de modernização do módulo `perfil-usuario`, iniciado no BATCH-119.

---

## O que governou o desenho

**A migração é de MARCAÇÃO: o backend não mudou.** Isso desloca o risco. Não há defeito lógico a
caçar; há duas formas silenciosas de errar:

1. uma classe do Fomantic sobrevivendo num arquivo — que só aparece como tela sem estilo em
   produção, porque as telas públicas deixaram de carregar o framework;
2. um `name` de campo POST, id de formulário ou bloco de template perdido na reescrita — que aparece
   como formulário que envia e **não grava nada**, sem erro em lugar nenhum.

Nenhuma das duas é pega por teste de comportamento. Por isso a cobertura deste lote lê os arquivos
**reais** de recursos, nos dois idiomas, e trava os dois contratos.

## M1 — Layout público

`layout-pagina-sem-permissao-tailwind` (pt-br e en, HTML + CSS autoral): header com logo, miolo
centralizado em `max-w-md` e rodapé discreto. O legado `layout-pagina-sem-permissao` **não foi
tocado** (critério de aceite 1).

No CSS autoral entrou apenas o que utility não expressa:

- **Autofill do Chrome**: ele pinta o campo de amarelo e ignora `background-color`; o único caminho é
  a sombra interna. Sem isso, todo formulário de login nasce com um campo destoando do card.
- **Espaçamento do campo de código 2FA**: o usuário confere o código caractere a caractere.

## M2 — As 15 telas

`acessar-sistema`, `cadastrar-no-sistema`, `esqueceu-a-senha`, `esqueceu-a-senha-email-enviado`,
`redefinir-senha`, `redefinir-senha-confirmacao`, `signin-2fa`, `confirmacao-de-email`,
`social-login`, `oauth-callback`, `oauth-authenticate`, `oauth-authenticate-2fa`, `validar-usuario`,
`sair-sistema` e `Area-restrita` — em pt-br e en (**30 arquivos**).

As páginas foram **geradas por script** e não escritas à mão. Elas compartilham o mesmo esqueleto
(card, título, campo, botão, rodapé de links); escrever cada uma a mão faria o vocabulário divergir
entre telas do mesmo fluxo — exatamente o defeito visual que o lote está corrigindo. O gerador está
no scratchpad da sessão; o resultado é o que vale.

`Area-restrita` é a exceção de layout: ela é step-up auth dentro do painel, então acompanha o
`layout-administrativo-tailwind` do BATCH-119, com `tailwind_bundle` (o compilador acusou o F3 nela
assim que passou a ser servida por aquele layout).

## M3 — Blocos gerados em PHP

O HTML que o PHP monta em runtime também era Fomantic e migrou junto, com o vocabulário em
constantes (`PERFIL_PUBLICO_*`): o alternador senha/e-mail (`perfil_usuario_login_method_switch`),
os botões sociais (`perfil_usuario_botao_social`), o campo de código e o reenvio de e-mail das telas
de 2FA, e a etiqueta de status dos metadados do perfil.

**Contrato com o JS preservado**: `.login-method-toggle`, `data-method` e a classe `active`
continuam sendo o que o `perfil-usuario.js` lê — nenhum handler mudou.

Os botões sociais usam uma **letra** como ícone, não webfont: as telas públicas não carregam o
Fomantic, e puxar uma folha de ícones inteira para dois glifos custaria mais que o botão.

As 10 inclusões diretas de `interface.js` no módulo viraram `interface_assets_incluir()` — sem isso,
as telas migradas receberiam o runtime legado, que depende do Fomantic e quebraria nelas.

**Ganho colateral do req-119**: a tela `signin-2fa` passou a dizer ao usuário que o mesmo campo
aceita um código de recuperação (`recovery-code-label`).

## Validação

- `php -l` OK; `node --check` OK.
- Compilador de recursos: **32 compilados, 0 erros**; os avisos do F3 em `Area-restrita`
  desapareceram com o bundle e sobraram os 4 pré-existentes.
- Varredura direta: **zero** ocorrência de classe Fomantic nos 30 arquivos de página.
- `composer test` → **494/494** (novo `PerfilUsuarioTelasPublicasTest` **107/107**).
- `npx vitest run` → **328/328**.
- **Pendente**: deploy `Update => Core` (layout e páginas vêm do banco) + homologação dos fluxos.

## Limites de escopo (declarados)

- Os componentes de e-mail do módulo (`layout-email-*`) continuam como estavam: e-mail exige CSS
  inline e tabelas, e Tailwind não se aplica ali.
- As telas administrativas de outros módulos seguem no layout legado — a migração delas depende de
  `interface.php` ganhar as variantes restantes (listagem, inclusão, visualização, configuração),
  registrado como caminho natural no DEC-113.
