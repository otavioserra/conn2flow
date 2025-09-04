<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona coluna `plugin` em usuarios_perfis, usuarios_perfis_modulos e usuarios_perfis_modulos_operacoes.
 * Motivo: rastrear perfis e permissões criados por plugins para futura remoção segura.
 * Exclui tabelas indicadas pelo produto (acessos, arquivos, etc.).
 */
final class AlterUsuariosPerfisPermissoesAddPlugin extends AbstractMigration
{
    public function change(): void
    {
        $targets = [
            'usuarios_perfis' => [ ['plugin','id'] ],
            'usuarios_perfis_modulos' => [ ['plugin','perfil','modulo'] ],
            'usuarios_perfis_modulos_operacoes' => [ ['plugin','perfil','operacao'] ],
        ];
        foreach ($targets as $tableName => $indexes) {
            if (!$this->hasTable($tableName)) continue;
            $t = $this->table($tableName);
            if (!$t->hasColumn('plugin')) {
                // Inserir após coluna 'id' lógica se existir, senão após primeira coluna textual
                $after = $t->hasColumn('id') ? 'id' : ( $t->hasColumn('nome') ? 'nome' : null );
                $opts = ['limit'=>255,'null'=>true,'default'=>null];
                if ($after) $opts['after']=$after;
                $t->addColumn('plugin','string',$opts);
            }
            // Índices compostos conforme lista
            foreach ($indexes as $cols) {
                $idxName = 'idx_'.$tableName.'_'.implode('_',$cols);
                $allExist = true; foreach ($cols as $c) { if (!$t->hasColumn($c)) { $allExist=false; break; } }
                if ($allExist && !$t->hasIndexByName($idxName)) {
                    $t->addIndex($cols, ['name'=>$idxName]);
                }
            }
            $t->update();
        }
    }
}
