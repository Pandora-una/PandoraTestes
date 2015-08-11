<?php
namespace PandoraTestes\Context;

use Behat\WebApiExtension\Context\WebApiContext;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use PandoraTestes\Fixture\FixtureBuilder;
use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkAwareContext;
use Behat\Mink\Mink;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Behat\Tester\Result\UndefinedStepResult;

/**
 * Passos
 */
abstract class PandoraApiContext implements Context, MinkAwareContext
{

    protected static $zendApp;

    protected $_fixtureBuilder;

    protected $_mink;

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
     * @Then /^I wait until I see "([^"]*)"$/
     */
    public function iWaitUntilISee($text)
    {
        $this->spin($text);
    }
    
    /**
     * @Then /^I wait until I do not see "([^"]*)"$/
     */
    public function iWaitUntilIDoNotSee($text)
    {
        $this->spin($text, true);
    }

    /**
     * @When /^I wait$/
     */
    public function iWait()
    {
        $this->spin(function ($context) {
            return false;
        }, 1, false);
    }

    /**
     * @Then /^I wait for the suggestion box of entidade to appear, but continues if not$/
     */
    public function iWaitForSomethingToAppearButContinues()
    {
        $this->spin(function ($context) {
            return $context->getSession()
                ->getPage()
                ->findById('entipess_popup0') != null;
        }, 3, false);
    }
    
    // /**
    // * Initializes context.
    // *
    // * Every scenario gets its own context instance.
    // * You can also pass arbitrary arguments to the
    // * context constructor through behat.yml.
    // */
    // public function __construct()
    // {
    // $this->addHeader('Accept', 'application/json');
    // $this->addHeader('x-pandora-teste', 1);
    // }
    
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
     * Take screenshot when step fails.
     * Works only with Selenium2Driver.
     *
     * @AfterStep
     */
    public function takeScreenshotAfterFailedStep($event)
    {
        $result = $event->getTestResult();
        if (!($result instanceof UndefinedStepResult) && $result->hasException()) {
            $driver = $this->_mink->getSession()->getDriver();
            
            if ($driver instanceof Selenium2Driver) {
                $fileName = '/home/vinicius/tmp/teste.png';
                echo file_put_contents($fileName, $driver->getScreenshot());
            }
        }
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

    public function spin($text, $negative = false, $canFail = true, $wait = 3)
    {
        for ($i = 0; $i < $wait; $i += 0.3) {
            try {
                if($negative)
                    $this->_mink->assertSession()->pageTextNotContains(str_replace('\\"', '"', $text));
                else
                    $this->_mink->assertSession()->pageTextContains(str_replace('\\"', '"', $text));
                return true;
            } catch (\Exception $e) {
            }
            usleep(100000);
        }
        
        if ($canFail) {
            $backtrace = debug_backtrace();
            
            throw new \Exception("Timeout thrown by " . $backtrace[1]['class'] . "::" . $backtrace[1]['function'] . "()\n" . $backtrace[1]['file'] . ", line " . $backtrace[1]['line']);
        }
    }

    /**
     * Sets Mink instance.
     *
     * @param Mink $mink
     *            Mink session manager
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
    {}
}
