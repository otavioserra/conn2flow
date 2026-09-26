---
title: "Plugins"
description: "Pacotes instaláveis, recursos de módulos e validação do instalador."
section: concepts
order: 70
sources:
  - gestor/bibliotecas/plugins-installer.php
  - gestor/modulos/admin-plugins/admin-plugins.php
verified_at: 3b099ff0
---

# Plugins

Plugins adicionam módulos e recursos sem editar o core. O instalador aceita ZIP enviado, repositório GitHub público ou privado e caminho local. Extrai em `gestor/temp/plugins/<slug>/`, procura `manifest.json` e instala em `gestor/plugins/<slug>/`. O manifesto exige `id`, `name` e `version` no formato `x.y.z`. Uma versão ou manifesto inválido interrompe a instalação.

Quando há arquivo `.sha256` para o ZIP, `plugin_process()` confere o SHA-256 antes de extrair. Sem esse arquivo, o checksum calculado serve para detectar pacote inalterado e evitar reprocessamento; isso não autentica a origem. A instalação pode executar migrações do pacote e sincronizar recursos e módulos declarados. O painel `admin-plugins` controla instalação e atualização.

O módulo de plugin usa `modules/<id>/` ou `modulos/<id>/`, com seu JSON e `resources/<idioma>/`. IDs devem ser únicos: a sincronização de [hooks](hooks.md) apaga os registros pelo id do módulo sem distinguir plugin e core. Além disso, o renderizador atual de [widgets](widgets.md) só procura em `gestor/modulos/`; um widget que exista apenas sob `plugins/` não é encontrado por esse caminho.

> [!WARNING]
> Instalar um pacote executa PHP do plugin, inclusive migrações. Confirme a procedência e o manifesto antes da instalação.
