<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class ModulosOperacoesSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'id_modulos_operacoes' => '1',
                'id_modulos' => ' 4',
                'id_usuarios' => ' 2',
                'nome' => 'Modificar Permissão da Página',
                'id' => 'modificar-permissao-da-pagina',
                'operacao' => 'permissao-pagina',
                'status' => 'A',
                'versao' => ' 1',
                'data_criacao' => '2021-06-21 12:17:55',
                'data_modificacao' => '2021-06-21 12:17:55',
            ],
        ];

        if (count($data) > 0) {
            $table = $this->table('modulos_operacoes');
            // Esvazia a tabela antes de inserir para evitar duplicatas
            $table->truncate(); 
            $table->insert($data)->saveData();
        }
    }
}