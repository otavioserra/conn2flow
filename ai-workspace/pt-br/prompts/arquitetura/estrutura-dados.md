# Estruturação de Dados - Desenvolvimento Conn2Flow

## 🎯 Contexto Inicial
1. Vamos migrar a estrutura de dados para arquivos JSON. Pois assim vc poderá criar e modificar dados usando scripts PHP com maior qualidade, sem a necessidade de ficar usando regex complicados atoa.
2. O objetivo principal e passar a estrutura de dados que mudam durante o desenvolvimento como arquivos HTML e CSS, variáveis, categorias, e qualquer outro tipo que por ventura for surgindo no sistema.
3. A primeira tarefa será passar todas as variáveis do escopo dos arquivos dos módulos, que está armazenado na variável **$_GESTOR['modulo#'.$_GESTOR['modulo-id']]**, para um arquivo .JSON na mesma pasta do módulo. E trocar o valor dessa variável com essas variáveis, para a decomposição do .JSON passando para o tipo array que essa variável irá receber como novo valor.
4. Na sequência iremos fazer o mesmo com todos os seeds, os dados internos destes seeds, usarão a mesma estratégia de remover os dados internos aos arquivos dos seeds, que estão dentro da variável **$data**, para um arquivo .JSON numa pasta data um nível acima dos seeds.
5. Existe um problema com dados faltando de alguns recursos como `páginas`. Com a nova estrutura de dados .JSON, iremos fazer uma busca num backup .SQL, para 

## 📋 Informações do Projeto
- Sempre use esse arquivo como referência das ações que precisam ser feitas.
- Você pode mudar este arquivo e atualizar as informações livremente. 
- Sempre verifique se todas as tarefas aqui descritas foram completadas.
- Caso tenha dúvidas, favor questionar todas elas antes de qualquer implementação.
- Peque pelo excesso de tira dúvidas. Não precisa ter pressa em implementar, podemos interagir algumas vezes antes.
- Não precisa fazer todas as tarefas numa única interação, pode dividir a mesma em etapas.

## 🤔 Dúvidas e 📝 Sugestões Respostas

# ✅ PROGRESSO DA IMPLEMENTAÇÃO
- [x] Análise da estrutura atual concluída (41 módulos, 14 seeders encontrados)
- [x] Scripts de teste criados e validados com sucesso
- [x] Script completo de migração criado
- [x] Execução da migração dos módulos (41/41 migrados com sucesso)
- [x] Execução da migração dos seeders (14/14 migrados com sucesso)  
- [x] Validação final e testes (100% validado)

# 🎉 RESULTADO FINAL
**MIGRAÇÃO 100% CONCLUÍDA COM SUCESSO!**
- ✅ 41 módulos migrados para JSON
- ✅ 14 seeders migrados para JSON
- ✅ Todos os arquivos PHP atualizados para usar json_decode()
- ✅ Estrutura de dados totalmente migrada para arquivos JSON externos
- ✅ Validação completa confirmou que tudo está funcionando

# 📁 Arquivos Criados
## Scripts de Migração:
- `ai-workspace/scripts/migrate-data-to-json.php` - Script de análise inicial
- `ai-workspace/scripts/migrate-complete-to-json.php` - Script completo de migração
- `ai-workspace/scripts/test-module-migration.php` - Teste de módulo específico
- `ai-workspace/scripts/test-seeder-migration.php` - Teste de seeder específico  
- `ai-workspace/scripts/validate-migration-focused.php` - Validação final

## Estrutura JSON Criada:
- `gestor/modulos/{modulo-id}/{modulo-id}.json` - 41 arquivos JSON de módulos
- `gestor/db/data/{Nome}Data.json` - 14 arquivos JSON de seeders

# Tarefa 1:
1. Todo módulo tem as duas variáveis definidas dentro do `{modulo-id}.php`. Exemplo:
```php
global $_GESTOR;

$_GESTOR['modulo-id']							=	'admin-arquivos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
```
2. Substituir completamente somente a variável **$_GESTOR['modulo#'.$_GESTOR['modulo-id']]**, o restante do arquivo tem logicas importantes e não devem ser alterados.
3. Todos os módulos tem essa variável, mas caso haja um erro em relação a isso, imprimir no terminal o problema e vamos tratando cada erro um a um.

