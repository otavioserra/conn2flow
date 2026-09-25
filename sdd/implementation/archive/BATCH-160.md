# BATCH-160 — Animações de entrada dos templates de sessão

- **Status**: complete
- **Intake**: `sdd/human-requests/req-159.md`
- **Data de abertura**: 2026-09-02
- **Modo**: supervisionado

## Escopo aprovado

1. Definir os tokens de animação no contrato central do core (decisão do operador).
2. Recompilar os sidecars afetados pelo pipeline oficial.
3. Guarda automatizada contra classe `animate-*` sem regra compilada.

## Diagnóstico medido

Quatro templates aplicavam `animate-fade-in-up` sem que o token existisse em contrato nenhum. No
Tailwind v4 a classe `animate-<nome>` só é gerada quando há `--animate-<nome>` no `@theme`; sem ele a
utility é descartada em silêncio na compilação.

Medido em Chromium, sobre o HTML e o sidecar reais, ANTES da correção:

```
sessao-contato-mapa  .animate-fade-in-up   animation-name: none     0s     <- morto
sessao-com-abas      .animate-fade-in      animation-name: fadeIn   0.5s   <- funciona
sessao-destaque      .animate-bounce       animation-name: bounce   1s     <- funciona
sessao-destaque      .animate-pulse        animation-name: pulse    2s     <- funciona
```

O contraste com `sessao-com-abas` deu a causa: aquele template **traz o `@keyframes fadeIn` num
`<style>` embutido no HTML**, e por isso anima. Os quatro afetados usam a classe sem trazer
definição. `animate-pulse` (38 usos) e `animate-bounce` (26) funcionam por serem nativas.

Alcance: 7 usos por idioma, **14 no total**, em `sessao-contato-mapa`,
`sessao-contato-mapa-alternativo`, `sessao-galeria-masonry` e `sessao-newsletter-minimalista`.

**Severidade baixa, pelo motivo que importa**: nenhum elemento afetado tem `opacity-0` junto, então
todos renderizam normalmente e apenas entram estáticos. Fosse o contrário, o conteúdo ficaria
invisível para sempre — a animação que o traria de volta não existe. É esse modo de falha que
justifica corrigir a utility em vez de apenas remover a classe.

## Live Todo List

- [x] Auditar a pasta de templates e separar defeito real de falso positivo.
- [x] Cadastrar a req-159 e abrir o BATCH-160.
- [x] Definir `--animate-fade-in` / `--animate-fade-in-up` e os `@keyframes` no contrato central.
- [x] Recompilar os recursos pelo pipeline oficial.
- [x] Verificar em Chromium que as animações passaram a rodar, sem regressão nas nativas.
- [x] Guarda automatizada, validada por mutação.
- [x] Homologar visualmente com o operador.

## Implementação

- `gestor/assets/tailwindcss/system-input.css` ganhou o bloco `@theme` com `--animate-fade-in` e
  `--animate-fade-in-up`, mais os dois `@keyframes`.
- A escolha do contrato central em vez de `<style>` por template é do operador, e tem consequência
  técnica direta: `tailwind_recursos_browser_contract()` **deriva** o `browser-contract.css` deste
  arquivo, removendo `@import "tailwindcss"` e `@source`. Uma definição aqui alcança o build offline,
  o editor visual e a Editbar sem uma segunda declaração para envelhecer fora de sincronia —
  verificado: o contrato do navegador foi regenerado com os dois tokens.
- `fade-in` acompanhou `fade-in-up` porque `sessao-com-abas` já carregava aquele keyframe embutido;
  com o token central, a cópia local deixa de ser a única fonte.

## Evidências

- **14/14 animações vivas** (`animation-name` e duração reais) nos 4 templates × 2 idiomas, contra
  0/14 antes. `animate-pulse`, `animate-bounce` e `animate-fade-in` sem regressão.
- `resources:sync --force`: **237/237** recursos Tailwind recompilados, 0 problemas.
- `browser-contract.css` derivado automaticamente com `--animate-fade-in-up` e `@keyframes`.
- **Guarda validada por mutação**: removida a regra de um sidecar, 2 testes falham nomeando template
  e classe; restaurada, voltam a passar. O teste detecta o defeito que existia.
- PHPUnit completo: **1.096/1.096**, 7.547 asserções (1.092 antes; +4). Vitest: **394/394**.
- `assets:minify --verificar`: 0 derivados desatualizados.

## Falsos positivos descartados na auditoria

Registrado para não ser reaberto:

- `tab-btn`, `tab-content`, `tab-btn-alt`, `tab-content-alt`, `sidebar-item`, `active`,
  `c-header-nav-btn`, `c-header-nav-mobile` aparecem sem regra compilada porque **não são
  utilities**: são hooks de JavaScript (`document.querySelector`) ou estão em `<style>` local.
- 20 templates sem arquivo de miniatura: layouts e componentes **não declaram** o campo `thumbnail`
  no manifesto — só as sessões declaram, porque só elas aparecem no seletor visual de inserção.
  `TemplatesTailwindIntegrityTest` passa, pois exige o arquivo apenas quando o campo existe.
- 36 Tailwind com sidecar / 36 Fomantic sem: correto, Fomantic não usa o pipeline do Tailwind.

## Gates residuais

- **Ressalva de arquitetura**: `tailwind_recursos_input_central()` usa o
  `contents/tailwindcss/input.css` do PROJETO quando existe, **substituindo** o do core — não
  estendendo. O `conn2flow-site` tem input próprio (só cores e fonte), então um projeto que recompile
  estes templates não herda a animação enquanto não declarar o mesmo token. Tornar o contrato de
  projeto uma extensão do contrato do core mudaria comportamento para todos os projetos e exige
  change request própria.
- Homologação visual com o operador pendente.

## Restrições

- Nenhum commit, push, release ou deploy sem autorização do Humano-no-Loop.
