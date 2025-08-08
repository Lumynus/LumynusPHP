<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;


class ErrorTemplate extends LumaClasses
{
    private string $template;
    private array $defaultData;

    /**
     * Cria uma nova instância do template de erro.
     * Define os dados padrão e carrega o template HTML.
     */
    public function __construct()
    {
        date_default_timezone_set('America/Sao_Paulo');
        $this->template = $this->getTemplate();
        $this->defaultData = [
            'framework_name' => 'Lumynus',
            'error_title' => 'Ops! Algo deu errado',
            'error_message' => 'Erro não especificado',
            'error_type' => 'Error',
            'error_code' => 500,
            'file' => 'Não especificado',
            'line' => 'N/A',
            'method' => 'N/A',
            'timestamp' => date('Y-m-d H:i:s'),
            'stack_trace' => 'Stack trace não disponível',
            'http_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'url' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'lumynus_version' => '1.2.3',
            'node_version' => phpversion(),
            'environment' => 'development',
            'memory_usage' => $this->formatBytes(memory_get_usage(true))
        ];
    }

    /**
     * Renderiza o template de erro com os dados fornecidos.
     *
     * @param array $data Dados adicionais para substituir os placeholders no template.
     * @return string HTML renderizado com os dados fornecidos.
     */
    public function render(array $data = []): string
    {
        $mergedData = array_merge($this->defaultData, $data);

        return $this->replacePlaceholders($this->template, $mergedData);
    }

    /**
     * Renderiza o template de erro com os dados de uma exceção.
     *
     * @param \Throwable $exception A exceção capturada.
     * @param array $additionalData Dados adicionais para substituir os placeholders no template.
     * @return string HTML renderizado com os dados da exceção.
     */
    public function renderWithException(\Throwable $exception, array $additionalData = []): string
    {
        $errorData = [
            'error_message' => $exception->getMessage(),
            'error_type' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => (string) $exception->getLine(),
            'stack_trace' => $exception->getTraceAsString(),
            'method' => $this->extractMethod($exception->getTrace())
        ];

        $mergedData = array_merge($errorData, $additionalData);

        return $this->render($mergedData);
    }

    /**
     * Substitui os placeholders no template pelos valores fornecidos.
     *
     * @param string $template O template HTML com placeholders.
     * @param array $data Dados para substituir os placeholders.
     * @return string O template com os placeholders substituídos pelos valores correspondentes.
     */
    private function replacePlaceholders(string $template, array $data): string
    {
        $placeholders = [];
        $values = [];

        foreach ($data as $key => $value) {
            $placeholders[] = '{{' . $key . '}}';
            $values[] = $this->escapeHtml((string) $value);
        }

        return str_replace($placeholders, $values, $template);
    }

    /**
     * Escapa caracteres especiais em uma string para uso em HTML.
     *
     * @param string $value O valor a ser escapado.
     * @return string O valor escapado.
     */
    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Extrai o método da pilha de rastreamento.
     *
     * @param array $trace A pilha de rastreamento da exceção.
     * @return string O método formatado como "Classe::Método()".
     */
    private function extractMethod(array $trace): string
    {
        if (empty($trace)) {
            return 'N/A';
        }

        $firstTrace = $trace[0];

        if (isset($firstTrace['class']) && isset($firstTrace['function'])) {
            return $firstTrace['class'] . '::' . $firstTrace['function'] . '()';
        }

        return $firstTrace['function'] ?? 'N/A';
    }

    /**
     * Formata um valor em bytes para uma string legível (B, KB, MB, GB).
     *
     * @param int $bytes O valor em bytes.
     * @return string O valor formatado como uma string legível.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor(log($bytes, 1024));

        return sprintf('%.1f %s', $bytes / (1024 ** $factor), $units[$factor]);
    }

    /**
     * Retorna o caminho do template de erro.
     *
     * @param string $name Nome do template.
     * @param string $extension Extensão do arquivo (padrão é 'php').
     * @return string Caminho completo do template.
     */
    public  static function getViewPath(string $name, string $extension = 'php'): string
    {
        $path = Config::pathProject()
            . Config::getAplicationConfig()['pagesErrors'][$name] . '.' . $extension;

        if (file_exists($path)) {
            return file_get_contents($path);
        }

        return '⚠️ File not found: ' . $name . '.' . $extension;
    }

