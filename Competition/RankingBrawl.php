<?php

namespace Keiwen\Utils\Competition;

class RankingBrawl extends AbstractRanking
{

    const PERF_TOTAL_BRAWL_COUNT_WON = 'totalBrawlCountWon';
    const PERF_TOTAL_BRAWL_COUNT_LOSS = 'totalBrawlCountLoss';

    protected static $performanceTypesToRank = array(self::PERF_TOTAL_BRAWL_COUNT_WON, self::PERF_TOTAL_BRAWL_COUNT_LOSS);

    protected static $pointByResult = array(
        GameBrawl::RESULT_WON => 1,
        GameBrawl::RESULT_LOSS => 0,
    );

    public function getWon(): int
    {
        return $this->getPlayedByResult(GameBrawl::RESULT_WON);
    }

    public function getLoss(): int
    {
        return $this->getPlayedByResult(GameBrawl::RESULT_LOSS);
    }

    public static function getPointsForWon(): int
    {
        return static::getPointsForResult(GameBrawl::RESULT_WON);
    }

    public static function getPointsForLoss(): int
    {
        return static::getPointsForResult(GameBrawl::RESULT_LOSS);
    }

    public function getBrawlCountWon(): int
    {
        return $this->getPerformanceTotal(self::PERF_TOTAL_BRAWL_COUNT_WON);
    }

    public function getBrawlCountLoss(): int
    {
        return $this->getPerformanceTotal(self::PERF_TOTAL_BRAWL_COUNT_LOSS);
    }

    public function getBrawlCount(): int
    {
        return $this->getBrawlCountWon() + $this->getBrawlCountLoss();
    }

    public function getBrawlAverageCountWon(): float
    {
        if (!$this->getWon()) return 0;
        return $this->getBrawlCountWon() / $this->getWon();
    }

    public function getBrawlAverageCountLoss(): float
    {
        if (!$this->getLoss()) return 0;
        return $this->getBrawlCountLoss() / $this->getLoss();
    }

    public function getBrawlAverageCount(): float
    {
        if (!$this->getPlayed()) return 0;
        return $this->getBrawlCount() / $this->getPlayed();
    }


    public function saveGame(AbstractGame $game): bool
    {
        if (!$game instanceof GameBrawl) {
            throw new CompetitionException(sprintf('Ranking brawl require %s as game, %s given', GameBrawl::class, get_class($game)));
        }

        $this->saveGamePerformances($game);
        if (!isset($this->performances[self::PERF_TOTAL_BRAWL_COUNT_WON])) $this->performances[self::PERF_TOTAL_BRAWL_COUNT_WON] = 0;
        if (!isset($this->performances[self::PERF_TOTAL_BRAWL_COUNT_LOSS])) $this->performances[self::PERF_TOTAL_BRAWL_COUNT_LOSS] = 0;
        $this->saveGameExpenses($game);
        $this->saveGameBonusAndMalus($game);

        if ($game->hasPlayerWon($this->getPlayerKey())) {
            $this->gameByResult[GameBrawl::RESULT_WON]++;
            $this->performances[self::PERF_TOTAL_BRAWL_COUNT_WON] += $game->getPlayerCount();
        } else {
            $this->gameByResult[GameBrawl::RESULT_LOSS]++;
            $this->performances[self::PERF_TOTAL_BRAWL_COUNT_LOSS] += $game->getPlayerCount();
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
