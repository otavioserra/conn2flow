<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class PluginsSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'id_plugins' => '1',
                'id_usuarios' => NULL,
                'nome' => 'Agendamentos',
                'id' => 'agendamentos',
                'status' => 'D',
                'versao' => ' 6',
                'data_criacao' => '2022-03-29 13:56:27',
                'data_modificacao' => '2022-03-29 13:59:10',
            ],
            [
                'id_plugins' => '2',
                'id_usuarios' => NULL,
                'nome' => 'Agendamentos',
                'id' => 'agendamentos',
                'status' => 'A',
                'versao' => ' 1',
                'data_criacao' => '2022-03-29 13:59:21',
                'data_modificacao' => '2022-03-29 13:59:21',
            ],
            [
                'id_plugins' => '3',
                'id_usuarios' => NULL,
                'nome' => 'Escalas',
                'id' => 'escalas',
                'status' => 'A',
                'versao' => ' 1',
                'data_criacao' => '2022-03-29 16:10:02',
                'data_modificacao' => '2022-03-29 16:10:02',
            ],
        ];

        if (count($data) > 0) {
            $table = $this->table('plugins');
            // Esvazia a tabela antes de inserir para evitar duplicatas
            $table->truncate(); 
            $table->insert($data)->saveData();
        }
    }
}