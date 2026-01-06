<?php

declare(strict_types=1);

namespace Lumynus\Bundle\Framework;

/**
 * Trait com expressões regulares rigorosas para validação de dados.
 * Abrange: Texto, Numéricos, Docs BR, Internet, Segurança, Arquivos e Diversos.
 * * @package Lumynus\Bundle\Framework
 */
trait Requirements
{
    // =========================================================================
    // 📌 1. TEXTO E FORMATAÇÃO
    // =========================================================================

    /**
     * Apenas letras (com acentos) e espaços simples.
     * Ex: "Nome Sobrenome"
     */
    public const TEXT_ONLY = "/^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/";

    /**
     * Alias para TEXT_ONLY (mantido para compatibilidade semântica).
     */
    public const WORDS_WITH_SPACES = "/^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/";

    /**
     * Apenas letras, sem espaços ou números.
     */
    public const LETTERS_ONLY = "/^[A-Za-zÀ-ÖØ-öø-ÿ]+$/";

    /**
     * Nome completo rigoroso (Mínimo 2 nomes).
     * Evita espaços duplos ou nomes terminados em espaço.
     */
    public const NAME = "/^[A-Za-zÀ-ÖØ-öø-ÿ]+(?:\s[A-Za-zÀ-ÖØ-öø-ÿ]+)+$/";

    /**
     * Texto alfanumérico (letras, números e acentos). Sem símbolos.
     */
    public const ALPHANUMERIC = "/^[A-Za-z0-9À-ÖØ-öø-ÿ]+$/";

    /**
     * Slug para URLs amigáveis (SEO). Ex: "meu-artigo-2024".
     */
    public const SLUG = "/^[a-z0-9]+(?:-[a-z0-9]+)*$/";

    /**
     * Parágrafo: permite pontuação comum (.,;!?'"()-).
     */
    public const PARAGRAPH = "/^[\wÀ-ÖØ-öø-ÿ\s.,;!?\"'()\-\n\r]+$/";

    /**
     * Verifica se a string contém APENAS espaços em branco.
     */
    public const WHITESPACE_ONLY = "/^\s+$/";

    // =========================================================================
    // 📌 2. NUMÉRICOS, FINANCEIRO E CIENTÍFICO
    // =========================================================================

    public const WHOLE = "/^\d+$/";                 // Inteiro positivo
    public const INT = "/^-?\d+$/";                 // Inteiro (pos/neg)
    public const FLOAT = "/^-?\d+(\.\d+)?$/";       // Decimal

    /**
     * Notação Científica. Ex: 1.2e3, -5E-4.
     */
    public const SCIENTIFIC = "/^-?\d+(\.\d+)?([eE][-+]?\d+)?$/";

    /**
     * Binário (0 e 1).
     */
    public const BINARY = "/^[01]+$/";

    /**
     * Hexadecimal numérico. Ex: 0xFF, 1A3B.
     */
    public const HEXADECIMAL = "/^[A-Fa-f0-9]+$/";

    /**
     * Moeda (com ou sem símbolo). Ex: R$ 1.000,00 ou 100.50.
     */
    public const CURRENCY = "/^([A-Z$]{1,3}\s?)?\d{1,3}(\.?\d{3})*(,\d{2})?$/";

    /**
     * Cartão de Crédito (Validação básica de formato 13-19 dígitos).
     */
    public const CREDIT_CARD = "/^\d{13,19}$/";

    /**
     * Coordenadas (Lat: -90 a 90, Long: -180 a 180).
     */
    public const COORDINATES = "/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/";

    // =========================================================================
    // 📌 3. INTERNET E REDE (CRÍTICO)
    // =========================================================================

    /**
     * E-mail rigoroso (Não permite pontos consecutivos ou no início/fim).
     */
    public const EMAIL = "/^[a-zA-Z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/";

    /**
     * URL válida (HTTP/HTTPS).
     */
    public const URL = "/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?(\?[\w=&-]*)?(#[\w-]*)?$/";

    /**
     * IPv4 Real (0-255).
     */
    public const IPV4 = "/^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/";

    public const IPV6 = "/^([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$/";

    /**
     * Endereço MAC (físico). Ex: 00:1A:2B:3C:4D:5E.
     */
    public const MAC_ADDRESS = "/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/";

    // =========================================================================
    // 📌 4. DOCUMENTOS E PADRÕES BRASIL
    // =========================================================================

