<?php

namespace PandoraTestes\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Result\UndefinedStepResult;
use Behat\MinkExtension\Context\MinkAwareContext;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Mink;

/**
 * Passos.
 */
class PandoraCliContext implements Context, MinkAwareContext
{
    protected $_mink;

    protected $_minkParameters;

    protected $_errorFolder;

    public function __construct($error_folder = null)
    {
        $this->_errorFolder = $error_folder;
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

            if ($driver instanceof Selenium2Driver && $this->_errorFolder) {
                $file = $event->getFeature()->getFile();
                $file = explode('/', $file);
                $file = array_pop($file);
                $file = str_replace('.feature', '', $file);
                $line = $event->getStep()->getLine();
                $fileName = $this->_errorFolder."/pan-erro-$file-$line.png";
                file_put_contents($fileName, $driver->getScreenshot());
            }
        }
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
     * Click on an element with given id or name.
     *
     * @When /^I click in "([^"]*)"$/
     */
    public function iClickIn($name)
    {
        $element = $this->_mink->getSession()->getPage()->find('named', array('id_or_name', $name));
        if ($element) {
            $element->click();
        } else {
            throw new \Exception("Elemento com id|name '$name' não encontrado.");
        }
    }

    /**
     * Wait until some element with given css is visible.
     *
     * @When /^I wait until the element with css "([^"]*)" (is|is not) visible$/
     */
    public function iWaitUntilTheElementWithCssIsVisible($css, $negative)
    {
        $negative = $negative === 'is not';

        $callback = function ($css) {
            $element = $this->_mink->getSession()->getPage()->find('css', $css);

            return $element && $element->isVisible();
        };

        $this->spin($css, $callback, $negative);
    }

    /**
     * Wait until some element with given css is visible.
     *
     * @When /^I wait until the element with css "([^"]*)" (is|is not) disabled$/
     */
    public function iWaitUntilTheElementWithCssIsDisabled($css, $negative)
    {
        $negative = $negative === 'is not';

        $callback = function ($css) {
            $element = $this->_mink->getSession()->getPage()->find('css', $css);

            return $element && $element->hasAttribute('disabled');
        };

        $this->spin($css, $callback, $negative);
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
            throw new ExpectationException('Select option field with value|text "'.$option.'" is not selected in the select "'.$select.'"', $this->_mink->getSession());
        }
    }

    /**
     * Checks that the element with specified css is visible on page.
     *
     * @Then /^the element "([^"]*)" is visible$/
     */
    public function theElementIsVisible($css)
    {
        $element = $this->_mink->getSession()->getPage()->find('css', $css);
        if ($element && $element->isVisible()) {
            return;
        } else {
            throw new \Behat\Mink\Exception\ElementNotFoundException($this->_mink->getSession(), 'element', 'css', $css);
        }
    }

    /**
     * Checks that the element with specified css is not visible on page.
     *
     * @Then /^the element "([^"]*)" is not visible$/
     */
    public function theElementIsNotVisible($css)
    {
        $element = $this->_mink->getSession()->getPage()->find('css', $css);
        if (!$element || !$element->isVisible()) {
            return;
        } else {
            throw new \Exception("Form item with css \"$css\" is visible.");
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

    public function getMink()
    {
        return $this->_mink;
    }

    public function getMinkparameters()
    {
        return $this->_minkParameters;
    }

    protected function spin($text, $callback, $negative = false, $canFail = true, $wait = 10)
    {
        for ($i = 0; $i < $wait; $i += 0.3) {
            try {
                if ($negative) {
                    if (!$callback($text)) {
                        return true;
                    }
                } else {
                    if ($callback($text)) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
            }
            usleep(300000);
        }

        if ($canFail) {
            $backtrace = debug_backtrace();

            throw new \Exception('Timeout thrown by '.$backtrace[1]['class'].'::'.$backtrace[1]['function']."()\n");
        }
    }
}
