<?php

namespace Keiwen\Utils\Competition;

abstract class AbstractGame
{

    protected $players = array();
    protected $results = array();
    protected $performances = array();
    protected $gameNumber = 0;
    protected $played = false;
    protected $affected = false;
    protected $affectedTo = null;


    protected function setPlayers(array $idPlayersOrd)
    {
        $inputCount = count($idPlayersOrd);
        $players = array_flip(array_combine(range(1, count($idPlayersOrd)), array_values($idPlayersOrd)));
        if ($inputCount != count($players)) {
            throw new CompetitionException('Unable to find unique players on start order, check for duplicates');
        }
        $this->players = $players;
    }


    /**
     * @return int
     */
    public function getPlayerCount(): int
    {
        return count($this->players);
    }

    /**
     * @param int $idPlayer
     * @return int 0 if not found
     */
    public function getPlayerStartingOrd(int $idPlayer)
    {
        return $this->players[$idPlayer] ?? 0;
    }


    /**
     * @param int $ord
     * @return int id player or 0 if not found
     */
    public function getPlayerThatStartedAt(int $ord)
    {
        $playersPositions = array_flip($this->players);
        return $playersPositions[$ord] ?? 0;
    }

    /**
     * @return array idPlayer => starting ord
     */
    public function getStartingOrd()
    {
        return $this->players;
    }


    /**
     * @param int $idPlayer
     * @param mixed $result
     */
    protected function setPlayerResult(int $idPlayer, $result)
    {
        $this->results[$idPlayer] = $result;
    }

    /**
     * @param int $idPlayer
     * @return mixed|null null if not found
     */
    public function getPlayerResult(int $idPlayer)
    {
        return $this->results[$idPlayer] ?? null;
    }

    /**
     * @return array idPlayer => result
     */
    public function getResults(): array
    {
        return $this->results;
    }


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

    /**
     * @param array $performances ID player => performances for this player
     * @return bool true if set
     */
    public function setAllPlayersPerformances(array $performances): bool
    {
        if ($this->isPlayed()) return false;
        foreach ($performances as $idPlayer => $playerPerformances) {
            if (!in_array($idPlayer, array_keys($this->players))) continue;
            if (!is_array($playerPerformances)) continue;
            $this->setPlayerPerformances($idPlayer, $playerPerformances);
        }
        return true;
    }


    /**
     * @param int $idPlayer
     * @param array $performances
     * @return bool true if set
     */
    public function setPlayerPerformances(int $idPlayer, array $performances): bool
    {
        if ($this->isPlayed()) return false;
        if (!in_array($idPlayer, array_keys($this->players))) return false;
        $this->performances[$idPlayer] = $performances;
        return true;
    }

    /**
     * @param int $idPlayer
     * @param array $performances
     * @return bool true if set
     */
    public function setPlayerPerformanceType(int $idPlayer, string $performanceType, $performance): bool
    {
        if ($this->isPlayed()) return false;
        if (!in_array($idPlayer, array_keys($this->players))) return false;
        if (empty($this->performances[$idPlayer])) $this->performances[$idPlayer] = array();
        $this->performances[$idPlayer][$performanceType] = $performance;
        return true;
    }

    /**
     * @param int $idPlayer
     * @return array|null null if not found
     */
    public function getPlayerPerformances(int $idPlayer): ?array
    {
        return $this->performances[$idPlayer] ?? null;
    }

    /**
     * @param int $idPlayer
     * @return mixed|null null if not found
     */
    public function getPlayerPerformanceType(int $idPlayer, string $performanceType)
    {
        $performances = $this->getPlayerPerformances($idPlayer);
        if (empty($performances)) return null;
        return $performances[$performanceType] ?? null;
    }

    /**
     * @return array idPlayer => performances
     */
    public function getPerformances(): array
    {
        return $this->performances;
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
