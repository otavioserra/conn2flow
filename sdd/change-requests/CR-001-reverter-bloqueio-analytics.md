# CR-001 — Reverter o bloqueio de analytics do req-109 e corrigir o laço de verificação de cookie

- **Status**: accepted
- **Data**: 2026-08-13
- **Origem**: Engenheiro Chefe, em homologação do BATCH-109
- **Afeta**: `req-109.md` §3 e §4 (Módulo 2), `BATCH-109`, `DEC-104`
- **Decisão**: DEC-106
- **Implementação**: BATCH-111

---

## O que muda no requisito

O req-109, Módulo 2, pedia:

> **3.** Suprimir a injeção/execução de scripts de analytics e pixels (GTM, Meta Pixel) na página
> `cookies-is-mandatory/` e páginas de erro/sistema.
>
> **4.** Tratar a inicialização do Meta Pixel no frontend (`gestor/assets/global/global.js`) para
> evitar duplicação (`Duplicate Pixel ID`) e requisições CAPI malformadas (400 Bad Request).

**Os dois itens ficam REVERTIDOS.** Nenhuma página do sistema bloqueia coletor de analytics. A
decisão é do Engenheiro Chefe: os sistemas de analytics têm que continuar acessando as informações.

## Por que o requisito estava errado

O intake partiu de um diagnóstico invertido. A leitura era "a `cookies-is-mandatory/` aparece nos
relatórios porque o analytics roda nela". A causa real é o contrário: **o analytics não roda nela por
escolha — todo cliente sem cookie era EMPURRADO para lá** pelo laço de verificação. Bots de analytics
são stateless: não guardam cookie, não seguem o round-trip.

Bloquear o script trata o sintoma no lugar errado, e ainda joga fora sinal legítimo — `404` e `500`
são justamente o que se quer enxergar no relatório para achar link quebrado e campanha mal apontada.

## Evidência medida

Medição em produção em 2026-08-13, com `curl`, **antes** de qualquer correção estar no ar
(`snapphoton.com` em 2.9.34 e `conn2flow.com` em 2.9.33):

| Cliente | Resultado na home |
| --- | --- |
| Navegador real (com cookie jar) | 2 saltos → **200 OK** |
| Cliente stateless (sem cookies) | **laço infinito**, `curl` aborta com "too many redirects" |
| Googlebot (não persiste cookie entre requisições) | **laço infinito** |

Cadeia observada:

```
/ → _gestor-cookie-verify/<id>/?url=
  → cookies-is-mandatory/
  → _gestor-cookie-verify/<id>/?url=cookies-is-mandatory%2F
  → cookies-is-mandatory/ → …
```

**Causa do laço**: a própria `cookies-is-mandatory/` é uma página e, ao ser renderizada, chama
`gestor_cookie_verificacao()` de novo. A tela que existe para explicar o problema estava atrás do
mesmo portão que deveria explicar.

O mesmo teste com User-Agent do WhatsApp deu o mesmo laço, confirmando que o BATCH-109 não estava
deployado. E o `gestor_cookie_verificacao()` em `HEAD` tem o mesmo `header("Location: …"); exit;`
incondicional — **o defeito não é das versões antigas, está na `main` de hoje**.

Consequência real, maior que o relatório de analytics: os sites ficam praticamente **invisíveis para
o Googlebot**, que nunca chega a um `200` na home.

## O que passa a valer

1. **Sem bloqueio de analytics em lugar nenhum.** `gestor_rastreamento_remover()` e o flag
   `gestor.rastreamentoBloqueado` foram removidos do core, junto com os testes que os cobriam.
2. **Rota de sistema nunca redireciona** na verificação de cookie: emite o `Set-Cookie` e devolve a
   página pedida. É a trava que fecha o laço.
3. **`gestor_permissao()` não faz mais o round-trip de cookie** — era redundante: quem chega sem
   sessão vai para `/signin/` de qualquer forma, e é o `/signin/` que faz a prova.
4. **Rotas de sistema saem do índice** (`noindex, nofollow` + `X-Robots-Tag`), para o que já foi
   indexado sair e uma reincidência não voltar a poluir relatório e busca.
5. **Detecção de robô deixa de ser peça crítica.** Cliente sem cookie recebe a página pública seja
   ele reconhecido ou não. A lista de tokens passa a servir só ao outro uso — entregar o `<head>`
   com OpenGraph em página protegida.
6. **Lista de tokens em duas camadas**: baseline embutido, sempre ativo e ampliado com bots de
   anúncio e auditoria (`AdsBot-Google`, `Mediapartners-Google`, `Chrome-Lighthouse`, `AhrefsBot`,
   `SemrushBot`, monitores de uptime); e uma lista extra editável em **Ambiente → Configurações do
   Site**, desligada por padrão.

## O que NÃO muda

- Módulos 1, 3, 4 e 5 do req-109 seguem valendo integralmente.
- O req-110 (BATCH-110) não é afetado; ele só reusa `gestor_pagina_rota_sistema()`, que foi
  renomeada (era `gestor_pagina_sistema_sem_rastreamento` — o nome virou mentira sem o bloqueio).

## Rastreabilidade

- Intake original: [req-109.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-109.md)
- Decisão revertida: DEC-104 §4 (recebe adendo apontando para cá)
- Decisão nova: DEC-106
- Implementação: [BATCH-111.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/implementation/BATCH-111.md)
