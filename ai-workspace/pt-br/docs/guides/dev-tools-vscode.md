---
title: "Use o Conn2Flow Dev Tools no VS Code"
description: "Painel da extensão VS Code: escopo SDD, projetos, diagnósticos e operações do Core."
section: guides
sources:
  - .vscode/tasks.json
  - cli/src/Console/Application.php
  - cli/src/Commands/ModuleCreateCommand.php
verified_at: e5b61f8e
---

# Use o Conn2Flow Dev Tools no VS Code

O painel **Conn2Flow Dev Tools** é a extensão `conn2flow-tools` do repositório `conn2flow-ai-workspace/vscode-extension/`. Abra o ícone Conn2Flow na barra de atividades do VS Code. A árvore atual da extensão (ver `src/providers/conn2flowTreeProvider.ts` no repositório da extensão, commit `a537050`) é organizada em Visão geral, SDD, Core, Projetos, Diagnósticos e Agentes; Ações personalizadas aparecem quando há manifesto de ações.

## Escolha escopo e alvo

Em **Visão geral**, selecione o escopo SDD, o projeto alvo, idioma, topologia e autonomia. A seção SDD abre `CURRENT.md`, SPEC, checklist, requisições, lotes, decisões e handoffs do escopo selecionado. A seção Projetos exige alvo explícito para os atalhos de atualizar, sincronizar Core/arquivos e deploy. O painel também oferece seleção de outro projeto para operações pontuais.

## Execute e acompanhe

Em **Core**, `Update All` chama o pipeline do gestor; há ações para recursos, auditoria e reconstrução de CSS. Releases de gestor e instalador têm verificação de permissão e execução guiada. Em **Diagnósticos**, um alvo Docker oferece status, logs Apache/PHP e limpeza de logs; um alvo VM troca essas ações por logs PHP/Nginx. O painel **Agentes** reúne busca e abertura de documentação e catálogo de skills.

A extensão abre comandos em tarefas/terminais e usa formulários para operações com parâmetros; revise o alvo e a ação antes de confirmar um deploy. Em workspace não confiável, a execução de comandos é limitada. A [CLI do Core](../reference/cli/index.md) é a alternativa no terminal; a árvore da extensão e `.vscode/tasks.json` são interfaces distintas.

## Instalação local da extensão

No repositório `conn2flow-ai-workspace/vscode-extension/`, o pacote declara VS Code `^1.85.0`. Para desenvolver a extensão, use `npm install`, `npm run compile` e `npm run package` no diretório dela; instale o arquivo VSIX gerado pelo VS Code. Confira a versão e o nome do arquivo gerado antes de instalar.
