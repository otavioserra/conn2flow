<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class PaginasSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../data/PaginasData.json'), true);

        $table = $this->table('paginas');
        $table->insert($data)->saveData();
    }
}