    /**
     * Retorna o template HTML do erro.
     *
     * @return string O template HTML com placeholders para os dados do erro.
     */
    private function getTemplate(): string
    {
        return '<!DOCTYPE html>
    <html lang="pt-BR" data-theme="dark">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{error_type}} - {{framework_name}}</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            :root {
                --bg-primary: #f8fafc;
                --bg-secondary: #ffffff;
                --bg-tertiary: #f1f5f9;
                --text-primary: #0f172a;
                --text-secondary: #64748b;
                --text-muted: #94a3b8;
                --border-color: #e2e8f0;
                --error-color: #dc2626;
                --accent-color: #3b82f6;
                --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
                --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            }

            [data-theme="dark"] {
                --bg-primary: #0f172a;
                --bg-secondary: #1e293b;
                --bg-tertiary: #334155;
                --text-primary: #f1f5f9;
                --text-secondary: #cbd5e1;
                --text-muted: #94a3b8;
                --border-color: #334155;
                --error-color: #ef4444;
                --accent-color: #60a5fa;
                --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3), 0 1px 2px 0 rgba(0, 0, 0, 0.2);
                --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.2);
            }

            body {
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background-color: var(--bg-primary);
                color: var(--text-primary);
                line-height: 1.6;
                font-size: 14px;
                padding: 20px;
                min-height: 100vh;
                transition: all 0.2s ease;
            }

            .error-container {
                max-width: 1200px;
                margin: 0 auto;
                display: flex;
                flex-direction: column;
                gap: 20px;
            }

            .error-header {
                background: var(--bg-secondary);
                border: 1px solid var(--border-color);
                border-radius: 12px;
                padding: 32px;
                box-shadow: var(--shadow);
                text-align: center;
                position: relative;
            }

            .theme-toggle {
                position: absolute;
                top: 20px;
                right: 20px;
                display: flex;
                align-items: center;
                gap: 8px;
                background: var(--bg-tertiary);
                border: 1px solid var(--border-color);
                color: var(--text-secondary);
                padding: 8px 16px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                transition: all 0.2s ease;
            }

            .theme-toggle:hover {
                background: var(--border-color);
                transform: translateY(-1px);
            }

            .framework-name {
                font-size: 14px;
                font-weight: 500;
                color: var(--text-muted);
                text-transform: uppercase;
                letter-spacing: 0.1em;
                margin-bottom: 16px;
            }

            .error-title {
                font-size: 42px;
                font-weight: 700;
                color: var(--error-color);
                margin-bottom: 16px;
                line-height: 1.1;
            }

            .error-message {
                font-size: 18px;
                color: var(--text-secondary);
                margin-bottom: 24px;
                line-height: 1.5;
                max-width: 600px;
                margin-left: auto;
                margin-right: auto;
            }

            .error-location {
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 14px;
                color: var(--text-muted);
                background: var(--bg-tertiary);
                padding: 12px 20px;
                border-radius: 8px;
                border: 1px solid var(--border-color);
                display: inline-block;
                font-weight: 500;
            }

            .error-cards {
                display: grid;
                grid-template-columns: 1fr 2fr;
                gap: 20px;
            }

            .error-sidebar {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }

            .card {
                background: var(--bg-secondary);
                border: 1px solid var(--border-color);
                border-radius: 12px;
                overflow: hidden;
                box-shadow: var(--shadow);
                transition: all 0.2s ease;
            }

            .card:hover {
                box-shadow: var(--shadow-lg);
                transform: translateY(-2px);
            }

            .card-header {
                background: var(--bg-tertiary);
                padding: 16px 24px;
                border-bottom: 1px solid var(--border-color);
                font-weight: 600;
                font-size: 14px;
                color: var(--text-secondary);
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }

            .card-content {
                padding: 0;
            }

            .card-item {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 16px 24px;
                border-bottom: 1px solid var(--border-color);
            }

            .card-item:last-child {
                border-bottom: none;
            }

            .card-label {
                color: var(--text-secondary);
                font-weight: 500;
                font-size: 14px;
                min-width: 80px;
                flex-shrink: 0;
            }

            .card-value {
                color: var(--text-primary);
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 13px;
                text-align: right;
                word-break: break-word;
                margin-left: 16px;
                line-height: 1.4;
                font-weight: 500;
            }

            .stack-trace-card {
                grid-column: 1 / -1;
            }

            .stack-trace {
                padding: 24px;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 13px;
                line-height: 1.6;
                color: var(--text-primary);
                white-space: pre-wrap;
                word-break: break-word;
                max-height: 500px;
                overflow-y: auto;
                background: var(--bg-tertiary);
                border-radius: 8px;
                margin: 20px;
            }

            .stack-trace-empty {
                padding: 40px;
                text-align: center;
                color: var(--text-muted);
                font-style: italic;
            }

            @media (max-width: 768px) {
                body {
                    padding: 16px;
                }

                .error-header {
                    padding: 24px 20px;
                }

                .theme-toggle {
                    position: static;
                    margin-bottom: 20px;
                    align-self: flex-end;
                }

                .error-title {
                    font-size: 32px;
                }

                .error-message {
                    font-size: 16px;
                }

                .error-cards {
                    grid-template-columns: 1fr;
                    gap: 16px;
                }

                .card-item {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 8px;
                    padding: 16px 20px;
                }

                .card-value {
                    text-align: left;
                    margin-left: 0;
                }

                .stack-trace {
                    margin: 16px;
                    padding: 16px;
                    max-height: 400px;
                }
            }

            /* Custom scrollbar */
            ::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }

            ::-webkit-scrollbar-track {
                background: var(--bg-tertiary);
                border-radius: 4px;
            }

            ::-webkit-scrollbar-thumb {
                background: var(--border-color);
                border-radius: 4px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: var(--text-muted);
            }

            /* Loading animation */
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .card {
                animation: fadeIn 0.5s ease-out;
            }

            .card:nth-child(1) { animation-delay: 0.1s; }
            .card:nth-child(2) { animation-delay: 0.2s; }
            .card:nth-child(3) { animation-delay: 0.3s; }
            .card:nth-child(4) { animation-delay: 0.4s; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-header">
                <button class="theme-toggle" onclick="toggleTheme()">
                    <span id="theme-icon">🌙</span>
                    <span id="theme-text">Dark Mode</span>
                </button>
                
                <div class="framework-name">{{framework_name}}</div>
                <div class="error-title">{{error_type}} {{error_code}}</div>
                <div class="error-message">{{error_message}}</div>
                <div class="error-location">{{file}}:{{line}}</div>
            </div>

            <div class="error-cards">
                <div class="error-sidebar">
                    <div class="card">
                        <div class="card-header">Request</div>
                        <div class="card-content">
                            <div class="card-item">
                                <span class="card-label">Method</span>
                                <span class="card-value">{{http_method}}</span>
                            </div>
                            <div class="card-item">
                                <span class="card-label">URL</span>
                                <span class="card-value">{{url}}</span>
                            </div>
                            <div class="card-item">
                                <span class="card-label">IP</span>
                                <span class="card-value">{{ip}}</span>
                            </div>
                            <div class="card-item">
                                <span class="card-label">Time</span>
                                <span class="card-value">{{timestamp}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">Environment</div>
                        <div class="card-content">
                            <div class="card-item">
                                <span class="card-label">{{framework_name}}</span>
                                <span class="card-value">{{lumynus_version}}</span>
                            </div>
                            <div class="card-item">
                                <span class="card-label">PHP</span>
                                <span class="card-value">{{node_version}}</span>
                            </div>
                            <div class="card-item">
                                <span class="card-label">Environment</span>
                                <span class="card-value">{{environment}}</span>
                            </div>
                            <div class="card-item">
                                <span class="card-label">Memory</span>
                                <span class="card-value">{{memory_usage}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">Context</div>
                        <div class="card-content">
                            <div class="card-item">
                                <span class="card-label">Method</span>
                                <span class="card-value">{{method}}</span>
                            </div>
                            <div class="card-item">
                                <span class="card-label">User Agent</span>
                                <span class="card-value">{{user_agent}}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Stack Trace</div>
                    <div class="stack-trace">{{stack_trace}}</div>
                </div>
            </div>
        </div>

        <script>
    function cleanBodyKeepErrorContainerAndStyle() {
        const errorContainer = document.querySelector(".error-container");
        if (!errorContainer) return;

        const bodyChildren = Array.from(document.body.childNodes);

        for (const node of bodyChildren) {
            // Se não for a error-container nem uma tag <style>, remove
            if (node !== errorContainer && !(node.nodeType === 1 && node.tagName.toLowerCase() === "style")) {
                node.remove();
            }
        }
    }

    window.addEventListener("DOMContentLoaded", () => {
        cleanBodyKeepErrorContainerAndStyle();
    });
</script>


    </body>
    </html>';
    }

    /**
     * Método para obter a instância da classe Luma.
     * @return Luma Retorna uma nova instância da classe Luma.
     */
    public function __debugInfo(): array
    {
        return [
            'Lumynus' => "Framework PHP"
        ];
    }
}
