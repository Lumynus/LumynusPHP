<?php

declare(strict_types=1);

namespace App\Commands;

use Lumynus\Console\Contracts\Terminal;
use Lumynus\Console\Contracts\Output;
use Lumynus\Bundle\Framework\LumynusCommands;

class TesteCommand extends LumynusCommands
{

    //1 - Forma de usar com contratos
    public function handle(Terminal $terminal, Output $res)
    {

        // Métodos para obter dados digitados

        // $terminal->method();
        // $terminal->command();
        $dados = $terminal->getAll();

        // Métodos para responder

        // $res->info('Colorido azul automaticamente', 'passra cor em formato ANSI');
        $res->success('Sucesso para: ' . $dados[0]);
        // $res->error('Colorido vermelho automaticamente');

    }

    //2 - Forma simples
    public function handle2($dados)
    {
        $this->output()->success('Sucesso para: ' . $dados[0]);
    }
}
