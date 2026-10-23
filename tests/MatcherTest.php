<?php

namespace PandoraTestesTeste;

use Behat\WebApiExtension\Context\WebApiContext;
use GuzzleHttp\Psr7\Response;
use PandoraTestes\Context\PandoraSrvContext;
use PHPUnit\Framework\TestCase;

/**
 * Caracterização dos passos JSON do PandoraSrvContext, com o matcher (coduo/php-matcher) que
 * eles usam: "at least these fields" (571 cenários no area-restrita), "this format" e "not
 * contain". Uma matriz de padrões × valores, gravada com o php-matcher 2.0.1. A 3.x, com outra
 * versão do matcher, tem de dar o mesmo resultado em cada célula (D10).
 */
class MatcherTest extends TestCase
{
    /** Em JSON literal: o json_encode de float muda com o serialize_precision de cada PHP */
    const PADROES = [
        '"@integer@"', '"@string@"', '"@number@"', '"@double@"', '"@*@"', '"@array@"', '"@null@"',
        '"@boolean@"', '"@string@.startsWith(\'20\')"', '"@string@.startsWith(\'\')"',
        '"@string@.contains(\'loc\')"', '"@string@.matchRegex(\'/^\\\\d+$/\')"',
        '"@double@.lowerThan(2).greaterThan(1)"', '"@integer@.greaterThan(0)"', '"@integer@.lowerThan(5)"',
        '"@array@.count(1)"', '1', '"1"', '1.5', '1.0', 'null', 'true', '""', '"abc"',
    ];

    const VALORES = [
        '1', '"1"', '1.5', '"1.5"', '0', '-3', '""', '"abc"', '"Abc"', 'null', 'true', 'false', '[]',
        '[1]', '{"a":1}', '"2026-01-02T10:00:00-03:00"', '"http://localhost:8000/x"', '"nada local"',
        '10000.0', '1.0', '"12"',
    ];

    private function contexto($corpo)
    {
        $webApi = new WebApiContext();
        Pilha::respostaNoWebApi($webApi, new Response(200, [], $corpo));
        $contexto = new PandoraSrvContext();
        Pilha::ligaWebApi($contexto, $webApi);
        return $contexto;
    }

    public function testMatrizDePadroesEValores()
    {
        $resultado = [];
        foreach (self::PADROES as $padrao) {
            $etalon = '{"c":' . $padrao . '}';
            foreach (self::VALORES as $valor) {
                $contexto = $this->contexto('{"c":' . $valor . ',"extra":"x"}');
                $resultado[$padrao][$valor] = [
                    'at least' => Pilha::resultado(function () use ($contexto, $etalon) {
                        $contexto->theResponseShouldContainJsonWithAtLeastThisFields(Pilha::texto($etalon));
                    }),
                    'format' => Pilha::resultado(function () use ($contexto, $etalon) {
                        $contexto->theResponseShouldContainJsonWithFormat(Pilha::texto($etalon));
                    }),
                    'not contain' => Pilha::resultado(function () use ($contexto, $etalon) {
                        $contexto->theResponseShouldNotContainJsonWithAtLeastThisFields(Pilha::texto($etalon));
                    }),
                ];
            }
        }
        Instantaneo::confere('matcher-matriz', $resultado);
    }

