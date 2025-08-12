# Estruturação de Criação de Seeds - Atualização - Desenvolvimento Conn2Flow

## 🎯 Contexto Inicial
1. Fizemos uma restruturação da origem dos dados para formar os seeds iniciais de uma nova instalação. Antes da restruturação este era o script que lidava com isso: `gestor\resources\generate.multilingual.seeders.php`.
2. A restruturação foi feita por um agente anterior que usou o seguinte prompt para realizar a tarefa: `ai-workspace\prompts\arquitetura\estrutura-dados.md` e depois concluiu usando o seguinte prompt: `ai-workspace\prompts\arquitetura\estrutura-dados-globais.md`. Inclusive atualizou ambos os arquivos com o que foi feito. Use ambos os arquivos para poder entender o contexto com bastante profundidade.
3. Atualize o script acima para poder lidar com a nova formatação dos dados de origem. Na sequência vamos rodar o script para verificar se corretamente criou os 3 seeders.]
4. Um dos motivos que fizemos a alteração, era porque o regex usado na estrutura anterior dava muito problema. Por isso, evite usar regex quando possível.

## 📋 Informações do Projeto
- Sempre use esse arquivo como referência das ações que precisam ser feitas.
- Você pode mudar este arquivo e atualizar as informações livremente. 
- Sempre verifique se todas as tarefas aqui descritas foram completadas.
- Caso tenha dúvidas, favor questionar todas elas antes de qualquer implementação.
- Peque pelo excesso de tira dúvidas. Não precisa ter pressa em implementar, podemos interagir algumas vezes antes.
- Não precisa fazer todas as tarefas numa única interação, pode dividir a mesma em etapas.

## 🤔 Dúvidas e 📝 Sugestões Respostas

