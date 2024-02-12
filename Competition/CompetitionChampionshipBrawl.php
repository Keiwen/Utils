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
        foreach ($this->playersSeeds as $key => $seed) {
            $this->rankings[$key] = new RankingBrawl($key, $seed);
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


    /**
     * @return GameBrawl
     * @throws CompetitionException
     */
    protected function addGame(): AbstractGame
    {
        $brawl = new GameBrawl(array_keys($this->players));
        $gameNumber = count($this->gameRepository) + 1;
        $brawl->affectTo($this, $gameNumber);
        $this->gameRepository[] = $brawl;
        // if competition was considered as done, this new game became the next
        if ($this->nextGameNumber == -1) $this->setNextGame($gameNumber);
        return $brawl;
    }

    /**
     * @param string $name
     * @throws CompetitionException
     */
    public function addBrawl(string $name = '')
    {
        $this->addGame()->setName($name);
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
        foreach ($results as $playerKey => $result)  {
            ($this->rankings[$playerKey])->saveGame($game);
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
