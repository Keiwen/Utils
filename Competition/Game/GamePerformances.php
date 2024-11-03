<?php

namespace Keiwen\Utils\Competition\Game;

class GamePerformances extends AbstractGame
{

    const RESULT_WON = 'W';
    const RESULT_LOSS = 'L';

    protected $performanceTypesToSum = array();
    protected $gameRanks = array();
    protected $playerCanSkipGame = true;

    public function __construct(array $playersKeyList, array $performanceTypesToSum, bool $playerCanSkipGame = true)
    {
        parent::setPlayers($playersKeyList);
        $this->playerCanSkipGame = $playerCanSkipGame;
        $this->performanceTypesToSum = $performanceTypesToSum;
    }

    public function getName(): string
    {
        if (!empty($this->name)) return $this->name;
        return $this->getGameNumber();
    }

    public function hasPlayerCanSkipGame(): bool
    {
        return $this->playerCanSkipGame;
    }

    /**
     * @return array
     */
    public function getPerformanceTypesToSum(): array
    {
        return $this->performanceTypesToSum;
    }


    /**
     * After game is played, if performances was set one by one, mark game as ended
     * @return bool true if set
     */
    public function setEndOfGame(): bool
    {
        if ($this->isPlayed()) return false;
        $maxPerf = 0;
        $playerKeysWithMax = array();
        $this->gameRanks = array();
        foreach ($this->playersStartingOrder as $playerKey => $startingOrder) {
            if ($this->playerCanSkipGame && !$this->hasPlayerPerformed($playerKey)) continue;
            $gamePerf = $this->getPlayerPerformancesSum($playerKey);
            $this->gameRanks[$playerKey] = $gamePerf;
            if ($gamePerf >= $maxPerf) {
                if ($gamePerf > $maxPerf) {
                    $maxPerf = $gamePerf;
                    $playerKeysWithMax = array();
                }
                $playerKeysWithMax[] = $playerKey;
            }
        }
        foreach ($this->playersStartingOrder as $playerKey => $startingOrder) {
            if ($this->playerCanSkipGame && !$this->hasPlayerPerformed($playerKey)) continue;
            if (in_array($playerKey, $playerKeysWithMax)) {
                $this->setPlayerResult($playerKey, self::RESULT_WON);
            } else {
                $this->setPlayerResult($playerKey, self::RESULT_LOSS);
            }
        }
        arsort($this->gameRanks);

        $this->played = true;
        if ($this->isAffected()) {
            $this->affectedTo->updateGamesPlayed();
        }
        return true;
    }

    /**
     * @return array Player key => performance, sorted from highest to lowest perf
     */
    public function getGameRanks(): array
    {
        return $this->gameRanks;
    }

    /**
     * @param int|string $playerKey
     * @return int 0 if player not found
     */
    public function getPlayerGameRank($playerKey): int
    {
        if (!isset($this->gameRanks[$playerKey])) return 0;
        $rank = 1;
        foreach ($this->gameRanks as $rankKey => $rankPerf) {
            if ($rankKey == $playerKey) return $rank;
            $rank++;
        }
        return 0;
    }



    /**
     * @param int|string $playerKey
     * @return int sum of all performance if integer values
     */
    public function getPlayerPerformancesSum($playerKey): int
    {
        $sum = 0;
        if (!$this->hasPlayerPerformed($playerKey)) return $sum;
        foreach ($this->getPerformanceTypesToSum() as $type) {
            $performance = $this->getPlayerPerformanceType($playerKey, $type);
            if (empty($performance) || !is_int($performance)) $performance = 0;
            $sum += $performance;
        }
        return $sum;
    }

    /**
     * @param int|string $playerKey
     * @return bool
     */
    public function hasPlayerPerformed($playerKey): bool
    {
        return isset($this->performances[$playerKey]);
    }


    public function hasPlayerWon($playerKey): bool
    {
        return $this->getPlayerResult($playerKey) == self::RESULT_WON;
    }

    /**
     * @param int|string $playerKey
     * @param int $performanceSum
     * @return bool
     */
    public function hasPlayerReachedPerformance($playerKey, int $performanceSum): bool
    {
        return in_array($playerKey, $this->getPlayersKeysThatReachedPerformance($performanceSum));
    }

    /**
     * return keys of all players that reached a minimum performance
     * @param int $performanceSum
     * @return array
     */
    public function getPlayersKeysThatReachedPerformance(int $performanceSum): array
    {
        $playersRanked = $this->getGameRanks();
        $playersKeys = array();
        foreach ($playersRanked as $playerKey => $playerPerf) {
            if ($playerPerf >= $performanceSum) {
                $playersKeys[] = $playerKey;
            } else {
                break;
            }
        }
        return $playersKeys;
    }

}
