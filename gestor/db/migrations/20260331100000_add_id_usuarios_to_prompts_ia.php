<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddIdUsuariosToPromptsIa extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('prompts_ia');
        $table->addColumn('id_usuarios', 'integer', [
                'null' => true,
                'default' => 1,
                'after' => 'id_prompts_ia',
            ])
            ->addIndex(['id_usuarios'])
            ->update();
    }
}
