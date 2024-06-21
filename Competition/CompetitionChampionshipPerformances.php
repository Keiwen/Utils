<?php

namespace Keiwen\Utils\Competition;

class CompetitionChampionshipPerformances extends AbstractCompetition
{
    /** @var GamePerformances[] $gameRepository */
    protected $gameRepository = array();
    protected $performanceTypesToSum = array();


    public function __construct(array $players, array $performanceTypesToSum = array())
    {
        $this->performanceTypesToSum = $performanceTypesToSum;

        parent::__construct($players);
    }

    public static function getMinPlayerCount(): int
    {
        return 2;
    }

    protected function initializeRankingsHolder(): RankingsHolder
    {
        return RankingPerformances::generateDefaultRankingsHolder();
    }


    public function getPerformanceTypesToSum(): array
    {
        return $this->performanceTypesToSum;
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
     * @param int $round
     * @return GamePerformances[]
     */
    public function getGamesByRound(int $round): array
    {
        // for this kind of competition, round = game number
        $game = $this->getGameByNumber($round);
        return $game ? array($game) : array();
    }


    /**
     * @param bool $playerCanSkipGame
     * @return GamePerformances
     */
    protected function addGame(bool $playerCanSkipGame = true): AbstractGame
    {
        $game = new GamePerformances(array_keys($this->players), $this->getPerformanceTypesToSum(), $playerCanSkipGame);
        $gameNumber = count($this->gameRepository) + 1;
        $game->affectTo($this, $gameNumber);
        $game->setCompetitionRound($gameNumber);
        $this->roundCount = $gameNumber;
        $this->gameRepository[] = $game;
        // if competition was considered as done, this new game became the next
        if ($this->nextGameNumber == -1) $this->setNextGame($gameNumber);
        return $game;
    }

    /**
     * @param string $name
     * @param bool $playerCanSkipGame
     */
    public function addPerformancesGame(string $name = '', bool $playerCanSkipGame = true)
    {
        $this->addGame($playerCanSkipGame)->setName($name);
    }


    /**
     * @param int $count
     */
    public function addPerformancesGames(int $count)
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->addPerformancesGame();
        }
    }

    /**
     * @param GamePerformances $game
     */
    protected function updateRankingsForGame($game)
    {
        $results = $game->getResults();
        foreach ($results as $playerKey => $result)  {
            $ranking = $this->rankingsHolder->getRanking($playerKey);
            if ($ranking) {
                $ranking->saveGame($game);
            }
        }
    }


    public function getMaxPointForAGame(): int
    {
        return -1;
    }


    public function getMinPointForAGame(): int
    {
        return 0;
    }

    /**
     * @param CompetitionChampionshipPerformances $competition
     * @param bool $ranked
     * @return CompetitionChampionshipPerformances
     * @throws CompetitionException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        $newCompetition = new CompetitionChampionshipPerformances($competition->getPlayers($ranked), $competition->getPerformanceTypesToSum());
        $newCompetition->setTeamComposition($competition->getTeamComposition());
        return $newCompetition;
    }


}
