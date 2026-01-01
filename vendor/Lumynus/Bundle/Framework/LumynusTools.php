<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\Sessions;
use Lumynus\Http\Response;
use Lumynus\Bundle\Framework\Sanitizer;
use Lumynus\Bundle\Framework\Converts;
use Lumynus\Bundle\Framework\LumaHTTP;
use Lumynus\Bundle\Framework\HttpClient;
use Lumynus\Bundle\Framework\Brasil;
use Lumynus\Bundle\Framework\Requirements;
use Lumynus\Bundle\Framework\Regex;
use Lumynus\Bundle\Framework\Encryption;
use Lumynus\Bundle\Framework\Validate;
use Lumynus\Bundle\Framework\Logs;
use Lumynus\Bundle\Framework\Cookies;
use Lumynus\Bundle\Framework\QueueManager;
use Lumynus\Bundle\Framework\CSRF;
use Lumynus\Bundle\Framework\Memory;
use Lumynus\Bundle\Framework\CORS;
use Lumynus\Bundle\Framework\Resolver;

/**
 * Trait com métodos utilitários comuns do framework Lumynus.
 * Pode ser usada em qualquer classe que estenda LumaClasses ou outras classes do framework.
 */
trait LumynusTools
{
    use Requirements;


    protected function sessions(bool $autostart = true): Sessions
    {
        return new Sessions($autostart);
    }

    protected function cookies(): Cookies
    {
        return new Cookies();
    }

    protected function validate(): Validate
    {
        return new Validate();
    }

    protected function logs(): Logs
    {
        return new Logs;
    }

    protected function response(): Response
    {
        return new Response();
    }

    protected function sanitizer(): Sanitizer
    {
        return new Sanitizer();
    }

    protected function converter(): Converts
    {
        return new Converts();
    }

    protected function brasil(): Brasil
    {
        return new Brasil();
    }

    protected function lumaHTTP(): LumaHTTP
    {
        return new LumaHTTP();
    }

    protected function httpClient(): HttpClient
    {
        return new HttpClient();
    }

    protected function regex(): Regex
    {
        return new Regex();
    }

    protected function encrypt(): Encryption
    {
        return new Encryption();
    }

    protected function queue(): QueueManager
    {
        return new QueueManager;
    }

    protected function csrf(): CSRF
    {
        return new CSRF;
    }

    protected function memory() : Memory {
        return new Memory;
    }

    protected function cors(): CORS
    {
        return new CORS();
    }

    protected function resolver() : Resolver {
        return new Resolver;
    }

    protected static function static(): static
    {
        return new static();
    }

    public function __debugInfo(): array
    {
        return [
            'Lumynus' => "Framework PHP"
        ];
    }
}
