<?php

namespace Keiwen\Utils\Competition;

class CompetitionRanking
{

    protected $idPlayer;
    protected $won = 0;
    protected $drawn = 0;
    protected $loss = 0;
    protected $scoreFor = 0;
    protected $scoreAgainst = 0;

    protected static $pointWon = 3;
    protected static $pointDrawn = 1;
    protected static $pointLoss = 0;

    public function __construct(int $idPlayer)
    {
        $this->idPlayer = $idPlayer;
    }

    /**
     * @param int $pointsWon points earned for each game won
     * @param int $pointsDrawn points earned for each game drawn
     * @param int $pointsLoss points earned for each game loss
     * @return void
     */
    public static function setPointsAttribution(int $pointsWon, int $pointsDrawn, int $pointsLoss)
    {
        static::$pointWon = $pointsWon;
        static::$pointDrawn = $pointsDrawn;
        static::$pointLoss = $pointsLoss;
    }

    public function getIdPlayer()
    {
        return $this->idPlayer;
    }

    public function getPlayed(): int
    {
        return $this->getWon() + $this->getDrawn() + $this->getLoss();
    }

    public function getWon(): int
    {
        return $this->won;
    }

    public function getDrawn(): int
    {
        return $this->drawn;
    }

    public function getLoss(): int
    {
        return $this->loss;
    }

    public function getScoreFor(): int
    {
        return $this->scoreFor;
    }

    public function getScoreAgainst(): int
    {
        return $this->scoreAgainst;
    }

    public function getScoreDiff(): int
    {
        return $this->getScoreFor() - $this->getScoreAgainst();
    }

    public static function getPointsForWon(): int
    {
        return static::$pointWon;
    }

    public static function getPointsForDrawn(): int
    {
        return static::$pointDrawn;
    }

    public static function getPointsForLoss(): int
    {
        return static::$pointLoss;
    }

    public function getPoints()
    {
        return $this->getWon() * static::getPointsForWon()
            + $this->getDrawn() * static::getPointsForDrawn()
            + $this->getLoss() * static::getPointsForLoss();
    }

    public function saveGame(CompetitionGame $game)
    {
        $isHome = $isAway = false;
        if ($game->getIdHome() == $this->getIdPlayer()) $isHome = true;
        if ($game->getIdAway() == $this->getIdPlayer()) $isAway = true;
        if (!$isHome && !$isAway) return false;

        if ($isHome) {
            if ($game->hasHomeWon()) $this->won++;
            if ($game->hasAwayWon()) $this->loss++;
            if ($game->isDraw()) $this->drawn++;
            $this->scoreFor += $game->getScoreHome();
            $this->scoreAgainst += $game->getScoreAway();
        } else {
            if ($game->hasHomeWon()) $this->loss++;
            if ($game->hasAwayWon()) $this->won++;
            if ($game->isDraw()) $this->drawn++;
            $this->scoreFor += $game->getScoreAway();
            $this->scoreAgainst += $game->getScoreHome();
        }

        return true;
    }

    /**
     * @return int
     */
    public static function orderRankings(self $rankingA, self $rankingB)
    {
        // first compare points: more points is first
        if ($rankingA->getPoints() > $rankingB->getPoints()) return 1;
        if ($rankingA->getPoints() < $rankingB->getPoints()) return -1;
        // won games: more won is first
        if ($rankingA->getWon() > $rankingB->getWon()) return 1;
        if ($rankingA->getWon() < $rankingB->getWon()) return -1;
        // score diff: more diff is first
        if ($rankingA->getScoreDiff() > $rankingB->getScoreDiff()) return 1;
        if ($rankingA->getScoreDiff() < $rankingB->getScoreDiff()) return -1;
        // score for: more score is first
        if ($rankingA->getScoreFor() > $rankingB->getScoreFor()) return 1;
        if ($rankingA->getScoreFor() < $rankingB->getScoreFor()) return -1;
        // played games: less played is first
        if ($rankingA->getPlayed() < $rankingB->getPlayed()) return 1;
        if ($rankingA->getPlayed() > $rankingB->getPlayed()) return -1;
        // last case, first registered player is first
        if ($rankingA->getIdPlayer() < $rankingB->getIdPlayer()) return 1;
        return -1;
    }

}
