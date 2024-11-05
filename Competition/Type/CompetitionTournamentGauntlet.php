<?php

namespace Keiwen\Utils\Competition\Type;

use Keiwen\Utils\Competition\Exception\CompetitionPlayerCountException;
use Keiwen\Utils\Competition\Exception\CompetitionRankingException;
use Keiwen\Utils\Competition\Exception\CompetitionRuntimeException;
use Keiwen\Utils\Competition\Ranking\AbstractRanking;
use Keiwen\Utils\Competition\Ranking\RankingDuel;

class CompetitionTournamentGauntlet extends AbstractTournamentCompetition
{

    /**
     * @param array $players
     * @throws CompetitionPlayerCountException
     * @throws CompetitionRankingException
     * @throws CompetitionRuntimeException
     */
    public function __construct(array $players)
    {
        parent::__construct($players);
    }


    /**
     * always false, by competition definition
     * @return bool
     */
    public function isBestSeedAlwaysHome(): bool
    {
        return false;
    }

    /**
     * always false, by competition definition
     * @return bool
     */
    public function hasPreRoundShuffle(): bool
    {
        return false;
    }


    /**
     * @return void
     * @throws CompetitionRuntimeException
     */
    protected function generateCalendar(): void
    {
        $this->roundCount = $this->playerCount - 1;

        $this->generateNextRoundGames();
    }


    /**
     * @return void
     * @throws CompetitionRuntimeException
     */
    protected function generateNextRoundGames()
    {
        $this->currentRound++;

        // for each finished round (current - 1), 1 player was eliminated
        $eliminated = ($this->currentRound - 1);
        $lastSeed = $this->playerCount - $eliminated;

        // on new round, last 2 seed face each other
        $this->addGame($this->currentRound, $this->getPlayerKeyOnSeed($lastSeed - 1), $this->getPlayerKeyOnSeed($lastSeed));

        // consolidate calendar after each round games generation
        $this->consolidateCalendar();
    }


    public function updateGamesPlayed()
    {
        parent::updateGamesPlayed();

        if ($this->nextGameNumber == -1) {
            // we run out of games

            // round is ended, update seeding
            $this->reseedPlayers();

            // check if new game needed
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


    /**
     * @param bool $byExpenses
     * @return RankingDuel[] first to last
     */
    public function getRankings(bool $byExpenses = false): array
    {
        // do not use classic rankings computing: rank by player seed instead
        return $byExpenses ? $this->rankingsHolder->getRankingsByExpenses() : $this->rankingsHolder->getRankingsBySeed($this->getPlayerKeysSeeded());
    }

    /**
     * @return AbstractRanking[] first to last
     */
    public function getTeamRankings(): array
    {
        // do not use classic rankings computing: rank by average players seeds instead
        return $this->rankingsHolder->getTeamRankingsByAverageSeed($this->teamComp, $this->getPlayerKeysSeeded());
    }


    public function getMaxPointForAGame(): int
    {
        return 1;
    }


    public function getMinPointForAGame(): int
    {
        return 0;
    }


    /**
     * @param int|string $playerKey
     * @param int $rank
     * @return bool
     */
    public function canPlayerReachRank($playerKey, int $rank): bool
    {
        // by design, if not in eliminated seed, a player can reach any seed
        $eliminated = ($this->currentRound - 1);
        if ($this->getPlayerSeed($playerKey) > ($this->playerCount - $eliminated)) return false;
        return true;
    }

    /**
     * @param int|string $playerKey
     * @param int $rank
     * @return bool
     */
    public function canPlayerDropToRank($playerKey, int $rank): bool
    {
        // by design, if not already eliminated, a player can drop for only one seed
        $eliminated = ($this->currentRound - 1);
        $playerSeed = $this->getPlayerSeed($playerKey);
        if ($playerSeed > ($this->playerCount - $eliminated)) return false;
        $playerRanking = $this->rankingsHolder->getRanking($playerKey);
        // if player played at least one game, he cannot go down
        if ($playerRanking->getPlayed() > 0) return false;
        if ($rank == ($playerSeed + 1)) return true;
        return false;
    }



    /**
     * @param CompetitionTournamentGauntlet $competition
     * @param bool $ranked
     * @return CompetitionTournamentGauntlet
     * @throws CompetitionPlayerCountException
     * @throws CompetitionRuntimeException
     * @throws CompetitionRankingException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        $newCompetition = new CompetitionTournamentGauntlet($competition->getPlayers($ranked));
        $newCompetition->setTeamComposition($competition->getTeamComposition());
        return $newCompetition;
    }

}
