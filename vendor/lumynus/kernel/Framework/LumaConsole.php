<?php

declare(strict_types=1);

/**
 * @author Weleny Santos <welenysantos@gmail.com>
 * @package Lumynus\Framework
 */

namespace Lumynus\Framework;

use Lumynus\Framework\LumaClasses;
use Lumynus\Framework\Config;

class LumaConsole extends LumaClasses
{

    /**
     * Executa o console com os argumentos fornecidos
     *
     * @param array $args Argumentos do console
     */
    public static function run(array $args)
    {
        // Remove o nome do script
        unset($args[0]);
        $args = array_values($args); // Reindexa o array para que o índice 0 seja o comando

        // Se não houver comandos, retorna erro
        if (empty($args)) {
            self::help();
            return;
        }

        // Comando e argumentos restantes
        $rawCommand = $args[0];

        // aceita: make:User, make:user, make:user:create
        if (str_contains($rawCommand, ':')) {
            [$command, $subCommand] = explode(':', $rawCommand, 2);

            // reescreve os argumentos
            $commandArgs = array_merge([$subCommand], array_slice($args, 1));
        } else {
            $command = $rawCommand;
            $commandArgs = array_slice($args, 1);
        }


        // Lista de comandos disponíveis
        $commands = [
            # Dados do Sistema
            'help' => 'Mostrar menu de ajuda',
            'status' => 'Mostrar funcionamento do sistema',
            'version' => 'Mostrar versão do aplicativo',
            'info' => 'Mostrar informações do sistema',
            'mode' => 'Mostrar modo atual do aplicativo',
            'server' => 'Iniciar servidor de desenvolvimento',

            # Limpar arquivos
            'clear' => 'Limpar arquivos temporários e cache',

            # Criptografia
            'key' => 'Gerar chave de criptografia',
            'remove_key' => 'Remover chave de criptografia',
            'encrypt' => 'Criptografar dados',
            'decrypt' => 'Descriptografar dados',
            'encrypt_save' => 'Criptografar dados e salvar em arquivo',

            # Criações
            'controller' => 'Criar um novo controlador',
            # Criações
            'command' => 'Criar um novo comando',
            //'model' => 'Criar um novo modelo',
            'inspect' => 'Iniciar o inspector',
            'make' => 'Executar um comando',
            'middleware' => 'Criar um novo middleware',
            'apache_htaccess' => 'Cria um arquivo .htaccess',
            'nginx_conf' => 'Cria um arquivo conf de exemplo'
        ];

        // Verifica se o comando existe
        if (isset($commands[$command])) {
            // Chama o método correspondente passando os argumentos
            self::{$command}($commandArgs);
        } else {

            echo self::errors('invalid');
        }
    }

    /**
     * Exibe mensagens de erro
     *
     * @param string $type Tipo de erro
     * @return string Mensagem de erro formatada
     */
    private static function errors(string $type)
    {

        $values = [

            /**INVALID */
            'invalid' => <<<EOT
        \n\nNo command provided. Use 'help' for available commands.
        (Nenhum comando fornecido. Use 'help' para os comandos disponíveis.)\n\n
EOT


        ];

        return $values[$type] ?? "\n\nUnknown error occurred.\n\n
        (Ocorreu um erro desconhecido.)\n\n";
    }

    /**
     * Exibe o menu de ajuda
     */
    private static function help($data = '')
    {

        // Definindo as cores ANSI para PHP
        $BOLD = "\033[1m";
        $BLUE = "\033[94m";
        $YELLOW = "\033[93m";
        $CYAN = "\033[96m";
        $GREEN = "\033[92m";
        $WHITE = "\033[97m";
        $PURPLE = "\033[95m";
        $RESET = "\033[0m";

        echo PHP_EOL;
        echo PHP_EOL;
        echo $BOLD . $BLUE . "════════════════════════════════════════════" . $RESET . PHP_EOL;
        echo $BOLD . $YELLOW;
        echo "    ██╗     ██╗   ██╗ ███╗   ███╗ ██╗   ██╗ ███╗   ██╗ ██╗   ██╗ ███████╗" . PHP_EOL;
        echo "    ██║     ██║   ██║ ████╗ ████║ ╚██╗ ██╔╝ ████╗  ██║ ██║   ██║ ██╔════╝" . PHP_EOL;
        echo "    ██║     ██║   ██║ ██╔████╔██║  ╚████╔╝  ██╔██╗ ██║ ██║   ██║ ███████╗" . PHP_EOL;
        echo "    ██║     ██║   ██║ ██║╚██╔╝██║   ╚██╔╝   ██║╚██╗██║ ██║   ██║ ╚════██║" . PHP_EOL;
        echo "    ███████╗╚██████╔╝ ██║ ╚═╝ ██║    ██║    ██║ ╚████║ ╚██████╔╝ ███████║" . PHP_EOL;
        echo "    ╚══════╝ ╚═════╝  ╚═╝     ╚═╝    ╚═╝    ╚═╝  ╚═══╝  ╚═════╝  ╚══════╝" . PHP_EOL;
        echo $RESET;
        echo $BOLD . $BLUE . "════════════════════════════════════════════" . $RESET . PHP_EOL;
        echo PHP_EOL;

        echo $BOLD . $PURPLE . "╔═══════════════════════════════════════════════════════════╗" . $RESET . PHP_EOL;
        echo $BOLD . $PURPLE . "║" . $RESET . "  " . $BOLD . $WHITE . "🚀 Luma Console - Lumynus Framework  dev. Weleny Santos" . $RESET . "  " . $BOLD . $PURPLE . "║" . $RESET . PHP_EOL;
        echo $BOLD . $PURPLE . "╚═══════════════════════════════════════════════════════════╝" . $RESET . PHP_EOL;
        echo PHP_EOL;

        echo "Available commands:\n\n";
        echo "  {$CYAN}help{$RESET}               - Show this help menu | Mostrar opções do menu\n";
        echo "  {$CYAN}version{$RESET}            - Show application version | Mostrar versão do aplicativo\n";
        echo "  {$CYAN}status{$RESET}             - Display required system commands and environment status | Exibe o status do ambiente\n";
        echo "  {$CYAN}info{$RESET}               - Show system information | Mostrar informações do sistema\n";
        echo "  {$CYAN}server{$RESET}             - Start development server | Iniciar servidor de desenvolvimento\n";
        echo "  {$CYAN}mode{$RESET}               - Show current application mode | Mostrar modo atual do aplicativo\n";
        echo "  {$CYAN}inspect{$RESET}            - Start inspector | Iniciar o inspector\n";
        echo "  {$CYAN}clear{$RESET}              - Clear temporary files and cache | Limpar arquivos temporários e cache\n";
        echo "  {$CYAN}key{$RESET}                - Generate encryption key | Gerar chave de criptografia\n";
        echo "  {$CYAN}remove_key{$RESET}         - Remove encryption key | Remover chave de criptografia\n";
        echo "  {$CYAN}encrypt{$RESET}            - Encrypt data | Criptografar dados\n";
        echo "  {$CYAN}encrypt_save{$RESET}       - Encrypt data and save | Criptografar dados e salvar\n";
        echo "  {$CYAN}decrypt{$RESET}            - Decrypt data | Descriptografar dados\n";
        echo "  {$CYAN}controller{$RESET}         - Create a new controller | Criar um novo controlador\n";
        echo "  {$CYAN}command{$RESET}            - Create a new Command | Criar um novo comando\n";
        echo "  {$CYAN}make{$RESET}               - Execute a command | Executar um comando\n";
        echo "  {$CYAN}middleware{$RESET}         - Create a new middleware | Criar um novo middleware\n";
        echo "  {$CYAN}apache_htaccess{$RESET}    - Create a .htaccess: /public | Cria .htaccess na /public\n";
        echo "  {$CYAN}nginx_conf{$RESET}         - Creates a sample configuration | Cria um exemplo de configuração\n\n";
    }

