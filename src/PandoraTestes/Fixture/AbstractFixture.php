<?php
namespace PandoraTestes\Fixture;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use PandoraBase\Entity\AbstractEntity;
use Doctrine\ORM\EntityManagerInterface;

/**
 *
 * @author vinicius
 *        
 */
abstract class AbstractFixture implements FixtureInterface
{

    protected $entityName;

    protected $identifier;

    protected $builder;

    protected $entity;

    /**
     *
     * @param string $entityName            
     * @param string $identifier            
     * @param FixtureBuilder $builder            
     */
    public function __construct($entityName, $identifier, FixtureBuilder $builder)
    {
        $this->entityName = $entityName;
        $this->identifier = $identifier;
        $this->builder = $builder;
    }

    /*
     * (non-PHPdoc)
     * @see \Doctrine\Common\DataFixtures\FixtureInterface::load()
     */
    public function load(ObjectManager $manager)
    {
        $entity = $this->_newEntity();
        
        foreach ($this->_getParams() as $param => $value)
            $this->_applyParam($entity, $param, $value);
        
        $this->_applyAssociations($entity);
        
        $this->__commit($manager, $entity);
        
        $this->_updateIdentifier($entity, $manager);
        
        $this->entity = $entity;
    }

    /**
     * Load specific trait into the fixture.
     *
     * @param string $trait            
     * @throws \Exception
     */
    public function useTrait($trait)
    {
        if (! isset($this->traits[$trait]))
            throw new \Exception("Trait '$trait' não existe");
        
        $traitParams = $this->traits[$trait];
        
        $traitParams = $this->_useTraitAssociations($traitParams);
        
        foreach ($traitParams as $param => $value)
            $this->_setParam($param, $value);
    }

    /**
     * Get the generated entity
     *
     * @return object
     */
    public function getCreatedEntity()
    {
        return $this->entity;
    }

    /**
     *
     * @param unknown $entity            
     * @param EntityManagerInterface $manager            
     */
    protected function _updateIdentifier($entity, EntityManagerInterface $manager)
    {
        if (isset($this->_getParams()[$this->identifier])) {
            $this->_applyParam($entity, $this->identifier, $this->_getParams()[$this->identifier]);
            $this->__commit($manager, $entity);
        }
    }

    /**
     *
     * @param object $entity            
     * @param string $param            
     * @param mixed $value            
     * @throws \Exception
     */
    protected function _applyParam($entity, $param, $value)
    {
        $method = 'set' . ucfirst($param);
        
        if (! method_exists($entity, $method)) {
            throw new \Exception("A Entidade $this->entityName não possui o método $method");
        }
        
        call_user_func_array(array(
            $entity,
            $method
        ), array(
            $value
        ));
    }

    /**
     * @param object $entity
     */
    protected function _applyAssociations($entity)
    {
        foreach ($this->_getAssociations() as $fixture)
            $this->builder->load($fixture, true);
        foreach ($this->_getAssociations() as $param => $fixture)
            $this->_applyAssociation($entity, $param, $fixture);
    }

    /**
     *
     * @param object $entity            
     * @param string $param            
     * @param string $fixture            
     */
    protected function _applyAssociation($entity, $param, $fixture)
    {
        $association = $this->builder->load($fixture, true);
        $association = $this->builder->getEntityManager()->merge($association);
        $this->_applyParam($entity, $param, $association);
    }

    /**
     *
     * @throws \Exception
     * @return object
     */
    protected function _newEntity()
    {
        if (! class_exists($this->entityName))
            throw new \Exception("A entidade $this->entityName não existe");
        
        $entityClass = new \ReflectionClass($this->entityName);
        return $entityClass->newInstance();
    }

    /**
     *
     * @throws \Exception
     * @return array
     */
    protected function _getParams()
    {
        if (! property_exists($this, 'params'))
            throw new \Exception("É preciso preencher o atributo 'params' desta fixture");
        
        return $this->params;
    }
    
    protected function _useTraitAssociations(array $traitParams){
        if (!isset($traitParams['_associations']))
            return $traitParams;
        foreach ($traitParams['_associations'] as $param => $fixture)
            $this->_setAssociation($param, $fixture);
        unset($traitParams['_associations']);
        return $traitParams;
    }

    /**
     *
     * @param string $param            
     * @param mixed $value            
     * @throws \Exception
     */
    protected function _setParam($param, $value)
    {
        if (! property_exists($this, 'params'))
            throw new \Exception("É preciso preencher o atributo 'params' desta fixture");
        
        $this->params[$param] = $value;
    }
    
    protected function _setAssociation($param, $value)
    {
        if (! property_exists($this, 'associations'))
            throw new \Exception("É preciso preencher o atributo 'associations' desta fixture");
    
            $this->associations[$param] = $value;
    }
    


    /**
     *
     * @return array
     */
    protected function _getTraits()
    {
        return $this->traits;
    }

    /**
     *
     * @return array
     */
    protected function _getAssociations()
    {
        if (! property_exists($this, 'associations'))
            return array();
        return $this->associations;
    }

    /**
     *
     * @param EntityManagerInterface $manager            
     * @param object $entity            
     */
    private function __commit(EntityManagerInterface $manager, $entity)
    {
        $manager->persist($entity);
        $manager->flush();
    }
}