# Tarefa 2:
1. Sim, mas eu já criei ela por vc.
2. Não porque se houver problemas temos um gerenciamento de versão feita pelo Git e podemos recuperar os mesmos.
3. Sim, todos eles tem a mesma estrutura. Mas pode fazer um busca e caso não encontre, imprima no terminal o erro.

# Sugestões
1. Pode analisar e caso não encontre problemas, pode implementar a solução.
2. Já atualizei o arquivo por vc. Pode seguir.

## 🔧 Comandos Úteis


## 📝 Desenvolvimento das Tarefas
1. Você vai criar um script PHP para fazer a primeira tarefa de migração das variáveis dos módulos.
- Os módulos estão na seguinte pasta: `gestor\modulos`
- Cada módulo tem um ID único e o ID é o nome da pasta `gestor\modulos\{modulo-id}`
- Dentro de cada módulo temos a mesma ideia para a definição dos 2 arquivos principais ao mesmo: `gestor\modulos\{modulo-id}\{modulo-id}.php` e `gestor\modulos\{modulo-id}\{modulo-id}.js`.
- O script irá varrer todos os módulos da pasta `gestor\modulos\{modulo-id}`, pegar os dados atuais da variável **$_GESTOR['modulo#'.$_GESTOR['modulo-id']]** dentro de cada `{modulo-id}.php`. Que é um array conforme o exemplo abaixo do módulo `gestor\modulos\admin-arquivos\admin-arquivos.php`:

```php
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.4',
	'bibliotecas' => Array('interface','html','arquivo'),
	'tabela' => Array(
		'nome' => 'arquivos',
		'id' => 'id',
		'id_numerico' => 'id_'.'arquivos',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
	),
	'imagem' => Array(
		'mini_width' => 200,
	),
	'resources' => [
		'pt-br' => [
			'layouts' => [],
			'pages' => [
			    [
			        'name' => 'Admin Arquivos',
			        'id' => 'admin-arquivos',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'admin-arquivos/',
			        'type' => 'system',
			        'option' => 'listar-arquivos',
			        'root' => true,
			        'version' => '1.2',
			        'checksum' => [
			            'html' => '8f33d8113e655162a32f7a7213409e19',
			            'css' => 'da65a7d1abba118408353e14d6102779',
			            'combined' => 'ddb032331dd7e8da25416f3ac40a104a',
			        ],
			    ],
			    [
			        'name' => 'Admin Arquivos - Adicionar',
			        'id' => 'admin-arquivos-adicionar',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'admin-arquivos/adicionar/',
			        'type' => 'system',
			        'option' => 'upload',
			        'version' => '1.0',
			        'checksum' => [
			            'html' => '394122d6c6e3f8ba4ac350a5d7791b33',
			            'css' => '72b0bf4a8ae69c7acf2cffbd036d1c62',
			            'combined' => 'defcd0d295170fbfe13cc2788dd402fe',
			        ],
			    ],
			    [
			        'name' => 'Emissão teste',
			        'id' => 'emissao-teste',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'admin-arquivos/emissao-teste/',
			        'type' => 'system',
			        'without_permission' => true,
			        'version' => '1.0',
			        'checksum' => [
			            'html' => '',
			            'css' => '',
			            'combined' => '',
			        ],
			    ],
			],
			'components' => [],
		],
	],
);
```
- Irá copiar todo os valores da variável **$_GESTOR['modulo#'.$_GESTOR['modulo-id']]** , passar para um tipo .JSON usando a função `json_encode()` e por fim irá gravar os dados no arquivo `{modulo-id}.json`, na mesma pasta do `{modulo-id}.php`, criando o mesmo caso não exista ou então sobrescrever o mesmo arquivo usando como referência o exemplo abaixo:

