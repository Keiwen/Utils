<?php

namespace Keiwen\Utils\Competition;

abstract class AbstractCompetition
{
    /** @var array $playersSeeds key => seed */
    protected $playersSeeds;
    /** @var array $players key => player */
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
        $this->players = $players;
        $this->playersSeeds = array_combine(array_keys($players), range(1, count($players)));
    }

    abstract protected function initializeRanking();

    public function getPlayerCount(): int
    {
        return $this->playerCount;
    }

    public function getGameCount(): int
    {
        return count($this->gameRepository);
    }

    public function getGameCountByPlayer(): int
    {
        return count($this->gameRepository);
    }

    /**
     * @return AbstractGame[]
     */
    public function getGames(): array
    {
        return $this->gameRepository;
    }

    public function getRoundCount(): int
    {
        return $this->roundCount;
    }

    /**
     * @param int|string $playerKey
     * @return int 0 if not found
     */
    public function getPlayerSeed($playerKey): int
    {
        return $this->playersSeeds[$playerKey] ?? 0;
    }

    /**
     * @param int $playerSeed
     * @return int|string|null null if not found
     */
    public function getPlayerKeyOnSeed(int $playerSeed)
    {
        $keysBySeed = array_flip($this->playersSeeds);
        return $keysBySeed[$playerSeed] ?? null;
    }

    /**
     * @param int $playerSeed
     * @return mixed|null null if not found
     */
    public function getPlayerOnSeed(int $playerSeed)
    {
        return $this->getPlayer($this->getPlayerKeyOnSeed($playerSeed));
    }

    /**
     * @param bool $ranked
     * @return array
     */
    public function getPlayers(bool $ranked = false): array
    {
        if (!$ranked) return $this->players;

        $rankedList = array();
        $rankings = $this->getRankings();
        foreach ($rankings as $ranking) {
            $nextPlayerKey = $ranking->getPlayerKey();
            $nextPlayer = $this->getPlayer($nextPlayerKey);
            if ($nextPlayer !== null) $rankedList[] = $nextPlayer;
        }

        return $rankedList;
    }

    /**
     * @param int|string $playerKey
     * @return mixed|null if found, player data
     */
    public function getPlayer($playerKey)
    {
        return $this->players[$playerKey] ?? null;
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
        $this->orderedRankings = array_values($this->rankings);
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
    public function getRankings(bool $byExpenses = false): array
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
     * @param int|string $playerKey
     * @return bool
     */
    public function canPlayerWin($playerKey): bool
    {
        return $this->canPlayerReachRank($playerKey, 1);
    }

    /**
     * @param int|string $playerKey
     * @param int $rank
     * @return bool
     */
    public function canPlayerReachRank($playerKey, int $rank): bool
    {
        $rankRanking = $this->orderedRankings[$rank - 1] ?? null;
        $playerRanking = $this->rankings[$playerKey] ?? null;
        if (empty($rankRanking) || empty($playerRanking)) return false;
        if (static::getMaxPointForAGame() === -1) return true;
        $toBePlayedForRank = $this->getGameCountByPlayer() - $rankRanking->getPlayed();
        $minPointsForRank = $rankRanking->getPoints() + $toBePlayedForRank * static::getMinPointForAGame();
        $toBePlayedForPlayer = $this->getGameCountByPlayer() - $playerRanking->getPlayed();
        $maxPointsForPlayer = $playerRanking->getPoints() + $toBePlayedForPlayer * static::getMaxPointForAGame();
        return $maxPointsForPlayer >= $minPointsForRank;
    }

    /**
     * @param int|string $playerKey
     * @param int $rank
     * @return bool
     */
    public function canPlayerDropToRank($playerKey, int $rank): bool
    {
        $rankRanking = $this->orderedRankings[$rank - 1] ?? null;
        $playerRanking = $this->rankings[$playerKey] ?? null;
        if (empty($rankRanking) || empty($playerRanking)) return false;
        if (static::getMaxPointForAGame() === -1) return true;
        $toBePlayedForRank = $this->getGameCountByPlayer() - $rankRanking->getPlayed();
        $maxPointsForRank = $rankRanking->getPoints() + $toBePlayedForRank * static::getMaxPointForAGame();
        $toBePlayedForPlayer = $this->getGameCountByPlayer() - $playerRanking->getPlayed();
        $minPointsForPlayer = $playerRanking->getPoints() + $toBePlayedForPlayer * static::getMinPointForAGame();
        return $maxPointsForRank >= $minPointsForPlayer;
    }

    /**
     * @param int|string $playerKey
     * @return bool
     */
    public function canPlayerLoose($playerKey): bool
    {
        return $this->canPlayerDropToRank($playerKey, 2);
    }


    /** @return int -1 if no max defined */
    abstract public static function getMaxPointForAGame(): int;
    abstract public static function getMinPointForAGame(): int;


    /**
     * @return bool
     */
    public function isCompleted(): bool
    {
        if ($this->nextGameNumber != -1) return false;
        return $this->getGameCount() != 0;
    }

    /**
     * @return int
     */
    public function getGamesCompletedCount(): int
    {
        if ($this->isCompleted()) return $this->getGameCount();
        if (empty($this->getGameCount())) return 0;
        return $this->nextGameNumber - 1;
    }

    /**
     * @return int
     */
    public function getGamesToPlayCount(): int
    {
        if (empty($this->getGameCount())) return 0;
        return $this->getGameCount() - $this->getGamesCompletedCount();
    }

    /**
     * @param AbstractCompetition $competition
     * @param bool $ranked
     * @return static
     */
    public static function newCompetitionWithSamePlayers(AbstractCompetition $competition, bool $ranked = false): AbstractCompetition
    {
        return new static($competition->getPlayers($ranked));
    }


}
