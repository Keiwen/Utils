<?php

namespace Keiwen\Utils\Competition;

class GameBrawl extends AbstractGame
{

    const RESULT_WON = 'W';
    const RESULT_LOSS = 'L';

    public function __construct(array $playersSeedList)
    {
        parent::setPlayers($playersSeedList);
    }

    /**
     * After game is played, save winner
     * @param int $winnerSeed
     * @return bool true if set
     */
    public function setWinner(int $winnerSeed): bool
    {
        if ($this->isPlayed()) return false;
        foreach ($this->players as $playerSeed => $startingOrder) {
            if ($playerSeed == $winnerSeed) {
                $this->setPlayerResult($playerSeed, self::RESULT_WON);
            } else {
                $this->setPlayerResult($playerSeed, self::RESULT_LOSS);
            }
        }
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    public function hasPlayerWon(int $playerSeed): bool
    {
        return $this->getPlayerResult($playerSeed) == self::RESULT_WON;
    }

}
