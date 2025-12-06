<?php

namespace Lumynus\Bundle\Framework;

class LumaJS
{

    /**
     * Mapeamento de métodos LumaJS para atributos data-luma- correspondentes.
     * @var array
     */
    private static array $methods = [

        /**
         * 
         * Exemplo de uso: 
         * Os Models são usados para criar as variáveis;
         * Os demais sempre passaram as variáveis para analisar se a condição é verdadeira ou não.
         * 
         * @example:
         * <input @luma:model="teste">  cria a @var teste 
         * <div @luma:if="teste">Avava</div> Verifica se @var teste é verdadeira ou existe e exibe o conteúdo;
         * 
         * @example:
         * <select @luma:model="fin"></select> Cria a @var fin
         * <div @luma:if="fin == 'oi'">Exiba </div> verifica se a @var fin é igual a 'oi' e exibe o conteúdo;
         * 
         * ///////////////////// OS DEMAIS SEGUE O MESMO PADRÃO /////////////////////
         * 
         * @example:
         * <input @luma:model="post"> Cria a @var post
         * <div @luma:show="post">Exibe</div> Verifica se a @var post existe ou é verdadeira e exibe o conteúdo;
         * 
         */

        'model' => 'data-luma-model', // Usado por inputs, selects e textareas pra inserir valores
        'event' => 'data-luma-click', // Usado para eventos
        'if' => 'data-luma-if', // Usado para condicionais
        'show' => 'data-luma-show', // Usado para exibir elementos
        'hide' => 'data-luma-hide', // Usado para esconder elementos
        'text' => 'data-luma-text', // Usado para inserir texto nos elementos
        'html' => 'data-luma-html', //Usado para inserir HTML nos elementos
        'class' => 'data-luma-class', // Usado para adicionar classes nos elementos
        'style' => 'data-luma-style', // Usado para adicionar estilos nos elementos
    ];

    /**
     * Compilar o contexto HTML para substituir atributos personalizados do LumaJS
     * com atributos data-luma- correspondentes.
     * @param string $context O contexto HTML a ser compilado.
     * @return string O contexto HTML compilado com atributos data-luma-.
     */
    public static function compile(string $context): string
    {

        if (strpos($context, '@luma:') !== false) {
            $context = self::compileLumaAttributes($context);
        }

        return $context;
    }

    /**
     * Compila atributos personalizados do LumaJS no HTML para atributos data-luma-.
     * @param string $html O HTML a ser compilado.
     * @return string O HTML com atributos data-luma-.
     */
    private static function compileLumaAttributes(string $html): string
    {
        return preg_replace_callback(
            '/@luma:([\w-]+)="([^"]+)"/',
            function ($matches) {
                $attr = $matches[1];  // ex: model, click, if, show...
                $val = htmlspecialchars($matches[2], ENT_QUOTES);

                if (array_key_exists($attr, self::$methods)) {
                    return "data-luma-{$attr}=\"{$val}\"";
                }
            },
            $html
        );
    }


    private function __js() {}
}
