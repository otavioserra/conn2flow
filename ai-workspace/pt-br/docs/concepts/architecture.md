---
title: "Arquitetura do Conn2Flow"
description: "Como roteador, módulos, recursos e banco compõem uma página."
section: concepts
order: 10
sources:
  - gestor/gestor.php
  - gestor/config.php
  - gestor/bibliotecas/gestor.php
verified_at: 3b099ff0
---

# Arquitetura do Conn2Flow

O Gestor é o ponto de entrada PHP. `config.php` prepara caminhos, idiomas e configuração; `gestor.php` interpreta a rota, verifica acesso, consulta a página e monta a resposta. O [ciclo de requisição](request-lifecycle.md) descreve a ordem e os desvios para API e arquivos estáticos.

## Módulos e recursos

Cada módulo mora em `gestor/modulos/<id>/` e pode trazer controlador PHP, JSON de metadados, JavaScript, widgets e `resources/<idioma>/`. Os recursos também podem ser globais em `gestor/resources/<idioma>/`. O compilador transforma a autoria em arquivos `*Data.json`; o atualizador grava os registros no SQL. Em execução normal, páginas, layouts, componentes e variáveis são consultados no banco. Consulte [recursos](resources.md) e [hooks](hooks.md).

O módulo administrativo costuma usar `interface_iniciar()` e `interface_finalizar()` para CRUD, permissões e montagem da tela. Nem todo módulo usa essa interface: controladores e rotas específicas continuam possíveis. As páginas só são encontradas no idioma ativo e com o caminho correspondente.

## Separação de responsabilidades

- `gestor/gestor.php`: roteamento e montagem da resposta;
- `gestor/bibliotecas/`: funções compartilhadas, como `gestor.php`, `interface.php`, `banco.php` e `widgets.php`;
- `gestor/modulos/`: comportamento e recursos por módulo;
- `gestor/controladores/`: API, arquivos estáticos e atualização;
- `cli/`: comandos de autoria, compilação e implantação.

> [!IMPORTANT]
> Editar somente um arquivo HTML em `resources/` não altera a página publicada: o pipeline precisa atualizar os dados do banco e reconstruir o CSS derivado.
