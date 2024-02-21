<?php

namespace Keiwen\Utils\Competition;

use Keiwen\Utils\Math\Divisibility;

class CompetitionEliminationContest extends AbstractFixedCalendarGame
{
    /** @var GamePerformances[] $gameRepository */
    protected $gameRepository = array();

    protected $lastGameNumberAdded = 0;

    protected $playerPassingCount;
    protected $playerEliminatedPerRound = 0;
    protected $performanceTypesToSum = array();

    /**
     * @param array $players
     * @param string[] $performanceTypesToSum performance type to consider on sum. Leave it empty to take all performance from rankings
     * @param int[] $playerPassingCount for each round, number of players to keep for next round
     * @param int $playerEliminatedPerRound after each round, number of player to eliminate
     * @throws CompetitionException
     */
    public function __construct(array $players, array $performanceTypesToSum = array(), array $playerPassingCount = array(), int $playerEliminatedPerRound = 0)
    {
        if (empty($performanceTypesToSum)) $performanceTypesToSum = RankingPerformances::getPerformanceTypesToRank();
        if (empty($performanceTypesToSum)) throw new CompetitionException('Cannot create competition without performance to sum');
        $this->performanceTypesToSum = $performanceTypesToSum;

        foreach ($playerPassingCount as $count) {
            if (!is_int($count)) throw new CompetitionException('Cannot create competition with player passing count as non-integer value');
        }
        $this->playerPassingCount = $playerPassingCount;
        if (empty($playerPassingCount)) {
            if ($playerEliminatedPerRound < 1) {
                throw new CompetitionException('Cannot create competition without player elimination');
            }
            $this->playerEliminatedPerRound = $playerEliminatedPerRound;
        }
        parent::__construct($players);
    }

    public static function getMinPlayerCount(): int
    {
        return 3;
    }

    public function getPlayerPassingCount(): array
    {
        return $this->playerPassingCount;
    }

    public function getPlayerEliminatedPerRound(): int
    {
        return $this->playerEliminatedPerRound;
    }

    public function usePlayerPassingCount(): bool
    {
        return !empty($this->playerPassingCount);
    }

    public function usePlayerEliminatedPerRound(): bool
    {
        return !empty($this->playerEliminatedPerRound);
    }

    public function getPerformanceTypesToSum(): array
    {
        return $this->performanceTypesToSum;
    }

    /**
     * @param int|string $playerKey
     * @param int $playerSeed
     * @return RankingPerformances
     */
    protected function initializePlayerRanking($playerKey, int $playerSeed = 0): AbstractRanking
    {
        return new RankingPerformances($playerKey, $playerSeed);
    }


    public function getGameCountByPlayer(): int
    {
        return 1;
    }

    protected function generateCalendar(): void
    {
        if ($this->usePlayerPassingCount()) {
            $this->roundCount = count($this->playerPassingCount) + 1;
        } else {
            $this->roundCount = Divisibility::getPartFromTotal(count($this->players), $this->playerEliminatedPerRound);
        }
        $this->addGame(array_keys($this->players));
    }

    /**
     * get games for given round
     * @param int $round
     * @return GamePerformances[] games of the round
     */
    public function getGamesByRound(int $round): array
    {
        return parent::getGamesByRound($round);
    }

    /**
     * get game with a given number
     * @param int $gameNumber
     * @return GamePerformances|null game if found
     */
    public function getGameByNumber(int $gameNumber): ?AbstractGame
    {
        return parent::getGameByNumber($gameNumber);
    }

    /**
     * @return GamePerformances[]
     */
    public function getGames(): array
    {
        return parent::getGames();
    }


    /**
     * @param array $playerKeys
     * @param int $round
     * @return AbstractGame
     */
    protected function addGame(array $playerKeys = array(), int $round = 1): AbstractGame
    {
        $gamePerf = new GamePerformances($playerKeys, $this->getPerformanceTypesToSum(), false);
        $gamePerf->setCompetitionRound($round);
        $this->calendar[$round][] = $gamePerf;
        $gameNumber = $round;
        $this->lastGameNumberAdded = $gameNumber;
        $gamePerf->affectTo($this, $gameNumber);
        $this->gameRepository[$gameNumber] = array(
            'round' => $round,
            'index' => 0,
        );
        // if competition was considered as done, this new game became the next
        if ($this->nextGameNumber == -1) $this->setNextGame($gameNumber);
        return $gamePerf;
    }

    /**
     * @param GamePerformances $game
     */
    protected function updateRankingsForGame($game)
    {
        $results = $game->getResults();
        foreach ($results as $playerKey => $result)  {
            ($this->rankings[$playerKey])->saveGame($game);
        }
    }


    public function updateGamesPlayed()
    {
        parent::updateGamesPlayed();

        if ($this->nextGameNumber == -1) {
            // we run out of games, check if new game needed
            $potentialRound = $this->lastGameNumberAdded + 1;
            $playerCountExpected = $this->getPlayersCountToStartRound($potentialRound);
            // if no player expected, it's done!
            if ($playerCountExpected == 0) return;

            $this->currentRound++;

            $lastGame = $this->getGameByNumber($this->lastGameNumberAdded);
            $keysRanked = array_keys($lastGame->getGameRanks());
            $nextRoundKeys = array_slice($keysRanked, 0, $playerCountExpected);
            $newGame = $this->addGame($nextRoundKeys, $potentialRound);

            // call back setNextGame
            $this->setNextGame($potentialRound);
        }

    }

    /**
     * @param int $round
     * @return int how many player should start given round
     */
    public function getPlayersCountToStartRound(int $round): int
    {
        // if out of bonds, 0 players expected
        if ($round > $this->roundCount) return 0;
        if ($round < 1) return 0;
        // for first round, all players
        if ($round == 1) return $this->playerCount;
        if ($this->usePlayerPassingCount()) {
            // round X, so index is at -1: as we counting players passing
            // at the end of round, so -1 again
            return $this->playerPassingCount[$round - 2];
        } else {
            $eliminationCount = $this->playerEliminatedPerRound * ($round - 1);
            return $this->playerCount - $eliminationCount;
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
     * @param CompetitionEliminationContest $competition
     * @param bool $ranked
     * @return CompetitionEliminationContest
     * @throws CompetitionException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        $newCompetition = new CompetitionEliminationContest($competition->getPlayers($ranked), $competition->getPerformanceTypesToSum(), $competition->getPlayerPassingCount(), $competition->getPlayerEliminatedPerRound());
        $newCompetition->setTeamComposition($competition->getTeamComposition());
        return $newCompetition;
    }

}
