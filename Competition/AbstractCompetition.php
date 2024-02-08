<?php

namespace Keiwen\Utils\Competition;

abstract class AbstractCompetition
{
    protected $givenPlayers;
    protected $players;
    protected $playerCount;
    protected $roundCount = 1;

    /** @var AbstractGame[] $gameRepository */
    protected $gameRepository = array();
    protected $nextGameNumber = 1;

    /** @var AbstractRanking[] $rankings */
    protected $rankings = array();
    /** @var AbstractRanking[] $orderedRankings */
    protected $orderedRankings = array();


    public function __construct(array $players)
    {
        $this->initializePlayers($players);
        // initialize rankings;
        $this->initializeRanking();
        $this->orderRankings();
    }

    protected function initializePlayers(array $players)
    {
        $this->playerCount = count($players);
        $this->givenPlayers = $players;
        $this->players = array_keys($players);
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

    public function getGameCountByPlayer()
    {
        return count($this->gameRepository);
    }

    public function getGames()
    {
        return $this->gameRepository;
    }

    public function getRoundCount(): int
    {
        return $this->roundCount;
    }

    /**
     * @param bool $ranked
     * @return array
     */
    public function getFullPlayers(bool $ranked = false)
    {
        if (!$ranked) return $this->givenPlayers;

        $rankedList = array();
        $rankings = $this->getRankings();
        foreach ($rankings as $ranking) {
            $nextPlayerSeed = $ranking->getPlayerSeed();
            $nextPlayer = $this->givenPlayers[$nextPlayerSeed] ?? null;
            if (!empty($nextPlayer)) $rankedList[] = $nextPlayer;
        }

        return $rankedList;
    }

    /**
     * @param int $playerSeed
     * @return mixed|null if found, full player data passed in constructor
     */
    public function getFullPlayer(int $playerSeed)
    {
        return $this->givenPlayers[$playerSeed - 1] ?? null;
    }

    /**
     * get game with a given number
     * @param int $gameNumber
     * @return AbstractGame|null game if found
     */
    public function getGameByNumber(int $gameNumber): ?AbstractGame
    {
        return $this->gameRepository[$gameNumber - 1] ?? null;
    }


    /**
     * get next game to play
     * @return AbstractGame|null game if found
     */
    public function getNextGame(): ?AbstractGame
    {
        return $this->getGameByNumber($this->nextGameNumber);
    }

    public function updateGamesPlayed()
    {
        $gameNumber = $this->nextGameNumber;
        // check first if championship already done
        if ($gameNumber == -1) return;
        do {
            $nextGamePlayed = false;
            $game = $this->getGameByNumber($gameNumber);
            if ($game && $game->isPlayed()) {
                $nextGamePlayed = true;
                $gameNumber++;
            }
        } while ($nextGamePlayed);

        if ($gameNumber != $this->nextGameNumber) {
            $this->updateRankings($this->nextGameNumber, $gameNumber - 1);
            $this->setNextGame($gameNumber);
        }
    }


    /**
     * @param int $gameNumber
     */
    protected function setNextGame(int $gameNumber)
    {
        $this->nextGameNumber = $gameNumber;
        if ($gameNumber <= 0 || $gameNumber > $this->getGameCount()) {
            // set to -1 if out of bounds
            $this->nextGameNumber = -1;
        }
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
        if (!empty($this->orderedRankings)) {
            $firstRanking = reset($this->orderedRankings);
            $rankingClass = get_class($firstRanking);
            usort($this->orderedRankings, array($rankingClass, 'orderRankings'));
            $this->orderedRankings = array_reverse($this->orderedRankings);
        }
    }

    abstract protected function updateRankingsForGame($game);

    protected function seedGapInPlayers(int $currentSeed, int $seedGap): int
    {
        $nextSeed = $currentSeed + $seedGap;
        if ($nextSeed > $this->playerCount) $nextSeed -= $this->playerCount;
        if ($nextSeed < 1) $nextSeed += $this->playerCount;
        return $nextSeed;
    }


    abstract protected function addGame(): AbstractGame;

    /**
     * @return bool
     */
    public function canGameBeAdded(): bool
    {
        return true;
    }

    /**
     * @return AbstractRanking[] first to last
     */
    public function getRankings(bool $byExpenses = false)
    {
        if ($byExpenses) {
            if (empty($this->rankings)) return array();
            $rankingsByExpenses = $this->rankings;
            $firstRanking = reset($rankingsByExpenses);
            $rankingClass = get_class($firstRanking);
            usort($rankingsByExpenses, array($rankingClass, 'orderRankingsByExpenses'));
            $rankingsByExpenses = array_reverse($rankingsByExpenses);
            return $rankingsByExpenses;
        }
        return $this->orderedRankings;
    }

    /**
     * @param int $playerSeed
     * @return bool
     */
    public function canPlayerWin(int $playerSeed)
    {
        return $this->canPlayerReachRank($playerSeed, 1);
    }

    /**
     * @param int $playerSeed
     * @param int $rank
     * @return bool
     */
    public function canPlayerReachRank(int $playerSeed, int $rank)
    {
        $rankRanking = $this->orderedRankings[$rank - 1] ?? null;
        $playerRanking = $this->rankings[$playerSeed] ?? null;
        if (empty($rankRanking) || empty($playerRanking)) return false;
        $toBePlayedForRank = $this->getGameCountByPlayer() - $rankRanking->getPlayed();
        $minPointsForRank = $rankRanking->getPoints() + $toBePlayedForRank * static::getMinPointForAGame();
        $toBePlayedForPlayer = $this->getGameCountByPlayer() - $playerRanking->getPlayed();
        $maxPointsForPlayer = $playerRanking->getPoints() + $toBePlayedForPlayer * static::getMaxPointForAGame();
        return $maxPointsForPlayer >= $minPointsForRank;
    }

    /**
     * @param int $playerSeed
     * @param int $rank
     * @return bool
     */
    public function canPlayerDropToRank(int $playerSeed, int $rank)
    {
        $rankRanking = $this->orderedRankings[$rank - 1] ?? null;
        $playerRanking = $this->rankings[$playerSeed] ?? null;
        if (empty($rankRanking) || empty($playerRanking)) return false;
        $toBePlayedForRank = $this->getGameCountByPlayer() - $rankRanking->getPlayed();
        $maxPointsForRank = $rankRanking->getPoints() + $toBePlayedForRank * static::getMaxPointForAGame();
        $toBePlayedForPlayer = $this->getGameCountByPlayer() - $playerRanking->getPlayed();
        $minPointsForPlayer = $playerRanking->getPoints() + $toBePlayedForPlayer * static::getMinPointForAGame();
        return $maxPointsForRank >= $minPointsForPlayer;
    }

    /**
     * @param int $playerSeed
     * @return bool
     */
    public function canPlayerLoose(int $playerSeed)
    {
        return $this->canPlayerDropToRank($playerSeed, 2);
    }


    abstract public static function getMaxPointForAGame(): int;
    abstract public static function getMinPointForAGame(): int;


    /**
     * @param AbstractCompetition $competition
     * @param bool $ranked
     * @return static
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false)
    {
        return new static($competition->getFullPlayers($ranked));
    }

    public function copyGamesFromCompetition(AbstractCompetition $competition)
    {
        if ($this->nextGameNumber != 1) {
            throw new CompetitionException('Cannot copy players as competition has started');
        }

        $this->gameRepository = array();
        $previousGames = $this->getGames();
        foreach ($previousGames as $game) {
            $newGame = $this->addGame();
            $newGame->setName($game->getName());
        }
    }



}
