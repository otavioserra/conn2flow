<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class ModulosGruposSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'id_modulos_grupos' => '1',
                'id_usuarios' => ' 1',
                'nome' => 'Administração Gestor',
                'id' => 'administracao',
                'host' => NULL,
                'status' => 'A',
                'versao' => ' 4',
                'data_criacao' => '2021-03-16 09:13:10',
                'data_modificacao' => '2022-07-04 16:02:47',
                'ordemMenu' => NULL,
            ],
            [
                'id_modulos_grupos' => '2',
                'id_usuarios' => ' 1',
                'nome' => 'Loja',
                'id' => 'loja',
                'host' => NULL,
                'status' => 'A',
                'versao' => ' 7',
                'data_criacao' => '2021-03-22 14:37:11',
                'data_modificacao' => '2021-04-20 10:28:15',
                'ordemMenu' => ' 1',
            ],
            [
                'id_modulos_grupos' => '3',
                'id_usuarios' => ' 1',
                'nome' => 'Bibliotecas',
                'id' => 'bibliotecas',
                'host' => NULL,
                'status' => 'A',
                'versao' => ' 1',
                'data_criacao' => '2021-03-26 14:22:07',
                'data_modificacao' => '2021-03-26 14:22:07',
                'ordemMenu' => NULL,
            ],
            [
                'id_modulos_grupos' => '4',
                'id_usuarios' => ' 1',
                'nome' => 'Geral',
                'id' => 'inicio',
                'host' => NULL,
                'status' => 'A',
                'versao' => ' 2',
                'data_criacao' => '2021-04-22 11:09:02',
                'data_modificacao' => '2021-04-22 11:10:18',
                'ordemMenu' => NULL,
            ],
            [
                'id_modulos_grupos' => '5',
                'id_usuarios' => ' 2',
                'nome' => 'Host',
                'id' => 'host',
                'host' => NULL,
                'status' => 'A',
                'versao' => ' 1',
                'data_criacao' => '2021-06-17 11:56:34',
                'data_modificacao' => '2021-06-17 11:56:34',
                'ordemMenu' => NULL,
            ],
            [
                'id_modulos_grupos' => '6',
                'id_usuarios' => ' 1',
                'nome' => 'Teste 2',
                'id' => 'teste',
                'host' => NULL,
                'status' => 'D',
                'versao' => ' 5',
                'data_criacao' => '2021-04-30 12:02:49',
                'data_modificacao' => '2021-04-30 12:03:08',
                'ordemMenu' => NULL,
            ],
            [
                'id_modulos_grupos' => '7',
                'id_usuarios' => ' 1',
                'nome' => 'Gestor',
                'id' => 'gestor',
                'host' => NULL,
                'status' => 'A',
                'versao' => ' 1',
                'data_criacao' => '2021-07-21 11:14:22',
                'data_modificacao' => '2021-07-21 11:14:22',
                'ordemMenu' => ' 4',
            ],
            [
                'id_modulos_grupos' => '8',
                'id_usuarios' => ' 2',
                'nome' => 'Configurações',
                'id' => 'configuracoes',
                'host' => NULL,
                'status' => 'A',
                'versao' => ' 1',
                'data_criacao' => '2021-09-27 09:06:15',
                'data_modificacao' => '2021-09-27 09:06:15',
                'ordemMenu' => ' 3',
            ],
            [
                'id_modulos_grupos' => '9',
                'id_usuarios' => ' 2',
                'nome' => 'Conteúdos',
                'id' => 'conteudos',
                'host' => NULL,
                'status' => 'A',
                'versao' => ' 1',
                'data_criacao' => '2021-11-11 16:46:05',
                'data_modificacao' => '2021-11-11 16:46:05',
                'ordemMenu' => ' 2',
            ],
            [
                'id_modulos_grupos' => '10',
                'id_usuarios' => ' 2',
                'nome' => 'Usuários',
                'id' => 'usuarios',
                'host' => NULL,
                'status' => 'A',
                'versao' => ' 1',
                'data_criacao' => '2022-06-28 09:27:18',
                'data_modificacao' => '2022-06-28 09:27:18',
                'ordemMenu' => NULL,
            ],
            [
                'id_modulos_grupos' => '11',
                'id_usuarios' => ' 2',
                'nome' => 'Administração Usuários',
                'id' => 'administracao-usuarios',
                'host' => NULL,
                'status' => 'A',
                'versao' => ' 1',
                'data_criacao' => '2022-07-04 16:03:14',
                'data_modificacao' => '2022-07-04 16:03:14',
                'ordemMenu' => NULL,
            ],
            [
                'id_modulos_grupos' => '12',
                'id_usuarios' => ' 2',
                'nome' => 'Administração Sistema',
                'id' => 'administracao-sistema',
                'host' => NULL,
                'status' => 'A',
                'versao' => ' 1',
                'data_criacao' => '2022-07-04 16:03:32',
                'data_modificacao' => '2022-07-04 16:03:32',
                'ordemMenu' => NULL,
            ],
        ];

        if (count($data) > 0) {
            $table = $this->table('modulos_grupos');
            // Esvazia a tabela antes de inserir para evitar duplicatas
            $table->truncate(); 
            $table->insert($data)->saveData();
        }
    }
}