---
name: c2f-module-crud-scaffolding
description: "LEIA ANTES de criar, estruturar ou refatorar novos módulos administrativos e CRUDs no Conn2Flow. Se não ler: o módulo viola o padrão canônico modulos-grupos, quebra o histórico de auditoria e falha na sincronização determinística."
user-invocable: false
---

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Criar uma nova pasta em `modulos/` ou `gestor/modulos/` contendo controller `.php`, schema `.json`, script `.js` e `resources/`.
- **SKIP APENAS SE**: Pequenos ajustes pontuais em módulos legados sem alteração de estrutura ou ciclo de vida.
- **CONSEQUÊNCIA DE IGNORAR**: Módulos incompatíveis com o dispatcher de rotas, ausência de chave natural (`strategy: "natural_key"`), falha no snapshot de histórico (`banco_select_campos_antes_iniciar`) e perda de auditoria.

---

# Scaffolding Canônico de Módulos CRUD no Conn2Flow

Consulte e aplique este guia arquitetural sempre que for criar ou refatorar um módulo administrativo no Conn2Flow. O módulo canônico de referência é `gestor/modulos/modulos-grupos/`.

## 📚 Subskills (leitura sob demanda)

| Subskill | Leia quando |
| --- | --- |
| [`subskills/schema-declarativo.md`](subskills/schema-declarativo.md) | criar ou alterar o `<modulo-id>.json` (tabela, `natural_key`, páginas e variáveis) |
| [`subskills/controller-escrita-e-historico.md`](subskills/controller-escrita-e-historico.md) | implementar `_adicionar()`, `_editar()` ou `_clonar()` |

---

## 1. Estrutura Padrão de Diretórios e Arquivos

Todo módulo administrativo deve residir em `gestor/modulos/<modulo-id>/` ou `modulos/<modulo-id>/` com a seguinte composição obrigatória:

```
modulos/<modulo-id>/
├── <modulo-id>.php         # Controller principal e dispatcher de ciclo de vida
├── <modulo-id>.json        # Schema declarativo: bibliotecas, tabela, natural_key e recursos
├── <modulo-id>.js          # Script JavaScript carregado pelo módulo no frontend
├── cover.webp              # [Obrigatório] Capa 3D conceitual gerada via c2f-module-visual-assets
└── resources/
    ├── pt-br/
    │   ├── pages/
    │   │   ├── <modulo-id>/                     # Tela de listagem (opcao=listar)
    │   │   ├── <modulo-id>-adicionar/           # Tela de inserção (opcao=adicionar)
    │   │   ├── <modulo-id>-editar/              # Tela de edição (opcao=editar)
    │   │   └── <modulo-id>-clonar/              # Tela de clonagem (opcao=clonar)
    │   └── variables.json                       # Variáveis e labels multilíngues pt-br
    └── en/
        ├── pages/ ...
        └── variables.json                       # Variáveis e labels multilíngues en
```

### 1.1 Identidade Visual e Capa Obrigatória (`c2f-module-visual-assets`)

Todo novo módulo administrativo DEVE nascer com sua capa conceitual 3D padronizada, seguindo rigorosamente as diretrizes da skill `c2f-module-visual-assets`:
- **Padrão Estético**: 3D isométrico, fundo azul meia-noite profundo, acentos ciano/violeta, vidro fosco e cerâmica suave (Design System V3.0). Sem texto, números ou logotipos.
- **Formato e Resolução**: WebP 1024×1024 px (proporção 1:1), qualidade 90, método 6, peso máximo < 120 KB (alvo: 60 KB a 95 KB).
- **Armazenamento Canônico**: Arquivo `gestor/assets/modulos/covers/<modulo-id>.webp` (e opcionalmente espelhado na raiz do módulo como `modulos/<modulo-id>/cover.webp`).
- **Registro Obrigatório no Manifesto**: Toda nova capa deve ser cadastrada em `gestor/assets/modulos/covers/manifest.json` com `id`, `width`, `height`, `bytes` e hash `sha256` antes da homologação do módulo.

---

## 2. Schema Declarativo (`<modulo-id>.json`)

O JSON declara `versao`, `bibliotecas` (ex.: `interface`, `html`), o mapeamento da `tabela` e os `resources` por idioma. Exemplo completo: **`subskills/schema-declarativo.md`**.

- `tabela` nomeia as colunas de controle: `id` (slug), `id_numerico`, `status`, `versao`, `data_criacao`, `data_modificacao`.
- `tabela.config` usa `"strategy": "natural_key"` com `natural_key_columns` (`["language", "id"]`), `preserve_on_user_modified` e `insert_only`. Sem a chave natural a sincronização deixa de ser determinística.
- `resources.<lang>.pages` declara as quatro telas (`listar` com `root: true`, `adicionar`, `editar`, `clonar`) com `layout-administrativo-do-gestor` e `type: system`; `variables` declara os rótulos consumidos por `gestor_variaveis()`.

---

## 3. Anatomia do Controller PHP (`<modulo-id>.php`)

### 3.1. Inicialização e Carregamento
```php
<?php

global $_GESTOR;

$_GESTOR['modulo-id']                      = 'meu-modulo';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/meu-modulo.json'), true);
```

### 3.2–3.4. Funções `_adicionar()`, `_editar()` e `_clonar()`

Código completo de referência: **`subskills/controller-escrita-e-historico.md`**. O mínimo obrigatório:

