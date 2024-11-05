<?php

namespace Keiwen\Utils\Competition\Exception;

class CompetitionParameterException extends CompetitionException
{

    protected $parameterName;

    public function __construct(string $subMessage, string $parameterName)
    {
        $this->parameterName = $parameterName;
        $message = sprintf('Cannot create competition regarding parameter %s: ' . $subMessage, $parameterName);
        parent::__construct($message);
    }

    public function getParameterName(): int
    {
        return $this->parameterName;
    }

}
