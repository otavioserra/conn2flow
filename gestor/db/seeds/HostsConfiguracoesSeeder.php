<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class HostsConfiguracoesSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'id_hosts_configuracoes' => '1',
                'id_hosts' => ' 13',
                'modulo' => 'comunicacao-configuracoes',
                'versao' => ' 19',
                'data_modificacao' => '2022-05-25 14:56:12',
            ],
            [
                'id_hosts_configuracoes' => '2',
                'id_hosts' => ' 13',
                'modulo' => 'menus',
                'versao' => ' 8',
                'data_modificacao' => '2022-06-30 14:59:29',
            ],
        ];

        if (count($data) > 0) {
            $table = $this->table('hosts_configuracoes');
            // Esvazia a tabela antes de inserir para evitar duplicatas
            $table->truncate(); 
            $table->insert($data)->saveData();
        }
    }
}