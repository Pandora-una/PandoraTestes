<?php

namespace PandoraTestesTeste;

use Behat\Gherkin\Node\TableNode;
use Behat\WebApiExtension\Context\WebApiContext;
use Behat\WebApiExtension\ServiceContainer\WebApiExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Caracterização do WebApiContext (behat/web-api-extension dev-master, Guzzle 6), que os sistemas
 * usam em ~670 passos: o cliente que a extensão monta a partir do base_url, os passos de
 * requisição e de resposta, e as propriedades que o FeatureContext do area-restrita lê por
 * reflection (headers, request, response, sendRequest). O servidor é um eco (tests/_servidor).
 */
class WebApiContextTest extends TestCase
{
    private static $servidor;
    private static $porta;

    public static function setUpBeforeClass(): void
    {
        self::$porta = 18000 + getmypid() % 1000;
        $comando = sprintf(
            '%s -S 127.0.0.1:%d %s',
            escapeshellarg(PHP_BINARY),
            self::$porta,
            escapeshellarg(__DIR__ . '/_servidor/eco.php')
        );
        self::$servidor = proc_open($comando, [['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']], $tubos);
        for ($i = 0; $i < 50 && ! @fsockopen('127.0.0.1', self::$porta); $i++) {
            usleep(100000);
        }
    }

    public static function tearDownAfterClass(): void
    {
        proc_terminate(self::$servidor);
    }

    /** Contexto com o cliente que a extensão monta, como o Behat faz. */
    private function contexto($baseUrl = null)
    {
        $container = new ContainerBuilder();
        (new WebApiExtension())->load($container, ['base_url' => $baseUrl ?: 'http://127.0.0.1:' . self::$porta . '/api/']);
        $contexto = new WebApiContext();
        $container->get('web_api.context_initializer')->initializeContext($contexto);
        return $contexto;
    }

    private static function privado(WebApiContext $contexto, $nome)
    {
        $propriedade = new \ReflectionProperty(WebApiContext::class, $nome);
        $propriedade->setAccessible(true);
        return $propriedade->getValue($contexto);
    }

    /** O que o servidor recebeu e o que o contexto guardou. */
    private function estado(WebApiContext $contexto)
    {
        $resposta = self::privado($contexto, 'response');
        $requisicao = self::privado($contexto, 'request');
        // a porta do servidor muda a cada execução
        return json_decode(preg_replace('/127\.0\.0\.1:\d+/', 'servidor', json_encode([
            'recebido' => json_decode((string) $resposta->getBody(), true),
            'status' => $resposta->getStatusCode(),
            'requisicao' => [$requisicao->getMethod(), (string) $requisicao->getUri(), $requisicao->getHeaders()],
            'headers' => self::privado($contexto, 'headers'),
        ])), true);
    }

    public function testRequisicoes()
    {
        $c = $this->contexto();
        $resultado = [];

        $c->iSetHeaderWithValue('Accept', 'application/json');
        $c->iSetHeaderWithValue('X-Teste', 'um');
        $c->iSetHeaderWithValue('X-Teste', 'dois');
        $c->iAmAuthenticatingAs('fulano@email.com', 'segredo');
        $c->iSendARequest('GET', '/usuario/1?filtro=a b');
        $resultado['GET com cabeçalho repetido e basic'] = $this->estado($c);

        $c->iAmAuthenticatingAs('outro', 'x');
        $c->setPlaceHolder('{id}', '42');
        $c->iSendARequestWithBody('POST', 'usuario/{id}', Pilha::texto("  {\"id\": \"{id}\", \"nome\": \"Ana\"}  \n"));
        $resultado['POST com corpo e placeholder'] = $this->estado($c);

        $c->iSendARequestWithValues('PUT', 'usuario/{id}', new TableNode([['nome', 'Beto {id}'], ['idade', '30']]));
        $resultado['PUT com valores'] = $this->estado($c);

        $c->iSendARequestWithFormData('POST', 'form', Pilha::texto("a=1\nb[]=x\nb[]=y"));
        $resultado['POST com form data'] = $this->estado($c);

        $c->iSendARequest('DELETE', '/usuario/{id}?status=404');
        $resultado['404'] = $this->estado($c);

        $c->iSendARequest('GET', 'erro?status=500');
        $resultado['500'] = $this->estado($c);

        $c->iSendARequest('GET', 'http://127.0.0.1:' . self::$porta . '/fora-da-api');
        $resultado['URL absoluta'] = $this->estado($c);

        Instantaneo::confere('webapi-requisicoes', $resultado);
    }

    public function testPassosDeResposta()
    {
        $c = $this->contexto();
        $c->iSendARequest('GET', 'x?status=201');
        $resultado = [];
        $passos = [
            'código 201' => function () use ($c) { $c->theResponseCodeShouldBe('201'); },
            'código 200' => function () use ($c) { $c->theResponseCodeShouldBe('200'); },
            'contém (sem maiúsculas)' => function () use ($c) { $c->theResponseShouldContain('resposta do eco'); },
            'contém texto com barra' => function () use ($c) { $c->theResponseShouldContain('/api/x'); },
            'contém o que não tem' => function () use ($c) { $c->theResponseShouldContain('nada'); },
            'não contém (com maiúsculas)' => function () use ($c) { $c->theResponseShouldNotContain('resposta do eco'); },
            'não contém o que tem' => function () use ($c) { $c->theResponseShouldNotContain('Resposta do ECO'); },
            'json: subconjunto' => function () use ($c) { $c->theResponseShouldContainJson(Pilha::texto('{"metodo": "GET"}')); },
            'json: valor diferente' => function () use ($c) { $c->theResponseShouldContainJson(Pilha::texto('{"metodo": "POST"}')); },
            'json: chave ausente' => function () use ($c) { $c->theResponseShouldContainJson(Pilha::texto('{"outra": 1}')); },
            'json: igualdade solta' => function () use ($c) { $c->theResponseShouldContainJson(Pilha::texto('{"cabecalhos": []}')); },
            'json inválido' => function () use ($c) { $c->theResponseShouldContainJson(Pilha::texto('{x}')); },
        ];
        foreach ($passos as $nome => $passo) {
            $resultado[$nome] = Pilha::resultado($passo);
        }
        ob_start();
        $c->printResponse();
        $resultado['print response'] = preg_replace('/127\.0\.0\.1:\d+/', 'servidor', ob_get_clean());
        Instantaneo::confere('webapi-respostas', $resultado);
    }

    public function testSemServidor()
    {
        $c = $this->contexto('http://127.0.0.1:1/api/');
        $resultado = Pilha::resultado(function () use ($c) {
            $c->iSendARequest('GET', 'x');
        });
        Instantaneo::confere('webapi-sem-servidor', $resultado);
    }

    /**
     * O que o FeatureContext do area-restrita faz por reflection: monta uma requisição multipart,
     * grava em 'request' e chama 'sendRequest'.
     */
    public function testRequisicaoMontadaDeFora()
    {
        $c = $this->contexto();
        $c->iSetHeaderWithValue('X-Teste', 'um');
        $headers = self::privado($c, 'headers');
        $propriedade = new \ReflectionProperty(WebApiContext::class, 'request');
        $propriedade->setAccessible(true);
        $propriedade->setValue($c, new \GuzzleHttp\Psr7\Request('POST', 'envio', $headers + ['Content-Type' => 'text/plain'], 'conteúdo'));
        $envia = new \ReflectionMethod(WebApiContext::class, 'sendRequest');
        $envia->setAccessible(true);
        $envia->invoke($c);
        Instantaneo::confere('webapi-de-fora', $this->estado($c));
    }
}
