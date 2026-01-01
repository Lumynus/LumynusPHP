<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Bundle\Framework\Config;


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
            'lumynus_version' => LumaClasses::VERSION,
            'node_version' => phpversion(),
            'environment' => (Config::modeProduction() ? 'Production' : 'Development'),
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
        // Limpa output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        $mergedData = array_merge($this->defaultData, $data);
        $errorTemplate = $this->replacePlaceholders($this->template, $mergedData);

        // Adiciona JavaScript de limpeza no início do template
        $cleanupScript = '
    <script>
        // Executa imediatamente para limpar qualquer conteúdo anterior
        (function() {
            if (document.body) {
                document.body.innerHTML = "";
                document.body.className = "";
            }
            
            // Remove qualquer CSS ou JS anterior
            const existingStyles = document.querySelectorAll("style:not([data-error-template])");
            existingStyles.forEach(style => style.remove());
            
            const existingScripts = document.querySelectorAll("script:not([data-error-template])");
            existingScripts.forEach(script => {
                if (!script.src.includes("error-template")) {
                    script.remove();
                }
            });
        })();
    </script>';

        // Adiciona o script no início do template
        $cleanTemplate = str_replace('<head>', '<head>' . $cleanupScript, $errorTemplate);

        return $cleanTemplate;
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
                --bg-primary: #ffffff;
                --bg-secondary: #f8fafc;
                --bg-tertiary: #e2e8f0;
                --text-primary: #1e293b;
                --text-secondary: #475569;
                --text-muted: #64748b;
                --border-color: #cbd5e1;
                --error-color: #dc2626;
                --accent-color: #3b82f6;
                --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                --shadow-card: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
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
                --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
                --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
                --shadow-card: 0 1px 3px 0 rgba(0, 0, 0, 0.3), 0 1px 2px 0 rgba(0, 0, 0, 0.2);
            }

            body {
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
                color: var(--text-primary);
                line-height: 1.6;
                font-size: 14px;
                padding: 20px;
                min-height: 100vh;
                transition: all 0.3s ease;
            }

            .error-container {
                max-width: 1200px;
                margin: 0 auto;
                display: flex;
                flex-direction: column;
                gap: 24px;
            }

            .error-header {
                background: var(--bg-secondary);
                border: 2px solid var(--border-color);
                border-radius: 16px;
                padding: 40px;
                box-shadow: var(--shadow-lg);
                text-align: center;
                position: relative;
                backdrop-filter: blur(10px);
                overflow: hidden;
            }

            [data-theme="light"] .error-header {
                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                border: 2px solid #e2e8f0;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            }

            .theme-toggle {
                position: absolute;
                top: 20px;
                right: 20px;
                display: flex;
                align-items: center;
                gap: 8px;
                background: var(--bg-primary);
                border: 2px solid var(--border-color);
                color: var(--text-secondary);
                padding: 10px 18px;
                border-radius: 10px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                transition: all 0.3s ease;
                box-shadow: var(--shadow-card);
            }

            .theme-toggle:hover {
                background: var(--bg-tertiary);
                transform: translateY(-2px);
                box-shadow: var(--shadow);
            }

            [data-theme="light"] .theme-toggle {
                background: #ffffff;
                border: 2px solid #cbd5e1;
                color: #475569;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            [data-theme="light"] .theme-toggle:hover {
                background: #f1f5f9;
                border-color: #94a3b8;
            }

            .framework-name {
                font-size: 14px;
                font-weight: 600;
                color: var(--text-muted);
                text-transform: uppercase;
                letter-spacing: 0.15em;
                margin-bottom: 16px;
            }

            [data-theme="light"] .framework-name {
                color: #64748b;
                font-weight: 700;
            }

            .error-title {
                font-size: 48px;
                font-weight: 800;
                color: var(--error-color);
                margin-bottom: 16px;
                line-height: 1.1;
                text-shadow: 0 2px 4px rgba(220, 38, 38, 0.1);
                word-wrap: break-word;
                overflow-wrap: break-word;
                hyphens: auto;
            }

            [data-theme="light"] .error-title {
                color: #dc2626;
                text-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);
            }

            .error-message {
                font-size: 18px;
                color: var(--text-secondary);
                margin-bottom: 24px;
                line-height: 1.5;
                max-width: 600px;
                margin-left: auto;
                margin-right: auto;
                font-weight: 500;
            }

            [data-theme="light"] .error-message {
                color: #475569;
                font-weight: 600;
            }

            .error-location {
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 14px;
                color: var(--text-primary);
                background: var(--bg-primary);
                padding: 14px 24px;
                border-radius: 10px;
                border: 2px solid var(--border-color);
                display: inline-block;
                font-weight: 600;
                box-shadow: var(--shadow-card);
                word-wrap: break-word;
                overflow-wrap: break-word;
                max-width: 100%;
            }

            [data-theme="light"] .error-location {
                background: #ffffff;
                color: #1e293b;
                border: 2px solid #cbd5e1;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            .error-cards {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 24px;
            }

            .card {
                background: var(--bg-secondary);
                border: 2px solid var(--border-color);
                border-radius: 16px;
                overflow: hidden;
                box-shadow: var(--shadow-card);
                transition: all 0.3s ease;
                backdrop-filter: blur(10px);
            }

            .card:hover {
                box-shadow: var(--shadow-lg);
                transform: translateY(-4px);
                border-color: var(--accent-color);
            }

            [data-theme="light"] .card {
                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                border: 2px solid #e2e8f0;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            [data-theme="light"] .card:hover {
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                border-color: #3b82f6;
            }

            .card-header {
                background: var(--bg-tertiary);
                padding: 18px 24px;
                border-bottom: 2px solid var(--border-color);
                font-weight: 700;
                font-size: 14px;
                color: var(--text-primary);
                text-transform: uppercase;
                letter-spacing: 0.1em;
            }

            [data-theme="light"] .card-header {
                background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
                color: #1e293b;
                border-bottom: 2px solid #cbd5e1;
            }

            .card-content {
                padding: 0;
            }

            .card-item {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 18px 24px;
                border-bottom: 1px solid var(--border-color);
                transition: background-color 0.2s ease;
            }

            .card-item:hover {
                background: var(--bg-primary);
            }

            [data-theme="light"] .card-item:hover {
                background: #f8fafc;
            }

            .card-item:last-child {
                border-bottom: none;
            }

            .card-label {
                color: var(--text-secondary);
                font-weight: 600;
                font-size: 14px;
                min-width: 80px;
                flex-shrink: 0;
            }

            [data-theme="light"] .card-label {
                color: #475569;
                font-weight: 700;
            }

            .card-value {
                color: var(--text-primary);
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 13px;
                text-align: right;
                word-break: break-word;
                margin-left: 16px;
                line-height: 1.4;
                font-weight: 600;
                background: var(--bg-tertiary);
                padding: 6px 12px;
                border-radius: 6px;
            }

            [data-theme="light"] .card-value {
                color: #1e293b;
                background: #f1f5f9;
                font-weight: 700;
            }

            .stack-trace-card {
                grid-column: 1 / -1;
                margin-top: 8px;
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
                border-radius: 12px;
                margin: 20px;
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            [data-theme="light"] .stack-trace {
                background: #f1f5f9;
                color: #1e293b;
                border: 1px solid #cbd5e1;
            }

            .stack-trace-empty {
                padding: 40px;
                text-align: center;
                color: var(--text-muted);
                font-style: italic;
                font-weight: 500;
            }

            @media (max-width: 768px) {
                body {
                    padding: 16px;
                }

                .error-header {
                    padding: 32px 24px;
                }

                .theme-toggle {
                    position: static;
                    margin-bottom: 24px;
                    align-self: flex-end;
                }

                .error-title {
                     font-size: 28px;
                    word-break: break-word;
                }

                .error-message {
                    font-size: 16px;
                }

                .error-cards {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }

                .card-item {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 12px;
                    padding: 18px 20px;
                }

                .card-value {
                    text-align: left;
                    margin-left: 0;
                    width: 100%;
                }

                .stack-trace {
                    margin: 16px;
                    padding: 20px;
                    max-height: 400px;
                }
            }

            /* Custom scrollbar */
            ::-webkit-scrollbar {
                width: 10px;
                height: 10px;
            }

            ::-webkit-scrollbar-track {
                background: var(--bg-tertiary);
                border-radius: 6px;
            }

            ::-webkit-scrollbar-thumb {
                background: var(--border-color);
                border-radius: 6px;
                border: 2px solid var(--bg-tertiary);
            }

            ::-webkit-scrollbar-thumb:hover {
                background: var(--text-muted);
            }

            /* Loading animation */
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .card {
                animation: fadeInUp 0.6s ease-out;
            }

            .card:nth-child(1) { animation-delay: 0.1s; }
            .card:nth-child(2) { animation-delay: 0.2s; }
            .card:nth-child(3) { animation-delay: 0.3s; }
            .card:nth-child(4) { animation-delay: 0.4s; }

            .error-header {
                animation: fadeInUp 0.6s ease-out;
            }
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
        </div>

        <script>
        function toggleTheme() {
            const html = document.documentElement;
            const themeIcon = document.getElementById("theme-icon");
            const themeText = document.getElementById("theme-text");
            
            const currentTheme = html.getAttribute("data-theme");
            
            if (currentTheme === "dark") {
                html.setAttribute("data-theme", "light");
                themeIcon.textContent = "🌙";
                themeText.textContent = "Modo Escuro";
                localStorage.setItem("theme", "light");
            } else {
                html.setAttribute("data-theme", "dark");
                themeIcon.textContent = "☀️";
                themeText.textContent = "Modo Claro";
                localStorage.setItem("theme", "dark");
            }
        }

        function cleanBodyKeepErrorContainerAndStyle() {
            const errorContainer = document.querySelector(".error-container");
            if (!errorContainer) return;

            const bodyChildren = Array.from(document.body.childNodes);

            for (const node of bodyChildren) {
                if (node !== errorContainer && !(node.nodeType === 1 && node.tagName.toLowerCase() === "style")) {
                    node.remove();
                }
            }
        }

        // Load saved theme
        window.addEventListener("DOMContentLoaded", () => {
            cleanBodyKeepErrorContainerAndStyle();
            
            const savedTheme = localStorage.getItem("theme");
            const html = document.documentElement;
            const themeIcon = document.getElementById("theme-icon");
            const themeText = document.getElementById("theme-text");
            
            if (savedTheme === "light") {
                html.setAttribute("data-theme", "light");
                themeIcon.textContent = "🌙";
                themeText.textContent = "Modo Escuro";
            } else {
                html.setAttribute("data-theme", "dark");
                themeIcon.textContent = "☀️";
                themeText.textContent = "Modo Claro";
            }
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
