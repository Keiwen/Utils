<?php

namespace Keiwen\Utils\Competition;


class CompetitionEliminationThreshold extends AbstractFixedCalendarCompetition
{
    /** @var GamePerformances[] $gameRepository */
    protected $gameRepository = array();

    protected $lastGameNumberAdded = 0;

    protected $minPerformanceFirstRound = 0;
    protected $minPerformanceRoundStep = 1;
    protected $performanceTypesToSum = array();

    /**
     * @param array $players
     * @param string[] $performanceTypesToSum performance type to consider on sum. Leave it empty to take all performance from rankings
     * @param int $minPerformanceFirstRound total performance to reach to pass first round
     * @param int $minPerformanceRoundStep increment to total performance to reach for each additional round
     * @throws CompetitionException
     */
    public function __construct(array $players, array $performanceTypesToSum = array(), int $minPerformanceFirstRound = 0, int $minPerformanceRoundStep = 1)
    {
        if (empty($performanceTypesToSum)) $performanceTypesToSum = RankingPerformances::getPerformanceTypesToRank();
        if (empty($performanceTypesToSum)) throw new CompetitionException('Cannot create competition without performance to sum');
        $this->performanceTypesToSum = $performanceTypesToSum;

        if ($minPerformanceRoundStep < 1) {
            throw new CompetitionException('Cannot create competition without performance step');
        }
        $this->minPerformanceFirstRound = $minPerformanceFirstRound;
        $this->minPerformanceRoundStep = $minPerformanceRoundStep;
        parent::__construct($players);
    }

    public static function getMinPlayerCount(): int
    {
        return 2;
    }

    public function getMinPerformanceFirstRound(): array
    {
        return $this->minPerformanceFirstRound;
    }

    public function getMinPerformanceRoundStep(): int
    {
        return $this->minPerformanceRoundStep;
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


    public function getMinGameCountByPlayer(): int
    {
        return 1;
    }

    protected function generateCalendar(): void
    {
        $this->roundCount = 1;
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
        $gamePerf->setName($this->getMinPerformanceForRound($round));
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

            // else we need another round
            $this->roundCount++;
            $this->currentRound++;

            $lastGame = $this->getGameByNumber($this->lastGameNumberAdded);
            $qualified = $lastGame->getPlayersKeysThatReachedPerformance($this->getMinPerformanceForRound($this->lastGameNumberAdded));
            // store elimination round
            $alreadyEliminated = array_keys($this->playerEliminationRound);
            foreach ($this->getPlayerKeysSeeded() as $playerKey) {
                if (!in_array($playerKey, $qualified) && !in_array($playerKey, $alreadyEliminated)) {
                    $this->setPlayerEliminationRound($playerKey, $this->currentRound - 1);
                }
            }
            $newGame = $this->addGame($qualified, $potentialRound);

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
        // if empty round, 0 players expected
        if ($round < 1) return 0;
        // for first round, all players
        if ($round == 1) return $this->playerCount;

        $gameBefore = $this->getGameByNumber($round - 1);
        $qualified = $gameBefore->getPlayersKeysThatReachedPerformance($this->getMinPerformanceForRound($round - 1));
        return count($qualified);
    }


    /**
     * @param int $round
     * @return int min perf to reach to succeed given round
     */
    public function getMinPerformanceForRound(int $round): int
    {
        return $this->minPerformanceFirstRound + ($round - 1) * $this->minPerformanceRoundStep;
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
     * @param CompetitionEliminationThreshold $competition
     * @param bool $ranked
     * @return CompetitionEliminationThreshold
     * @throws CompetitionException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        $newCompetition = new CompetitionEliminationThreshold($competition->getPlayers($ranked), $competition->getPerformanceTypesToSum(), $competition->getMinPerformanceFirstRound(), $competition->getMinPerformanceRoundStep());
        $newCompetition->setTeamComposition($competition->getTeamComposition());
        return $newCompetition;
    }

}
