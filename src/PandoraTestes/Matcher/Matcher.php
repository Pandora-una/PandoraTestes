<?php

namespace PandoraTestes\Matcher;

use Coduo\PHPMatcher\Backtrace\VoidBacktrace;
use Coduo\PHPMatcher\Lexer;
use Coduo\PHPMatcher\Matcher as CoduoMatcher;
use Coduo\PHPMatcher\Matcher as M;
use Coduo\PHPMatcher\Parser;

/**
 * O matcher da coduo/php-matcher 2.0.1, com a API dela (match() e getError()), montado com as
 * classes da 6.
 *
 * Não é o PHPMatcher da 6: a cadeia dele é mais permissiva. Dentro de um array, ela tenta também
 * o TextMatcher e o JsonObjectMatcher, e então "@integer@" aceita "1", "12" e "", "@double@" aceita
 * "1.5" e "@number@" aceita "". A suíte deixaria de acusar uma API que passasse a devolver número
 * como texto. Aqui a cadeia é a do SimpleFactory da 2.0.1: os dez matchers escalares de lá,
 * "||" e array. Fixado por tests/MatcherTest.php (instantâneos gravados com a 2.0.1).
 */
class Matcher
{
    /**
     * @var CoduoMatcher
     */
    protected $matcher;

    public function __construct()
    {
        $backtrace = new VoidBacktrace();
        $parser = new Parser(new Lexer(), new Parser\ExpanderInitializer($backtrace));
        $escalares = function () use ($backtrace, $parser) {
            return new M\ChainMatcher('scalars', $backtrace, [
                new M\CallbackMatcher($backtrace),
                new M\ExpressionMatcher($backtrace),
                new M\NullMatcher($backtrace),
                new M\StringMatcher($backtrace, $parser),
                new M\IntegerMatcher($backtrace, $parser),
                new M\BooleanMatcher($backtrace, $parser),
                new M\DoubleMatcher($backtrace, $parser),
                new M\NumberMatcher($backtrace, $parser),
                new M\ScalarMatcher($backtrace),
                new M\WildcardMatcher($backtrace),
            ]);
        };
        $array = new M\ArrayMatcher(
            new M\ChainMatcher('array', $backtrace, [new M\OrMatcher($backtrace, $escalares()), $escalares()]),
            $backtrace,
            $parser
        );
        $ou = new M\ChainMatcher('or', $backtrace, [new M\OrMatcher($backtrace, $escalares()), $array]);

        $this->matcher = new CoduoMatcher(
            new M\ChainMatcher('all', $backtrace, [
                $escalares(),
                $ou,
                new M\JsonMatcher($array, $backtrace),
                new M\TextMatcher($backtrace, $parser),
            ]),
            $backtrace
        );
    }

    /**
     * @param mixed $value
     * @param mixed $pattern
     *
     * @return bool
     */
    public function match($value, $pattern)
    {
        return $this->matcher->match($value, $pattern);
    }

    /**
     * @return string|null
     */
    public function getError()
    {
        return $this->matcher->getError();
    }
}
