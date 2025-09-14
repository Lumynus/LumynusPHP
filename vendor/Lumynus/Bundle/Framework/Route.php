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

        [$cleanRoute, $fieldsPermitted, $isApi] = self::parseRouteFields($route);

        self::$routes[strtoupper($method)][$cleanRoute] = [
            'controller' => $controller,
            'action' => $action,
            'fieldsPermitted' => $fieldsPermitted,
            'middlewares' => self::$middlewareStack,
            'api' => $isApi
        ];
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
    private static function parseRouteFields(string $route): array
    {
        $fieldsPermitted = [];
        $cleanRoute = $route;
        $isApi = false;

        // Verifica se tem marcador {api}
        if (preg_match('/\{api\}$/', $route)) {
            $isApi = true;
            $cleanRoute = str_replace('{api}', '', $cleanRoute);
        }

        if (preg_match('/^(.*?)\[(.*?)\]$/', $cleanRoute, $matches)) {
            $cleanRoute = $matches[1];
            $fieldsRaw = trim($matches[2]);

            if ($fieldsRaw === '*') {
                $fieldsPermitted = '*';
            } elseif (preg_match('/^(string|int|float|bool)\s+\*$/', $fieldsRaw, $typeMatch)) {
                $fieldsPermitted = ['*' => $typeMatch[1]];
            } else {

                $fields = preg_split('/\s*,\s*/', $fieldsRaw); // Remove espaços ao redor das vírgulas
                foreach ($fields as $field) {
                    $field = trim($field);
                    if (preg_match('/^(string|int|float|bool)\s+(\w+)$/', $field, $fieldMatch)) {
                        [, $type, $name] = $fieldMatch;
                        $fieldsPermitted[$name] = $type;
                    }
                }
            }
        }

        return [$cleanRoute, $fieldsPermitted, $isApi];
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

        if (!file_exists($cacheFile)) {
            return false;
        }

        $cacheTime = filemtime($cacheFile);

        $routerPath = $basePath . Config::getAplicationConfig()['path']['routers'];
        $routerFiles = glob($routerPath . '/*.php');

        $lastModified = 0;
        foreach ($routerFiles as $file) {
            $mtime = filemtime($file);
            if ($mtime > $lastModified) {
                $lastModified = $mtime;
            }
        }

        if ($cacheTime < $lastModified) {
            return false;
        }

        self::$routes = require $cacheFile;
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

        $export = var_export(self::$routes, true);
        $content = "<?php\n\nreturn {$export};";

        // Cria diretório de cache se não existir
        $cacheDir = dirname($cacheFile);

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents($cacheFile, $content);
    }

    /**
     * Inicia o roteamento da aplicação.
     * Lê a URI da requisição, valida parâmetros e executa o controlador.
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

        // Obtém o método da requisição e a URI
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $uri = strtok($_SERVER['REQUEST_URI'], '?');
        $queryString = $_SERVER['QUERY_STRING'];

        $route = $queryString;
        $params = [];

        if (str_contains($queryString, ':')) {
            [$route, $query] = explode(':', $queryString);
            parse_str($query, $params);
        }

        if (!isset(self::$routes[$requestMethod][$route])) {
            self::throwError('Route not found', 404, 'html');
            return;
        }

        $routeConfig = self::$routes[$requestMethod][$route];
        $validation = self::validateParams($params, $routeConfig['fieldsPermitted']);

        if (!$validation['valid']) {
            Logs::register("Validation Params", [
                'Message' => 'Invalid fields sent. Some parameters are not allowed.'
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
            ['GET' => $params ?? []],
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
        // Instancia o controlador e chama a ação correspondente
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
