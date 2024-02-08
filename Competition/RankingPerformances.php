<?php

namespace Keiwen\Utils\Competition;

class RankingPerformances extends AbstractRanking
{
    const RANK_METHOD_SUM = 'sum';
    const RANK_METHOD_AVERAGE = 'average';
    const RANK_METHOD_WON = 'won';

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
        );
    }


    public static function setPointsAttribution(array $points)
    {
        return false;
    }

    public static function setPointsAttributionForResult($result, int $points)
    {
        return false;
    }

    public static function getPointsForResult($result): int
    {
        return 0;
    }

    public static function setPointsByBonus(int $points)
    {
        return false;
    }

    public static function getPointsByBonus(): int
    {
        return 0;
    }

    public static function setPointsByMalus(int $points)
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
                return $this->getAveragePerformance();
                break;
            case self::RANK_METHOD_SUM:
            default:
                return $this->getPerformancesSum();
                break;
        }
    }

    /**
     * Sum all performances if values are integer
     * @return int
     */
    public function getPerformancesSum(): int
    {
        $sum = 0;
        foreach (static::getPerformanceTypesToRank() as $type) {
            $typeSum = $this->getPerformanceTotal($type);
            if (is_int($typeSum)) $sum += $typeSum;
        }
        return $sum;
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

        if ($game->hasPlayerWon($this->getPlayerSeed())) {
            $this->gameByResult[GamePerformances::RESULT_WON]++;
        } else {
            $this->gameByResult[GamePerformances::RESULT_LOSS]++;
        }
        return true;
    }

    /**
     * @return int
     */
    public static function orderRankings(AbstractRanking $rankingA, AbstractRanking $rankingB)
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
        // last case, first registered player is first
        if ($rankingA->getPlayerSeed() < $rankingB->getPlayerSeed()) return 1;
        return -1;
    }

}
