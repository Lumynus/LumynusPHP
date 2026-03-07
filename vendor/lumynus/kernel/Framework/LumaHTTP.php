<?php

declare(strict_types=1);

/**
 * @author WelenySantos de Oliveira <welenysantos@gmail.com>
 * @package Lumynus\Framework
 */

namespace Lumynus\Framework;

use Lumynus\Framework\LumaClasses;

/**
 * Classe responsável por gerenciar requisições HTTP com suporte a autenticação e rate limiting.
 *
 * @author Lumynus Framework
 * @version 2.0
 */
final class LumaHTTP extends LumaClasses
{
    /** @var array<string> Lista de cabeçalhos HTTP */
    private array $headers = [];

    /** @var int Código de status HTTP da última requisição */
    private int $statusCode = 0;

    /** @var string Resposta da última requisição */
    private string $response = '';

    /** @var string Erro da última requisição */
    private string $error = '';

    /** @var string URL da última requisição */
    private string $lastUrl = '';

    /** @var bool Indica se SSL deve ser verificado */
    private bool $verifySSL = true;

    /** @var int Timeout para conexão em segundos */
    private int $timeout = 30;

    // Rate limiting
    /** @var int|null Limite de requisições por período */
    private ?int $rateLimit = null;

    /** @var int|null Período em segundos para rate limiting */
    private ?int $ratePeriod = null;

    /** @var int Contador de requisições no período atual */
    private int $requestCount = 0;

    /** @var int Timestamp do início do período atual */
    private int $periodStart;

    /** @var string Estratégia de rate limiting */
    private string $rateLimitStrategy = 'wait'; // 'wait', 'throw', 'skip'

