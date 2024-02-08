<?php

namespace Keiwen\Utils\Competition;

class GameRace extends AbstractGame
{

    public function __construct(array $playerSeedList)
    {
        parent::setPlayers($playerSeedList);
    }

    /**
     * After game is played, save positions
     * @param int[] $playerSeedsOrdered
     * @return bool
     */
    public function setPosition(array $playerSeedsOrdered)
    {
        if ($this->isPlayed()) return false;
        $playerSeedsOrdered = array_values($playerSeedsOrdered);
        foreach ($playerSeedsOrdered as $index => $playerSeed) {
            if (!$this->isPlayerInGame($playerSeed)) continue;
            $this->setPlayerResult($playerSeed, $index + 1);
        }
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    /**
     * After game is played, save positions and performances
     * @param array $playersAndPerformances player seed => performances
     * @return bool
     */
    public function setPositionAndPerformance(array $playersAndPerformances)
    {
        if ($this->isPlayed()) return false;
        $rank = 0;
        foreach ($playersAndPerformances as $playerSeed => $performances) {
            $rank++;
            if (!$this->isPlayerInGame($playerSeed)) continue;
            $this->setPlayerResult($playerSeed, $rank);
            if (!is_array($performances)) continue;
            $this->setPlayerPerformances($playerSeed, $performances);
        }
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    /**
     * @param int $playerSeed
     * @return int 0 if player not found
     */
    public function getPlayerPosition(int $playerSeed): int
    {
        $position = $this->getPlayerResult($playerSeed);
        if ($position === null) return 0;
        return $position;
    }

    /**
     * @return array position (starting at 1) => Player seed
     */
    public function getPositions(): array
    {
        return $this->results;
    }

    public function hasPlayerWon(int $playerSeed): bool
    {
        return $this->getPlayerPosition($playerSeed) == 1;
    }


}
