<?php

declare(strict_types=1);

namespace Lumynus\Templates;

trait Errors
{
    /**
     * Envia uma resposta de erro baseada no Accept da request.
     *
     * @param string|array $message Mensagem de erro
     * @param int $code Código HTTP
     */
    public static function throwError(string|array|null $message = null, int $code = 500, ?string $forceType = null): void
    {
        http_response_code($code);

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '*/*';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        $message = $message ?? 'Occurred an error in application';

        // Se $forceType foi especificado, usa ele
        if ($forceType !== null) {
            $type = strtolower($forceType);
        } else {
            // Detecta pelo Content-Type ou Accept
            if (str_contains($accept, 'application/json') || str_contains($contentType, 'application/json')) {
                $type = 'json';
            } elseif (str_contains($accept, 'application/xml') || str_contains($contentType, 'application/xml') || str_contains($accept, 'text/xml')) {
                $type = 'xml';
            } elseif (str_contains($accept, 'application/javascript') || str_contains($accept, 'text/javascript') || str_contains($contentType, 'application/javascript')) {
                $type = 'javascript';
            } elseif (str_contains($accept, 'text/plain') || str_contains($contentType, 'text/plain')) {
                $type = 'plain';
            } else {
                $type = 'html';
            }
        }

        // Chama a resposta certa
        switch ($type) {
            case 'json':
                self::respondJson($message, $code);
                break;
            case 'xml':
                self::respondXml($message, $code);
                break;
            case 'javascript':
                self::respondJavaScript($message, $code);
                break;
            case 'plain':
                self::respondPlain($message, $code);
                break;
            case 'html':
            default:
                self::respondHtml($message, $code);
                break;
        }

        exit;
    }



    // ===== Métodos privados por tipo =====

    private static function respondJson(string|array $message, int $code): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => $message,
            'code' => $code
        ], JSON_PRETTY_PRINT);
    }

    private  static function respondXml(string|array $message, int $code): void
    {
        header('Content-Type: application/xml');
        $msg = is_array($message) ? implode('; ', $message) : $message;
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<error>\n";
        echo "  <code>$code</code>\n";
        echo "  <message>" . htmlspecialchars($msg) . "</message>\n";
        echo "</error>";
    }

    private static function respondJavaScript(string|array $message, int $code): void
    {
        header('Content-Type: application/javascript');
        $msg = json_encode(is_array($message) ? $message : ['error' => $message, 'code' => $code], JSON_PRETTY_PRINT);
        echo "console.error(" . $msg . ");";
    }

    private static function respondPlain(string|array $message, int $code): void
    {
        header('Content-Type: text/plain');
        echo "Erro $code: " . (is_array($message) ? print_r($message, true) : $message);
    }

    private static function respondHtml(string|array $message, int $code): void
    {
        header('Content-Type: text/html');
        $msg = htmlspecialchars(is_array($message) ? implode('<br>', $message) : $message);
        echo "<!DOCTYPE html>
    <html lang=\"pt-BR\">
    <head>
        <meta charset=\"UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <title>Erro $code</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                background-color: #f8f9fa;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .error-card {
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                padding: 40px;
                max-width: 500px;
                width: 100%;
                text-align: left;
                position: relative;
            }
            
            .error-header {
                display: flex;
                align-items: center;
                margin-bottom: 24px;
            }
            
            .error-code {
                background-color: #722f37;
                color: white;
                font-size: 24px;
                font-weight: 700;
                padding: 12px 20px;
                border-radius: 8px;
                margin-right: 16px;
                min-width: 80px;
                text-align: center;
            }
            
            .error-title {
                color: #2c3e50;
                font-size: 20px;
                font-weight: 600;
                margin: 0;
            }
            
            .error-message {
                color: #5a6c7d;
                font-size: 16px;
                line-height: 1.6;
                margin-top: 16px;
            }
            
            .framework-signature {
                position: absolute;
                bottom: 16px;
                right: 20px;
                color: #95a5a6;
                font-size: 11px;
                font-weight: 300;
                letter-spacing: 0.5px;
            }
            
            @media (max-width: 480px) {
                .error-card {
                    padding: 24px;
                    margin: 16px;
                }
                
                .error-header {
                    flex-direction: column;
                    align-items: flex-start;
                }
                
                .error-code {
                    margin-right: 0;
                    margin-bottom: 12px;
                }
            }
        </style>
    </head>
    <body>
        <div class=\"error-card\">
            <div class=\"error-header\">
                <div class=\"error-code\">$code</div>
                <h1 class=\"error-title\">Oops! Something went wrong</h1>
            </div>
            <div class=\"error-message\">$msg</div>
            <div class=\"framework-signature\">Lumynus</div>
        </div>
    </body>
    </html>";
    }
}
