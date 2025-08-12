<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class ModulosSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../data/ModulosData.json'), true);

        if (count($data) > 0) {
            $table = $this->table('modulos');
            // Esvazia a tabela antes de inserir para evitar duplicatas
            $table->truncate(); 
            $table->insert($data)->saveData();
        }
    }
}