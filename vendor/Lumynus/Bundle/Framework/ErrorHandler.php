<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\ErrorTemplate;
use Lumynus\Bundle\Framework\LumaClasses;

class ErrorHandler extends LumaClasses
{
    /**
     * Registra manipuladores de erros e exceções para o framework Lumynus.
     * Define como os erros e exceções serão tratados, renderizando um template de erro.
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
            print('Aplicattion is not in debug mode. Error handler is not registered. Configure your config.ini file to enable debug mode.');
            return;
        } else {



            $callback($fileConfigured);

            set_exception_handler(function ($e) use ($configFile) {
                http_response_code(500);

                if (isset($configFile['app']['debug']) && ($configFile['app']['debug'] === 'true' || $configFile['app']['debug'] == '1')) {
                    echo self::error()->render([
                        'error_message' => $e->getMessage(),
                        'error_type' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'error_code' => 500
                    ]);
                    exit;
                } else {
                    echo self::error()->getViewPath('500', 'php');
                }

                exit;
            });

            set_error_handler(function ($severity, $message, $file, $line) use ($configFile) {
                http_response_code(500);
                if (isset($configFile['app']['debug']) && ($configFile['app']['debug'] === 'true' || $configFile['app']['debug'] == '1')) {
                    echo self::error()->render([
                        'error_message' => $message,
                        'error' => $severity,
                        'file' => $file,
                        'line' => $line,
                        'error_code' => 500
                    ]);
                    exit;
                } else {
                    echo self::error()->getViewPath('500', 'php');
                }
                exit;
            });

            register_shutdown_function(function () use ($configFile) {
                $error = error_get_last();

                if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                    http_response_code(500);
                    if (isset($configFile['app']['debug']) && ($configFile['app']['debug'] === 'true' || $configFile['app']['debug'] == '1')) {
                        echo self::error()->render([
                            'error_message' => $error['message'],
                            'error' => 'Fatal error caught',
                            'file' => $error['file'],
                            'line' => $error['line'],
                            'error_code' => 500
                        ]);
                        exit;
                    } else {
                        echo self::error()->getViewPath('500', 'php');
                    }
                    exit;
                }
            });
        }
    }

    /**
     * Retorna uma instância de ErrorTemplate.
     * Método auxiliar para facilitar a criação de templates de erro.
     *
     * @return ErrorTemplate
     */
    private static function error(): ErrorTemplate
    {
        return new ErrorTemplate();
    }

    /**
     * Método para obter a instância da classe Luma.
     * @return Luma Retorna uma nova instância da classe Luma.
     */
    public function __debugInfo():array
    {
        return [
            'Lumynus' => "Framework PHP"
        ];
    }
}
