<?php
/**
 * Biblioteca de manipulação de arquivos.
 *
 * Fornece funções auxiliares e principais para operações com arquivos
 * no sistema Conn2Flow.
 *
 * BATCH-090 (req-090): funções puras de segurança para o gerenciador de
 * arquivos baseado em árvore física de diretórios (sob `$_GESTOR['contents-path']`).
 * Elas são propositalmente independentes do framework (sem `$_GESTOR`, banco ou
 * saída) para permitir cobertura por testes unitários isolados.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.1.0
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-arquivo']							=	Array(
	'versao' => '1.1.0',
);

// ===== Funções auxiliares (puras, testáveis)

if (!function_exists('arquivo_nome_sanitizar')) {
	/**
	 * Higieniza um nome de arquivo ou pasta para uso seguro em sistemas de
	 * arquivos heterogêneos (Windows/Linux/rede).
	 *
	 * - Remove qualquer componente de diretório (usa apenas o basename).
	 * - Substitui caracteres inválidos (`: * ? " < > | / \` e controles) por `-`.
	 * - Colapsa espaços/hífens repetidos e apara pontos/espaços das pontas.
	 *
	 * @param string $nome Nome cru informado pelo usuário ou upload.
	 * @return string Nome higienizado; string vazia quando nada sobra de válido.
	 */
	function arquivo_nome_sanitizar($nome) {
		$nome = (string)$nome;

		// Remove bytes nulos.
		$nome = str_replace("\0", '', $nome);

		// Mantém apenas o último componente, tratando / e \ como separadores
		// (determinístico e cross-platform, ao contrário de basename()).
		$partes = preg_split('#[\\\\/]+#', $nome);
		$nome = $partes ? (string)end($partes) : '';

		// Substitui caracteres proibidos e de controle.
		$nome = preg_replace('/[:\*\?"<>\|]+/u', '-', $nome);
		$nome = preg_replace('/[\x00-\x1F\x7F]+/u', '', $nome);

		// Colapsa espaços e hífens repetidos.
		$nome = preg_replace('/\s+/u', ' ', $nome);
		$nome = preg_replace('/-{2,}/', '-', $nome);

		// Nomes reservados (Windows) ou compostos apenas de pontos/espaços viram vazio.
		$nome = trim($nome, " .-\t\r\n");

		return $nome;
	}
}

if (!function_exists('arquivo_extensao_perigosa')) {
	/**
	 * Verifica se a extensão de um nome de arquivo é executável/perigosa e deve
	 * ser bloqueada no upload, mesmo que o usuário tente burlar a extensão.
	 *
	 * @param string $nome Nome do arquivo (com extensão).
	 * @return bool true quando a extensão é perigosa.
	 */
	function arquivo_extensao_perigosa($nome) {
		$perigosas = array(
			'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phar',
			'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'ksh', 'zsh', 'com', 'exe', 'bat', 'cmd',
			'msi', 'dll', 'so', 'jar', 'asp', 'aspx', 'jsp', 'jspx', 'htaccess', 'htpasswd',
			'ht', 'shtml', 'pom', 'inc', 'ini', 'conf',
		);

		$nome = strtolower(trim((string)$nome));

		// Arquivos ocultos "de configuração" sem extensão (ex.: .htaccess) contam pelo basename.
		$base = strtolower(basename($nome));
		if ($base === '.htaccess' || $base === '.htpasswd' || $base === '.user.ini') {
			return true;
		}

		// Qualquer segmento de extensão perigoso (cobre "arquivo.php.jpg" e "arquivo.jpg.php").
		$partes = explode('.', $base);
		array_shift($partes); // descarta o nome-base
		foreach ($partes as $ext) {
			if (in_array($ext, $perigosas, true)) {
				return true;
			}
		}

		return false;
	}
}

