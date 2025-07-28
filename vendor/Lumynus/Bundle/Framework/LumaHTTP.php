<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;
use Lumynus\Bundle\Framework\LumaClasses;

/**
 * Classe responsável por gerenciar requisições HTTP com suporte a autenticação e rate limiting.
 * 
 * @author Lumynus Framework
 * @version 2.0
 */
class LumaHTTP extends LumaClasses
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
    public function withBearerAuth(string $token, string $contentType = 'json', string $accept = 'json'): self
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
    public function withApiKey(string $apiKey, string $contentType = 'json', string $accept = 'json'): self
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
    public function withBasicAuth(string $username, string $password, string $contentType = 'json', string $accept = 'json'): self
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
    public function withHeaders(array $headers): self
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
    public function withTimeout(int $seconds): self
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
    public function withSSLVerification(bool $verify = true): self
    {
        $this->verifySSL = $verify;
        return $this;
    }

    /**
     * Configura rate limiting
     * 
     * @param int $requests Número máximo de requisições
     * @param int $seconds Período em segundos
     * @return self
     */
    public function withRateLimit(int $requests, int $seconds): self
    {
        $this->rateLimit = max(1, $requests);
        $this->ratePeriod = max(1, $seconds);
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
        $this->validateUrl($url);
        $this->validateMethod($method);
        $this->applyRateLimit();
        
        $this->lastUrl = $url;
        
        try {
            $curl = $this->createCurlHandle($url, $method, $data);
            
            $response = curl_exec($curl);
            $this->statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            
            curl_close($curl);
            
            if ($response === false) {
                $this->error = $curlError ?: 'Falha desconhecida na requisição cURL';
                $this->response = '';
                throw new \RuntimeException($this->error);
            }
            
            $this->response = (string) $response;
            $this->error = $curlError;
            
        } catch (\Exception $e) {
            $this->response = '';
            $this->statusCode = 0;
            $this->error = $e->getMessage();
        }
        
        return $this;
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
    private function hasFileUploads(array $data): bool
    {
        foreach ($data as $value) {
            if ($value instanceof \CURLFile || $value instanceof \CURLStringFile) {
                return true;
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
     * Aplica rate limiting se configurado
     * 
     * @return void
     */
    private function applyRateLimit(): void
    {
        if ($this->rateLimit === null || $this->ratePeriod === null) {
            return;
        }
        
        $currentTime = time();
        $elapsedTime = $currentTime - $this->periodStart;
        
        // Reset do período se necessário
        if ($elapsedTime >= $this->ratePeriod) {
            $this->requestCount = 0;
            $this->periodStart = $currentTime;
        }
        
        // Verifica se atingiu o limite
        if ($this->requestCount >= $this->rateLimit) {
            $waitTime = $this->ratePeriod - $elapsedTime;
            if ($waitTime > 0) {
                sleep($waitTime);
                $this->requestCount = 0;
                $this->periodStart = time();
            }
        }
        
        $this->requestCount++;
    }

    /**
     * Valida a URL
     * 
     * @param string $url
     * @throws \InvalidArgumentException
     */
    private function validateUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('URL inválida: ' . $url);
        }
    }

    /**
     * Valida o método HTTP
     * 
     * @param string $method
     * @throws \InvalidArgumentException
     */
    private function validateMethod(string $method): void
    {
        $validMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
        
        if (!in_array(strtoupper($method), $validMethods)) {
            throw new \InvalidArgumentException('Método HTTP inválido: ' . $method);
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
            'requestCount' => $this->requestCount
        ];
    }
}