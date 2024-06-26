<?php

namespace Keiwen\Utils\Competition;

use Keiwen\Utils\Math\Divisibility;

class CompetitionTournamentGauntlet extends AbstractTournamentCompetition
{

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


    protected function generateCalendar(): void
    {
        $this->roundCount = $this->playerCount - 1;

        $this->generateNextRoundGames();
    }


    protected function generateNextRoundGames()
    {
        $this->currentRound++;

        // for each finished round (current - 1), 1 player was eliminated
        $eliminated = ($this->currentRound - 1);
        $lastSeed = $this->playerCount - $eliminated;

        // on new round, last 2 seed face each other
        $this->addGame($this->getPlayerKeyOnSeed($lastSeed - 1), $this->getPlayerKeyOnSeed($lastSeed), $this->currentRound);

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
     * @param RankingDuel[] $rankings
     * @param bool $byExpenses
     * @return RankingDuel[]
     */
    protected function orderRankings(array $rankings, bool $byExpenses = false): array
    {
        if ($byExpenses) return parent::orderRankings($rankings, true);
        // do not use classic rankings orderings: rank by player seeds instead
        return $this->orderRankingsBySeed($rankings);
    }


    /**
     * @return RankingDuel[]
     */
    public function computeTeamRankings(): array
    {
        // do not use classic rankings computing: rank by average players seeds instead
        return $this->computeTeamRankingsBySeed();
    }


    public static function getMaxPointForAGame(): int
    {
        return 1;
    }


    public static function getMinPointForAGame(): int
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
        $playerRanking = $this->rankings[$playerKey] ?? null;
        // if player played at least one game, he cannot go down
        if ($playerRanking->getPlayed() > 0) return false;
        if ($rank == ($playerSeed + 1)) return true;
        return false;
    }



    /**
     * @param CompetitionTournamentGauntlet $competition
     * @param bool $ranked
     * @return CompetitionTournamentGauntlet
     * @throws CompetitionException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        $newCompetition = new CompetitionTournamentGauntlet($competition->getPlayers($ranked));
        $newCompetition->setTeamComposition($competition->getTeamComposition());
        return $newCompetition;
    }

}
