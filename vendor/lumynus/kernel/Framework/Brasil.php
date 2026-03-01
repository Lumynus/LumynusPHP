<?php

declare(strict_types=1);

namespace Lumynus\Framework;

use Lumynus\Framework\LumaClasses;

final class Brasil extends LumaClasses
{

    /**
     * Formata um valor numérico para o padrão monetário brasileiro.
     *
     * Converte o número para duas casas decimais, utilizando vírgula como separador
     * decimal e ponto como separador de milhar.
     *
     * @param float|int|null $valor Valor a ser formatado.
     * @return string|null Valor formatado em reais ou null caso a entrada seja null.
     */
    public static function realBrasil(float|int|null $valor): string|null
    {
        if ($valor === null) {
            return null;
        }

        if (is_numeric($valor)) {
            return number_format(round($valor, 2), 2, ',', '.');
        } else {
            return '0,00';
        }
    }

    /**
     * Converte uma string em formato monetário para valor float.
     *
     * Remove caracteres não numéricos, trata separadores de milhar e decimais
     * no padrão brasileiro e retorna o valor numérico correspondente.
     *
     * @param string|null $valor Valor monetário em formato string.
     * @param int $decimais Quantidade de casas decimais para arredondamento.
     * @return float|null Valor convertido para float ou null caso a entrada seja null.
     */
    public static function moedaParaFloat(string|null $valor, int $decimais = 2): float|null
    {
        if ($valor === null) {
            return null;
        }

        $valor = preg_replace('/[^\d.,]/', '', $valor);
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
        return round((float) $valor, $decimais);
    }

    /**
     * Formata uma string de CPF para o padrão brasileiro.
     *
     * Remove caracteres não numéricos e aplica a máscara ###.###.###-##.
     *
     * @param string|null $cpf CPF a ser formatado.
     * @return string|null CPF formatado ou null caso a entrada seja null.
     */
    public static function formatarCPF(string|null $cpf): string|null
    {
        if ($cpf === null) {
            return null;
        }

        return preg_replace("/^(\d{3})(\d{3})(\d{3})(\d{2})$/", "$1.$2.$3-$4", preg_replace('/\D/', '', $cpf));
    }

    /**
     * Formata uma string de CNPJ para o padrão brasileiro.
     *
     * Remove caracteres não numéricos e aplica a máscara ##.###.###/####-##.
     *
     * @param string|null $cnpj CNPJ a ser formatado.
     * @return string|null CNPJ formatado ou null caso a entrada seja null.
     */
    public static function formatarCNPJ(string|null $cnpj): string|null
    {
        if ($cnpj === null) {
            return null;
        }

        return preg_replace("/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/", "$1.$2.$3/$4-$5", preg_replace('/\D/', '', $cnpj));
    }

