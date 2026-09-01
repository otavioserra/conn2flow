<?php
/*********
	Descrição: guardas de execução do gestor instalador v2 (chave de pré-instalação,
	trava de concorrência e detecção do servidor web).
**********/

class InstallerGuard
{
    /** Versão corrente do gestor instalador. Fonte única para o index e o runner headless. */
    const VERSION = '2.1.1';

    /** Arquivo com a chave de segurança exigida antes de liberar o formulário. */
    const KEY_FILE = 'install-key.txt';

    /** Arquivo de trava da sessão que está conduzindo a instalação. */
    const LOCK_FILE = 'install.lock';

    /** Segundos de inatividade após os quais a trava pode ser tomada por outra sessão. */
    const LOCK_TIMEOUT = 1800;

    /** Caminho sondado para confirmar que o rewrite injeta `_gestor-caminho`. */
    const REWRITE_PROBE = '__c2f_rewrite_check__';

    /** Resposta devolvida pelo instalador quando a sonda de rewrite chega até ele. */
    const REWRITE_PROBE_OK = 'C2F_REWRITE_OK';

    /** Rota da API de pré-requisitos que reporta o estado do rewrite. */
    const API_REWRITE_PROBE = 'api/rewrite-probe';

    /** Arquivo de exemplo de VirtualHost Nginx entregue ao operador (REQ-027). */
    const NGINX_SAMPLE_FILE = 'nginx-vhost.conf.sample';

    public static function isCli()
    {
        return PHP_SAPI === 'cli';
    }

    public static function keyPath($baseDir)
    {
        return rtrim((string)$baseDir, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . self::KEY_FILE;
    }

    public static function lockPath($baseDir)
    {
        return rtrim((string)$baseDir, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . self::LOCK_FILE;
    }

    public static function readKey($baseDir)
    {
        $path = self::keyPath($baseDir);
        if (!is_file($path)) return '';

        return trim((string)file_get_contents($path));
    }

    /** Garante a existência da chave de segurança e devolve a chave vigente. */
    public static function ensureKey($baseDir)
    {
        $key = self::readKey($baseDir);
        if ($key !== '') return $key;

        $key = bin2hex(random_bytes(16));
        $path = self::keyPath($baseDir);
        if (file_put_contents($path, $key . PHP_EOL, LOCK_EX) === false) return '';
        @chmod($path, 0600);

        return $key;
    }

    public static function validateKey($baseDir, $provided)
    {
        $key = self::readKey($baseDir);
        $provided = trim((string)$provided);
        if ($key === '' || $provided === '') return false;

        return hash_equals($key, $provided);
    }

    public static function removeKey($baseDir)
    {
        $path = self::keyPath($baseDir);
        if (is_file($path)) @unlink($path);
    }

    private static function readLock($path)
    {
        if (!is_file($path)) return null;
        $lock = json_decode((string)file_get_contents($path), true);

        return is_array($lock) ? $lock : null;
    }

    private static function lockIsStale($lock, $now)
    {
        if (!is_array($lock)) return true;
        $ultimoSinal = (int)($lock['updated_at'] ?? $lock['created_at'] ?? 0);

        return ($now - $ultimoSinal) > self::LOCK_TIMEOUT;
    }

    public static function lockOwner($path, $token)
    {
        $lock = self::readLock($path);
        if (!is_array($lock) || !isset($lock['token_hash'])) return false;

        return hash_equals((string)$lock['token_hash'], hash('sha256', (string)$token));
    }

    /** Toma ou renova a trava. Devolve false quando outra sessão ainda está ativa. */
    public static function lockAcquire($path, $token)
    {
        $now = time();
        $lock = self::readLock($path);
        $sameOwner = self::lockOwner($path, $token);

        if ($lock !== null && !$sameOwner && !self::lockIsStale($lock, $now)) return false;

        $payload = json_encode([
            'token_hash' => hash('sha256', (string)$token),
            'created_at' => $sameOwner ? (int)($lock['created_at'] ?? $now) : $now,
            'updated_at' => $now,
        ], JSON_UNESCAPED_SLASHES);

        return file_put_contents($path, $payload, LOCK_EX) !== false;
    }

    /** Renova o carimbo de atividade da sessão dona sem disputar a trava. */
    public static function lockTouch($path, $token)
    {
        if (!self::lockOwner($path, $token)) return false;

        return self::lockAcquire($path, $token);
    }

    public static function lockRelease($path, $token)
    {
        if (!self::lockOwner($path, $token)) return false;

        return @unlink($path);
    }

    /**
     * Resolve qual rota de API o instalador deve atender na requisição corrente.
     *
     * A rota canônica é `gestor-instalador/api/rewrite-probe`, mas ela só existe quando o
     * rewrite está ativo — exatamente a condição que a sonda precisa diagnosticar. Por isso
     * `?api=rewrite-probe` é aceito como caminho determinístico: ele chega ao
     * front-controller do instalador mesmo em servidores sem rewrite configurado.
     * Devolve '' quando nenhuma rota de API foi solicitada.
     */
    public static function resolveApiRoute(array $request, array $server = [])
    {
        $candidatos = [];

        $caminho = (string)($request['_gestor-caminho'] ?? '');
        if ($caminho !== '') $candidatos[] = $caminho;

        $api = (string)($request['api'] ?? '');
        if ($api !== '') $candidatos[] = 'api/' . $api;

        $pathInfo = (string)($server['PATH_INFO'] ?? '');
        if ($pathInfo !== '') $candidatos[] = $pathInfo;

        $sufixo = '/' . self::API_REWRITE_PROBE;
        foreach ($candidatos as $candidato) {
            $normalizado = str_replace(DIRECTORY_SEPARATOR, '/', (string)$candidato);
            $normalizado = strtolower(trim(preg_replace('#/+#', '/', $normalizado), '/'));
            if ($normalizado === '') continue;
            if ($normalizado === self::API_REWRITE_PROBE) return self::API_REWRITE_PROBE;
            if (substr($normalizado, -strlen($sufixo)) === $sufixo) return self::API_REWRITE_PROBE;
        }

        return '';
    }

    /** Descobre o servidor web em execução. Devolve '' quando não é possível concluir. */
    public static function detectWebServer()
    {
        $software = strtolower((string)($_SERVER['SERVER_SOFTWARE'] ?? ''));
        if ($software === '') return '';
        if (strpos($software, 'nginx') !== false) return 'nginx';
        if (strpos($software, 'openresty') !== false) return 'nginx';
        // LiteSpeed e Apache compartilham o suporte a .htaccess.
        if (strpos($software, 'apache') !== false) return 'apache';
        if (strpos($software, 'httpd') !== false) return 'apache';
        if (strpos($software, 'litespeed') !== false) return 'apache';

        return '';
    }

    public static function normalizeWebServer($value, $fallback = 'apache')
    {
        $value = strtolower(trim((string)$value));
        if (in_array($value, ['nginx', 'apache'], true)) return $value;

        return in_array($fallback, ['nginx', 'apache'], true) ? $fallback : 'apache';
    }
}
