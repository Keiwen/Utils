<?php

namespace Keiwen\Utils\Competition;

class RankingBrawl extends AbstractRanking
{

    protected $totalBrawlCountWon = 0;
    protected $totalBrawlCountLoss = 0;

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
        return $this->totalBrawlCountWon;
    }

    public function getBrawlCountLoss(): int
    {
        return $this->totalBrawlCountLoss;
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
        if ($game->hasPlayerWon($this->getIdPlayer())) {
            $this->gameByResult[GameBrawl::RESULT_WON]++;
            $this->totalBrawlCountWon += $game->getPlayerCount();
        } else {
            $this->gameByResult[GameBrawl::RESULT_LOSS]++;
            $this->totalBrawlCountLoss += $game->getPlayerCount();
        }
        return true;
    }

    /**
     * @return int
     */
    public static function orderRankings(AbstractRanking $rankingA, AbstractRanking $rankingB)
    {
        static::checkStaticRankingClass($rankingA, $rankingB);
        // first compare points: more points is first
        if ($rankingA->getPoints() > $rankingB->getPoints()) return 1;
        if ($rankingA->getPoints() < $rankingB->getPoints()) return -1;
        // won games: more won is first
        if ($rankingA->getWon() > $rankingB->getWon()) return 1;
        if ($rankingA->getWon() < $rankingB->getWon()) return -1;
        // played games: more played is first
        if ($rankingA->getPlayed() > $rankingB->getPlayed()) return 1;
        if ($rankingA->getPlayed() < $rankingB->getPlayed()) return -1;
        // count for won: more opponent for won is first
        if ($rankingA->getBrawlAverageCountWon() > $rankingB->getBrawlAverageCountWon()) return 1;
        if ($rankingA->getBrawlAverageCountWon() < $rankingB->getBrawlAverageCountWon()) return -1;
        // count for loss: more opponent for loss is first
        if ($rankingA->getBrawlAverageCountLoss() > $rankingB->getBrawlAverageCountLoss()) return 1;
        if ($rankingA->getBrawlAverageCountLoss() < $rankingB->getBrawlAverageCountLoss()) return -1;
        // last case, first registered player is first
        if ($rankingA->getIdPlayer() < $rankingB->getIdPlayer()) return 1;
        return -1;
    }

}
