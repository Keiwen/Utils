<?php

namespace Keiwen\Utils\Analyser;


class DebugBacktracer
{

    /**
     * Get debug backtrace
     * @param int $limit
     * @return array
     */
    public static function debugBacktrace(int $limit = 0)
    {
        return debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $limit);
    }


    /**
     * From process 'caller context' -> 'your context' -> 'debug backtracer context',
     * provide debug trace of 'caller context'
     * @return array
     */
    public static function getCallerTrace()
    {
        //get 2 traces because this function will be included
        $trace = static::getCallersTrace(2);
        return end($trace);
    }


    /**
     * From process ['caller contexts'] -> 'your context' -> 'debug backtracer context',
     * provide debug trace of all caller contexts
     * @param int $limit
     * @return array
     */
    public static function getCallersTrace(int $limit = 0)
    {
        //first trace is debug method, second is here, third is where we call this, fourth is target caller
        $limit = $limit < 1 ? 0 : 2 + $limit;
        $trace = static::debugBacktrace($limit);
        //removed first 3
        array_shift($trace);
        array_shift($trace);
        array_shift($trace);
        return $trace;
    }

}
