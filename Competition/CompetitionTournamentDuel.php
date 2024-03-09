<?php

namespace Keiwen\Utils\Competition;

use Keiwen\Utils\Math\Divisibility;

class CompetitionTournamentDuel extends AbstractFixedCalendarCompetition
{

    /** @var GameDuel[] $gameRepository */
    protected $gameRepository = array();

    protected $hasPlayIn = false;
    protected $qualifiedAfterPlayIn = array();


    /**
     * Note: it really makes sense for at least 4 players as a stand-alone.
     * We set it at 2 to use it easily as potential finals in combined competitions
     * @return int
     */
    public static function getMinPlayerCount(): int
    {
        return 2;
    }

    /**
     * @param int|string $playerKey
     * @param int $playerSeed
     * @return RankingDuel
     */
    protected function initializePlayerRanking($playerKey, int $playerSeed = 0): AbstractRanking
    {
        $ranking =  new RankingDuel($playerKey, $playerSeed);
        $ranking->affectTo($this);
        return $ranking;
    }

    public function getGameCountByPlayer(): int
    {
        return 1;
    }

    protected function generateCalendar(): void
    {

        // check first if we need play-in (if not already created)
        if (!$this->hasPlayIn()) {
            // get the highest power of 2 that is <= to number of player
            $remainder = 0;
            $highestPowerof2 = Divisibility::getHighestPowerOf($this->playerCount, 2, $remainder);

            // initialize round count (without considering play-in yet)
            // equal to the power of 2 number of player (what we'll have with play-in if needed)
            $this->roundCount = $highestPowerof2;

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


        // for each player in first half, match with last player available
        // should be X (number of player) + 1 - first player seed
        // for 8 players, we will have
        // 1vs8, 2vs7, 3vs6, 4vs5
        // note: do not add games yet because of next step
        $duelTable = array();
        for ($i = 1; $i <= $numberOfRemainingPlayers / 2; $i++) {
            $duelTable[$i - 1][] = array(
                'seedHome' => $i,
                'seedAway' => $numberOfRemainingPlayers + 1 - $i,
            );
        }
        // now we want to avoid duel between high seeds until the end
        // to dispatch, each duel are set in a table part.
        // while this table has more than 1 part,
        // second half of parts are put in first half (in reversed order)
        // for 8 players, we started with 4 parts
        // first iteration will give
        // PART1, PART2
        // 1vs8, 2vs7
        // 4vs5, 3vs6 (not 3vs6 and 4vs5, as we reversed)
        // 2nd iteration will give
        // PART1
        // 1vs8
        // 4vs5
        // 2vs7
        // 3vs6
        // note: we always have halves in parts because number of player is power of 2
        while (count($duelTable) > 1) {
            $partCount = count($duelTable);
            for ($i = $partCount / 2; $i < $partCount; $i++) {
                $firstHalfPart = $partCount - $i - 1;
                $duelTable[$firstHalfPart] = array_merge($duelTable[$firstHalfPart], $duelTable[$i]);
                unset($duelTable[$i]);
            }
        }

        // now that all are dispatched, add games
        $duelTable = reset($duelTable);
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

        // get winners of previous round
        $previousWinners = $this->getRoundWinners($this->currentRound - 1);

        if ($this->currentRound == 2 && $this->hasPlayIn()) {
            // We are just out of play-in, retrieve REMAINING players
            // = previous winners, that includes bye games
            $this->qualifiedAfterPlayIn = $previousWinners;
            // regenerate calendar and consolidate
            $this->generateCalendar();
            $this->consolidateCalendar();
            return;
        }

        // check consistency: we should keep a number of players as a power of 2
        $numberOfPlayersLeft = count($previousWinners);
        $this->checkPowerOf2NumberOfPlayer($numberOfPlayersLeft, $this->currentRound);

        // match previous winner 2 by 2
        for ($i = 0; $i < $numberOfPlayersLeft; $i += 2) {
            $this->addGame($previousWinners[$i], $previousWinners[$i + 1], $this->currentRound);
        }
        // consolidate calendar after each round games generation
        $this->consolidateCalendar();
    }


    /**
     * @param int $numberOfPlayers
     * @param int $round
     * @return void
     * @throws CompetitionException
     */
    protected function checkPowerOf2NumberOfPlayer(int $numberOfPlayers, int $round)
    {
        $remainder = 0;
        Divisibility::getHighestPowerOf($numberOfPlayers, 2, $remainder);
        if ($remainder > 0) {
            throw new CompetitionException(sprintf('Cannot create next round with a number of players that is not a power of 2, %d given on round %d', $numberOfPlayers, $round));
        }
    }

    /**
     * @param int $round
     * @return int[]|string[]
     */
    public function getRoundWinners(int $round): array
    {
        $gamesInRound = $this->getGamesByRound($round);
        $winnerKeys = array();
        foreach ($gamesInRound as $game) {
            if (!$game->isPlayed()) continue;
            $winnerKeys[] = $game->hasAwayWon() ? $game->getKeyAway() : $game->getKeyHome();
            // we should not have drawn on tournament
            // but if drawn set, we consider that home won
        }
        return $winnerKeys;
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
     * @param int|string $keyHome
     * @param int|string $keyAway
     * @param int $round
     * @return GameDuel
     * @throws CompetitionException
     */
    protected function addGame($keyHome = 1, $keyAway = 2, int $round = 1): AbstractGame
    {
        $gameDuel = new GameDuel($keyHome, $keyAway);
        $gameDuel->setCompetitionRound($round);
        $this->calendar[$round][] = $gameDuel;
        return $gameDuel;
    }

    /**
     * @param GameDuel $game
     */
    protected function updateRankingsForGame($game)
    {
        if (isset($this->rankings[$game->getKeyHome()])) {
            ($this->rankings[$game->getKeyHome()])->saveGame($game);
        }
        if (isset($this->rankings[$game->getKeyAway()])) {
            ($this->rankings[$game->getKeyAway()])->saveGame($game);
        }
    }


    public function updateGamesPlayed()
    {
        parent::updateGamesPlayed();

        if ($this->nextGameNumber == -1) {

            // we run out of games, check if new game needed
            if ($this->currentRound >= $this->roundCount) {
                // if current round is above defined round count, it's done!
                return;
            }
            $lastGameNumber = count($this->gameRepository);
            $this->generateNextRoundGames();

            // call back setNextGame with last game number
            $this->setNextGame($lastGameNumber + 1);
        }
    }


    public static function getMaxPointForAGame(): int
    {
        return RankingDuel::getPointsForWon(true);
    }


    public static function getMinPointForAGame(): int
    {
        return RankingDuel::getPointsForLoss(true);
    }

    /**
     * @param CompetitionTournamentDuel $competition
     * @param bool $ranked
     * @return CompetitionTournamentDuel
     * @throws CompetitionException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        return parent::newCompetitionWithSamePlayers($competition, $ranked);
    }

}
