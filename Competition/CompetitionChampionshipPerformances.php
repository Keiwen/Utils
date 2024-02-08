<?php

namespace Keiwen\Utils\Competition;

class CompetitionChampionshipPerformances extends AbstractCompetition
{
    /** @var GamePerformances[] $gameRepository */
    protected $gameRepository = array();


    public function __construct(array $players)
    {
        if (count($players) < 2) throw new CompetitionException('Cannot create championship with less than 2 players');
        parent::__construct($players);
    }

    protected function initializeRanking()
    {
        for ($playerSeed = 1; $playerSeed <= $this->playerCount; $playerSeed++) {
            $this->rankings[$playerSeed] = new RankingPerformances($playerSeed);
        }
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
     * @return GamePerformances
     * @throws CompetitionException
     */
    protected function addGame(): AbstractGame
    {
        $game = new GamePerformances(range(1, $this->playerCount));
        $gameNumber = count($this->gameRepository) + 1;
        $game->affectTo($this, $gameNumber);
        $this->gameRepository[] = $game;
        // if competition was considered as done, this new game became the next
        if ($this->nextGameNumber == -1) $this->setNextGame($gameNumber);
        return $game;
    }

    /**
     * @param string $name
     * @throws CompetitionException
     */
    public function addPerformancesGame(string $name = '')
    {
        $this->addGame()->setName($name);
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
        foreach ($results as $playerSeed => $result)  {
            ($this->rankings[$playerSeed])->saveGame($game);
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



}
