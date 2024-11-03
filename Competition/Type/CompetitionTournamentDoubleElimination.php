<?php

namespace Keiwen\Utils\Competition\Type;

use Keiwen\Utils\Competition\Exception\CompetitionException;
use Keiwen\Utils\Competition\GameDuel;
use Keiwen\Utils\Math\Divisibility;

class CompetitionTournamentDoubleElimination extends AbstractTournamentCompetition
{

    protected $winnerBracketRounds = array();
    protected $loserBracketRoundsMajor = array();
    protected $loserBracketRoundsMinor = array();

    /** @var array $winnerBracketPlayerKeys key => true */
    protected $winnerBracketPlayerKeys = array();
    /** @var array $loserBracketPlayerKeys key => true */
    protected $loserBracketPlayerKeys = array();

    protected $secondFinalRequired = false;

    /**
     * @param array $players
     * @param bool $bestSeedAlwaysHome set true to always give higher seed the home spot
     * @param bool $preRoundShuffle set true to randomize matching before each round instead of following a fixed tree
     */
    public function __construct(array $players, bool $bestSeedAlwaysHome = false, bool $preRoundShuffle = false)
    {
        $this->bestSeedAlwaysHome = $bestSeedAlwaysHome;
        $this->preRoundShuffle = $preRoundShuffle;
        parent::__construct($players);
    }


    public function getMinGameCountByPlayer(): int
    {
        return 2;
    }

    protected function generateCalendar(): void
    {
        // initialize round count for winner bracket
        // equal to the power of 2 number of player + 1
        // we may also have a 2nd final to be added later
        $highestPowerOf2 = Divisibility::getHighestPowerOf($this->playerCount, 2);
        $WBRoundCount = $highestPowerOf2 + 1;

        // Except for WB first and last round, we actually have 2 rounds in LB between WB rounds:
        // ‘minor’ (get new losers from winner bracket)
        // ‘major’ (winners of loser round face again each other to match number of player in WB)
        // so LB rounds = (WB rounds - 2) * 2
        $LBRoundCount = ($WBRoundCount - 2) * 2;
        $this->roundCount = $WBRoundCount + $LBRoundCount;

        // check consistency: make sure that we have power of 2 number of players
        $this->checkPowerOf2NumberOfPlayer($this->playerCount, 1);

        // generate duel table for first round (in WB)
        $duelTable = $this->generateDuelTable($this->playerCount);
        // now that everything is dispatched, create games
        foreach ($duelTable as $duel) {
            $keyHome = $this->getPlayerKeyOnSeed($duel['seedHome']);
            $keyAway = $this->getPlayerKeyOnSeed($duel['seedAway']);
            $this->addGame(1, $keyHome, $keyAway);
            $this->winnerBracketPlayerKeys[$keyHome] = true;
            $this->winnerBracketPlayerKeys[$keyAway] = true;
        }
        // add first round in winner bracket and keep track of player count
        $this->winnerBracketRounds[] = 1;
    }

    /**
     * @param int $round
     * @return bool
     */
    public function isRoundInWinnerBracket(int $round): bool
    {
        return in_array($round, $this->winnerBracketRounds);
    }


    /**
     * @param GameDuel $game
     * @return bool
     */
    public function isGameInWinnerBracket(GameDuel $game): bool
    {
        $gameRound = $game->getCompetitionRound();
        return $this->isRoundInWinnerBracket($gameRound);
    }


    /**
     * @param int|string $playerKey
     * @return bool
     */
    public function isPlayerInWinnerBracket($playerKey): bool
    {
        return isset($this->winnerBracketPlayerKeys[$playerKey]);
    }


    /**
     * @return bool
     */
    public function hasSecondFinalRequired(): bool
    {
        return $this->secondFinalRequired;
    }


