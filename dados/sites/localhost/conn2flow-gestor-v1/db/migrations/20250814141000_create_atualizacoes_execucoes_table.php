<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

/**
 * Tabela: atualizacoes_execucoes
 * Registra cada execução do sistema de atualização (web ou CLI).
 * Campos principais:
 *  - id_atualizacoes_execucoes (PK)
 *  - session_id            (string) identificador curto usado na execução web
 *  - modo                  (string) full|only-files|only-db|files-without-db
 *  - release_tag           (string) tag/versão usada
 *  - checksum               (string) resumo SHA256 do artefato (quando disponível)
 *  - env_added              (integer) quantidade de variáveis .env adicionadas
 *  - stats_removed          (integer) arquivos removidos (wipe)
 *  - stats_copied           (integer) arquivos copiados/movidos
 *  - started_at             (timestamp)
 *  - finished_at            (timestamp null enquanto em andamento)
 *  - status                 (string) running|success|error
 *  - exit_code              (integer) código final (CLI) ou 0 sucesso
 *  - error_message          (text) mensagem de erro (se houver)
 *  - plan_json_path         (text) caminho do último plan-*.json
 *  - log_file_path          (text) caminho do log principal (atualizacoes-sistema-YYYYMMDD.log)
 *  - session_log_path       (text) caminho do log específico da sessão (temp/atualizacoes/sessions/<sid>.log)
 *  - created_at / updated_at (timestamps gerenciamento)
 */
final class CreateAtualizacoesExecucoesTable extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('atualizacoes_execucoes')) return;
        $t = $this->table('atualizacoes_execucoes', ['id' => 'id_atualizacoes_execucoes']);
    $t->addColumn('session_id','string',['limit'=>32,'null'=>true,'default'=>null])
          ->addColumn('modo','string',['limit'=>32,'null'=>true,'default'=>null])
          ->addColumn('release_tag','string',['limit'=>150,'null'=>true,'default'=>null])
          ->addColumn('checksum','string',['limit'=>150,'null'=>true,'default'=>null])
          ->addColumn('env_added','integer',['null'=>true,'default'=>null])
          ->addColumn('stats_removed','integer',['null'=>true,'default'=>null])
          ->addColumn('stats_copied','integer',['null'=>true,'default'=>null])
          ->addColumn('started_at','timestamp',['null'=>true,'default'=>null])
          ->addColumn('finished_at','timestamp',['null'=>true,'default'=>null])
          ->addColumn('status','string',['limit'=>32,'null'=>true,'default'=>null])
          ->addColumn('exit_code','integer',['null'=>true,'default'=>null])
          ->addColumn('error_message','text',['null'=>true,'default'=>null,'limit'=>MysqlAdapter::TEXT_REGULAR])
          ->addColumn('plan_json_path','text',['null'=>true,'default'=>null])
          ->addColumn('log_file_path','text',['null'=>true,'default'=>null])
          ->addColumn('session_log_path','text',['null'=>true,'default'=>null])
          ->addColumn('created_at','timestamp',['null'=>true,'default'=>null])
          ->addColumn('updated_at','timestamp',['null'=>true,'default'=>null])
          ->addIndex(['session_id'], ['name'=>'idx_atualizacoes_execucoes_session'])
          ->addIndex(['status'], ['name'=>'idx_atualizacoes_execucoes_status'])
          ->addIndex(['started_at'], ['name'=>'idx_atualizacoes_execucoes_started'])
          ->create();
    }
}
