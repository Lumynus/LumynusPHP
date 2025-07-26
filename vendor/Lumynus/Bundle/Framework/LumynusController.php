<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\Luma;
use Lumynus\Bundle\Framework\Sessions;
use Lumynus\Bundle\Framework\Response;

abstract class LumynusController
{

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
}
