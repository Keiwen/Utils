<?php

namespace Keiwen\Utils\Competition;

class GamePerformances extends AbstractGame
{

    const RESULT_WON = 'W';
    const RESULT_LOSS = 'L';

    protected $gameRanks = array();

    public function __construct(array $playersSeedList)
    {
        parent::setPlayers($playersSeedList);
    }

    /**
     * After game is played, if performances was set one by one, mark game as ended
     * @return bool
     */
    public function setEndOfGame()
    {
        if ($this->isPlayed()) return false;
        $maxPerf = 0;
        $playerSeedsWithMax = array();
        $this->gameRanks = array();
        foreach ($this->players as $playerSeed => $startingOrder) {
            if (!$this->hasPlayerPerformed($playerSeed)) continue;
            $gamePerf = $this->getPlayerPerformancesSum($playerSeed);
            $this->gameRanks[$playerSeed] = $gamePerf;
            if ($gamePerf >= $maxPerf) {
                if ($gamePerf > $maxPerf) {
                    $maxPerf = $gamePerf;
                    $playerSeedsWithMax = array();
                }
                $playerSeedsWithMax[] = $playerSeed;
            }
        }
        foreach ($this->players as $playerSeed) {
            if (!$this->hasPlayerPerformed($playerSeed)) continue;
            if (in_array($playerSeed, $playerSeedsWithMax)) {
                $this->setPlayerResult($playerSeed, self::RESULT_WON);
            } else {
                $this->setPlayerResult($playerSeed, self::RESULT_LOSS);
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
     * @return array Player seed => performance, sorted from highest to lowest perf
     */
    public function getGameRanks(): array
    {
        return $this->gameRanks;
    }

    /**
     * @param int $playerSeed
     * @return int 0 if player not found
     */
    public function getPlayerGameRank(int $playerSeed): int
    {
        if (!isset($this->gameRanks[$playerSeed])) return 0;
        $rank = 1;
        foreach ($this->gameRanks as $rankSeed => $rankPerf) {
            if ($rankSeed == $playerSeed) return $rank;
            $rank++;
        }
        return 0;
    }



    /**
     * @param int $playerSeed
     * @return int sum of all performance if integer values
     */
    public function getPlayerPerformancesSum(int $playerSeed)
    {
        $sum = 0;
        if (!$this->hasPlayerPerformed($playerSeed)) return $sum;
        foreach ($this->getPlayerPerformances($playerSeed) as $performance) {
            if (is_int($performance)) $sum += $performance;
        }
        return $sum;
    }

    /**
     * @param int $playerSeed
     * @return bool
     */
    public function hasPlayerPerformed(int $playerSeed): bool
    {
        return isset($this->performances[$playerSeed]);
    }


    public function hasPlayerWon(int $playerSeed): bool
    {
        return $this->getPlayerResult($playerSeed) == self::RESULT_WON;
    }

}
