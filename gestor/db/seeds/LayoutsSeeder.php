<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class LayoutsSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../data/LayoutsData.json'), true);
        if (!is_array($data) || empty($data)) {
            return;
        }
        $table = $this->table('layouts');
        $table->truncate();
        $table->insert($data)->saveData();
    }
}