    /**
     * Exibe a versão do aplicativo e do PHP
     */
    private static function version($data)
    {
        echo "\n\nLumynus version: " . LumaClasses::VERSION . PHP_EOL;
        echo "PHP version: " . phpversion() . PHP_EOL . PHP_EOL;
    }

    /**
     * Exibe informações do sistema
     */
    private static function info($data)
    {
        // Cores ANSI
        $GREEN = "\033[92m";   // Enabled
        $RED   = "\033[91m";   // Disabled
        $YELLOW = "\033[93m";  // Warnings / Info
        $RESET = "\033[0m";

        // Closure para formatar status
        $formatStatus = function ($condition) use ($GREEN, $RED, $RESET) {
            return $condition ? $GREEN . 'Enabled' . $RESET : $RED . 'Disabled' . $RESET;
        };

        // Cabeçalho
        echo "\n\n{$YELLOW}Lumynus Framework - A simple and lightweight PHP framework{$RESET}\n";
        echo "Developed by Weleny Santos\n";
        echo "Version: " . LumaClasses::VERSION . "\n";
        echo "PHP Version: " . phpversion() . "\n\n";

        // Informações do sistema
        echo "{$YELLOW}## System Information{$RESET}\n";
        echo "PHP SAPI: " . php_sapi_name() . PHP_EOL;
        echo "PHP OS: " . PHP_OS . PHP_EOL;
        echo "PHP Memory Limit: " . ini_get('memory_limit') . PHP_EOL;
        echo "PHP Max Execution Time: " . ini_get('max_execution_time') . " seconds" . PHP_EOL;
        echo "PHP Upload Max Filesize: " . ini_get('upload_max_filesize') . PHP_EOL;
        echo "PHP Post Max Size: " . ini_get('post_max_size') . PHP_EOL;
        echo "PHP Default Charset: " . ini_get('default_charset') . PHP_EOL;
        echo "PHP Display Errors: " . (ini_get('display_errors') ? 'On' : 'Off') . PHP_EOL;
        echo "PHP Error Reporting Level: " . error_reporting() . PHP_EOL;
        echo "PHP Timezone: " . date_default_timezone_get() . PHP_EOL . PHP_EOL;

        // Extensões principais
        echo "{$YELLOW}## Core Extensions{$RESET}\n";
        $extensions = [
            'openssl',
            'curl',
            'json',
            'xml',
            'mbstring',
            'zip',
            'bcmath',
            'tokenizer',
            'session',
            'pcre',
            'reflection',
            'phar',
            'hash',
            'filter',
            'iconv'
        ];
        foreach ($extensions as $ext) {
            echo "PHP " . ucfirst($ext) . " Support: " . $formatStatus(extension_loaded($ext)) . PHP_EOL;
        }

        // Database
        echo "\n{$YELLOW}## Database{$RESET}\n";
        $dbExtensions = ['pdo', 'mysqli', 'pdo_mysql', 'pgsql', 'pdo_pgsql', 'sqlite3', 'pdo_sqlite'];
        foreach ($dbExtensions as $ext) {
            echo "PHP " . strtoupper($ext) . " Support: " . $formatStatus(extension_loaded($ext)) . PHP_EOL;
        }

        // Network
        echo "\n{$YELLOW}## Network{$RESET}\n";
        $networkExtensions = ['ftp', 'sockets', 'soap'];
        foreach ($networkExtensions as $ext) {
            echo "PHP " . ucfirst($ext) . " Support: " . $formatStatus(extension_loaded($ext)) . PHP_EOL;
        }

        // Performance
        echo "\n{$YELLOW}## Performance{$RESET}\n";
        $performanceExtensions = ['Zend OPcache', 'apcu', 'redis', 'memcached'];
        foreach ($performanceExtensions as $ext) {
            echo "PHP " . $ext . " Support: " . $formatStatus(extension_loaded(strtolower(str_replace(' ', '', $ext)))) . PHP_EOL;
        }

        // Development
        echo "\n{$YELLOW}## Development{$RESET}\n";
        echo "PHP Xdebug Support: " . $formatStatus(extension_loaded('xdebug')) . PHP_EOL;

        // Advanced
        echo "\n{$YELLOW}## Advanced{$RESET}\n";
        echo "PHP Swoole Support: " . $formatStatus(extension_loaded('swoole')) . PHP_EOL;
        echo "PHP Calendar Support: " . $formatStatus(extension_loaded('calendar')) . PHP_EOL;
        echo "PHP GD Version: " . (function_exists('gd_info') ? gd_info()['GD Version'] : 'Not Available') . PHP_EOL;
    }

