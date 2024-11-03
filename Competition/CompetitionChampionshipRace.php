<?php

namespace Keiwen\Utils\Competition;

use Keiwen\Utils\Competition\Exception\CompetitionException;

class CompetitionChampionshipRace extends AbstractCompetition
{
    /** @var GameRace[] $gameRepository */
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
     * @return GameRace[]
     */
    public function getGames(): array
    {
        return parent::getGames();
    }


    /**
     * @param int $round
     * @return GameRace[]
     */
    public function getGamesByRound(int $round): array
    {
        return parent::getGamesByRound($round);
    }


    /**
     * @param int $round
     * @return GameRace
     * @throws CompetitionException
     */
    protected function addGame(int $round): AbstractGame
    {
        $race = new GameRace(array_keys($this->players));
        $race->setCompetitionRound($round);
        $this->calendar[$round][] = $race;
        return $race;
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
