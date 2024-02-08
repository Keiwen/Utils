<?php

namespace Keiwen\Utils\Competition;

abstract class AbstractGame
{

    protected $name = '';
    /** @var array $players seed => starting order */
    protected $players = array();
    protected $results = array();
    protected $performances = array();
    protected $expenses = array();
    protected $bonuses = array();
    protected $maluses = array();
    protected $gameNumber = 0;
    protected $competitionRound = 1;
    protected $played = false;
    /** @var AbstractCompetition $affectedTo */
    protected $affectedTo = null;

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected function setPlayers(array $playersSeedList)
    {
        $inputCount = count($playersSeedList);
        $players = array_flip(array_combine(range(1, count($playersSeedList)), array_values($playersSeedList)));
        if ($inputCount != count($players)) {
            throw new CompetitionException('Unable to find unique players seeds, check for duplicates');
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
     * @param int $playerSeed
     * @return int 0 if not found
     */
    public function getPlayerStartingOrder(int $playerSeed): int
    {
        return $this->players[$playerSeed] ?? 0;
    }


    /**
     * @param int $startingOrder
     * @return int player seed or 0 if not found
     */
    public function getPlayerThatStartedAt(int $startingOrder): int
    {
        $playersPositions = array_flip($this->players);
        return $playersPositions[$startingOrder] ?? 0;
    }

    /**
     * @return array Player seed => game starting order
     */
    public function getStartingOrder(): array
    {
        return $this->players;
    }


    /**
     * @return array game starting order => Player seed
     */
    public function getPlayers(): array
    {
        return array_flip($this->players);
    }


    /**
     * @return array game starting order => player
     */
    public function getFullPlayers(): array
    {
        $playerOrderList = $this->getPlayers();
        if (!$this->isAffected()) return $playerOrderList;
        $affectedCompetition = $this->getAffectation();
        $players = array();
        foreach ($playerOrderList as $startingOrder => $playerSeed) {
            $players[$startingOrder] = $affectedCompetition->getFullPlayer($playerSeed);
        }
        return $players;
    }

    /**
     * @param int $playerSeed
     * @param mixed $result
     */
    protected function setPlayerResult(int $playerSeed, $result)
    {
        $this->results[$playerSeed] = $result;
    }

    /**
     * @param int $playerSeed
     * @return mixed|null null if not found
     */
    public function getPlayerResult(int $playerSeed)
    {
        return $this->results[$playerSeed] ?? null;
    }

    /**
     * @return array Player seed => result
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
     * @param AbstractCompetition $competition
     * @param int $gameNumber
     * @return bool true if affected
     */
    public function affectTo(AbstractCompetition $competition, int $gameNumber): bool
    {
        if ($this->isAffected()) return false;
        $this->affectedTo = $competition;
        $this->gameNumber = $gameNumber;
        return true;
    }


    public function isPlayed(): bool
    {
        return $this->played;
    }

    /**
     * @param array $performances player seed => performances for this player
     * @return bool true if set
     */
    public function setAllPlayersPerformances(array $performances): bool
    {
        if ($this->isPlayed()) return false;
        foreach ($performances as $playerSeed => $playerPerformances) {
            if (!$this->isPlayerInGame($playerSeed)) continue;
            if (!is_array($playerPerformances)) continue;
            $this->setPlayerPerformances($playerSeed, $playerPerformances);
        }
        return true;
    }


    /**
     * @param int $playerSeed
     * @param array $performances
     * @return bool true if set
     */
    public function setPlayerPerformances(int $playerSeed, array $performances): bool
    {
        if ($this->isPlayed()) return false;
        if (!$this->isPlayerInGame($playerSeed)) return false;
        $this->performances[$playerSeed] = $performances;
        return true;
    }

    /**
     * @param int $playerSeed
     * @param string $performanceType
     * @param mixed $performance
     * @return bool true if set
     */
    public function setPlayerPerformanceType(int $playerSeed, string $performanceType, $performance): bool
    {
        if ($this->isPlayed()) return false;
        if (!$this->isPlayerInGame($playerSeed)) return false;
        if (empty($this->performances[$playerSeed])) $this->performances[$playerSeed] = array();
        $this->performances[$playerSeed][$performanceType] = $performance;
        return true;
    }

    /**
     * @param int $playerSeed
     * @return array|null null if not found
     */
    public function getPlayerPerformances(int $playerSeed): ?array
    {
        return $this->performances[$playerSeed] ?? null;
    }

    /**
     * @param int $playerSeed
     * @param string $performanceType
     * @return mixed|null null if not found
     */
    public function getPlayerPerformanceType(int $playerSeed, string $performanceType)
    {
        $performances = $this->getPlayerPerformances($playerSeed);
        if (empty($performances)) return null;
        return $performances[$performanceType] ?? null;
    }

    /**
     * @return array Player seed => performances
     */
    public function getPerformances(): array
    {
        return $this->performances;
    }

    /**
     * @param array $expenses player seed => expenses for this player
     * @return bool true if set
     */
    public function setAllPlayersExpenses(array $expenses): bool
    {
        if ($this->isPlayed()) return false;
        foreach ($expenses as $playerSeed => $playerExpenses) {
            if (!$this->isPlayerInGame($playerSeed)) continue;
            if (!is_array($playerExpenses)) continue;
            $this->setPlayerExpenses($playerSeed, $playerExpenses);
        }
        return true;
    }


    /**
     * @param int $playerSeed
     * @param array $expenses
     * @return bool true if set
     */
    public function setPlayerExpenses(int $playerSeed, array $expenses): bool
    {
        if ($this->isPlayed()) return false;
        if (!$this->isPlayerInGame($playerSeed)) return false;
        $this->expenses[$playerSeed] = $expenses;
        return true;
    }

    /**
     * @param int $playerSeed
     * @param string $expenseType
     * @param mixed $expense
     * @return bool true if set
     */
    public function setPlayerExpenseType(int $playerSeed, string $expenseType, $expense): bool
    {
        if ($this->isPlayed()) return false;
        if (!$this->isPlayerInGame($playerSeed)) return false;
        if (empty($this->expenses[$playerSeed])) $this->expenses[$playerSeed] = array();
        $this->expenses[$playerSeed][$expenseType] = $expense;
        return true;
    }

    /**
     * @param int $playerSeed
     * @return array|null null if not found
     */
    public function getPlayerExpenses(int $playerSeed): ?array
    {
        return $this->expenses[$playerSeed] ?? null;
    }

    /**
     * @param int $playerSeed
     * @param string $expenseType
     * @return mixed|null null if not found
     */
    public function getPlayerExpenseType(int $playerSeed, string $expenseType)
    {
        $expenses = $this->getPlayerExpenses($playerSeed);
        if (empty($expenses)) return null;
        return $expenses[$expenseType] ?? null;
    }

    /**
     * @return array Player seed => expenses
     */
    public function getExpenses(): array
    {
        return $this->expenses;
    }


    /**
     * @param array $bonuses player seed => bonus for this player
     * @return bool true if set
     */
    public function setAllPlayersBonuses(array $bonuses): bool
    {
        if ($this->isPlayed()) return false;
        foreach ($bonuses as $playerSeed => $bonus) {
            if (!$this->isPlayerInGame($playerSeed)) continue;
            if (!is_int($bonus)) continue;
            $this->setPlayerBonus($playerSeed, $bonus);
        }
        return true;
    }


    /**
     * @param int $playerSeed
     * @param int $bonus
     * @return bool true if set
     */
    public function setPlayerBonus(int $playerSeed, int $bonus): bool
    {
        if ($this->isPlayed()) return false;
        if (!$this->isPlayerInGame($playerSeed)) return false;
        $this->bonuses[$playerSeed] = $bonus;
        return true;
    }

    /**
     * @param int $playerSeed
     * @return int|null null if not found
     */
    public function getPlayerBonus(int $playerSeed): int
    {
        return $this->bonuses[$playerSeed] ?? 0;
    }

    /**
     * @return array Player seed => bonus
     */
    public function getBonuses(): array
    {
        return $this->bonuses;
    }


    /**
     * @param array $maluses player seed => malus for this player
     * @return bool true if set
     */
    public function setAllPlayersMaluses(array $maluses): bool
    {
        if ($this->isPlayed()) return false;
        foreach ($maluses as $playerSeed => $malus) {
            if (!$this->isPlayerInGame($playerSeed)) continue;
            if (!is_int($malus)) continue;
            $this->setPlayerMalus($playerSeed, $malus);
        }
        return true;
    }


    /**
     * @param int $playerSeed
     * @param int $malus
     * @return bool true if set
     */
    public function setPlayerMalus(int $playerSeed, int $malus): bool
    {
        if ($this->isPlayed()) return false;
        if (!$this->isPlayerInGame($playerSeed)) return false;
        $this->maluses[$playerSeed] = $malus;
        return true;
    }

    /**
     * @param int $playerSeed
     * @return int|null null if not found
     */
    public function getPlayerMalus(int $playerSeed): int
    {
        return $this->maluses[$playerSeed] ?? 0;
    }

    /**
     * @return array Player seed => malus
     */
    public function getMaluses(): array
    {
        return $this->maluses;
    }

    public function isAffected(): bool
    {
        return !empty($this->affectedTo);
    }

    /**
     * @return AbstractCompetition|null
     */
    public function getAffectation(): ?AbstractCompetition
    {
        return $this->affectedTo;
    }

    /**
     * @return int
     */
    public function getCompetitionRound(): int
    {
        return $this->competitionRound;
    }

    /**
     * @param int $competitionRound
     */
    public function setCompetitionRound(int $competitionRound)
    {
        $this->competitionRound = $competitionRound;
    }


    /**
     * @param int $playerSeed
     * @return bool
     */
    abstract public function hasPlayerWon(int $playerSeed): bool;


    /**
     * @return int[] seeds of all winners
     */
    public function getWinnerSeeds(): array
    {
        $winners = array();
        foreach ($this->getPlayers() as $playerSeed) {
            if ($this->hasPlayerWon($playerSeed)) $winners[] = $playerSeed;
        }
        return $winners;
    }

    /**
     * @param int $playerSeed
     * @return bool
     */
    public function isPlayerInGame(int $playerSeed): bool
    {
        return in_array($playerSeed, array_keys($this->players));
    }

}
