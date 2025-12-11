<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\Config;
use Lumynus\Bundle\Framework\CSRF;

/**
 * Exceções específicas para o motor de templates
 */
class ViewNotFoundException extends \Exception {}
class TemplateCompilationException extends \Exception {}
class TemplateSecurityException extends \Exception {}

/**
 * A classe Luma atua como um motor de templates simples e seguro.
 * Ela é responsável por renderizar, compilar e gerenciar o cache de views.
 *
 * @package Lumynus\Bundle\Framework
 */
class Luma extends LumaClasses
{
    private static array $viewStack = [];
    private static array $shareData = [];
    private static array $patterns = [];
    private static array $compilationCount = [];
    private static array $queuedHeaderAssets = [];

    // Limites de segurança
    private const MAX_TEMPLATE_SIZE = 1024 * 1024; // 1MB
    private const MAX_DIRECTIVES = 1000;
    private const MAX_NESTING_LEVEL = 10;
    private const RATE_LIMIT_PER_MINUTE = 10;

    /**
     * Inicializa os padrões de regex seguros para o compilador.
     */
    public static function bootCompiler(): void
    {
        self::$patterns = [
            // Loops abrem e fecham separadamente
            'for_open'      => '/@for\s*\((.*?)\)/',
            'for_close'     => '/@endfor/',
            'foreach_open'  => '/@foreach\s*\((.*?)\)/',
            'foreach_close' => '/@endforeach/',
            'while_open'    => '/@while\s*\((.*?)\)/',
            'while_close'   => '/@endwhile/',

            // If / Else
            'if'         => '/@if\s*\((.*?)\)/',
            'elseif'     => '/@elseif\s*\((.*?)\)/',
            'else'       => '/@else/',
            'endif'      => '/@endif/',
            'selected' => '/@selected\s*\(\s*(.*?)\s*,\s*(.*?)\s*\)/',
            'checked' => '/@checked\s*\(\s*(.*?)\s*,\s*(.*?)\s*\)/',

            // Exist / Not
            'exist'      => '/@exist\s*\((.*?)\)/',
            'endexist'   => '/@endexist/',
            'not'        => '/@not\s*\((.*?)\)/',
            'endnot'     => '/@endnot/',

            // Switch / Case
            'switch'     => '/@switch\s*\((.*?)\)/',
            'endswitch'  => '/@endswitch/',
            'case'       => '/@case\s*\((.*?)\)/',
            'default'    => '/@default/',
            'break'      => '/@break/',

            // Includes e variáveis
            'include'    => '/@include\s*\(\s*([\'"])?([\$\w\/_\-\.]+)\1?\s*(?:,\s*(\w+))?\s*\)/',
            'raw_var'    => '/\{\{\s*raw\s+(\$[a-zA-Z_][a-zA-Z0-9_\[\]\'"]*)\s*\}\}/',
            'typed_var'  => '/\{\{\s*(int|float|string|bool)\s+(\$[a-zA-Z_][a-zA-Z0-9_\[\]\'"]*)\s*(raw)?\s*\}\}/',
            'simple_var' => '/\{\{\s*(\$[a-zA-Z_][a-zA-Z0-9_\[\]\'"]*)\s*\}\}/',
            'use'        => '/@use\s*\(\s*([\'"])([\w\/_\-\.]+)\1\s*,\s*([\'"])([\w\-_]+)\3\s*\)/',

            // Assets
            'js'  => '/@js\s*\(\s*(?:([\'"])([a-zA-Z0-9\/_\-\.]+)\1|(\$[a-zA-Z_][a-zA-Z0-9_]*))\s*\)/',
            'css' => '/@css\s*\(\s*(?:([\'"])([a-zA-Z0-9\/_\-\.]+)\1|(\$[a-zA-Z_][a-zA-Z0-9_]*))\s*\)/',

            //Alterando headers de forma segura
            'header:css' => '/@header:css\s*\(\s*(?:([\'"])([a-zA-Z0-9\/_\-\.]+)\1|(\$[a-zA-Z_][a-zA-Z0-9_]*))\s*\)/',
            'header:js'  => '/@header:js\s*\(\s*(?:([\'"])([a-zA-Z0-9\/_\-\.]+)\1|(\$[a-zA-Z_][a-zA-Z0-9_]*))\s*\)/',

        ];
    }


