<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterModulosUpdateIconesProjetos extends AbstractMigration
{
    /**
     * req-125 F2 — pares Fomantic/Lucide dos módulos servidos por projetos derivados.
     *
     * A migração do req-086 (`20260820140000`) criou `icone_tailwind` e o `ModulosData.json` do core
     * preencheu os 42 módulos DO CORE. Os módulos que só existem em projeto — catálogo 3D, gateways,
     * redes sociais, arquivos, módulos-grupos distribuído — ficaram com a coluna nula e o menu
     * Tailwind caiu no vocabulário Fomantic legado para todos eles.
     *
     * O `*Data.json` do projeto já leva o par para instalações novas e para todo deploy de recursos;
     * esta migração é o que alcança os bancos JÁ EXISTENTES, onde a linha em `modulos` foi gravada
     * antes de a coluna existir. Os dois caminhos escrevem exatamente os mesmos valores.
     *
     * Um `UPDATE` que não encontra a linha é uma operação bem-sucedida de zero linhas: rodar isto no
     * banco do core (que não tem nenhum destes módulos) é inócuo por construção, e é por isso que a
     * migração pode viver no core e ainda assim corrigir o projeto.
     *
     * Os nomes foram conferidos contra os catálogos REAIS — `icon.min.css` do Fomantic 2.9.4 e os
     * 1.862 ícones do bundle UMD do Lucide 0.544.0 — e não contra memória. Um nome de ícone é
     * endereço dentro de um catálogo: errar o endereço não gera erro, gera item sem glifo.
     */
    public function up(): void
    {
        if (!$this->hasTable('modulos')) {
            return;
        }

        $tabela = $this->table('modulos');

        // Banco que ainda não rodou o req-086 não tem a coluna, e o UPDATE morreria com
        // "Unknown column". A migração precisa ser aplicável em qualquer ordem de catch-up.
        if (!$tabela->hasColumn('icone_tailwind')) {
            return;
        }

        // Chave = `modulos.id` REAL da instalação. Os apelidos em português do intake
        // (`catalogo-3d`, `conexoes-sociais`, `publicador-midias-sociais`) não são ids de nada:
        // os módulos do `conn2flow-site` são registrados com id em inglês, e
        // `modulos-grupos-distribuido` é singular. Ambas as grafias ficam listadas porque o custo de
        // um UPDATE sem correspondência é zero e uma instalação antiga pode ter qualquer uma.
        $mapeamentos = [
            '3d-catalog'                 => ['cube',                'box'],
            '3d-catalog-groups'          => ['cubes',               'boxes'],
            '3d-catalog-items'           => ['cube',                'box'],
            'catalogo-3d'                => ['cube',                'box'],
            'catalogo-3d-grupos'         => ['cubes',               'boxes'],
            'catalogo-3d-itens'          => ['cube',                'box'],
            'social-connections'         => ['share alternate',     'share-2'],
            'conexoes-sociais'           => ['share alternate',     'share-2'],
            'gateways-pagamentos'        => ['credit card outline', 'credit-card'],
            'publisher-social-media'     => ['bullhorn',            'megaphone'],
            'publicador-midias-sociais'  => ['bullhorn',            'megaphone'],
            'social-apps'                => ['mobile alternate',    'smartphone'],
            'arquivos'                   => ['folder open outline', 'folder-open'],
            'admin-arquivos'             => ['folder open outline', 'folder-open'],
            'modulos-grupos-distribuido' => ['sitemap',             'network'],
            'modulos-grupos-distribuidos'=> ['sitemap',             'network'],
        ];

        $adaptador = $this->getAdapter();

        $sql = sprintf(
            'UPDATE %s SET %s = ?, %s = ? WHERE %s = ?',
            $adaptador->quoteTableName('modulos'),
            $adaptador->quoteColumnName('icone'),
            $adaptador->quoteColumnName('icone_tailwind'),
            $adaptador->quoteColumnName('id')
        );

        foreach ($mapeamentos as $id => $par) {
            [$fomantic, $lucide] = $par;

            // Valores vão por bind, não por interpolação: o `sprintf` monta só identificadores, que
            // o próprio adaptador cita conforme o dialeto.
            $this->execute($sql, [$fomantic, $lucide, $id]);
        }
    }

    /**
     * Sem reversão: o estado anterior era "coluna nula ou vocabulário Fomantic incompleto", que não
     * é um estado a restaurar — é o defeito. Reverter a migração do req-086 remove a coluna inteira,
     * e essa é a saída correta para quem precisa desfazer.
     */
    public function down(): void
    {
    }
}
