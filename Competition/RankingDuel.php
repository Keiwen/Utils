<?php

namespace Keiwen\Utils\Competition;

class RankingDuel extends AbstractRanking
{

    const PERF_SCORE_FOR = 'scoreFor';
    const PERF_SCORE_AGAINST = 'scoreAgainst';
    const PERF_SCORE_DIFF = 'scoreDiff';

    protected $wonByForfeit = 0;
    protected $lossByForfeit = 0;
    protected $wonBye = 0;
    protected $cumulativeAdjustedPoints = array();
    protected $opponentKeys = array();

    /** @var AbstractCompetition $affectedTo */
    protected $affectedTo = null;


    protected static $performanceTypesToRank = array(self::PERF_SCORE_DIFF, self::PERF_SCORE_FOR, self::PERF_SCORE_AGAINST);

    protected static $pointByResult = array(
        GameDuel::RESULT_WON => 3,
        GameDuel::RESULT_DRAWN => 1,
        GameDuel::RESULT_LOSS => 0,
    );


    public function isAffected(): bool
    {
        return !empty($this->affectedTo);
    }

    /**
     * @return AbstractCompetition|null
     */
    public function getAffectation(): ?AbstractCompetition
    {
        return $this->affectedTo;
    }

    /**
     * @param AbstractCompetition $competition
     * @return bool true if affected
     */
    public function affectTo(AbstractCompetition $competition): bool
    {
        if ($this->isAffected()) return false;
        $this->affectedTo = $competition;
        return true;
    }

    /**
     * @return RankingDuel[] ordered by rank
     */
    public function getOpponentRankings(): array
    {
        if (!$this->isAffected()) return array();
        $competitionRankings = $this->getAffectation()->getRankings();
        $opponentRankings = array();
        $opponentKeysToBeFound = $this->opponentKeys;
        foreach ($competitionRankings as $ranking) {
            // use a while loop instead of just 'in_array'
            // so that we can consider opponent faced multiple times
            while(($index = array_search($ranking->getEntityKey(), $opponentKeysToBeFound)) !== false) {
                //this one is an opponent
                $opponentRankings[] = $ranking;
                unset($opponentKeysToBeFound[$index]);
            }
        }
        return $opponentRankings;
    }

    /**
     * Returns points adjusted for forfeit and bye (counted as draw points)
     * @return int
     */
    public function getAdjustedPoints(): int
    {
        $basePoints = $this->getPoints();
        // adjust points for bye and forfeit: counted as draw
        // formula: actual result A and counted result B, for X game:
        // -X*A +X*B = X * (B-A)
        $adjustedPoints = $basePoints
            + $this->getWonBye() * (static::getPointsForDrawn() - static::getPointsForWon())
            + $this->getWonByForfeit() * (static::getPointsForDrawn() - static::getPointsForWon())
            + $this->getLossByForfeit() * (static::getPointsForDrawn() - static::getPointsForLoss())
        ;
        return $adjustedPoints;
    }

    /**
     * @param int $extremeExclusion exclude the first X and last X element
     * @return int
     */
    public function getSumOfOpponentScores(int $extremeExclusion = 0): int
    {
        // NOTE: this method could take some time to compute
        // we cannot store some kind of result easily because it depends on other object
        // try to NOT call this by default, only on demand

        $sum = 0;
        // count how many we need with exclusion
        $totalToSum = $this->getOpponentCount() - $extremeExclusion * 2;
        // if none left, return 0
        if ($totalToSum < 1) return 0;
        $firstExcluded = 0;
        foreach ($this->getOpponentRankings() as $ranking) {
            if ($firstExcluded < $extremeExclusion) {
                // count exclusion for first ranks, and ignore these rankings
                $firstExcluded++;
                continue;
            }
            // if we already have all ranks needed, break the loop
            if ($totalToSum <= 0) break;
            $sum += $ranking->getAdjustedPoints();
            // after we added a ranking, decrement total needed
            $totalToSum--;
        }
        return $sum;
    }

    /**
     * Returns sum of opponent scores
     * @return int
     */
    public function getPointsSolkoffSystem(): int
    {
        return $this->getSumOfOpponentScores();
    }

