<?php

namespace PandoraTestes\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Coduo\PHPMatcher\Factory\SimpleFactory;
use PHPUnit_Framework_Assert as Assertions;

/**
 * Passos para testes no servidor.
 */
class PandoraSrvContext implements Context
{
    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        if ($environment->getSuite()->getName() == 'srv') {
            $this->_webApi = $scope->getEnvironment()->getContext('Behat\WebApiExtension\Context\WebApiContext');
        }
    }

    /**
     * Check if JSON response has fields with specified values (See coduo/php-matcher).
     *
     * @Then /^(?:the )?response should contain json with this format:$/
     */
    public function theResponseShouldContainJsonWithFormat(PyStringNode $jsonString)
    {
        $etalon = json_decode($jsonString->getRaw(), true);
        if (null === $etalon) {
            throw new \RuntimeException("Can not convert etalon to json:\n".$jsonString->getRaw());
        }
        $matcher = (new SimpleFactory())->createMatcher();
        Assertions::assertTrue($matcher->match($this->getJsonResponse(), $etalon), $matcher->getError());
    }

    /**
     * @return array
     */
    protected function getJsonResponse()
    {
        $reflectionWebApi = new \ReflectionClass('Behat\WebApiExtension\Context\WebApiContext');
        $reflectionResponse = $reflectionWebApi->getProperty('response');
        $reflectionResponse->setAccessible(true);

        return $reflectionResponse->getValue($this->getWebApi())->json();
    }

    /**
     * @return Behat\WebApiExtension\Context\WebApiContext
     */
    protected function getWebApi()
    {
        return $this->_webApi;
    }
}
