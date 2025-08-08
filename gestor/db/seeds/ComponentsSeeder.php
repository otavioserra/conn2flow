<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class ComponentsSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'component_id' => 1,
                'user_id' => 1,
                'name' => 'Interface Formulário Configurações',
                'id' => 'interface-formulario-configuracoes',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<h1 class=\"ui header\">#titulo#</h1>
<!-- botoes < --><div class=\"ui basic right aligned segment\">
    #botoes#
</div><!-- botoes > -->
<form id=\"#form-id#\" action=\"#form-action#\" method=\"post\" name=\"#form-name#\" class=\"ui form interfaceFormPadrao\">
    <div id=\"_gestor-interface-config-dados\">#form-page#</div>
    <input id=\"_gestor-atualizar\" name=\"_gestor-atualizar\" type=\"hidden\" value=\"1\">
    <div class=\"ui error message\"></div>
    <!-- botao-editar < --><div class=\"ui center aligned basic segment\">
    	<button id=\"_gestor-interface-edit-button\" data-tooltip=\"#form-button-title#\" data-position=\"top center\" data-inverted=\"\" class=\"positive ui button\">#form-button-value#</button>
    </div><!-- botao-editar > -->
</form>
<!-- historico < --><table class=\"ui celled table\">
    <thead>
        <tr>
            <th>Histórico de Alterações</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>#historico#</td>
        </tr>
    </tbody>
</table><!-- historico > -->',
                'css' => '#_gestor-interface-config-dados{
    margin-bottom:15px;
}
.first-letter-uppercase::first-letter {
    text-transform: uppercase;
}
.segment > .button {
    margin-bottom: 0.75em;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"86075406b67435166d945cba5df56ff1\",\"css\":\"87e1823d50f899396806b9b566ac6185\",\"combined\":\"9ee2293b64b881f1d9ceb43df096744b\"}',
            ],
            [
                'component_id' => 2,
                'user_id' => 1,
                'name' => 'Widget Template',
                'id' => 'widget-template',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui fluid horizontal card _gestor-widgetTemplate-cont\" id=\"#cont-id#\">
    <div class=\"image fileImageParent\">
        <img>
        <div class=\"fileImage\">
            <img src=\"#img-src#\" class=\"widgetTemplate-image\">
        </div>
    </div>
    <div class=\"content\">
        <div class=\"ui small header widgetTemplate-nome\">#nome#</div>
        <div class=\"description\">
            <div class=\"ui list\">
                <div class=\"item widgetTemplate-data\">
                    <i class=\"calendar alternate outline icon\"></i>
                    #data#
                </div>
                <div class=\"item widgetTemplate-tipo\">
                    <i class=\"info circle icon\"></i>
                    #tipo#
                </div>
            </div>
            <div class=\"ui blue button _gestor-widgetTemplate-btn-change\" data-tooltip=\"@[[widget-template-button-change-tooltip]]@\" data-inverted=\"\">@[[widget-template-button-change]]@</div>
        </div>
    </div>
    <input type=\"hidden\" value=\"#template-id#\" name=\"#campo-nome#\" class=\"widgetTemplate-templateId\">
    <input type=\"hidden\" value=\"#template-tipo#\" name=\"#campo-tipo#\" class=\"widgetTemplate-templateTipo\">
</div>',
                'css' => '.fileImageParent{
    overflow:hidden;
    width:250px !important;
    height:188px;
}
.fileImage{
    position: absolute;
    top:0;
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
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"9d003fd56bb000e1c9525245cd5106c4\",\"css\":\"ba0557b0e23e65afcce0a8da039e4a9b\",\"combined\":\"8271f0187a3cdebbd8196eb8633b3884\"}',
            ],
            [
                'component_id' => 3,
                'user_id' => 1,
                'name' => 'Interface Formulário Edição Incomum',
                'id' => 'interface-formulario-edicao-incomum',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<h1 class=\"ui header\">#titulo#</h1>
<!-- botoes < --><div class=\"ui basic right aligned segment\">
    #botoes#
</div><!-- botoes > -->
<!-- meta-dados < --><table class=\"ui celled table\">
    <thead>
        <tr>
            <!-- cel-th < --><th>#meta-titulo#</th>
            <!-- cel-th > -->
        </tr>
    </thead>
    <tbody>
        <tr>
            <!-- cel-td < --><td>#meta-dado#</td>
            <!-- cel-td > -->
        </tr>
    </tbody>
</table><!-- meta-dados > -->
<form id=\"#form-id#\" action=\"#form-action#\" method=\"post\" name=\"#form-name#\" class=\"ui form interfaceFormPadrao\">
    <div id=\"_gestor-interface-edit-dados\">#form-page#</div>
    <!-- nao-alterar-id < --><div class=\"field\">
        <div class=\"ui checkbox\">
            <input type=\"checkbox\" name=\"_gestor-nao-alterar-id\" value=\"1\" checked>
            <label>#form-nao-alterar-id-label#</label>
        </div>
    </div><!-- nao-alterar-id > -->
    <input id=\"_gestor-atualizar\" name=\"_gestor-atualizar\" type=\"hidden\" value=\"1\">
    <input id=\"_gestor-variavel-id\" name=\"_gestor-registro-id\" type=\"hidden\" value=\"#form-registro-id#\">
    <div class=\"ui error message\"></div>
    <!-- botao-editar < --><div class=\"ui center aligned basic segment\">
    	<button id=\"_gestor-interface-edit-button\" data-tooltip=\"#form-button-title#\" data-position=\"top center\" data-inverted=\"\" class=\"positive ui button\">#form-button-value#</button>
    </div><!-- botao-editar > -->
</form>
<!-- historico < --><table class=\"ui celled table\">
    <thead>
        <tr>
            <th>Histórico de Alterações</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>#historico#</td>
        </tr>
    </tbody>
</table><!-- historico > -->',
                'css' => '#_gestor-interface-edit-dados{
    margin-bottom:15px;
}
.first-letter-uppercase::first-letter {
    text-transform: uppercase;
}
.segment > .button {
    margin-bottom: 0.75em;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"66d0b2b64a3b00263c2a8eaa193867a3\",\"css\":\"a1c884e20814045be6503eec311e42e4\",\"combined\":\"a51ad8061d307a4097e7362a6b4e7b53\"}',
            ],
            [
                'component_id' => 4,
                'user_id' => 1,
                'name' => 'Interface Formulário Inclusão Incomum',
                'id' => 'interface-formulario-inclusao-incomum',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<h1 class=\"ui header\">#titulo#</h1>
<!-- botoes < --><div class=\"ui basic right aligned segment\">
    #botoes#
</div><!-- botoes > -->
<form id=\"#form-id#\" action=\"#form-action#\" method=\"post\" name=\"#form-name#\" class=\"ui form interfaceFormPadrao\">
    <div id=\"_gestor-interface-insert-dados\">#form-page#</div>
    <input id=\"_gestor-adicionar\" name=\"_gestor-adicionar\" type=\"hidden\" value=\"1\">
    <input id=\"opcao\" name=\"opcao\" type=\"hidden\" value=\"#form-opcao#\">
    <div class=\"ui error message\"></div>
    <div class=\"ui center aligned basic segment\">
        <button id=\"_gestor-interface-insert-button\" data-tooltip=\"#form-button-title#\" data-position=\"right center\" data-inverted=\"\" class=\"positive ui button\">
            #form-button-value#
        </button>
    </div>
</form>',
                'css' => '#_gestor-interface-insert-dados{
    margin-bottom:15px;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"bea79dcb0fb62a2cabff2d757b8747f9\",\"css\":\"1141c974d1568502c3a4f3847cdb8951\",\"combined\":\"095bc1b128ef8f72c4de0d38d902e396\"}',
            ],
            [
                'component_id' => 5,
                'user_id' => 1,
                'name' => 'Padrao Pagina Mestre - Carrinho',
                'id' => 'padrao-pagina-mestre-carrinho',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui padded grid\">
        <div class=\"column\"></div>
        <div class=\"eight wide column\">
            <div class=\"row\">
                <div class=\"ui segment\">
                    <div class=\"excluir\">
                        <i class=\"big times circle icon\"></i>
                    </div>
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
                                            <i class=\"plus circle icon\"></i>
                                          </button>
                                    </div>
                                </div>
                                <div class=\"ui two column very relaxed grid\">
                                    <div class=\"column\">
                                        <h5 class=\"ui header\">
                                            R$100,00
                                        </h5>
                                    </div>
                                    <div class=\"right aligned column\">
                                        <div class=\"ui grid\">
                                            <div class=\"row\">
                                                <div class=\"column\"></div>
                                                <div class=\"fourteen wide column\">
                                                    <h5 class=\"ui header\">
                                                        1
                                                    </h5>
                                                </div>
                                                <div class=\"column\"></div>
                                            </div>
                                        </div>
                                       
                                    </div>
                                </div>
                                <div class=\"ui two column very relaxed grid\">
                                    <div class=\"column\">
                                        <h5 class=\"ui header\">
                                            Subtotal:R$100,00
                                        </h5>
                                    </div>
                                    <div class=\"right aligned column\">
                                        <button class=\"ui icon button\">
                                            <i class=\"minus circle icon\"></i>
                                          </button>
                                    </div>
                                </div>
                            </div>
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
                    <button class=\"fluid ui button\">Continuar comprando</button>
                </div>
            </div>
        </div>
        <div class=\"column\"></div>
    </div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"cb61b5d9fbff016cc1c43034f69f184d\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"cb61b5d9fbff016cc1c43034f69f184d\"}',
            ],
            [
                'component_id' => 6,
                'user_id' => 1,
                'name' => 'top-footer-teste',
                'id' => 'top-footer-teste',
                'language' => 'pt-br',
                'module' => null,
                'html' => ' <div class=\"colunatop\">
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
    </div>',
                'css' => ' .colunatop {
            background-color: #52606D;
        }
.colunabottom {
            background-color: #E4E7EB;
            position: relative;
            top: 90%;
        }',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"095d84451070fe018b376b5c53b950f5\",\"css\":\"56f4a9e69fe691229bea4e6af30543a7\",\"combined\":\"a207f2b6871d3a9ade4eb38b7507a889\"}',
            ],
            [
                'component_id' => 7,
                'user_id' => 1,
                'name' => 'Modal Simples',
                'id' => 'modal-simples',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui modal simples\">
    <div class=\"header\">#titulo#</div>
    <div class=\"content\">
        <p>#mensagem#</p>
    </div>
    <div class=\"actions\">
        <div class=\"ui approve right labeled icon button green\">
            OK
            <i class=\"check circle icon\"></i>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"2a5e8f5920ab09841f6deaa40c4da5b3\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"2a5e8f5920ab09841f6deaa40c4da5b3\"}',
            ],
            [
                'component_id' => 8,
                'user_id' => 1,
                'name' => 'Interface Formulário Visualização',
                'id' => 'interface-formulario-visualizacao',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<h1 class=\"ui header\">#titulo#</h1>
<!-- botoes < --><div class=\"ui basic right aligned segment\">
    #botoes#
</div><!-- botoes > -->
<!-- meta-dados < --><table class=\"ui celled table\">
    <thead>
        <tr>
            <!-- cel-th < --><th>#meta-titulo#</th>
            <!-- cel-th > -->
        </tr>
    </thead>
    <tbody>
        <tr>
            <!-- cel-td < --><td>#meta-dado#</td>
            <!-- cel-td > -->
        </tr>
    </tbody>
</table><!-- meta-dados > -->
<div id=\"_gestor-interface-visualizar-dados\">#page#</div>
<!-- historico < --><table class=\"ui celled table\">
    <thead>
        <tr>
            <th>Histórico de Alterações</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>#historico#</td>
        </tr>
    </tbody>
</table><!-- historico > -->',
                'css' => '#_gestor-interface-visualizar-dados{
    margin-bottom:15px;
}
.first-letter-uppercase::first-letter {
    text-transform: uppercase;
}
.segment > .button {
    margin-bottom: 0.75em;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"8093f6b4f62afda1a9defa36b885e615\",\"css\":\"e1507d655c9c10e868884f3333705a88\",\"combined\":\"b6b5fcc39fb612dbaa1ab3a88d9a16d3\"}',
            ],
            [
                'component_id' => 9,
                'user_id' => 1,
                'name' => 'Interface Modal Genérico',
                'id' => 'interface-modal-generico',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui tiny modal confirm\">
    <div class=\"ui header\">#titulo#</div>
    <div class=\"content\">
        <p>#mensagem#</p>
    </div>
    <div class=\"actions\">
        <div class=\"ui deny button\">
            #botao-cancelar#
        </div>
        <div class=\"ui approve right labeled icon button red\">
            #botao-confirmar#
            <i class=\"share icon\"></i>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"27a2d26f94e8a8d26703af830c9d59de\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"27a2d26f94e8a8d26703af830c9d59de\"}',
            ],
            [
                'component_id' => 10,
                'user_id' => 1,
                'name' => 'Botão Superior Interface',
                'id' => 'botao-superior-interface',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<a class=\"ui button #cor#\" href=\"#url#\" data-content=\"#tooltip#\" data-id=\"adicionar\">
    <i class=\"#icon# icon\"></i>
    #label#
</a>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"17898f8cf079921914072cfa54971eb7\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"17898f8cf079921914072cfa54971eb7\"}',
            ],
            [
                'component_id' => 11,
                'user_id' => 1,
                'name' => 'Teste de Edição',
                'id' => 'teste-de-edicao',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<button class=\"ui button\">
  Botão
</button>',
                'css' => '#id{
	width:100%;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"99f3e3849ff34f23b98905e9679469c7\",\"css\":\"85fe0d62415cde276950dc711d8a79b0\",\"combined\":\"36a4c7767c980e0696784aec1491462e\"}',
            ],
            [
                'component_id' => 12,
                'user_id' => 1,
                'name' => 'Teste de Adição',
                'id' => 'teste-de-adicao',
                'language' => 'pt-br',
                'module' => null,
                'html' => 'dsasda',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ae97a3162c8302f195dd6f25ee1f68e7\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ae97a3162c8302f195dd6f25ee1f68e7\"}',
            ],
            [
                'component_id' => 13,
                'user_id' => 1,
                'name' => 'Teste nova Página',
                'id' => 'teste-nova-pagina',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<p>Ok</p>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d158caa2d30192b82c3890f47a1ff43e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d158caa2d30192b82c3890f47a1ff43e\"}',
            ],
            [
                'component_id' => 14,
                'user_id' => 1,
                'name' => 'Teste Novo Adição',
                'id' => 'teste-novo-adicao',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div>Olá enfermeira 2</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"819f609d0654dd34d0d1e5a446d6a60d\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"819f609d0654dd34d0d1e5a446d6a60d\"}',
            ],
            [
                'component_id' => 15,
                'user_id' => 1,
                'name' => 'Interface Alerta Modal',
                'id' => 'interface-alerta-modal',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui modal alerta\">
    <div class=\"header\">#titulo#</div>
    <div class=\"content\">
        <p>#mensagem#</p>
    </div>
    <div class=\"actions\">
        <div class=\"ui approve right labeled icon button green\">
            #botao-ok#
            <i class=\"check circle icon\"></i>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d89b05f7e9b207dd68d220ac34139054\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d89b05f7e9b207dd68d220ac34139054\"}',
            ],
            [
                'component_id' => 16,
                'user_id' => 1,
                'name' => 'Interface Carregando Modal',
                'id' => 'interface-carregando-modal',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui basic modal carregando\">
    <div class=\"ui active centered inline fast large loader\"></div>
    <div class=\"ui medium center aligned header\">Carregando</div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"c1574712e986563972da0ccb3938846a\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"c1574712e986563972da0ccb3938846a\"}',
            ],
            [
                'component_id' => 17,
                'user_id' => 1,
                'name' => 'Teste Adição Novo',
                'id' => 'teste-adicao-novo',
                'language' => 'pt-br',
                'module' => null,
                'html' => 'html mudou',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"52acf8327b36cb888c286e23b12bc4c4\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"52acf8327b36cb888c286e23b12bc4c4\"}',
            ],
            [
                'component_id' => 18,
                'user_id' => 1,
                'name' => 'Interface Deleção Modal',
                'id' => 'interface-delecao-modal',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui mini modal confirm\">
    <div class=\"ui header\">#titulo#</div>
    <div class=\"content\">
        <p>#mensagem#</p>
    </div>
    <div class=\"actions\">
        <div class=\"ui deny button\">
            #botao-cancelar#
        </div>
        <div class=\"ui approve right labeled icon button red\">
            #botao-confirmar#
            <i class=\"share icon\"></i>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"3b93f234f2a307b5c30157ff1514cb05\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"3b93f234f2a307b5c30157ff1514cb05\"}',
            ],
            [
                'component_id' => 19,
                'user_id' => 1,
                'name' => 'Teste Histórico',
                'id' => 'teste-historico',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui grid padded\">
    <div class=\"four wide column red\">Teste</div>
    <div class=\"four wide column blue\"></div>
    <div class=\"four wide column yellow\"></div>
    <div class=\"four wide column pink\"></div>
</div>',
                'css' => '#teste{
    width:100%;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ad86896c1d40921d37003331977b7fef\",\"css\":\"a4bb66d2c12af9389f505db21fd11925\",\"combined\":\"3e4b658f8e51e05713bb2ee5672fa37c\"}',
            ],
            [
                'component_id' => 20,
                'user_id' => 1,
                'name' => 'Teste de Adição Denovo',
                'id' => 'teste-de-adicao-denovo-0',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div>Teste 2</div>',
                'css' => '#teste{
    width:100%;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"0b89e3ead925b789aadf4d2a8fddbe2c\",\"css\":\"a4bb66d2c12af9389f505db21fd11925\",\"combined\":\"a4d3c36eab12adf8f12e6126dcc5ddb3\"}',
            ],
            [
                'component_id' => 21,
                'user_id' => 1,
                'name' => 'Interface Formulário Inclusão',
                'id' => 'interface-formulario-inclusao',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<h1 class=\"ui header\">#titulo#</h1>
<!-- botoes < --><div class=\"ui basic right aligned segment\">
    #botoes#
</div><!-- botoes > -->
<form id=\"#form-id#\" action=\"#form-action#\" method=\"post\" name=\"#form-name#\" class=\"ui form interfaceFormPadrao\">
    <div id=\"_gestor-interface-insert-dados\">#form-page#</div>
    <input id=\"_gestor-adicionar\" name=\"_gestor-adicionar\" type=\"hidden\" value=\"1\">
    <input id=\"opcao\" name=\"opcao\" type=\"hidden\" value=\"adicionar\">
    <div class=\"ui error message\"></div>
    <div class=\"ui center aligned basic segment\">
        <button id=\"_gestor-interface-insert-button\" data-tooltip=\"#form-button-title#\" data-position=\"right center\" data-inverted=\"\" class=\"positive ui button\">
            #form-button-value#
        </button>
    </div>
</form>',
                'css' => '#_gestor-interface-insert-dados{
    margin-bottom:15px;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"a4573ded54ad96a83a9c9021594bf08c\",\"css\":\"1141c974d1568502c3a4f3847cdb8951\",\"combined\":\"40558373f2ffe63ea8b1a0d932b88520\"}',
            ],
            [
                'component_id' => 22,
                'user_id' => 1,
                'name' => 'Interface Listar Sem Registros',
                'id' => 'interface-listar-sem-registros',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui icon positive message\">
    <i class=\"inbox icon\"></i>
    <div class=\"content\">
        <div class=\"header\">
            Não há registros cadastrados neste módulo.
        </div>
        <p>Se disponível, basta selecionar o botão adicionar acima para criar um registro.</p>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"7f4c7b58497756f22ea6245dbdecef88\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"7f4c7b58497756f22ea6245dbdecef88\"}',
            ],
            [
                'component_id' => 23,
                'user_id' => 1,
                'name' => 'Interface Listar',
                'id' => 'interface-listar',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<h1 class=\"ui header\">#titulo#</h1>
<!-- botoes < --><div class=\"ui basic right aligned segment\">
    #botoes#
</div><!-- botoes > -->
<div id=\"_gestor-interface-listar\" class=\"ui segment\">
    <div class=\"ui stackable one column grid\">
        <div class=\"column\" id=\"_gestor-interface-listar-column\">
            #lista#
        </div>
    </div>
</div>',
                'css' => '.dt-head-center {
    text-align: center !important;
}
.table-responsive-fix{
    overflow:hidden;
}
.segment > .button {
    margin-bottom: 0.75em;
}
#_gestor-interface-listar{
	margin-bottom: 1em;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"7f31481d3494f8a3e043663b21445f04\",\"css\":\"3609fbb0f5be09748ba6450ad493bd5e\",\"combined\":\"e67b30d21e1d4c398c9804340036c801\"}',
            ],
            [
                'component_id' => 24,
                'user_id' => 1,
                'name' => 'Interface Listar Arquivos Sem Registros',
                'id' => 'interface-listar-arquivos-sem-registros',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui icon positive message\">
    <i class=\"inbox icon\"></i>
    <div class=\"content\">
        <div class=\"header\">
            Não há registros cadastrados para esta filtragem.
        </div>
        <p>Favor tentar outra(s) opção(ões).</p>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d7133d08f38fbed13fc78114ce5580e6\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d7133d08f38fbed13fc78114ce5580e6\"}',
            ],
            [
                'component_id' => 25,
                'user_id' => 1,
                'name' => 'Layout menutop-teste',
                'id' => 'menutop-teste',
                'language' => 'pt-br',
                'module' => null,
                'html' => '  <div class=\"colunatop\">
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
    </div>',
                'css' => ' .colunatop {
            background-color: #52606D;
        }
