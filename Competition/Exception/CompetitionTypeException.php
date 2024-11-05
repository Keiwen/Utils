<?php

namespace Keiwen\Utils\Competition\Exception;

class CompetitionTypeException extends CompetitionException
{

    protected $competitionType;

    public function __construct(string $competitionType)
    {
        $this->competitionType = $competitionType;
        $message = sprintf('Competition type %s does not exists or is not correctly defined in builder', $competitionType);
        parent::__construct($message);
    }

    public function getCompetitionType(): int
    {
        return $this->competitionType;
    }

}
