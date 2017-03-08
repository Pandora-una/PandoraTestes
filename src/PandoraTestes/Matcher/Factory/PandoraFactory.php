<?php

namespace PandoraTestes\Matcher\Factory;

use Coduo\PHPMatcher\Factory\SimpleFactory;
use Coduo\PHPMatcher\Lexer;
use Coduo\PHPMatcher\Parser;
use Coduo\PHPMatcher\Parser\ExpanderInitializer;

class PandoraFactory extends SimpleFactory
{
    /**
     * @return Parser
     */
    protected function buildParser()
    {
        $expander = new Parser\ExpanderInitializer();
        $expander->setExpanderDefinition('count', 'PandoraTestes\Matcher\Expander\Count');
        return new Parser(new Lexer(), $expander);
    }
}
