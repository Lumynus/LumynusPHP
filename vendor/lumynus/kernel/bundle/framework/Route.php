<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\ErrorTemplate;
use Lumynus\Bundle\Framework\Config;
use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Templates\Errors;
use Lumynus\Bundle\Framework\Logs;
use Lumynus\Http\Request;
use Lumynus\Http\Response;
use Lumynus\Http\Contracts\Request as ContractsRequest;
use Lumynus\Http\Contracts\Response as ContractsResponse;

/**
 * Classe responsável pelo gerenciamento de rotas no framework Lumynus.
 * Permite o registro de rotas com validação de parâmetros e tipos.
 */
final class Route extends LumaClasses
{

    use Errors;

    /**
     * Armazena todas as rotas registradas, organizadas por método HTTP.
     *
     * @var array
     */
    private static array $routes = [];

    /**
     * Armazena a pilha de middlewares registrados.
     */
    private static array $middlewareStack = [];

    /**
     * Armazena rotas dinâmicas.
     *
     * @var array
     */
    private static array $dynamicRoutes = [];

    /**
     * Registra uma rota com base no método HTTP.
     *
     * @param string $method Método HTTP (GET, POST, PUT, DELETE).
     * @param string|array $route Caminho da rota, podendo conter definição de campos.
     * @param string $controller Nome da classe do controlador.
     * @param string $action Método a ser chamado no controlador.
     * @return void
     */
    private static function register(string $method, string|array $route, string $controller, string $action): void
    {
        if (is_array($route)) {
            foreach ($route as $r) {
                self::register($method, $r, $controller, $action);
            }
            return;
        }

        $parsed = self::parseRouteConfig($route);

        $config = [
            'controller'      => $controller,
            'action'          => $action,
            'fieldsPermitted' => $parsed['fields'],
            'middlewares'     => self::$middlewareStack,
            'api'             => $parsed['api']
        ];

        if ($parsed['isDynamic']) {
            self::$dynamicRoutes[strtoupper($method)][$parsed['regex']] = $config;
        } else {
            self::$routes[strtoupper($method)][$parsed['cleanRoute']] = $config;
        }
    }

    /**
     * Analisa a string da rota e extrai os campos permitidos e seus tipos.
     *
     * @param string $route Rota com ou sem definição de campos entre colchetes.
     * @return array Um array contendo [rota limpa, campos permitidos].
     */
    private static function parseRouteConfig(string $route): array
    {
        $isApi = false;
        $fieldsPermitted = [];
        $queryConfigString = '';

        // 1. Verifica e remove a flag {api} do final
        if (str_ends_with($route, '{api}')) {
            $isApi = true;
            $route = substr($route, 0, -5);
        }

        // 2. Separa a Rota Física da Configuração de Query String (?)
        if (str_contains($route, '?')) {
            // Divide em [0] => 'meu/{id}[int]', [1] => '[string nome][int *]'
            [$routePath, $queryConfigString] = explode('?', $route, 2);
            $route = rtrim($routePath, '/'); // Remove barra extra se houver antes da ?
        }

        // 3. Processa configurações da Query String: [tipo nome]
        if (!empty($queryConfigString)) {
            // Regex para capturar: [tipo nome] ou [tipo *]
            // Ex: [string va] -> Match 1: string, Match 2: va
            preg_match_all('/\[([a-zA-Z]+)\s+([a-zA-Z0-9_*]+)\]/', $queryConfigString, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $type = $match[1]; // ex: string
                $name = $match[2]; // ex: va (ou *)

                // Salva no array de campos permitidos
                $fieldsPermitted[$name] = $type;
            }
        }

        // 4. Se não tiver chaves {}, é uma rota Estática (mas pode ter query string processada acima)
        if (!str_contains($route, '{')) {
            return [
                'isDynamic' => false,
                'cleanRoute' => $route,
                'regex' => null,
                'fields' => $fieldsPermitted, // Inclui os campos da QueryString
                'api' => $isApi
            ];
        }

        // 5. Processamento de Rota Dinâmica (Caminho)
        $safeRoute = preg_quote($route, '~');
        $pattern = '/\\\\\{(\w+)\\\\\}(?:\\\\\[(\w+)\\\\\])?/';

        $regexRoute = preg_replace_callback($pattern, function ($matches) use (&$fieldsPermitted) {
            $paramName = $matches[1];
            $paramType = $matches[2] ?? '*';

            // Adiciona/Sobrescreve com os campos da URL
            $fieldsPermitted[$paramName] = $paramType;

            return '(?P<' . $paramName . '>[^/]+)';
        }, $safeRoute);

        $finalRegex = '~^' . $regexRoute . '$~';

        return [
            'isDynamic' => true,
            'cleanRoute' => $route, // A rota para o cache/chave
            'regex' => $finalRegex,
            'fields' => $fieldsPermitted, // Combinação de URL params + Query params
            'api' => $isApi
        ];
    }


