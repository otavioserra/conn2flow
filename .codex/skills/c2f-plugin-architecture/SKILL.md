---
name: c2f-plugin-architecture
description: "LEIA ANTES de criar ou empacotar novos plugins para o ecossistema Conn2Flow. Se não ler: plugins não instalam, colidem com tabelas do core ou não desinstalam limpamente."
user-invocable: false
---

# Arquitetura e Ciclo de Vida de Plugins (`CONN2FLOW-PLUGIN-ARCHITECTURE.md`)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Criar, estruturar ou testar pacotes em `dev-plugins/` ou `gestor/plugins/`.
- **SKIP APENAS SE**: Desenvolvimento de recursos nativos do Core ou customizações exclusivas de projeto.
- **CONSEQUÊNCIA DE IGNORAR**: Plugins incompatíveis com o ciclo de vida do instalador, resíduos órfãos deixados no banco após desinstalação e violação do isolamento de plugins.

---

Consulte e aplique as seguintes convenções ao trabalhar com plugins no Conn2Flow:

## 1. Estrutura de Diretórios de Plugins

Plugins residem sob `dev-plugins/plugins/`:
- **Públicos**: `dev-plugins/plugins/public/<plugin-id>/`
- **Privados**: `dev-plugins/plugins/private/<plugin-id>/`

Estrutura típica de um plugin:
```
dev-plugins/plugins/public/<plugin-id>/
├── plugin.json                 # Manifesto do plugin
├── modulos/                    # Módulos encapsulados pelo plugin
├── scripts/                    # Scripts de install, uninstall e release
└── resources/                  # Recursos específicos do plugin
```

---

## 2. Manifesto do Plugin (`plugin.json`)

```json
{
  "id": "meu-plugin",
  "nome": "Meu Plugin",
  "versao": "1.0.0",
  "tipo": "public",
  "modulos": ["modulo-a", "modulo-b"],
  "dependencias": []
}
```

---

## 3. Ciclo de Vida e Instalação (`_install` / `_uninstall`)

- **Instalação (`_install.php`)**: Executa migrações Phinx de tabela do plugin, registra hooks e sincroniza recursos.
- **Desinstalação (`_uninstall.php`)**: Remove dados temporários ou desabilita registros preservando dados do usuário se configurado.

---

## 4. Tarefas de Build e Sincronização

- Compilação de recursos do plugin: `php ./dev-plugins/plugins/public/scripts/resources/update-data-resources-plugin.php`.
- Sincronização local: `bash ./dev-plugins/plugins/public/scripts/dev/synchronizes.sh checksum`.
