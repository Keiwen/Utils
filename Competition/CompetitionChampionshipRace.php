<?php

namespace Keiwen\Utils\Competition;

class CompetitionChampionshipRace extends AbstractCompetition
{
    /** @var GameRace[] $gameRepository */
    protected $gameRepository = array();


    public function __construct(array $players)
    {
        if (count($players) < 2) throw new CompetitionException('Cannot create championship with less than 2 players');
        parent::__construct($players);
    }

    protected function initializeRanking()
    {
        foreach ($this->playersSeeds as $key => $seed) {
            $this->rankings[$key] = new RankingRace($key, $seed);
        }
    }


    /**
     * get game with a given number
     * @param int $gameNumber
     * @return GameRace|null game if found
     */
    public function getGameByNumber(int $gameNumber): ?AbstractGame
    {
        return parent::getGameByNumber($gameNumber);
    }


    /**
     * @return GameRace
     * @throws CompetitionException
     */
    protected function addGame(): AbstractGame
    {
        $race = new GameRace(array_keys($this->players));
        $gameNumber = count($this->gameRepository) + 1;
        $race->affectTo($this, $gameNumber);
        $this->gameRepository[] = $race;
        // if competition was considered as done, this new game became the next
        if ($this->nextGameNumber == -1) $this->setNextGame($gameNumber);
        return $race;
    }

    /**
     * @param string $name
     * @throws CompetitionException
     */
    public function addRace(string $name = '')
    {
        $this->addGame()->setName($name);
    }


    /**
     * @param int $count
     */
    public function addRaces(int $count)
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->addRace();
        }
    }

    /**
     * @param GameRace $game
     */
    protected function updateRankingsForGame($game)
    {
        $positions = $game->getPositions();
        foreach ($positions as $playerKey => $position)  {
            ($this->rankings[$playerKey])->saveGame($game);
        }
    }


    public static function getMaxPointForAGame(): int
    {
        return RankingRace::getPointsForResult(1);
    }


    public static function getMinPointForAGame(): int
    {
        return 0;
    }



}
