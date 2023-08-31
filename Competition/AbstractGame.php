<?php

namespace Keiwen\Utils\Competition;

abstract class AbstractGame
{

    protected $gameNumber = 0;
    protected $played = false;
    protected $affected = false;
    protected $affectedTo = null;

    /**
     * @return int
     */
    public function getGameNumber(): int
    {
        return $this->gameNumber;
    }

    /**
     * @param mixed $competition
     * @param int $gameNumber
     * @return bool true if affected
     */
    public function affectTo($competition, int $gameNumber): bool
    {
        if ($this->isAffected()) return false;
        $this->affectedTo = $competition;
        $this->gameNumber = $gameNumber;
        $this->affected = true;
        return true;
    }


    public function isPlayed(): bool
    {
        return $this->played;
    }

    public function isAffected(): bool
    {
        return $this->affected;
    }

    public function getAffectation()
    {
        return $this->affectedTo;
    }

}
