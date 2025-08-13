<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\Luma;
use Lumynus\Bundle\Framework\Sessions;
use Lumynus\Bundle\Framework\Response;
use Lumynus\Bundle\Framework\Sanitizantes;
use Lumynus\Bundle\Framework\Converts;
use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Bundle\Framework\LumaHTTP;
use Lumynus\Bundle\Framework\HttpClient;
use Lumynus\Bundle\Framework\CORS;
use Lumynus\Bundle\Framework\Requirements;
use Lumynus\Bundle\Framework\Regex;
use Lumynus\Bundle\Framework\Encryption;

abstract class LumynusMiddleware extends LumaClasses
{

    use Requirements;

    /**
     * Método para renderizar uma view com dados.
     *
     * @param string $view Nome da view a ser renderizada.
     * @param array $data Dados a serem passados para a view.
     * @return string Retorna o conteúdo renderizado da view.
     */
    protected function renderView(string $view, array $data = []): string
    {
        return Luma::render($view, $data);
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
     * Método para obter a instância da classe Response.
     * @return Response Retorna uma nova instância da classe Response.
     */
    protected function response(): Response
    {
        return new Response();
    }

    /**
     * Método para obter a instância da classe Sanitizantes.
     * @return Sanitizantes Retorna uma nova instância da classe Sanitizantes.
     */
    protected function sanitizer(): Sanitizantes
    {
        return new Sanitizantes();
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
     * Método para obter a instância da classe CORS.
     * @return CORS Retorna uma nova instância da classe CORS.
     */
    protected function regex(): Regex
    {
        return new Regex();
    }

    /**
     * Método para obter a instância da classe Encryption
     * @return Encryption Retorna uma nova instância da classe Encryption
     */
    protected function encrypt() : Encryption {
        return new Encryption();
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
