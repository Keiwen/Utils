<?php

namespace Keiwen\Utils\Competition\Game;

class GameBrawl extends AbstractGame
{

    const RESULT_WON = 'W';
    const RESULT_LOSS = 'L';

    public function __construct(array $playersKeyList)
    {
        parent::setPlayers($playersKeyList);
    }

    /**
     * After game is played, save winner
     * @param int|string $winnerKey
     * @return bool true if set
     */
    public function setWinner(int $winnerKey): bool
    {
        if ($this->isPlayed()) return false;
        foreach ($this->getPlayersKeys() as $startingOrder => $playerKey) {
            if ($playerKey == $winnerKey) {
                $this->setPlayerResult($playerKey, self::RESULT_WON);
            } else {
                $this->setPlayerResult($playerKey, self::RESULT_LOSS);
            }
        }
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    public function hasPlayerWon($playerKey): bool
    {
        return $this->getPlayerResult($playerKey) == self::RESULT_WON;
    }

    /**
     * @return string|int|null player key of null if not found
     */
    public function getWinnerKey()
    {
        $list = $this->getWinnerKeys();
        if (empty($list)) return null;
        $uniqueWinner = reset($list);
        return $uniqueWinner;
    }

}
