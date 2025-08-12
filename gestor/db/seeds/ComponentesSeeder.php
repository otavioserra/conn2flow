<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class ComponentesSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../data/ComponentesData.json'), true);

        $table = $this->table('componentes');
        $table->insert($data)->saveData();
    }
}
