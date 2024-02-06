<?php

namespace Keiwen\Utils\Competition;

class RankingRace extends AbstractRanking
{

    protected static $pointByResult = array(
        1 => 10,
        2 => 7,
        3 => 5,
        4 => 3,
        5 => 2,
        6 => 1,
    );

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

        $position = $game->getPlayerPosition($this->getPlayerOrd());
        if (empty($position)) return false;

        if (!isset($this->gameByResult[$position])) $this->gameByResult[$position] = 0;
        $this->gameByResult[$position]++;
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
        // 2nd games: more 2 is first
        if ($rankingA->getPlayedByResult(2) > $rankingB->getPlayedByResult(2)) return 1;
        if ($rankingA->getPlayedByResult(2) < $rankingB->getPlayedByResult(2)) return -1;
        // 3rd games: more 3 is first
        if ($rankingA->getPlayedByResult(3) > $rankingB->getPlayedByResult(3)) return 1;
        if ($rankingA->getPlayedByResult(3) < $rankingB->getPlayedByResult(3)) return -1;

        // then compare performances if declared
        $perfRanking = static::orderRankingsByPerformances($rankingA, $rankingB);
        if ($perfRanking !== 0) return $perfRanking;

        // played games: less played is first
        if ($rankingA->getPlayed() < $rankingB->getPlayed()) return 1;
        if ($rankingA->getPlayed() > $rankingB->getPlayed()) return -1;
        // last case, first registered player is first
        if ($rankingA->getPlayerOrd() < $rankingB->getPlayerOrd()) return 1;
        return -1;
    }


    public static function resetPointAttribution()
    {
        static::$pointByResult = array();
    }
}
