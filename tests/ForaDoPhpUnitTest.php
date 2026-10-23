<?php

namespace PandoraTestesTeste;

use PHPUnit\Framework\TestCase;

/**
 * Só na 3.x. Sob o Behat o PHPUnit não está rodando: um passo cuja asserção falha tem de dar
 * ExpectationFailedException com a mensagem, e não TypeError. O caso roda num processo PHP à parte,
 * porque aqui dentro a configuração do PHPUnit já existe.
 */
class ForaDoPhpUnitTest extends TestCase
{
    public function testAsserçãoQueFalhaDizPorQue()
    {
        if (! class_exists('PHPUnit\TextUI\Configuration\Registry')) {
            $this->markTestSkipped('só no PHPUnit 10+');
        }
        $codigo = <<<'PHP'
require $argv[1] . '/vendor/autoload.php';
$webApi = new Behat\WebApiExtension\Context\WebApiContext();
$resposta = new ReflectionProperty($webApi, 'response');
$resposta->setAccessible(true);
$resposta->setValue($webApi, new GuzzleHttp\Psr7\Response(500, [], '{}'));
$contexto = new PandoraTestes\Context\PandoraSrvContext();
$passos = [
    'código' => function () use ($webApi) { $webApi->theResponseCodeShouldBe('200'); },
    'json' => function () use ($contexto, $webApi) {
        (function ($w) { $this->_webApi = $w; })->call($contexto, $webApi);
        $contexto->theResponseShouldContainJsonWithAtLeastThisFields(new Behat\Gherkin\Node\PyStringNode(['{"a": 1}'], 1));
    },
];
foreach ($passos as $nome => $passo) {
    try {
        $passo();
        echo "$nome: passou\n";
    } catch (Throwable $e) {
        echo $nome, ': ', get_class($e), ': ', strtok($e->getMessage(), "\n"), "\n";
    }
}
PHP;
        $saida = shell_exec(sprintf('%s -r %s %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($codigo), escapeshellarg(dirname(__DIR__))));
        $this->assertSame(
            "código: PHPUnit\Framework\ExpectationFailedException: Failed asserting that 500 is identical to 200.\n"
            . "json: PHPUnit\Framework\ExpectationFailedException: There is no element under path [a] in value.\n",
            $saida
        );
    }
}