    /** Estruturas: chave a mais ou a menos, listas, objetos aninhados, ordem. */
    public function testEstruturas()
    {
        $casos = [
            'chave a mais na resposta' => ['{"a":1,"b":2}', '{"a":1}'],
            'chave a menos na resposta' => ['{"a":1}', '{"a":1,"b":2}'],
            'lista maior que o esperado' => ['{"l":[1,2,3]}', '{"l":[1,2]}'],
            'lista menor que o esperado' => ['{"l":[1]}', '{"l":[1,2]}'],
            'lista em outra ordem' => ['{"l":[2,1]}', '{"l":[1,2]}'],
            'lista de objetos com campo a mais' => ['{"l":[{"id":1,"n":"a"},{"id":2,"n":"b"}]}', '{"l":[{"id":1},{"id":2}]}'],
            'lista de objetos com padrão' => ['{"l":[{"id":1},{"id":"2"}]}', '{"l":[{"id":"@integer@"},{"id":"@integer@"}]}'],
            'aninhado com padrão' => ['{"o":{"p":{"q":"x"}}}', '{"o":{"p":{"q":"@string@"}}}'],
            'aninhado: objeto no lugar de valor' => ['{"o":{"p":1}}', '{"o":"@string@"}'],
            'aninhado: valor no lugar de objeto' => ['{"o":1}', '{"o":{"p":1}}'],
            'objeto vazio' => ['{"o":{}}', '{"o":{}}'],
            'lista vazia contra objeto' => ['{"o":[]}', '{"o":{"p":1}}'],
            'número inteiro contra 1.0' => ['{"n":1}', '{"n":1.0}'],
            'número 1.0 contra inteiro' => ['{"n":1.0}', '{"n":1}'],
            'texto contra número' => ['{"n":"1"}', '{"n":1}'],
            'raiz lista' => ['[{"id":1},{"id":2}]', '[{"id":1}]'],
            '@*@ numa lista' => ['{"l":[1,"a",null]}', '{"l":"@*@"}'],
            'resposta não é json' => ['texto', '{"a":1}'],
            'esperado não é json' => ['{"a":1}', '{a:1}'],
            'padrão desconhecido' => ['{"a":1}', '{"a":"@inteiro@"}'],
            'expansor desconhecido' => ['{"a":"x"}', '{"a":"@string@.comeca(\'x\')"}'],
        ];
        $resultado = [];
        foreach ($casos as $nome => list($resposta, $etalon)) {
            $contexto = $this->contexto($resposta);
            $resultado[$nome] = [
                'at least' => Pilha::resultado(function () use ($contexto, $etalon) {
                    $contexto->theResponseShouldContainJsonWithAtLeastThisFields(Pilha::texto($etalon));
                }),
                'format' => Pilha::resultado(function () use ($contexto, $etalon) {
                    $contexto->theResponseShouldContainJsonWithFormat(Pilha::texto($etalon));
                }),
                'not contain' => Pilha::resultado(function () use ($contexto, $etalon) {
                    $contexto->theResponseShouldNotContainJsonWithAtLeastThisFields(Pilha::texto($etalon));
                }),
            ];
        }
        Instantaneo::confere('matcher-estruturas', $resultado);
    }

    /** panToday:'<modificador>' vira {"date": "@string@.startsWith('<data>')"} no "at least". */
    public function testPanToday()
    {
        $data = function ($modificador) {
            return (new \DateTime())->modify($modificador)->format('Y-m-d') . ' 10:00:00.000000';
        };
        $casos = [
            'mesma data' => [['d' => ['date' => $data('+3 days')]], ['d' => "panToday:'+3 days'"]],
            'outra data' => [['d' => ['date' => $data('+4 days')]], ['d' => "panToday:'+3 days'"]],
            'campo a mais no objeto' => [['d' => ['date' => $data('today'), 'timezone' => 'X']], ['d' => "panToday:'today'"]],
            'texto no lugar do objeto' => [['d' => $data('today')], ['d' => "panToday:'today'"]],
            'aninhado em lista' => [['l' => [['d' => ['date' => $data('-1 day')]]]], ['l' => [['d' => "panToday:'-1 day'"]]]],
        ];
        $resultado = [];
        foreach ($casos as $nome => list($resposta, $etalon)) {
            $contexto = $this->contexto(json_encode($resposta));
            $resultado[$nome] = Pilha::resultado(function () use ($contexto, $etalon) {
                $contexto->theResponseShouldContainJsonWithAtLeastThisFields(Pilha::texto(json_encode($etalon)));
            });
        }
        Instantaneo::confere('matcher-pantoday', $resultado);
    }
}
