<?php

namespace Keiwen\Utils\Competition;

use Keiwen\Utils\Math\Divisibility;

class CompetitionTournamentDuel extends AbstractTournamentCompetition
{

    protected $hasPlayIn = false;
    protected $qualifiedAfterPlayIn = array();
    protected $includeThirdPlaceGame = false;

    /**
     * @param array $players
     * @param bool $includeThirdPlaceGame set true to include a third place game
     * @param bool $bestSeedAlwaysHome set true to always give higher seed the home spot
     * @param bool $preRoundShuffle set true to randomize matching before each round instead of following a fixed tree
     */
    public function __construct(array $players, bool $includeThirdPlaceGame = false, bool $bestSeedAlwaysHome = false, bool $preRoundShuffle = false)
    {
        $this->includeThirdPlaceGame = $includeThirdPlaceGame;
        $this->bestSeedAlwaysHome = $bestSeedAlwaysHome;
        $this->preRoundShuffle = $preRoundShuffle;
        parent::__construct($players);
    }


    /**
     * Note: it really makes sense for at least 4 players as a stand-alone.
     * We set it at 2 to use it easily as potential finals in combined competitions
     * @return int
     */
    public static function getMinPlayerCount(): int
    {
        return 2;
    }


    protected function generateCalendar(): void
    {

        // check first if we need play-in (if not already created)
        if (!$this->hasPlayIn()) {
            // get the highest power of 2 that is <= to number of player
            $remainder = 0;
            $highestPowerOf2 = Divisibility::getHighestPowerOf($this->playerCount, 2, $remainder);

            // initialize round count (without considering play-in yet)
            // equal to the power of 2 number of player (what we'll have with play-in if needed)
            $this->roundCount = $highestPowerOf2;
            // add additional round if third place game required
            if ($this->includeThirdPlaceGame()) $this->roundCount++;

            if ($remainder != 0) {
                // play-in is required with number of duel = remainder
                $this->generatePlayIn($remainder * 2);
                return;
            }
        }

        $firstFinalRound = $this->hasPlayIn() ? 2 : 1;

        // check consistency: make sure that we have power of 2 number of players REMAINING
        $numberOfRemainingPlayers = $this->hasPlayIn() ? count($this->qualifiedAfterPlayIn) : $this->playerCount;
        $this->checkPowerOf2NumberOfPlayer($numberOfRemainingPlayers, $firstFinalRound);


        // generate duel table
        $duelTable = $this->generateDuelTable($numberOfRemainingPlayers);
        // now that everything is dispatched, create games
        foreach ($duelTable as $duel) {
            // beware if we had a play-in: we must consider reseeding from this round
            // + now it's round 2 not one
            $this->addGame(
                $this->getPlayerKeyOnSeed($duel['seedHome'], true),
                $this->getPlayerKeyOnSeed($duel['seedAway'], true),
                $firstFinalRound);
        }
    }


    protected function generateNextRoundGames()
    {
        $this->currentRound++;

        // get winners and losers of previous round
        $previousLosers = array();
        $previousWinners = $this->getRoundWinners($this->currentRound - 1, $previousLosers);

        if ($this->currentRound == 2 && $this->hasPlayIn()) {
            // We are just out of play-in, retrieve REMAINING players
            // = previous winners, that includes bye games
            $this->qualifiedAfterPlayIn = $previousWinners;
            // store elimination round
            foreach ($previousLosers as $previousLoser) {
                $this->setPlayerEliminationRound($previousLoser, $this->currentRound - 1);
            }
            // regenerate calendar and consolidate
            $this->generateCalendar();
            $this->consolidateCalendar();
            return;
        }

        $numberOfPlayersLeft = count($previousWinners);

        if ($numberOfPlayersLeft == 3 && $this->includeThirdPlaceGame()) {
            // we just played third place game, so 3 winners: both finalists with bye game
            // and winner of third place.
            // get looser set their elimination round (as we had bye on first 2 games, we have only one looser defined)
            $this->setPlayerEliminationRound($previousLosers[0], $this->currentRound - 1);
            $this->setPlayerEliminationRound($previousWinners[2], $this->currentRound);
            // Keep the first 2 winners and set the final round
            $this->addGame($previousWinners[0], $previousWinners[1], $this->currentRound);
            $this->consolidateCalendar();
            return;
        }

        // check consistency: we should keep a number of players as a power of 2
        $this->checkPowerOf2NumberOfPlayer($numberOfPlayersLeft, $this->currentRound);

        if ($numberOfPlayersLeft == 2 && $this->includeThirdPlaceGame()) {
            // we have 2 finalists but a third place game is required!
            // add bye for previous winners
            $byeGame = $this->addGame($previousWinners[0], null, $this->currentRound);
            $byeGame->setEndOfBye();
            $byeGame = $this->addGame($previousWinners[1], null, $this->currentRound);
            $byeGame->setEndOfBye();

            // add the 3rd place game
            $this->addGame($previousLosers[0], $previousLosers[1], $this->currentRound);
            $this->consolidateCalendar();
            return;
        }

        // store elimination round
        foreach ($previousLosers as $previousLoser) {
            $this->setPlayerEliminationRound($previousLoser, $this->currentRound - 1);
        }

        $this->generateNextRoundForClassicRound($previousWinners);
        // consolidate calendar after each round games generation
        $this->consolidateCalendar();
    }

