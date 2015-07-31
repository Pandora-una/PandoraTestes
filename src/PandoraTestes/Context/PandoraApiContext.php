<?php
namespace PandoraTestes\Context;

use Behat\WebApiExtension\Context\WebApiContext;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use PandoraTestes\Fixture\FixtureBuilder;

/**
 * Passos
 */
abstract class PandoraApiContext extends WebApiContext
{

    protected static $zendApp;

    protected $_fixtureBuilder;

    abstract static function initializeZendFramework();

    /**
     * Create entity from fixtures
     *
     * @Given /^exists an? "([^"]*)"$/
     */
    public function givenExists($entity)
    {
        $this->getFixtureBuilder()->load($entity, true, $this->_getEntityManager());
    }

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->addHeader('Accept', 'application/json');
        $this->addHeader('x-pandora-teste', 1);
    }

    /**
     * @AfterSuite
     */
    public static function clean()
    {
        (new ORMPurger(self::$zendApp->getServiceManager()->get('Doctrine\ORM\EntityManager')))->purge();
    }

    /**
     * @BeforeScenario
     */
    public function loadBasicData()
    {
        $this->getFixtureBuilder()->clean();
        $this->getFixtureBuilder()->loadBaseData();
    }

    /**
     *
     * @return Doctrine\ORM\EntityManager
     */
    protected function _getEntityManager()
    {
        return self::$zendApp->getServiceManager()->get('Doctrine\ORM\EntityManager');
    }

    /**
     *
     * @return FixtureBuilder
     */
    protected function getFixtureBuilder()
    {
        if (! $this->_fixtureBuilder) {
            $this->_fixtureBuilder = self::$zendApp->getServiceManager()->get('PandoraTestes\Fixture\FixtureBuilder');
            $this->_fixtureBuilder->setEntityManager($this->_getEntityManager());
        }
        return $this->_fixtureBuilder;
    }
}
