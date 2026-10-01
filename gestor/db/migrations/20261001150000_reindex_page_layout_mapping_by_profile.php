<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * `paginas.layouts_users_profiles` passa de `{layout: perfil}` para `{perfil: layout}`.
 *
 * Com o layout na chave, o mesmo layout não podia servir a dois perfis: o segundo apagava o
 * primeiro. Só é invertido o registro em que TODAS as entradas têm cara do formato antigo (chave é
 * layout e valor é perfil); o que já está no formato novo, ou é ambíguo, fica como está.
 */
final class ReindexPageLayoutMappingByProfile extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('paginas') || !$this->table('paginas')->hasColumn('layouts_users_profiles')) return;

        $layouts = array_flip(array_map('strval', array_column($this->fetchAll('SELECT DISTINCT id FROM layouts'), 'id')));
        $perfis = array_flip(array_map('strval', array_column($this->fetchAll('SELECT DISTINCT id FROM usuarios_perfis'), 'id')));

        $linhas = $this->fetchAll(
            "SELECT id_paginas, layouts_users_profiles FROM paginas WHERE layouts_users_profiles IS NOT NULL AND layouts_users_profiles != ''"
        );
        $pdo = $this->getAdapter()->getConnection();
        $atualizar = $pdo->prepare('UPDATE paginas SET layouts_users_profiles = ? WHERE id_paginas = ?');

        foreach ($linhas as $linha) {
            $mapa = json_decode((string)$linha['layouts_users_profiles'], true);
            if (!is_array($mapa) || !$mapa) continue;

            $invertido = [];
            foreach ($mapa as $chave => $valor) {
                $chave = (string)$chave;
                if (!is_scalar($valor)) continue 2;
                $valor = (string)$valor;
                $antigo = isset($layouts[$chave]) && isset($perfis[$valor]);
                $novo = isset($perfis[$chave]) && isset($layouts[$valor]);
                if (!$antigo || $novo) continue 2;
                $invertido[$valor] = $chave;
            }

            $atualizar->execute([
                json_encode($invertido, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $linha['id_paginas'],
            ]);
        }
    }

    public function down(): void
    {
        // Sem volta: o formato antigo não representa dois perfis no mesmo layout.
    }
}