    /**
     * Returns player adjusted points * sum of opponent scores
     * @return int
     */
    public function getPointsBuchholzSystem(): int
    {
        return $this->getAdjustedPoints() * $this->getSumOfOpponentScores();
    }


    /**
     * Returns sum of opponent scores
     * This ignore the first X and last X opponents, with X = harkness rank given
     * @param int $harknessRank
     * @return int
     */
    public function getPointsHarknessSystem(int $harknessRank): int
    {
        if ($harknessRank < 1) $harknessRank = 1;
        return $this->getSumOfOpponentScores($harknessRank);
    }

    /**
     * @return int
     */
    public function getPointsCumulative(): int
    {
        $sum = 0;
        foreach ($this->cumulativeAdjustedPoints as $cumulPoints) {
            $sum += $cumulPoints;
        }
        return $sum;
    }



    public function getWon(): int
    {
        return $this->getPlayedByResult(GameDuel::RESULT_WON);
    }

    public function getWonByForfeit(): int
    {
        return $this->wonByForfeit;
    }

    public function getWonBye(): int
    {
        return $this->wonBye;
    }

    public function getDrawn(): int
    {
        return $this->getPlayedByResult(GameDuel::RESULT_DRAWN);
    }

    public function getLoss(): int
    {
        return $this->getPlayedByResult(GameDuel::RESULT_LOSS);
    }