    /**
     * Renderiza uma view com os dados fornecidos de forma segura.
     */
    public static function render(string $view, array $data = [], bool $regenerateCSRF = true): string
    {
        $startTime = microtime(true);

        // Verifica se esta é a view principal (Raiz)
        $isRootView = empty(self::$viewStack);

        try {
            if ($isRootView) {
                // Limpa a fila de assets anteriores se for uma nova requisição principal
                self::$queuedHeaderAssets = [];
                self::checkRateLimit();
            }

            self::validateView($view);

            if (in_array($view, self::$viewStack)) {
                throw new TemplateCompilationException("Rendering loop detected: '{$view}'");
            }

            if (count(self::$viewStack) >= self::MAX_NESTING_LEVEL) {
                throw new TemplateSecurityException("Maximum nesting level exceeded");
            }

            self::$viewStack[] = $view;
            self::$shareData = array_merge(self::$shareData, $data);

            $config = Config::getAplicationConfig();
            $basePath = Config::pathProject();

            $viewFile = self::resolveViewPath($basePath, $config, $view);
            $cacheFile = self::getCacheFilePath($basePath, $config, $view);

            if (!self::isCached($viewFile, $cacheFile)) {
                self::compileAndCache($viewFile, $cacheFile);
            }

            $output = self::getRenderedContent($cacheFile, self::$shareData, $regenerateCSRF);

            if ($isRootView && !empty(self::$queuedHeaderAssets)) {
                $injection = implode(PHP_EOL, self::$queuedHeaderAssets);

                if (preg_match('/<\/head>/i', $output)) {
                    $output = preg_replace(
                        '/<\/head>/i',
                        $injection . PHP_EOL . '</head>',
                        $output,
                        1
                    );
                } else {
                    $output = $injection . PHP_EOL . $output;
                }
            }

            self::logPerformance($view, $startTime);
            return $output;
        } finally {
            array_pop(self::$viewStack);
            if (empty(self::$viewStack)) {
                self::$shareData = [];
                self::$queuedHeaderAssets = [];
            }
        }
    }


    /**
     * Valida o nome da view contra caracteres perigosos.
     */
    private static function validateView(string $view): void
    {
        if (!preg_match('/^[a-zA-Z0-9\/_\-\.]+$/', $view)) {
            throw new TemplateSecurityException("The view name contains invalid characters: {$view}");
        }

        if (str_contains($view, '..')) {
            throw new TemplateSecurityException("Path traversal detected: {$view}");
        }
    }

    /**
     * Rate limiting para compilações.
     */
    private static function checkRateLimit(): void
    {
        $key = $_SERVER['REMOTE_ADDR'] ?? 'localhost';
        $now = time();

        if (!isset(self::$compilationCount[$key])) {
            self::$compilationCount[$key] = ['count' => 0, 'reset' => $now + 60];
        }

        if ($now > self::$compilationCount[$key]['reset']) {
            self::$compilationCount[$key] = ['count' => 0, 'reset' => $now + 60];
        }

        if (++self::$compilationCount[$key]['count'] > self::RATE_LIMIT_PER_MINUTE) {
            throw new TemplateSecurityException("Rate limit exceeded for template compilation.");
        }
    }

    /**
     * Resolve o caminho seguro da view.
     */
    private static function resolveViewPath(string $basePath, array $config, string $view): string
    {
        $viewsPath = realpath($basePath . $config['path']['views']);
        $viewFile = realpath($basePath . $config['path']['views'] . $view);

        if (!$viewFile || !str_starts_with($viewFile, $viewsPath)) {
            throw new ViewNotFoundException("View path not allowed: {$view}");
        }

        if (!is_file($viewFile)) {
            throw new ViewNotFoundException("View not found: {$view}");
        }

        return $viewFile;
    }