    private static function status($data)
    {
        $GREEN = "\033[92m";   // Enabled
        $RED   = "\033[91m";   // Disabled
        $YELLOW = "\033[93m";  // Warnings / Info
        $RESET = "\033[0m";

        // Closure para formatar status
        $formatStatus = function ($condition) use ($GREEN, $RED, $RESET) {
            return $condition ? $GREEN . 'Enabled' . $RESET : $RED . 'Disabled' . $RESET;
        };

        echo "\n\n{$YELLOW}Lumynus Framework - A simple and lightweight PHP framework{$RESET}\n";
        echo "{$YELLOW}## Core Extensions{$RESET}\n";
        echo "{$RED}All listed extensions are REQUIRED.$RESET\n";
        echo "{$RED}They are essential for the framework to work properly.$RESET\n";
        echo "{$RED}Missing extensions may cause unexpected behavior, errors, or system instability.$RESET\n\n";

        $extensions = [
            'openssl',
            'curl',
            'json',
            'xml',
            'mbstring',
            'zip',
            'bcmath',
            'tokenizer',
            'session',
            'pcre',
            'reflection',
            'phar',
            'hash',
            'filter',
            'iconv'
        ];
        foreach ($extensions as $ext) {
            echo "PHP " . ucfirst($ext) . " Support: " . $formatStatus(extension_loaded($ext)) . PHP_EOL;
        }
    }

    /**
     * Exibe o modo atual do aplicativo (produção ou desenvolvimento)
     */
    private static function mode($data)
    {
        if (empty($data)) {
            echo "\n\nLumynus run: " . (Config::modeProduction() ? 'Production' : 'Development') . PHP_EOL;
            echo "(Lumynus está rodando em modo: " . (Config::modeProduction() ? 'Produção' : 'Desenvolvimento') . ")\n\n";

            echo "To change the mode, use:\n";
            echo "  php luma mode production   (to set Production mode)\n";
            echo "  php luma mode development  (to set Development mode)\n\n";
            return;
        }

        $permissions = ['production', 'development'];
        if (in_array($data[0], $permissions)) {

            $isProduction = $data[0] === 'production';
            Config::setModeProduction($isProduction);

            echo "\n\nApplication mode set to: " . ($isProduction ? 'Production' : 'Development') . PHP_EOL;
            echo "(Modo do aplicativo definido para: " . ($isProduction ? 'Produção' : 'Desenvolvimento') . ")\n\n";
        } else {
            echo "\n\nInvalid mode. Use 'production' or 'development'.\n";
            echo "(Modo inválido. Use 'production' ou 'development'.)\n\n";
            return;
        }
    }

    /**
     * Limpa arquivos temporários e cache
     *
     * @param array $data Tipos de arquivos a serem limpos (cache, logs)
     */
    private static function clear(array $data)
    {
        $paths = [
            'cache' => Config::pathProject() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache',
            'logs'  => Config::pathProject() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs',
        ];

        if (empty($data)) {
            $data = ['cache', 'logs'];
        }

        foreach ($data as $type) {
            if (!isset($paths[$type])) {
                echo "\n\n(Unknown type: {$type})\n";
                echo "(Tipo desconhecido: {$type})\n\n";
                continue;
            }

            $dir = $paths[$type];

            if (!is_dir($dir)) {
                echo "\n\n({$type} directory does not exist)\n";
                echo "({$type} diretório não existe)\n\n";
                continue;
            }

            $deleted = self::deleteRecursive($dir);

            echo "\n\n({$type} cleared successfully. {$deleted} file(s) deleted)\n";
            echo "({$type} limpo com sucesso. {$deleted} arquivo(s) deletado(s))\n\n";
        }

        echo "\n";
    }

