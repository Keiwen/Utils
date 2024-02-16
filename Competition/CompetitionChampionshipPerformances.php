<?php

namespace Keiwen\Utils\Competition;

class CompetitionChampionshipPerformances extends AbstractCompetition
{
    /** @var GamePerformances[] $gameRepository */
    protected $gameRepository = array();


    public function __construct(array $players)
    {
        parent::__construct($players);
    }

    public static function getMinPlayerCount(): int
    {
        return 2;
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
     * @param bool $playerCanSkipGame
     * @return GamePerformances
     */
    protected function addGame(bool $playerCanSkipGame = true): AbstractGame
    {
        $game = new GamePerformances(array_keys($this->players), $playerCanSkipGame);
        $gameNumber = count($this->gameRepository) + 1;
        $game->affectTo($this, $gameNumber);
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
        $this->addGame()->setName($name, $playerCanSkipGame);
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
            ($this->rankings[$playerKey])->saveGame($game);
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
