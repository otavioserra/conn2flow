<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterModulosOperacoesTableAddModuloIdRemoveIdModulos extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('modulos_operacoes');
        $table->removeColumn('id_modulos')
              ->addColumn('modulo_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->update();
    }
}
