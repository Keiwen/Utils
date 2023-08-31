<?php

namespace Keiwen\Utils\Competition;

use Keiwen\Utils\Math\Divisibility;

class CompetitionChampionshipDuel extends AbstractCompetition
{
    protected $serieCount;
    protected $calendar;
    protected $calendarRoundCount;

    /** @var GameDuel[] $gameRepository */
    protected $gameRepository = array();

    protected $nextRoundNumber = 1;


    public function __construct(array $players, int $serieCount = 1, bool $shuffleCalendar = false)
    {
        if (count($players) < 3) throw new CompetitionException('Cannot create championship with less than 3 players');
        parent::__construct($players);

        if ($serieCount < 1) $serieCount = 1;
        $this->serieCount = $serieCount;
        $this->generateCalendar($shuffleCalendar);
    }

    protected function initializeRanking()
    {
        for ($playerOrd = 1; $playerOrd <= $this->playerCount; $playerOrd++) {
            $this->rankings[$playerOrd] = new RankingDuel($playerOrd);
        }
    }

    public function getRoundCount()
    {
        return $this->calendarRoundCount;
    }

    public function getSerieCount()
    {
        return $this->serieCount;
    }

    public function getGameCountByPlayer()
    {
        return ($this->playerCount - 1) * $this->serieCount;
    }

    protected function generateCalendar(bool $shuffle = false): void
    {
        if (Divisibility::isNumberEven($this->playerCount)) {
            $this->generateBaseCalendarEven();
        } else {
            $this->generateBaseCalendarOdd();
        }

        $this->generateFullCalendar();

        if ($shuffle) {
            $calendarCopy = $this->calendar;
            shuffle($calendarCopy);
            $this->calendar = array();
            $round = 1;
            foreach ($calendarCopy as $randomRound => $games) {
                $this->calendar[$round] = $calendarCopy[$randomRound];
                $round++;
            }
        }

        $this->consolidateCalendar();
    }

    protected function generateBaseCalendarEven(): void
    {
        $this->calendarRoundCount = $this->playerCount - 1;
        // for each round, first player will encounter all other in ascending order
        for ($round = 1; $round <= $this->calendarRoundCount; $round++) {
            $this->addGame(1, $round + 1, $round);
        }
        // init round when match next player
        $roundWhenMatchNextPlayer = 1;
        // starting next player, until we reach the penultimate (< instead of <= in loop)
        for ($ordHome = 2; $ordHome < $this->playerCount; $ordHome++) {
            // first match is on round following the round when this player matched previous player
            $round = $this->roundGapInCalendar($roundWhenMatchNextPlayer, 1);
            // first match is with the last one
            $this->addGame($ordHome, $this->playerCount, $round);

            // then match in ascending order with all others, starting with next player
            // stop before the last one, as already matched just before (< instead of <= in loop condition)
            // also store the round when we will match next player (so next of this one) to handle next player
            $roundWhenMatchNextPlayer = $this->roundGapInCalendar($round, 1);
            for ($ordAway = $ordHome + 1; $ordAway < $this->playerCount; $ordAway++) {
                $round = $this->roundGapInCalendar($round, 1);
                $this->addGame($ordHome, $ordAway, $round);
            }
        }
    }

    protected function generateBaseCalendarOdd(): void
    {
        $this->calendarRoundCount = $this->playerCount;
        // for each round, one player is out. We decided to go descendant order
        // the last player will not play on first round, the first will not play on last round

        $round = 1;
        // for each player
        for ($ordHome = 1; $ordHome <= $this->playerCount; $ordHome++) {
            // initialize ordAway
            $ordAway = $ordHome;
            // one game per other player
            for ($i = 1; $i <= ($this->playerCount - 1); $i++) {
                // get ord - 2 for each game.
                $ordAway = $this->ordGapInPlayers($ordAway, -2);
                // If opponent ord is lower, means that this match should be already done
                // in that case, advance to next step (next round next opponent)
                if ($ordHome < $ordAway) {
                    $this->addGame($ordHome, $ordAway, $round);
                }
                $round = $this->roundGapInCalendar($round, 1);
            }
        }
    }

