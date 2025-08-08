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
<!-- Teste de modificação 2025-08-07 21:37:46 -->
<!-- Teste de modificação 2025-08-08 12:53:59 -->',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.2',
                'checksum' => '{\"html\":\"f49a4fe4098bb2bcb483f5bbdeb0284f\",\"css\":\"73bc8b1f78e73e33830c8792761384bb\",\"combined\":\"18ea3b77c62817700edef37d9f0a9288\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"639464b69c7768bb89c9652ecd6da096\",\"css\":\"88642c85f3bc488b15cd109241606867\",\"combined\":\"d337e6be29531f9e2b748dd4f5a05c19\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"f743895944e00181febbefdd49eb7e50\",\"css\":\"fc98eb0f2b970c15499298cbaa499bee\",\"combined\":\"f12d1f1470f467f6f8e5b78cb2fd27e3\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"8bffae94e9a3b69f12522c2b68384b8e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"8bffae94e9a3b69f12522c2b68384b8e\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ace732bdb0ba1dd5842d8d600d5bffc5\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ace732bdb0ba1dd5842d8d600d5bffc5\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"8bffae94e9a3b69f12522c2b68384b8e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"8bffae94e9a3b69f12522c2b68384b8e\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d206efb560c38d53740e2871bf4264a4\",\"css\":\"88642c85f3bc488b15cd109241606867\",\"combined\":\"25717d5c19b341405f08279c19fc051f\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"328c4995ff1810fccd4df18225066e46\",\"css\":\"d5d5d38721ee6fea53960fe36904d0e3\",\"combined\":\"20a7a95ff85d56d0eeed6329aafe9c65\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"8bffae94e9a3b69f12522c2b68384b8e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"8bffae94e9a3b69f12522c2b68384b8e\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"6e2e765d093a4dfd4dd86025cb02c2a8\",\"css\":\"7a9c7627d747baa027d08d1461ca81fb\",\"combined\":\"1f2230d3fd2475858bf40743355878f2\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"7fdbde7c2f3ca6785743b3375d41fce4\",\"css\":\"4d4cad94393a040c501f6d712cbfb244\",\"combined\":\"160c0e3c7cb572c9dbc780aa229e4838\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"eed7eaf5d551bb8d66ec2ed5caead325\",\"css\":\"5c332c9422840d4658a492a7a8d5e6a8\",\"combined\":\"3947c804beef3afdfe09a50266c03f1b\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"3662c00cd39c81d8f43aedff4270a71a\",\"css\":\"ce06862621b92714d6717376aba8c2ab\",\"combined\":\"38cefe605f247ed9cd7360bb17ba7e29\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"77ecd4a57a138f62f7aba2a41ad07d10\",\"css\":\"790844bbf8e5096b40503eac9a60d721\",\"combined\":\"053ec3ebacc2893ab4e9e79939862a59\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ea42d1dbc1b0bd7bc10f0335d2b5921c\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ea42d1dbc1b0bd7bc10f0335d2b5921c\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"c3a3143394040c9ff4f164ec199b2d55\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"c3a3143394040c9ff4f164ec199b2d55\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"3662c00cd39c81d8f43aedff4270a71a\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"3662c00cd39c81d8f43aedff4270a71a\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"77ecd4a57a138f62f7aba2a41ad07d10\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"77ecd4a57a138f62f7aba2a41ad07d10\"}',
            ],
        ];

        $table = $this->table('layouts');
        $table->insert($data)->saveData();
    }
}
