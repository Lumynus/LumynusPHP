<?php

namespace Lumynus\Console\Contracts;

interface Output
{
    /**
     * Sai do comando com sucesso.
     * @param string $message Mensagem de sucesso a ser exibida.
     * @return self
     */
    public function success(string $message): self;

    /**
     * Outputs an informational message.
     * @param string $message Mensagem de informação a ser exibida.
     * @param string $colorANSI Cor ANSI para a mensagem.
     * @return self
     */
    public function info(string $message, string $colorANSI): self;

    /**
     * Outputs an error message.
     * @param string $message Mensagem de erro a ser exibida.
     * @return self
     */
    public function error(string $message): self;
    
    /**
     * Método para fazer uma pergunta ao usuário.
     *
     * @param string      $message   Mensagem a ser exibida ao usuário
     * @param string|null $colorANSI Cor da mensagem (padrão azul) . Exemplo: "\033[94m" para azul.
     * @return string
     */
    public function question(string $message, string $colorANSI = "\033[37m"): string;
}
