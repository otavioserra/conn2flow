---
title: "Módulo modulos"
description: "Registro administrativo dos módulos e suas variáveis de configuração."
section: reference
module: modulos
sources:
  - gestor/modulos/modulos/modulos.php
  - gestor/modulos/modulos/modulos.js
  - gestor/modulos/modulos/modulos.json
  - gestor/modulos/modulos/resources
  - gestor/db/migrations/20250723165527_create_modulos_table.php
  - gestor/db/migrations/20250902180000_alter_modulos_table_grupo_id.php
  - gestor/db/migrations/20250925211150_add_language_field_to_modules_tables.php
  - gestor/db/migrations/20260217100000_add_hooks_to_modulos_table.php
  - gestor/db/migrations/20260820140000_alter_modulos_add_icone_tailwind.php
  - gestor/db/migrations/20260821100000_alter_modulos_update_icones_projetos.php
verified_at: b6839aa1
---

# Módulo `modulos`

Administra o catálogo de módulos que compõe o painel: nome, grupo, título, ícones, plugin, presença no menu e sinalização de host. Também oferece a página de variáveis de configuração de cada módulo.

## Como usar

Abra `modulos/`, crie em `modulos/adicionar/` e edite em `modulos/editar/?id=<slug>`. Escolha o grupo, ícones para Fomantic e, quando disponível, Tailwind/Lucide, e a opção de exibir no menu. Em `modulos/variaveis/?id=<slug>`, ajuste variáveis de configuração do módulo. O JSON também lista `modulos/sincronizar-bancos/` e `modulos/copiar-variaveis/`; veja as limitações abaixo antes de usá-las. As rotas são iguais nos dois idiomas.

## Referência técnica

O controlador trata `listar`, `adicionar`, `editar`, `variaveis` e `copiar-variaveis`. Para `variaveis`, troca o modo da interface para `alteracoes` e chama `configuracao_administracao`/`configuracao_administracao_salvar`. Status e exclusão são ações compartilhadas. O `switch` AJAX não tem caso ativo. O JSON não fornece widget, template, hooks ou `hooks.api` para este módulo.

`modulos` contém `id_modulos` numérico, `id_usuarios`, `nome`, `id`, `titulo`, `icone`, `icone2`, `nao_menu_principal`, `plugin`, `host`, `status`, `versao` e datas. Migrações substituíram `id_modulos_grupos` por `modulo_grupo_id` textual, acrescentaram `language`, `hooks`, `icone_tailwind` e `icone2_tailwind`. O menu escolhe ícone conforme o framework, com fallback nos campos Fomantic. O JSON configura sincronização pela chave natural `(language,id)`; as migrations consultadas não declaram unicidade SQL equivalente. O valor `hooks` sinaliza ao gateway a existência de hooks configurados, não contém callbacks.

## Limitações confirmadas

> [!WARNING]
> `copiar-variaveis` é despachado, mas retorna imediatamente porque `$ativar = false`; o conteúdo de cópia abaixo dessa guarda não roda. `sincronizar-bancos` está no JSON de páginas, porém não tem caso no `switch($_GESTOR['opcao'])` deste controlador.

> [!CAUTION]
> O campo `nao_menu_principal` controla a apresentação no menu, não é uma permissão de acesso. As permissões dos perfis são vínculos separados em `usuarios_perfis_modulos` e `usuarios_perfis_modulos_operacoes`.

## Veja também

- [Operações de módulos](modulos-operacoes.md)
- [Perfis de usuários](usuarios-perfis.md)