    /**
     * Classic tournament uses a power of 2 number of players. If we have more, we use a play-in
     * as a quick 'qualifying' step to reduce players to a power of 2 number
     *
     * In default play-in, first player available duel last player available and so on.
     * Winner take the highest seed and qualify for classic tournament.
     * Example for 7 players, play-in will have 2vs7, 3vs6 and 4vs5. If 7 wins,
     * he will start tournament at seed 7
     * This example does not make a lot of sense but hey, find the 8th player then ;)
     *
     * In default play-in, format is kept quite simple on purpose.
     * Complex play-in may be defined by using combined competition.
     *
     * @param int $playinPlayerCount number of player in play-in phase (must be even)
     * @return void
     */
    protected function generatePlayIn(int $playinPlayerCount)
    {
        // check that we have a even number of players
        if (Divisibility::isNumberOdd($playinPlayerCount)) {
            throw new CompetitionException(sprintf('Cannot create competition play-in with an odd number of players'));
        }

        // add a new round
        $this->roundCount++;
        $this->hasPlayIn = true;

        // give a bye to players that does not need play-in
        $lastQualifiedSeed = $this->playerCount - $playinPlayerCount;
        for ($i = 1; $i <= $lastQualifiedSeed; $i++) {
            $byeGame = $this->addGame($this->getPlayerKeyOnSeed($i), null, 1);
            $byeGame->setEndOfBye();
        }
        // all the other goes through play-in
        for ($i = 1; $i <= $playinPlayerCount / 2; $i++) {
            $homeSeed = $lastQualifiedSeed + $i;
            $awaySeed = $this->playerCount - $i + 1;
            $this->addGame($this->getPlayerKeyOnSeed($homeSeed), $this->getPlayerKeyOnSeed($awaySeed), 1);
        }
    }

    /**
     * winners are matched 2 by 2
     * @param array $winnerKeys
     * @throws CompetitionException
     */
    protected function generateNextRoundForClassicRound(array $winnerKeys)
    {
        // shuffle if needed
        if ($this->hasPreRoundShuffle()) {
            shuffle($winnerKeys);
        }

        // match previous winner 2 by 2
        $this->matchPlayers2By2($winnerKeys);
    }


    /**
     * @param int $playerSeed
     * @param bool $afterPlayIn set to true to consider post-play-in re-seeding
     * @return int|string|null null if not found
     */
    public function getPlayerKeyOnSeed(int $playerSeed, bool $afterPlayIn = false)
    {
        // by default, follow parent
        if (!$afterPlayIn || !$this->hasPlayIn()) return parent::getPlayerKeyOnSeed($playerSeed);
        return $this->qualifiedAfterPlayIn[$playerSeed - 1] ?? null;
    }


    /**
     * @return bool
     */
    public function hasPlayIn(): bool
    {
        return $this->hasPlayIn;
    }

    /**
     * @return bool
     */
    public function includeThirdPlaceGame(): bool
    {
        return $this->includeThirdPlaceGame;
    }


    public function updateGamesPlayed()
    {
        parent::updateGamesPlayed();

        if ($this->nextGameNumber == -1) {

            // we run out of games, check if new game needed
            if ($this->currentRound >= $this->roundCount) {
                // if current round is above defined round count, it's done!

                // store elimination round for the last one
                $previousLosers = array();
                $this->getRoundWinners($this->roundCount, $previousLosers);
                foreach ($previousLosers as $previousLoser) {
                    $this->setPlayerEliminationRound($previousLoser, $this->roundCount);
                }
                return;
            }
            $lastGameNumber = count($this->gameRepository);
            $this->generateNextRoundGames();

            // call back setNextGame with last game number
            $this->setNextGame($lastGameNumber + 1);
        }
    }


    /**
     * @param CompetitionTournamentDuel $competition
     * @param bool $ranked
     * @return CompetitionTournamentDuel
     * @throws CompetitionException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        $newCompetition = new CompetitionTournamentDuel($competition->getPlayers($ranked), $competition->includeThirdPlaceGame(), $competition->isBestSeedAlwaysHome(), $competition->hasPreRoundShuffle());
        $newCompetition->setTeamComposition($competition->getTeamComposition());
        return $newCompetition;
    }

}
