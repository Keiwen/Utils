<?php

namespace Keiwen\Utils\Competition;

class RankingPerformances extends AbstractRanking
{
    const RANK_METHOD_SUM = 'sum';
    const RANK_METHOD_AVERAGE = 'average';
    const RANK_METHOD_WON = 'won';
    const RANK_METHOD_MAX = 'max';
    const RANK_METHOD_LAST_ROUND_RANK = 'last_round_rank';

    protected $maxPerformance = 0;
    protected $sumPerformance = 0;
    protected $lastRoundPoints = 0;


    public static function generateDefaultRankingsHolder(): RankingsHolder
    {
        $holder = new RankingsHolder(static::class);
        $holder->setPointsAttributionForResult(GamePerformances::RESULT_WON, 0);
        $holder->setPointsAttributionForResult(GamePerformances::RESULT_LOSS, 0);
        foreach (static::getDefaultPerformanceTypesToRank() as $performanceType) {
            $holder->addPerformanceTypeToRank($performanceType);
        }
        $holder->setPointsByBonus(0);
        $holder->setPointsByMalus(0);
        $holder->setPerfRankMethod(self::RANK_METHOD_SUM);
        return $holder;
    }

    /**
     * @return string[] list of ranking method
     */
    public static function getRankMethods(): array
    {
        return array(
            self::RANK_METHOD_SUM,
            self::RANK_METHOD_AVERAGE,
            self::RANK_METHOD_WON,
            self::RANK_METHOD_MAX,
            self::RANK_METHOD_LAST_ROUND_RANK,
        );
    }

    public function getPoints(): int
    {
        switch ($this->rankingsHolder->getPerfRankMethod()) {
            case self::RANK_METHOD_WON:
                return $this->getWon();
            case self::RANK_METHOD_AVERAGE:
                return round($this->getAveragePerformance());
            case self::RANK_METHOD_MAX:
                return $this->getMaxPerformance();
            case self::RANK_METHOD_LAST_ROUND_RANK:
                return $this->getLastRoundPoints();
            case self::RANK_METHOD_SUM:
            default:
                return $this->getPerformancesSum();
        }
    }

    /**
     * Sum of all performances if values are integer
     * @return int
     */
    public function getPerformancesSum(): int
    {
        return $this->sumPerformance;
    }


    public function getWon(): int
    {
        return $this->getPlayedByResult(GamePerformances::RESULT_WON);
    }

    public function getLoss(): int
    {
        return $this->getPlayedByResult(GamePerformances::RESULT_LOSS);
    }

    public function getAveragePerformance(): float
    {
        if (!$this->getPlayed()) return 0;
        return $this->getPerformancesSum() / $this->getPlayed();
    }


    public function saveGame(AbstractGame $game): bool
    {
        if (!$game instanceof GamePerformances) {
            throw new CompetitionException(sprintf('Ranking performances require %s as game, %s given', GamePerformances::class, get_class($game)));
        }

        $this->saveGamePerformances($game);
        $this->saveGameExpenses($game);
        $this->saveGameBonusAndMalus($game);
        $this->saveSumPerformance($game);
        $this->saveLastRoundPoints($game);

        if ($game->hasPlayerWon($this->getEntityKey())) {
            $this->gameByResult[GamePerformances::RESULT_WON]++;
        } else {
            $this->gameByResult[GamePerformances::RESULT_LOSS]++;
        }
        return true;
    }

    /**
     * @param GamePerformances $game
     * @return bool
     */
    protected function saveSumPerformance(AbstractGame $game): bool
    {
        $gamePerf = $game->getPlayerPerformancesSum($this->getEntityKey());
        if ($gamePerf > $this->maxPerformance) $this->maxPerformance = $gamePerf;
        $this->sumPerformance += $gamePerf;
        return true;
    }

    public function getMaxPerformance(): int
    {
        return $this->maxPerformance;
    }


    /**
     * @param GamePerformances $game
     * @return bool
     */
    protected function saveLastRoundPoints(AbstractGame $game): bool
    {
        $playerRank = $game->getPlayerGameRank($this->getEntityKey());
        if ($playerRank === 0) return false;
        $competition = $game->getAffectation();
        $playersInCompetition = empty($competition) ? count($game->getPlayers()) : $competition->getPlayerCount();
        $this->lastRoundPoints = $playersInCompetition - $playerRank + $game->getCompetitionRound();
        return true;
    }

    public function getLastRoundPoints(): int
    {
        return $this->lastRoundPoints;
    }

    /**
     * @param RankingPerformances $rankingToCompare
     * @return int
     */
    public function compareToRanking(AbstractRanking $rankingToCompare): int
    {
        // first compare points (depending on rank method): more points is first
        if ($this->getPoints() > $rankingToCompare->getPoints()) return 1;
        if ($this->getPoints() < $rankingToCompare->getPoints()) return -1;
        // compare perf sum: best sum is first
        if ($this->getPerformancesSum() > $rankingToCompare->getPerformancesSum()) return 1;
        if ($this->getPerformancesSum() < $rankingToCompare->getPerformancesSum()) return -1;
        // average perf: best average is first
        if ($this->getAveragePerformance() > $rankingToCompare->getAveragePerformance()) return 1;
        if ($this->getAveragePerformance() < $rankingToCompare->getAveragePerformance()) return -1;
        // won games: more won is first
        if ($this->getWon() > $rankingToCompare->getWon()) return 1;
        if ($this->getWon() < $rankingToCompare->getWon()) return -1;

        // compare performances if declared
        $perfRanking = $this->rankingsHolder->orderRankingsByPerformances($this, $rankingToCompare);
        if ($perfRanking !== 0) return $perfRanking;

        // played games: more played is first
        if ($this->getPlayed() > $rankingToCompare->getPlayed()) return 1;
        if ($this->getPlayed() < $rankingToCompare->getPlayed()) return -1;
        // last case, first registered entity is first
        if ($this->getEntitySeed() < $rankingToCompare->getEntitySeed()) return 1;
        return -1;
    }

    /**
     * @param RankingPerformances[] $rankings
     */
    public function combineRankings(array $rankings)
    {
        parent::combineRankings($rankings);
        foreach ($rankings as $ranking) {
            if ($ranking->maxPerformance > $this->maxPerformance) $this->maxPerformance = $ranking->getMaxPerformance();
            $this->sumPerformance += $ranking->getPerformancesSum();
            $this->lastRoundPoints += $ranking->getLastRoundPoints();
        }
    }

}
