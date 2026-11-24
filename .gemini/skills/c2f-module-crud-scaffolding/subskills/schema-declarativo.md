# Schema declarativo do módulo (`<modulo-id>.json`) — receita completa

Subskill de `c2f-module-crud-scaffolding`. Leia ao criar ou alterar o JSON de um módulo administrativo. As regras críticas (chave natural, recursos em `resources/`) também estão na skill-mãe.

## Schema completo de referência

O arquivo JSON define dependências de bibliotecas, mapeamento ORM/tabela e metadados de recursos:

```json
{
    "versao": "1.0.0",
    "bibliotecas": [
        "interface",
        "html"
    ],
    "tabela": {
        "nome": "meu_modulo_tabela",
        "id": "id",
        "id_numerico": "id_meu_modulo_tabela",
        "status": "status",
        "versao": "versao",
        "data_criacao": "data_criacao",
        "data_modificacao": "data_modificacao",
        "config": {
            "strategy": "natural_key",
            "natural_key_columns": [
                "language",
                "id"
            ],
            "preserve_on_user_modified": [],
            "insert_only": false
        },
        "deletar": []
    },
    "resources": {
        "pt-br": {
            "layouts": [],
            "pages": [
                {
                    "name": "Meu Módulo",
                    "id": "meu-modulo",
                    "layout": "layout-administrativo-do-gestor",
                    "path": "meu-modulo/",
                    "type": "system",
                    "option": "listar",
                    "root": true
                },
                {
                    "name": "Meu Módulo - Adicionar",
                    "id": "meu-modulo-adicionar",
                    "layout": "layout-administrativo-do-gestor",
                    "path": "meu-modulo/adicionar/",
                    "type": "system",
                    "option": "adicionar"
                },
                {
                    "name": "Meu Módulo - Editar",
                    "id": "meu-modulo-editar",
                    "layout": "layout-administrativo-do-gestor",
                    "path": "meu-modulo/editar/",
                    "type": "system",
                    "option": "editar"
                },
                {
                    "name": "Meu Módulo - Clonar",
                    "id": "meu-modulo-clonar",
                    "layout": "layout-administrativo-do-gestor",
                    "path": "meu-modulo/clonar/",
                    "type": "system",
                    "option": "clonar"
                }
            ],
            "components": [],
            "variables": [
                {
                    "id": "form-name-label",
                    "value": "Nome",
                    "type": "string"
                }
            ]
        }
    }
}
```
