<?php
namespace PandoraTestes\Fixture;

use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author vinicius
 *
 */
class FixtureBuilder
{

    protected $fixtureNamespace;

    protected $entitiesNamespace;

    protected $fixtureMetaData;

    protected $em;

    protected $entities;

    public function __construct(array $fixtureMetaData, $fixtureNamespace, $entitiesNamespace)
    {
        $this->fixtureMetaData = $fixtureMetaData;
        $this->fixtureNamespace = $fixtureNamespace;
        $this->entitiesNamespace = $entitiesNamespace;
        $this->entities = array();
    }

    /**
     * Load the base entities in the database.
     */
    public function loadBaseData()
    {
        if (! isset($this->fixtureMetaData['base']))
            return;
        foreach ($this->fixtureMetaData['base'] as $fixture)
            $this->load($fixture, true);
    }

    /**
     * Load the fixture in the database.
     * 
     * @param string $entity
     * @param boolean $append
     * @return mixed The generated entity
     */
    public function load($entity, $append)
    {
        if (! $append)
            $this->entities = array();
        if (isset($this->entities[$entity]))
            return $this->entities[$entity];
        
        $fixture = $this->_createFixture($entity);
        $this->_executeFixtures(array(
            $fixture
        ), $append);
        
        return $this->entities[$entity] = $fixture->getCreatedEntity();
    }

    /**
     * Clean the database.
     */
    public function clean()
    {
        $this->entities = array();
        $this->_executeFixtures(array(), false);
    }

    /**
     * @return \Doctrine\ORM\EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * @param EntityManagerInterface $em
     * @return \PandoraTestes\Fixture\FixtureBuilder
     */
    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;
        return $this;
    }

    /**
     *
     * @param string $entity            
     * @return AbstractFixture
     */
    protected function _createFixture($entity)
    {
        $entityTraits = explode(' ', $entity);
        $entity = ucfirst(array_pop($entityTraits));
        
        $fixture = $this->_createInstance($entity);
        
        foreach ($entityTraits as $trait)
            $fixture->useTrait($trait);
        
        return $fixture;
    }

    /**
     * @param string $entity
     * @return AbstractFixture
     */
    protected function _createInstance($entity)
    {
        $metadata = isset($this->fixtureMetaData[$entity]) ? $this->fixtureMetaData[$entity] : array();
        $identifier = isset($metadata['identifier']) ? $metadata['identifier'] : 'id';
        
        $entityName = $this->_buildEntityName($entity, $metadata);
        
        $class = new \ReflectionClass($this->fixtureNamespace . "\\$entity");
        
        return $class->newInstance($entityName, $identifier, $this);
    }

    /**
     * @param string $entity
     * @param array $metadata
     * @return string
     */
    protected function _buildEntityName($entity, array $metadata)
    {
        if (isset($metadata['entity_name']))
            return $metadata['entity_name'];
        return $this->entitiesNamespace . "\\$entity";
    }

    /**
     * @param array $fixtures
     * @param boolean $append
     */
    protected function _executeFixtures(array $fixtures, $append)
    {
        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->getEntityManager(), $purger);
        $executor->execute($fixtures, $append);
    }
}