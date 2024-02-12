<?php

namespace Keiwen\Utils\Competition;

class GameRace extends AbstractGame
{

    public function __construct(array $playerKeyList)
    {
        parent::setPlayers($playerKeyList);
    }

    /**
     * After game is played, save positions
     * @param int[]|string[] $playerKeysOrdered
     * @return bool true if set
     */
    public function setPositions(array $playerKeysOrdered): bool
    {
        if ($this->isPlayed()) return false;
        $playerKeysOrdered = array_values($playerKeysOrdered);
        foreach ($playerKeysOrdered as $index => $playerKey) {
            if (!$this->isPlayerInGame($playerKey)) continue;
            $this->setPlayerResult($playerKey, $index + 1);
        }
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    /**
     * After game is played, save positions and performances
     * @param array $playersAndPerformances player key => performances
     * @return bool true if set
     */
    public function setPositionsAndPerformance(array $playersAndPerformances): bool
    {
        if ($this->isPlayed()) return false;
        $rank = 0;
        foreach ($playersAndPerformances as $playerKey => $performances) {
            $rank++;
            if (!$this->isPlayerInGame($playerKey)) continue;
            $this->setPlayerResult($playerKey, $rank);
            if (!is_array($performances)) continue;
            $this->setPlayerPerformances($playerKey, $performances);
        }
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    /**
     * @param int|string $playerKey
     * @return int 0 if player not found
     */
    public function getPlayerPosition($playerKey): int
    {
        $position = $this->getPlayerResult($playerKey);
        if ($position === null) return 0;
        return $position;
    }

    /**
     * @return array position (starting at 1) => Player key
     */
    public function getPositions(): array
    {
        $positions = array_flip($this->results);
        ksort($positions);
        return $positions;
    }

    public function hasPlayerWon($playerKey): bool
    {
        return $this->getPlayerPosition($playerKey) == 1;
    }


}
