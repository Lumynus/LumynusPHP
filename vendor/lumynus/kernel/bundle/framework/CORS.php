<?php

declare(strict_types=1);
namespace Lumynus\Bundle\Framework;
use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Templates\Errors;

final class CORS extends LumaClasses
{

    use Errors;

    /**
     * Origens permitidas para CORS.
     * Estas são as origens que o servidor aceitará para requisições CORS.
     * Você pode adicionar ou remover origens conforme necessário.
     */
    private $allowedOrigins = [];

    /**
     * Métodos permitidos para CORS.
     * Estes são os métodos HTTP que o servidor aceitará de requisições CORS.
     * Você pode adicionar ou remover métodos conforme necessário.
     */
    private $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];

    /*     * Cabeçalhos permitidos para CORS.
     * Estes são os cabeçalhos que o servidor aceitará de requisições CORS.
     * Você pode adicionar ou remover cabeçalhos conforme necessário.
     */
    private $allowedHeaders = [
        'Content-Type',
        'Authorization',
        'Accept',
        'Origin',
        'X-Requested-With',
        'X-CSRF-Token',
        'X-Auth-Token',
        'X-Access-Token',
        'Cache-Control',
        'Pragma',
    ];

    /**
     * Tempo de cache para as respostas CORS.
     * Este é o tempo em segundos que o navegador deve armazenar em cache as respostas CORS.
     * O padrão é 86400 segundos (1 dia).
     */
    private $maxAge = 86400; // 1 dia

    /**
     * Construtor da classe CORS.
     * Define as origens permitidas, métodos, cabeçalhos e tempo de cache.
     *
     * @param array $origins Array de origens permitidas.
     */
    public function setOrigins(string| array $origin)
    {
        if (is_string($origin)) {
            $this->allowedOrigins = [$origin];
        } elseif (is_array($origin)) {
            $this->allowedOrigins = $origin;
        } else {
            throw new \InvalidArgumentException('Origin must be a string or an array of strings.');
        }
    }

    /**
     * Define os métodos permitidos para CORS.
     * @param array $methods Array de métodos HTTP permitidos.
     * @throws \InvalidArgumentException Se os métodos não forem válidos.
     */
    public function setMethods(array $methods)
    {
        $this->allowedMethods = array_intersect($methods, $this->allowedMethods);
    }

    /**
     * Define os cabeçalhos permitidos para CORS.
     * @param array $headers Array de cabeçalhos permitidos.
     * @throws \InvalidArgumentException Se os cabeçalhos não forem válidos.
     */
    public function setHeaders(array $headers)
    {
        $this->allowedHeaders = array_intersect($headers, $this->allowedHeaders);
    }

    /**
     * Define o tempo de cache para as respostas CORS.
     * @param int $seconds Tempo em segundos.
     * @throws \InvalidArgumentException Se o tempo não for um inteiro positivo.
     */
    public function setTimeCache(int $seconds)
    {
        if ($seconds > 0) {
            $this->maxAge = $seconds;
        } else {
            throw new \InvalidArgumentException('Cache time must be a positive integer.');
        }
    }

    /**
     * Manipula a requisição CORS.
     * Verifica se a origem é permitida e define os cabeçalhos CORS.
     * Se a origem não for permitida, retorna um erro 403.
     *
     * @return void
     */
    public function handle()
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (!in_array($origin, $this->allowedOrigins)) {
            self::throwError('CORS origin not allowed', 403);
            return;
        }

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: ' . implode(', ', $this->allowedMethods));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->allowedHeaders));
        header('Access-Control-Max-Age: ' . $this->maxAge);

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }

    }
}
