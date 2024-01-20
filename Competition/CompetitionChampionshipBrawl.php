<?php

namespace Keiwen\Utils\Competition;

class CompetitionChampionshipBrawl extends AbstractCompetition
{
    /** @var GameBrawl[] $gameRepository */
    protected $gameRepository = array();


    public function __construct(array $players)
    {
        if (count($players) < 3) throw new CompetitionException('Cannot create championship with less than 3 players');
        parent::__construct($players);
    }

    protected function initializeRanking()
    {
        for ($playerOrd = 1; $playerOrd <= $this->playerCount; $playerOrd++) {
            $this->rankings[$playerOrd] = new RankingBrawl($playerOrd);
        }
    }


    /**
     * get game with a given number
     * @param int $gameNumber
     * @return GameBrawl|null game if found
     */
    public function getGameByNumber(int $gameNumber): ?AbstractGame
    {
        return parent::getGameByNumber($gameNumber);
    }


    protected function addGame()
    {
        $race = new GameBrawl(range(1, $this->playerCount));
        $race->affectTo($this, count($this->gameRepository) + 1);
        $this->gameRepository[] = $race;
    }

    /**
     */
    public function addBrawl()
    {
        $this->addGame();
    }

    /**
     * @param int $count
     */
    public function addBrawls(int $count)
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->addBrawl();
        }
    }

    /**
     * @param GameBrawl $game
     */
    protected function updateRankingsForGame($game)
    {
        $results = $game->getResults();
        foreach ($results as $playerOrd => $result)  {
            ($this->rankings[$playerOrd])->saveGame($game);
        }
    }


    public static function getMaxPointForAGame(): int
    {
        return RankingBrawl::getPointsForWon();
    }


    public static function getMinPointForAGame(): int
    {
        return RankingBrawl::getPointsForLoss();
    }



}
