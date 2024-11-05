<?php

namespace Keiwen\Utils\Competition\Type;

use Keiwen\Utils\Competition\Exception\CompetitionPlayerCountException;
use Keiwen\Utils\Competition\Exception\CompetitionRankingException;
use Keiwen\Utils\Competition\Exception\CompetitionRoundCountException;
use Keiwen\Utils\Competition\Game\AbstractGame;
use Keiwen\Utils\Competition\Game\GameBrawl;
use Keiwen\Utils\Competition\Ranking\RankingBrawl;
use Keiwen\Utils\Competition\Ranking\RankingsHolder;

class CompetitionChampionshipBrawl extends AbstractCompetition
{
    /** @var GameBrawl[] $gameRepository */
    protected $gameRepository = array();


    /**
     * @param array $players
     * @param int $roundCount cannot be less than 1
     * @throws CompetitionPlayerCountException
     * @throws CompetitionRoundCountException
     * @throws CompetitionRankingException
     */
    public function __construct(array $players, int $roundCount)
    {
        if ($roundCount < 1) throw new CompetitionRoundCountException('to create competition', 1);
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


    /**
     * @return RankingsHolder
     * @throws CompetitionRankingException
     */
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
     * @return AbstractGame
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
     * @throws CompetitionPlayerCountException
     * @throws CompetitionRankingException
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        return parent::newCompetitionWithSamePlayers($competition, $ranked);
    }

}
