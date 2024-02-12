<?php

namespace Keiwen\Utils\Competition;

class RankingDuel extends AbstractRanking
{

    const PERF_SCORE_FOR = 'scoreFor';
    const PERF_SCORE_AGAINST = 'scoreAgainst';
    const PERF_SCORE_DIFF = 'scoreDiff';

    protected static $performanceTypesToRank = array(self::PERF_SCORE_DIFF, self::PERF_SCORE_FOR, self::PERF_SCORE_AGAINST);

    protected static $pointByResult = array(
        GameDuel::RESULT_WON => 3,
        GameDuel::RESULT_DRAWN => 1,
        GameDuel::RESULT_LOSS => 0,
    );

    public function getWon(): int
    {
        return $this->getPlayedByResult(GameDuel::RESULT_WON);
    }

    public function getDrawn(): int
    {
        return $this->getPlayedByResult(GameDuel::RESULT_DRAWN);
    }

    public function getLoss(): int
    {
        return $this->getPlayedByResult(GameDuel::RESULT_LOSS);
    }

    public function getScoreFor(): int
    {
        return $this->getPerformanceTotal(self::PERF_SCORE_FOR);
    }

    public function getScoreAgainst(): int
    {
        return $this->getPerformanceTotal(self::PERF_SCORE_AGAINST);
    }

    public function getScoreDiff(): int
    {
        return $this->getPerformanceTotal(self::PERF_SCORE_DIFF);
    }

    public static function getPointsForWon(): int
    {
        return static::getPointsForResult(GameDuel::RESULT_WON);
    }

    public static function getPointsForDrawn(): int
    {
        return static::getPointsForResult(GameDuel::RESULT_DRAWN);
    }

    public static function getPointsForLoss(): int
    {
        return static::getPointsForResult(GameDuel::RESULT_LOSS);
    }


    public function saveGame(AbstractGame $game): bool
    {
        if (!$game instanceof GameDuel) {
            throw new CompetitionException(sprintf('Ranking duel require %s as game, %s given', GameDuel::class, get_class($game)));
        }
        $isHome = $isAway = false;
        if ($game->getKeyHome() == $this->getPlayerKey()) $isHome = true;
        if ($game->getkeyAway() == $this->getPlayerKey()) $isAway = true;
        if (!$isHome && !$isAway) return false;

        $this->saveGamePerformances($game);
        if (!isset($this->performances[self::PERF_SCORE_FOR])) $this->performances[self::PERF_SCORE_FOR] = 0;
        if (!isset($this->performances[self::PERF_SCORE_AGAINST])) $this->performances[self::PERF_SCORE_AGAINST] = 0;
        if (!isset($this->performances[self::PERF_SCORE_DIFF])) $this->performances[self::PERF_SCORE_DIFF] = 0;
        $this->saveGameExpenses($game);
        $this->saveGameBonusAndMalus($game);

        if ($isHome) {
            if ($game->hasHomeWon()) $this->gameByResult[GameDuel::RESULT_WON]++;
            if ($game->hasAwayWon()) $this->gameByResult[GameDuel::RESULT_LOSS]++;
            if ($game->isDraw()) $this->gameByResult[GameDuel::RESULT_DRAWN]++;
            $this->performances[self::PERF_SCORE_FOR] += $game->getScoreHome();
            $this->performances[self::PERF_SCORE_AGAINST] += $game->getScoreAway();
            $this->performances[self::PERF_SCORE_DIFF] += ($game->getScoreHome() - $game->getScoreAway());
        } else {
            if ($game->hasHomeWon()) $this->gameByResult[GameDuel::RESULT_LOSS]++;
            if ($game->hasAwayWon()) $this->gameByResult[GameDuel::RESULT_WON]++;
            if ($game->isDraw()) $this->gameByResult[GameDuel::RESULT_DRAWN]++;
            $this->performances[self::PERF_SCORE_FOR] += $game->getScoreAway();
            $this->performances[self::PERF_SCORE_AGAINST] += $game->getScoreHome();
            $this->performances[self::PERF_SCORE_DIFF] += ($game->getScoreAway() - $game->getScoreHome());
        }

        return true;
    }

    /**
     * @return int
     */
    public static function orderRankings(AbstractRanking $rankingA, AbstractRanking $rankingB): int
    {
        static::checkStaticRankingClass($rankingA, $rankingB);
        // first compare points: more points is first
        if ($rankingA->getPoints() > $rankingB->getPoints()) return 1;
        if ($rankingA->getPoints() < $rankingB->getPoints()) return -1;
        // won games: more won is first
        if ($rankingA->getWon() > $rankingB->getWon()) return 1;
        if ($rankingA->getWon() < $rankingB->getWon()) return -1;

        // then compare performances if declared
        $perfRanking = static::orderRankingsByPerformances($rankingA, $rankingB);
        if ($perfRanking !== 0) return $perfRanking;

        // played games: less played is first
        if ($rankingA->getPlayed() < $rankingB->getPlayed()) return 1;
        if ($rankingA->getPlayed() > $rankingB->getPlayed()) return -1;
        // last case, first registered player is first
        if ($rankingA->getPlayerSeed() < $rankingB->getPlayerSeed()) return 1;
        return -1;
    }

}
