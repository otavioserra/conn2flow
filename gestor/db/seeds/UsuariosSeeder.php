<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class UsuariosSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'id_usuarios' => '1',
                'id_hosts' => NULL,
                'id_usuarios_perfis' => ' 1',
                'nome_conta' => 'Administrador',
                'nome' => 'Administrador',
                'id' => 'administrador',
                'usuario' => 'admin',
                'senha' => '$argon2i$v=19$m=65536,t=4,p=1$N0pjcGRSLzBic0lEa283cQ$sJ+NqlPTDoZxCU95GZggRIxXEMFihzbm5Fc8aiE5FQY',
                'email' => 'admin@admin',
                'primeiro_nome' => 'Administrador',
                'ultimo_nome' => NULL,
                'nome_do_meio' => NULL,
                'status' => 'A',
                'versao' => ' 6',
                'data_criacao' => '2021-02-23 15:39:40',
                'data_modificacao' => '2021-04-27 17:47:05',
                'email_confirmado' => NULL,
                'gestor' => NULL,
                'gestor_perfil' => NULL,
            ],
        ];

        if (count($data) > 0) {
            $table = $this->table('usuarios');
            // Esvazia a tabela antes de inserir para evitar duplicatas
            $table->truncate(); 
            $table->insert($data)->saveData();
        }
    }
}