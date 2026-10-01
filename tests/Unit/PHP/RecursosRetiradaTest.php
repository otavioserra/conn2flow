<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/gestor/controladores/atualizacoes/atualizacoes-recursos-retirada.php';

/** req-199 / BATCH-207: exclusão declarativa de dados — o que o dono deixou de entregar sai do banco. */
final class RecursosRetiradaTest extends TestCase
{
    private const NK = ['id', 'language', 'modulo'];

    private function pdo(bool $comStatus = true): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE paginas (id_paginas INTEGER PRIMARY KEY, id TEXT, language TEXT, modulo TEXT, project TEXT, user_modified INTEGER DEFAULT 0'
            . ($comStatus ? ", status TEXT DEFAULT 'A'" : '') . ')');
        $ins = $pdo->prepare('INSERT INTO paginas (id, language, modulo, project, user_modified) VALUES (?, ?, ?, ?, ?)');
        foreach ([['home', 'pt-br', null, null, 0], ['antiga', 'pt-br', 'blog', null, 0], ['editada', 'pt-br', null, null, 1], ['do-projeto', 'pt-br', null, 'site', 0], ['criada-no-painel', 'pt-br', null, null, 0]] as $l) $ins->execute($l);
        return $pdo;
    }

    private function colunas(PDO $pdo): array
    {
        $c = [];
        foreach ($pdo->query('PRAGMA table_info(paginas)')->fetchAll(PDO::FETCH_ASSOC) as $l) $c[$l['name']] = true;
        return $c;
    }

    private function statusDe(PDO $pdo, string $id): ?string
    {
        $v = $pdo->query("SELECT status FROM paginas WHERE id = '$id'")->fetchColumn();
        return $v === false ? null : (string)$v;
    }

    public function testChavesEPlanoDoQueSaiu(): void
    {
        $antes = recursos_retirada_chaves([['id' => 'Home', 'language' => 'pt-br'], ['id' => 'antiga', 'language' => 'pt-br', 'modulo' => 'blog'], ['id' => 'sem-idioma']], self::NK);
        $this->assertSame(['home|pt-br|', 'antiga|pt-br|blog'], array_keys($antes), 'Chave em minúsculas; sem coluna obrigatória fica de fora.');
        $this->assertSame(['id' => 'Home', 'language' => 'pt-br', 'modulo' => null], $antes['home|pt-br|']);
        $depois = recursos_retirada_chaves([['id' => 'home', 'linguagem_codigo' => 'pt-br']], self::NK);
        $this->assertSame(['antiga|pt-br|blog'], array_keys(recursos_retirada_planejar($antes, $depois)), 'linguagem_codigo vale como language.');
    }

    public function testRetiraOQueSaiuComStatusD(): void
    {
        $pdo = $this->pdo();
        $itens = recursos_retirada_chaves([['id' => 'antiga', 'language' => 'pt-br', 'modulo' => 'blog']], self::NK);
        $r = recursos_retirada_aplicar($pdo, 'paginas', $itens, $this->colunas($pdo), null, false);
        $this->assertSame(1, $r['marcados']);
        $this->assertSame('D', $this->statusDe($pdo, 'antiga'));
        $this->assertSame('A', $this->statusDe($pdo, 'home'));
        $this->assertSame('A', $this->statusDe($pdo, 'criada-no-painel'), 'O que nunca foi entregue não sai.');
    }

    public function testSemColunaStatusApaga(): void
    {
        $pdo = $this->pdo(false);
        $r = recursos_retirada_aplicar($pdo, 'paginas', recursos_retirada_chaves([['id' => 'home', 'language' => 'pt-br']], self::NK), $this->colunas($pdo), null, false);
        $this->assertSame(1, $r['retirados']);
        $this->assertSame(0, (int)$pdo->query("SELECT COUNT(*) FROM paginas WHERE id = 'home'")->fetchColumn());
    }

    public function testEditadoOnlineViraChoque(): void
    {
        $pdo = $this->pdo();
        $r = recursos_retirada_aplicar($pdo, 'paginas', recursos_retirada_chaves([['id' => 'editada', 'language' => 'pt-br']], self::NK), $this->colunas($pdo), null, false);
        $this->assertSame(0, $r['marcados']);
        $this->assertSame('A', $this->statusDe($pdo, 'editada'));
        $this->assertCount(1, $r['choques']);
        $this->assertSame('registro', $r['choques'][0]['tipo']);
        $this->assertSame('retirado-editado', $r['choques'][0]['motivo']);
        $this->assertSame('db:paginas?id=editada&language=pt-br', $r['choques'][0]['caminho']);
    }

    public function testDonoSoMexeNoQueEDele(): void
    {
        $pdo = $this->pdo();
        $item = recursos_retirada_chaves([['id' => 'do-projeto', 'language' => 'pt-br']], self::NK);
        $core = recursos_retirada_aplicar($pdo, 'paginas', $item, $this->colunas($pdo), null, false);
        $this->assertSame(1, $core['ausentes'], 'O core não retira registro marcado de projeto.');
        $this->assertSame('A', $this->statusDe($pdo, 'do-projeto'));
        $outro = recursos_retirada_aplicar($pdo, 'paginas', $item, $this->colunas($pdo), 'outro', false);
        $this->assertSame(1, $outro['ausentes']);
        $dono = recursos_retirada_aplicar($pdo, 'paginas', $item, $this->colunas($pdo), 'site', false);
        $this->assertSame(1, $dono['marcados']);
    }

    public function testSimulacaoNaoMudaNada(): void
    {
        $pdo = $this->pdo();
        $r = recursos_retirada_aplicar($pdo, 'paginas', recursos_retirada_chaves([['id' => 'antiga', 'language' => 'pt-br', 'modulo' => 'blog']], self::NK), $this->colunas($pdo), null, true);
        $this->assertSame(1, $r['simulados']);
        $this->assertSame('A', $this->statusDe($pdo, 'antiga'));
    }

    public function testManifestoPorDono(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-rec-' . uniqid();
        $this->assertSame([], recursos_retirada_manifesto_ler($base, 'core'));
        $this->assertTrue(recursos_retirada_manifesto_gravar($base, 'site', ['paginas' => ['home|pt-br|' => ['id' => 'home']]]));
        $this->assertSame(['paginas' => ['home|pt-br|' => ['id' => 'home']]], recursos_retirada_manifesto_ler($base, 'site'));
        $this->assertSame([], recursos_retirada_manifesto_ler($base, 'core'), 'Cada dono tem o seu.');
        $this->assertStringEndsWith('recursos-a_b.json', recursos_retirada_manifesto_arquivo($base, 'a/b'));
        @unlink(recursos_retirada_manifesto_arquivo($base, 'site'));
        @rmdir($base . '/installation/manifests'); @rmdir($base . '/installation'); @rmdir($base);
    }

    /** req-206: o que a retirada marcou volta ao status que tinha quando o dono entrega de novo. */
    public function testOQueVoltaASerEntregueEReativado(): void
    {
        $pdo = $this->pdo();
        $pdo->exec("UPDATE paginas SET status = 'I' WHERE id = 'home'");
        $itens = recursos_retirada_chaves([['id' => 'antiga', 'language' => 'pt-br', 'modulo' => 'blog'], ['id' => 'home', 'language' => 'pt-br']], self::NK);
        $r = recursos_retirada_aplicar($pdo, 'paginas', $itens, $this->colunas($pdo), null, false);
        $this->assertSame(['antiga|pt-br|blog', 'home|pt-br|'], array_keys($r['marcados_chaves']));
        $this->assertSame('I', $r['marcados_chaves']['home|pt-br|']['status'], 'Guarda o status que o registro tinha.');
        $this->assertSame('D', $this->statusDe($pdo, 'antiga'));

        $volta = recursos_retirada_reativar($pdo, 'paginas', $r['marcados_chaves'], $this->colunas($pdo), null, false);
        $this->assertSame(2, $volta['reativados']);
        $this->assertSame('A', $this->statusDe($pdo, 'antiga'));
        $this->assertSame('I', $this->statusDe($pdo, 'home'), 'Volta ao status anterior, não a um status fixo.');
    }

    public function testReativacaoNaoTocaNoQueARetiradaNaoMarcou(): void
    {
        $pdo = $this->pdo();
        // Desativado por outra via (painel, por exemplo): nunca entrou na lista de marcados.
        $pdo->exec("UPDATE paginas SET status = 'D' WHERE id = 'criada-no-painel'");
        $pdo->exec("UPDATE paginas SET status = 'D' WHERE id = 'do-projeto'");
        $marcado = ['do-projeto|pt-br|' => ['valores' => ['id' => 'do-projeto', 'language' => 'pt-br', 'modulo' => null], 'status' => 'A']];

        $outroDono = recursos_retirada_reativar($pdo, 'paginas', $marcado, $this->colunas($pdo), 'outro', false);
        $this->assertSame(0, $outroDono['reativados'], 'Dono diferente não reativa.');
        $simulado = recursos_retirada_reativar($pdo, 'paginas', $marcado, $this->colunas($pdo), 'site', true);
        $this->assertSame(0, $simulado['reativados']);
        $this->assertSame('D', $this->statusDe($pdo, 'do-projeto'), 'Simulação não muda nada.');

        $dono = recursos_retirada_reativar($pdo, 'paginas', $marcado, $this->colunas($pdo), 'site', false);
        $this->assertSame(1, $dono['reativados']);
        $this->assertSame('A', $this->statusDe($pdo, 'do-projeto'));
        $this->assertSame('D', $this->statusDe($pdo, 'criada-no-painel'));

        $semStatus = $this->pdo(false);
        $this->assertSame(0, recursos_retirada_reativar($semStatus, 'paginas', $marcado, $this->colunas($semStatus), 'site', false)['reativados']);
    }

    public function testManifestoGuardaOsRetiradosEOsPreservaEmGravacaoAntiga(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-rec-' . uniqid();
        $marcados = ['forms' => ['form-contacts|pt-br|' => ['valores' => ['id' => 'form-contacts'], 'status' => 'A']]];
        $this->assertSame([], recursos_retirada_marcados_ler($base, 'site'));
        $this->assertTrue(recursos_retirada_manifesto_gravar($base, 'site', ['forms' => []], $marcados));
        $this->assertSame($marcados, recursos_retirada_marcados_ler($base, 'site'));
        // Gravação sem o quarto argumento (chamador antigo) não apaga a lista.
        $this->assertTrue(recursos_retirada_manifesto_gravar($base, 'site', ['forms' => []]));
        $this->assertSame($marcados, recursos_retirada_marcados_ler($base, 'site'));
        // Tabela sem pendência some da lista.
        $this->assertTrue(recursos_retirada_manifesto_gravar($base, 'site', ['forms' => []], ['forms' => []]));
        $this->assertSame([], recursos_retirada_marcados_ler($base, 'site'));
        @unlink(recursos_retirada_manifesto_arquivo($base, 'site'));
        @rmdir($base . '/installation/manifests'); @rmdir($base . '/installation'); @rmdir($base);
    }
}