    /**
     * Registra uma rota do tipo GET.
     *
     * @param string|array $route
     * @param string $controller
     * @param string $action
     * @return void
     */
    public static function get(string|array $route, string $controller, string $action): void
    {
        self::register('GET', $route, $controller, $action);
    }

    /**
     * Registra uma rota do tipo POST.
     *
     * @param string|array $route
     * @param string $controller
     * @param string $action
     * @return void
     */
    public static function post(string|array $route, string $controller, string $action): void
    {
        self::register('POST', $route, $controller, $action);
    }

    /**
     * Registra uma rota do tipo PUT.
     *
     * @param string|array $route
     * @param string $controller
     * @param string $action
     * @return void
     */
    public static function put(string|array $route, string $controller, string $action): void
    {
        self::register('PUT', $route, $controller, $action);
    }

    /**
     * Registra uma rota do tipo DELETE.
     *
     * @param string|array $route
     * @param string $controller
     * @param string $action
     * @return void
     */
    public static function delete(string|array $route, string $controller, string $action): void
    {
        self::register('DELETE', $route, $controller, $action);
    }

    /**
     * Registra uma rota do tipo PATCH.
     *
     * @param string|array $route
     * @param string $controller
     * @param string $action
     * @return void
     */
    public static function patch(string|array $route, string $controller, string $action): void
    {
        self::register('PATCH', $route, $controller, $action);
    }


    /**
     * Registra uma rota de qualquer tipo (GET, POST, PUT, DELETE).
     *
     * @param string|array $route
     * @param string $controller
     * @param string $action
     * @return void
     */
    public static function any(string|array $route, string $controller, $action): void
    {
        self::register('GET', $route, $controller, $action);
        self::register('POST', $route, $controller, $action);
        self::register('PUT', $route, $controller, $action);
        self::register('DELETE', $route, $controller, $action);
        self::register('PATCH', $route, $controller, $action);
    }



    /**
     * Requer todos os arquivos de roteadores disponíveis no diretório src/Routers.
     * Utilizado para carregar rotas definidas em arquivos separados.
     *
     * @return void
     */
    private static function requireRouters()
    {
        $projetoRoot = Config::pathProject();

        if (!is_dir($projetoRoot)) {

            print(self::error()->render([
                'error_message' => 'Route files not found',
                'error_code' => 404,
                'file' => 'Check if the Routes folder exists inside the src directory, or if there are any route files inside it.'
            ]));

            return;
        }

        $routerFiles = glob(
            $projetoRoot .
                DIRECTORY_SEPARATOR .
                'src' .
                DIRECTORY_SEPARATOR .
                'routers' .
                DIRECTORY_SEPARATOR .
                '*.php'
        );

        ob_start();
        foreach ($routerFiles as $file) {
            if (is_file($file) && !str_contains($file, '..')) {
                require_once $file;
            }
        }
        ob_end_clean();
    }

    /**
     * Valida os parâmetros recebidos com base nos campos permitidos da rota.
     *
     * @param array $params Parâmetros recebidos pela requisição.
     * @param mixed $fieldsPermitted Campos permitidos (array ou '*').
     * @return array ['valid' => bool, 'error' => string|null]
     */
    private static function validateParams(array $params, mixed $fieldsPermitted): array
    {
        // Se a rota permite tudo sem restrição de tipo
        if ($fieldsPermitted === '*') {
            return ['valid' => true];
        }

        foreach ($params as $key => $value) {
            $expectedType = null;

            // 1. Verifica se existe uma regra Específica para este campo (Prioridade Alta)
            if (array_key_exists($key, $fieldsPermitted)) {
                $expectedType = $fieldsPermitted[$key];
            }
            // 2. Se não, verifica se existe um Curinga Global [*] (Prioridade Baixa)
            elseif (isset($fieldsPermitted['*'])) {
                $expectedType = $fieldsPermitted['*'];
            }

            // 3. Se não achou regra nem curinga, o campo é Proibido (Whitelist estrita)
            if ($expectedType === null) {
                return ['valid' => false, 'error' => "Field '$key' is not permitted."];
            }

            // 4. Valida o tipo
            if (!self::validateType($value, $expectedType)) {
                return ['valid' => false, 'error' => "Invalid type for '$key'. Expected $expectedType."];
            }
        }

        return ['valid' => true];
    }

