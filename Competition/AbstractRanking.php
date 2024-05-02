<?php

namespace Keiwen\Utils\Competition;

abstract class AbstractRanking
{

    protected $entityKey;
    protected $entitySeed = 0;

    protected $gameByResult = array();

    protected $performances = array();
    protected $expenses = array();
    protected $bonusCount = 0;
    protected $malusCount = 0;

    protected $combinedRankings = 0;
    /** @var RankingsHolder $rankingsHolder */
    protected $rankingsHolder;

    public function __construct($entityKey, int $entitySeed = 0)
    {
        $this->entityKey = $entityKey;
        $this->entitySeed = $entitySeed;
        // initialize game by result
        $this->rankingsHolder = static::generateDefaultRankingsHolder();
        $this->gameByResult = array_fill_keys(array_keys($this->rankingsHolder->getPointsByResult()), 0);
    }


    abstract public static function generateDefaultRankingsHolder(): RankingsHolder;

    public function setRankingsHolder(RankingsHolder $rankingsHolder)
    {
        $this->rankingsHolder = $rankingsHolder;

        // check if we have new results type, initiate in gameByResult
        $results = array_keys($this->rankingsHolder->getPointsByResult());
        foreach($results as $result) {
            if (!isset($this->gameByResult[$result])) $this->gameByResult[$result] = 0;
        }
    }

    public function getEntitySeed(): int
    {
        return $this->entitySeed;
    }

    /**
     * @return int|string
     */
    public function getEntityKey()
    {
        return $this->entityKey;
    }

    public function getPlayed(): int
    {
        $played = 0;
        foreach ($this->gameByResult as $gameCount) {
            $played += $gameCount;
        }
        return $played;
    }

    public function getPlayedByResult($result): int
    {
        return $this->gameByResult[$result] ?? 0;
    }

    /**
     * @return array result => number of game ended with this result
     */
    public function getGameByResult(): array
    {
        return $this->gameByResult;
    }


    public function getPoints(): int
    {
        $points = 0;
        $pointsByResult = $this->rankingsHolder->getPointsByResult();
        foreach ($pointsByResult as $result => $resultPoints) {
            $points += $resultPoints * $this->getPlayedByResult($result);
        }
        $points = $points + $this->getBonusPoints() - $this->getMalusPoints();
        return $points;
    }

    public function getBonusCount(): int
    {
        return $this->bonusCount;
    }

    public function getMalusCount(): int
    {
        return $this->malusCount;
    }

    public function getBonusPoints(): int
    {
        return $this->bonusCount * $this->rankingsHolder->getPointsByBonus();
    }

    public function getMalusPoints(): int
    {
        return $this->malusCount * $this->rankingsHolder->getPointsByMalus();
    }

    /**
     * @param string $performanceType
     * @return int
     */
    public function getPerformanceTotal(string $performanceType): int
    {
        return $this->performances[$performanceType] ?? 0;
    }


    /**
     * @param string $expenseType
     * @return int
     */
    public function getExpenseTotal(string $expenseType): int
    {
        return $this->expenses[$expenseType] ?? 0;
    }

    /**
     * @param string $expenseType
     * @return int
     */
    public function getRemainingCapital(string $expenseType): int
    {
        if ($this->combinedRankings <= 1) {
            return ($this->rankingsHolder->getStartingCapitalForExpense($expenseType) - $this->getExpenseTotal($expenseType));
        }
        $totalCapital = $this->combinedRankings * $this->rankingsHolder->getStartingCapitalForExpense($expenseType);
        return $totalCapital - $this->getExpenseTotal($expenseType);
    }



    /**
     * @param AbstractGame $game
     * @return bool true if saved
     */
    abstract public function saveGame(AbstractGame $game): bool;

    /**
     * Only performances as integer values are stored for rankings
     * @param AbstractGame $game
     * @return bool
     */
    protected function saveGamePerformances(AbstractGame $game): bool
    {
        $playerPerformances = $game->getPlayerPerformances($this->getEntityKey());
        if (empty($playerPerformances)) return false;
        foreach ($playerPerformances as $type => $performance) {
            if (empty($performance) || !is_int($performance)) $performance = 0;
            if (!isset($this->performances[$type])) $this->performances[$type] = 0;
            $this->performances[$type] += $performance;
        }
        return true;
    }

    protected function saveGameExpenses(AbstractGame $game): bool
    {
        $playerExpenses = $game->getPlayerExpenses($this->getEntityKey());
        if (empty($playerExpenses)) return false;
        foreach ($playerExpenses as $type => $expense) {
            if (empty($expense) || !is_numeric($expense)) $expense = 0;
            if (!isset($this->expenses[$type])) $this->expenses[$type] = 0;
            $this->expenses[$type] += $expense;
        }
        return true;
    }

    protected function saveGameBonusAndMalus(AbstractGame $game): bool
    {
        $this->bonusCount += $game->getPlayerBonus($this->getEntityKey());
        $this->malusCount += $game->getPlayerMalus($this->getEntityKey());
        return true;
    }


    /**
     * @param AbstractRanking $rankingToCompare
     * @return int
     */
    public function compareToRanking(AbstractRanking $rankingToCompare): int
    {
        // first compare points: more points is first
        if ($this->getPoints() > $rankingToCompare->getPoints()) return 1;
        if ($this->getPoints() < $rankingToCompare->getPoints()) return -1;

        // then compare performances if declared
        $perfRanking = $this->rankingsHolder->orderRankingsByPerformances($this, $rankingToCompare);
        if ($perfRanking !== 0) return $perfRanking;
        // then compare expenses if declared
        $expenseRanking = $this->rankingsHolder->orderRankingsByExpenses($this, $rankingToCompare);
        if ($expenseRanking !== 0) return $expenseRanking;

        // played games: less played is first
        if ($this->getPlayed() < $rankingToCompare->getPlayed()) return 1;
        if ($this->getPlayed() > $rankingToCompare->getPlayed()) return -1;
        // last case, first registered entity is first
        if ($this->getEntitySeed() < $rankingToCompare->getEntitySeed()) return 1;
        return -1;
    }


    /**
     * Used for team ranking. Create a brand new rankings and combine all players rankings
     * @param AbstractRanking[] $rankings players rankings to combine
     */
    public function combineRankings(array $rankings)
    {
        $this->combinedRankings += count($rankings);
        foreach ($rankings as $ranking) {
            foreach ($ranking->getGameByResult() as $result => $numberOfGames) {
                if (!isset($this->gameByResult[$result])) $this->gameByResult[$result] = 0;
                $this->gameByResult[$result] += $numberOfGames;
            }
            $this->bonusCount += $ranking->getBonusCount();
            $this->malusCount += $ranking->getMalusCount();

            foreach ($this->rankingsHolder->getPerformanceTypesToRank() as $type) {
                if (!isset($this->performances[$type])) $this->performances[$type] = 0;
                $this->performances[$type] += $ranking->getPerformanceTotal($type);
            }
            foreach ($this->rankingsHolder->getExpenseTypesToRank() as $type) {
                if (!isset($this->expenses[$type])) $this->expenses[$type] = 0;
                $this->expenses[$type] += $ranking->getExpenseTotal($type);
            }
        }
    }


}
