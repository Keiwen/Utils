<?php

namespace Keiwen\Utils\Competition;

class RankingsHolder
{

    protected $rankingClassName;

    protected $rankings = array();
    protected $orderedRankings = array();
    protected $performanceTypesToRank = array();
    protected $expensesTypesToRank = array();

    protected $pointByResult = array();
    protected $startingCapitals = array();
    protected $pointByBonus = 1;
    protected $pointByMalus = 1;

    protected $perfRankMethod = RankingPerformances::RANK_METHOD_SUM;
    protected $duelPointMethod = RankingDuel::POINT_METHOD_BASE;
    protected $duelTieBreakerMethod = RankingDuel::POINT_METHOD_BASE;


    public function __construct(string $rankingClassName)
    {
        if (!is_subclass_of($rankingClassName, AbstractRanking::class)) {
            throw new CompetitionException('Ranking class %s must extends AbstractRanking', $rankingClassName);
        }
        $this->rankingClassName = $rankingClassName;
    }


    public function duplicateEmptyHolder(): self
    {
        $duplicate = new static($this->rankingClassName);
        foreach ($this->getPerformanceTypesToRank() as $performanceType) {
            $duplicate->addPerformanceTypeToRank($performanceType);
        }
        foreach ($this->getExpenseTypesToRank() as $expenseType) {
            $duplicate->addExpenseTypeToRank($expenseType);
        }
        $duplicate->setPointsAttribution($this->getPointsByResult());
        $duplicate->setStartingCapitals($this->getStartingCapitals());
        $duplicate->setPointsByBonus($this->getPointsByBonus());
        $duplicate->setPointsByMalus($this->getPointsByMalus());
        $duplicate->setPerfRankMethod($this->getPerfRankMethod());
        $duplicate->setDuelPointMethod($this->getDuelPointMethod());
        $duplicate->setDuelTieBreakerMethod($this->getDuelTieBreakerMethod());

        return $duplicate;
    }

    /**
     * @param int|string $entityKey
     * @param int $entitySeed
     */
    public function addRanking($entityKey, int $entitySeed = 0)
    {
        $ranking = new ($this->rankingClassName)($entityKey, $entitySeed);
        $this->integrateRanking($ranking);
    }

    /**
     * @param AbstractRanking $ranking
     */
    public function integrateRanking(AbstractRanking $ranking)
    {
        $ranking->setRankingsHolder($this);
        $this->rankings[$ranking->getEntityKey()] = $ranking;
    }

    /**
     * @param int|string $entityKey
     * @return AbstractRanking|null
     */
    public function getRanking($entityKey): ?AbstractRanking
    {
        return $this->rankings[$entityKey] ?? null;
    }

    /**
     * @param int $rank
     * @return AbstractRanking|null
     */
    public function getRank(int $rank): ?AbstractRanking
    {
        return $this->orderedRankings[$rank - 1] ?? null;
    }


    /**
     * @param int|string $entityKey
     * @return int 0 if not found
     */
    public function getEntityRank($entityKey): int
    {
        $rank = 0;
        foreach ($this->orderedRankings as $ranking) {
            $rank++;
            if ($ranking->getEntityKey() === $entityKey) return $rank;
        }
        return 0;
    }


    /**
     * @return AbstractRanking[] entityKey => ranking object
     */
    public function getAllRankings(): array
    {
        return $this->rankings;
    }

    /**
     * @param mixed $result
     * @param int $points
     * @return bool true if set
     */
    public function setPointsAttributionForResult($result, int $points): bool
    {
        $this->pointByResult[$result] = $points;
        return true;
    }


    public function resetPointAttribution()
    {
        $this->pointByResult = array();
    }



    /**
     * @param array $points
     * @return bool true if set
     */
    public function setPointsAttribution(array $points): bool
    {
        if (count($points) != count($this->pointByResult)) return false;
        $loopIndex = 0;
        foreach ($this->pointByResult as $result => $resultPoints) {
            if (is_int($points[$loopIndex])) {
                $this->setPointsAttributionForResult($result, $points[$loopIndex]);
            }
            $loopIndex++;
        }
        return true;
    }