    protected function generateNextRoundGames()
    {
        $this->currentRound++;

        // first, if we need second final, just set it
        if ($this->secondFinalRequired) {
            $this->generateNextRoundForSecondFinal();
            $this->consolidateCalendar();
            return;
        }

        $previousRoundInWinnerBracket = $this->isRoundInWinnerBracket($this->currentRound - 1);

        // get winners and losers of previous round
        $previousLosers = array();
        $previousWinners = $this->getRoundWinners($this->currentRound - 1, $previousLosers);

        if ($previousRoundInWinnerBracket) {
            // after WB round, we have LB major round

            // losers are removed from WB and goes in LB
            // to duel against a player already in LB
            foreach ($previousLosers as $loserKey) {
                unset($this->winnerBracketPlayerKeys[$loserKey]);
                $this->loserBracketPlayerKeys[$loserKey] = true;
            }

            // ** UNLESS if first round, then we go to minor round directly
            if ($this->currentRound == 2) {
                // no major round after first round, set up a minor one with all players
                $this->generateNextRoundForLoserBracketMinor(array_merge($previousWinners, $previousLosers));
            } else {
                $this->generateNextRoundForLoserBracketMajor($previousWinners, $previousLosers);
            }
        } else {
            // we played on LB
            foreach ($previousLosers as $loserKey) {
                // eliminate all losers
                unset($this->loserBracketPlayerKeys[$loserKey]);
                $this->setPlayerEliminationRound($loserKey, $this->currentRound - 1);
            }

            // if we have as many players in LB left than on WB, we need minor LB round
            // else, we can setup a new WB round as we have enough space
            // to include a new bunch of WB losers
            if (count($this->loserBracketPlayerKeys) == count($this->winnerBracketPlayerKeys)) {
                // minor LB round

                // ** UNLESS we have only one player left, no more LB round
                if (count($this->loserBracketPlayerKeys) == 1) {
                    // we start first final here
                    $this->generateNextRoundForFirstFinal();
                } else {
                    $this->generateNextRoundForLoserBracketMinor($previousWinners);
                }
            } else {
                // new WB round
                $this->generateNextRoundForWinnerBracket($previousWinners);
            }
        }

        // consolidate calendar after each round games generation
        $this->consolidateCalendar();
    }


    /**
     * winners from WB are matched 2 by 2
     * winners from LB gets a bye game
     * @param array $winnerKeys include winners from both WB and LB
     * @throws CompetitionException
     */
    protected function generateNextRoundForWinnerBracket(array $winnerKeys)
    {
        $this->winnerBracketRounds[] = $this->currentRound;
        $winnersFromWB = array();
        foreach ($winnerKeys as $winnerKey) {
            if ($this->isPlayerInWinnerBracket($winnerKey)) {
                // track winners from WB
                $winnersFromWB[] = $winnerKey;
            } else {
                // set a bye for LB
                $byeGame = $this->addGame($this->currentRound, $winnerKey, null);
                $byeGame->setEndOfBye();
            }
        }
        // shuffle if needed
        if ($this->hasPreRoundShuffle()) {
            shuffle($winnersFromWB);
        }

        // match winners from WB 2 by 2
        $this->matchPlayers2By2($winnersFromWB);
    }

    /**
     * LB major round aims to match LB winners with WB losers
     *
     * winners from WB gets a bye game
     * losers from WB are matched against winners from LB
     * @param array $winnerKeys include winners from both WB and LB
     * @param array $losersKeyFromWB include losers from WB only, as we should not have LB round before
     * @throws CompetitionException
     */
    protected function generateNextRoundForLoserBracketMajor(array $winnerKeys, array $losersKeyFromWB)
    {
        $this->loserBracketRoundsMajor[] = $this->currentRound;
        $winnersFromLB = array();
        foreach ($winnerKeys as $winnerKey) {
            if ($this->isPlayerInWinnerBracket($winnerKey)) {
                $byeGame = $this->addGame($this->currentRound, $winnerKey, null);
                $byeGame->setEndOfBye();
            } else {
                $winnersFromLB[] = $winnerKey;
            }
        }
        // check consistency: we must have same between WB losers and LB winners
        if (count($losersKeyFromWB) != count($winnersFromLB)) {
            throw new CompetitionException(sprintf('Cannot create loser bracket major round with %d losers and %d winners (round %d)', count($losersKeyFromWB), count($winnersFromLB), $this->currentRound));
        }

        // we could just match first loser against first winner and so on here
        // but by doing this, we could quickly repeat games we had in first round
        // we will try to mix a bit here, so we reverse winners orders on odd iteration
        if (Divisibility::isNumberOdd(count($this->loserBracketRoundsMajor))) {
            $losersKeyFromWB = array_reverse($losersKeyFromWB);
        }

        // shuffle if needed
        if ($this->hasPreRoundShuffle()) {
            shuffle($losersKeyFromWB);
        }

        // now match a WB loser vs LB winner
        // note: as we may have shuffled loser array, loop on winner array to keep LB orders
        for ($i = 0; $i < count($winnersFromLB); $i ++) {
            // we let player from WB having home side
            $this->addGame($this->currentRound, $losersKeyFromWB[$i], $winnersFromLB[$i]);
        }
    }


