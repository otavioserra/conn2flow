<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Remove índice único (id, language) da tabela componentes e recria índice normal.
 * Mantém integridade apenas pela PK id_componentes.
 * Reversível: recoloca índice como único no rollback.
 */
final class AlterComponentesIndexIdLanguage extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('componentes');
        if ($table->hasIndex(['id','language'])) {
            $table->removeIndex(['id','language'])->save();
        }
        if (!$table->hasIndex(['id','language'])) {
            $table->addIndex(['id','language'])->save();
        }
    }

    public function down(): void
    {
        $table = $this->table('componentes');
        if ($table->hasIndex(['id','language'])) {
            $table->removeIndex(['id','language'])->save();
        }
        if (!$table->hasIndex(['id','language'])) {
            $table->addIndex(['id','language'], ['unique'=>true])->save();
        }
    }
}
