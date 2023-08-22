<?php

namespace Keiwen\Utils\Competition;

abstract class AbstractRanking
{

    protected $idPlayer;

    protected $gameByResult = array();

    protected static $pointByResult = array();

    public function __construct(int $idPlayer)
    {
        $this->idPlayer = $idPlayer;
        // initialize game by result
        $this->gameByResult = array_fill_keys(static::getPossibleResults(), 0);
    }

    /**
     * @return string[]
     */
    public static function getPossibleResults(): array
    {
        return array_keys(static::$pointByResult);
    }


    public static function setPointsAttributionForResult(string $result, int $points)
    {
        if (!in_array($result, static::getPossibleResults())) return false;
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

    public static function getPointsForResult(string $result): int
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
        foreach (static::getPossibleResults() as $result) {
            $played += $this->getPlayedByResult($result);
        }
        return $played;
    }

    public function getPlayedByResult(string $result): int
    {
        if (!in_array($result, static::getPossibleResults())) return 0;
        return $this->gameByResult[$result] ?? 0;
    }


    public function getPoints()
    {
        $points = 0;
        foreach (static::getPossibleResults() as $result) {
            $points += $this->getPlayedByResult($result) * static::getPointsForResult($result);
        }
        return $points;
    }


    abstract public function saveGame(AbstractGame $game): bool;


    /**
     * @return int
     */
    public static function orderRankings(self $rankingA, self $rankingB)
    {
        // first compare points: more points is first
        if ($rankingA->getPoints() > $rankingB->getPoints()) return 1;
        if ($rankingA->getPoints() < $rankingB->getPoints()) return -1;
        // played games: less played is first
        if ($rankingA->getPlayed() < $rankingB->getPlayed()) return 1;
        if ($rankingA->getPlayed() > $rankingB->getPlayed()) return -1;
        // last case, first registered player is first
        if ($rankingA->getIdPlayer() < $rankingB->getIdPlayer()) return 1;
        return -1;
    }

}
