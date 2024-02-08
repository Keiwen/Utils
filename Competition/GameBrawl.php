<?php

namespace Keiwen\Utils\Competition;

class GameBrawl extends AbstractGame
{

    const RESULT_WON = 'W';
    const RESULT_LOSS = 'L';

    public function __construct(array $playersOrdList)
    {
        parent::setPlayers($playersOrdList);
    }

    /**
     * After game is played, save winner
     * @param int $playerOrdWinner
     * @return bool
     */
    public function setWinner(int $playerOrdWinner)
    {
        if ($this->isPlayed()) return false;
        foreach ($this->players as $playerOrd => $ord) {
            if ($playerOrd == $playerOrdWinner) {
                $this->setPlayerResult($playerOrd, self::RESULT_WON);
            } else {
                $this->setPlayerResult($playerOrd, self::RESULT_LOSS);
            }
        }
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    public function hasPlayerWon(int $playerOrd): bool
    {
        return $this->getPlayerResult($playerOrd) == self::RESULT_WON;
    }

}
