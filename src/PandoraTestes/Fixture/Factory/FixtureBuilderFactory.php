<?php
namespace PandoraTestes\Fixture\Factory;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use PandoraTestes\Fixture\FixtureBuilder;
use Psr\Container\ContainerInterface;

class FixtureBuilderFactory
{

    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        if (! $services->has('config')) {
            throw new ServiceNotCreatedException('Config não existe');
        }
        if(isset($services->get('config')['pandora-testes']))
            $config = $services->get('config')['pandora-testes'];
        else 
            $config = array();
        $fixtureNamespace = isset($config['fixtures_namespace']) ? $config['fixtures_namespace'] : 'Application\Fixture';
        $fixtureMetaData = isset($config['fixtures']) ? $config['fixtures'] : array();
        $entitiesNamespace = isset($config['entities_namespace']) ? $config['entities_namespace'] : 'Application\Entity';

        $fixtureBuilder = new FixtureBuilder($fixtureMetaData, $fixtureNamespace, $entitiesNamespace);
        if (isset($config['connection']) && is_array($config['connection']) && !empty($config['connection'])) {
            $defaultEntityManager = $services->get('Doctrine\ORM\EntityManager');
            $fixtureBuilder->setEntityManager(
                $this->createEntityManager($defaultEntityManager, $config['connection'])
            );
        }

        return $fixtureBuilder;
    }

    /**
     * @param EntityManagerInterface $defaultEntityManager
     * @param array                  $connectionConfig
     *
     * @return EntityManagerInterface
     */
    protected function createEntityManager(
        EntityManagerInterface $defaultEntityManager,
        array $connectionConfig
    ) {
        $connectionParams = array_merge(
            $defaultEntityManager->getConnection()->getParams(),
            $connectionConfig
        );

        unset($connectionParams['pdo']);

        // o EntityManager::create() está depreciado no ORM 2.20: a mesma montagem, à mão
        $configuration = $defaultEntityManager->getConfiguration();
        $eventManager = $defaultEntityManager->getEventManager();

        return new EntityManager(
            DriverManager::getConnection($connectionParams, $configuration, $eventManager),
            $configuration,
            $eventManager
        );
    }
}