# Dúvidas
1. Os seeders devem incluir apenas dados globais ou também dados dos módulos? (Aparentemente ambos, mas confirmar se há alguma filtragem ou tratamento especial para dados de módulos.)
- 1.1: Todos os Seeders vamos fazer de ambos. Na verdade os recursos têm 3 origens dos arquivos HTML e CSS: Global `gestor\resources\{language}\`, Módulos `gestor\modulos\{modulo-id}\resources\{language}\` e Módulos de Plugins `gestor-plugins\{plugin-id}\local\modulos\{modulo-id}\resources\{language}\`. O mapeamento e demais campos para cada origem: Global `gestor\resources\{language}\{recurso}.json`, Módulos `gestor\modulos\{modulo-id}\{modulo-id}.json`e Módulos de Plugins `gestor-plugins\{plugin-id}\local\modulos\{modulo-id}\{modulo-id}.json`.
- 1.2: **IMPORTANTE**: Os Seeders em si mudaram a origem dos dados e como eles são armazenados. Sendo assim, teremos que adaptar o script para salvar os dados nos arquivos.JSON de cada tabela e não mais no arquivo Seeder diretamente. Desta forma, pois os dados serão incluídos nos arquivos de execução propriamente dito, agora eles pegam os dados das tabelas diretamente da pasta: `gestor\db\data` nos arquivos .JSON de cada tabela. Por exemplo: `gestor\db\data\PaginasData.json` são os dados do Seeder: `gestor\db\seeds\PaginasSeeder.php`. Ou seja, a tabela `Paginas`, tem os dados iniciais definidos no `PaginasData.json` e são incluídos no Seeder `PaginasSeeder.php`. Esse padrão é usado em todos os Seeders. Ou seja, nome da tabela: `Tabela`, origem dos dados: `TabelaData`, que são incluídos pelo Seeder: `TabelaSeeder`. Outra informação importante. Não é necessário mudar o arquivo do Seeder em si, pois ele já referencia aos dados, mas sim os .JSONs. Exemplo: `PaginasSeeder.php`:
```php
<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class PaginasSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../data/PaginasData.json'), true);

        $table = $this->table('paginas');
        $table->insert($data)->saveData();
    }
}
```
Exemplo de dado final no `gestor\db\data\PaginasData.json`:
```json
{
    "id_paginas": 1,
    "id_usuarios": 1,
    "id_layouts": 6,
    "nome": "Teste Coluna Centralizada e Tabela",
    "id": "teste-coluna-centralizada",
    "language": "pt-br",
    "caminho": "teste-mudanca-seila\/",
    "tipo": "sistema",
    "modulo": null,
    "opcao": "editar",
    "raiz": 0,
    "sem_permissao": null,
    "html": "<div class=\"ui three column grid stackable\">\n    <div class=\"two wide column\"><\/div>\n    <div class=\"twelve wide column\">\n        <table class=\"ui very basic fixed table\">\n            <tbody>\n                <tr>\n                    <td>Coluna 1 Linha 1<\/td>\n                    <td>Coluna 2 Linha 1<\/td>\n                    <td class=\"ten wide\">É um fato conhecido de todos que um leitor se distrairá com o conteúdo de texto legível de uma página quando estiver examinando sua diagramação. A vantagem de usar Lorem Ipsum é que ele tem uma distribuição normal de letras, ao contrário de \"Conteúdo aqui, conteúdo aqui\", fazendo com que ele tenha uma aparência similar a de um texto legível. Muitos softwares de publicação e editores de páginas na internet agora usam Lorem Ipsum como texto-modelo padrão, e uma rápida busca por 'lorem ipsum' mostra vários websites ainda em sua fase de construção. Várias versões novas surgiram ao longo dos anos, eventualmente por acidente, e às vezes de propósito (injetando humor, e coisas do gênero). <\/td>\n                <\/tr>\n            <\/tbody>\n        <\/table>\n    <\/div>\n    <div class=\"two wide column\"><\/div>\n<\/div>",
    "css": "input,teste{\n\twidth:auto !important;\n}",
    "status": "A",
    "versao": 1,
    "data_criacao": "2025-08-08 20:57:15",
    "data_modificacao": "2025-08-08 20:57:15",
    "user_modified": 0,
    "file_version": "1.0",
    "checksum": "{\"html\":\"abd323652303af692993705eb9a6b154\",\"css\":\"6167910c62f61c6823c352b91b5f2d7b\",\"combined\":\"ec8d83716be53edd06fca3c0f112784e\"}"
},
```
2. Algum campo dos recursos deve ser omitido ou transformado na geração dos seeders, ou todos os campos dos JSONs devem ser mantidos?
- Os JSONs dos recursos (Global, Módulo e Módulo de Plugins) não têm todos os campos da tabela final. Logo, sim, precisamos transformar alguns e definir padrão de outros. Segue exemplos dos 3 tipos de recursos `paginas`, `layouts` e `componentes`. Com a formatação do .JSON de origem dos recursos => o .JSON de destino dos dados, que serão usados no seeders: 
- **Layouts e Componentes São Similares**:
Exemplo de .JSON recursos na origem `gestor\resources\pt-br\layouts.json`:
```json
[
    {
        "name": "Layout Administrativo do Gestor", // Não modificar
        "id": "layout-administrativo-do-gestor", // Não modificar
        "version": "1.0", // Modificar caso o checksum atual dos HTML e CSS forem diferentes dos que estão definidos aqui. De '1.0' para '1.1'. Senão tiver e for recurso novo, colocar inicialmente '1.0'
        "checksum": {
            "html": "47b4e8434e35ea44407008a3c1b02ff7", // Modificar caso o checksum atual do HTML desse recurso for diferente.
            "css": "f10fa90d6577c2fdae5f56b13589179a", // Modificar caso o checksum atual do CSS desse recurso for diferente.
            "combined": "f94ef140d592752cdc8e4d4d0e1c8e18" // Modificar caso o checksum atual do HTML ou CSS forem diferentes.
        }
    },
    ...
]
```
Exemplo de .JSON dos dados no destino `gestor\db\data\LayoutsData.json`:
```json
[
    {
        "id_layouts": 1, // Não modificar e icrementar 1 caso for novo registro.
        "id_usuarios": 1, // Sempre 1, usuário admin inicial
        "nome": "Layout Administrativo do Gestor", // Mesmo valor da origem do campo "name"
        "id": "layout-administrativo-do-gestor", // Mesmo valor da origem
        "language": "pt-br", // Linguagem da origem em questão
        "modulo": null, // Neste caso não tem módulo vinculado, mas caso tenha, colocar o ID do modulo.
        "html": "<!DOCTYPE html>\n<html>\n<head>\n    <!-- pagina#titulo -->\n    <meta http-equiv=\"Content-Type\" content=\"text\/html; charset=UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n    <link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"@[[pagina#url-raiz]]@favicon\/apple-touch-icon.png\">\n    <link rel=\"icon\" type=\"image\/png\" sizes=\"32x32\" href=\"@[[pagina#url-raiz]]@favicon\/favicon-32x32.png\">\n    <link rel=\"icon\" type=\"image\/png\" sizes=\"16x16\" href=\"@[[pagina#url-raiz]]@favicon\/favicon-16x16.png\">\n    <link rel=\"manifest\" href=\"@[[pagina#url-raiz]]@favicon\/site.webmanifest\">\n    <link rel=\"mask-icon\" href=\"@[[pagina#url-raiz]]@favicon\/safari-pinned-tab.svg\" color=\"#5bbad5\">\n    <meta name=\"msapplication-TileColor\" content=\"#da532c\">\n    <meta name=\"theme-color\" content=\"#ffffff\">\n    <!-- pagina#css -->\n    <!-- pagina#js -->\n<\/head>\n<body>\n    <div class=\"ui left sidebar\" id=\"entrey-menu-principal\">\n    \t@[[pagina#menu]]@\n    <\/div>\n    <div class=\"pusher layoutPusher\">\n        <div class=\"menuComputerCont\">\n            @[[pagina#menu]]@\n        <\/div>\n        <div class=\"paginaCont\">\n            <!-- Topo -->\n            <div class=\"desktopcode\">\n                <div class=\"ui three column padded stackable grid\">\n                    <!-- Logo -->\n                    <div class=\"row\">\n                        <div class=\"eight wide column\">\n                            <div class=\"menubarlogomargin\">\n                                <div class=\"logo\">\n                                    <a class=\"item\" href=\"@[[pagina#url-raiz]]@dashboard\/\">\n                                        <img class=\"ui bottom aligned small image\" id=\"entrey-logo-principal\" src=\"@[[pagina#url-raiz]]@images\/logo-principal.png\">\n                                    <\/a>\n                                <\/div>\n                            <\/div>\n                        <\/div>\n                        <!-- Nome Usuário -->\n                        <div class=\"usuario eight wide column right aligned\">\n                            <div class=\"menubarperfilmargin\">\n                                <i class=\"user icon\"><\/i>\n                                <span style=\"color:#000000DE;\">@[[usuario#nome]]@<\/span>\n                            <\/div>\n                        <\/div>\n                    <\/div>\n                <\/div>\n            <\/div>\n            <div class=\"mobilecode\">\n                <div class=\"ui two column padded grid\">\n                    <div class=\"row\">\n                        <div class=\"column\">\n                            <a class=\"item\" href=\"@[[pagina#url-raiz]]@dashboard\/\">\n                                <img class=\"ui bottom aligned small image\" id=\"entrey-logo-principal\" src=\"@[[pagina#url-raiz]]@images\/logo-principal.png\">\n                            <\/a>\n                        <\/div>\n                        <div class=\"right aligned column\">\n                            <div class=\"menumobilemargin _gestor-menuPrincipalMobile\">\n                                <i class=\"big grey bars icon\"><\/i>\n                            <\/div>\n                        <\/div>\n                    <\/div>\n                <\/div>\n            <\/div>\n            <div class=\"ui divider\"><\/div>\n            <div class=\"ui main container\">\n                @[[pagina#corpo]]@\n            <\/div>\n            <!-- Rodapé -->\n            <div class=\"mobilecode\">\n                <div class=\"ui two column padded grid\">\n                    <div class=\"row\">\n                        <div class=\"column\">\n                            <i class=\"user icon\"><\/i>\n                            <span style=\"color:#000000DE;\">@[[usuario#nome]]@<\/span>\n                        <\/div>\n                    <\/div>\n                <\/div>\n            <\/div>\n        <\/div>\n    <\/div>\n    <div id=\"gestor-listener\"><\/div>\n    <div class=\"ui dimmer paginaCarregando\">\n        <div class=\"ui huge text loader\">Carregando<\/div>\n    <\/div>\n<\/body>\n<\/html>\n<!-- Teste de modificação 2025-08-07 21:37:46 -->", // Vem do arquivo HTML vinculado ao recurso.
        "css": ".main.container{\n    margin-left: 1em !important;\n    margin-right: 1em !important;\n    width: auto !important;\n    max-width: 1650px !important;\n}\n.menuComputerCont{\n\tposition: fixed;\n    z-index: 1;\n    width: 250px;\n    background-color: #fff;\n    height:100%;\n    -webkit-box-flex: 0;\n    -webkit-flex: 0 0 auto;\n    -ms-flex: 0 0 auto;\n    flex: 0 0 auto;\n    -webkit-box-shadow: 4px 0 5px 0 rgba(30,30,30,0.2);\n\tbox-shadow: 4px 0 5px 0 rgba(30,30,30,0.2);\n    overflow-y: auto!important;\n}\n.paginaCont{\n    margin-left: 250px;\n}\n#gestor-listener{\n    display:none;\n}\n#entrey-logo-principal{\n    width:130px;\n}\nbody {\n    background-color: #F4F5FA;\n}\nbody.pushable>.pusher {\n    background: #F4F5FA !important;\n}\n.menubarperfilmargin {\n    margin-top: 5px;\n}\n.menubarlogomargin {\n    margin-left: 18px;\n}\n.menumobilemargin {\n    margin-top: 6px;\n}\n#entrey-uso-dados{\n    position: fixed;\n    top: auto;\n    bottom: 20px;\n    left: 20px;\n    width: inherit;\n    margin-left: -13px;\n}\n#entrey-menu-principal{\n\tbackground: #FFF;\n}\n.texto{\n    white-space: nowrap;\n}\n.botoesMargem > .button {\n    margin-bottom: 0.75em;\n}\n@media screen and (max-width: 770px) {\n    .desktopcode {\n        display: none;\n    }\n    .menuComputerCont{\n        display: none;\n    }\n    .paginaCont{\n        margin-left: 0px;\n    }\n    #_gestor-interface-listar{\n        padding: 1em 0px;\n    }\n    #_gestor-interface-listar-column{\n    \tpadding: 1em 0px;\n    }\n    #_gestor-interface-lista-tabela_filter .input{\n        width: calc(100% - 100px);\n    }\n    #_gestor-interface-lista-tabela_filter{\n        text-align:left;\n    }\n}\n@media screen and (min-width: 770px) {\n    .mobilecode {\n        display: none;\n    }\n    .menuComputerCont{\n        display: block;\n    }\n    #_gestor-interface-lista-tabela_filter .input{\n        width: 250px;\n    }\n}", // Vem do arquivo CSS vinculado ao recurso.
        "status": "A", // Padrão sempre 'A' de 'ativo'
        "versao": 1, // Padrão sempre 1 pois é a versão inicial.
        "data_criacao": "2025-08-08 20:57:15", // Calcular usando NOW() do MySQL.
        "data_modificacao": "2025-08-08 20:57:15", // Calcular usando NOW() do MySQL.
        "user_modified": 0, // Padrão sempre 0 pois é a versão inicial.
        "file_version": "1.0", // Mesmo valor da origem
        "checksum": "{\"html\":\"47b4e8434e35ea44407008a3c1b02ff7\",\"css\":\"f10fa90d6577c2fdae5f56b13589179a\",\"combined\":\"f94ef140d592752cdc8e4d4d0e1c8e18\"}" // Mesmo valor da origem
    },
]

```
- **Páginas**:
Exemplo de .JSON recursos na origem `gestor\modulos\admin-arquivos\admin-arquivos.json`:
```json
[
    "resources": {
        "pt-br": {
            "layouts": [],
            "pages": [
                {
                    "name": "Admin Arquivos", // Não modificar
                    "id": "admin-arquivos", // Não modificar
                    "layout": "layout-administrativo-do-gestor", // Não modificar
                    "path": "admin-arquivos\/", // Não modificar
                    "type": "system", // Caso for 'system' traduzir para 'sistema', caso for 'page' traduzir para 'paginas'.
                    "option": "listar-arquivos", // Não modificar
                    "root": true, // Não modificar
                    "version": "1.2", // Modificar caso o checksum atual dos HTML e CSS forem diferentes dos que estão definidos aqui. De '1.2' para '1.3'. Senão tiver e for recurso novo, colocar inicialmente '1.0'
                    "checksum": {
                        "html": "8f33d8113e655162a32f7a7213409e19", // Modificar caso o checksum atual do HTML desse recurso for diferente.
                        "css": "da65a7d1abba118408353e14d6102779", // Modificar caso o checksum atual do CSS desse recurso for diferente.
                        "combined": "ddb032331dd7e8da25416f3ac40a104a" // Modificar caso o checksum atual do HTML ou CSS forem diferentes.
                    }
                },
                ...
]
```
Exemplo de .JSON dos dados no destino `gestor\db\data\LayoutsData.json`:
```json
[
    {
        "id_paginas": 38, // Não modificar e icrementar 1 caso for novo registro.
        "id_usuarios": 1, // Sempre 1, usuário admin inicial
        "id_layouts": 1, // Usar o id_layouts do `gestor\db\data\LayoutsData.json`. Na origem tem o campo "layout": "layout-administrativo-do-gestor", que vc acha o id_layouts procurando pelo 'id' do layout que neste caso é "layout-administrativo-do-gestor".
        "nome": "Admin Arquivos", // Mesmo valor da origem do campo 'name'
        "id": "admin-arquivos", // Mesmo valor da origem
        "language": "pt-br", // Valor da linguagem do recurso.
        "caminho": "admin-arquivos\/", // Mesmo valor da origem. Campo na origem é 'path'.
        "tipo": "sistema", // Caso for 'system' traduzir para 'sistema', caso for 'page' traduzir para 'paginas'. Campo na origem é 'type'.
        "modulo": "admin-arquivos", // ID do módulo em questão. Mas caso não exista usar 'NULL'.
        "opcao": "listar-arquivos", // Mesmo valor da origem. Mas caso não exista usar 'NULL'. Campo na origem é 'option'
        "raiz": 1, // Se definido root 'true' colocar 1. Mas caso não exista usar 'NULL'. Campo na origem é 'root'.
        "sem_permissao": null, // Se definido without_permission 'true' colocar 1. Mas caso não exista usar 'NULL'. Campo na origem é 'without_permission'.
        "html": "<h1 class=\"ui header\">@[[pagina#titulo]]@<\/h1>\n<div class=\"ui basic right aligned segment\">\n    <a class=\"ui button blue\" href=\"@[[pagina#url-raiz]]@@[[pagina#modulo-id]]@\/adicionar\/#paginaIframe#\"\n        data-content=\"@[[button-add-tooltip]]@\" data-id=\"adicionar\">\n        <i class=\"plus circle icon\"><\/i> @[[button-add-label]]@ <\/a>\n<\/div>\n<div class=\"filesFilterCont hidden\">\n    <div class=\"ui large header\">@[[filter-label]]@<\/div>\n    <div class=\"ui teal message\">@[[filter-info]]@<\/div>\n    <div class=\"ui grid stackable\">\n        <div class=\"eight wide column\">\n            <div class=\"ui medium header\">@[[date-label]]@<\/div>\n            <div class=\"ui form\">\n                <div class=\"two fields\">\n                    <div class=\"field\">\n                        <div class=\"ui calendar inverted\" id=\"rangestart\">\n                            <div class=\"ui input left icon\">\n                                <i class=\"calendar icon\"><\/i>\n                                <input type=\"text\" placeholder=\"@[[date-start-label]]@\">\n                            <\/div>\n                        <\/div>\n                    <\/div>\n                    <div class=\"field\">\n                        <div class=\"ui calendar inverted\" id=\"rangeend\">\n                            <div class=\"ui input left icon\">\n                                <i class=\"calendar icon\"><\/i>\n                                <input type=\"text\" placeholder=\"@[[date-end-label]]@\">\n                            <\/div>\n                        <\/div>\n                    <\/div>\n                <\/div>\n            <\/div>\n        <\/div>\n        <div class=\"eight wide column\">\n            <div class=\"ui medium header\">@[[categories-label]]@<\/div>\n            <span>#select-categories#<\/span>\n        <\/div>\n        <div class=\"eight wide column\">\n            <div class=\"ui medium header\">@[[order-label]]@<\/div>\n            <span>#select-order#<\/span>\n        <\/div>\n        <div class=\"eight wide column\">\n            <p>&nbsp;<\/p>\n        <\/div>\n        <div class=\"eight wide column\">\n            <button class=\"ui positive button filterButton\">@[[filter-button]]@<\/button>\n            <button class=\"ui blue button clearButton\">@[[clear-button]]@<\/button>\n        <\/div>\n    <\/div>\n<\/div>\n<p>&nbsp;<\/p>\n<div class=\"listFilesCont hidden\">\n    <div class=\"ui large header\">@[[files-list-label]]@<\/div>\n    <div id=\"files-list-cont\">#arquivos-lista#<\/div>\n    <div class=\"ui basic center aligned segment hidden listMoreResultsCont\">\n        <button class=\"ui blue button moreResultsButton\" id=\"lista-mais-resultados\">@[[more-results-button]]@<\/button>\n    <\/div>\n<\/div>\n<div class=\"withoutResultsCont hidden\"> #without-results-cont# <\/div>\n<div class=\"withoutFilesCont hidden\"> #without-files-cont# <\/div>", // Vem do arquivo HTML vinculado ao recurso. Mas caso não exista usar 'NULL'.
        "css": ".hidden{\n    display:none;\n}\n.extra.content{\n\tline-height: 3.5em;\n}\n.fileImage{\n    position: relative;\n    width: 100%;\n    padding-top: 75%;\n    overflow:hidden;\n}\n.fileImage img{\n    position: absolute;\n    object-fit: cover;\n    width: 100%;\n    height: 100%;\n    top:0;\n}", // Vem do arquivo CSS vinculado ao recurso. Mas caso não exista usar 'NULL'.
        "status": "A", // Padrão sempre 'A' de 'ativo'
        "versao": 1, // Padrão sempre 1 pois é a versão inicial.
        "data_criacao": "2025-08-08 20:57:15", // Calcular usando NOW() do MySQL.
        "data_modificacao": "2025-08-08 20:57:15", // Calcular usando NOW() do MySQL.
        "user_modified": 0, // Padrão sempre 0 pois é a versão inicial.
        "file_version": "1.0", // Mesmo valor da origem
        "checksum": "{\"html\":\"8f33d8113e655162a32f7a7213409e19\",\"css\":\"da65a7d1abba118408353e14d6102779\",\"combined\":\"ddb032331dd7e8da25416f3ac40a104a\"}" // Mesmo valor da origem
    },
]

```
3. O script deve gerar seeders para todos os idiomas presentes ou apenas para pt-br?
- A definição da liguagem e onde buscar os dados está na definição dos recursos: 
- Global `gestor\resources\resources.map.php`:
```php
$resources = [
	'languages' => [
        'pt-br' => [
            'name' => 'Português (Brasil)',
            'data' => [
                'layouts' => 'layouts.json',
                'pages' => 'pages.json',
                'components' => 'components.json',
            ],
            'version' => '1',
        ],
        // Caso haja outras línguas: 'en' => ..., etc.
    ],
];
```
- Módulo, exemplo `gestor\modulos\admin-arquivos\admin-arquivos.json`:
```json
    "resources": {
        "pt-br": {
            "layouts": [],
            "pages": [
                {
                    "name": "Admin Arquivos",
                    "id": "admin-arquivos",
                    "layout": "layout-administrativo-do-gestor",
                    "path": "admin-arquivos\/",
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
                ...
        // Caso haja outras línguas: "en": {..., etc.
```
- Módulo de Plugin, exemplo `gestor-plugins\agendamentos\local\modulos\agendamentos\agendamentos.json`:
```json
    "resources": {
        "pt-br": {
            "pages": [
                {
                    "name": "Agendamentos",
                    "id": "agendamentos",
                    "version": "1.0",
                    "checksum": {
                        "html": "",
                        "css": "",
                        "combined": ""
                    },
                    "layout": "layout-administrativo-do-gestor",
                    "path": "agendamentos\/",
                    "type": "system",
                    "option": "administrar",
                    "root": true
                },
                ...
        // Caso haja outras línguas: "en": {..., etc.
```
4. Alguma convenção especial para o nome das classes dos seeders ou para os campos do array de dados?
- Os arquivos seeders em si tão definidos e também a conveção. Essas ideias falei da resposta da dúvida 1.
5. Os checksums e versões dos recursos devem ser recalculados ou mantidos conforme estão nos JSONs?
- Os checksums são sempre calculados pelo script e comparados com o checksum anteriores guardado na interação anterior. Caso sejam diferentes (arquivos foram modificados), devem trocar os valores e neste caso aumentar a versão, se for 1.0 para 1.1. Se for, 1.6 para 1.7, e assim por diante. Caso os checksums sejam iguais aos guardados, não modificar nem o checksum nem a versão.

# Sugestões
Caso tenha sugestões coloque elas aqui

## 🔧 Comandos Úteis

## 📝 Desenvolvimento das Tarefas


## 📁 Arquivos Relevantes Esperados
1. Sempre que for criar algum script de operação, obrigatoriamente use a pasta para armazenar os mesmos: `ai-workspace\scripts\arquitetura\`.

---
**Data:** $(date)
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow v1.11.0
