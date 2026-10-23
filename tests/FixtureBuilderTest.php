<?php

namespace PandoraTestesTeste;

use PandoraTestes\Fixture\FixtureBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Caracterização do FixtureBuilder e da AbstractFixture (121 fixtures no area-restrita): params,
 * traits, "campo:valor", associações (uma e em lista), id atribuído, reaproveitamento da entidade
 * já carregada, limpeza do banco e mensagens de erro.
 */
class FixtureBuilderTest extends TestCase
{
    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    private function builder(array $metadados = [])
    {
        $this->em = Pilha::entityManager([__DIR__ . '/src/Entity']);
        (new \Doctrine\ORM\Tools\SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $metadados += ['Estante' => ['entity_name' => 'PandoraTestesTeste\Entity\Autor']];
        $builder = new FixtureBuilder($metadados, 'PandoraTestesTeste\Fixture', 'PandoraTestesTeste\Entity');
        $builder->setEntityManager($this->em);
        return $builder;
    }

    private function tabelas()
    {
        $conexao = $this->em->getConnection();
        $linhas = function ($sql) use ($conexao) {
            $resultado = $conexao->executeQuery($sql);
            $todas = method_exists($resultado, 'fetchAllAssociative') ? $resultado->fetchAllAssociative() : $resultado->fetchAll();
            // o PDO SQLite do PHP 8.1+ devolve inteiro onde o 7.2 devolve texto
            return array_map(function ($linha) {
                return array_map(function ($v) {
                    return $v === null ? null : (string) $v;
                }, $linha);
            }, $todas);
        };
        return [
            'autor' => $linhas('SELECT * FROM autor ORDER BY id'),
            'livro' => $linhas('SELECT * FROM livro ORDER BY id'),
        ];
    }

    public function testCarregaComParamsTraitsECampoValor()
    {
        $b = $this->builder();
        $resultado = [];
        foreach (['autor', 'inativo autor', 'nome:Beto autor', 'maiusculo autor', 'inativo nome:Caio autor'] as $fixture) {
            $entidade = $b->load($fixture, true);
            $resultado[$fixture] = [get_class($entidade), $entidade->getId(), $entidade->getNome(), $entidade->getAtivo()];
        }
        $resultado['tabelas'] = $this->tabelas();
        Instantaneo::confere('fixture-params', $resultado);
    }

    /**
     * Id informado na fixture (trait ou "id:N", como em exists an "id:1 usuario"). É o primeiro
     * insert de cada banco: no SQLite (IDENTITY) o Doctrine guarda o SQL do insert sem a coluna
     * id; no PostgreSQL dos sistemas (SEQUENCE) o insert sempre leva o id.
     */
    public function testIdAtribuido()
    {
        $resultado = [];
        foreach (['comId autor', 'id:7 autor', 'id:3 livro'] as $fixture) {
            $b = $this->builder();
            $entidade = $b->load($fixture, true);
            $resultado[$fixture] = [$entidade->getId(), $this->tabelas()];
        }
        Instantaneo::confere('fixture-id', $resultado);
    }

    public function testMesmaFixtureDuasVezesReaproveita()
    {
        $b = $this->builder();
        $primeira = $b->load('autor', true);
        $this->em->clear();
        $segunda = $b->load('autor', true);
        $terceira = $b->load('autor', false);
        Instantaneo::confere('fixture-reaproveita', [
            'mesmo id' => $primeira->getId() === $segunda->getId(),
            'segunda é gerenciada' => $this->em->contains($segunda),
            'sem append cria outra' => [$terceira->getId(), count($this->tabelas()['autor'])],
        ]);
    }

    public function testAssociacoes()
    {
        $b = $this->builder();
        $livro = $b->load('livro', true);
        $deInativo = $b->load('deInativo livro', true);
        $semAutor = $b->load('semAutor livro', true);
        $estante = $b->load('estante', true);
        $this->em->clear();
        Instantaneo::confere('fixture-associacoes', [
            'livro' => [$livro->getId(), $livro->getAutor() ? $livro->getAutor()->getNome() : null],
            'deInativo livro' => [$deInativo->getId(), $deInativo->getAutor()->getAtivo()],
            'semAutor livro' => [$semAutor->getId(), $semAutor->getAutor()],
            'estante' => [$estante->getId(), $estante->getNome()],
            'tabelas' => $this->tabelas(),
        ]);
    }

    public function testLimpaEBase()
    {
        $b = $this->builder(['base' => ['autor', 'nome:Base autor']]);
        $b->load('livro', true);
        $b->clean();
        $depoisDeLimpar = $this->tabelas();
        $b->loadBaseData();
        Instantaneo::confere('fixture-limpa-e-base', ['limpo' => $depoisDeLimpar, 'base' => $this->tabelas()]);
    }

    public function testUpdateRegistraOutraEntidade()
    {
        $b = $this->builder();
        $b->load('autor', true);
        $outro = $b->load('nome:Outro autor', true);
        $b->update('autor', $outro);
        $semTrocar = $b->load('autor', true)->getNome();
        $b->update('autor', $outro, true);
        $trocado = $b->load('autor', true)->getNome();
        Instantaneo::confere('fixture-update', [$semTrocar, $trocado]);
    }

    public function testErros()
    {
        $b = $this->builder();
        $resultado = [];
        foreach (['traitInexistente autor', 'callbackInvalido autor', 'inexistente', 'nome:x livro'] as $fixture) {
            try {
                $b->load($fixture, true);
                $resultado[$fixture] = 'carregou';
            } catch (\Throwable $e) {
                // tira a cor do terminal e o que vem do driver (texto do SQLite muda de versão)
                // e as aspas no nome da classe, que o PHP 8 pôs na mensagem da ReflectionException
                $mensagem = str_replace('"', '', preg_replace('/\e\[[0-9;]*m/', '', $e->getMessage()));
                $resultado[$fixture] = [get_class($e), $e->getCode(), strtok($mensagem, "\n")];
            }
        }
        $semEm = new FixtureBuilder([], 'x', 'y');
        try {
            $semEm->getEntityManager();
        } catch (\Throwable $e) {
            $resultado['sem conexão'] = [get_class($e), $e->getMessage()];
        }
        Instantaneo::confere('fixture-erros', $resultado);
    }

    /** O clean() só liga o session_replication_role no PostgreSQL. */
    public function testDeteccaoDePostgres()
    {
        $b = new class([], 'x', 'y') extends FixtureBuilder {
            public function ehPostgres($em)
            {
                return $this->_isPostgres($em);
            }
        };
        $resultado = [];
        foreach (['pdo_pgsql', 'pdo_sqlite'] as $driver) {
            $config = new \Doctrine\ORM\Configuration();
            $config->setMetadataDriverImpl(new \Doctrine\ORM\Mapping\Driver\AnnotationDriver(new \Doctrine\Common\Annotations\AnnotationReader(), []));
            $config->setProxyDir(sys_get_temp_dir());
            $config->setProxyNamespace('P');
            // sem conectar: a versão do servidor vai nos parâmetros
            $conexao = \Doctrine\DBAL\DriverManager::getConnection(
                ['driver' => $driver, 'memory' => true, 'serverVersion' => '13', 'host' => 'nenhum'],
                $config
            );
            $construtor = new \ReflectionMethod(\Doctrine\ORM\EntityManager::class, '__construct');
            $em = $construtor->isPublic()
                ? new \Doctrine\ORM\EntityManager($conexao, $config)
                : \Doctrine\ORM\EntityManager::create($conexao, $config);
            $resultado[$driver] = $b->ehPostgres($em);
        }
        Instantaneo::confere('fixture-postgres', $resultado);
    }
}
