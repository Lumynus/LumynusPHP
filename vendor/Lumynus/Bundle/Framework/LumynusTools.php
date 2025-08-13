<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\Sessions;
use Lumynus\Bundle\Framework\Response;
use Lumynus\Bundle\Framework\Sanitizantes;
use Lumynus\Bundle\Framework\Converts;
use Lumynus\Bundle\Framework\LumaHTTP;
use Lumynus\Bundle\Framework\HttpClient;
use Lumynus\Bundle\Framework\Brasil;
use Lumynus\Bundle\Framework\Requirements;
use Lumynus\Bundle\Framework\Regex;
use Lumynus\Bundle\Framework\Encryption;

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

    protected function response(): Response
    {
        return new Response();
    }

    protected function sanitizer(): Sanitizantes
    {
        return new Sanitizantes();
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

    public function __debugInfo(): array
    {
        return [
            'Lumynus' => "Framework PHP"
        ];
    }
}
