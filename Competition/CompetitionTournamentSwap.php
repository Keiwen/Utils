<?php

namespace Keiwen\Utils\Competition;

use Keiwen\Utils\Math\Divisibility;

class CompetitionTournamentSwap extends AbstractTournamentCompetition
{

    /**
     * @param array $players
     * @param bool $bestSeedAlwaysHome set true to always give higher seed the home spot
     */
    public function __construct(array $players, bool $bestSeedAlwaysHome = false)
    {
        $this->bestSeedAlwaysHome = $bestSeedAlwaysHome;
        parent::__construct($players);
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
        if (Divisibility::isNumberOdd($this->playerCount)) {
            throw new CompetitionException('Cannot create tournament swap with a odd number of players');
        }
        $this->roundCount = $this->playerCount / 2;

        $this->generateNextRoundGames();
    }


    protected function generateNextRoundGames()
    {
        $this->currentRound++;

        // for each finished round (current - 1), 2 players got their final seed
        // first and last one
        $seedFixed = ($this->currentRound - 1) * 2;
        $stillCompeting = $this->playerCount - $seedFixed;

        // for next round, ignore the first and last seeds that were already determined and final
        $playerSeedsRemaining = array_slice($this->getPlayerKeysSeeded(), $seedFixed / 2, $stillCompeting);

        // match players 2 by 2 in seed order
        $this->matchPlayers2By2($playerSeedsRemaining);

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
        $playerSeed = $this->getPlayerSeed($playerKey);
        // by default, player can keep its rank/seed
        if ($rank == $playerSeed) return true;

        // check possibility regarding fixed seeds
        $firstSeedsFixed = ($this->currentRound - 1);
        // if player in first fixed seed, cannot go further
        if ($playerSeed <= $firstSeedsFixed) return false;
        // if player in last fixed seed, cannot go further
        if ($playerSeed > ($this->playerCount - $firstSeedsFixed)) return false;
        // by design, each round a seed is fixed on top, so we cannot reach them
        if ($rank <= $firstSeedsFixed) return false;

        return $this->canPlayerReachSeed($playerKey, $rank);
    }

    /**
     * @param int|string $playerKey
     * @param int $rank
     * @return bool
     */
    public function canPlayerDropToRank($playerKey, int $rank): bool
    {
        $playerSeed = $this->getPlayerSeed($playerKey);
        // by default, player can keep its rank/seed
        if ($rank == $playerSeed) return true;

        // check possibility regarding fixed seeds
        $lastSeedsFixed = ($this->currentRound - 1);
        // if player in first fixed seed, cannot drop further
        if ($playerSeed <= $lastSeedsFixed) return false;
        // if player in last fixed seed, cannot drop further
        if ($playerSeed > ($this->playerCount - $lastSeedsFixed)) return false;
        // by design, each round a seed is fixed on bottom, so we cannot drop to them
        if ($rank > ($this->playerCount - $lastSeedsFixed)) return false;

        return $this->canPlayerDropToSeed($playerKey, $rank);
    }



    /**
     * @param CompetitionTournamentSwap $competition
     * @param bool $ranked
     * @return CompetitionTournamentSwap
     * @throws CompetitionException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        $newCompetition = new CompetitionTournamentSwap($competition->getPlayers($ranked), $competition->isBestSeedAlwaysHome());
        $newCompetition->setTeamComposition($competition->getTeamComposition());
        return $newCompetition;
    }

}