    /**
     * Gera caminho seguro para arquivo de cache.
     */
    private static function getCacheFilePath(string $basePath, array $config, string $view): string
    {
        $cacheDir = $basePath . $config['path']['cache'] . 'views';

        if (!is_dir($cacheDir)) {
            if (!mkdir($cacheDir, 0755, true)) {
                throw new TemplateCompilationException("Unable to create cache directory.");
            }
        }

        return $cacheDir . DIRECTORY_SEPARATOR . md5($view) . '.php';
    }

    /**
     * Compila e armazena template em cache com validações de segurança.
     */
    private static function compileAndCache(string $viewFile, string $cacheFile): void
    {
        $original = file_get_contents($viewFile);

        if (strlen($original) > self::MAX_TEMPLATE_SIZE) {
            throw new TemplateSecurityException("Template too large.");
        }

        if (substr_count($original, '@') > self::MAX_DIRECTIVES) {
            throw new TemplateSecurityException("Many directives in the template");
        }

        $compiled = self::compile($original);

        if (empty($compiled)) {
            throw new TemplateCompilationException("Template compilation failed.");
        }

        $tmpFile = $cacheFile . '.tmp';
        if (file_put_contents($tmpFile, $compiled, LOCK_EX) === false) {
            throw new TemplateCompilationException("Error writing cache");
        }

        if (!rename($tmpFile, $cacheFile)) {
            unlink($tmpFile);
            throw new TemplateCompilationException("Error clearing cache");
        }
    }

    /**
     * Compila template com validações de segurança aprimoradas.
     */
    private static function compile(string $template): string
    {
        if (empty(self::$patterns)) {
            self::bootCompiler();
        }

        try {

            // Ordem importante para evitar conflitos
            $template = self::compileControlStructures($template);
            $template = self::compileIncludes($template);
            $template = self::compileAssets($template);
            $template = self::compileHelpers($template);
            $template = self::compileEscape($template);
            $template = self::addCSRFToken($template);

            return $template;
        } catch (\Exception $e) {
            throw new TemplateCompilationException("Compilation error: " . $e->getMessage());
        }
    }

    /**
     * Compila estruturas de controle de forma segura.
     */
    private static function compileControlStructures(string $template): string
    {
        // Loops
        $template = preg_replace(self::$patterns['foreach_open'], '<?php foreach ($1): ?>', $template);
        $template = preg_replace(self::$patterns['foreach_close'], '<?php endforeach; ?>', $template);

        $template = preg_replace(self::$patterns['for_open'], '<?php for ($1): ?>', $template);
        $template = preg_replace(self::$patterns['for_close'], '<?php endfor; ?>', $template);

        $template = preg_replace(self::$patterns['while_open'], '<?php while ($1): ?>', $template);
        $template = preg_replace(self::$patterns['while_close'], '<?php endwhile; ?>', $template);

        // If / Else
        $template = preg_replace(self::$patterns['if'], '<?php if ($1): ?>', $template);
        $template = preg_replace(self::$patterns['elseif'], '<?php elseif ($1): ?>', $template);
        $template = preg_replace(self::$patterns['else'], '<?php else: ?>', $template);
        $template = preg_replace(self::$patterns['endif'], '<?php endif; ?>', $template);

        // Exist / Not
        $template = preg_replace(self::$patterns['exist'], '<?php if (!empty($1)): ?>', $template);
        $template = preg_replace(self::$patterns['endexist'], '<?php endif; ?>', $template);

        $template = preg_replace(self::$patterns['not'], '<?php if (empty($1)): ?>', $template);
        $template = preg_replace(self::$patterns['endnot'], '<?php endif; ?>', $template);

        // Switch
        $template = preg_replace(self::$patterns['switch'], '<?php switch ($1): ?>', $template);
        $template = preg_replace(self::$patterns['endswitch'], '<?php endswitch; ?>', $template);
        $template = preg_replace(self::$patterns['case'], '<?php case $1: ?>', $template);
        $template = preg_replace(self::$patterns['default'], '<?php default: ?>', $template);
        $template = preg_replace(self::$patterns['break'], '<?php break; ?>', $template);

        return $template;
    }

