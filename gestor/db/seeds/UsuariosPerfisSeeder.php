<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class UsuariosPerfisSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'id_usuarios_perfis' => '1',
                'nome' => 'Administradores',
                'id' => 'administradores',
                'padrao' => NULL,
                'status' => 'A',
                'versao' => ' 93',
                'data_criacao' => '2021-03-29 16:22:36',
                'data_modificacao' => '2022-07-25 15:55:34',
            ],
        ];

        if (count($data) > 0) {
            $table = $this->table('usuarios_perfis');
            // Esvazia a tabela antes de inserir para evitar duplicatas
            $table->truncate(); 
            $table->insert($data)->saveData();
        }
    }
}