<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class PagesSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'page_id' => 1,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Teste Coluna Centralizada e Tabela',
                'id' => 'teste-coluna-centralizada',
                'language' => 'pt-br',
                'path' => 'teste-coluna-centralizada/',
                'type' => 'system',
                'module' => null,
                'option' => 'editar',
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"ui three column grid stackable\">
    <div class=\"two wide column\"></div>
    <div class=\"twelve wide column\">
        <table class=\"ui very basic fixed table\">
            <tbody>
                <tr>
                    <td>Coluna 1 Linha 1</td>
                    <td>Coluna 2 Linha 1</td>
                    <td class=\"ten wide\">É um fato conhecido de todos que um leitor se distrairá com o conteúdo de texto legível de uma página quando estiver examinando sua diagramação. A vantagem de usar Lorem Ipsum é que ele tem uma distribuição normal de letras, ao contrário de \"Conteúdo aqui, conteúdo aqui\", fazendo com que ele tenha uma aparência similar a de um texto legível. Muitos softwares de publicação e editores de páginas na internet agora usam Lorem Ipsum como texto-modelo padrão, e uma rápida busca por \'lorem ipsum\' mostra vários websites ainda em sua fase de construção. Várias versões novas surgiram ao longo dos anos, eventualmente por acidente, e às vezes de propósito (injetando humor, e coisas do gênero). </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class=\"two wide column\"></div>
</div>',
                'css' => 'input,teste{
	width:auto !important;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"67a3785c1c5150f9a94e2c596b3ec987\",\"css\":\"23032b3d6fd258d5a4db5d8b7bbfa614\",\"combined\":\"43441e383198ba42cf70c0416cfc69c9\"}',
            ],
            [
                'page_id' => 2,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Contato',
                'id' => 'contato',
                'language' => 'pt-br',
                'path' => 'contato/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"ui header\">Contato</div>
<p>Descritivo</p>
<p><button class=\"ui button\">Bot&atilde;o</button></p>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"c355467d108bf96e9a95c46df4e3a692\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"c355467d108bf96e9a95c46df4e3a692\"}',
            ],
            [
                'page_id' => 3,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Teste Variável Global 2',
                'id' => 'teste-variavel-global',
                'language' => 'pt-br',
                'path' => 'teste-variavel-global/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<p>Teste novo porra @[[variavel-global]]@ que deve ser @[[variavel-novo]]@ como deve ser.</p>
<p>Mas será que dá certo @[[variavel-nova]]@ , sei lá</p>
<p>Dinovo!!!</p>',
                'css' => 'p{
    width:@[[variavel-nova]]@;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"effb09176d8398846c04651fdac0168e\",\"css\":\"75cccb47d580ac36844615427b70963b\",\"combined\":\"a3079d4ef5648f3f7be6e1b7ea28a387\"}',
            ],
            [
                'page_id' => 4,
                'user_id' => 1,
                'layout_id' => null,
                'name' => '404 - Página Não Encontrada',
                'id' => '404-pagina-nao-encontrada',
                'language' => 'pt-br',
                'path' => '404-pagina-nao-encontrada/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<h1>404</h1>
<h2>P&aacute;gina n&atilde;o encontrada!</h2>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"b2eba34fbd33750287623a2ebc97ea91\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"b2eba34fbd33750287623a2ebc97ea91\"}',
            ],
            [
                'page_id' => 5,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Página Raiz do Sistema',
                'id' => 'pagina-raiz-do-sistema',
                'language' => 'pt-br',
                'path' => 'pagina-raiz-do-sistema/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<h1>Bem vindos ao Entrey.com.br</h1>
<p>Descrição...</p>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"064c8aaac37b68af07a3c56caba1c9d9\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"064c8aaac37b68af07a3c56caba1c9d9\"}',
            ],
            [
                'page_id' => 6,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Dashboard Caixa',
                'id' => 'dashboard-caixa',
                'language' => 'pt-br',
                'path' => 'dashboard-caixa/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"ui segment\">
    <div class=\"ui two columns grid\">
        <div class=\"column\">
            <div class=\"ui large header\"> 15 <div class=\"sub header\">Novos Clientes</div>
            </div>
        </div>
        <div class=\"right aligned column\">
            <i class=\"comments outline huge grey icon\"></i>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"61b374f2ce0cd067ed224dddfb815f3e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"61b374f2ce0cd067ed224dddfb815f3e\"}',
            ],
            [
                'page_id' => 7,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Emissão',
                'id' => 'emissao',
                'language' => 'pt-br',
                'path' => 'emissao/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<!DOCTYPE html>
<html>

<head>
    <!-- pagina#titulo -->
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <link rel=\"stylesheet\" type=\"text/css\" href=\"https://cdn.jsdelivr.net/npm/fomantic-ui@2.8.7/dist/semantic.min.css\">
    <!-- pagina#css -->
    <!-- pagina#js -->
    <style>
        .colunatop {
            background-color: #52606D;
        }

        .colunabottom {
            background-color: #E4E7EB;
            position: relative;
            top: 90%;
        }
    </style>
</head>

<body>
    <div class=\"colunatop\">
        <div class=\"colunatop ui three column padded stackable grid\">
            <div class=\"row\">
                <div class=\"three wide right aligned column\">
                    <div class=\"logo\">
                        <a href=\"#\">
                            <img src=\"https://platform.b2make.com/images/gestor/logo.png\">
                        </a>
                    </div>
                </div>
                <div class=\"ten wide column\">
                    <div class=\"ui grid\">
                        <div class=\"column row\">
                            <div class=\"center aligned two column row\">
                                <div class=\"ui ordered steps\">
                                    <div class=\"disabled step\">
                                        <div class=\"content\">
                                            <div class=\"title\">Carrinho</div>
                                        </div>
                                    </div>
                                    <div class=\"disabled step\">
                                        <div class=\"content\">
                                            <div class=\"title\">Identificação</div>
                                        </div>
                                    </div>
                                    <div class=\"active step\">
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
                </div>
                <div class=\"three wide column\">
                    <div class=\"ui grid\">
                        <div class=\"two column row\">
                            <div class=\"right aligned column\">
                                <i class=\"large user icon\"></i>
                                <span style=\"color:#ffffffde;\">Nome Usuário</span>
                            </div>
                            <div class=\"left column aligned\">
                                <i class=\"large shopping cart icon\"></i>
                                <span style=\"color:#ffffffde;\">Carrinho</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class=\"ui padded grid\">
        <div class=\"column\"></div>
        <div class=\"eight wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui sixteen wide columns grid\">
                        <div class=\"three wide column\">
                            <div class=\"row\">
                                <div class=\"ui placeholder\">
                                    <div class=\"image\"></div>
                                </div>
                            </div>
                        </div>
                        <div class=\"thirteen wide centered column\">
                            <div class=\"row\">
                                <div class=\"ui two column very relaxed grid\">
                                    <div class=\"column\">
                                        <h3 class=\"ui header\">
                                            Quiropraxia para Pequenos Animais
                                        </h3>
                                    </div>
                                    <div class=\"right aligned column\">
                                        <button class=\"ui icon button\">
                                            <i class=\"black pen icon\"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class=\"ui sixteen wide columns grid\">
                    <div class=\"eight wide column\">
                        <div class=\"row\">
                            <button class=\"ui fluid button\">
                                Uso Próprio
                            </button>
                        </div>
                    </div>
                    <div class=\"eight wide column\">
                        <div class=\"row\">
                            <button class=\"ui fluid button\">
                                Para terceiros
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"six wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <select class=\"ui fluid search dropdown\">
                        <option value=\"\">Resumo do Pedidos</option>
                    </select>
                    <div class=\"ui divider\"></div>
                    <div class=\"ui sixteen wide columns grid\">
                        <div class=\"row\">
                            <div class=\"two column\">
                                <i class=\"big black file alternate icon\"></i>
                            </div>
                            <div class=\"fourteen wide column\">
                                <h5>1x Curso de Extensão de Odontologia Integrativa - VALOR ATÉ DIA 05/02</h5>
                                <h3>Subtotal: R$200,00</h3>
                            </div>
                        </div>
                    </div>

                    <div class=\"ui divider\"></div>
                    <h5> Subtotal: R$1.850,00</h5>
                    <h5> Desconto: R$200,00</h5>
                    <div class=\"ui divider\"></div>
                    <h3> Total: R$1.650,00</h3>
                    <div class=\"ui divider\"></div>
                    <button class=\"fluid ui button\">Prosseguir para identificação</button>
                </div>
            </div>
        </div>
        <div class=\"column\"></div>
    </div>

    <div class=\"colunabottom\">
        <div class=\"ui four column grid padded stackable\">
            <div class=\"one wide column\"></div>
            <div class=\"seven wide column\">
                <div class=\"ui two column grid\">
                    <div class=\"column\">
                        FHLoja
                        Rua Florêncio de Abreu, 1112 - (16) 2101-5497
                        65.567.935/0001-08 - Centro - Ribeirão Preto - SP - Brasil
                        Todos os direitos reservados - 2020
                    </div>
                    <div class=\"column\"></div>
                </div>
            </div>
            <div class=\"seven wide right aligned column\">
                Formas de Pagamento
            </div>
            <div class=\"one wide column\"></div>
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
                'checksum' => '{\"html\":\"b5f5358b87204b368105c51d7cf5e15a\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"b5f5358b87204b368105c51d7cf5e15a\"}',
            ],
            [
                'page_id' => 8,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pagina TESTE de Layout Carrinho',
                'id' => 'pagina-teste-de-layout',
                'language' => 'pt-br',
                'path' => 'pagina-teste-de-layout/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"desktopcode\">
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui padded grid\">
        <div class=\"column\"></div>
        <div class=\"eight wide column\">
            <!-- cel-servicos < -->
            <div class=\"ui segment\">
                <div class=\"excluir\">
                    <i class=\"big times circle icon\"></i>
                </div>
                <div class=\"ui grid\">
                    <div class=\"four wide column\">
                        <!--Foto do curso/produto-->
                        <div class=\"img-produto\">
                            <div class=\"ui placeholder\">
                                <div class=\"image\"></div>
                            </div>
                        </div>
                    </div>
                    <div class=\"twelve wide column\">
                        <div class=\"ui grid\">
                            <div class=\"thirteen wide column\">
                                <h3 class=\"ui header\">#nome-curso/produto#</h3>
                            </div>
                            <div class=\"three wide right aligned column\">
                                <button class=\"ui icon button\">
                                    <i class=\"plus circle icon\"></i>
                                </button>
                            </div>
                        </div>
                        <div class=\"ui grid\">
                            <div class=\"thirteen wide column\">
                                <h5 class=\"ui header\">R$: #preço#</h5>
                            </div>
                            <div class=\"three wide right aligned column\">
                                <div class=\"ui tiny header\">
                                    <div class=\"quantidade\">#</div>
                                </div>
                            </div>
                        </div>
                        <div class=\"ui grid\">
                            <div class=\"thirteen wide column\">
                                <h5 class=\"ui header\">Subtotal: R$ #subtotal# </h5>
                            </div>
                            <div class=\"three wide right aligned column\">
                                <button class=\"ui icon button\">
                                    <i class=\"minus circle icon\"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- cel-servicos > -->
        </div>
        <div class=\"six wide column\">
            <div class=\"ui segment\">
                <h3>Resumo do Pedido</h3>
                <div class=\"ui divider\"></div>
                <!-- cel-servicos < -->
                <div class=\"ui grid\">
                    <div class=\"row\">
                        <div class=\"three wide column\">
                            <i class=\"huge black file alternate icon\"></i>
                        </div>
                        <div class=\"thirteen wide column\">
                            <h5>#nome-do-curso#</h5>
                            <h3>Subtotal: R$ #subtotal#</h3>
                        </div>
                    </div>
                </div><!-- cel-servicos > -->
                <div class=\"ui divider\"></div>
                <h5>Subtotal: R$ #subtotal#</h5>
                <h5>Desconto: R$ #desconto#</h5>
                <div class=\"ui divider\"></div>
                <h3>Total: R$ #total#</h3>
                <div class=\"ui divider\"></div>
                <button class=\"fluid ui black  button\">Prosseguir para identificação</button>
                <button class=\"fluid ui button\">Continuar comprando</button>
            </div>
        </div>
        <div class=\"column\"></div>
    </div>
</div>
<div class=\"mobilecode\">
    <!-- cel-servicos < -->
    <div class=\"ui padded grid\">
        <div class=\"sixteen wide column\">
            <div class=\"ui segment\">
                <div class=\"excluir\">
                    <i class=\"big times circle icon\"></i>
                </div>
                <div class=\"ui grid\">
                    <div class=\"five wide column\">
                        <!--Foto do curso/produto-->
                        <div class=\"img-produto\">
                            <div class=\"ui placeholder\">
                                <div class=\"image\"></div>
                            </div>
                        </div>
                    </div>
                    <div class=\"eleven wide column\">
                        <div class=\"ui grid\">
                            <div class=\"eleven wide column\">
                                <h3 class=\"ui header\">#nome-curso/produto#</h3>
                            </div>
                            <div class=\"five wide right aligned column\">
                                <button class=\"ui icon button\">
                                    <i class=\"plus circle icon\"></i>
                                </button>
                            </div>
                        </div>
                        <div class=\"ui grid\">
                            <div class=\"eleven wide column\">
                                <h5 class=\"ui header\">R$: #preço#</h5>
                            </div>
                            <div class=\"five wide right aligned column\">
                                <div class=\"ui tiny header\">
                                    <div class=\"quantidade\">#</div>
                                </div>
                            </div>
                        </div>
                        <div class=\"ui grid\">
                            <div class=\"eleven wide column\">
                                <h5 class=\"ui header\">Subtotal: R$ #subtotal# </h5>
                            </div>
                            <div class=\"five wide right aligned column\">
                                <button class=\"ui icon button\">
                                    <i class=\"minus circle icon\"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- cel-servicos > -->
            <div class=\"ui segment\">
                <h3>Resumo do Pedido</h3>
                <div class=\"ui divider\"></div>
                <!-- cel-servicos < -->
                <div class=\"ui sixteen wide columns grid\">
                    <div class=\"row\">
                        <div class=\"two column\">
                            <i class=\"big black file alternate icon\"></i>
                        </div>
                        <div class=\"fourteen wide column\">
                            <h5>#nome-do-curso#</h5>
                            <h3>Subtotal: R$ #subtotal#</h3>
                        </div>
                    </div>
                </div><!-- cel-servicos > -->
                <div class=\"ui divider\"></div>
                <h5>Subtotal: R$ #subtotal#</h5>
                <h5>Desconto: R$ #desconto#</h5>
                <div class=\"ui divider\"></div>
                <h3>Total: R$ #total#</h3>
                <div class=\"ui divider\"></div>
                <button class=\"fluid ui black button\">Prosseguir para identificação</button>
                <button class=\"fluid ui button\">Continuar comprando</button>
            </div>
        </div>
    </div>
</div>',
                'css' => '.excluir {
    position: absolute;
    left: -17px;
    top: 10px;
    background-color:#FFF;
    cursor:pointer;
}
.segment > .button {
    margin-bottom: 0.75em;
}
.quantidade{
    width:40px;
    text-align:center;
    float:right;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"3440f26e5ad59ab07b0b2ceb1cbc59d4\",\"css\":\"5ab60cf2df94d33b38a9d6e3bfe96c01\",\"combined\":\"709c48258d716fe4ee17ff16b307113a\"}',
            ],
            [
                'page_id' => 9,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pagina TESTE de Layout Emissão',
                'id' => 'pagina-teste-de-layout-emissao',
                'language' => 'pt-br',
                'path' => 'pagina-teste-de-layout-emissao/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"desktopcode\">
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui padded grid\">
        <div class=\"column\"></div>
        <div class=\"eight wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui sixteen wide columns grid\">
                        <div class=\"four wide column\">
                            <!--Foto do curso/produto-->
                            <div class=\"p\">
                                <div class=\"ui placeholder\">
                                    <div class=\"image\"></div>
                                </div>
                            </div>
                        </div>
                        <div class=\"twelve wide centered column\">
                            <div class=\"row\">
                                <div class=\"ui sixteen wide columns grid\">
                                    <div class=\"thirteen wide column\">
                                        <h3 class=\"ui header\">#nome-do-curso/produto#</h3>
                                    </div>
                                    <div class=\"right aligned three wide column\">
                                        <button class=\"ui icon button\">
                                            <i class=\"black pen icon\"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class=\"ui sixteen wide columns grid\">
                    <div class=\"eight wide column\">
                        <div class=\"row\">
                            <button class=\"ui fluid button\">Uso Próprio</button>
                        </div>
                    </div>
                    <div class=\"eight wide column\">
                        <div class=\"row\">
                            <button class=\"ui fluid button\">Para terceiros</button>
                        </div>
                    </div>
                </div>
                <div class=\"ui hidden divider\"></div>
                <!--Formulario da emissão, aparecer/desaparecer ao clicar no botao editar-->
                <form class=\"ui form\">
                    <div class=\"field\">
                        <input type=\"text\" name=\"Nome\" placeholder=\"Nome\">
                    </div>
                    <div class=\"field\">
                        <input type=\"text\" name=\"Documento\" placeholder=\"Documento\">
                    </div>
                    <div class=\"field\">
                        <input type=\"text\" name=\"Telefone\" placeholder=\"Telefone\">
                    </div>
                    <div class=\"ui sixteen wide columns grid\">
                        <div class=\"eight wide column\">
                            <div class=\"row\">
                                <button class=\"ui fluid button\">Cancelar</button>
                            </div>
                        </div>
                        <div class=\"eight wide column\">
                            <div class=\"row\">
                                <button class=\"ui fluid black button\">Salvar</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class=\"six wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <h3>Resumo do Pedido</h3>
                    <div class=\"ui divider\"></div>
                    <!-- cel-servicos < -->
                    <div class=\"ui grid\">
                        <div class=\"row\">
                            <div class=\"three wide column\">
                                <i class=\"huge black file alternate icon\"></i>
                            </div>
                            <div class=\"thirteen wide column\">
                                <h5>#nome-do-curso#</h5>
                                <h3>Subtotal: R$ #subtotal#</h3>
                            </div>
                        </div>
                    </div><!-- cel-servicos > -->
                    <div class=\"ui divider\"></div>
                    <h5>Subtotal: R$ #subtotal#</h5>
                    <h5>Desconto: R$ #desconto#</h5>
                    <div class=\"ui divider\"></div>
                    <h3>Total: R$ #total#</h3>
                    <div class=\"ui divider\"></div>
                    <button class=\"fluid ui black  button\">Prosseguir para identificação</button>
                </div>
            </div>
        </div>
        <div class=\"column\"></div>
    </div>
</div>
<div class=\"mobilecode\">
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui padded grid\">
        <div class=\"sixteen wide column\">
            <div class=\"ui segment\">
                <div class=\"ui sixteen wide columns grid\">
                    <div class=\"three wide column\">
                        <div class=\"row\">
                            <!--Foto do curso/produto-->
                            <div class=\"p\">
                                <div class=\"ui placeholder\">
                                    <div class=\"image\"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class=\"thirteen wide centered column\">
                        <div class=\"row\">
                            <div class=\"ui sixteen wide columns grid\">
                                <div class=\"thirteen wide column\">
                                    <h3 class=\"ui header\">#nome-do-curso/produto#</h3>
                                </div>
                                <div class=\"right aligned three wide column\">
                                    <button class=\"ui icon button\">
                                        <i class=\"black pen icon\"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class=\"ui sixteen wide columns grid\">
                <div class=\"eight wide column\">
                    <div class=\"row\">
                        <button class=\"ui fluid button\">Uso Próprio</button>
                    </div>
                </div>
                <div class=\"eight wide column\">
                    <div class=\"row\">
                        <button class=\"ui fluid button\">Para terceiros</button>
                    </div>
                </div>
            </div>
            <div class=\"ui hidden divider\"></div>
            <!--Formulario da emissão, aparecer/desaparecer ao clicar no botao editar-->
            <form class=\"ui form\">
                <div class=\"field\">
                    <input type=\"text\" name=\"Nome\" placeholder=\"Nome\">
                </div>
                <div class=\"field\">
                    <input type=\"text\" name=\"Documento\" placeholder=\"Documento\">
                </div>
                <div class=\"field\">
                    <input type=\"text\" name=\"Telefone\" placeholder=\"Telefone\">
                </div>
                <div class=\"ui sixteen wide columns grid\">
                    <div class=\"eight wide column\">
                        <div class=\"row\">
                            <button class=\"ui fluid button\">Cancelar</button>
                        </div>
                    </div>
                    <div class=\"eight wide column\">
                        <div class=\"row\">
                            <button class=\"ui fluid black button\">Salvar</button>
                        </div>
                    </div>
                </div>
            </form>
            <div class=\"ui hidden divider\"></div>
            <div class=\"ui  grid\">
                <div class=\"sixteen wide column\">
                    <div class=\"ui segment\">
                        <h3>Resumo do Pedido</h3>
                        <div class=\"ui divider\"></div>
                        <div class=\"ui sixteen wide columns grid\">
                            <div class=\"row\">
                                <div class=\"two column\">
                                    <i class=\"big black file alternate icon\"></i>
                                </div>
                                <div class=\"fourteen wide column\">
                                    <h5>#nome-do-curso/produto#</h5>
                                    <h3>Subtotal: R$ #subtotal#</h3>
                                </div>
                            </div>
                        </div>
                        <div class=\"ui divider\"></div>
                        <h5>Subtotal: R$ #sub-total#</h5>
                        <h5>Desconto: R$ #desconto#</h5>
                        <div class=\"ui divider\"></div>
                        <h3>Total: R$ #total#</h3>
                        <div class=\"ui divider\"></div>
                        <button class=\"fluid ui black button\">Prosseguir para identificação</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>',
                'css' => '.p{
    width: 110px;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"4586880f460aa43b6edc40d1f43e6f2f\",\"css\":\"510b7776ce45448b1168408b605b8f81\",\"combined\":\"f642dc579bce821ed1258da8ffa44834\"}',
            ],
            [
                'page_id' => 10,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pagina TESTE de Layout Meus Pedidos',
                'id' => 'pagina-teste-de-layout-meus-pedidos',
                'language' => 'pt-br',
                'path' => 'pagina-teste-de-layout-meus-pedidos/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"ui four column grid padded stackable\">
    <div class=\"one wide column\"></div>
    <div class=\"seven wide column\">
        <div class=\"ui segment\">
            <!--Foto do curso/produto-->
            <div class=\"ui fluid placeholder\">
                <div class=\"image\"></div>
            </div>
            <div class=\"ui two column grid\">
                <div class=\"column\">
                    <h5>#data#</h5>
                </div>
                <div class=\"right aligned column\">
                    <i class=\"bookmark icon\"></i>
                </div>
            </div>
            <h3>#nome-do-curso/produto#</h3>
            <div class=\"ui five column grid\">
                <div class=\"column\">
                    <div class=\"pedido-btn\">
                        <i class=\"large user icon\"></i> Pessoas
                    </div>
                </div>
                <div class=\"column\">
                    <i class=\"large envelope outline icon\"></i> Enviar
                </div>
                <div class=\"column\">
                    <i class=\"large print icon\"></i> Imprimir
                </div>
                <div class=\"column\">
                    <i class=\"large gift icon\"></i> Presente
                </div>
                <div class=\"column\">
                    <i class=\"large info icon\"></i> Visualizar
                </div>
            </div>
        </div>
    </div>
    <div class=\"seven wide column\">
        <div class=\"ui segment\">
            <!--Foto do curso/produto-->
            <div class=\"ui fluid placeholder\">
                <div class=\"image\"></div>
            </div>
            <div class=\"ui two column grid\">
                <div class=\"column\">
                    <h5>#data#</h5>
                </div>
                <div class=\"right aligned column\">
                    <i class=\"bookmark icon\"></i>
                </div>
            </div>
            <h3>#nome-do-curso/produto#</h3>
            <div class=\"ui five column grid\">
                <div class=\"column\">
                    <i class=\"large credit card outline icon\"></i> Pagar
                </div>
                <div class=\"column\">
                    <i class=\"large info icon\"></i> Detalhes
                </div>
                <div class=\"column\"></div>
                <div class=\"column\"></div>
                <div class=\"column\"></div>
            </div>
        </div>
    </div>
    <div class=\"one wide column\"></div>
</div>',
                'css' => '.pedido-btn{
    cursor:pointer;
    min-width:40px;
    white-space: nowrap;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"224acbac54cfd3adb0c1123f8cd19486\",\"css\":\"bf83cdc127255f7331d6105783b20ba5\",\"combined\":\"8c516e856d7b1df588af12d704b99ecd\"}',
            ],
            [
                'page_id' => 11,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pagina TESTE de Layout Pagamento',
                'id' => 'pagina-teste-de-layout-pagamento',
                'language' => 'pt-br',
                'path' => 'pagina-teste-de-layout-pagamento/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"ui hidden divider\"></div>
<div class=\"ui hidden divider\"></div>
<div class=\"ui padded grid\">
    <div class=\"column\"></div>
    <div class=\"eight wide column\">
        <div class=\"row\">
            <!--wireframe pagamento-->
            <div class=\"ui segment\">
                <div class=\"ui fluid steps\">
                    <div class=\"active step\">
                        <i class=\"payment icon\"></i>
                        <div class=\"content\">
                            <div class=\"title\">Cartão de Crédito Próprio</div>
                        </div>
                    </div>
                    <div class=\"disabled step\">
                        <i class=\"payment icon\"></i>
                        <div class=\"content\">
                            <div class=\"title\">Cartão de Crédito de Terceiro</div>
                        </div>
                    </div>
                    <div class=\"disabled step\">
                        <i class=\"paypal icon\"></i>
                        <div class=\"content\">
                            <div class=\"title\">PayPal</div>
                        </div>
                    </div>
                </div>
                <!--Cartão de crédito próprio -->
                <form class=\"ui form\">
                    <div class=\"field\">
                        <input type=\"text\" name=\"Número do cartão\" placeholder=\"Número do cartão\">
                    </div>
                    <div class=\"field\">
                        <label>Nome do títular do cartão</label>
                        <div class=\"two fields\">
                            <div class=\"field\">
                                <input type=\"text\" name=\"Nome\" placeholder=\"Nome\">
                            </div>
                            <div class=\"field\">
                                <input type=\"text\" name=\"Sobrenome\" placeholder=\"Sobrenome\">
                            </div>
                        </div>
                    </div>
                    <div class=\"fields\">
                        <div class=\"ten wide field\">
                            <label>Vencimento</label>
                            <div class=\"two fields\">
                                <div class=\"field\">
                                    <select class=\"ui fluid search dropdown\" name=\"MM\">
                                        <option value=\"\">Mês</option>
                                    </select>
                                </div>
                                <div class=\"field\">
                                    <select class=\"ui fluid search dropdown\" name=\"AA\">
                                        <option value=\"\">Ano</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class=\"six wide field\">
                            <label>Código de segurança (CSC)</label>
                            <input type=\"text\" name=\"3 dígitos\" placeholder=\"3 dígitos\">
                        </div>
                    </div>
                    <div class=\"ui fluid placeholder\">1x de R$ 1,50</div> <!-- Valores da parcela  -->
                    <div class=\"ui hidden divider\"></div>
                    <label>Suas informações serão coletadas de acordo com a Política de Privacidade do PayPal.</label>
                    <div class=\"ui hidden divider\"></div>
                    <div class=\"field\">
                        <div class=\"ui checkbox\">
                            <input type=\"checkbox\" tabindex=\"0\" class=\"hidden\">
                            <label>Salve o número do meu cartão de crédito para a próxima compra neste vendedor.</label>
                        </div>
                    </div>
                </form>
                <div class=\"ui hidden divider\"></div>
                <!--Cartão de crédito de terceiros -->
                <p>Preencha as informações pessoais do outro pagador desta compra e clique no botão CONTINUAR.</p>
                <form class=\"ui form\">
                    <div class=\"field\">
                        <input type=\"text\" name=\"Primeiro Nome\" placeholder=\"Primeiro Nome\">
                    </div>
                    <div class=\"field\">
                        <input type=\"text\" name=\"Último Nome\" placeholder=\"Último Nome\">
                    </div>
                    <div class=\"field\">
                        <input type=\"text\" name=\"Email\" placeholder=\"Email\">
                    </div>
                    <div class=\"field\">
                        <input type=\"text\" name=\"Telefone\" placeholder=\"Telefone\">
                    </div>
                    <div class=\"field\">
                        <div class=\"ui buttons\">
                            <button class=\"ui button\">CPF</button>
                            <button class=\"ui button\">CNPJ</button>
                        </div>
                    </div>
                    <div class=\"field\">
                        <input type=\"text\" name=\"CPF\" placeholder=\"CPF\">
                    </div>
                </form>
                <!--wireframe paypal -->
                <div class=\"ui fluid placeholder\">
                    <div class=\"image\">IMAGEM PAYPAL</div>
                </div>
            </div>
        </div>
    </div>
    <div class=\"six wide column\">
        <div class=\"row\">
            <div class=\"ui segment\">
                <h3>Resumo do Pedidos</h3>
                <div class=\"ui divider\"></div>
                <div class=\"ui sixteen wide columns grid\">
                    <div class=\"row\">
                        <div class=\"two column\">
                            <i class=\"big black file alternate icon\"></i>
                        </div>
                        <div class=\"fourteen wide column\">
                            <h5>#nome-do-curso/produto#</h5>
                            <h3>#Subtotal:R$#</h3>
                        </div>
                    </div>
                </div>
                <div class=\"ui divider\"></div>
                <h5> #Subtotal: R$#</h5>
                <h5> #Desconto: R$#</h5>
                <div class=\"ui divider\"></div>
                <h3> #Total: R$#</h3>
                <div class=\"ui divider\"></div>
                <button class=\"fluid ui button\">Prosseguir para identificação</button>
            </div>
        </div>
    </div>
    <div class=\"column\"></div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"398c7a188374df44662a81a5d27f0fca\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"398c7a188374df44662a81a5d27f0fca\"}',
            ],
            [
                'page_id' => 12,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pagina TESTE de Layout Cadastro Identificação',
                'id' => 'pagina-teste-de-layout-cadastro',
                'language' => 'pt-br',
                'path' => 'pagina-teste-de-layout-cadastro/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"desktopcode\">
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui grid\">
        <div class=\"four wide column\"></div>
        <div class=\"eight wide column\">
            <form class=\"ui form\">
                <div class=\"field\">
                    <h2>Cadastro</h2>
                    <input type=\"text\" name=\"Nome\" placeholder=\"Nome\">
                </div>
                <div class=\"field\">
                    <input type=\"text\" name=\"Sobrenome\" placeholder=\"Sobrenome\">
                </div>
                <div class=\"field\">
                    <input type=\"text\" name=\"Email\" placeholder=\"Email\">
                </div>
                <div class=\"field\">
                    <input type=\"text\" name=\"Senha\" placeholder=\"Senha\">
                </div>
                <div class=\"field\">
                    <input type=\"text\" name=\"Confirmar senha\" placeholder=\"Confirmar senha\">
                </div>
                <div class=\"field\">
                    <div class=\"ui buttons\">
                        <button class=\"ui button\">CPF</button>
                        <button class=\"ui button\">CNPJ</button>
                    </div>
                </div>
                <div class=\"field\">
                    <input type=\"text\" name=\"CPF\" placeholder=\"CPF\">
                </div>
                <button class=\"fluid ui button\">Cadastrar</button>
            </form>
            <div class=\"four wide column\"></div>
        </div>
    </div>
</div>
<div class=\"ui hidden divider\"></div>
<div class=\"mobilecode\">
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui padded grid\">
        <div class=\"sixteen wide column\">
            <form class=\"ui form\">
                <div class=\"field\">
                    <h2>Cadastro</h2>
                    <input type=\"text\" name=\"Nome\" placeholder=\"Nome\">
                </div>
                <div class=\"field\">
                    <input type=\"text\" name=\"Sobrenome\" placeholder=\"Sobrenome\">
                </div>
                <div class=\"field\">
                    <input type=\"text\" name=\"Email\" placeholder=\"Email\">
                </div>
                <div class=\"field\">
                    <input type=\"text\" name=\"Senha\" placeholder=\"Senha\">
                </div>
                <div class=\"field\">
                    <input type=\"text\" name=\"Confirmar senha\" placeholder=\"Confirmar senha\">
                </div>
                <div class=\"field\">
                    <div class=\"ui buttons\">
                        <button class=\"ui fluid button\">CPF</button>
                        <button class=\"ui fluid button\">CNPJ</button>
                    </div>
                </div>
                <div class=\"field\">
                    <input type=\"text\" name=\"CPF\" placeholder=\"CPF\">
                </div>
                <button class=\"fluid ui button\">Cadastrar</button>
            </form>
        </div>
    </div>
</div>
<div class=\"ui hidden divider\"></div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"761796f6db5d5a580a13d26bc2ed7376\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"761796f6db5d5a580a13d26bc2ed7376\"}',
            ],
            [
                'page_id' => 13,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pagina TESTE de Layout Identificação',
                'id' => 'pagina-teste-de-layout-identificacao',
                'language' => 'pt-br',
                'path' => 'pagina-teste-de-layout-identificacao/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"desktopcode\">
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui hidden divider\"></div>
    <form class=\"ui form\">
        <div class=\"ui grid\">
            <div class=\"three wide column\"></div>
            <div class=\"ten wide column\">
                <div class=\"ui two column stackable grid\">
                    <div class=\"row\">
                        <div class=\"left aligned column barraLateral\">
                            <div class=\"field\">
                                <h2>Já sou cliente</h2>
                                <input type=\"text\" name=\"Email\" placeholder=\"Email\">
                            </div>
                            <div class=\"field\">
                                <input type=\"text\" name=\"Senha\" placeholder=\"Senha\">
                            </div>
                            <div class=\"field\">
                                <div class=\"ui two column very relaxed grid\">
                                    <div class=\"column\">
                                        <div class=\"ui checkbox\">
                                            <input type=\"checkbox\" tabindex=\"0\" class=\"hidden\">
                                            <label>Lembrar-me</label>
                                        </div>
                                    </div>
                                    <div class=\"right aligned column\">
                                        <div class=\"column\">
                                            <a href=\"@[[pagina#url-raiz]]@#esqueceu-senha-url#\">Esqueci minha senha</a>
                                        </div>
                                    </div>
                                </div>
                                <div class=\"ui hidden divider\"></div>
                                <div class=\"field\">
                                    <button class=\"fluid ui button\">Login</button>
                                </div>
                            </div>
                        </div>
                        <div class=\"ui divider\"></div>
                        <div class=\"left aligned column\">
                            <div class=\"field\">
                                <h2>Criar uma conta</h2>
                                <input type=\"text\" name=\"Email\" placeholder=\"Email\">
                            </div>
                            <div class=\"field\">
                                <button class=\"fluid ui button\">Cadastrar</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class=\"three wide column\"></div>
            </div>
        </div>
    </form>
</div>
<div class=\"mobilecode\">
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui hidden divider\"></div>
    <form class=\"ui form\">
        <div class=\"ui grid\">
            <div class=\"three wide column\"></div>
            <div class=\"ten wide column\">
                <div class=\"ui two column stackable grid\">
                    <div class=\"row\">
                        <div class=\"left aligned column barraLateral\">
                            <div class=\"field\">
                                <h2>Já sou cliente</h2>
                                <input type=\"text\" name=\"Email\" placeholder=\"Email\">
                            </div>
                            <div class=\"field\">
                                <input type=\"text\" name=\"Senha\" placeholder=\"Senha\">
                            </div>
                            <div class=\"field\">
                                <div class=\"ui two column very relaxed grid\">
                                    <div class=\"column\">
                                        <div class=\"ui checkbox\">
                                            <input type=\"checkbox\" tabindex=\"0\" class=\"hidden\">
                                            <label>Lembrar-me</label>
                                        </div>
                                    </div>
                                    <div class=\"right aligned column\">
                                        <div class=\"column\">
                                            <a href=\"@[[pagina#url-raiz]]@#esqueceu-senha-url#\">Esqueci minha senha</a>
                                        </div>
                                    </div>
                                </div>
                                <div class=\"ui hidden divider\"></div>
                                <div class=\"field\">
                                    <button class=\"fluid ui button\">Login</button>
                                </div>
                            </div>
                        </div>
                        <div class=\"left aligned column\">
                            <div class=\"field\">
                                <div class=\"ui divider\"></div>
                                <h2>Criar uma conta</h2>
                                <input type=\"text\" name=\"Email\" placeholder=\"Email\">
                            </div>
                            <div class=\"field\">
                                <button class=\"fluid ui button\">Cadastrar</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class=\"three wide column\"></div>
            </div>
        </div>
        <div class=\"ui hidden divider\"></div>
        <div class=\"ui hidden divider\"></div>
        <div class=\"ui hidden divider\"></div>
        <div class=\"ui hidden divider\"></div>
    </form>
</div>',
                'css' => '.t {
    border-left: 1px solid lightgrey;
    height: 300px;
    position: absolute;
    left: 601px;
    margin-left: 3px;
    top: 0;
}
.barraLateral {
    border-right: 1px solid lightgrey;
}
@media screen and (max-width: 770px) {
    .barraLateral {
        border-right: none;
    }
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
                'checksum' => '{\"html\":\"2c610b3d54fe473896db6c2328bb6ebc\",\"css\":\"6cfa78e023a05aff9ffd9acf32fce4fc\",\"combined\":\"2d088e89e9420a1b547b9c59b03ff447\"}',
            ],
            [
                'page_id' => 14,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pagina TESTE de Layout Pedidos',
                'id' => 'pagina-teste-de-layout-pedidos',
                'language' => 'pt-br',
                'path' => 'pagina-teste-de-layout-pedidos/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"desktopcode\">
    <div class=\"ui hidden divider\"></div>
    <div class=\"menuTextosTop\">
        <div class=\"ui six wide column padded grid\">
            <!--redirecionar para qual item a pessoa clicar-->
            <div class=\"four wide column\"></div>
            <div class=\"center aligned two wide column\">Minhas Compras</div>
            <div class=\"center aligned two wide column\">Meus Dados</div>
            <div class=\"center aligned two wide column\">Como Funciona</div>
            <div class=\"center aligned two wide column\">Sair</div>
            <div class=\"center aligned four wide column\"></div>
        </div>
    </div>
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui six wide column padded grid\">
        <div class=\"four wide column\"></div>
        <div class=\"eight wide column\">
            <div class=\"ui segment\">
                <div class=\"ui grid\">
                    <div class=\"eight wide column\">
                        <h3>#pedido#</h3>
                    </div>
                    <div class=\"four wide right aligned  column\">
                        #pago-ou-não#
                    </div>
                    <div class=\"four wide right aligned column\">
                        #disponível#
                    </div>
                </div>
                <div class=\"ui divider\"></div>
                <div class=\"ui sixteen wide columns grid\">
                    <div class=\"three wide column\">
                        <div class=\"row\">
                            <!--Imagem do curso-->
                            <div class=\"p\">
                                <div class=\"ui placeholder\">
                                    <div class=\"image\"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class=\"ten wide column\">
                        <div class=\"row\">
                            <h3>#nome-do-curso#</h3>
                            <h5>#valor#</h5>
                            <h5>#validade#</h5>
                        </div>
                    </div>
                    <div class=\"three wide column\">
                        <div class=\"row\">
                            <!--qr code-->
                            <div class=\"p\">
                                <div class=\"ui placeholder\">
                                    <div class=\"image\"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class=\"ui divider\"></div>
                <div class=\"ui five column grid menuItem\">
                    <div class=\"column\">
                        <div class=\"valorMinimo\">
                            <i class=\"large user icon\"></i> Pessoas
                        </div>
                    </div>
                    <div class=\"column\">
                        <div class=\"valorMinimo\">
                            <i class=\"large envelope outline icon\"></i> Enviar
                        </div>
                    </div>
                    <div class=\"column\">
                        <div class=\"valorMinimo\">
                            <i class=\"large print icon\"></i> Imprimir
                        </div>
                    </div>
                    <div class=\"column\">
                        <div class=\"valorMinimo\">
                            <i class=\"large gift icon\"></i> Presente
                        </div>
                    </div>
                    <div class=\"column\">
                        <div class=\"valorMinimo\">
                            <i class=\"large info icon\"></i> Visualizar
                        </div>
                    </div>
                </div>
                <div class=\"ui divider\"></div>
                <p> Lorem ipsum vestibulum sapien volutpat venenatis purus morbi praesent donec mattis etiam
                    class, pellentesque consequat convallis dictumst condimentum imperdiet eros magna pretium
                    elementum mollis, malesuada diam cubilia integer sapien egestas ultricies gravida vel platea
                    nibh. elit aenean elementum, suscipit quisque est curae diam in lacinia ultricies cursus
                    viverra senectus erat mauris adipiscing aliquam. </p>
            </div>
        </div>
        <div class=\"four wide column\"></div>
    </div>
</div>
<div class=\"mobilecode\">
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui padded grid\">
        <div class=\"sixteen wide column\">
            <!--wireframe pagamento-->
            <div class=\"ui segment\">
                <div class=\"menuTextosTop\">
                    <div class=\"ui four wide column padded grid\">
                        <!--redirecionar para qual item a pessoa clicar-->
                        <div class=\"center aligned four wide column\">Minhas Compras</div>
                        <div class=\"center aligned four wide column\">Meus Dados</div>
                        <div class=\"center aligned four wide column\">Como Funciona</div>
                        <div class=\"center aligned four wide column\">Sair</div>
                    </div>
                    <div class=\"ui divider\"></div>
                    <div class=\"ui six wide column grid\">
                        <div class=\"eight wide column\">
                            <h3>#pedido#</h3>
                        </div>
                        <div class=\"four wide column\">#pago-ou-não#</div>
                        <div class=\"four wide column\">#disponível#</div>
                        <div class=\"column\"></div>
                    </div>
                    <div class=\"ui divider\"></div>
                    <div class=\"ui fluid placeholder\">
                        <div class=\"image\"></div>
                    </div>
                    <div class=\"ui hidden divider\"></div>
                    <div class=\"ui sixteen wide columns grid\">
                        <div class=\"twelve wide column\">
                            <div class=\"row\">
                                <h3>#nome-do-curso#</h3>
                                <h5>#valor#</h5>
                                <h5>#validade#</h5>
                            </div>
                        </div>
                        <div class=\"four wide column\">
                            <div class=\"row\">
                                <!--qr code-->
                                <div class=\"ui placeholder\">
                                    <div class=\"image\"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class=\"ui divider\"></div>
                    <div class=\"ui five column grid menuItem\">
                        <div class=\"column\">
                            <div class=\"valorMinimo\">
                                <i class=\"large user icon\"></i> Pessoas
                            </div>
                        </div>
                        <div class=\"column\">
                            <div class=\"valorMinimo\">
                                <i class=\"large envelope outline icon\"></i> Enviar
                            </div>
                        </div>
                        <div class=\"column\">
                            <div class=\"valorMinimo\">
                                <i class=\"large print icon\"></i> Imprimir
                            </div>
                        </div>
                        <div class=\"column\">
                            <div class=\"valorMinimo\">
                                <i class=\"large gift icon\"></i> Presente
                            </div>
                        </div>
                        <div class=\"column\">
                            <div class=\"valorMinimo\">
                                <i class=\"large info icon\"></i> Visualizar
                            </div>
                        </div>
                    </div>
                    <div class=\"ui divider\"></div>
                    <p> Lorem ipsum vestibulum sapien volutpat venenatis purus morbi praesent donec mattis etiam
                        class, pellentesque consequat convallis dictumst condimentum imperdiet eros magna
                        pretium elementum mollis, malesuada diam cubilia integer sapien egestas ultricies
                        gravida vel platea nibh. elit aenean elementum, suscipit quisque est curae diam in
                        lacinia ultricies cursus viverra senectus erat mauris adipiscing aliquam. </p>
                </div>
            </div>
        </div>
    </div>
</div>',
                'css' => '.valorMinimo {
    min-width: 100px;
}

