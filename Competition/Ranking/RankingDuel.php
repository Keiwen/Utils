<?php

namespace Keiwen\Utils\Competition\Ranking;

use Keiwen\Utils\Competition\Exception\CompetitionException;
use Keiwen\Utils\Competition\Game\AbstractGame;
use Keiwen\Utils\Competition\Game\GameDuel;

class RankingDuel extends AbstractRanking
{

    const PERF_SCORE_FOR = 'scoreFor';
    const PERF_SCORE_AGAINST = 'scoreAgainst';
    const PERF_SCORE_DIFF = 'scoreDiff';

    const POINT_METHOD_BASE = 'base';
    const POINT_METHOD_SCOREFOR = 'scoreFor';
    const POINT_METHOD_SCOREDIFF = 'scoreDiff';
    const POINT_METHOD_CUMULATIVE = 'cumulative';
    const POINT_METHOD_ROUNDREACHADD = 'roundReachAdd';
    const POINT_METHOD_SOLKOFF = 'solkoff';
    const POINT_METHOD_BUCHHOLZ = 'buchholz';
    const POINT_METHOD_HARKNESS1 = 'harkness1';
    const POINT_METHOD_HARKNESS2 = 'harkness2';
    const POINT_METHOD_HARKNESS3 = 'harkness3';
    const POINT_METHOD_KOYA = 'koya';
    const POINT_METHOD_KOYA40 = 'koya40';
    const POINT_METHOD_KOYA30 = 'koya30';
    const POINT_METHOD_KOYA25 = 'koya25';
    const POINT_METHOD_KOYA20 = 'koya20';
    const POINT_METHOD_NEUSTADTL = 'neustadtl';
    const POINT_METHOD_SONNEBORNBERGER = 'sonnebornBerger';

    protected $wonByForfeit = 0;
    protected $lossByForfeit = 0;
    protected $wonBye = 0;
    protected $cumulativeAdjustedPoints = array();
    protected $opponentKeys = array();
    protected $gameResults = array();

    protected $lastPointMethodCalcul = 0;
    protected $lastPointMethodTieBreakerCalcul = 0;


    public static function generateDefaultRankingsHolder(): RankingsHolder
    {
        $holder = new RankingsHolder(static::class);
        $holder->setPointsAttributionForResult(GameDuel::RESULT_WON, 3);
        $holder->setPointsAttributionForResult(GameDuel::RESULT_DRAWN, 1);
        $holder->setPointsAttributionForResult(GameDuel::RESULT_LOSS, 0);
        foreach (static::getDefaultPerformanceTypesToRank() as $performanceType) {
            $holder->addPerformanceTypeToRank($performanceType);
        }
        $holder->setDuelPointMethod(self::POINT_METHOD_BASE);
        $holder->setDuelTieBreakerMethod(self::POINT_METHOD_BASE);
        return $holder;
    }

    public static function getDefaultPerformanceTypesToRank(): array
    {
        return array(self::PERF_SCORE_DIFF, self::PERF_SCORE_FOR, self::PERF_SCORE_AGAINST);
    }

