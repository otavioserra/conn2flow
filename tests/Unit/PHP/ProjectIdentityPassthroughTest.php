<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Identidade do projeto no atualizador de banco (req-131 / BATCH-133).
 *
 * `CLI_OPTS['project']` é o que separa os dois fluxos documentados em
 * `CONN2FLOW-PROJECT-DATABASE-PROTECTION.md`:
 *
 *  - **deploy DE projeto**: sobrescreve o recurso e o marca com o id do projeto;
 *  - **atualização normal**: respeita a marcação e não toca em recurso de projeto.
 *
 * O defeito não estava nessa regra — estava em quem a alimenta. O
 * `updates-manager-database.sh` recebia `--project` (usava-o para resolver o `dockerPath`) e não o
 * repassava ao PHP. O deploy local de um projeto era tratado como atualização normal e ficava
 * bloqueado pela marcação que ele mesmo havia gravado: a alteração parava no `Data.json`, a linha
 * era contada como "sem alteração" e a rotina terminava com sucesso, **sem nenhum aviso**.
 *
 * O caminho remoto nunca teve o defeito (`api.php` monta `CLI_OPTS['project']` a partir do
 * cabeçalho `X-Project-ID`): era uma assimetria entre os dois deploys do MESMO projeto.
 *
 * Estes testes fixam a regra nos dois modos e o repasse do script, que é o elo que faltava.
 *
 * Mesma infraestrutura do `ForcarAtualizacaoTest`: PDO SQLite em memória e contrato temporário.
 */
final class ProjectIdentityPassthroughTest extends TestCase
{
    private static string $tmpDir = '';

    public static function setUpBeforeClass(): void
    {
        if (!defined('SDD_NO_AUTORUN')) {
            define('SDD_NO_AUTORUN', true);
        }
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR
            . 'controladores' . DIRECTORY_SEPARATOR . 'atualizacoes' . DIRECTORY_SEPARATOR
            . 'atualizacoes-banco-de-dados.php';

        self::$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f_project_id_' . uniqid();
        @mkdir(self::$tmpDir, 0775, true);
        $GLOBALS['DB_DATA_DIR'] = self::$tmpDir . DIRECTORY_SEPARATOR;
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$tmpDir && is_dir(self::$tmpDir)) {
            foreach (glob(self::$tmpDir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) { @unlink($f); }
            @rmdir(self::$tmpDir);
        }
    }

