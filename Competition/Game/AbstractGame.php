<?php

namespace Keiwen\Utils\Competition\Game;

use Keiwen\Utils\Competition\Type\AbstractCompetition;

abstract class AbstractGame
{

    protected $name = '';
    /** @var array $playersStartingOrder key => starting order */
    protected $playersStartingOrder = array();
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

    protected function setPlayers(array $playersKeyList)
    {
        $playersKeyList = array_unique($playersKeyList);
        $this->playersStartingOrder = array_combine(array_values($playersKeyList), range(1, count($playersKeyList)));
    }


    /**
     * @return int
     */
    public function getPlayerCount(): int
    {
        return count($this->playersStartingOrder);
    }

    /**
     * @param int|string $playerKey
     * @return int 0 if not found
     */
    public function getPlayerStartingOrder($playerKey): int
    {
        return $this->playersStartingOrder[$playerKey] ?? 0;
    }


    /**
     * @param int $startingOrder
     * @return int|string|null player key or null if not found
     */
    public function getPlayerKeyThatStartedAt(int $startingOrder)
    {
        $startingOrders = array_flip($this->playersStartingOrder);
        return $startingOrders[$startingOrder] ?? null;
    }


    /**
     * @return array game starting order => Player key
     */
    public function getPlayersKeys(): array
    {
        return array_flip($this->playersStartingOrder);
    }


    /**
     * @return array game starting order => player
     */
    public function getPlayers(): array
    {
        $playersKeys = $this->getPlayersKeys();
        if (!$this->isAffected()) return $playersKeys;
        $affectedCompetition = $this->getAffectation();
        $players = array();
        foreach ($playersKeys as $startingOrder => $playerKey) {
            $players[$startingOrder] = $affectedCompetition->getPlayer($playerKey);
        }
        return $players;
    }

    /**
     * @param int|string $playerKey
     * @param mixed $result
     */
    protected function setPlayerResult($playerKey, $result)
    {
        if ($playerKey !== null) $this->results[$playerKey] = $result;
    }

    /**
     * @param int|string $playerKey
     * @return mixed|null null if not found
     */
    public function getPlayerResult($playerKey)
    {
        return $this->results[$playerKey] ?? null;
    }

    /**
     * @return array Player key => result
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
     * @param array $performances player key => performances for this player
     * @return bool true if set
     */
    public function setAllPlayersPerformances(array $performances): bool
    {
        if ($this->isPlayed()) return false;
        foreach ($performances as $playerKey => $playerPerformances) {
            if (!$this->isPlayerInGame($playerKey)) continue;
            if (!is_array($playerPerformances)) continue;
            $this->setPlayerPerformances($playerKey, $playerPerformances);
        }
        return true;
    }


    /**
     * @param int|string $playerKey
     * @param array $performances
     * @return bool true if set
     */
    public function setPlayerPerformances($playerKey, array $performances): bool
    {
        if ($this->isPlayed()) return false;
        if (!$this->isPlayerInGame($playerKey)) return false;
        $this->performances[$playerKey] = $performances;
        return true;
    }

    /**
     * @param int|string $playerKey
     * @param string $performanceType
     * @param mixed $performance
     * @return bool true if set
     */
    public function setPlayerPerformanceType($playerKey, string $performanceType, $performance): bool
    {
        if ($this->isPlayed()) return false;
        if (!$this->isPlayerInGame($playerKey)) return false;
        if (empty($this->performances[$playerKey])) $this->performances[$playerKey] = array();
        $this->performances[$playerKey][$performanceType] = $performance;
        return true;
    }

    /**
     * @param int|string $playerKey
     * @return array|null null if not found
     */
    public function getPlayerPerformances($playerKey): ?array
    {
        return $this->performances[$playerKey] ?? null;
    }

    /**
     * @param int|string $playerKey
     * @param string $performanceType
     * @return mixed|null null if not found
     */
    public function getPlayerPerformanceType($playerKey, string $performanceType)
    {
        $performances = $this->getPlayerPerformances($playerKey);
        if (empty($performances)) return null;
        return $performances[$performanceType] ?? null;
    }

    /**
     * @return array Player key => performances
     */
    public function getPerformances(): array
    {
        return $this->performances;
    }

    /**
     * @param array $expenses player key => expenses for this player
     * @return bool true if set
     */
    public function setAllPlayersExpenses(array $expenses): bool
    {
        if ($this->isPlayed()) return false;
        foreach ($expenses as $playerKey => $playerExpenses) {
            if (!$this->isPlayerInGame($playerKey)) continue;
            if (!is_array($playerExpenses)) continue;
            $this->setPlayerExpenses($playerKey, $playerExpenses);
        }
        return true;
    }


