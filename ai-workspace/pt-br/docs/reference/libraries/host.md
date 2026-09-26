---
title: "Biblioteca host.php"
description: "Legado do modo multi-host: host_url(), host_pub_id() e host_loja_nome() consultam tabelas que as instalações atuais não têm."
section: reference
order: 330
sources:
  - gestor/bibliotecas/host.php
  - gestor/bibliotecas/interface.php
verified_at: 8768245a
---

# Biblioteca `host.php`

Resto da época em que um Gestor atendia várias lojas (hosts) no mesmo banco. As três funções leem as tabelas `hosts` e `hosts_variaveis` e usam `$_GESTOR['host-id']` como host padrão.

> [!WARNING]
> Nas instalações atuais, **nenhuma migração cria `hosts`, `hosts_variaveis` nem `hosts_arquivos`**, e nada define `$_GESTOR['host-id']`. Sem `id_hosts` explícito, as funções devolvem `false`; com ele, a consulta falha por tabela inexistente. Não use esta biblioteca em código novo.

| Função | Devolve |
|---|---|
| `host_url(['opcao' => 'full', 'id_hosts' => …])` | `https://<dominio>/` com `opcao=full`, senão só o domínio |
| `host_pub_id(['id_hosts' => …])` | a coluna `pub_id` do host |
| `host_loja_nome(['id_hosts' => …])` | a variável `nome` do módulo `loja-configuracoes`, ou `Minha Loja <id>` |

Defeitos, para quem mantiver código legado:

- O resultado fica em cache na global `$_HOST` **sem considerar o `id_hosts`**: a segunda chamada com outro host devolve o valor do primeiro.
- `host_pub_id()` ignora o `id_hosts` recebido na consulta e usa sempre `$_GESTOR['host-id']`.
- `host_url()` sem `opcao` gera *warning* de variável indefinida.
- `id_hosts` entra no SQL sem escape.

O único chamador no core é a `interface.php`, nos campos de imagem que procuram miniaturas em `hosts_arquivos`, um ramo que também depende das tabelas ausentes.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/host.php` por `c2f docs:extract` — 3 funções. Não edite dentro deste bloco.

- `host_url(array|false $params = false): string|false` — [linha 38](../../../../../gestor/bibliotecas/host.php#L38)
- `host_pub_id(array|false $params = false): string|false` — [linha 98](../../../../../gestor/bibliotecas/host.php#L98)
- `host_loja_nome(array|false $params = false): string|false` — [linha 148](../../../../../gestor/bibliotecas/host.php#L148)

<!-- c2f:extract:end -->