    /**
     * Deleta arquivos e pastas recursivamente
     *
     * @param string $path Caminho do diretório
     * @return int Número de arquivos deletados
     */
    private static function deleteRecursive(string $path): int
    {
        $count = 0;

        if (!is_dir($path)) {
            return 0;
        }

        $items = scandir($path);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $full = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($full)) {

                $count += self::deleteRecursive($full);

                @rmdir($full);
            } else {
                if (@unlink($full)) {
                    $count++;
                } else {
                    echo "\n(Não foi possível deletar {$full})\n";
                    self::fixPermissions($full);
                }
            }
        }

        return $count;
    }

    /**
     * Cria uma nova chave de criptografia
     *
     * @param array $data
     */
    private static function key(array $data)
    {
        $ciano = "\033[96m";
        $reset = "\033[0m";
        if (empty($data)) {
            echo "\n\nPlease provide a key name\n";
            echo "(Por favor, forneça um nome para a chave)\n\n";
            return;
        }

        try {
            Encryption::createKey($data[0]);
            echo "\n\nKey '{$ciano}{$data[0]}{$reset}' created successfully\n";
            echo "(Chave '{$ciano}{$data[0]}{$reset}' criada com sucesso)\n\n";
        } catch (\Exception $e) {
            echo "\n\nCannot create key '{$ciano}{$data[0]}{$reset}'. Check permissions\n";
            echo "(Não foi possível criar a chave '{$ciano}{$data[0]}{$reset}'. Verifique permissões)\n\n";
        }
    }

    private static function remove_key(array $data)
    {
        $ciano = "\033[96m";
        $reset = "\033[0m";
        if (empty($data)) {
            echo "\n\nPlease provide a key name\n";
            echo "(Por favor, forneça um nome para a chave)\n\n";
            return;
        }

        try {
            Encryption::removeKey($data[0]);
            echo "\n\nKey '{$ciano}{$data[0]}{$reset}' removed successfully\n";
            echo "(Chave '{$ciano}{$data[0]}{$reset}' removida com sucesso)\n\n";
        } catch (\Exception $e) {
            echo "\n\nCannot remove key '{$ciano}{$data[0]}{$reset}'. Check permissions\n";
            echo "(Não foi possível remover a chave '{$ciano}{$data[0]}{$reset}'. Verifique permissões)\n\n";
        }
    }

    /**
     * Encrypts data using the specified key
     *
     * @param array $data
     */
    private static function encrypt($data)
    {
        $ciano = "\033[96m";
        $reset = "\033[0m";
        $roxo = "\033[95m";

        if (empty($data) || self::l_countStatic($data) < 2) {
            echo "\n\nPlease provide a key name and data to encrypt\n";
            echo "(Por favor, forneça um nome de chave e dados para criptografar)\nExample: php luma encrypt {$roxo}key_name{$roxo} '{$ciano}data{$reset}'\n\n";
            return;
        }

        try {

            $keyName = $data[0];
            $dataToEncrypt = implode(' ', array_slice($data, 1));

            $dataSuccess = Encryption::encrypt($dataToEncrypt, $keyName);
            echo "\n\nData encrypted: '{$ciano}{$dataSuccess}{$reset}'\n\n";
        } catch (\Throwable $th) {

            echo "\n\nCannot encrypt data with key '{$keyName}'. Check permissions\n";
            echo "(Não foi possível criptografar os dados com a chave '{$keyName}'. Verifique permissões)\n\n";
        }
    }

    /**
     * Lê arquivos criptografados e retorna os dados descriptografados
     *
     * @param mixed $data Caminho do arquivo ou dados criptografados
     * @param string|null $keyName Nome do arquivo da chave PEM (opcional)
     * @return mixed Dados descriptografados
     * @throws \RuntimeException Se a chave não for encontrada ou se ocorrer um erro ao descriptografar
     */
    private static function decrypt($data)
    {

        $ciano = "\033[96m";
        $roxo = "\033[95m";
        $reset = "\033[0m";
        if (empty($data) || self::l_countStatic($data) < 2) {
            echo "\n\nPlease provide a key name and data to decrypt\n";
            echo "(Por favor, forneça um nome de chave e dados para descriptografar)\nExample: php luma decrypt {$roxo}key_name{$reset} '{$ciano}data{$reset}'\n\n";
            return;
        }

        try {

            $keyName = $data[0];
            $dataToDecrypt = implode(' ', array_slice($data, 1));

            $decryptedData = Encryption::readFiles($dataToDecrypt, $keyName);

            echo "\n\nDecrypted Data: {$ciano}'{$decryptedData}'{$reset}\n\n";
        } catch (\Throwable $th) {

            echo "\n\nCannot decrypt data with key '{$keyName}'. Check permissions\n";
            echo "(Não foi possível descriptografar os dados com a chave '{$keyName}'. Verifique permissões)\n\n";
        }
    }

    private static function encrypt_save($data)
    {
        $ciano = "\033[96m";
        $reset = "\033[0m";
        $roxo = "\033[95m";
        $verde = "\033[92m";

        if (empty($data) || self::l_countStatic($data) < 3) {
            echo "\n\nPlease provide a key name and data to encrypt and save\n";
            echo "(Por favor, forneça um nome de chave e dados para criptografar e salvar)\nExample: php luma encrypt_save {$verde}key_name{$reset} '{$ciano}data{$reset}' {$roxo}nameFile{$reset}\n\n";
            return;
        }

        $keyName = $data[0];
        $dataToEncrypt = implode(' ', array_slice($data, 1, -1));
        $nameFile = array_pop($data);

        try {

            $result = Encryption::saveToFile($nameFile, $dataToEncrypt, $keyName);
            if ($result === false) {
                echo "\n\nCannot encrypt and save data with key '{$keyName}'. Check permissions\n";
                echo "(Não foi possível criptografar e salvar os dados com a chave '{$keyName}'. Verifique permissões)\n\n";
                return;
            }
            echo "\n{$verde}Data encrypted and saved successfully.{$reset}\n\n";

            echo "{$ciano}Key name:{$reset} {$keyName}\n";
            echo "{$ciano}Output file:{$reset} {$nameFile}\n";
            echo "{$ciano}Raw data:{$reset}\n";
            echo $dataToEncrypt . "\n\n";
        } catch (\Throwable $th) {

            echo "\n\nCannot encrypt and save data with key '{$keyName}'. Check permissions\n";
            echo "(Não foi possível criptografar e salvar os dados com a chave '{$keyName}'. Verifique permissões)\n\n";
        }
    }

    private static function server($dados)
    {

        $ciano = "\033[96m";
        $reset = "\033[0m";
        $roxo = "\033[95m";
        $verde = "\033[92m";

        if (Config::modeProduction()) {
            echo "\n\nServer cannot be started in production mode. Switch to development mode to use this command.\n";
            echo "(O servidor não pode ser iniciado no modo de produção. Mude para o modo de desenvolvimento para usar este comando.)\n\n";
            return;
        }

        if (empty($dados) && self::l_countStatic($dados) > 1) {
            echo "\n\nPlease provide a door you wish to serve.\n";
            echo "(Por favor, forneça uma porta que deseja servir)\nExample: php luma {$verde}server{$reset} {$ciano}8000{$reset}\n\n";
            return;
        }

        $caminho = Config::pathProject() . DIRECTORY_SEPARATOR . Config::getApplicationConfig()['path']['public'];
        $caminho = preg_replace('#[\/\\\\]+#', DIRECTORY_SEPARATOR, $caminho);

        $index = array_search('--host', $dados);
        $hostname = $index !== false ? gethostbyname(php_uname('n')) : 'localhost';
        $dadosLimpos = array_values(array_filter($dados, fn($v) => $v !== '--host'));
        $porta = $dadosLimpos[0] ?? '8000';

        shell_exec('php -S ' . $hostname . ':' . $porta . ' -t ' . $caminho);
    }

    private static function inspect($dados)
    {

        $ciano = "\033[96m";
        $reset = "\033[0m";
        $roxo = "\033[95m";
        $verde = "\033[92m";

        if (Config::modeProduction()) {
            echo "\n\nServer cannot be started in production mode. Switch to development mode to use this command.\n";
            echo "(O servidor não pode ser iniciado no modo de produção. Mude para o modo de desenvolvimento para usar este comando.)\n\n";
            return;
        }

        if (empty($dados) && self::l_countStatic($dados) > 1) {
            echo "\n\nPlease provide a door you wish to serve Inspector.\n";
            echo "(Por favor, forneça uma porta que deseja servir)\nExample: php luma {$verde}inspect{$reset} {$ciano}8759{$reset}\n\n";
            return;
        }

        $caminho = Config::pathProject() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'inspector' . DIRECTORY_SEPARATOR;
        $caminho = preg_replace('#[\/\\\\]+#', DIRECTORY_SEPARATOR, $caminho);


        shell_exec('php -S localhost:' . ($dados[0] ?? '8759') . ' -t ' . $caminho);
    }


    private static function controller($dados)
    {
        $input = trim($dados[0], '\\');
        $parts = explode('\\', $input);
        $className = array_pop($parts);

        $subPath = implode(DIRECTORY_SEPARATOR, $parts);

        $namespace = 'App\\Controllers';
        if (!empty($parts)) {
            $namespace .= '\\' . implode('\\', $parts);
        }

        $basePath = Config::pathProject()
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . 'Controllers';

        if (!empty($subPath)) {
            $basePath .= DIRECTORY_SEPARATOR . $subPath;
        }

        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $file = $basePath . DIRECTORY_SEPARATOR . $className . '.php';

        $result = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Lumynus\Framework\AbstractController;
use Lumynus\Http\Contracts\Request;
use Lumynus\Http\Contracts\Response;

class {$className} extends AbstractController
{
    public function index(Request \$req, Response \$res): Response
    {
       return \$res->status(200)->json([
            'success' => true,
            'message_pt' => 'Controller executado com sucesso',
            'message_en' => 'Controller executed successfully'
        ]);
    }
}
PHP;

        if (file_exists($file)) {
            echo "Arquivo já existe, não criado.\n";
            echo "File already exists, not created.\n\n";
            return;
        }

        $bytes = @file_put_contents($file, $result);

        if ($bytes === false) {
            echo "Não foi possível criar o arquivo.\n";
            echo "File could not be created.\n\n";
            return;
        }

        echo "Arquivo criado com sucesso: {$file}\n";
        echo "File successfully created: {$file}\n\n";
    }


    private static function middleware($dados)
    {
        $input = trim($dados[0], '\\');

        $parts = explode('\\', $input);

        $className = array_pop($parts);

        $subPath = implode(DIRECTORY_SEPARATOR, $parts);

        $namespace = 'App\\Middlewares';
        if (!empty($parts)) {
            $namespace .= '\\' . implode('\\', $parts);
        }

        $basePath = Config::pathProject()
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . 'Middlewares';

        if (!empty($subPath)) {
            $basePath .= DIRECTORY_SEPARATOR . $subPath;
        }

        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $file = $basePath . DIRECTORY_SEPARATOR . $className . '.php';

        $result = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Lumynus\Framework\AbstractMiddleware;
use Lumynus\Http\Contracts\Request;
use Lumynus\Http\Contracts\Response;

class {$className} extends AbstractMiddleware
{
    public function handle(Request \$req, Response \$res)
    {
        if (!isset(\$req->getHeaders()['token'])) {
            return false;
            // PT: Interrompe o fluxo; Controller não é executado
            // EN: Stops the flow; Controller will not be executed
        }

        // PT: Cria um atributo acessível no Controller
        // EN: Creates an attribute accessible by the Controller
        \$req->setAttribute('testado', 'ok');
    }
}
PHP;

        if (file_exists($file)) {
            echo "Arquivo já existe, não criado.\n";
            echo "File already exists, not created.\n\n";
            return;
        }

        $bytes = @file_put_contents($file, $result);

        if ($bytes === false) {
            echo "Não foi possível criar o arquivo.\n";
            echo "File could not be created.\n\n";
            return;
        }

        echo "Arquivo criado com sucesso: {$file}\n";
        echo "File successfully created: {$file}\n\n";
    }


    private static function command($dados)
    {
        $input = trim($dados[0], '\\');
        $parts = explode('\\', $input);
        $className = array_pop($parts);

        $subPath = implode(DIRECTORY_SEPARATOR, $parts);

        $namespace = 'App\\Commands';
        if (!empty($parts)) {
            $namespace .= '\\' . implode('\\', $parts);
        }

        $basePath = Config::pathProject()
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . 'Commands';

        if (!empty($subPath)) {
            $basePath .= DIRECTORY_SEPARATOR . $subPath;
        }

        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $file = $basePath . DIRECTORY_SEPARATOR . $className . '.php';

        $result = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Lumynus\Console\Contracts\Terminal;
use Lumynus\Console\Contracts\Output;
use Lumynus\Framework\AbstractCommand;

class {$className} extends AbstractCommand
{
    // 1 - Forma com contratos / Contract-based usage
    public function handle(Terminal \$terminal, Output \$output)
    {
        // PT: Obtém todos os argumentos digitados
        // EN: Get all typed arguments
        \$dados = \$terminal->getAll();

        // PT: Resposta de sucesso
        // EN: Success response
        \$output->success('Sucesso para: ' . \$dados[0]);
    }

    // 2 - Forma simples / Simple usage
    public function handle2(\$dados)
    {
        \$this->output()->success('Sucesso para: ' . \$dados[0]);
    }
}
PHP;

        if (file_exists($file)) {
            echo "Arquivo já existe, não criado.\n";
            echo "File already exists, not created.\n\n";
            return;
        }

        $bytes = @file_put_contents($file, $result);

        if ($bytes === false) {
            echo "Não foi possível criar o arquivo.\n";
            echo "File could not be created.\n\n";
            return;
        }

        echo "Arquivo criado com sucesso: {$file}\n";
        echo "File successfully created: {$file}\n\n";
    }


    private static function make($dados)
    {

        if (self::l_countStatic($dados) < 2) {

            echo "\n";
            echo "Invalid command usage.\n";
            echo "At minimum, you must provide: command, method and values (if required).\n";
            echo "\nUso inválido do comando.\n";
            echo "São necessários, no mínimo: comando, método e valores (se necessário).\n\n";

            echo "Example / Exemplo:\n";
            echo "  luma make:User create admin\n\n";

            return;
        }

        try {
            CommandDispatcher::boot($dados);
        } catch (\Throwable $th) {

            Logs::register('Terminal error', $th->getMessage());

            echo "\nAn error occurred while trying to execute; please verify that the data entered matches a command.\n";
            echo "(Ocorreu um erro ao tentar executar, verifique os dados digitados correspondem a um comando.)\n\n";
            return;
        }
    }

    private static function apache_htaccess()
    {

        $htaccess = <<<'EOL'

                        # =========================================================
                # Proteção ULTRA-ESPECÍFICA - Bloqueia apenas ataques reais
                # =========================================================

                <IfModule mod_rewrite.c>
                    RewriteEngine On

                    # =========================================================
                    # Redirect all requests to index.php
                    # =========================================================
                    RewriteCond %{REQUEST_FILENAME} !-f
                    RewriteCond %{REQUEST_FILENAME} !-d
                    RewriteRule ^(.*)$ index.php?$1 [QSA,L]

                    # =========================================================
                    # PROTEÇÃO ULTRA-ESPECÍFICA
                    # Só bloqueia quando há CERTEZA de ataque
                    # =========================================================

                    # 1. Null bytes - SEMPRE malicioso
                    RewriteCond %{QUERY_STRING} (%00|\x00) [NC,OR]

                    # 2. Directory traversal extremo (3+ níveis)
                    RewriteCond %{QUERY_STRING} (\.\.\/.*\.\.\/.*\.\.\/) [NC,OR]

                    # 3. SQL Injection com múltiplos comandos PERIGOSOS
                    RewriteCond %{QUERY_STRING} (;\s*(drop\s+table|truncate\s+table|delete\s+from.*where.*=|update.*set.*where.*=)) [NC,OR]

                    # 4. UNION SELECT com FROM (ataque real)
                    RewriteCond %{QUERY_STRING} (union\s+select.*from) [NC,OR]

                    # 5. XSS com javascript ativo
                    RewriteCond %{QUERY_STRING} (\<script[^>]*javascript:|javascript:\s*[^;]*[;(]|data:text/html.*script) [NC,OR]

                    # 6. PHP code execution
                    RewriteCond %{QUERY_STRING} (eval\s*\(\s*base64_decode|exec\s*\(\s*[\"\']|system\s*\(\s*[\"\']) [NC,OR]

                    # 7. File inclusion perigoso
                    RewriteCond %{QUERY_STRING} (php://input|php://filter.*convert|file://.*etc/passwd) [NC,OR]

                    # 8. Command injection óbvio
                    RewriteCond %{QUERY_STRING} (;\s*cat\s+/etc/|;\s*ls\s+-la|;\s*wget\s+http|;\s*curl\s+http) [NC]

                    RewriteRule ^ - [F,L]

                    # =========================================================
                    # WHITELIST PARA DESENVOLVIMENTO (descomente se necessário)
                    # =========================================================
                    # RewriteCond %{REQUEST_URI} ^/(admin|editor|docs)/ [NC]
                    # RewriteRule ^ - [S=20]  # Pula as regras de segurança

                    # =========================================================
                    # LOG DE ATAQUES BLOQUEADOS (opcional)
                    # =========================================================
                    # RewriteCond %{QUERY_STRING} (%00|\x00|union\s+select.*from) [NC]
                    # RewriteRule ^ - [E=blocked:1]
                    # CustomLog /var/log/apache2/blocked_attacks.log "%h %t \"%r\" \"%{QUERY_STRING}e\"" env=blocked

                </IfModule>

                # =========================================================
                # Configurações básicas
                # =========================================================
                Options -Indexes
                DirectoryIndex index.php

                # =========================================================
                # Protect sensitive files including .luma, .pem, .ini
                # Protege arquivos sensíveis
                # =========================================================
                <FilesMatch "(\.env|\.htaccess|composer\.json|composer\.lock|config\.php|README\.md|package\.json|\.luma|\.pem|\.ini|\.log|\.bak|\.sql)$">
                    Require all denied
                </FilesMatch>

                # =========================================================
                # Prevent direct access to PHP files except index.php
                # Evita execução direta de arquivos PHP exceto index.php
                # =========================================================
                <Files "*.php">
                    Require all denied
                </Files>
                <Files "index.php">
                    Require all granted
                </Files>

                # =========================================================
                # Security headers
                # Cabeçalhos de segurança
                # =========================================================
                <IfModule mod_headers.c>
                    # Enable X-Frame-Options
                    # Habilita X-Frame-Options
                    Header always set X-Frame-Options "SAMEORIGIN"

                    # Enable X-Content-Type-Options
                    # Habilita X-Content-Type-Options
                    Header set X-Content-Type-Options "nosniff"

                    # Enable Referrer-Policy
                    # Habilita Referrer-Policy
                    Header set Referrer-Policy "strict-origin-when-cross-origin"

                    # Enable Strict-Transport-Security (HSTS) for HTTPS
                    # Habilita HSTS para HTTPS
                    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" env=HTTPS

                    # Enable Content-Security-Policy (basic but functional)
                    # Habilita Content-Security-Policy básica mas funcional
                    Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'self'"

                    # Enable Permissions-Policy (successor to Feature-Policy)
                    # Habilita Permissions-Policy
                    Header set Permissions-Policy "geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), accelerometer=(), gyroscope=()"

                    # Optional: Force cookies HttpOnly, Secure and SameSite=Strict
                    # Opcional: Força cookies HttpOnly, Secure e SameSite=Strict
                    # Header edit Set-Cookie ^(.*)$ $1;HttpOnly;Secure;SameSite=Strict
                </IfModule>

                # =========================================================
                # Enable Gzip compression
                # Habilita compressão Gzip
                # =========================================================
                <IfModule mod_deflate.c>
                    # Compress HTML, CSS, JavaScript, Text, XML, Fonts
                    AddOutputFilterByType DEFLATE text/plain text/html text/xml text/css text/javascript
                    AddOutputFilterByType DEFLATE application/javascript application/json application/xml
                    AddOutputFilterByType DEFLATE application/xhtml+xml application/rss+xml application/atom+xml
                    AddOutputFilterByType DEFLATE application/font-woff application/font-woff2 font/woff font/woff2
                    AddOutputFilterByType DEFLATE image/svg+xml

                    # Don't compress images, videos, or already compressed files
                    # Não comprimir imagens, vídeos ou arquivos já comprimidos
                    SetEnvIfNoCase Request_URI \.(?:gif|jpe?g|png|zip|gz|bz2|sit|rar|pdf|mov|avi|mp3|mp4|rm)$ no-gzip dont-vary
                </IfModule>

                # =========================================================
                # Browser caching for static files
                # Cache do navegador para arquivos estáticos (1 mês para todos)
                # =========================================================
                <IfModule mod_expires.c>
                    ExpiresActive On
                    ExpiresByType text/css "access plus 1 month"
                    ExpiresByType application/javascript "access plus 1 month"
                    ExpiresByType image/png "access plus 1 month"
                    ExpiresByType image/jpg "access plus 1 month"
                    ExpiresByType image/jpeg "access plus 1 month"
                    ExpiresByType image/gif "access plus 1 month"
                    ExpiresByType image/svg+xml "access plus 1 month"
                    ExpiresByType application/font-woff "access plus 1 month"
                    ExpiresByType application/font-woff2 "access plus 1 month"
                    ExpiresByType font/woff "access plus 1 month"
                    ExpiresByType font/woff2 "access plus 1 month"
                    ExpiresByType application/pdf "access plus 1 month"
                    ExpiresByType image/ico "access plus 1 month"
                    ExpiresByType image/x-icon "access plus 1 month"
                </IfModule>

                # =========================================================
                # Optional: Prevent hotlinking (images, PDFs, etc.)
                # Opcional: Bloqueio de hotlinking para imagens e arquivos
                # =========================================================
                # <IfModule mod_rewrite.c>
                #     RewriteCond %{HTTP_REFERER} !^$
                #     RewriteCond %{HTTP_REFERER} !^https?://(www\.)?seudominio\.com/ [NC]
                #     RewriteRule \.(jpg|jpeg|png|gif|pdf|css|js)$ - [F,NC,L]
                # </IfModule>

                # =========================================================
                # End of file
                # Fim do arquivo
                # =========================================================

        EOL;

        $file = Config::pathProject() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . '.htaccess';
        if (file_exists($file)) {
            echo "\nFile exists, not created\n";
            echo "(Arquivo existe, não criado.)\n\n";
            return;
        }

        try {
            $bytes = @file_put_contents($file, $htaccess);

            if ($bytes === false) {
                echo "\nFile could not be created\n";
                echo "(Não foi possível criar o arquivo)\n\n";
                return;
            }

            echo "\nFile successfully created: {$file}\n";
            echo "(Arquivo criado com sucesso: {$file})\n\n";
        } catch (\Throwable $e) {
            self::fixPermissions($file);
        }
    }


    private static function nginx_conf()
    {

        $project_path = Config::pathProject();

        $domain = Config::getApplicationConfig()['App']['host'] ?? 'www.example.com';

        $nginx_config = <<<'EOL'

            # =========================================================
            # Lumynus Framework - Nginx Virtual Host Configuration
            # =========================================================
            # INSTRUÇÕES DE INSTALAÇÃO:
            # 1. Copie este arquivo para: /etc/nginx/sites-available/lumynus
            # 2. Execute: sudo ln -s /etc/nginx/sites-available/lumynus /etc/nginx/sites-enabled/
            # 3. Execute: sudo nginx -t && sudo systemctl reload nginx
            # =========================================================

            server {
                listen 80;
                listen [::]:80;
                server_name {$domain} www.{$domain};

                # Caminho para a pasta public do projeto
                root {$project_path}/public;
                index index.php;

                # =========================================================
                # Security headers
                # Cabeçalhos de segurança
                # =========================================================
                add_header X-Frame-Options "SAMEORIGIN" always;
                add_header X-Content-Type-Options "nosniff" always;
                add_header Referrer-Policy "strict-origin-when-cross-origin" always;

                # Content Security Policy (básica mas funcional)
                add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'self'" always;

                # Permissions Policy
                add_header Permissions-Policy "geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), accelerometer=(), gyroscope=()" always;

                # =========================================================
                # PROTEÇÃO ULTRA-ESPECÍFICA
                # Só bloqueia quando há CERTEZA de ataque
                # =========================================================

                # 1. Null bytes - SEMPRE malicioso
                if (\$query_string ~ "(%00|\\x00)") {
                    return 403;
                }

                # 2. Directory traversal extremo (3+ níveis)
                if (\$query_string ~ "(\\.\\.\/.*\\.\\.\/.*\\.\\.\/)") {
                    return 403;
                }

                # 3. SQL Injection com múltiplos comandos PERIGOSOS
                if (\$query_string ~* "(;\\s*(drop\\s+table|truncate\\s+table|delete\\s+from.*where.*=|update.*set.*where.*=))") {
                    return 403;
                }

                # 4. UNION SELECT com FROM (ataque real)
                if (\$query_string ~* "(union\\s+select.*from)") {
                    return 403;
                }

                # 5. XSS com javascript ativo
                if (\$query_string ~* "(<script[^>]*javascript:|javascript:\\s*[^;]*[;(]|data:text/html.*script)") {
                    return 403;
                }

                # 6. PHP code execution
                if (\$query_string ~* "(eval\\s*\\(\\s*base64_decode|exec\\s*\\(\\s*[\"']|system\\s*\\(\\s*[\"'])") {
                    return 403;
                }

                # 7. File inclusion perigoso
                if (\$query_string ~* "(php://input|php://filter.*convert|file://.*etc/passwd)") {
                    return 403;
                }

                # 8. Command injection óbvio
                if (\$query_string ~* "(;\\s*cat\\s+/etc/|;\\s*ls\\s+-la|;\\s*wget\\s+http|;\\s*curl\\s+http)") {
                    return 403;
                }

                # =========================================================
                # Protect sensitive files including .luma, .pem, .ini
                # Protege arquivos sensíveis
                # =========================================================
                location ~* \\.(env|htaccess|json|lock|md|luma|pem|ini|log|bak|sql)\$ {
                    deny all;
                    return 404;
                }

                # Protect composer files
                location ~* ^/(composer\\.(json|lock))\$ {
                    deny all;
                    return 404;
                }

                # Protect config files
                location ~* ^/(config\\.php|README\\.md|package\\.json)\$ {
                    deny all;
                    return 404;
                }

                # =========================================================
                # Prevent direct access to PHP files except index.php
                # Evita execução direta de arquivos PHP exceto index.php
                # =========================================================
                location ~* ^(?!.*/index\\.php\$).+\\.php\$ {
                    deny all;
                    return 404;
                }

                # =========================================================
                # Browser caching for static files (1 mês para todos)
                # Cache do navegador para arquivos estáticos
                # =========================================================
                location ~* \\.(css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot|ico|pdf)\$ {
                    expires 1M;
                    add_header Cache-Control "public, immutable";
                    add_header X-Content-Type-Options "nosniff" always;

                    # Opcional: Prevent hotlinking
                    # valid_referers none blocked server_names ~\\.seudominio\\.com;
                    # if (\$invalid_referer) {
                    #     return 403;
                    # }
                }

                # =========================================================
                # Gzip compression
                # Compressão Gzip
                # =========================================================
                gzip on;
                gzip_vary on;
                gzip_min_length 1024;
                gzip_proxied any;
                gzip_comp_level 6;
                gzip_types
                    text/plain
                    text/css
                    text/xml
                    text/javascript
                    application/javascript
                    application/xml+rss
                    application/json
                    application/xml
                    application/xhtml+xml
                    application/atom+xml
                    image/svg+xml
                    application/font-woff
                    application/font-woff2
                    font/woff
                    font/woff2;

                # =========================================================
                # Main location block - redirect all to index.php
                # Bloco principal - redireciona tudo para index.php
                # =========================================================
                location / {
                    try_files \$uri \$uri/ /index.php?\$query_string;
                }

                # =========================================================
                # PHP-FPM configuration
                # Configuração do PHP-FPM
                # =========================================================
                location ~ \\.php\$ {
                    try_files \$uri =404;
                    fastcgi_split_path_info ^(.+\\.php)(/.+)\$;
                    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;  # Ajuste a versão do PHP
                    fastcgi_index index.php;
                    fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
                    include fastcgi_params;

                    # Timeout settings
                    fastcgi_connect_timeout 60s;
                    fastcgi_send_timeout 60s;
                    fastcgi_read_timeout 60s;

                    # Buffer settings
                    fastcgi_buffer_size 128k;
                    fastcgi_buffers 256 16k;
                    fastcgi_busy_buffers_size 256k;
                    fastcgi_temp_file_write_size 256k;
                }

                # =========================================================
                # Deny access to hidden files and directories
                # Nega acesso a arquivos e diretórios ocultos
                # =========================================================
                location ~ /\\. {
                    deny all;
                    return 404;
                }

                # =========================================================
                # Optional: Block common exploit attempts
                # Opcional: Bloquear tentativas de exploit comuns
                # =========================================================
                location ~* (wp-admin|wp-login|xmlrpc\\.php|wp-content) {
                    return 404;
                }

                # =========================================================
                # Rate limiting (opcional - descomente para ativar)
                # =========================================================
                # limit_req_zone \$binary_remote_addr zone=login:10m rate=1r/s;
                # location /login {
                #     limit_req zone=login burst=5 nodelay;
                #     try_files \$uri \$uri/ /index.php?\$query_string;
                # }
            }

            # =========================================================
            # HTTPS Configuration (descomente para produção)
            # =========================================================
            # server {
            #     listen 443 ssl http2;
            #     listen [::]:443 ssl http2;
            #     server_name seudominio.com www.seudominio.com;
            #
            #     root /var/www/html/public;
            #     index index.php;
            #
            #     # SSL Configuration
            #     ssl_certificate /path/to/certificate.crt;
            #     ssl_certificate_key /path/to/private.key;
            #     ssl_protocols TLSv1.2 TLSv1.3;
            #     ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
            #     ssl_prefer_server_ciphers off;
            #     ssl_session_cache shared:SSL:10m;
            #     ssl_session_timeout 10m;
            #
            #     # Include all the location blocks from above here
            #     # (copie todos os blocos location de cima)
            # }

            # =========================================================
            # End of file
            # Fim do arquivo
            # =========================================================

    EOL;

        $file = Config::pathProject() . DIRECTORY_SEPARATOR . 'nginx.conf';
        if (file_exists($file)) {
            echo "\\nNginx config file exists, not created\\n";
            echo "(Arquivo de configuração do Nginx existe, não criado.)\\n\\n";
            return;
        }

        try {
            $bytes = @file_put_contents($file, $nginx_config);

            if ($bytes === false) {
                echo "\\nNginx config file could not be created\\n";
                echo "(Não foi possível criar o arquivo de configuração do Nginx)\\n\\n";
                return;
            }

            echo "\n=== Nginx Configuration Created ===\n";
            echo "File: {$file}\n\n";

            echo "== English ==\n";
            echo "IMPORTANT: Copy this file to your Nginx sites-available directory\n";
            echo "Example:\n";
            echo "  sudo cp nginx.conf /etc/nginx/sites-available/lumynus\n";
            echo "Then create a symbolic link:\n";
            echo "  sudo ln -s /etc/nginx/sites-available/lumynus /etc/nginx/sites-enabled/\n";
            echo "Test and reload Nginx:\n";
            echo "  sudo nginx -t && sudo systemctl reload nginx\n\n";

            echo "== Português ==\n";
            echo "IMPORTANTE: Copie este arquivo para o diretório sites-available do Nginx\n";
            echo "Exemplo:\n";
            echo "  sudo cp nginx.conf /etc/nginx/sites-available/lumynus\n";
            echo "Em seguida, crie um link simbólico:\n";
            echo "  sudo ln -s /etc/nginx/sites-available/lumynus /etc/nginx/sites-enabled/\n";
            echo "Teste e recarregue o Nginx:\n";
            echo "  sudo nginx -t && sudo systemctl reload nginx\n\n";
        } catch (\Throwable $e) {
            self::fixPermissions($file);
        }
    }


    /**
     * Diagnostica problemas de permissão e propriedade
     *
     * @param array $data
     */
    private static function fixPermissions(string $path)
    {
        $os = PHP_OS_FAMILY;

        echo "\n================ ENGLISH =================\n";

        switch ($os) {
            case 'Linux':
            case 'Darwin': // Mac
                echo "You are running on {$os}. Please review the file and directory permissions for the path: {$path}.\n";
                echo "If you encounter permission issues, run the command with elevated privileges:\n";
                echo "    sudo php luma comando\n";
                break;

            case 'Windows':
                echo "You are running on Windows. Please make sure that your user account has read and write access to the path: {$path}.\n";
                echo "If you encounter permission issues, run the command as Administrator:\n";
                echo "    php luma comando\n";
                break;

            default:
                echo "Operating system not recognized. Please confirm the access rights for the path: {$path}.\n";
                echo "If you encounter issues, try running the command with administrator privileges.\n";
                break;
        }

        echo "\n================ PORTUGUÊS =================\n";

        switch ($os) {
            case 'Linux':
            case 'Darwin': // Mac
                echo "Você está executando no {$os}. Por favor, revise manualmente as permissões de arquivos e pastas no caminho: {$path}.\n";
                echo "Se encontrar problemas de permissão, execute o comando com privilégios elevados:\n";
                echo "    sudo php luma comando\n";
                break;

            case 'Windows':
                echo "Você está executando no Windows. Verifique se a sua conta de usuário possui permissões de leitura e escrita para o caminho: {$path}.\n";
                echo "Se encontrar problemas de permissão, execute o comando como Administrador:\n";
                echo "    php luma comando\n";
                break;

            default:
                echo "Sistema operacional não reconhecido. Confirme manualmente as permissões de acesso para o caminho: {$path}.\n";
                echo "Se encontrar problemas, tente executar o comando com privilégios de administrador.\n";
                break;
        }

        echo "\n===========================================\n";
    }
}
