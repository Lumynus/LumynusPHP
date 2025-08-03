<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Bundle\Framework\Config;

class Luma extends LumaClasses
/** Luz */
{
    /**
     * Armazena as variáveis globais para o template.
     *
     * @var array
     */
    private static array $viewStack = [];

    /**
     * Renderiza uma view com os dados fornecidos.
     *
     * @param string $view Caminho relativo da view dentro de src/views/
     * @param array $data Dados a serem passados para a view
     * @return string Conteúdo renderizado da view
     */
    public static function render(string $view, array $data = []): string
    {

        if (in_array($view, self::$viewStack)) {
            throw new \Exception("Loop detected: the view '$view' is already being rendered.");
        }

        self::$viewStack[] = $view;

        $basePath = Config::pathProject();
        $viewFile = $basePath . Config::getAplicationConfig()['path']['views'] . $view;
        $cacheFile = $basePath . '/cache/views/' . $view . '.php';

        // Cria diretório de cache se não existir
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        // Compila se o cache não existir ou se a view original foi modificada
        if (!file_exists($cacheFile) || filemtime($cacheFile) < filemtime($viewFile)) {
            $original = file_get_contents($viewFile);
            $compiled = self::compile($original);
            file_put_contents($cacheFile, $compiled);
        }

        // Cria variáveis com base nos dados
        extract($data, EXTR_SKIP);

        // Executa e captura a saída
        ob_start();
        include $cacheFile;
        array_pop(self::$viewStack);
        return ob_get_clean();
    }

    /**
     * Renderiza uma view com os dados fornecidos e retorna o conteúdo.
     *
     * @param string $view Caminho relativo da view dentro de src/views/
     * @param array $data Dados a serem passados para a view
     * @return string Conteúdo renderizado da view
     */
    private static function compile(string $template): string
    {
        $context = self::escape($template);
        $context = self::ifelse($context);
        $context = self::for($context);
        $context = self::foreach($context);
        $context = self::while($context);
        $context = self::switch($context);
        $context = self::case($context);
        $context = self::break($context);
        $context = self::default($context);
        $context = self::include($context);
        $context = self::use($context);
        $context = self::js($context);
        $context = \Lumynus\Bundle\Framework\LumaJS::compile($context);

        return $context;
    }

    /**
     * Escapa variáveis no template usando a sintaxe {{ var }} ou {{ int var }}.
     *
     * @param string $template O template a ser processado
     * @return string O template com as variáveis escapadas
     */
    private static function escape(string $template): string
    {
        return preg_replace_callback('/\{\{\s*([^\s}]+)(?:\s+([^\s}]+))?(?:\s+([^\s}]+))?\s*\}\}/', function ($matches) {
            $first = $matches[1];
            $second = $matches[2] ?? null;
            $third = $matches[3] ?? null;

            if ($second === null) {
                // Só variável simples: {{ var }} - escapa por padrão
                return '<?= htmlspecialchars(' . $first . ') ?>';
            }

            // Se o primeiro é 'raw', sempre sem escape
            if ($first === 'raw') {
                return '<?= ' . $second . ' ?>';
            }

            // Verifica se o segundo parâmetro é "raw"
            if ($second === 'raw') {
                // Caso: {{ int raw variavel }}
                $variable = $third;
                $isRaw = true;
            } else {
                // Caso: {{ int variavel }} ou {{ int variavel raw }}
                $variable = $second;
                $isRaw = ($third === 'raw');
            }

            // Comando + variável: {{ int var }}, {{ string var }}, etc.
            switch ($first) {
                case 'int':
                    $output = '(int) ' . $variable;
                    break;
                case 'float':
                    $output = '(float) ' . $variable;
                    break;
                case 'string':
                    $output = '(string) ' . $variable;
                    break;
                case 'bool':
                    $output = '(bool) ' . $variable;
                    break;
                default:
                    // Comando desconhecido, retorna original
                    return $matches[0];
            }

            // Aplica escape se não for raw
            if ($isRaw) {
                return '<?= ' . $output . ' ?>';
            } else {
                return '<?= htmlspecialchars(' . $output . ') ?>';
            }
        }, $template);
    }


    /**
     * Processa o template substituindo as diretivas do Lux por código PHP.
     *
     * @param string $template O template a ser processado
     * @return string O template com as diretivas convertidas para PHP
     */
    private static function ifelse(string $template)
    {
        // Primeiro processa @elseif
        $template = preg_replace_callback('/@if\s*\((.+?)\)(.*?)(?:@elseif\s*\((.+?)\)(.*?))*(?:@else(.*?))?@endif/s', function ($matches) {
            $condition = $matches[1];
            $trueBlock = $matches[2];

            $result = '<?php if (' . $condition . '): ?>' . $trueBlock;

            // Processa @elseif se existir
            if (isset($matches[3])) {
                $result .= '<?php elseif (' . $matches[3] . '): ?>' . $matches[4];
            }

            // Processa @else se existir
            if (isset($matches[5])) {
                $result .= '<?php else: ?>' . $matches[5];
            }

            $result .= '<?php endif; ?>';

            return $result;
        }, $template);

        return $template;
    }

    /**
     * Processa o template substituindo as diretivas do Lux por código PHP.
     *
     * @param string $template O template a ser processado
     * @return string O template com as diretivas convertidas para PHP
     */
    private static function for(string $template)
    {
        return preg_replace_callback('/@for\s*\((.+?)\)(.*?)@endfor/s', function ($matches) {
            $condition = $matches[1];
            $block = $matches[2];

            return '<?php for (' . $condition . '): ?>' . $block . '<?php endfor; ?>';
        }, $template);
    }

    /* Processa o template substituindo as diretivas do Lux por código PHP.
     *
     * @param string $template O template a ser processado
     * @return string O template com as diretivas convertidas para PHP
     */
    private static function foreach(string $template)
    {
        return preg_replace_callback('/@foreach\s*\((.+?)\)(.*?)@endforeach/s', function ($matches) {
            $condition = $matches[1];
            $block = $matches[2];

            return '<?php foreach (' . $condition . '): ?>' . $block . '<?php endforeach; ?>';
        }, $template);
    }

    /**
     * Processa o template substituindo as diretivas do Lux por código PHP.
     *
     * @param string $template O template a ser processado
     * @return string O template com as diretivas convertidas para PHP
     */
    private static function while(string $template)
    {
        return preg_replace_callback('/@while\s*\((.+?)\)(.*?)@endwhile/s', function ($matches) {
            $condition = $matches[1];
            $block = $matches[2];

            return '<?php while (' . $condition . '): ?>' . $block . '<?php endwhile; ?>';
        }, $template);
    }

    /**
     * Processa o template substituindo as diretivas do Lux por código PHP.
     *
     * @param string $template O template a ser processado
     * @return string O template com as diretivas convertidas para PHP
     */
    private static function switch(string $template)
    {
        return preg_replace_callback('/@switch\s*\((.+?)\)(.*?)@endswitch/s', function ($matches) {
            $condition = $matches[1];
            $block = $matches[2];

            // Processa @case e @default dentro do switch
            $block = $this->case($block);
            $block = $this->default($block);
            $block = $this->break($block);

            return '<?php switch (' . $condition . '): ?>' . $block . '<?php endswitch; ?>';
        }, $template);
    }

    /**
     * Processa o template substituindo as diretivas do Lux por código PHP.
     *
     * @param string $template O template a ser processado
     * @return string O template com as diretivas convertidas para PHP
     */
    private static function case(string $template)
    {
        return preg_replace_callback('/@case\s*\((.+?)\)(.*?)(?=@case|@default|@endswitch)/s', function ($matches) {
            $condition = $matches[1];
            $block = $matches[2];

            return '<?php case ' . $condition . ': ?>' . $block . '<?php break; ?>';
        }, $template);
    }

    /**
     * Processa o template substituindo as diretivas do Lux por código PHP.
     *
     * @param string $js O código JavaScript a ser processado
     * @return string O código JavaScript com as diretivas convertidas para PHP
     */
    private static function js(string $js)
    {
        return preg_replace_callback('/@js\s*\((["\'])(.+?)\1\)/', function ($matches) {
            $path = $matches[2];

            // Garante que o caminho seja relativo ao projeto
            $fullPath = Config::getAplicationConfig()['path']['js'] . $path . '.js';

            if (!file_exists($fullPath)) {
                return "<script>console.error('Arquivo JS não encontrado: {$fullPath}');</script>";
            }
            $content = file_get_contents($fullPath);
            $hash = base64_encode(hash('sha384', $content, true));

            return <<<HTML
                <script src="{$fullPath}" type="module" integrity="sha384-{$hash}" crossorigin="anonymous"></script>
            HTML;
        }, $js);
    }



    // 3. break() - Novo método
    private static function break(string $template)
    {
        return preg_replace('/@break/', '<?php break; ?>', $template);
    }

    /**
     * Processa o template substituindo as diretivas do Lux por código PHP.
     *
     * @param string $template O template a ser processado
     * @return string O template com as diretivas convertidas para PHP
     */
    private static function default(string $template)
    {
        return preg_replace_callback('/@default(.*?)@enddefault/s', function ($matches) {
            $block = $matches[1];

            return '<?php default: ?>' . $block . '<?php break; ?>';
        }, $template);
    }

    /**
     * Processa o template substituindo as diretivas do Lux por código PHP.
     *
     * @param string $template O template a ser processado
     * @return string O template com as diretivas convertidas para PHP
     */
    /**
     * Processa o template substituindo as diretivas do Lux por código PHP.
     *
     * @param string $template O template a ser processado
     * @return string O template com as diretivas convertidas para PHP
     */
    private static function include(string $template)
    {
        return preg_replace_callback('/@include\s*\(\s*[\'"](.+?)[\'"]\s*(?:,\s*(.+?))?\s*\)/', function ($matches) {
            $view = $matches[1];
            $additionalVars = $matches[2] ?? '[]';

            return '<?php echo \Lumynus\Bundle\Framework\Lux::render('
                . var_export($view, true) . ', '
                . 'array_merge(get_defined_vars(), ' . $additionalVars . ')'
                . '); ?>';
        }, $template);
    }

    /**
     * Processa o template substituindo as diretivas do Lux por código PHP.
     *
     * @param string $template O template a ser processado
     * @return string O template com as diretivas convertidas para PHP
     */
    private static function use(string $template): string
    {
        return preg_replace_callback('/@use\s*\(\s*[\'"](.+?)[\'"]\s*,\s*(\w+)\s*\)/', function ($matches) {
            $view = $matches[1];
            $block = $matches[2];

            $basePath = Config::pathProject();
            $viewFile = $basePath . Config::getAplicationConfig()['path']['views'] . $view;
            $cacheFile = $basePath . '/cache/views/' . $view . '.php';

            if (!file_exists($viewFile)) {
                throw new \Exception("View '$view' not found at $viewFile");
            }

            // Cria diretório de cache se não existir
            $cacheDir = dirname($cacheFile);
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0777, true);
            }

            // Compila se necessário
            if (!file_exists($cacheFile) || filemtime($cacheFile) < filemtime($viewFile)) {
                $original = file_get_contents($viewFile);
                $compiled = self::compile($original);
                file_put_contents($cacheFile, $compiled);
            }

            // Lê o arquivo compilado
            $compiledContent = file_get_contents($cacheFile);

            // Regex para buscar o bloco no conteúdo compilado
            if (preg_match('/\{\%\s*' . preg_quote($block, '/') . '\s*\%\}(.*?)\{\%\s*endblock\s*\%\}/s', $compiledContent, $blockMatch)) {
                return $blockMatch[1];
            } else {
                throw new \Exception("Block '{$block}' not found in view '{$view}'");
            }
        }, $template);
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
