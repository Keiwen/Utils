<?php

namespace Keiwen\Utils\Elo;


class EloRace extends AbstractEloMultiplayer
{

    protected $rankedKeys = array();

    /**
     * @param int[]|string[] $rankedKeys ranked keys list, first to last
     * @return int[] competitor key => gain
     */
    public function getGains(array $rankedKeys = array())
    {
        $gains = array();
        foreach($this->eloList as $competitorKey => $competitor) {
            $gains[$competitorKey] = $this->getGain($competitorKey, $rankedKeys);
        }
        return $gains;
    }


    /**
     * get gain for specific competitor
     * @param int|string $competitorKey
     * @param int[]|string[] $rankedKeys ranked keys list, first to last
     * @return int
     */
    public function getGain($competitorKey, array $rankedKeys = array()): int
    {
        $competitor = $this->eloList[$competitorKey];

        // if rankings not forced, try the one stored
        if (empty($rankedKeys)) $rankedKeys = $this->rankedKeys;
        // if no rankings, return 0
        if (empty($rankedKeys)) return 0;

        $result = EloSystem::LOSS;
        $gain = 0;
        $opponentCount = 0;
        foreach ($rankedKeys as $key) {
            if ($key == $competitorKey) {
                $result = EloSystem::WIN;
                continue;
            }
            if (!isset($this->eloList[$key])) continue;
            $opponentCount++;
            $duel = new EloDuel($competitor, $this->eloList[$key]);
            $gain += $duel->getGain($result);
        }
        $gain = $gain / $opponentCount;
        $gain = round($gain);
        $gain = $competitor->getEloSystem()->adjustGainLimit($gain);
        return $gain;
    }


    /**
     * @param int[]|string[] $rankedKeys ranked keys list, first to last
     */
    public function setResult(array $rankedKeys = array())
    {
        $this->rankedKeys = $rankedKeys;

        $this->updateElo();
    }

}
