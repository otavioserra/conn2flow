<?php
/**
 * Biblioteca de variáveis do sistema.
 *
 * Gerencia variáveis de configuração do sistema armazenadas no banco de dados.
 * Fornece funções para leitura, inclusão e atualização de variáveis organizadas
 * por grupos e módulos.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.0
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-variaveis']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

/**
 * Retorna variável(is) do sistema.
 *
 * Busca variáveis de sistema do banco de dados e as armazena em cache
 * na variável global $_VARIAVEIS_SISTEMA para reutilização. As variáveis
 * são organizadas por grupos.
 *
 * @global array $_VARIAVEIS_SISTEMA Cache de variáveis do sistema.
 * 
 * @param string $grupo Grupo da variável (obrigatório).
 * @param string|false $id ID específico da variável (opcional).
 * 
 * @return string|array|null Se $id fornecido, retorna o valor da variável específica.
 *                           Se $id não fornecido, retorna array com todas as variáveis do grupo.
 *                           Retorna null se variável específica não existir.
 */
function variaveis_sistema($grupo,$id = false){
	global $_VARIAVEIS_SISTEMA;
	
	// Carrega variáveis do grupo do banco se ainda não estiverem em cache
	if(!isset($_VARIAVEIS_SISTEMA[$grupo])){
		$variaveis = banco_select(Array(
			'tabela' => 'variaveis',
			'campos' => Array(
				'id',
				'valor',
			),
			'extra' => 
				"WHERE modulo='_sistema'"
				." AND grupo='".$grupo."'"
		));
		
		// Monta array associativo com as variáveis
		if($variaveis){
			foreach($variaveis as $var){
				$_VARIAVEIS_SISTEMA[$grupo][$var['id']] = $var['valor'];
			}
		} else {
			$_VARIAVEIS_SISTEMA[$grupo] = Array();
		}
	}
	
	// Retorna variável específica ou todas do grupo
	if($id){
		return (isset($_VARIAVEIS_SISTEMA[$grupo][$id]) ? $_VARIAVEIS_SISTEMA[$grupo][$id] : null );
	} else {
		return $_VARIAVEIS_SISTEMA[$grupo];
	}
}

/**
 * Inclui uma nova variável do sistema.
 *
 * Verifica se a variável já existe e, se não existir, cria um novo
 * registro na tabela de variáveis do sistema.
 *
 * @param string $grupo Grupo da variável (obrigatório).
 * @param string $id ID da variável (obrigatório).
 * @param string $valor Valor que será incluído (obrigatório).
 * @param string $tipo Tipo da variável (opcional, padrão: 'string').
 * 
 * @return void
 */
function variaveis_sistema_incluir($grupo,$id,$valor,$tipo = 'string'){
	// Verifica se variável já existe
	$variaveis = banco_select(Array(
		'unico' => true,
		'tabela' => 'variaveis',
		'campos' => Array(
			'id_variaveis',
		),
		'extra' => 
			"WHERE modulo='_sistema'"
			." AND grupo='".$grupo."'"
			." AND id='".$id."'"
	));
	
	// Cria nova variável se não existir
	if(!$variaveis){
		banco_insert_name_campo('modulo','_sistema');
		banco_insert_name_campo('id',$id);
		banco_insert_name_campo('valor',(isset($valor) ? $valor : 'NULL'),(isset($valor) ? false : true));
		banco_insert_name_campo('tipo',$tipo);
		banco_insert_name_campo('grupo',$grupo);
		
		banco_insert_name
		(
			banco_insert_name_campos(),
			"variaveis"
		);
	}
}

/**
 * Atualiza o valor de uma variável do sistema.
 *
 * Atualiza o valor de uma variável existente no banco de dados
 * identificada pelo grupo e ID fornecidos.
 *
 * @global array $_VARIAVEIS_id Variável global não utilizada (possivelmente legado).
 * 
 * @param string $grupo Grupo da variável (obrigatório).
 * @param string $id ID da variável (obrigatório).
 * @param string $valor Novo valor que será atribuído (obrigatório).
 * 
 * @return void
 */
function variaveis_sistema_atualizar($grupo,$id,$valor){
	global $_VARIAVEIS_id;
	
	// Executa update no banco de dados
	banco_update
	(
		"valor=".(isset($valor) ? "'".banco_escape_field($valor)."'" : "NULL"),
		"variaveis",
		"WHERE modulo='_sistema'"
		." AND grupo='".$grupo."'"
		." AND id='".$id."'"
	);
}

?>