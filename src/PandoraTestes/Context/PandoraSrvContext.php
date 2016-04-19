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
     * Check if the JSON response has fields with specified values (See coduo/php-matcher).
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
     * Check if the JSON response has at least the specified fields and the values match (See coduo/php-matcher).
     *
     * @Then /^(?:the )?response should contain json with at least (?:this|these) fields?:$/
     */
    public function theResponseShouldContainJsonWithAtLeastThisFields(PyStringNode $jsonString)
    {
        $etalon = json_decode($jsonString->getRaw(), true);
        if (null === $etalon) {
            throw new \RuntimeException("Can not convert etalon to json:\n".$jsonString->getRaw());
        }

        $filteredArray = $this->recursiveArrayIntersectKeys($this->getJsonResponse(), $etalon);

        $matcher = (new SimpleFactory())->createMatcher();
        Assertions::assertTrue($matcher->match($filteredArray, $etalon), $matcher->getError());
    }

    /**
     * @return array
     */
    protected function getJsonResponse()
    {
        $reflectionWebApi = new \ReflectionClass('Behat\WebApiExtension\Context\WebApiContext');
        $reflectionResponse = $reflectionWebApi->getProperty('response');
        $reflectionResponse->setAccessible(true);
        $response = $reflectionResponse->getValue($this->getWebApi());

        // echo get_class($reflectionResponse->getValue($this->getWebApi())) . "\n";die;
        return json_decode($response->getBody(), true);
    }

    protected function recursiveArrayIntersectKeys(array $array1, $array2)
    {
        if (!is_array($array2)) {
            return $array1;
        }
        $array1 = array_intersect_key($array1, $array2);
        foreach ($array1 as $key => &$value) {
            if (is_array($value)) {
                $value = $this->recursiveArrayIntersectKeys($value, $array2[$key]);
            }
        }

        return $array1;
    }

    /**
     * @return Behat\WebApiExtension\Context\WebApiContext
     */
    protected function getWebApi()
    {
        return $this->_webApi;
    }
}
