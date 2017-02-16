<?php

namespace PandoraTestes\Fixture;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author vinicius
 */
class FixtureBuilder
{
    protected $fixtureNamespace;

    protected $entitiesNamespace;

    protected $fixtureMetaData;

    protected $em;

    protected $entities;

    protected $mostrou = false;

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
        if (!isset($this->fixtureMetaData['base'])) {
            return;
        }
        foreach ($this->fixtureMetaData['base'] as $fixture) {
            $this->load($fixture, true);
        }
    }

    /**
     * Load the fixture in the database.
     *
     * @param string $entity
     * @param bool   $append
     *
     * @return mixed The generated entity
     */
    public function load($entity, $append)
    {
        if (!$append) {
            $this->entities = array();
        }
        if (isset($this->entities[$entity])) {
            return $this->entities[$entity];
        }

        $fixture = $this->_createFixture($entity);
        try {
            $this->_executeFixtures(array(
                $fixture,
            ), $append);
        } catch (\Exception $e) {
            $message = $this->_criaMensagem($e, $entity);
            throw new \Exception($message, $e->getCode());
        }

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
     *
     * @return \PandoraTestes\Fixture\FixtureBuilder
     */
    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;

        return $this;
    }

    /**
     * @param string $entity
     *
     * @return AbstractFixture
     */
    protected function _createFixture($entity)
    {
        $entityTraits = explode(' ', $entity);
        $entity = ucfirst(array_pop($entityTraits));

        $fixture = $this->_createInstance($entity);

        foreach ($entityTraits as $trait) {
            $fixture->useTrait($trait);
        }

        return $fixture;
    }

    /**
     * @param string $entity
     *
     * @return AbstractFixture
     */
    protected function _createInstance($entity)
    {
        $metadata = isset($this->fixtureMetaData[$entity]) ? $this->fixtureMetaData[$entity] : array();
        $identifier = isset($metadata['identifier']) ? $metadata['identifier'] : 'id';

        $entityName = $this->_buildEntityName($entity, $metadata);

        $class = new \ReflectionClass($this->fixtureNamespace."\\$entity");

        return $class->newInstance($entityName, $identifier, $this);
    }

    /**
     * @param string $entity
     * @param array  $metadata
     *
     * @return string
     */
    protected function _buildEntityName($entity, array $metadata)
    {
        if (isset($metadata['entity_name'])) {
            return $metadata['entity_name'];
        }

        return $this->entitiesNamespace."\\$entity";
    }

    /**
     * @param array $fixtures
     * @param bool  $append
     */
    protected function _executeFixtures(array $fixtures, $append)
    {
        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->getEntityManager(), $purger);
        $executor->execute($fixtures, $append);
    }

    /**
     *
     * @param  \Exception $e
     * @param  string     $entity
     *
     * @return string
     */
    protected function _criaMensagem(\Exception $e, $entity)
    {
        $message = "Erro ao gerar a fixture '\033[1m$entity\033[0m\033[31m' devido a\n\n" . $e->getMessage();

        if (!$this->mostrou) {
            $message .=
                "\n\n\033[1mAs fixtures geradas previamente foram:\033[0m\033[31m \n\n" .
                implode("\n", array_keys($this->entities)) .
                "\n\n";
            $this->mostrou = true;
        }

        return $message;
    }
}
