<?php

namespace Keiwen\Utils\Competition;

class GameRace extends AbstractGame
{

    /** @var CompetitionChampionshipRace $affectedChampionship */
    protected $affectedTo = null;


    public function __construct(array $idPlayers)
    {
        parent::setPlayers($idPlayers);
    }


    /**
     * @param CompetitionChampionshipRace $competition
     * @param int $gameNumber
     * @return bool true if affected
     */
    public function affectTo($competition, int $gameNumber): bool
    {
        if (!$competition instanceof CompetitionChampionshipRace) {
            throw new CompetitionException(sprintf('Race require %s as affectation, %s given', CompetitionChampionshipRace::class, get_class($competition)));
        }
        return parent::affectTo($competition, $gameNumber);
    }

    public function getChampionship(): ?CompetitionChampionshipRace
    {
        return parent::getAffectation();
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
        if ($this->isAffected() && $this->affectedTo) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    /**
     * After game is played, save positions and performances
     * @param array $playersAndPerformances idPlayer => performance
     * @return bool
     */
    public function setPositionAndPerformance(array $playersAndPerformances)
    {
        if ($this->isPlayed()) return false;
        $rank = 0;
        foreach ($playersAndPerformances as $idPlayer => $performance) {
            $rank++;
            if (!in_array($idPlayer, array_keys($this->players))) continue;
            $this->setPlayerResult($idPlayer, $rank);
            $this->setPlayerPerformance($idPlayer, $performance);
        }
        $this->played = true;
        if ($this->isAffected() && $this->affectedTo) {
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


}
