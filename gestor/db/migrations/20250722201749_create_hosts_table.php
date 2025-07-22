<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts', ['id' => false, 'primary_key' => ['id_hosts']]);
        $table->addColumn('id_hosts', 'integer')
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('pub_id', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('pre_configurado', 'boolean', ['null' => true])
              ->addColumn('instalado', 'boolean', ['null' => true])
              ->addColumn('configurado', 'boolean', ['null' => true])
              ->addColumn('atualizar', 'boolean', ['null' => true])
              ->addColumn('user_cpanel', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('user_ftp', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('user_db', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('server', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('dominio', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->addColumn('chave_publica', 'text', ['null' => true])
              ->addColumn('gestor_cliente_versao', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('gestor_cliente_versao_num', 'integer', ['null' => true])
              ->addColumn('dominio_proprio', 'boolean', ['null' => true])
              ->addColumn('dominio_proprio_url', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('google_recaptcha_ativo', 'boolean', ['null' => true])
              ->addColumn('google_recaptcha_site', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('google_recaptcha_secret', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('google_recaptcha_v2_ativo', 'boolean', ['null' => true])
              ->addColumn('google_recaptcha_v2_site', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('google_recaptcha_v2_secret', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('google_recaptcha_tipo', 'string', ['null' => true, 'limit' => 100])
              ->create();
    }
}