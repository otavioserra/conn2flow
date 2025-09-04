<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterRecursosAddPluginId extends AbstractMigration
{
    public function change(): void
    {
        // Lista simplificada: evitamos índices em colunas potencialmente TEXT (ex: caminho, grupo)
        $alvos = [
            'layouts' => [ ['colunas'=>['id','language']] ],
            'paginas' => [ ['colunas'=>['id','language']] ],
            'componentes' => [ ['colunas'=>['id','language']] ],
            'variaveis' => [ ['colunas'=>['id','linguagem_codigo']] ],
        ];

        foreach ($alvos as $tabela => $idxLista) {
            if (!$this->hasTable($tabela)) continue;
            $table = $this->table($tabela);
            if (!$table->hasColumn('plugin')) {
                $table->addColumn('plugin', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'after'=>'id']);
            }
            // Criar índices compostos incluindo plugin para preservar unicidade interna sem quebrar existentes.
            foreach ($idxLista as $i) {
                $cols = array_merge(['plugin'], $i['colunas']);
                $idxName = 'idx_'.$tabela.'_'.implode('_',$cols);
                // Tentativa simples: se não houver índice com esse nome específico, criamos.
                if(!$table->hasIndexByName($idxName)){
                    $table->addIndex($cols, ['name'=>$idxName]);
                }
            }
            $table->update();
        }
    }
}
