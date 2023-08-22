<?php

namespace Keiwen\Utils\Competition;

class RankingDuel extends AbstractRanking
{

    protected $scoreFor = 0;
    protected $scoreAgainst = 0;

    const RESULT_WON = 'W';
    const RESULT_DRAWN = 'D';
    const RESULT_LOSS = 'L';

    protected static $pointByResult = array(
        self::RESULT_WON => 3,
        self::RESULT_DRAWN => 1,
        self::RESULT_LOSS => 0,
    );

    public function getWon(): int
    {
        return $this->getPlayedByResult(self::RESULT_WON);
    }

    public function getDrawn(): int
    {
        return $this->getPlayedByResult(self::RESULT_DRAWN);
    }

    public function getLoss(): int
    {
        return $this->getPlayedByResult(self::RESULT_LOSS);
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
        return static::getPointsForResult(self::RESULT_WON);
    }

    public static function getPointsForDrawn(): int
    {
        return static::getPointsForResult(self::RESULT_DRAWN);
    }

    public static function getPointsForLoss(): int
    {
        return static::getPointsForResult(self::RESULT_LOSS);
    }


    public function saveGame(AbstractGame $game): bool
    {
        if (!$game instanceof GameDuel) {
            throw new CompetitionException(sprintf('Ranking duel require %s as game, %s given', GameDuel::class, get_class($game)));
        }
        $isHome = $isAway = false;
        if ($game->getIdHome() == $this->getIdPlayer()) $isHome = true;
        if ($game->getIdAway() == $this->getIdPlayer()) $isAway = true;
        if (!$isHome && !$isAway) return false;

        if ($isHome) {
            if ($game->hasHomeWon()) $this->gameByResult[self::RESULT_WON]++;
            if ($game->hasAwayWon()) $this->gameByResult[self::RESULT_LOSS]++;
            if ($game->isDraw()) $this->gameByResult[self::RESULT_DRAWN]++;
            $this->scoreFor += $game->getScoreHome();
            $this->scoreAgainst += $game->getScoreAway();
        } else {
            if ($game->hasHomeWon()) $this->gameByResult[self::RESULT_LOSS]++;
            if ($game->hasAwayWon()) $this->gameByResult[self::RESULT_WON]++;
            if ($game->isDraw()) $this->gameByResult[self::RESULT_DRAWN]++;
            $this->scoreFor += $game->getScoreAway();
            $this->scoreAgainst += $game->getScoreHome();
        }

        return true;
    }

    /**
     * @return int
     */
    public static function orderRankings(AbstractRanking $rankingA, AbstractRanking $rankingB)
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
