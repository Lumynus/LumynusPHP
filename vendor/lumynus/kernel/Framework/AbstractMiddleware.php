<?php

declare(strict_types=1);

namespace Lumynus\Framework;

use Lumynus\Framework\Luma;
use Lumynus\Framework\Sessions;
use Lumynus\Http\HttpResponse;
use Lumynus\Framework\Sanitizer;
use Lumynus\Framework\Converts;
use Lumynus\Framework\LumaClasses;
use Lumynus\Framework\LumaHTTP;
use Lumynus\Framework\HttpClient;
use Lumynus\Framework\CORS;
use Lumynus\Framework\Requirements;
use Lumynus\Framework\Regex;
use Lumynus\Framework\Encryption;
use Lumynus\Framework\Validate;
use Lumynus\Framework\Logs;
use Lumynus\Framework\Cookies;
use Lumynus\Framework\QueueManager;
use Lumynus\Framework\CSRF;
use Lumynus\Framework\Memory;
use Lumynus\Framework\Resolver;
use Lumynus\Http\Contracts\Request;
use Lumynus\Http\Contracts\Response;
use Lumynus\Framework\LumynusContainer;

abstract class AbstractMiddleware extends LumaClasses
{

    use Requirements;

    /**
     * Método para renderizar uma view com dados.
     *
     * @param string $view Nome da view a ser renderizada.
     * @param array $data Dados a serem passados para a view.
     * @param bool $regenerateCSRF Informa se deseja regernar o CSRF na view
     * @return string Retorna o conteúdo renderizado da view.
     */
    public function renderView(string $view, array $data = [], bool $regenerateCSRF = true): string
    {
        return Luma::render($view, $data, $regenerateCSRF);
    }


    /**
     * Método para obter a instância da classe Sessions.
     * @param array $userOptions Opções personalizadas para a sessão.
     * @return Sessions Retorna uma nova instância da classe Sessions.
     * @throws \Exception Se a sessão não puder ser iniciada.
     */
    public function sessions(array $userOptions = []): Sessions
    {
        $key = 'sessions_' . md5(json_encode($userOptions));
        return $this->makeInstance(Sessions::class, [$userOptions], $key);
    }

    /**
     * Método para obter a instância da classe Cookie.
     * @return Cookies Retorna uma nova instância da classe Cookie.
     */
    public function cookies(): Cookies
    {
        return $this->makeInstance(Cookies::class);
    }

    /**
     * Método para obter a instância da classe Validate.
     * @return Validate Retorna uma nova instância da classe Validate.
     */
    public function validate(): Validate
    {
        return $this->makeInstance(Validate::class);
    }

    /**
     * Método para obter a instância da classe Response.
     * @return HttpResponse Retorna uma nova instância da classe Response.
     */
    public function response(): HttpResponse
    {
        return new HttpResponse();
    }

    /**
     * Método para obter a instância da classe Sanitizer.
     * @return Sanitizer Retorna uma nova instância da classe Sanitizer.
     */
    public function sanitizer(): Sanitizer
    {
        return new Sanitizer();
    }

    /**
     * Método para obter a instância da classe Converts.
     * @return Converts Retorna uma nova instância da classe Converts.
     */
    public function converter(): Converts
    {
        return $this->makeInstance(Converts::class);
    }

    /**
     * Método para obter a instância da classe Logs.
     * @return Logs Retorna uma nova instância da classe Logs.
     */
    public function logs(): Logs
    {
        return $this->makeInstance(Logs::class);
    }

    /**
     * Método para obter a instância da classe LumaHTTP.
     * @return LumaHTTP Retorna uma nova instância da classe LumaHTTP.
     */
    public function lumaHTTP(): LumaHTTP
    {
        return $this->makeInstance(LumaHTTP::class);
    }

    /**
     * Método para obter a instância da classe HttpClient.
     * @return HttpClient Retorna uma nova instância da classe HttpClient.
     */
    public function httpClient(): HttpClient
    {
        return $this->makeInstance(HttpClient::class);
    }

    /**
     * Método para obter a instância da classe CORS.
     * @return CORS Retorna uma nova instância da classe CORS.
     */
    public function cors(): CORS
    {
        return $this->makeInstance(CORS::class);
    }

    /**
     * Método para obter a instância da classe Regex.
     * @return Regex Retorna uma nova instância da classe Regex.
     */
    public function regex(): Regex
    {
        return $this->makeInstance(Regex::class);
    }

    /**
     * Método para obter a instância da classe Encryption
     * @return Encryption Retorna uma nova instância da classe Encryption
     */
    public function encryption(): Encryption
    {
        return $this->makeInstance(Encryption::class);
    }

    /**
     * Método para obter a instância da classe QueueManager
     * @return QueueManager Retorna uma nova instância da classe QueueManager
     */
    public function queue(): QueueManager
    {
        return $this->makeInstance(QueueManager::class);
    }