    /**
     * Valida se um valor corresponde ao tipo esperado.
     *
     * @param mixed $value Valor a ser validado.
     * @param string $type Tipo esperado (string, int, float, bool, id).
     * @return bool
     */
    private static function validateType($value, string $type): bool
    {
        return match ($type) {
            '*'      => true,
            'string' => is_string($value),
            'int'    => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'float'  => filter_var($value, FILTER_VALIDATE_FLOAT) !== false,
            'bool'   => in_array(strtolower($value), ['1', '0', 'true', 'false'], true),
            default  => false,
        };
    }

    /**
     * Obtém os headers da requisição de forma compatível com diferentes ambientes.
     *
     * @return array Associative array com os headers da requisição.
     */
    private static function getRequestHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders(); // Se existir, usa direto
        }

        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                // Transforma HTTP_AUTHORIZATION → Authorization
                $header = str_replace(' ', '-', ucwords(strtolower(substr($key, 5)), ''));
                $headers[$header] = $value;
            }

            // Alguns headers não vêm com HTTP_ (como CONTENT_TYPE)
            if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $header = str_replace(' ', '-', ucwords(strtolower($key), ''));
                $headers[$header] = $value;
            }
        }

        return $headers;
    }

    /**
     * Registra middlewares que serão aplicados às rotas definidas no callback.
     *
     * @param string|array $middleware Nome(s) do(s) middleware(s) a serem aplicados.
     * @param string|array $action Nome(s) das ação(ões) a serem executadas.
     * @param callable $callback Função de callback onde as rotas serão definidas.
     * @return void
     */
    public static function midd(array|string $middlewares, array|string $actions, callable $callback): void
    {
        $map = [];

        if (is_string($middlewares) && is_string($actions)) {
            $map[] = [
                'midd' => $middlewares,
                'action' => $actions
            ];
        } elseif (is_array($middlewares)) {
            if (is_string($actions)) {
                foreach ($middlewares as $midd) {
                    $map[] = [
                        'midd' => $midd,
                        'action' => $actions
                    ];
                }
            } elseif (is_array($actions)) {
                if (count($middlewares) !== count($actions)) {
                    throw new \InvalidArgumentException("If 'middlewares' and 'actions' are arrays, they must have the same number of elements.");
                }

                foreach ($middlewares as $i => $midd) {
                    $map[] = [
                        'midd' => $midd,
                        'action' => $actions[$i]
                    ];
                }
            }
        } else {
            throw new \InvalidArgumentException("Middlewares and actions must be strings or arrays.");
        }

        self::$middlewareStack = $map;

        $callback();

        array_pop(self::$middlewareStack);
    }

    /**
     * Carrega as rotas do cache, se existir e estiver atualizado.
     * Verifica a data de modificação dos arquivos originais para invalidar o cache automaticamente.
     *
     * @return bool
     */
    private static function loadRoutesFromCache(): bool
    {
        $basePath = Config::pathProject();
        $cachePathRelative = Config::getAplicationConfig()['path']['cache'] . 'routers' . DIRECTORY_SEPARATOR;
        $cacheFile = $basePath . $cachePathRelative . 'routes.cache.php';

        if (!file_exists($cacheFile)) {
            return false;
        }

        $cacheTime = filemtime($cacheFile);

        $routerPattern = $basePath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'routers' . DIRECTORY_SEPARATOR . '*.php';
        $routerFiles = glob($routerPattern);

        if ($routerFiles === false) {
            return false;
        }

        foreach ($routerFiles as $file) {
            if (is_file($file) && filemtime($file) > $cacheTime) {
                return false;
            }
        }
        $cachedData = require $cacheFile;

        if (isset($cachedData['static']) || isset($cachedData['dynamic'])) {
            self::$routes = $cachedData['static'] ?? [];
            self::$dynamicRoutes = $cachedData['dynamic'] ?? [];
        } else {
            return false;
        }

        return true;
    }

    /**
     * Salva as rotas registradas em um arquivo de cache.
     * Utilizado para otimizar o carregamento das rotas em execuções futuras.
     *
     * @return void
     */
    private static function cacheRoutes(): void
    {
        $basePath = Config::pathProject();
        $cacheFile = $basePath . Config::getAplicationConfig()['path']['cache'] . 'routers' . DIRECTORY_SEPARATOR . 'routes.cache.php';

        $dataToCache = [
            'static'  => self::$routes,
            'dynamic' => self::$dynamicRoutes
        ];

        $export = var_export($dataToCache, true);
        $content = "<?php\n\nreturn {$export};";

        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

        file_put_contents($cacheFile, $content);
    }


    /**
     * Inicia o roteamento da aplicação.
     * Lê a URI da requisição, busca rotas estáticas ou dinâmicas, valida e executa.
     *
     * @return void
     */
    /**
     * Inicia o roteamento da aplicação.
     * Lê a URI da requisição, busca rotas estáticas ou dinâmicas, valida e executa.
     *
     * @return void
     */
    public static function start(): void
    {
        // ======================
        // ROTAS (CACHE)
        // ======================
        if (!self::loadRoutesFromCache()) {
            self::requireRouters();
            self::cacheRoutes();
        }

        // ======================
        // REQUEST BÁSICO
        // ======================
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

        // ======================
        // BASE PATH
        // ======================
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $base   = rtrim(dirname($script), '/');

        if ($base !== '' && $base !== '.' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $index = basename($script);
        if (str_starts_with($uri, '/' . $index)) {
            $uri = substr($uri, strlen('/' . $index));
        }

        $route = trim($uri, '/');

        // ======================
        // INPUT
        // ======================
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        // ======================
        // REQUEST / RESPONSE
        // ======================
        $request = new Request(
            $method,
            $uri,
            $_GET,
            $_POST,
            self::getRequestHeaders(),
            $_FILES,
            $_SERVER,
            $input
        );

        $response = new Response();

        // ======================
        // MATCH DE ROTAS
        // ======================
        $routeConfig = self::$routes[$method][$route] ?? null;
        $routeParams = [];

        if (!$routeConfig && isset(self::$dynamicRoutes[$method])) {
            foreach (self::$dynamicRoutes[$method] as $regex => $config) {
                if (preg_match($regex, $route, $matches)) {
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $routeParams[$key] = $value;
                        }
                    }
                    $routeConfig = $config;
                    break;
                }
            }
        }

        if (!$routeConfig) {
            self::throwError('Route not found', 404, 'html');
            return;
        }

        // ======================
        // PARAMS
        // ======================
        $params = array_merge($_GET, $routeParams);

        if (!self::validateParams($params, $routeConfig['fieldsPermitted'])['valid']) {
            Logs::register('Validation Params', [
                'params' => $params,
                'allowed' => $routeConfig['fieldsPermitted']
            ]);
            self::throwError('Forbidden', 403, 'html');
            return;
        }

        // ======================
        // CSRF
        // ======================
        if (
            !($routeConfig['api'] ?? false) &&
            Config::getAplicationConfig()['security']['csrf']['enabled'] &&
            in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)
        ) {
            $tokenName = Config::getAplicationConfig()['security']['csrf']['nameToken'];
            $token =
                $input[$tokenName]
                ?? $_POST[$tokenName]
                ?? $_SERVER['HTTP_X_CSRF_TOKEN']
                ?? null;

            if (!$token || !CSRF::isValidToken($token)) {
                Logs::register('CSRF Token Mismatch', ['token' => $token]);
                self::throwError('Page Expired', 419, 'html');
                return;
            }
        }

        // ======================
        // MIDDLEWARES
        // ======================
        $middlewareData = [];

        foreach ($routeConfig['middlewares'] ?? [] as $midd) {
            $instance = new $midd['midd']();

            $result = $instance->{$midd['action']}(
                $request,
                $response,
                $params
            );

            if ($result === false) {
                self::throwError('Forbidden', 403, 'html');
                return;
            }

            if (is_array($result)) {
                $middlewareData = array_merge($middlewareData, $result);
            }
        }

        // ======================
        // CONTROLLER
        // ======================
        $controller = new $routeConfig['controller']();
        $methodName = $routeConfig['action'];

        $customizeParamsPosts = array_merge(
            ['GET' => $params ?? []],
            ['POST' => $_POST ?? []],
            ['INPUT' => $input ?? []],
            ['FILE' => $_FILES ?? []],
            ['HEADER' => self::getRequestHeaders() ?? []]
        );

        try {
            $reflection = new \ReflectionMethod($controller, $methodName);
            $args = [];

            foreach ($reflection->getParameters() as $param) {
                $type = $param->getType()?->getName();

                match ($type) {
                    Request::class,
                    ContractsRequest::class => $args[] = $request,

                    Response::class,
                    ContractsResponse::class => $args[] = $response,

                    'array' => $args[] = $middlewareData,

                    default => $args[] = $customizeParamsPosts
                };
            }

            $controller->{$methodName}(...$args);
        } catch (\Throwable $e) {
            Logs::register('Controller Error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);
            self::throwError('Internal server error', 500, 'html');
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
     * Retorna todas as rotas registradas no sistema.
     *
     * @return array
     */
    public static function listRoutes(): array
    {
        return self::$routes;
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
