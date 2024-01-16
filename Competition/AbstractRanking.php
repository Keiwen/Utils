<?php

namespace Keiwen\Utils\Competition;

abstract class AbstractRanking
{

    protected $idPlayer;

    protected $gameByResult = array();

    protected $performances = array();

    protected static $performanceTypesToRank = array();

    protected static $pointByResult = array();

    public function __construct(int $idPlayer)
    {
        $this->idPlayer = $idPlayer;
        // initialize game by result
        $this->gameByResult = array_fill_keys(array_keys(static::$pointByResult), 0);
    }


    public static function setPointsAttributionForResult($result, int $points)
    {
        static::$pointByResult[$result] = $points;
        return true;
    }

    /**
     * @param array $points
     * @return bool
     */
    public static function setPointsAttribution(array $points)
    {
        if (count($points) != count(static::$pointByResult)) return false;
        $loopIndex = 0;
        foreach (static::$pointByResult as $result => $resultPoints) {
            if (is_int($points[$loopIndex])) {
                static::setPointsAttributionForResult($result, $points[$loopIndex]);
            }
            $loopIndex++;
        }
        return true;
    }

    public static function getPointsForResult($result): int
    {
        return static::$pointByResult[$result] ?? 0;
    }

    public function getIdPlayer()
    {
        return $this->idPlayer;
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


    public function getPoints()
    {
        $points = 0;
        foreach (static::$pointByResult as $result => $resultPoints) {
            $points += $resultPoints * $this->getPlayedByResult($result);
        }
        return $points;
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
     * add a performance type that should be taken into account when ranking.
     * the first added will be prioritized
     * @param string $performanceType
     */
    public static function addPerformanceTypesToRank(string $performanceType, bool $reverse)
    {
        if ($reverse) $performanceType = '-'.$performanceType;
        static::$performanceTypesToRank[] = $performanceType;
    }


    abstract public function saveGame(AbstractGame $game): bool;

    protected function saveGamePerformances(AbstractGame $game): bool
    {
        $playerPerformances = $game->getPlayerPerformances($this->getIdPlayer());
        if (empty($playerPerformances)) return false;
        foreach ($playerPerformances as $type => $performance) {
            if (empty($performance) || !is_numeric($performance)) $performance = 0;
            if (!isset($this->performances[$type])) $this->performances[$type] = 0;
            $this->performances[$type] += $performance;
        }
        return true;
    }

    /**
     * @return int
     */
    public static function orderRankings(self $rankingA, self $rankingB)
    {
        static::checkStaticRankingClass($rankingA, $rankingB);
        // first compare points: more points is first
        if ($rankingA->getPoints() > $rankingB->getPoints()) return 1;
        if ($rankingA->getPoints() < $rankingB->getPoints()) return -1;

        // then compare performances if declared
        $perfRanking = static::orderRankingsByPerformances($rankingA, $rankingB);
        if ($perfRanking !== 0) return $perfRanking;

        // played games: less played is first
        if ($rankingA->getPlayed() < $rankingB->getPlayed()) return 1;
        if ($rankingA->getPlayed() > $rankingB->getPlayed()) return -1;
        // last case, first registered player is first
        if ($rankingA->getIdPlayer() < $rankingB->getIdPlayer()) return 1;
        return -1;
    }

    /**
     * @return int
     */
    protected static function orderRankingsByPerformances(self $rankingA, self $rankingB)
    {
        foreach (static::$performanceTypesToRank as $performanceType) {
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

    protected static function checkStaticRankingClass(self $rankingA, self $rankingB)
    {
        if (!$rankingA instanceof static) {
            throw new CompetitionException(sprintf('Ranking ordering require %s as ranking, %s given', static::class, get_class($rankingA)));
        }
        if (!$rankingB instanceof static) {
            throw new CompetitionException(sprintf('Ranking ordering require %s as ranking, %s given', static::class, get_class($rankingB)));
        }
    }

}
