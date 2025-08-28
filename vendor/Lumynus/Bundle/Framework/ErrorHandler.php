<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\ErrorTemplate;
use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Templates\Errors;

class ErrorHandler extends LumaClasses
{
    use Errors;

    /**
     * Registra manipuladores de erros e exceções para o framework Lumynus.
     *
     * @return void
     */
    public static function register(callable $callback): void
    {
        $fileConfigured = true;
        $configFile = Config::getINI();
        if (!$configFile || !isset($configFile['app']['debug'])) {
            $fileConfigured = false;
            $callback($fileConfigured);
            print('Application is not in debug mode. Error handler is not registered. Configure your config.ini file to enable debug mode.');
            return;
        }

        $callback($fileConfigured);

        /**
         * Função auxiliar para decidir o formato de resposta
         */
        $renderError = function (array $data, bool $debug, int $statusCode = 500) {
            http_response_code($statusCode);

            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            // Decide se deve responder em JSON
            $wantsJson = (
                stripos($accept, 'application/json') !== false ||
                stripos($contentType, 'application/json') !== false
            );

            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                if ($debug) {
                    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'error' => 'Internal Server Error',
                        'code'  => $statusCode
                    ]);
                }
            } else {
                // fallback para HTML
                if ($debug) {
                    echo self::error()->render($data);
                } else {
                    self::throwError('Internal Server Error', $statusCode, 'html');
                }
            }
            exit;
        };

        set_exception_handler(function ($e) use ($configFile, $renderError) {
            $debug = ($configFile['app']['debug'] === 'true' || $configFile['app']['debug'] == '1');

            $data = [
                'error_message' => $e->getMessage(),
                'error_type'    => get_class($e),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'error_code'    => 500
            ];

            $renderError($data, $debug, 500);
        });

        set_error_handler(function ($severity, $message, $file, $line) use ($configFile, $renderError) {
            $debug = ($configFile['app']['debug'] === 'true' || $configFile['app']['debug'] == '1');

            $data = [
                'error_message' => $message,
                'error'         => $severity,
                'file'          => $file,
                'line'          => $line,
                'error_code'    => 500
            ];

            $renderError($data, $debug, 500);
        });

        register_shutdown_function(function () use ($configFile, $renderError) {
            $error = error_get_last();
            $debug = ($configFile['app']['debug'] === 'true' || $configFile['app']['debug'] == '1');

            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $data = [
                    'error_message' => $error['message'],
                    'error'         => 'Fatal error caught',
                    'file'          => $error['file'],
                    'line'          => $error['line'],
                    'error_code'    => 500
                ];

                $renderError($data, $debug, 500);
            }
        });
    }

    /**
     * Retorna uma instância de ErrorTemplate.
     *
     * @return ErrorTemplate
     */
    private static function error(): ErrorTemplate
    {
        return new ErrorTemplate();
    }

    /**
     * Informações de debug da classe
     */
    public function __debugInfo(): array
    {
        return [
            'Lumynus' => "Framework PHP"
        ];
    }
}