    /**
     * @param int|string $playerKey
     * @param array $expenses
     * @return bool true if set
     */
    public function setPlayerExpenses($playerKey, array $expenses): bool
    {
        if ($this->isPlayed()) return false;
        if (!$this->isPlayerInGame($playerKey)) return false;
        $this->expenses[$playerKey] = $expenses;
        return true;
    }

    /**
     * @param int|string $playerKey
     * @param string $expenseType
     * @param mixed $expense
     * @return bool true if set
     */
    public function setPlayerExpenseType($playerKey, string $expenseType, $expense): bool
    {
        if ($this->isPlayed()) return false;
        if (!$this->isPlayerInGame($playerKey)) return false;
        if (empty($this->expenses[$playerKey])) $this->expenses[$playerKey] = array();
        $this->expenses[$playerKey][$expenseType] = $expense;
        return true;
    }

    /**
     * @param int|string $playerKey
     * @return array|null null if not found
     */
    public function getPlayerExpenses($playerKey): ?array
    {
        return $this->expenses[$playerKey] ?? null;
    }

    /**
     * @param int|string $playerKey
     * @param string $expenseType
     * @return mixed|null null if not found
     */
    public function getPlayerExpenseType($playerKey, string $expenseType)
    {
        $expenses = $this->getPlayerExpenses($playerKey);
        if (empty($expenses)) return null;
        return $expenses[$expenseType] ?? null;
    }

    /**
     * @return array Player key => expenses
     */
    public function getExpenses(): array
    {
        return $this->expenses;
    }


    /**
     * @param array $bonuses player key => bonus for this player
     * @return bool true if set
     */
    public function setAllPlayersBonuses(array $bonuses): bool
    {
        if ($this->isPlayed()) return false;
        foreach ($bonuses as $playerKey => $bonus) {
            if (!$this->isPlayerInGame($playerKey)) continue;
            if (!is_int($bonus)) continue;
            $this->setPlayerBonus($playerKey, $bonus);
        }
        return true;
    }


    /**
     * @param int|string $playerKey
     * @param int $bonus
     * @return bool true if set
     */
    public function setPlayerBonus($playerKey, int $bonus): bool
    {
        if ($this->isPlayed()) return false;
        if (!$this->isPlayerInGame($playerKey)) return false;
        $this->bonuses[$playerKey] = $bonus;
        return true;
    }

    /**
     * @param int|string $playerKey
     * @return int|null null if not found
     */
    public function getPlayerBonus($playerKey): int
    {
        return $this->bonuses[$playerKey] ?? 0;
    }

    /**
     * @return array Player key => bonus
     */
    public function getBonuses(): array
    {
        return $this->bonuses;
    }


    /**
     * @param array $maluses player key => malus for this player
     * @return bool true if set
     */
    public function setAllPlayersMaluses(array $maluses): bool
    {
        if ($this->isPlayed()) return false;
        foreach ($maluses as $playerKey => $malus) {
            if (!$this->isPlayerInGame($playerKey)) continue;
            if (!is_int($malus)) continue;
            $this->setPlayerMalus($playerKey, $malus);
        }
        return true;
    }


    /**
     * @param int|string $playerKey
     * @param int $malus
     * @return bool true if set
     */
    public function setPlayerMalus($playerKey, int $malus): bool
    {
        if ($this->isPlayed()) return false;
        if (!$this->isPlayerInGame($playerKey)) return false;
        $this->maluses[$playerKey] = $malus;
        return true;
    }

    /**
     * @param int|string $playerKey
     * @return int|null null if not found
     */
    public function getPlayerMalus($playerKey): int
    {
        return $this->maluses[$playerKey] ?? 0;
    }

    /**
     * @return array Player key => malus
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
     * @param int|string $playerKey
     * @return bool
     */
    abstract public function hasPlayerWon($playerKey): bool;


    /**
     * @return array keys of all winners
     */
    public function getWinnerKeys(): array
    {
        $winners = array();
        foreach ($this->getPlayersKeys() as $playerKey) {
            if ($this->hasPlayerWon($playerKey)) $winners[] = $playerKey;
        }
        return $winners;
    }

    /**
     * @param int|string $playerKey
     * @return bool
     */
    public function isPlayerInGame($playerKey): bool
    {
        return in_array($playerKey, array_keys($this->playersStartingOrder));
    }

}
