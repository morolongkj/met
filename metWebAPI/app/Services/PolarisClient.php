<?php
namespace App\Services;

use CodeIgniter\HTTP\CURLRequest;
use Config\Polaris as PolarisConfig;

class PolarisClient
{
    private PolarisConfig $cfg;
    private CURLRequest $http;

    public function __construct(?PolarisConfig $cfg = null, ?CURLRequest $http = null)
    {
        $this->cfg = $cfg ?? new PolarisConfig();

        // Build HTTP client with sane defaults
        $opts = [
            'baseURI'         => $this->cfg->baseURL ? ($this->cfg->baseURL . '/') : null,
            'timeout'         => $this->cfg->timeout,
            'connect_timeout' => $this->cfg->connectTimeout,
            'http_errors'     => false,
            'verify'          => $this->cfg->verifySSL, // true in prod
            'headers'         => [
                'Accept'     => 'application/json',
                'User-Agent' => 'CI4-PolarisSync/1.0',
            ],
        ];

        // If you trust a custom CA, explicitly pass it to cURL
        if ($this->cfg->caFile) {
            $opts['verify']  = true; // keep verification ON when CA is provided
            $opts['options'] = [
                CURLOPT_CAINFO => $this->cfg->caFile,
            ];
        }

        $this->http = $http ?? service('curlrequest', $opts);

        if (empty($this->cfg->baseURL) || empty($this->cfg->apiToken)) {
            throw new \RuntimeException('PolarisClient requires POLARIS_BASE_URL and POLARIS_API_TOKEN in .env');
        }
    }

    /** Build absolute URL when a relative path is provided. */
    private function buildUrl(string $path): string
    {
        if (preg_match('~^https?://~i', $path)) {
            return $path; // absolute "next" URLs supported
        }
        return rtrim($this->cfg->baseURL, '/') . '/' . ltrim($path, '/');
    }

    /** Split URL into base (scheme://host[:port]/path) and an array of existing query params. */
    private function splitUrl(string $url): array
    {
        $p    = parse_url($url) ?: [];
        $base = ($p['scheme'] ?? 'https') . '://' . ($p['host'] ?? '')
            . (isset($p['port']) ? ':' . $p['port'] : '')
            . ($p['path'] ?? '/');

        $existing = [];
        if (! empty($p['query'])) {
            parse_str($p['query'], $existing);
        }
        return [$base, $existing];
    }

    /**
     * GET JSON and return decoded array.
     * Always ensures api_token is present (even when following "next" URLs).
     */
    public function get(string $path, array $query = []): array
    {
        $url = $this->buildUrl($path);

        // Merge any query already present in the URL (e.g., from "next") with our own
        [$cleanUrl, $existing] = $this->splitUrl($url);
        $params                = $existing + $query;

        // Always include token
        if (empty($params['api_token'])) {
            $params['api_token'] = $this->cfg->apiToken;
        }

        // Provide default limit if not already present
        if (! array_key_exists('limit', $params)) {
            $params['limit'] = $this->cfg->pageSize;
        }

        $res    = $this->http->get($cleanUrl, ['query' => $params]);
        $status = $res->getStatusCode();
        $body   = (string) $res->getBody();

        if ($status !== 200) {
            $full = $cleanUrl . '?' . http_build_query($params);
            log_message('error', 'Polaris GET failed: {status} {url} body: {body}', [
                'status' => $status,
                'url'    => $full,
                'body'   => substr($body, 0, 1000),
            ]);
            throw new \RuntimeException("Polaris request failed: HTTP {$status}");
        }

        $data = json_decode($body, true);
        if (! is_array($data)) {
            throw new \RuntimeException('Invalid JSON from Polaris');
        }

        return $data;
    }

    /**
     * Iterate across pages; "next" can be relative or absolute.
     * We do not carry paging params; "next" controls page/limit and we only re-inject the token.
     */
    public function paginate(string $path, array $query = []): \Generator
    {
        $next  = $path;
        $carry = $query;

        while ($next) {
            $data  = $this->get($next, $carry);
            $items = $data['items'] ?? [];
            if (! empty($items)) {
                yield $items;
            }
            $next  = $data['metadata']['page']['next'] ?? null; // relative or absolute
            $carry = [];                                        // let "next" decide paging; we’ll still add token in get()
        }
    }
}
