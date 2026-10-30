<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'equipe';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/equipe.json'), true);

function equipe_var($id){
	global $_GESTOR;

	return gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => $id));
}

function equipe_usuario_id(){
	$usuario = gestor_usuario();

	return (int)($usuario['id_usuarios'] ?? 0);
}

/**
 * Preenche as linhas de um bloco da página a partir dos dados.
 */
function equipe_blocos($pagina,$bloco,$itens,$preencher){
	$modelo = modelo_tag_val($pagina,'<!-- '.$bloco.' < -->','<!-- '.$bloco.' > -->');
	$html = '';

	foreach($itens as $item){
		$html .= $preencher($modelo,$item);
	}

	return modelo_tag_in($pagina,'<!-- '.$bloco.' < -->','<!-- '.$bloco.' > -->',$html);
}

/**
 * Mantém ou retira um bloco opcional da página.
 */
function equipe_bloco_condicional($pagina,$bloco,$manter){
	$conteudo = $manter ? modelo_tag_val($pagina,'<!-- '.$bloco.' < -->','<!-- '.$bloco.' > -->') : '';

	return modelo_tag_in($pagina,'<!-- '.$bloco.' < -->','<!-- '.$bloco.' > -->',$conteudo);
}

function equipe_raiz(){
	global $_GESTOR;

	$id_titular = equipe_usuario_id();

	// O vínculo do módulo com o perfil já depende da chave e do limite; aqui entra também o
	// sub-usuário e a conta inativa, que não gerem equipe.
	if(!$id_titular || !usuario_equipe_habilitada($id_titular)){
		gestor_redirecionar('dashboard/');
	}

	gestor_pagina_javascript_incluir();

	$cota = usuario_equipe_cota($id_titular);
	$membros = usuario_equipe_membros($id_titular);
	$perfis = usuario_equipe_perfis($id_titular);
	$disponiveis = usuario_equipe_permissoes_disponiveis($id_titular);

	$nomesPerfis = Array();
	$opcoes = '';

	foreach($perfis as $perfil){
		$nomesPerfis[(int)$perfil['id_usuarios_perfis']] = $perfil['nome'];
		$opcoes .= '<option value="'.(int)$perfil['id_usuarios_perfis'].'">'.htmlspecialchars((string)$perfil['nome']).'</option>';
	}

	$assentos = equipe_var($cota['ilimitado'] ? 'team-seats-unlimited' : 'team-seats-used');
	$assentos = modelo_var_troca($assentos,'#usados#',(string)$cota['usados']);
	$assentos = modelo_var_troca($assentos,'#limite#',(string)$cota['limite']);

	$pagina = $_GESTOR['pagina'];

	$pagina = modelo_var_troca($pagina,'#assentos#',htmlspecialchars($assentos));
	$pagina = modelo_var_troca_tudo($pagina,'#opcoes-perfil#',$opcoes);

	$pagina = equipe_bloco_condicional($pagina,'novo-membro',$cota['disponivel'] && $perfis);
	$pagina = equipe_bloco_condicional($pagina,'sem-assento',!$cota['disponivel']);
	$pagina = equipe_bloco_condicional($pagina,'sem-perfil',$cota['disponivel'] && !$perfis);
	$pagina = equipe_bloco_condicional($pagina,'membros-vazio',!$membros);
	$pagina = equipe_bloco_condicional($pagina,'membros-tabela',(bool)$membros);
	$pagina = equipe_bloco_condicional($pagina,'perfis-vazio',!$perfis);
	$pagina = equipe_bloco_condicional($pagina,'perfis-tabela',(bool)$perfis);

	$pagina = equipe_blocos($pagina,'membro',$membros,function($linha,$membro) use ($opcoes,$nomesPerfis){
		$ativo = ($membro['status'] === 'A');
		$id_perfil = (int)$membro['id_usuarios_perfis'];

		$selecao = str_replace('value="'.$id_perfil.'"','value="'.$id_perfil.'" selected',$opcoes);

		// Membro sem perfil de equipe (criado antes dos perfis) precisa escolher um.
		if(!isset($nomesPerfis[$id_perfil])) $selecao = '<option value="">'.htmlspecialchars(equipe_var('team-profile-choose')).'</option>'.$selecao;

		$linha = modelo_var_troca($linha,'#nome#',htmlspecialchars((string)$membro['nome']));
		$linha = modelo_var_troca($linha,'#email#',htmlspecialchars((string)$membro['email']));
		$linha = modelo_var_troca($linha,'#selecao-perfil#',$selecao);
		$linha = modelo_var_troca($linha,'#selo-status#',$ativo ? 'c2fc-selo-ativo' : 'c2fc-selo-inativo');
		$linha = modelo_var_troca($linha,'#status#',equipe_var($ativo ? 'team-status-active' : 'team-status-inactive'));
		$linha = modelo_var_troca_tudo($linha,'#id#',(string)(int)$membro['id_usuarios']);
		$linha = modelo_var_troca($linha,'#proximo-status#',$ativo ? 'I' : 'A');
		$linha = modelo_var_troca($linha,'#acao#',equipe_var($ativo ? 'team-deactivate' : 'team-activate'));

		return $linha;
	});

	$pagina = equipe_blocos($pagina,'perfil',$perfis,function($linha,$perfil) use ($disponiveis){
		$nomes = Array();

		foreach($perfil['modulos'] as $modulo){
			if(isset($disponiveis['modulos'][$modulo])) $nomes[] = $disponiveis['modulos'][$modulo]['nome'];
		}

		$linha = modelo_var_troca_tudo($linha,'#nome#',htmlspecialchars((string)$perfil['nome'],ENT_QUOTES));
		$linha = modelo_var_troca($linha,'#modulos#',htmlspecialchars(implode(', ',$nomes)));
		$linha = modelo_var_troca_tudo($linha,'#id#',(string)(int)$perfil['id_usuarios_perfis']);
		$linha = modelo_var_troca($linha,'#json-modulos#',htmlspecialchars(json_encode($perfil['modulos']),ENT_QUOTES));
		$linha = modelo_var_troca($linha,'#json-operacoes#',htmlspecialchars(json_encode($perfil['operacoes']),ENT_QUOTES));

		return $linha;
	});

	$pagina = equipe_blocos($pagina,'opcao-modulo',$disponiveis['modulos'],function($linha,$modulo){
		$linha = modelo_var_troca($linha,'#valor#',htmlspecialchars((string)$modulo['id'],ENT_QUOTES));
		$linha = modelo_var_troca($linha,'#rotulo#',htmlspecialchars((string)$modulo['nome']));

		return $linha;
	});

	$pagina = equipe_bloco_condicional($pagina,'grupo-operacoes',(bool)$disponiveis['operacoes']);

	$pagina = equipe_blocos($pagina,'opcao-operacao',$disponiveis['operacoes'],function($linha,$operacao) use ($disponiveis){
		$rotulo = $disponiveis['modulos'][$operacao['modulo_id']]['nome'].': '.$operacao['nome'];

		$linha = modelo_var_troca($linha,'#valor#',htmlspecialchars((string)$operacao['id'],ENT_QUOTES));
		$linha = modelo_var_troca($linha,'#rotulo#',htmlspecialchars($rotulo));

		return $linha;
	});

	$_GESTOR['pagina'] = $pagina;
}

