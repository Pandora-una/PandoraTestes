<?php

namespace PandoraTestesTeste;

use Behat\WebApiExtension\Context\WebApiContext;
use GuzzleHttp\Psr7\Response;
use PandoraTestes\Fixture\FixtureBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Caracterização dos passos e ganchos do PandoraContext que a suíte srv usa: exists an "...",
 * the "campo" of "fixture" is "valor", a limpeza antes de cada cenário, a limpeza depois da
 * suíte, print pretty response e a factory do FixtureBuilder.
 */
class PandoraContextTest extends TestCase
{
    private $em;

    private function aplicacao(array $config = [])
    {
        $this->em = Pilha::entityManager([__DIR__ . '/src/Entity']);
        Pilha::criaTabelas($this->em);
        $builder = new FixtureBuilder(['base' => ['nome:Base autor']], 'PandoraTestesTeste\Fixture', 'PandoraTestesTeste\Entity');
        $builder->setEntityManager($this->em);
        $container = new \Laminas\ServiceManager\ServiceManager();
        $container->setService('config', $config);
        $container->setService('PandoraTestes\Fixture\FixtureBuilder', $builder);
        $aplicacao = new class($container) {
            private $container;

            public function __construct($container)
            {
                $this->container = $container;
            }

            public function getServiceManager()
            {
                return $this->container;
            }
        };
        ContextoDeTeste::usaAplicacao($aplicacao);
        return new ContextoDeTeste();
    }

    private function autores()
    {
        $resultado = $this->em->getConnection()->executeQuery('SELECT id, nome, ativo FROM autor ORDER BY id');
        $linhas = method_exists($resultado, 'fetchAllAssociative') ? $resultado->fetchAllAssociative() : $resultado->fetchAll();
        return array_map(function ($linha) {
            return array_map('strval', $linha);
        }, $linhas);
    }

    public function testPassosDeFixture()
    {
        $c = $this->aplicacao();
        $c->loadBasicData();
        $c->givenExists('autor');
        $c->givenExists('autor');
        $c->theFieldOfFixtureIsValue('nome', 'autor', 'Trocado');
        $c->theFieldOfFixtureIsValue('ativo', 'autor', 'false');
        $c->givenExists('inativo autor');
        $erro = Pilha::resultado(function () use ($c) {
            $c->theFieldOfFixtureIsValue('cor', 'autor', 'azul');
        });
        $antesDeLimpar = $this->autores();
        $c->loadBasicData();
        Instantaneo::confere('contexto-fixture', [
            'antes de limpar' => $antesDeLimpar,
            'campo sem setter' => $erro,
            'depois do loadBasicData' => $this->autores(),
            'processValue' => [$c->valor('null'), $c->valor('false'), $c->valor('true'), $c->valor('0'), $c->valor('')],
        ]);
    }

    public function testLimpezaDepoisDaSuite()
    {
        $resultado = [];
        foreach (['sem config' => [], 'ligada' => ['pandora-testes' => ['clean-after-suite' => true]]] as $nome => $config) {
            $c = $this->aplicacao($config);
            $c->givenExists('autor');
            ContextoDeTeste::clean();
            $resultado[$nome] = count($this->autores());
        }
        Instantaneo::confere('contexto-limpeza-suite', $resultado);
    }

    public function testPrintPrettyResponse()
    {
        $c = $this->aplicacao();
        $resultado = [];
        foreach (['json' => '{"a":1,"b":[1,2]}', 'html' => '<p>Ol&aacute; <b>mundo</b></p>'] as $nome => $corpo) {
            $webApi = new WebApiContext();
            Pilha::respostaNoWebApi($webApi, new Response(200, [], $corpo));
            Pilha::ligaWebApi($c, $webApi);
            ob_start();
            $c->printPrettyResponse();
            $resultado[$nome] = ob_get_clean();
        }
        Instantaneo::confere('contexto-print', $resultado);
    }

    public function testFactoryDoFixtureBuilder()
    {
        $em = Pilha::entityManager([__DIR__ . '/src/Entity']);
        $resultado = [];
        foreach ([
            'sem config' => [],
            'com conexão' => ['pandora-testes' => [
                'fixtures_namespace' => 'X\Fixture',
                'entities_namespace' => 'X\Entity',
                'fixtures' => ['base' => ['a']],
                'connection' => ['memory' => true, 'driver' => 'pdo_sqlite'],
            ]],
        ] as $nome => $config) {
            $container = new \Laminas\ServiceManager\ServiceManager();
            $container->setService('config', $config);
            $container->setService('Doctrine\ORM\EntityManager', $em);
            $factory = new \PandoraTestes\Fixture\Factory\FixtureBuilderFactory();
            $builder = method_exists($factory, '__invoke')
                ? $factory($container, 'PandoraTestes\Fixture\FixtureBuilder')
                : $factory->createService($container);
            $propriedades = [];
            foreach (['fixtureNamespace', 'entitiesNamespace', 'fixtureMetaData', 'em'] as $nomePropriedade) {
                $propriedade = new \ReflectionProperty($builder, $nomePropriedade);
                $propriedade->setAccessible(true);
                $propriedades[$nomePropriedade] = $propriedade->getValue($builder);
            }
            $outroEm = $propriedades['em'];
            $propriedades['em'] = $outroEm === null ? null : [
                'é outro EntityManager' => $outroEm !== $em,
                'mesma configuração' => $outroEm->getConfiguration() === $em->getConfiguration(),
                'outra conexão' => $outroEm->getConnection() !== $em->getConnection(),
                'parâmetros' => array_intersect_key($outroEm->getConnection()->getParams(), ['memory' => 1, 'driver' => 1, 'pdo' => 1]),
            ];
            $resultado[$nome] = $propriedades;
        }
        Instantaneo::confere('contexto-factory', $resultado);
    }
}
