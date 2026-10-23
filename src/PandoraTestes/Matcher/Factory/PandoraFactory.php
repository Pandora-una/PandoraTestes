<?php

namespace PandoraTestes\Matcher\Factory;

use PandoraTestes\Matcher\Matcher;

/**
 * Mesma API da 2.x: createMatcher() devolve um matcher com match() e getError(), agora o
 * PandoraTestes\Matcher\Matcher (a cadeia da php-matcher 2.0.1 sobre as classes da 6). O expansor
 * count(), que era da casa, a 6 já tem com a mesma semântica.
 */
class PandoraFactory
{
    /**
     * @return Matcher
     */
    public function createMatcher()
    {
        return new Matcher();
    }
}