```json
{
    "versao": "1.0.4",
    "bibliotecas": [
        "interface",
        "html",
        "arquivo"
    ],
    "tabela": {
        "nome": "arquivos",
        "id": "id",
        "id_numerico": "id_arquivos",
        "status": "status",
        "versao": "versao",
        "data_criacao": "data_criacao",
        "data_modificacao": "data_modificacao"
    },
    "imagem": {
        "mini_width": 200
    },
    "resources": {
        "pt-br": {
            "layouts": [],
            "pages": [
                {
                    "name": "Admin Arquivos",
                    "id": "admin-arquivos",
                    "layout": "layout-administrativo-do-gestor",
                    "path": "admin-arquivos/",
                    "type": "system",
                    "option": "listar-arquivos",
                    "root": true,
                    "version": "1.2",
                    "checksum": {
                        "html": "8f33d8113e655162a32f7a7213409e19",
                        "css": "da65a7d1abba118408353e14d6102779",
                        "combined": "ddb032331dd7e8da25416f3ac40a104a"
                    }
                },
                {
                    "name": "Admin Arquivos - Adicionar",
                    "id": "admin-arquivos-adicionar",
                    "layout": "layout-administrativo-do-gestor",
                    "path": "admin-arquivos/adicionar/",
                    "type": "system",
                    "option": "upload",
                    "version": "1.0",
                    "checksum": {
                        "html": "394122d6c6e3f8ba4ac350a5d7791b33",
                        "css": "72b0bf4a8ae69c7acf2cffbd036d1c62",
                        "combined": "defcd0d295170fbfe13cc2788dd402fe"
                    }
                },
                {
                    "name": "Emissão teste",
                    "id": "emissao-teste",
                    "layout": "layout-administrativo-do-gestor",
                    "path": "admin-arquivos/emissao-teste/",
                    "type": "system",
                    "without_permission": true,
                    "version": "1.0",
                    "checksum": {
                        "html": "",
                        "css": "",
                        "combined": ""
                    }
                }
            ],
            "components": []
        }
    }
}
```
- Por fim, irá referenciar este mesmo arquivo criado, como parâmetro novo da variável **$_GESTOR['modulo#'.$_GESTOR['modulo-id']]** dentro de cada `{modulo-id}.php`. Passando do tipo JSON para o tipo Array. Exemplo:

```php
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode($jsonData,true);
```

2. Vamos remover todos as variáveis **$data** de dentro dos seeds que estão na pasta: `gestor\db\seeds`.
- Fazer um script que irá varrer todos os arquivos dentro desta pasta. Exemplo: `gestor\db\seeds\PaginasSeeder.php`.
- O script vai copiar os valores da variável **$data** de cada arquivo. Exemplo:
```php
$data = [
            [
                'id_paginas' => 1,
                'id_usuarios' => 1,
                'id_layouts' => 6,
                'nome' => 'Teste Coluna Centralizada e Tabela',
                'id' => 'teste-coluna-centralizada',
                'language' => 'pt-br',
                'caminho' => 'teste-mudanca-seila/',
                'tipo' => 'sistema',
                'modulo' => null,
                'opcao' => 'editar',
                'raiz' => 0,
                'sem_permissao' => null,
                'html' => '<div class="ui three column grid stackable">'
            ...
```
- Vai passar o array para JSON usando a função `json_encode()` e gravar o resultado dentro da pasta `gestor\db\data`. O nome do arquivo será o mesmo do arquivo de seed original, trocando a palavra `Seeder` por `Data`com a extensão .JSON . Exemplo: `PaginasSeeder.php` => `PaginasData.json`.
- Por fim, irá referenciar este mesmo arquivo criado, como parâmetro novo da variável **data** dentro de cada seed (Exemplo: `PaginasSeeder.php`). Passando do tipo JSON para o tipo Array. Exemplo:

```php
$data = json_decode($jsonData,true);
```

3. Depois que terminarmos iremos criar a nova tarefa para os dados e vamos atualizar aqui.

## 📁 Arquivos Relevantes Esperados
1. Sempre que for criar algum script, obrigatoriamente use a pasta para armazenar os mesmos: `ai-workspace\scripts`

---
**Data:** $(date)
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow v1.11.0
