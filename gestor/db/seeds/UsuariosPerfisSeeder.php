<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class UsuariosPerfisSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../data/UsuariosPerfisData.json'), true);

        if (count($data) > 0) {
            $table = $this->table('usuarios_perfis');
            // Esvazia a tabela antes de inserir para evitar duplicatas
            $table->truncate(); 
            $table->insert($data)->saveData();
        }
    }
}