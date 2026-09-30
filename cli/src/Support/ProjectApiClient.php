<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Support;

use RuntimeException;

/**
 * Cliente da API de um projeto do `environment.json` (req-198/199): URL do projeto, token OAuth ou pessoal
 * (`api.access_token`) e, para ambientes sem DNS para o próprio nome (Lab), `api_resolve_ip`, que resolve o
 * host da URL para esse IP (como `curl --resolve`).
 */
final class ProjectApiClient
{
    private string $baseUrl;
    private string $token;
    private string $projectId;
    private ?string $resolveIp;

    /** @param array{accessUrl: string, id: string, config: array<string, mixed>} $projeto Saída do ProjectEnvironmentResolver. */
    public function __construct(array $projeto)
    {
        $config = $projeto['config'];
        $this->baseUrl = rtrim((string)($config['api_url'] ?? $projeto['accessUrl']), '/');
        $this->token = (string)($config['api']['access_token'] ?? '');
        $this->projectId = (string)$projeto['id'];
        $ip = trim((string)($config['api_resolve_ip'] ?? ''));
        $this->resolveIp = $ip !== '' ? $ip : null;
        if ($this->token === '') {
            throw new RuntimeException("Projeto '{$this->projectId}' sem api.access_token no environment.json; gere um token no perfil do usuário da instalação.");
        }
    }

    public function url(string $endpoint): string
    {
        return $this->baseUrl . '/' . ltrim($endpoint, '/');
    }

    /**
     * Chamada JSON. Devolve o código HTTP e a resposta decodificada.
     *
     * @return array{http: int, json: array<string, mixed>|null, corpo: string, erro: string}
     */
    public function request(string $metodo, string $endpoint, ?array $corpo = null, int $timeout = 300): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('A extensão cURL do PHP é necessária para falar com a API.');
        }
        $url = $this->url($endpoint);
        $ch = curl_init($url);
        $opcoes = [
            CURLOPT_CUSTOMREQUEST => strtoupper($metodo),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->token, 'X-Project-ID: ' . $this->projectId, 'Content-Type: application/json', 'Accept: application/json'],
        ];
        if ($corpo !== null) {
            $opcoes[CURLOPT_POSTFIELDS] = (string)json_encode($corpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $resolve = self::resolveEntry($url, $this->resolveIp);
        if ($resolve !== null) {
            $opcoes[CURLOPT_RESOLVE] = [$resolve];
            // Lab com certificado autoassinado: só quando o IP é forçado (ambiente de teste declarado).
            $opcoes[CURLOPT_SSL_VERIFYPEER] = false;
            $opcoes[CURLOPT_SSL_VERIFYHOST] = 0;
        }
        curl_setopt_array($ch, $opcoes);
        $resposta = (string)curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $erro = curl_error($ch);
        curl_close($ch);
        $json = json_decode($resposta, true);
        return ['http' => $http, 'json' => is_array($json) ? $json : null, 'corpo' => $resposta, 'erro' => $erro];
    }

    /** Entrada `host:porta:ip` do CURLOPT_RESOLVE para a URL, ou null sem IP forçado. */
    public static function resolveEntry(string $url, ?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }
        $u = parse_url($url);
        $porta = (int)($u['port'] ?? ((($u['scheme'] ?? 'https') === 'http') ? 80 : 443));
        return ($u['host'] ?? '') . ':' . $porta . ':' . $ip;
    }

    /** Mensagem legível para uma resposta de erro. */
    public static function describeError(array $r): string
    {
        if ($r['http'] === 0) {
            return 'sem resposta da API: ' . $r['erro'];
        }
        if ($r['http'] === 401) {
            return 'token recusado (401): renove o token do projeto e tente de novo';
        }
        $m = is_array($r['json']) ? (string)($r['json']['message'] ?? '') : substr($r['corpo'], 0, 300);
        return 'HTTP ' . $r['http'] . ($m !== '' ? ': ' . $m : '');
    }
}
