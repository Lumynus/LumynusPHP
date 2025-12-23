<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

abstract class LumynusCommands extends LumaClasses
{
    use \Lumynus\Bundle\Framework\LumynusTools;

    public function __construct()
    {
        if (PHP_SAPI !== 'cli') {
            throw new \RuntimeException(
                'Commands can only be executed via CLI.'
            );
        }
    }

    private function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }


    private const GREEN = "\033[32m";
    private const RED   = "\033[31m";
    private const RESET = "\033[0m";

    private bool $responded = false;

    /**
     * Método para responder ao comando.
     *
     * @return self
     */
    protected function respond(): self
    {
        return $this;
    }

    /**
     * Método para indicar sucesso no comando.
     *
     * @param string $message
     * @return self
     */
    protected function success(string $message): self
    {
        if ($this->responded) {
            return $this;
        }

        $this->responded = true;

        echo self::GREEN . $message . self::RESET . PHP_EOL;
        return $this;
    }

    /**
     * Método para indicar informação no comando.
     *
     * @param string $message
     * @return self
     */
    protected function info(string $message, string $colorANSI = "\033[94m"): self
    {
        if ($this->responded) {
            return $this;
        }

        $this->responded = true;

        echo $colorANSI . $message . self::RESET . PHP_EOL;
        return $this;
    }

    /**
     * Método para indicar erro no comando.
     *
     * @param string      $message
     * @param string|null $logMessage
     * @return self
     */
    protected function error(string $message, ?string $logMessage = null): self
    {
        if ($this->responded) {
            return $this;
        }

        $this->responded = true;
        $this->logs()->register('Error in command: ', ($logMessage ?? $message));
        echo self::RED . $message . self::RESET . PHP_EOL;
        return $this;
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
     * Executa um comando do sistema operacional.
     *
     * @param string $command Comando a ser executado.
     * @return array Resultado da execução.
     * @throws \RuntimeException Se o processo não puder ser iniciado.
     */
    protected function runProcess(string $command): array
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        // Ajuste para Windows
        if ($this->isWindows()) {
            $command = 'cmd /C ' . $command;
        }

        $process = proc_open($command, $descriptorSpec, $pipes);

        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start process.');
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'stdout'   => trim($stdout),
            'stderr'   => trim($stderr),
            'exitCode' => $exitCode,
        ];
    }
}
