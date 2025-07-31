<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\Luma;
use Lumynus\Bundle\Framework\Sessions;
use Lumynus\Bundle\Framework\Response;
use Lumynus\Bundle\Framework\Sanitizantes;
use Lumynus\Bundle\Framework\Converts;
use Lumynus\Bundle\Framework\LumaClasses;

abstract class LumynusMiddleware extends LumaClasses
{
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
