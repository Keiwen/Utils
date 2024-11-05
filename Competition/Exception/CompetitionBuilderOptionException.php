<?php

namespace Keiwen\Utils\Competition\Exception;

class CompetitionBuilderOptionException extends CompetitionException
{

    protected $optionName;

    public function __construct(string $subMessage, string $optionName)
    {
        $this->optionName = $optionName;
        $message = sprintf('Invalid option %s in builder: ' . $subMessage, $optionName);
        parent::__construct($message);
    }

    public function getOptionName(): int
    {
        return $this->optionName;
    }

}
