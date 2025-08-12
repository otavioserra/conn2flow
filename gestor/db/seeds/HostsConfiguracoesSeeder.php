<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class HostsConfiguracoesSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../data/HostsConfiguracoesData.json'), true);

        if (count($data) > 0) {
            $table = $this->table('hosts_configuracoes');
            // Esvazia a tabela antes de inserir para evitar duplicatas
            $table->truncate(); 
            $table->insert($data)->saveData();
        }
    }
}