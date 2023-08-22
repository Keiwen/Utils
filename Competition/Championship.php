<?php

namespace Keiwen\Utils\Competition;

use Keiwen\Utils\Math\Divisibility;

class Championship
{
    protected $givenPlayers;
    protected $players;
    protected $playerCount;
    protected $serieCount;
    protected $calendar;
    protected $calendarRoundCount;
    /** @var CompetitionGame[] $gameRepository */
    protected $gameRepository = array();
    protected $nextGameNumber = 1;
    protected $nextRoundNumber = 1;
    /** @var CompetitionRanking[] $rankings */
    protected $rankings = array();
    /** @var CompetitionRanking[] $orderedRankings */
    protected $orderedRankings = array();


    public function __construct(array $players, int $serieCount = 1, bool $shuffleCalendar = false)
    {
        if ($serieCount < 1) $serieCount = 1;
        $this->playerCount = count($players);
        if ($this->playerCount < 3) throw new CompetitionException('Cannot create championship with less than 3 players');
        $this->givenPlayers = $players;
        $this->players = array_keys($players);
        $this->serieCount = $serieCount;
        $this->generateCalendar($shuffleCalendar);
        // initialize rankings;
        for ($playerOrd = 1; $playerOrd <= $this->playerCount; $playerOrd++) {
            $this->rankings[$playerOrd] = new CompetitionRanking($playerOrd);
        }
    }

    public function getPlayerCount()
    {
        return $this->playerCount;
    }

    public function getRoundCount()
    {
        return $this->calendarRoundCount;
    }

    public function getGameCount()
    {
        return count($this->gameRepository);
    }

    public function getGameCountByPlayer()
    {
        return ($this->playerCount - 1) * $this->serieCount;
    }

    public function getSerieCount()
    {
        return $this->serieCount;
    }

    /**
     * @param int $playerOrd
     * @return mixed|null if found, full player data passed in constructor
     */
    public function getFullPlayer(int $playerOrd)
    {
        return $this->givenPlayers[$playerOrd - 1] ?? null;
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
                    /** @var CompetitionGame $game */
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
                        /** @var CompetitionGame $firstSerieGame */
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
                /** @var CompetitionGame $game */
                $game->affectToChampionship($this, $gameNumber);
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
     * @return CompetitionGame[]] games of the round
     */
    public function getGamesByRound(int $round): array
    {
        return $this->calendar[$round] ?? array();
    }

