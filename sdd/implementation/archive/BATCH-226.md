# BATCH-226: módulos distribuídos — imagens do layout dentro do painel (req-218)

Execução da [req-218](../../human-requests/archive/req-218.md). Corrige o limite registrado na REQ-098 do `conn2flow-site` (logo do portal com 404 no painel distribuído); entregue junto da REQ-099 do site (`sdd/implementation/modulos-distribuidos/batch-093-marketplace-fase-2.md`).

**Status**: `in-review` (validado no Lab; revisão humana pendente).

## O que mudou

| Onde | Mudança |
|---|---|
| `gestor/bibliotecas/modulo-distribuido-protocolo.php` | `MODULO_DISTRIBUIDO_PASTAS_ESTATICAS` (`vendor`, `favicon`, `images`): dentro do prefixo `_distributed/run/<id>/`, arquivos estáticos dessas pastas voltam ao próprio endereço (302). O destino vem do endereço original da requisição, porque o roteador passa `caminho` para minúsculas e o nome do arquivo pode ter maiúsculas. O destino é sempre do mesmo host. |

## Validação

- `tests/Unit/PHP/ModuloDistribuidoEstaticosReq218Test.php`: 3 testes (pastas, rota de módulo mantém o prefixo, regra sem `..` e com as extensões). Canal: 93 testes OK.
- Lab: `/_distributed/run/<id>/favicon/Otavio-@-Conn2Flow-Favicon-3-150x150.png` → 302 → 200. O roteiro do marketplace no painel do cliente não tem mais 404 em `favicon/` (`req099-marketplace-fase2-e2e.cjs`).
