<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class LayoutsSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'layout_id' => 1,
                'user_id' => 1,
                'name' => 'Layout Administrativo do Gestor',
                'id' => 'layout-administrativo-do-gestor',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<!DOCTYPE html>
<html>
<head>
    <!-- pagina#titulo -->
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"@[[pagina#url-raiz]]@favicon/apple-touch-icon.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"@[[pagina#url-raiz]]@favicon/favicon-32x32.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"@[[pagina#url-raiz]]@favicon/favicon-16x16.png\">
    <link rel=\"manifest\" href=\"@[[pagina#url-raiz]]@favicon/site.webmanifest\">
    <link rel=\"mask-icon\" href=\"@[[pagina#url-raiz]]@favicon/safari-pinned-tab.svg\" color=\"#5bbad5\">
    <meta name=\"msapplication-TileColor\" content=\"#da532c\">
    <meta name=\"theme-color\" content=\"#ffffff\">
    <!-- pagina#css -->
    <!-- pagina#js -->
</head>
<body>
    <div class=\"ui left sidebar\" id=\"entrey-menu-principal\">
    	@[[pagina#menu]]@
    </div>
    <div class=\"pusher layoutPusher\">
        <div class=\"menuComputerCont\">
            @[[pagina#menu]]@
        </div>
        <div class=\"paginaCont\">
            <!-- Topo -->
            <div class=\"desktopcode\">
                <div class=\"ui three column padded stackable grid\">
                    <!-- Logo -->
                    <div class=\"row\">
                        <div class=\"eight wide column\">
                            <div class=\"menubarlogomargin\">
                                <div class=\"logo\">
                                    <a class=\"item\" href=\"@[[pagina#url-raiz]]@dashboard/\">
                                        <img class=\"ui bottom aligned small image\" id=\"entrey-logo-principal\" src=\"@[[pagina#url-raiz]]@images/logo-principal.png\">
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- Nome Usuário -->
                        <div class=\"usuario eight wide column right aligned\">
                            <div class=\"menubarperfilmargin\">
                                <i class=\"user icon\"></i>
                                <span style=\"color:#000000DE;\">@[[usuario#nome]]@</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class=\"mobilecode\">
                <div class=\"ui two column padded grid\">
                    <div class=\"row\">
                        <div class=\"column\">
                            <a class=\"item\" href=\"@[[pagina#url-raiz]]@dashboard/\">
                                <img class=\"ui bottom aligned small image\" id=\"entrey-logo-principal\" src=\"@[[pagina#url-raiz]]@images/logo-principal.png\">
                            </a>
                        </div>
                        <div class=\"right aligned column\">
                            <div class=\"menumobilemargin _gestor-menuPrincipalMobile\">
                                <i class=\"big grey bars icon\"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class=\"ui divider\"></div>
            <div class=\"ui main container\">
                @[[pagina#corpo]]@
            </div>
            <!-- Rodapé -->
            <div class=\"mobilecode\">
                <div class=\"ui two column padded grid\">
                    <div class=\"row\">
                        <div class=\"column\">
                            <i class=\"user icon\"></i>
                            <span style=\"color:#000000DE;\">@[[usuario#nome]]@</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id=\"gestor-listener\"></div>
    <div class=\"ui dimmer paginaCarregando\">
        <div class=\"ui huge text loader\">Carregando</div>
    </div>
</body>
</html>
<!-- Teste de modificação 2025-08-07 21:37:46 -->',
                'css' => '.main.container{
    margin-left: 1em !important;
    margin-right: 1em !important;
    width: auto !important;
    max-width: 1650px !important;
}
.menuComputerCont{
	position: fixed;
    z-index: 1;
    width: 250px;
    background-color: #fff;
    height:100%;
    -webkit-box-flex: 0;
    -webkit-flex: 0 0 auto;
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    -webkit-box-shadow: 4px 0 5px 0 rgba(30,30,30,0.2);
	box-shadow: 4px 0 5px 0 rgba(30,30,30,0.2);
    overflow-y: auto!important;
}
.paginaCont{
    margin-left: 250px;
}
#gestor-listener{
    display:none;
}
#entrey-logo-principal{
    width:130px;
}
body {
    background-color: #F4F5FA;
}
body.pushable>.pusher {
    background: #F4F5FA !important;
}
.menubarperfilmargin {
    margin-top: 5px;
}
.menubarlogomargin {
    margin-left: 18px;
}
.menumobilemargin {
    margin-top: 6px;
}
#entrey-uso-dados{
    position: fixed;
    top: auto;
    bottom: 20px;
    left: 20px;
    width: inherit;
    margin-left: -13px;
}
#entrey-menu-principal{
	background: #FFF;
}
.texto{
    white-space: nowrap;
}
.botoesMargem > .button {
    margin-bottom: 0.75em;
}
@media screen and (max-width: 770px) {
    .desktopcode {
        display: none;
    }
    .menuComputerCont{
        display: none;
    }
    .paginaCont{
        margin-left: 0px;
    }
    #_gestor-interface-listar{
        padding: 1em 0px;
    }
    #_gestor-interface-listar-column{
    	padding: 1em 0px;
    }
    #_gestor-interface-lista-tabela_filter .input{
        width: calc(100% - 100px);
    }
    #_gestor-interface-lista-tabela_filter{
        text-align:left;
    }
}
@media screen and (min-width: 770px) {
    .mobilecode {
        display: none;
    }
    .menuComputerCont{
        display: block;
    }
    #_gestor-interface-lista-tabela_filter .input{
        width: 250px;
    }
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"47b4e8434e35ea44407008a3c1b02ff7\",\"css\":\"f10fa90d6577c2fdae5f56b13589179a\",\"combined\":\"f94ef140d592752cdc8e4d4d0e1c8e18\"}',
            ],
            [
                'layout_id' => 2,
                'user_id' => 1,
                'name' => 'Layout Página Sem Permissão',
                'id' => 'layout-pagina-sem-permissao',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<!DOCTYPE html>
<html>
<head>
    <!-- pagina#titulo -->
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"@[[pagina#url-raiz]]@favicon/apple-touch-icon.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"@[[pagina#url-raiz]]@favicon/favicon-32x32.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"@[[pagina#url-raiz]]@favicon/favicon-16x16.png\">
    <link rel=\"manifest\" href=\"@[[pagina#url-raiz]]@favicon/site.webmanifest\">
    <link rel=\"mask-icon\" href=\"@[[pagina#url-raiz]]@favicon/safari-pinned-tab.svg\" color=\"#5bbad5\">
    <meta name=\"msapplication-TileColor\" content=\"#da532c\">
    <meta name=\"theme-color\" content=\"#ffffff\">
    <!-- pagina#css -->
    <!-- pagina#js -->
</head>
<body>
    <!-- Topo -->
    <div class=\"desktopcode\">
        <div class=\"ui three column padded stackable grid\">
            <!-- Logo -->
            <div class=\"row\">
                <div class=\"three wide column\">
                    <div class=\"menubarlogomargin\">
                        <div class=\"logo\">
                            <a class=\"item\" href=\"@[[pagina#url-raiz]]@dashboard/\">
                                <img class=\"ui bottom aligned small image\" id=\"entrey-logo-principal\" src=\"@[[pagina#url-raiz]]@images/logo-principal.png\">
                            </a>
                        </div>
                    </div>
                </div>
                <div class=\"seven wide column\">
                    <div class=\"ui grid\">
                        <div class=\"two column row\">
                            <div class=\"column left aligned\">
                            </div>
                            <div class=\"column\">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Nome Usuário -->
                <div class=\"usuario six wide column right aligned\">
                </div>
            </div>
        </div>
    </div>
    <div class=\"mobilecode\">
        <div class=\"ui two column padded grid\">
            <div class=\"row\">
                <div class=\"column\">
                    <a class=\"item\" href=\"@[[pagina#url-raiz]]@dashboard/\">
                        <img class=\"ui bottom aligned small image\" id=\"entrey-logo-principal\" src=\"@[[pagina#url-raiz]]@images/logo-principal.png\">
                    </a>
                </div>
                <div class=\"right aligned column\">
                </div>
            </div>
        </div>
    </div>
    <!-- Miolo -->
    <div class=\"ui divider\"></div>
    <div class=\"ui one column padded stackable grid\">
        <div class=\"row\">
            <div class=\"column\">
                @[[pagina#corpo]]@
            </div>
        </div>
    </div>
    <!-- Rodapé -->
    <div class=\"mobilecode\">
        <div class=\"ui two column padded grid\">
            <div class=\"row\">
                <div class=\"column\">
                </div>
                <div class=\"right aligned column\">
                </div>
            </div>
        </div>
    </div>
    <div id=\"gestor-listener\"></div>
</body>
</html> ',
                'css' => '#gestor-pagina-menu{
    float:left;
    width:200px;
    height:200px;
    padding:20px;
}
#gestor-pagina-corpo{
    float:right;
    width:calc(100% - 260px);
    margin-right:20px;
}
#gestor-listener{
    display:none;
}
#entrey-logo-principal{
    width:130px;
}
body {
    background-color: #F4F5FA;
}
.menubarperfilmargin {
    margin-top: 5px;
}
.menubarlogomargin {
    margin-left: 18px;
}
.menumobilemargin {
    margin-top: 6px;
}
#entrey-uso-dados{
    position: fixed;
    top: auto;
    bottom: 20px;
    left: 20px;
    width: inherit;
    margin-left: -13px;
}
.texto{
    white-space: nowrap;
}
@media screen and (max-width: 770px) {
    .desktopcode {
        display: none;
    }
    #entrey-menu-principal{
        display: none;
        position: fixed;
    	background-color:#FFF;
        width:100%;
        top:0px;
        left:0px;
        bottom:0px;
        z-index:999999;
    }
    #entrey-menu-principal-close{
        display: block;
    }
    #_gestor-interface-listar{
        padding: 1em 0px;
    }
    #_gestor-interface-listar-column{
    	padding: 1em 0px;
    }
    #_gestor-interface-lista-tabela_filter .input{
        width: calc(100% - 100px);
    }
    #_gestor-interface-lista-tabela_filter{
        text-align:left;
    }
}
@media screen and (min-width: 770px) {
    .mobilecode {
        display: none;
    }
    #entrey-menu-principal{
        display: block;
    }
    #entrey-menu-principal-close{
        display: none;
    }
    #_gestor-interface-lista-tabela_filter .input{
        width: 250px;
    }
}
.margin {
    margin-right: 13px;
    margin-left: 13px;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"534a5cb00759351609bfee177e8ed489\",\"css\":\"c3fd0dfa321e5a4f032ff574cc07a4fb\",\"combined\":\"d21bfb8b8103d1b5e33f217e9ae19cb0\"}',
            ],
            [
                'layout_id' => 3,
                'user_id' => 1,
                'name' => 'Layout Emails',
                'id' => 'layout-emails',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<!DOCTYPE html>
<html>
<head>
    <!-- mail#titulo -->
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <!-- mail#css -->
    <!-- mail#js -->
</head>
<body>
    <!-- mail#corpo -->
</body>
</html>',
                'css' => 'h1{
    color:red;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"b9246cdd3db37c3e1e558b3fc846c368\",\"css\":\"9454a802af5d3febf83ff9c503544b36\",\"combined\":\"c332742920e013627280ff66b3180c84\"}',
            ],
            [
                'layout_id' => 4,
                'user_id' => 1,
                'name' => 'Layout Iframes',
                'id' => 'layout-iframes',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<!DOCTYPE html>
<html>
<head>
    <!-- pagina#titulo -->
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <!-- pagina#css -->
    <!-- pagina#js -->
</head>
<body>
    @[[pagina#corpo]]@
</body>
</html>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"0bf6b9ed25e76568e4accab429285052\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"0bf6b9ed25e76568e4accab429285052\"}',
            ],
            [
                'layout_id' => 5,
                'user_id' => 1,
                'name' => 'Página Lista Pedidos',
                'id' => 'pagina-lista-pedidos',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<!DOCTYPE html>
<html>

<head>
    <!-- pagina#titulo -->
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <link rel=\"stylesheet\" type=\"text/css\" href=\"https://cdn.jsdelivr.net/npm/fomantic-ui@2.8.7/dist/semantic.min.css\">
    <!-- pagina#css -->
    <!-- pagina#js -->
    <style type=\"text/css\">
        body {
            background-color: #F4F5FA;
        }

        .h3 {
            color: #00000099
        }

        .h2 {
            font: normal normal 300 23px/32px Open Sans;
        }

        .menubarperfilmargin {
            margin-top: 5px;
        }

        .menubarlogomargin {
            margin-left: 18px;
        }

        .menumobilemargin {
            margin-top: 6px;
        }

        #entrey-uso-dados {
            position: fixed;
            top: auto;
            bottom: 20px;
            left: 20px;
            width: inherit;
            margin-left: -13px;
        }

        .texto {
            white-space: nowrap;
        }

        @media screen and (max-width: 770px) {
            .desktopcode {
                display: none;
            }
        }

        @media screen and (min-width: 770px) {
            .mobilecode {
                display: none;
            }
        }

        .margin {
            margin-right: 13px;
            margin-left: 13px;
        }
    </style>
</head>

<body>
    <div class=\"desktopcode\">
        <div class=\"ui three column padded stackable grid\">
            <div class=\"row\">
                <div class=\"three wide column\">
                    <div class=\"menubarlogomargin\">
                        <div class=\"logo\">
                            <a href=\"#\">
                                <img src=\"https://platform.b2make.com/images/gestor/logo.png\">
                            </a>
                        </div>
                    </div>
                </div>
                <div class=\"seven wide column\">
                    <div class=\"ui grid\">
                        <div class=\"two column row\">
                            <div class=\"column left aligned\">
                                <div class=\"topmenusearch ui category search\">
                                    <div class=\"ui icon input\">
                                        <input class=\"prompt\" type=\"text\" placeholder=\"O que você procura?\">
                                        <i class=\"search icon\"></i>
                                    </div>
                                    <div class=\"results\"></div>
                                </div>
                            </div>
                            <div class=\"column\">
                                <button class=\"topmenubutton ui primary button\">
                                    Ajuda
                                </button>
                                <button class=\"topmenubutton ui button\">
                                    Upgrade
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class=\"usuario six wide column right aligned\">
                    <div class=\"menubarperfilmargin\">
                        <i class=\"user icon\"></i>
                        <span style=\"color:#000000DE;\">Nome Usuário</span>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"ui divider\"></div>
        <div class=\"ui two column padded stackable grid\">
            <div class=\"row\">
                <div class=\"three wide column\">
                    <div class=\"ui fluid  secondary vertical pointing menu\">
                        <a class=\"active item\">
                            Dashboard
                        </a>
                        <a class=\"item\">
                            Loja
                        </a>
                        <a class=\"item\">
                            Conteúdos
                        </a>
                        <a class=\"item\">
                            Usuários
                        </a>
                        <a class=\"item\">
                            Design
                        </a>
                        <a class=\"item\">
                            Configurações
                        </a>
                    </div>
                    <div id=\"entrey-uso-dados\">
                        <div class=\"ui active progress\">
                            <div class=\"bar\">
                                <div class=\"progress\"></div>
                            </div>
                            <div class=\"label\">Uso do disco</div>
                        </div>
                    </div>
                </div>
                <div class=\"thirteen wide black column\">
                    <div class=\"ui grid\">
                        <div class=\"wide column\">
                            <div class=\"row\">
                                <div class=\"ui segment\">
                                    <div class=\"ui grid\">
                                        <div class=\"eight wide red column\">
                                            <h2>Pedidos</h2>
                                        </div>
                                        <div class=\"right aligned eight wide grey column\">
                                            <button class=\"ui primary button\">
                                                <i class=\"plus icon\"></i>
                                                Novo Pedido
                                            </button>
                                            <button class=\"ui primary button\">
                                                <i class=\"share icon\"></i>
                                                Exportar
                                            </button>
                                        </div>
                                    </div>
                                    <div class=\"ui grid\">
                                        <div class=\"three wide orange column\">
                                            <div class=\"ui search\">
                                                <div class=\"ui icon input\">
                                                    <input class=\"prompt\" type=\"text\" placeholder=\"Buscar conteúdo...\">
                                                    <i class=\"left aligned search icon\"></i>
                                                </div>
                                                <div class=\"results\"></div>
                                            </div>
                                        </div>
                                        <div class=\"thirteen wide brown column\">
                                            <div class=\"ui grid\">
                                                <div class=\"two wide green column\">
                                                    <h5>Filtrar por:</h5>
                                                </div>
                                                <div class=\"three wide purple column\">
                                                    <div class=\"ui compact menu\">
                                                        <div class=\"ui simple dropdown item\">
                                                            Serviço
                                                            <i class=\"dropdown icon\"></i>
                                                            <div class=\"menu\">
                                                                <div class=\"item\">Choice 1</div>
                                                                <div class=\"item\">Choice 2</div>
                                                                <div class=\"item\">Choice 3</div>
                                                            </div>
                                                        </div>
                                                        <div class=\"ui simple dropdown item\">
                                                            Status
                                                            <i class=\"dropdown icon\"></i>
                                                            <div class=\"menu\">
                                                                <div class=\"item\">Choice 1</div>
                                                                <div class=\"item\">Choice 2</div>
                                                                <div class=\"item\">Choice 3</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class=\"eight green wide column\">
                                                    <div class=\"ui form\">
                                                        <div class=\"two fields\">
                                                            <div class=\"field\">
                                                                <label>De</label>
                                                                <div class=\"ui calendar\" id=\"rangestart\">
                                                                    <div class=\"ui input left icon\">
                                                                        <i class=\"calendar icon\"></i>
                                                                        <input type=\"text\" placeholder=\"Start\">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class=\"field\">
                                                                <label>Até</label>
                                                                <div class=\"ui calendar\" id=\"rangeend\">
                                                                    <div class=\"ui input left icon\">
                                                                        <i class=\"calendar icon\"></i>
                                                                        <input type=\"text\" placeholder=\"End\">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class=\"three purple wide column\">
                                                    <button class=\"ui primary button\">
                                                        Filtrar
                                                    </button>
                                                    <button class=\"ui button\">
                                                        Limpar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class=\"ui grid\">
                                            

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    </div>
    </div>
    </div>
    </div>
</body>

</html>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"32efb11426f18240e00a7245cbce3212\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"32efb11426f18240e00a7245cbce3212\"}',
            ],
            [
                'layout_id' => 6,
                'user_id' => 1,
                'name' => 'Layout Página Simples',
                'id' => 'layout-pagina-simples',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<!DOCTYPE html>
<html>
<head>
    <!-- pagina#titulo -->
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <!-- pagina#css -->
    <!-- pagina#js -->
</head>
<body>
    @[[pagina#corpo]]@
</body>
</html>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"0bf6b9ed25e76568e4accab429285052\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"0bf6b9ed25e76568e4accab429285052\"}',
            ],
            [
                'layout_id' => 7,
                'user_id' => 1,
                'name' => 'Layout Página Configuração',
                'id' => 'layout-pagina-configuracao',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<!DOCTYPE html>
<html>
<head>
    <!-- pagina#titulo -->
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"@[[pagina#url-raiz]]@favicon/apple-touch-icon.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"@[[pagina#url-raiz]]@favicon/favicon-32x32.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"@[[pagina#url-raiz]]@favicon/favicon-16x16.png\">
    <link rel=\"manifest\" href=\"@[[pagina#url-raiz]]@favicon/site.webmanifest\">
    <link rel=\"mask-icon\" href=\"@[[pagina#url-raiz]]@favicon/safari-pinned-tab.svg\" color=\"#5bbad5\">
    <meta name=\"msapplication-TileColor\" content=\"#da532c\">
    <meta name=\"theme-color\" content=\"#ffffff\">
    <!-- pagina#css -->
    <!-- pagina#js -->
</head>
<body>
    <!-- Topo -->
    <div class=\"desktopcode\">
        <div class=\"ui three column padded stackable grid\">
            <!-- Logo -->
            <div class=\"row\">
                <div class=\"three wide column\">
                    <div class=\"menubarlogomargin\">
                        <div class=\"logo\">
                            <a class=\"item\" href=\"@[[pagina#url-raiz]]@dashboard/\">
                                <img class=\"ui bottom aligned small image\" id=\"entrey-logo-principal\" src=\"@[[pagina#url-raiz]]@images/logo-principal.png\">
                            </a>
                        </div>
                    </div>
                </div>
                <div class=\"seven wide column\">
                    <div class=\"ui grid\">
                        <div class=\"two column row\">
                            <div class=\"column left aligned\">
                            </div>
                            <div class=\"column\">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Nome Usuário -->
                <div class=\"usuario six wide column right aligned\">
                </div>
            </div>
        </div>
    </div>
    <div class=\"mobilecode\">
        <div class=\"ui two column padded grid\">
            <div class=\"row\">
                <div class=\"column\">
                    <a class=\"item\" href=\"@[[pagina#url-raiz]]@dashboard/\">
                        <img class=\"ui bottom aligned small image\" id=\"entrey-logo-principal\" src=\"@[[pagina#url-raiz]]@images/logo-principal.png\">
                    </a>
                </div>
                <div class=\"right aligned column\">
                </div>
            </div>
        </div>
    </div>
    <!-- Miolo -->
    <div class=\"ui divider\"></div>
    <div class=\"ui one column padded stackable grid\">
        <div class=\"row\">
            <div class=\"column\">
                @[[pagina#corpo]]@
            </div>
        </div>
    </div>
    <!-- Rodapé -->
    <div class=\"mobilecode\">
        <div class=\"ui two column padded grid\">
            <div class=\"row\">
                <div class=\"column\">
                </div>
                <div class=\"right aligned column\">
                </div>
            </div>
        </div>
    </div>
    <div id=\"gestor-listener\"></div>
</body>
</html>',
                'css' => '#gestor-pagina-menu{
    float:left;
    width:200px;
    height:200px;
    padding:20px;
}
#gestor-pagina-corpo{
    float:right;
    width:calc(100% - 260px);
    margin-right:20px;
}
#gestor-listener{
    display:none;
}
#entrey-logo-principal{
    width:130px;
}
body {
    background-color: #F4F5FA;
}
.menubarperfilmargin {
    margin-top: 5px;
}
.menubarlogomargin {
    margin-left: 18px;
}
.menumobilemargin {
    margin-top: 6px;
}
#entrey-uso-dados{
    position: fixed;
    top: auto;
    bottom: 20px;
    left: 20px;
    width: inherit;
    margin-left: -13px;
}
.texto{
    white-space: nowrap;
}
@media screen and (max-width: 770px) {
    .desktopcode {
        display: none;
    }
    #entrey-menu-principal{
        display: none;
        position: fixed;
    	background-color:#FFF;
        width:100%;
        top:0px;
        left:0px;
        bottom:0px;
        z-index:999999;
    }
    #entrey-menu-principal-close{
        display: block;
    }
    #_gestor-interface-listar{
        padding: 1em 0px;
    }
    #_gestor-interface-listar-column{
    	padding: 1em 0px;
    }
    #_gestor-interface-lista-tabela_filter .input{
        width: calc(100% - 100px);
    }
    #_gestor-interface-lista-tabela_filter{
        text-align:left;
    }
}
@media screen and (min-width: 770px) {
    .mobilecode {
        display: none;
    }
    #entrey-menu-principal{
        display: block;
    }
    #entrey-menu-principal-close{
        display: none;
    }
    #_gestor-interface-lista-tabela_filter .input{
        width: 250px;
    }
}
.margin {
    margin-right: 13px;
    margin-left: 13px;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"73492006e83b39b4c93cc5e50dc9c1f9\",\"css\":\"c3fd0dfa321e5a4f032ff574cc07a4fb\",\"combined\":\"6ac006e1557dcaec0ed165b17e5267b2\"}',
            ],
            [
                'layout_id' => 8,
                'user_id' => 1,
                'name' => 'Layout Mestre - Loja',
                'id' => 'layout-mestre-loja',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<!DOCTYPE html>
<html>
    <head>
        <!-- pagina#titulo -->
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
        <!-- pagina#css -->
        <!-- pagina#js -->
    </head>
    <body>
        <!--Header-->
        <div class=\"desktopcode\">
            <div class=\"ui padded grid colunatop\">
                <div class=\"column\"></div>
                <div class=\"fourteen wide column\">
					<div class=\"ui three column stackable padded grid\">
                        <div class=\"three wide column\">
                            <div class=\"logo\">
                                <a href=\"#\">
                                    <img src=\"https://beta.entrey.com.br/images/logo-principal.png\" class=\"logo-imagem\"> 
                                </a>
                            </div>
                        </div>
                        <div class=\"nine wide column\">
                            <div class=\"ui grid\">
                                <div class=\"center aligned two column\">
                                    <div class=\"ui ordered steps\">
                                        <div class=\"active step\">
                                            <div class=\"content\">
                                                <div class=\"title\">Carrinho</div>
                                            </div>
                                        </div>
                                        <div class=\"disabled step\">
                                            <div class=\"content\">
                                                <div class=\"title\">Identificação</div>
                                            </div>
                                        </div>
                                        <div class=\"disabled step\">
                                            <div class=\"content\">
                                                <div class=\"title\">Emissão</div>
                                            </div>
                                        </div>
                                        <div class=\"disabled step\">
                                            <div class=\"content\">
                                                <div class=\"title\">Pagamento</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class=\"four wide column\">
                            <div class=\"ui grid\">
                                <div class=\"ten wide column\">
                                    <i class=\"large user icon\"></i>
                                    <span style=\"color:#000;\">#nome-usuário#</span>
                                </div>
                                <div class=\"six wide right aligned column\">
                                    <i class=\"large shopping cart icon\"></i>
                                    <span style=\"color:#000;\">Carrinho</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class=\"column\"></div>
            </div>
        </div>
        <div class=\"mobilecode\">
            <div class=\"ui padded grid colunatop\">
                <div class=\"eleven wide column\">
                    <div class=\"logo-imagem-mobile\">
                        <a href=\"#\">
                            <img src=\"https://beta.entrey.com.br/images/logo-principal.png\">
                        </a>
                    </div>
                </div>
                <div class=\"two wide column\">
                    <i class=\"big user icon\"></i>
                </div>
                <div class=\"two wide column\">
                    <i class=\"big shopping cart icon\"></i>
                </div>
                <div class=\"sixteen wide column\">
                    <!--STEPS - Mudar a orden de acordo com a etapa que o cliente está-->
                    <div class=\"ui fluid steps\">
                        <div class=\"active step\">
                            <div class=\"content\">
                                <div class=\"title\">Carrinho</div>
                            </div>
                        </div>
                        <div class=\"disabled step\">
                            <div class=\"content\">
                                <div class=\"title\">Identificação</div>
                            </div>
                        </div>
                        <div class=\"disabled step\">
                            <div class=\"content\">
                                <div class=\"title\">Emissão</div>
                            </div>
                        </div>
                        <div class=\"disabled step\">
                            <div class=\"content\">
                                <div class=\"title\">Pagamento</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @[[pagina#corpo]]@
        <!--Footer-->
        <div class=\"desktopcode\">
            <div class=\"rodape\">
                <div class=\"ui four column grid padded stackable colunabottom\">
                    <div class=\"one wide column\"></div>
                    <div class=\"seven wide column\">
                        <div class=\"ui two column grid\">
                            <!--Informações do cliente-->
                            <div class=\"column\">
                                Loja Teste
                                Rua Florêncio de Abreu, 0000 - (00) 0000-0000
                                00.000.000/0001-00 - Centro - Ribeirão Preto - SP - Brasil
                                Todos os direitos reservados - 2021
                            </div>
                            <div class=\"column\"></div>
                        </div>
                    </div>
                    <div class=\"seven wide right aligned column\">
                        #formas-de-pagamento#
                    </div>
                    <div class=\"one wide column\"></div>
                </div>
            </div>
        </div>
        <div class=\"mobilecode\">
            <div class=\"colunabottom\">
                <div class=\"ui padded grid\">
                    <div class=\"sixteen wide center aligned column\">
                        #formas-de-pagamento#
                    </div>
                </div>
                <div class=\"ui padded grid\">
                    <div class=\"sixteen wide center aligned column\">
                        Loja Teste
                        Rua Florêncio de Abreu, 0000 - (00) 0000-0000
                        00.000.000/0001-00 - Centro - Ribeirão Preto - SP - Brasil
                        Todos os direitos reservados - 2021
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>',
                'css' => '.colunatop {
    background-color: #E4E7EB;
}
.colunabottom {
    background-color: #E4E7EB;
}
.valorMinimo{
    min-width:100px;
}
.rodape{
    position: absolute;
    bottom: 0px;
    width: 100%
}
.logo-imagem{
    max-width: 130px;
}
.logo-imagem-mobile img{
    max-width: 130px;
}
@media screen and (max-width: 970px) {
    .desktopcode {
        display: none;
    }
}
@media screen and (min-width: 970px) {
    .mobilecode {
        display: none;
    }
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"5600cc39c547c5f54a79141a1609a67d\",\"css\":\"2594d3fe597dde9fd1504761b40e35d9\",\"combined\":\"2f7747f656f0b3cd467099d331cdbff4\"}',
            ],
            [
                'layout_id' => 9,
                'user_id' => 1,
                'name' => 'Layout Mestre - Páginas',
                'id' => 'layout-mestre-paginas',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<!DOCTYPE html>
<html>
<head>
    <!-- pagina#titulo -->
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <!-- pagina#css -->
    <!-- pagina#js -->
</head>
<body>
    @[[pagina#corpo]]@
</body>
</html>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"0bf6b9ed25e76568e4accab429285052\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"0bf6b9ed25e76568e4accab429285052\"}',
            ],
            [
                'layout_id' => 10,
                'user_id' => 1,
                'name' => 'Layout Serviços',
                'id' => 'layout-servicos',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<!DOCTYPE html>
<html>
    <head>
        <!-- pagina#titulo -->
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
        <!-- pagina#css -->
        <!-- pagina#js -->
    </head>
    <body>
        <!--Header-->
        <div class=\"desktopcode\">
            <div class=\"ui padded grid colunatop\">
                <div class=\"column\"></div>
                <div class=\"fourteen wide column\">
					<div class=\"ui padded grid\">
                        <div class=\"three wide column\">
                            <div class=\"logo\">
                                <a href=\"#\">
                                    <img src=\"https://beta.entrey.com.br/images/logo-principal.png\" class=\"logo-imagem\"> 
                                </a>
                            </div>
                        </div>
                        <div class=\"thirteen wide column\"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"mobilecode\">
            <div class=\"ui padded grid colunatop\">
                <div class=\"column\"></div>
                <div class=\"fourteen wide column\">
					<div class=\"ui padded grid\">
                        <div class=\"three wide column\">
                            <div class=\"logo\">
                                <a href=\"#\">
                                    <img src=\"https://beta.entrey.com.br/images/logo-principal.png\" class=\"logo-imagem\"> 
                                </a>
                            </div>
                        </div>
                        <div class=\"thirteen wide column\"></div>
                    </div>
                </div>
            </div>
        </div>
        @[[pagina#corpo]]@
        <!--Footer-->
        
    </body>
</html>',
                'css' => '.logo-imagem{
    max-width: 130px;
}
.logo-imagem-mobile img{
    max-width: 130px;
}
@media screen and (max-width: 770px) {
    .desktopcode {
        display: none;
    }
}
@media screen and (min-width: 770px) {
    .mobilecode {
        display: none;
    }
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ae88be428a8d8119961ff930c7ba35db\",\"css\":\"15b6f90fa29dc85001fce5909d771034\",\"combined\":\"eb06a5a02a4b2ec78658b1a1cbabe3ba\"}',
            ],
            [
                'layout_id' => 11,
                'user_id' => 1,
                'name' => 'Layout Página Padrão',
                'id' => 'layout-pagina-padrao',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<!DOCTYPE html>
<html>
<head>
    <!-- pagina#titulo -->
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"@[[pagina#url-raiz]]@favicon/apple-touch-icon.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"@[[pagina#url-raiz]]@favicon/favicon-32x32.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"@[[pagina#url-raiz]]@favicon/favicon-16x16.png\">
    <link rel=\"manifest\" href=\"@[[pagina#url-raiz]]@favicon/site.webmanifest\">
    <link rel=\"mask-icon\" href=\"@[[pagina#url-raiz]]@favicon/safari-pinned-tab.svg\" color=\"#5bbad5\">
    <meta name=\"msapplication-TileColor\" content=\"#da532c\">
    <meta name=\"theme-color\" content=\"#ffffff\">
    <!-- pagina#css -->
    <!-- pagina#js -->
</head>
<body>
    <div class=\"logo\">
        <a class=\"item\" href=\"@[[pagina#url-raiz]]@dashboard/\">
            <img class=\"ui small image\" src=\"@[[pagina#url-raiz]]@images/logo-principal.png\">
        </a>
    </div>
    <div class=\"ui divider\"></div>
    <div class=\"ui container\">
        @[[pagina#corpo]]@
    </div>
    <div id=\"gestor-listener\"></div>
</body>
</html> ',
                'css' => 'body {
    background-color: #F4F5FA;
}
.logo{
    margin:14px 32px;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"3cff323514c7507537029b50eac33eef\",\"css\":\"b37dfd1ae99d8fbc8fec01de439345aa\",\"combined\":\"8074b5ab4720428c6bf86af3d7465b6b\"}',
            ],
            [
                'layout_id' => 12,
                'user_id' => 1,
                'name' => 'Layout Impressão',
                'id' => 'layout-impressao',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<!DOCTYPE html>
<html>
<head>
    <!-- pagina#titulo -->
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"@[[pagina#url-raiz]]@favicon/apple-touch-icon.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"@[[pagina#url-raiz]]@favicon/favicon-32x32.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"@[[pagina#url-raiz]]@favicon/favicon-16x16.png\">
    <link rel=\"manifest\" href=\"@[[pagina#url-raiz]]@favicon/site.webmanifest\">
    <link rel=\"mask-icon\" href=\"@[[pagina#url-raiz]]@favicon/safari-pinned-tab.svg\" color=\"#5bbad5\">
    <meta name=\"msapplication-TileColor\" content=\"#da532c\">
    <meta name=\"theme-color\" content=\"#ffffff\">
    <!-- pagina#css -->
    <!-- pagina#js -->
</head>
<body onload=\"window.print()\">
    @[[pagina#corpo]]@
</body>
</html>',
                'css' => 'body{
    padding:10px;
}
.nowrap{
    white-space: nowrap;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d12a11673a45cc1ac8bb301570566b17\",\"css\":\"3cb92b2858b7f058956f6b632a804f6a\",\"combined\":\"cc496fecd41e6adf962f7bbb393d0952\"}',
            ],
            [
                'layout_id' => 13,
                'user_id' => 1,
                'name' => 'Admin Layouts',
                'id' => 'admin-layouts',
                'language' => 'pt-br',
                'module' => 'admin-layouts',
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'layout_id' => 14,
                'user_id' => 1,
                'name' => 'Admin Layouts Adicionar',
                'id' => 'admin-layouts-adicionar',
                'language' => 'pt-br',
                'module' => 'admin-layouts',
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"ui top attached tabular menu\">
    <a class=\"active item\" data-tab=\"codigo-html\" data-tooltip=\"#form-html-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-html-label#</a>
    <a class=\"item\" data-tab=\"css\" data-tooltip=\"#form-css-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-css-label#</a>
</div>
<div class=\"ui bottom attached active tab segment\" data-tab=\"codigo-html\">
    <textarea class=\"codemirror-html\" name=\"html\"></textarea>
</div>
<div class=\"ui bottom attached tab segment\" data-tab=\"css\">
    <textarea class=\"codemirror-css\" name=\"css\"></textarea>
</div>',
                'css' => '.CodeMirror{
    border: 1px solid #ccc;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"65578a67da1ba8603be9c6c030c28af7\",\"css\":\"bdb85de7c6afcf6a3fdca44a2b25b9ff\",\"combined\":\"823d9e6c01a59becc22c8cf58f0200c1\"}',
            ],
            [
                'layout_id' => 15,
                'user_id' => 1,
                'name' => 'Admin Layouts Editar',
                'id' => 'admin-layouts-editar',
                'language' => 'pt-br',
                'module' => 'admin-layouts',
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\" value=\"#nome#\">
</div>
<div class=\"ui top attached tabular menu\">
    <a class=\"active item\" data-tab=\"codigo-html\" data-tooltip=\"#form-html-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-html-label#</a>
    <a class=\"item\" data-tab=\"css\" data-tooltip=\"#form-css-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-css-label#</a>
</div>
<div class=\"ui bottom attached active tab segment\" data-tab=\"codigo-html\">#pagina-html-backup# <textarea class=\"codemirror-html\" name=\"html\">#pagina-html#</textarea>
</div>
<div class=\"ui bottom attached tab segment\" data-tab=\"css\">#pagina-css-backup# <textarea class=\"codemirror-css\" name=\"css\">#pagina-css#</textarea>
</div>',
                'css' => '.CodeMirror{
    border: 1px solid #ccc;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ba1ad805a73ad08b26465d568e4260be\",\"css\":\"0c9ea4a87a3788f62f7e4e6e63c80a5e\",\"combined\":\"7bbf08407b099e10b048427d2c2da4a3\"}',
            ],
            [
                'layout_id' => 16,
                'user_id' => 1,
                'name' => 'Admin Templates Adicionar Layouts',
                'id' => 'admin-templates-adicionar-layouts',
                'language' => 'pt-br',
                'module' => 'admin-templates',
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"field\">
    <label>#form-category-label#</label>
    <span>#select-category#</span>
</div>
<div class=\"field\">
    <label>#form-default-label#</label>
    <div class=\"ui toggle checkbox\">
        <input type=\"checkbox\" name=\"padrao\" data-checked=\"\">
        <label>&nbsp;</label>
    </div>
</div>
<div class=\"field\">
    <label>#form-template-layout-label#</label>
    <span>#select-templates_layouts#</span>
</div>
<div class=\"field\">
    <label>#form-thumbnail-label#</label>
    <span>#imagepick-thumbnail#</span>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"28fc5a6b25095dd34a3dba6674a08f0e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"28fc5a6b25095dd34a3dba6674a08f0e\"}',
            ],
            [
                'layout_id' => 17,
                'user_id' => 1,
                'name' => 'Admin Templates Editar Layout',
                'id' => 'admin-templates-editar-layout',
                'language' => 'pt-br',
                'module' => 'admin-templates',
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'layout_id' => 18,
                'user_id' => 1,
                'name' => 'Admin Templates Editar Layouts',
                'id' => 'admin-templates-editar-layouts',
                'language' => 'pt-br',
                'module' => 'admin-templates',
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\" value=\"#nome#\">
</div>
<div class=\"field\">
    <label>#form-category-label#</label>
    <span>#select-category#</span>
</div>
<div class=\"field\">
    <label>#form-default-label#</label>
    <div class=\"ui toggle checkbox\">
        <input type=\"checkbox\" name=\"padrao\" data-checked=\"#padrao#\">
        <label>&nbsp;</label>
    </div>
</div>
<div class=\"field\">
    <label>#form-template-layout-label#</label>
    <span>#select-templates_layouts#</span>
</div>
<div class=\"field\">
    <label>#form-thumbnail-label#</label>
    <span>#imagepick-thumbnail#</span>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"8864fd0e462b55ed7dd02b9ee03fcbba\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"8864fd0e462b55ed7dd02b9ee03fcbba\"}',
            ],
            [
                'layout_id' => 19,
                'user_id' => 1,
                'name' => 'Layouts',
                'id' => 'layouts',
                'language' => 'pt-br',
                'module' => 'layouts',
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'layout_id' => 20,
                'user_id' => 1,
                'name' => 'Layouts Adicionar',
                'id' => 'layouts-adicionar',
                'language' => 'pt-br',
                'module' => 'layouts',
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"ui top attached tabular menu\">
    <a class=\"active item\" data-tab=\"codigo-html\" data-tooltip=\"#form-html-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-html-label#</a>
    <a class=\"item\" data-tab=\"css\" data-tooltip=\"#form-css-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-css-label#</a>
</div>
<div class=\"ui bottom attached active tab segment\" data-tab=\"codigo-html\">
    <textarea class=\"codemirror-html\" name=\"html\"></textarea>
</div>
<div class=\"ui bottom attached tab segment\" data-tab=\"css\">
    <textarea class=\"codemirror-css\" name=\"css\"></textarea>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"65578a67da1ba8603be9c6c030c28af7\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"65578a67da1ba8603be9c6c030c28af7\"}',
            ],
            [
                'layout_id' => 21,
                'user_id' => 1,
                'name' => 'Layouts Editar',
                'id' => 'layouts-editar',
                'language' => 'pt-br',
                'module' => 'layouts',
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\" value=\"#nome#\">
</div>
<div class=\"ui top attached tabular menu\">
    <a class=\"active item\" data-tab=\"codigo-html\" data-tooltip=\"#form-html-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-html-label#</a>
    <a class=\"item\" data-tab=\"css\" data-tooltip=\"#form-css-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-css-label#</a>
</div>
<div class=\"ui bottom attached active tab segment\" data-tab=\"codigo-html\">#pagina-html-backup# <textarea class=\"codemirror-html\" name=\"html\">#pagina-html#</textarea>
</div>
<div class=\"ui bottom attached tab segment\" data-tab=\"css\">#pagina-css-backup# <textarea class=\"codemirror-css\" name=\"css\">#pagina-css#</textarea>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ba1ad805a73ad08b26465d568e4260be\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ba1ad805a73ad08b26465d568e4260be\"}',
            ],
        ];

        $table = $this->table('layouts');
        $table->insert($data)->saveData();
    }
}
