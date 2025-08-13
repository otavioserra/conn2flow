<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class ComponentesSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../data/ComponentesData.json'), true);
        if (!is_array($data) || empty($data)) {
            return; // nada a inserir
        }
        $table = $this->table('componentes');
        // Evita duplicatas ao reexecutar seeds
        $table->truncate();
        $table->insert($data)->saveData();
    }
}
