<?php

namespace Keiwen\Utils\Competition\Exception;

class CompetitionRoundCountException extends CompetitionException
{

    protected $roundCountRequired;

    public function __construct(string $subMessage, int $roundCountRequired)
    {
        $this->roundCountRequired = $roundCountRequired;
        $message = sprintf('Not enough rounds ' . $subMessage .  ' (%d required)', $roundCountRequired);
        parent::__construct($message);
    }

    public function getRoundCountRequired(): int
    {
        return $this->roundCountRequired;
    }

}
