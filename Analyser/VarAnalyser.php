<?php

namespace Keiwen\Utils\Analyser;


class VarAnalyser
{

    /**
     * @param mixed $var
     * @return string
     */
    protected static function getVarSum($var)
    {
        switch(true) {
            case is_null($var):
                return 'null';
                break;
            case is_bool($var):
                return 'boolean, ' . ($var ? 'true' : 'false');
                break;
            case is_int($var):
                return 'integer';
                break;
            case is_numeric($var):
                return 'numeric not integer';
                break;
            case is_string($var):
                return 'string, length ' . strlen($var);
                break;
            case is_array($var):
                return 'array, size ' . count($var);
                break;
            case is_resource($var):
                return 'resource';
                break;
            case is_object($var):
                return 'object, class ' . get_class($var);
                break;
        }
        return 'unrecognized';
    }


}
