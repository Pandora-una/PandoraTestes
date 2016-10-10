<?php

namespace PandoraTestes\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author vinicius
 */
abstract class AbstractFixture implements FixtureInterface
{
    protected $entityName;

    protected $identifier;

    protected $builder;

    protected $entity;

    /**
     * @param string         $entityName
     * @param string         $identifier
     * @param FixtureBuilder $builder
     */
    public function __construct($entityName, $identifier, FixtureBuilder $builder)
    {
        $this->entityName = $entityName;
        $this->identifier = $identifier;
        $this->builder = $builder;
    }

    /**
     * Get the generated entity.
     *
     * @return object
     */
    public function getCreatedEntity()
    {
        return $this->entity;
    }

    /*
     * (non-PHPdoc)
     * @see \Doctrine\Common\DataFixtures\FixtureInterface::load()
     */
    public function load(ObjectManager $manager)
    {
        $entity = $this->_newEntity();

        foreach ($this->_getParams() as $param => $value) {
            $this->_applyParam($entity, $param, $value);
        }

        $this->_applyAssociations($entity);

        $this->__commit($manager, $entity);
        $this->_updateIdentifier($entity, $manager);

        $this->entity = $entity;
    }

    /**
     * Load specific trait into the fixture.
     *
     * @param string $trait
     *
     * @throws \Exception
     */
    public function useTrait($trait)
    {
        if (!isset($this->traits[$trait])) {
            throw new \Exception("Trait '$trait' não existe");
        }

        $traitParams = $this->traits[$trait];

        $traitParams = $this->_useTraitAssociations($traitParams);

        foreach ($traitParams as $param => $value) {
            $this->_setParam($param, $value);
        }
    }

    /**
     * @param EntityManagerInterface $manager
     * @param object                 $entity
     */
    protected function __commit(EntityManagerInterface $manager, $entity)
    {
        $manager->persist($entity);
        $manager->flush();
    }

    /**
     * @param object $entity
     * @param string $param
     * @param string $fixture
     */
    protected function _applyAssociation($entity, $param, $fixture)
    {
        if (is_array($fixture)) {
            $collection = array();
            foreach ($fixture as $singleFixture) {
                $collection[] = $this->_loadAssociation($singleFixture);
            }
            $this->_applyParam($entity, $param, new ArrayCollection($collection), 'add');
        } else {
            if ($fixture) {
                $association = $this->_loadAssociation($fixture);
            } else {
                $association = null;
            }
            $this->_applyParam($entity, $param, $association);
        }
    }

    /**
     * @param object $entity
     */
    protected function _applyAssociations($entity)
    {
        foreach ($this->_getAssociations() as $fixtures) {
            if (!$fixtures) {
                continue;
            }
            if (!is_array($fixtures)) {
                $fixtures = array($fixtures);
            }
            foreach ($fixtures as $fixture) {
                $this->builder->load($fixture, true);
            }
        }
        foreach ($this->_getAssociations() as $param => $fixture) {
            $this->_applyAssociation($entity, $param, $fixture);
        }
    }

    /**
     * @param object $entity
     * @param string $param
     * @param mixed  $value
     *
     * @throws \Exception
     */
    protected function _applyParam($entity, $param, $value, $prefix = 'set')
    {
        $method = $prefix.ucfirst($param);

        if (!method_exists($entity, $method)) {
            throw new \Exception("A Entidade $this->entityName não possui o método $method");
        }

        try {
            $value = $this->applyCallback($value);
        } catch (\Exception $e) {
            throw new \Exception("Erro no callback do parametro $param", 500);
        }

        call_user_func_array(array(
            $entity,
            $method,
        ), array(
            $value,
        ));
    }

    /**
     * @return array
     */
    protected function _getAssociations()
    {
        if (!property_exists($this, 'associations')) {
            return array();
        }

        return $this->associations;
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    protected function _getParams()
    {
        if (!property_exists($this, 'params')) {
            throw new \Exception("É preciso preencher o atributo 'params' desta fixture");
        }

        return $this->params;
    }

    /**
     * @return array
     */
    protected function _getTraits()
    {
        return $this->traits;
    }

    /**
     * @param string $fixture
     *
     * @return AbstractEntity
     */
    protected function _loadAssociation($fixture)
    {
        $association = $this->builder->load($fixture, true);

        return $this->builder->getEntityManager()->merge($association);
    }

    /**
     * @throws \Exception
     *
     * @return object
     */
    protected function _newEntity()
    {
        if (!class_exists($this->entityName)) {
            throw new \Exception("A entidade $this->entityName não existe");
        }

        $entityClass = new \ReflectionClass($this->entityName);

        return $entityClass->newInstance();
    }

    protected function _setAssociation($param, $value)
    {
        if (!property_exists($this, 'associations')) {
            throw new \Exception("É preciso preencher o atributo 'associations' desta fixture");
        }

        $this->associations[$param] = $value;
    }

    /**
     * @param string $param
     * @param mixed  $value
     *
     * @throws \Exception
     */
    protected function _setParam($param, $value)
    {
        if (!property_exists($this, 'params')) {
            throw new \Exception("É preciso preencher o atributo 'params' desta fixture");
        }

        $this->params[$param] = $value;
    }

    /**
     * @param unknown                $entity
     * @param EntityManagerInterface $manager
     */
    protected function _updateIdentifier($entity, EntityManagerInterface $manager)
    {
        if (isset($this->_getParams()[$this->identifier])) {
            $this->_applyParam($entity, $this->identifier, $this->_getParams()[$this->identifier]);
            $this->__commit($manager, $entity);
        }
    }

    protected function _useTraitAssociations(array $traitParams)
    {
        if (!isset($traitParams['_associations'])) {
            return $traitParams;
        }
        foreach ($traitParams['_associations'] as $param => $fixture) {
            $this->_setAssociation($param, $fixture);
        }
        unset($traitParams['_associations']);

        return $traitParams;
    }

    protected function applyCallback($value)
    {
        if (!is_array($value) || !isset($value['callback'])) {
            return $value;
        }

        if (!is_callable($value['callback'])) {
            throw new \Exception('Callback inválido', 500);
        }

        return call_user_func($value['callback'], $value['value']);
    }
}
