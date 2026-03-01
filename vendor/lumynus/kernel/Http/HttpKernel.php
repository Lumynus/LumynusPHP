<?php

declare(strict_types=1);

namespace Lumynus\Http;

use Lumynus\Http\HttpException;
use Lumynus\Templates\Errors;
use Lumynus\Framework\Route;
use Lumynus\Framework\Config;
use Lumynus\Framework\DataBase;
use Lumynus\Framework\Logs;
use Lumynus\Framework\LumynusContainer;
use Lumynus\Framework\LumynusUtilities;

class HttpKernel
{

    use Errors;

    public function handle(
        ?array $server = null,
        ?array $get = null,
        ?array $post = null,
        ?array $files = null,
        ?array $headers = null,
        ?string $rawContent = null
    ): void {
        try {
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
}
