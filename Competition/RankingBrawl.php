<?php

namespace Keiwen\Utils\Competition;

class RankingBrawl extends AbstractRanking
{

    const PERF_TOTAL_BRAWL_COUNT_WON = 'totalBrawlCountWon';
    const PERF_TOTAL_BRAWL_COUNT_LOSS = 'totalBrawlCountLoss';


    public static function generateDefaultRankingsHolder(): RankingsHolder
    {
        $holder = new RankingsHolder(static::class);
        $holder->setPointsAttributionForResult(GameBrawl::RESULT_WON, 1);
        $holder->setPointsAttributionForResult(GameBrawl::RESULT_LOSS, 0);
        $holder->addPerformanceTypeToRank(self::PERF_TOTAL_BRAWL_COUNT_WON);
        $holder->addPerformanceTypeToRank(self::PERF_TOTAL_BRAWL_COUNT_LOSS);
        return $holder;
    }


    public function getWon(): int
    {
        return $this->getPlayedByResult(GameBrawl::RESULT_WON);
    }

    public function getLoss(): int
    {
        return $this->getPlayedByResult(GameBrawl::RESULT_LOSS);
    }

    public function getPointsForWon(): int
    {
        return $this->rankingsHolder->getPointsForResult(GameBrawl::RESULT_WON);
    }

    public function getPointsForLoss(): int
    {
        return $this->rankingsHolder->getPointsForResult(GameBrawl::RESULT_LOSS);
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

        if ($game->hasPlayerWon($this->getEntityKey())) {
            $this->gameByResult[GameBrawl::RESULT_WON]++;
            $this->performances[self::PERF_TOTAL_BRAWL_COUNT_WON] += $game->getPlayerCount();
        } else {
            $this->gameByResult[GameBrawl::RESULT_LOSS]++;
            $this->performances[self::PERF_TOTAL_BRAWL_COUNT_LOSS] += $game->getPlayerCount();
        }
        return true;
    }

    /**
     * @param RankingBrawl $rankingToCompare
     * @return int
     */
    public function compareToRanking(AbstractRanking $rankingToCompare): int
    {
        // first compare points: more points is first
        if ($this->getPoints() > $rankingToCompare->getPoints()) return 1;
        if ($this->getPoints() < $rankingToCompare->getPoints()) return -1;
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
     * @param RankingBrawl[] $rankings
     */
    public function combineRankings(array $rankings)
    {
        parent::combineRankings($rankings);
    }


}
