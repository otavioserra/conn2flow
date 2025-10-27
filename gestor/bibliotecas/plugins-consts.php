<?php
/**
 * Biblioteca de Constantes para Sistema de Plugins
 *
 * Define constantes para códigos de saída, status de execução e helpers
 * para o instalador e gerenciador de plugins (Fase 1).
 * 
 * Mantém alinhamento com documentação em:
 * ai-workspace/prompts/plugins/modificar-plugins.md
 *
 * @version 1.0.0
 */

// ===== Códigos de Saída do Instalador de Plugins =====

/** @var int Instalação/atualização bem-sucedida */
if(!defined('PLG_EXIT_OK')) define('PLG_EXIT_OK', 0);

/** @var int Erro: parâmetros inválidos ou arquivo não encontrado */
if(!defined('PLG_EXIT_PARAMS_OR_FILE')) define('PLG_EXIT_PARAMS_OR_FILE', 10);

/** @var int Erro: falha na validação do plugin */
if(!defined('PLG_EXIT_VALIDATE')) define('PLG_EXIT_VALIDATE', 11);

/** @var int Erro: falha ao mover/copiar arquivos do plugin */
if(!defined('PLG_EXIT_MOVE')) define('PLG_EXIT_MOVE', 12);

/** @var int Erro: falha no download do plugin */
if(!defined('PLG_EXIT_DOWNLOAD')) define('PLG_EXIT_DOWNLOAD', 20);

/** @var int Erro: arquivo ZIP inválido ou corrompido */
if(!defined('PLG_EXIT_ZIP_INVALID')) define('PLG_EXIT_ZIP_INVALID', 21);

/** @var int Erro: checksum não confere (arquivo adulterado) */
if(!defined('PLG_EXIT_CHECKSUM')) define('PLG_EXIT_CHECKSUM', 22);


// ===== Status de Execução =====

/** @var string Status: sistema ocioso, sem operações em andamento */
if(!defined('PLG_STATUS_IDLE')) define('PLG_STATUS_IDLE','idle');

/** @var string Status: instalação de plugin em andamento */
if(!defined('PLG_STATUS_INSTALANDO')) define('PLG_STATUS_INSTALANDO','instalando');

/** @var string Status: atualização de plugin em andamento */
if(!defined('PLG_STATUS_ATUALIZANDO')) define('PLG_STATUS_ATUALIZANDO','atualizando');

/** @var string Status: erro durante operação */
if(!defined('PLG_STATUS_ERRO')) define('PLG_STATUS_ERRO','erro');

/** @var string Status: operação concluída com sucesso */
if(!defined('PLG_STATUS_OK')) define('PLG_STATUS_OK','ok');


// ===== Funções Helper =====

/**
 * Converte código de saída em label descritivo.
 *
 * Função auxiliar para depuração que traduz códigos numéricos
 * de saída em strings descritivas legíveis.
 *
 * @param int $code Código de saída do instalador de plugins.
 * @return string Label descritivo do código (ex: 'OK', 'DOWNLOAD', 'UNKNOWN').
 */
if(!function_exists('plg_exit_code_label')){
    function plg_exit_code_label(int $code): string {
        switch($code){
            case PLG_EXIT_OK: return 'OK';
            case PLG_EXIT_PARAMS_OR_FILE: return 'PARAMS_OR_FILE';
            case PLG_EXIT_VALIDATE: return 'VALIDATE';
            case PLG_EXIT_MOVE: return 'MOVE';
            case PLG_EXIT_DOWNLOAD: return 'DOWNLOAD';
            case PLG_EXIT_ZIP_INVALID: return 'ZIP_INVALID';
            case PLG_EXIT_CHECKSUM: return 'CHECKSUM';
        }
        return 'UNKNOWN';
    }
}
?>
