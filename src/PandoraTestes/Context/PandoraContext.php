<?php

namespace PandoraTestes\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Mink;
use Behat\MinkExtension\Context\MinkAwareContext;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use PandoraTestes\Fixture\FixtureBuilder;

/**
 * Passos.
 */
abstract class PandoraContext implements Context, MinkAwareContext
{
    protected static $zendApp;
    protected static $_cleanAfterSuite;

    protected $_fixtureBuilder;
    protected $_mink;
    protected $_minkParameters;
    protected $_webApi;
    protected $_spin;

    abstract public static function initializeZendFramework();

    /**
     * Create entity from fixtures.
     *
     * @Given /^exists an? "([^"]*)"$/
     */
    public function givenExists($entity)
    {
        $this->getFixtureBuilder()->load($entity, true, $this->_getEntityManager());
    }

    /**
     * Create entity from fixtures.
     *
     * @Given /^the "([^"]*)" of "([^"]*)" is "([^"]*)"$/
     */
    public function theFieldOfFixtureIsValue($field, $fixture, $value)
    {
        $entity = $this->getFixtureBuilder()->load($fixture, true, $this->_getEntityManager());
        $method = 'set' . ucfirst($field);
        if (!method_exists($entity, $method)) {
            $class = get_class($entity);
            throw new \Exception("There is no method named $method in class $class", 1);
        }
        $entity = $this->_getEntityManager()->merge($entity);
        $entity->{$method}($value);
        $this->_getEntityManager()->flush();
    }

    /**
     * @When /^I wait$/
     */
    public function iWait()
    {
        sleep(1);
    }

    /**
     * @Then /^I wait until I see "([^"]*)"$/
     */
    public function iWaitUntilISee($text)
    {
        $self     = $this;
        $callback = function ($text) use ($self) {
            return $self->_mink->assertSession()->pageTextContains($text);
        };

        $this->spin($text, $callback);
    }

    /**
     * @Then /^I wait until I do not see "([^"]*)"$/
     */
    public function iWaitUntilIDoNotSee($text)
    {
        $self     = $this;
        $callback = function ($text) use ($self) {
            return $self->_mink->assertSession()->pageTextNotContains($text);
        };

        $this->spin($text, $callback);
    }

    /**
     * Print pretty response.
     *
     * @Then /^print pretty response$/
     */
    public function printPrettyResponse()
    {
        if (!$this->getResponse()) {
            return;
        }

        $json = json_decode((string) $this->getResponse()->getBody());

        if ($json) {
            $json = json_encode($json, JSON_PRETTY_PRINT);
            echo $json;
        } else {
            $body   = $this->getResponse()->getBody();
            echo html_entity_decode(strip_tags((string) $body));
        }
    }

    /**
     * @AfterSuite
     */
    public static function clean()
    {
        if (self::getCleanAfterSuite()) {
            $this->getFixtureBuilder()->clean();
        }
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
     * @BeforeScenario
     */
    public function loadWebApi(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        if ($environment->getSuite()->getName() == 'srv') {
            $this->_webApi = $scope->getEnvironment()->getContext('Behat\WebApiExtension\Context\WebApiContext');
        }
    }

    /**
     * @return Doctrine\ORM\EntityManager
     */
    protected function _getEntityManager()
    {
        return self::$zendApp->getServiceManager()->get('Doctrine\ORM\EntityManager');
    }

    /**
     * @return FixtureBuilder
     */
    protected function getFixtureBuilder()
    {
        if (!$this->_fixtureBuilder) {
            $this->_fixtureBuilder = self::$zendApp->getServiceManager()->get('PandoraTestes\Fixture\FixtureBuilder');
            $this->_fixtureBuilder->setEntityManager($this->_getEntityManager());
        }

        return $this->_fixtureBuilder;
    }

    public function spin($text, $negative = false, $canFail = true, $wait = 10)
    {
        if (!$this->_spin) {
            $this->_spin = new Spin();
        }
        return $this->_spin->__invoke($text, $negative, $canFail, $wait);
    }

    /**
     * Sets Mink instance.
     *
     * @param Mink $mink
     *                   Mink session manager
     */
    public function setMink(Mink $mink)
    {
        $this->_mink = $mink;
    }

    /**
     * Sets parameters provided for Mink.
     *
     * @param array $parameters
     */
    public function setMinkParameters(array $parameters)
    {
        $this->_minkParameters = $parameters;
    }

    /**
     * Locates url, based on provided path.
     * Override to provide custom routing mechanism.
     *
     * @param string $path
     *
     * @return string
     */
    protected function locatePath($path)
    {
        $startUrl = rtrim($this->_minkParameters['base_url'], '/').'/';

        return 0 !== strpos($path, 'http') ? $startUrl.ltrim($path, '/') : $path;
    }

    /**
     * Returns fixed step argument (with \\" replaced back to ").
     *
     * @param string $argument
     *
     * @return string
     */
    protected function fixStepArgument($argument)
    {
        return str_replace('\\"', '"', $argument);
    }

    public function getMink()
    {
        return $this->_mink;
    }

    public function getMinkparameters()
    {
        return $this->_minkParameters;
    }

    protected static function getCleanAfterSuite()
    {
        if (!self::$_cleanAfterSuite) {
            $config = self::$zendApp->getServiceManager()->get('config');
            if (!isset($config['pandora-testes']) || !isset($config['pandora-testes']['clean-after-suite'])) {
                self::$_cleanAfterSuite = false;
            } else {
                self::$_cleanAfterSuite = $config['pandora-testes']['clean-after-suite'];
            }
        }

        return self::$_cleanAfterSuite;
    }

    /**
     * @return array
     */
    protected function getResponse()
    {
        $reflectionWebApi   = new \ReflectionClass('Behat\WebApiExtension\Context\WebApiContext');
        $reflectionResponse = $reflectionWebApi->getProperty('response');
        $reflectionResponse->setAccessible(true);

        return $reflectionResponse->getValue($this->_webApi);
    }
}
