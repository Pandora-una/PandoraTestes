<?php

namespace PandoraTestesTeste;

use Behat\Gherkin\Node\PyStringNode;
use Behat\WebApiExtension\Context\WebApiContext;

/** Ajudas que servem às duas pilhas. */
class Pilha
{
    public static function texto($json)
    {
        return new PyStringNode(explode("\n", $json), 1);
    }

    /** Põe a resposta no WebApiContext (privada, como o próprio pandora-testes lê). */
    public static function respostaNoWebApi(WebApiContext $webApi, $resposta)
    {
        $propriedade = new \ReflectionProperty(WebApiContext::class, 'response');
        $propriedade->setAccessible(true);
        $propriedade->setValue($webApi, $resposta);
    }

    /** Liga o contexto ao WebApiContext, como o gatherContexts faria. */
    public static function ligaWebApi($contexto, WebApiContext $webApi)
    {
        $liga = function ($webApi) {
            $this->_webApi = $webApi;
        };
        \Closure::bind($liga, $contexto, get_class($contexto))->__invoke($webApi);
    }

    /**
     * Roda um passo e devolve 'passa', 'falha' (asserção) ou 'erro' (outra exceção).
     */
    public static function resultado(callable $passo)
    {
        try {
            $passo();
            return 'passa';
        } catch (\Throwable $e) {
            $falhas = ['PHPUnit\Framework\AssertionFailedError', 'PHPUnit_Framework_AssertionFailedError'];
            foreach ($falhas as $falha) {
                if (class_exists($falha) && $e instanceof $falha) {
                    return 'falha';
                }
            }
            return 'erro';
        }
    }

    /**
     * Tabelas de autor e livro com o mesmo DDL nas duas pilhas: o SchemaTool do DBAL 3 cria a chave
     * do SQLite com AUTOINCREMENT, que não reinicia depois do purge (o do DBAL 2.6 reiniciava). Nos
     * sistemas (PostgreSQL) o purge nunca reiniciou sequence.
     */
    public static function criaTabelas($em)
    {
        $conexao = $em->getConnection();
        $executa = method_exists($conexao, 'executeStatement') ? 'executeStatement' : 'exec';
        $conexao->$executa('CREATE TABLE autor (id INTEGER NOT NULL, nome VARCHAR(255) NOT NULL, ativo BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $conexao->$executa('CREATE TABLE livro (id INTEGER NOT NULL, autor_id INTEGER DEFAULT NULL, titulo VARCHAR(255) NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_LIVRO_AUTOR FOREIGN KEY (autor_id) REFERENCES autor (id))');
    }

    public static function entityManager(array $caminhos)
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataDriverImpl(new \Doctrine\ORM\Mapping\Driver\AnnotationDriver(
            new \Doctrine\Common\Annotations\AnnotationReader(),
            $caminhos
        ));
        $config->setProxyDir(sys_get_temp_dir() . '/ptestes-proxies');
        $config->setProxyNamespace('PtestesProxies');
        $config->setAutoGenerateProxyClasses(true);
        $conexao = \Doctrine\DBAL\DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $construtor = new \ReflectionMethod(\Doctrine\ORM\EntityManager::class, '__construct');
        return $construtor->isPublic()
            ? new \Doctrine\ORM\EntityManager($conexao, $config)
            : \Doctrine\ORM\EntityManager::create($conexao, $config);
    }
}
