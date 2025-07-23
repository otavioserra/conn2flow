<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class UsuariosPerfisModulosSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'id_usuarios_perfis_modulos' => '1',
                'perfil' => 'administradores',
                'modulo' => 'dashboard',
            ],
            [
                'id_usuarios_perfis_modulos' => '2',
                'perfil' => 'administradores',
                'modulo' => 'usuarios-perfis',
            ],
            [
                'id_usuarios_perfis_modulos' => '3',
                'perfil' => 'administradores',
                'modulo' => 'modulos',
            ],
            [
                'id_usuarios_perfis_modulos' => '4',
                'perfil' => 'administradores',
                'modulo' => 'modulos-grupos',
            ],
            [
                'id_usuarios_perfis_modulos' => '5',
                'perfil' => 'administradores',
                'modulo' => 'usuarios',
            ],
            [
                'id_usuarios_perfis_modulos' => '6',
                'perfil' => 'administradores',
                'modulo' => 'admin-layouts',
            ],
            [
                'id_usuarios_perfis_modulos' => '7',
                'perfil' => 'administradores',
                'modulo' => 'admin-paginas',
            ],
            [
                'id_usuarios_perfis_modulos' => '8',
                'perfil' => 'administradores',
                'modulo' => 'perfil-usuario',
            ],
            [
                'id_usuarios_perfis_modulos' => '9',
                'perfil' => 'administradores',
                'modulo' => 'admin-arquivos',
            ],
            [
                'id_usuarios_perfis_modulos' => '10',
                'perfil' => 'administradores',
                'modulo' => 'admin-categorias',
            ],
            [
                'id_usuarios_perfis_modulos' => '11',
                'perfil' => 'administradores',
                'modulo' => 'admin-templates',
            ],
            [
                'id_usuarios_perfis_modulos' => '12',
                'perfil' => 'administradores',
                'modulo' => 'modulos-operacoes',
            ],
            [
                'id_usuarios_perfis_modulos' => '13',
                'perfil' => 'administradores',
                'modulo' => 'usuarios-planos',
            ],
            [
                'id_usuarios_perfis_modulos' => '14',
                'perfil' => 'administradores',
                'modulo' => 'testes',
            ],
            [
                'id_usuarios_perfis_modulos' => '15',
                'perfil' => 'administradores',
                'modulo' => 'admin-componentes',
            ],
            [
                'id_usuarios_perfis_modulos' => '16',
                'perfil' => 'administradores',
                'modulo' => 'admin-hosts',
            ],
            [
                'id_usuarios_perfis_modulos' => '17',
                'perfil' => 'administradores',
                'modulo' => 'admin-plugins',
            ],
            [
                'id_usuarios_perfis_modulos' => '18',
                'perfil' => 'administradores',
                'modulo' => 'usuarios-hospedeiro-perfis-admin',
            ],
        ];

        if (count($data) > 0) {
            $table = $this->table('usuarios_perfis_modulos');
            // Esvazia a tabela antes de inserir para evitar duplicatas
            $table->truncate(); 
            $table->insert($data)->saveData();
        }
    }
}