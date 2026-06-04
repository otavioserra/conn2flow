<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cria a tabela skeleton.
 * 
 * Informações adicionais.
 */
final class CreateSkeletonTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('skeleton', ['id' => 'id_skeleton']);
        
        $table
            // Usuário que criou o registro
            ->addColumn('id_usuarios', 'integer', ['null' => true, 'signed' => false, 'default' => 1])
            
            // Identificação do registro
            ->addColumn('id', 'string', ['limit' => 255, 'null' => false, 'comment' => 'ID field'])
            ->addColumn('nome', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Nome field'])
            
            // Controle
            ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
            ->addColumn('status', 'char', ['limit' => 1, 'null' => false, 'default' => 'A', 'comment' => 'A=Ativo, I=Inativo, E=Excluído'])
            ->addColumn('versao', 'integer', ['null' => false, 'default' => 1])
            ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
            
            // Índices
            ->addIndex(['id', 'language'], ['unique' => true])
            ->addIndex(['language'])
            
            ->create();
    }
}