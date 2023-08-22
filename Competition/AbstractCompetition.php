<?php

namespace Keiwen\Utils\Competition;

abstract class AbstractCompetition
{
    protected $givenPlayers;
    protected $players;
    protected $playerCount;

    /** @var AbstractCompetitionGame[] $gameRepository */
    protected $gameRepository = array();
    protected $nextGameNumber = 1;

    /** @var AbstractCompetitionRanking[] $rankings */
    protected $rankings = array();
    /** @var AbstractCompetitionRanking[] $orderedRankings */
    protected $orderedRankings = array();


    public function __construct(array $players)
    {
        $this->playerCount = count($players);
        $this->givenPlayers = $players;
        $this->players = array_keys($players);
        // initialize rankings;
        $this->initializeRanking();
    }

    abstract protected function initializeRanking();

    public function getPlayerCount()
    {
        return $this->playerCount;
    }

    public function getGameCount()
    {
        return count($this->gameRepository);
    }

    abstract public function getGameCountByPlayer();

    /**
     * @param int $playerOrd
     * @return mixed|null if found, full player data passed in constructor
     */
    public function getFullPlayer(int $playerOrd)
    {
        return $this->givenPlayers[$playerOrd - 1] ?? null;
    }

    /**
     * get game with a given number
     * @param int $number
     * @return AbstractCompetitionGame|null game if found
     */
    public function getGameByNumber(int $number): ?AbstractCompetitionGame
    {
        return $this->gameRepository[$number - 1] ?? null;
    }


    /**
     * get next game to play
     * @return AbstractCompetitionGame|null game if found
     */
    public function getNextGame(): ?AbstractCompetitionGame
    {
        return $this->getGameByNumber($this->nextGameNumber);
    }

    public function updateGamesPlayed()
    {
        $gameNumber = $this->nextGameNumber;
        do {
            $nextGamePlayed = false;
            $game = $this->getGameByNumber($gameNumber);
            if ($game) {
                if ($game->isPlayed()) {
                    $nextGamePlayed = true;
                    $gameNumber++;
                }
            }
        } while ($nextGamePlayed);
        if ($game) {
            if ($gameNumber != $this->nextGameNumber) {
                $this->updateRankings($this->nextGameNumber, $gameNumber - 1);
                $this->setNextGame($gameNumber);
            }
        } else {
            $this->setNextGame(-1);
        }
    }

    /**
     * @param int $gameNumber
     */
    protected function setNextGame(int $gameNumber)
    {
        $this->nextGameNumber = $gameNumber;
    }

    /**
     * @param int $fromGame
     * @param int $toGame
     */
    protected function updateRankings(int $fromGame, int $toGame) {
        for ($gameNumber = $fromGame; $gameNumber <= $toGame; $gameNumber++) {
            $game = $this->getGameByNumber($gameNumber);
            if (!$game) continue;
            $this->updateRankingsForGame($game);
        }
        $this->orderRankings();
    }

    protected function orderRankings()
    {
        $this->orderedRankings = $this->rankings;
        usort($this->orderedRankings, array(AbstractCompetitionRanking::class, 'orderRankings'));
        $this->orderedRankings = array_reverse($this->orderedRankings);
    }

    abstract protected function updateRankingsForGame($game);

    protected function ordGapInPlayers(int $currentOrd, int $ordGap): int
    {
        $nextOrd = $currentOrd + $ordGap;
        if ($nextOrd > $this->playerCount) $nextOrd -= $this->playerCount;
        if ($nextOrd < 1) $nextOrd += $this->playerCount;
        return $nextOrd;
    }


    abstract protected function addGame();

    /**
     * @return AbstractCompetitionRanking[] first to last
     */
    public function getRankings()
    {
        return $this->orderedRankings;
    }

    /**
     * @param int $playerOrd
     * @return bool
     */
    public function canPlayerWin(int $playerOrd)
    {
        return $this->canPlayerReachRank($playerOrd, 1);
    }

    /**
     * @param int $playerOrd
     * @param int $rank
     * @return bool
     */
    public function canPlayerReachRank(int $playerOrd, int $rank)
    {
        $rankRanking = $this->orderedRankings[$rank - 1] ?? null;
        $playerRanking = $this->rankings[$playerOrd] ?? null;
        if (empty($rankRanking) || empty($playerRanking)) return false;
        $toBePlayedForRank = $this->getGameCountByPlayer() - $rankRanking->getPlayed();
        $minPointsForRank = $rankRanking->getPoints() + $toBePlayedForRank * $rankRanking::getPointsForLoss();
        $toBePlayedForPlayer = $this->getGameCountByPlayer() - $playerRanking->getPlayed();
        $maxPointsForPlayer = $playerRanking->getPoints() + $toBePlayedForPlayer * $playerRanking::getPointsForWon();
        return $maxPointsForPlayer >= $minPointsForRank;
    }

    /**
     * @param int $playerOrd
     * @param int $rank
     * @return bool
     */
    public function canPlayerDropToRank(int $playerOrd, int $rank)
    {
        $rankRanking = $this->orderedRankings[$rank - 1] ?? null;
        $playerRanking = $this->rankings[$playerOrd] ?? null;
        if (empty($rankRanking) || empty($playerRanking)) return false;
        $toBePlayedForRank = $this->getGameCountByPlayer() - $rankRanking->getPlayed();
        $maxPointsForRank = $rankRanking->getPoints() + $toBePlayedForRank * $rankRanking::getPointsForWon();
        $toBePlayedForPlayer = $this->getGameCountByPlayer() - $playerRanking->getPlayed();
        $minPointsForPlayer = $playerRanking->getPoints() + $toBePlayedForPlayer * $playerRanking::getPointsForLoss();
        return $maxPointsForRank >= $minPointsForPlayer;
    }

    /**
     * @param int $playerOrd
     * @return bool
     */
    public function canPlayerLoose(int $playerOrd)
    {
        return $this->canPlayerDropToRank($playerOrd, 2);
    }



}
