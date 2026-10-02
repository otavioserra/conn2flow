# BATCH-221: módulos distribuídos, melhorias da revisão (req-213)

Execução da [req-213](../human-requests/req-213.md), parte do core da REQ-094 do `conn2flow-site`. Relatório completo, medições e E2E no site (`sdd/implementation/modulos-distribuidos/batch-088-nove-melhorias-da-revisao.md`).

**Status**: `in-review` (validado no Lab; revisão humana pendente).

## O que mudou

| Onde | Mudança |
|---|---|
| Componente `modulo-distribuido-app` | Sandbox do iframe com `allow-modals`, `allow-popups` e `allow-popups-to-escape-sandbox` |
| `gestor/bibliotecas/modulo-distribuido-protocolo.php` | `modulo_distribuido_instalacao()` consulta antes um provedor de projeto (`installations-provider`); faxina de nonces amostrada; o gancho do login carrega a biblioteca de tokens |
| `gestor/bibliotecas/modulo-distribuido.php` | Handle do cURL reaproveitado entre consultas da requisição; `modulo_distribuido_signin()` removida |
| `gestor/controladores/api/api-module-central.php`, `api.php` | Ação `signin` removida; `app_id` sem instalação válida é recusado antes do segredo global legado |
| `gestor/bibliotecas/autenticacao.php` | `autenticacao_distribuido_validar_credenciais()` removida |
| `gestor/resources/{pt-br,en}/variables.json` | Três textos da tela antiga removidos |
| `cli/src/Commands/ProjectVerifyCommand.php` (novo) | `project:verify`: compara por hash o código do destino com a origem (core + projeto) |
| `cli/src/Commands/ProjectUpdateAllCommand.php` | Termina com a conferência; `--no-verify` pula |

## Decisões

- **Provedor de instalações, e não tabela no core.** O cadastro de clientes é operação privada do projeto. O core define o contrato: `array` é a instalação, `false` diz que existe e está desativada (o `.env` não é consultado), `null` diz que o provedor não a conhece (o `.env` responde). Falha do provedor recusa.
- **Conexão reaproveitada, e não consultas agrupadas.** O módulo original espera o resultado de cada consulta antes da próxima; agrupar exigiria mudar os módulos.
- **A conferência por hash só avisa.** Não muda o resultado do pipeline: num destino compartilhado, divergência pode ser legítima por alguns minutos. `project:verify --strict` devolve 1 para quem quiser travar.

## Defeitos corrigidos

- **Login distribuído com segundo fator ou login social** terminava no painel do Central: o gancho chamava `autenticacao_distribuido_gerar_tokens()` sem a biblioteca carregada, e o gerenciador de ganchos engole o erro fora do modo de desenvolvimento.
- **Instalação desativada** ainda podia ser atendida pelo segredo global legado nas ações `permissao` e `refresh`.

## Validação

- `ModuloDistribuidoReq092Test`, `ModuloDistribuidoTest`, `ProjectVerifyReq213Test`; suíte completa: 1.427 testes, 1 falha anterior ao lote (`ProjectSshDeployReq034Test`).
- No Lab: custo por consulta de 31,9 ms para 4,6 ms; E2E em Chromium, Firefox e WebKit 36/36; `project:verify` listou as divergências reais do destino e, no tenant, conferiu 501 arquivos.

## Limites

- A conferência compara `php`, `js` e `sh`. Recursos compilados (HTML, CSS, JSON) têm conferência própria no banco.
- Link em nova aba para outra tela do módulo abre sem sessão: o cookie do iframe é particionado pelo site do cliente.

## Pendências

- Revisão humana.