.menuItem>div:hover {
    cursor: pointer;
}

.menuTextosTop>div:hover {
    cursor: pointer;
}

.p{
    min-width: 125px;
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
                'checksum' => '{\"html\":\"1f22d7e865d394a7ecf9937f62da38b3\",\"css\":\"fd727830c53f5066166c9f3a248e74cb\",\"combined\":\"8b3fe8f0144ffbb45d7fe195548b8c65\"}',
            ],
            [
                'page_id' => 15,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Error buttons stacking without margin top',
                'id' => 'error-buttons-stacking-without-margin-top',
                'language' => 'pt-br',
                'path' => 'error-buttons-stacking-without-margin-top/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<h1>Class UI Button Inside A\'s tag</h1>
<div class=\"ui basic right aligned segment\">
    <a class=\"ui button blue\" href=\"/admin-layouts/adicionar/\" data-content=\"Clique para Adicionar.\" data-id=\"adicionar\">
        <i class=\"plus circle icon\"></i> Adicionar </a>
    <a class=\"ui button green\" href=\"/admin-layouts/?opcao=status&amp;status=I&amp;id=layout-mestre-loja&amp;redirect=admin-layouts%2Feditar%2F%3Fid%3Dlayout-mestre-loja\" data-content=\"Clique para Desativar\" data-id=\"status\">
        <i class=\"eye icon\"></i> Desativar </a>
    <div class=\"ui button excluir red\" data-href=\"/admin-layouts/?opcao=excluir&amp;id=layout-mestre-loja\" data-content=\"Clique para Excluir\" data-id=\"excluir\">
        <i class=\"trash alternate icon\"></i> Excluir
    </div>
    <a class=\"ui button blue\" href=\"/admin-layouts/adicionar/\" data-content=\"Clique para Adicionar.\" data-id=\"adicionar\">
        <i class=\"plus circle icon\"></i> Adicionar </a>
    <a class=\"ui button green\" href=\"/admin-layouts/?opcao=status&amp;status=I&amp;id=layout-mestre-loja&amp;redirect=admin-layouts%2Feditar%2F%3Fid%3Dlayout-mestre-loja\" data-content=\"Clique para Desativar\" data-id=\"status\">
        <i class=\"eye icon\"></i> Desativar </a>
    <div class=\"ui button excluir red\" data-href=\"/admin-layouts/?opcao=excluir&amp;id=layout-mestre-loja\" data-content=\"Clique para Excluir\" data-id=\"excluir\">
        <i class=\"trash alternate icon\"></i> Excluir
    </div>
</div>
<h1>Class UI Button Inside BUTTON\'s tag</h1>
<div class=\"ui basic right aligned segment\">
    <button class=\"ui button blue\">
        <i class=\"plus circle icon\"></i>
        Adicionar 
    </button>
    <button class=\"ui button green\">
        <i class=\"eye icon\"></i> 
        Desativar
    </button>
    <button class=\"ui button red\">
        <i class=\"trash alternate icon\"></i>
        Excluir
    </button>
    <button class=\"ui button blue\">
        <i class=\"plus circle icon\"></i>
        Adicionar 
    </button>
    <button class=\"ui button green\">
        <i class=\"eye icon\"></i> 
        Desativar
    </button>
    <button class=\"ui button red\">
        <i class=\"trash alternate icon\"></i>
        Excluir
    </button>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"8ec2ab881b5fa247d697c3c5cf08d928\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"8ec2ab881b5fa247d697c3c5cf08d928\"}',
            ],
            [
                'page_id' => 16,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Rascunho',
                'id' => 'rascunho',
                'language' => 'pt-br',
                'path' => 'rascunho/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<!-- Exemplo Mobile / Desktop -->
<div class=\"mobile\">Só aparece <span class=\"ui warning text\">mobile</span>
</div>
<div class=\"desktop\">Só aparece <span class=\"ui success text\">desktop</span>
</div>
<!-- Exemplo 3 -->
<div class=\"ui relaxed divided list\">
    <div class=\"item\">
        <div class=\"content paddingItens\">
            <a class=\"header\">Meus Pedidos</a>
        </div>
    </div>
    <div class=\"item\">
        <div class=\"content paddingItens\">
            <a class=\"header\">Meus Dados</a>
        </div>
    </div>
    <div class=\"item\">
        <div class=\"content paddingItens\">
            <a class=\"header\">Sair</a>
        </div>
    </div>
</div>
<!-- Exemplo 2 -->
<div>Elemento 1</div>
<div class=\"espaco-entre-elementos\"></div>
<div>Elemento 2</div>
<!-- Exemplo 1 -->
<div class=\"ui three item secondary menu\">
    <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/\">
        <div class=\"ui large header\">
            <i class=\"plug icon\"></i>
            <div class=\"content\"> Cartão de Crédito Próprio </div>
        </div>
    </a>
    <a class=\"active item\" href=\"@[[pagina#url-raiz]]@payment/other-payer/\">
        <div class=\"ui large header\">
            <i class=\"plug icon\"></i>
            <div class=\"content\"> Cartão de Crédito Terceiro </div>
        </div>
    </a>
    <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/paypal/\">
        <div class=\"ui large header\">
            <i class=\"plug icon\"></i>
            <div class=\"content\"> PayPal </div>
        </div>
    </a>
</div>',
                'css' => '.espaco-entre-elementos{
    height:150px;
}
.paddingItens{
    padding:10px;
}
/* Exemplo Mobile / Desktop */
@media screen and (max-width: 770px) {
    .desktop {
        display: none;
    }
}
@media screen and (min-width: 770px) {
    .mobile {
        display: none;
    }
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"9b76bd56bfb27d352ceedef19b2c9e2c\",\"css\":\"fd45ab95e7eb51164a30c50d98440fca\",\"combined\":\"c7a9fc53942d3a53b305c0bce33a9213\"}',
            ],
            [
                'page_id' => 17,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pagina TESTE de Layout Pagamento CCPróprio',
                'id' => 'pagina-teste-de-layout-pagamento-ccproprio',
                'language' => 'pt-br',
                'path' => 'pagina-teste-de-layout-pagamento-ccproprio/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"desktopcode\">
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui padded grid\">
        <div class=\"column\"></div>
        <div class=\"eight wide column\">
            <div class=\"row\">
                <!--wireframe pagamento-->
                <div class=\"ui segment\">
                    <div class=\"ui three item secondary stackable menu\">
                        <a class=\"active item\" href=\"@[[pagina#url-raiz]]@payment/\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui small header\">
                                    <i class=\"payment icon\"></i>
                                    <div class=\"content\"> Cartão de Crédito Próprio </div>
                                </div>
                            </div>
                        </a>
                        <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/other-payer/\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui small header\">
                                    <i class=\"payment  icon\"></i>
                                    <div class=\"content\"> Cartão de Crédito Terceiro </div>
                                </div>
                            </div>
                        </a>
                        <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/paypal/\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui small header\">
                                    <i class=\"paypal icon\"></i>
                                    <div class=\"content\"> PayPal </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!--Cartão de crédito próprio -->
                    <form class=\"ui form\">
                        <div class=\"field\">
                            <label>Número do cartão</label>
                            <input type=\"text\" name=\"Número do cartão\" placeholder=\"Número do cartão\">
                        </div>
                        <div class=\"field\">
                            <label>Nome do títular do cartão</label>
                            <div class=\"two fields\">
                                <div class=\"field\">
                                    <input type=\"text\" name=\"Nome\" placeholder=\"Nome\">
                                </div>
                                <div class=\"field\">
                                    <input type=\"text\" name=\"Sobrenome\" placeholder=\"Sobrenome\">
                                </div>
                            </div>
                        </div>
                        <div class=\"fields\">
                            <div class=\"ten wide field\">
                                <label>Vencimento</label>
                                <div class=\"two fields\">
                                    <div class=\"field\">
                                        <select class=\"ui fluid search dropdown\" name=\"MM\">
                                            <option value=\"\">Mês</option>
                                        </select>
                                    </div>
                                    <div class=\"field\">
                                        <select class=\"ui fluid search dropdown\" name=\"AA\">
                                            <option value=\"\">Ano</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class=\"six wide field\">
                                <label>Código de segurança (CSC)</label>
                                <input type=\"text\" name=\"3 dígitos\" placeholder=\"3 dígitos\">
                            </div>
                        </div>
                        <div class=\"ui fluid placeholder\">#x de R$#</div> <!-- Valores da parcela  -->
                        <div class=\"ui hidden divider\"></div>
                        <label>Suas informações serão coletadas de acordo com a Política de Privacidade do PayPal.</label>
                        <div class=\"ui hidden divider\"></div>
                        <div class=\"field\">
                            <div class=\"ui checkbox\">
                                <input type=\"checkbox\" tabindex=\"0\" class=\"hidden\">
                                <label>Salve o número do meu cartão de crédito para a próxima compra neste vendedor.</label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class=\"six wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <h3>Resumo do Pedido</h3>
                    <div class=\"ui divider\"></div>
                    <div class=\"ui sixteen wide columns grid\">
                        <div class=\"row\">
                            <div class=\"two column\">
                                <i class=\"big black file alternate icon\"></i>
                            </div>
                            <div class=\"fourteen wide column\">
                                <h5>#nome-do-curso/produto#</h5>
                                <h3>Subtotal: R$ #subtotal#</h3>
                            </div>
                        </div>
                    </div>
                    <div class=\"ui divider\"></div>
                    <h5> Subtotal: R$ #subtotal#</h5>
                    <h5> Desconto: R$ #desconto#</h5>
                    <div class=\"ui divider\"></div>
                    <h3> Total: R$ #total#</h3>
                    <div class=\"ui divider\"></div>
                    <button class=\"fluid ui button\">Prosseguir para identificação</button>
                </div>
            </div>
        </div>
        <div class=\"column\"></div>
    </div>
</div>
<div class=\"mobilecode\">
    <div class=\"ui hidden divider\"></div>
    <!-- cel-servicos < -->
    <div class=\"ui padded grid\">
        <div class=\"sixteen wide column\">
            <!--wireframe pagamento-->
            <div class=\"ui segment\">
                <div class=\"ui three item secondary stackable menu\">
                    <a class=\"active item\" href=\"@[[pagina#url-raiz]]@payment/\">
                        <div class=\"ui basic segment\">
                            <div class=\"ui small header\">
                                <i class=\"payment icon\"></i>
                                <div class=\"content\"> Cartão de Crédito Próprio </div>
                            </div>
                        </div>
                    </a>
                    <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/other-payer/\">
                        <div class=\"ui basic segment\">
                            <div class=\"ui small header\">
                                <i class=\"payment  icon\"></i>
                                <div class=\"content\"> Cartão de Crédito Terceiro </div>
                            </div>
                        </div>
                    </a>
                    <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/paypal/\">
                        <div class=\"ui basic segment\">
                            <div class=\"ui small header\">
                                <i class=\"paypal icon\"></i>
                                <div class=\"content\"> PayPal </div>
                            </div>
                        </div>
                    </a>
                </div>
                <!--Cartão de crédito próprio -->
                <form class=\"ui form\">
                    <div class=\"field\">
                        <label>Número do cartão</label>
                        <input type=\"text\" name=\"Número do cartão\" placeholder=\"Número do cartão\">
                    </div>
                    <div class=\"field\">
                        <label>Nome do títular do cartão</label>
                        <div class=\"two fields\">
                            <div class=\"field\">
                                <input type=\"text\" name=\"Nome\" placeholder=\"Nome\">
                            </div>
                            <div class=\"field\">
                                <input type=\"text\" name=\"Sobrenome\" placeholder=\"Sobrenome\">
                            </div>
                        </div>
                    </div>
                    <div class=\"fields\">
                        <div class=\"ten wide field\">
                            <label>Vencimento</label>
                            <div class=\"two fields\">
                                <div class=\"field\">
                                    <select class=\"ui fluid search dropdown\" name=\"MM\">
                                        <option value=\"\">Mês</option>
                                    </select>
                                </div>
                                <div class=\"field\">
                                    <select class=\"ui fluid search dropdown\" name=\"AA\">
                                        <option value=\"\">Ano</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class=\"six wide field\">
                            <label>Código de segurança (CSC)</label>
                            <input type=\"text\" name=\"3 dígitos\" placeholder=\"3 dígitos\">
                        </div>
                    </div>
                    <div class=\"ui fluid placeholder\">#x de R$#</div> <!-- Valores da parcela  -->
                    <div class=\"ui hidden divider\"></div>
                    <label>Suas informações serão coletadas de acordo com a Política de Privacidade do PayPal.</label>
                    <div class=\"ui hidden divider\"></div>
                    <div class=\"field\">
                        <div class=\"ui checkbox\">
                            <input type=\"checkbox\" tabindex=\"0\" class=\"hidden\">
                            <label>Salve o número do meu cartão de crédito para a próxima compra neste vendedor.</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class=\"ui segment\">
                <h3>Resumo do Pedido</h3>
                <div class=\"ui divider\"></div>
                <!-- cel-servicos < -->
                <div class=\"ui sixteen wide columns grid\">
                    <div class=\"row\">
                        <div class=\"two column\">
                            <i class=\"big black file alternate icon\"></i>
                        </div>
                        <div class=\"fourteen wide column\">
                            <h5>#nome-do-curso#</h5>
                            <h3>Subtotal:R$#subtotal#</h3>
                        </div>
                    </div>
                </div><!-- cel-servicos > -->
                <div class=\"ui divider\"></div>
                <h5>Subtotal: R$ #subtotal#</h5>
                <h5>Desconto: R$ #desconto#</h5>
                <div class=\"ui divider\"></div>
                <h3>Total: R$#total#</h3>
                <div class=\"ui divider\"></div>
                <button class=\"fluid ui button\">Prosseguir para identificação</button>
            </div>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ef96ee5680047848949c3ccb6bf6323e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ef96ee5680047848949c3ccb6bf6323e\"}',
            ],
            [
                'page_id' => 18,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pagina TESTE de Layout Pagamento CCTerceiro',
                'id' => 'pagina-teste-de-layout-pagamento-ccterceiro',
                'language' => 'pt-br',
                'path' => 'pagina-teste-de-layout-pagamento-ccterceiro/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"desktopcode\">
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui padded grid\">
        <div class=\"column\"></div>
        <div class=\"eight wide column\">
            <div class=\"row\">
                <!--wireframe pagamento-->
                <div class=\"ui segment\">
                    <div class=\"ui three item secondary stackable menu\">
                        <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui small header\">
                                    <i class=\"payment icon\"></i>
                                    <div class=\"content\"> Cartão de Crédito Próprio </div>
                                </div>
                            </div>
                        </a>
                        <a class=\"active item\" href=\"@[[pagina#url-raiz]]@payment/other-payer/\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui small header\">
                                    <i class=\"payment  icon\"></i>
                                    <div class=\"content\"> Cartão de Crédito Terceiro </div>
                                </div>
                            </div>
                        </a>
                        <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/paypal/\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui small header\">
                                    <i class=\"paypal icon\"></i>
                                    <div class=\"content\"> PayPal </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!--Cartão de crédito de terceiros -->
                    <p>Preencha as informações pessoais do outro pagador desta compra e clique no botão CONTINUAR.</p>
                    <form class=\"ui form\">
                        <div class=\"field\">
                            <input type=\"text\" name=\"Primeiro Nome\" placeholder=\"Primeiro Nome\">
                        </div>
                        <div class=\"field\">
                            <input type=\"text\" name=\"Último Nome\" placeholder=\"Último Nome\">
                        </div>
                        <div class=\"field\">
                            <input type=\"text\" name=\"Email\" placeholder=\"Email\">
                        </div>
                        <div class=\"field\">
                            <input type=\"text\" name=\"Telefone\" placeholder=\"Telefone\">
                        </div>
                        <div class=\"field\">
                            <div class=\"ui buttons\">
                                <button class=\"ui button\">CPF</button>
                                <button class=\"ui button\">CNPJ</button>
                            </div>
                        </div>
                        <div class=\"field\">
                            <input type=\"text\" name=\"CPF\" placeholder=\"CPF\">
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class=\"six wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <h3>Resumo do Pedido</h3>
                    <div class=\"ui divider\"></div>
                    <div class=\"ui sixteen wide columns grid\">
                        <div class=\"row\">
                            <div class=\"two column\">
                                <i class=\"big black file alternate icon\"></i>
                            </div>
                            <div class=\"fourteen wide column\">
                                <h5>#nome-do-curso/produto#</h5>
                                <h3>Subtotal: R$ #subtotal#</h3>
                            </div>
                        </div>
                    </div>
                    <div class=\"ui divider\"></div>
                    <h5> Subtotal: R$ #subtotal#</h5>
                    <h5> Desconto: R$ #desconto#</h5>
                    <div class=\"ui divider\"></div>
                    <h3> Total: R$ #total#</h3>
                    <div class=\"ui divider\"></div>
                    <button class=\"fluid ui button\">CONTINUAR</button>
                </div>
            </div>
        </div>
        <div class=\"column\"></div>
    </div>
</div>
<div class=\"mobilecode\">
    <div class=\"ui hidden divider\"></div>
    <!-- cel-servicos < -->
    <div class=\"ui padded grid\">
        <div class=\"sixteen wide column\">
            <!--wireframe pagamento-->
            <div class=\"ui segment\">
                <div class=\"ui three item secondary stackable menu\">
                    <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/\">
                        <div class=\"ui basic segment\">
                            <div class=\"ui small header\">
                                <i class=\"payment icon\"></i>
                                <div class=\"content\"> Cartão de Crédito Próprio </div>
                            </div>
                        </div>
                    </a>
                    <a class=\"active item\" href=\"@[[pagina#url-raiz]]@payment/other-payer/\">
                        <div class=\"ui basic segment\">
                            <div class=\"ui small header\">
                                <i class=\"payment  icon\"></i>
                                <div class=\"content\"> Cartão de Crédito Terceiro </div>
                            </div>
                        </div>
                    </a>
                    <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/paypal/\">
                        <div class=\"ui basic segment\">
                            <div class=\"ui small header\">
                                <i class=\"paypal icon\"></i>
                                <div class=\"content\"> PayPal </div>
                            </div>
                        </div>
                    </a>
                </div>
                <!--Cartão de crédito de terceiros -->
                <p>Preencha as informações pessoais do outro pagador desta compra e clique no botão CONTINUAR.</p>
                <form class=\"ui form\">
                    <div class=\"field\">
                        <input type=\"text\" name=\"Primeiro Nome\" placeholder=\"Primeiro Nome\">
                    </div>
                    <div class=\"field\">
                        <input type=\"text\" name=\"Último Nome\" placeholder=\"Último Nome\">
                    </div>
                    <div class=\"field\">
                        <input type=\"text\" name=\"Email\" placeholder=\"Email\">
                    </div>
                    <div class=\"field\">
                        <input type=\"text\" name=\"Telefone\" placeholder=\"Telefone\">
                    </div>
                    <div class=\"field\">
                        <div class=\"ui buttons\">
                            <button class=\"ui button\">CPF</button>
                            <button class=\"ui button\">CNPJ</button>
                        </div>
                    </div>
                    <div class=\"field\">
                        <input type=\"text\" name=\"CPF\" placeholder=\"CPF\">
                    </div>
                </form>
            </div>
            <div class=\"ui segment\">
                <h3>Resumo do Pedido</h3>
                <div class=\"ui divider\"></div>
                <!-- cel-servicos < -->
                <div class=\"ui sixteen wide columns grid\">
                    <div class=\"row\">
                        <div class=\"two column\">
                            <i class=\"big black file alternate icon\"></i>
                        </div>
                        <div class=\"fourteen wide column\">
                            <h5>#nome-do-curso#</h5>
                            <h3>Subtotal:R$#subtotal#</h3>
                        </div>
                    </div>
                </div><!-- cel-servicos > -->
                <div class=\"ui divider\"></div>
                <h5>Subtotal: R$ #subtotal#</h5>
                <h5>Desconto: R$ #desconto#</h5>
                <div class=\"ui divider\"></div>
                <h3>Total: R$#total#</h3>
                <div class=\"ui divider\"></div>
                <button class=\"fluid ui button\">CONTINUAR</button>
            </div>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"b618bff64ed07a101e3a363d623b4ef8\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"b618bff64ed07a101e3a363d623b4ef8\"}',
            ],
            [
                'page_id' => 19,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pagina TESTE de Layout Pagamento PayPal',
                'id' => 'pagina-teste-de-layout-pagamento-paypal',
                'language' => 'pt-br',
                'path' => 'pagina-teste-de-layout-pagamento-paypal/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"desktopcode\">
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui padded grid\">
        <div class=\"column\"></div>
        <div class=\"eight wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui three item secondary stackable menu\">
                        <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui small header\">
                                    <i class=\"payment icon\"></i>
                                    <div class=\"content\"> Cartão de Crédito Próprio </div>
                                </div>
                            </div>
                        </a>
                        <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/other-payer/\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui small header\">
                                    <i class=\"payment  icon\"></i>
                                    <div class=\"content\"> Cartão de Crédito Terceiro </div>
                                </div>
                            </div>
                        </a>
                        <a class=\"active item\" href=\"@[[pagina#url-raiz]]@payment/paypal/\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui small header\">
                                    <i class=\"paypal icon\"></i>
                                    <div class=\"content\"> PayPal </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!--wireframe paypal -->
                    <div class=\"ui fluid placeholder\">
                        <div class=\"image\">IMAGEM PAYPAL</div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"six wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <h3>Resumo do Pedido</h3>
                    <div class=\"ui divider\"></div>
                    <div class=\"ui sixteen wide columns grid\">
                        <div class=\"row\">
                            <div class=\"two column\">
                                <i class=\"big black file alternate icon\"></i>
                            </div>
                            <div class=\"fourteen wide column\">
                                <h5>#nome-do-curso/produto#</h5>
                                <h3>Subtotal: R$ #subtotal#</h3>
                            </div>
                        </div>
                    </div>
                    <div class=\"ui divider\"></div>
                    <h5> Subtotal: R$ #subtotal#</h5>
                    <h5> Desconto: R$ #desconto#</h5>
                    <div class=\"ui divider\"></div>
                    <h3> Total: R$ #total#</h3>
                    <div class=\"ui divider\"></div>
                    <div class=\"ui fluid placeholder\">
                        <div class=\"image\">BOTAO PAYPAL</div>
                        <!--wireframe paypal -->
                    </div>
                </div>
            </div>
        </div>
        <div class=\"column\"></div>
    </div>
</div>
<div class=\"mobilecode\">
    <div class=\"ui hidden divider\"></div>
    <!-- cel-servicos < -->
    <div class=\"ui padded grid\">
        <div class=\"sixteen wide column\">
            <!--wireframe pagamento-->
            <div class=\"ui segment\">
                <div class=\"ui three item secondary stackable menu\">
                    <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/\">
                        <div class=\"ui basic segment\">
                            <div class=\"ui small header\">
                                <i class=\"payment icon\"></i>
                                <div class=\"content\"> Cartão de Crédito Próprio </div>
                            </div>
                        </div>
                    </a>
                    <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/other-payer/\">
                        <div class=\"ui basic segment\">
                            <div class=\"ui small header\">
                                <i class=\"payment  icon\"></i>
                                <div class=\"content\"> Cartão de Crédito Terceiro </div>
                            </div>
                        </div>
                    </a>
                    <a class=\"active item\" href=\"@[[pagina#url-raiz]]@payment/paypal/\">
                        <div class=\"ui basic segment\">
                            <div class=\"ui small header\">
                                <i class=\"paypal icon\"></i>
                                <div class=\"content\"> PayPal </div>
                            </div>
                        </div>
                    </a>
                </div>
                <!--wireframe paypal -->
                <div class=\"ui fluid placeholder\">
                    <div class=\"image\">IMAGEM PAYPAL</div>
                </div>
            </div>
            <div class=\"ui segment\">
                <h3>Resumo do Pedido</h3>
                <div class=\"ui divider\"></div>
                <!-- cel-servicos < -->
                <div class=\"ui sixteen wide columns grid\">
                    <div class=\"row\">
                        <div class=\"two column\">
                            <i class=\"big black file alternate icon\"></i>
                        </div>
                        <div class=\"fourteen wide column\">
                            <h5>#nome-do-curso#</h5>
                            <h3>Subtotal:R$#subtotal#</h3>
                        </div>
                    </div>
                </div><!-- cel-servicos > -->
                <div class=\"ui divider\"></div>
                <h5>Subtotal: R$ #subtotal#</h5>
                <h5>Desconto: R$ #desconto#</h5>
                <div class=\"ui divider\"></div>
                <h3>Total: R$#total#</h3>
                <div class=\"ui divider\"></div>
                <div class=\"ui fluid placeholder\">
                    <div class=\"image\">BOTAO PAYPAL</div>
                    <!--wireframe paypal -->
                </div>
            </div>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"56f5a8ea10e433e57d7d739cf2ee34b2\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"56f5a8ea10e433e57d7d739cf2ee34b2\"}',
            ],
            [
                'page_id' => 20,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pagina TESTE de Layout Pagamento CCPróprio - Cartão Salvo ou Novo Cartão',
                'id' => 'pagina-teste-de-layout-pagamento-ccproprio-cartao-salvo-ou-novo-cartao',
                'language' => 'pt-br',
                'path' => 'pagina-teste-de-layout-pagamento-ccproprio-cartao-salvo-ou-novo-cartao/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"desktopcode\">
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui padded grid\">
        <div class=\"column\"></div>
        <div class=\"eight wide column\">
            <div class=\"row\">
                <!--wireframe pagamento-->
                <div class=\"ui segment\">
                    <div class=\"ui three item secondary stackable menu\">
                        <a class=\"active item\" href=\"@[[pagina#url-raiz]]@payment/\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui small header\">
                                    <i class=\"payment icon\"></i>
                                    <div class=\"content\"> Cartão de Crédito Próprio </div>
                                </div>
                            </div>
                        </a>
                        <a class=\"item\" href=\"@[[pagina§#url-raiz]]@payment/other-payer/\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui small header\">
                                    <i class=\"payment  icon\"></i>
                                    <div class=\"content\"> Cartão de Crédito Terceiro </div>
                                </div>
                            </div>
                        </a>
                        <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/paypal/\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui small header\">
                                    <i class=\"paypal icon\"></i>
                                    <div class=\"content\"> PayPal </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class=\"ui segment\">
                        <div clasas=\"ui fluid placeholder\">
                            <!--formas de pagamento-->
                            <div class=\"image\">#formas-de-pagamento</div>
                        </div>
                        <div class=\"ui segment\">
                            <div class=\"ui grid\">
                                <div class=\"three wide column\">
                                    <div class=\"row\">
                                        <div class=\"ui checkbox\">
                                            <input type=\"checkbox\">
                                            <label>
                                                <div class=\"ui placeholder\">
                                                    <i class=\"big payment icon\"></i>
                                                </div>
                                            </label>
                                            <!--No site b2make, ta o simbolo da visa, entao tem q mudar de acordo com o cartao salvo-->
                                        </div>
                                    </div>
                                </div>
                                <div class=\"nine wide column\">
                                    <div class=\"row\"> #número-do-cartão# </div>
                                </div>
                                <div class=\"four wide right aligned column\">
                                    <div class=\"row\">
                                        <i class=\"trash alternate icon\"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class=\"ui segment\">
                            <div class=\"ui grid\">
                                <div class=\"three wide column\">
                                    <div class=\"row\">
                                        <div class=\"ui checkbox\">
                                            <input type=\"checkbox\">
                                            <label>
                                                <div class=\"ui placeholder\">
                                                    <i class=\"big payment icon\"></i>
                                                </div>
                                            </label>
                                            <!--Aqui deixei o placeholder tmb, pq n consegui deixar so o checkbox-->
                                        </div>
                                    </div>
                                </div>
                                <div class=\"nine wide column\">
                                    <div class=\"row\"> Novo cartão </div>
                                </div>
                                <div class=\"four wide right aligned column\"></div>
                            </div>
                        </div>
                        <div class=\"ui fluid placeholder\">#x de R$#</div> <!-- Valores da parcela  -->
                        <div class=\"ui hidden divider\"></div>
                        <label>Suas informações serão coletadas de acordo com a Política de Privacidade do PayPal.</label>
                        <div class=\"ui hidden divider\"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"six wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <h3>Resumo do Pedido</h3>
                    <div class=\"ui divider\"></div>
                    <div class=\"ui sixteen wide columns grid\">
                        <div class=\"row\">
                            <div class=\"two column\">
                                <i class=\"big black file alternate icon\"></i>
                            </div>
                            <div class=\"fourteen wide column\">
                                <h5>#nome-do-curso/produto#</h5>
                                <h3>#Subtotal:R$#</h3>
                            </div>
                        </div>
                    </div>
                    <div class=\"ui divider\"></div>
                    <h5> Subtotal: R$ #subtotal#</h5>
                    <h5> Desconto: R$ #desconto#</h5>
                    <div class=\"ui divider\"></div>
                    <h3> Total: R$ #total#</h3>
                    <div class=\"ui divider\"></div>
                    <button class=\"fluid ui button\">Prosseguir para identificação</button>
                </div>
            </div>
        </div>
        <div class=\"column\"></div>
    </div>
</div>
<div class=\"mobilecode\">
    <div class=\"ui hidden divider\"></div>
    <!-- cel-servicos < -->
    <div class=\"ui padded grid\">
        <div class=\"sixteen wide column\">
            <!--wireframe pagamento-->
            <div class=\"ui segment\">
                <div class=\"ui three item secondary stackable menu\">
                    <a class=\"active item\" href=\"@[[pagina#url-raiz]]@payment/\">
                        <div class=\"ui basic segment\">
                            <div class=\"ui small header\">
                                <i class=\"payment icon\"></i>
                                <div class=\"content\"> Cartão de Crédito Próprio </div>
                            </div>
                        </div>
                    </a>
                    <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/other-payer/\">
                        <div class=\"ui basic segment\">
                            <div class=\"ui small header\">
                                <i class=\"payment  icon\"></i>
                                <div class=\"content\"> Cartão de Crédito Terceiro </div>
                            </div>
                        </div>
                    </a>
                    <a class=\"item\" href=\"@[[pagina#url-raiz]]@payment/paypal/\">
                        <div class=\"ui basic segment\">
                            <div class=\"ui small header\">
                                <i class=\"paypal icon\"></i>
                                <div class=\"content\"> PayPal </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class=\"ui segment\">
                    <div clasas=\"ui fluid placeholder\">
                        <!--formas de pagamento-->
                        <div class=\"image\">#formas-de-pagamento</div>
                    </div>
                    <div class=\"ui segment\">
                        <div class=\"ui grid\">
                            <div class=\"three wide column\">
                                <div class=\"row\">
                                    <div class=\"ui checkbox\">
                                        <input type=\"checkbox\">
                                        <label>
                                            <div class=\"ui placeholder\">
                                                <i class=\"big payment icon\"></i>
                                            </div>
                                        </label>
                                        <!--No site b2make, ta o simbolo da visa, entao tem q mudar de acordo com o cartao salvo-->
                                    </div>
                                </div>
                            </div>
                            <div class=\"nine wide column\">
                                <div class=\"row\"> #número-do-cartão# </div>
                            </div>
                            <div class=\"four wide right aligned column\">
                                <div class=\"row\">
                                    <i class=\"trash alternate icon\"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class=\"ui segment\">
                        <div class=\"ui grid\">
                            <div class=\"three wide column\">
                                <div class=\"row\">
                                    <div class=\"ui checkbox\">
                                        <input type=\"checkbox\">
                                        <label>
                                            <div class=\"ui placeholder\">
                                                <i class=\"big payment icon\"></i>
                                            </div>
                                        </label>
                                        <!--Aqui deixei o placeholder tmb, pq n consegui deixar so o checkbox-->
                                    </div>
                                </div>
                            </div>
                            <div class=\"nine wide column\">
                                <div class=\"row\"> Novo cartão </div>
                            </div>
                            <div class=\"four wide right aligned column\"></div>
                        </div>
                    </div>
                    <div class=\"ui fluid placeholder\">#x de R$#</div> <!-- Valores da parcela  -->
                    <div class=\"ui hidden divider\"></div>
                    <label>Suas informações serão coletadas de acordo com a Política de Privacidade do PayPal.</label>
                    <div class=\"ui hidden divider\"></div>
                </div>
            </div>
            <div class=\"ui segment\">
                <h3>Resumo do Pedido</h3>
                <div class=\"ui divider\"></div>
                <!-- cel-servicos < -->
                <div class=\"ui sixteen wide columns grid\">
                    <div class=\"row\">
                        <div class=\"two column\">
                            <i class=\"big black file alternate icon\"></i>
                        </div>
                        <div class=\"fourteen wide column\">
                            <h5>#nome-do-curso#</h5>
                            <h3>Subtotal:R$#subtotal#</h3>
                        </div>
                    </div>
                </div><!-- cel-servicos > -->
                <div class=\"ui divider\"></div>
                <h5>Subtotal: R$ #subtotal#</h5>
                <h5>Desconto: R$ #desconto#</h5>
                <div class=\"ui divider\"></div>
                <h3>Total: R$#total#</h3>
                <div class=\"ui divider\"></div>
                <button class=\"fluid ui button\">Prosseguir para identificação</button>
            </div>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"34f635f0fc6e721843204d09427b15bd\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"34f635f0fc6e721843204d09427b15bd\"}',
            ],
            [
                'page_id' => 21,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pagina TESTE de Minha Conta',
                'id' => 'pagina-teste-de-minha-conta',
                'language' => 'pt-br',
                'path' => 'pagina-teste-de-minha-conta/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"desktopcode\">
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui padded grid\">
        <div class=\"column\"></div>
        <div class=\"seven wide column\">
            <div class=\"ui grid\">
                <div class=\"five wide column\">
                    <img class=\"ui small image\" src=\"/images/imagem-padrao.png\">
                </div>
                <div class=\"ten wide column\">
                    <h3>#nome#</h3>
                    <h4>#email#</h4>
                </div>
                <div class=\"one wide column\"></div>
            </div>
            <div class=\"espaco-entre-elementos-desktop\"></div>
            <div class=\"ui relaxed divided list\">
                <div class=\"ui relaxed divided list\">
                    <div class=\"item\">
                        <div class=\"content paddingItens\">
                            <a class=\"header\">Meus Pedidos</a>
                        </div>
                    </div>
                    <div class=\"item\">
                        <div class=\"content paddingItens\">
                            <a class=\"header\">Meus Dados</a>
                        </div>
                    </div>
                    <div class=\"item\">
                        <div class=\"content paddingItens\">
                            <a class=\"header\">Sair</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"seven wide column\">
            <div class=\"row\">
                <form class=\"ui form\">
                    <div class=\"field\">
                        <h2>Meus Dados</h2>
                        <input type=\"text\" name=\"Nome\" placeholder=\"Nome\">
                    </div>
                    <div class=\"field\">
                        <input type=\"text\" name=\"Sobrenome\" placeholder=\"Sobrenome\">
                    </div>
                    <div class=\"field\">
                        <input type=\"text\" name=\"Telefone\" placeholder=\"Telefone\">
                    </div>
                    <div class=\"ui hidden divider\"></div>
                    <div class=\"ui buttons\">
                        <button class=\"ui padded button\">CPF</button>
                        <button class=\"ui padded button\">CNPJ</button>
                    </div>
                    <div class=\"ui hidden divider\"></div>
                    <div class=\"field\">
                        <input type=\"text\" name=\"#cpf-ou-cnpj#\" placeholder=\"#cpf-ou-cnpj#\">
                        <!--Alterar de acordo com a opção selecionada- cpf ou cnpj-->
                    </div>
                    <button class=\"fluid ui button\">Atualizar</button>
                    <h3>Histórico de Alterações</h3>
                    <div class=\"ui divider\"></div>
                    <h5>#data# #hora# - #alterações-feita-pelo-usuário-#</h5>
                </form>
            </div>
        </div>
    </div>
    <div class=\"column\"></div>
</div>
<div class=\"mobilecode\">
    <div class=\"ui hidden divider\"></div>
    <div class=\"ui padded grid\">
        <div class=\"sixteen wide column\">
            <div class=\"ui grid\">
                <div class=\"five wide column\">
                    <img class=\"ui small image\" src=\"/images/imagem-padrao.png\">
                </div>
                <div class=\"ten wide column\">
                    <h3>#nome#</h3>
                    <h4>#email#</h4>
                </div>
                <div class=\"one wide column\"></div>
            </div>
            <div class=\"espaco-entre-elementos-mobile\"></div>
            <div class=\"ui relaxed divided list\">
                <div class=\"ui relaxed divided list\">
                    <div class=\"item\">
                        <div class=\"content paddingItens\">
                            <a class=\"header\">Meus Pedidos</a>
                        </div>
                    </div>
                    <div class=\"item\">
                        <div class=\"content paddingItens\">
                            <a class=\"header\">Meus Dados</a>
                        </div>
                    </div>
                    <div class=\"item\">
                        <div class=\"content paddingItens\">
                            <a class=\"header\">Sair</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class=\"ui divider\"></div>
    <div class=\"ui padded grid\">
        <div class=\"sixteen wide column\">
            <form class=\"ui form\">
                <div class=\"field\">
                    <h2>Meus Dados</h2>
                    <input type=\"text\" name=\"Nome\" placeholder=\"Nome\">
                </div>
                <div class=\"field\">
                    <input type=\"text\" name=\"Sobrenome\" placeholder=\"Sobrenome\">
                </div>
                <div class=\"field\">
                    <input type=\"text\" name=\"Telefone\" placeholder=\"Telefone\">
                </div>
                <div class=\"ui hidden divider\"></div>
                <div class=\"ui buttons\">
                    <button class=\"ui padded button\">CPF</button>
                    <button class=\"ui padded button\">CNPJ</button>
                </div>
                <div class=\"ui hidden divider\"></div>
                <div class=\"field\">
                    <input type=\"text\" name=\"#cpf-ou-cnpj#\" placeholder=\"#cpf-ou-cnpj#\">
                    <!--Alterar de acordo com a opção selecionada- cpf ou cnpj-->
                </div>
                <button class=\"fluid ui button\">Atualizar</button>
                <h3>Histórico de Alterações</h3>
                <div class=\"ui divider\"></div>
                <h5>#data# #hora# - #alterações-feita-pelo-usuário-#</h5>
            </form>
        </div>
    </div>
</div>',
                'css' => '.espaco-entre-elementos-desktop {
    height: 50px;
}
.espaco-entre-elementos-mobile {
    height: 10px;
}
.paddingItens {
    padding: 10px;
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
                'checksum' => '{\"html\":\"95372f818a8f9ceaf0959a146ec94c2d\",\"css\":\"e806ac3231c0cebda2e4ebb8d74cee3c\",\"combined\":\"3bde9ee3189218a29715a87b3a370681\"}',
            ],
            [
                'page_id' => 22,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Teste Mudança',
                'id' => 'teste-mudanca',
                'language' => 'pt-br',
                'path' => 'teste-mudanca/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"/>
	<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
	<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>
	<link href=\"https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap\" rel=\"stylesheet\">
	<section id=\"app\">
		<section class=\"box\">
			<img src=\"https://safeleads-prod.s3-sa-east-1.amazonaws.com/safeleadsfiles/soltaic/capturadetela20220221160824menor_f0f2.png\" alt=\"\" class=\"img\"/>
			<img src=\"https://safeleads-prod.s3-sa-east-1.amazonaws.com/safeleadsfiles/soltaic/capturadetela20220221161808_6812.png\" alt=\"\" class=\"img\"/>
			<div class=\"descritivo-geral\">\"Para o correto dimensionamento e precificação do seu projeto é
fundamental a nossa visita técnica. Uma vez que a face e a inclinação
do telhado podem alterar a geração de energia do sistema fotovoltaico.
Na visita nós avaliaremos também as condições da estrutura física,
assim como elétrica. É possível que sejam necessárias adequações
para a sua segurança e a correta homologação do sistema junto à
Concessionária de Energia.\"</div>
		</section>
		<section class=\"dados-gerais\">
			<div class=\"flex-coluna\">
				<div class=\"dados-consumo\">@[[kwPSimulacao]]@ KWP | @[[ConsumoSimulacao]]@ KWH</div>
				<div class=\"nome-proposta\">@[[NomeLead]]@</div>
				<span class=\"dados-simulacao\">Data da Simulação: @[[DataSimulacao]]@</span><br>
				<span class=\"dados-simulacao\">Orçamento válido até: @[[DataValidadePropostaSimulacao]]@</span><br>
				<span class=\"dados-simulacao\">Orçamento N.: @[[IdPreProposta]]@</span>
			</div>
			<div class=\"flex-coluna vendedor-foto\">
				<img src=\"@[[ImagemPerfilURLUsuario]]@\" width=\"100%\"/>
			</div>
			<div class=\"flex-coluna vendedor-dados\">
				<div class=\"vendedor-nome\">@[[NomeUsuario]]@</div>
				<div class=\"vendedor-perfil\">@[[PerfilUsuario]]@</div>
				<span><a href=\"https://api.whatsapp.com/send?phone=@[[CelularUsuario]]@\" target=\"_blank\" class=\"vendedor-link\">@[[CelularUsuario]]@</a></span><br>
				<span>@[[EmailUsuario]]@</span>
			</div>
		</section>
		<section class=\"box conta-energia\">
			<div class=\"divisor\"></div>
			<img src=\"https://safeleads-prod.s3-sa-east-1.amazonaws.com/safeleadsfiles/soltaic/propostacurta_02_d8ff.jpg\" alt=\"\" class=\"img\"/>
			<div class=\"ce-tit ce-sem-solar ce-col1 ce-linha1\">SEM SOLAR</div>
			<div class=\"ce-tit ce-com-solar ce-col2 ce-linha1\">COM SOLAR</div>
			<div class=\"ce-tit ce-economia ce-col3 ce-linha1\">ECONOMIA DE:</div>
			<div class=\"ce-sub ce-sem-solar ce-col1 ce-linha2\">R$ @[[ContaAtualMesSimulacao]]@ / MÊS</div>
			<div class=\"ce-sub ce-com-solar ce-col2 ce-linha2\">R$ @[[ContaGeradorMesSimulacao]]@ / MÊS</div>
			<div class=\"ce-sub ce-economia ce-col3 ce-linha2\">R$ @[[ContaEconomiaMesSimulacao]]@ / MÊS</div>
			<div class=\"ce-sub ce-sem-solar ce-col1 ce-linha3\">R$ @[[ContaAtualAnoSimulacao]]@ / ANO</div>
			<div class=\"ce-sub ce-com-solar ce-col2 ce-linha3\">R$ @[[ContaGeradorAnoSimulacao]]@ / ANO</div>
			<div class=\"ce-sub ce-economia ce-col3 ce-linha3\">R$ @[[ContaEconomiaAnoSimulacao]]@ / ANO</div>
		</section>
		<section class=\"descritivo-equipamentos\">
			<div class=\"de-cont\">
				<div class=\"de-icon de-icon1\"></div>
				Potência do Sistema: @[[kwPSimulacao]]@kWp
			</div>
			<div class=\"de-cont\">
				<div class=\"de-icon de-icon2\"></div>
				Inversor @[[InversorSimulacao]]@
			</div>
			<div class=\"de-cont\">
				<div class=\"de-icon de-icon3\"></div>
				@[[QuantidadePlacasSimulacao]]@ Módulos Solares @[[PlacaNomeSimulacao]]@ - @[[PlacaPotenciaSimulacao]]@
			</div>
			<div class=\"de-cont\">
				<div class=\"de-icon de-icon4\"></div>
				Geração Estimada Mensal de @[[GeracaoMensalSimulacao]]@kWh/mês
			</div>
		</section>
		<section class=\"valor\">
			Valor R$ @[[PrecoSimulacao]]@
		</section>
		<section class=\"financiamento\">
			@[[Financiamentos4Simulacao]]@
		</section>
		<section class=\"box\">
			<img src=\"https://safeleads-prod.s3-sa-east-1.amazonaws.com/safeleadsfiles/soltaic/propostacurta_05_a036_d328.jpg\" alt=\"\" class=\"img\"/>
		</section>
		<section class=\"box\">
			<img src=\"https://safeleads-prod.s3-sa-east-1.amazonaws.com/safeleadsfiles/soltaic/propostacurta_05_a036footer_ac4f.jpg\" alt=\"\" class=\"img\"/>
		</section>
	</section>',
                'css' => '* {
	box-sizing: border-box;
	font-family: \\\'Open Sans\\\', sans-serif;
}
body {
	margin: 0;
}
body, html{
	margin-top:0px !important;
	margin-right:auto !important;
	margin-bottom:0px !important;
	margin-left:auto !important;
	overflow-x:auto !important;
	width:100% !important;
	font-size:16px;
}
@media screen and (max-width: 600px) {
	body, html{
		font-size: 12px;
	}
	html{
		zoom: 50%;
	}
}
html{
	background-color:rgb(238, 238, 238) !important;
}
body{
	background-color:rgb(255, 255, 255) !important;
	margin-top:0px;
	margin-right:0px;
	margin-bottom:0px;
	margin-left:0px;
}
#app{
	width:100%;
	max-width:21cm;
	min-width:21cm;
	margin-top:0px;
	margin-right:auto;
	margin-bottom:0px;
	margin-left:auto;
	background-color:rgb(255, 255, 255);
}
/**************** B2make < *******************/
.box .img{
	width:100%;
	margin: 0;
	display: block;
}
.descritivo-geral{
	background-color:#1B3654;
	color:#FFF;
	margin:0;
	padding:15px 130px;
	font-weight:bold;
	font-size:1rem;
}
.dados-gerais{
	padding:15px 40px;
	display:flex;
	justify-content:space-between;
}
.flex-coluna{
	flex-grow: 1;
}
.dados-consumo{
	font-size:1.4rem;
}
.nome-proposta{
	font-size:1.6rem;
	font-weight:bold;
	margin-bottom:2rem;
}
.dados-simulacao{
	font-size:1rem;
	font-weight:bold;
}
.vendedor-foto{
	text-align:center;
	max-width:130px;
}
.vendedor-dados{
	text-align:center;
	font-size:0.9rem;
	color:#174387;
}
.vendedor-nome{
	margin-top:0.3rem;
	font-size:1.2rem;
}
.vendedor-perfil{
	margin-bottom:0.5rem;
}
.vendedor-link{
	text-decoration-line:none;
	text-decoration-style:initial;
	text-decoration-color:initial;
	color:#174387;
}
.divisor{
	height:30px;
	background-color:#1B3654;
}
.conta-energia{
	position:relative;
}
.ce-tit{
	position:absolute;
	font-size:1.3rem;
	font-weight:bold;
}
.ce-sub{
	position:absolute;
	font-size:1.3rem;
}
.ce-sem-solar{
}
.ce-com-solar{
	color:#0374CD;
}
.ce-economia{
	color:#FFF;
}
.ce-col1{
    left: 75px;
}
.ce-col2{
    left: 290px;
}
.ce-col3{
    left: 518px;
}
.ce-linha1{
    bottom: 66px;
}
.ce-linha2{
	bottom: 42px;
}
.ce-linha3{
	bottom: 20px;
}
.descritivo-equipamentos{
	background-color:#FFF;
	padding:20px 60px 20px 110px;
}
.de-cont{
	position:relative;
	padding-left:40px;
	padding-top:7px;
	min-height:40px;
}
.de-icon{
	position:absolute;
	width:30px;
	height:40px;
	left:0px;
	top:0px;
	background-image:url(https://safeleads-prod.s3-sa-east-1.amazonaws.com/safeleadsfiles/soltaic/capturadetela20220221190520_108b.png);
	background-size:38px;
}
.de-icon1{
	background-position:0px -5px;
}
.de-icon2{
	background-position:0px -38px;
}
.de-icon3{
	background-position:0px -70px;
}
.de-icon4{
	background-position:0px -110px;
}
.valor{
	font-size:2.3rem;
	text-align:center;
}
.financiamento{
	padding:15px;
	text-align:center;
}
/**************** B2make > *******************/',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"efad6d09136770a995bd5a0c397ad931\",\"css\":\"878ce2b371a1e40ab7683c2decd4e9ef\",\"combined\":\"76493f30f34b264ea40af806cc960a63\"}',
            ],
            [
                'page_id' => 23,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Testes do Dashboard',
                'id' => 'testes-do-dashboard',
                'language' => 'pt-br',
                'path' => 'testes-do-dashboard/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"desktopcode\">
    <h2 class=\"frase ui header\" style=\"color:#00000099;\"> Seja bem-vindo, #usuário#, um ótimo dia hoje não? </h2>
    <div class=\"ui grid\">
        <div class=\"four wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui grid\">
                        <div class=\"eleven wide column\">
                            <div class=\"texto\">
                                <div class=\"ui large header\"> 15 <div class=\"sub header\">Novos Clientes</div>
                                </div>
                            </div>
                        </div>
                        <div class=\"right aligned five wide column\">
                            <i class=\"big comments outline grey icon\" style=\"color:#EBEBEB;\"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"four wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui two columns grid\">
                        <div class=\"column\">
                            <div class=\"ui large header\"> 22 <div class=\"sub header\">Vendas</div>
                            </div>
                        </div>
                        <div class=\"right aligned column\">
                            <i class=\"big tags grey icon\" style=\"color:#EBEBEB;\"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"four wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui two columns grid\">
                        <div class=\"column\">
                            <div class=\"ui large header\"> 15 <div class=\"sub header\">Receita</div>
                            </div>
                        </div>
                        <div class=\"right aligned column\">
                            <i class=\"big dollar sign grey icon\" style=\"color:#EBEBEB;\"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"four wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui grid\">
                        <div class=\"eleven wide column\">
                            <div class=\"ui large header\"> 15 <div class=\"sub header\">Total Mensal</div>
                            </div>
                        </div>
                        <div class=\"right aligned five wide column\">
                            <i class=\"big file alternate grey icon\" style=\"color:#EBEBEB;\"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class=\"ui grid\">
        <div class=\"eight wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui two columns grid\">
                        <div class=\"column\">
                            <h3 class=\"ui header\"> Resumo Mensal </h3>
                        </div>
                        <div class=\"right aligned column\">
                            <div class=\"ui compact menu\">
                                <div class=\"ui simple dropdown item\"> Fevereiro <i class=\"fluid dropdown icon\"></i>
                                    <div class=\"menu\">
                                        <div class=\"item\">Janeiro</div>
                                        <div class=\"item\">Fevereiro</div>
                                        <div class=\"item\">Março</div>
                                        <div class=\"item\">Abril</div>
                                        <div class=\"item\">Maio</div>
                                        <div class=\"item\">Junho</div>
                                        <div class=\"item\">Julho</div>
                                        <div class=\"item\">Agosto</div>
                                        <div class=\"item\">Setembro</div>
                                        <div class=\"item\">Outubro</div>
                                        <div class=\"item\">Novembro</div>
                                        <div class=\"item\">Dezembro</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class=\"ui placeholder\">
                        <div class=\"image\">Gráfico</div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"eight wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <h3 class=\"ui header\"> Pedidos Recentes </h3>
                    <div class=\"ui placeholder\">
                        <div class=\"image\">Gráfico</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class=\"ui grid\">
        <div class=\"eight wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui two columns grid\">
                        <div class=\"column\">
                            <h3 class=\"ui header\"> Serviços </h3>
                        </div>
                        <div class=\"right aligned column\">
                            <h5 class=\"ui header\"> Ver todos os serviços &gt; </h5>
                        </div>
                    </div>
                    <div class=\"ui sixteen wide columns grid\">
                        <div class=\"one wide column\">
                            <div class=\"row\">
                                <i class=\"check circle green icon\"></i>
                            </div>
                        </div>
                        <div class=\"thirteen wide centered column\">
                            <div class=\"row\">
                                <h4 class=\"ui header\" style=\"color:#000000DE;\"> Congresso internacional de Medicina Veterinária Integrativa </h4>
                                <div class=\"ui three column very relaxed grid\">
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\"> Serviços Ativos</div>
                                    </div>
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 em estoque</div>
                                    </div>
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 vendidos</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class=\"two wide right aligned column\">
                            <div class=\"row\">
                                <i class=\"ellipsis horizontal grey icon\"></i>
                            </div>
                        </div>
                    </div>
                    <div class=\"ui sixteen wide columns grid\">
                        <div class=\"one wide column\">
                            <div class=\"row\">
                                <i class=\"check circle green icon\"></i>
                            </div>
                        </div>
                        <div class=\"thirteen wide centered column\">
                            <div class=\"row\">
                                <h4 class=\"ui header\" style=\"color:#000000DE;\"> Congresso internacional de Medicina Veterinária Integrativa </h4>
                                <div class=\"ui three column very relaxed grid\">
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\"> Serviços Ativos</div>
                                    </div>
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 em estoque</div>
                                    </div>
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 vendidos</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class=\"two wide right aligned column\">
                            <div class=\"row\">
                                <i class=\"ellipsis horizontal grey icon\"></i>
                            </div>
                        </div>
                    </div>
                    <div class=\"ui sixteen wide columns grid\">
                        <div class=\"one wide column\">
                            <div class=\"row\">
                                <i class=\"times circle red icon\"></i>
                            </div>
                        </div>
                        <div class=\"thirteen wide centered column\">
                            <div class=\"row\">
                                <h4 class=\"ui header\" style=\"color:#000000DE;\"> Congresso internacional de Medicina Veterinária Integrativa </h4>
                                <div class=\"ui three column very relaxed grid\">
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\"> Serviços Ativos</div>
                                    </div>
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 em estoque</div>
                                    </div>
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 vendidos</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class=\"two wide right aligned column\">
                            <div class=\"row\">
                                <i class=\"ellipsis horizontal grey icon\"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"eight wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui two columns grid\">
                        <div class=\"column\">
                            <h3 class=\"ui header\"> Novidades </h3>
                        </div>
                        <div class=\"right aligned column\">
                            <h5 class=\"ui header\"> Ver todas as novidades &gt; </h5>
                        </div>
                    </div>
                    <div class=\"ui two columns grid\">
                        <div class=\"column\" style=\"color:#00000061;\"> Versão 2.4.0 </div>
                        <div class=\"right aligned column\" style=\"color:#00000061;\"> 25/02/2019 </div>
                    </div>
                    <p style=\"color:#00000061;\">Após efetivar o cadastro você tem à disposição diversas funcionalidades para aumentar os recursos de seu posicionamento na internet e potencializar ainda mais seu negócio, atraindo e convertendo vendas e clientes e profissionalizando ainda mais sua atividade.</p>
                    <p style=\"color:#00000061;\">Teste grátis e sem compromisso o uso de todas as ferramentas por 07 dias. Nosso maior objetivo é que você e seus clientes tenham uma experiência única, simples e objetiva no uso da ferramenta.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Mobile Code -->
<div class=\"mobilecode\">
    <div class=\"margin\">
        <div class=\"ui segment\">
            <div class=\"ui two columns padded grid\">
                <div class=\"column\">
                    <div class=\"ui large header\"> 15 <div class=\"sub header\">Novos Clientes</div>
                    </div>
                </div>
                <div class=\"right aligned column\">
                    <i class=\"huge comments outline grey icon\" style=\"color:#EBEBEB;\"></i>
                </div>
            </div>
        </div>
        <div class=\"ui segment\">
            <div class=\"ui two columns padded grid\">
                <div class=\"column\">
                    <div class=\"ui large header\"> 22 <div class=\"sub header\">Vendas</div>
                    </div>
                </div>
                <div class=\"right aligned column\">
                    <i class=\"huge tags grey icon\" style=\"color:#EBEBEB;\"></i>
                </div>
            </div>
        </div>
        <div class=\"ui segment\">
            <div class=\"ui two columns padded grid\">
                <div class=\"column\">
                    <div class=\"ui large header\"> 15 <div class=\"sub header\">Receita</div>
                    </div>
                </div>
                <div class=\"right aligned padded column\">
                    <i class=\"huge dollar sign grey icon\" style=\"color:#EBEBEB;\"></i>
                </div>
            </div>
        </div>
        <div class=\"ui segment\">
            <div class=\"ui two columns padded grid\">
                <div class=\"column\">
                    <div class=\"ui large header\"> 15 <div class=\"sub header\">Total Mensal</div>
                    </div>
                </div>
                <div class=\"right aligned column\">
                    <i class=\"huge file alternate grey icon\" style=\"color:#EBEBEB;\"></i>
                </div>
            </div>
        </div>
        <div class=\"ui segment\">
            <div class=\"ui two columns grid\">
                <div class=\"column\">
                    <h3 class=\"ui header\"> Resumo Mensal </h3>
                </div>
                <div class=\"right aligned column\">
                    <div class=\"ui compact menu\">
                        <div class=\"ui simple dropdown item\"> Fevereiro <i class=\"fluid dropdown icon\"></i>
                            <div class=\"menu\">
                                <div class=\"item\">Janeiro</div>
                                <div class=\"item\">Fevereiro</div>
                                <div class=\"item\">Março</div>
                                <div class=\"item\">Abril</div>
                                <div class=\"item\">Maio</div>
                                <div class=\"item\">Junho</div>
                                <div class=\"item\">Julho</div>
                                <div class=\"item\">Agosto</div>
                                <div class=\"item\">Setembro</div>
                                <div class=\"item\">Outubro</div>
                                <div class=\"item\">Novembro</div>
                                <div class=\"item\">Dezembro</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class=\"ui placeholder\">
                <div class=\"image\">Gráfico</div>
            </div>
        </div>
        <div class=\"ui segment\">
            <h3 class=\"ui header\"> Pedidos Recentes </h3>
            <div class=\"ui placeholder\">
                <div class=\"image\">Gráfico</div>
            </div>
        </div>
        <div class=\"ui segment\">
            <div class=\"ui two columns grid\">
                <div class=\"column\">
                    <h3 class=\"ui header\"> Serviços </h3>
                </div>
                <div class=\"right aligned column\">
                    <h5 class=\"ui header\"> Ver todos os serviços &gt; </h5>
                </div>
            </div>
            <div class=\"ui sixteen wide columns grid\">
                <div class=\"one wide column\">
                    <div class=\"row\">
                        <i class=\"check circle green icon\"></i>
                    </div>
                </div>
                <div class=\"fourteen wide centered column\">
                    <div class=\"row\">
                        <h4 class=\"ui header\" style=\"color:#000000DE;\"> Congresso internacional de Medicina Veterinária Integrativa </h4>
                        <div class=\"ui three column very relaxed grid\">
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">Serviços Ativos</div>
                            </div>
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 em estoque</div>
                            </div>
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 vendidos</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class=\"one wide column\">
                    <div class=\"row\">
                        <i class=\"ellipsis horizontal grey icon\"></i>
                    </div>
                </div>
            </div>
            <div class=\"ui sixteen wide columns grid\">
                <div class=\"one wide column\">
                    <div class=\"row\">
                        <i class=\"check circle green icon\"></i>
                    </div>
                </div>
                <div class=\"fourteen wide centered column\">
                    <div class=\"row\">
                        <h4 class=\"ui header\" style=\"color:#000000DE;\"> Congresso internacional de Medicina Veterinária Integrativa </h4>
                        <div class=\"ui three column very relaxed grid\">
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">Serviços Ativos</div>
                            </div>
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 em estoque</div>
                            </div>
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 vendidos</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class=\"one wide column\">
                    <div class=\"row\">
                        <i class=\"ellipsis horizontal grey icon\"></i>
                    </div>
                </div>
            </div>
            <div class=\"ui sixteen wide columns grid\">
                <div class=\"one wide column\">
                    <div class=\"row\">
                        <i class=\"times circle red icon\"></i>
                    </div>
                </div>
                <div class=\"fourteen wide centered column \">
                    <div class=\"row\">
                        <h4 class=\"ui header\" style=\"color:#000000DE;\"> Congresso internacional de Medicina Veterinária Integrativa </h4>
                        <div class=\"ui three column very relaxed grid\">
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">Serviços Ativos</div>
                            </div>
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 em estoque</div>
                            </div>
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 vendidos</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class=\"one wide column\">
                    <div class=\"row\">
                        <i class=\"ellipsis horizontal grey icon\"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"ui segment\">
            <div class=\"ui two columns grid\">
                <div class=\"column\">
                    <h3 class=\"ui header\"> Novidades </h3>
                </div>
                <div class=\"right aligned column\">
                    <h5 class=\"ui header\"> Ver todas as novidades &gt; </h5>
                </div>
            </div>
            <div class=\"ui two columns grid\">
                <div class=\"column\" style=\"color:#00000061;\"> Versão 2.4.0 </div>
                <div class=\"right aligned column\" style=\"color:#00000061;\"> 25/02/2019 </div>
            </div>
            <p style=\"color:#00000061;\">Após efetivar o cadastro você tem à disposição diversas funcionalidades para aumentar os recursos de seu posicionamento na internet e potencializar ainda mais seu negócio, atraindo e convertendo vendas e clientes e profissionalizando ainda mais sua atividade.</p>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"b67e53ae2f70f150c2341f00e7279778\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"b67e53ae2f70f150c2341f00e7279778\"}',
            ],
            [
                'page_id' => 24,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Cookies Devem Estar Ativados no Seu Navegador',
                'id' => 'cookies-devem-estar-ativados-no-seu-navegador',
                'language' => 'pt-br',
                'path' => 'cookies-devem-estar-ativados-no-seu-navegador/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <div class=\"ui icon huge header\">
                <i class=\"settings icon\"></i>
                <div class=\"content\"> Cookies Devem Estar Ativados no Seu Navegador <div class=\"sub header\">O sistema detectou que os Cookies estão desativados no seu navegador de internet. Para que o nosso sistema funcione corretamente, é necessário que esteja ativado Cookies e JavaScript no seu navegador. Cada navegador tem uma forma de habilitar esta opção. Qualquer dificuldades entre em contato com nosso suporte.</div>
                </div>
            </div>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"6a6bcdbc0f0c0cb3435707ccc276ab3c\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"6a6bcdbc0f0c0cb3435707ccc276ab3c\"}',
            ],
            [
                'page_id' => 25,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Templates Páginas - Adicionar',
                'id' => 'admin-templates-paginas-adicionar',
                'language' => 'pt-br',
                'path' => 'admin-templates-paginas-adicionar/',
                'type' => 'system',
                'module' => 'admin-templates-paginas',
                'option' => 'adicionar',
                'root' => 0,
                'no_permission' => null,
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
                'page_id' => 26,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Templates Páginas - Editar',
                'id' => 'admin-templates-paginas-editar',
                'language' => 'pt-br',
                'path' => 'admin-templates-paginas-editar/',
                'type' => 'system',
                'module' => 'admin-templates-paginas',
                'option' => 'editar',
                'root' => 0,
                'no_permission' => null,
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
            [
                'page_id' => 27,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Templates Layouts - Adicionar',
                'id' => 'admin-templates-layouts-adicionar',
                'language' => 'pt-br',
                'path' => 'admin-templates-layouts-adicionar/',
                'type' => 'system',
                'module' => 'admin-templates-layouts',
                'option' => 'adicionar',
                'root' => 0,
                'no_permission' => null,
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
                'page_id' => 28,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Templates Layouts - Editar',
                'id' => 'admin-templates-layouts-editar',
                'language' => 'pt-br',
                'path' => 'admin-templates-layouts-editar/',
                'type' => 'system',
                'module' => 'admin-templates-layouts',
                'option' => 'editar',
                'root' => 0,
                'no_permission' => null,
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
            [
                'page_id' => 29,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pagina Serviços',
                'id' => 'pagina-servicos',
                'language' => 'pt-br',
                'path' => 'pagina-servicos/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"desktopcode\">
    <div class=\"espaco-entre-elementos\"></div>
    <div class=\"ui grid\">
        <div class=\"four wide column\"></div>
        <div class=\"eight wide column\">
            <div class=\"ui grid\">
                <div class=\"left aligned sixteen wide column\">
                    <h3>#Título#</h3>
                </div>
                <div class=\"ui grid\">
                    <div class=\"six wide column\">
                        <div class=\"ui placeholder imagemph\">
                            <div class=\"image\"></div>
                        </div>
                    </div>
                    <div class=\"ten wide column\">
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
                        <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
                    </div>
                </div>
            </div>
            <div class=\"ui grid\">
                <div class=\"six wide column\">
                    <div class=\"ui fluid primary button\">Comprar</div>
                </div>
                <div class=\"ten wide column\">
                    <h5 class=\"ui header\">Preço: #preço#</h5>
                </div>
            </div>
        </div>
        <div class=\"four wide column\"></div>
    </div>
</div>
<!-- Mobile Code -->
<div class=\"mobilecode\">
    <div class=\"espaco-entre-elementos\"></div>
    <div class=\"ui padded grid\">
        <div class=\"ui grid\">
            <div class=\"left aligned sixteen wide column\">
                <h3>#Título#</h3>
            </div>
            <div class=\"ui grid\">
                <div class=\"six wide column\">
                    <div class=\"ui placeholder imagemph\">
                        <div class=\"image\"></div>
                    </div>
                </div>
                <div class=\"ten wide column\">
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
                </div>
            </div>
        </div>
    </div>
    <div class=\"ui padded grid\">
        <div class=\"six wide column\">
            <div class=\"ui fluid primary button\">Comprar</div>
        </div>
        <div class=\"ten wide column\">
            <h5 class=\"ui header\">Preço: #preço#</h5>
        </div>
    </div>
</div>
<div id=\"gestor-listener\"></div>',
                'css' => '.hover-serviços:hover {
    background-color: lightblue;
}
.espaco-entre-elementos {
    height: 80px;
}
.imagemph{
    min-width: 180px;
    max-width: 250px;
    height: 250px;
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
                'checksum' => '{\"html\":\"953ffe145d86aa7948e6a2c474f9eceb\",\"css\":\"fde6ea95079beef1c779a8e5d35cd335\",\"combined\":\"8deb29bf499075da22236182e50c46a4\"}',
            ],
            [
                'page_id' => 30,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Página Mestre Teste - Serviço',
                'id' => 'pagina-mestre-teste-servico',
                'language' => 'pt-br',
                'path' => 'pagina-mestre-teste-servico/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"ui hidden divider\"></div>
<div class=\"ui large header\">#titulo#</div>
<div class=\"ui stackable grid\">
    <div class=\"six wide column\">
        <img class=\"ui fluid image\" src=\"/images/imagem-padrao.png\">
    </div>
    <div class=\"ten wide column\">
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
        <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
    </div>
</div>
<div class=\"ui small header\">Preço: #preco#</div>
<div class=\"ui stackable grid\">
    <div class=\"six wide column\">
        <div class=\"ui fluid primary button\">Comprar</div>
    </div>
    <div class=\"ten wide column\">

    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"bd25480282b4ba5883c1aaf764556925\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"bd25480282b4ba5883c1aaf764556925\"}',
            ],
            [
                'page_id' => 31,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Página Mestre Teste - Carrinho',
                'id' => 'pagina-mestre-teste-carrinho',
                'language' => 'pt-br',
                'path' => 'pagina-mestre-teste-carrinho/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"ui hidden divider\"></div>
<div class=\"ui hidden divider\"></div>
<div class=\"ui stackable grid\">
    <div class=\"two column row\">
        <div class=\"ten wide column\">
            <!-- cel-servicos < -->
            <div class=\"ui segment\">
                <div class=\"excluir\">
                    <i class=\"big times circle icon\"></i>
                </div>
                <div class=\"botao-mais\">
                    <button class=\"ui medium icon button botoes\">
                        <i class=\"plus circle icon\"></i>
                    </button>
                </div>
                <div class=\"botao-menos\">
                    <button class=\"ui medium icon button botoes\">
                        <i class=\"minus circle icon\"></i>
                    </button>
                </div>
                <div class=\"ui three column grid\">
                    <div class=\"row\">
                        <div class=\"five wide column\">
                            <img class=\"ui fluid image\" src=\"/images/imagem-padrao.png\">
                        </div>
                        <div class=\"eight wide column\">
                            <div class=\"ui medium header\">#servico-nome#</div>
                            <div class=\"ui hidden divider\"></div>
                            <div class=\"ui tiny header\">R$ #preco#</div>
                            <div class=\"ui tiny header\">Subtotal: R$ #subtotal#</div>
                        </div>
                        <div class=\"three wide middle aligned column\">
                            <div class=\"ui small header\">
                                <div class=\"quantidade\">99</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- cel-servicos > -->
        </div>
        <div class=\"six wide column\">
            <div class=\"ui segment\">
                <div class=\"ui medium header\">Resumo do Pedido</div>
                <div class=\"ui divider\"></div>
                <div class=\"ui grid\">
                    <div class=\"four wide column\">
                        <i class=\"huge black file alternate icon\"></i>
                    </div>
                    <div class=\"twelve wide column\">
                        <div class=\"ui medium header\">#servico-nome#</div>
                        <div class=\"ui tiny header\">Subtotal: R$ #subtotal#</div>
                    </div>
                </div>
                <div class=\"ui divider\"></div>
                <div class=\"ui small header\">Subtotal: R$ #subtotal#</div>
                <div class=\"ui small header\">Desconto: R$ #desconto#</div>
                <div class=\"ui divider\"></div>
                <div class=\"ui medium header\">Total: R$ #total#</div>
                <div class=\"ui divider\"></div>
                <button class=\"fluid ui black button\">Prosseguir para identificação</button>
                <button class=\"fluid ui button\">Continuar comprando</button>
            </div>
        </div>
    </div>
</div>
<div class=\"ui hidden divider\"></div>
<div class=\"ui icon message\">
    <i class=\"huge icons\">
        <i class=\"shopping cart icon\"></i>
        <i class=\"top right corner times red icon\"></i>
    </i>
    <div class=\"content\">
        <div class=\"header\"> Carrinho Vazio </div>
        <p>O seu carrinho está vazio. É necessário incluir ítens no carrinho para poder continuar.</p>
    </div>
</div>
<!-- step [[
<div class=\"ui tiny ordered steps\">
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
]] step -->
<!-- step-mobile [[
<div class=\"ui tiny fluid steps\">
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
]] step-mobile -->',
                'css' => '.excluir {
    position: absolute;
    left: -17px;
    top: 10px;
    background-color:#FFF;
    cursor:pointer;
    width:30px;
    z-index:1;
}
.segment > .button {
    margin-bottom: 0.75em;
}
.botao-mais {
    position: absolute;
    right: 10px;
    top: 10px;
    z-index:1;
}
.botao-menos {
    position: absolute;
    right: 10px;
    bottom: 10px;
    z-index:1;
}
.quantidade{
	text-align:right;
    padding-right:13px;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"db02ed9e6ab71dbbad5ddc22921285cb\",\"css\":\"70dc73092d8d147dadbf4b0e77ea0c11\",\"combined\":\"d24a07d68946f84a41842ba32a497473\"}',
            ],
            [
                'page_id' => 32,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Página Mestre Teste - Identificação',
                'id' => 'pagina-mestre-teste-identificacao',
                'language' => 'pt-br',
                'path' => 'pagina-mestre-teste-identificacao/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"ui hidden divider\"></div>
<div class=\"ui hidden divider\"></div>
<form class=\"ui form\">
    <div class=\"ui stackable grid\">
        <div class=\"two column row\">
            <div class=\"eight wide column barraLateral\">
                <div class=\"field\">
                    <h2>Já sou cliente</h2>
                    <input type=\"text\" name=\"Email\" placeholder=\"Email\">
                </div>
                <div class=\"field\">
                    <input type=\"text\" name=\"Senha\" placeholder=\"Senha\">
                </div>
                <div class=\"ui two column grid\">
                    <div class=\"ten wide column\">
                        <div class=\"field\">
                            <div class=\"ui checkbox\">
                                <input type=\"checkbox\" name=\"permanecer-logado\" value=\"1\">
                                <label>@[[login-keep-logged-in-label]]@</label>
                            </div>
                        </div>
                    </div>
                    <div class=\"six wide right aligned column\">
                        <a href=\"@[[pagina#url-raiz]]@#esqueceu-senha-url#\">Esqueci minha senha</a>
                    </div>
                </div>
                <div class=\"ui hidden divider\"></div>
                <div class=\"field\">
                    <button class=\"fluid ui button\">Login</button>
                </div>
            </div>
            <div class=\"eight wide column\">
                <div class=\"field\">
                    <h2>Criar uma conta</h2>
                    <input type=\"text\" name=\"Email\" placeholder=\"Email\">
                </div>
                <div class=\"field\">
                    <button class=\"fluid ui button\">Cadastrar</button>
                </div>
            </div>
        </div>
    </div>
</form>
<div class=\"ui hidden divider\"></div>
<div class=\"ui hidden divider\"></div>',
                'css' => '.barraLateral {
    border-right: 1px solid lightgrey;
}
@media screen and (max-width: 770px) {
    .barraLateral {
        border-right: none;
    }
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"fb6ee1a14fc4d4de98a0b6515b563aaa\",\"css\":\"53ba62b04ba8efa0a1de98a443892740\",\"combined\":\"84e0f9fb4657f353102fe381aad09d3e\"}',
            ],
            [
                'page_id' => 33,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Página Mestre Teste - Identificação Cadastro',
                'id' => 'pagina-mestre-teste-identificacao-cadastro',
                'language' => 'pt-br',
                'path' => 'pagina-mestre-teste-identificacao-cadastro/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"ui hidden divider\"></div>
<div class=\"ui hidden divider\"></div>
<form class=\"ui form\">
    <h2>Cadastro</h2>
    <div class=\"field\">
        <input type=\"text\" name=\"Email\" placeholder=\"Email\">
    </div>
    <div class=\"two fields\">
        <div class=\"field\">
            <input type=\"text\" name=\"Nome\" placeholder=\"Nome\">
        </div>
        <div class=\"field\">
            <input type=\"text\" name=\"Sobrenome\" placeholder=\"Sobrenome\">
        </div>
    </div>
    <div class=\"two fields\">
        <div class=\"field\">
            <input type=\"text\" name=\"Senha\" placeholder=\"Senha\">
        </div>
        <div class=\"field\">
            <input type=\"text\" name=\"Confirmar senha\" placeholder=\"Confirmar senha\">
        </div>
    </div>
    <div class=\"field\">
        <div class=\"ui buttons\">
            <div class=\"ui button active\">CPF</div>
            <div class=\"or\" data-text=\"ou\"></div>
            <div class=\"ui button\">CNPJ</div>
        </div>
    </div>
    <div class=\"two fields\">
        <div class=\"field\">
            <input type=\"text\" name=\"CPF\" placeholder=\"CPF\">
        </div>
        <div class=\"field\">
            
        </div>
    </div>
    <div class=\"two fields\">
        <div class=\"field\">
            <input type=\"text\" name=\"CNPJ\" placeholder=\"CNPJ\">
        </div>
        <div class=\"field\">
            
        </div>
    </div>
    <button class=\"fluid ui button\">Cadastrar</button>
</form>
<div class=\"ui hidden divider\"></div>
<div class=\"ui hidden divider\"></div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"5836c825fc24ece9e2e48ec14af4e151\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"5836c825fc24ece9e2e48ec14af4e151\"}',
            ],
            [
                'page_id' => 34,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Página de Impressão',
                'id' => 'pagina-de-impressao',
                'language' => 'pt-br',
                'path' => 'pagina-de-impressao/',
                'type' => 'system',
                'module' => null,
                'option' => 'impressao',
                'root' => 0,
                'no_permission' => null,
                'html' => null,
                'css' => '@media print {
    .pagebreak { page-break-before: always; } /* page-break-after works, as well */
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"6f08edd9ae603692713a90a1bd40ec1d\",\"combined\":\"6f08edd9ae603692713a90a1bd40ec1d\"}',
            ],
            [
                'page_id' => 35,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuários Hosts - Adicionar',
                'id' => 'usuarios-hosts-adicionar',
                'language' => 'pt-br',
                'path' => 'usuarios-hosts-adicionar/',
                'type' => 'system',
                'module' => 'usuarios-hosts',
                'option' => 'adicionar',
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>@[[form-name-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<table class=\"ui celled table\">
    <thead>
        <tr>
            <th>@[[first-name]]@</th>
            <th>@[[middle-name]]@</th>
            <th>@[[last-name]]@</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class=\"first-name\"></td>
            <td class=\"middle-name\"></td>
            <td class=\"last-name\"></td>
        </tr>
    </tbody>
</table>
<div class=\"field\">
    <label>@[[form-user-profile-label]]@</label>
    <span>#select-user-profile#</span>
</div>
<div class=\"field\">
    <label>@[[form-email-label]]@</label>
    <input type=\"email\" name=\"email\" placeholder=\"@[[form-email-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<div class=\"field\">
    <label>@[[form-email-2-label]]@</label>
    <input type=\"email\" name=\"email-2\" placeholder=\"@[[form-email-2-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<div class=\"field\">
    <label>@[[form-user-label]]@</label>
    <input type=\"text\" name=\"usuario\" placeholder=\"@[[form-user-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<div class=\"two fields\">
    <div class=\"field\">
        <label>@[[form-password-label]]@</label>
        <input type=\"password\" name=\"senha\" placeholder=\"@[[form-password-placeholder]]@\" autocomplete=\"new-password\" />
    </div>
    <div class=\"field\">
        <label>@[[form-password-2-label]]@</label>
        <input type=\"password\" name=\"senha-2\" placeholder=\"@[[form-password-2-placeholder]]@\" autocomplete=\"new-password\" />
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"0e4d39920b17ee4329ec7570f23f9247\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"0e4d39920b17ee4329ec7570f23f9247\"}',
            ],
            [
                'page_id' => 36,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuários Hosts - Editar',
                'id' => 'usuarios-hosts-editar',
                'language' => 'pt-br',
                'path' => 'usuarios-hosts-editar/',
                'type' => 'system',
                'module' => 'usuarios-hosts',
                'option' => 'editar',
                'root' => 0,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>@[[form-name-account-label]]@</label>
    <input type=\"text\" name=\"nome_conta\" placeholder=\"@[[form-name-account-placeholder]]@\" value=\"#nome_conta#\">
</div>
<div class=\"field\">
    <label>@[[form-name-user-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-user-placeholder]]@\" value=\"#nome#\">
</div>
<table class=\"ui celled table\">
    <thead>
        <tr>
            <th>@[[first-name]]@</th>
            <th>@[[middle-name]]@</th>
            <th>@[[last-name]]@</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class=\"first-name\">#primeiro_nome#</td>
            <td class=\"middle-name\">#nome_do_meio#</td>
            <td class=\"last-name\">#ultimo_nome#</td>
        </tr>
    </tbody>
</table>
<div class=\"field\">
    <label>@[[form-user-profile-label]]@</label>
    <span>#select-user-profile#</span>
</div>
<div class=\"field\">
    <label>@[[form-email-label]]@</label>
    <input type=\"email\" name=\"email\" placeholder=\"@[[form-email-placeholder]]@\" value=\"#email#\">
</div>
<div class=\"field\">
    <label>@[[form-email-2-label]]@</label>
    <input type=\"email\" name=\"email-2\" placeholder=\"@[[form-email-2-placeholder]]@\" value=\"#email#\">
</div>
<div class=\"field\">
    <label>@[[form-user-label]]@</label>
    <input type=\"text\" name=\"usuario\" placeholder=\"@[[form-user-placeholder]]@\" value=\"#usuario#\">
</div>
<!-- senha-campos < -->
<div class=\"two fields\" id=\"senha-campos\">
    <div class=\"field\">
        <label>@[[form-password-label]]@</label>
        <input type=\"password\" name=\"senha\" placeholder=\"@[[form-password-placeholder]]@\" autocomplete=\"new-password\">
    </div>
    <div class=\"field\">
        <label>@[[form-password-2-label]]@</label>
        <input type=\"password\" name=\"senha-2\" placeholder=\"@[[form-password-2-placeholder]]@\" autocomplete=\"new-password\">
    </div>
    <input type=\"hidden\" name=\"senha-atualizar\" value=\"1\">
</div><!-- senha-campos > -->
<!-- senha-botao < -->
<div class=\"field\">
    <label>@[[form-password-label]]@</label>
    <a class=\"ui button\" href=\"@[[pagina#url-raiz]]@@[[pagina#url-caminho]]@?id=@[[pagina#registro-id]]@&amp;password-button=1#senha-campos\">
        <i class=\"user lock icon\"></i> @[[form-password-button]]@ </a>
</div>
<!-- senha-botao > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"1a0a414a647f601b9b58df1668a12ca5\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"1a0a414a647f601b9b58df1668a12ca5\"}',
            ],
            [
                'page_id' => 37,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Instalação Concluída',
                'id' => 'instalacao-sucesso',
                'language' => 'pt-br',
                'path' => 'instalacao-sucesso/',
                'type' => 'page',
                'module' => null,
                'option' => null,
                'root' => 0,
                'no_permission' => null,
                'html' => '<div style=\"text-align: center; padding: 40px; font-family: Arial, sans-serif;\">
    <div style=\"max-width: 600px; margin: 0 auto;\">
        <div style=\"background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 20px; margin-bottom: 20px;\">
            <h1 style=\"color: #155724; margin: 0 0 15px 0;\">
                🎉 Instalação Concluída com Sucesso!
            </h1>
            <p style=\"color: #155724; margin: 0; font-size: 16px;\">
                O Conn2Flow foi instalado e configurado com sucesso em seu servidor.
            </p>
        </div>
        
        <div style=\"background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 20px; margin-bottom: 20px;\">
            <h3 style=\"color: #333; margin-top: 0;\">Próximos Passos:</h3>
            <ol style=\"text-align: left; color: #666;\">
                <li>Acesse o painel administrativo do seu site</li>
                <li>Configure suas preferências de sistema</li>
                <li>Personalize o design e conteúdo</li>
                <li>Comece a usar o Conn2Flow!</li>
            </ol>
        </div>
        
        <div>
            <a href=\"./\" style=\"background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;\">
                Acessar Painel Administrativo
            </a>
        </div>
        
        <p style=\"margin-top: 20px; color: #666; font-size: 14px;\">
            Esta página será removida automaticamente após o primeiro acesso ao painel.
        </p>
    </div>
</div>',
                'css' => 'body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"6e46c9e040b94361a08511d690dc888a\",\"css\":\"d66e854af0c522ca96e846dc7add5a36\",\"combined\":\"0ed96a5ede0eadef2e29045aee7518a2\"}',
            ],
            [
                'page_id' => 38,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Arquivos',
                'id' => 'admin-arquivos',
                'language' => 'pt-br',
                'path' => 'admin-arquivos/',
                'type' => 'sistema',
                'module' => 'admin-arquivos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<h1 class=\"ui header\">@[[pagina#titulo]]@</h1>
<div class=\"ui basic right aligned segment\">
    <a class=\"ui button blue\" href=\"@[[pagina#url-raiz]]@@[[pagina#modulo-id]]@/adicionar/#paginaIframe#\" data-content=\"@[[button-add-tooltip]]@\" data-id=\"adicionar\">
        <i class=\"plus circle icon\"></i> @[[button-add-label]]@ </a>
</div>
<div class=\"filesFilterCont hidden\">
    <div class=\"ui large header\">@[[filter-label]]@</div>
    <div class=\"ui teal message\">@[[filter-info]]@</div>
    <div class=\"ui grid stackable\">
        <div class=\"eight wide column\">
            <div class=\"ui medium header\">@[[date-label]]@</div>
            <div class=\"ui form\">
                <div class=\"two fields\">
                    <div class=\"field\">
                        <div class=\"ui calendar inverted\" id=\"rangestart\">
                            <div class=\"ui input left icon\">
                                <i class=\"calendar icon\"></i>
                                <input type=\"text\" placeholder=\"@[[date-start-label]]@\">
                            </div>
                        </div>
                    </div>
                    <div class=\"field\">
                        <div class=\"ui calendar inverted\" id=\"rangeend\">
                            <div class=\"ui input left icon\">
                                <i class=\"calendar icon\"></i>
                                <input type=\"text\" placeholder=\"@[[date-end-label]]@\">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"eight wide column\">
            <div class=\"ui medium header\">@[[categories-label]]@</div>
            <span>#select-categories#</span>
        </div>
        <div class=\"eight wide column\">
            <div class=\"ui medium header\">@[[order-label]]@</div>
            <span>#select-order#</span>
        </div>
        <div class=\"eight wide column\">
            <p>&nbsp;</p>
        </div>
        <div class=\"eight wide column\">
            <button class=\"ui positive button filterButton\">@[[filter-button]]@</button>
            <button class=\"ui blue button clearButton\">@[[clear-button]]@</button>
        </div>
    </div>
</div>
<p>&nbsp;</p>
<div class=\"listFilesCont hidden\">
    <div class=\"ui large header\">@[[files-list-label]]@</div>
    <div id=\"files-list-cont\">#arquivos-lista#</div>
    <div class=\"ui basic center aligned segment hidden listMoreResultsCont\">
        <button class=\"ui blue button moreResultsButton\" id=\"lista-mais-resultados\">@[[more-results-button]]@</button>
    </div>
</div>
<div class=\"withoutResultsCont hidden\"> #without-results-cont# </div>
<div class=\"withoutFilesCont hidden\"> #without-files-cont# </div>',
                'css' => '.hidden{
    display:none;
}
.extra.content{
	line-height: 3.5em;
}
.fileImage{
    position: relative;
    width: 100%;
    padding-top: 75%;
    overflow:hidden;
}
.fileImage img{
    position: absolute;
    object-fit: cover;
    width: 100%;
    height: 100%;
    top:0;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"e39ca6c90ae587323b153dc33bbc47e3\",\"css\":\"701235bfff333789477b95cef8060234\",\"combined\":\"1bcf1f5c7525622aaa54e67247dfb666\"}',
            ],
            [
                'page_id' => 39,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Arquivos Adicionar',
                'id' => 'admin-arquivos-adicionar',
                'language' => 'pt-br',
                'path' => 'admin-arquivos-adicionar/',
                'type' => 'sistema',
                'module' => 'admin-arquivos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<!-- botao-voltar < -->
<a class=\"botaoVoltar\" href=\"#url#\">
    <i class=\"arrow circle left big link icon\"></i>
</a><!-- botao-voltar > -->
<h1 class=\"ui header\">@[[pagina#titulo]]@</h1>
<div class=\"ui large header\">@[[add-files-options-header]]@</div>
<div class=\"ui grid stackable\">
    <div class=\"six wide column\">
        <div class=\"ui medium header\">@[[add-files-label]]@</div>
        <p>
            <input id=\"fileupload\" type=\"file\" name=\"files[]\" multiple=\"\">
            <label for=\"fileupload\" class=\"ui button positive\">@[[add-button-label]]@</label>
        </p>
    </div>
    <div class=\"ten wide column\">
        <div class=\"ui medium header\">@[[add-categories-label]]@</div>
        <div class=\"ui teal message\">@[[add-categories-info]]@</div>
        <span>#select-categories#</span>
    </div>
    <div class=\"six wide column\">
        <div class=\"fileButtonsAll hidden\">
            <div class=\"ui medium header\">@[[add-buttons-header]]@</div>
            <button class=\"ui positive button fileSendAll\">@[[add-file-buton-all-send]]@</button>
            <button class=\"ui negative button fileCancelAll\">@[[add-file-buton-all-cancel]]@</button>
        </div>
    </div>
    <div class=\"ten wide column\">
        <div class=\"fileWaitAll hidden\">
            <div class=\"ui medium header\">@[[add-status-header]]@</div>
            <div class=\"ui blue filling indeterminate progress\">
                <div class=\"bar\">
                    <div class=\"progress\">@[[add-file-progress-waiting]]@</div>
                </div>
            </div>
        </div>
        <div class=\"fileProgressAll hidden\">
            <div class=\"ui medium header\">@[[add-status-header]]@</div>
            <div class=\"ui indicating progress\" data-percent=\"0\">
                <div class=\"bar\">
                    <div class=\"progress\"></div>
                </div>
                <div class=\"label\">@[[add-file-progress-all]]@</div>
            </div>
        </div>
    </div>
</div>
<div class=\"ui large header hidden filesHeader\">@[[add-files-header]]@</div>
<div id=\"files-cont\"></div>
<!-- files-cel < -->
<div class=\"ui segment\">
    <div class=\"ui grid stackable\">
        <div class=\"four wide column\">
            <img class=\"ui image\" style=\"width: 200px; height: 200px; object-fit: cover;\" id=\"#file-img-id#\" src=\"#file-img-src#\">
        </div>
        <div class=\"eight wide column middle aligned\">
            <table class=\"ui definition table\">
                <tbody>
                    <tr>
                        <td class=\"five wide\">@[[add-file-name]]@</td>
                        <td class=\"eleven wide\">#file-name#</td>
                    </tr>
                    <tr>
                        <td>@[[add-file-last-modified]]@</td>
                        <td>#file-last-modified#</td>
                    </tr>
                    <tr>
                        <td>@[[add-file-size]]@</td>
                        <td>#file-size#</td>
                    </tr>
                    <tr>
                        <td>@[[add-file-type]]@</td>
                        <td>#file-type#</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class=\"four wide column middle aligned\">
            <div class=\"fileWait\">
                <div class=\"ui blue filling indeterminate progress\">
                    <div class=\"bar\">
                        <div class=\"progress\">@[[add-file-progress-waiting]]@</div>
                    </div>
                </div>
            </div>
            <div class=\"fileProgress hidden\">
                <div class=\"ui indicating progress\" data-percent=\"0\">
                    <div class=\"bar\">
                        <div class=\"progress\"></div>
                    </div>
                    <div class=\"label\">@[[add-file-progress]]@</div>
                </div>
            </div>
            <div class=\"ui container center aligned\">
                <button class=\"ui positive button fileSend\">@[[add-file-buton-send]]@</button>
                <button class=\"ui negative button fileCancel\">@[[add-file-buton-cancel]]@</button>
                <div class=\"fileDone hidden\">
                    <!-- btn-select < -->
                    <button class=\"ui blue button fileSelect\">@[[add-files-select]]@</button><!-- btn-select > -->
                    <!-- btn-copy < -->
                    <button class=\"ui blue button fileCopyClipboard\">@[[add-files-copy-clipboard]]@</button><!-- btn-copy > -->
                </div>
            </div>
            <div class=\"fileError hidden\">
                <div class=\"ui negative message\">
                    <div class=\"header\"> @[[add-file-progress-error-head]]@ </div>
                    <p class=\"fileErrorBody\">erro</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- files-cel > -->',
                'css' => 'input[type=\"file\"] { 
    opacity: 0; /* make transparent */
    z-index: -1; /* move under anything else */
    position: absolute; /* don\\\'t let it take up space */
}
#files-cont{
    margin-top:20px;
}
.hidden{
    display:none;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"6de224c1f87273e92e870c0870da2cf8\",\"css\":\"be42908fa1ef5959dc07e53d527fe60d\",\"combined\":\"a2f5ff655b3049686fb2e030af6d7261\"}',
            ],
            [
                'page_id' => 40,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Categorias',
                'id' => 'admin-categorias',
                'language' => 'pt-br',
                'path' => 'admin-categorias/',
                'type' => 'sistema',
                'module' => 'admin-categorias',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
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
                'page_id' => 41,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Categorias Adicionar',
                'id' => 'admin-categorias-adicionar',
                'language' => 'pt-br',
                'path' => 'admin-categorias-adicionar/',
                'type' => 'sistema',
                'module' => 'admin-categorias',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"field\">
    <label>#form-module-label#</label>
    <span>#select-module#</span>
</div>
<div class=\"field\">
    <label>@[[form-plugin-label]]@</label>
    <span>#select-plugin#</span>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"fc2e7b2eec2cda42ffc77a68ff6c4a5d\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"fc2e7b2eec2cda42ffc77a68ff6c4a5d\"}',
            ],
            [
                'page_id' => 42,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Categorias Adicionar Filho',
                'id' => 'admin-categorias-adicionar-filho',
                'language' => 'pt-br',
                'path' => 'admin-categorias-adicionar-filho/',
                'type' => 'sistema',
                'module' => 'admin-categorias',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"ui visible info message\">
    <b>#form-name-parent-label#:</b> #categoria-pai-texto#.
</div>
<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"field\">
    <label>@[[form-plugin-label]]@</label>
    <span>#select-plugin#</span>
</div>
<input type=\"hidden\" name=\"id_pai\" value=\"#id-pai#\">',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"65a5fe2f37df43131bdfc83432513519\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"65a5fe2f37df43131bdfc83432513519\"}',
            ],
            [
                'page_id' => 43,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Categorias Editar',
                'id' => 'admin-categorias-editar',
                'language' => 'pt-br',
                'path' => 'admin-categorias-editar/',
                'type' => 'sistema',
                'module' => 'admin-categorias',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\" value=\"#nome#\">
</div>
<div class=\"field\">
    <label>#form-module-label#</label>
    <span>#select-module#</span>
</div>
<div class=\"field\">
    <label>@[[form-plugin-label]]@</label>
    <span>#select-plugin#</span>
</div>
<!-- categoria-pai < -->
<div class=\"field\">
    <label>#form-categories-parent-label#</label> #cont-categoria-pai#
</div><!-- categoria-pai > -->
<div class=\"field\">
    <label>#form-categories-child-label#</label>
    <a class=\"ui mini button orange\" href=\"@[[pagina#url-raiz]]@admin-categorias/adicionar-filho/?id=#categoria-pai#\" data-content=\"@[[category-child-placeholder]]@\" data-id=\"adicionar-filho\">
        <i class=\"plus circle icon\"></i> @[[category-child-label]]@ </a> #categorias-filho#
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"f203515a290225b3238882e883f9d828\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"f203515a290225b3238882e883f9d828\"}',
            ],
            [
                'page_id' => 44,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Componentes',
                'id' => 'admin-componentes',
                'language' => 'pt-br',
                'path' => 'admin-componentes/',
                'type' => 'sistema',
                'module' => 'admin-componentes',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
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
                'page_id' => 45,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Componentes Adicionar',
                'id' => 'admin-componentes-adicionar',
                'language' => 'pt-br',
                'path' => 'admin-componentes-adicionar/',
                'type' => 'sistema',
                'module' => 'admin-componentes',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"field\">
    <label>#form-module-label#</label>
    <span>#select-module#</span>
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
                'checksum' => '{\"html\":\"8f45096270270440a767a4b1714fb7e4\",\"css\":\"ce06862621b92714d6717376aba8c2ab\",\"combined\":\"a4cb44b858fc21c07bb5c3dfd2f3e491\"}',
            ],
            [
                'page_id' => 46,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Componentes Editar',
                'id' => 'admin-componentes-editar',
                'language' => 'pt-br',
                'path' => 'admin-componentes-editar/',
                'type' => 'sistema',
                'module' => 'admin-componentes',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\" value=\"#nome#\">
</div>
<div class=\"field\">
    <label>#form-module-label#</label>
    <span>#select-module#</span>
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
                'checksum' => '{\"html\":\"e77ceb8c3a19d117fe17fae2923d0150\",\"css\":\"790844bbf8e5096b40503eac9a60d721\",\"combined\":\"e5d14964cd275bbc93c126e153bd3063\"}',
            ],
            [
                'page_id' => 47,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Hosts',
                'id' => 'admin-hosts',
                'language' => 'pt-br',
                'path' => 'admin-hosts/',
                'type' => 'sistema',
                'module' => 'admin-hosts',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
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
                'page_id' => 48,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Hosts Editar',
                'id' => 'admin-hosts-editar',
                'language' => 'pt-br',
                'path' => 'admin-hosts-editar/',
                'type' => 'sistema',
                'module' => 'admin-hosts',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"ui dividing header\">Dados do Usuário Proprietário</div>
<table class=\"ui definition table\">
    <tbody>
        <tr>
            <td>Nome do Usuário</td>
            <td>@[[usuario-nome]]@</td>
        </tr>
        <tr>
            <td>E-mail</td>
            <td>@[[usuario-email]]@</td>
        </tr>
        <tr>
            <td>Usuário de Acesso</td>
            <td>@[[usuario-acesso]]@</td>
        </tr>
    </tbody>
</table>
<div class=\"ui dividing header\">Dados do Domínio</div>
<table class=\"ui definition table\">
    <tbody>
        <tr>
            <td>Plano</td>
            <td><span>#select-user-profile#</span></td>
        </tr>
        <tr>
            <td>Domínio</td>
            <td>@[[dominio]]@</td>
        </tr>
        <tr>
            <td>Usuário cPanel</td>
            <td>@[[user_cpanel]]@</td>
        </tr>
        <tr>
            <td>Usuário FTP</td>
            <td>@[[user_ftp]]@</td>
        </tr>
        <tr>
            <td>Usuário e Nome Banco de Dados</td>
            <td>@[[user_db]]@</td>
        </tr>
        <tr>
            <td>Versão do Gestor Cliente</td>
            <td>@[[gestor_cliente_versao]]@</td>
        </tr>
    </tbody>
</table>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"8dcc752f8fc5eff5a1398ba8c7e3a99c\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"8dcc752f8fc5eff5a1398ba8c7e3a99c\"}',
            ],
            [
                'page_id' => 49,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Paginas',
                'id' => 'admin-paginas',
                'language' => 'pt-br',
                'path' => 'admin-paginas/',
                'type' => 'sistema',
                'module' => 'admin-paginas',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
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
                'page_id' => 50,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Paginas Adicionar',
                'id' => 'admin-paginas-adicionar',
                'language' => 'pt-br',
                'path' => 'admin-paginas-adicionar/',
                'type' => 'sistema',
                'module' => 'admin-paginas',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"pagina-nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"field\">
    <label>#form-layout-label#</label>
    <span>#select-layout#</span>
</div>
<div class=\"ui top attached tabular menu\">
    <a class=\"active item\" data-tab=\"codigo-html\" data-tooltip=\"#form-html-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-html-label#</a>
    <a class=\"item\" data-tab=\"css\" data-tooltip=\"#form-css-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-css-label#</a>
</div>
<div class=\"ui bottom attached tab segment\" data-tab=\"codigo-html\">
    <textarea class=\"codemirror-html\" name=\"html\"></textarea>
</div>
<div class=\"ui bottom attached tab segment\" data-tab=\"css\">
    <textarea class=\"codemirror-css\" name=\"css\"></textarea>
</div>
<div class=\"ui dividing header\">#form-config-divider#</div>
<div class=\"three fields\">
    <div class=\"field\">
        <label>#form-type-label#</label>
        <span>#select-type#</span>
    </div>
    <div class=\"field\">
        <label>#form-module-label#</label>
        <span>#select-module#</span>
    </div>
    <div class=\"field\">
        <label>#form-option-label#</label>
        <input type=\"text\" name=\"pagina-opcao\" placeholder=\"#form-option-placeholder#\">
    </div>
</div>
<div class=\"field\">
    <label>#form-path-label#</label>
    <input type=\"text\" name=\"paginaCaminho\" placeholder=\"#form-path-placeholder#\">
</div>
<div class=\"fields\">
    <div class=\"field\">
        <label>#form-root-label#</label>
        <div class=\"ui toggle checkbox\">
            <input type=\"checkbox\" name=\"raiz\" data-checked=\"\">
            <label>&nbsp;</label>
        </div>
    </div>
    <!-- permissao-pagina < -->
    <div class=\"field\">
        <label>#form-permission-label#</label>
        <div class=\"ui toggle checkbox\">
            <input type=\"checkbox\" name=\"sem_permissao\" data-checked=\"\">
            <label>&nbsp;</label>
        </div>
    </div><!-- permissao-pagina > -->
</div> ',
                'css' => '.CodeMirror{
    border: 1px solid #ccc;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"e2dd968835e611beae51716d90667b38\",\"css\":\"790844bbf8e5096b40503eac9a60d721\",\"combined\":\"7144c4429027213c2c1400393622659f\"}',
            ],
            [
                'page_id' => 51,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Paginas Editar',
                'id' => 'admin-paginas-editar',
                'language' => 'pt-br',
                'path' => 'admin-paginas-editar/',
                'type' => 'sistema',
                'module' => 'admin-paginas',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"ui blue message\"> @[[form-url-label]]@: <b>
        <a href=\"#url#\">#url#</a>
    </b>. </div>
<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"pagina-nome\" placeholder=\"#form-name-placeholder#\" value=\"#pagina-nome#\">
</div>
<div class=\"field\">
    <label>#form-layout-label#</label>
    <span>#select-layout#</span>
</div>
<div class=\"ui top attached tabular menu\">
    <a class=\"active item\" data-tab=\"codigo-html\" data-tooltip=\"#form-html-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-html-label#</a>
    <a class=\"item\" data-tab=\"css\" data-tooltip=\"#form-css-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-css-label#</a>
</div>
<div class=\"ui bottom attached tab segment\" data-tab=\"codigo-html\">#pagina-html-backup# <textarea class=\"codemirror-html\" name=\"html\">#pagina-html#</textarea>
</div>
<div class=\"ui bottom attached tab segment\" data-tab=\"css\">#pagina-css-backup# <textarea class=\"codemirror-css\" name=\"css\">#pagina-css#</textarea>
</div>
<div class=\"ui dividing header\">#form-config-divider#</div>
<div class=\"three fields\">
    <div class=\"field\">
        <label>#form-type-label#</label>
        <span>#select-type#</span>
    </div>
    <div class=\"field\">
        <label>#form-module-label#</label>
        <span>#select-module#</span>
    </div>
    <div class=\"field\">
        <label>#form-option-label#</label>
        <input type=\"text\" name=\"pagina-opcao\" placeholder=\"#form-option-placeholder#\" value=\"#opcao#\">
    </div>
</div>
<div class=\"field\">
    <label>#form-path-label#</label>
    <input type=\"text\" name=\"paginaCaminho\" placeholder=\"#form-path-placeholder#\" value=\"#caminho#\">
</div>
<div class=\"fields\">
    <div class=\"field\">
        <label>#form-root-label#</label>
        <div class=\"ui toggle checkbox\">
            <input type=\"checkbox\" name=\"raiz\" data-checked=\"#raiz#\">
            <label>&nbsp;</label>
        </div>
    </div>
    <!-- permissao-pagina < -->
    <div class=\"field\">
        <label>#form-permission-label#</label>
        <div class=\"ui toggle checkbox\">
            <input type=\"checkbox\" name=\"sem_permissao\" data-checked=\"#sem_permissao#\">
            <label>&nbsp;</label>
        </div>
    </div><!-- permissao-pagina > -->
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ae1165f1c5049ef2d9eefe56fcbfca5d\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ae1165f1c5049ef2d9eefe56fcbfca5d\"}',
            ],
            [
                'page_id' => 52,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Plugins',
                'id' => 'admin-plugins',
                'language' => 'pt-br',
                'path' => 'admin-plugins/',
                'type' => 'sistema',
                'module' => 'admin-plugins',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
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
                'page_id' => 53,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Plugins Adicionar',
                'id' => 'admin-plugins-adicionar',
                'language' => 'pt-br',
                'path' => 'admin-plugins-adicionar/',
                'type' => 'sistema',
                'module' => 'admin-plugins',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>@[[form-name-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-placeholder]]@\">
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"554ce8157bb7aaaf4dba021b747a75a0\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"554ce8157bb7aaaf4dba021b747a75a0\"}',
            ],
            [
                'page_id' => 54,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Plugins Editar',
                'id' => 'admin-plugins-editar',
                'language' => 'pt-br',
                'path' => 'admin-plugins-editar/',
                'type' => 'sistema',
                'module' => 'admin-plugins',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>@[[form-name-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-placeholder]]@\" value=\"#nome#\">
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"c4bd791baff253a5adf99a634f2fbabb\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"c4bd791baff253a5adf99a634f2fbabb\"}',
            ],
            [
                'page_id' => 55,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Templates',
                'id' => 'admin-templates',
                'language' => 'pt-br',
                'path' => 'admin-templates/',
                'type' => 'sistema',
                'module' => 'admin-templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
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
                'page_id' => 56,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Templates Adicionar',
                'id' => 'admin-templates-adicionar',
                'language' => 'pt-br',
                'path' => 'admin-templates-adicionar/',
                'type' => 'sistema',
                'module' => 'admin-templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
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
    <label>#form-thumbnail-label#</label>
    <span>#imagepick-thumbnail#</span>
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
</div>
<input type=\"hidden\" name=\"modelo\" value=\"#modelo#\">',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"20eee4d1b720449bf75bb9abf6d826c0\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"20eee4d1b720449bf75bb9abf6d826c0\"}',
            ],
            [
                'page_id' => 57,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Templates Adicionar Paginas',
                'id' => 'admin-templates-adicionar-paginas',
                'language' => 'pt-br',
                'path' => 'admin-templates-adicionar-paginas/',
                'type' => 'sistema',
                'module' => 'admin-templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
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
    <label>#form-template-page-label#</label>
    <span>#select-templates_paginas#</span>
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
                'checksum' => '{\"html\":\"93dbe435ac34d94ebd06c5d89070c0a9\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"93dbe435ac34d94ebd06c5d89070c0a9\"}',
            ],
            [
                'page_id' => 58,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Templates Editar',
                'id' => 'admin-templates-editar',
                'language' => 'pt-br',
                'path' => 'admin-templates-editar/',
                'type' => 'sistema',
                'module' => 'admin-templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
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
    <label>#form-thumbnail-label#</label>
    <span>#imagepick-thumbnail#</span>
</div>
<div class=\"ui top attached tabular menu\">
    <a class=\"active item\" data-tab=\"codigo-html\" data-tooltip=\"#form-html-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-html-label#</a>
    <a class=\"item\" data-tab=\"css\" data-tooltip=\"#form-css-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-css-label#</a>
</div>
<div class=\"ui bottom attached active tab segment\" data-tab=\"codigo-html\">#pagina-html-backup# <textarea class=\"codemirror-html\" name=\"html\">#pagina-html#</textarea>
</div>
<div class=\"ui bottom attached tab segment\" data-tab=\"css\">#pagina-css-backup# <textarea class=\"codemirror-css\" name=\"css\">#pagina-css#</textarea>
</div>
<input type=\"hidden\" name=\"modelo\" value=\"#modelo#\">',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"07c02cd11a72d60971354dd3963faf3c\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"07c02cd11a72d60971354dd3963faf3c\"}',
            ],
            [
                'page_id' => 59,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Templates Editar Indice',
                'id' => 'admin-templates-editar-Indice',
                'language' => 'pt-br',
                'path' => 'admin-templates-editar-Indice/',
                'type' => 'sistema',
                'module' => 'admin-templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
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
                'page_id' => 60,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Templates Editar Paginas',
                'id' => 'admin-templates-editar-paginas',
                'language' => 'pt-br',
                'path' => 'admin-templates-editar-paginas/',
                'type' => 'sistema',
                'module' => 'admin-templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
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
    <label>#form-template-page-label#</label>
    <span>#select-templates_paginas#</span>
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
                'checksum' => '{\"html\":\"a24e35ba30eabbb76179e2da3a90fe2a\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"a24e35ba30eabbb76179e2da3a90fe2a\"}',
            ],
            [
                'page_id' => 61,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Templates Editar Template',
                'id' => 'admin-templates-editar-template',
                'language' => 'pt-br',
                'path' => 'admin-templates-editar-template/',
                'type' => 'sistema',
                'module' => 'admin-templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
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
                'page_id' => 62,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Admin Templates Pre Visualizacao',
                'id' => 'admin-templates-pre-visualizacao',
                'language' => 'pt-br',
                'path' => 'admin-templates-pre-visualizacao/',
                'type' => 'sistema',
                'module' => 'admin-templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"ui icon message\">
    <i class=\"file alternate icon\"></i>
    <div class=\"content\">
        <div class=\"header\"> Corpo da Página </div>
        <p>Está caixa será subistituída pelo corpo da página.</p>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"7d56049c47c89d1530a57c128b7240f5\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"7d56049c47c89d1530a57c128b7240f5\"}',
            ],
            [
                'page_id' => 63,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Arquivos',
                'id' => 'arquivos',
                'language' => 'pt-br',
                'path' => 'arquivos/',
                'type' => 'sistema',
                'module' => 'arquivos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<h1 class=\"ui header\">@[[pagina#titulo]]@</h1>
<div class=\"ui basic right aligned segment\">
    <a class=\"ui button blue\" href=\"@[[pagina#url-raiz]]@@[[pagina#modulo-id]]@/adicionar/#paginaIframe#\" data-content=\"@[[button-add-tooltip]]@\" data-id=\"adicionar\">
        <i class=\"plus circle icon\"></i> @[[button-add-label]]@ </a>
</div>
<div class=\"filesFilterCont hidden\">
    <div class=\"ui large header\">@[[filter-label]]@</div>
    <div class=\"ui teal message\">@[[filter-info]]@</div>
    <div class=\"ui grid stackable\">
        <div class=\"eight wide column\">
            <div class=\"ui medium header\">@[[date-label]]@</div>
            <div class=\"ui form\">
                <div class=\"two fields\">
                    <div class=\"field\">
                        <div class=\"ui calendar inverted\" id=\"rangestart\">
                            <div class=\"ui input left icon\">
                                <i class=\"calendar icon\"></i>
                                <input type=\"text\" placeholder=\"@[[date-start-label]]@\">
                            </div>
                        </div>
                    </div>
                    <div class=\"field\">
                        <div class=\"ui calendar inverted\" id=\"rangeend\">
                            <div class=\"ui input left icon\">
                                <i class=\"calendar icon\"></i>
                                <input type=\"text\" placeholder=\"@[[date-end-label]]@\">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"eight wide column\">
            <div class=\"ui medium header\">@[[categories-label]]@</div>
            <span>#select-categories#</span>
        </div>
        <div class=\"eight wide column\">
            <div class=\"ui medium header\">@[[order-label]]@</div>
            <span>#select-order#</span>
        </div>
        <div class=\"eight wide column\">
            <p>&nbsp;</p>
        </div>
        <div class=\"eight wide column\">
            <button class=\"ui positive button filterButton\">@[[filter-button]]@</button>
            <button class=\"ui blue button clearButton\">@[[clear-button]]@</button>
        </div>
    </div>
</div>
<p>&nbsp;</p>
<div class=\"listFilesCont hidden\">
    <div class=\"ui large header\">@[[files-list-label]]@</div>
    <div id=\"files-list-cont\">#arquivos-lista#</div>
    <div class=\"ui basic center aligned segment hidden listMoreResultsCont\">
        <button class=\"ui blue button moreResultsButton\" id=\"lista-mais-resultados\">@[[more-results-button]]@</button>
    </div>
</div>
<div class=\"withoutResultsCont hidden\"> #without-results-cont# </div>
<div class=\"withoutFilesCont hidden\"> #without-files-cont# </div>',
                'css' => '.hidden{
    display:none;
}
.extra.content{
	line-height: 3.5em;
}
.fileImage{
    position: relative;
    width: 100%;
    padding-top: 75%;
    overflow:hidden;
}
.fileImage img{
    position: absolute;
    object-fit: cover;
    width: 100%;
    height: 100%;
    top:0;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"e39ca6c90ae587323b153dc33bbc47e3\",\"css\":\"701235bfff333789477b95cef8060234\",\"combined\":\"1bcf1f5c7525622aaa54e67247dfb666\"}',
            ],
            [
                'page_id' => 64,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Arquivos Adicionar',
                'id' => 'arquivos-adicionar',
                'language' => 'pt-br',
                'path' => 'arquivos-adicionar/',
                'type' => 'sistema',
                'module' => 'arquivos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<!-- botao-voltar < -->
<a class=\"botaoVoltar\" href=\"#url#\">
    <i class=\"arrow circle left big link icon\"></i>
</a><!-- botao-voltar > -->
<h1 class=\"ui header\">@[[pagina#titulo]]@</h1>
<div class=\"ui large header\">@[[add-files-options-header]]@</div>
<div class=\"ui grid stackable\">
    <div class=\"six wide column\">
        <div class=\"ui medium header\">@[[add-files-label]]@</div>
        <p>
            <input id=\"fileupload\" type=\"file\" name=\"files[]\" multiple=\"\">
            <label for=\"fileupload\" class=\"ui button positive\">@[[add-button-label]]@</label>
        </p>
    </div>
    <div class=\"ten wide column\">
        <div class=\"ui medium header\">@[[add-categories-label]]@</div>
        <div class=\"ui teal message\">@[[add-categories-info]]@</div>
        <span>#select-categories#</span>
    </div>
    <div class=\"six wide column\">
        <div class=\"fileButtonsAll hidden\">
            <div class=\"ui medium header\">@[[add-buttons-header]]@</div>
            <button class=\"ui positive button fileSendAll\">@[[add-file-buton-all-send]]@</button>
            <button class=\"ui negative button fileCancelAll\">@[[add-file-buton-all-cancel]]@</button>
        </div>
    </div>
    <div class=\"ten wide column\">
        <div class=\"fileWaitAll hidden\">
            <div class=\"ui medium header\">@[[add-status-header]]@</div>
            <div class=\"ui blue filling indeterminate progress\">
                <div class=\"bar\">
                    <div class=\"progress\">@[[add-file-progress-waiting]]@</div>
                </div>
            </div>
        </div>
        <div class=\"fileProgressAll hidden\">
            <div class=\"ui medium header\">@[[add-status-header]]@</div>
            <div class=\"ui indicating progress\" data-percent=\"0\">
                <div class=\"bar\">
                    <div class=\"progress\"></div>
                </div>
                <div class=\"label\">@[[add-file-progress-all]]@</div>
            </div>
        </div>
    </div>
</div>
<div class=\"ui large header hidden filesHeader\">@[[add-files-header]]@</div>
<div id=\"files-cont\"></div>
<!-- files-cel < -->
<div class=\"ui segment\">
    <div class=\"ui grid stackable\">
        <div class=\"four wide column\">
            <img class=\"ui image\" style=\"width: 200px; height: 200px; object-fit: cover;\" id=\"#file-img-id#\" src=\"#file-img-src#\">
        </div>
        <div class=\"eight wide column middle aligned\">
            <table class=\"ui definition table\">
                <tbody>
                    <tr>
                        <td class=\"five wide\">@[[add-file-name]]@</td>
                        <td class=\"eleven wide\">#file-name#</td>
                    </tr>
                    <tr>
                        <td>@[[add-file-last-modified]]@</td>
                        <td>#file-last-modified#</td>
                    </tr>
                    <tr>
                        <td>@[[add-file-size]]@</td>
                        <td>#file-size#</td>
                    </tr>
                    <tr>
                        <td>@[[add-file-type]]@</td>
                        <td>#file-type#</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class=\"four wide column middle aligned\">
            <div class=\"fileWait\">
                <div class=\"ui blue filling indeterminate progress\">
                    <div class=\"bar\">
                        <div class=\"progress\">@[[add-file-progress-waiting]]@</div>
                    </div>
                </div>
            </div>
            <div class=\"fileProgress hidden\">
                <div class=\"ui indicating progress\" data-percent=\"0\">
                    <div class=\"bar\">
                        <div class=\"progress\"></div>
                    </div>
                    <div class=\"label\">@[[add-file-progress]]@</div>
                </div>
            </div>
            <div class=\"ui container center aligned\">
                <button class=\"ui positive button fileSend\">@[[add-file-buton-send]]@</button>
                <button class=\"ui negative button fileCancel\">@[[add-file-buton-cancel]]@</button>
                <div class=\"fileDone hidden\">
                    <!-- btn-select < -->
                    <button class=\"ui blue button fileSelect\">@[[add-files-select]]@</button><!-- btn-select > -->
                    <!-- btn-copy < -->
                    <button class=\"ui blue button fileCopyClipboard\">@[[add-files-copy-clipboard]]@</button><!-- btn-copy > -->
                </div>
            </div>
            <div class=\"fileError hidden\">
                <div class=\"ui negative message\">
                    <div class=\"header\"> @[[add-file-progress-error-head]]@ </div>
                    <p class=\"fileErrorBody\">erro</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- files-cel > -->',
                'css' => 'input[type=\"file\"] { 
    opacity: 0; /* make transparent */
    z-index: -1; /* move under anything else */
    position: absolute; /* don\\\'t let it take up space */
}
#files-cont{
    margin-top:20px;
}
.hidden{
    display:none;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"6de224c1f87273e92e870c0870da2cf8\",\"css\":\"be42908fa1ef5959dc07e53d527fe60d\",\"combined\":\"a2f5ff655b3049686fb2e030af6d7261\"}',
            ],
            [
                'page_id' => 65,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Categorias',
                'id' => 'categorias',
                'language' => 'pt-br',
                'path' => 'categorias/',
                'type' => 'sistema',
                'module' => 'categorias',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
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
                'page_id' => 66,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Categorias Adicionar',
                'id' => 'categorias-adicionar',
                'language' => 'pt-br',
                'path' => 'categorias-adicionar/',
                'type' => 'sistema',
                'module' => 'categorias',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"field\">
    <label>#form-module-label#</label>
    <span>#select-module#</span>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"a5485e53257ca3927a825918a3252fb0\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"a5485e53257ca3927a825918a3252fb0\"}',
            ],
            [
                'page_id' => 67,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Categorias Adicionar Filho',
                'id' => 'categorias-adicionar-filho',
                'language' => 'pt-br',
                'path' => 'categorias-adicionar-filho/',
                'type' => 'sistema',
                'module' => 'categorias',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"ui visible info message\">
    <b>#form-name-parent-label#:</b> #categoria-pai-texto#.
</div>
<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\">
</div>
<input type=\"hidden\" name=\"id_pai\" value=\"#id-pai#\">',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"e514b95e0e55fff79ccef296b3fc5394\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"e514b95e0e55fff79ccef296b3fc5394\"}',
            ],
            [
                'page_id' => 68,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Categorias Editar',
                'id' => 'categorias-editar',
                'language' => 'pt-br',
                'path' => 'categorias-editar/',
                'type' => 'sistema',
                'module' => 'categorias',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\" value=\"#nome#\">
</div>
<div class=\"field\">
    <label>#form-module-label#</label>
    <span>#select-module#</span>
</div>
<!-- categoria-pai < -->
<div class=\"field\">
    <label>#form-categories-parent-label#</label> #cont-categoria-pai#
</div><!-- categoria-pai > -->
<div class=\"field\">
    <label>#form-categories-child-label#</label>
    <a class=\"ui mini button orange\" href=\"@[[pagina#url-raiz]]@categorias/adicionar-filho/?id=#categoria-pai#\" data-content=\"@[[category-child-placeholder]]@\" data-id=\"adicionar-filho\">
        <i class=\"plus circle icon\"></i> @[[category-child-label]]@ </a> #categorias-filho#
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"51de020c2de6dd9982283c547701265a\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"51de020c2de6dd9982283c547701265a\"}',
            ],
            [
                'page_id' => 69,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Componentes',
                'id' => 'componentes',
                'language' => 'pt-br',
                'path' => 'componentes/',
                'type' => 'sistema',
                'module' => 'componentes',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
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
                'page_id' => 70,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Componentes Adicionar',
                'id' => 'componentes-adicionar',
                'language' => 'pt-br',
                'path' => 'componentes-adicionar/',
                'type' => 'sistema',
                'module' => 'componentes',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"field\">
    <label>#form-module-label#</label>
    <span>#select-module#</span>
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
                'checksum' => '{\"html\":\"8f45096270270440a767a4b1714fb7e4\",\"css\":\"ce06862621b92714d6717376aba8c2ab\",\"combined\":\"a4cb44b858fc21c07bb5c3dfd2f3e491\"}',
            ],
            [
                'page_id' => 71,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Componentes Editar',
                'id' => 'componentes-editar',
                'language' => 'pt-br',
                'path' => 'componentes-editar/',
                'type' => 'sistema',
                'module' => 'componentes',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\" value=\"#nome#\">
</div>
<div class=\"field\">
    <label>#form-module-label#</label>
    <span>#select-module#</span>
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
                'checksum' => '{\"html\":\"e77ceb8c3a19d117fe17fae2923d0150\",\"css\":\"790844bbf8e5096b40503eac9a60d721\",\"combined\":\"e5d14964cd275bbc93c126e153bd3063\"}',
            ],
            [
                'page_id' => 72,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Comunicacao Configuracoes',
                'id' => 'comunicacao-configuracoes',
                'language' => 'pt-br',
                'path' => 'comunicacao-configuracoes/',
                'type' => 'sistema',
                'module' => 'comunicacao-configuracoes',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<!-- comunicacao-configuracoes -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"a260107449f3e3a36513442c78ab2609\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"a260107449f3e3a36513442c78ab2609\"}',
            ],
            [
                'page_id' => 73,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Dashboard',
                'id' => 'dashboard',
                'language' => 'pt-br',
                'path' => 'dashboard/',
                'type' => 'sistema',
                'module' => 'dashboard',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<h1 class=\"ui header\">#titulo#</h1>
<div class=\"ui segment\">
    <!-- menu-item < -->
    <div class=\"ui dividing header\">#grupo#</div>
    <div class=\"ui three stackable cards\">
        <!-- card < -->
        <div class=\"ui card\">
            <div class=\"content\">
                <a class=\"header\" href=\"#link#\">
                    <div class=\"center aligned header\">
                        <!-- icon < --><i class=\"#icon# huge icon\"></i><!-- icon > -->
                        <!-- icon-2 < --><i class=\"large huge icons\">
                            <i class=\"#icon# icon\"></i>
                            <i class=\"#icon-2# icon\"></i>
                        </i><!-- icon-2 > -->
                    </div>
                </a>
            </div>
            <div class=\"content\">
                <a class=\"header\" href=\"#link#\">
                    <div class=\"center aligned header\">#nome#</div>
                </a>
            </div>
        </div><!-- card > -->
    </div><!-- menu-item > -->
    <div class=\"ui dividing header\">@[[logout-grup]]@</div>
    <div class=\"ui three stackable cards\">
        <div class=\"ui card\">
            <div class=\"content\">
                <a class=\"header\" href=\"@[[pagina#url-raiz]]@signout/\">
                    <div class=\"center aligned header\">
                        <i class=\"sign out alternate huge icon\"></i>
                    </div>
                </a>
            </div>
            <div class=\"content\">
                <a class=\"header\" href=\"@[[pagina#url-raiz]]@signout/\">
                    <div class=\"center aligned header\">@[[logout-label]]@</div>
                </a>
            </div>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"1b854ede756a411d1a317e20401c6b4d\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"1b854ede756a411d1a317e20401c6b4d\"}',
            ],
            [
                'page_id' => 74,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Testes Do Dashboard',
                'id' => 'testes-do-dashboard',
                'language' => 'pt-br',
                'path' => 'testes-do-dashboard/',
                'type' => 'sistema',
                'module' => 'dashboard',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"desktopcode\">
    <h2 class=\"frase ui header\" style=\"color:#00000099;\"> Seja bem-vindo, #usuário#, um ótimo dia hoje não? </h2>
    <div class=\"ui grid\">
        <div class=\"four wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui grid\">
                        <div class=\"eleven wide column\">
                            <div class=\"texto\">
                                <div class=\"ui large header\"> 15 <div class=\"sub header\">Novos Clientes</div>
                                </div>
                            </div>
                        </div>
                        <div class=\"right aligned five wide column\">
                            <i class=\"big comments outline grey icon\" style=\"color:#EBEBEB;\"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"four wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui two columns grid\">
                        <div class=\"column\">
                            <div class=\"ui large header\"> 22 <div class=\"sub header\">Vendas</div>
                            </div>
                        </div>
                        <div class=\"right aligned column\">
                            <i class=\"big tags grey icon\" style=\"color:#EBEBEB;\"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"four wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui two columns grid\">
                        <div class=\"column\">
                            <div class=\"ui large header\"> 15 <div class=\"sub header\">Receita</div>
                            </div>
                        </div>
                        <div class=\"right aligned column\">
                            <i class=\"big dollar sign grey icon\" style=\"color:#EBEBEB;\"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"four wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui grid\">
                        <div class=\"eleven wide column\">
                            <div class=\"ui large header\"> 15 <div class=\"sub header\">Total Mensal</div>
                            </div>
                        </div>
                        <div class=\"right aligned five wide column\">
                            <i class=\"big file alternate grey icon\" style=\"color:#EBEBEB;\"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class=\"ui grid\">
        <div class=\"eight wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui two columns grid\">
                        <div class=\"column\">
                            <h3 class=\"ui header\"> Resumo Mensal </h3>
                        </div>
                        <div class=\"right aligned column\">
                            <div class=\"ui compact menu\">
                                <div class=\"ui simple dropdown item\"> Fevereiro <i class=\"fluid dropdown icon\"></i>
                                    <div class=\"menu\">
                                        <div class=\"item\">Janeiro</div>
                                        <div class=\"item\">Fevereiro</div>
                                        <div class=\"item\">Março</div>
                                        <div class=\"item\">Abril</div>
                                        <div class=\"item\">Maio</div>
                                        <div class=\"item\">Junho</div>
                                        <div class=\"item\">Julho</div>
                                        <div class=\"item\">Agosto</div>
                                        <div class=\"item\">Setembro</div>
                                        <div class=\"item\">Outubro</div>
                                        <div class=\"item\">Novembro</div>
                                        <div class=\"item\">Dezembro</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class=\"ui placeholder\">
                        <div class=\"image\">Gráfico</div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"eight wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <h3 class=\"ui header\"> Pedidos Recentes </h3>
                    <div class=\"ui placeholder\">
                        <div class=\"image\">Gráfico</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class=\"ui grid\">
        <div class=\"eight wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui two columns grid\">
                        <div class=\"column\">
                            <h3 class=\"ui header\"> Serviços </h3>
                        </div>
                        <div class=\"right aligned column\">
                            <h5 class=\"ui header\"> Ver todos os serviços &gt; </h5>
                        </div>
                    </div>
                    <div class=\"ui sixteen wide columns grid\">
                        <div class=\"one wide column\">
                            <div class=\"row\">
                                <i class=\"check circle green icon\"></i>
                            </div>
                        </div>
                        <div class=\"thirteen wide centered column\">
                            <div class=\"row\">
                                <h4 class=\"ui header\" style=\"color:#000000DE;\"> Congresso internacional de Medicina Veterinária Integrativa </h4>
                                <div class=\"ui three column very relaxed grid\">
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\"> Serviços Ativos</div>
                                    </div>
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 em estoque</div>
                                    </div>
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 vendidos</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class=\"two wide right aligned column\">
                            <div class=\"row\">
                                <i class=\"ellipsis horizontal grey icon\"></i>
                            </div>
                        </div>
                    </div>
                    <div class=\"ui sixteen wide columns grid\">
                        <div class=\"one wide column\">
                            <div class=\"row\">
                                <i class=\"check circle green icon\"></i>
                            </div>
                        </div>
                        <div class=\"thirteen wide centered column\">
                            <div class=\"row\">
                                <h4 class=\"ui header\" style=\"color:#000000DE;\"> Congresso internacional de Medicina Veterinária Integrativa </h4>
                                <div class=\"ui three column very relaxed grid\">
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\"> Serviços Ativos</div>
                                    </div>
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 em estoque</div>
                                    </div>
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 vendidos</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class=\"two wide right aligned column\">
                            <div class=\"row\">
                                <i class=\"ellipsis horizontal grey icon\"></i>
                            </div>
                        </div>
                    </div>
                    <div class=\"ui sixteen wide columns grid\">
                        <div class=\"one wide column\">
                            <div class=\"row\">
                                <i class=\"times circle red icon\"></i>
                            </div>
                        </div>
                        <div class=\"thirteen wide centered column\">
                            <div class=\"row\">
                                <h4 class=\"ui header\" style=\"color:#000000DE;\"> Congresso internacional de Medicina Veterinária Integrativa </h4>
                                <div class=\"ui three column very relaxed grid\">
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\"> Serviços Ativos</div>
                                    </div>
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 em estoque</div>
                                    </div>
                                    <div class=\"column\">
                                        <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 vendidos</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class=\"two wide right aligned column\">
                            <div class=\"row\">
                                <i class=\"ellipsis horizontal grey icon\"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"eight wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"ui two columns grid\">
                        <div class=\"column\">
                            <h3 class=\"ui header\"> Novidades </h3>
                        </div>
                        <div class=\"right aligned column\">
                            <h5 class=\"ui header\"> Ver todas as novidades &gt; </h5>
                        </div>
                    </div>
                    <div class=\"ui two columns grid\">
                        <div class=\"column\" style=\"color:#00000061;\"> Versão 2.4.0 </div>
                        <div class=\"right aligned column\" style=\"color:#00000061;\"> 25/02/2019 </div>
                    </div>
                    <p style=\"color:#00000061;\">Após efetivar o cadastro você tem à disposição diversas funcionalidades para aumentar os recursos de seu posicionamento na internet e potencializar ainda mais seu negócio, atraindo e convertendo vendas e clientes e profissionalizando ainda mais sua atividade.</p>
                    <p style=\"color:#00000061;\">Teste grátis e sem compromisso o uso de todas as ferramentas por 07 dias. Nosso maior objetivo é que você e seus clientes tenham uma experiência única, simples e objetiva no uso da ferramenta.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Mobile Code -->
<div class=\"mobilecode\">
    <div class=\"margin\">
        <div class=\"ui segment\">
            <div class=\"ui two columns padded grid\">
                <div class=\"column\">
                    <div class=\"ui large header\"> 15 <div class=\"sub header\">Novos Clientes</div>
                    </div>
                </div>
                <div class=\"right aligned column\">
                    <i class=\"huge comments outline grey icon\" style=\"color:#EBEBEB;\"></i>
                </div>
            </div>
        </div>
        <div class=\"ui segment\">
            <div class=\"ui two columns padded grid\">
                <div class=\"column\">
                    <div class=\"ui large header\"> 22 <div class=\"sub header\">Vendas</div>
                    </div>
                </div>
                <div class=\"right aligned column\">
                    <i class=\"huge tags grey icon\" style=\"color:#EBEBEB;\"></i>
                </div>
            </div>
        </div>
        <div class=\"ui segment\">
            <div class=\"ui two columns padded grid\">
                <div class=\"column\">
                    <div class=\"ui large header\"> 15 <div class=\"sub header\">Receita</div>
                    </div>
                </div>
                <div class=\"right aligned padded column\">
                    <i class=\"huge dollar sign grey icon\" style=\"color:#EBEBEB;\"></i>
                </div>
            </div>
        </div>
        <div class=\"ui segment\">
            <div class=\"ui two columns padded grid\">
                <div class=\"column\">
                    <div class=\"ui large header\"> 15 <div class=\"sub header\">Total Mensal</div>
                    </div>
                </div>
                <div class=\"right aligned column\">
                    <i class=\"huge file alternate grey icon\" style=\"color:#EBEBEB;\"></i>
                </div>
            </div>
        </div>
        <div class=\"ui segment\">
            <div class=\"ui two columns grid\">
                <div class=\"column\">
                    <h3 class=\"ui header\"> Resumo Mensal </h3>
                </div>
                <div class=\"right aligned column\">
                    <div class=\"ui compact menu\">
                        <div class=\"ui simple dropdown item\"> Fevereiro <i class=\"fluid dropdown icon\"></i>
                            <div class=\"menu\">
                                <div class=\"item\">Janeiro</div>
                                <div class=\"item\">Fevereiro</div>
                                <div class=\"item\">Março</div>
                                <div class=\"item\">Abril</div>
                                <div class=\"item\">Maio</div>
                                <div class=\"item\">Junho</div>
                                <div class=\"item\">Julho</div>
                                <div class=\"item\">Agosto</div>
                                <div class=\"item\">Setembro</div>
                                <div class=\"item\">Outubro</div>
                                <div class=\"item\">Novembro</div>
                                <div class=\"item\">Dezembro</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class=\"ui placeholder\">
                <div class=\"image\">Gráfico</div>
            </div>
        </div>
        <div class=\"ui segment\">
            <h3 class=\"ui header\"> Pedidos Recentes </h3>
            <div class=\"ui placeholder\">
                <div class=\"image\">Gráfico</div>
            </div>
        </div>
        <div class=\"ui segment\">
            <div class=\"ui two columns grid\">
                <div class=\"column\">
                    <h3 class=\"ui header\"> Serviços </h3>
                </div>
                <div class=\"right aligned column\">
                    <h5 class=\"ui header\"> Ver todos os serviços &gt; </h5>
                </div>
            </div>
            <div class=\"ui sixteen wide columns grid\">
                <div class=\"one wide column\">
                    <div class=\"row\">
                        <i class=\"check circle green icon\"></i>
                    </div>
                </div>
                <div class=\"fourteen wide centered column\">
                    <div class=\"row\">
                        <h4 class=\"ui header\" style=\"color:#000000DE;\"> Congresso internacional de Medicina Veterinária Integrativa </h4>
                        <div class=\"ui three column very relaxed grid\">
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">Serviços Ativos</div>
                            </div>
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 em estoque</div>
                            </div>
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 vendidos</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class=\"one wide column\">
                    <div class=\"row\">
                        <i class=\"ellipsis horizontal grey icon\"></i>
                    </div>
                </div>
            </div>
            <div class=\"ui sixteen wide columns grid\">
                <div class=\"one wide column\">
                    <div class=\"row\">
                        <i class=\"check circle green icon\"></i>
                    </div>
                </div>
                <div class=\"fourteen wide centered column\">
                    <div class=\"row\">
                        <h4 class=\"ui header\" style=\"color:#000000DE;\"> Congresso internacional de Medicina Veterinária Integrativa </h4>
                        <div class=\"ui three column very relaxed grid\">
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">Serviços Ativos</div>
                            </div>
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 em estoque</div>
                            </div>
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 vendidos</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class=\"one wide column\">
                    <div class=\"row\">
                        <i class=\"ellipsis horizontal grey icon\"></i>
                    </div>
                </div>
            </div>
            <div class=\"ui sixteen wide columns grid\">
                <div class=\"one wide column\">
                    <div class=\"row\">
                        <i class=\"times circle red icon\"></i>
                    </div>
                </div>
                <div class=\"fourteen wide centered column \">
                    <div class=\"row\">
                        <h4 class=\"ui header\" style=\"color:#000000DE;\"> Congresso internacional de Medicina Veterinária Integrativa </h4>
                        <div class=\"ui three column very relaxed grid\">
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">Serviços Ativos</div>
                            </div>
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 em estoque</div>
                            </div>
                            <div class=\"column\">
                                <div class=\"ui grey tiny header\" style=\"color:#00000061;\">10.000 vendidos</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class=\"one wide column\">
                    <div class=\"row\">
                        <i class=\"ellipsis horizontal grey icon\"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"ui segment\">
            <div class=\"ui two columns grid\">
                <div class=\"column\">
                    <h3 class=\"ui header\"> Novidades </h3>
                </div>
                <div class=\"right aligned column\">
                    <h5 class=\"ui header\"> Ver todas as novidades &gt; </h5>
                </div>
            </div>
            <div class=\"ui two columns grid\">
                <div class=\"column\" style=\"color:#00000061;\"> Versão 2.4.0 </div>
                <div class=\"right aligned column\" style=\"color:#00000061;\"> 25/02/2019 </div>
            </div>
            <p style=\"color:#00000061;\">Após efetivar o cadastro você tem à disposição diversas funcionalidades para aumentar os recursos de seu posicionamento na internet e potencializar ainda mais seu negócio, atraindo e convertendo vendas e clientes e profissionalizando ainda mais sua atividade.</p>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"b67e53ae2f70f150c2341f00e7279778\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"b67e53ae2f70f150c2341f00e7279778\"}',
            ],
            [
                'page_id' => 75,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Gateways De Pagamentos',
                'id' => 'gateways-de-pagamentos',
                'language' => 'pt-br',
                'path' => 'gateways-de-pagamentos/',
                'type' => 'sistema',
                'module' => 'gateways-de-pagamentos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<h1 class=\"ui header\">#titulo#</h1>
<p>Gateways de pagamentos disponíveis:</p>
<a class=\"ui large blue button\" href=\"@[[pagina#url-raiz]]@gateways-de-pagamentos/paypal/\">
    <i class=\"paypal icon\"></i> PayPal </a>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ac94b2d83a071682666f81badc5e3215\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ac94b2d83a071682666f81badc5e3215\"}',
            ],
            [
                'page_id' => 76,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Plataforma Servidor Testes',
                'id' => 'plataforma-servidor-testes',
                'language' => 'pt-br',
                'path' => 'plataforma-servidor-testes/',
                'type' => 'sistema',
                'module' => 'host-configuracao',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div id=\"plataforma-testes\">
    <div class=\"ui large header\">Plataforma Testes</div>
    <button class=\"ui primary button testBtn\"> Testar </button>
    <div class=\"ui message\">
        <div class=\"header\"> Retornos da Plataforma </div>
        <div class=\"content\"> Aguardando ação... </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"76e70f473bb62a24296bfaafd3bcd8b5\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"76e70f473bb62a24296bfaafd3bcd8b5\"}',
            ],
            [
                'page_id' => 77,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Loja Configuracoes',
                'id' => 'loja-configuracoes',
                'language' => 'pt-br',
                'path' => 'loja-configuracoes/',
                'type' => 'sistema',
                'module' => 'loja-configuracoes',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<h2 class=\"ui dividing header\">
    <i class=\"store icon\"></i>
    <div class=\"content\"> Sua Loja <div class=\"sub header\">É aqui que você se apresenta para o mundo. Insira as informações principais a serem compartilhadas com seus clientes.</div>
    </div>
</h2>
<div class=\"field\">
    <label>@[[label-nome]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[placeholder-nome]]@\" value=\"#nome#\">
</div>
<div class=\"field\">
    <label>@[[label-continuar-comprando]]@</label>
    <input type=\"text\" name=\"continuarComprando\" placeholder=\"@[[placeholder-continuar-comprando]]@\" value=\"#continuarComprando#\">
</div>
<h2 class=\"ui dividing header\">
    <i class=\"city icon\"></i>
    <div class=\"content\"> Dados Gerais <div class=\"sub header\">Defina os dados gerais do seu negócio.</div>
    </div>
</h2>
<div class=\"two fields\">
    <div class=\"field\">
        <label>@[[label-cnpj]]@</label>
        <input type=\"text\" name=\"cnpj\" placeholder=\"@[[placeholder-cnpj]]@\" value=\"#cnpj#\" class=\"cnpj\">
    </div>
    <div class=\"field\">
        <label>@[[label-cpf]]@</label>
        <input type=\"text\" name=\"cpf\" placeholder=\"@[[placeholder-cpf]]@\" value=\"#cpf#\" class=\"cpf\">
    </div>
</div>
<div class=\"fields\">
    <div class=\"ten wide field\">
        <label>@[[label-endereco]]@</label>
        <input type=\"text\" name=\"endereco\" placeholder=\"@[[placeholder-endereco]]@\" value=\"#endereco#\">
    </div>
    <div class=\"two wide field\">
        <label>@[[label-numero]]@</label>
        <input type=\"text\" name=\"numero\" placeholder=\"@[[placeholder-numero]]@\" value=\"#numero#\" class=\"numero\">
    </div>
    <div class=\"four wide field\">
        <label>@[[label-complemento]]@</label>
        <input type=\"text\" name=\"complemento\" placeholder=\"@[[placeholder-complemento]]@\" value=\"#complemento#\">
    </div>
</div>
<div class=\"fields\">
    <div class=\"five wide field\">
        <label>@[[label-bairro]]@</label>
        <input type=\"text\" name=\"bairro\" placeholder=\"@[[placeholder-bairro]]@\" value=\"#bairro#\">
    </div>
    <div class=\"five wide field\">
        <label>@[[label-cidade]]@</label>
        <input type=\"text\" name=\"cidade\" placeholder=\"@[[placeholder-cidade]]@\" value=\"#cidade#\">
    </div>
    <div class=\"two wide field\">
        <label>@[[label-uf]]@</label>
        <input type=\"text\" name=\"uf\" placeholder=\"@[[placeholder-uf]]@\" value=\"#uf#\" class=\"uf\">
    </div>
    <div class=\"four wide field\">
        <label>@[[label-pais]]@</label>
        <input type=\"text\" name=\"pais\" placeholder=\"@[[placeholder-pais]]@\" value=\"#pais#\">
    </div>
</div>
<div class=\"two fields\">
    <div class=\"field\">
        <label>@[[label-cep]]@</label>
        <input type=\"text\" name=\"cep\" placeholder=\"@[[placeholder-cep]]@\" value=\"#cep#\" class=\"cep\">
    </div>
    <div class=\"field\">
        <label>@[[label-telefone]]@</label>
        <input type=\"text\" name=\"telefone\" placeholder=\"@[[placeholder-telefone]]@\" value=\"#telefone#\" class=\"tel\">
    </div>
</div>
<h2 class=\"ui dividing header\">
    <i class=\"image outline icon\"></i>
    <div class=\"content\"> Layout <div class=\"sub header\">Defina as características visuais do seu negócio.</div>
    </div>
</h2>
<div class=\"field\">
    <label>@[[label-logomarca]]@</label>
    <span>#imagepick-logomarca#</span>
</div>
<h2 class=\"ui dividing header\">
    <i class=\"cogs icon\"></i>
    <div class=\"content\"> Configurações Gerais <div class=\"sub header\">Padrões dos alertas, campos e informativos do funcionamento da loja.</div>
    </div>
</h2>
<!-- loja < -->
<div class=\"field\">
    <label>@[[label]]@</label>
    <input type=\"text\" name=\"@[[name]]@\" placeholder=\"@[[placeholder]]@\" value=\"@[[value]]@\">
</div><!-- loja > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"e9a39d099d0c52dbc914bcb1b792dca6\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"e9a39d099d0c52dbc914bcb1b792dca6\"}',
            ],
            [
                'page_id' => 78,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Menus',
                'id' => 'menus',
                'language' => 'pt-br',
                'path' => 'menus/',
                'type' => 'sistema',
                'module' => 'menus',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"escondido\">
    <div class=\"ui top attached tabular menu\" id=\"menus-cont\">
        <a class=\"active item\" data-tab=\"menuPaginaInicial\" data-position=\"top left\" data-inverted=\"\">@[[label-menu-pagina-inicial]]@</a>
    </div>
    <div class=\"ui bottom attached tab segment active menuCont\" data-tab=\"menuPaginaInicial\">
        <div class=\"field escondido\">
            <label>@[[label-tipo]]@</label>
            <div class=\"ui large buttons\">
                <div class=\"ui button controleTipo blue active\" data-id=\"padrao\" data-tooltip=\"@[[tooltip-padrao]]@\" data-inverted=\"\">@[[label-padrao]]@</div>
                <div class=\"or\" data-text=\"ou\"></div>
                <div class=\"ui button controleTipo\" data-id=\"personalizado\" data-tooltip=\"@[[tooltip-personalizado]]@\" data-inverted=\"\">@[[label-personalizado]]@</div>
            </div>
            <input type=\"hidden\" name=\"tipo\" value=\"padrao\">
        </div>
        <div class=\"menu-itens-cont\"></div>
    </div>
</div>
<div class=\"ui top attached tabular menu\" id=\"menus-cont\">
    <a class=\"active item\" data-tab=\"menuMinhaConta\" data-position=\"top left\" data-inverted=\"\">@[[label-menu-minha-conta]]@</a>
</div>
<div class=\"ui bottom attached tab segment active menuCont\" data-tab=\"menuMinhaConta\">
    <div class=\"menu-itens-cont\"></div>
</div>
<div class=\"menu-item-template\">
    <div class=\"menu-item\" draggable=\"false\">
        <div class=\"item-grab escondido\">
            <i class=\"grip lines large icon\"></i>
        </div>
        <div class=\"item-opcoes\">
            <div class=\"ui accordion\">
                <div class=\"title\">
                    <i class=\"dropdown icon\"></i>
                    <span class=\"itemNome\"></span>
                </div>
                <div class=\"content\">
                    <div class=\"field\">
                        <div class=\"ui right aligned toggle checkbox itemInativo\">
                            <input type=\"checkbox\">
                            <label>Inativo: </label>
                        </div>
                    </div>
                    <p>URL: <span class=\"itemUrl\"></span>.</p>
                    <p>Tipo: <span class=\"itemTipo\"></span>.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<input type=\"hidden\" name=\"dadosServidor\" value=\"#dadosServidor#\" id=\"dadosServidor\">',
                'css' => '.escondido{
    display:none;
}
.menu-itens-cont{
    position:relative;
}
.menu-item-template{
    display:none;
}
.menu-item{
    display: flex;
    justify-content: space-between;
    align-items: stretch;
    align-content: flex-start;
    border: 1px solid #d4d4d5;
    padding: 4px;
    margin:10px 0px;
    border-radius:5px;
}
.menu-item:last-of-type{
    margin-bottom:0px;
}
.item-grab{
    padding-top:6px;
    width: 40px;
    cursor:grab;
    text-align:center;
}
.item-opcoes{
    width: calc(100% - 40px);
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"00526f0cbab35125f79c391056a0f95f\",\"css\":\"06a47937ff45a2e842315a49a679d3fc\",\"combined\":\"729c21bcf20ac984bd1b0d52161ba7f3\"}',
            ],
            [
                'page_id' => 79,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Modulos',
                'id' => 'modulos',
                'language' => 'pt-br',
                'path' => 'modulos/',
                'type' => 'sistema',
                'module' => 'modulos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 80,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Modulos Adicionar',
                'id' => 'modulos-adicionar',
                'language' => 'pt-br',
                'path' => 'modulos-adicionar/',
                'type' => 'sistema',
                'module' => 'modulos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"field\">
    <label>#form-title-label#</label>
    <input type=\"text\" name=\"titulo\" placeholder=\"#form-title-placeholder#\">
</div>
<div class=\"field\">
    <label>@[[form-host-label]]@</label>
    <div class=\"ui toggle checkbox\">
        <input type=\"checkbox\" name=\"host\" data-checked=\"\">
        <label>&nbsp;</label>
    </div>
</div>
<div class=\"field\">
    <label>#form-grup-label#</label>
    <span>#select-grup#</span>
</div>
<div class=\"field\">
    <label>#form-plugin-label#</label>
    <span>#select-plugin#</span>
</div>
<div class=\"field\">
    <label>#form-icon-label#</label>
    <input type=\"text\" name=\"icone\" placeholder=\"#form-icon-placeholder#\">
</div>
<div class=\"field\">
    <label>#form-icon-2-label#</label>
    <input type=\"text\" name=\"icone2\" placeholder=\"#form-icon-2-placeholder#\">
</div>
<div class=\"field\">
    <label>#form-menu-label#</label>
    <span>#select-menu#</span>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"9439eb9e98debf8dce934216f6735dae\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"9439eb9e98debf8dce934216f6735dae\"}',
            ],
            [
                'page_id' => 81,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Modulos Administracao De Sistema Copiar Variaveis',
                'id' => 'modulos-administracao-de-sistema-copiar-variaveis',
                'language' => 'pt-br',
                'path' => 'modulos-administracao-de-sistema-copiar-variaveis/',
                'type' => 'sistema',
                'module' => 'modulos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 82,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Modulos Administracao De Sistema Sincronizar Bancos',
                'id' => 'modulos-administracao-de-sistema-sincronizar-bancos',
                'language' => 'pt-br',
                'path' => 'modulos-administracao-de-sistema-sincronizar-bancos/',
                'type' => 'sistema',
                'module' => 'modulos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 83,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Modulos Editar',
                'id' => 'modulos-editar',
                'language' => 'pt-br',
                'path' => 'modulos-editar/',
                'type' => 'sistema',
                'module' => 'modulos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\" value=\"#nome#\">
</div>
<div class=\"field\">
    <label>#form-title-label#</label>
    <input type=\"text\" name=\"titulo\" placeholder=\"#form-title-placeholder#\" value=\"#titulo#\">
</div>
<div class=\"field\">
    <label>@[[form-host-label]]@</label>
    <div class=\"ui toggle checkbox\">
        <input type=\"checkbox\" name=\"host\" data-checked=\"#checked#\">
        <label>&nbsp;</label>
    </div>
</div>
<div class=\"field\">
    <label>#form-grup-label#</label>
    <span>#select-grup#</span>
</div>
<div class=\"field\">
    <label>#form-plugin-label#</label>
    <span>#select-plugin#</span>
</div>
<div class=\"field\">
    <label>#form-icon-label#</label>
    <input type=\"text\" name=\"icone\" placeholder=\"#form-icon-placeholder#\" value=\"#icone#\">
</div>
<div class=\"field\">
    <label>#form-icon-2-label#</label>
    <input type=\"text\" name=\"icone2\" placeholder=\"#form-icon-2-placeholder#\" value=\"#icone2#\">
</div>
<div class=\"field\">
    <label>#form-menu-label#</label>
    <span>#select-menu#</span>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d78b01e64a55afbf20cb548448f2adbc\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d78b01e64a55afbf20cb548448f2adbc\"}',
            ],
            [
                'page_id' => 84,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Modulos Variaveis',
                'id' => 'modulos-variaveis',
                'language' => 'pt-br',
                'path' => 'modulos-variaveis/',
                'type' => 'sistema',
                'module' => 'modulos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"ui large header\">M&oacute;dulo: <span class=\"ui blue text\">#nome#</span></div>
<div class=\"field\">
    <label>#form-language-label#</label>
    <span>#select-language#</span>
</div>
<!-- configuracao-administracao -->',
                'css' => '.campoModelo{
	display:none;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"c3322a1549a8c1b36e4e9d648cdab369\",\"css\":\"44d19aa88ee9abed08f25ab4b7a93ae2\",\"combined\":\"3a4ec5814b29b1a8f877575d9cf29e20\"}',
            ],
            [
                'page_id' => 85,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Modulos Grupos',
                'id' => 'modulos-grupos',
                'language' => 'pt-br',
                'path' => 'modulos-grupos/',
                'type' => 'sistema',
                'module' => 'modulos-grupos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 86,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Modulos Grupos Adicionar',
                'id' => 'modulos-grupos-adicionar',
                'language' => 'pt-br',
                'path' => 'modulos-grupos-adicionar/',
                'type' => 'sistema',
                'module' => 'modulos-grupos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\" />
</div>
<div class=\"field\">
    <label>@[[form-host-label]]@</label>
    <div class=\"ui toggle checkbox\">
        <input type=\"checkbox\" name=\"host\" data-checked=\"\">
        <label>&nbsp;</label>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"4c5b33e993a4f77438a14e51677fce05\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"4c5b33e993a4f77438a14e51677fce05\"}',
            ],
            [
                'page_id' => 87,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Modulos Grupos Editar',
                'id' => 'modulos-grupos-editar',
                'language' => 'pt-br',
                'path' => 'modulos-grupos-editar/',
                'type' => 'sistema',
                'module' => 'modulos-grupos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\" value=\"#nome#\" />
</div>
<div class=\"field\">
    <label>@[[form-host-label]]@</label>
    <div class=\"ui toggle checkbox\">
        <input type=\"checkbox\" name=\"host\" data-checked=\"#checked#\">
        <label>&nbsp;</label>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"7b9c7f1b9498d9099d15b7d90a88a05a\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"7b9c7f1b9498d9099d15b7d90a88a05a\"}',
            ],
            [
                'page_id' => 88,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Modulos Operacoes',
                'id' => 'modulos-operacoes',
                'language' => 'pt-br',
                'path' => 'modulos-operacoes/',
                'type' => 'sistema',
                'module' => 'modulos-operacoes',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 89,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Modulos Operacoes Adicionar',
                'id' => 'modulos-operacoes-adicionar',
                'language' => 'pt-br',
                'path' => 'modulos-operacoes-adicionar/',
                'type' => 'sistema',
                'module' => 'modulos-operacoes',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"field\">
    <label>#form-module-label#</label>
    <span>#select-module#</span>
</div>
<div class=\"field\">
    <label>#form-operation-label#</label>
    <input type=\"text\" name=\"operacao\" placeholder=\"#form-operation-placeholder#\">
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"13daed3811c3397964c10696d51948c4\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"13daed3811c3397964c10696d51948c4\"}',
            ],
            [
                'page_id' => 90,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Modulos Operacoes Editar',
                'id' => 'modulos-operacoes-editar',
                'language' => 'pt-br',
                'path' => 'modulos-operacoes-editar/',
                'type' => 'sistema',
                'module' => 'modulos-operacoes',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\" value=\"#nome#\">
</div>
<div class=\"field\">
    <label>#form-module-label#</label>
    <span>#select-module#</span>
</div>
<div class=\"field\">
    <label>#form-operation-label#</label>
    <input type=\"text\" name=\"operacao\" placeholder=\"#form-operation-placeholder#\" value=\"#operacao#\">
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"e5033776dc862a9139c9948b68df67aa\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"e5033776dc862a9139c9948b68df67aa\"}',
            ],
            [
                'page_id' => 91,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Paginas',
                'id' => 'paginas',
                'language' => 'pt-br',
                'path' => 'paginas/',
                'type' => 'sistema',
                'module' => 'paginas',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 92,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Paginas Adicionar',
                'id' => 'paginas-adicionar',
                'language' => 'pt-br',
                'path' => 'paginas-adicionar/',
                'type' => 'sistema',
                'module' => 'paginas',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"pagina-nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"field\">
    <label>#form-layout-label#</label>
    <span>#select-layout#</span>
</div>
<div class=\"ui top attached tabular menu\">
    <a class=\"active item\" data-tab=\"codigo-html\" data-tooltip=\"#form-html-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-html-label#</a>
    <a class=\"item\" data-tab=\"css\" data-tooltip=\"#form-css-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-css-label#</a>
</div>
<div class=\"ui bottom attached tab segment\" data-tab=\"codigo-html\">
    <textarea class=\"codemirror-html\" name=\"html\"></textarea>
</div>
<div class=\"ui bottom attached tab segment\" data-tab=\"css\">
    <textarea class=\"codemirror-css\" name=\"css\"></textarea>
</div>
<div class=\"ui dividing header\">#form-config-divider#</div>
<div class=\"field\">
    <label>#form-path-label#</label>
    <input type=\"text\" name=\"paginaCaminho\" placeholder=\"#form-path-placeholder#\">
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"60d506a6b4ea89e191ad934a1f9f956f\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"60d506a6b4ea89e191ad934a1f9f956f\"}',
            ],
            [
                'page_id' => 93,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Paginas Editar',
                'id' => 'paginas-editar',
                'language' => 'pt-br',
                'path' => 'paginas-editar/',
                'type' => 'sistema',
                'module' => 'paginas',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"ui blue message\"> @[[form-url-label]]@: <b>
        <a href=\"#url#\">#url#</a>
    </b>. </div>
<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"pagina-nome\" placeholder=\"#form-name-placeholder#\" value=\"#pagina-nome#\">
</div>
<div class=\"field\">
    <label>#form-layout-label#</label>
    <span>#select-layout#</span>
</div>
<div class=\"ui top attached tabular menu\">
    <a class=\"active item\" data-tab=\"codigo-html\" data-tooltip=\"#form-html-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-html-label#</a>
    <a class=\"item\" data-tab=\"css\" data-tooltip=\"#form-css-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-css-label#</a>
</div>
<div class=\"ui bottom attached tab segment\" data-tab=\"codigo-html\">#pagina-html-backup# <textarea class=\"codemirror-html\" name=\"html\">#pagina-html#</textarea>
</div>
<div class=\"ui bottom attached tab segment\" data-tab=\"css\">#pagina-css-backup# <textarea class=\"codemirror-css\" name=\"css\">#pagina-css#</textarea>
</div>
<div class=\"ui dividing header\">#form-config-divider#</div>
<div class=\"field\">
    <label>#form-path-label#</label>
    <input type=\"text\" name=\"paginaCaminho\" placeholder=\"#form-path-placeholder#\" value=\"#caminho#\">
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"0a27e97a25e2bc6b5214489a9025b07f\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"0a27e97a25e2bc6b5214489a9025b07f\"}',
            ],
            [
                'page_id' => 94,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pedidos',
                'id' => 'pedidos',
                'language' => 'pt-br',
                'path' => 'pedidos/',
                'type' => 'sistema',
                'module' => 'pedidos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 95,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pedidos Visualizar',
                'id' => 'pedidos-visualizar',
                'language' => 'pt-br',
                'path' => 'pedidos-visualizar/',
                'type' => 'sistema',
                'module' => 'pedidos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"ui dividing header\">Resumo do Pedido</div>
<table class=\"ui celled table\">
    <thead>
        <tr>
            <th>Serviço</th>
            <th>Quantidade</th>
            <th>Preço</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <!-- cel-servico < -->
        <tr>
            <td>@[[nome]]@</td>
            <td>@[[quantidade]]@</td>
            <td>@[[preco]]@</td>
            <td>@[[subtotal]]@</td>
        </tr><!-- cel-servico > -->
    </tbody>
</table>
<div class=\"ui dividing header\">Dados do Cliente</div>
<table class=\"ui definition table\">
    <tbody>
        <tr>
            <td>Nome</td>
            <td>@[[cliente-nome]]@</td>
        </tr>
        <tr>
        </tr>
        <tr>
            <td>Email</td>
            <td>@[[cliente-email]]@</td>
        </tr>
        <tr>
            <td>Telefone</td>
            <td>@[[cliente-telefone]]@</td>
        </tr>
        <tr>
            <td>CPF</td>
            <td>@[[cliente-cpf]]@</td>
        </tr>
        <tr>
            <td>CNPJ</td>
            <td>@[[cliente-cnpj]]@</td>
        </tr>
    </tbody>
</table>
<!-- comp-vouchers < -->
<div class=\"ui dividing header\">Vouchers</div>
<div class=\"ui stackable two column celled grid\">
    <!-- cel-voucher < -->
    <div class=\"column voucherCel\" data-id=\"@[[voucher-id]]@\">
        <div class=\"ui two column grid\">
            <div class=\"row\">
                <div class=\"six wide column\">
                    <img class=\"ui fluid image\" src=\"@[[servico-imagem]]@\">
                </div>
                <div class=\"ten wide column servicoCol\">
                    <div class=\"ui medium header\">@[[servico-nome]]@</div>
                    @[[identificacao-status]]@
                    <div class=\"ui small header\">
                        <i class=\"address card icon\"></i>
                        <div class=\"content\"> Identidade </div>
                    </div>
                    <table class=\"ui very basic table\">
                        <tbody>
                            <tr>
                                <td class=\"collapsing\">
                                    <i class=\"right triangle large fitted icon\"></i>
                                    <span class=\"ui small header\">Nome</span>
                                </td>
                                <td class=\"campoNome\">@[[identificacao-nome]]@</td>
                            </tr>
                            <tr>
                                <td class=\"collapsing\">
                                    <i class=\"right triangle large fitted icon\"></i>
                                    <span class=\"ui small header\">Documento</span>
                                </td>
                                <td class=\"campoDocumento\">@[[identificacao-documento]]@</td>
                            </tr>
                            <tr>
                                <td class=\"collapsing\">
                                    <i class=\"right triangle large fitted icon\"></i>
                                    <span class=\"ui small header\">Telefone</span>
                                </td>
                                <td class=\"campoTelefone\">@[[identificacao-telefone]]@</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- cel-voucher > -->
</div>
<!-- comp-vouchers > -->
<!-- comp-pagamentos < -->
<div class=\"ui dividing header\">Requisições de Pagamento</div>
<table class=\"ui celled structured table\">
    <thead>
        <tr>
            <th>Provedora</th>
            <th>ID Requisição</th>
            <th>ID Final</th>
            <th>Parcelas</th>
            <th>Data Criação</th>
            <th>Data Modificação</th>
            <th>Status</th>
            <th class=\"center aligned\">Pagador</th>
        </tr>
    </thead>
    <tbody>
        <!-- cel-pagamentos < -->
        <tr>
            <td>
                <span class=\"ui blue label\">PayPal</span>
            </td>
            <td>@[[pay_id]]@</td>
            <td>@[[final_id]]@</td>
            <td>@[[parcelas]]@</td>
            <td>@[[data_criacao]]@</td>
            <td>@[[data_modificacao]]@</td>
            <td>@[[status]]@</td>
            <td class=\"center aligned\">
                <div class=\"ui icon buttons\">
                    <a class=\"ui button basic green dadosPagadorBtn\" data-content=\"Clique para Visualizar Dados do Pagador\" data-id=\"@[[pagamento_id]]@\">
                        <i class=\"file alternate outline icon\"></i>
                    </a>
                </div>
            </td>
        </tr>
        <!-- cel-pagamentos > -->
    </tbody>
</table>
<!-- comp-pagamentos > -->',
                'css' => '.voucherCel{
    background-color:#FFF;
}
.servicoCol > .label {
    margin-bottom: 0.75em;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"c7829c81604973c65e54097fdce62dc9\",\"css\":\"719484d11b22a41739437ec1e28f423a\",\"combined\":\"1d3de323cf9962d0e95ae443408e229c\"}',
            ],
            [
                'page_id' => 96,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Perfil Usuario',
                'id' => 'perfil-usuario',
                'language' => 'pt-br',
                'path' => 'perfil-usuario/',
                'type' => 'sistema',
                'module' => 'perfil-usuario',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<table class=\"ui celled table\">
    <thead>
        <tr>
            <th>@[[form-name-account-label]]@</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>#nome_conta#</td>
        </tr>
    </tbody>
</table>
<table class=\"ui celled table\">
    <thead>
        <tr>
            <th>@[[first-name]]@</th>
            <th>@[[middle-name]]@</th>
            <th>@[[last-name]]@</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class=\"first-name\">#primeiro_nome#</td>
            <td class=\"middle-name\">#nome_do_meio#</td>
            <td class=\"last-name\">#ultimo_nome#</td>
        </tr>
    </tbody>
</table>
<table class=\"ui celled table\">
    <thead>
        <tr>
            <th>@[[form-user-profile-label]]@</th>
            <th>@[[form-email-label]]@</th>
            <th>@[[form-user-label]]@</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>#user-profile#</td>
            <td>#email#</td>
            <td>#usuario#</td>
        </tr>
    </tbody>
</table>
<!-- botoes < -->
<div class=\"ui dividing header\" id=\"formulario-alteracoes\">@[[form-changes-label]]@</div>
<div class=\"field botoesMargem\">
    <a class=\"ui button\" href=\"@[[pagina#url-raiz]]@perfil-usuario/?mudar-nome=sim#formulario-alteracoes\">
        <i class=\"user tie icon\"></i> @[[button-change-name]]@ </a>
    <a class=\"ui button\" href=\"@[[pagina#url-raiz]]@perfil-usuario/?mudar-email=sim#formulario-alteracoes\">
        <i class=\"at icon\"></i> @[[button-change-email]]@ </a>
    <a class=\"ui button\" href=\"@[[pagina#url-raiz]]@perfil-usuario/?mudar-usuario=sim#formulario-alteracoes\">
        <i class=\"id badge icon\"></i> @[[button-change-user]]@ </a>
    <a class=\"ui button\" href=\"@[[pagina#url-raiz]]@perfil-usuario/?mudar-senha=sim#formulario-alteracoes\">
        <i class=\"user lock icon\"></i> @[[button-change-password]]@ </a>
</div><!-- botoes > -->
<!-- nome-campos < -->
<div class=\"field\">
    <label>@[[form-name-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-placeholder]]@\" value=\"#nome#\">
</div>
<input type=\"hidden\" name=\"mudar-nome-banco\" value=\"1\"><!-- nome-campos > -->
<!-- email-campos < -->
<div class=\"two fields\">
    <div class=\"field\">
        <label>@[[form-email-label]]@</label>
        <input type=\"email\" name=\"email\" placeholder=\"@[[form-email-placeholder]]@\" value=\"#email#\">
    </div>
    <div class=\"field\">
        <label>@[[form-email-2-label]]@</label>
        <input type=\"email\" name=\"email-2\" placeholder=\"@[[form-email-2-placeholder]]@\" value=\"#email#\">
    </div>
</div>
<input type=\"hidden\" name=\"mudar-email-banco\" value=\"1\"><!-- email-campos > -->
<!-- usuario-campos < -->
<div class=\"field\">
    <label>@[[form-user-label]]@</label>
    <input type=\"text\" name=\"usuario\" placeholder=\"@[[form-user-placeholder]]@\" value=\"#usuario#\">
</div>
<input type=\"hidden\" name=\"mudar-usuario-banco\" value=\"1\"><!-- usuario-campos > -->
<!-- senha-campos < -->
<div class=\"two fields\">
    <div class=\"field\">
        <label>@[[form-password-label]]@</label>
        <input type=\"password\" name=\"senha\" placeholder=\"@[[form-password-placeholder]]@\" autocomplete=\"new-password\">
    </div>
    <div class=\"field\">
        <label>@[[form-password-2-label]]@</label>
        <input type=\"password\" name=\"senha-2\" placeholder=\"@[[form-password-2-placeholder]]@\" autocomplete=\"new-password\">
    </div>
</div>
<input type=\"hidden\" name=\"mudar-senha-banco\" value=\"1\"><!-- senha-campos > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"954e780f5373a2dc13604902ebd5f6a5\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"954e780f5373a2dc13604902ebd5f6a5\"}',
            ],
            [
                'page_id' => 97,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Postagens',
                'id' => 'postagens',
                'language' => 'pt-br',
                'path' => 'postagens/',
                'type' => 'sistema',
                'module' => 'postagens',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 98,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Postagens Adicionar',
                'id' => 'postagens-adicionar',
                'language' => 'pt-br',
                'path' => 'postagens-adicionar/',
                'type' => 'sistema',
                'module' => 'postagens',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"field\">
    <label>#form-thumbnail-label#</label>
    <span>#imagepick-thumbnail#</span>
</div>
<div class=\"field\">
    <label>#form-description-label#</label>
    <textarea class=\"tinymce\" name=\"descricao\"></textarea>
</div>
<div class=\"ui dividing header\">@[[section-page]]@</div>
<div class=\"field\">
    <label>#form-path-label#</label>
    <input type=\"text\" name=\"paginaCaminho\" placeholder=\"#form-path-placeholder#\">
</div>
<div class=\"field\">
    <label>#form-template-label#</label>
    <span>#templates-template#</span>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"b1ec418459426d3fad227f6a3867a7f0\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"b1ec418459426d3fad227f6a3867a7f0\"}',
            ],
            [
                'page_id' => 99,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Postagens Editar',
                'id' => 'postagens-editar',
                'language' => 'pt-br',
                'path' => 'postagens-editar/',
                'type' => 'sistema',
                'module' => 'postagens',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"ui blue message\">
    @[[form-url-label]]@: <b><a href=\"#url#\">#url#</a></b>.
</div>

<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\" value=\"#nome#\">
</div>
<div class=\"field\">
    <label>#form-thumbnail-label#</label>
    <span>#imagepick-thumbnail#</span>
</div>
<div class=\"field\">
    <label>#form-description-label#</label>
    <textarea class=\"tinymce\" name=\"descricao\">#descricao#</textarea>
</div>
<div class=\"ui dividing header\">@[[section-page]]@</div>
<div class=\"field\">
    <label>#form-path-label#</label>
    <input type=\"text\" name=\"paginaCaminho\" placeholder=\"#form-path-placeholder#\" value=\"#caminho#\">
</div>
<div class=\"field\">
    <label>#form-template-label#</label>
    <span>#templates-template#</span>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"606ec6b441ccce0bc7b1cae15f6e08c2\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"606ec6b441ccce0bc7b1cae15f6e08c2\"}',
            ],
            [
                'page_id' => 100,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Servicos',
                'id' => 'servicos',
                'language' => 'pt-br',
                'path' => 'servicos/',
                'type' => 'sistema',
                'module' => 'servicos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 101,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Servicos Adicionar',
                'id' => 'servicos-adicionar',
                'language' => 'pt-br',
                'path' => 'servicos-adicionar/',
                'type' => 'sistema',
                'module' => 'servicos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"field\">
    <label>#form-thumbnail-label#</label>
    <span>#imagepick-thumbnail#</span>
</div>
<div class=\"field\">
    <label>#form-description-label#</label>
    <textarea class=\"tinymce\" name=\"descricao\"></textarea>
</div>
<div class=\"field\">
    <label>@[[form-type-label]]@</label>
    <div class=\"ui large buttons\">
        <div class=\"ui button active blue controleTipo\" data-id=\"simples\" data-tooltip=\"@[[type-simple-tooltip]]@\" data-inverted=\"\">@[[form-type-simple]]@</div>
        <div class=\"or\" data-text=\"ou\"></div>
        <div class=\"ui button controleTipo\" data-id=\"lotes-variacoes\" data-tooltip=\"@[[type-batch-tooltip]]@\" data-inverted=\"\">@[[form-type-batch]]@</div>
    </div>
    <input type=\"hidden\" name=\"tipo\" value=\"simples\">
</div>
<div class=\"simplesCont\">
    <div class=\"two fields\">
        <div class=\"field\">
            <label>@[[form-price-label]]@</label>
            <div class=\"ui labeled input\">
                <label for=\"preco\" class=\"ui label\">R$</label>
                <input type=\"text\" name=\"preco\" placeholder=\"@[[form-price-placeholder]]@\" class=\"preco\">
            </div>
        </div>
        <div class=\"field\">
            <label>@[[form-quantity-label]]@</label>
            <input type=\"text\" name=\"quantidade\" placeholder=\"@[[form-quantity-placeholder]]@\" class=\"quantidade\">
        </div>
    </div>
    <div class=\"field\">
        <div class=\"ui checkbox cheGratuito\">
            <input type=\"checkbox\" tabindex=\"0\" name=\"gratuito\" class=\"hidden gratuito\" value=\"1\">
            <label>@[[form-free-label]]@</label>
        </div>
    </div>
</div>
<div class=\"lotesVariacoesCont escondido\">
    <div class=\"ui dividing header\">@[[batch-title]]@</div>
    <div class=\"ui blue floating labeled icon dropdown button lotesMenu\" data-tooltip=\"@[[batch-menu-tooltip]]@\" data-inverted=\"\">
        <i class=\"dropdown icon\"></i>
        <span class=\"text\">#lotes-menu-selected-nome#</span>
        <div class=\"menu\">
            <!-- lote-menu-cel < --><div class=\"item\" data-value=\"#lotes-menu-value#\">#lotes-menu-nome#</div><!-- lote-menu-cel > -->
        </div>
    </div>
    <div class=\"ui button positive loteAdicionar\" data-tooltip=\"@[[batch-add-tooltip]]@\" data-inverted=\"\">
        <i class=\"plus circle icon\"></i>
        @[[btn-batch-add]]@
    </div>
    <!-- lote-cel < --><div class=\"ui segment loteCont\" data-value=\"#lote-value#\" data-num=\"#lote-num#\">
        <!-- btn-del < --><div class=\"ui button red right floated button loteExcluir\" data-tooltip=\"@[[batch-del-tooltip]]@\" data-inverted=\"\" data-position=\"top right\">
            <i class=\"times circle icon\"></i>
            @[[btn-batch-del]]@
        </div><!-- btn-del > -->
        <!-- btn-duplicar < --><div class=\"ui button blue right floated button loteDuplicar\" data-tooltip=\"@[[batch-duplicate-tooltip]]@\" data-inverted=\"\" data-position=\"top right\">
            <i class=\"copy outline icon\"></i>
            @[[btn-batch-duplicate]]@
        </div><!-- btn-duplicar > -->
        <div class=\"field\">
            <label>@[[form-batch-label]]@</label>
            <input type=\"text\" placeholder=\"@[[form-batch-name]]@\" value=\"#lote-nome#\" maxlength=\"100\" class=\"loteNome\">
        </div>
        <div class=\"field\">
            <label>@[[form-visibility-label]]@</label>
            <span>#select-visibility#</span>
        </div>
        <div class=\"data-inicio data-visibilidade escondido\">
            <div class=\"field\">
                <label>@[[form-startdate-label]]@</label>
                <div class=\"ui calendar inverted startdate\">
                    <div class=\"ui input left icon\">
                        <i class=\"calendar icon\"></i>
                        <input type=\"text\" placeholder=\"@[[date-start-label]]@\" class=\"inputDataInicio\">
                    </div>
                </div>
            </div>
        </div>
        <div class=\"data-fim data-visibilidade escondido\">
            <div class=\"field\">
                <label>@[[form-enddate-label]]@</label>
                <div class=\"ui calendar inverted enddate\">
                    <div class=\"ui input left icon\">
                        <i class=\"calendar icon\"></i>
                        <input type=\"text\" placeholder=\"@[[date-end-label]]@\" class=\"inputDataFim\">
                    </div>
                </div>
            </div>
        </div>
        <div class=\"data-periodo data-visibilidade escondido\">
            <div class=\"two fields\">
                <div class=\"field\">
                    <label>@[[form-rangestart-label]]@</label>
                    <div class=\"ui calendar inverted rangestart\">
                        <div class=\"ui input left icon\">
                            <i class=\"calendar icon\"></i>
                            <input type=\"text\" placeholder=\"@[[date-start-label]]@\" class=\"inputDataInicio\">
                        </div>
                    </div>
                </div>
                <div class=\"field\">
                    <label>@[[form-rangeend-label]]@</label>
                    <div class=\"ui calendar inverted rangeend\">
                        <div class=\"ui input left icon\">
                            <i class=\"calendar icon\"></i>
                            <input type=\"text\" placeholder=\"@[[date-end-label]]@\" class=\"inputDataFim\">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"ui dividing header\">@[[variations-title]]@</div>
        <div class=\"ui button positive varAdicionar\" data-tooltip=\"@[[variations-add-tooltip]]@\" data-inverted=\"\">
            <i class=\"plus circle icon\"></i>
            @[[btn-variations-add]]@
        </div>
        <div class=\"ui divided items varItems\">
            <!-- variacao-cel < --><div class=\"item varItem\" data-value=\"#variacao-value#\" data-num=\"#variacao-num#\">
                <div class=\"content\">
                    <!-- var-btn-del < --><div class=\"ui button red right floated button varExcluir\" data-tooltip=\"@[[variations-del-tooltip]]@\" data-inverted=\"\" data-position=\"top right\">
                        <i class=\"times circle icon\"></i>
                        @[[btn-variation-del]]@
                    </div><!-- var-btn-del > -->
                    <div class=\"field\">
                        <label>@[[form-variations-name-label]]@</label>
                        <input type=\"text\" placeholder=\"@[[form-variations-name-placeholder]]@\" value=\"#variacao-nome#\" maxlength=\"100\" class=\"variacaoNome\" data-validate=\"variacaoNome\">
                    </div>
                    <div class=\"two fields\">
                        <div class=\"field\">
                            <label>@[[form-variations-price-label]]@</label>
                            <div class=\"ui labeled input\">
                                <label for=\"preco\" class=\"ui label\">R$</label>
                                <input type=\"text\" placeholder=\"@[[form-price-placeholder]]@\" value=\"#variacao-preco#\" class=\"preco variacaoPreco\" data-validate=\"variacaoPreco\">
                            </div>
                        </div>
                        <div class=\"field\">
                            <label>@[[form-variations-quantity-label]]@</label>
                            <input type=\"text\" placeholder=\"@[[form-quantity-placeholder]]@\" value=\"#variacao-quantidade#\" class=\"quantidade variacaoQuantidade\" data-validate=\"variacaoQuantidade\">
                        </div>
                    </div>
                    <div class=\"field\">
                        <div class=\"ui checkbox cheVariacaoGratuito\">
                            <input type=\"checkbox\" tabindex=\"0\" class=\"hidden variacaoGratuito\" value=\"1\">
                            <label>@[[variation-free-label]]@</label>
                        </div>
                    </div>
                </div>
            </div><!-- variacao-cel > -->
        </div>
    </div><!-- lote-cel > -->
    <div class=\"lotesContsFim\"></div>
    <div class=\"lotesModelos\">
        #lotes-modelos#
    </div>
</div>
<div class=\"ui dividing header\">@[[section-page]]@</div>
<div class=\"field\">
    <label>#form-path-label#</label>
    <input type=\"text\" name=\"paginaCaminho\" placeholder=\"#form-path-placeholder#\">
</div>
<div class=\"field\">
    <label>#form-template-label#</label>
    <span>#templates-template#</span>
</div>
<input type=\"hidden\" name=\"dadosServidor\" value=\"#dadosServidor#\" id=\"dadosServidor\">',
                'css' => '.lotesModelos,.loteCont,.escondido{
    display:none;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"631d2fa48c9b589430a46113a9690b52\",\"css\":\"ad66d4a62cec6d638233bcbac8aed46c\",\"combined\":\"07284f6b6d645585935f66235c8c2031\"}',
            ],
            [
                'page_id' => 102,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Servicos Editar',
                'id' => 'servicos-editar',
                'language' => 'pt-br',
                'path' => 'servicos-editar/',
                'type' => 'sistema',
                'module' => 'servicos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"ui blue message\">
    @[[form-url-label]]@: <b><a href=\"#url#\">#url#</a></b>.
</div>

<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\" value=\"#nome#\">
</div>
<div class=\"field\">
    <label>#form-thumbnail-label#</label>
    <span>#imagepick-thumbnail#</span>
</div>
<div class=\"field\">
    <label>#form-description-label#</label>
    <textarea class=\"tinymce\" name=\"descricao\">#descricao#</textarea>
</div>
<div class=\"field\">
    <label>@[[form-type-label]]@</label>
    <div class=\"ui large buttons\">
        <div class=\"ui button controleTipo #tipo-simples#\" data-id=\"simples\" data-tooltip=\"@[[type-simple-tooltip]]@\" data-inverted=\"\">@[[form-type-simple]]@</div>
        <div class=\"or\" data-text=\"ou\"></div>
        <div class=\"ui button controleTipo #tipo-lotes#\" data-id=\"lotes-variacoes\" data-tooltip=\"@[[type-batch-tooltip]]@\" data-inverted=\"\">@[[form-type-batch]]@</div>
    </div>
    <input type=\"hidden\" name=\"tipo\" value=\"#tipo-value#\">
</div>
<div class=\"simplesCont #cont-simples#\">
    <div class=\"field\">
        <label>#form-price-label#</label>
        <div class=\"ui labeled input\">
            <label for=\"preco\" class=\"ui label\">R$</label>
            <input type=\"text\" name=\"preco\" placeholder=\"#form-price-placeholder#\" class=\"preco\" value=\"#preco#\">
        </div>
    </div>
    <div class=\"field\">
        <label>#form-quantity-label#</label>
        <input type=\"text\" name=\"quantidade\" placeholder=\"#form-quantity-placeholder#\" class=\"quantidade\" value=\"#quantidade#\">
    </div>
    <div class=\"field\">
        <div class=\"ui checkbox cheGratuito\">
            <input type=\"checkbox\" tabindex=\"0\" name=\"gratuito\" class=\"hidden gratuito\" value=\"1\" checked=\"#gratuito-checked#\">
            <label>@[[form-free-label]]@</label>
        </div>
    </div>
</div>
<div class=\"lotesVariacoesCont #cont-lotes#\">
    <div class=\"ui dividing header\">@[[batch-title]]@</div>
    <div class=\"ui blue floating labeled icon dropdown button lotesMenu\" data-tooltip=\"@[[batch-menu-tooltip]]@\" data-inverted=\"\">
        <i class=\"dropdown icon\"></i>
        <span class=\"text\">#lotes-menu-selected-nome#</span>
        <div class=\"menu\">
            <!-- lote-menu-cel < --><div class=\"item\" data-value=\"#lotes-menu-value#\">#lotes-menu-nome#</div><!-- lote-menu-cel > -->
        </div>
    </div>
    <div class=\"ui button positive loteAdicionar\" data-tooltip=\"@[[batch-add-tooltip]]@\" data-inverted=\"\">
        <i class=\"plus circle icon\"></i>
        @[[btn-batch-add]]@
    </div>
    <!-- lote-cel < --><div class=\"ui segment loteCont\" data-value=\"#lote-value#\" data-num=\"#lote-num#\" data-id=\"#lote-id#\">
        <!-- btn-del < --><div class=\"ui button red right floated button loteExcluir\" data-tooltip=\"@[[batch-del-tooltip]]@\" data-inverted=\"\" data-position=\"top right\">
            <i class=\"times circle icon\"></i>
            @[[btn-batch-del]]@
        </div><!-- btn-del > -->
        <!-- btn-duplicar < --><div class=\"ui button blue right floated button loteDuplicar\" data-tooltip=\"@[[batch-duplicate-tooltip]]@\" data-inverted=\"\" data-position=\"top right\">
            <i class=\"copy outline icon\"></i>
            @[[btn-batch-duplicate]]@
        </div><!-- btn-duplicar > -->
        <div class=\"field\">
            <label>@[[form-batch-label]]@</label>
            <input type=\"text\" placeholder=\"@[[form-batch-name]]@\" value=\"#lote-nome#\" maxlength=\"100\" class=\"loteNome\">
        </div>
        <div class=\"field\">
            <label>@[[form-visibility-label]]@</label>
            <span>#select-visibility#</span>
        </div>
        <div class=\"data-inicio data-visibilidade #lote-cont-data-inicio#\">
            <div class=\"field\">
                <label>@[[form-startdate-label]]@</label>
                <div class=\"ui calendar inverted startdate\">
                    <div class=\"ui input left icon\">
                        <i class=\"calendar icon\"></i>
                        <input type=\"text\" placeholder=\"@[[date-start-label]]@\" class=\"inputDataInicio\" value=\"#lote-startdate#\">
                    </div>
                </div>
            </div>
        </div>
        <div class=\"data-fim data-visibilidade #lote-cont-data-fim#\">
            <div class=\"field\">
                <label>@[[form-enddate-label]]@</label>
                <div class=\"ui calendar inverted enddate\">
                    <div class=\"ui input left icon\">
                        <i class=\"calendar icon\"></i>
                        <input type=\"text\" placeholder=\"@[[date-end-label]]@\" class=\"inputDataFim\" value=\"#lote-enddate#\">
                    </div>
                </div>
            </div>
        </div>
        <div class=\"data-periodo data-visibilidade #lote-cont-periodo#\">
            <div class=\"two fields\">
                <div class=\"field\">
                    <label>@[[form-rangestart-label]]@</label>
                    <div class=\"ui calendar inverted rangestart\">
                        <div class=\"ui input left icon\">
                            <i class=\"calendar icon\"></i>
                            <input type=\"text\" placeholder=\"@[[date-start-label]]@\" class=\"inputDataInicio\" value=\"#lote-rangestart#\">
                        </div>
                    </div>
                </div>
                <div class=\"field\">
                    <label>@[[form-rangeend-label]]@</label>
                    <div class=\"ui calendar inverted rangeend\">
                        <div class=\"ui input left icon\">
                            <i class=\"calendar icon\"></i>
                            <input type=\"text\" placeholder=\"@[[date-end-label]]@\" class=\"inputDataFim\" value=\"#lote-rangeend#\">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"ui dividing header\">@[[variations-title]]@</div>
        <div class=\"ui button positive varAdicionar\" data-tooltip=\"@[[variations-add-tooltip]]@\" data-inverted=\"\">
            <i class=\"plus circle icon\"></i>
            @[[btn-variations-add]]@
        </div>
        <div class=\"ui divided items varItems\">
            <!-- variacao-cel < --><div class=\"item varItem\" data-value=\"#variacao-value#\" data-num=\"#variacao-num#\" data-id=\"#variacao-id#\">
                <div class=\"content\">
                    <!-- var-btn-del < --><div class=\"ui button red right floated button varExcluir\" data-tooltip=\"@[[variations-del-tooltip]]@\" data-inverted=\"\" data-position=\"top right\">
                        <i class=\"times circle icon\"></i>
                        @[[btn-variation-del]]@
                    </div><!-- var-btn-del > -->
                    <div class=\"field\">
                        <label>@[[form-variations-name-label]]@</label>
                        <input type=\"text\" placeholder=\"@[[form-variations-name-placeholder]]@\" value=\"#variacao-nome#\" maxlength=\"100\" class=\"variacaoNome\" data-validate=\"variacaoNome\">
                    </div>
                    <div class=\"two fields\">
                        <div class=\"field\">
                            <label>@[[form-variations-price-label]]@</label>
                            <div class=\"ui labeled input\">
                                <label for=\"preco\" class=\"ui label\">R$</label>
                                <input type=\"text\" placeholder=\"@[[form-price-placeholder]]@\" value=\"#variacao-preco#\" class=\"preco variacaoPreco\" data-validate=\"variacaoPreco\">
                            </div>
                        </div>
                        <div class=\"field\">
                            <label>@[[form-variations-quantity-label]]@</label>
                            <input type=\"text\" placeholder=\"@[[form-quantity-placeholder]]@\" value=\"#variacao-quantidade#\" class=\"quantidade variacaoQuantidade\" data-validate=\"variacaoQuantidade\">
                        </div>
                    </div>
                    <div class=\"field\">
                        <div class=\"ui checkbox cheVariacaoGratuito\">
                            <input type=\"checkbox\" tabindex=\"0\" class=\"hidden variacaoGratuito\" value=\"1\" checked=\"#variacao-gratuito-checked#\">
                            <label>@[[variation-free-label]]@</label>
                        </div>
                    </div>
                </div>
            </div><!-- variacao-cel > -->
        </div>
    </div><!-- lote-cel > -->
    <div class=\"lotesContsFim\"></div>
    <div class=\"lotesModelos\">
        #lotes-modelos#
    </div>
</div>
<div class=\"ui dividing header\">@[[inventory-control-page]]@</div>
<div class=\"three fields\">
    <div class=\"field\">
        <label>#form-quantity-cart-label#</label>
        <div class=\"ui disabled input\">
            <input type=\"text\" placeholder=\"#form-quantity-cart-placeholder#\" value=\"#quantidade-carrinhos#\">
        </div>
    </div>
    <div class=\"field\">
        <label>#form-quantity-pre-order-label#</label>
        <div class=\"ui disabled input\">
            <input type=\"text\" placeholder=\"#form-quantity-pre-order-placeholder#\" value=\"#quantidade-pedidos-pendentes#\">
        </div>
    </div>
    <div class=\"field\">
        <label>#form-quantity-order-label#</label>
        <div class=\"ui disabled input\">
            <input type=\"text\" placeholder=\"#form-quantity-order-placeholder#\" value=\"#quantidade-pedidos#\">
        </div>
    </div>
</div>
<div class=\"ui dividing header\">@[[section-page]]@</div>
<div class=\"field\">
    <label>#form-path-label#</label>
    <input type=\"text\" name=\"paginaCaminho\" placeholder=\"#form-path-placeholder#\" value=\"#caminho#\">
</div>
<div class=\"field\">
    <label>#form-template-label#</label>
    <span>#templates-template#</span>
</div>
<input type=\"hidden\" name=\"dadosServidor\" value=\"#dadosServidor#\" id=\"dadosServidor\">',
                'css' => '.lotesModelos,.loteCont,.escondido{
    display:none;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d9972fac28974f2fbcb8ed1045a34c6b\",\"css\":\"ad66d4a62cec6d638233bcbac8aed46c\",\"combined\":\"53804e78cf865372fd9188fd2a9ef0bf\"}',
            ],
            [
                'page_id' => 103,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Templates',
                'id' => 'templates',
                'language' => 'pt-br',
                'path' => 'templates/',
                'type' => 'sistema',
                'module' => 'templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 104,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Templates Adicionar',
                'id' => 'templates-adicionar',
                'language' => 'pt-br',
                'path' => 'templates-adicionar/',
                'type' => 'sistema',
                'module' => 'templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
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
    <label>#form-thumbnail-label#</label>
    <span>#imagepick-thumbnail#</span>
</div>
<div class=\"ui top attached tabular menu\">
    <a class=\"active item\" data-tab=\"codigo-html\" data-tooltip=\"#form-html-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-html-label#</a>
    <a class=\"item\" data-tab=\"css\" data-tooltip=\"#form-css-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-css-label#</a>
</div>
<div class=\"ui bottom attached active tab segment\" data-tab=\"codigo-html\">
    <textarea class=\"codemirror-html\" name=\"html\">#pagina-html#</textarea>
</div>
<div class=\"ui bottom attached tab segment\" data-tab=\"css\">
    <textarea class=\"codemirror-css\" name=\"css\">#pagina-css#</textarea>
</div>
<input type=\"hidden\" name=\"modelo\" value=\"#modelo#\">',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"82278fec463f44090e17ff3ba91c8d66\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"82278fec463f44090e17ff3ba91c8d66\"}',
            ],
            [
                'page_id' => 105,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Templates Ativar',
                'id' => 'templates-ativar',
                'language' => 'pt-br',
                'path' => 'templates-ativar/',
                'type' => 'sistema',
                'module' => 'templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 106,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Templates Atualizacoes',
                'id' => 'templates-atualizacoes',
                'language' => 'pt-br',
                'path' => 'templates-atualizacoes/',
                'type' => 'sistema',
                'module' => 'templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<h1 class=\"ui header\">@[[pagina#titulo]]@</h1>
<div class=\"ui message\">
    <div class=\"header\"> Atualização dos Templates </div>
    <p>Clique no botão abaixo para atualizar os templates padrões com a última versão disponível.</p>
</div>
<a class=\"ui button green\" href=\"?atualizar=sim\" data-content=\"Clique para atualizar templates.\">
    <i class=\"cloud download alternate icon\"></i> Atualizar </a>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"cddd25ebffeb9cf4ed02edb027b2d152\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"cddd25ebffeb9cf4ed02edb027b2d152\"}',
            ],
            [
                'page_id' => 107,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Templates Editar',
                'id' => 'templates-editar',
                'language' => 'pt-br',
                'path' => 'templates-editar/',
                'type' => 'sistema',
                'module' => 'templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<!-- template-ativacao < -->
<table class=\"ui celled table\">
    <thead>
        <tr>
            <th>@[[activation-title]]@</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <!-- btn-active < -->
                <a class=\"ui active positive button\">
                    <i class=\"check circle outline icon\"></i> @[[button-active]]@ </a><!-- btn-active > -->
                <!-- btn-activate < -->
                <a class=\"ui orange button templateActivate\" data-tooltip=\"@[[button-tooltip-activate]]@\" data-inverted=\"\" href=\"@[[pagina#url-raiz]]@@[[pagina#modulo-id]]@/ativar/?modelo=#modelo#&amp;tipo=#template-tipo#&amp;id=#template-id#&amp;editar=sim\">
                    <i class=\"circle outline icon\"></i> @[[button-activate]]@ </a><!-- btn-activate > -->
                <!-- btn-update < -->
                <a class=\"ui positive button templateUpdate\" data-tooltip=\"@[[button-tooltip-update]]@\" data-inverted=\"\" href=\"@[[pagina#url-raiz]]@@[[pagina#modulo-id]]@/atualizacoes/?atualizar=sim&amp;editar=sim&amp;modelo=#modelo#&amp;id=#template-id#&amp;categoria_id=#categoria-id#\">
                    <i class=\"cloud download alternate icon\"></i> @[[button-update]]@ </a><!-- btn-update > -->
                <!-- btn-updated < -->
                <a class=\"ui button\">
                    <i class=\"check circle outline icon\"></i> @[[button-updated]]@ </a><!-- btn-updated > -->
            </td>
        </tr>
    </tbody>
</table>
<!-- template-ativacao > -->
<div class=\"field\">
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
    <label>#form-thumbnail-label#</label>
    <span>#imagepick-thumbnail#</span>
</div>
<div class=\"ui top attached tabular menu\">
    <a class=\"active item\" data-tab=\"codigo-html\" data-tooltip=\"#form-html-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-html-label#</a>
    <a class=\"item\" data-tab=\"css\" data-tooltip=\"#form-css-tooltip#\" data-position=\"top left\" data-inverted=\"\">#form-css-label#</a>
</div>
<div class=\"ui bottom attached active tab segment\" data-tab=\"codigo-html\">#pagina-html-backup# <textarea class=\"codemirror-html\" name=\"html\">#pagina-html#</textarea>
</div>
<div class=\"ui bottom attached tab segment\" data-tab=\"css\">#pagina-css-backup# <textarea class=\"codemirror-css\" name=\"css\">#pagina-css#</textarea>
</div>
<input type=\"hidden\" name=\"modelo\" value=\"#modelo#\">',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"dd982492f3ddae7e851b21b30178e074\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"dd982492f3ddae7e851b21b30178e074\"}',
            ],
            [
                'page_id' => 108,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Templates Editar Indice',
                'id' => 'templates-editar-Indice',
                'language' => 'pt-br',
                'path' => 'templates-editar-Indice/',
                'type' => 'sistema',
                'module' => 'templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 109,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Templates Pre Visualizacao',
                'id' => 'templates-pre-visualizacao',
                'language' => 'pt-br',
                'path' => 'templates-pre-visualizacao/',
                'type' => 'sistema',
                'module' => 'templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"ui icon message\">
    <i class=\"file alternate icon\"></i>
    <div class=\"content\">
        <div class=\"header\"> Corpo da Página </div>
        <p>Está caixa será subistituída pelo corpo da página.</p>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"7d56049c47c89d1530a57c128b7240f5\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"7d56049c47c89d1530a57c128b7240f5\"}',
            ],
            [
                'page_id' => 110,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Templates Seletores',
                'id' => 'templates-seletores',
                'language' => 'pt-br',
                'path' => 'templates-seletores/',
                'type' => 'sistema',
                'module' => 'templates',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<h1 class=\"ui header\">@[[pagina#titulo]]@</h1>
<div class=\"optionsCont hidden-obj\">
    <div class=\"ui medium header\">@[[form-category-label]]@</div>
    <span>#select-category#</span>
</div>
<p>&nbsp;</p>
<div class=\"listSelectorsCont hidden-obj\">
    <div class=\"ui medium header\">@[[selectors-list-label]]@</div>
    <div id=\"selectors-list-cont\">#seletores-lista#</div>
</div>
<div class=\"withoutResultsCont hidden-obj\"> #without-results-cont# </div>',
                'css' => '.hidden-obj{
    display:none;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d0ff2b1d359c05ae090514b054ad4b2c\",\"css\":\"019c4b82a45f71000961e23d3e958779\",\"combined\":\"ea21a515e16abda284261f554229fb85\"}',
            ],
            [
                'page_id' => 111,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Pagina De Testes',
                'id' => 'pagina-de-testes',
                'language' => 'pt-br',
                'path' => 'pagina-de-testes/',
                'type' => 'sistema',
                'module' => 'testes',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => 'tag{
	width:100px;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"0b58367cd17703c084b92e6bf311f9a4\",\"combined\":\"0b58367cd17703c084b92e6bf311f9a4\"}',
            ],
            [
                'page_id' => 112,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios',
                'id' => 'usuarios',
                'language' => 'pt-br',
                'path' => 'usuarios/',
                'type' => 'sistema',
                'module' => 'usuarios',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 113,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Adicionar',
                'id' => 'usuarios-adicionar',
                'language' => 'pt-br',
                'path' => 'usuarios-adicionar/',
                'type' => 'sistema',
                'module' => 'usuarios',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>@[[form-name-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<table class=\"ui celled table\">
    <thead>
        <tr>
            <th>@[[first-name]]@</th>
            <th>@[[middle-name]]@</th>
            <th>@[[last-name]]@</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class=\"first-name\"></td>
            <td class=\"middle-name\"></td>
            <td class=\"last-name\"></td>
        </tr>
    </tbody>
</table>
<div class=\"field\">
    <label>@[[form-user-profile-label]]@</label>
    <span>#select-user-profile#</span>
</div>
<div class=\"field\">
    <label>@[[form-email-label]]@</label>
    <input type=\"email\" name=\"email\" placeholder=\"@[[form-email-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<div class=\"field\">
    <label>@[[form-email-2-label]]@</label>
    <input type=\"email\" name=\"email-2\" placeholder=\"@[[form-email-2-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<div class=\"field\">
    <label>@[[form-user-label]]@</label>
    <input type=\"text\" name=\"usuario\" placeholder=\"@[[form-user-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<div class=\"two fields\">
    <div class=\"field\">
        <label>@[[form-password-label]]@</label>
        <input type=\"password\" name=\"senha\" placeholder=\"@[[form-password-placeholder]]@\" autocomplete=\"new-password\" />
    </div>
    <div class=\"field\">
        <label>@[[form-password-2-label]]@</label>
        <input type=\"password\" name=\"senha-2\" placeholder=\"@[[form-password-2-placeholder]]@\" autocomplete=\"new-password\" />
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"0e4d39920b17ee4329ec7570f23f9247\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"0e4d39920b17ee4329ec7570f23f9247\"}',
            ],
            [
                'page_id' => 114,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Editar',
                'id' => 'usuarios-editar',
                'language' => 'pt-br',
                'path' => 'usuarios-editar/',
                'type' => 'sistema',
                'module' => 'usuarios',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<!-- usuario-pai < --><div class=\"ui blue message\">
    <p>Usuário Pai: <b><a href=\"./?id=#idPai#\">#nomePai#</a></b>.</p>
    <p><strong>IMPORTANTE: </strong>não é possível alterar o perfil deste usuário pois o mesmo herda o perfil do pai. Altere o perfil do pai caso queira modificar o perfil dos usuários filhos do mesmo.</p>
</div><!-- usuario-pai > -->
<div class=\"field\">
    <label>@[[form-name-account-label]]@</label>
    <input type=\"text\" name=\"nome_conta\" placeholder=\"@[[form-name-account-placeholder]]@\" value=\"#nome_conta#\">
</div>
<div class=\"field\">
    <label>@[[form-name-user-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-user-placeholder]]@\" value=\"#nome#\">
</div>
<table class=\"ui celled table\">
    <thead>
        <tr>
            <th>@[[first-name]]@</th>
            <th>@[[middle-name]]@</th>
            <th>@[[last-name]]@</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class=\"first-name\">#primeiro_nome#</td>
            <td class=\"middle-name\">#nome_do_meio#</td>
            <td class=\"last-name\">#ultimo_nome#</td>
        </tr>
    </tbody>
</table>
<div class=\"field\">
    <label>@[[form-user-profile-label]]@</label>
    <span>#select-user-profile#</span>
</div>
<div class=\"field\">
    <label>@[[form-email-label]]@</label>
    <input type=\"email\" name=\"email\" placeholder=\"@[[form-email-placeholder]]@\" value=\"#email#\">
</div>
<div class=\"field\">
    <label>@[[form-email-2-label]]@</label>
    <input type=\"email\" name=\"email-2\" placeholder=\"@[[form-email-2-placeholder]]@\" value=\"#email#\">
</div>
<div class=\"field\">
    <label>@[[form-user-label]]@</label>
    <input type=\"text\" name=\"usuario\" placeholder=\"@[[form-user-placeholder]]@\" value=\"#usuario#\">
</div>
<!-- senha-campos < -->
<div class=\"two fields\" id=\"senha-campos\">
    <div class=\"field\">
        <label>@[[form-password-label]]@</label>
        <input type=\"password\" name=\"senha\" placeholder=\"@[[form-password-placeholder]]@\" autocomplete=\"new-password\">
    </div>
    <div class=\"field\">
        <label>@[[form-password-2-label]]@</label>
        <input type=\"password\" name=\"senha-2\" placeholder=\"@[[form-password-2-placeholder]]@\" autocomplete=\"new-password\">
    </div>
    <input type=\"hidden\" name=\"senha-atualizar\" value=\"1\">
</div><!-- senha-campos > -->
<!-- senha-botao < -->
<div class=\"field\">
    <label>@[[form-password-label]]@</label>
    <a class=\"ui button\" href=\"@[[pagina#url-raiz]]@@[[pagina#url-caminho]]@?id=@[[pagina#registro-id]]@&amp;password-button=1#senha-campos\">
        <i class=\"user lock icon\"></i> @[[form-password-button]]@ </a>
</div>
<!-- senha-botao > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"2f363546fda25c2e25c7aeeaa4a476da\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"2f363546fda25c2e25c7aeeaa4a476da\"}',
            ],
            [
                'page_id' => 115,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Gestores',
                'id' => 'usuarios-gestores',
                'language' => 'pt-br',
                'path' => 'usuarios-gestores/',
                'type' => 'sistema',
                'module' => 'usuarios-gestores',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 116,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Gestores Adicionar',
                'id' => 'usuarios-gestores-adicionar',
                'language' => 'pt-br',
                'path' => 'usuarios-gestores-adicionar/',
                'type' => 'sistema',
                'module' => 'usuarios-gestores',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>@[[form-name-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<table class=\"ui celled table\">
    <thead>
        <tr>
            <th>@[[first-name]]@</th>
            <th>@[[middle-name]]@</th>
            <th>@[[last-name]]@</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class=\"first-name\"></td>
            <td class=\"middle-name\"></td>
            <td class=\"last-name\"></td>
        </tr>
    </tbody>
</table>
<div class=\"two fields\">
    <div class=\"field\">
        <label>@[[form-user-manager-profile-label]]@</label>
        <span>#select-user-profile#</span>
    </div>
    <div class=\"field\">
        <label>@[[form-permission-label]]@</label>
        <div class=\"ui toggle checkbox\">
            <input type=\"checkbox\" name=\"privilegios_admin\" data-checked=\"\">
            <label>&nbsp;</label>
        </div>
    </div>
</div>
<div class=\"field\">
    <label>@[[form-email-label]]@</label>
    <input type=\"email\" name=\"email\" placeholder=\"@[[form-email-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<div class=\"field\">
    <label>@[[form-email-2-label]]@</label>
    <input type=\"email\" name=\"email-2\" placeholder=\"@[[form-email-2-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<div class=\"field\">
    <label>@[[form-user-label]]@</label>
    <input type=\"text\" name=\"usuario\" placeholder=\"@[[form-user-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<div class=\"two fields\">
    <div class=\"field\">
        <label>@[[form-password-label]]@</label>
        <input type=\"password\" name=\"senha\" placeholder=\"@[[form-password-placeholder]]@\" autocomplete=\"new-password\" />
    </div>
    <div class=\"field\">
        <label>@[[form-password-2-label]]@</label>
        <input type=\"password\" name=\"senha-2\" placeholder=\"@[[form-password-2-placeholder]]@\" autocomplete=\"new-password\" />
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"9e6cdd511b6ce7a88c8a691490a9e8c8\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"9e6cdd511b6ce7a88c8a691490a9e8c8\"}',
            ],
            [
                'page_id' => 117,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Gestores Editar',
                'id' => 'usuarios-gestores-editar',
                'language' => 'pt-br',
                'path' => 'usuarios-gestores-editar/',
                'type' => 'sistema',
                'module' => 'usuarios-gestores',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>@[[form-name-account-label]]@</label>
    <input type=\"text\" name=\"nome_conta\" placeholder=\"@[[form-name-account-placeholder]]@\" value=\"#nome_conta#\">
</div>
<div class=\"field\">
    <label>@[[form-name-user-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-user-placeholder]]@\" value=\"#nome#\">
</div>
<table class=\"ui celled table\">
    <thead>
        <tr>
            <th>@[[first-name]]@</th>
            <th>@[[middle-name]]@</th>
            <th>@[[last-name]]@</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class=\"first-name\">#primeiro_nome#</td>
            <td class=\"middle-name\">#nome_do_meio#</td>
            <td class=\"last-name\">#ultimo_nome#</td>
        </tr>
    </tbody>
</table>
<div class=\"two fields\">
    <div class=\"field\">
        <label>@[[form-user-manager-profile-label]]@</label>
        <span>#select-user-profile#</span>
    </div>
    <div class=\"field\">
        <label>@[[form-permission-label]]@</label>
        <div class=\"ui toggle checkbox\">
            <input type=\"checkbox\" name=\"privilegios_admin\" data-checked=\"#checked#\">
            <label>&nbsp;</label>
        </div>
    </div>
</div>
<div class=\"field\">
    <label>@[[form-email-label]]@</label>
    <input type=\"email\" name=\"email\" placeholder=\"@[[form-email-placeholder]]@\" value=\"#email#\">
</div>
<div class=\"field\">
    <label>@[[form-email-2-label]]@</label>
    <input type=\"email\" name=\"email-2\" placeholder=\"@[[form-email-2-placeholder]]@\" value=\"#email#\">
</div>
<div class=\"field\">
    <label>@[[form-user-label]]@</label>
    <input type=\"text\" name=\"usuario\" placeholder=\"@[[form-user-placeholder]]@\" value=\"#usuario#\">
</div>
<!-- senha-campos < -->
<div class=\"two fields\" id=\"senha-campos\">
    <div class=\"field\">
        <label>@[[form-password-label]]@</label>
        <input type=\"password\" name=\"senha\" placeholder=\"@[[form-password-placeholder]]@\" autocomplete=\"new-password\">
    </div>
    <div class=\"field\">
        <label>@[[form-password-2-label]]@</label>
        <input type=\"password\" name=\"senha-2\" placeholder=\"@[[form-password-2-placeholder]]@\" autocomplete=\"new-password\">
    </div>
    <input type=\"hidden\" name=\"senha-atualizar\" value=\"1\">
</div><!-- senha-campos > -->
<!-- senha-botao < -->
<div class=\"field\">
    <label>@[[form-password-label]]@</label>
    <a class=\"ui button\" href=\"@[[pagina#url-raiz]]@@[[pagina#url-caminho]]@?id=@[[pagina#registro-id]]@&amp;password-button=1#senha-campos\">
        <i class=\"user lock icon\"></i> @[[form-password-button]]@ </a>
</div>
<!-- senha-botao > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"a5ad23e5006dad3a7c0fea9a92bc318c\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"a5ad23e5006dad3a7c0fea9a92bc318c\"}',
            ],
            [
                'page_id' => 118,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Gestores Perfis',
                'id' => 'usuarios-gestores-perfis',
                'language' => 'pt-br',
                'path' => 'usuarios-gestores-perfis/',
                'type' => 'sistema',
                'module' => 'usuarios-gestores-perfis',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 119,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Gestores Perfis Adicionar',
                'id' => 'usuarios-gestores-perfis-adicionar',
                'language' => 'pt-br',
                'path' => 'usuarios-gestores-perfis-adicionar/',
                'type' => 'sistema',
                'module' => 'usuarios-gestores-perfis',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>@[[form-name-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-placeholder]]@\">
</div>
<div class=\"ui dividing tiny header\">#modules-name#</div>
<!-- grupo < -->
<div class=\"ui segment\">
    <div class=\"ui grid\">
        <div class=\"column\">
            <div class=\"ui left floated small header\">#module-grup-name#</div>
            <button class=\"ui right floated button selectAll\" type=\"button\">
                <i class=\"check circle outline icon\"></i>@[[module-select-all]]@ </button>
            <button class=\"ui right floated button unselectAll\" type=\"button\">
                <i class=\"circle outline icon\"></i>@[[module-unselect-all]]@ </button>
        </div>
    </div>
    <div class=\"ui divided items\">
        <div class=\"item\">
            <div class=\"content\">
                <div class=\"ui cards\">
                    <!-- items < -->
                    <div class=\"ui raised card\">
                        <div class=\"content\">
                            <div class=\"ui small header\">#module-label#</div>
                            <div class=\"ui toggle checkbox\">
                                <input type=\"checkbox\" name=\"modulo-#num#\" data-checked=\"#checked#\" value=\"#id#\">
                                <label>&nbsp;</label>
                            </div>
                        </div>
                        <!-- operacoes < -->
                        <div class=\"extra content\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui tiny header\">#operacoes-nome#</div>
                                <div class=\"ui relaxed divided list\">
                                    <!-- operacoes-items < -->
                                    <div class=\"item\">
                                        <div class=\"ui checkbox\">
                                            <input type=\"checkbox\" name=\"operacao-#operacao-num#\" data-checked=\"#operacao-checked#\" value=\"#operacao-id#\">
                                            <label>#operacao-label#</label>
                                        </div>
                                    </div><!-- operacoes-items > -->
                                </div>
                            </div>
                        </div><!-- operacoes > -->
                    </div>
                    <!-- items > -->
                </div>
            </div>
        </div>
    </div>
</div>
<!-- grupo > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"61a4fcb4fc80216a38fb5e40a5e544e7\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"61a4fcb4fc80216a38fb5e40a5e544e7\"}',
            ],
            [
                'page_id' => 120,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Gestores Perfis Editar',
                'id' => 'usuarios-gestores-perfis-editar',
                'language' => 'pt-br',
                'path' => 'usuarios-gestores-perfis-editar/',
                'type' => 'sistema',
                'module' => 'usuarios-gestores-perfis',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>@[[form-name-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-placeholder]]@\" value=\"#nome#\">
</div>
<div class=\"ui dividing tiny header\">#modules-name#</div>
<!-- grupo < -->
<div class=\"ui segment\">
    <div class=\"ui grid\">
        <div class=\"column\">
            <div class=\"ui left floated small header\">#module-grup-name#</div>
            <button class=\"ui right floated button selectAll\" type=\"button\">
                <i class=\"check circle outline icon\"></i>@[[module-select-all]]@ </button>
            <button class=\"ui right floated button unselectAll\" type=\"button\">
                <i class=\"circle outline icon\"></i> @[[module-unselect-all]]@ </button>
        </div>
    </div>
    <div class=\"ui divided items\">
        <div class=\"item\">
            <div class=\"content\">
                <div class=\"ui cards\">
                    <!-- items < -->
                    <div class=\"ui raised card\">
                        <div class=\"content\">
                            <div class=\"ui small header\">#module-label#</div>
                            <div class=\"ui toggle checkbox\">
                                <input type=\"checkbox\" name=\"modulo-#num#\" data-checked=\"#checked#\" value=\"#id#\">
                                <label>&nbsp;</label>
                            </div>
                        </div>
                        <!-- operacoes < -->
                        <div class=\"extra content\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui tiny header\">#operacoes-nome#</div>
                                <div class=\"ui relaxed divided list\">
                                    <!-- operacoes-items < -->
                                    <div class=\"item\">
                                        <div class=\"ui checkbox\">
                                            <input type=\"checkbox\" name=\"operacao-#operacao-num#\" data-checked=\"#operacao-checked#\" value=\"#operacao-id#\">
                                            <label>#operacao-label#</label>
                                        </div>
                                    </div><!-- operacoes-items > -->
                                </div>
                            </div>
                        </div><!-- operacoes > -->
                    </div>
                    <!-- items > -->
                </div>
            </div>
        </div>
    </div>
</div>
<!-- grupo > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"8cde358a3d847ba628d35653c34ccefa\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"8cde358a3d847ba628d35653c34ccefa\"}',
            ],
            [
                'page_id' => 121,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Hospedeiro',
                'id' => 'usuarios-hospedeiro',
                'language' => 'pt-br',
                'path' => 'usuarios-hospedeiro/',
                'type' => 'sistema',
                'module' => 'usuarios-hospedeiro',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 122,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Hospedeiro Adicionar',
                'id' => 'usuarios-hospedeiro-adicionar',
                'language' => 'pt-br',
                'path' => 'usuarios-hospedeiro-adicionar/',
                'type' => 'sistema',
                'module' => 'usuarios-hospedeiro',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>@[[form-name-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<table class=\"ui celled table\">
    <thead>
        <tr>
            <th>@[[first-name]]@</th>
            <th>@[[middle-name]]@</th>
            <th>@[[last-name]]@</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class=\"first-name\"></td>
            <td class=\"middle-name\"></td>
            <td class=\"last-name\"></td>
        </tr>
    </tbody>
</table>
<div class=\"field\">
    <label>@[[form-user-profile-label]]@</label>
    <span>#select-user-profile#</span>
</div>
<div class=\"field\">
    <label>@[[form-document-type-label]]@</label>
    <div class=\"ui buttons\">
        <div class=\"ui button active controleDoc\" data-id=\"cpf\">CPF</div>
        <div class=\"or\" data-text=\"ou\"></div>
        <div class=\"ui button controleDoc\" data-id=\"cnpj\">CNPJ</div>
    </div>
    <input type=\"hidden\" name=\"cnpj_ativo\" value=\"nao\">
</div>
<div class=\"field\">
    <label>@[[form-cpf-label]]@</label>
    <input type=\"text\" name=\"cpf\" placeholder=\"CPF\" class=\"cpf\" autocomplete=\"new-password\">
</div>
<div class=\"field escondido\">
    <label>@[[form-cnpj-label]]@</label>
    <input type=\"text\" name=\"cnpj\" placeholder=\"CNPJ\" class=\"cnpj\" autocomplete=\"new-password\">
</div>
<div class=\"field\">
    <label>@[[form-phone-label]]@</label>
    <input type=\"text\" name=\"telefone\" placeholder=\"Telefone\" class=\"telefone\" autocomplete=\"new-password\">
</div>
<div class=\"field\">
    <label>@[[form-email-label]]@</label>
    <input type=\"email\" name=\"email\" placeholder=\"@[[form-email-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<div class=\"field\">
    <label>@[[form-email-2-label]]@</label>
    <input type=\"email\" name=\"email-2\" placeholder=\"@[[form-email-2-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<div class=\"field\">
    <label>@[[form-user-label]]@</label>
    <input type=\"text\" name=\"usuario\" placeholder=\"@[[form-user-placeholder]]@\" autocomplete=\"new-password\" />
</div>
<div class=\"two fields\">
    <div class=\"field\">
        <label>@[[form-password-label]]@</label>
        <input type=\"password\" name=\"senha\" placeholder=\"@[[form-password-placeholder]]@\" autocomplete=\"new-password\" />
    </div>
    <div class=\"field\">
        <label>@[[form-password-2-label]]@</label>
        <input type=\"password\" name=\"senha-2\" placeholder=\"@[[form-password-2-placeholder]]@\" autocomplete=\"new-password\" />
    </div>
</div>',
                'css' => '.escondido{
    display:none;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"5fc1bb02584dde4830bf12d2bffc7c2f\",\"css\":\"c120e3f8edf1c289de8b09f494657350\",\"combined\":\"2e57e3274d425d9b4386c984c4d51dd2\"}',
            ],
            [
                'page_id' => 123,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Hospedeiro Editar',
                'id' => 'usuarios-hospedeiro-editar',
                'language' => 'pt-br',
                'path' => 'usuarios-hospedeiro-editar/',
                'type' => 'sistema',
                'module' => 'usuarios-hospedeiro',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>@[[form-name-account-label]]@</label>
    <input type=\"text\" name=\"nome_conta\" placeholder=\"@[[form-name-account-placeholder]]@\" value=\"#nome_conta#\">
</div>
<div class=\"field\">
    <label>@[[form-name-user-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-user-placeholder]]@\" value=\"#nome#\">
</div>
<table class=\"ui celled table\">
    <thead>
        <tr>
            <th>@[[first-name]]@</th>
            <th>@[[middle-name]]@</th>
            <th>@[[last-name]]@</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class=\"first-name\">#primeiro_nome#</td>
            <td class=\"middle-name\">#nome_do_meio#</td>
            <td class=\"last-name\">#ultimo_nome#</td>
        </tr>
    </tbody>
</table>
<div class=\"field\">
    <label>@[[form-user-profile-label]]@</label>
    <span>#select-user-profile#</span>
</div>
<div class=\"field\">
    <label>@[[form-document-type-label]]@</label>
    <div class=\"ui buttons\">
        <div class=\"ui button active controleDoc\" data-id=\"cpf\">CPF</div>
        <div class=\"or\" data-text=\"ou\"></div>
        <div class=\"ui button controleDoc\" data-id=\"cnpj\">CNPJ</div>
    </div>
    <input type=\"hidden\" name=\"cnpj_ativo\" value=\"#cnpj_ativo#\">
</div>
<div class=\"field\">
    <label>@[[form-cpf-label]]@</label>
    <input type=\"text\" name=\"cpf\" placeholder=\"CPF\" class=\"cpf\" value=\"#cpf#\">
</div>
<div class=\"field escondido\">
    <label>@[[form-cnpj-label]]@</label>
    <input type=\"text\" name=\"cnpj\" placeholder=\"CNPJ\" class=\"cnpj\" value=\"#cnpj#\">
</div>
<div class=\"field\">
    <label>@[[form-phone-label]]@</label>
    <input type=\"text\" name=\"telefone\" placeholder=\"Telefone\" class=\"telefone\" value=\"#telefone#\">
</div>
<div class=\"field\">
    <label>@[[form-email-label]]@</label>
    <input type=\"email\" name=\"email\" placeholder=\"@[[form-email-placeholder]]@\" value=\"#email#\">
</div>
<div class=\"field\">
    <label>@[[form-email-2-label]]@</label>
    <input type=\"email\" name=\"email-2\" placeholder=\"@[[form-email-2-placeholder]]@\" value=\"#email#\">
</div>
<div class=\"field\">
    <label>@[[form-user-label]]@</label>
    <input type=\"text\" name=\"usuario\" placeholder=\"@[[form-user-placeholder]]@\" value=\"#usuario#\">
</div>
<!-- senha-campos < -->
<div class=\"two fields\" id=\"senha-campos\">
    <div class=\"field\">
        <label>@[[form-password-label]]@</label>
        <input type=\"password\" name=\"senha\" placeholder=\"@[[form-password-placeholder]]@\" autocomplete=\"new-password\">
    </div>
    <div class=\"field\">
        <label>@[[form-password-2-label]]@</label>
        <input type=\"password\" name=\"senha-2\" placeholder=\"@[[form-password-2-placeholder]]@\" autocomplete=\"new-password\">
    </div>
    <input type=\"hidden\" name=\"senha-atualizar\" value=\"1\">
</div><!-- senha-campos > -->
<!-- senha-botao < -->
<div class=\"field\">
    <label>@[[form-password-label]]@</label>
    <a class=\"ui button\" href=\"@[[pagina#url-raiz]]@@[[pagina#url-caminho]]@?id=@[[pagina#registro-id]]@&amp;password-button=1#senha-campos\">
        <i class=\"user lock icon\"></i> @[[form-password-button]]@ </a>
</div>
<!-- senha-botao > -->',
                'css' => '.escondido{
    display:none;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"db04e2c1091e64bb42353bdea3534322\",\"css\":\"c120e3f8edf1c289de8b09f494657350\",\"combined\":\"ee95e78e56b213a452d1953afc70962a\"}',
            ],
            [
                'page_id' => 124,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Hospedeiro Perfis',
                'id' => 'usuarios-hospedeiro-perfis',
                'language' => 'pt-br',
                'path' => 'usuarios-hospedeiro-perfis/',
                'type' => 'sistema',
                'module' => 'usuarios-hospedeiro-perfis',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 125,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Hospedeiro Perfis Adicionar',
                'id' => 'usuarios-hospedeiro-perfis-adicionar',
                'language' => 'pt-br',
                'path' => 'usuarios-hospedeiro-perfis-adicionar/',
                'type' => 'sistema',
                'module' => 'usuarios-hospedeiro-perfis',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>@[[form-name-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-placeholder]]@\">
</div>
<div class=\"field\">
    <label>@[[form-default-label]]@</label>
    <div class=\"ui toggle checkbox\">
        <input type=\"checkbox\" name=\"padrao\" data-checked=\"\">
        <label>&nbsp;</label>
    </div>
</div>
<div class=\"ui dividing tiny header\">#modules-name#</div>
<!-- grupo < -->
<div class=\"ui segment\">
    <div class=\"ui grid\">
        <div class=\"column\">
            <div class=\"ui left floated small header\">#module-grup-name#</div>
            <button class=\"ui right floated button selectAll\" type=\"button\">
                <i class=\"check circle outline icon\"></i>@[[module-select-all]]@ </button>
            <button class=\"ui right floated button unselectAll\" type=\"button\">
                <i class=\"circle outline icon\"></i>@[[module-unselect-all]]@ </button>
        </div>
    </div>
    <div class=\"ui divided items\">
        <div class=\"item\">
            <div class=\"content\">
                <div class=\"ui cards\">
                    <!-- items < -->
                    <div class=\"ui raised card\">
                        <div class=\"content\">
                            <div class=\"ui small header\">#module-label#</div>
                            <div class=\"ui toggle checkbox\">
                                <input type=\"checkbox\" name=\"modulo-#num#\" data-checked=\"#checked#\" value=\"#id#\">
                                <label>&nbsp;</label>
                            </div>
                        </div>
                        <!-- operacoes < -->
                        <div class=\"extra content\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui tiny header\">#operacoes-nome#</div>
                                <div class=\"ui relaxed divided list\">
                                    <!-- operacoes-items < -->
                                    <div class=\"item\">
                                        <div class=\"ui checkbox\">
                                            <input type=\"checkbox\" name=\"operacao-#operacao-num#\" data-checked=\"#operacao-checked#\" value=\"#operacao-id#\">
                                            <label>#operacao-label#</label>
                                        </div>
                                    </div><!-- operacoes-items > -->
                                </div>
                            </div>
                        </div><!-- operacoes > -->
                    </div>
                    <!-- items > -->
                </div>
            </div>
        </div>
    </div>
</div>
<!-- grupo > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"85f6f0fa6aef9954afc5da483a266742\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"85f6f0fa6aef9954afc5da483a266742\"}',
            ],
            [
                'page_id' => 126,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Hospedeiro Perfis Editar',
                'id' => 'usuarios-hospedeiro-perfis-editar',
                'language' => 'pt-br',
                'path' => 'usuarios-hospedeiro-perfis-editar/',
                'type' => 'sistema',
                'module' => 'usuarios-hospedeiro-perfis',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>@[[form-name-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-placeholder]]@\" value=\"#nome#\">
</div>
<div class=\"field\">
    <label>@[[form-default-label]]@</label>
    <div class=\"ui toggle checkbox\">
        <input type=\"checkbox\" name=\"padrao\" data-checked=\"#padrao-checked#\">
        <label>&nbsp;</label>
    </div>
</div>
<div class=\"ui dividing tiny header\">#modules-name#</div>
<!-- grupo < -->
<div class=\"ui segment\">
    <div class=\"ui grid\">
        <div class=\"column\">
            <div class=\"ui left floated small header\">#module-grup-name#</div>
            <button class=\"ui right floated button selectAll\" type=\"button\">
                <i class=\"check circle outline icon\"></i>@[[module-select-all]]@ </button>
            <button class=\"ui right floated button unselectAll\" type=\"button\">
                <i class=\"circle outline icon\"></i> @[[module-unselect-all]]@ </button>
        </div>
    </div>
    <div class=\"ui divided items\">
        <div class=\"item\">
            <div class=\"content\">
                <div class=\"ui cards\">
                    <!-- items < -->
                    <div class=\"ui raised card\">
                        <div class=\"content\">
                            <div class=\"ui small header\">#module-label#</div>
                            <div class=\"ui toggle checkbox\">
                                <input type=\"checkbox\" name=\"modulo-#num#\" data-checked=\"#checked#\" value=\"#id#\">
                                <label>&nbsp;</label>
                            </div>
                        </div>
                        <!-- operacoes < -->
                        <div class=\"extra content\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui tiny header\">#operacoes-nome#</div>
                                <div class=\"ui relaxed divided list\">
                                    <!-- operacoes-items < -->
                                    <div class=\"item\">
                                        <div class=\"ui checkbox\">
                                            <input type=\"checkbox\" name=\"operacao-#operacao-num#\" data-checked=\"#operacao-checked#\" value=\"#operacao-id#\">
                                            <label>#operacao-label#</label>
                                        </div>
                                    </div><!-- operacoes-items > -->
                                </div>
                            </div>
                        </div><!-- operacoes > -->
                    </div>
                    <!-- items > -->
                </div>
            </div>
        </div>
    </div>
</div>
<!-- grupo > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"34fa172e5418016e67b74388d8d07517\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"34fa172e5418016e67b74388d8d07517\"}',
            ],
            [
                'page_id' => 127,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Hospedeiro Perfis Admin',
                'id' => 'usuarios-hospedeiro-perfis-admin',
                'language' => 'pt-br',
                'path' => 'usuarios-hospedeiro-perfis-admin/',
                'type' => 'sistema',
                'module' => 'usuarios-hospedeiro-perfis-admin',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 128,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Hospedeiro Perfis Admin Adicionar',
                'id' => 'usuarios-hospedeiro-perfis-admin-adicionar',
                'language' => 'pt-br',
                'path' => 'usuarios-hospedeiro-perfis-admin-adicionar/',
                'type' => 'sistema',
                'module' => 'usuarios-hospedeiro-perfis-admin',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"field\">
    <label>@[[form-default-label]]@</label>
    <div class=\"ui toggle checkbox\">
        <input type=\"checkbox\" name=\"padrao\" data-checked=\"\">
        <label>&nbsp;</label>
    </div>
</div>
<div class=\"ui dividing tiny header\">#modules-name#</div>
<!-- grupo < -->
<div class=\"ui segment\">
    <div class=\"ui grid\">
        <div class=\"column\">
            <div class=\"ui left floated small header\">#module-grup-name#</div>
            <button class=\"ui right floated button selectAll\" type=\"button\">
                <i class=\"check circle outline icon\"></i>#module-select-all# </button>
            <button class=\"ui right floated button unselectAll\" type=\"button\">
                <i class=\"circle outline icon\"></i>#module-unselect-all# </button>
        </div>
    </div>
    <div class=\"ui divided items\">
        <div class=\"item\">
            <div class=\"content\">
                <div class=\"ui cards\">
                    <!-- items < -->
                    <div class=\"ui raised card\">
                        <div class=\"content\">
                            <div class=\"ui small header\">#module-label#</div>
                            <div class=\"ui toggle checkbox\">
                                <input type=\"checkbox\" name=\"modulo-#num#\" data-checked=\"#checked#\" value=\"#id#\">
                                <label>&nbsp;</label>
                            </div>
                        </div>
                        <!-- operacoes < -->
                        <div class=\"extra content\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui tiny header\">#operacoes-nome#</div>
                                <div class=\"ui relaxed divided list\">
                                    <!-- operacoes-items < -->
                                    <div class=\"item\">
                                        <div class=\"ui checkbox\">
                                            <input type=\"checkbox\" name=\"operacao-#operacao-num#\" data-checked=\"#operacao-checked#\" value=\"#operacao-id#\">
                                            <label>#operacao-label#</label>
                                        </div>
                                    </div><!-- operacoes-items > -->
                                </div>
                            </div>
                        </div><!-- operacoes > -->
                    </div>
                    <!-- items > -->
                </div>
            </div>
        </div>
    </div>
</div>
<!-- grupo > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"fa4eb8342191da856f9bdfd0c4600189\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"fa4eb8342191da856f9bdfd0c4600189\"}',
            ],
            [
                'page_id' => 129,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Hospedeiro Perfis Admin Editar',
                'id' => 'usuarios-hospedeiro-perfis-admin-editar',
                'language' => 'pt-br',
                'path' => 'usuarios-hospedeiro-perfis-admin-editar/',
                'type' => 'sistema',
                'module' => 'usuarios-hospedeiro-perfis-admin',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\" value=\"#nome#\">
</div>
<div class=\"field\">
    <label>@[[form-default-label]]@</label>
    <div class=\"ui toggle checkbox\">
        <input type=\"checkbox\" name=\"padrao\" data-checked=\"#padrao-checked#\">
        <label>&nbsp;</label>
    </div>
</div>
<div class=\"ui dividing tiny header\">#modules-name#</div>
<!-- grupo < -->
<div class=\"ui segment\">
    <div class=\"ui grid\">
        <div class=\"column\">
            <div class=\"ui left floated small header\">#module-grup-name#</div>
            <button class=\"ui right floated button selectAll\" type=\"button\">
                <i class=\"check circle outline icon\"></i>#module-select-all# </button>
            <button class=\"ui right floated button unselectAll\" type=\"button\">
                <i class=\"circle outline icon\"></i> #module-unselect-all# </button>
        </div>
    </div>
    <div class=\"ui divided items\">
        <div class=\"item\">
            <div class=\"content\">
                <div class=\"ui cards\">
                    <!-- items < -->
                    <div class=\"ui raised card\">
                        <div class=\"content\">
                            <div class=\"ui small header\">#module-label#</div>
                            <div class=\"ui toggle checkbox\">
                                <input type=\"checkbox\" name=\"modulo-#num#\" data-checked=\"#checked#\" value=\"#id#\">
                                <label>&nbsp;</label>
                            </div>
                        </div>
                        <!-- operacoes < -->
                        <div class=\"extra content\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui tiny header\">#operacoes-nome#</div>
                                <div class=\"ui relaxed divided list\">
                                    <!-- operacoes-items < -->
                                    <div class=\"item\">
                                        <div class=\"ui checkbox\">
                                            <input type=\"checkbox\" name=\"operacao-#operacao-num#\" data-checked=\"#operacao-checked#\" value=\"#operacao-id#\">
                                            <label>#operacao-label#</label>
                                        </div>
                                    </div><!-- operacoes-items > -->
                                </div>
                            </div>
                        </div><!-- operacoes > -->
                    </div>
                    <!-- items > -->
                </div>
            </div>
        </div>
    </div>
</div>
<!-- grupo > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"cc8d9a2d0d8ae6b00523f6e5d50f3c55\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"cc8d9a2d0d8ae6b00523f6e5d50f3c55\"}',
            ],
            [
                'page_id' => 130,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Perfis',
                'id' => 'usuarios-perfis',
                'language' => 'pt-br',
                'path' => 'usuarios-perfis/',
                'type' => 'sistema',
                'module' => 'usuarios-perfis',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 131,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Perfis Adicionar',
                'id' => 'usuarios-perfis-adicionar',
                'language' => 'pt-br',
                'path' => 'usuarios-perfis-adicionar/',
                'type' => 'sistema',
                'module' => 'usuarios-perfis',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\">
</div>
<div class=\"field\">
    <label>@[[form-default-label]]@</label>
    <div class=\"ui toggle checkbox\">
        <input type=\"checkbox\" name=\"padrao\" data-checked=\"\">
        <label>&nbsp;</label>
    </div>
</div>
<div class=\"ui dividing tiny header\">#modules-name#</div>
<!-- grupo < -->
<div class=\"ui segment\">
    <div class=\"ui grid\">
        <div class=\"column\">
            <div class=\"ui left floated small header\">#module-grup-name#</div>
            <button class=\"ui right floated button selectAll\" type=\"button\">
                <i class=\"check circle outline icon\"></i>#module-select-all# </button>
            <button class=\"ui right floated button unselectAll\" type=\"button\">
                <i class=\"circle outline icon\"></i>#module-unselect-all# </button>
        </div>
    </div>
    <div class=\"ui divided items\">
        <div class=\"item\">
            <div class=\"content\">
                <div class=\"ui cards\">
                    <!-- items < -->
                    <div class=\"ui raised card\">
                        <div class=\"content\">
                            <div class=\"ui small header\">#module-label#</div>
                            <div class=\"ui toggle checkbox\">
                                <input type=\"checkbox\" name=\"modulo-#num#\" data-checked=\"#checked#\" value=\"#id#\">
                                <label>&nbsp;</label>
                            </div>
                        </div>
                        <!-- operacoes < -->
                        <div class=\"extra content\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui tiny header\">#operacoes-nome#</div>
                                <div class=\"ui relaxed divided list\">
                                    <!-- operacoes-items < -->
                                    <div class=\"item\">
                                        <div class=\"ui checkbox\">
                                            <input type=\"checkbox\" name=\"operacao-#operacao-num#\" data-checked=\"#operacao-checked#\" value=\"#operacao-id#\">
                                            <label>#operacao-label#</label>
                                        </div>
                                    </div><!-- operacoes-items > -->
                                </div>
                            </div>
                        </div><!-- operacoes > -->
                    </div>
                    <!-- items > -->
                </div>
            </div>
        </div>
    </div>
</div>
<!-- grupo > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"fa4eb8342191da856f9bdfd0c4600189\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"fa4eb8342191da856f9bdfd0c4600189\"}',
            ],
            [
                'page_id' => 132,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Perfis Editar',
                'id' => 'usuarios-perfis-editar',
                'language' => 'pt-br',
                'path' => 'usuarios-perfis-editar/',
                'type' => 'sistema',
                'module' => 'usuarios-perfis',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>#form-name-label#</label>
    <input type=\"text\" name=\"nome\" placeholder=\"#form-name-placeholder#\" value=\"#nome#\">
</div>
<div class=\"field\">
    <label>@[[form-default-label]]@</label>
    <div class=\"ui toggle checkbox\">
        <input type=\"checkbox\" name=\"padrao\" data-checked=\"#padrao-checked#\">
        <label>&nbsp;</label>
    </div>
</div>
<div class=\"ui dividing tiny header\">#modules-name#</div>
<!-- grupo < -->
<div class=\"ui segment\">
    <div class=\"ui grid\">
        <div class=\"column\">
            <div class=\"ui left floated small header\">#module-grup-name#</div>
            <button class=\"ui right floated button selectAll\" type=\"button\">
                <i class=\"check circle outline icon\"></i>#module-select-all# </button>
            <button class=\"ui right floated button unselectAll\" type=\"button\">
                <i class=\"circle outline icon\"></i> #module-unselect-all# </button>
        </div>
    </div>
    <div class=\"ui divided items\">
        <div class=\"item\">
            <div class=\"content\">
                <div class=\"ui cards\">
                    <!-- items < -->
                    <div class=\"ui raised card\">
                        <div class=\"content\">
                            <div class=\"ui small header\">#module-label#</div>
                            <div class=\"ui toggle checkbox\">
                                <input type=\"checkbox\" name=\"modulo-#num#\" data-checked=\"#checked#\" value=\"#id#\">
                                <label>&nbsp;</label>
                            </div>
                        </div>
                        <!-- operacoes < -->
                        <div class=\"extra content\">
                            <div class=\"ui basic segment\">
                                <div class=\"ui tiny header\">#operacoes-nome#</div>
                                <div class=\"ui relaxed divided list\">
                                    <!-- operacoes-items < -->
                                    <div class=\"item\">
                                        <div class=\"ui checkbox\">
                                            <input type=\"checkbox\" name=\"operacao-#operacao-num#\" data-checked=\"#operacao-checked#\" value=\"#operacao-id#\">
                                            <label>#operacao-label#</label>
                                        </div>
                                    </div><!-- operacoes-items > -->
                                </div>
                            </div>
                        </div><!-- operacoes > -->
                    </div>
                    <!-- items > -->
                </div>
            </div>
        </div>
    </div>
</div>
<!-- grupo > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"cc8d9a2d0d8ae6b00523f6e5d50f3c55\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"cc8d9a2d0d8ae6b00523f6e5d50f3c55\"}',
            ],
            [
                'page_id' => 133,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Planos',
                'id' => 'usuarios-planos',
                'language' => 'pt-br',
                'path' => 'usuarios-planos/',
                'type' => 'sistema',
                'module' => 'usuarios-planos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d41d8cd98f00b204e9800998ecf8427e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d41d8cd98f00b204e9800998ecf8427e\"}',
            ],
            [
                'page_id' => 134,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Planos Adicionar',
                'id' => 'usuarios-planos-adicionar',
                'language' => 'pt-br',
                'path' => 'usuarios-planos-adicionar/',
                'type' => 'sistema',
                'module' => 'usuarios-planos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>@[[form-name-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-placeholder]]@\">
</div>
<div class=\"field\">
    <label>@[[form-cpanel-plan-label]]@</label>
    <input type=\"text\" name=\"cpanel_plano\" placeholder=\"@[[form-cpanel-plan-placeholder]]@\">
</div>
<div class=\"field\">
    <label>@[[form-order-label]]@</label>
    <input type=\"text\" name=\"ordem\" placeholder=\"@[[form-order-placeholder]]@\">
</div>
<div class=\"field\">
    <label>@[[form-public-label]]@</label>
    <div class=\"ui toggle checkbox\">
        <input type=\"checkbox\" name=\"publico\" data-checked=\"#publico#\">
        <label>&nbsp;</label>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d622183df528a571b850d5d038eb9a23\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d622183df528a571b850d5d038eb9a23\"}',
            ],
            [
                'page_id' => 135,
                'user_id' => 1,
                'layout_id' => null,
                'name' => 'Usuarios Planos Editar',
                'id' => 'usuarios-planos-editar',
                'language' => 'pt-br',
                'path' => 'usuarios-planos-editar/',
                'type' => 'sistema',
                'module' => 'usuarios-planos',
                'option' => 'listar',
                'root' => 1,
                'no_permission' => null,
                'html' => '<div class=\"field\">
    <label>@[[form-name-label]]@</label>
    <input type=\"text\" name=\"nome\" placeholder=\"@[[form-name-placeholder]]@\" value=\"#nome#\">
</div>
<div class=\"field\">
    <label>@[[form-cpanel-plan-label]]@</label>
    <input type=\"text\" name=\"cpanel_plano\" placeholder=\"@[[form-cpanel-plan-placeholder]]@\" value=\"#cpanel_plano#\">
</div>
<div class=\"field\">
    <label>@[[form-order-label]]@</label>
    <input type=\"text\" name=\"ordem\" placeholder=\"@[[form-order-placeholder]]@\" value=\"#ordem#\">
</div>
<div class=\"field\">
    <label>@[[form-public-label]]@</label>
    <div class=\"ui toggle checkbox\">
        <input type=\"checkbox\" name=\"publico\" data-checked=\"#publico#\">
        <label>&nbsp;</label>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"a2da599fa466dd930de5584daf8c9067\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"a2da599fa466dd930de5584daf8c9067\"}',
            ],
        ];

        $table = $this->table('pages');
        $table->insert($data)->saveData();
    }
}
