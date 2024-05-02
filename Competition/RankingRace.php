<?php

namespace Keiwen\Utils\Competition;

class RankingRace extends AbstractRanking
{

    public static function generateDefaultRankingsHolder(): RankingsHolder
    {
        $holder = new RankingsHolder(static::class);
        $holder->setPointsAttributionForResult(1, 10);
        $holder->setPointsAttributionForResult(2, 7);
        $holder->setPointsAttributionForResult(3, 5);
        $holder->setPointsAttributionForResult(4, 3);
        $holder->setPointsAttributionForResult(5, 2);
        $holder->setPointsAttributionForResult(6, 1);
        return $holder;
    }


    public function getWon(): int
    {
        return $this->getPlayedByResult(1);
    }

    public function getPlayedInFirstPositions(int $upTo): int
    {
        $played = 0;
        for ($position = 1; $position <= $upTo; $position++) {
            $played += $this->getPlayedByResult($position);
        }
        return $played;
    }


    public function saveGame(AbstractGame $game): bool
    {
        if (!$game instanceof GameRace) {
            throw new CompetitionException(sprintf('Ranking race require %s as game, %s given', GameRace::class, get_class($game)));
        }

        $this->saveGamePerformances($game);
        $this->saveGameExpenses($game);
        $this->saveGameBonusAndMalus($game);

        $position = $game->getPlayerPosition($this->getEntityKey());
        if (empty($position)) return false;

        if (!isset($this->gameByResult[$position])) $this->gameByResult[$position] = 0;
        $this->gameByResult[$position]++;
        return true;
    }

    /**
     * @param RankingRace $rankingToCompare
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
        // 2nd games: more 2 is first
        if ($this->getPlayedByResult(2) > $rankingToCompare->getPlayedByResult(2)) return 1;
        if ($this->getPlayedByResult(2) < $rankingToCompare->getPlayedByResult(2)) return -1;
        // 3rd games: more 3 is first
        if ($this->getPlayedByResult(3) > $rankingToCompare->getPlayedByResult(3)) return 1;
        if ($this->getPlayedByResult(3) < $rankingToCompare->getPlayedByResult(3)) return -1;

        // then compare performances if declared
        $perfRanking = $this->rankingsHolder->orderRankingsByPerformances($this, $rankingToCompare);
        if ($perfRanking !== 0) return $perfRanking;

        // played games: less played is first
        if ($this->getPlayed() < $rankingToCompare->getPlayed()) return 1;
        if ($this->getPlayed() > $rankingToCompare->getPlayed()) return -1;
        // last case, first registered entity is first
        if ($this->getEntitySeed() < $rankingToCompare->getEntitySeed()) return 1;
        return -1;
    }

    /**
     * @param RankingRace[] $rankings
     */
    public function combineRankings(array $rankings)
    {
        parent::combineRankings($rankings);
    }

}
