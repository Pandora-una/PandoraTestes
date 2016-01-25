<?php

namespace PandoraTestes\Context;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkAwareContext;
use Behat\Mink\Mink;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use PandoraTestes\Fixture\FixtureBuilder;

/**
 * Passos.
 */
abstract class PandoraContext implements Context, MinkAwareContext
{
    protected static $zendApp;

    protected $_fixtureBuilder;

    protected $_mink;

    protected $_minkParameters;

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
     * @When /^I wait$/
     */
    public function iWait()
    {
        sleep(1);
    }

    /**
     * Click on an element with given CSS.
     *
     * @When /^I click the element with css "([^"]*)"$/
     */
    public function iClickTheElementWithCss($element)
    {
        $cssElement = $this->_mink->getSession()->getPage()->find('css', $element);
        if ($cssElement) {
            $cssElement->click();
        } else {
            throw new \Exception("Elemento com css '$element' não encontrado.");
        }
    }

    /**
     * Checks, that option from select with specified id|name|label|value is selected.
     *
     * @Then /^the option "(?P<option>(?:[^"]|\\")*)" from "(?P<select>(?:[^"]|\\")*)" (?:is|should be) selected$/
     * @Then /^"(?P<option>(?:[^"]|\\")*)" from "(?P<select>(?:[^"]|\\")*)" (?:is|should be) selected$/
     */
    public function theOptionFromShouldBeSelected($option, $select)
    {
        $selectField = $this->_mink->getSession()
            ->getPage()
            ->findField($select);
        if (null === $selectField) {
            throw new ElementNotFoundException($this->_mink->getSession(), 'select field', 'id|name|label|value', $select);
        }

        $optionField = $selectField->find('named', array(
            'option',
            $option,
        ));

        if (null === $optionField) {
            throw new ElementNotFoundException($this->_mink->getSession(), 'select option field', 'id|name|label|value', $option);
        }

        if (!$optionField->isSelected()) {
            throw new ExpectationException('Select option field with value|text "'.$option.'" is not selected in the select "'.$select.'"', $this->getSession());
        }
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

    /**
     * @AfterSuite
     */
    public static function clean()
    {
        //(new ORMPurger(self::$zendApp->getServiceManager()->get('Doctrine\ORM\EntityManager')))->purge();
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

    public function spin($text, $negative = false, $canFail = true, $wait = 3)
    {
        for ($i = 0; $i < $wait; $i += 0.3) {
            try {
                if ($negative) {
                    $this->_mink->assertSession()->pageTextNotContains(str_replace('\\"', '"', $text));
                } else {
                    $this->_mink->assertSession()->pageTextContains(str_replace('\\"', '"', $text));
                }

                return true;
            } catch (\Exception $e) {
            }
            usleep(100000);
        }

        if ($canFail) {
            $backtrace = debug_backtrace();

            throw new \Exception('Timeout thrown by '.$backtrace[1]['class'].'::'.$backtrace[1]['function']."()\n".$backtrace[1]['file'].', line '.$backtrace[1]['line']);
        }
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
}