.colunabottom {
            background-color: #E4E7EB;
            position: relative;
            top: 90%;
        }',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d0519b4719c473b83f6b13d03ab6a7e4\",\"css\":\"56f4a9e69fe691229bea4e6af30543a7\",\"combined\":\"b85cbf764a381ce1f69ce5b482588fec\"}',
            ],
            [
                'component_id' => 26,
                'user_id' => 1,
                'name' => 'Página Mestre - Página Serviço Especial Natal',
                'id' => 'pagina-mestre-pagina-servico-especial-natal',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<p>Página Servico Especial Natal</p>',
                'css' => 'div{
	width:100%;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"8fed0c7c7c60691eb6b8ae2ec480f82b\",\"css\":\"17e9a0b0318f7a53c4645773203b4f8f\",\"combined\":\"30a064776f3446bdb509d9cd3ede27ed\"}',
            ],
            [
                'component_id' => 27,
                'user_id' => 1,
                'name' => 'Interface Iframe Modal',
                'id' => 'interface-iframe-modal',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui overlay fullscreen modal iframePagina\">
    <i class=\"close icon\"></i>
    <div class=\"header\">Head</div>
    <div class=\"content\">
        <div class=\"iframe-container\">
        	<iframe></iframe>
        </div>
    </div>
    <div class=\"actions\">
        <div class=\"ui cancel button\">Cancel</div>
    </div>
    <div class=\"ui dimmer\">
        <div class=\"ui large loader\"></div>
    </div>
</div>',
                'css' => '.iframe-container{
    position: absolute;
    width:calc(100% - 2rem);
    height:calc(100% - 12rem);
}
.iframe-container iframe{
    position: absolute;
    height: 100%;
    width: 100%;
    border:none;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"0eeeae1b953bccc0d26a0f1cc335803a\",\"css\":\"0745e249a8e1258353142b9bcb1a2438\",\"combined\":\"be737048e45d0347399310fc86221b63\"}',
            ],
            [
                'component_id' => 28,
                'user_id' => 1,
                'name' => 'Widget Imagem',
                'id' => 'widget-imagem',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui fluid horizontal card _gestor-widgetImage-cont\" id=\"#cont-id#\">
    <div class=\"image fileImageParent\">
        <img>
        <div class=\"fileImage\">
            <img src=\"#img-src#\" class=\"widgetImage-image\">
        </div>
    </div>
    <div class=\"content\">
        <div class=\"ui small header widgetImage-nome\">#nome#</div>
        <div class=\"description\">
            <div class=\"ui list\">
                <div class=\"item widgetImage-data\">
                    <i class=\"calendar alternate outline icon\"></i>
                    #data#
                </div>
                <div class=\"item widgetImage-tipo\">
                    <i class=\"info circle icon\"></i>
                    #tipo#
                </div>
            </div>
            <div class=\"ui blue button _gestor-widgetImage-btn-add\" data-tooltip=\"@[[widget-image-button-add-tooltip]]@\" data-inverted=\"\">@[[widget-image-button-add]]@</div>
            <div class=\"ui red button _gestor-widgetImage-btn-del\" data-tooltip=\"@[[widget-image-button-del-tooltip]]@\" data-inverted=\"\">@[[widget-image-button-del]]@</div>
        </div>
    </div>
    <input type=\"hidden\" value=\"#file-id#\" name=\"#campo-nome#\">
</div>',
                'css' => '.fileImageParent{
    overflow:hidden;
    width:250px !important;
    height:188px;
}
.fileImage{
    position: absolute;
    top:0;
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
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"53ae2df5b19777bbe6b8ab98529a80d5\",\"css\":\"ba0557b0e23e65afcce0a8da039e4a9b\",\"combined\":\"c8b5857c48f0eb1122ca4efef54ccb5f\"}',
            ],
            [
                'component_id' => 29,
                'user_id' => 1,
                'name' => 'Página Mestre Conteúdos Padrão',
                'id' => 'pagina-mestre-conteudos-padrao',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div>Testes</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"0a84799d2500b40dfd9074c43fd9d5dc\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"0a84799d2500b40dfd9074c43fd9d5dc\"}',
            ],
            [
                'component_id' => 30,
                'user_id' => 1,
                'name' => 'Teste Componente',
                'id' => 'teste-componente',
                'language' => 'pt-br',
                'module' => null,
                'html' => 'Teste 2',
                'css' => 'teste{
	width:100px;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"1266231e9976b3fb8e440b87a83692c6\",\"css\":\"b0c2fa48692c7aa07b229741130b7253\",\"combined\":\"6e9263e410dea18f635d1ba114f3f407\"}',
            ],
            [
                'component_id' => 31,
                'user_id' => 1,
                'name' => 'Menu Principal Sistema',
                'id' => 'menu-principal-sistema',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<!-- conteiner < -->
<div class=\"ui basic segment menuConteiner\">
    <div class=\"ui list\">
        <!-- itemContCel < --><div class=\"item\">
        #itemCont#
        </div><!-- itemContCel > -->
    </div>
</div><!-- conteiner > -->
<!-- categoria < -->
<div class=\"ui small header\">#categoria-nome#</div>
<div class=\"ui vertical fluid menu\">
    <!-- itemMenu -->
</div><!-- categoria > -->
<!-- simples < -->
<div class=\"ui vertical fluid menu\">
    <!-- itemMenu -->
</div><!-- simples > -->
<!-- item < --><a class=\"item#class#\" href=\"#link#\">
    <div class=\"ui tiny header\">
        <!-- icon < --><i class=\"#icon# icon\"></i><!-- icon > -->
        <!-- icon-2 < --><i class=\"large icons\">
        <i class=\"#icon# icon\"></i>
        <i class=\"#icon-2# icon\"></i>
        </i><!-- icon-2 > -->
        <div class=\"content ajusteItem\">
            #nome#
        </div>
    </div>
</a><!-- item > -->',
                'css' => '.ajusteItem{
    padding-left:10px !important;
}
#entrey-menu-principal-close{
    position:absolute;
    top:10px;
    right:0px;
    z-index:1;
    cursor:pointer;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"708d6c3eab26e3bd0473f87bac06d8ff\",\"css\":\"dc8ada26a5c0c9580e7a0e75648eae47\",\"combined\":\"fee5ddcf40f2110aa7ae0bed854105ea\"}',
            ],
            [
                'component_id' => 32,
                'user_id' => 1,
                'name' => 'Interface Formulário Edição',
                'id' => 'interface-formulario-edicao',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<h1 class=\"ui header\">#titulo#</h1>
<!-- botoes < --><div class=\"ui basic right aligned segment\">
    #botoes#
</div><!-- botoes > -->
<!-- meta-dados < --><table class=\"ui celled table\">
    <thead>
        <tr>
            <!-- cel-th < --><th>#meta-titulo#</th>
            <!-- cel-th > -->
        </tr>
    </thead>
    <tbody>
        <tr>
            <!-- cel-td < --><td>#meta-dado#</td>
            <!-- cel-td > -->
        </tr>
    </tbody>
</table><!-- meta-dados > -->
<form id=\"#form-id#\" action=\"#form-action#\" method=\"post\" name=\"#form-name#\" class=\"ui form interfaceFormPadrao\">
    <div id=\"_gestor-interface-edit-dados\">#form-page#</div>
    <!-- nao-alterar-id < --><div class=\"field\">
        <div class=\"ui checkbox\">
            <input type=\"checkbox\" name=\"_gestor-nao-alterar-id\" value=\"1\" checked>
            <label>#form-nao-alterar-id-label#</label>
        </div>
    </div><!-- nao-alterar-id > -->
    <input id=\"_gestor-atualizar\" name=\"_gestor-atualizar\" type=\"hidden\" value=\"1\">
    <input id=\"_gestor-variavel-id\" name=\"_gestor-registro-id\" type=\"hidden\" value=\"#form-registro-id#\">
    <div class=\"ui error message\"></div>
    <!-- botao-editar < --><div class=\"ui center aligned basic segment\">
    	<button id=\"_gestor-interface-edit-button\" data-tooltip=\"#form-button-title#\" data-position=\"top center\" data-inverted=\"\" class=\"positive ui button\">#form-button-value#</button>
    </div><!-- botao-editar > -->
</form>
<!-- historico < --><table class=\"ui celled table\">
    <thead>
        <tr>
            <th>Histórico de Alterações</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>#historico#</td>
        </tr>
    </tbody>
</table><!-- historico > -->',
                'css' => '#_gestor-interface-edit-dados{
    margin-bottom:15px;
}
.first-letter-uppercase::first-letter {
    text-transform: uppercase;
}
.segment > .button {
    margin-bottom: 0.75em;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"66d0b2b64a3b00263c2a8eaa193867a3\",\"css\":\"a1c884e20814045be6503eec311e42e4\",\"combined\":\"a51ad8061d307a4097e7362a6b4e7b53\"}',
            ],
            [
                'component_id' => 33,
                'user_id' => 1,
                'name' => 'Interface Formulário Autorização Provisória',
                'id' => 'interface-formulario-autorizacao-provisoria',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui tiny modal autorizacaoProvisoria\">
    <div class=\"ui header\">#titulo#</div>
    <div class=\"content\">
        <p>#mensagem#</p>
    </div>
    <div class=\"actions\">
        <a class=\"ui cancel button\" href=\"#botao-cancelar-url#\">
            #botao-cancelar#
        </a>
        <a class=\"ui approve right labeled icon button blue\" href=\"#botao-confirmar-url#\">
            #botao-confirmar#
            <i class=\"lock icon\"></i>
        </a>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"59811a7d383533a272e2a9829498b948\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"59811a7d383533a272e2a9829498b948\"}',
            ],
            [
                'component_id' => 34,
                'user_id' => 1,
                'name' => 'Interface Backup Dropdown',
                'id' => 'interface-backup-dropdown',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<label class=\"ui tiny header\">Backup: </label>
<div class=\"ui selection dropdown backupDropdown\" data-id=\"#id-numerico#\" data-campo=\"#campo#\" data-campo-form=\"#campo_form#\" data-callback=\"#callback#\">
    <i class=\"dropdown icon\"></i>
    <div class=\"default text\">
    	<i class=\"#versao-atual-icon# icon\"></i>
    	#versao-atual-label#
        <span class=\"description\">#versao-atual-description#</span>
    </div>
    <div class=\"menu\">
        <!-- item < --><div class=\"item\" data-value=\"#id#\">
        	<i class=\"#icon# icon\"></i>
            #data#
            <span class=\"description\">#versao#</span>
        </div><!-- item > -->
    </div>
</div>
<div class=\"ui hidden divider\"></div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"f771df80f24f82a024044243859cb8c6\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"f771df80f24f82a024044243859cb8c6\"}',
            ],
            [
                'component_id' => 35,
                'user_id' => 1,
                'name' => 'Configuração - Campos',
                'id' => 'configuracao-campos',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<!-- string < --><input type=\"text\" name=\"valor-#value-num#\" placeholder=\"@[[form-value-placeholder]]@\" value=\"#value-valor#\" class=\"campo string\" /><!-- string > -->
<!-- text < --><textarea name=\"valor-#value-num#\" placeholder=\"@[[form-value-placeholder]]@\" class=\"campo text\">#value-valor#</textarea><!-- text > -->
<!-- number < --><input type=\"number\" name=\"valor-#value-num#\" placeholder=\"@[[form-value-placeholder]]@\" value=\"#value-valor#\" class=\"campo number\" /><!-- number > -->
<!-- quantidade < --><input type=\"text\" name=\"valor-#value-num#\" placeholder=\"@[[form-value-placeholder]]@\" value=\"#value-valor#\" class=\"campo quantidade\" /><!-- quantidade > -->
<!-- dinheiro < --><input type=\"text\" name=\"valor-#value-num#\" placeholder=\"@[[form-value-placeholder]]@\" value=\"#value-valor#\" class=\"campo dinheiro\" /><!-- dinheiro > -->
<!-- bool < --><div class=\"ui toggle checkbox campo bool\">
    <input type=\"checkbox\" name=\"valor-#value-num#\" value=\"1\" checked>
    <label></label>
</div><!-- bool > -->
<!-- tinymce < --><textarea name=\"valor-#value-num#\" placeholder=\"@[[form-value-placeholder]]@\" class=\"campo tinymce\">#value-valor#</textarea><!-- tinymce > -->
<!-- css < --><textarea name=\"valor-#value-num#\" placeholder=\"@[[form-value-placeholder]]@\" class=\"campo css\">#value-valor#</textarea><!-- css > -->
<!-- js < --><textarea name=\"valor-#value-num#\" placeholder=\"@[[form-value-placeholder]]@\" class=\"campo js\">#value-valor#</textarea><!-- js > -->
<!-- html < --><textarea name=\"valor-#value-num#\" placeholder=\"@[[form-value-placeholder]]@\" class=\"campo html\">#value-valor#</textarea><!-- html > -->
<!-- datas-multiplas < -->
<div class=\"ui existing segment calendar-multiple campo datas-multiplas\">
    <div class=\"ui calendar-dates\"></div>
    <div class=\"ui calendar multiplo\"></div>
    <input type=\"hidden\" name=\"valor-#value-num#\"  value=\"#value-valor#\" class=\"calendar-dates-input\">
</div>
<!-- datas-multiplas > -->
<!-- data-hora < -->
<div class=\"ui calendar campo data-hora\">
    <div class=\"ui input left icon\">
        <i class=\"calendar icon\"></i>
        <input type=\"text\" name=\"valor-#value-num#\" placeholder=\"Data/Hora\" value=\"#value-valor#\" class=\"calendarInput\" autocomplete=\"off\">
    </div>
</div>
<!-- data-hora > -->
<!-- data < -->
<div class=\"ui calendar campo data\">
    <div class=\"ui input left icon\">
        <i class=\"calendar icon\"></i>
        <input type=\"text\" name=\"valor-#value-num#\" placeholder=\"Data\" value=\"#value-valor#\" class=\"calendarInput\" autocomplete=\"off\">
    </div>
</div>
<!-- data > -->',
                'css' => '.calendar-dates{
	margin-bottom: 0.14285714rem;
}
.calendar-dates .label{
	margin: 0.14285714rem 0.28571429rem 0.14285714rem 0;
}
.noselect {
  -webkit-touch-callout: none; /* iOS Safari */
    -webkit-user-select: none; /* Safari */
     -khtml-user-select: none; /* Konqueror HTML */
       -moz-user-select: none; /* Old versions of Firefox */
        -ms-user-select: none; /* Internet Explorer/Edge */
            user-select: none; /* Non-prefixed version, currently
                                  supported by Chrome, Edge, Opera and Firefox */
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"4a31ee642af5a1f3a198bdd44380d201\",\"css\":\"4183f8ca0236bdb2a6b7d5fd983eba86\",\"combined\":\"94726b42f4fc448c6d394ee3efe4f0e5\"}',
            ],
            [
                'component_id' => 36,
                'user_id' => 1,
                'name' => 'Configuração - Widget',
                'id' => 'configuracao-widget',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui grid\">
    <div class=\"column\">
        <div class=\"ui button variavelBtnAdicionar\">
            <i class=\"add icon\"></i>
            @[[variavel-add-btn]]@
        </div>
    </div>
</div>
<div class=\"ui divider\"></div>
<div class=\"ui two doubling cards variaveisCont\" id=\"_gestor-configuracao-administracao\">
    <!-- item < --><div class=\"card variavelCont\" data-id=\"#variavelID#\" data-num=\"#variavelNum#\" data-tipo=\"#variavelTipo#\">
        <div class=\"content\">
            <div class=\"ui circular red icon tiny right floated button variavelBtnExcluir\">
                <i class=\"icon trash alternate outline\"></i>
            </div>
            <div class=\"ui circular blue icon tiny right floated button variavelBtnEditar\">
                <i class=\"icon edit outline\"></i>
            </div>
            <div class=\"header\">
                <span class=\"ui text teal variavelNome\">#variavelNome#</span>
            </div>
            <div class=\"description\">
                <p class=\"variavelDescricaoCont\">
                    <i class=\"icon blue info circle\"></i>
                    <span class=\"variavelDescricao\">#variavelDescricao#</span>
                </p>
                <span class=\"variavelGrupoCont\">
                	<a class=\"ui ribbon teal label variavelGrupo\">#variavelGrupo#</a>
                </span>
                <div class=\"ui fitted basic segment variavelValor\">
                    #variavelValor#
                </div>
            </div>
            <input name=\"ref-#ref-num#\" type=\"hidden\" value=\"#ref-valor#\" />
        </div>
    </div><!-- item > -->
</div>
<div class=\"componenteAdicionarBaixo\">
	<div class=\"ui divider\"></div>
    <div class=\"ui grid\">
        <div class=\"column\">
            <div class=\"ui button variavelBtnAdicionarAbaixo\">
                <i class=\"add icon\"></i>
                @[[variavel-add-btn]]@
            </div>
        </div>
    </div>
</div>
<div class=\"camposModelos escondido\">#campos-modelos#</div>
<input id=\"variaveis-total\" name=\"variaveis-total\" type=\"hidden\" value=\"#variaveis-total#\" />
<div class=\"ui two doubling cards modeloItens\">
    <div class=\"card adicionar\">
        <div class=\"content\">
            <div class=\"ui circular red icon tiny right floated button adicionarBtnCancelar\">
                <i class=\"icon times\"></i>
            </div>
            <div class=\"header\">
                <span class=\"ui text blue\">Adicionar Variável</span>
            </div>
            <div class=\"description\">
                <p>
                    <i class=\"icon blue info circle\"></i>
                    Preencha os campos abaixo e clique no botão Enviar.
                </p>
                <div class=\"two fields\">
                    <div class=\"field\">
                        <label>@[[variavel-id-label]]@</label>
                        <input type=\"text\" name=\"id-#id-num#\" placeholder=\"@[[variavel-id-placeholder]]@\" class=\"identificador\" data-validate=\"ids-obrigatorios\" maxlength=\"255\" />
                    </div>
                    <div class=\"field\">
                        <label>@[[variavel-group-label]]@</label>
                        <input type=\"text\" name=\"grupo-#grupo-num#\" placeholder=\"@[[variavel-group-placeholder]]@\" class=\"grupo\" maxlength=\"255\" />
                    </div>
                </div>
                <div class=\"field\">
                    <label>@[[variavel-description-label]]@</label>
                    <input type=\"text\" name=\"descricao-#descricao-num#\" placeholder=\"@[[variavel-description-placeholder]]@\" class=\"descricao\" maxlength=\"255\" />
                </div>
                <div class=\"field\">
                    <label>@[[variavel-tipo-label]]@</label>
                    <span>#select-tipo#</span>
                </div>
                <div class=\"field\">
                    <label>@[[variavel-valor-label]]@</label>
                    <span class=\"variavelValor\">#variavelValor#</span>
                </div>
            </div>
        </div>
    </div>
    <div class=\"card editar\">
        <div class=\"content\">
            <div class=\"ui circular red icon tiny right floated button editarBtnCancelar\">
                <i class=\"icon times\"></i>
            </div>
            <div class=\"header\">
                <span class=\"ui text yellow\">Editar Variável</span>
            </div>
            <div class=\"description\">
                <p>
                    <i class=\"icon yellow info circle\"></i>
                    Altere os campos abaixo e clique no botão Enviar.
                </p>
                <div class=\"two fields\">
                    <div class=\"field\">
                        <label>@[[variavel-id-label]]@</label>
                        <input type=\"text\" name=\"id-#id-num#\" placeholder=\"@[[variavel-id-placeholder]]@\" value=\"#id-valor#\" class=\"identificador\" data-validate=\"ids-obrigatorios\" maxlength=\"255\" />
                    </div>
                    <div class=\"field\">
                        <label>@[[variavel-group-label]]@</label>
                        <input type=\"text\" name=\"grupo-#grupo-num#\" placeholder=\"@[[variavel-group-placeholder]]@\" value=\"#grupo-valor#\" class=\"grupo\" maxlength=\"255\" />
                    </div>
                </div>
                <div class=\"field\">
                    <label>@[[variavel-description-label]]@</label>
                    <input type=\"text\" name=\"descricao-#descricao-num#\" placeholder=\"@[[variavel-description-placeholder]]@\" value=\"#descricao-valor#\" class=\"descricao\" maxlength=\"255\" />
                </div>
                <div class=\"field\">
                    <label>@[[variavel-tipo-label]]@</label>
                    <span>#select-tipo#</span>
                </div>
                <div class=\"field\">
                    <label>@[[variavel-valor-label]]@</label>
                    <span class=\"variavelValor\">#variavelValor#</span>
                </div>
            </div>
            <input name=\"ref-#ref-num#\" type=\"hidden\" value=\"#ref-valor#\" class=\"variavelReferencia\" />
        </div>
    </div>
</div>',
                'css' => '.escondido{
	display:none;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"20d42343ba08a767a53e0b622cc8579f\",\"css\":\"e76c325ef114d06f7eaa79aaa025fe38\",\"combined\":\"d6c04b6680ab2488c3d36fc1cc045ba8\"}',
            ],
            [
                'component_id' => 37,
                'user_id' => 1,
                'name' => 'Configuração Hosts - Widget',
                'id' => 'configuracao-hosts-widget',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui divider\"></div>
<div class=\"ui two doubling cards variaveisCont\" id=\"_gestor-configuracao-hosts\">
    <!-- item < --><div class=\"card variavelCont\" data-id=\"#variavelID#\" data-num=\"#variavelNum#\" data-tipo=\"#variavelTipo#\">
        <div class=\"content\">
            <div class=\"ui circular blue icon tiny right floated button variavelValorBTN\" data-content=\"@[[default-value-change]]@\" data-variation=\"inverted\">
                <i class=\"icon undo\"></i>
            </div>
            <div class=\"header\">
                <span class=\"ui text teal variavelNome\">#variavelNome#</span>
            </div>
            <div class=\"description\">
                <p class=\"variavelDescricaoCont\">
                    <i class=\"icon blue info circle\"></i>
                    <span class=\"variavelDescricao\">#variavelDescricao#</span>
                </p>
                <span class=\"variavelGrupoCont\">
                	<a class=\"ui ribbon teal label variavelGrupo\">#variavelGrupo#</a>
                </span>
                <div class=\"ui fitted basic segment variavelValor\">
                    #variavelValor#
                </div>
            </div>
            <input name=\"ref-#ref-num#\" type=\"hidden\" value=\"#ref-valor#\" />
            <input name=\"ref-host-#ref-num#\" type=\"hidden\" value=\"#ref-host-valor#\" />
            <div class=\"valorPadrao escondido\">#valorPadrao#</div>
        </div>
    </div><!-- item > -->
</div>
<div class=\"ui divider\"></div>
<input id=\"variaveis-total\" name=\"variaveis-total\" type=\"hidden\" value=\"#variaveis-total#\" />',
                'css' => '.escondido{
	display:none;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"cc02b9dd067c1a6219ed830fd2c2c3a7\",\"css\":\"e76c325ef114d06f7eaa79aaa025fe38\",\"combined\":\"d31e6131bff9a9e00c02c16c064e408b\"}',
            ],
            [
                'component_id' => 38,
                'user_id' => 1,
                'name' => 'Interface Simples',
                'id' => 'interface-simples',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<h1 class=\"ui header\">#titulo#</h1>
<!-- botoes < --><div class=\"ui basic right aligned segment\">
    #botoes#
</div><!-- botoes > -->
<div id=\"_gestor-interface-simples\">#form-page#</div>
<!-- historico < --><table class=\"ui celled table\">
    <thead>
        <tr>
            <th>Histórico de Alterações</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>#historico#</td>
        </tr>
    </tbody>
</table><!-- historico > -->',
                'css' => '#_gestor-interface-config-dados{
    margin-bottom:15px;
}
.first-letter-uppercase::first-letter {
    text-transform: uppercase;
}
.segment > .button {
    margin-bottom: 0.75em;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"b323c939a51b60054f34b0b5ba58c414\",\"css\":\"87e1823d50f899396806b9b566ac6185\",\"combined\":\"9ef891f70868d898d23757590acb5307\"}',
            ],
            [
                'component_id' => 39,
                'user_id' => 1,
                'name' => 'Impressão Sem Dados',
                'id' => 'impressao-sem-dados',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div class=\"ui segment\">
    <div class=\"ui icon error message\">
        <i class=\"inbox icon\"></i>
        <div class=\"content\">
            <div class=\"header\">
                Impressão Erro
            </div>
            <p>A fila de impressão está vazia. Ou por algum erro sistêmico ou por tentativa de acessar essa página diretamente.</p>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"b5f8004dc420d0a6792e77a8738e11df\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"b5f8004dc420d0a6792e77a8738e11df\"}',
            ],
            [
                'component_id' => 40,
                'user_id' => 1,
                'name' => 'Admin Categorias Categorias Filho Info',
                'id' => 'admin-categorias-categorias-filho-info',
                'language' => 'pt-br',
                'module' => 'admin-categorias',
                'html' => '<div class=\"ui info visible message\">
    #message#
</div>
<!-- Teste módulo 2025-08-07 21:37:46 -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"2510e9c1f9bfbf24edc7c347f97359db\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"2510e9c1f9bfbf24edc7c347f97359db\"}',
            ],
            [
                'component_id' => 41,
                'user_id' => 1,
                'name' => 'Admin Categorias Categorias Filho Lista',
                'id' => 'admin-categorias-categorias-filho-lista',
                'language' => 'pt-br',
                'module' => 'admin-categorias',
                'html' => '<table class=\"ui unstackable table\">
    <tbody>
        <!-- item < --><tr>
        <td>
            #nome#
        </td>
        <td class=\"right aligned\">
            <a class=\"ui mini button blue\" href=\"@[[pagina#url-raiz]]@admin-categorias/editar/?id=#id#\" data-content=\"#tooltip-button-edit#\" data-id=\"editar\">
                <i class=\"edit circle icon\"></i>
                #label-button-edit#
            </a>
        </td>
        </tr><!-- item > -->
    </tbody>
</table>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"fa6886d682bc63a6c480a9d9af2cd920\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"fa6886d682bc63a6c480a9d9af2cd920\"}',
            ],
            [
                'component_id' => 42,
                'user_id' => 1,
                'name' => 'Admin Categorias Categorias Pai Info',
                'id' => 'admin-categorias-categorias-pai-info',
                'language' => 'pt-br',
                'module' => 'admin-categorias',
                'html' => '<table class=\"ui unstackable table\">
    <tbody>
        <tr>
            <td>
                <div class=\"ui large breadcrumb\">
                    <!-- pais < --><a class=\"section\" href=\"#pai-url#\">#pai-nome#</a>
                    <!-- div-pais < --><i class=\"right chevron icon divider\"></i><!-- div-pais > --><!-- pais > -->
                    <i class=\"right arrow icon divider\"></i>
                    <div class=\"active section\">#filho-nome#</div>
                </div>
            </td>
    </tbody>
</table>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"7d565f9182a447515db661c097cf06e8\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"7d565f9182a447515db661c097cf06e8\"}',
            ],
            [
                'component_id' => 43,
                'user_id' => 1,
                'name' => 'Emissao',
                'id' => 'emissao',
                'language' => 'pt-br',
                'module' => 'admin-layouts',
                'html' => '    <div class=\"ui padded grid\">
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
    </div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"315ddba71715884bda43fde4c244c32e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"315ddba71715884bda43fde4c244c32e\"}',
            ],
            [
                'component_id' => 44,
                'user_id' => 1,
                'name' => 'Emissao Teste',
                'id' => 'emissao-teste',
                'language' => 'pt-br',
                'module' => 'admin-layouts',
                'html' => '  <div class=\"ui padded grid\">
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
    </div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"c03712e94846c0734d390d6a98bd6d7f\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"c03712e94846c0734d390d6a98bd6d7f\"}',
            ],
            [
                'component_id' => 45,
                'user_id' => 1,
                'name' => 'Arquivos Lista',
                'id' => 'arquivos-lista',
                'language' => 'pt-br',
                'module' => 'arquivos',
                'html' => '<div class=\"ui medium header\">#categoria#</div>
<div class=\"ui four doubling cards\" id=\"#id#\">
    <!-- card < -->
    <div class=\"ui fluid raised card fileCont\" data-id=\"#file-id#\">
        <div class=\"image fileImage\">
            <img>
            <div class=\"fileImage\">
                <img src=\"#img-src#\">
            </div>
        </div>
        <div class=\"content\">
            <div class=\"ui small header\">#nome#</div>
            <div class=\"description\">
                <div class=\"ui list\">
                    <div class=\"item\">
                        <i class=\"calendar alternate outline icon\"></i>
                        #data#
                    </div>
                    <div class=\"item\">
                        <i class=\"info circle icon\"></i>
                        #tipo#
                    </div>
                </div>
            </div>
        </div>
        <div class=\"extra content center aligned\">
            <!-- btn-select < --><button class=\"ui blue button fileSelect\" data-id=\"#file-id#\" data-dados=\"#file-data#\" data-tooltip=\"@[[list-button-select-tooltip]]@\" data-inverted=\"\">@[[list-button-select]]@</button><!-- btn-select > -->
            <!-- btn-copy < --><button class=\"ui blue button fileCopyUrl\" data-id=\"#file-id#\" data-url=\"#file-url#\" data-tooltip=\"@[[list-button-copy-tooltip]]@\" data-inverted=\"\">@[[list-button-copy]]@</button><!-- btn-copy > -->
            <button class=\"ui red button fileDelete\" data-id=\"#file-id#\" data-tooltip=\"@[[list-button-del-tooltip]]@\" data-inverted=\"\">@[[list-button-del]]@</button>
        </div>
    </div><!-- card > -->
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"4188ef82d4482a48bc75dea5b0dac9c4\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"4188ef82d4482a48bc75dea5b0dac9c4\"}',
            ],
            [
                'component_id' => 46,
                'user_id' => 1,
                'name' => 'Categorias Categorias Filho Info',
                'id' => 'categorias-categorias-filho-info',
                'language' => 'pt-br',
                'module' => 'categorias',
                'html' => '<div class=\"ui info visible message\">
    #message#
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"2b5394fe49b8a5fa96a3ad33391c2319\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"2b5394fe49b8a5fa96a3ad33391c2319\"}',
            ],
            [
                'component_id' => 47,
                'user_id' => 1,
                'name' => 'Categorias Categorias Filho Lista',
                'id' => 'categorias-categorias-filho-lista',
                'language' => 'pt-br',
                'module' => 'categorias',
                'html' => '<table class=\"ui unstackable table\">
    <tbody>
        <!-- item < --><tr>
        <td>
            #nome#
        </td>
        <td class=\"right aligned\">
            <a class=\"ui mini button blue\" href=\"@[[pagina#url-raiz]]@categorias/editar/?id=#id#\" data-content=\"#tooltip-button-edit#\" data-id=\"editar\">
                <i class=\"edit circle icon\"></i>
                #label-button-edit#
            </a>
        </td>
        </tr><!-- item > -->
    </tbody>
</table>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"5844c03abd1547325c26e622ca82f679\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"5844c03abd1547325c26e622ca82f679\"}',
            ],
            [
                'component_id' => 48,
                'user_id' => 1,
                'name' => 'Categorias Categorias Pai Info',
                'id' => 'categorias-categorias-pai-info',
                'language' => 'pt-br',
                'module' => 'categorias',
                'html' => '<table class=\"ui unstackable table\">
    <tbody>
        <tr>
            <td>
                <div class=\"ui large breadcrumb\">
                    <!-- pais < --><a class=\"section\" href=\"#pai-url#\">#pai-nome#</a>
                    <!-- div-pais < --><i class=\"right chevron icon divider\"></i><!-- div-pais > --><!-- pais > -->
                    <i class=\"right arrow icon divider\"></i>
                    <div class=\"active section\">#filho-nome#</div>
                </div>
            </td>
    </tbody>
</table>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"7d565f9182a447515db661c097cf06e8\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"7d565f9182a447515db661c097cf06e8\"}',
            ],
            [
                'component_id' => 49,
                'user_id' => 1,
                'name' => 'Comunicacao Email Teste',
                'id' => 'comunicacao-email-teste',
                'language' => 'pt-br',
                'module' => 'comunicacao-configuracoes',
                'html' => '<h1>Email Teste da Plataforma</h1>
<p>Você recebeu este email que foi enviado num teste feito na plataforma Entrey.com.br . Caso tenha alguma dúvida ou então entenda isso ser um abuso, favor entrar em contato conosco no suporte.</p>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"98cd5d697e6c6f1bfd798819ae65539f\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"98cd5d697e6c6f1bfd798819ae65539f\"}',
            ],
            [
                'component_id' => 50,
                'user_id' => 1,
                'name' => 'Disparador De Emails Teste',
                'id' => 'disparador-de-emails-teste',
                'language' => 'pt-br',
                'module' => 'comunicacao-configuracoes',
                'html' => '<h2 class=\"ui dividing header\">
    <i class=\"paper plane outline icon\"></i>
    <div class=\"content\"> Disparador de Emails<div class=\"sub header\">Faça os testes de envio de emails pela nossa plataforma logo abaixo para testar as suas configurações personalizadas.</div>
    </div>
</h2>
<a class=\"ui right labeled positive icon button enviarEmailTeste\">
    <i class=\"right arrow icon\"></i>
    Enviar Email Teste
</a>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"aea098f46856f41f33e1ba518b9d083c\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"aea098f46856f41f33e1ba518b9d083c\"}',
            ],
            [
                'component_id' => 51,
                'user_id' => 1,
                'name' => 'Layout Emails Assinatura',
                'id' => 'layout-emails-assinatura',
                'language' => 'pt-br',
                'module' => 'comunicacao-configuracoes',
                'html' => '<p>Atenciosamente</p>
<p>Equipe Entrey</p>
<p>Acessar Sistema: <a href=\"@[[pagina#url-full-http]]@signin/\">@[[pagina#url-full-http]]@signin/</a></p>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"6411590a438bb1b96dae7bfb0fe89dc2\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"6411590a438bb1b96dae7bfb0fe89dc2\"}',
            ],
            [
                'component_id' => 52,
                'user_id' => 1,
                'name' => 'Gateway De Pagamento Paypal',
                'id' => 'gateway-de-pagamento-paypal',
                'language' => 'pt-br',
                'module' => 'gateways-de-pagamentos',
                'html' => '<!-- instalacao < -->
<div class=\"ui medium header\">
    <i class=\"plug icon\"></i>
    <div class=\"content\"> Instalação </div>
</div>
<div class=\"ui info message visible\">
    <div class=\"header\">Ação Necessária: Instalar Novo APP do PayPal.</div>
    <div class=\"ui ordered list\">
        <div class=\"item\">Acesse o site do Developers do PayPal para criar um novo APP do PayPal acessando este site: <a href=\"https://www.paypal.com/signin?returnUri=https%3A%2F%2Fdeveloper.paypal.com%2Fdeveloper%2Fapplications\" target=\"paypal-developers\">https://www.paypal.com/signin?returnUri=https%3A%2F%2Fdeveloper.paypal.com%2Fdeveloper%2Fapplications</a>. </div>
        <div class=\"item\">Crie uma nova conta PayPal ou forneça uma sua existente e faça o login.</div>
        <div class=\"item\">Na Dashboard do \"PayPal Developers\", acesse a seção \"My apps &amp; credentials\". Na subseção \"REST API apps\", clique no botão azul \"Create App\".</div>
        <div class=\"item\">Na página \"Create New App\" dê um nome a seu aplicativo que quiser. Deixe as opções padrões marcadas e por fim clique no botão azul \"Create App\".</div>
        <div class=\"item\">Em seguida, na página do seu app do PayPal, na seção \"API CREDENTIALS\", copie os valores do \"Client ID\" e do \"Secret\". Retorne a esta página e forneça os mesmos logo no formulário abaixo.</div>
        <div class=\"item\">Clique no botão \"My apps &amp; credentials\" no menu à esquerda. Em seguida na \"Dashboard\", clique no botão branco \"Live\" logo abaixo da seção \"My apps &amp; credentials\". Em seguida repita os mesmos procedimentos descritos nas etapas 3 a 5 logo acima.</div>
        <div class=\"item\">Por fim, clique no botão \"Instalar PayPal APP\" aqui nesta página.</div>
    </div>
</div>
<div class=\"ui dividing header\">SANDBOX API CREDENTIALS</div>
<div class=\"field\">
    <label>Client ID</label>
    <input type=\"text\" name=\"app_sandbox_code\" placeholder=\"Client ID\" autocomplete=\"off\">
</div>
<div class=\"field\">
    <label>Secret</label>
    <input type=\"text\" name=\"app_sandbox_secret\" placeholder=\"Secret\" autocomplete=\"off\">
</div>
<div class=\"ui dividing header\">LIVE API CREDENTIALS</div>
<div class=\"field\">
    <label>Client ID</label>
    <input type=\"text\" name=\"app_code\" placeholder=\"Client ID\" autocomplete=\"off\">
</div>
<div class=\"field\">
    <label>Secret</label>
    <input type=\"text\" name=\"app_secret\" placeholder=\"Secret\" autocomplete=\"off\">
</div>
<input type=\"hidden\" name=\"instalar\" value=\"1\">
<!-- instalacao > -->
<!-- reference < -->
<h1 class=\"ui header\">#titulo#</h1>
<div class=\"ui medium header\">
    <i class=\"handshake outline icon\"></i>
    <div class=\"content\"> Comissão Gestor </div>
</div>
<div class=\"ui info message visible\">
    <div class=\"header\">Taxa de Comissão por Operação.</div>
    <div class=\"ui ordered list\">
        <div class=\"item\">Cada venda de serviço/produto no uso do nosso gestor, é aplicada uma taxa de #taxa#% sobre o valor total da transação.</div>
        <div class=\"item\">Para isso, é necessária a instalação do PayPal Reference.</div>
        <div class=\"item\">Clique no botão logo abaixo \"PayPal Reference Habilitar\". Acesse o site do PayPal, forneça seu usário e senha da sua conta PayPal. E então inclua uma cartão de crédito válido seu ou de sua empresa.</div>
        <div class=\"item\">É necessário a inclusão de um cartão de crédito, pois caso não haja recursos na sua carteira do PayPal para o pagamento do uso de nosso gestor, a taxa será debitada neste cartão.</div>
    </div>
</div>
<a class=\"ui button\" href=\"/gateways-de-pagamentos/paypal-reference-create/\">
    <i class=\"paypal user\"></i> PayPal Reference Habilitar </a>
<!-- reference > -->
<!-- controles < -->
<h1 class=\"ui header\">#titulo#</h1>
<div class=\"ui medium header\">
    <i class=\"cogs icon\"></i>
    <div class=\"content\"> Controles </div>
</div>
<table class=\"ui very basic table\">
    <tbody>
        <tr>
            <td>
                <button class=\"ui button desinstalarBtn\" data-content=\"Clique para DESINSTALAR o APP do PayPal\" data-variation=\"inverted\">Desinstalar</button>
            </td>
        </tr>
        <tr>
            <td>
                <div class=\"ui buttons ativacao\">
                    <button class=\"ui button ativarBtn\" data-content=\"Clique para ATIVAR o meio de Pagamento PayPal\" data-variation=\"inverted\" data-acao=\"ativar\">Ativo</button>
                    <div class=\"or\" data-text=\"ou\"></div>
                    <button class=\"ui button inativarBtn\" data-content=\"Clique para INATIVAR o meio de Pagamento PayPal\" data-variation=\"inverted\" data-acao=\"inativar\">Inativo</button>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class=\"ui buttons ambiente\">
                    <button class=\"ui button liveBtn\" data-content=\"Clique para alterar o ambiente para LIVE\" data-variation=\"inverted\" data-acao=\"live\">Live</button>
                    <div class=\"or\" data-text=\"ou\"></div>
                    <button class=\"ui button sandboxBtn\" data-content=\"Clique para alterar o ambiente para SANDBOX\" data-variation=\"inverted\" data-acao=\"sandbox\">Sandbox</button>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class=\"ui buttons payPalPlus\">
                    <button class=\"ui button pppAtivoBtn\" data-content=\"Clique para ATIVAR o PayPal Plus\" data-variation=\"inverted\" data-acao=\"pppAtivar\">PayPal Plus Ativo</button>
                    <div class=\"or\" data-text=\"ou\"></div>
                    <button class=\"ui button pppInativoBtn\" data-content=\"Clique para INATIVAR o PayPal Plus\" data-variation=\"inverted\" data-acao=\"pppInativar\">PayPal Plus Inativo</button>
                </div>
            </td>
        </tr>
    </tbody>
</table>
<!-- controles > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"517529b3f63c06afe846d59ef8f0103f\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"517529b3f63c06afe846d59ef8f0103f\"}',
            ],
            [
                'component_id' => 53,
                'user_id' => 1,
                'name' => 'Gateway De Pagamento Paypal Reference Create',
                'id' => 'gateway-de-pagamento-paypal-reference-create',
                'language' => 'pt-br',
                'module' => 'gateways-de-pagamentos',
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
                'component_id' => 54,
                'user_id' => 1,
                'name' => 'Gateway De Pagamento Paypal Reference Return',
                'id' => 'gateway-de-pagamento-paypal-reference-return',
                'language' => 'pt-br',
                'module' => 'gateways-de-pagamentos',
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
                'component_id' => 55,
                'user_id' => 1,
                'name' => 'Gateways De Pagamentos Mensagem Mudanca De Estado',
                'id' => 'gateways-de-pagamentos-mensagem-mudanca-de-estado',
                'language' => 'pt-br',
                'module' => 'gateways-de-pagamentos',
                'html' => '<h2>@[[titulo]]@</h2>
<h3>Pedido nº @[[codigo]]@ - @[[status]]@</h3>
<p>Houve uma atualização de estado do seu pedido.</p>
@[[assinatura]]@',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"6a1ab17724b68285a7d0a6079a660673\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"6a1ab17724b68285a7d0a6079a660673\"}',
            ],
            [
                'component_id' => 56,
                'user_id' => 1,
                'name' => 'Plataforma Gateways Emails Assinatura',
                'id' => 'plataforma-gateways-emails-assinatura',
                'language' => 'pt-br',
                'module' => 'gateways-de-pagamentos',
                'html' => '<p>Atenciosamente.</p>
<h3>@[[loja-nome]]@</h3>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"3f11a62796c0461988385da00da395c0\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"3f11a62796c0461988385da00da395c0\"}',
            ],
            [
                'component_id' => 57,
                'user_id' => 1,
                'name' => 'Plataforma Gateways Mensagem Disputa',
                'id' => 'plataforma-gateways-mensagem-disputa',
                'language' => 'pt-br',
                'module' => 'gateways-de-pagamentos',
                'html' => '<h2>@[[titulo]]@</h2>
<h3>Pedido nº @[[codigo]]@ - @[[status]]@</h3>
<p>Você, dentro do prazo de liberação da transação, abriu uma disputa.</p>
<p>O seu voucher está bloqueado até a regularização dessa disputa.</p>
@[[assinatura]]@',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"6a4d182d7a902eebd3f6719d17672570\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"6a4d182d7a902eebd3f6719d17672570\"}',
            ],
            [
                'component_id' => 58,
                'user_id' => 1,
                'name' => 'Plataforma Gateways Mensagem Negado',
                'id' => 'plataforma-gateways-mensagem-negado',
                'language' => 'pt-br',
                'module' => 'gateways-de-pagamentos',
                'html' => '<h2>@[[titulo]]@</h2>
<h3>Pedido nº @[[codigo]]@ - @[[status]]@</h3>
<p>A transação não foi aceita pois seu pagamento foi negado pela instituição financeira.</p>
@[[assinatura]]@',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"e5710cd635668cd75df31e68c27b62f6\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"e5710cd635668cd75df31e68c27b62f6\"}',
            ],
            [
                'component_id' => 59,
                'user_id' => 1,
                'name' => 'Plataforma Gateways Mensagem Novo',
                'id' => 'plataforma-gateways-mensagem-novo',
                'language' => 'pt-br',
                'module' => 'gateways-de-pagamentos',
                'html' => '<h2>@[[titulo]]@</h2>
<h3>Pedido nº @[[codigo]]@ - @[[status]]@</h3>
<p>Você fez um pedido e iniciou a transação, mas até o momento não recebemos nenhuma informação sobre o pagamento.</p>
@[[assinatura]]@',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"e35f07a99c563258faaf35cbe7256e30\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"e35f07a99c563258faaf35cbe7256e30\"}',
            ],
            [
                'component_id' => 60,
                'user_id' => 1,
                'name' => 'Plataforma Gateways Mensagem Pago',
                'id' => 'plataforma-gateways-mensagem-pago',
                'language' => 'pt-br',
                'module' => 'gateways-de-pagamentos',
                'html' => '<h2>@[[titulo]]@</h2>
<h3>Pedido nº @[[codigo]]@ - @[[status]]@</h3>
<p>A transação foi paga e recebemos uma confirmação da instituição financeira responsável pelo processamento.</p>
<p>O voucher do seu pedido está disponível para Visualização, Impressão ou Envio por e-mail. Para isso acesse: @[[url]]@</p>
@[[assinatura]]@',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"4cbb60d8b60cf3206945ef2f203f9763\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"4cbb60d8b60cf3206945ef2f203f9763\"}',
            ],
            [
                'component_id' => 61,
                'user_id' => 1,
                'name' => 'Plataforma Gateways Mensagem Pago Gratuito',
                'id' => 'plataforma-gateways-mensagem-pago-gratuito',
                'language' => 'pt-br',
                'module' => 'gateways-de-pagamentos',
                'html' => '<h2>@[[titulo]]@</h2>
<h3>Pedido nº @[[codigo]]@ - @[[status]]@</h3>
<p>Seu pedido gratuito foi processado com sucesso.</p>
<p>O voucher do seu pedido está disponível para Visualização, Impressão ou Envio por e-mail. Para isso acesse: @[[url]]@</p>
@[[assinatura]]@',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"babb1ce83d4e78d54acfb741c6e9eb38\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"babb1ce83d4e78d54acfb741c6e9eb38\"}',
            ],
            [
                'component_id' => 62,
                'user_id' => 1,
                'name' => 'Plataforma Gateways Mensagem Pendente',
                'id' => 'plataforma-gateways-mensagem-pendente',
                'language' => 'pt-br',
                'module' => 'gateways-de-pagamentos',
                'html' => '<h2>@[[titulo]]@</h2>
<h3>Pedido nº @[[codigo]]@ - @[[status]]@</h3>
<p>Você optou por pagar com cartão de crédito e estamos analisando a transação.</p>
@[[assinatura]]@',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ef172d52e7cd86169f39e547fc7fb737\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ef172d52e7cd86169f39e547fc7fb737\"}',
            ],
            [
                'component_id' => 63,
                'user_id' => 1,
                'name' => 'Plataforma Gateways Mensagem Reembolso',
                'id' => 'plataforma-gateways-mensagem-reembolso',
                'language' => 'pt-br',
                'module' => 'gateways-de-pagamentos',
                'html' => '<h2>@[[titulo]]@</h2>
<h3>Pedido nº @[[codigo]]@ - @[[status]]@</h3>
<p>O valor da transação foi devolvido para você e seu pedido foi cancelado.</p>
@[[assinatura]]@',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"43ea4b29c8c5ce30839ea2b77908cea8\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"43ea4b29c8c5ce30839ea2b77908cea8\"}',
            ],
            [
                'component_id' => 64,
                'user_id' => 1,
                'name' => 'Hospedeiro Alteracoes',
                'id' => 'hospedeiro-alteracoes',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<div class=\"ui stackable grid\">
    <div class=\"ten wide column\">
    	<h2 class=\"ui dividing header\">
            <i class=\"cloud icon\"></i>
            <div class=\"content\"> Domínio <div class=\"sub header\">É aqui que você altera as informações do domínio do seu site.</div>
            </div>
        </h2>
        <div class=\"ui blue message\">
            URL Atual do Seu Site: <b><a href=\"#url-site#\">#url-site#</a></b>.
        </div>
        <div class=\"field\">
            <label>@[[form-type-domain-label]]@</label>
            <div class=\"ui large buttons\">
                <div class=\"ui button controleDominio #tipo-sistema#\" data-id=\"sistema\" data-tooltip=\"@[[type-system-tooltip]]@\" data-inverted=\"\">@[[form-type-system]]@</div>
                <div class=\"or\" data-text=\"ou\"></div>
                <div class=\"ui button controleDominio #tipo-proprio#\" data-id=\"proprio\" data-tooltip=\"@[[type-own-tooltip]]@\" data-inverted=\"\">@[[form-type-own]]@</div>
            </div>
            <input type=\"hidden\" name=\"tipo\" value=\"#tipo-value#\">
        </div>
        <div class=\"contProprio #cont-proprio#\">
            <div class=\"field\">
                <label>@[[form-own-domain-label]]@</label>
                <input type=\"text\" name=\"dominio_proprio_url\" placeholder=\"@[[form-own-domain-placeholder]]@\" value=\"#dominio_proprio_url#\">
            </div>
            <div class=\"ui info message visible\">
                <div class=\"header\">
                    Importante
                </div>
                <p>Para que seu domínio próprio funcione corretamente, é necessário seguir os seguinte passos:</p>
                <ol class=\"ui list\">
                    <li>Preencha a URL do seu domínio próprio e clique em enviar para poder instalar este domínio no seu hospedeiro no nosso sistema.</li>
                    <li>Copie os seguintes NSs a seguir, acesse o sistema de gerenciamento do seu domínio na provedora autoridade do seu domínio e altere os servidores de DNS por lá.
                        <ol>
                            <!-- ns-cel < --><li><b>#ns#</b></li><!-- ns-cel > -->
                        </ol>
                    </li>
                    <li>Aguarde o prazo de propagação dos servidores de DNS informado pela sua provedora.</li>
                </ol>
            </div>
            <div class=\"ui hidden divider\"></div>
        </div>
        <div class=\"field\">
            <label>@[[form-recaptcha-label]]@</label>
            <div class=\"ui large buttons\">
                <div class=\"ui button controleRecaptcha\" data-id=\"nenhum\" data-tooltip=\"@[[recaptcha-none-tooltip]]@\" data-inverted=\"\">@[[form-recaptcha-none]]@</div>
                <div class=\"ui button controleRecaptcha\" data-id=\"recaptcha-v2\" data-tooltip=\"@[[recaptcha-v2-tooltip]]@\" data-inverted=\"\">@[[form-recaptcha-v2]]@</div>
                <div class=\"ui button controleRecaptcha\" data-id=\"recaptcha-v3\" data-tooltip=\"@[[recaptcha-v3-tooltip]]@\" data-inverted=\"\">@[[form-recaptcha-v3]]@</div>
            </div>
            <input type=\"hidden\" name=\"recaptcha-tipo\" value=\"#recaptcha-tipo-value#\">
        </div>
        <div class=\"contRecaptcha #cont-recaptcha#\">
            <h3 class=\"ui dividing header\">
                <i class=\"user shield icon\"></i>
                <div class=\"content\"> Google reCAPTCHA<div class=\"sub header\">Para que o seu domínio próprio possa ser usado por usuários de maneira segura, é necessário que o Google reCAPTCHA esteja corretamente instalado em nosso sistema.</div>
                </div>
            </h3>
            <div class=\"google-recaptcha-ativo escondido\">
                <div class=\"ui active positive button\">
                    <i class=\"check circle outline icon\"></i>
                    Instalado
                </div>
                <div class=\"ui blue button gr-controle\" data-action=\"reinstalar\">
                    <i class=\"cog icon\"></i>
                    Reinstalar
                </div>
                <div class=\"ui negative button gr-controle\" data-action=\"excluir\">
                    <i class=\"times icon\"></i>
                    Excluir
                </div>
                <input type=\"hidden\" name=\"google-recaptcha-comando\">
                <input type=\"hidden\" name=\"google-recaptcha-tipo\">
            </div>
            <div class=\"google-recaptcha-instalacao escondido\">
                <div class=\"ui info message visible\">
                    <div class=\"header\">
                        Instruções de instalação do Google reCAPTCHA v3
                    </div>
                    <ol class=\"ui list\">
                        <li>Com uma conta Google acesse o site: <a href=\"https://www.google.com/recaptcha/admin\" target=\"google-recaptcha\">https://www.google.com/recaptcha/admin</a></li>
                        <li>Selecione a opção \"Criar\" para registrar um novo site.</li>
                        <li>Preencha o formulário de criação informando:
                            <ol>
                                <li>Etiqueta - Pode ser o seu domínio próprio ou outro nome que preferir.</li>
                                <li>Tipo reCAPTCHA - Selecione a opção reCAPTCHA v3.</li>
                                <li>Domínios - Inclua o domínio próprio informado acima.</li>
                                <li>Selecione - Aceitar os Termos de Serviço do reCAPTCHA</li>
                            </ol>
                        </li>
                        <li>Clique no botão enviar do site do Google para criar as chaves.</li>
                        <li>Copie as 2 chaves geradas nos botões <span class=\"ui text blue\">COPIAR CHAVE DE SITE.</span> e <span class=\"ui text blue\">COPIAR CHAVE SECRETA</span> de lá e cole nos campos correspondentes logo abaixo aqui.</li>
                        <li>Por fim, clique no botão enviar abaixo para salvar estas chaves no nosso sistema.</li>
                    </ol>
                </div>
                <div class=\"field\">
                    <label>@[[form-google-recaptcha-site-label]]@</label>
                    <input type=\"text\" name=\"google_recaptcha_site\" placeholder=\"@[[form-google-recaptcha-site-placeholder]]@\">
                </div>
                <div class=\"field\">
                    <label>@[[form-google-recaptcha-secret-label]]@</label>
                    <input type=\"text\" name=\"google_recaptcha_secret\" placeholder=\"@[[form-google-recaptcha-secret-placeholder]]@\">
                </div>
            </div>
            <div class=\"google-recaptcha-instalacao-v2 escondido\">
                <div class=\"ui info message visible\">
                    <div class=\"header\">
                        Instruções de instalação do Google reCAPTCHA v2
                    </div>
                    <ol class=\"ui list\">
                        <li>Com uma conta Google acesse o site: <a href=\"https://www.google.com/recaptcha/admin\" target=\"google-recaptcha\">https://www.google.com/recaptcha/admin</a></li>
                        <li>Selecione a opção \"Criar\" para registrar um novo site.</li>
                        <li>Preencha o formulário de criação informando:
                            <ol>
                                <li>Etiqueta - Pode ser o seu domínio próprio ou outro nome que preferir.</li>
                                <li>Tipo reCAPTCHA - Selecione a opção reCAPTCHA v2.</li>
                                <li>Domínios - Inclua o domínio próprio informado acima.</li>
                                <li>Selecione - Aceitar os Termos de Serviço do reCAPTCHA</li>
                            </ol>
                        </li>
                        <li>Clique no botão enviar do site do Google para criar as chaves.</li>
                        <li>Copie as 2 chaves geradas nos botões <span class=\"ui text blue\">COPIAR CHAVE DE SITE.</span> e <span class=\"ui text blue\">COPIAR CHAVE SECRETA</span> de lá e cole nos campos correspondentes logo abaixo aqui.</li>
                        <li>Por fim, clique no botão enviar abaixo para salvar estas chaves no nosso sistema.</li>
                    </ol>
                </div>
                <div class=\"field\">
                    <label>@[[form-google-recaptcha-site-label]]@</label>
                    <input type=\"text\" name=\"google_recaptcha_v2_site\" placeholder=\"@[[form-google-recaptcha-site-placeholder]]@\">
                </div>
                <div class=\"field\">
                    <label>@[[form-google-recaptcha-secret-label]]@</label>
                    <input type=\"text\" name=\"google_recaptcha_v2_secret\" placeholder=\"@[[form-google-recaptcha-secret-placeholder]]@\">
                </div>
            </div>
        </div>
    </div>
    <div class=\"six wide column\">
    	<div class=\"ui message\">
            <div class=\"ui center aligned header medium\">@[[host-update-title]]@</div>
            <p>#mensagem-atualizacao#</p>
        	<a class=\"fluid ui button blue\" href=\"@[[pagina#url-raiz]]@host-update/\">@[[host-changes-update-button]]@</a>
        </div>
    	<div class=\"ui message\">
            <div class=\"ui center aligned header medium\">@[[host-plugins-title]]@</div>
            <p>#mensagem-plugins#</p>
        	<a class=\"fluid ui button blue\" href=\"@[[pagina#url-raiz]]@host-plugins/\">@[[host-plugins-button]]@</a>
        </div>
    </div>
</div>',
                'css' => '.escondido{
    display:none;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"b8ad084c87f9b78afae839cd33cf28b4\",\"css\":\"f32438e7149f85825359382e76eec749\",\"combined\":\"9c5750d03a5789127182117ed535a78b\"}',
            ],
            [
                'component_id' => 65,
                'user_id' => 1,
                'name' => 'Hospedeiro Atualizacao',
                'id' => 'hospedeiro-atualizacao',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<!-- atualizacao < -->
<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <form id=\"_gestor-host-update\" action=\"@[[pagina#url-raiz]]@host-update/\" method=\"post\" name=\"_gestor-host-update\" class=\"ui form interfaceFormPadrao\">
                <div class=\"ui center aligned header large\">@[[host-update-title]]@</div>
                <div class=\"ui message\">
                    <div class=\"header\">@[[host-update-msg-title]]@</div>
                    <p>#mensagem-atualizacao#</p>
                </div>
                <div class=\"field\">
                    <div class=\"ui checkbox\">
                        <input type=\"checkbox\" name=\"atualizarConfig\" value=\"1\">
                        <label>@[[host-config-update-label]]@</label>
                    </div>
                </div>
                <div class=\"ui info message visible\">
                    <div class=\"header\">Atualizar Configuração Instruções</div>
                    <ul class=\"list\">
                        <li>Só selecione esta opção caso seja realmente necessário. Pois não há necessidade de atualizar as configurações normalmente.</li>
                        <li>Todas as chaves de segurança serão novamente criadas.</li>
                        <li>Todos os usuários logados serão desconectados do sistema. Assim, todos deverão refazerem seus logins.</li>
                    </ul>
                </div>
                <input type=\"hidden\" name=\"atualizar\" value=\"sim\">
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"ui error message\">&nbsp;</div>
                <div class=\"field\">
                    <button class=\"fluid ui button blue\">#botao-atualizacao#</button>
                    <div class=\"ui hidden divider\">&nbsp;</div>
                </div>
            </form>
        </div>
    </div>
</div><!-- atualizacao > -->
<!-- conta-ftp < -->
<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <form id=\"_gestor-host-update\" action=\"@[[pagina#url-raiz]]@host-update/\" method=\"post\" name=\"_gestor-host-update\" class=\"ui form interfaceFormPadrao\">
                <div class=\"ui center aligned header large\">@[[host-update-title]]@</div>
                <div class=\"ui message\">
                    <div class=\"header\">@[[host-update-msg-title]]@</div>
                    <p>@[[host-update-msg-content]]@</p>
                </div>
                <input type=\"hidden\" name=\"user-ftp\" value=\"entrey-user-ftp\" autocomplete=\"username\">
                <div class=\"ui dividing header\">@[[host-update-ftp-title]]@</div>
                <div class=\"field\">
                    <label>@[[host-update-ftp-pass-label]]@</label>
                    <input type=\"password\" name=\"senha-ftp\" placeholder=\"@[[host-update-ftp-pass-placeholder]]@\" autocomplete=\"current-password\">
                </div>
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"ui error message\">&nbsp;</div>
                <div class=\"field\">
                    <button class=\"fluid ui button blue\">@[[host-update-button-label]]@</button>
                    <div class=\"ui hidden divider\">&nbsp;</div>
                    <div class=\"ui basic segment center aligned\">
                        <div class=\"\">@[[host-update-forgot-password-label]]@ <a href=\"@[[pagina#url-raiz]]@host-config-forgot-password/\">@[[host-update-forgot-password-button]]@</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div><!-- conta-ftp > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"36f9a9a7f9e37f145e48caab2e8bbcbd\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"36f9a9a7f9e37f145e48caab2e8bbcbd\"}',
            ],
            [
                'component_id' => 66,
                'user_id' => 1,
                'name' => 'Hospedeiro Configuracao',
                'id' => 'hospedeiro-configuracao',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <form id=\"_gestor-host-config\" action=\"@[[pagina#url-raiz]]@host-config/\" method=\"post\" name=\"_gestor-host-config\" class=\"ui form interfaceFormPadrao\">
                <div class=\"ui center aligned header large\">@[[host-config-title]]@</div>
                <div class=\"ui message\">
                    <div class=\"header\">@[[host-config-msg-title]]@</div>
                    <p>@[[host-config-msg-content]]@</p>
                </div>
                <input type=\"hidden\" name=\"user-ftp\" value=\"entrey-user-ftp\" autocomplete=\"username\">
                <div class=\"ui dividing header\">@[[host-config-ftp-title]]@</div>
                <div class=\"field\">
                    <label>@[[host-config-ftp-pass-label]]@</label>
                    <input type=\"password\" name=\"senha-ftp\" placeholder=\"@[[host-config-ftp-pass-placeholder]]@\" autocomplete=\"current-password\">
                </div>
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"ui error message\">&nbsp;</div>
                <div class=\"field\">
                    <button class=\"fluid ui button blue\">@[[host-config-button-label]]@</button>
                    <div class=\"ui hidden divider\">&nbsp;</div>
                    <div class=\"ui basic segment center aligned\">
                        <div class=\"\">@[[host-config-forgot-password-label]]@ <a href=\"@[[pagina#url-raiz]]@host-config-forgot-password/\">@[[host-config-forgot-password-button]]@</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"c814c219db08d526256a6334f70cf78f\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"c814c219db08d526256a6334f70cf78f\"}',
            ],
            [
                'component_id' => 67,
                'user_id' => 1,
                'name' => 'Hospedeiro Esqueceu Senha',
                'id' => 'hospedeiro-esqueceu-senha',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <form id=\"_gestor-host-config-forgot-password\" action=\"@[[pagina#url-raiz]]@host-config-forgot-password/\" method=\"post\" name=\"_gestor-host-config-forgot-password\" class=\"ui form interfaceFormPadrao\">
                <div class=\"ui center aligned header large\">@[[host-config-forgot-password-title]]@</div>
                <div class=\"ui message\">
                    <div class=\"header\">@[[host-config-forgot-password-msg-title]]@</div>
                    <p>@[[host-config-forgot-password-msg-content]]@</p>
                </div>
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"ui error message\">&nbsp;</div>
                <div class=\"field\">
                    <button class=\"fluid ui button blue\">@[[host-config-forgot-password-button-label]]@</button>
                </div>
                <input id=\"forgot-password\" name=\"forgot-password\" type=\"hidden\" value=\"1\">
            </form>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"c58e965efca8c80f054c2aa56bc047a4\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"c58e965efca8c80f054c2aa56bc047a4\"}',
            ],
            [
                'component_id' => 68,
                'user_id' => 1,
                'name' => 'Hospedeiro Esqueceu Senha Confirmacao',
                'id' => 'hospedeiro-esqueceu-senha-confirmacao',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <div class=\"ui center aligned header large\">@[[host-config-forgot-password-confirmation-title]]@</div>
            <div class=\"ui hidden divider\">&nbsp;</div>
            <div class=\"ui icon message\">
                <i class=\"paper plane icon\"></i>
                <div class=\"content\">
                    <div class=\"header\">@[[host-config-forgot-password-confirmation-message-header]]@ </div>
                    <p>#message#</p>
                </div>
            </div>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"c7eab78c337d414c4da4015d409d236e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"c7eab78c337d414c4da4015d409d236e\"}',
            ],
            [
                'component_id' => 69,
                'user_id' => 1,
                'name' => 'Hospedeiro Plugins',
                'id' => 'hospedeiro-plugins',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<!-- atualizacao < -->
<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <form id=\"_gestor-host-plugins\" action=\"@[[pagina#url-raiz]]@host-plugins/\" method=\"post\" name=\"_gestor-host-plugins\" class=\"ui form interfaceFormPadrao\">
                <div class=\"ui center aligned header large\">@[[host-plugins-title]]@</div>
                <div class=\"ui message\">
                    <div class=\"header\">@[[host-plugins-msg-title]]@</div>
                    <p>Ative ou inative os plugins abaixo que melhor convir. Depois, clique no botão <b>@[[host-plugins-btn]]@</b> para que o sistema habilite / desabilite os módulos gerenciais dos plugins e instale / remova os módulos no hospedeiro da sua conta.</p>
                </div>
                <div class=\"ui header\">Plugins:</div>
                <div class=\"ui relaxed divided list\">
                    <!-- plugins < --><div class=\"item\">
                        <div class=\"ui toggle checkbox\">
                            <input type=\"checkbox\" name=\"#name#\" data-checked=\"#checked#\" value=\"1\">
                            <label>#titulo#</label>
                        </div>
                    </div><!-- plugins > -->
                </div>
                <input type=\"hidden\" name=\"atualizar\" value=\"sim\">
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"ui error message\">&nbsp;</div>
                <div class=\"field\">
                    <button class=\"fluid ui button blue\">@[[host-plugins-btn]]@</button>
                    <div class=\"ui hidden divider\">&nbsp;</div>
                </div>
            </form>
        </div>
    </div>
</div><!-- atualizacao > -->
<!-- conta-ftp < -->
<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <form id=\"_gestor-host-plugins\" action=\"@[[pagina#url-raiz]]@host-plugins/\" method=\"post\" name=\"_gestor-host-plugins\" class=\"ui form interfaceFormPadrao\">
                <div class=\"ui center aligned header large\">@[[host-plugins-title]]@</div>
                <div class=\"ui message\">
                    <div class=\"header\">@[[host-plugins-msg-title]]@</div>
                    <p>@[[host-plugins-msg-2-content]]@</p>
                </div>
                <input type=\"hidden\" name=\"user-ftp\" value=\"entrey-user-ftp\" autocomplete=\"username\">
                <div class=\"ui dividing header\">@[[host-update-ftp-title]]@</div>
                <div class=\"field\">
                    <label>@[[host-update-ftp-pass-label]]@</label>
                    <input type=\"password\" name=\"senha-ftp\" placeholder=\"@[[host-update-ftp-pass-placeholder]]@\" autocomplete=\"current-password\">
                </div>
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"ui error message\">&nbsp;</div>
                <div class=\"field\">
                    <button class=\"fluid ui button blue\">@[[host-plugins-btn]]@</button>
                    <div class=\"ui hidden divider\">&nbsp;</div>
                    <div class=\"ui basic segment center aligned\">
                        <div class=\"\">@[[host-update-forgot-password-label]]@ <a href=\"@[[pagina#url-raiz]]@host-config-forgot-password/\">@[[host-update-forgot-password-button]]@</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div><!-- conta-ftp > -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"133384c714c0b70922e9cb1fba3084d2\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"133384c714c0b70922e9cb1fba3084d2\"}',
            ],
            [
                'component_id' => 70,
                'user_id' => 1,
                'name' => 'Hospedeiro Redefinir Senha',
                'id' => 'hospedeiro-redefinir-senha',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <form id=\"_gestor-form-host-redefine-password\" action=\"@[[pagina#url-raiz]]@host-config-redefine-password/\" method=\"post\" name=\"_gestor-form-host-redefine-password\" class=\"ui form interfaceFormPadrao\">
                <div class=\"ui center aligned header large\">@[[redefine-password-title]]@</div>
                <div class=\"ui icon blue message\">
                    <i class=\"user secret icon\"></i>
                    <div class=\"content\">
                        <div class=\"header\"> @[[redefine-password-message-header]]@ </div>
                        <p>@[[redefine-password-message-content]]@</p>
                    </div>
                </div>
                <div class=\"ui dividing header\">@[[redefine-password-ftp-title]]@</div>
                <div class=\"field\">
                    <label>@[[redefine-password-pass-label]]@</label>
                    <input type=\"password\" name=\"senha\" placeholder=\"@[[redefine-password-pass-placeholder]]@\" autocomplete=\"new-password\">
                </div>
                <div class=\"field\">
                    <label>@[[redefine-password-pass-2-label]]@</label>
                    <input type=\"password\" name=\"senha-2\" placeholder=\"@[[redefine-password-pass-2-placeholder]]@\" autocomplete=\"new-password\">
                </div>
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"ui error message\">&nbsp;</div>
                <div class=\"field\">
                    <button class=\"fluid ui button blue\">@[[redefine-password-button]]@</button>
                    <div class=\"ui hidden divider\">&nbsp;</div>
                </div>
                <input id=\"_gestor-host-redefine-password\" name=\"_gestor-host-redefine-password\" type=\"hidden\" value=\"1\">
                <input id=\"_gestor-host-redefine-password-token\" name=\"_gestor-host-redefine-password-token\" type=\"hidden\" value=\"#token#\">
            </form>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"a913f078f052430e5c51b618e171ac6f\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"a913f078f052430e5c51b618e171ac6f\"}',
            ],
            [
                'component_id' => 71,
                'user_id' => 1,
                'name' => 'Hospedeiro Redefinir Senha Confirmacao',
                'id' => 'hospedeiro-redefinir-senha-confirmacao',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <div class=\"ui center aligned header large\">@[[redefine-password-confirmation-title]]@</div>
            <div class=\"ui hidden divider\">&nbsp;</div>
            <div class=\"ui icon green message\">
                <i class=\"user check icon\"></i>
                <div class=\"content\">
                    <div class=\"header\"> @[[redefine-password-confirmation-message-header]]@ </div>
                    <p>#message#</p>
                </div>
            </div>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"28c989f67e4b3ede39f67e74391b0232\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"28c989f67e4b3ede39f67e74391b0232\"}',
            ],
            [
                'component_id' => 72,
                'user_id' => 1,
                'name' => 'Host Config Carregando',
                'id' => 'host-config-carregando',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<div class=\"ui basic modal configurando\">
    <div class=\"ui icon large header\">
        <i class=\"circular inverted teal cloud download alternate icon\"></i>
        Hospedeiro Configuração
    </div>
    <div class=\"ui tiny center aligned header\">Estamos configurando o hospedeiro do seu site e atualizando a plataforma do seu cliente para a última versão. Este processo leva poucos segundos.</div>
    <div class=\"ui active centered inline slow large loader\"></div>
    <div class=\"ui center aligned header\">Configurando... Aguarde!</div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"2fa29aa0709b12ea45665f957ebe8b8a\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"2fa29aa0709b12ea45665f957ebe8b8a\"}',
            ],
            [
                'component_id' => 73,
                'user_id' => 1,
                'name' => 'Host Instalacao',
                'id' => 'host-instalacao',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <form id=\"_gestor-host-install\" action=\"@[[pagina#url-raiz]]@host-install/\" method=\"post\" name=\"_gestor-host-install\" class=\"ui form interfaceFormPadrao\">
                <div class=\"ui center aligned header large\">@[[host-install-title]]@</div>
                <div class=\"ui message\">
                    <div class=\"header\">@[[host-install-msg-title]]@</div>
                    <p>@[[host-install-msg-content]]@</p>
                </div>
                <input type=\"hidden\" name=\"user-ftp\" value=\"entrey-user-ftp\" autocomplete=\"username\">
                <div class=\"ui dividing header\">@[[host-install-ftp-title]]@</div>
                <div class=\"field\">
                    <label>@[[host-install-ftp-pass-label]]@</label>
                    <input type=\"password\" name=\"senha-ftp\" placeholder=\"@[[host-install-ftp-pass-placeholder]]@\" autocomplete=\"new-password\">
                </div>
                <div class=\"field\">
                    <label>@[[host-install-ftp-pass-2-label]]@</label>
                    <input type=\"password\" name=\"senha-ftp-2\" placeholder=\"@[[host-install-ftp-pass-2-placeholder]]@\" autocomplete=\"new-password\">
                </div>
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"ui error message\">&nbsp;</div>
                <div class=\"field\">
                    <button class=\"fluid ui button blue\">@[[host-install-button-label]]@</button>
                </div>
            </form>
        </div>
    </div>
</div> ',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"677ca88b21cf326e2927ff88348eb231\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"677ca88b21cf326e2927ff88348eb231\"}',
            ],
            [
                'component_id' => 74,
                'user_id' => 1,
                'name' => 'Host Install Carregando',
                'id' => 'host-install-carregando',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<div class=\"ui basic modal instalando\">
    <div class=\"ui icon large header\">
        <i class=\"circular inverted teal globe icon\"></i>
        Hospedeiro Instalação
    </div>
    <div class=\"ui tiny center aligned header\">Estamos instalando o hospedeiro do seu site. Este processo leva poucos segundos.</div>
    <div class=\"ui active centered inline slow large loader\"></div>
    <div class=\"ui center aligned header\">Instalando... Aguarde!</div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"fc0a77faa1499f23246b6165d71f5ad2\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"fc0a77faa1499f23246b6165d71f5ad2\"}',
            ],
            [
                'component_id' => 75,
                'user_id' => 1,
                'name' => 'Host Update Carregando',
                'id' => 'host-update-carregando',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<div class=\"ui basic modal atualizando\">
    <div class=\"ui icon large header\">
        <i class=\"circular inverted teal cloud download alternate icon\"></i>
        Hospedeiro Atualização
    </div>
    <div class=\"ui tiny center aligned header\">Estamos atualizando a plataforma do hospedeiro do seu site para a última versão. Este processo leva poucos segundos.</div>
    <div class=\"ui active centered inline slow large loader\"></div>
    <div class=\"ui center aligned header\">Atualizando... Aguarde!</div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"e5461fa7cbc8b9812069ed33305f6c95\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"e5461fa7cbc8b9812069ed33305f6c95\"}',
            ],
            [
                'component_id' => 76,
                'user_id' => 1,
                'name' => 'Hosts Interface Alerta Modal',
                'id' => 'hosts-interface-alerta-modal',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<div class=\"ui modal alerta\">
    <div class=\"header\">#titulo#</div>
    <div class=\"content\">
        <p>#mensagem#</p>
    </div>
    <div class=\"actions\">
        <div class=\"ui approve right labeled icon button green\">
            #botao-ok#
            <i class=\"check circle icon\"></i>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d89b05f7e9b207dd68d220ac34139054\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d89b05f7e9b207dd68d220ac34139054\"}',
            ],
            [
                'component_id' => 77,
                'user_id' => 1,
                'name' => 'Hosts Interface Carregando Modal',
                'id' => 'hosts-interface-carregando-modal',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<div class=\"ui basic modal carregando\">
    <div class=\"ui active centered inline fast large loader\"></div>
    <div class=\"ui medium center aligned header\">Carregando</div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"c1574712e986563972da0ccb3938846a\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"c1574712e986563972da0ccb3938846a\"}',
            ],
            [
                'component_id' => 78,
                'user_id' => 1,
                'name' => 'Hosts Interface Formulario Autorizacao Provisoria',
                'id' => 'hosts-interface-formulario-autorizacao-provisoria',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<div class=\"ui tiny modal autorizacaoProvisoria\">
    <div class=\"ui header\">#titulo#</div>
    <div class=\"content\">
        <p>#mensagem#</p>
    </div>
    <div class=\"actions\">
        <a class=\"ui cancel button\" href=\"#botao-cancelar-url#\">
            #botao-cancelar#
        </a>
        <a class=\"ui approve right labeled icon button blue\" href=\"#botao-confirmar-url#\">
            #botao-confirmar#
            <i class=\"lock icon\"></i>
        </a>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"59811a7d383533a272e2a9829498b948\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"59811a7d383533a272e2a9829498b948\"}',
            ],
            [
                'component_id' => 79,
                'user_id' => 1,
                'name' => 'Hosts Interface Modal Informativo',
                'id' => 'hosts-interface-modal-informativo',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<div class=\"ui small modal informativo\">
    <i class=\"close icon\"></i>
    <div class=\"header\">#titulo#</div>
    <div class=\"scrolling content\">
        #mensagem#
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"e946dbda97715a7383613c582c18eed1\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"e946dbda97715a7383613c582c18eed1\"}',
            ],
            [
                'component_id' => 80,
                'user_id' => 1,
                'name' => 'Hosts Layout Email Esqueceu Senha',
                'id' => 'hosts-layout-email-esqueceu-senha',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<p>Caro(a) #nome#,</p>
<p>Esqueceu-se da sua senha?</p>
<p><b>Redefinir Senha:</b> #url#</p>
<p>Lembrando que esta ligação vai expirar dentro de #expiracao# hora(s) e só pode ser utilizada uma vez.</p>
<p>Se não pretender alterar a sua senha ou não tiver efetuado este pedido, ignore e elimine esta mensagem.</p>
#assinatura#',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"0dfd41316fd9355ef67b10cc6b1e04ad\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"0dfd41316fd9355ef67b10cc6b1e04ad\"}',
            ],
            [
                'component_id' => 81,
                'user_id' => 1,
                'name' => 'Hosts Layout Emails Assinatura',
                'id' => 'hosts-layout-emails-assinatura',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<p>Atenciosamente</p>
<p>Equipe Entrey</p>
<p>Acessar Site: <a href=\"@[[url]]@\">@[[url]]@</a></p>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"b0605a8a624e02d03a35b98c5ee6de75\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"b0605a8a624e02d03a35b98c5ee6de75\"}',
            ],
            [
                'component_id' => 82,
                'user_id' => 1,
                'name' => 'Layout Email Host Instalacao',
                'id' => 'layout-email-host-instalacao',
                'language' => 'pt-br',
                'module' => 'host-configuracao',
                'html' => '<p>Caro(a) #nome#,</p>
<p>Foi criado um novo hospedeiro para o seu site no nosso sistema com sucesso!</p>
<p><b>IMPORTANTE:</b> Guarde as senhas definidas em um local seguro! Em algum momento no futuro, numa operação de manutenção do sistema, poderá ser requisitada a senha novamente! Se por algum acaso perder esta senha da Conta FTP, é possível criar novamente a mesma. Para isso, acesse o sistema e vá até o menu Configurações / Hospedeiro / Conta FTP.</p>
#assinatura#',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"1678c78d36a677dbc4b795b7610db377\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"1678c78d36a677dbc4b795b7610db377\"}',
            ],
            [
                'component_id' => 83,
                'user_id' => 1,
                'name' => 'Host Configuracao Manual',
                'id' => 'host-configuracao-manual',
                'language' => 'pt-br',
                'module' => 'host-configuracao-manual',
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
                'component_id' => 84,
                'user_id' => 1,
                'name' => 'Sincronizar Bancos',
                'id' => 'sincronizar-bancos',
                'language' => 'pt-br',
                'module' => 'modulos',
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
                'component_id' => 85,
                'user_id' => 1,
                'name' => 'Teste De Adicao Novo',
                'id' => 'teste-de-adicao-novo',
                'language' => 'pt-br',
                'module' => 'modulos-grupos',
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
                'component_id' => 86,
                'user_id' => 1,
                'name' => 'Pedidos Dados Do Pagador',
                'id' => 'pedidos-dados-do-pagador',
                'language' => 'pt-br',
                'module' => 'pedidos',
                'html' => '<table class=\"ui definition table\">
    <thead>
        <tr>
            <th></th>
            <th>Dados do Pagador</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Nome</td>
            <td>#pagador-nome#</td>
        </tr>
        <tr>
        </tr>
        <tr>
            <td>Email</td>
            <td>#pagador-email#</td>
        </tr>
        <tr>
            <td>Telefone</td>
            <td>#pagador-telefone#</td>
        </tr>
        <tr>
            <td>CPF</td>
            <td>#pagador-cpf#</td>
        </tr>
        <tr>
            <td>CNPJ</td>
            <td>#pagador-cnpj#</td>
        </tr>
    </tbody>
</table>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"fb257c4d816d06ddefc998eb99c9421b\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"fb257c4d816d06ddefc998eb99c9421b\"}',
            ],
            [
                'component_id' => 87,
                'user_id' => 1,
                'name' => 'Area Restrita',
                'id' => 'Area-restrita',
                'language' => 'pt-br',
                'module' => 'perfil-usuario',
                'html' => '<h1 class=\"ui header\">@[[pagina#titulo]]@</h1>
<div class=\"ui icon message\">
    <i class=\"user lock icon\"></i>
    <div class=\"content\">
        <div class=\"header\"> @[[restrict-area-info-title]]@ </div>
        <p>@[[restrict-area-info]]@</p>
    </div>
</div>
<form id=\"_gestor-restrict-area\" action=\"#form-action#\" method=\"post\" name=\"_gestor-restrict-area\" class=\"ui form restrictArea\">
    <div class=\"field\">
        <label>@[[form-password-label]]@</label>
        <input type=\"password\" name=\"senha\" placeholder=\"@[[form-password-placeholder]]@\" autocomplete=\"new-password\">
    </div>
    <input id=\"_gestor-restrict-area-atualizar\" name=\"_gestor-restrict-area-atualizar\" type=\"hidden\" value=\"1\">
    <input id=\"_gestor-restrict-area-querystring\" name=\"_gestor-restrict-area-querystring\" type=\"hidden\" value=\"#form-querystring#\">
    <div class=\"ui error message\"></div>
    <div class=\"ui center aligned basic segment\">
        <button id=\"_gestor-restrict-area-button\" data-tooltip=\"#form-button-title#\" data-position=\"top center\" data-inverted=\"\" class=\"positive ui button\">#form-button-value#</button>
    </div>
</form>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"b106ef13fa11754052fb9a31122c643c\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"b106ef13fa11754052fb9a31122c643c\"}',
            ],
            [
                'component_id' => 88,
                'user_id' => 1,
                'name' => 'Acessar Sistema',
                'id' => 'acessar-sistema',
                'language' => 'pt-br',
                'module' => 'perfil-usuario',
                'html' => '<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <form id=\"_gestor-form-logar\" action=\"@[[pagina#url-raiz]]@signin/\" method=\"post\" name=\"_gestor-form-logar\" class=\"ui form\">
                <div class=\"ui center aligned header large\">@[[login-titulo]]@</div>
                <div class=\"ui hidden divider\">&nbsp;</div>
                <!-- bloqueado-mensagem < --><div class=\"ui icon negative message visible\">
                    <i class=\"exclamation triangle icon\"></i>
                    <div class=\"content\">
                        <div class=\"header\">
                            Endereço de IP do seu dispositivo está BLOQUEADO!
                        </div>
                        <p>Infelizmente não é possível acessar sua conta deste dispositivo atual devido ao excesso de falhas de tentativa de acesso com usuário e/ou senha inválidos. Favor tentar novamente mais tarde neste dispositivo ou então em um outro numa outra rede.</p>
                    </div>
                </div>
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"field\">
                    <div class=\"ui hidden divider\">&nbsp;</div>
                    <div class=\"ui basic segment center aligned\">
                        <div class=\"\">@[[login-forgot-password-label]]@ <a href=\"@[[pagina#url-raiz]]@forgot-password/\">@[[login-forgot-password-button]]@</a>
                        </div>
                        <div>@[[login-new-register-label]]@ <a href=\"@[[pagina#url-raiz]]@signup/\">@[[login-new-register-button]]@</a>
                        </div>
                    </div>
                </div>
                <!-- bloqueado-mensagem > -->
                <!-- formulario < -->
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"field\">
                    <label>@[[login-user-label]]@</label>
                    <input type=\"text\" name=\"usuario\" placeholder=\"@[[login-user-placeholder]]@\">
                </div>
                <div class=\"field\">
                    <label>@[[login-password-label]]@</label>
                    <input type=\"password\" name=\"senha\" placeholder=\"@[[login-password-placeholder]]@\">
                </div>
                <div class=\"field\">
                    <div class=\"ui checkbox\">
                        <input type=\"checkbox\" name=\"permanecer-logado\" value=\"1\">
                        <label>@[[login-keep-logged-in-label]]@</label>
                    </div>
                </div>
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"ui error message\">&nbsp;</div>
                <div class=\"field\">
                    <button class=\"fluid ui button blue\">@[[login-button-label]]@</button>
                    <div class=\"ui hidden divider\">&nbsp;</div>
                    <div class=\"ui basic segment center aligned\">
                        <div class=\"\">@[[login-forgot-password-label]]@ <a href=\"@[[pagina#url-raiz]]@forgot-password/\">@[[login-forgot-password-button]]@</a>
                        </div>
                        <div>@[[login-new-register-label]]@ <a href=\"@[[pagina#url-raiz]]@signup/\">@[[login-new-register-button]]@</a>
                        </div>
                    </div>
                </div>
                <input id=\"_gestor-logar\" name=\"_gestor-logar\" type=\"hidden\" value=\"1\">
                <input id=\"_gestor-fingerprint\" name=\"_gestor-fingerprint\" type=\"hidden\">
                <!-- formulario > -->
            </form>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"1a28df4eb15690a1a94815ac85e7d7b2\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"1a28df4eb15690a1a94815ac85e7d7b2\"}',
            ],
            [
                'component_id' => 89,
                'user_id' => 1,
                'name' => 'Cadastrar No Sistema',
                'id' => 'cadastrar-no-sistema',
                'language' => 'pt-br',
                'module' => 'perfil-usuario',
                'html' => '<div class=\"ui two column centered grid stackable\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <form id=\"_gestor-form-signup\" action=\"@[[pagina#url-raiz]]@signup/\" method=\"post\" name=\"_gestor-form-signup\" class=\"ui form interfaceFormPadrao\">
                <div class=\"ui center aligned header large\">@[[signup-title]]@</div>
                <div class=\"ui hidden divider\"></div>
                <!-- bloqueado-mensagem < --><div class=\"ui icon negative message visible\">
                    <i class=\"exclamation triangle icon\"></i>
                    <div class=\"content\">
                        <div class=\"header\">
                            Quantidade Máxima de Cadastros Permitidos Alcançada!
                        </div>
                        <p>Infelizmente não é possível cadastrar uma nova conta no nosso sistema uma vez que o limite máximo de cadastros por dia na sua rede foram alcançadas. Favor tentar novamente dentro de 24h.</p>
                    </div>
                </div><!-- bloqueado-mensagem > -->
            	<!-- formulario < --><div class=\"ui hidden divider\"></div>
                <div class=\"field\">
                    <label>@[[signup-name-label]]@</label>
                    <input type=\"text\" name=\"nome\" placeholder=\"@[[signup-name-placeholder]]@\" autocomplete=\"new-password\">
                </div>
                <div class=\"field\">
                    <label>@[[signup-email-label]]@</label>
                    <input type=\"text\" name=\"email\" placeholder=\"@[[signup-email-placeholder]]@\" maxlength=\"100\" autocomplete=\"new-password\">
                </div>
                <div class=\"field\">
                    <label>@[[signup-email-2-label]]@</label>
                    <input type=\"text\" name=\"email-2\" placeholder=\"@[[signup-email-2-placeholder]]@\" maxlength=\"100\" autocomplete=\"new-password\">
                </div>
                <div class=\"field\">
                    <label>@[[signup-senha-label]]@</label>
                    <input type=\"password\" name=\"senha\" placeholder=\"@[[signup-senha-placeholder]]@\" maxlength=\"100\" autocomplete=\"new-password\">
                </div>
                <div class=\"field\">
                    <label>@[[signup-senha-2-label]]@</label>
                    <input type=\"password\" name=\"senha-2\" placeholder=\"@[[signup-senha-2-placeholder]]@\" maxlength=\"100\" autocomplete=\"new-password\">
                </div>
                <div class=\"ui hidden divider\"></div>
                <div class=\"inline fields\">
                    <label for=\"plano\">@[[signup-plan]]@:</label>
                    <!-- plano-cel < -->
                    <div class=\"field\">
                        <div class=\"ui radio checkbox\">
                            <input type=\"radio\" name=\"plano\" value=\"#val#\" #checked#=\"\">
                            <label>#nome#</label>
                        </div>
                    </div><!-- plano-cel > -->
                </div>
                <div class=\"ui hidden divider\"></div>
                <div class=\"ui hidden divider\"></div>
                <div class=\"ui error message\">&nbsp;</div>
                <div class=\"field\">
                    <button class=\"fluid ui button blue\">@[[signup-button]]@</button>
                </div>
            	<!-- formulario > -->
                <div class=\"field\">
                    <div class=\"ui hidden divider\">&nbsp;</div>
                    <div class=\"ui basic segment center aligned\">
                        <div class=\"\">@[[signup-login-label]]@ <a href=\"@[[pagina#url-raiz]]@signin/\">@[[signup-login-button]]@</a>
                        </div>
                    </div>
                </div>
                <input id=\"_gestor-signup\" name=\"_gestor-signup\" type=\"hidden\" value=\"1\">
            </form>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"62ae3b0260aed09055dbd0cd3ff7f33e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"62ae3b0260aed09055dbd0cd3ff7f33e\"}',
            ],
            [
                'component_id' => 90,
                'user_id' => 1,
                'name' => 'Confirmacao De Email',
                'id' => 'confirmacao-de-email',
                'language' => 'pt-br',
                'module' => 'perfil-usuario',
                'html' => '<h1 class=\"ui header\">@[[pagina#titulo]]@</h1>
<p>Email confirmado com sucesso!</p>
<p>Para acessar o sistema entre em <a href=\"@[[pagina#url-raiz]]@dashboard/\">@[[pagina#url-raiz]]@dashboard/</a>
</p>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"589314427de8eec6b99dcffb571ce7cb\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"589314427de8eec6b99dcffb571ce7cb\"}',
            ],
            [
                'component_id' => 91,
                'user_id' => 1,
                'name' => 'Esqueceu A Senha',
                'id' => 'esqueceu-a-senha',
                'language' => 'pt-br',
                'module' => 'perfil-usuario',
                'html' => '<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <div class=\"ui center aligned header large\">@[[forgot-password-title]]@</div>
            <!-- bloqueado-mensagem < --><div class=\"ui hidden divider\">&nbsp;</div>
            <div class=\"ui icon negative message visible\">
            	<i class=\"exclamation triangle icon\"></i>
                <div class=\"content\">
                    <div class=\"header\">
                        Endereço de IP do seu dispositivo está BLOQUEADO!
                    </div>
                    <p>Infelizmente não é possível alterar a sua senha neste dispositivo atual devido ao excesso de falhas na tentativa de informar emails inválidos. Favor tentar novamente mais tarde neste dispositivo ou então em um outro numa outra rede.</p>
                </div>
            </div>
            <div class=\"ui hidden divider\">&nbsp;</div>
            <div class=\"ui hidden divider\">&nbsp;</div>
            <div class=\"field\">
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"ui basic segment center aligned\">
                    <div class=\"\">@[[forgot-password-login-label]]@ <a href=\"@[[pagina#url-raiz]]@signin/\">@[[forgot-password-login-button]]@</a>
                    </div>
                    <div>@[[forgot-password-new-register-label]]@ <a href=\"@[[pagina#url-raiz]]@signup/\">@[[forgot-password-new-register-button]]@</a>
                    </div>
                </div>
            </div><!-- bloqueado-mensagem > -->
            <!-- formulario < -->
            <form id=\"_gestor-form-forgot-password\" action=\"@[[pagina#url-raiz]]@forgot-password/\" method=\"post\" name=\"_gestor-form-forgot-password\" class=\"ui form\">
                <div class=\"ui icon message\">
                    <i class=\"user lock icon\"></i>
                    <div class=\"content\">
                        <div class=\"header\"> @[[forgot-password-message-header]]@ </div>
                        <p>@[[forgot-password-message-content]]@</p>
                    </div>
                </div>
                <div class=\"field\">
                    <label>@[[forgot-password-email-label]]@</label>
                    <input type=\"text\" name=\"email\" placeholder=\"@[[forgot-password-email-placeholder]]@\">
                </div>
                <div class=\"field\">
                    <label>@[[forgot-password-email-2-label]]@</label>
                    <input type=\"text\" name=\"email-2\" placeholder=\"@[[forgot-password-email-2-placeholder]]@\">
                </div>
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"ui error message\">&nbsp;</div>
                <div class=\"field\">
                    <button class=\"fluid ui button blue\">@[[forgot-password-button]]@</button>
                    <div class=\"ui hidden divider\">&nbsp;</div>
                    <div class=\"ui basic segment center aligned\">
                        <div class=\"\">@[[forgot-password-login-label]]@ <a href=\"@[[pagina#url-raiz]]@signin/\">@[[forgot-password-login-button]]@</a>
                        </div>
                        <div>@[[forgot-password-new-register-label]]@ <a href=\"@[[pagina#url-raiz]]@signup/\">@[[forgot-password-new-register-button]]@</a>
                        </div>
                    </div>
                </div>
                <input id=\"_gestor-forgot-password\" name=\"_gestor-forgot-password\" type=\"hidden\" value=\"1\">
            </form>
            <!-- formulario > -->
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"36252affc726b0a003349db772ffe552\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"36252affc726b0a003349db772ffe552\"}',
            ],
            [
                'component_id' => 92,
                'user_id' => 1,
                'name' => 'Esqueceu A Senha Email Enviado',
                'id' => 'esqueceu-a-senha-email-enviado',
                'language' => 'pt-br',
                'module' => 'perfil-usuario',
                'html' => '<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <div class=\"ui center aligned header large\">@[[forgot-password-confirmation-title]]@</div>
            <div class=\"ui hidden divider\">&nbsp;</div>
            <div class=\"ui icon message\">
                <i class=\"paper plane icon\"></i>
                <div class=\"content\">
                    <div class=\"header\">@[[forgot-password-confirmation-message-header]]@ </div>
                    <p>#message#</p>
                </div>
            </div>
            <div class=\"ui basic segment center aligned\">
                <div class=\"\">@[[forgot-password-login-label]]@ <a href=\"@[[pagina#url-raiz]]@signin/\">@[[forgot-password-login-button]]@</a>
                </div>
                <div>@[[forgot-password-new-register-label]]@ <a href=\"@[[pagina#url-raiz]]@signup/\">@[[forgot-password-new-register-button]]@</a>
                </div>
            </div>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"100a56701ff0b6d915e3cf9e3f2791c8\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"100a56701ff0b6d915e3cf9e3f2791c8\"}',
            ],
            [
                'component_id' => 93,
                'user_id' => 1,
                'name' => 'Layout Email Esqueceu Senha',
                'id' => 'layout-email-esqueceu-senha',
                'language' => 'pt-br',
                'module' => 'perfil-usuario',
                'html' => '<p>Caro(a) #nome#,</p>
<p>Esqueceu-se da sua senha?</p>
<p><b>Redefinir Senha:</b> #url#</p>
<p>Lembrando que esta ligação vai expirar dentro de #expiracao# hora(s) e só pode ser utilizada uma vez.</p>
<p>Se não pretender alterar a sua senha ou não tiver efetuado este pedido, ignore e elimine esta mensagem.</p>
#assinatura#',
                'css' => 'h1{
    color:blue;
}',
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"0dfd41316fd9355ef67b10cc6b1e04ad\",\"css\":\"2f40d13cf61ff32dcb801f3fec1d211f\",\"combined\":\"67e9a5119e68ec38f6cd816ad7b3aaa0\"}',
            ],
            [
                'component_id' => 94,
                'user_id' => 1,
                'name' => 'Layout Email Novo Cadastro',
                'id' => 'layout-email-novo-cadastro',
                'language' => 'pt-br',
                'module' => 'perfil-usuario',
                'html' => '<p>Caro(a) #nome#,</p>
<p>Seja bem vindo à plataforma Entrey!</p>
<p>- Caso você não tenha feito esse cadastro, favor desconsiderar este email.</p>
<p>- Se você fez o cadastro, é necessário que você confirme o seu email acessando #url-confirmacao#.</p>
<p>- Para futuros acessos, você pode acessar via #url-signin#.</p>
#assinatura#',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"447021ae9358098d9adb9627ecb65014\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"447021ae9358098d9adb9627ecb65014\"}',
            ],
            [
                'component_id' => 95,
                'user_id' => 1,
                'name' => 'Layout Email Senha Redefinida',
                'id' => 'layout-email-senha-redefinida',
                'language' => 'pt-br',
                'module' => 'perfil-usuario',
                'html' => '<p>Caro(a) #nome#,</p>
<p>Sua senha foi redefinida com sucesso!</p>
<p>Caso não tenha sido você, favor entrar em contato conosco reportando o problema.</p>
#assinatura#',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"a733b0e5baa367661d0636c0bab45c5b\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"a733b0e5baa367661d0636c0bab45c5b\"}',
            ],
            [
                'component_id' => 96,
                'user_id' => 1,
                'name' => 'Redefinir Senha',
                'id' => 'redefinir-senha',
                'language' => 'pt-br',
                'module' => 'perfil-usuario',
                'html' => '<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <form id=\"_gestor-form-redefine-password\" action=\"@[[pagina#url-raiz]]@redefine-password/\" method=\"post\" name=\"_gestor-form-redefine-password\" class=\"ui form\">
                <div class=\"ui center aligned header large\">@[[redefine-password-title]]@</div>
                <div class=\"ui icon blue message\">
                    <i class=\"user secret icon\"></i>
                    <div class=\"content\">
                        <div class=\"header\"> @[[redefine-password-message-header]]@ </div>
                        <p>@[[redefine-password-message-content]]@</p>
                    </div>
                </div>
                <div class=\"field\">
                    <label>@[[redefine-password-pass-label]]@</label>
                    <input type=\"password\" name=\"senha\" placeholder=\"@[[redefine-password-pass-placeholder]]@\" autocomplete=\"new-password\">
                </div>
                <div class=\"field\">
                    <label>@[[redefine-password-pass-2-label]]@</label>
                    <input type=\"password\" name=\"senha-2\" placeholder=\"@[[redefine-password-pass-2-placeholder]]@\" autocomplete=\"new-password\">
                </div>
                <div class=\"ui hidden divider\">&nbsp;</div>
                <div class=\"ui error message\">&nbsp;</div>
                <div class=\"field\">
                    <button class=\"fluid ui button blue\">@[[redefine-password-button]]@</button>
                    <div class=\"ui hidden divider\">&nbsp;</div>
                    <div class=\"ui basic segment center aligned\">
                        <div class=\"\">@[[forgot-password-login-label]]@ <a href=\"@[[pagina#url-raiz]]@signin/\">@[[forgot-password-login-button]]@</a>
                        </div>
                        <div>@[[forgot-password-new-register-label]]@ <a href=\"@[[pagina#url-raiz]]@signup/\">@[[forgot-password-new-register-button]]@</a>
                        </div>
                    </div>
                </div>
                <input id=\"_gestor-redefine-password\" name=\"_gestor-redefine-password\" type=\"hidden\" value=\"1\">
                <input id=\"_gestor-redefine-password-token\" name=\"_gestor-redefine-password-token\" type=\"hidden\" value=\"#token#\">
            </form>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"920fbd11ee6d54345028e33ac3d7599c\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"920fbd11ee6d54345028e33ac3d7599c\"}',
            ],
            [
                'component_id' => 97,
                'user_id' => 1,
                'name' => 'Redefinir Senha Confirmacao',
                'id' => 'redefinir-senha-confirmacao',
                'language' => 'pt-br',
                'module' => 'perfil-usuario',
                'html' => '<div class=\"ui stackable two column centered grid\">
    <div class=\"column\">
        <div class=\"ui segment\">
            <div class=\"ui center aligned header large\">@[[redefine-password-confirmation-title]]@</div>
            <div class=\"ui hidden divider\">&nbsp;</div>
            <div class=\"ui icon green message\">
                <i class=\"user check icon\"></i>
                <div class=\"content\">
                    <div class=\"header\"> @[[redefine-password-confirmation-message-header]]@ </div>
                    <p>#message#</p>
                </div>
            </div>
            <div class=\"ui basic segment center aligned\">
                <div class=\"\">@[[forgot-password-login-label]]@ <a href=\"@[[pagina#url-raiz]]@signin/\">@[[forgot-password-login-button]]@</a>
                </div>
                <div>@[[forgot-password-new-register-label]]@ <a href=\"@[[pagina#url-raiz]]@signup/\">@[[forgot-password-new-register-button]]@</a>
                </div>
            </div>
        </div>
    </div>
</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"1e674e0f0326986bb2b77ec4130490ae\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"1e674e0f0326986bb2b77ec4130490ae\"}',
            ],
            [
                'component_id' => 98,
                'user_id' => 1,
                'name' => 'Sair Sistema',
                'id' => 'sair-sistema',
                'language' => 'pt-br',
                'module' => 'perfil-usuario',
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
                'component_id' => 99,
                'user_id' => 1,
                'name' => 'Validar Usuario',
                'id' => 'validar-usuario',
                'language' => 'pt-br',
                'module' => 'perfil-usuario',
                'html' => '<form id=\"_gestor-form-validar-usuario\" action=\"#form-action#\" method=\"post\" name=\"_gestor-form-validar-usuario\">
    <input id=\"_gestor-validar-usuario\" name=\"_gestor-validar-usuario\" type=\"hidden\" value=\"1\">
    <input id=\"_gestor-validar-usuario-querystring\" name=\"_gestor-validar-usuario-querystring\" type=\"hidden\" value=\"#form-querystring#\">
    <input id=\"_gestor-validar-usuario-fingerprint\" name=\"_gestor-validar-usuario-fingerprint\" type=\"hidden\">
</form>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"df58b7e5c554fb44a338c46b5bc2f8a6\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"df58b7e5c554fb44a338c46b5bc2f8a6\"}',
            ],
            [
                'component_id' => 100,
                'user_id' => 1,
                'name' => 'Layout Email Baixa Voucher',
                'id' => 'layout-email-baixa-voucher',
                'language' => 'pt-br',
                'module' => 'servicos',
                'html' => '<h3>Voucher utilizado no estabelecimento.</h3>
<p>Caro(a) <b>#nome#</b>,</p>
<p>O voucher <b>#voucherID#</b> foi apresentado no estabelecimento, validado com a chave de segurança vinculado ao mesmo e o lojista então deu baixa no sistema com o estado <b>Usado</b>.</p>
#assinatura#',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"5c695e12cc34c9fc5e1683cb39e83780\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"5c695e12cc34c9fc5e1683cb39e83780\"}',
            ],
            [
                'component_id' => 101,
                'user_id' => 1,
                'name' => 'Layout Email Voucher',
                'id' => 'layout-email-voucher',
                'language' => 'pt-br',
                'module' => 'servicos',
                'html' => '<h3>Voucher enviado por email.</h3>
<p>Segue o voucher <b>#voucherID#</b> em anexo neste email.</p>
#assinatura#',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"346b2189361de57a7ddda8a8e43f89b5\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"346b2189361de57a7ddda8a8e43f89b5\"}',
            ],
            [
                'component_id' => 102,
                'user_id' => 1,
                'name' => 'Templates Seletores Lista',
                'id' => 'templates-seletores-lista',
                'language' => 'pt-br',
                'module' => 'templates',
                'html' => '<div class=\"ui four doubling cards\">
    <!-- card < -->
    <div class=\"ui fluid raised card templateCont\" data-id=\"#template-id#\" data-type=\"#template-tipo#\">
        <div class=\"image fileImage\">
            <img>
            <div class=\"fileImage\">
                <img src=\"#img-src#\">
            </div>
        </div>
        <div class=\"content\">
            <div class=\"ui small header\">
                <!-- padrao < --><a class=\"ui teal ribbon label\">#padrao-texto#</a><!-- padrao > -->
                <span>#nome#</span>
           	</div>
            <div class=\"description\">
                <div class=\"ui list\">
                    <div class=\"item\">
                        <i class=\"calendar alternate outline icon\"></i>
                        #data#
                    </div>
                    <div class=\"item\">
                        <i class=\"info circle icon\"></i>
                        #tipo#
                    </div>
                </div>
            </div>
        </div>
        <div class=\"extra content center aligned botoesMargem\">
            <!-- btn-select < --><button class=\"ui blue button templateSelect\" data-dados=\"#template-data#\" data-tooltip=\"@[[list-button-tooltip-select]]@\" data-inverted=\"\">@[[list-button-select]]@</button><!-- btn-select > -->
            <!-- btn-copy < --><a class=\"ui blue button templateCopy\" data-tooltip=\"@[[list-button-tooltip-copy]]@\" data-inverted=\"\" href=\"@[[pagina#url-raiz]]@@[[pagina#modulo-id]]@/adicionar/?modelo=#modelo#&tipo=#template-tipo#&id=#template-id#\">@[[list-button-copy]]@</a><!-- btn-copy > -->
            <!-- btn-active < --><a class=\"ui active positive button\">
                <i class=\"check circle outline icon\"></i>
                @[[list-button-active]]@
            </a><!-- btn-active > -->
            <!-- btn-activate < --><a class=\"ui orange button templateActivate\" data-tooltip=\"@[[list-button-tooltip-activate]]@\" data-inverted=\"\" href=\"@[[pagina#url-raiz]]@@[[pagina#modulo-id]]@/ativar/?modelo=#modelo#&tipo=#template-tipo#&id=#template-id#\">
            	<i class=\"circle outline icon\"></i>
            	@[[list-button-activate]]@
            </a><!-- btn-activate > -->
        </div>
    </div><!-- card > -->
</div>',
                'css' => '.fileImage{
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
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"c674bbb07b7a3e1668844ce6ba646107\",\"css\":\"72ffce1b31ede3a0a1d33d34872cb144\",\"combined\":\"5a84aff65bccac5ded7a6c0f4b74d35a\"}',
            ],
            [
                'component_id' => 103,
                'user_id' => 1,
                'name' => 'Sem Permissao Teste',
                'id' => 'sem-permissao-teste',
                'language' => 'pt-br',
                'module' => 'testes',
                'html' => 'Sem Permissão Teste',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"599d814ccd070be528a96bc560fadcef\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"599d814ccd070be528a96bc560fadcef\"}',
            ],
            [
                'component_id' => 104,
                'user_id' => 1,
                'name' => 'Layout Email Conta Ftp Senha Redefinida',
                'id' => 'layout-email-conta-ftp-senha-redefinida',
                'language' => 'pt-br',
                'module' => 'usuarios-hospedeiro',
                'html' => '<p>Caro(a) #nome#,</p>
<p>A senha da sua Conta FTP foi redefinida com sucesso!</p>
<p>Caso não tenha sido você, favor entrar em contato conosco reportando o problema.</p>
#assinatura#',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"e6828b756bf211d846b6265014c3d4ef\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"e6828b756bf211d846b6265014c3d4ef\"}',
            ],
            [
                'component_id' => 105,
                'user_id' => 1,
                'name' => 'Layout Email Esqueceu Senha Da Conta Ftp',
                'id' => 'layout-email-esqueceu-senha-da-conta-ftp',
                'language' => 'pt-br',
                'module' => 'usuarios-hospedeiro',
                'html' => '<p>Caro(a) #nome#,</p>
<p>Esqueceu-se da sua senha da conta FTP?</p>
<p><b>Redefinir Senha:</b> #url#</p>
<p>Lembrando que esta ligação vai expirar dentro de #expiracao# hora(s) e só pode ser utilizada uma vez.</p>
<p>Se não pretender alterar a sua senha da conta FTP, ignore e elimine esta mensagem.</p>
#assinatura#',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 15:38:00',
                'updated_at' => '2025-08-08 15:38:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"39ecfaad1cb0b009a1c00267b759e439\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"39ecfaad1cb0b009a1c00267b759e439\"}',
            ],
        ];

        $table = $this->table('components');
        $table->insert($data)->saveData();
    }
}
