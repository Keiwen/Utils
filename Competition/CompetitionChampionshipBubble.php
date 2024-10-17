<?php

namespace Keiwen\Utils\Competition;

use Keiwen\Utils\Math\Divisibility;

class CompetitionChampionshipBubble extends AbstractCompetition
{

    /** @var GameDuel[] $gameRepository */
    protected $gameRepository = array();

    /**
     * @param array $players
     * @param int $roundCount leave empty to use default round count
     */
    public function __construct(array $players, int $roundCount = 0)
    {
        if ($roundCount < 0) $roundCount = 0;
        if ($roundCount == 0) {
            $roundCount = static::getDefaultRoundCount(count($players));
        }
        $this->roundCount = $roundCount;
        parent::__construct($players);
    }

    public static function getMinPlayerCount(): int
    {
        return 3;
    }

    /**
     * get default number of round for a given number of players
     * - enough round to allow the last player, on a perfect run, to reach 1st spot
     * - even round count
     * @param int $playersCount
     * @return int
     */
    public static function getDefaultRoundCount(int $playersCount): int
    {
        // enough round for last player = player count
        return $playersCount;
    }

    protected function initializeRankingsHolder(): RankingsHolder
    {
        return RankingDuel::generateDefaultRankingsHolder();
    }


    public function getMinGameCountByPlayer(): int
    {
        return round($this->getRoundCount() / 2);
    }

    protected function generateCalendar(): void
    {
        // generate first round
        $this->generateNextRoundGames();
    }

    protected function generateNextRoundGames()
    {
        $this->currentRound++;
        // first player is left aside on odd round
        $startFromSeed = Divisibility::isNumberOdd($this->currentRound) ? 2 : 1;

        if ($startFromSeed == 2) {
            // if first player left aside, set a bye for him
            $byeGame = $this->addGame($this->currentRound, $this->getPlayerKeyOnSeed(1), null);
            $byeGame->setEndOfBye();
        }

        // each seed will duel vs following seed
        // note that last seed is left aside one on two rounds (depend on player count odd/even)
        for ($homeSeed = $startFromSeed; $homeSeed <= ($this->playerCount - 1); $homeSeed += 2) {
            $this->addGame($this->currentRound, $this->getPlayerKeyOnSeed($homeSeed), $this->getPlayerKeyOnSeed($homeSeed + 1));
        }

        if ($homeSeed == ($this->playerCount)) {
            // if last player left aside, set a bye for him
            $byeGame = $this->addGame($this->currentRound, $this->getPlayerKeyOnSeed(($this->playerCount)), null);
            $byeGame->setEndOfBye();
        }

        // consolidate calendar after each round games generation
        $this->consolidateCalendar();
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
     * @param int $round
     * @param int|string $keyHome
     * @param int|string $keyAway
     * @return GameDuel
     * @throws CompetitionException
     */
    protected function addGame(int $round, $keyHome = 1, $keyAway = 2): AbstractGame
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
        $rankingHome = $this->rankingsHolder->getRanking($game->getKeyHome());
        if ($rankingHome) {
            $rankingHome->saveGame($game);
        }
        $rankingAway = $this->rankingsHolder->getRanking($game->getKeyAway());
        if ($rankingAway) {
            $rankingAway->saveGame($game);
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
        return $this->canPlayerReachSeed($playerKey, $rank);
    }

    /**
     * @param int|string $playerKey
     * @param int $rank
     * @return bool
     */
    public function canPlayerDropToRank($playerKey, int $rank): bool
    {
        return $this->canPlayerDropToSeed($playerKey, $rank);
    }


    /**
     * @param CompetitionChampionshipBubble $competition
     * @param bool $ranked
     * @return CompetitionChampionshipBubble
     * @throws CompetitionException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        $newCompetition = new CompetitionChampionshipBubble($competition->getPlayers($ranked), $competition->getRoundCount());
        $newCompetition->setTeamComposition($competition->getTeamComposition());
        return $newCompetition;
    }

}
