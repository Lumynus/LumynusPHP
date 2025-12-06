<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

use Lumynus\Bundle\Framework\ErrorTemplate;
use Lumynus\Bundle\Framework\Config;
use Lumynus\Bundle\Framework\LumaClasses;
use Lumynus\Templates\Errors;
use Lumynus\Bundle\Framework\Logs;

/**
 * Classe responsável pelo gerenciamento de rotas no framework Lumynus.
 * Permite o registro de rotas com validação de parâmetros e tipos.
 */
class Route extends LumaClasses
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

        // 1. Verifica e remove a flag {api} do final
        if (str_ends_with($route, '{api}')) {
            $isApi = true;
            $route = substr($route, 0, -5); // Remove '{api}'
        }

        // 2. Se não tiver chaves {}, é uma rota Estática simples
        if (!str_contains($route, '{')) {
            return [
                'isDynamic' => false,
                'cleanRoute' => $route, // Rota limpa sem regex
                'regex' => null,
                'fields' => [], // Sem campos variáveis
                'api' => $isApi
            ];
        }

        // 3. Processamento de Rota Dinâmica
        // Escapa a string para ser segura em Regex (pontos viram \., barras viram \/)
        $safeRoute = preg_quote($route, '~');

        // Regex Mágica para encontrar: \{nome\} OU \{nome\}[tipo]
        // Como usamos preg_quote, as chaves e colchetes estão escapados (\{\})
        $pattern = '/\\\\\{(\w+)\\\\\}(?:\\\\\[(\w+)\\\\\])?/';

        // Substitui cada ocorrência pelo regex de captura (?P<name>[^/]+)
        // E popula o array $fieldsPermitted
        $regexRoute = preg_replace_callback($pattern, function ($matches) use (&$fieldsPermitted) {
            $paramName = $matches[1];
            $paramType = $matches[2] ?? '*'; // Se não tiver [tipo], assume '*'

            $fieldsPermitted[$paramName] = $paramType;

            // Retorna o grupo de captura regex para a URL
            return '(?P<' . $paramName . '>[^/]+)';
        }, $safeRoute);

        // Adiciona âncoras de inicio e fim para o Regex final
        $finalRegex = '~^' . $regexRoute . '$~';

        return [
            'isDynamic' => true,
            'cleanRoute' => $route, // A rota original "legível" (sem {api})
            'regex' => $finalRegex,
            'fields' => $fieldsPermitted,
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
        if ($fieldsPermitted === '*') {
            return ['valid' => true];
        }

        if (isset($fieldsPermitted['*'])) {
            $expectedType = $fieldsPermitted['*'];
            foreach ($params as $key => $value) {
                if (!self::validateType($value, $expectedType)) {
                    return ['valid' => false, 'error' => "Invalid type for '$key'. Expected $expectedType."];
                }
            }
            return ['valid' => true];
        }

        foreach ($params as $key => $value) {
            if (!array_key_exists($key, $fieldsPermitted)) {
                return ['valid' => false, 'error' => "Field '$key' is not permitted."];
            }

            $expectedType = $fieldsPermitted[$key];

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
                $header = str_replace('', '-', ucwords(strtolower(substr($key, 5)), ''));
                $headers[$header] = $value;
            }

            // Alguns headers não vêm com HTTP_ (como CONTENT_TYPE)
            if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $header = str_replace('', '-', ucwords(strtolower($key), ''));
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
     * Carrega as rotas do cache, se existir.
     * Caso contrário, chama o método para requerer os arquivos de roteadores.
     *
     * @return bool Retorna true se as rotas foram carregadas do cache, false caso contrário.
     */
    private static function loadRoutesFromCache(): bool
    {
        $basePath = Config::pathProject();
        $cacheFile = $basePath . Config::getAplicationConfig()['path']['cache'] . 'routers' . DIRECTORY_SEPARATOR . 'routes.cache.php';

        if (!file_exists($cacheFile)) return false;

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
    public static function start(): void
    {
        // Verifica se as rotas foram carregadas
        if (!self::loadRoutesFromCache()) {
            self::requireRouters();
            self::cacheRoutes(); // salva para as próximas execuções
        }

        $requestMethod = $_SERVER['REQUEST_METHOD'];

        // Obtém apenas o caminho da URL
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        /////// TRATAMENTO DE DIRETÓRIO / BASE PATH ///////

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $basePath = rtrim(dirname($scriptName), '/');

        if ($basePath === '.' || $basePath === '/') {
            $basePath = '';
        }

        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        $index = basename($scriptName);
        if (str_starts_with($uri, '/' . $index)) {
            $uri = substr($uri, strlen('/' . $index));
        }

        //////// FIM DO TRATAMENTO ////////

        // Limpa barras extras no início/fim
        $route = trim($uri, '/');

        if ($route === '') {
            $route = '';
        }

        // Prepara os parâmetros iniciais (GET e QueryString manual)
        $params = $_GET;
        $queryString = $_SERVER['QUERY_STRING'] ?? '';

        if (str_contains($queryString, ':')) {
            [, $query] = explode(':', $queryString);
            parse_str($query, $customParams);
            $params = array_merge($params, $customParams);
        }

        $routeConfig = null;
        $routeParams = []; // Para armazenar IDs capturados (ex: id=10)

        // 1. Tenta encontrar uma Rota Estática (Exata) - É mais rápido
        if (isset(self::$routes[$requestMethod][$route])) {
            $routeConfig = self::$routes[$requestMethod][$route];
        }
        // 2. Se não achou, tenta encontrar uma Rota Dinâmica (Regex)
        else {
            if (isset(self::$dynamicRoutes[$requestMethod])) {
                foreach (self::$dynamicRoutes[$requestMethod] as $regex => $config) {
                    if (preg_match($regex, $route, $matches)) {
                        $routeConfig = $config;

                        // Extrai apenas as chaves de texto (os nomes dos parâmetros)
                        foreach ($matches as $key => $value) {
                            if (is_string($key)) {
                                $routeParams[$key] = $value;
                            }
                        }
                        break; // Parar o loop assim que encontrar
                    }
                }
            }
        }

        // 3. Se não encontrou em nenhum lugar, lança 404
        if (!$routeConfig) {
            self::throwError('Route not found', 404, 'html');
            return;
        }

        // 4. MERGE FINAL: Junta os parâmetros da URL ({id}) com os do GET
        // Os parâmetros da rota têm prioridade ou complementam o GET
        $params = array_merge($params, $routeParams);

        // Validação de Tipos (Agora usa o array configurado no parse)
        $validation = self::validateParams($params, $routeConfig['fieldsPermitted']);

        if (!$validation['valid']) {
            Logs::register("Validation Params", [
                'Message' => 'Invalid fields sent. Some parameters are not allowed or type mismatch.',
                'params'  => $params,
                'fieldsPermitted' => $routeConfig['fieldsPermitted']
            ]);
            self::throwError('Forbidden', 403, 'html');
            return;
        }

        // VERIFICA SE O TOKEN CSRF FOI ENVIADO E É VÁLIDO (apenas para rotas Web)
        $isApi = $routeConfig['api'] ?? false;
        $input = json_decode(file_get_contents('php://input'), true);

        if (
            !$isApi &&
            Config::getAplicationConfig()['security']['csrf']['enabled'] === true
            && in_array($requestMethod, ['POST', 'PUT', 'DELETE'])
        ) {
            $tokenName = Config::getAplicationConfig()['security']['csrf']['nameToken'];
            $token = null;

            if (!empty($input[$tokenName])) {
                $token = $input[$tokenName];
            }
            // 2. Fallback para header HTTP
            elseif (!empty($_SERVER['X_CSRF_TOKEN'])) {
                $token = $_SERVER['X_CSRF_TOKEN'];
            }
            // 3. Fallback para POST tradicional
            elseif (!empty($_POST[$tokenName])) {
                $token = $_POST[$tokenName];
            }
            // 4. Fallback para header HTTP alternativo
            elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
            }

            if (!$token || CSRF::isValidToken($token) === false) {
                Logs::register("CSRF Token Mismatch Error", [
                    'Sent Token'    => $token,
                    'Session Token' => CSRF::getToken(),
                    'Message'       => 'The token sent does not match the session token. Possible CSRF attack or session expired.'
                ]);
                self::throwError('Page Expired', 419, 'html');
                return;
            }
        }

        $customizeParamsPosts = array_merge(
            ['GET' => $params ?? []], // $params agora contém os dados da URL validada
            ['POST' => $_POST ?? []],
            ['INPUT' => $input ?? []],
            ['FILE' => $_FILES ?? []],
            ['HEADER' => self::getRequestHeaders() ?? []]
        );

        // Executa os middlewares registrados para a rota
        $returnMidd = null;
        if (!empty($routeConfig['middlewares'])) {

            foreach ($routeConfig['middlewares'] as $key => $midd) {
                $middlewareClass =  $midd['midd'];

                if (!class_exists($middlewareClass)) {
                    throw new \Exception("Error Processing Request - Midd", 1);
                }

                $middleware = new $middlewareClass();

                if (!method_exists($middleware, $midd['action'])) {
                    throw new \Exception("Error Processing Request - Midd", 1);
                }

                $returnMidd = call_user_func_array([$middleware, $midd['action']], [$customizeParamsPosts]);

                if ($returnMidd === false) {
                    Logs::register("System Interrupted", [
                        'Code'    => 403,
                        'Message' => 'Request blocked by middleware: user is not authorized or action is forbidden.'
                    ]);
                    self::throwError('Forbidden', 403, 'html');
                    return;
                }
            }
        }

        // Se chegou aqui, significa que a rota é válida e os middlewares passaram
        $controller = $routeConfig['controller'];
        $action = $routeConfig['action'];

        if (class_exists($controller) && method_exists($controller, $action)) {

            $instance = new $controller();

            call_user_func_array(
                [$instance, $action],
                [$customizeParamsPosts, ($returnMidd !== null && $returnMidd !== false) ? $returnMidd : null]
            );
        } else {
            Logs::register("System Interrupted", [
                'Code'    => 500,
                'Message' => 'The system encountered an error and cannot process the request.'
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
