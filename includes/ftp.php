<?php
/***********************************************************************************************************************************
	AgeOne Digital Marketing.
	Setor de Desenvolvimento de Sistemas - Desenvolvimento Para Web
	Contato: webmaster@ageone.com.br
	
	Copyright (c) 2012 AgeOne Digital Marketing

	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation
	files (the "Software"), to deal in the Software without
	restriction, including without limitation the rights to use,
	copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following
	conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
***********************************************************************************************************************************/

$_VERSAO_MODULO_INCLUDE				=	'1.0.0';

function ftp_conectar(){
	global $_VARS;
	global $_CONEXAO_FTP;
	global $_CONEXAO_FTP_FALHA;
	global $_ALERTA;
	
	$_CONEXAO_FTP_FALHA = false;
	
	if($_VARS['ftp']['usuario'] && $_VARS['ftp']['senha']){
		$_CONEXAO_FTP = ftp_connect($_VARS['ftp']['host']) or $_ALERTA = "ERRO FTP: Conexão com o Server FTP não realizada!";
		if($_CONEXAO_FTP){
			if(!ftp_login($_CONEXAO_FTP, $_VARS['ftp']['usuario'], $_VARS['ftp']['senha'])){
				$_ALERTA = "ERRO FTP: Usuário e/ou Senha do FTP inválido(s)!";
				$_CONEXAO_FTP_FALHA = true;
				ftp_fechar_conexao();
			}
		}
	} else {
		$_ALERTA = "ERRO FTP: Usuário e/ou Senha do FTP não estão definidos!";
		$_CONEXAO_FTP_FALHA = true;
	}
}

function ftp_fechar_conexao(){
	global $_CONEXAO_FTP;
	global $_ALERTA;
	global $_CONEXAO_FTP_FALHA;
	
	if(!$_CONEXAO_FTP){
		if(!$_CONEXAO_FTP_FALHA)$_ALERTA = 'ERRO FTP: Não há conexão com o servidor FTP.';
        return false;
	}
	
	ftp_close($_CONEXAO_FTP) or $_ALERTA = "ERRO FTP: Impossível fechar conexao com o Server FTP!";
	$_CONEXAO_FTP = false;
}

function ftp_definir_root(){
	global $_SYSTEM;
	global $_CONEXAO_FTP;
	global $_ROOT_FTP;
	global $_ALERTA;
	global $_CONEXAO_FTP_FALHA;
	
	if(!$_CONEXAO_FTP){
		if(!$_CONEXAO_FTP_FALHA)$_ALERTA = 'ERRO FTP: Não há conexão com o servidor FTP.';
        return false;
	}
	
	$root_aux = explode('/',$_SYSTEM['ROOT']);
	$path_aux = array_reverse(explode($_SYSTEM['SEPARADOR'],$_SYSTEM['PATH']));
	
	$nivel = 0;
	if($root_aux)
	foreach($root_aux as $root){
		if($root)$nivel++;
	}
	
	if($path_aux)
	foreach($path_aux as $path){
		if($path)$diretorios[] = $path;
	}
	
	$_ROOT_FTP = ftp_verificar_root($diretorios,$nivel);
	
	ftp_chdir($_CONEXAO_FTP,$_ROOT_FTP);
}

function ftp_verificar_root($diretorios,$nivel,$filhos = ''){
	global $_SYSTEM;
	global $_ALERTA;
	
	if(count($diretorios) > $nivel){
		$dir = '/'.$diretorios[$nivel].$filhos.'/'.$_SYSTEM['ROOT'];
		
		if(ftp_is_dir($dir)){
			return $dir;
		} else {
			$filhos = '/'.$diretorios[$nivel] . $filhos;
			return ftp_verificar_root($diretorios,$nivel+1,$filhos);
		}
	} else {
		$_ALERTA = 'ERRO FTP: Problemas com a definição do root do FTP.';
	}
}

function ftp_is_dir($directory){
	global $_CONEXAO_FTP;
	global $_ALERTA;
	global $_CONEXAO_FTP_FALHA;
	
	if(!$_CONEXAO_FTP){
		if(!$_CONEXAO_FTP_FALHA)$_ALERTA = 'ERRO FTP: Não há conexão com o servidor FTP.';
        return false;
	}
	
    $pushd = ftp_pwd($_CONEXAO_FTP);

    if($pushd !== false && @ftp_chdir($_CONEXAO_FTP, $directory)){
        ftp_chdir($_CONEXAO_FTP, $pushd);   
        return true;
    }

    return false;
}