    public function getPointsForResult($result): int
    {
        return $this->pointByResult[$result] ?? 0;
    }

    /**
     * @return array result => points
     */
    public function getPointsByResult(): array
    {
        return $this->pointByResult;
    }

    /**
     * @param string $expenseType
     * @param int $startingCapital
     * @return bool true if set
     */
    public function setStartingCapitalForExpense(string $expenseType, int $startingCapital): bool
    {
        $this->startingCapitals[$expenseType] = $startingCapital;
        return true;
    }

    /**
     * @param array $capitals
     * @return bool true if set
     */
    public function setStartingCapitals(array $capitals): bool
    {
        if (count($capitals) != count($this->startingCapitals)) return false;
        $loopIndex = 0;
        foreach ($this->startingCapitals as $expense => $startingCapital) {
            if (is_int($capitals[$loopIndex])) {
                $this->setStartingCapitalForExpense($expense, $capitals[$loopIndex]);
            }
            $loopIndex++;
        }
        return true;
    }

    public function getStartingCapitalForExpense(string $expenseType): int
    {
        return $this->startingCapitals[$expenseType] ?? 0;
    }

    /**
     * @return int[] expense type => capital
     */
    public function getStartingCapitals(): array
    {
        return $this->startingCapitals;
    }

    /**
     * @param int $points
     * @return bool true if set
     */
    public function setPointsByBonus(int $points): bool
    {
        $this->pointByBonus = $points;
        return true;
    }

    public function getPointsByBonus(): int
    {
        return $this->pointByBonus;
    }

    /**
     * @param int $points set as positive value, these points will be substracted from total points
     * @return bool true if set
     */
    public function setPointsByMalus(int $points): bool
    {
        $this->pointByMalus = $points;
        return true;
    }

    public function getPointsByMalus(): int
    {
        return $this->pointByMalus;
    }


    /**
     * add a performance type that should be taken into account when ranking.
     * the first added will be prioritized
     * @param string $performanceType
     * @param bool $reverse if true, lowest value in this performance will rank higher
     */
    public function addPerformanceTypeToRank(string $performanceType, bool $reverse = false)
    {
        if ($reverse) $performanceType = '-'.$performanceType;
        $this->performanceTypesToRank[] = $performanceType;
    }

    /**
     * @return array
     */
    public function getPerformanceTypesToRank(): array
    {
        return $this->performanceTypesToRank;
    }

    /**
     * add an expense type that should be taken into account when ranking.
     * the first added will be prioritized. Less expense will have best rank
     * @param string $expenseType
     */
    public function addExpenseTypeToRank(string $expenseType, bool $reverse = false)
    {
        if ($reverse) $expenseType = '-'.$expenseType;
        $this->expensesTypesToRank[] = $expenseType;
    }

    /**
     * @return array
     */
    public function getExpenseTypesToRank(): array
    {
        return $this->expensesTypesToRank;
    }


    protected function checkRankingClass(AbstractRanking $ranking)
    {
        if (!$ranking instanceof $this->rankingClassName) {
            throw new CompetitionException(sprintf('Ranking ordering require %s as ranking, %s given', $this->rankingClassName, get_class($ranking)));
        }
    }


    /**
     * @return int
     */
    public function orderRankings(AbstractRanking $rankingA, AbstractRanking $rankingB): int
    {
        $this->checkRankingClass($rankingA);
        $this->checkRankingClass($rankingB);

        return $rankingA->compareToRanking($rankingB);
    }


