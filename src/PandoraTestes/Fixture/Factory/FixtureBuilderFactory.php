<?php
namespace PandoraTestes\Fixture\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use PandoraTestes\Fixture\FixtureBuilder;

class FixtureBuilderFactory implements FactoryInterface
{

    /* (non-PHPdoc)
     * @see \Zend\ServiceManager\FactoryInterface::createService()
     */
    public function createService(ServiceLocatorInterface $services)
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
        if (isset($config['clean_connection']) && is_array($config['clean_connection'])) {
            $defaultEntityManager = $services->get('Doctrine\ORM\EntityManager');
            $fixtureBuilder->setCleanEntityManager(
                $this->createCleanEntityManager($defaultEntityManager, $config['clean_connection'])
            );
        }

        return $fixtureBuilder;
    }

    /**
     * @param EntityManagerInterface $defaultEntityManager
     * @param array                  $cleanConnectionConfig
     *
     * @return EntityManagerInterface
     */
    protected function createCleanEntityManager(
        EntityManagerInterface $defaultEntityManager,
        array $cleanConnectionConfig
    ) {
        $connectionParams = array_merge(
            $defaultEntityManager->getConnection()->getParams(),
            $cleanConnectionConfig
        );

        unset($connectionParams['pdo']);

        return EntityManager::create(
            $connectionParams,
            $defaultEntityManager->getConfiguration(),
            $defaultEntityManager->getEventManager()
        );
    }
}
