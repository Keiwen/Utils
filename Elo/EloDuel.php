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
     * @return int
     */
    public function getGain($result, &$opponentGain = null)
    {
        $resultFactor = $this->eloRating->getEloSystem()->getResultFactor($result);
        $gain = $this->computeGain($resultFactor);
        if($this->eloRating->getCurrentKFactor() == $this->eloRatingOpponent->getCurrentKFactor()) {
            $opponentGain = -$gain;
        } else {
            $opponentGain = $this->computeGain($resultFactor, true);
        }
        return $gain;
    }


    /**
     * @param EloRating $eloRating
     * @param float $resultFactor
     * @return int
     */
    protected function computeGain(float $resultFactor, bool $opponent = false)
    {
        $eloRating = $opponent ? $this->eloRatingOpponent : $this->eloRating;
        $kFactor = $eloRating->getCurrentKFactor();
        $multiplier = $resultFactor - $this->getWinProbability();
        if($opponent) $multiplier = 1 - $multiplier;
        $floatGain = $kFactor * $multiplier;
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
