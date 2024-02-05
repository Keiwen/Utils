<?php

namespace Keiwen\Utils\Competition;

class GameRace extends AbstractGame
{

    public function __construct(array $idPlayers)
    {
        parent::setPlayers($idPlayers);
    }

    /**
     * After game is played, save positions
     * @param int[] $idPlayersOrdered
     * @return bool
     */
    public function setPosition(array $idPlayersOrdered)
    {
        if ($this->isPlayed()) return false;
        $idPlayersOrdered = array_values($idPlayersOrdered);
        foreach ($idPlayersOrdered as $index => $idPlayer) {
            if (!in_array($idPlayer, array_keys($this->players))) continue;
            $this->setPlayerResult($idPlayer, $index + 1);
        }
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    /**
     * After game is played, save positions and performances
     * @param array $playersAndPerformances idPlayer => performances
     * @return bool
     */
    public function setPositionAndPerformance(array $playersAndPerformances)
    {
        if ($this->isPlayed()) return false;
        $rank = 0;
        foreach ($playersAndPerformances as $idPlayer => $performances) {
            $rank++;
            if (!in_array($idPlayer, array_keys($this->players))) continue;
            $this->setPlayerResult($idPlayer, $rank);
            if (!is_array($performances)) continue;
            $this->setPlayerPerformances($idPlayer, $performances);
        }
        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    /**
     * @param int $idPlayer
     * @return int 0 if player not found
     */
    public function getPlayerPosition(int $idPlayer): int
    {
        return $this->getPlayerResult($idPlayer);
    }

    /**
     * @return array position (starting at 1) => idPlayer
     */
    public function getPositions(): array
    {
        return $this->results;
    }

    public function hasPlayerWon(int $idPlayer): bool
    {
        return $this->getPlayerPosition($idPlayer) == 1;
    }


}
