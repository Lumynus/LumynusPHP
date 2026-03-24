<?php

namespace Lumynus\Framework;

use Lumynus\Http\Contracts\Response;
use Lumynus\Framework\LumynusContainer;

trait Helpers
{

    /**
     * Método para debugar dados.
     * 
     * @param mixed $data 
     * @return Response 
     */
    public function dd(...$data): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];

        echo '<pre style="background:#111;color:#0f0;padding:15px;border-radius:8px;">';

        echo "Line {$trace['file']} : {$trace['line']}\n";

        foreach ($data as $item) {
            var_dump($item);
        }

        echo "\nTime: " . round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]), 4) . "s";
        echo "\nMemory: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB";

        echo "</pre>";

        exit;
    }

    /**
     * Método para obter a instância da classe ContainerProxy
     * @return ContainerProxy Retorna uma nova instância da classe ContainerProxy
     */
    public function container(): ContainerProxy
    {
        // O proxy também fica sob controle do container
        return LumynusContainer::resolve(ContainerProxy::class);
    }
}
