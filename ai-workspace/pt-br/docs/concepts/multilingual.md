---
title: "Sistema multilíngue"
description: "Seleção de idioma, caminhos, recursos e isolamento de conteúdo."
section: concepts
order: 60
sources:
  - gestor/config.php
  - gestor/gestor.php
  - gestor/bibliotecas/gestor.php
  - gestor/bibliotecas/lang.php
verified_at: 3b099ff0
---

# Sistema multilíngue

`LANGUAGES` configura os idiomas aceitos. `gestor_config()` retira um prefixo de idioma válido do caminho (`/en/...`) e o inclui nas URLs raiz; sem prefixo, um cookie de idioma válido pode selecionar o idioma. O roteador consulta páginas com `language` igual ao idioma ativo. A lista de idiomas disponíveis para uma página é calculada por consulta às páginas correspondentes.

Recursos de idiomas diferentes são arquivos separados em `resources/<idioma>/` e registros separados no banco. Ausência de um recurso num idioma não cria tradução automática. Variáveis do sistema são consultadas pelo idioma ativo; textos de interfaces devem ter pares nos recursos de cada idioma. Consulte [recursos](resources.md) e [variáveis globais](global-variables.md).

`gestor/bibliotecas/lang.php` implementa um dicionário JSON separado, com `__t()` e retorno da própria chave quando falta tradução. Esse dicionário não substitui os recursos `variables` compilados; o arquivo padrão esperado pela biblioteca não está presente no core atual. Para conteúdo do site e do painel, use os recursos por idioma.

> [!WARNING]
> Adicionar um prefixo de URL sem criar a página e seus recursos no idioma correspondente resulta em página ausente; a rota não traduz HTML existente.