// ==== Ajax

/**
 * Traduz o resultado de uma operação de equipe no envelope do AJAX do módulo.
 */
function equipe_ajax_resposta($resultado,$mensagem_ok){
	global $_GESTOR;

	$_GESTOR['ajax-json'] = !empty($resultado['ok'])
		? Array('status' => 'success', 'message' => equipe_var($mensagem_ok))
		: Array('status' => 'error', 'message' => equipe_var($resultado['erro']));
}

function equipe_ajax_membro_criar(){
	equipe_ajax_resposta(usuario_equipe_membro_criar(equipe_usuario_id(),Array(
		'nome' => $_REQUEST['nome'] ?? '',
		'email' => $_REQUEST['email'] ?? '',
		'senha' => $_REQUEST['senha'] ?? '',
		'id_perfil' => $_REQUEST['id_perfil'] ?? 0,
	)),'team-created-ok');
}

function equipe_ajax_membro_status(){
	equipe_ajax_resposta(usuario_equipe_membro_status(
		equipe_usuario_id(),
		(int)($_REQUEST['id'] ?? 0),
		(string)($_REQUEST['status'] ?? '')
	),'team-status-ok');
}

function equipe_ajax_membro_perfil(){
	equipe_ajax_resposta(usuario_equipe_membro_perfil(
		equipe_usuario_id(),
		(int)($_REQUEST['id'] ?? 0),
		(int)($_REQUEST['id_perfil'] ?? 0)
	),'team-status-ok');
}

function equipe_ajax_perfil_salvar(){
	equipe_ajax_resposta(usuario_equipe_perfil_salvar(equipe_usuario_id(),Array(
		'id_perfil' => $_REQUEST['id_perfil'] ?? 0,
		'nome' => $_REQUEST['nome'] ?? '',
		'modulos' => $_REQUEST['modulos'] ?? Array(),
		'operacoes' => $_REQUEST['operacoes'] ?? Array(),
	)),'team-profile-saved-ok');
}

function equipe_ajax_perfil_excluir(){
	equipe_ajax_resposta(usuario_equipe_perfil_excluir(equipe_usuario_id(),(int)($_REQUEST['id_perfil'] ?? 0)),'team-profile-deleted-ok');
}

// ==== Start

function equipe_start(){
	global $_GESTOR;

	gestor_incluir_bibliotecas();

	if($_GESTOR['ajax']){
		interface_ajax_iniciar();

		switch($_GESTOR['ajax-opcao']){
			case 'membro-criar': equipe_ajax_membro_criar(); break;
			case 'membro-status': equipe_ajax_membro_status(); break;
			case 'membro-perfil': equipe_ajax_membro_perfil(); break;
			case 'perfil-salvar': equipe_ajax_perfil_salvar(); break;
			case 'perfil-excluir': equipe_ajax_perfil_excluir(); break;
		}

		interface_ajax_finalizar();
	} else {
		$_GESTOR['tailwind-page-bundle'] = true;

		interface_iniciar();

		switch($_GESTOR['opcao']){
			case 'raiz': equipe_raiz(); break;
		}

		interface_finalizar();
	}
}

if(!defined('CONN2FLOW_DISABLE_MODULE_START')) equipe_start();

?>
