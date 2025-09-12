<?php
// Constantes de códigos de saída e status do instalador de plugins (Fase 1)
// Mantém alinhado com documentação em ai-workspace/prompts/plugins/modificar-plugins.md

if(!defined('PLG_EXIT_OK')) define('PLG_EXIT_OK', 0);
if(!defined('PLG_EXIT_PARAMS_OR_FILE')) define('PLG_EXIT_PARAMS_OR_FILE', 10);
if(!defined('PLG_EXIT_VALIDATE')) define('PLG_EXIT_VALIDATE', 11);
if(!defined('PLG_EXIT_MOVE')) define('PLG_EXIT_MOVE', 12);
if(!defined('PLG_EXIT_DOWNLOAD')) define('PLG_EXIT_DOWNLOAD', 20);
if(!defined('PLG_EXIT_ZIP_INVALID')) define('PLG_EXIT_ZIP_INVALID', 21);
if(!defined('PLG_EXIT_CHECKSUM')) define('PLG_EXIT_CHECKSUM', 22);

// Status_execucao possíveis
if(!defined('PLG_STATUS_IDLE')) define('PLG_STATUS_IDLE','idle');
if(!defined('PLG_STATUS_INSTALANDO')) define('PLG_STATUS_INSTALANDO','instalando');
if(!defined('PLG_STATUS_ATUALIZANDO')) define('PLG_STATUS_ATUALIZANDO','atualizando');
if(!defined('PLG_STATUS_ERRO')) define('PLG_STATUS_ERRO','erro');
if(!defined('PLG_STATUS_OK')) define('PLG_STATUS_OK','ok');

// Helper simples para mapear código em texto (depuração futura)
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
