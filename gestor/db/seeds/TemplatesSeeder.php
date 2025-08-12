<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class TemplatesSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../data/TemplatesData.json'), true);

        if (count($data) > 0) {
            $table = $this->table('templates');
            // Esvazia a tabela antes de inserir para evitar duplicatas
            $table->truncate(); 
            $table->insert($data)->saveData();
        }
    }
}