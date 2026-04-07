<?php
namespace Config;

use CodeIgniter\Config\BaseConfig;

class Polaris extends BaseConfig
{
    public string $baseURL     = '';
    public string $apiToken    = '';
    public int $timeout        = 30;
    public int $connectTimeout = 10;
    public bool $verifySSL     = true;
    public int $pageSize       = 500;
    public ?string $caFile     = null; // optional path to CA file (for self-signed / private CA)

    public function __construct()
    {
        parent::__construct();

        $this->baseURL        = rtrim((string) env('POLARIS_BASE_URL', ''), '/');
        $this->apiToken       = (string) env('POLARIS_API_TOKEN', '');
        $this->timeout        = (int) env('POLARIS_TIMEOUT', 30);
        $this->connectTimeout = (int) env('POLARIS_CONNECT_TIMEOUT', 10);
        $this->verifySSL      = filter_var(env('POLARIS_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN);
        $this->pageSize       = (int) env('POLARIS_PAGE_SIZE', 500);
        $this->caFile         = env('POLARIS_CA_FILE') ?: null;
    }
}
