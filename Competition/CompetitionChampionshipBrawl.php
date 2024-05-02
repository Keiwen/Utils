<?php

namespace Keiwen\Utils\Competition;

class CompetitionChampionshipBrawl extends AbstractCompetition
{
    /** @var GameBrawl[] $gameRepository */
    protected $gameRepository = array();


    public function __construct(array $players)
    {
        parent::__construct($players);
    }

    public static function getMinPlayerCount(): int
    {
        return 3;
    }


    protected function initializeRankingsHolder(): RankingsHolder
    {
        return RankingBrawl::generateDefaultRankingsHolder();
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
        $brawl->setCompetitionRound($gameNumber);
        $this->roundCount = $gameNumber;
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
            $ranking = $this->rankingsHolder->getRanking($playerKey);
            if ($ranking) {
                $ranking->saveGame($game);
            }
        }
    }


    public function getMaxPointForAGame(): int
    {
        $rankings = $this->rankingsHolder->getAllRankings();
        $firstRanking = reset($rankings);
        if (empty($firstRanking)) return -1;
        return $firstRanking->getPointsForWon(true);
    }


    public function getMinPointForAGame(): int
    {
        $rankings = $this->rankingsHolder->getAllRankings();
        $firstRanking = reset($rankings);
        if (empty($firstRanking)) return 0;
        return $firstRanking->getPointsForLoss(true);
    }


    /**
     * @param CompetitionChampionshipBrawl $competition
     * @param bool $ranked
     * @return CompetitionChampionshipBrawl
     * @throws CompetitionException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        return parent::newCompetitionWithSamePlayers($competition, $ranked);
    }

}
