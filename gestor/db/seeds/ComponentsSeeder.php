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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"08e2afea992a6b1cb333e9668cf61ed4\",\"css\":\"31f47e457d8193518413a8882a355b73\",\"combined\":\"30bc76cc2ee111cde91f356e70ed9f47\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"a84aa60d7ebd5b5aa0cb38b2a914a992\",\"css\":\"3891a26f5c9e3640f42e4b2b7752462b\",\"combined\":\"6733a7d95074af9ebc75c79975a9d747\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"710de9d0016dcd6f16d251acf94174af\",\"css\":\"09cb7a475aa6dd7d1ab15cd13cf60b2e\",\"combined\":\"11825ebafaaeb41467a29b83d4263708\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"868b6fef9951e71e79949c65e6da7c6a\",\"css\":\"450114044370c3e0721c33a0a844e980\",\"combined\":\"3a2f8ca6cd07f02ef50d398f212b4a3e\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"41097d2e6a1be3ac1d6ccd1c62b1f24e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"41097d2e6a1be3ac1d6ccd1c62b1f24e\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"5ed3ea43983eaba1d9e92d3a44704477\",\"css\":\"7407f3306be0b7b12b00435570ad2adf\",\"combined\":\"71ad7e23cad5b26022375387ff13b1ce\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"1b7c348d5daf974191dae9642623e47e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"1b7c348d5daf974191dae9642623e47e\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"fcbb4c74f9f39c6d4b900065c1271baa\",\"css\":\"266f3c531ac71faf91898995225c9e28\",\"combined\":\"484d4c9bd5682ac71fb0c5b2a9a21821\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"25f54805a5b3d1366d6057143764865b\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"25f54805a5b3d1366d6057143764865b\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"53c4b611141dd7f591d54c7b3008b9e0\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"53c4b611141dd7f591d54c7b3008b9e0\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"bdf6f3faa23c198eb4a1c3c9c7cf504b\",\"css\":\"7824db08cd6122ed374a255412ce7317\",\"combined\":\"320f064baba467544ca6752f57011ae9\"}',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d158caa2d30192b82c3890f47a1ff43e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d158caa2d30192b82c3890f47a1ff43e\"}',
            ],
            [
                'component_id' => 14,
                'user_id' => 1,
                'name' => 'Teste de Adição',
                'id' => 'teste-de-adicao',
                'language' => 'pt-br',
                'module' => null,
                'html' => 'dsasda',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ae97a3162c8302f195dd6f25ee1f68e7\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ae97a3162c8302f195dd6f25ee1f68e7\"}',
            ],
            [
                'component_id' => 15,
                'user_id' => 1,
                'name' => 'Teste de Adição',
                'id' => 'teste-de-adicao',
                'language' => 'pt-br',
                'module' => null,
                'html' => 'dsasda',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ae97a3162c8302f195dd6f25ee1f68e7\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ae97a3162c8302f195dd6f25ee1f68e7\"}',
            ],
            [
                'component_id' => 16,
                'user_id' => 1,
                'name' => 'Teste Novo Adição',
                'id' => 'teste-novo-adicao',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div>Olá enfermeira 2</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"819f609d0654dd34d0d1e5a446d6a60d\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"819f609d0654dd34d0d1e5a446d6a60d\"}',
            ],
            [
                'component_id' => 17,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"89732bcc975705e2ff50c499244fdfd7\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"89732bcc975705e2ff50c499244fdfd7\"}',
            ],
            [
                'component_id' => 18,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"f4ac4417442a16bad6b98a479d8b90bf\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"f4ac4417442a16bad6b98a479d8b90bf\"}',
            ],
            [
                'component_id' => 19,
                'user_id' => 1,
                'name' => 'Teste Adição Novo',
                'id' => 'teste-adicao-novo',
                'language' => 'pt-br',
                'module' => null,
                'html' => 'html mudou',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"52acf8327b36cb888c286e23b12bc4c4\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"52acf8327b36cb888c286e23b12bc4c4\"}',
            ],
            [
                'component_id' => 20,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"83c15c8bf7014cc823ac6c48140c80d7\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"83c15c8bf7014cc823ac6c48140c80d7\"}',
            ],
            [
                'component_id' => 21,
                'user_id' => 1,
                'name' => 'Teste de Adição',
                'id' => 'teste-de-adicao',
                'language' => 'pt-br',
                'module' => null,
                'html' => 'dsasda',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ae97a3162c8302f195dd6f25ee1f68e7\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ae97a3162c8302f195dd6f25ee1f68e7\"}',
            ],
            [
                'component_id' => 22,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"22051afd844c5fecee6d8446bf452a7b\",\"css\":\"2fc305ea67b93c579ed510c6b9b6064f\",\"combined\":\"e00426a5ad62c1493ad0bdd07b90eab1\"}',
            ],
            [
                'component_id' => 23,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"0b89e3ead925b789aadf4d2a8fddbe2c\",\"css\":\"2fc305ea67b93c579ed510c6b9b6064f\",\"combined\":\"c5c0681a60a610ca1f93bf5619f7d17f\"}',
            ],
            [
                'component_id' => 24,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"9cd8d8a62f5b765e5d5ace39d89c4281\",\"css\":\"450114044370c3e0721c33a0a844e980\",\"combined\":\"326fefdbe7bd877388595be7688524d3\"}',
            ],
            [
                'component_id' => 25,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d8f1a63646bffcb06861213446afb00d\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d8f1a63646bffcb06861213446afb00d\"}',
            ],
            [
                'component_id' => 26,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"3d19284fc38aee61afd664dbc4528c8c\",\"css\":\"0b03fcda6ed3bebaeb5eb77c7a49334b\",\"combined\":\"ebbfab3f094c37872fd2529dc54ad149\"}',
            ],
            [
                'component_id' => 27,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"001c6674758d21657d8bcbd9fc465791\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"001c6674758d21657d8bcbd9fc465791\"}',
            ],
            [
                'component_id' => 28,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"10efa73f0af65782570777b1d50fcdff\",\"css\":\"7407f3306be0b7b12b00435570ad2adf\",\"combined\":\"ad207759f39d87a5b77923d157142a62\"}',
            ],
            [
                'component_id' => 29,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"8fed0c7c7c60691eb6b8ae2ec480f82b\",\"css\":\"21e8ee98072bd7faefa1b157441303e2\",\"combined\":\"b8e957e24ba251da700ce06ace10b592\"}',
            ],
            [
                'component_id' => 30,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"b6e5cf2b9f19bd874dcddd65c402aec6\",\"css\":\"d7607f7c72347ea15d8561a91b8fbdbb\",\"combined\":\"5876c08564bd3aaf370d9730d8bf02af\"}',
            ],
            [
                'component_id' => 31,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"8c45b8654571a81fdd265e6ffe476621\",\"css\":\"3891a26f5c9e3640f42e4b2b7752462b\",\"combined\":\"637681f6ab10be1d55540872e559b6de\"}',
            ],
            [
                'component_id' => 32,
                'user_id' => 1,
                'name' => 'Página Mestre Conteúdos Padrão',
                'id' => 'pagina-mestre-conteudos-padrao',
                'language' => 'pt-br',
                'module' => null,
                'html' => '<div>Testes</div>',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"0a84799d2500b40dfd9074c43fd9d5dc\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"0a84799d2500b40dfd9074c43fd9d5dc\"}',
            ],
            [
                'component_id' => 33,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"1266231e9976b3fb8e440b87a83692c6\",\"css\":\"33eb7985229e8035ef8a4e3a90b15953\",\"combined\":\"043ecce8bc990502e2a87cc930dfdec2\"}',
            ],
            [
                'component_id' => 34,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"f92ddf1d0425dea7033850c80a0dd29a\",\"css\":\"816403ef80e10f7d59895cfc41a2e451\",\"combined\":\"23fa716dc7618d64f26bff90ec6b3867\"}',
            ],
            [
                'component_id' => 35,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"710de9d0016dcd6f16d251acf94174af\",\"css\":\"09cb7a475aa6dd7d1ab15cd13cf60b2e\",\"combined\":\"11825ebafaaeb41467a29b83d4263708\"}',
            ],
            [
                'component_id' => 36,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"663fe479ec12deac2602976353b83a63\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"663fe479ec12deac2602976353b83a63\"}',
            ],
            [
                'component_id' => 37,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"8fea7d61c681010d329d5962655d4499\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"8fea7d61c681010d329d5962655d4499\"}',
            ],
            [
                'component_id' => 38,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"0d45809688cb248b664bf2e7154b211e\",\"css\":\"b2c6c1e2d606c3274a0ed1d94433277a\",\"combined\":\"5b3a9bc6053adaca24b0196ab8335475\"}',
            ],
            [
                'component_id' => 39,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"01e4a448ee6b68991e2d5a0c9612dd7c\",\"css\":\"138c05d3f444f1c9087fc6f66be3f466\",\"combined\":\"3672ff06163494961d19d679618fb709\"}',
            ],
            [
                'component_id' => 40,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"16db8854c34ff2d39317281d4cba07e9\",\"css\":\"138c05d3f444f1c9087fc6f66be3f466\",\"combined\":\"f07941e173a58c0bacbbe3a6e2128e73\"}',
            ],
            [
                'component_id' => 41,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"66264c261cbba1fba366b57c2c887d2e\",\"css\":\"31f47e457d8193518413a8882a355b73\",\"combined\":\"8afdc0a9930a9c53fc3f2527b56d207e\"}',
            ],
            [
                'component_id' => 42,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"b53dbc687dddb364c2bd9ef18ea15e56\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"b53dbc687dddb364c2bd9ef18ea15e56\"}',
            ],
            [
                'component_id' => 43,
                'user_id' => 1,
                'name' => 'Admin Categorias Categorias Filho Info',
                'id' => 'admin-categorias-categorias-filho-info',
                'language' => 'pt-br',
                'module' => 'admin-categorias',
                'html' => '<div class=\"ui info visible message\">
    #message#
</div>
<!-- Teste módulo 2025-08-07 21:37:46 -->
<!-- Teste módulo 2025-08-08 12:53:59 -->',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"4635d4c56b32d83b8a1534e27e34bbe1\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"4635d4c56b32d83b8a1534e27e34bbe1\"}',
            ],
            [
                'component_id' => 44,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"eaa8260243937bfe68688462a0f13fb6\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"eaa8260243937bfe68688462a0f13fb6\"}',
            ],
            [
                'component_id' => 45,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"76ab062b296f7c2958a15f4b99e2feb5\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"76ab062b296f7c2958a15f4b99e2feb5\"}',
            ],
            [
                'component_id' => 46,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"67c62da199c18f63bd0b81b81fcaa45d\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"67c62da199c18f63bd0b81b81fcaa45d\"}',
            ],
            [
                'component_id' => 47,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"951b7dd20d4948f0bb859ef663a16ed0\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"951b7dd20d4948f0bb859ef663a16ed0\"}',
            ],
            [
                'component_id' => 48,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"6da8a46c3e87428f93e2c452d47058be\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"6da8a46c3e87428f93e2c452d47058be\"}',
            ],
            [
                'component_id' => 49,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"fb8509ec37fba0c6f75da3f5ba06446f\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"fb8509ec37fba0c6f75da3f5ba06446f\"}',
            ],
            [
                'component_id' => 50,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"baa16f5a64c0174eee45e1ffd9cb0eaf\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"baa16f5a64c0174eee45e1ffd9cb0eaf\"}',
            ],
            [
                'component_id' => 51,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"76ab062b296f7c2958a15f4b99e2feb5\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"76ab062b296f7c2958a15f4b99e2feb5\"}',
            ],
            [
                'component_id' => 52,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"401255d8c45c417adc4636a2837a8151\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"401255d8c45c417adc4636a2837a8151\"}',
            ],
            [
                'component_id' => 53,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"1914100d1be7ff5d604dc529548ae2fb\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"1914100d1be7ff5d604dc529548ae2fb\"}',
            ],
            [
                'component_id' => 54,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"bae98906634d996be369512d5bc3ecea\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"bae98906634d996be369512d5bc3ecea\"}',
            ],
            [
                'component_id' => 55,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"47bcf43efcd0cf2eb944d28d0f2470da\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"47bcf43efcd0cf2eb944d28d0f2470da\"}',
            ],
            [
                'component_id' => 56,
                'user_id' => 1,
                'name' => 'Gateway De Pagamento Paypal Reference Create',
                'id' => 'gateway-de-pagamento-paypal-reference-create',
                'language' => 'pt-br',
                'module' => 'gateways-de-pagamentos',
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
                'component_id' => 57,
                'user_id' => 1,
                'name' => 'Gateway De Pagamento Paypal Reference Return',
                'id' => 'gateway-de-pagamento-paypal-reference-return',
                'language' => 'pt-br',
                'module' => 'gateways-de-pagamentos',
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
                'component_id' => 58,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"157c3ccb190c70c0972056d1d0879c9b\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"157c3ccb190c70c0972056d1d0879c9b\"}',
            ],
            [
                'component_id' => 59,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"200dec14e69649f5cdaef69e69a6d413\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"200dec14e69649f5cdaef69e69a6d413\"}',
            ],
            [
                'component_id' => 60,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"c793acc907be9dadd30a864bb91199bc\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"c793acc907be9dadd30a864bb91199bc\"}',
            ],
            [
                'component_id' => 61,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"f3fadfede47d5dbbb964382cf961334b\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"f3fadfede47d5dbbb964382cf961334b\"}',
            ],
            [
                'component_id' => 62,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"eee3aa1582995f33a55e2740f994032a\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"eee3aa1582995f33a55e2740f994032a\"}',
            ],
            [
                'component_id' => 63,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"f6af971c191a377fea2473682c5693a1\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"f6af971c191a377fea2473682c5693a1\"}',
            ],
            [
                'component_id' => 64,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"1e2419cee519e83d43ac14701a3e7d12\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"1e2419cee519e83d43ac14701a3e7d12\"}',
            ],
            [
                'component_id' => 65,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ac25b6b57e86f201c0ae07f80a223b98\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ac25b6b57e86f201c0ae07f80a223b98\"}',
            ],
            [
                'component_id' => 66,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"8d0a921ccf3b608ad2516b682925a4d6\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"8d0a921ccf3b608ad2516b682925a4d6\"}',
            ],
            [
                'component_id' => 67,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"a768ccaaceced0523336b7bc0e9bd8b0\",\"css\":\"c120e3f8edf1c289de8b09f494657350\",\"combined\":\"b8ac19dca03a42e6d8c036e43f989785\"}',
            ],
            [
                'component_id' => 68,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"96f9a1d4b3ddf88118a8463c229ac3a9\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"96f9a1d4b3ddf88118a8463c229ac3a9\"}',
            ],
            [
                'component_id' => 69,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"7b29b87c4dbd3ef762dea86d07e79153\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"7b29b87c4dbd3ef762dea86d07e79153\"}',
            ],
            [
                'component_id' => 70,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ca140834fba75417079413be67dbcb02\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ca140834fba75417079413be67dbcb02\"}',
            ],
            [
                'component_id' => 71,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ed818747e1a8b2b240c592bd2c13e20e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ed818747e1a8b2b240c592bd2c13e20e\"}',
            ],
            [
                'component_id' => 72,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ed654959783ea7688d2d97299e75e4ea\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ed654959783ea7688d2d97299e75e4ea\"}',
            ],
            [
                'component_id' => 73,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"7d500c073fcab37ee26d34cde3d6deb6\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"7d500c073fcab37ee26d34cde3d6deb6\"}',
            ],
            [
                'component_id' => 74,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"292366d3bc933395ad85b70ccb29e76a\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"292366d3bc933395ad85b70ccb29e76a\"}',
            ],
            [
                'component_id' => 75,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"7de3f267dd996bf11cb08e0f0a1753fc\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"7de3f267dd996bf11cb08e0f0a1753fc\"}',
            ],
            [
                'component_id' => 76,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"0d0d4a7a820832e52745843df6b8f4ad\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"0d0d4a7a820832e52745843df6b8f4ad\"}',
            ],
            [
                'component_id' => 77,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"1fc1b859d973c6c71108309bbfc6288a\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"1fc1b859d973c6c71108309bbfc6288a\"}',
            ],
            [
                'component_id' => 78,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"7fd123a2020124a0f5f3a5c8fdb1afe2\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"7fd123a2020124a0f5f3a5c8fdb1afe2\"}',
            ],
            [
                'component_id' => 79,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"89732bcc975705e2ff50c499244fdfd7\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"89732bcc975705e2ff50c499244fdfd7\"}',
            ],
            [
                'component_id' => 80,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"f4ac4417442a16bad6b98a479d8b90bf\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"f4ac4417442a16bad6b98a479d8b90bf\"}',
            ],
            [
                'component_id' => 81,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"663fe479ec12deac2602976353b83a63\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"663fe479ec12deac2602976353b83a63\"}',
            ],
            [
                'component_id' => 82,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ab74c26cad2d258543bf1483437db942\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ab74c26cad2d258543bf1483437db942\"}',
            ],
            [
                'component_id' => 83,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"6e6710e729ba5ca2bfed4fca6024299c\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"6e6710e729ba5ca2bfed4fca6024299c\"}',
            ],
            [
                'component_id' => 84,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"e3789ede7f539ef233fbe752ad70bc93\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"e3789ede7f539ef233fbe752ad70bc93\"}',
            ],
            [
                'component_id' => 85,
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
                'created_at' => '2025-08-08 12:54:00',
                'updated_at' => '2025-08-08 12:54:00',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"7b70a83e60b0e499414fc807ab097aa7\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"7b70a83e60b0e499414fc807ab097aa7\"}',
            ],
            [
                'component_id' => 86,
                'user_id' => 1,
                'name' => 'Host Configuracao Manual',
                'id' => 'host-configuracao-manual',
                'language' => 'pt-br',
                'module' => 'host-configuracao-manual',
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
                'component_id' => 87,
                'user_id' => 1,
                'name' => 'Sincronizar Bancos',
                'id' => 'sincronizar-bancos',
                'language' => 'pt-br',
                'module' => 'modulos',
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
                'component_id' => 88,
                'user_id' => 1,
                'name' => 'Teste De Adicao Novo',
                'id' => 'teste-de-adicao-novo',
                'language' => 'pt-br',
                'module' => 'modulos-grupos',
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
                'component_id' => 89,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"906fcdb88f4cf293bc91e558e10e67c5\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"906fcdb88f4cf293bc91e558e10e67c5\"}',
            ],
            [
                'component_id' => 90,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"562eb703cf279fdec65c904b9b0cc8ae\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"562eb703cf279fdec65c904b9b0cc8ae\"}',
            ],
            [
                'component_id' => 91,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"25259d8999aef58bd1de9237e966a295\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"25259d8999aef58bd1de9237e966a295\"}',
            ],
            [
                'component_id' => 92,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"bd37456a11d550b952a2250627bfe3e3\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"bd37456a11d550b952a2250627bfe3e3\"}',
            ],
            [
                'component_id' => 93,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"7e4e5a4488720f91c42604d9b8654db8\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"7e4e5a4488720f91c42604d9b8654db8\"}',
            ],
            [
                'component_id' => 94,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"a12b249886e4075dc48ca3380c880f0e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"a12b249886e4075dc48ca3380c880f0e\"}',
            ],
            [
                'component_id' => 95,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"71603e548a8b6d82f361f1fb10f4cf43\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"71603e548a8b6d82f361f1fb10f4cf43\"}',
            ],
            [
                'component_id' => 96,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"6e6710e729ba5ca2bfed4fca6024299c\",\"css\":\"e4b0bc753ae73edf1aea03238ac8b8bb\",\"combined\":\"9c908e80b25d8b5c2701575235ad3e69\"}',
            ],
            [
                'component_id' => 97,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"269948c502ca2d65837f8abbef76a89a\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"269948c502ca2d65837f8abbef76a89a\"}',
            ],
            [
                'component_id' => 98,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"71bef62359404d276334517b6db99e90\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"71bef62359404d276334517b6db99e90\"}',
            ],
            [
                'component_id' => 99,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"020c66d7500f79babf577ff35a65a5c5\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"020c66d7500f79babf577ff35a65a5c5\"}',
            ],
            [
                'component_id' => 100,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d2bf3839042072d83a8d519e07a09b8d\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d2bf3839042072d83a8d519e07a09b8d\"}',
            ],
            [
                'component_id' => 101,
                'user_id' => 1,
                'name' => 'Sair Sistema',
                'id' => 'sair-sistema',
                'language' => 'pt-br',
                'module' => 'perfil-usuario',
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
                'component_id' => 102,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"3e5479f6effdc7f07c7758dbbae54c87\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"3e5479f6effdc7f07c7758dbbae54c87\"}',
            ],
            [
                'component_id' => 103,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"ebc88cbe7f7d28f5c98b7da1c7568883\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"ebc88cbe7f7d28f5c98b7da1c7568883\"}',
            ],
            [
                'component_id' => 104,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"d8b932a9797deef89540e9f93deed4d4\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"d8b932a9797deef89540e9f93deed4d4\"}',
            ],
            [
                'component_id' => 105,
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
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"bc361ac4500c7e161370d8de8adf7062\",\"css\":\"37bfbd6dfef8d769cb8ef590c2124951\",\"combined\":\"26088c3703c0601d3d93e62570503dce\"}',
            ],
            [
                'component_id' => 106,
                'user_id' => 1,
                'name' => 'Sem Permissao Teste',
                'id' => 'sem-permissao-teste',
                'language' => 'pt-br',
                'module' => 'testes',
                'html' => 'Sem Permissão Teste',
                'css' => null,
                'status' => 'A',
                'version' => 1,
                'created_at' => '2025-08-08 12:54:01',
                'updated_at' => '2025-08-08 12:54:01',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"599d814ccd070be528a96bc560fadcef\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"599d814ccd070be528a96bc560fadcef\"}',
            ],
            [
                'component_id' => 107,
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
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"62f5025db5f02261821553ce2073378b\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"62f5025db5f02261821553ce2073378b\"}',
            ],
            [
                'component_id' => 108,
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
                'created_at' => '2025-08-08 12:54:02',
                'updated_at' => '2025-08-08 12:54:02',
                'user_modified' => 0,
                'file_version' => '1.0',
                'checksum' => '{\"html\":\"45578f3f1c4c1aae2b6ca6fdcf98a90e\",\"css\":\"d41d8cd98f00b204e9800998ecf8427e\",\"combined\":\"45578f3f1c4c1aae2b6ca6fdcf98a90e\"}',
            ],
        ];

        $table = $this->table('components');
        $table->insert($data)->saveData();
    }
}
