<?php

declare(strict_types=1);

namespace Lumynus\Http;

use Lumynus\Http\HttpException;
use Lumynus\Templates\Errors;
use Lumynus\Framework\Route;
use Lumynus\Framework\Config;
use Lumynus\Framework\CORS;
use Lumynus\Framework\DataBase;
use Lumynus\Framework\Logs;
use Lumynus\Framework\LumynusContainer;
use Lumynus\Framework\LumynusUtilities;

class HttpKernel
{

    use Errors;

    /**
     * Inicia o kernel HTTP
     *
     * @param array|null $server
     * @param array|null $get
     * @param array|null $post
     * @param array|null $files
     * @param array|null $headers
     * @param string|null $rawContent
     * @return void
     */
    public function handle(
        ?array $server = null,
        ?array $get = null,
        ?array $post = null,
        ?array $files = null,
        ?array $headers = null,
        ?string $rawContent = null
    ): void {
        try {

            $this->setup();

            $response = Route::start(
                $server,
                $get,
                $post,
                $files,
                $headers,
                $rawContent
            );
            if ($response !== null) {
                $response->dispatch();
            }
        } catch (HttpException $e) {
            $this->throwError($e->getMessage(), $e->getStatusCode(), $e->getFormat());
        } finally {
            $this->terminate();
        }
    }

    /**
     * Encerra a requisição
     */
    private function terminate(): void
    {

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if (Config::getApplicationConfig()['logs']['autoClear'] === true) {
            Logs::clear();
        }

        if (Config::getApplicationConfig()['persistentRuntime']['is'] === true) {
            Route::clear();
            gc_collect_cycles();
        }

        DataBase::closeAll();

        LumynusContainer::clear();
    }

    /**
     * Caso o desenvolvedor queira inicicar as requisições com uma configuração específica,
     * ele pode criar um arquivo setup.php na raiz do projeto.
     * Este método será chamado automaticamente pelo kernel no método handle(), antes do roteamento.
     * O arquivo deve conter a função setup() que recebe o kernel como parâmetro.
     * @return void
     */
    private function setup(): void
    {

        $corsCfg = Config::getApplicationConfig()['security']['cors'] ?? [];
        if (!empty($corsCfg['enabled'])) {
            $cors = new CORS();
            $cors->setOrigins($corsCfg['allowedOrigins'] ?? []);
            $cors->setHeaders($corsCfg['allowedHeaders'] ?? []);
            $cors->setMethods($corsCfg['allowedMethods'] ?? ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH']);
            $cors->setTimeCache((int)($corsCfg['timeCache'] ?? 86400));
            $cors->handle();
        }

        $raiz = Config::pathProject();
        if (file_exists($raiz . '/setup.php')) {
            require_once $raiz . '/setup.php';
        }
    }
}