if (!function_exists('arquivo_caminho_relativo_seguro')) {
	/**
	 * Normaliza um caminho relativo informado pelo cliente e garante que ele
	 * permanece dentro da árvore de conteúdos (previne path traversal).
	 *
	 * - Rejeita bytes nulos, caminhos absolutos (`/...`, `C:\...`) e sequências `..`.
	 * - Converte `\\` em `/`, colapsa barras repetidas e higieniza cada segmento.
	 *
	 * @param string $rel Caminho relativo cru (pode vir vazio = raiz).
	 * @return string|false Caminho relativo canônico com `/` (sem barra inicial/final),
	 *                      string vazia para a raiz, ou false quando inseguro.
	 */
	function arquivo_caminho_relativo_seguro($rel) {
		$rel = (string)$rel;

		if (strpos($rel, "\0") !== false) {
			return false;
		}

		// Uniformiza separadores.
		$rel = str_replace('\\', '/', $rel);

		// Rejeita caminho absoluto (POSIX ou unidade Windows).
		if ($rel !== '' && ($rel[0] === '/' || preg_match('#^[A-Za-z]:#', $rel))) {
			return false;
		}

		$segmentos = array();
		foreach (explode('/', $rel) as $seg) {
			if ($seg === '' || $seg === '.') {
				continue;
			}
			if ($seg === '..') {
				return false; // qualquer tentativa de subir na árvore é rejeitada.
			}

			$limpo = arquivo_nome_sanitizar($seg);
			if ($limpo === '') {
				return false;
			}
			$segmentos[] = $limpo;
		}

		return implode('/', $segmentos);
	}
}

if (!function_exists('arquivo_caminho_resolver')) {
	/**
	 * Resolve o caminho absoluto de um relativo seguro sob uma base e confirma,
	 * via realpath quando o alvo existe, que ele não escapa da base.
	 *
	 * @param string $base Raiz absoluta de conteúdos (`$_GESTOR['contents-path']`).
	 * @param string $rel  Caminho relativo (será validado por arquivo_caminho_relativo_seguro).
	 * @return string|false Caminho absoluto (com separador nativo) ou false se inseguro.
	 */
	function arquivo_caminho_resolver($base, $rel) {
		$relSeguro = arquivo_caminho_relativo_seguro($rel);
		if ($relSeguro === false) {
			return false;
		}

		$base = rtrim(str_replace('\\', '/', (string)$base), '/');
		$alvo = $relSeguro === '' ? $base : $base . '/' . $relSeguro;

		// Defesa extra: quando o alvo já existe, o realpath tem que continuar sob a base.
		$realBase = realpath($base);
		$realAlvo = realpath($alvo);
		if ($realBase !== false && $realAlvo !== false) {
			$realBaseN = rtrim(str_replace('\\', '/', $realBase), '/');
			$realAlvoN = str_replace('\\', '/', $realAlvo);
			if ($realAlvoN !== $realBaseN && strpos($realAlvoN, $realBaseN . '/') !== 0) {
				return false;
			}
		}

		return str_replace('/', DIRECTORY_SEPARATOR, $alvo);
	}
}

if (!function_exists('arquivo_mini_caminho_relativo')) {
	/**
	 * Dado o caminho relativo de um arquivo, retorna o caminho relativo da sua
	 * miniatura na subpasta física `mini/` da mesma pasta.
	 *
	 * Ex.: `files/2026/foto.jpg` => `files/2026/mini/foto.jpg`.
	 *
	 * @param string $rel Caminho relativo do arquivo original.
	 * @return string Caminho relativo da miniatura (com `/`).
	 */
	function arquivo_mini_caminho_relativo($rel) {
		$rel = str_replace('\\', '/', (string)$rel);
		$dir = dirname($rel);
		$base = basename($rel);
		if ($dir === '.' || $dir === '') {
			return 'mini/' . $base;
		}
		return $dir . '/mini/' . $base;
	}
}

if (!function_exists('arquivo_tipo_por_extensao')) {
	/**
	 * Classifica o "tipo" de um arquivo (image/video/audio/file) a partir da
	 * extensão, sem depender de `mime_content_type` (que exige o arquivo em disco).
	 *
	 * @param string $nome Nome do arquivo.
	 * @return string Um de: image, video, audio, file.
	 */
	function arquivo_tipo_por_extensao($nome) {
		$ext = strtolower(pathinfo((string)$nome, PATHINFO_EXTENSION));

		$imagens = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico', 'avif', 'tiff');
		$videos  = array('mp4', 'webm', 'ogv', 'mov', 'avi', 'mkv', 'm4v', 'wmv', 'flv', '3gp');
		$audios  = array('mp3', 'wav', 'ogg', 'oga', 'flac', 'aac', 'm4a', 'wma', 'opus');

		if (in_array($ext, $imagens, true)) return 'image';
		if (in_array($ext, $videos, true)) return 'video';
		if (in_array($ext, $audios, true)) return 'audio';
		return 'file';
	}
}

// ===== Funções principais



?>
