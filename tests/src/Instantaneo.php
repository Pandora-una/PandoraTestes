<?php

namespace PandoraTestesTeste;

use PHPUnit\Framework\TestCase as Assert;

/**
 * Resposta registrada na pilha 2.x (produção) e conferida na 3.x. Com PTESTES_GRAVAR=1 o arquivo é
 * (re)gravado; sem ele, a resposta tem de ser idêntica: mesmos valores e mesmos tipos (1 não é
 * "1"). Só a ordem das chaves de objeto não importa.
 */
class Instantaneo
{
    /**
     * @param callable|null $ajusteEsperado diferença aceita entre as pilhas, aplicada ao instantâneo
     *                                     da 1.x antes de comparar (documentada no teste)
     */
    public static function confere($nome, $resposta, ?callable $ajusteEsperado = null)
    {
        $arquivo = __DIR__ . '/../_instantaneos/' . $nome . '.json';
        $atual = self::normaliza($resposta);
        if (getenv('PTESTES_GRAVAR') || ! file_exists($arquivo)) {
            if (! getenv('PTESTES_GRAVAR')) {
                Assert::fail("Instantâneo $nome não existe: grave na pilha 2.x com PTESTES_GRAVAR=1");
            }
            @mkdir(dirname($arquivo), 0777, true);
            file_put_contents($arquivo, json_encode($atual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
            return;
        }
        $esperado = json_decode(file_get_contents($arquivo), true);
        if ($ajusteEsperado) {
            $esperado = $ajusteEsperado($esperado);
        }
        $esperado = self::normaliza($esperado);
        Assert::assertSame(
            json_encode($esperado, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            json_encode($atual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            "resposta diferente do instantâneo $nome"
        );
    }

    private static function normaliza($valor)
    {
        if (! is_array($valor)) {
            return $valor;
        }
        $associativo = array_keys($valor) !== range(0, count($valor) - 1);
        $valor = array_map([self::class, 'normaliza'], $valor);
        if ($associativo) {
            // como texto: a comparação de chave inteira com texto mudou no PHP 8
            ksort($valor, SORT_STRING);
        }
        return $valor;
    }
}
