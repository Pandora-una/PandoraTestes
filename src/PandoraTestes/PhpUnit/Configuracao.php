<?php

namespace PandoraTestes\PhpUnit;

use PHPUnit\TextUI\CliArguments\Builder;
use PHPUnit\TextUI\Configuration\Registry;
use PHPUnit\TextUI\XmlConfiguration\DefaultConfiguration;

/**
 * As asserções do PHPUnit 12 leem a configuração do PHPUnit ao montar a mensagem de falha. Sob o
 * Behat ela não existe, e uma asserção que falha vira TypeError (Registry::get() devolve null) em vez
 * de ExpectationFailedException: o passo falha sem dizer por quê. Aqui ela é criada com os padrões,
 * só quando ainda não existe (dentro do próprio PHPUnit, vale a dele).
 */
final class Configuracao
{
    public static function garante()
    {
        // sem chamar Registry::get(): sem configuração ele dá TypeError ou, com zend.assertions,
        // AssertionError
        $instancia = new \ReflectionProperty(Registry::class, 'instance');
        if ($instancia->getValue() !== null) {
            return;
        }
        Registry::init((new Builder())->fromParameters([]), DefaultConfiguration::create());
    }
}
