<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts', ['id' => 'id_hosts']);
        $table->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('pub_id', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('pre_configurado', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('instalado', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('configurado', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('atualizar', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('user_cpanel', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('user_ftp', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('user_db', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('server', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('dominio', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('data_modificacao', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('chave_publica', 'text', ['null' => true])
              ->addColumn('gestor_cliente_versao', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('gestor_cliente_versao_num', 'integer', ['null' => true, 'default' => null])
              ->addColumn('dominio_proprio', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('dominio_proprio_url', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('google_recaptcha_ativo', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('google_recaptcha_site', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('google_recaptcha_secret', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('google_recaptcha_v2_ativo', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('google_recaptcha_v2_site', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('google_recaptcha_v2_secret', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('google_recaptcha_tipo', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->create();
    }
}