    /**
     * @return int
     */
    public function orderRankingsByPerformances(AbstractRanking $rankingA, AbstractRanking $rankingB): int
    {
        $this->checkRankingClass($rankingA);
        $this->checkRankingClass($rankingB);
        foreach ($this->performanceTypesToRank as $performanceType) {
            if (strpos($performanceType, '-') === 0) {
                $performanceType = substr($performanceType, 1);
                // if minus found before the name, remove it and prioritize the lowest
                if ($rankingA->getPerformanceTotal($performanceType) < $rankingB->getPerformanceTotal($performanceType)) return 1;
                if ($rankingA->getPerformanceTotal($performanceType) > $rankingB->getPerformanceTotal($performanceType)) return -1;
            } else {
                // greater value win
                if ($rankingA->getPerformanceTotal($performanceType) > $rankingB->getPerformanceTotal($performanceType)) return 1;
                if ($rankingA->getPerformanceTotal($performanceType) < $rankingB->getPerformanceTotal($performanceType)) return -1;
            }
            // equal on this performance, go to the next
        }
        // all performances are equal, cannot decide
        return 0;
    }

    /**
     * @return int
     */
    public function orderRankingsByExpenses(AbstractRanking $rankingA, AbstractRanking $rankingB): int
    {
        $this->checkRankingClass($rankingA);
        $this->checkRankingClass($rankingB);
        foreach ($this->expensesTypesToRank as $expenseType) {
            if (strpos($expenseType, '-') === 0) {
                $expenseType = substr($expenseType, 1);
                // if minus found before the name, remove it and prioritize the highest
                if ($rankingA->getExpenseTotal($expenseType) > $rankingB->getExpenseTotal($expenseType)) return 1;
                if ($rankingA->getExpenseTotal($expenseType) < $rankingB->getExpenseTotal($expenseType)) return -1;
            } else {
                // lower value win
                if ($rankingA->getExpenseTotal($expenseType) < $rankingB->getExpenseTotal($expenseType)) return 1;
                if ($rankingA->getExpenseTotal($expenseType) > $rankingB->getExpenseTotal($expenseType)) return -1;
            }
            // equal on this expense, go to the next
        }
        // all expenses are equal, cannot decide
        return 0;
    }


    /**
     * @param string $rankMethod
     * @return bool true if set
     */
    public function setPerfRankMethod(string $rankMethod): bool
    {
        if (in_array($rankMethod, RankingPerformances::getRankMethods())) {
            $this->perfRankMethod = $rankMethod;
            return true;
        }
        return false;
    }

    public function getPerfRankMethod(): string
    {
        return $this->perfRankMethod;
    }


    public function getDuelPointMethod(): string
    {
        return $this->duelPointMethod;
    }

    public function setDuelPointMethod(string $method): bool
    {
        if (in_array($method, RankingDuel::getPointMethods())) {
            $this->duelPointMethod = $method;
            return true;
        }
        return false;
    }

    public function getDuelTieBreakerMethod(): string
    {
        return $this->duelTieBreakerMethod;
    }

    public function setDuelTieBreakerMethod(string $method): bool
    {
        if (in_array($method, RankingDuel::getPointMethods(true))) {
            $this->duelTieBreakerMethod = $method;
            return true;
        }
        return false;
    }


    /**
     * @return AbstractRanking[]
     */
    public function computeRankingsOrder(): array
    {
        $rankings = array_values($this->rankings);
        if (!empty($rankings)) {
            $firstRanking = reset($rankings);

            if ($firstRanking instanceof RankingDuel) {
                // update point method before actually order
                foreach ($rankings as $ranking) {
                    /** @var RankingDuel $ranking */
                    $ranking->updatePointMethodCalcul(false);
                    $ranking->updatePointMethodCalcul(true);
                }
            }

            usort($rankings, array($this, 'orderRankings'));
            $rankings = array_reverse($rankings);
        }
        $this->orderedRankings = $rankings;
        return $rankings;
    }


    /**
     * @return AbstractRanking[]
     */
    public function getRankings(): array
    {
        return $this->orderedRankings;
    }