    /**
     * Método para obter a instância da classe CSRF
     * @return CSRF Retorna uma nova instância da classe CSRF
     */
    public function csrf(): CSRF
    {
        return $this->makeInstance(CSRF::class);
    }

    /**
     * Método para obter a instância da classe Memory
     * @return Memory Retorna uma nova instância da classe Memory
     */
    public function memory(): Memory
    {
        return $this->makeInstance(Memory::class);
    }

    /**
     * Método para obter a instância da classe Resolver
     * @return Resolver Retorna uma nova instância da classe Resolver
     */
    public function resolver(): Resolver
    {
        return $this->makeInstance(Resolver::class);
    }

    /**
     * Método para chamar funções em molde estático
     * @return self
     */
    public static function static(mixed ...$args): static
    {
        return new static(...$args);
    }

    /**
     * Analisa a execução do middleware, coletando dados contextuais
     * da requisição e métricas de tempo para fins de observabilidade
     * e auditoria.
     *
     * @param Request $req Instância da requisição HTTP analisada.
     *
     * @return void
     */
    public function analyze(Request $req): void
    {
        $now = microtime(true);
        $durationSeconds = $now - $this->LUMA_START;

        $this->logs()->register('Middleware analyze', [
            'middleware'        => static::class,
            'method'            => $req->getMethod(),
            'uri'               => (string) $req->getUri(),
            'query'             => $req->getQueryParams(),
            'headers'           => $req->getHeaders(),
            'body'              => $req->getParsedBody(),
            'attributes'        => $req->getAttributes(),
            'start_time'        => $this->LUMA_START,
            'end_time'          => $now,
            'duration_seconds' => round($durationSeconds, 6),
            'duration_ms'       => round($durationSeconds * 1000, 2),
        ]);
    }

    /**
     * Registra métricas básicas de execução do middleware.
     *
     * Coleta informações essenciais da requisição e o tempo total de
     * processamento desde o início do ciclo do middleware, registrando
     * a duração em milissegundos.
     *
     * Este método é destinado exclusivamente ao uso interno de middlewares,
     * não devendo ser utilizado em controllers ou regras de negócio.
     *
     * @param Request $req
     *        Instância da requisição HTTP utilizada para coleta das métricas.
     *
     * @return void
     */
    public function metrics(Request $req): void
    {
        $seconds = microtime(true) - $this->LUMA_START;

        $this->logs()->register('Middleware metrics', [
            'middleware'        => static::class,
            'method'            => $req->getMethod(),
            'uri'               => (string) $req->getUri(),
            'duration_seconds'  => round($seconds, 6),
            'duration_ms'       => round($seconds * 1000, 2)
        ]);
    }

    /**
     * Interrompe a execução atual do middleware e retorna uma resposta imediata.
     *
     * Este método é utilizado para abortar o fluxo do pipeline de middlewares,
     * gerando uma resposta HTTP formatada conforme o tipo solicitado.
     *
     * - Se a mensagem for um array, o retorno será automaticamente em JSON.
     * - Se for string ou null, será sanitizada e retornada no formato especificado.
     * - Caso o tipo informado seja inválido, será utilizado "html" como padrão.
     *
     * @param string|array|null $message Mensagem ou payload a ser retornado na resposta
     * @param string $type Tipo de resposta desejada: 'text', 'json' ou 'html'
     * @param int $status Código HTTP a ser retornado (padrão: 500)
     *
     * @return Response Objeto de resposta HTTP já formatado
     */
    public function abort(string|array|null $message = null, string $type = 'html', int $status = 500): Response
    {
        $type = in_array($type, ['text', 'json', 'html'], true) ? $type : 'html';

        if (is_array($message)) {
            return $this->response()
                ->status($status)
                ->json($message);
        }

        $message = $this->sanitizer()->string($message ?? 'Internal Server Error');
        return $this->response()->status($status)->{$type}($message);
    }

    /**
     * Converte uma exceção em uma resposta HTTP padronizada.
     *
     * Este método captura informações de um Throwable e as transforma
     * em uma resposta JSON apropriada, utilizando o código da exceção
     * como status HTTP quando válido. Caso o código seja inválido ou
     * inexistente, será utilizado o status 500 por padrão.
     *
     * @param \Throwable $e Exceção a ser convertida em resposta
     *
     * @return Response Objeto de resposta HTTP formatado em JSON
     */
    public function abortException(\Throwable $e): Response
    {
        $status = method_exists($e, 'getCode') && $e->getCode() >= 100
            ? (int) $e->getCode()
            : 500;

        $message = $e->getMessage() ?: 'Internal Server Error';

        return $this->abort($message, 'json', $status);
    }

    /**
     * Método genérico para criar instâncias de classes utilitárias.
     * @param string $class O nome da classe a ser instanciada.
     * @param array $options Opções para o construtor da classe.
     * @param string|null $key Chave opcional para armazenar a instância.
     * @return object Retorna uma nova instância da classe especificada.
     */
    private function makeInstance(string $class, array $options = [], ?string $key = null)
    {
        return LumynusContainer::resolve($class, $options, $key);
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
