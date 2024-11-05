<?php

namespace Keiwen\Utils\Competition\Exception;

class CompetitionPlayerCountException extends CompetitionException
{

    protected $playerCountRequired;

    public function __construct(string $subMessage, int $playerCountRequired)
    {
        $this->playerCountRequired = $playerCountRequired;
        $message = sprintf('Not enough players ' . $subMessage .  ' (%d required)', $playerCountRequired);
        parent::__construct($message);
    }

    public function getPlayerCountRequired(): int
    {
        return $this->playerCountRequired;
    }

}
