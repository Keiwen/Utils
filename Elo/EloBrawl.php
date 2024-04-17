<?php

namespace Keiwen\Utils\Elo;


class EloBrawl extends AbstractEloMultiplayer
{
    protected $winnerKey = null;


    /**
     * @param int|string $winnerKey specify value to check potential gain
     * @return int[] competitor key => gain
     */
    public function getGains($winnerKey = null)
    {
        $gains = array();
        foreach($this->eloList as $competitorKey => $competitor) {
            $gains[$competitorKey] = $this->getGain($competitorKey, $winnerKey);
        }
        return $gains;
    }



    /**
     * get gain for specific competitor
     * @param int|string $competitorKey
     * @param int|string $winnerKey specify value to check potential gain
     * @return int
     */
    public function getGain($competitorKey, $winnerKey = null): int
    {
        $competitor = $this->eloList[$competitorKey];

        // if winner not forced, try the one stored
        if ($winnerKey == null) $winnerKey = $this->winnerKey;
        // if no winner, return 0
        if ($winnerKey == null) return 0;

        if ($winnerKey == $competitorKey) {
            // win over all other
            $gain = 0;
            foreach($this->eloList as $key => $eloRating) {
                if($key == $competitorKey) {
                    continue;
                }
                $duel = new EloDuel($competitor, $eloRating);
                $gain += $duel->getGain(EloSystem::WIN);
            }
            $gain = $gain / ($this->competitorsCount - 1);
            $gain = round($gain);
            $gain = $competitor->getEloSystem()->adjustGainLimit($gain);
            return $gain;
        } else {
            // loose against winner
            $winnerRating = $this->eloList[$winnerKey];
            if (empty($winnerRating)) return 0;
            $duel = new EloDuel($competitor, $winnerRating);
            $gain = $duel->getGain(EloSystem::LOSS);
            $gain = $gain / ($this->competitorsCount - 1);
            $gain = round($gain);
            $gain = $competitor->getEloSystem()->adjustGainLimit($gain);
            return $gain;
        }
    }


    /**
     * @param int|string $winnerKey
     */
    public function setResult($winnerKey = null)
    {
        $this->winnerKey = $winnerKey;

        $this->updateElo();
    }


}
