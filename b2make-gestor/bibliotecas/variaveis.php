<?php

global $_GESTOR;

$_GESTOR['biblioteca-variaveis']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

// ===== Funções principais

function variaveis_sistema($grupo,$id = false){
	/**********
		Descrição: retornar uma variável do sistema
	**********/
	
	global $_VARIAVEIS_SISTEMA;
	
	// ===== Parâmetros
	
	// grupo - String - Obrigatório - Grupo da variável.
	// id - String - Opcional - id da variável.
	
	// ===== 
	
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
		
		if($variaveis){
			foreach($variaveis as $var){
				$_VARIAVEIS_SISTEMA[$grupo][$var['id']] = $var['valor'];
			}
		} else {
			$_VARIAVEIS_SISTEMA[$grupo] = Array();
		}
	}
	
	if($id){
		return (isset($_VARIAVEIS_SISTEMA[$grupo][$id]) ? $_VARIAVEIS_SISTEMA[$grupo][$id] : null );
	} else {
		return $_VARIAVEIS_SISTEMA[$grupo];
	}
}

function variaveis_sistema_incluir($grupo,$id,$valor,$tipo = 'string'){
	/**********
		Descrição: incluir uma variável do sistema.
	**********/
	
	// ===== Parâmetros
	
	// grupo - String - Obrigatório - Grupo da variável.
	// id - String - Obrigatório - id da variável.
	// valor - String - Obrigatório - Valor que será incluído.
	// tipo - String - Opcional - Tipo da variável incluído.
	
	// ===== 
	
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

function variaveis_sistema_atualizar($grupo,$id,$valor){
	/**********
		Descrição: atualizar uma variável do sistema.
	**********/
	
	global $_VARIAVEIS_id;
	
	// ===== Parâmetros
	
	// grupo - String - Obrigatório - Grupo da variável.
	// id - String - Obrigatório - id da variável.
	// valor - String - Obrigatório - Valor que será incluído.
	
	// ===== 
	
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