    /** Tabela com os campos que o contrato de recursos usa. */
    private function bancoComRecursoDeProjeto(int $userModified): PDO
    {
        $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE TABLE widgets_demo (
            id_widgets_demo INTEGER PRIMARY KEY AUTOINCREMENT,
            id TEXT, language TEXT, html TEXT, css_precompiled TEXT, status TEXT,
            html_updated TEXT, system_updated INTEGER DEFAULT 0,
            user_modified INTEGER DEFAULT 0, project TEXT
        )');
        $pdo->prepare("INSERT INTO widgets_demo
            (id,language,html,css_precompiled,status,user_modified,project)
            VALUES ('painel','pt-br','HTML ANTIGO','PRE ANTIGO','A',?,'projX')")
            ->execute([$userModified]);

        file_put_contents(self::$tmpDir . DIRECTORY_SEPARATOR . 'schema-metadata.json', (string) json_encode([
            'generated_at' => date('c'),
            'tables' => ['widgets_demo' => [
                'nome' => 'widgets_demo', 'id' => 'id', 'id_numerico' => 'id_widgets_demo',
                'data_file' => 'WidgetsDemoData.json', 'strategy' => 'natural_key',
                'natural_key_columns' => ['language', 'id'],
                'preserve_on_user_modified' => ['html'], 'insert_only' => false, 'source' => 'test',
            ]],
            'deletar' => [],
            'forcar_atualizacao' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $pdo;
    }

    private function sincronizar(PDO $pdo, ?string $project): array
    {
        $GLOBALS['CLI_OPTS'] = ['orphans-mode' => 'ignore'];
        if ($project !== null) {
            $GLOBALS['CLI_OPTS']['project'] = $project;
        }

        $registros = [
            ['id' => 'painel', 'language' => 'pt-br', 'html' => 'HTML NOVO', 'css_precompiled' => 'PRE NOVO', 'status' => 'A'],
        ];

        ob_start();
        $res = sincronizarTabela($pdo, 'widgets_demo', $registros, false, false);
        ob_end_clean();

        $linha = $pdo->query('SELECT * FROM widgets_demo')->fetch(PDO::FETCH_ASSOC);
        return ['res' => $res, 'linha' => $linha];
    }

    /**
     * É o defeito, reproduzido: sem a identidade do projeto, o próprio deploy do projeto não
     * consegue atualizar o recurso que ele marcou.
     */
    public function testSemIdentidadeDeProjetoORecursoMarcadoNaoEAtualizado(): void
    {
        $pdo = $this->bancoComRecursoDeProjeto(0);
        $out = $this->sincronizar($pdo, null);

        self::assertSame('HTML ANTIGO', $out['linha']['html'], 'o recurso foi atualizado sem identidade de projeto');
        self::assertSame('projX', $out['linha']['project']);
        // E o pior: a linha é contada como "sem alteração", então o relatório fecha com sucesso.
        self::assertSame(0, $out['res']['updated']);
        self::assertSame(1, $out['res']['same']);
    }

    /** Com a identidade, o deploy do projeto sobrescreve e mantém a marcação. */
    public function testComIdentidadeDeProjetoORecursoEAtualizadoEMarcado(): void
    {
        $pdo = $this->bancoComRecursoDeProjeto(0);
        $out = $this->sincronizar($pdo, 'projX');

        self::assertSame('HTML NOVO', $out['linha']['html']);
        self::assertSame('projX', $out['linha']['project']);
        self::assertSame(1, $out['res']['updated']);
    }

    /** Um projeto diferente também atualiza, e a marcação passa a ser dele — é quem publicou por último. */
    public function testDeployDeOutroProjetoRemarcaORecurso(): void
    {
        $pdo = $this->bancoComRecursoDeProjeto(0);
        $out = $this->sincronizar($pdo, 'projY');

        self::assertSame('HTML NOVO', $out['linha']['html']);
        self::assertSame('projY', $out['linha']['project']);
    }

    /**
     * A GARANTIA QUE O OPERADOR PRECISA: mesmo no deploy de projeto, o que o usuário editou pela
     * interface é preservado, e a marcação dele permanece. `system_updated` sobe para 1, que é como
     * a tela sabe que existe versão nova do sistema para aquele recurso.
     *
     * Só `forcar_atualizacao`, declarado item a item, atravessa esta proteção.
     *
     * **Observação registrada, não alterada:** o valor novo só é copiado para `html_updated` quando
     * essa coluna já tem algum valor — a condição no núcleo usa `isset()`, e `isset(null)` é falso.
     * Com a coluna em `NULL` (o caso comum na primeira divergência), a versão nova é descartada em
     * vez de ficar disponível para comparação. Mudar isso altera o que o deploy grava em toda base
     * em produção e é decisão de outra ordem — fora do escopo desta requisição.
     */
    public function testDeployDeProjetoNaoSobrescreveOQueOUsuarioEditou(): void
    {
        $pdo = $this->bancoComRecursoDeProjeto(1);
        $out = $this->sincronizar($pdo, 'projX');

        self::assertSame('HTML ANTIGO', $out['linha']['html'], 'a edição do usuário foi sobrescrita');
        self::assertSame(1, (int) $out['linha']['user_modified'], 'a marcação do usuário foi apagada');
        self::assertSame(1, (int) $out['linha']['system_updated'], 'a tela não saberia que há versão nova');
        // O comportamento atual, fixado para que uma mudança futura seja deliberada e não acidental.
        self::assertNull($out['linha']['html_updated']);
    }

    /**
     * O elo que faltava: o script de atualização local precisa REPASSAR a identidade ao PHP.
     *
     * Sem esta linha, tudo acima continua correto e mesmo assim o deploy local não funciona — foi
     * exatamente o que aconteceu. O script já recebia `--project` para resolver o `dockerPath`.
     */
    public function testScriptLocalRepassaAIdentidadeDoProjetoAoPhp(): void
    {
        $script = dirname(CONN2FLOW_GESTOR_ROOT) . DIRECTORY_SEPARATOR . 'ai-workspace'
            . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR . 'scripts'
            . DIRECTORY_SEPARATOR . 'dev-environment' . DIRECTORY_SEPARATOR
            . 'updates-manager-database.sh';

        self::assertFileExists($script);
        $conteudo = (string) file_get_contents($script);

        self::assertStringContainsString(
            'PHP_ARGS="$PHP_ARGS --project=$PROJECT_TARGET"',
            $conteudo,
            'o script não repassa a identidade do projeto ao atualizador'
        );

        // O repasse é CONDICIONADO: sem `--project`, a execução é uma atualização normal do núcleo
        // e não pode marcar recurso nenhum como sendo de projeto.
        self::assertMatchesRegularExpression(
            '/if \[ -n "\$PROJECT_TARGET" \]; then\s*\n(?:.*\n)*?\s*PHP_ARGS="\$PHP_ARGS --project=\$PROJECT_TARGET"/',
            $conteudo,
            'o repasse precisa estar condicionado à presença do projeto'
        );

        // O valor entra numa linha executada por `docker exec`: mesma validação do `--tables`.
        self::assertStringContainsString(
            '[[ ! "$PROJECT_TARGET" =~ ^[a-zA-Z0-9_-]+$ ]]',
            $conteudo,
            'o identificador do projeto precisa ser validado antes de virar argumento de comando'
        );
    }
}