function ftp_recursive_delete($directory){
	global $_CONEXAO_FTP;
	global $_ALERTA;
	global $_CONEXAO_FTP_FALHA;
	
    // Sanity check
    if (!is_resource($_CONEXAO_FTP) ||
        get_resource_type($_CONEXAO_FTP) !== 'FTP Buffer') {
		if(!$_CONEXAO_FTP_FALHA)$_ALERTA = 'ERRO FTP: Não há conexão com o servidor FTP.';
        return false;
    }
 
    // Init
    $i             = 0;
    $files         = array();
    $folders       = array();
    $statusnext    = false;
    $currentfolder = '';
 
	ftp_chdir($_CONEXAO_FTP,$directory);
 
    // Get raw file listing
    $list = ftp_rawlist($_CONEXAO_FTP, '-a', true);
 
    // Iterate listing
    foreach ($list as $current) {
        // An empty element means the next element will be the new folder
        if (empty($current) && !$statusnext2) {
            $statusnext = true;
            continue;
        }
 
        // Save the current folder
        if ($statusnext === true) {
			$split = preg_split('[ ]', $current, 9, PREG_SPLIT_NO_EMPTY);
			$entry = $split[8];
			
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			
			
            $currentfolder = substr($current, 0, -1);
			$currentfolder = preg_replace('/\.\//i', '/', $currentfolder);
            $statusnext = false;
            $statusnext2 = true;
            continue;
        }
		
		$statusnext2 = false;
 
        // Split the data into chunks
        $split = preg_split('[ ]', $current, 9, PREG_SPLIT_NO_EMPTY);
        $entry = $split[8];
        $isdir = ($split[0]{0} === 'd') ? true : false;
 
        // Skip pointers
        if ($entry === '.' || $entry === '..') {
            continue;
        }
 
        // Build the file and folder list
        if ($isdir === true) {
            $folders[] = $currentfolder . '/' . $entry;
        } else {
            $files[] = $currentfolder . '/' . $entry;
        }
    }
 
	echo 'Files<br>';
    // Delete all the files
    foreach ($files as $file) {
		echo $file.'<br>';
        //ftp_delete($_CONEXAO_FTP, $file);
    }
	
	echo 'Folders<br>';
    // Delete all the directories
    // Reverse sort the folders so the deepest directories are unset first
    rsort($folders);
    foreach ($folders as $folder) {
		echo $folder.'<br>';
        //ftp_rmdir($_CONEXAO_FTP, $folder);
    }
 
    // Delete the final folder and return its status
   /* return ftp_rmdir($_CONEXAO_FTP, $directory); */
	
	return;
}

function ftp_put_recursive($src_dir, $dst_dir, $first_exec = true){
	global $_CONEXAO_FTP;
	global $_ALERTA;
	global $_CONEXAO_FTP_FALHA;
	
	if(!$_CONEXAO_FTP){
		if(!$_CONEXAO_FTP_FALHA)$_ALERTA = 'ERRO FTP: Não há conexão com o servidor FTP.';
        return false;
	}
	
	if($first_exec)if(!ftp_is_dir($dst_dir))ftp_mkdir($_CONEXAO_FTP, $dst_dir);
	
	$d = dir($src_dir);
	while($file = $d->read()){ // do this for each file in the directory
		if ($file != "." && $file != "..") { // to prevent an infinite loop
			if (is_dir($src_dir."/".$file)) { // do the following if it is a directory
				if (!ftp_nlist($_CONEXAO_FTP, $dst_dir."/".$file)) {
					ftp_mkdir($_CONEXAO_FTP, $dst_dir."/".$file); // create directories that do not yet exist
				}
				ftp_put_recursive($src_dir."/".$file, $dst_dir."/".$file, false); // recursive part
			} else {
				$upload = ftp_put($_CONEXAO_FTP, $dst_dir."/".$file, $src_dir."/".$file, FTP_BINARY); // put the files
			}
		}
	}
	$d->close();
}

?>