    /**
     * @return AbstractRanking[]
     */
    public function getRankingsByExpenses(): array
    {
        $rankings = array_values($this->rankings);
        if (!empty($rankings)) {
            usort($rankings, array($this, 'orderRankingsByExpenses'));
            $rankings = array_reverse($rankings);
        }
        return $rankings;
    }


    /**
     * @param int[]|string[] $playerKeysSeeded
     * @return AbstractRanking[]
     */
    public function getRankingsBySeed(array $playerKeysSeeded): array
    {
        // instead of classic rankings orderings, rank as specified
        $orderedRankings = array();
        foreach ($playerKeysSeeded as $playerKey) {
            $playerRanking = $this->getRanking($playerKey);
            if (!empty($playerRanking)) {
                $orderedRankings[] = $playerRanking;
            }
        }
        return $orderedRankings;
    }


    /**
     * @param array $teamComposition $teamKey => array of player keys
     * @return AbstractRanking[]
     */
    public function getTeamRankings(array $teamComposition): array
    {
        $teamRankings = array();
        $teamSeed = 1;
        foreach ($teamComposition as $teamKey => $playerKeys) {
            if (!is_array($playerKeys)) continue;
            /** @var AbstractRanking $teamRanking */
            $teamRanking = new ($this->rankingClassName)($teamKey, $teamSeed);
            $playerRankings = array();
            foreach ($playerKeys as $playerKey) {
                $playerRanking = $this->getRanking($playerKey);
                if ($playerRanking) $playerRankings[] = $playerRanking;
            }

            $teamRanking->setRankingsHolder($this->duplicateEmptyHolder());
            $teamRanking->combineRankings($playerRankings);

            $teamRankings[$teamKey] = $teamRanking;
            $teamSeed++;
        }
        usort($teamRankings, array($this, 'orderRankings'));
        $teamRankings = array_reverse($teamRankings);

        return $teamRankings;
    }

    /**
     * @param array $teamComposition $teamKey => array of player keys
     * @param int[]|string[] $playerKeysSeeded
     * @return AbstractRanking[]
     */
    public function getTeamRankingsByAverageSeed(array $teamComposition, array $playerKeysSeeded): array
    {
        $teamRankings = array();
        $teamSeed = 1;
        $teamBySeed = array();
        $playerKeysSeeded = array_values($playerKeysSeeded);
        $playerIndexes = array_flip($playerKeysSeeded);
        // playerIndexes now have key => index and seed = index + 1

        // first get combined rankings while computing average seed of the team
        foreach ($teamComposition as $teamKey => $playerInTeamKeys) {
            if (!is_array($playerInTeamKeys)) continue;
            /** @var AbstractRanking $teamRanking */
            $teamRanking = new ($this->rankingClassName)($teamKey, $teamSeed);
            if (!$teamRanking instanceof RankingDuel) continue;
            $playerRankings = array();
            $sumSeeds = 0;
            foreach ($playerInTeamKeys as $playerKey) {
                $playerRanking = $this->getRanking($playerKey);
                if ($playerRanking) $playerRankings[] = $playerRanking;
                if (isset($playerIndexes[$playerKey])) {
                    $sumSeeds += $playerIndexes[$playerKey] + 1;
                }
            }

            $teamRanking->setRankingsHolder($this->duplicateEmptyHolder());
            $teamRanking->combineRankings($playerRankings);

            $teamRankings[$teamKey] = $teamRanking;

            // if no player, average seed is defined to 0
            $averageSeed = empty($playerInTeamKeys) ? 0 : $sumSeeds / count($playerInTeamKeys);
            $teamBySeed[$teamKey] = $averageSeed;
            $teamSeed++;
        }

        // sort by average seed (so lowest is better)
        asort($teamBySeed);

        // reorder rankings with seeding
        $orderedRankings = array();
        foreach ($teamBySeed as $teamKey => $averageSeed) {
            $orderedRankings[] = $teamRankings[$teamKey];
        }

        return $orderedRankings;
    }

}
