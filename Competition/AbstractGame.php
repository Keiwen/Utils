<?php

namespace Keiwen\Utils\Competition;

abstract class AbstractGame
{

    protected $name = '';
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

    protected function setPlayers(array $playersOrdList)
    {
        $inputCount = count($playersOrdList);
        $players = array_flip(array_combine(range(1, count($playersOrdList)), array_values($playersOrdList)));
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
     * @param int $playerOrd
     * @return int 0 if not found
     */
    public function getPlayerStartingOrd(int $playerOrd)
    {
        return $this->players[$playerOrd] ?? 0;
    }


    /**
     * @param int $ord
     * @return int player ord or 0 if not found
     */
    public function getPlayerThatStartedAt(int $ord)
    {
        $playersPositions = array_flip($this->players);
        return $playersPositions[$ord] ?? 0;
    }

    /**
     * @return array Player ord => starting ord
     */
    public function getStartingOrd()
    {
        return $this->players;
    }


    /**
     * @return array starting ord => Player ord
     */
    public function getPlayers()
    {
        return array_flip($this->players);
    }


    /**
     * @return array starting ord => player
     */
    public function getFullPlayers()
    {
        $playerOrdList = $this->getPlayers();
        if (!$this->isAffected()) return $playerOrdList;
        $affectedCompetition = $this->getAffectation();
        $players = array();
        foreach ($playerOrdList as $startingOrd => $playerOrd) {
            $players[$startingOrd] = $affectedCompetition->getFullPlayer($playerOrd);
        }
        return $players;
    }

    /**
     * @param int $playerOrd
     * @param mixed $result
     */
    protected function setPlayerResult(int $playerOrd, $result)
    {
        $this->results[$playerOrd] = $result;
    }

    /**
     * @param int $playerOrd
     * @return mixed|null null if not found
     */
    public function getPlayerResult(int $playerOrd)
    {
        return $this->results[$playerOrd] ?? null;
    }

    /**
     * @return array Player ord => result
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
     * @param array $performances player ord => performances for this player
     * @return bool true if set
     */
    public function setAllPlayersPerformances(array $performances): bool
    {
        if ($this->isPlayed()) return false;
        foreach ($performances as $playerOrd => $playerPerformances) {
            if (!in_array($playerOrd, array_keys($this->players))) continue;
            if (!is_array($playerPerformances)) continue;
            $this->setPlayerPerformances($playerOrd, $playerPerformances);
        }
        return true;
    }


    /**
     * @param int $playerOrd
     * @param array $performances
     * @return bool true if set
     */
    public function setPlayerPerformances(int $playerOrd, array $performances): bool
    {
        if ($this->isPlayed()) return false;
        if (!in_array($playerOrd, array_keys($this->players))) return false;
        $this->performances[$playerOrd] = $performances;
        return true;
    }

    /**
     * @param int $playerOrd
     * @param string $performanceType
     * @param mixed $performance
     * @return bool true if set
     */
    public function setPlayerPerformanceType(int $playerOrd, string $performanceType, $performance): bool
    {
        if ($this->isPlayed()) return false;
        if (!in_array($playerOrd, array_keys($this->players))) return false;
        if (empty($this->performances[$playerOrd])) $this->performances[$playerOrd] = array();
        $this->performances[$playerOrd][$performanceType] = $performance;
        return true;
    }

    /**
     * @param int $playerOrd
     * @return array|null null if not found
     */
    public function getPlayerPerformances(int $playerOrd): ?array
    {
        return $this->performances[$playerOrd] ?? null;
    }

    /**
     * @param int $playerOrd
     * @param string $performanceType
     * @return mixed|null null if not found
     */
    public function getPlayerPerformanceType(int $playerOrd, string $performanceType)
    {
        $performances = $this->getPlayerPerformances($playerOrd);
        if (empty($performances)) return null;
        return $performances[$performanceType] ?? null;
    }

    /**
     * @return array Player ord => performances
     */
    public function getPerformances(): array
    {
        return $this->performances;
    }

    /**
     * @param array $expenses player ord => expenses for this player
     * @return bool true if set
     */
    public function setAllPlayersExpenses(array $expenses): bool
    {
        if ($this->isPlayed()) return false;
        foreach ($expenses as $playerOrd => $playerExpenses) {
            if (!in_array($playerOrd, array_keys($this->players))) continue;
            if (!is_array($playerExpenses)) continue;
            $this->setPlayerExpenses($playerOrd, $playerExpenses);
        }
        return true;
    }


    /**
     * @param int $playerOrd
     * @param array $expenses
     * @return bool true if set
     */
    public function setPlayerExpenses(int $playerOrd, array $expenses): bool
    {
        if ($this->isPlayed()) return false;
        if (!in_array($playerOrd, array_keys($this->players))) return false;
        $this->expenses[$playerOrd] = $expenses;
        return true;
    }

    /**
     * @param int $playerOrd
     * @param string $expenseType
     * @param mixed $expense
     * @return bool true if set
     */
    public function setPlayerExpenseType(int $playerOrd, string $expenseType, $expense): bool
    {
        if ($this->isPlayed()) return false;
        if (!in_array($playerOrd, array_keys($this->players))) return false;
        if (empty($this->expenses[$playerOrd])) $this->expenses[$playerOrd] = array();
        $this->expenses[$playerOrd][$expenseType] = $expense;
        return true;
    }

    /**
     * @param int $playerOrd
     * @return array|null null if not found
     */
    public function getPlayerExpenses(int $playerOrd): ?array
    {
        return $this->expenses[$playerOrd] ?? null;
    }

    /**
     * @param int $playerOrd
     * @param string $expenseType
     * @return mixed|null null if not found
     */
    public function getPlayerExpenseType(int $playerOrd, string $expenseType)
    {
        $expenses = $this->getPlayerExpenses($playerOrd);
        if (empty($expenses)) return null;
        return $expenses[$expenseType] ?? null;
    }

    /**
     * @return array Player ord => expenses
     */
    public function getExpenses(): array
    {
        return $this->expenses;
    }


    /**
     * @param array $bonuses player ord => bonus for this player
     * @return bool true if set
     */
    public function setAllPlayersBonuses(array $bonuses): bool
    {
        if ($this->isPlayed()) return false;
        foreach ($bonuses as $playerOrd => $bonus) {
            if (!in_array($playerOrd, array_keys($this->players))) continue;
            if (!is_int($bonus)) continue;
            $this->setPlayerBonus($playerOrd, $bonus);
        }
        return true;
    }


    /**
     * @param int $playerOrd
     * @param int $bonus
     * @return bool true if set
     */
    public function setPlayerBonus(int $playerOrd, int $bonus): bool
    {
        if ($this->isPlayed()) return false;
        if (!in_array($playerOrd, array_keys($this->players))) return false;
        $this->bonuses[$playerOrd] = $bonus;
        return true;
    }

    /**
     * @param int $playerOrd
     * @return int|null null if not found
     */
    public function getPlayerBonus(int $playerOrd): int
    {
        return $this->bonuses[$playerOrd] ?? 0;
    }

    /**
     * @return array Player ord => bonus
     */
    public function getBonuses(): array
    {
        return $this->bonuses;
    }


    /**
     * @param array $maluses player ord => malus for this player
     * @return bool true if set
     */
    public function setAllPlayersMaluses(array $maluses): bool
    {
        if ($this->isPlayed()) return false;
        foreach ($maluses as $playerOrd => $malus) {
            if (!in_array($playerOrd, array_keys($this->players))) continue;
            if (!is_int($malus)) continue;
            $this->setPlayerMalus($playerOrd, $malus);
        }
        return true;
    }


    /**
     * @param int $playerOrd
     * @param int $malus
     * @return bool true if set
     */
    public function setPlayerMalus(int $playerOrd, int $malus): bool
    {
        if ($this->isPlayed()) return false;
        if (!in_array($playerOrd, array_keys($this->players))) return false;
        $this->maluses[$playerOrd] = $malus;
        return true;
    }

    /**
     * @param int $playerOrd
     * @return int|null null if not found
     */
    public function getPlayerMalus(int $playerOrd): int
    {
        return $this->maluses[$playerOrd] ?? 0;
    }

    /**
     * @return array Player ord => malus
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
     * @return $this
     */
    public function setCompetitionRound(int $competitionRound): self
    {
        $this->competitionRound = $competitionRound;
        return $this;
    }


    /**
     * @param int $playerOrd
     * @return bool
     */
    abstract public function hasPlayerWon(int $playerOrd): bool;


    /**
     * @return int|null null if no winner
     */
    public function getWinnerOrd(): ?int
    {
        foreach ($this->getPlayers() as $playerOrd) {
            if ($this->hasPlayerWon($playerOrd)) return $playerOrd;
        }
        return null;
    }


}
