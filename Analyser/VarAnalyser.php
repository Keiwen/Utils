<?php

namespace Keiwen\Utils\Analyser;


class VarAnalyser
{

    protected $var;

    /**
     * VarAnalyser constructor.
     * @param mixed $var
     */
    public function __construct($var)
    {
        $this->var = $var;
    }

    /**
     * @return string
     */
    public function getVarVerbose()
    {
        switch(true) {
            case ($this->var === null):
                return 'null';
                break;
            case is_bool($this->var):
                return 'boolean, ' . ($this->var ? 'true' : 'false');
                break;
            case is_int($this->var):
                return 'integer';
                break;
            case is_float($this->var):
                return 'float';
                break;
            case is_numeric($this->var):
                return 'numeric';
                break;
            case is_string($this->var):
                return 'string, length ' . strlen($this->var);
                break;
            case is_array($this->var):
                return 'array, size ' . count($this->var);
                break;
            case is_resource($this->var):
                return 'resource';
                break;
            case is_object($this->var):
                return 'object, class ' . get_class($this->var);
                break;
        }
        return 'unrecognized';
    }


}