    protected function generateFullCalendar()
    {
        if ($this->serieCount == 1) return;
        // more than 1 serie, repeat base calendar for each other series
        $round = $this->calendarRoundCount + 1;
        // copy current calendar as a base
        $baseCalendar = $this->calendar;
        // for each serie
        for ($serie = 2; $serie <= $this->serieCount; $serie++) {
            // for each round of base calendar
            foreach ($baseCalendar as $baseRound => $gamesOfRound) {
                // for each games
                foreach ($gamesOfRound as $game) {
                    /** @var GameDuel $game */
                    // add a copy for a new round but switch home/away for each round
                    $reverse = (Divisibility::isNumberEven($serie) && Divisibility::isNumberOdd($baseRound))
                        || (Divisibility::isNumberOdd($serie) && Divisibility::isNumberEven($baseRound));
                    // unless if total series are odd, reverse even series only as it will not be fair anyway
                    // this way first seeds will be prioritized
                    if (Divisibility::isNumberOdd($this->serieCount)) {
                        $reverse = Divisibility::isNumberEven($serie);
                    }
                    if ($reverse) {
                        $this->addGame($game->getIdAway(), $game->getIdHome(), $round);
                    } else {
                        $this->addGame($game->getIdHome(), $game->getIdAway(), $round);
                    }
                }
                $round++;
            }
        }

        // after this, also switch home/away for first serie only if total series are even
        // here calendarRoundCount is still equal to first serie rounds
        if (Divisibility::isNumberEven($this->serieCount)) {
            for ($round = 1; $round <= $this->calendarRoundCount; $round++) {
                if (Divisibility::isNumberEven($round)) {
                    foreach ($this->calendar[$round] as $firstSerieGame) {
                        /** @var GameDuel $firstSerieGame */
                        $firstSerieGame->reverseHomeAway();
                    }
                }
            }
        }

        $this->calendarRoundCount = $this->calendarRoundCount * $this->serieCount;
    }

    protected function consolidateCalendar()
    {
        $gameNumber = 1;
        foreach ($this->calendar as $round => $gamesOfTheRound) {
            foreach ($gamesOfTheRound as $index => $game) {
                // for each game, give a number to order it
                /** @var GameDuel $game */
                $game->affectTo($this, $gameNumber);
                $this->gameRepository[$gameNumber] = array(
                    'round' => $round,
                    'index' => $index,
                );
                $gameNumber++;
            }
        }
    }

    /**
     * @return array round => [games]
     */
    public function getCalendar(): array
    {
        return $this->calendar;
    }

    /**
     * get games for given round
     * @param int $round
     * @return GameDuel[]] games of the round
     */
    public function getGamesByRound(int $round): array
    {
        return $this->calendar[$round] ?? array();
    }

    /**
     * get game with a given number
     * @param int $gameNumber
     * @return GameDuel|null game if found
     */
    public function getGameByNumber(int $gameNumber): ?AbstractGame
    {
        if (!isset($this->gameRepository[$gameNumber])) return null;
        $round = $this->gameRepository[$gameNumber]['round'] ?? 0;
        $index = $this->gameRepository[$gameNumber]['index'] ?? 0;
        if (empty($round)) return null;
        if (!isset($this->calendar[$round])) return null;
        return $this->calendar[$round][$index] ?? null;
    }

    /**
     * @param int $gameNumber
     * @return int|null round number if found
     */
    public function getGameRound(int $gameNumber): ?int
    {
        if (!isset($this->gameRepository[$gameNumber])) return null;
        return $this->gameRepository[$gameNumber]['round'] ?? null;
    }


    /**
     * @param int $gameNumber
     */
    protected function setNextGame(int $gameNumber)
    {
        parent::setNextGame($gameNumber);
        $this->nextRoundNumber = $this->getGameRound($gameNumber);
        if (empty($this->nextRoundNumber)) $this->nextRoundNumber = -1;
    }


    protected function roundGapInCalendar(int $currentRound, int $roundGap): int
    {
        $nextRound = $currentRound + $roundGap;
        if ($nextRound > $this->calendarRoundCount) $nextRound -= $this->calendarRoundCount;
        if ($nextRound < 1) $nextRound += $this->calendarRoundCount;
        return $nextRound;
    }

    protected function addGame(int $ordHome = 1, int $ordAway = 2, int $round = 1)
    {
        $this->calendar[$round][] = new GameDuel($ordHome, $ordAway);
    }


    /**
     * @param GameDuel $game
     */
    protected function updateRankingsForGame($game)
    {
        if (isset($this->rankings[$game->getIdHome()])) {
            ($this->rankings[$game->getIdHome()])->saveGame($game);
        }
        if (isset($this->rankings[$game->getIdAway()])) {
            ($this->rankings[$game->getIdAway()])->saveGame($game);
        }
    }


    protected function orderRankings()
    {
        $this->orderedRankings = $this->rankings;
        usort($this->orderedRankings, array(RankingDuel::class, 'orderRankings'));
        $this->orderedRankings = array_reverse($this->orderedRankings);
    }


    public static function getMaxPointForAGame(): int
    {
        return RankingDuel::getPointsForWon();
    }


    public static function getMinPointForAGame(): int
    {
        return RankingDuel::getPointsForLoss();
    }


}
