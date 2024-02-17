<?php

namespace Keiwen\Utils\Competition;

use Keiwen\Utils\Math\Divisibility;

class CompetitionChampionshipBubble extends AbstractFixedCalendarGame
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
        $roundCount = $playersCount;
        // if round, 1 round can be removed
        if (Divisibility::isNumberOdd($roundCount)) $roundCount--;
        return $roundCount;
    }

    /**
     * @param int|string $playerKey
     * @param int $playerSeed
     * @return RankingDuel
     */
    protected function initializePlayerRanking($playerKey, int $playerSeed = 0): AbstractRanking
    {
        return new RankingDuel($playerKey, $playerSeed);
    }

    public function getGameCountByPlayer(): int
    {
        return round($this->roundCount / 2);
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
        // each seed will duel vs following seed
        // note that last seed is left aside one on two rounds (depend on player count odd/even)
        for ($homeSeed = $startFromSeed; $homeSeed <= ($this->playerCount - 1); $homeSeed += 2) {
            $this->addGame($this->getPlayerKeyOnSeed($homeSeed), $this->getPlayerKeyOnSeed($homeSeed + 1), $this->currentRound);
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
     * @param int|string $keyHome
     * @param int|string $keyAway
     * @param int $round
     * @return GameDuel
     * @throws CompetitionException
     */
    protected function addGame($seedHome = 1, $seedAway = 2, int $round = 1): AbstractGame
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
        if (isset($this->rankings[$game->getKeyHome()])) {
            ($this->rankings[$game->getKeyHome()])->saveGame($game);
        }
        if (isset($this->rankings[$game->getKeyAway()])) {
            ($this->rankings[$game->getKeyAway()])->saveGame($game);
        }
    }


    protected function orderRankings()
    {
        // do not use classic rankings orderings: update player seeds instead
        $this->orderedRankings = array();
        $playerKeysSeeded = $this->getPlayerKeysSeeded();
        foreach ($playerKeysSeeded as $seed => $playerKey) {
            $this->orderedRankings[] = $this->rankings[$playerKey];
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

            $lastRoundGames = $this->getGamesByRound($this->currentRound);
            foreach ($lastRoundGames as $game) {
                if ($game->hasAwayWon()) {
                    // switch both seeds
                    $homeSeed = $this->getPlayerSeed($game->getKeyHome());
                    $this->playersSeeds[$game->getKeyHome()] = $homeSeed + 1;
                    $this->playersSeeds[$game->getKeyAway()] = $homeSeed;
                }
            }
            // call back order rankings
            $this->orderRankings();

            $this->generateNextRoundGames();

            // call back setNextGame with last game number
            $this->setNextGame($game->getGameNumber() + 1);
        }
    }


    public static function getMaxPointForAGame(): int
    {
        return -1;
    }


    public static function getMinPointForAGame(): int
    {
        return 0;
    }

    /**
     * @param CompetitionChampionshipBubble $competition
     * @param bool $ranked
     * @return CompetitionChampionshipBubble
     * @throws CompetitionException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        return new CompetitionChampionshipBubble($competition->getPlayers($ranked), $competition->getRoundCount());
    }

}
