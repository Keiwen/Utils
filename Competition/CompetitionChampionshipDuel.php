<?php

namespace Keiwen\Utils\Competition;

use Keiwen\Utils\Math\Divisibility;

class CompetitionChampionshipDuel extends AbstractFixedCalendarGame
{
    protected $serieCount;

    /** @var GameDuel[] $gameRepository */
    protected $gameRepository = array();

    public function __construct(array $players, bool $shuffleCalendar = false, int $serieCount = 1)
    {
        if (count($players) < 3) throw new CompetitionException('Cannot create championship with less than 3 players');
        if ($serieCount < 1) $serieCount = 1;
        $this->serieCount = $serieCount;
        parent::__construct($players, $shuffleCalendar);
    }

    protected function initializeRanking()
    {
        for ($playerSeed = 1; $playerSeed <= $this->playerCount; $playerSeed++) {
            $this->rankings[$playerSeed] = new RankingDuel($playerSeed);
        }
    }

    public function getSerieCount(): int
    {
        return $this->serieCount;
    }

    public function getGameCountByPlayer(): int
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

        $roundInASerie = $this->getRoundCount();
        $this->generateFullCalendar();

        if ($shuffle) {
            // shuffle each serie individually instead of full calendar
            $calendarCopy = array_values($this->calendar);
            $this->calendar = array();
            $round = 1;
            for ($i = 1; $i <= $this->serieCount; $i++) {
                //for each serie, shuffle rounds inside
                //get calendar of current serie
                $calendarRandom = array_slice($calendarCopy, ($i - 1) * $roundInASerie, $roundInASerie);
                shuffle($calendarRandom);
                //shuffle and distribute again in actual calendar
                foreach ($calendarRandom as $randomRound => $oldRoundGames) {
                    $this->calendar[$round] = $calendarRandom[$randomRound];
                    foreach ($this->calendar[$round] as $newRoundGames) {
                        /** @var AbstractGame $newRoundGames */
                        $newRoundGames->setCompetitionRound($round);
                    }
                    $round++;
                }
            }
        }
    }

    protected function generateBaseCalendarEven()
    {
        $this->roundCount = $this->playerCount - 1;
        // for each round, first player will encounter all other in ascending order
        for ($round = 1; $round <= $this->roundCount; $round++) {
            $this->addGame(1, $round + 1, $round);
        }
        // init round when match next player
        $roundWhenMatchNextPlayer = 1;
        // starting next player, until we reach the penultimate (< instead of <= in loop)
        for ($seedHome = 2; $seedHome < $this->playerCount; $seedHome++) {
            // first match is on round following the round when this player matched previous player
            $round = $this->roundGapInCalendar($roundWhenMatchNextPlayer, 1);
            // first match is with the last one
            $this->addGame($seedHome, $this->playerCount, $round);

            // then match in ascending order with all others, starting with next player
            // stop before the last one, as already matched just before (< instead of <= in loop condition)
            // also store the round when we will match next player (so next of this one) to handle next player
            $roundWhenMatchNextPlayer = $this->roundGapInCalendar($round, 1);
            for ($seedAway = $seedHome + 1; $seedAway < $this->playerCount; $seedAway++) {
                $round = $this->roundGapInCalendar($round, 1);
                $this->addGame($seedHome, $seedAway, $round);
            }
        }
    }

    protected function generateBaseCalendarOdd()
    {
        $this->roundCount = $this->playerCount;
        // for each round, one player is out. We decided to go descendant order
        // the last player will not play on first round, the first will not play on last round

        $round = 1;
        // for each player
        for ($seedHome = 1; $seedHome <= $this->playerCount; $seedHome++) {
            // initialize $seedAway
            $seedAway = $seedHome;
            // one game per other player
            for ($i = 1; $i <= ($this->playerCount - 1); $i++) {
                // get seed - 2 for each game.
                $seedAway = $this->seedGapInPlayers($seedAway, -2);
                // If opponent seed is lower, means that this match should be already done
                // in that case, advance to next step (next round next opponent)
                if ($seedHome < $seedAway) {
                    $this->addGame($seedHome, $seedAway, $round);
                }
                $round = $this->roundGapInCalendar($round, 1);
            }
        }
    }

    protected function generateFullCalendar()
    {
        if ($this->serieCount == 1) return;
        // more than 1 serie, repeat base calendar for each other series
        $round = $this->roundCount + 1;
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
                        $this->addGame($game->getSeedAway(), $game->getSeedHome(), $round);
                    } else {
                        $this->addGame($game->getSeedHome(), $game->getSeedAway(), $round);
                    }
                }
                $round++;
            }
        }

        // after this, also switch home/away for first serie only if total series are even
        // here roundCount is still equal to first serie rounds
        if (Divisibility::isNumberEven($this->serieCount)) {
            for ($round = 1; $round <= $this->roundCount; $round++) {
                if (Divisibility::isNumberEven($round)) {
                    foreach ($this->calendar[$round] as $firstSerieGame) {
                        /** @var GameDuel $firstSerieGame */
                        $firstSerieGame->reverseHomeAway();
                    }
                }
            }
        }

        $this->roundCount = $this->roundCount * $this->serieCount;
    }

    /**
     * get games for given round
     * @param int $round
     * @return GameDuel[] games of the round
     */
    public function getGamesByRound(int $round): array
    {
        return parent::getGamesByRound($round);
    }

    /**
     * get game with a given number
     * @param int $gameNumber
     * @return GameDuel|null game if found
     */
    public function getGameByNumber(int $gameNumber): ?AbstractGame
    {
        return parent::getGameByNumber($gameNumber);
    }

    /**
     * @return GameDuel[]
     */
    public function getGames(): array
    {
        return parent::getGames();
    }


    /**
     * @param int $seedHome
     * @param int $seedAway
     * @param int $round
     * @return GameDuel
     * @throws CompetitionException
     */
    protected function addGame(int $seedHome = 1, int $seedAway = 2, int $round = 1): AbstractGame
    {
        $gameDuel = new GameDuel($seedHome, $seedAway);
        $gameDuel->setCompetitionRound($round);
        $this->calendar[$round][] = $gameDuel;
        return $gameDuel;
    }

    /**
     * @param GameDuel $game
     */
    protected function updateRankingsForGame($game)
    {
        if (isset($this->rankings[$game->getSeedHome()])) {
            ($this->rankings[$game->getSeedHome()])->saveGame($game);
        }
        if (isset($this->rankings[$game->getSeedAway()])) {
            ($this->rankings[$game->getSeedAway()])->saveGame($game);
        }
    }


    public static function getMaxPointForAGame(): int
    {
        return RankingDuel::getPointsForWon();
    }


    public static function getMinPointForAGame(): int
    {
        return RankingDuel::getPointsForLoss();
    }

    /**
     * @param CompetitionChampionshipDuel $competition
     * @param bool $ranked
     * @param bool $shuffleCalendar
     * @param int $serieCount
     * @return CompetitionChampionshipDuel
     * @throws CompetitionException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false, bool $shuffleCalendar = false, int $serieCount = 1): AbstractCompetition
    {
        return new CompetitionChampionshipDuel($competition->getFullPlayers($ranked), $shuffleCalendar, $serieCount);
    }

}