    public function getLossByForfeit(): int
    {
        return $this->lossByForfeit;
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

    protected function getLastCumulPoints(): int
    {
        if (empty($this->cumulativeAdjustedPoints)) return 0;
        return $this->cumulativeAdjustedPoints[count($this->cumulativeAdjustedPoints) - 1];
    }

    /**
     * @param string $result
     * @param bool $isForfeit
     * @param bool $isBye
     */
    protected function addCumulPoints(string $result, bool $isForfeit = false, bool $isBye = false)
    {
        $cumul = $this->getLastCumulPoints();
        // from last cumul, add points for last game
        // if forfeit or bye, count adjusted point as draw
        if ($isForfeit || $isBye || $result == GameDuel::RESULT_DRAWN) {
            $cumul += static::getPointsForDrawn();
        } else if ($result == GameDuel::RESULT_WON) {
            $cumul += static::getPointsForWon();
        } else if ($result == GameDuel::RESULT_LOSS) {
            $cumul += static::getPointsForLoss();
        }
        $this->cumulativeAdjustedPoints[] = $cumul;
    }


    public function saveGame(AbstractGame $game): bool
    {
        if (!$game instanceof GameDuel) {
            throw new CompetitionException(sprintf('Ranking duel require %s as game, %s given', GameDuel::class, get_class($game)));
        }
        $isHome = $isAway = false;
        if ($game->getKeyHome() == $this->getEntityKey()) $isHome = true;
        if ($game->getkeyAway() == $this->getEntityKey()) $isAway = true;
        if (!$isHome && !$isAway) return false;

        $this->saveGamePerformances($game);
        if (!isset($this->performances[self::PERF_SCORE_FOR])) $this->performances[self::PERF_SCORE_FOR] = 0;
        if (!isset($this->performances[self::PERF_SCORE_AGAINST])) $this->performances[self::PERF_SCORE_AGAINST] = 0;
        if (!isset($this->performances[self::PERF_SCORE_DIFF])) $this->performances[self::PERF_SCORE_DIFF] = 0;
        $this->saveGameExpenses($game);
        $this->saveGameBonusAndMalus($game);

        if ($isHome) {
            if ($game->hasHomeWon()) {
                $this->gameByResult[GameDuel::RESULT_WON]++;
                if ($game->hasForfeit()) $this->wonByForfeit++;
                if ($game->isByeGame()) $this->wonBye++;
                $this->addCumulPoints(GameDuel::RESULT_WON, $game->hasForfeit(), $game->isByeGame());
            }
            if ($game->hasAwayWon()) {
                $this->gameByResult[GameDuel::RESULT_LOSS]++;
                if ($game->hasForfeit()) $this->lossByForfeit++;
                $this->addCumulPoints(GameDuel::RESULT_LOSS, $game->hasForfeit());
            }
            if ($game->isDraw()) {
                $this->gameByResult[GameDuel::RESULT_DRAWN]++;
                $this->addCumulPoints(GameDuel::RESULT_DRAWN);
            }
            $this->performances[self::PERF_SCORE_FOR] += $game->getScoreHome();
            $this->performances[self::PERF_SCORE_AGAINST] += $game->getScoreAway();
            $this->performances[self::PERF_SCORE_DIFF] += ($game->getScoreHome() - $game->getScoreAway());
            if (!$game->isByeGame()) $this->opponentKeys[] = $game->getKeyAway();
            // if bye, flag as empty string key in opponent list
            else $this->opponentKeys[] = '';
        } else {
            if ($game->hasHomeWon()) {
                $this->gameByResult[GameDuel::RESULT_LOSS]++;
                if ($game->hasForfeit()) $this->lossByForfeit++;
                $this->addCumulPoints(GameDuel::RESULT_LOSS, $game->hasForfeit());
            }
            if ($game->hasAwayWon()) {
                $this->gameByResult[GameDuel::RESULT_WON]++;
                if ($game->hasForfeit()) $this->wonByForfeit++;
                $this->addCumulPoints(GameDuel::RESULT_WON, $game->hasForfeit());
            }
            if ($game->isDraw()) {
                $this->gameByResult[GameDuel::RESULT_DRAWN]++;
                $this->addCumulPoints(GameDuel::RESULT_DRAWN);
            }
            $this->performances[self::PERF_SCORE_FOR] += $game->getScoreAway();
            $this->performances[self::PERF_SCORE_AGAINST] += $game->getScoreHome();
            $this->performances[self::PERF_SCORE_DIFF] += ($game->getScoreAway() - $game->getScoreHome());
            $this->opponentKeys[] = $game->getKeyHome();
        }

        return true;
    }

    public function getOpponentKeys(): array
    {
        return $this->opponentKeys;
    }

    /**
     * @param bool $excludeBye true by default, to count only real opponent
     * @return int
     */
    public function getOpponentCount(bool $excludeBye = true): int
    {
        $count = count($this->opponentKeys);
        if (!$excludeBye) return $count;
        $countValues = array_count_values($this->opponentKeys);
        $countBye = $countValues[''] ?? 0;
        return $count - $countBye;
    }

    /**
     * @param int $round
     * @return int|string|null null if not found
     */
    public function getOpponentKeyFacedInRound(int $round)
    {
        return $this->opponentKeys[$round - 1] ?? null;
    }

    /**
     * @param int|string $playerKey
     * @return bool
     */
    public function hasOpponent($playerKey): bool
    {
        return in_array($playerKey, $this->opponentKeys);
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
        // won bye games: less won for bye game is first
        if ($rankingA->getWonBye() < $rankingB->getWonBye()) return 1;
        if ($rankingA->getWonBye() > $rankingB->getWonBye()) return -1;

        // then compare performances if declared
        $perfRanking = static::orderRankingsByPerformances($rankingA, $rankingB);
        if ($perfRanking !== 0) return $perfRanking;

        // played games: less played is first
        if ($rankingA->getPlayed() < $rankingB->getPlayed()) return 1;
        if ($rankingA->getPlayed() > $rankingB->getPlayed()) return -1;
        // last case, first registered entity is first
        if ($rankingA->getEntitySeed() < $rankingB->getEntitySeed()) return 1;
        return -1;
    }

    /**
     * @param RankingDuel[] $rankings
     */
    public function combinedRankings(array $rankings)
    {
        parent::combinedRankings($rankings);
        foreach ($rankings as $ranking) {
            $this->wonByForfeit += $ranking->getWonByForfeit();
            $this->lossByForfeit += $ranking->getLossByForfeit();
            $this->wonBye += $ranking->getWonBye();
            $this->opponentKeys = array_merge($this->opponentKeys, $ranking->getOpponentKeys());
        }
    }


}
