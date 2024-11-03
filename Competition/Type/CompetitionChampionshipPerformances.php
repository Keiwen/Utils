<?php

namespace Keiwen\Utils\Competition\Type;

use Keiwen\Utils\Competition\AbstractGame;
use Keiwen\Utils\Competition\Exception\CompetitionException;
use Keiwen\Utils\Competition\GamePerformances;
use Keiwen\Utils\Competition\RankingPerformances;
use Keiwen\Utils\Competition\RankingsHolder;

class CompetitionChampionshipPerformances extends AbstractCompetition
{
    /** @var GamePerformances[] $gameRepository */
    protected $gameRepository = array();
    protected $performanceTypesToSum = array();


    /**
     * @param array $players
     * @param int $roundCount cannot be less than 1
     * @param array $performanceTypesToSum
     * @throws CompetitionException
     */
    public function __construct(array $players, int $roundCount, array $performanceTypesToSum = array())
    {
        if ($roundCount < 1) throw new CompetitionException('Cannot create competition with less than 1 round');
        $this->roundCount = $roundCount;
        $this->performanceTypesToSum = $performanceTypesToSum;

        parent::__construct($players);
    }

    protected function generateCalendar(): void
    {
        for ($round = 1; $round <= $this->roundCount; $round++) {
            $this->addGame($round);
        }
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
     * @return GamePerformances[]
     */
    public function getGames(): array
    {
        return parent::getGames();
    }


    /**
     * @param int $round
     * @return GamePerformances[]
     */
    public function getGamesByRound(int $round): array
    {
        return parent::getGamesByRound($round);
    }


    /**
     * @param int $round
     * @return GamePerformances
     */
    protected function addGame(int $round): AbstractGame
    {
        $game = new GamePerformances(array_keys($this->players), $this->getPerformanceTypesToSum(), false);
        $game->setCompetitionRound($round);
        $this->calendar[$round][] = $game;
        return $game;
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
        $newCompetition = new CompetitionChampionshipPerformances($competition->getPlayers($ranked), $competition->getRoundCount(), $competition->getPerformanceTypesToSum());
        $newCompetition->setTeamComposition($competition->getTeamComposition());
        return $newCompetition;
    }


}
