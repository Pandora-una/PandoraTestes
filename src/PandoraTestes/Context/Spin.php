<?php

namespace PandoraTestes\Context;

class Spin
{
    public function __invoke($value, $callback, $negative = false, $canFail = true, $wait = 10)
    {
        for ($i = 0; $i < $wait; $i += 0.2) {
            try {
                if ($negative) {
                    if (!call_user_func($callback, $value)) {
                        return true;
                    }
                } else {
                    if (call_user_func($callback, $value)) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
            }
            usleep(200000);
        }

        if ($canFail) {
            $backtrace = debug_backtrace();

            throw new \Exception('Timeout thrown by '.$backtrace[1]['class'].'::'.$backtrace[1]['function']."()\n");
        }
    }
}
