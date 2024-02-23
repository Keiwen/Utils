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
    protected $currentRound = 0;
    /** @var array $teamComp team key => array of player keys */
    protected $teamComp = array();

    /** @var AbstractGame[] $gameRepository */
    protected $gameRepository = array();
    protected $nextGameNumber = 1;

    /** @var AbstractRanking[] $rankings */
    protected $rankings = array();
    /** @var AbstractRanking[] $orderedRankings */
    protected $orderedRankings = array();
    /** @var AbstractRanking[] $teamRankings */
    protected $teamRankings = array();


    public function __construct(array $players)
    {
        if (count($players) < static::getMinPlayerCount()) throw new CompetitionException(sprintf('Cannot create competition with less than %d players', static::getMinPlayerCount()));
        $this->initializePlayers($players);
        // initialize rankings;
        $this->initializeRanking();
        $this->orderedRankings = $this->orderRankings($this->rankings);
        $this->teamRankings = $this->computeTeamRankings();
    }

    protected function initializePlayers(array $players)
    {
        $this->playerCount = count($players);
        $this->players = $players;
        $this->playersSeeds = array_combine(array_keys($players), range(1, count($players)));
    }

    protected function initializeRanking()
    {
        foreach ($this->playersSeeds as $key => $seed) {
            $this->rankings[$key] = $this->initializePlayerRanking($key, $seed);
        }
    }

    abstract protected function initializePlayerRanking($playerKey, int $playerSeed = 0): AbstractRanking;

    abstract public static function getMinPlayerCount(): int;

    public function getPlayerCount(): int
    {
        return $this->playerCount;
    }

    public function getTeamCount(): int
    {
        return count($this->teamComp);
    }

    /**
     * @param array $teamComp team key => players keys
     */
    public function setTeamComposition(array $teamComp)
    {
        $this->teamComp = $teamComp;
        $this->teamRankings = $this->computeTeamRankings();
    }

    /**
     * @return array team key => players keys
     */
    public function getTeamComposition(): array
    {
        return $this->teamComp;
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

    public function getCurrentRound(): int
    {
        return $this->currentRound;
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
     * @param int|string $teamKey
     * @return int 0 if not found
     */
    public function getTeamSeed($teamKey): int
    {
        if (!isset($this->teamComp[$teamKey])) return 0;
        $teams = array_keys($this->teamComp);
        // now have index => team key
        $teams = array_flip($teams);
        // now have team key => index
        return $teams[$teamKey] + 1;
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
     * @param int $teamSeed
     * @return int|string|null null if not found
     */
    public function getTeamKeyOnSeed(int $teamSeed)
    {
        $teams = array_keys($this->teamComp);
        // now have index => team key
        return $teams[$teamSeed - 1] ?? null;
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
     * @param int|string $teamKey
     * @return array list of player keys
     */
    public function getPlayerKeysInTeam($teamKey): array
    {
        return $this->teamComp[$teamKey] ?? array();
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
            $nextPlayerKey = $ranking->getEntityKey();
            $nextPlayer = $this->getPlayer($nextPlayerKey);
            if ($nextPlayer !== null) $rankedList[] = $nextPlayer;
        }

        return $rankedList;
    }

    /**
     * @return int[]|string[] seed => key, ordered by seed
     */
    public function getPlayerKeysSeeded(): array
    {
        $keysBySeed = array_flip($this->playersSeeds);
        ksort($keysBySeed);
        return $keysBySeed;
    }

    /**
     * @return array
     */
    public function getTeamKeys(): array
    {
        return array_keys($this->teamComp);
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
        $nextGame = $this->getGameByNumber($this->nextGameNumber);
        if ($nextGame && $nextGame->isPlayed()) {
            $this->updateRankings($this->nextGameNumber, $this->nextGameNumber);
            $this->nextGameNumber++;
            return $this->getNextGame();
        }
        return $nextGame;
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
                $this->currentRound = $game->getCompetitionRound();
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
        $this->orderedRankings = $this->orderRankings($this->rankings);
        $this->teamRankings = $this->computeTeamRankings();
    }

    /**
     * @param AbstractRanking[] $rankings
     * @param bool $byExpenses
     * @return AbstractRanking[]
     */
    protected function orderRankings(array $rankings, bool $byExpenses = false): array
    {
        $rankings = array_values($rankings);
        if (!empty($rankings)) {
            $firstRanking = reset($rankings);
            $rankingClass = get_class($firstRanking);

            if ($firstRanking instanceof RankingDuel && !$byExpenses) {
                // update point method before actually order
                foreach ($rankings as $ranking) {
                    /** @var RankingDuel $ranking */
                    $ranking->updatePointMethodCalcul(false);
                    $ranking->updatePointMethodCalcul(true);
                }
            }

            $rankingMethod = $byExpenses ? 'orderRankingsByExpenses' : 'orderRankings';
            usort($rankings, array($rankingClass, $rankingMethod));
            $rankings = array_reverse($rankings);
        }
        return $rankings;
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
     * @param bool $byExpenses
     * @return AbstractRanking[] first to last
     */
    public function getRankings(bool $byExpenses = false): array
    {
        if ($byExpenses) {
            return $this->orderRankings($this->rankings, $byExpenses);
        }
        return $this->orderedRankings;
    }

    /**
     * @param int|string $playerKey
     * @return AbstractRanking|null null if not found
     */
    public function getPlayerRanking($playerKey): ?AbstractRanking
    {
        return $this->rankings[$playerKey] ?? null;
    }

    /**
     * @param bool $byExpenses
     * @return AbstractRanking[] first to last
     */
    public function getTeamRankings(bool $byExpenses = false): array
    {
        if ($byExpenses) {
            return $this->orderRankings($this->teamRankings, $byExpenses);
        }
        return $this->teamRankings;
    }


    /**
     * @param bool $byExpenses
     * @return AbstractRanking[] first to last
     */
    public function computeTeamRankings(): array
    {
        $teamRankings = array();
        $teamSeed = 1;
        foreach ($this->teamComp as $teamKey => $playerKeys) {
            $teamRanking = $this->initializePlayerRanking($teamKey, $teamSeed);
            $playerRankings = array();
            foreach ($playerKeys as $playerKey) {
                $playerRankings[] = $this->rankings[$playerKey];
            }
            $teamRanking->combinedRankings($playerRankings);

            $teamRankings[$teamKey] = $teamRanking;
            $teamSeed++;
        }

        return $this->orderRankings($teamRankings);
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

    /**
     * @param int|string $playerKey
     * @return bool
     */
    public function canPlayerBeLast($playerKey): bool
    {
        return $this->canPlayerDropToRank($playerKey, $this->getPlayerCount());
    }


    /**
     * @param int|string $playerKey
     * @return int 0 if not found
     */
    public function getPlayerRank($playerKey): int
    {
        $rank = 0;
        foreach ($this->orderedRankings as $ranking) {
            $rank++;
            if ($ranking->getEntityKey() === $playerKey) return $rank;
        }
        return 0;
    }


    /**
     * @param int|string $playerKey
     * @return int 0 if not found
     */
    public function getPlayerMaxReachableRank($playerKey): int
    {
        if ($this->canPlayerWin($playerKey)) return 1;
        $playerRank = $this->getPlayerRank($playerKey);
        if (empty($playerRank)) return 0;
        for ($rank = $playerRank - 1; $rank > 1; $rank--) {
            if (!$this->canPlayerReachRank($playerKey, $rank)) return ($rank + 1);
        }
        return $rank + 1;
    }

    /**
     * @param int|string $playerKey
     * @return int 0 if not found
     */
    public function getPlayerMaxDroppableRank($playerKey): int
    {
        if ($this->canPlayerBeLast($playerKey)) return $this->getPlayerCount();
        $playerRank = $this->getPlayerRank($playerKey);
        if (empty($playerRank)) return 0;
        for ($rank = $playerRank + 1; $rank < $this->getPlayerCount(); $rank++) {
            if (!$this->canPlayerDropToRank($playerKey, $rank)) return ($rank - 1);
        }
        return $rank - 1;
    }

    /**
     * @param int|string $teamKey
     * @return int 0 if not found
     */
    public function getTeamRank($teamKey): int
    {
        $rank = 0;
        foreach ($this->teamRankings as $ranking) {
            $rank++;
            if ($ranking->getEntityKey() === $teamKey) return $rank;
        }
        return 0;
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
        $newCompetition = new static($competition->getPlayers($ranked));
        $newCompetition->setTeamComposition($competition->getTeamComposition());
        return $newCompetition;
    }


}
