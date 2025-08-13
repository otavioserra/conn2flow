<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Converte o índice único (id, language) da tabela layouts em índice normal.
 * Mantém somente a PK id_layouts como garantia de unicidade numérica.
 * Down reverte para índice único novamente.
 */
final class AlterLayoutsIndexIdLanguage extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('layouts');
        if ($table->hasIndex(['id','language'])) {
            $table->removeIndex(['id','language'])->save();
        }
        if (!$table->hasIndex(['id','language'])) {
            $table->addIndex(['id','language'])->save(); // índice não único
        }
    }

    public function down(): void
    {
        $table = $this->table('layouts');
        if ($table->hasIndex(['id','language'])) {
            $table->removeIndex(['id','language'])->save();
        }
        if (!$table->hasIndex(['id','language'])) {
            $table->addIndex(['id','language'], ['unique'=>true])->save();
        }
    }
}