    /**
     * @return RankingDuel[] ordered by rank
     */
    public function getOpponentRankings(): array
    {
        $competitionRankings = $this->rankingsHolder->getAllRankings();
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
     * @param int|string $opponentKey
     * @return RankingDuel|null null if not found
     */
    public function getOpponentRanking($opponentKey): ?RankingDuel
    {
        if (!$this->hasOpponent($opponentKey)) return null;
        /** @var RankingDuel $opponentRanking */
        $opponentRanking = $this->rankingsHolder->getRanking($opponentKey);
        return $opponentRanking;
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
            + $this->getWonBye() * ($this->getPointsForDrawn() - $this->getPointsForWon())
            + $this->getWonByForfeit() * ($this->getPointsForDrawn() - $this->getPointsForWon())
            + $this->getLossByForfeit() * ($this->getPointsForDrawn() - $this->getPointsForLoss())
        ;
        return $adjustedPoints;
    }

    /**
     * @param int $extremeExclusion exclude the first X and last X element
     * @return int
     */
    public function getSumOfOpponentsPoints(int $extremeExclusion = 0): int
    {
        // NOTE: this method could take some time to compute
        // we cannot store some kind of final result of this easily
        // because it depends on other object

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
     * Returns base points + max round reached
     * Useful in tournaments where players could be eliminated
     * @return int
     */
    public function getPointsRoundReachAdd(): int
    {
        return $this->getPoints() + $this->getMaxRoundReached();
    }

    /**
     * Returns sum of opponent scores
     * @return int
     */
    public function getPointsSolkoffSystem(): int
    {
        return $this->getSumOfOpponentsPoints();
    }

    /**
     * Returns player adjusted points * sum of opponent scores
     * @return int
     */
    public function getPointsBuchholzSystem(): int
    {
        return $this->getAdjustedPoints() * $this->getSumOfOpponentsPoints();
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
        return $this->getSumOfOpponentsPoints($harknessRank);
    }

    /**
     * Returns sum of points earned vs player who gets a minimum ratio of points
     * @param float $minPointsRatio default is .5, to get points against player that have at least 50 % of possible points
     * @return int
     */
    public function getPointsKoyaSystem(float $minPointsRatio = 0.5): int
    {
        if ($minPointsRatio > 1) $minPointsRatio = 1;
        if ($minPointsRatio < 0) $minPointsRatio = 0;
        $sum = 0;
        foreach ($this->getOpponentRankings() as $ranking) {
            $actualPoints = $ranking->getPoints();
            $maxPoints = $ranking->getPlayed() * $this->getPointsForWon();
            $pointsRatio = $maxPoints ? $actualPoints / $maxPoints : 0;
            // check if this opponent is above min ratio
            if ($pointsRatio >= $minPointsRatio) {
                $results = $this->getResultsAgainst($ranking->getEntityKey());
                foreach ($results as $result) {
                    // sum all result we've got against this opponent
                    switch ($result) {
                        case GameDuel::RESULT_WON: $sum += $this->getPointsForWon(); break;
                        case GameDuel::RESULT_DRAWN: $sum += $this->getPointsForDrawn(); break;
                        case GameDuel::RESULT_LOSS: $sum += $this->getPointsForLoss(); break;
                    }
                }
            }
        }
        return $sum;
    }


    /**
     * Returns sum of opponents points, considering:
     * - all points if player won against this opponent
     * - half points if player drawn against this opponent
     * - no point if player loss against this opponent
     * @return float
     */
    public function getPointsNeustadtlSystem(): float
    {
        $sum = 0;
        foreach ($this->gameResults as $index => $result) {
            // if loss, do nothing
            if ($result == GameDuel::RESULT_LOSS) continue;

            // get opponent points
            $opponent = $this->opponentKeys[$index];
            $opponentRanking = $this->rankingsHolder->getRanking($opponent);
            if (!$opponentRanking) continue;
            $opponentPoints = $opponentRanking->getPoints();
            if ($result == GameDuel::RESULT_DRAWN) {
                // drawn: count half
                $sum += $opponentPoints / 2;
            } elseif ($result == GameDuel::RESULT_WON) {
                // won: count all
                $sum += $opponentPoints;
            }
        }
        return $sum;
    }

    /**
     * Add the square of current player points to the Neustadtl points
     * @return float
     */
    public function getPointsSonnebornBergerSystem(): float
    {
        return $this->getPoints() ** 2 + $this->getPointsNeustadtlSystem();
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


    /**
     * @param bool $tieBreaker set true to return methods used for tie breaker
     * @return string[] available methods
     */
    public static function getPointMethods(bool $tieBreaker = false): array
    {
        if ($tieBreaker) {
            return array(
                self::POINT_METHOD_BASE,
                self::POINT_METHOD_SCOREFOR,
                self::POINT_METHOD_SCOREDIFF,
                self::POINT_METHOD_CUMULATIVE,
                self::POINT_METHOD_ROUNDREACHADD,
                self::POINT_METHOD_SOLKOFF,
                self::POINT_METHOD_HARKNESS1,
                self::POINT_METHOD_HARKNESS2,
                self::POINT_METHOD_HARKNESS3,
                self::POINT_METHOD_NEUSTADTL,
                self::POINT_METHOD_KOYA,
                self::POINT_METHOD_KOYA40,
                self::POINT_METHOD_KOYA30,
                self::POINT_METHOD_KOYA25,
                self::POINT_METHOD_KOYA20,
            );
        }
        return array(
            self::POINT_METHOD_BASE,
            self::POINT_METHOD_SCOREFOR,
            self::POINT_METHOD_SCOREDIFF,
            self::POINT_METHOD_CUMULATIVE,
            self::POINT_METHOD_ROUNDREACHADD,
            self::POINT_METHOD_BUCHHOLZ,
            self::POINT_METHOD_SONNEBORNBERGER,
        );
    }


    public function updatePointMethodCalcul(bool $tieBreaker = false)
    {
        // do not update for conbined rankings
        if ($this->combinedRankings >= 1) {
            return;
        }
        $method = $tieBreaker ? $this->rankingsHolder->getDuelTieBreakerMethod() : $this->rankingsHolder->getDuelPointMethod();
        switch ($method) {
            case self::POINT_METHOD_SCOREFOR:
                $points = $this->getScoreFor(); break;
            case self::POINT_METHOD_SCOREDIFF:
                $points = $this->getScoreDiff(); break;
            case self::POINT_METHOD_CUMULATIVE:
                $points = $this->getPointsCumulative(); break;
            case self::POINT_METHOD_ROUNDREACHADD:
                $points = $this->getPointsRoundReachAdd(); break;
            case self::POINT_METHOD_SOLKOFF:
                $points = $this->getPointsSolkoffSystem(); break;
            case self::POINT_METHOD_BUCHHOLZ:
                $points = $this->getPointsBuchholzSystem(); break;
            case self::POINT_METHOD_HARKNESS1:
                $points = $this->getPointsHarknessSystem(1); break;
            case self::POINT_METHOD_HARKNESS2:
                $points = $this->getPointsHarknessSystem(2); break;
            case self::POINT_METHOD_HARKNESS3:
                $points = $this->getPointsHarknessSystem(3); break;
            case self::POINT_METHOD_NEUSTADTL:
                $points = $this->getPointsNeustadtlSystem(); break;
            case self::POINT_METHOD_SONNEBORNBERGER:
                $points = $this->getPointsSonnebornBergerSystem(); break;
            case self::POINT_METHOD_KOYA:
                $points = $this->getPointsKoyaSystem(); break;
            case self::POINT_METHOD_KOYA40:
                $points = $this->getPointsKoyaSystem(0.4); break;
            case self::POINT_METHOD_KOYA30:
                $points = $this->getPointsKoyaSystem(0.3); break;
            case self::POINT_METHOD_KOYA25:
                $points = $this->getPointsKoyaSystem(0.25); break;
            case self::POINT_METHOD_KOYA20:
                $points = $this->getPointsKoyaSystem(0.2); break;
            case self::POINT_METHOD_BASE;
            default:
                $points = $this->getPoints();
        }

        if ($tieBreaker) {
            $this->lastPointMethodTieBreakerCalcul = $points;
        } else {
            $this->lastPointMethodCalcul = $points;
        }
    }


    /**
     * @return int|float
     */
    public function getPointsInMethod(bool $tieBreaker = false)
    {
        return $tieBreaker ? $this->lastPointMethodTieBreakerCalcul : $this->lastPointMethodCalcul;
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

    public function getMaxRoundReached(): int
    {
        return count($this->gameResults);
    }

    public function getPointsForWon(bool $inMethod = false): int
    {
        if (!$inMethod) return $this->rankingsHolder->getPointsForResult(GameDuel::RESULT_WON);
        switch ($this->rankingsHolder->getDuelPointMethod()) {
            case self::POINT_METHOD_SCOREFOR:
            case self::POINT_METHOD_SCOREDIFF:
            case self::POINT_METHOD_CUMULATIVE:
            case self::POINT_METHOD_ROUNDREACHADD:
            case self::POINT_METHOD_BUCHHOLZ:
            case self::POINT_METHOD_SONNEBORNBERGER:
                // is variable
                return -1;
            case self::POINT_METHOD_BASE:
            default:
                return $this->rankingsHolder->getPointsForResult(GameDuel::RESULT_WON);
        }
    }

    public function getPointsForDrawn(bool $inMethod = false): int
    {
        if (!$inMethod) return $this->rankingsHolder->getPointsForResult(GameDuel::RESULT_DRAWN);
        switch ($this->rankingsHolder->getDuelPointMethod()) {
            case self::POINT_METHOD_SCOREFOR:
            case self::POINT_METHOD_SCOREDIFF:
            case self::POINT_METHOD_CUMULATIVE:
            case self::POINT_METHOD_ROUNDREACHADD:
            case self::POINT_METHOD_BUCHHOLZ:
            case self::POINT_METHOD_SONNEBORNBERGER:
                // is variable
                return -1;
            case self::POINT_METHOD_BASE:
            default:
                return $this->rankingsHolder->getPointsForResult(GameDuel::RESULT_DRAWN);
        }
    }

    public function getPointsForLoss(bool $inMethod = false): int
    {
        if (!$inMethod) return $this->rankingsHolder->getPointsForResult(GameDuel::RESULT_LOSS);
        switch ($this->rankingsHolder->getDuelPointMethod()) {
            case self::POINT_METHOD_SCOREFOR:
            case self::POINT_METHOD_SCOREDIFF:
            case self::POINT_METHOD_CUMULATIVE:
            case self::POINT_METHOD_BUCHHOLZ:
                // is variable
                return -1;
            case self::POINT_METHOD_SONNEBORNBERGER:
                return $this->rankingsHolder->getPointsForResult(GameDuel::RESULT_LOSS) ** 2;
            case self::POINT_METHOD_ROUNDREACHADD:
                return 0;
            case self::POINT_METHOD_BASE:
            default:
                return $this->rankingsHolder->getPointsForResult(GameDuel::RESULT_LOSS);
        }
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
    protected function storeGameResult(string $result, bool $isForfeit = false, bool $isBye = false)
    {
        // store result
        $this->gameByResult[$result]++;
        $this->gameResults[] = $result;

        // store cumul points
        $cumul = $this->getLastCumulPoints();
        // from last cumul, add points for last game
        // if forfeit or bye, count adjusted point as draw
        if ($isForfeit || $isBye || $result == GameDuel::RESULT_DRAWN) {
            $cumul += $this->getPointsForDrawn();
        } else if ($result == GameDuel::RESULT_WON) {
            $cumul += $this->getPointsForWon();
        } else if ($result == GameDuel::RESULT_LOSS) {
            $cumul += $this->getPointsForLoss();
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
                if ($game->hasForfeit()) $this->wonByForfeit++;
                if ($game->isByeGame()) $this->wonBye++;
                $this->storeGameResult(GameDuel::RESULT_WON, $game->hasForfeit(), $game->isByeGame());
            }
            if ($game->hasAwayWon()) {
                if ($game->hasForfeit()) $this->lossByForfeit++;
                $this->storeGameResult(GameDuel::RESULT_LOSS, $game->hasForfeit());
            }
            if ($game->isDraw()) {
                $this->storeGameResult(GameDuel::RESULT_DRAWN);
            }
            $this->performances[self::PERF_SCORE_FOR] += $game->getScoreHome();
            $this->performances[self::PERF_SCORE_AGAINST] += $game->getScoreAway();
            $this->performances[self::PERF_SCORE_DIFF] += ($game->getScoreHome() - $game->getScoreAway());
            if (!$game->isByeGame()) $this->opponentKeys[] = $game->getKeyAway();
            // if bye, flag as empty string key in opponent list
            else $this->opponentKeys[] = '';
        } else {
            if ($game->hasHomeWon()) {
                if ($game->hasForfeit()) $this->lossByForfeit++;
                $this->storeGameResult(GameDuel::RESULT_LOSS, $game->hasForfeit());
            }
            if ($game->hasAwayWon()) {
                if ($game->hasForfeit()) $this->wonByForfeit++;
                $this->storeGameResult(GameDuel::RESULT_WON, $game->hasForfeit());
            }
            if ($game->isDraw()) {
                $this->storeGameResult(GameDuel::RESULT_DRAWN);
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
     * Returns results for the last games
     * @param int $lastGamesCount number of games to consider (0 by default to return all)
     * @return string[]
     */
    public function getGameResults(int $lastGamesCount = 0): array
    {
        if ($lastGamesCount < 0) $lastGamesCount = 0;
        if ($lastGamesCount > count($this->gameResults)) $lastGamesCount = 0;
        $lastGamesCount = -$lastGamesCount;
        return array_slice($this->gameResults, $lastGamesCount);
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
     * @param int $round
     * @return string|null null if not found
     */
    public function getResultInRound(int $round): ?string
    {
        return $this->gameResults[$round - 1] ?? null;
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
     * @param int|string $playerKey
     * @return string[]
     */
    public function getResultsAgainst($playerKey): array
    {
        $confrontationRounds = array();
        $opponentList = $this->opponentKeys;
        while (($index = array_search($playerKey, $opponentList)) !== false) {
            $confrontationRounds[] = $index + 1;
            unset($opponentList[$index]);
        }
        $confrontationResults = array();
        foreach ($confrontationRounds as $round) {
            $confrontationResults[] = $this->getResultInRound($round);
        }
        return $confrontationResults;
    }

    /**
     * compares only direct duels versus given player
     * @param int|string $playerKey
     * @return int 1 if more won, -1 if more loss, 0 if tie
     */
    public function orderDirectDuelsAgainst($playerKey): int
    {
        $confrontationResults = $this->getResultsAgainst($playerKey);
        $confrontationResults = array_count_values($confrontationResults);
        if (!isset($confrontationResults[GameDuel::RESULT_WON])) $confrontationResults[GameDuel::RESULT_WON] = 0;
        if (!isset($confrontationResults[GameDuel::RESULT_LOSS])) $confrontationResults[GameDuel::RESULT_LOSS] = 0;
        if ($confrontationResults[GameDuel::RESULT_WON] > $confrontationResults[GameDuel::RESULT_LOSS]) return 1;
        if ($confrontationResults[GameDuel::RESULT_WON] < $confrontationResults[GameDuel::RESULT_LOSS]) return -1;
        return 0;
    }

    /**
     * @param RankingDuel $rankingToCompare
     * @return int
     */
    public function compareToRanking(AbstractRanking $rankingToCompare): int
    {
        // first compare points: more points is first
        if ($this->getPointsInMethod() > $rankingToCompare->getPointsInMethod()) return 1;
        if ($this->getPointsInMethod() < $rankingToCompare->getPointsInMethod()) return -1;
        // then compare points with tiebreaker method: more points is first
        if ($this->getPointsInMethod(true) > $rankingToCompare->getPointsInMethod(true)) return 1;
        if ($this->getPointsInMethod(true) < $rankingToCompare->getPointsInMethod(true)) return -1;
        // won games: more won is first
        if ($this->getWon() > $rankingToCompare->getWon()) return 1;
        if ($this->getWon() < $rankingToCompare->getWon()) return -1;
        // won bye games: less won for bye game is first
        if ($this->getWonBye() < $rankingToCompare->getWonBye()) return 1;
        if ($this->getWonBye() > $rankingToCompare->getWonBye()) return -1;
        // round reach: higher round reach is first
        if ($this->getMaxRoundReached() > $rankingToCompare->getMaxRoundReached()) return 1;
        if ($this->getMaxRoundReached() < $rankingToCompare->getMaxRoundReached()) return -1;

        // then compare direct confrontations
        $directRanking = $this->orderDirectDuelsAgainst($rankingToCompare->getEntityKey());
        if ($directRanking !== 0) return $directRanking;

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
     * @param RankingDuel[] $rankings
     */
    public function combineRankings(array $rankings)
    {
        parent::combineRankings($rankings);
        foreach ($rankings as $ranking) {
            $this->wonByForfeit += $ranking->getWonByForfeit();
            $this->lossByForfeit += $ranking->getLossByForfeit();
            $this->wonBye += $ranking->getWonBye();
            $this->opponentKeys = array_merge($this->opponentKeys, $ranking->getOpponentKeys());
            $this->lastPointMethodCalcul += $ranking->getPointsInMethod();
            $this->lastPointMethodTieBreakerCalcul += $ranking->getPointsInMethod(true);
        }
    }


}
