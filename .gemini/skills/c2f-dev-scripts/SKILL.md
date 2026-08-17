---
name: c2f-dev-scripts
description: Use ao consultar, executar ou criar scripts de automação CLI/Bash/PHP em ambiente de desenvolvimento Conn2Flow.
user-invocable: false
---

# Scripts de Automação e Desenvolvimento (`ai-workspace/scripts/`)

Consulte as convenções para uso e criação de scripts de desenvolvimento no Conn2Flow:

## 1. Estrutura de Diretórios de Scripts

```
ai-workspace/scripts/
├── dev-environment/   # Configuração e criação de novos módulos/instalações
├── projects/          # Deploy, recover, sync e atualização de projetos
├── resources/         # CLI de manipulação de recursos (upsert-resources.php)
├── updates/           # Builds locais e compilação de pacotes
├── releases/          # Tags e automação de releases Git
├── commits/           # Validação e padronização de commits
└── utils/             # Scripts auxiliares Node/JS (ex: fix-tailwind-spacing.js)
```

---

## 2. Principais Scripts Utilitários Disponíveis

* `ai-workspace/scripts/resources/upsert-resources.php`: CLI para criar, clonar, editar e deletar recursos com suporte a `--type`, `--id`, `--action=copy` e `--open`.
* `ai-workspace/scripts/projects/deploy-project-v2.sh`: Empacotamento e deploy automatizado de projetos via API OAuth.
* `ai-workspace/scripts/projects/recover-project.sh`: Recuperação/pull de dados e estrutura do servidor.
* `ai-workspace/scripts/dev-environment/create-new-module.sh`: Scaffold de novos módulos.
* `ai-workspace/scripts/dev-environment/create-new-installation.sh`: Setup de nova instalação local.
* `ai-workspace/pt-br/scripts/utils/fix-tailwind-spacing.js`: Utilitário Node para correção de classes Tailwind.

---

## 3. Diretrizes para Criar Novos Scripts de Desenvolvimento

1. **Parâmetros Flexíveis**: Suporte a flags explícitas (`--project=ID`, `--lang=pt-br`, `--force`) e fallbacks seguros para execução interativa se os argumentos omitirem parâmetros requeridos.
2. **Idempotência e Segurança**: Scripts de atualização/sincronização devem ser idempotentes (poder rodar múltiplas vezes sem corromper dados).
3. **Logs e Retorno**: Emita saída colorida/formatada no console (`echo -e "\033[0;32mOK...\033[0m"`) e verifique códigos de saída HTTP ou status de comandos.
