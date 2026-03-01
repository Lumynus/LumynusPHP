<?php

declare(strict_types=1);

namespace Lumynus\Framework;

use Lumynus\Framework\ErrorTemplate;
use Lumynus\Framework\Config;
use Lumynus\Framework\LumaClasses;
use Lumynus\Templates\Errors;
use Lumynus\Framework\Logs;
use Lumynus\Http\HttpRequest;
use Lumynus\Http\HttpResponse;
use Lumynus\Http\Contracts\Request as ContractsRequest;
use Lumynus\Http\Contracts\Response as ContractsResponse;
use Lumynus\Http\HttpException;

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
     * Controla se as rotas já estão na memória
     */
    private static bool $booted = false;

    /**
     * Indica se a aplicação está sendo executada em modo CLI (Command Line Interface).
     *
     * Quando `true`, significa que o script está rodando via terminal,
     * permitindo comportamentos específicos para ambiente de linha de comando.
     * Quando `false`, a execução ocorre em ambiente web (HTTP).
     *
     * @var bool
     */
    private static bool $cliMode = false;

    /**
     * Registra uma rota do tipo GET.
     *
     * @param string|array $route @example '/users/{id}[int]'
     * @param string $controller @example 'UserController'
     * @param string $action @example 'show'
     * @return void
     */
    public static function get(string|array $route, string $controller, string $action): void
    {
        self::register('GET', $route, $controller, $action);
    }

    /**
     * Registra uma rota do tipo POST.
     *
     * @param string|array $route @example '/users/{id}[int]'
     * @param string $controller @example 'UserController'
     * @param string $action @example 'create'
     * @param bool $noCsrf -- Indica se a verificação CSRF deve ser desativada para esta rota.
     * @return void
     */
    public static function post(string|array $route, string $controller, string $action, bool $noCsrf = false): void
    {
        self::register('POST', $route, $controller, $action, $noCsrf);
    }

    /**
     * Registra uma rota do tipo PUT.
     *
     * @param string|array $route @example '/users/{id}[int]'
     * @param string $controller @example 'UserController'
     * @param string $action @example 'update'
     * @param bool $noCsrf -- Indica se a verificação CSRF deve ser desativada para esta rota.
     * @return void
     */
    public static function put(string|array $route, string $controller, string $action, bool $noCsrf = false): void
    {
        self::register('PUT', $route, $controller, $action, $noCsrf);
    }

    /**
     * Registra uma rota do tipo DELETE.
     *
     * @param string|array $route @example '/users/{id}[int]'
     * @param string $controller @example 'UserController'
     * @param string $action @example 'delete'
     * @param bool $noCsrf -- Indica se a verificação CSRF deve ser desativada para esta rota.
     * @return void
     */
    public static function delete(string|array $route, string $controller, string $action, bool $noCsrf = false): void
    {
        self::register('DELETE', $route, $controller, $action, $noCsrf);
    }

    /**
     * Registra uma rota do tipo PATCH.
     *
     * @param string|array $route @example '/users/{id}[int]'
     * @param string $controller @example 'UserController'
     * @param string $action @example 'update'
     * @param bool $noCsrf -- Indica se a verificação CSRF deve ser desativada para esta rota.
     * @return void
     */
    public static function patch(string|array $route, string $controller, string $action, bool $noCsrf = false): void
    {
        self::register('PATCH', $route, $controller, $action, $noCsrf);
    }

    /**
     * Registra uma rota de qualquer tipo (GET, POST, PUT, PATCH, DELETE).
     *
     * @param string|array $route @example '/users/{id}[int]'
     * @param string $controller @example 'UserController'
     * @param string $action @example 'update'
     * @param bool $noCsrf -- Indica se a verificação CSRF deve ser desativada para esta rota. Exceto para GET.
     * @return void
     */
    public static function any(string|array $route, string $controller, $action, bool $noCsrf = false): void
    {
        self::register('GET', $route, $controller, $action);
        self::register('POST', $route, $controller, $action, $noCsrf);
        self::register('PUT', $route, $controller, $action, $noCsrf);
        self::register('DELETE', $route, $controller, $action, $noCsrf);
        self::register('PATCH', $route, $controller, $action, $noCsrf);
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
        $previousStack = self::$middlewareStack;

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
                if (self::l_countStatic($middlewares) !== self::l_countStatic($actions)) {
                    throw new \InvalidArgumentException(
                        "If 'middlewares' and 'actions' are arrays, they must have the same number of elements."
                    );
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

        self::$middlewareStack = array_merge(self::$middlewareStack, $map);
        try {
            $callback();
        } finally {
            self::$middlewareStack = $previousStack;
        }
    }

    /**
     * Registra uma rota com base no método HTTP.
     *
     * @param string $method Método HTTP (GET, POST, PUT, DELETE).
     * @param string|array $route Caminho da rota, podendo conter definição de campos.
     * @param string $controller Nome da classe do controlador.
     * @param string $action Método a ser chamado no controlador.
     * @param bool $noCsrf -- Indica se a verificação CSRF deve ser desativada para esta rota.
     * @return void
     */
    private static function register(string $method, string|array $route, string $controller, string $action, bool $noCsrf = false): void
    {
        if (self::$booted) return;

        if (is_array($route)) {
            foreach ($route as $r) self::register($method, $r, $controller, $action, $noCsrf);
            return;
        }

        $parsed = self::parseRouteConfig($route, $noCsrf);
        $config = [
            'controller'      => $controller,
            'action'          => $action,
            'fieldsPermitted' => $parsed['fields'],
            'middlewares'     => array_values(self::$middlewareStack),
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
     * @param bool $noCsrf -- Indica se a verificação CSRF deve ser desativada para esta rota.
     * @return array Um array contendo [rota limpa, campos permitidos].
     */
    private static function parseRouteConfig(string $route, bool $noCsrf = false): array
    {
        $isApi = false;
        $fieldsPermitted = [];
        $queryConfigString = '';

        // 1. Remove barra inicial se existir
        if (str_starts_with($route, '/')) {
            $route = substr($route, 1);
        }

        // 2. Verifica e remove a flag {api} do final
        if (str_ends_with($route, '{api}')) {
            $isApi = true;
            $route = substr($route, 0, -5);
        }

        // 3. Se a rota for marcada como noCsrf, considera como API
        if ($noCsrf) {
            $isApi = true;
        }

        // 4. Separa a Rota Física da Configuração de Query String (?)
        if (str_contains($route, '?')) {
            // Divide em [0] => 'meu/{id}[int]', [1] => '[string nome][int *]'
            [$routePath, $queryConfigString] = explode('?', $route, 2);
            $route = rtrim($routePath, '/'); // Remove barra extra se houver antes da ?
        }

        // 5. Processa configurações da Query String: [tipo nome]
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

        // 6. Se não tiver chaves {}, é uma rota Estática (mas pode ter query string processada acima)
        if (!str_contains($route, '{')) {
            return [
                'isDynamic' => false,
                'cleanRoute' => $route,
                'regex' => null,
                'fields' => $fieldsPermitted, // Inclui os campos da QueryString
                'api' => $isApi
            ];
        }

        // 7. Processamento de Rota Dinâmica (Caminho)
        $safeRoute = preg_quote($route, '~');
        $pattern = '/\\\\\{(\w+)\\\\\}(?:\\\\\[(\w+)\\\\\])?/';

        $regexRoute = preg_replace_callback($pattern, function ($matches) use (&$fieldsPermitted) {
            $paramName = $matches[1];
            $paramType = $matches[2] ?? '*';

            $fieldsPermitted[$paramName] = $paramType;

            return '(?P<' . $paramName . '>[^/]+)';
        }, $safeRoute);

        $finalRegex = '~^' . $regexRoute . '$~';

        return [
            'isDynamic' => true,
            'cleanRoute' => $route,
            'regex' => $finalRegex,
            'fields' => $fieldsPermitted,
            'api' => $isApi
        ];
    }

    /**
     * Limpa o estado interno.
     * Útil para testes unitários ou se você precisar dar um "hot reload".
     */
    public static function clear(): void
    {
        if (self::$booted && !self::$cliMode) {
            Logs::register('Warning', ['msg' => 'Attempted to clear routes during runtime. Operation blocked.']);
            return;
        }
        self::$routes = [];
        self::$dynamicRoutes = [];
        self::$middlewareStack = [];
        self::$booted = false;
    }

    /**
     * Ativa o modo de execução via CLI (Command Line Interface).
     *
     * Define a aplicação como sendo executada em ambiente de linha de comando,
     * permitindo que fluxos e comportamentos específicos para terminal sejam aplicados.
     *
     * @return void
     */
    public static function enableCliMode(): void
    {
        self::$cliMode = true;
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
            throw new HttpException('Route files not found', 500);
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
            return ['valid' => true, 'error' => '...'];
        }

        foreach ($params as $key => $value) {
            $expectedType = null;

            if (array_key_exists($key, $fieldsPermitted)) {
                $expectedType = $fieldsPermitted[$key];
            } elseif (isset($fieldsPermitted['*'])) {
                $expectedType = $fieldsPermitted['*'];
            }

            if ($expectedType === null) {
                return ['valid' => false, 'error' => "Field '$key' is not permitted."];
            }

            if (!self::validateType($value, $expectedType)) {
                return ['valid' => false, 'error' => "Invalid type for '$key'. Expected $expectedType."];
            }
        }

        return ['valid' => true, 'error' => '...'];
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
            'bool'   => is_bool($value)
                || (is_string($value) && in_array(strtolower($value), ['1', '0', 'true', 'false'], true)),
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
            return getallheaders();
        }

        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace(' ', '-', ucwords(strtolower(substr($key, 5)), ''));
                $headers[$header] = $value;
            }

            if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $header = str_replace(' ', '-', ucwords(strtolower($key), ''));
                $headers[$header] = $value;
            }
        }

        return $headers;
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
        $cachePathRelative = Config::getApplicationConfig()['path']['cache'] . 'routers' . DIRECTORY_SEPARATOR;
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
        $cacheFile = $basePath . Config::getApplicationConfig()['path']['cache'] . 'routers' . DIRECTORY_SEPARATOR . 'routes.cache.php';

        $dataToCache = [
            'static'  => self::$routes,
            'dynamic' => self::$dynamicRoutes
        ];

        $export = var_export($dataToCache, true);
        $content = "<?php\n\nreturn {$export};";

        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
        $tmpFile = $cacheFile . '.' . uniqid((string)mt_rand(), true) . '.tmp';

        if (file_put_contents($tmpFile, $content, LOCK_EX) !== false) {

            if (PHP_OS_FAMILY === 'Windows' && file_exists($cacheFile)) {
                @unlink($cacheFile);
            }

            if (!@rename($tmpFile, $cacheFile)) {
                @unlink($tmpFile);
            }
        }
    }

    /**
     * Resolve a instância do controlador garantindo isolamento.
     * No futuro, este ponto será integrado ao DI Container do Lumynus.
     */
    private static function resolveController(string $controllerClass): object
    {
        if (!class_exists($controllerClass)) {
            throw new HttpException("Controller $controllerClass not found", 500, 'html');
        }
        return new $controllerClass();
    }

    /**
     * Inicializa o ciclo de boot da aplicação.
     *
     * Executa o carregamento das rotas apenas uma vez durante o ciclo de vida
     * do sistema. Caso exista cache de rotas válido, ele será utilizado.
     * Caso contrário, os arquivos de rotas serão carregados manualmente
     * e posteriormente armazenados em cache.
     *
     * Ao final do processo, marca a aplicação como inicializada e
     * redefine a pilha de middlewares.
     *
     * @return void
     */
    public static function boot(): void
    {
        if (self::$booted) return;

        if (!self::loadRoutesFromCache()) {
            self::requireRouters();
            self::cacheRoutes();
        }

        self::$booted = true;
        self::$middlewareStack = [];
    }

    /**
     * Resolve e normaliza os dados da requisição HTTP a partir do array `$server`.
     *
     * Extrai o método da requisição e a URI, removendo automaticamente:
     * - O base path da aplicação
     * - O nome do script (ex: index.php)
     *
     * O resultado é uma rota limpa, pronta para ser utilizada pelo roteador.
     *
     * @param array $server Array equivalente ao $_SERVER.
     *
     * @return array{
     *     0: string, // route normalizada (sem barras iniciais/finais)
     *     1: string, // método HTTP (GET, POST, etc.)
     *     2: string  // URI original
     * }
     */
    public static function resolveRequestRoute(array $server): array
    {

        $method = $server['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        $script = str_replace('\\', '/', $server['SCRIPT_NAME'] ?? '');
        $base   = rtrim(dirname($script), '/');

        if ($base !== '' && $base !== '.' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $index = basename($script);
        if (str_starts_with($uri, '/' . $index)) {
            $uri = substr($uri, strlen('/' . $index));
        }

        return [trim($uri, '/'), $method, $uri];
    }

    /**
     * Realiza o processo de correspondência (match) de uma rota
     * com base no método HTTP e no caminho informado.
     *
     * Primeiro verifica rotas estáticas registradas. Caso não encontre,
     * percorre as rotas dinâmicas (regex) do mesmo método, extraindo
     * automaticamente os parâmetros nomeados quando houver correspondência.
     *
     * @param string $method Método HTTP da requisição (GET, POST, PUT, DELETE, etc.).
     * @param string $route  Caminho da rota já normalizado (sem barras nas extremidades).
     *
     * @return array{
     *     0: array|null,
     *     1: array<string, string>
     * }
     *
     */
    private static function matchRoute(string $method, string $route): array
    {

        $routeConfig = self::$routes[$method][$route] ?? null;
        $routeParams = [];

        if (!$routeConfig && isset(self::$dynamicRoutes[$method])) {
            foreach (self::$dynamicRoutes[$method] as $regex => $config) {
                if (preg_match($regex, $route, $matches)) {
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) $routeParams[$key] = $value;
                    }
                    $routeConfig = $config;
                    break;
                }
            }
        }

        return [$routeConfig, $routeParams];
    }

    /**
     * Executa a pilha de middlewares associada à rota atual.
     *
     * Para cada middleware configurado:
     * - Instancia a classe definida.
     * - Executa a ação informada, injetando Request, Response e parâmetros da rota.
     *
     * Regras de controle de fluxo:
     * - Se ocorrer exceção, o erro é registrado em log e uma resposta 500 é emitida.
     * - Se o middleware retornar `false`, a execução é interrompida com resposta 403.
     * - Se retornar uma instância de ContractsResponse, ela é imediatamente despachada
     *   e o fluxo da requisição é encerrado.
     *
     * Caso nenhum middleware interrompa o fluxo, a execução continua normalmente
     * para o dispatcher do controller.
     *
     * @param array             $routeConfig Configuração da rota contendo a lista de middlewares.
     * @param ContractsRequest  $request     Objeto da requisição atual.
     * @param ContractsResponse $response    Objeto de resposta atual.
     * @param array             $params      Parâmetros extraídos da rota.
     *
     * @return bool|ContractsResponse
     */
    private static function handleMiddlewares(
        $routeConfig,
        ContractsRequest $request,
        ContractsResponse $response,
        $params
    ): ContractsResponse|bool {
        foreach ($routeConfig['middlewares'] ?? [] as $midd) {
            $instance = new $midd['midd']();
            try {
                $result = $instance->{$midd['action']}($request, $response, $params);
            } catch (\Throwable $e) {
                Logs::register('Middleware Error', ['msg' => $e->getMessage()]);
                throw new HttpException('Internal server error', 500, 'html');
            }

            if ($result === false) {
                throw new HttpException('Forbidden', 403, 'html');
            }

            if ($result instanceof ContractsResponse) {
                return $result;
            }
        }
        return true;
    }

    /**
     * Realiza o despacho do controller associado à rota encontrada.
     *
     * Resolve a instância do controller, identifica o método de ação
     * configurado e executa a chamada utilizando Reflection para
     * injeção automática de dependências com base na tipagem dos parâmetros.
     *
     * Regras de injeção:
     * - HttpRequest / ContractsRequest  → injeta o objeto de requisição
     * - HttpResponse / ContractsResponse → injeta o objeto de resposta
     * - Demais parâmetros                → injeta o array de parâmetros da rota
     *
     * Tratamento de retorno:
     * - Se retornar uma instância de ContractsResponse, ela será despachada.
     * - Se retornar string ou número, será enviada como resposta HTML.
     * - Caso contrário, a resposta padrão será despachada.
     *
     * Em caso de exceção, o erro é registrado em log e uma resposta
     * 500 (Internal Server Error) é emitida.
     *
     * @param array             $routeConfig Configuração da rota (controller e action).
     * @param ContractsRequest  $request     Objeto da requisição atual.
     * @param ContractsResponse $response    Objeto de resposta atual.
     * @param array             $params      Parâmetros extraídos da rota.
     *
     * @return void
     */
    private static function dispatchController($routeConfig, ContractsRequest $request, ContractsResponse $response, $params): ContractsResponse
    {
        $controller = self::resolveController($routeConfig['controller']);
        $methodName = $routeConfig['action'];

        try {
            $reflection = new \ReflectionMethod($controller, $methodName);
            $args = [];
            foreach ($reflection->getParameters() as $param) {
                $type = ($param->getType() instanceof \ReflectionNamedType) ? $param->getType()->getName() : null;
                match ($type) {
                    HttpRequest::class, ContractsRequest::class => $args[] = $request,
                    HttpResponse::class, ContractsResponse::class => $args[] = $response,
                    default => $args[] = $params
                };
            }

            $result = $controller->{$methodName}(...$args);

            if ($result instanceof ContractsResponse) {
                return $result;
            } elseif (is_string($result) || is_numeric($result)) {
                return $response->html((string) $result);
            } elseif (is_array($result)) {
                return $response->json((array) $result);
            } else {
                return $response;
            }
        } catch (\Throwable $e) {
            Logs::register('Controller Error', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw new HttpException('Internal server error', 500);
        }
    }

    /**
     * Inicia o roteamento da aplicação.
     * Lê a URI da requisição, busca rotas estáticas ou dinâmicas, valida e executa.
     *
     * @return void
     */
    public static function start(
        ?array $server = null,
        ?array $get = null,
        ?array $post = null,
        ?array $files = null,
        ?array $headers = null,
        ?string $rawContent = null
    ): ContractsResponse {

        self::$middlewareStack = [];

        self::boot();

        $server  = $server  ?? $_SERVER;
        $get     = $get     ?? $_GET;
        $post    = $post    ?? $_POST;
        $files   = $files   ?? $_FILES;
        $headers = $headers ?? self::getRequestHeaders();
        $headers = array_change_key_case($headers, CASE_UPPER);

        [$route, $method, $uri] = self::resolveRequestRoute($server);

        [$routeConfig, $routeParams] = self::matchRoute($method, $route);

        if (!$routeConfig) {
            throw new HttpException('Route not found', 404);
        }

        $params = array_merge($get, $routeParams);

        // INSTANCIAÇÃO
        $request = new HttpRequest($method, $uri, $params, $post, $headers, $files, $server, $rawContent);
        $response = new HttpResponse();
        $input = $request->getParsedBody() ?? [];

        // Validação de Parâmetros
        if (!self::validateParams($params, $routeConfig['fieldsPermitted'])['valid']) {
            Logs::register('Validation Params', ['params' => $params, 'allowed' => $routeConfig['fieldsPermitted']]);
            throw new HttpException('Forbidden', 403);
        }

        // CSRF (Usando $post e $server locais)
        $config = Config::getApplicationConfig()['security']['csrf'];
        if (!($routeConfig['api'] ?? false) && $config['enabled'] && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $tokenName = $config['nameToken'];
            $token =
                $headers['X-CSRF-TOKEN']
                ?? $headers['X-XSRF-TOKEN']
                ?? $post[$tokenName]
                ?? $server['HTTP_X_CSRF_TOKEN']
                ?? $input[$tokenName]
                ?? $headers[$tokenName]
                ?? null;

            if (!$token || !CSRF::isValidToken($token)) {
                Logs::register('CSRF Token Mismatch', ['token' => $token]);
                throw new HttpException('Page Expired', 419);
            }
        }

        $customizeParamsPosts = [
            'GET' => $params,
            'POST' => $post,
            'INPUT' => $input,
            'FILE' => $files,
            'HEADER' => $headers
        ];

        // MIDDLEWARES
        $middlewareResult = self::handleMiddlewares($routeConfig, $request, $response, $customizeParamsPosts);

        if ($middlewareResult instanceof ContractsResponse) {
            return $middlewareResult;
        }

        // CONTROLLER
        $response =  self::dispatchController($routeConfig, $request, $response, $customizeParamsPosts);
        if (!$response instanceof ContractsResponse) {
            throw new \LogicException('Controller must return a valid Response instance.');
        }
        return $response;
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
