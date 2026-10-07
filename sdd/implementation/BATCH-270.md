# BATCH-270 — Ponto de extensão geral das telas do painel (`interface` / `pagina`)

- **Requisição:** [REQ-261](../human-requests/req-261.md)
- **Status:** `implemented-pending-homologation`
- **Linha:** `3.0` (branch `feat/req-261`, entregue em `main`, `3.0` e `3.1`).
- **Data:** 2026-10-07
- **Coordenada com:** REQ-114 do site (IA nos campos de formulário), que é quem usa o ponto.

## Live Todo List

- [x] Disparo de `interface` / `pagina` junto com os pontos por módulo
- [x] Teste automatizado e documentação nos dois idiomas
- [x] Exercitado por um módulo real (o `ai-fields` do site) nos dois ambientes de teste
- [x] Correção na camada de provedores de IA: conexão com o banco solta antes da espera pelo provedor
- [ ] Homologação humana

## O que mudou

`interface_finalizar()` já disparava `<módulo>` / `<opção>.pagina` quando a tela ficava pronta. Agora dispara também, logo depois e nos mesmos casos (não em envio de formulário):

```php
hook_do_action('interface', 'pagina', $modulo, $opcao);
```

Serve ao módulo que acrescenta um recurso ao painel inteiro e não a uma tela específica. Antes ele teria de se registrar módulo por módulo, opção por opção. Quem não se registra não percebe diferença: sem callback, o disparo é uma consulta de hooks por pedido, como os outros dois.

Uso, no JSON do módulo:

```json
"hooks": {
    "controllers": {"interface": "meu-modulo.hooks.php"},
    "actions": {"interface": {"pagina": "meu_modulo_interface_pagina"}}
}
```

O callback recebe `$modulo` e `$opcao`, altera `$_GESTOR['pagina']` e inclui o JavaScript dele. Como roda em toda tela, deve sair cedo quando não se aplica.

## Arquivos

- `gestor/bibliotecas/interface.php`
- `gestor/bibliotecas/ia-provedores.php` (correção da REQ-260, ver abaixo)
- `ai-workspace/pt-br/docs/concepts/hooks.md` e `ai-workspace/en/docs/concepts/hooks.md`
- `tests/Unit/PHP/InterfaceHookGeralReq261Test.php` e `tests/Unit/PHP/IaProvedoresReq260Test.php`

## Validação

| Checagem | Resultado |
|---|---|
| PHPUnit (suíte inteira) | 1.750 testes, 20.510 asserções, 5 pulados — OK |
| Teste da requisição | 3 testes: ordem e bloco do disparo, argumentos em GET e nenhum disparo em POST, documentação |
| Uso real | o módulo `ai-fields` do site entra nas telas por este ponto; roteiro dele 24/24 em `v3.1-conn2flow.local` e 22/22 em `conn2flow.local` |
| Roteiro da REQ-260 depois da correção da conexão | 24/24 em `conn2flow.local` |

O Vitest não foi rodado de novo: o lote não toca JavaScript do core.

## Correção na camada de provedores de IA (REQ-260)

Achada ao validar o módulo do site: de quatro pedidos de IA bem-sucedidos, só dois ou três ficavam no registro de uso. O pedido ao provedor pode levar mais tempo do que o banco mantém uma conexão parada; quando isso acontecia, a conexão caía e a gravação feita logo depois da resposta falhava. O `banco_query()` registra o erro no log e devolve falso, então nada aparecia na tela.

`ia_provedor_http()` passou a soltar a conexão com o banco antes de falar com o provedor (`ia_provedor_banco_soltar()`); a consulta seguinte abre outra, como o `banco_query()` já faz quando não há conexão. Vale para tudo o que usa a camada: Assistente IA do editor, teste de conexão do cadastro de servidores, criador de imagens e IA nos campos.

Depois da correção: oito pedidos em sequência, oito registros.

O tempo que o banco do ambiente de teste mantém a conexão parada não foi medido; a causa foi confirmada pelo efeito da correção, não pela leitura da configuração do banco.

## Cuidado para quem usar

Marcação acrescentada a todas as telas precisa de folha de estilo própria, com classes só dela. CSS do Tailwind compilado de um componente, incluído depois do pacote da tela, leva classes utilitárias genéricas (`hidden`, `flex`) que podem inverter regras responsivas da tela hospedeira. O `ai-fields` começou assim e foi corrigido antes da entrega.

## O que não foi exercitado

- Módulo que monta a tela sem `interface_finalizar()` não dispara o ponto.
- Mais de um módulo registrado ao mesmo tempo no ponto geral.
