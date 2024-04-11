<?php

namespace Keiwen\Utils\Elo;


class EloDuel
{

    protected $eloRating;
    protected $eloRatingOpponent;


    /**
     * EloDuel constructor.
     * EloSystem should be common
     *
     * @param EloRating $eloRating
     * @param EloRating $eloRatingOpponent
     */
    public function __construct(EloRating $eloRating, EloRating $eloRatingOpponent)
    {
        $this->eloRating = $eloRating;
        $this->eloRatingOpponent = $eloRatingOpponent;
    }

    /**
     * EloSystem should be common
     * @param int $elo
     * @param int $eloOpponent
     * @return static
     */
    public static function buildDuel(int $elo, int $eloOpponent)
    {
        return new static(new EloRating($elo), new EloRating($eloOpponent));
    }


    /**
     * @return int
     */
    public function getDiff()
    {
        $diff = $this->eloRating->getElo() - $this->eloRatingOpponent->getElo();
        return $this->eloRating->getEloSystem()->adjustDiffLimit($diff);
    }


    /**
     * @return float
     */
    public function getWinProbability()
    {
        $diff = $this->getDiff();
        $pow = 10 ** (-$diff / $this->eloRating->getEloSystem()->getMultiplier());
        return 1 / (1 + $pow);
    }


    /**
     * @param string|int $result
     * @param int|null   $opponentGain
     * @param int        $scoreDiff score difference between player to adjust gain (if set)
     * @return int
     */
    public function getGain($result, &$opponentGain = null, int $scoreDiff = 0)
    {
        $resultFactor = $this->eloRating->getEloSystem()->getResultFactor($result);
        $gain = $this->computeGain($resultFactor, false, $scoreDiff);
        if($this->eloRating->getCurrentKFactor() == $this->eloRatingOpponent->getCurrentKFactor()) {
            $opponentGain = -$gain;
        } else {
            $opponentGain = $this->computeGain($resultFactor, true, $scoreDiff);
        }
        return $gain;
    }


    /**
     * @param EloRating $eloRating
     * @param float $resultFactor
     * @param int $scoreDiff score difference between player to adjust gain (if set)
     * @return int
     */
    protected function computeGain(float $resultFactor, bool $opponent = false, int $scoreDiff = 0)
    {
        $eloRating = $opponent ? $this->eloRatingOpponent : $this->eloRating;
        $kFactor = $eloRating->getCurrentKFactor();
        $multiplier = $resultFactor - $this->getWinProbability();
        $scoreFactor = $eloRating->getEloSystem()->getScoreFactor($scoreDiff);
        if($opponent) $multiplier = 1 - $multiplier;
        $floatGain = $kFactor * $scoreFactor * $multiplier;
        $intGain = round($floatGain);
        $limitedGain = $eloRating->getEloSystem()->adjustGainLimit($intGain);
        return $limitedGain;
    }


    /**
     * @param string|int $result
     */
    public function updateElo($result)
    {
        $gain = $this->getGain($result, $oppGain);
        $this->eloRating->gainElo($gain);
        $this->eloRatingOpponent->gainElo($oppGain);
    }


    /**
     * @param bool $opponent true to get opponent's elo
     * @return int
     */
    public function getElo(bool $opponent = false)
    {
        return $opponent ? $this->eloRatingOpponent->getElo() : $this->eloRating->getElo();
    }

}