    /**
     * Compila includes de forma segura.
     */
    private static function compileIncludes(string $template): string
    {
        // 1. Processa @include normal
        $template = preg_replace_callback(self::$patterns['include'], function ($matches) {
            $view = $matches[2];
            $data = $matches[3] ?? '[]';

            if ($data === 'all') {
                $pairs = array_map(fn($key) => "'$key' => \$$key", array_keys(self::$shareData));
                $data = '[' . implode(', ', $pairs) . ']';
            }

            if (str_starts_with($view, '$')) {
                return "<?php echo \Lumynus\\Bundle\\Framework\\Luma::render($view, {$data}, false); ?>";
            }

            return "<?php echo \Lumynus\\Bundle\\Framework\\Luma::render('{$view}', {$data}, false); ?>";
        }, $template);


        return $template;
    }


    /**
     * Compila assets com validação de integridade e gerencia injeção no header.
     */
    private static function compileAssets(string $template): string
    {

        $template = preg_replace_callback(self::$patterns['js'], function ($m) {
            if (!empty($m[2])) {
                return self::getAssetHtml($m[2], 'js');
            } elseif (!empty($m[3])) {
                return "<?php echo \Lumynus\Bundle\Framework\Luma::getAssetHtml({$m[3]}, 'js'); ?>";
            }
        }, $template);

        $template = preg_replace_callback(self::$patterns['css'], function ($m) {
            if (!empty($m[2])) {
                return self::getAssetHtml($m[2], 'css');
            } elseif (!empty($m[3])) {
                return "<?php echo \Lumynus\Bundle\Framework\Luma::getAssetHtml({$m[3]}, 'css'); ?>";
            }
        }, $template);

        $template = preg_replace_callback(self::$patterns['header:css'], function ($m) {
            $param = !empty($m[2]) ? "'{$m[2]}'" : $m[3]; // String literal ou variável
            return "<?php \Lumynus\Bundle\Framework\Luma::registerHeaderAsset({$param}, 'css'); ?>";
        }, $template);


        $template = preg_replace_callback(self::$patterns['header:js'], function ($m) {
            $param = !empty($m[2]) ? "'{$m[2]}'" : $m[3];
            return "<?php \Lumynus\Bundle\Framework\Luma::registerHeaderAsset({$param}, 'js'); ?>";
        }, $template);


        return $template;
    }


    /**
     * Compila escape de variáveis com validação rigorosa.
     */
    private static function compileEscape(string $template): string
    {
        // Raw (sem escape) - já validado pela regex
        $template = preg_replace_callback(
            self::$patterns['raw_var'],
            fn($m) => '<?= ' . $m[1] . ' ?? "" ?>',
            $template
        );

        // Variáveis tipadas
        $template = preg_replace_callback(self::$patterns['typed_var'], function ($m) {
            $type = $m[1];
            $var = $m[2]; // Já validado
            $raw = isset($m[3]) && $m[3] === 'raw';

            $cast = "({$type})({$var} ?? '')";
            return $raw ? "<?= {$cast} ?>" : "<?= htmlspecialchars({$cast}) ?>";
        }, $template);

        // Variáveis simples com escape
        $template = preg_replace_callback(
            self::$patterns['simple_var'],
            fn($m) => '<?= htmlspecialchars((string)(' . $m[1] . ' ?? "")) ?>',
            $template
        );

        return $template;
    }

    /**
     * Compila helpers personalizados.
     */
    private static function compileHelpers(string $template): string
    {
        // @selected($var, $value)
        $template = preg_replace_callback(self::$patterns['selected'], function ($m) {
            $var = $m[1];
            $value = $m[2];
            return "<?= (htmlspecialchars(($var ?? ''), ENT_QUOTES, 'UTF-8') === htmlspecialchars($value, ENT_QUOTES, 'UTF-8')) ? 'selected' : '' ?>";
        }, $template);

        // @checked($var, $value)
        $template = preg_replace_callback(self::$patterns['checked'], function ($m) {
            $var = $m[1];
            $value = $m[2];
            return "<?= (htmlspecialchars(($var ?? ''), ENT_QUOTES, 'UTF-8') === htmlspecialchars($value, ENT_QUOTES, 'UTF-8')) ? 'checked' : '' ?>";
        }, $template);

        return $template;
    }