    /**
     * get game with a given number
     * @param int $number
     * @return CompetitionGame|null game if found
     */
    public function getGameByNumber(int $number): ?CompetitionGame
    {
        if (!isset($this->gameRepository[$number])) return null;
        $round = $this->gameRepository[$number]['round'] ?? 0;
        $index = $this->gameRepository[$number]['index'] ?? 0;
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
     * get next game to play
     * @return CompetitionGame|null game if found
     */
    public function getNextGame(): ?CompetitionGame
    {
        return $this->getGameByNumber($this->nextGameNumber);
    }

    public function updateGamesPlayed()
    {
        $gameNumber = $this->nextGameNumber;
        do {
            $nextGamePlayed = false;
            $game = $this->getGameByNumber($gameNumber);
            if ($game) {
                if ($game->isPlayed()) {
                    $nextGamePlayed = true;
                    $gameNumber++;
                }
            }
        } while ($nextGamePlayed);
        if ($game) {
            if ($gameNumber != $this->nextGameNumber) {
                $this->updateRankings($this->nextGameNumber, $gameNumber - 1);
                $this->nextGameNumber = $gameNumber;
                $this->nextRoundNumber = $this->getGameRound($game->getGameNumber());
            }
        } else {
            $this->nextGameNumber = -1;
            $this->nextRoundNumber = -1;
        }
    }

    /**
     * @param int $fromGame
     * @param int $toGame
     */
    protected function updateRankings(int $fromGame, int $toGame) {
        for ($gameNumber = $fromGame; $gameNumber <= $toGame; $gameNumber++) {
            $game = $this->getGameByNumber($gameNumber);
            if (!$game) continue;
            if (!isset($this->rankings[$game->getIdHome()])) continue;
            ($this->rankings[$game->getIdHome()])->saveGame($game);
            if (!isset($this->rankings[$game->getIdAway()])) continue;
            ($this->rankings[$game->getIdAway()])->saveGame($game);
        }
        $this->orderedRankings = $this->rankings;
        usort($this->orderedRankings, array(CompetitionRanking::class, 'orderRankings'));
        $this->orderedRankings = array_reverse($this->orderedRankings);
    }


    protected function roundGapInCalendar(int $currentRound, int $roundGap): int
    {
        $nextRound = $currentRound + $roundGap;
        if ($nextRound > $this->calendarRoundCount) $nextRound -= $this->calendarRoundCount;
        if ($nextRound < 1) $nextRound += $this->calendarRoundCount;
        return $nextRound;
    }

    protected function ordGapInPlayers(int $currentOrd, int $ordGap): int
    {
        $nextOrd = $currentOrd + $ordGap;
        if ($nextOrd > $this->playerCount) $nextOrd -= $this->playerCount;
        if ($nextOrd < 1) $nextOrd += $this->playerCount;
        return $nextOrd;
    }


    protected function addGame(int $ordHome, int $ordAway, int $round)
    {
        $this->calendar[$round][] = new CompetitionGame($ordHome, $ordAway);
    }

    /**
     * @return CompetitionRanking[] first to last
     */
    public function getRankings()
    {
        return $this->orderedRankings;
    }

    /**
     * @param int $playerOrd
     * @return bool
     */
    public function canPlayerWin(int $playerOrd)
    {
        return $this->canPlayerReachRank($playerOrd, 1);
    }

    /**
     * @param int $playerOrd
     * @param int $rank
     * @return bool
     */
    public function canPlayerReachRank(int $playerOrd, int $rank)
    {
        $rankRanking = $this->orderedRankings[$rank - 1] ?? null;
        $playerRanking = $this->rankings[$playerOrd] ?? null;
        if (empty($rankRanking) || empty($playerRanking)) return false;
        $toBePlayedForRank = $this->getGameCountByPlayer() - $rankRanking->getPlayed();
        $minPointsForRank = $rankRanking->getPoints() + $toBePlayedForRank * CompetitionRanking::getPointsForLoss();
        $toBePlayedForPlayer = $this->getGameCountByPlayer() - $playerRanking->getPlayed();
        $maxPointsForPlayer = $playerRanking->getPoints() + $toBePlayedForPlayer * CompetitionRanking::getPointsForWon();
        return $maxPointsForPlayer >= $minPointsForRank;
    }

    /**
     * @param int $playerOrd
     * @param int $rank
     * @return bool
     */
    public function canPlayerDropToRank(int $playerOrd, int $rank)
    {
        $rankRanking = $this->orderedRankings[$rank - 1] ?? null;
        $playerRanking = $this->rankings[$playerOrd] ?? null;
        if (empty($rankRanking) || empty($playerRanking)) return false;
        $toBePlayedForRank = $this->getGameCountByPlayer() - $rankRanking->getPlayed();
        $maxPointsForRank = $rankRanking->getPoints() + $toBePlayedForRank * CompetitionRanking::getPointsForWon();
        $toBePlayedForPlayer = $this->getGameCountByPlayer() - $playerRanking->getPlayed();
        $minPointsForPlayer = $playerRanking->getPoints() + $toBePlayedForPlayer * CompetitionRanking::getPointsForLoss();
        return $maxPointsForRank >= $minPointsForPlayer;
    }

    /**
     * @param int $playerOrd
     * @return bool
     */
    public function canPlayerLoose(int $playerOrd)
    {
        return $this->canPlayerDropToRank($playerOrd, 2);
    }



}
