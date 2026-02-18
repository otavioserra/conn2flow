<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddHooksToModulosTable extends AbstractMigration
{
    /**
     * Adiciona coluna 'hooks' na tabela 'modulos'.
     * 
     * A coluna 'hooks' indica se o módulo possui hooks registrados
     * no seu arquivo JSON de configuração. Quando NOT NULL (1),
     * a plataforma de gateways irá carregar o JSON do módulo e
     * buscar os arquivos de hook configurados.
     * 
     * Valor NULL = sem hooks (padrão)
     * Valor 1    = possui hooks configurados no JSON
     */
    public function change(): void
    {
        $table = $this->table('modulos', ['id' => 'id_modulos']);
        $table->addColumn('hooks', 'integer', [
                  'limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY,
                  'null' => true,
                  'default' => null,
                  'after' => 'host',
                  'comment' => 'Indica se o módulo possui hooks configurados no JSON (NULL=não, 1=sim)',
              ])
              ->update();
    }
}
