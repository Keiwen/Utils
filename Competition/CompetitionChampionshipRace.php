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
        for ($playerOrd = 1; $playerOrd <= $this->playerCount; $playerOrd++) {
            $this->rankings[$playerOrd] = new RankingRace($playerOrd);
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


    protected function addGame()
    {
        $race = new GameRace(range(1, $this->playerCount));
        $race->affectTo($this, count($this->gameRepository) + 1);
        $this->gameRepository[] = $race;
    }

    /**
     */
    public function addRace()
    {
        $this->addGame();
    }


    /**
     * @param GameRace $game
     */
    protected function updateRankingsForGame($game)
    {
        $positions = $game->getPositions();
        foreach ($positions as $playerOrd => $position)  {
            ($this->rankings[$playerOrd])->saveGame($game);
        }
    }


    protected function orderRankings()
    {
        $this->orderedRankings = $this->rankings;
        usort($this->orderedRankings, array(RankingRace::class, 'orderRankings'));
        $this->orderedRankings = array_reverse($this->orderedRankings);
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
