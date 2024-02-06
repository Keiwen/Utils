<?php

namespace Keiwen\Utils\Competition;

class GameRace extends AbstractGame
{

    public function __construct(array $playerOrdList)
    {
        parent::setPlayers($playerOrdList);
    }

    /**
     * After game is played, save positions
     * @param int[] $playerOrdOrdered
     * @return bool
     */
    public function setPosition(array $playerOrdOrdered)
    {
        if ($this->isPlayed()) return false;
        $playerOrdOrdered = array_values($playerOrdOrdered);
        foreach ($playerOrdOrdered as $index => $playerOrd) {
            if (!in_array($playerOrd, array_keys($this->players))) continue;
            $this->setPlayerResult($playerOrd, $index + 1);
        }
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    /**
     * After game is played, save positions and performances
     * @param array $playersAndPerformances player ord => performances
     * @return bool
     */
    public function setPositionAndPerformance(array $playersAndPerformances)
    {
        if ($this->isPlayed()) return false;
        $rank = 0;
        foreach ($playersAndPerformances as $playerOrd => $performances) {
            $rank++;
            if (!in_array($playerOrd, array_keys($this->players))) continue;
            $this->setPlayerResult($playerOrd, $rank);
            if (!is_array($performances)) continue;
            $this->setPlayerPerformances($playerOrd, $performances);
        }
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    /**
     * @param int $playerOrd
     * @return int 0 if player not found
     */
    public function getPlayerPosition(int $playerOrd): int
    {
        $position = $this->getPlayerResult($playerOrd);
        if ($position === null) return 0;
        return $position;
    }

    /**
     * @return array position (starting at 1) => Player ord
     */
    public function getPositions(): array
    {
        return $this->results;
    }

    public function hasPlayerWon(int $playerOrd): bool
    {
        return $this->getPlayerPosition($playerOrd) == 1;
    }


}