    /**
     * Valida CPF.
     *
     * @param string|null $cpf O CPF a ser validado.
     * @return bool Retorna true se o CPF ou CNPJ for válido, caso contrário, false.
     */
    public static function validarCPF(string|null $cpf): bool
    {
        if ($cpf === null) {
            return false;
        }

        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) != 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
        }
        return true;
    }

    /**
     * Valida CNPJ.
     *
     * @param string $cnpj O CNPJ a ser validado.
     * @return bool Retorna true se o CNPJ for válido, caso contrário, false.
     */
    public static function validarCNPJ(string|null $cnpj): bool
    {
        if ($cnpj === null) {
            return false;
        }

        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) !== 14) {
            return false;
        }
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        for ($t = 12; $t < 14; $t++) {
            $soma = 0;
            $peso = $t - 7;

            for ($i = 0; $i < $t; $i++) {
                $soma += $cnpj[$i] * $peso--;
                if ($peso < 2) {
                    $peso = 9;
                }
            }

            $digito = $soma % 11;
            $digito = ($digito < 2) ? 0 : 11 - $digito;

            if ((int)$cnpj[$t] !== $digito) {
                return false;
            }
        }

        return true;
    }

    /**
     * Formata uma string de CEP para o padrão brasileiro.
     *
     * Remove caracteres não numéricos e aplica a máscara #####-###.
     *
     * @param string|null $cep CEP a ser formatado.
     * @return string|null CEP formatado ou null caso a entrada seja null.
     */
    public static function formatarCEP(string $cep): string|null
    {
        if ($cep === null) {
            return null;
        }

        return preg_replace("/^(\d{5})(\d{3})$/", "$1-$2", preg_replace('/\D/', '', $cep));
    }

    /**
     * Valida CEP.
     *
     * @param string|null $cep O CEP a ser validado.
     * @return bool Retorna true se o CEP for válido, caso contrário, false.
     */
    public static function validarCEP(string|null $cep): bool
    {
        if ($cep === null) {
            return false;
        }
        return preg_match('/^\d{5}-?\d{3}$/', $cep) === 1;
    }

    /**
     * Converte uma data do formato padrão (YYYY-MM-DD ou outro aceito pelo strtotime)
     *  para o formato brasileiro (DD/MM/YYYY).
     *
     * @param string|null $data Data em formato string.
     * @return string|null Data formatada para o padrão brasileiro ou null caso a entrada seja null.
     */
    public static function dataParaBR(string|null $data): string|null
    {
        if ($data === null) {
            return null;
        }
        return date('d/m/Y', strtotime($data));
    }

    /**
     * Converte uma data do formato brasileiro (DD/MM/YYYY) para o formato SQL (YYYY-MM-DD).
     *
     * @param string|null $data Data em formato brasileiro.
     * @return string|null Data convertida para o padrão SQL ou null caso a entrada seja null.
     */
    public static function dataParaSQL(string|null $data): string|null
    {
        if ($data === null) {
            return null;
        }
        return date('Y-m-d', strtotime(str_replace('/', '-', $data)));
    }

    /**
     * Calcula a idade a partir de uma data de nascimento.
     *
     * @param string|null $dataNascimento Data de nascimento em formato string (YYYY-MM-DD ou outro aceito pelo DateTime).
     * @return int|null Idade em anos completos ou null caso a entrada seja null.
     */
    public static function idade(string|null $dataNascimento): int|null
    {
        if ($dataNascimento === null) {
            return null;
        }
        $nasc = new \DateTime($dataNascimento);
        $hoje = new \DateTime();
        return $hoje->diff($nasc)->y;
    }

    /**
     * Retorna o dia da semana em português para uma determinada data.
     *
     * @param string|null $data Data em formato string (YYYY-MM-DD ou outro aceito pelo strtotime).
     * @return string|null Nome do dia da semana em português ou null caso a entrada seja null.
     */
    public static function diaSemana(string|null $data): string|null
    {
        if ($data === null) {
            return null;
        }
        $dias = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
        return $dias[date('w', strtotime($data))];
    }

    /**
     * Converte a sigla de uma unidade federativa (UF) para o nome completo do estado.
     *
     * @param string|null $uf Sigla do estado (ex: "SP", "RJ").
     * @return string|null Nome completo do estado ou null caso a entrada seja null ou inválida.
     */
    public static function ufParaEstado(string|null $uf): string|null
    {
        if ($uf === null) {
            return null;
        }

        $map = [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'São Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins'
        ];

        $uf = strtoupper($uf);
        return $map[$uf] ?? 'Não encontrado';
    }

    /**
     * Retorna o estado correspondente a um código DDD do Brasil.
     *
     * @param int|null $ddd Código DDD de 2 ou 3 dígitos.
     * @return string|null Nome do estado ou null caso a entrada seja null ou não reconhecida.
     */
    public static function dddParaEstado(int|null $ddd): string|null
    {
        if ($ddd === null) {
            return null;
        }

        $map = [
            // Região Sudeste
            11 => 'São Paulo (Capital e região metropolitana)',
            12 => 'São José dos Campos (SP)',
            13 => 'Baixada Santista (SP)',
            14 => 'Bauru (SP)',
            15 => 'Sorocaba (SP)',
            16 => 'Ribeirão Preto (SP)',
            17 => 'São José do Rio Preto (SP)',
            18 => 'Presidente Prudente (SP)',
            19 => 'Campinas (SP)',

            21 => 'Rio de Janeiro (Capital)',
            22 => 'Região dos Lagos / Norte Fluminense (RJ)',
            24 => 'Região Serrana / Sul Fluminense (RJ)',

            27 => 'Vitória / Grande Vitória (ES)',
            28 => 'Sul do Espírito Santo',

            31 => 'Belo Horizonte (MG)',
            32 => 'Zona da Mata (MG)',
            33 => 'Vale do Rio Doce (MG)',
            34 => 'Triângulo Mineiro (MG)',
            35 => 'Sul de Minas (MG)',
            37 => 'Centro-Oeste de Minas',
            38 => 'Norte de Minas',

            // Região Sul
            41 => 'Curitiba (PR)',
            42 => 'Centro-Sul do Paraná',
            43 => 'Norte do Paraná',
            44 => 'Noroeste do Paraná',
            45 => 'Oeste do Paraná',
            46 => 'Sudoeste do Paraná',

            47 => 'Joinville / Norte de SC',
            48 => 'Florianópolis / Sul de SC',
            49 => 'Oeste de Santa Catarina',

            51 => 'Porto Alegre (RS)',
            53 => 'Pelotas / Sul do RS',
            54 => 'Caxias do Sul / Serra Gaúcha',
            55 => 'Santa Maria / Oeste do RS',

            // Região Centro-Oeste
            61 => 'Distrito Federal (Brasília)',
            62 => 'Goiânia / Centro de GO',
            64 => 'Sul de Goiás',
            65 => 'Cuiabá (MT)',
            66 => 'Interior de Mato Grosso',
            67 => 'Mato Grosso do Sul',

            // Região Nordeste
            71 => 'Salvador (BA)',
            73 => 'Sul da Bahia',
            74 => 'Centro-Norte da Bahia',
            75 => 'Recôncavo Baiano',
            77 => 'Oeste da Bahia',

            79 => 'Sergipe (Aracaju)',

            81 => 'Recife (PE)',
            87 => 'Interior de Pernambuco',

            82 => 'Alagoas (Maceió)',
            83 => 'Paraíba (João Pessoa)',
            84 => 'Rio Grande do Norte (Natal)',
            85 => 'Fortaleza (CE)',
            88 => 'Interior do Ceará',
            86 => 'Teresina (PI)',
            89 => 'Interior do Piauí',
            98 => 'São Luís (MA)',
            99 => 'Interior do Maranhão',

            // Região Norte
            91 => 'Belém (PA)',
            93 => 'Santarém / Oeste do Pará',
            94 => 'Marabá / Sudeste do Pará',

            92 => 'Manaus (AM)',
            97 => 'Interior do Amazonas',

            95 => 'Boa Vista (RR)',
            96 => 'Macapá (AP)',
            99 => 'Interior do Maranhão',
            69 => 'Rondônia (Porto Velho)',
            68 => 'Acre (Rio Branco)',
        ];

        return $map[$ddd] ?? 'DDD desconhecido';
    }

    /**
     * Retorna todos os códigos DDD correspondentes a um estado brasileiro.
     *
     * @param string|null $uf Sigla do estado (ex: "SP", "RJ").
     * @return array|null Array de DDDs do estado ou null caso a entrada seja null ou inválida.
     */
    public static function estadoParaDDDs(string|null $uf): array|null
    {
        if ($uf === null) {
            return null;
        }

        $uf = strtoupper(trim($uf));

        $dddMap = [
            'SP' => [11, 12, 13, 14, 15, 16, 17, 18, 19],
            'RJ' => [21, 22, 24],
            'ES' => [27, 28],
            'MG' => [31, 32, 33, 34, 35, 37, 38],
            'PR' => [41, 42, 43, 44, 45, 46],
            'SC' => [47, 48, 49],
            'RS' => [51, 53, 54, 55],
            'DF' => [61],
            'GO' => [62, 64],
            'MT' => [65, 66],
            'MS' => [67],
            'BA' => [71, 73, 74, 75, 77],
            'SE' => [79],
            'PE' => [81, 87],
            'AL' => [82],
            'PB' => [83],
            'RN' => [84],
            'CE' => [85, 88],
            'PI' => [86, 89],
            'MA' => [98, 99],
            'PA' => [91, 93, 94],
            'AM' => [92, 97],
            'RR' => [95],
            'AP' => [96],
            'RO' => [69],
            'AC' => [68],
        ];

        return $dddMap[$uf] ?? [];
    }

    /**
     * Formata um número de telefone brasileiro para o padrão (XX) XXXXX-XXXX.
     *
     * Remove caracteres não numéricos e aplica a máscara de telefone.
     *
     * @param string|null $numero Número de telefone em formato string.
     * @return string|null Telefone formatado ou null caso a entrada seja null.
     */
    public static function formatarTelefone(string|null $numero): string|null
    {
        if ($numero === null) {
            return null;
        }

        $numero = preg_replace('/\D/', '', $numero);
        return preg_replace("/(\d{2})(\d{5})(\d{4})/", "($1) $2-$3", $numero);
    }

    /**
     * Remove acentos e caracteres especiais de uma string.
     *
     * Converte caracteres UTF-8 acentuados para ASCII equivalente.
     *
     * @param string|null $texto Texto a ser processado.
     * @return string|null Texto sem acentos ou null caso a entrada seja null.
     */
    public static function removerAcentos(string|null $texto): string|null
    {
        if ($texto === null) {
            return null;
        }
        return iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    }

    /**
     * Gera um código aleatório de tamanho especificado.
     *
     * @param int $tamanho O tamanho do código a ser gerado.
     * @return string O código aleatório gerado.
     */
    public static function gerarCodigoAleatorio(int $tamanho = 8): string
    {
        return substr(bin2hex(random_bytes($tamanho)), 0, $tamanho);
    }

    /**
     * Retorna o nome do banco correspondente a um código bancário.
     *
     * @param string|null $codigo Código do banco (ex: "001", "237").
     * @return string|null Nome do banco ou null caso a entrada seja null ou não encontrada.
     */
    public static function bancoPorCodigo(string|null $codigo): string|null
    {
        if ($codigo === null) {
            return null;
        }
        $bancos = [
            '001' => 'Banco do Brasil S.A.',
            '003' => 'Banco da Amazônia S.A.',
            '004' => 'Banco do Nordeste do Brasil S.A.',
            '012' => 'Banco Standard de Investimentos S.A.',
            '014' => 'Natixis Brasil S.A. Banco Múltiplo',
            '016' => 'BB Banco Popular do Brasil S.A.',
            '017' => 'Banco BBA Creditanstalt S.A.',
            '018' => 'Banco Tricury S.A.',
            '021' => 'Banestes S.A. Banco do Estado do Espírito Santo',
            '025' => 'Banco Alfa S.A.',
            '027' => 'Banco do Estado do Pará S.A. - BANPARÁ',
            '029' => 'Banco Banerj S.A.',
            '031' => 'Banco Beg S.A.',
            '033' => 'Banco Santander (Brasil) S.A.',
            '036' => 'Banco Bradesco BBI S.A.',
            '037' => 'Banco do Estado de Sergipe S.A.',
            '038' => 'Banco do Estado do Maranhão S.A.',
            '039' => 'Banco Mercantil do Brasil S.A.',
            '041' => 'Banco do Estado do Rio Grande do Sul S.A. - Banrisul',
            '047' => 'Banco do Estado de Alagoas S.A.',
            '062' => 'Hipercard Banco Múltiplo S.A.',
            '063' => 'Banco Ibi S.A. Banco Múltiplo',
            '064' => 'Banco Bradesco Cartões S.A.',
            '065' => 'Banco Votorantim S.A.',
            '066' => 'Banco Morgan Stanley S.A.',
            '069' => 'Banco Crefisa S.A.',
            '070' => 'BRB - Banco de Brasília S.A.',
            '072' => 'Banco Rural Mais S.A.',
            '074' => 'Banco J.P. Morgan S.A.',
            '075' => 'Banco ABN AMRO S.A.',
            '076' => 'Banco KDB S.A.',
            '077' => 'Banco Inter S.A.',
            '078' => 'Banco JP Morgan Chase Bank, National Association',
            '079' => 'Banco Original do Agronegócio S.A.',
            '080' => 'Banco Topázio S.A.',
            '081' => 'Banco do Estado do Piauí S.A.',
            '082' => 'Banco Topázio S.A.',
            '083' => 'Banco da China Brasil S.A.',
            '084' => 'Unicred Central Cooperativa de Crédito Ltda.',
            '085' => 'Cooperativa Central de Crédito Noroeste Brasileiro Ltda.',
            '086' => 'Banco C6 S.A.',
            '087' => 'Banco KEB Hana do Brasil S.A.',
            '088' => 'Banco Modal S.A.',
            '089' => 'C6 Bank',
            '090' => 'Banco Caixa Geral - Brasil S.A.',
            '091' => 'Banco do Estado do Amazonas S.A.',
            '092' => 'Banco do Estado de Santa Catarina S.A.',
            '093' => 'Banco Century S.A.',
            '094' => 'Banco Caixa Econômica Federal',
            '095' => 'Banco Neon S.A.',
            '096' => 'Banco BM&FBOVESPA de Serviços de Liquidação e Custódia S.A.',
            '097' => 'Cooperativa Central de Crédito Urbano-CECRED',
            '098' => 'Banco de Desenvolvimento de Minas Gerais S.A.',
            '099' => 'Banco de Crédito Real de Investimento S.A.',
            '104' => 'Caixa Econômica Federal',
            '107' => 'Banco BBM S.A.',
            '116' => 'Banco BMG S.A.',
            '121' => 'Banco Ourinvest S.A.',
            '124' => 'Banco Paulista S.A.',
            '125' => 'Banco Bradesco Cartões S.A.',
            '126' => 'Banco Bradesco Financiamentos S.A.',
            '127' => 'Banco Cooperativo do Brasil S.A. - BANCOOB',
            '128' => 'Banco Cooperativo Sicredi S.A.',
            '129' => 'Banco Rendimento S.A.',
            '130' => 'Banco Mercedes-Benz do Brasil S.A.',
            '131' => 'Tullett Prebon Brasil Corretora de Títulos e Valores Mobiliários Ltda.',
            '132' => 'XP Investimentos CCTVM S.A.',
            '135' => 'Banco Unicard',
            '136' => 'Banco Itaú Consignado S.A.',
            '138' => 'Banco Sofisa S.A.',
            '139' => 'Unicred Central RS',
            '146' => 'Banco Porto Seguro S.A.',
            '147' => 'Banco Rabobank International Brasil S.A.',
            '168' => 'Banco BNP Paribas Brasil S.A.',
            '175' => 'Banco Mizuho do Brasil S.A.',
            '184' => 'Banco Itaú BBA S.A.',
            '204' => 'Banco Bradesco BBI S.A.',
            '208' => 'Banco UBS Pactual S.A.',
            '212' => 'Banco Matone S.A.',
            '213' => 'Banco Arbi S.A.',
            '214' => 'Banco Dibens S.A.',
            '215' => 'Banco Comercial e de Investimento Sudameris S.A.',
            '217' => 'Banco John Deere S.A.',
            '218' => 'Banco Bonsucesso S.A.',
            '222' => 'Banco Calyon Brasil S.A.',
            '224' => 'Banco Fibra S.A.',
            '225' => 'Banco Brascan S.A.',
            '229' => 'Banco Cruzeiro do Sul S.A.',
            '230' => 'Banco Intercap S.A.',
            '233' => 'Banco GE Capital S.A.',
            '237' => 'Banco Bradesco S.A.',
            '241' => 'Banco Clássico S.A.',
            '246' => 'Banco ABC Brasil S.A.',
            '248' => 'Banco Boavista Interatlântico S.A.',
            '249' => 'Banco Investcred Unibanco S.A.',
            '250' => 'Banco Schahin S.A.',
            '251' => 'Banco Ourinvest S.A.',
            '252' => 'Banco Finaxis S.A.',
            '253' => 'Banco Garantia S.A.',
            '254' => 'Banco Indusval S.A.',
            '255' => 'Banco Real S.A. (antigo)',
            '256' => 'Banco para Investimento Real S.A.',
            '259' => 'Banco Cresol',
            '260' => 'Nu Pagamentos S.A. - MEI (Nubank)',
            '265' => 'Banco Fator S.A.',
            '266' => 'Banco Cédula S.A.',
            '300' => 'Banco de La Provincia de Buenos Aires',
            '318' => 'Banco BMG S.A.',
            '320' => 'Banco Industrial e Comercial S.A.',
            '341' => 'Banco Itaú S.A.',
            '347' => 'Banco Sudameris S.A.',
            '351' => 'Banco Santander Brasil S.A.',
            '353' => 'Banco Santander Brasil S.A.',
            '356' => 'Banco Real S.A.',
            '366' => 'Banco Société Générale Brasil S.A.',
            '370' => 'Banco Mizuho do Brasil S.A.',
            '376' => 'Banco JP Morgan S.A.',
            '389' => 'Banco Mercantil do Brasil S.A.',
            '394' => 'Banco Mercantil do Brasil S.A.',
            '399' => 'HSBC Bank Brasil S.A.',
            '409' => 'Unibanco - União de Bancos Brasileiros S.A.',
            '412' => 'Banco Capital S.A.',
            '422' => 'Banco Safra S.A.',
            '453' => 'Banco Rural Mais S.A.',
            '456' => 'Banco Tokyo-Mitsubishi UFJ Brasil S.A.',
            '464' => 'Banco Sumitomo Mitsui Brasileiro S.A.',
            '473' => 'Banco Caixa Geral - Brasil S.A.',
            '477' => 'Citibank N.A.',
            '487' => 'Deutsche Bank S.A. - Banco Alemão',
            '488' => 'JPMorgan Chase Bank',
            '492' => 'ING Bank N.V.',
            '494' => 'Banco de La Nacion Argentina',
            '495' => 'Banco de La Provincia de Buenos Aires',
            '505' => 'Banco Credit Suisse (Brasil) S.A.',
            '600' => 'Banco Luso Brasileiro S.A.',
            '604' => 'Banco Industrial do Brasil S.A.',
            '610' => 'Banco VR S.A.',
            '611' => 'Banco Paulista S.A.',
            '612' => 'Banco Guanabara S.A.',
            '613' => 'Banco Pecunia S.A.',
            '623' => 'Banco Panamericano S.A.',
            '626' => 'Banco Ficsa S.A.',
            '630' => 'Banco Intercap S.A.',
            '633' => 'Banco Rendimento S.A.',
            '634' => 'Banco Triângulo S.A.',
            '637' => 'Banco Sofisa S.A.',
            '643' => 'Banco Pine S.A.',
            '652' => 'Itaú Unibanco Holding S.A.',
            '653' => 'Banco Indusval S.A.',
            '654' => 'Banco A.J.Renner S.A.',
            '655' => 'Banco Votorantim S.A.',
            '656' => 'Banco C6 S.A.',
            '707' => 'Banco Daycoval S.A.',
            '712' => 'Banco Ourinvest S.A.',
            '719' => 'Banco Banif S.A.',
            '721' => 'Banco Credibel S.A.',
            '734' => 'Banco Gerdau S.A.',
            '735' => 'Banco Pottencial S.A.',
            '738' => 'Banco Morada S.A.',
            '739' => 'Banco Galvão S.A.',
            '740' => 'Banco Barclays S.A.',
            '741' => 'Banco Ribeirão Preto S.A.',
            '743' => 'Banco Semear S.A.',
            '745' => 'Banco Citibank S.A.',
            '746' => 'Banco Modal S.A.',
            '747' => 'Banco Rabobank International Brasil S.A.',
            '748' => 'Banco Cooperativo do Brasil S.A. - BANCOOB',
            '751' => 'Dresdner Bank Brasil S.A. Banco Múltiplo',
            '752' => 'Banco BNP Paribas Brasil S.A.',
            '753' => 'Banco Comercial UBS AG',
            '754' => 'Banco Sistema S.A.',
            '755' => 'Banco Merrill Lynch de Investimentos S.A.',
            '756' => 'Banco Cooperativo Sicredi S.A.',
            '757' => 'Banco KDB S.A.',
            '758' => 'Banco UBS Brasil S.A.',
            '759' => 'Banco Nissan S.A.',
            '760' => 'Banco Moda S.A.',
            '765' => 'Banco ABN AMRO S.A.',
            '767' => 'Banco Caixa Econômica S.A.',
            'rest' => 'Banco Desconhecido'
        ];

        return $bancos[$codigo] ?? 'Banco desconhecido';
    }

    /**
     * Converte o nome completo de um estado brasileiro para sua sigla (UF).
     *
     * @param string|null $estado Nome completo do estado (ex: "São Paulo").
     * @return string|null Sigla do estado (ex: "SP") ou null caso a entrada seja null ou não reconhecida.
     */
    public static function estadoParaUF(string|null $estado): string|null
    {

        if ($estado === null) {
            return null;
        }

        $estados = [
            'acre' => 'AC',
            'alagoas' => 'AL',
            'amapa' => 'AP',
            'amazonas' => 'AM',
            'bahia' => 'BA',
            'ceara' => 'CE',
            'distrito federal' => 'DF',
            'espirito santo' => 'ES',
            'goias' => 'GO',
            'maranhao' => 'MA',
            'mato grosso' => 'MT',
            'mato grosso do sul' => 'MS',
            'minas gerais' => 'MG',
            'para' => 'PA',
            'paraiba' => 'PB',
            'parana' => 'PR',
            'pernambuco' => 'PE',
            'piaui' => 'PI',
            'rio de janeiro' => 'RJ',
            'rio grande do norte' => 'RN',
            'rio grande do sul' => 'RS',
            'rondonia' => 'RO',
            'roraima' => 'RR',
            'santa catarina' => 'SC',
            'sao paulo' => 'SP',
            'sergipe' => 'SE',
            'tocantins' => 'TO'
        ];

        $estado = strtolower($estado);
        $estado = strtr($estado, [
            'á' => 'a',
            'à' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
            'Á' => 'a',
            'À' => 'a',
            'Ã' => 'a',
            'Â' => 'a',
            'Ä' => 'a',
            'É' => 'e',
            'È' => 'e',
            'Ê' => 'e',
            'Ë' => 'e',
            'Í' => 'i',
            'Ì' => 'i',
            'Î' => 'i',
            'Ï' => 'i',
            'Ó' => 'o',
            'Ò' => 'o',
            'Ô' => 'o',
            'Õ' => 'o',
            'Ö' => 'o',
            'Ú' => 'u',
            'Ù' => 'u',
            'Û' => 'u',
            'Ü' => 'u',
            'Ç' => 'c'
        ]);

        return isset($estados[$estado]) ? $estados[$estado] : 'Nenhum estado localizado';
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
