<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\Luma;
use Lumynus\Bundle\Framework\Sessions;
use Lumynus\Http\Response;
use Lumynus\Bundle\Framework\Sanitizer;
use Lumynus\Bundle\Framework\Converts;
use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Bundle\Framework\LumaHTTP;
use Lumynus\Bundle\Framework\HttpClient;
use Lumynus\Bundle\Framework\CORS;
use Lumynus\Bundle\Framework\Requirements;
use Lumynus\Bundle\Framework\Regex;
use Lumynus\Bundle\Framework\Encryption;
use Lumynus\Bundle\Framework\Validate;
use Lumynus\Bundle\Framework\Logs;
use Lumynus\Bundle\Framework\Cookies;
use Lumynus\Bundle\Framework\QueueManager;
use Lumynus\Bundle\Framework\CSRF;
use Lumynus\Bundle\Framework\Memory;
use Lumynus\Bundle\Framework\Resolver;

abstract class LumynusMiddleware extends LumaClasses
{

    use Requirements;

    /**
     * Método para renderizar uma view com dados.
     *
     * @param string $view Nome da view a ser renderizada.
     * @param array $data Dados a serem passados para a view.
     * @param bool $regenerateCSRF Informa se deseja regernar o CSRF na view
     * @return string Retorna o conteúdo renderizado da view.
     */
    protected function renderView(string $view, array $data = [], bool $regenerateCSRF = true): string
    {
        return Luma::render($view, $data, $regenerateCSRF);
    }


    /**
     * Método para obter a instância da classe Sessions.
     * @param bool $autostart Indica se as configurações de segurança e sessão devem ser iniciadas automaticamente.
     * @return Sessions Retorna uma nova instância da classe Sessions.
     * @throws \Exception Se a sessão não puder ser iniciada.
     */
    protected function sessions(bool $autostart = true): Sessions
    {
        return new Sessions($autostart);
    }

    /**
     * Método para obter a instância da classe Cookie.
     * @return Cookie Retorna uma nova instância da classe Cookie.
     */
    protected function cookies(): Cookies
    {
        return new Cookies();
    }

    /**
     * Método para obter a instância da classe Response.
     * @return Response Retorna uma nova instância da classe Response.
     */
    protected function response(): Response
    {
        return new Response();
    }

    /**
     * Método para obter a instância da classe Sanitizer.
     * @return Sanitizer Retorna uma nova instância da classe Sanitizer.
     */
    protected function sanitizer(): Sanitizer
    {
        return new Sanitizer();
    }

    /**
     * Método para obter a instância da classe Converts.
     * @return Converts Retorna uma nova instância da classe Converts.
     */
    protected function converter(): Converts
    {
        return new Converts();
    }

    /**
     * Método para obter a instância da classe Validate.
     * @return Validate Retorna uma nova instância da classe Validate.
     */
    protected function validate(): Validate
    {
        return new Validate();
    }

    /**
     * Método para obter a instância da classe Logs.
     * @return Logs Retorna uma nova instância da classe Logs.
     */
    protected function logs(): Logs
    {
        return new Logs;
    }

    /**
     * Método para obter a instância da classe LumaHTTP.
     * @return LumaHTTP Retorna uma nova instância da classe LumaHTTP.
     */
    protected function lumaHTTP(): LumaHTTP
    {
        return new LumaHTTP();
    }

    /**
     * Método para obter a instância da classe HttpClient.
     * @return HttpClient Retorna uma nova instância da classe HttpClient.
     */
    protected function httpClient(): HttpClient
    {
        return new HttpClient();
    }

    /**
     * Método para obter a instância da classe CORS.
     * @return CORS Retorna uma nova instância da classe CORS.
     */
    protected function cors(): CORS
    {
        return new CORS();
    }

    /**
     * Método para obter a instância da classe Regex.
     * @return Regex Retorna uma nova instância da classe Regex.
     */
    protected function regex(): Regex
    {
        return new Regex();
    }

    /**
     * Método para obter a instância da classe Encryption
     * @return Encryption Retorna uma nova instância da classe Encryption
     */
    protected function encrypt(): Encryption
    {
        return new Encryption();
    }

    /**
     * Método para obter a instância da classe QueueManager
     * @return QueueManager Retorna uma nova instância da classe QueueManager
     */
    protected function queue(): QueueManager
    {
        return new QueueManager;
    }

    /**
     * Método para obter a instância da classe CSRF
     * @return CSRF Retorna uma nova instância da classe CSRF
     */
    protected function csrf(): CSRF
    {
        return new CSRF;
    }

    /**
     * Método para obter a instância da classe Memory
     * @return Memory Retorna uma nova instância da classe Memory
     */
    protected function memory(): Memory
    {
        return new Memory;
    }

    /**
     * Método para obter a instância da classe Resolver
     * @return Resolver Retorna uma nova instância da classe Resolver
     */
    protected function resolver() : Resolver {
        return new Resolver;
    }

    /**
     * Método para chamar funções em molde estático
     * @return self
     */
    protected static function static(): static
    {
        return new static();
    }

    /**
     * Método para obter a instância da classe Luma.
     * @return Luma Retorna uma nova instância da classe Luma.
     */
    public function __debugInfo(): array
    {
        return [
            'Lumynus' => "Framework PHP"
        ];
    }
}
