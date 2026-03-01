<?php

declare(strict_types=1);

namespace Lumynus\Framework;

use Lumynus\Framework\Sessions;
use Lumynus\Http\HttpResponse;
use Lumynus\Framework\Sanitizer;
use Lumynus\Framework\Converts;
use Lumynus\Framework\LumaHTTP;
use Lumynus\Framework\HttpClient;
use Lumynus\Framework\Brasil;
use Lumynus\Framework\Requirements;
use Lumynus\Framework\Regex;
use Lumynus\Framework\Encryption;
use Lumynus\Framework\Validate;
use Lumynus\Framework\Logs;
use Lumynus\Framework\Cookies;
use Lumynus\Framework\QueueManager;
use Lumynus\Framework\CSRF;
use Lumynus\Framework\Memory;
use Lumynus\Framework\CORS;
use Lumynus\Framework\Resolver;
use Lumynus\Framework\LumynusContainer;

/**
 * Trait com métodos utilitários comuns do framework Lumynus.
 * Pode ser usada em qualquer classe que estenda LumaClasses ou outras classes do framework.
 */
trait LumynusUtilities
{
    use Requirements;

    /**
     * Método para obter a instância da classe Sessions.
     * @param array $userOptions Opções personalizadas para a sessão.
     * @return Sessions Retorna uma nova instância da classe Sessions.
     * @throws \Exception Se a sessão não puder ser iniciada.
     */
    public function sessions(array $userOptions = []): Sessions
    {
        $key = 'sessions_' . md5(json_encode($userOptions));
        return $this->makeInstance(Sessions::class, [$userOptions], $key);
    }

    /**
     * Método para obter a instância da classe Cookie.
     * @return Cookies Retorna uma nova instância da classe Cookie.
     */
    public function cookies(): Cookies
    {
        return $this->makeInstance(Cookies::class);
    }

    /**
     * Método para obter a instância da classe Validate.
     * @return Validate Retorna uma nova instância da classe Validate.
     */
    public function validate(): Validate
    {
        return $this->makeInstance(Validate::class);
    }

    /**
     * Método para obter a instância da classe Logs.
     * @return Logs Retorna uma nova instância da classe Logs.
     */
    public function logs(): Logs
    {
        return $this->makeInstance(Logs::class);
    }

    /**
     * Método para obter a instância da classe Response.
     * @return HttpResponse Retorna uma nova instância da classe Response.
     */
    public function response(): HttpResponse
    {
        return $this->makeInstance(HttpResponse::class);
    }

    /**
     * Método para obter a instância da classe Sanitizer.
     * @return Sanitizer Retorna uma nova instância da classe Sanitizer.
     */
    public function sanitizer(): Sanitizer
    {
        return $this->makeInstance(Sanitizer::class);
    }

    /**
     * Método para obter a instância da classe Converts.
     * @return Converts Retorna uma nova instância da classe Converts.
     */
    public function converter(): Converts
    {
        return $this->makeInstance(Converts::class);
    }

    /**
     * Método para obter a instância da classe Brasil.
     * @return Brasil Retorna uma nova instância da classe Brasil.
     */
    public function brasil(): Brasil
    {
        return $this->makeInstance(Brasil::class);
    }

    /**
     * Método para obter a instância da classe LumaHTTP.
     * @return LumaHTTP Retorna uma nova instância da classe LumaHTTP.
     */
    public function lumaHTTP(): LumaHTTP
    {
        return $this->makeInstance(LumaHTTP::class);
    }

    /**
     * Método para obter a instância da classe HttpClient.
     * @return HttpClient Retorna uma nova instância da classe HttpClient.
     */
    public function httpClient(): HttpClient
    {
        return $this->makeInstance(HttpClient::class);
    }

    /**
     * Método para obter a instância da classe Regex.
     * @return Regex Retorna uma nova instância da classe Regex.
     */
    public function regex(): Regex
    {
        return $this->makeInstance(Regex::class);
    }

    /**
     * Método para obter a instância da classe Encryption
     * @return Encryption Retorna uma nova instância da classe Encryption
     */
    public function encryption(): Encryption
    {
        return $this->makeInstance(Encryption::class);
    }

    /**
     * Método para obter a instância da classe QueueManager
     * @return QueueManager Retorna uma nova instância da classe QueueManager
     */
    public function queue(): QueueManager
    {
        return $this->makeInstance(QueueManager::class);
    }

    /**
     * Método para obter a instância da classe CSRF
     * @return CSRF Retorna uma nova instância da classe CSRF
     */
    public function csrf(): CSRF
    {
        return $this->makeInstance(CSRF::class);
    }

    /**
     * Método para obter a instância da classe Memory
     * @return Memory Retorna uma nova instância da classe Memory
     */
    public function memory(): Memory
    {
        return $this->makeInstance(Memory::class);
    }

    /**
     * Método para obter a instância da classe CORS.
     * @return CORS Retorna uma nova instância da classe CORS.
     */
    public function cors(): CORS
    {
        return $this->makeInstance(CORS::class);
    }

    /**
     * Método para obter a instância da classe Resolver
     * @return Resolver Retorna uma nova instância da classe Resolver
     */
    public function resolver(): Resolver
    {
        return $this->makeInstance(Resolver::class);
    }

    /**
     * Método genérico para criar instâncias de classes utilitárias.
     * @param string $class O nome da classe a ser instanciada.
     * @param array $options Opções para o construtor da classe.
     * @param string|null $key Chave opcional para armazenar a instância.
     * @return object Retorna uma nova instância da classe especificada.
     */
    private function makeInstance(string $class, array $options = [], ?string $key = null)
    {
        return LumynusContainer::resolve($class, $options, $key);
    }

    /**
     * Método para chamar funções em molde estático
     * @return self
     */
    public static function static(mixed ...$args): static
    {
        return new static(...$args);
    }

    public function __debugInfo(): array
    {
        return [
            'Lumynus' => "Framework PHP"
        ];
    }
}
