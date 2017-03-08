<?php

namespace PandoraTestes\Matcher\Expander;

use Coduo\PHPMatcher\Matcher\Pattern\PatternExpander;
use Coduo\ToString\StringConverter;

class Count implements PatternExpander
{

    protected $count;
    protected $error;

    /**
     * @param $count
     */
    public function __construct($count)
    {
        $this->count = $count;
    }

    /**
     * @param $value
     * @return boolean
     */
    public function match($value)
    {
        if (!is_array($value)) {
            $this->error = sprintf("Count expander require \"array\", got \"%s\".", new StringConverter($value));
            return false;
        }

        if (count($value) != $this->count) {
            $this->error = sprintf("array doesn't have \"%s\" elements.", new StringConverter($this->count));
            return false;
        }

        return true;
    }

    /**
     * @return string|null
     */
    public function getError()
    {
        return $this->error;
    }
}
