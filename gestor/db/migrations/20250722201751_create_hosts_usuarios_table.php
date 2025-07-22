<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsUsuariosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_usuarios', ['id' => false, 'primary_key' => ['id_hosts_usuarios']]);
        $table->addColumn('id_hosts_usuarios', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_usuarios_perfis', 'integer', ['null' => true])
              ->addColumn('nome_conta', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('usuario', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('senha', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('email', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('telefone', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('primeiro_nome', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('ultimo_nome', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('nome_do_meio', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('cnpj_ativo', 'boolean', ['null' => true])
              ->addColumn('cpf', 'string', ['null' => true, 'limit' => 30])
              ->addColumn('cnpj', 'string', ['null' => true, 'limit' => 30])
              ->addColumn('ppp_remembered_card_hash', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}