    /**
     * LB minor round aims to match WB players count in LB
     *
     * winners from WB gets a bye game
     * winners from LB are matched 2 by 2
     * @param array $winnerKeys include winners from both WB and LB
     * @throws CompetitionException
     */
    protected function generateNextRoundForLoserBracketMinor(array $winnerKeys)
    {
        $this->loserBracketRoundsMinor[] = $this->currentRound;
        $winnersFromLB = array();
        foreach ($winnerKeys as $winnerKey) {
            // set another bye for WB
            if ($this->isPlayerInWinnerBracket($winnerKey)) {
                $byeGame = $this->addGame($this->currentRound, $winnerKey, null);
                $byeGame->setEndOfBye();
            } else {
                // track winners from LB
                $winnersFromLB[] = $winnerKey;
            }
        }

        // shuffle if needed
        if ($this->hasPreRoundShuffle()) {
            shuffle($winnersFromLB);
        }

        // match winners from LB 2 by 2
        $this->matchPlayers2By2($winnersFromLB);
    }

    /**
     * First final aims to match the last WB player vs the last LB player
     *
     * @throws CompetitionException
     */
    protected function generateNextRoundForFirstFinal()
    {
        $this->winnerBracketRounds[] = $this->currentRound;
        $keysFromWB = array_keys($this->winnerBracketPlayerKeys);
        $lastWBPlayer = reset($keysFromWB);
        $keysFromLB = array_keys($this->loserBracketPlayerKeys);
        $lastLBPlayer = reset($keysFromLB);
        $this->addGame($this->currentRound, $lastWBPlayer, $lastLBPlayer);
    }

    /**
     * Second final aims to match the last LB player vs the last WB player
     * given that WB player lost the first final
     *
     * @throws CompetitionException
     */
    protected function generateNextRoundForSecondFinal()
    {
        $this->winnerBracketRounds[] = $this->currentRound;
        $keysFromWB = array_keys($this->winnerBracketPlayerKeys);
        $lastWBPlayer = reset($keysFromWB);
        $keysFromLB = array_keys($this->loserBracketPlayerKeys);
        $lastLBPlayer = reset($keysFromLB);
        // as second final, player from LB won, so we give him the home spot this time
        $this->addGame($this->currentRound, $lastLBPlayer, $lastWBPlayer);
    }


    public function updateGamesPlayed()
    {
        parent::updateGamesPlayed();

        if ($this->nextGameNumber == -1) {

            // we run out of games, check if new game needed
            if ($this->currentRound >= $this->roundCount) {
                // if current round is above defined round count, it's done!

                // ** UNLESS we did not check second final
                if (!$this->secondFinalRequired) {
                    $lastGameNumber = count($this->gameRepository);
                    $lastGame = $this->getGameByNumber($lastGameNumber);
                    // if away won, meaning LB player, WB player had his first time loss
                    // so we need to go to second and decisive final
                    if ($lastGame->hasAwayWon()) {
                        $this->secondFinalRequired = true;
                        // add a new round! and update games
                        $this->roundCount++;
                        $this->generateNextRoundGames();
                        $this->setNextGame($lastGameNumber + 1);
                        return;
                    }
                }

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
     * @param CompetitionTournamentDoubleElimination $competition
     * @param bool $ranked
     * @return CompetitionTournamentDoubleElimination
     * @throws CompetitionException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        $newCompetition = new CompetitionTournamentDoubleElimination($competition->getPlayers($ranked), $competition->isBestSeedAlwaysHome(), $competition->hasPreRoundShuffle());
        $newCompetition->setTeamComposition($competition->getTeamComposition());
        return $newCompetition;
    }

}
