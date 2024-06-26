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

    protected static $pointByResult = array(
        GamePerformances::RESULT_WON => 0,
        GamePerformances::RESULT_LOSS => 0,
    );

    protected static $rankMethod = self::RANK_METHOD_SUM;

    /**
     * @param string $rankMethod
     * @return bool true if set
     */
    public static function setRankMethod(string $rankMethod): bool
    {
        if (in_array($rankMethod, static::getRankMethods())) {
            static::$rankMethod = $rankMethod;
            return true;
        }
        return false;
    }

    public static function getRankMethod(): string
    {
        return static::$rankMethod;
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


    public static function setPointsAttribution(array $points): bool
    {
        return false;
    }

    public static function setPointsAttributionForResult($result, int $points): bool
    {
        return false;
    }

    public static function getPointsForResult($result): int
    {
        return 0;
    }

    public static function setPointsByBonus(int $points): bool
    {
        return false;
    }

    public static function getPointsByBonus(): int
    {
        return 0;
    }

    public static function setPointsByMalus(int $points): bool
    {
        return false;
    }

    public static function getPointsByMalus(): int
    {
        return 0;
    }

    public function getPoints(): int
    {
        switch (static::$rankMethod) {
            case self::RANK_METHOD_WON:
                return $this->getWon();
                break;
            case self::RANK_METHOD_AVERAGE:
                return round($this->getAveragePerformance());
                break;
            case self::RANK_METHOD_MAX:
                return $this->getMaxPerformance();
                break;
            case self::RANK_METHOD_LAST_ROUND_RANK:
                return $this->getLastRoundPoints();
                break;
            case self::RANK_METHOD_SUM:
            default:
                return $this->getPerformancesSum();
                break;
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
     * @return int
     */
    public static function orderRankings(AbstractRanking $rankingA, AbstractRanking $rankingB): int
    {
        static::checkStaticRankingClass($rankingA, $rankingB);
        // first compare points (depending on rank method): more points is first
        if ($rankingA->getPoints() > $rankingB->getPoints()) return 1;
        if ($rankingA->getPoints() < $rankingB->getPoints()) return -1;
        // compare perf sum: best sum is first
        if ($rankingA->getPerformancesSum() > $rankingB->getPerformancesSum()) return 1;
        if ($rankingA->getPerformancesSum() < $rankingB->getPerformancesSum()) return -1;
        // average perf: best average is first
        if ($rankingA->getAveragePerformance() > $rankingB->getAveragePerformance()) return 1;
        if ($rankingA->getAveragePerformance() < $rankingB->getAveragePerformance()) return -1;
        // won games: more won is first
        if ($rankingA->getWon() > $rankingB->getWon()) return 1;
        if ($rankingA->getWon() < $rankingB->getWon()) return -1;

        // compare performances if declared
        $perfRanking = static::orderRankingsByPerformances($rankingA, $rankingB);
        if ($perfRanking !== 0) return $perfRanking;

        // played games: more played is first
        if ($rankingA->getPlayed() > $rankingB->getPlayed()) return 1;
        if ($rankingA->getPlayed() < $rankingB->getPlayed()) return -1;
        // last case, first registered entity is first
        if ($rankingA->getEntitySeed() < $rankingB->getEntitySeed()) return 1;
        return -1;
    }

    /**
     * @param RankingPerformances[] $rankings
     */
    public function combinedRankings(array $rankings)
    {
        parent::combinedRankings($rankings);
        foreach ($rankings as $ranking) {
            if ($ranking->maxPerformance > $this->maxPerformance) $this->maxPerformance = $ranking->getMaxPerformance();
            $this->sumPerformance += $ranking->getPerformancesSum();
            $this->lastRoundPoints += $ranking->getLastRoundPoints();
        }
    }

}
