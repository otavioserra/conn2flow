<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'gestor.php';
require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'interface.php';

/**
 * req-240 — nenhum botão do painel Tailwind sai com o círculo de fallback.
 *
 * Os módulos declaram o ícone pelo nome do Fomantic (`'icon' => 'trash alternate'`) e
 * `interface_botao_tailwind_icone()` o traduz para o Lucide. Sem tradução a função devolve vazio e a
 * listagem (`interface-listar-tailwind.js`) desenha `circle`: o "círculo preto" da auditoria humana.
 */
final class IconesLucideReq240Test extends TestCase
{
    /** @return array<string, string[]> nome do ícone => arquivos que o declaram */
    private function iconesDeclarados(): array
    {
        $declarados = [];
        $raizes = [
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos',
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas',
        ];

        foreach ($raizes as $raiz) {
            $arquivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS));
            foreach ($arquivos as $arquivo) {
                $caminho = str_replace('\\', '/', $arquivo->getPathname());
                if (substr($caminho, -4) !== '.php' || strpos($caminho, '/resources/') !== false) {
                    continue;
                }

                if (!preg_match_all("/'(?:icon|icone)'\s*=>\s*'([^'\$]+)'/", (string)file_get_contents($caminho), $achados)) {
                    continue;
                }

                foreach ($achados[1] as $nome) {
                    $declarados[strtolower(trim($nome))][] = basename($caminho);
                }
            }
        }

        ksort($declarados);
        return $declarados;
    }

    private function existeNoLucide(string $nome): bool
    {
        static $pacote = null;
        if ($pacote === null) {
            $arquivos = glob(CONN2FLOW_GESTOR_ROOT . '/assets/vendor/lucide/*/lucide.min.js');
            $this->assertNotEmpty($arquivos, 'Pacote Lucide embarcado não encontrado.');
            $pacote = (string)file_get_contents($arquivos[0]);
        }

        $pascal = implode('', array_map('ucfirst', explode('-', $nome)));
        return (bool)preg_match('/[,{]' . preg_quote($pascal, '/') . ':/', $pacote);
    }

    public function testTodoIconeDeclaradoPelosModulosTemTraducao(): void
    {
        $semTraducao = [];
        foreach ($this->iconesDeclarados() as $nome => $arquivos) {
            if (interface_botao_tailwind_icone($nome) === '') {
                $semTraducao[] = "'" . $nome . "' (" . implode(', ', array_unique($arquivos)) . ')';
            }
        }

        $this->assertSame([], $semTraducao, 'Ícone sem tradução vira o círculo de fallback: acrescente-o ao mapa de interface_botao_tailwind_icone().');
    }

    public function testTodaTraducaoExisteNoPacoteLucideEmbarcado(): void
    {
        $inexistentes = [];
        foreach (array_keys($this->iconesDeclarados()) as $nome) {
            $lucide = interface_botao_tailwind_icone($nome);
            if ($lucide !== '' && !$this->existeNoLucide($lucide)) {
                $inexistentes[] = "'" . $nome . "' => '" . $lucide . "'";
            }
        }

        $this->assertSame([], $inexistentes, 'Nome que o Lucide não conhece deixa o ícone invisível.');
    }

    /** @return iterable<string> HTML de páginas e componentes dos módulos e dos recursos globais */
    private function recursosHtml(): iterable
    {
        foreach ([CONN2FLOW_GESTOR_ROOT . '/modulos', CONN2FLOW_GESTOR_ROOT . '/resources'] as $raiz) {
            $arquivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS));
            foreach ($arquivos as $arquivo) {
                $caminho = str_replace('\\', '/', $arquivo->getPathname());
                if (substr($caminho, -5) !== '.html') {
                    continue;
                }
                if (strpos($caminho, '/pages/') === false && strpos($caminho, '/components/') === false) {
                    continue;
                }
                yield $caminho;
            }
        }
    }

    public function testNenhumIconeCircleDeMarcadorDeLugarNosRecursos(): void
    {
        $com = [];
        foreach ($this->recursosHtml() as $caminho) {
            if (strpos((string)file_get_contents($caminho), 'data-lucide="circle"') !== false) {
                $com[] = substr($caminho, strlen(CONN2FLOW_GESTOR_ROOT) + 1);
            }
        }

        $this->assertSame([], $com, 'O ícone `circle` como marcador de lugar é o círculo preto da auditoria: use um ícone semântico.');
    }

    public function testSelectDoPainelUsaAClassePadronizada(): void
    {
        $com = [];
        foreach ($this->recursosHtml() as $caminho) {
            if (preg_match('/<select\b[^>]*class="[^"]*\bc2fc-campo-entrada\b/', (string)file_get_contents($caminho))) {
                $com[] = substr($caminho, strlen(CONN2FLOW_GESTOR_ROOT) + 1);
            }
        }

        $this->assertSame([], $com, 'Select do painel usa c2fc-campo-selecao, não a classe de campo de texto.');
    }

    public function testTodaPaginaDeModuloTemMarcacaoBalanceada(): void
    {
        $desbalanceadas = [];
        foreach ($this->recursosHtml() as $caminho) {
            if (strpos($caminho, '/modulos/') === false || strpos($caminho, '/pages/') === false) {
                continue;
            }
            $html = (string)file_get_contents($caminho);
            if (preg_match_all('/<div\b/', $html) !== preg_match_all('/<\/div>/', $html)) {
                $desbalanceadas[] = substr($caminho, strlen(CONN2FLOW_GESTOR_ROOT) + 1);
            }
        }

        $this->assertSame([], $desbalanceadas, 'Página com <div> sem fechar engole o que vem depois dela no formulário.');
    }

    public function testIconeDesconhecidoContinuaVazio(): void
    {
        $this->assertSame('', interface_botao_tailwind_icone('icone que nao existe'));
        $this->assertSame('trash-2', interface_botao_tailwind_icone(' Trash Alternate '));
    }
}
