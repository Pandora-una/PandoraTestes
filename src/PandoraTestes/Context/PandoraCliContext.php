<?php
namespace PandoraTestes\Context;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkAwareContext;
use Behat\Mink\Mink;
/**
 * Passos
 */
class PandoraCliContext implements Context, MinkAwareContext
{
    protected $_mink;

    protected $_minkParameters;

    /**
     * Take screenshot when step fails.
     * Works only with Selenium2Driver.
     *
     * @AfterStep
     */
    public function takeScreenshotAfterFailedStep($event)
    {
        $result = $event->getTestResult();
        if (! ($result instanceof UndefinedStepResult) && $result->hasException()) {
            $driver = $this->_mink->getSession()->getDriver();

            if ($driver instanceof Selenium2Driver) {
                $fileName = '/home/vinicius/tmp/teste.png';
                echo file_put_contents($fileName, $driver->getScreenshot());
            }
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

}