    /**
     * Adiciona token CSRF se habilitado.
     */
    private static function addCSRFToken(string $template): string
    {
        $config = Config::getAplicationConfig();

        if (($config['security']['csrf']['enabled'] ?? false) !== true) {
            return $template;
        }

        return preg_replace(
            '/<\/body>/i',
            '<input type="hidden" name="<?= $csrf_name ?? "csrf" ?>" value="<?= $csrf_token ?? "" ?>">' . PHP_EOL . '</body>',
            $template,
            1
        );
    }

    /**
     * Log de performance para templates lentos.
     */
    private static function logPerformance(string $view, float $startTime): void
    {
        $duration = microtime(true) - $startTime;
        if ($duration > 0.1) { // 100ms
            Logs::register("Error", "Slow template render: {$view} took " . round($duration * 1000) . "ms");
        }
    }

    // ... outros métodos permanecem iguais mas com validações adicionais

    private static function isCached(string $viewFile, string $cacheFile): bool
    {
        return file_exists($cacheFile)
            && filemtime($cacheFile) >= filemtime($viewFile)
            && filesize($cacheFile) > 0;
    }

    private static function getRenderedContent(string $cacheFile, array $data, bool $regenerateCSRF): string
    {
        $config = Config::getAplicationConfig();
        if (($config['security']['csrf']['enabled'] ?? false) === true) {
            $data['csrf_name'] = $config['security']['csrf']['nameToken'] ?? 'csrf';
            $data['csrf_token'] = $regenerateCSRF ? CSRF::generateToken() : CSRF::getToken();
        }

        return (function (string $file, array $data) {
            extract($data, EXTR_OVERWRITE);
            ob_start();
            include $file;
            return ob_get_clean() ?: '';
        })($cacheFile, $data);
    }


    /**
     * Registra um asset na fila para injeção posterior no head.
     * Deve ser público pois será chamado pelos arquivos de cache compilados.
     */
    public static function registerHeaderAsset(string $pathOrVar, string $type): void
    {
        // Gera o HTML do asset (link ou script)
        $html = self::getAssetHtml($pathOrVar, $type);

        if (!empty($html) && !in_array($html, self::$queuedHeaderAssets)) {
            self::$queuedHeaderAssets[] = $html;
        }
    }

    /**
     * Gera HTML para assets com validação de integridade.
     */
    public static function getAssetHtml(string $path, string $type): string
    {
        $config = Config::getAplicationConfig();
        $basePath = Config::pathProject();

        // 1. Prepara os nomes das pastas removendo barras extras
        $publicDirName = trim($config['path']['public'], '/'); // ex: "public"
        $resourcePath = trim($config['path'][$type], '/');     // ex: "resources/js"
        $fileName = $path . '.' . $type;

        $fullPath = $basePath . DIRECTORY_SEPARATOR . $publicDirName . DIRECTORY_SEPARATOR . $resourcePath . DIRECTORY_SEPARATOR . $fileName;

        $fullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);

        if (!file_exists($fullPath)) {
            Logs::register("Error", "Asset not found: {$fullPath}");
            return "";
        }

        $integrity = '';
        if (($config['security']['integrityAssets']['enabled'] ?? false) === true) {
            $content = file_get_contents($fullPath);
            $hash = base64_encode(hash('sha384', $content, true));
            $integrity = "integrity='sha384-{$hash}' crossorigin='anonymous'";
        }

        $baseUrl = dirname($_SERVER['SCRIPT_NAME']);

        $baseUrl = str_replace('\\', '/', $baseUrl);
        $baseUrl = rtrim($baseUrl, '/');
        $url = $baseUrl . '/' . $resourcePath . '/' . $fileName;

        if (($config['frontend']['versionAssets'] ?? false) === true) {
            $url .= "?v=" . filemtime($fullPath);
        }

        if ($type === 'js') {
            return "<script type='module' src=\"{$url}\" {$integrity}></script>";
        }

        return "<link rel=\"stylesheet\" href=\"{$url}\" {$integrity}>";
    }

    public function __debugInfo(): array
    {
        return ['Lumynus' => "Framework PHP"];
    }
}

Luma::bootCompiler();
