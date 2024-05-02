<?php

namespace Keiwen\Utils\Competition;

class CompetitionChampionshipRace extends AbstractCompetition
{
    /** @var GameRace[] $gameRepository */
    protected $gameRepository = array();


    public function __construct(array $players)
    {
        parent::__construct($players);
    }

    public static function getMinPlayerCount(): int
    {
        return 2;
    }

    protected function initializeRankingsHolder(): RankingsHolder
    {
        return RankingRace::generateDefaultRankingsHolder();
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
        $race->setCompetitionRound($gameNumber);
        $this->roundCount = $gameNumber;
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
        foreach ($positions as $position => $playerKey)  {
            $ranking = $this->rankingsHolder->getRanking($playerKey);
            if ($ranking) {
                $ranking->saveGame($game);
            }
        }
    }


    public function getMaxPointForAGame(): int
    {
        return $this->rankingsHolder->getPointsForResult(1);
    }


    public function getMinPointForAGame(): int
    {
        return 0;
    }


    /**
     * @param CompetitionChampionshipRace $competition
     * @param bool $ranked
     * @return CompetitionChampionshipRace
     * @throws CompetitionException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        return parent::newCompetitionWithSamePlayers($competition, $ranked);
    }

}
