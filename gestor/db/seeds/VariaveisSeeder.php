<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class VariaveisSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../data/VariaveisData.json'), true);

        if (count($data) > 0) {
            $table = $this->table('variaveis');
            // Esvazia a tabela antes de inserir para evitar duplicatas
            $table->truncate(); 
            $table->insert($data)->saveData();
        }
    }
}