- **`_adicionar()`** (gatilho `$_GESTOR['adicionar-banco']`): valida no servidor com `interface_validacao_campos_obrigatorios()`, gera o slug com `banco_identificador()` filtrado por `language='".$_GESTOR['linguagem-codigo']."'`, grava `id_usuarios`, `language`, status `A`, versão `1` e `NOW()` nas datas via `banco_insert_name()` e redireciona para `editar/`. Repete a validação no frontend em `$_GESTOR['interface']['adicionar']['finalizar']`.
- **`_editar()`** (gatilho `$_GESTOR['atualizar-banco']`): abre com `banco_select_campos_antes_iniciar()` (falha = `interface_alerta` + `gestor_redirecionar_raiz()`), compara cada campo com `banco_select_campos_antes()`, acumula `$alteracoes`, regenera o slug com `id_valor` quando o nome muda (salvo `_gestor-nao-alterar-id`), incrementa `versao`, grava `data_modificacao=NOW()` e chama `interface_historico_incluir(Array('alteracoes' => $alteracoes))`. Toda consulta filtra `status!='D'` e o idioma.
- **Renderização de `_editar()`**: `banco_select_editar()`; sem resultado, `gestor_redirecionar_raiz()`. Preenche `metaDados` (datas, versão, status) e os botões `adicionar`, `clonar`, `status` e `excluir` em `$_GESTOR['interface']['editar']['finalizar']`.
- **`_clonar()`**: mesma lógica de `_adicionar()`, carregando o registro original com `banco_select_editar()`.

### 3.5. Função `_interfaces_padroes()` (Configuração da Listagem)
```php
function meu_modulo_interfaces_padroes(){
    global $_GESTOR;
    
    $modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
    
    switch($_GESTOR['opcao']){
        case 'listar':
            $_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
                'banco' => Array(
                    'nome' => $modulo['tabela']['nome'],
                    'campos' => Array('nome', $modulo['tabela']['data_criacao'], $modulo['tabela']['data_modificacao']),
                    'id' => $modulo['tabela']['id'],
                    'status' => $modulo['tabela']['status'],
                    'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
                ),
                'tabela' => Array(
                    'rodape' => true,
                    'colunas' => Array(
                        Array('id' => 'nome', 'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-name')), 'ordenar' => 'asc'),
                        Array('id' => $modulo['tabela']['data_criacao'], 'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')), 'formatar' => 'dataHora', 'nao_procurar' => true),
                        Array('id' => $modulo['tabela']['data_modificacao'], 'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')), 'formatar' => 'dataHora', 'nao_procurar' => true),
                    ),
                ),
                'opcoes' => Array(
                    'editar' => Array('url' => 'editar/', 'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-edit')), 'icon' => 'edit', 'cor' => 'basic blue'),
                    'clonar' => Array('url' => 'clonar/', 'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-clone')), 'icon' => 'clone', 'cor' => 'basic teal'),
                    'ativar' => Array('opcao' => 'status', 'status_atual' => 'I', 'status_mudar' => 'A', 'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-active')), 'icon' => 'eye slash', 'cor' => 'basic brown'),
                    'desativar' => Array('opcao' => 'status', 'status_atual' => 'A', 'status_mudar' => 'I', 'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-desactive')), 'icon' => 'eye', 'cor' => 'basic green'),
                    'excluir' => Array('opcao' => 'excluir', 'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-delete')), 'icon' => 'trash alternate', 'cor' => 'basic red'),
                ),
                'botoes' => Array(
                    'adicionar' => Array('url' => 'adicionar/', 'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')), 'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-insert')), 'icon' => 'plus circle', 'cor' => 'blue'),
                ),
            );
        break;
    }
}
```

### 3.6. Dispatcher Central `_start()`

O núcleo `interface` reserva quatro valores de `ajaxOpcao` (`listar`, `verificar-campo`, `backup-campos-mudou`, `historico-mais-resultados`). É proibido usá-los em endpoints AJAX customizados do módulo. Use ações específicas do módulo, como `listar-itens`.
```php
function meu_modulo_start(){
    global $_GESTOR;
    
    gestor_incluir_bibliotecas();
    
    if($_GESTOR['ajax']){
        interface_ajax_iniciar();
        
        switch($_GESTOR['ajax-opcao']){
            // case 'minha-acao': meu_modulo_ajax_acao(); break;
        }
        
        interface_ajax_finalizar();
    } else {
        meu_modulo_interfaces_padroes();
        
        interface_iniciar();
        
        switch($_GESTOR['opcao']){
            case 'adicionar': meu_modulo_adicionar(); break;
            case 'editar': meu_modulo_editar(); break;
            case 'clonar': meu_modulo_clonar(); break;
        }
        
        interface_finalizar();
    }
}

meu_modulo_start();
```

---

## 4. Regras Mandatórias de Engenharia

1. **PROIBIDO Hardcode de Textos**: Todo e qualquer rótulo, título, tooltip ou mensagem de alerta deve vir de `gestor_variaveis(...)` (ver `c2f-variables-system`).
2. **PROIBIDO Arquivos Estáticos Soltos**: Todas as telas devem residir no sistema de recursos em `resources/<lang>/pages/` (ver `c2f-resources-system`).
3. **Sempre Usar Chave Natural**: Gerar slugs e IDs com `banco_identificador()` garantindo unicidade por idioma (`language = $_GESTOR['linguagem-codigo']`).
4. **Sempre Usar Snapshot de Edição**: Toda gravação de edição DEVE iniciar com `banco_select_campos_antes_iniciar()` e registrar o log de alterações com `interface_historico_incluir()`.
