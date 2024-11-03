<?php

namespace Keiwen\Utils\Competition\Type;

use Keiwen\Utils\Competition\AbstractGame;
use Keiwen\Utils\Competition\Exception\CompetitionException;
use Keiwen\Utils\Competition\GameBrawl;
use Keiwen\Utils\Competition\RankingBrawl;
use Keiwen\Utils\Competition\RankingsHolder;

class CompetitionChampionshipBrawl extends AbstractCompetition
{
    /** @var GameBrawl[] $gameRepository */
    protected $gameRepository = array();


    /**
     * @param array $players
     * @param int $roundCount cannot be less than 1
     * @throws CompetitionException
     */
    public function __construct(array $players, int $roundCount)
    {
        if ($roundCount < 1) throw new CompetitionException('Cannot create competition with less than 1 round');
        $this->roundCount = $roundCount;
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
     * @return GameBrawl[]
     */
    public function getGames(): array
    {
        return parent::getGames();
    }


    /**
     * @param int $round
     * @return GameBrawl[]
     */
    public function getGamesByRound(int $round): array
    {
        return parent::getGamesByRound($round);
    }


    /**
     * @param int $round
     * @return GameBrawl
     * @throws CompetitionException
     */
    protected function addGame(int $round): AbstractGame
    {
        $brawl = new GameBrawl(array_keys($this->players));
        $brawl->setCompetitionRound($round);
        $this->calendar[$round][] = $brawl;
        return $brawl;
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
