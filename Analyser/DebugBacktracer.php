<?php

namespace Keiwen\Utils\Analyser;


class DebugBacktracer
{

    /**
     * Get debug backtrace
     * @param int $limit
     * @return array
     */
    public static function debugBacktrace($limit = 0)
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
        $trace = static::getCallersTrace(1);
        return reset($trace);
    }


    /**
     * From process ['caller contexts'] -> 'your context' -> 'debug backtracer context',
     * provide debug trace of all caller contexts
     * @param int $limit
     * @return array
     */
    public static function getCallersTrace($limit = 0)
    {
        //first trace is here, second is where we call this, third is target caller
        $limit = $limit < 1 ? 0 : 2 + $limit;
        $trace = static::debugBacktrace($limit);
        //removed first 2
        array_shift($trace);
        array_shift($trace);
        return $trace;
    }

}