    /**
     * Construtor da classe
     *
     * @throws \RuntimeException Se cURL não estiver disponível
     */
    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('Extensão cURL não está disponível');
        }

        $this->periodStart = time();
    }

    /**
     * Configura autenticação Bearer Token
     *
     * @param string $token Token de autenticação
     * @param string $contentType Tipo de conteúdo (json, xml, form, text)
     * @param string $accept Tipo de resposta aceita
     * @return self
     */
    public function useBearer(string $token, string $contentType = 'json', string $accept = 'json'): self
    {
        $this->headers = array_merge($this->headers, [
            'Authorization: Bearer ' . $token,
            $this->getContentTypeHeader($contentType),
            $this->getAcceptHeader($accept)
        ]);

        return $this;
    }

    /**
     * Configura autenticação API Key
     *
     * @param string $apiKey Chave da API
     * @param string $contentType Tipo de conteúdo
     * @param string $accept Tipo de resposta aceita
     * @return self
     */
    public function useApiKey(string $apiKey, string $contentType = 'json', string $accept = 'json'): self
    {
        $this->headers = array_merge($this->headers, [
            'X-API-Key: ' . $apiKey,
            $this->getContentTypeHeader($contentType),
            $this->getAcceptHeader($accept)
        ]);

        return $this;
    }

    /**
     * Configura autenticação Basic
     *
     * @param string $username Nome de usuário
     * @param string $password Senha
     * @param string $contentType Tipo de conteúdo
     * @param string $accept Tipo de resposta aceita
     * @return self
     */
    public function useBasicAuth(string $username, string $password, string $contentType = 'json', string $accept = 'json'): self
    {
        $credentials = base64_encode($username . ':' . $password);

        $this->headers = array_merge($this->headers, [
            'Authorization: Basic ' . $credentials,
            $this->getContentTypeHeader($contentType),
            $this->getAcceptHeader($accept)
        ]);

        return $this;
    }

    /**
     * Adiciona cabeçalhos customizados
     *
     * @param array<string, string> $headers Cabeçalhos no formato ['chave' => 'valor']
     * @return self
     */
    public function headers(array $headers): self
    {
        foreach ($headers as $key => $value) {
            $this->headers[] = $key . ': ' . $value;
        }

        return $this;
    }

    /**
     * Configura timeout da requisição
     *
     * @param int $seconds Timeout em segundos
     * @return self
     */
    public function timeout(int $seconds): self
    {
        $this->timeout = max(1, $seconds);
        return $this;
    }

    /**
     * Configura verificação SSL
     *
     * @param bool $verify Se deve verificar SSL
     * @return self
     */
    public function sslVerification(bool $verify = true): self
    {
        $this->verifySSL = $verify;
        return $this;
    }

    /**
     * Configura rate limiting
     *
     * @param int $requests Número máximo de requisições
     * @param int $seconds Período em segundos
     * @param string $strategy Estratégia de rate limiting ('wait', 'throw', 'skip')
     * @return self
     */
    public function rateLimit(int $requests, int $seconds, string $strategy = 'wait'): self
    {
        $this->rateLimit = max(1, $requests);
        $this->ratePeriod = max(1, $seconds);

        $validStrategies = ['wait', 'throw', 'skip'];
        $this->rateLimitStrategy = in_array($strategy, $validStrategies) ? $strategy : 'wait';

        return $this;
    }

    /**
     * Realiza uma requisição GET
     *
     * @param string $url URL da requisição
     * @return self
     */
    public function get(string $url): self
    {
        return $this->request('GET', $url);
    }

    /**
     * Realiza uma requisição POST
     *
     * @param string $url URL da requisição
     * @param mixed $data Dados para envio
     * @return self
     */
    public function post(string $url, $data = null): self
    {
        return $this->request('POST', $url, $data);
    }

    /**
     * Realiza uma requisição PUT
     *
     * @param string $url URL da requisição
     * @param mixed $data Dados para envio
     * @return self
     */
    public function put(string $url, $data = null): self
    {
        return $this->request('PUT', $url, $data);
    }

    /**
     * Realiza uma requisição DELETE
     *
     * @param string $url URL da requisição
     * @return self
     */
    public function delete(string $url): self
    {
        return $this->request('DELETE', $url);
    }

    /**
     * Realiza a requisição HTTP
     *
     * @param string $method Método HTTP
     * @param string $url URL da requisição
     * @param mixed $data Dados para envio
     * @return self
     * @throws \InvalidArgumentException Para URLs ou métodos inválidos
     */
    public function request(string $method, string $url, $data = null): self
    {
        // Validações robustas
        $this->validateUrl($url);
        $this->validateMethod($method);
        $this->validateRequestData($data, $method);
        $this->validateConfiguration();

        // Aplica rate limiting (pode retornar false para pular)
        if (!$this->applyRateLimit()) {
            return $this; // Requisição pulada devido ao rate limit
        }

        $this->lastUrl = $url;

        try {
            $curl = $this->createCurlHandle($url, $method, $data);

            $response = curl_exec($curl);
            $this->statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);

            if (PHP_VERSION_ID < 80500) {
                curl_close($curl);
            } else {
                unset($curl);
            }

            if ($response === false) {
                $this->error = $curlError ?: 'Unknown cURL request failure';
                $this->response = '';
                throw new \RuntimeException($this->error);
            }

            $this->response = (string) $response;
            $this->error = $curlError;
        } catch (\Exception $e) {
            $this->response = '';
            $this->statusCode = 0;
            $this->error = $e->getMessage();

            // Re-lança exceções críticas
            if (
                $e instanceof \InvalidArgumentException ||
                ($e instanceof \RuntimeException && str_contains($e->getMessage(), 'Rate limit'))
            ) {
                throw $e;
            }
        }

        return $this;
    }

    /**
     * Verifica se pode fazer uma nova requisição com base no rate limit
     *
     * @return bool
     */
    public function canMakeRequest(): bool
    {
        if ($this->rateLimit === null || $this->ratePeriod === null) {
            return true;
        }

        $currentTime = time();
        $elapsedTime = $currentTime - $this->periodStart;

        // Reset do período se necessário
        if ($elapsedTime >= $this->ratePeriod) {
            return true; // Período resetou, pode fazer requisição
        }

        return $this->requestCount < $this->rateLimit;
    }

    /**
     * Retorna quantas requisições ainda podem ser feitas no período atual
     *
     * @return int|string
     */
    public function getRemainingRequests()
    {
        if ($this->rateLimit === null) {
            return 'unlimited';
        }

        $currentTime = time();
        $elapsedTime = $currentTime - $this->periodStart;

        // Reset do período se necessário
        if ($elapsedTime >= $this->ratePeriod) {
            return $this->rateLimit; // Período resetou
        }

        return max(0, $this->rateLimit - $this->requestCount);
    }

    /**
     * Retorna o tempo em segundos até o próximo reset do rate limit
     *
     * @return int
     */
    public function getTimeUntilReset(): int
    {
        if ($this->ratePeriod === null) {
            return 0;
        }

        $currentTime = time();
        $elapsedTime = $currentTime - $this->periodStart;

        return max(0, $this->ratePeriod - $elapsedTime);
    }

    /**
     * Retorna o código de status da última requisição
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Retorna a resposta como string
     *
     * @return string
     */
    public function getResponse(): string
    {
        return $this->response;
    }

    /**
     * Retorna a resposta como array (decodifica JSON)
     *
     * @return array<mixed>
     */
    public function getArray(): array
    {
        $decoded = json_decode($this->response, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Retorna a resposta como objeto (decodifica JSON)
     *
     * @return object|null
     */
    public function getObject(): ?object
    {
        $decoded = json_decode($this->response);
        return is_object($decoded) ? $decoded : null;
    }

    /**
     * Retorna se a requisição foi bem-sucedida (status 2xx)
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Retorna o erro da última requisição
     *
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Salva a resposta em um arquivo
     *
     * @param string $filePath Caminho do arquivo
     * @return bool True se salvou com sucesso
     */
    public function saveToFile(string $filePath): bool
    {
        if (empty($this->response)) {
            return false;
        }

        $directory = dirname($filePath);
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            return false;
        }

        return file_put_contents($filePath, $this->response) !== false;
    }

    /**
     * Cria e configura o handle do cURL
     *
     * @param string $url URL da requisição
     * @param string $method Método HTTP
     * @param mixed $data Dados para envio
     * @return \CurlHandle Handle do cURL
     * @throws \RuntimeException Se não conseguir criar o handle
     */
    private function createCurlHandle(string $url, string $method, $data = null): \CurlHandle
    {
        $curl = curl_init();

        if ($curl === false) {
            throw new \RuntimeException('Não foi possível inicializar o cURL');
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_SSL_VERIFYPEER => $this->verifySSL,
            CURLOPT_SSL_VERIFYHOST => $this->verifySSL ? 2 : 0,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_FAILONERROR => false,
            CURLOPT_USERAGENT => 'LumHTTP/2.0'
        ]);

        if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->prepareData($data));
        }

        return $curl;
    }

    /**
     * Prepara os dados para envio
     *
     * @param mixed $data Dados para preparar
     * @return string|array<mixed>
     */
    private function prepareData($data)
    {
        if (is_array($data) || is_object($data)) {
            // Verifica se há arquivos no array
            if (is_array($data) && $this->hasFileUploads($data)) {
                return $data; // Mantém como array para multipart/form-data
            }

            // Se o Content-Type é JSON, codifica como JSON
            if ($this->isJsonContentType()) {
                return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            // Caso contrário, codifica como form data
            return http_build_query($data);
        }

        return (string) $data;
    }

    /**
     * Verifica se há uploads de arquivo nos dados
     *
     * @param array<mixed> $data
     * @return bool
     */
    private function hasFileUploads($data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        return $this->recursiveFileCheck($data);
    }

    /**
     * Verifica recursivamente por arquivos em arrays/objetos
     *
     * @param mixed $data
     * @return bool
     */
    private function recursiveFileCheck($data): bool
    {
        if (is_array($data) || is_object($data)) {
            foreach ($data as $value) {
                if ($this->isFileUpload($value) || $this->recursiveFileCheck($value)) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Verifica se o Content-Type é JSON
     *
     * @return bool
     */
    private function isJsonContentType(): bool
    {
        foreach ($this->headers as $header) {
            if (stripos($header, 'Content-Type: application/json') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica se um valor específico é um upload de arquivo
     *
     * @param mixed $value
     * @return bool
     */
    private function isFileUpload($value): bool
    {
        // Classes nativas do cURL
        if ($value instanceof \CURLFile || $value instanceof \CURLStringFile) {
            return true;
        }

        // String que começa com @ (formato antigo do cURL)
        if (is_string($value) && str_starts_with($value, '@')) {
            return true;
        }

        // Array com chaves específicas de upload
        if (is_array($value)) {
            $uploadKeys = ['tmp_name', 'name', 'type', 'size', 'error'];
            $hasUploadKeys = array_intersect_key($value, array_flip($uploadKeys));

            if ($this->l_count($hasUploadKeys) >= 3) { // Pelo menos 3 chaves de upload
                return true;
            }
        }

        // Objeto com propriedades de upload (ex: UploadedFile do Symfony/Laravel)
        if (is_object($value)) {
            $methods = get_class_methods($value);
            $uploadMethods = ['getClientOriginalName', 'getMimeType', 'getSize', 'getPathname', 'isValid'];

            if (array_intersect($methods, $uploadMethods)) {
                return true;
            }

            // Verifica propriedades comuns de upload
            $properties = get_object_vars($value);
            if (isset($properties['tmp_name']) || isset($properties['name']) || isset($properties['type'])) {
                return true;
            }
        }

        // Resource de arquivo
        if (is_resource($value) && get_resource_type($value) === 'stream') {
            $meta = stream_get_meta_data($value);
            return isset($meta['uri']) && is_file($meta['uri']);
        }

        return false;
    }

    /**
     * Aplica rate limiting se configurado
     *
     * @return void
     */
    private function applyRateLimit(): bool
    {
        if ($this->rateLimit === null || $this->ratePeriod === null) {
            return true;
        }

        $currentTime = time();
        $elapsedTime = $currentTime - $this->periodStart;

        // Reset do período se necessário
        if ($elapsedTime >= $this->ratePeriod) {
            $this->requestCount = 0;
            $this->periodStart = $currentTime;
            $elapsedTime = 0;
        }

        // Verifica se atingiu o limite
        if ($this->requestCount >= $this->rateLimit) {
            $waitTime = $this->ratePeriod - $elapsedTime;

            switch ($this->rateLimitStrategy) {
                case 'throw':
                    throw new \RuntimeException(
                        sprintf(
                            'Rate limit reached: %d/%d requests. Try again in %d seconds.',
                            $this->requestCount,
                            $this->rateLimit,
                            $waitTime
                        )
                    );

                case 'skip':
                    $this->error = sprintf(
                        'Request skipped due to rate limit: %d/%d. Reset in %d seconds.',
                        $this->requestCount,
                        $this->rateLimit,
                        $waitTime
                    );
                    return false;

                case 'wait':
                default:
                    if ($waitTime > 0) {
                        // Para aplicações assíncronas, evita bloqueios longos
                        if ($waitTime > 5) {
                            // Se o tempo de espera for muito longo, lance exceção
                            throw new \RuntimeException(
                                sprintf(
                                    'Rate limit reached. Wait time too long: %d seconds. Use "skip" or "throw" strategy.',
                                    $waitTime
                                )
                            );
                        }
                        sleep($waitTime);
                        $this->requestCount = 0;
                        $this->periodStart = time();
                    }
                    break;
            }
        }

        $this->requestCount++;
        return true;
    }

    /**
     * Valida a URL
     *
     * @param string $url
     * @throws \InvalidArgumentException
     */
    private function validateUrl(string $url): void
    {
        // Verifica se não está vazia
        if (empty(trim($url))) {
            throw new \InvalidArgumentException('URL cannot be empty');
        }

        // Validação básica de formato
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL format: ' . $url);
        }

        // Parse da URL para validações específicas
        $parsed = parse_url($url);

        if ($parsed === false) {
            throw new \InvalidArgumentException('Could not parse URL: ' . $url);
        }

        // Verifica se tem scheme
        if (!isset($parsed['scheme'])) {
            throw new \InvalidArgumentException('URL must contain a protocol (http/https): ' . $url);
        }

        // Só permite HTTP e HTTPS
        $allowedSchemes = ['http', 'https'];
        if (!in_array(strtolower($parsed['scheme']), $allowedSchemes)) {
            throw new \InvalidArgumentException(
                'Unsupported protocol: ' . $parsed['scheme'] . '. Use http or https.'
            );
        }

        // Verifica se tem host
        if (!isset($parsed['host']) || empty($parsed['host'])) {
            throw new \InvalidArgumentException('URL must contain a valid host: ' . $url);
        }

        // Validação de host
        if (!$this->isValidHost($parsed['host'])) {
            throw new \InvalidArgumentException('Invalid host: ' . $parsed['host']);
        }

        // Verifica porta se especificada
        if (isset($parsed['port'])) {
            if ($parsed['port'] < 1 || $parsed['port'] > 65535) {
                throw new \InvalidArgumentException('Invalid port: ' . $parsed['port']);
            }
        }
    }

    /**
     * Valida se o host é válido
     *
     * @param string $host
     * @return bool
     */
    private function isValidHost(string $host): bool
    {
        // Remove porta se presente
        $host = explode(':', $host)[0];

        // Verifica se é um IP válido
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }

        // Verifica se é um hostname válido
        if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return true;
        }

        // Validação manual adicional para casos especiais
        if (preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/', $host)) {
            return true;
        }

        return false;
    }

    /**
     * Valida o método HTTP de forma mais robusta
     *
     * @param string $method
     * @throws \InvalidArgumentException
     */
    private function validateMethod(string $method): void
    {
        if (empty(trim($method))) {
            throw new \InvalidArgumentException('HTTP method cannot be empty');
        }

        $method = strtoupper(trim($method));
        $validMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS', 'TRACE', 'CONNECT'];

        if (!in_array($method, $validMethods)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid HTTP method: %s. Supported methods: %s',
                    $method,
                    implode(', ', $validMethods)
                )
            );
        }

        // Validações específicas por método
        switch ($method) {
            case 'TRACE':
            case 'CONNECT':
                // Métodos que geralmente não são usados em APIs REST
                trigger_error(
                    "Method $method is rarely used. Please verify if it's really necessary.",
                    E_USER_NOTICE
                );
                break;
        }
    }


    /**
     * Valida dados de entrada de forma mais robusta
     *
     * @param mixed $data
     * @param string $method
     * @throws \InvalidArgumentException
     */
    private function validateRequestData($data, string $method): void
    {
        // Métodos que normalmente não devem ter corpo
        $methodsWithoutBody = ['GET', 'HEAD', 'DELETE', 'OPTIONS'];

        if (in_array(strtoupper($method), $methodsWithoutBody) && $data !== null) {
            trigger_error(
                "Method $method normally should not contain data in request body.",
                E_USER_WARNING
            );
        }

        // Verifica tamanho dos dados (para evitar problemas de memória)
        if (is_string($data) && strlen($data) > 10 * 1024 * 1024) { // 10MB
            trigger_error(
                'Data too large (>10MB). Consider using streaming or splitting the request.',
                E_USER_WARNING
            );
        }

        // Validação de dados JSON
        if (is_string($data) && $this->isJsonContentType()) {
            json_decode($data);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException(
                    'Invalid JSON data: ' . json_last_error_msg()
                );
            }
        }
    }

    /**
     * Valida configurações antes da requisição
     *
     * @throws \InvalidArgumentException
     */
    private function validateConfiguration(): void
    {
        // Valida timeout
        if ($this->timeout <= 0) {
            throw new \InvalidArgumentException('Timeout must be greater than zero');
        }

        if ($this->timeout > 300) { // 5 minutos
            trigger_error(
                'Timeout too high (' . $this->timeout . 's). Consider using a lower value.',
                E_USER_WARNING
            );
        }

        // Valida rate limiting
        if ($this->rateLimit !== null && $this->ratePeriod !== null) {
            if ($this->rateLimit <= 0 || $this->ratePeriod <= 0) {
                throw new \InvalidArgumentException('Rate limit and period must be greater than zero');
            }

            if ($this->rateLimit > 1000) {
                trigger_error(
                    'Rate limit too high (' . $this->rateLimit . '). Please verify if it\'s correct.',
                    E_USER_WARNING
                );
            }
        }
    }

    /**
     * Retorna o cabeçalho Content-Type baseado no tipo
     *
     * @param string $type
     * @return string
     */
    private function getContentTypeHeader(string $type): string
    {
        $types = [
            'json' => 'Content-Type: application/json',
            'xml' => 'Content-Type: application/xml',
            'form' => 'Content-Type: application/x-www-form-urlencoded',
            'text' => 'Content-Type: text/plain',
            'multipart' => 'Content-Type: multipart/form-data'
        ];

        return $types[$type] ?? $types['json'];
    }

    /**
     * Retorna o cabeçalho Accept baseado no tipo
     *
     * @param string $type
     * @return string
     */
    private function getAcceptHeader(string $type): string
    {
        $types = [
            'json' => 'Accept: application/json',
            'xml' => 'Accept: application/xml',
            'form' => 'Accept: application/x-www-form-urlencoded',
            'text' => 'Accept: text/plain',
            'html' => 'Accept: text/html'
        ];

        return $types[$type] ?? $types['json'];
    }


    /**
     * Exibe estatísticas detalhadas das requisições e rate limiting
     * Útil para debugging e monitoramento durante desenvolvimento
     *
     * @param bool $formatted Se deve retornar formatado para exibição ou array
     * @return array<string, mixed>|string
     */
    public function getRequestStats(bool $formatted = true)
    {
        $currentTime = time();
        $elapsedTime = $currentTime - $this->periodStart;
        $remainingTime = $this->ratePeriod ? max(0, $this->ratePeriod - $elapsedTime) : 0;
        $remainingRequests = $this->rateLimit ? max(0, $this->rateLimit - $this->requestCount) : 'Unlimited';

        $stats = [
            'request_info' => [
                'last_url' => $this->lastUrl ?: 'No requests made yet',
                'last_status_code' => $this->statusCode,
                'last_error' => $this->error ?: 'No errors',
                'ssl_verification' => $this->verifySSL ? 'Enabled' : 'Disabled',
                'timeout' => $this->timeout . ' seconds',
            ],
            'rate_limiting' => [
                'status' => $this->rateLimit ? 'Enabled' : 'Disabled',
                'limit_per_period' => $this->rateLimit ?: 'Not configured',
                'period_duration' => $this->ratePeriod ? $this->ratePeriod . ' seconds' : 'Not configured',
                'current_period_requests' => $this->requestCount,
                'remaining_requests' => $remainingRequests,
                'period_elapsed_time' => $elapsedTime . ' seconds',
                'time_until_reset' => $remainingTime . ' seconds',
                'period_start' => date('Y-m-d H:i:s', $this->periodStart),
                'next_reset' => $this->ratePeriod ? date('Y-m-d H:i:s', $this->periodStart + $this->ratePeriod) : 'N/A'
            ],
            'headers_count' => $this->l_count($this->headers),
            'timestamp' => date('Y-m-d H:i:s', $currentTime)
        ];

        if (!$formatted) {
            return $stats;
        }

        return $this->formatStatsOutput($stats);
    }

    /**
     * Formata a saída das estatísticas para exibição legível
     *
     * @param array<string, mixed> $stats
     * @return string
     */
    private function formatStatsOutput(array $stats): string
    {
        $output = "\n" . str_repeat("=", 60) . "\n";
        $output .= "           LUMYNUS HTTP - REQUEST STATISTICS\n";
        $output .= str_repeat("=", 60) . "\n";

        // Last request information
        $output .= "\n📊 LAST REQUEST:\n";
        $output .= str_repeat("-", 40) . "\n";
        $output .= sprintf("• URL: %s\n", $stats['request_info']['last_url']);
        $output .= sprintf("• Status Code: %s\n", $stats['request_info']['last_status_code'] ?: 'N/A');
        $output .= sprintf("• Error: %s\n", $stats['request_info']['last_error']);
        $output .= sprintf("• SSL: %s\n", $stats['request_info']['ssl_verification']);
        $output .= sprintf("• Timeout: %s\n", $stats['request_info']['timeout']);

        // Rate Limiting
        $output .= "\n🚦 RATE LIMITING:\n";
        $output .= str_repeat("-", 40) . "\n";
        $output .= sprintf("• Status: %s\n", $stats['rate_limiting']['status']);

        if ($this->rateLimit) {
            $output .= sprintf(
                "• Limit: %s requests per %s\n",
                $stats['rate_limiting']['limit_per_period'],
                $stats['rate_limiting']['period_duration']
            );
            $output .= sprintf("• Current period requests: %s\n", $stats['rate_limiting']['current_period_requests']);
            $output .= sprintf("• Remaining requests: %s\n", $stats['rate_limiting']['remaining_requests']);
            $output .= sprintf("• Elapsed time in period: %s\n", $stats['rate_limiting']['period_elapsed_time']);
            $output .= sprintf("• Time until reset: %s\n", $stats['rate_limiting']['time_until_reset']);
            $output .= sprintf("• Period start: %s\n", $stats['rate_limiting']['period_start']);
            $output .= sprintf("• Next reset: %s\n", $stats['rate_limiting']['next_reset']);

            // Visual progress bar
            $progress = $this->rateLimit > 0 ? ($this->requestCount / $this->rateLimit) * 100 : 0;
            $progressBar = $this->createProgressBar($progress);
            $output .= sprintf("• Limit progress: %s %.1f%%\n", $progressBar, $progress);
        }

        // General information
        $output .= "\n⚙️  CONFIGURATION:\n";
        $output .= str_repeat("-", 40) . "\n";
        $output .= sprintf("• Configured headers: %s\n", $stats['headers_count']);
        $output .= sprintf("• Timestamp: %s\n", $stats['timestamp']);

        $output .= "\n" . str_repeat("=", 60) . "\n";

        return $output;
    }

    /**
     * Cria uma barra de progresso visual
     *
     * @param float $percentage
     * @return string
     */
    private function createProgressBar(float $percentage): string
    {
        $barLength = 20;
        $filledLength = (int) round(($percentage / 100) * $barLength);
        $emptyLength = $barLength - $filledLength;

        $bar = '[' . str_repeat('█', $filledLength) . str_repeat('░', $emptyLength) . ']';

        // Adiciona cor baseada na porcentagem (para terminais que suportam)
        if ($percentage >= 90) {
            $bar = "\033[31m" . $bar . "\033[0m"; // Vermelho
        } elseif ($percentage >= 70) {
            $bar = "\033[33m" . $bar . "\033[0m"; // Amarelo
        } else {
            $bar = "\033[32m" . $bar . "\033[0m"; // Verde
        }

        return $bar;
    }

    /**
     * Exibe as estatísticas diretamente no output (útil para debugging)
     *
     * @return void
     */
    public function showStats(): void
    {
        echo $this->getRequestStats(true);
    }

    /**
     * Salva as estatísticas em um arquivo de log
     *
     * @param string $filePath Caminho do arquivo
     * @param bool $append Se deve anexar ao arquivo existente
     * @return bool
     */
    public function logStats(string $filePath, bool $append = true): bool
    {
        $stats = $this->getRequestStats(true);
        $logEntry = "\n" . date('Y-m-d H:i:s') . " - LUMYNUS HTTP STATS\n" . $stats . "\n";

        $flags = $append ? FILE_APPEND | LOCK_EX : LOCK_EX;

        return file_put_contents($filePath, $logEntry, $flags) !== false;
    }

    /**
     * Retorna um resumo rápido das estatísticas em uma linha
     *
     * @return string
     */
    public function getQuickStats(): string
    {
        $rateLimitInfo = $this->rateLimit
            ? sprintf("Rate Limit: %d/%d", $this->requestCount, $this->rateLimit)
            : "Rate Limit: OFF";

        $lastStatus = $this->statusCode ? "Status: {$this->statusCode}" : "Status: N/A";

        return sprintf(
            "[LumaHTTP] %s | %s | Headers: %d | SSL: %s",
            $rateLimitInfo,
            $lastStatus,
            $this->l_count($this->headers),
            $this->verifySSL ? 'ON' : 'OFF'
        );
    }

    /**
     * Teste de debug completo
     *
     * Este método executa uma série de testes para verificar se a classe LumaHTTP está funcionando corretamente.
     * Ele inclui validações de pré-requisitos, criação de instância, requisições GET e POST, e exibição de resultados.
     */
    public function debugTest()
    {
        echo "🔧 LUMAHTTP COMPLETE DEBUG TEST\n <br><br>";
        echo str_repeat("=", 50) . "\n <br><br>";

        // Check required extensions
        echo "📋 Checking prerequisites:\n <br>";
        echo "- cURL: " . (extension_loaded('curl') ? '✅ OK' : '❌ MISSING') . "\n <br>";
        echo "- JSON: " . (extension_loaded('json') ? '✅ OK' : '❌ MISSING') . "\n <br>";

        if (!extension_loaded('curl')) {
            echo "❌ cURL is not available. Please install php-curl extension.\n <br>";
            return;
        }

        try {
            echo "\n🏗️ Instantiating LumaHTTP...\n <br>";
            $http = new \Lumynus\Framework\LumaHTTP();
            echo "✅ Instance created successfully!\n <br>";

            echo "\n📡 Testing GET request...\n<br>";
            $start = microtime(true);
            $response = $http->get('https://jsonplaceholder.typicode.com/posts/1');
            $duration = round((microtime(true) - $start) * 1000, 2);

            echo "⏱️ Response time: {$duration}ms\n <br>";

            echo "<br>\n📊 Results:\n<br>";
            echo "- Status Code: " . $response->getStatusCode() . "\n<br>";
            echo "- Is Success: " . ($response->isSuccess() ? 'Yes' : 'No') . "\n<br>";
            echo "- Has Error: " . (empty($response->getError()) ? 'No' : 'Yes') . "\n<br><br>";

            if (!empty($response->getError())) {
                echo "- Error: " . $response->getError() . "\n<br><br>";
            }

            $responseData = $response->getResponse();
            echo "- Response Size: " . strlen($responseData) . " bytes\n <br>";

            if ($response->isSuccess()) {
                $array = $response->getArray();
                echo "- Valid JSON: " . (is_array($array) && !empty($array) ? 'Yes' : 'No') . "\n <br><br>";

                if (!empty($array)) {
                    echo "\n📄 Received data:\n<br>";
                    foreach (['id', 'title', 'userId'] as $field) {
                        if (isset($array[$field])) {
                            $value = is_string($array[$field]) ?
                                substr($array[$field], 0, 50) . (strlen($array[$field]) > 50 ? '...' : '') :
                                $array[$field];
                            echo "- $field: " . $value . "\n<br>";
                        }
                    }
                }
            }

            echo "\n" . str_repeat("=", 50) . "\n <br>";
            echo "<br>🎉 Debug test completed successfully!\n<br><br>";
        } catch (\Throwable $e) {
            echo "<br>💥 CRITICAL ERROR:\n<br><br>";
            echo "- Type: " . get_class($e) . "\n";
            echo "- Message: " . $e->getMessage() . "\n";
            echo "- File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            echo "- Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
    }

    /**
     * Informações de debug
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $sanitizedHeaders = array_map(function ($header) {
            if (stripos($header, 'Authorization:') !== false || stripos($header, 'X-API-Key:') !== false) {
                return preg_replace('/(:\s*)(.+)/', '$1[HIDDEN]', $header);
            }
            return $header;
        }, $this->headers);

        return [
            'lastUrl' => $this->lastUrl,
            'statusCode' => $this->statusCode,
            'verifySSL' => $this->verifySSL,
            'timeout' => $this->timeout,
            'headers' => $sanitizedHeaders,
            'error' => $this->error,
            'rateLimit' => $this->rateLimit,
            'ratePeriod' => $this->ratePeriod,
            'rateLimitStrategy' => $this->rateLimitStrategy ?? 'wait',
            'requestCount' => $this->requestCount,
            'canMakeRequest' => $this->canMakeRequest(),
            'remainingRequests' => $this->getRemainingRequests(),
            'timeUntilReset' => $this->getTimeUntilReset()
        ];
    }
}