    public const CPF = "/^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$/";
    public const CNPJ = "/^\d{2}\.?\d{3}\.?\d{3}\/?\d{4}-?\d{2}$/";
    public const CEP = "/^\d{5}-?\d{3}$/";
    
    /**
     * Telefone BR (Celular 9 dígitos ou Fixo). Ex: (11) 91234-5678.
     */
    public const PHONE = "/^\(?\d{2}\)?\s?(?:9\d{4}|\d{4})-?\d{4}$/";
    
    /**
     * Placa Veículo (Mercosul e Antiga).
     */
    public const VEHICLE_PLATE = "/^[A-Z]{3}-?\d{4}$|^[A-Z]{3}\d[A-Z]\d{2}$/";

    // =========================================================================
    // 📌 5. DATAS E HORAS
    // =========================================================================

    /**
     * Data BR (DD/MM/AAAA). Valida dias 01-31 e meses 01-12.
     */
    public const DATE_BR = "/^(0[1-9]|[12]\d|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/";

    /**
     * Data ISO (AAAA-MM-DD). Padrão de banco de dados.
     */
    public const DATE_ISO = "/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/";

    /**
     * Horário (HH:MM ou HH:MM:SS) formato 24h.
     */
    public const TIME = "/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/";

    /**
     * Horário formato 12h (Ex: 02:30 PM).
     */
    public const TIME_12H = "/^(0[1-9]|1[0-2]):[0-5]\d\s?(AM|PM|am|pm)$/";

    /**
     * Timestamp UNIX (Segundos desde 1970).
     */
    public const UNIX_TIMESTAMP = "/^\d{10}$/";

    // =========================================================================
    // 📌 6. SEGURANÇA E TOKENS
    // =========================================================================

    /**
     * Senha Forte (Min 8 chars, 1 Letra, 1 Número, 1 Especial).
     */
    public const SECURE_PASSWORD = "/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/";

    /**
     * Senha Muito Forte (Min 12, Maiúscula, Minúscula, Número, Especial).
     */
    public const VERY_STRONG_PASSWORD = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/";

    /**
     * Senha Numérica (PIN) - 4 a 8 dígitos.
     */
    public const NUMERIC_PASSWORD = "/^\d{4,8}$/";

    /**
     * Token genérico (32 a 128 chars).
     */
    public const SECURE_TOKEN = "/^[A-Za-z0-9\-_]{32,128}$/";

    /**
     * UUID (v4 ou genérico).
     */
    public const UUID = "/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/";

    /**
     * JWT (JSON Web Token).
     */
    public const JWT = "/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]*$/";

    // =========================================================================
    // 📌 7. ARQUIVOS, CORES E ESTILOS
    // =========================================================================

    /**
     * Hex Color (CSS). Ex: #FFF, #000000, #000000FF.
     */
    public const HEX_COLOR = "/^#?([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/";

    /**
     * Unidade CSS (px, em, rem, %, vh, etc). Ex: 10px, 2.5rem.
     */
    public const CSS_UNIT = "/^-?\d+(\.\d+)?(px|em|rem|%|vh|vw|pt|cm|mm|in|pc)$/i";

    public const SAFE_FILENAME = "/^[A-Za-z0-9\-_]+\.[a-z0-9]+$/i";
    public const FILE_EXTENSION = "/^.*\.(jpg|jpeg|png|gif|webp|svg|pdf|doc|docx|xls|xlsx|csv|txt|zip|rar|mp4|mp3)$/i";

    // =========================================================================
    // 📌 8. IDENTIFICADORES E SOCIAIS
    // =========================================================================

    public const BARCODE = "/^\d{8,14}$/";          // EAN-8, EAN-13, EAN-14
    public const SKU = "/^[A-Za-z0-9\-_]{3,20}$/";  // SKU Produto
    
    /**
     * Username Social (X/Twitter, Insta). Começa com @ opcional.
     */
    public const SOCIAL_USERNAME = "/^@?[A-Za-z0-9_]{3,25}$/";

    /**
     * Link de Rede Social (Facebook, X, Instagram, LinkedIn, Youtube, TikTok).
     */
    public const SOCIAL_LINK = "/^(https?:\/\/)?(www\.)?(facebook|twitter|x|instagram|linkedin|youtube|tiktok)\.com\/[A-Za-z0-9_.\/-]+$/";

    /**
     * Endereço Cripto (Bitcoin Legacy e SegWit básico).
     */
    public const CRYPTO_ADDRESS = "/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}|bc1[a-zA-HJ-NP-Z0-9]{39,59}$/";
}